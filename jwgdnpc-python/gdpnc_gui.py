#!/usr/bin/env python3
# -*- coding: utf-8 -*-
# pyright: reportImplicitRelativeImport=false
"""
广东南方职业学院教务系统 —— 课表查询 图形界面（单文件版）
=========================================================

把网页版「全校行政班课表」( /jsxsd/kbcx/kbxx_xzb ) 的查询功能搬到桌面上：

* 启动时弹窗登录（账号 / 密码，可勾选“显示密码”），并显示登录后的 Token；
* 登录后自动拉取所有下拉候选项（学年学期、时间模式、院系、年级、专业、
  周次、星期、节次），行为与网页一致（专业随院系/年级联动、节次随学期/模式联动）；
* 「查询」提交与网页完全相同的表单并解析结果；
* 结果以两种视图展示：
  - 明细：每门课一行（班级 / 星期 / 节次 / 课程 / 教师 / 周次 / 地点），支持关键字过滤；
  - 课表：按班级渲染“节次×星期”的课表网格；
* 支持导出 CSV。

本文件是自包含的：登录、查询、解析逻辑全部内置，不再依赖
gdpnc_jw.py / gdpnc_login.py。

运行：
    python gdpnc_gui.py

依赖：requests、beautifulsoup4、lxml（tkinter 为 Python 标准库）。
"""

from __future__ import annotations

import base64
import csv
import queue
import re
import sys
import threading
import tkinter as tk
from dataclasses import dataclass, field
from tkinter import filedialog, messagebox, ttk
from typing import Callable

import requests
from bs4 import BeautifulSoup, Tag

BASE = "https://jw.gdpnc.cn"
APP_TITLE = "广东南方职业学院 · 全校课表查询"
USER_AGENT = ("Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
              "AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36")

DAYS = ("星期一", "星期二", "星期三", "星期四", "星期五", "星期六", "星期日")

# 网页上「4+4+4」时间模式对应的 kbjcmsid（作为兜底默认值）
DEFAULT_MODE = "4BB5A62B15F845A9AFBD5A9764CAF7C5"


# --------------------------------------------------------------------------- #
# 登录编码（与网页 encodeInp 完全一致，按 JS 的 UTF-16 code unit 处理）
# --------------------------------------------------------------------------- #

_KEY_STR = ("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=")


def _utf16_units(text: str) -> list[int]:
    """把 Python 字符串转成 JS charCodeAt() 会得到的 UTF-16 code unit 序列。"""
    units: list[int] = []
    for ch in text:
        cp = ord(ch)
        if cp <= 0xFFFF:
            units.append(cp)
        else:
            cp -= 0x10000
            units.append(0xD800 + (cp >> 10))
            units.append(0xDC00 + (cp & 0x3FF))
    return units


def encode_inp(text: str) -> str:
    """复刻教务系统页面的 encodeInp()（自定义 Base64 变体）。"""
    units = _utf16_units(text)
    out: list[str] = []
    i = 0
    n = len(units)
    while i < n:
        c1 = units[i] if i < n else 0
        c2 = units[i + 1] if i + 1 < n else None
        c3 = units[i + 2] if i + 2 < n else None
        i += 3

        e1 = c1 >> 2
        e2 = ((c1 & 3) << 4) | ((c2 or 0) >> 4)
        e3 = (((c2 or 0) & 15) << 2) | ((c3 or 0) >> 6)
        e4 = (c3 or 0) & 63
        if c2 is None:
            e3 = e4 = 64
        elif c3 is None:
            e4 = 64
        out.append(_KEY_STR[e1] + _KEY_STR[e2] + _KEY_STR[e3] + _KEY_STR[e4])
    return "".join(out)


def build_encoded(account: str, password: str) -> str:
    """网页登录表单里的 encoded 字段：encodeInp(账号) + '%%%' + encodeInp(密码)。"""
    return f"{encode_inp(account)}%%%{encode_inp(password)}"


# --------------------------------------------------------------------------- #
# 数据模型
# --------------------------------------------------------------------------- #

@dataclass
class Options:
    """查询页上的所有下拉候选项：(value, 显示文本)。"""

    terms: list[tuple[str, str]] = field(default_factory=list)
    modes: list[tuple[str, str]] = field(default_factory=list)
    colleges: list[tuple[str, str]] = field(default_factory=list)
    grades: list[tuple[str, str]] = field(default_factory=list)
    days: list[tuple[str, str]] = field(default_factory=list)
    weeks: list[tuple[str, str]] = field(default_factory=list)

    @staticmethod
    def value_of(items: list[tuple[str, str]], text: str) -> str:
        for value, label in items:
            if label == text:
                return value
        return ""


@dataclass
class Lesson:
    class_name: str
    course: str
    teacher: str
    weeks: str
    room: str
    day: str
    day_index: int
    period: str
    period_index: int
    period_end: int = 0


# --------------------------------------------------------------------------- #
# 教务系统客户端（原 gdpnc_jw.py / gdpnc_login.py 的逻辑）
# --------------------------------------------------------------------------- #

class JwClient:
    def __init__(self) -> None:
        self.session = requests.Session()
        self.session.headers["User-Agent"] = USER_AGENT
        self.options = Options()
        self.account = ""
        self.token = ""

    # -- 登录 -------------------------------------------------------------- #

    def login(self, account: str, password: str) -> tuple[bool, str]:
        self.session = requests.Session()
        self.session.headers["User-Agent"] = USER_AGENT

        try:
            r = self.session.post(
                BASE + "/jsxsd/xk/LoginToXk",
                data={
                    "loginMethod": "LoginToXk",
                    "userAccount": account,
                    "userPassword": "",
                    "yhxy": "on",
                    "encoded": build_encoded(account, password),
                },
                timeout=30,
                allow_redirects=True,
            )
        except requests.RequestException as exc:
            return False, f"网络错误：{exc}"

        ok = ("教务管理系统" in r.text) and ('id="loginForm"' not in r.text)
        if ok:
            self.account = account
            self.token = (self.session.cookies.get("bzb_jsxsd")
                          or self.session.cookies.get("JSESSIONID")
                          or self.session.cookies.get("sl-session")
                          or "")
            return True, "登录成功"

        msg = ""
        m = re.search(r'id="showMsg"[^>]*>(.*?)</', r.text, re.S)
        if m:
            msg = BeautifulSoup(m.group(1), "lxml").get_text(strip=True)
        return False, msg or "账号或密码错误"

    # -- 下拉选项 ---------------------------------------------------------- #

    @staticmethod
    def _options_of(soup: BeautifulSoup, name: str) -> list[tuple[str, str]]:
        sel = soup.find("select", id=name)
        if not isinstance(sel, Tag):
            sel = soup.find("select", attrs={"name": name})
        if not isinstance(sel, Tag):
            return []
        return [(str(o.get("value") or ""), o.get_text(strip=True))
                for o in sel.find_all("option") if isinstance(o, Tag)]

    def fetch_options(self) -> Options:
        r = self.session.get(BASE + "/jsxsd/kbcx/kbxx_xzb", timeout=30)
        r.raise_for_status()
        soup = BeautifulSoup(r.text, "lxml")
        opts = Options(
            terms=self._options_of(soup, "xnxqh"),
            modes=self._options_of(soup, "kbjcmsid"),
            colleges=self._options_of(soup, "skyx"),
            grades=self._options_of(soup, "sknj"),
            days=self._options_of(soup, "skxq1"),
            weeks=self._options_of(soup, "zc1"),
        )
        self.options = opts
        return opts

    def fetch_periods(self, term: str, mode: str) -> list[tuple[str, str]]:
        r = self.session.get(BASE + "/jsxsd/kbxx/initJc",
                             params={"xnxq": term, "kbjcmsid": mode},
                             timeout=30)
        data = r.json()
        if not data.get("success"):
            return []
        n = int(data.get("mtdjs") or 12)
        out: list[tuple[str, str]] = []
        for i in range(1, n + 1):
            value = f"{i:02d}" if i < 10 else str(i)
            out.append((value, value))
        return out

    def fetch_majors(self, college: str, grade: str) -> list[tuple[str, str]]:
        r = self.session.get(BASE + "/jsxsd/kbcx/getZyByAjax",
                             params={"skyx": college, "sknj": grade},
                             timeout=30)
        data = r.json()
        return [(str(d.get("dm", "")), str(d.get("dm_mc", ""))) for d in data]

    # -- 查询 -------------------------------------------------------------- #

    def query(self, **fields: str) -> list[Lesson]:
        data = {
            "xnxqh": fields.get("xnxqh", ""),
            "kbjcmsid": fields.get("kbjcmsid", ""),
            "skyx": fields.get("skyx", ""),
            "sknj": fields.get("sknj", ""),
            "skzy": fields.get("skzy", ""),
            "skbjid": fields.get("skbjid", ""),
            "skbj": fields.get("skbj", ""),
            "zc1": fields.get("zc1", ""),
            "zc2": fields.get("zc2", ""),
            "skxq1": fields.get("skxq1", ""),
            "skxq2": fields.get("skxq2", ""),
            "jc1": fields.get("jc1", ""),
            "jc2": fields.get("jc2", ""),
        }
        r = self.session.post(BASE + "/jsxsd/kbcx/kbxx_xzb_ifr",
                              data=data, timeout=60)
        r.raise_for_status()
        return parse_timetable(r.text)


# --------------------------------------------------------------------------- #
# 课表解析
# --------------------------------------------------------------------------- #

_WEEK_RE = re.compile(r"^[（(].*周[）)]$")


def _split_course_block(div: Tag) -> tuple[str, str, str, str] | None:
    """把一个 div.kbcontent1 拆成 (课程, 教师, 周次, 地点)。"""
    parts = [x.strip() for x in div.get_text("\n").split("\n") if x.strip()]
    if not parts:
        return None

    course = parts[0]
    # 第 2 段通常是班级名，跳过；从第 3 段开始找周次
    body = parts[2:] if len(parts) >= 3 else []

    weeks = ""
    weeks_at = -1
    for i, part in enumerate(body):
        if _WEEK_RE.match(part):
            weeks = part
            weeks_at = i
            break

    if weeks_at >= 0:
        teacher = " ".join(body[:weeks_at]).strip()
        room = " ".join(body[weeks_at + 1:]).strip()
    else:
        teacher = body[0] if body else ""
        room = " ".join(body[1:]).strip()

    return course, teacher, weeks, room


def _merge_ranges(periods: list[int]) -> list[tuple[int, int]]:
    """把节次集合合并成连续区间。"""
    if not periods:
        return []
    periods = sorted(set(periods))
    ranges: list[tuple[int, int]] = []
    start = prev = periods[0]
    for p in periods[1:]:
        if p == prev + 1:
            prev = p
            continue
        ranges.append((start, prev))
        start = prev = p
    ranges.append((start, prev))
    return ranges


def parse_timetable(html: str) -> list[Lesson]:
    soup = BeautifulSoup(html, "lxml")
    table = soup.find("table", id="timetable")
    if not isinstance(table, Tag):
        return []

    rows = [r for r in table.find_all("tr") if isinstance(r, Tag)]
    if len(rows) < 3:
        return []

    # 第 2 行（索引 1）是“班级\节次 + 各节次编号”，用来推断每天节数
    head_cells = [c for c in rows[1].find_all(["td", "th"], recursive=False)
                  if isinstance(c, Tag)]
    labels = [c.get_text(strip=True) for c in head_cells[1:]]
    per_day = len(labels) // len(DAYS) if len(labels) >= len(DAYS) else 12
    if per_day <= 0:
        per_day = 12

    # key -> 节次集合
    groups: dict[tuple[str, str, str, str, str, int], set[int]] = {}

    for row in rows[2:]:
        cells = [c for c in row.find_all(["td", "th"], recursive=False)
                 if isinstance(c, Tag)]
        if len(cells) < 2:
            continue
        class_name = cells[0].get_text(" ", strip=True)
        if not class_name:
            continue

        for col, cell in enumerate(cells[1:1 + len(DAYS) * per_day]):
            day_index = col // per_day + 1
            period = col % per_day + 1
            for div in cell.find_all("div", class_="kbcontent1"):
                if not isinstance(div, Tag):
                    continue
                parsed = _split_course_block(div)
                if not parsed:
                    continue
                course, teacher, weeks, room = parsed
                if not course:
                    continue
                key = (class_name, course, teacher, weeks, room, day_index)
                groups.setdefault(key, set()).add(period)

    lessons: list[Lesson] = []
    for (class_name, course, teacher, weeks, room, day_index), periods in groups.items():
        for start, end in _merge_ranges(list(periods)):
            period_text = (f"{start:02d}" if start == end
                           else f"{start:02d}-{end:02d}")
            lessons.append(Lesson(
                class_name=class_name,
                course=course,
                teacher=teacher,
                weeks=weeks,
                room=room,
                day=DAYS[day_index - 1] if 0 < day_index <= len(DAYS) else "",
                day_index=day_index,
                period=period_text,
                period_index=start,
                period_end=end,
            ))

    lessons.sort(key=lambda x: (x.class_name, x.day_index, x.period_index,
                                x.course))
    return lessons


# --------------------------------------------------------------------------- #
# 主界面
# --------------------------------------------------------------------------- #

class App(ttk.Frame):
    def __init__(self, master: tk.Tk, client: JwClient, account: str):
        super().__init__(master, padding=8)
        self.master = master
        self.client = client
        self.account = account

        self.opts: Options = client.options
        self.period_opts: list[tuple[str, str]] = []
        self.major_opts: list[tuple[str, str]] = []
        self.lessons: list[Lesson] = []
        self._class_names: list[str] = []
        self._q: queue.Queue[tuple[Callable[..., None], object, object]] = (
            queue.Queue())

        self._build_ui()
        self.master.after(80, self._drain_queue)
        self._load_options_async()

    # -- 界面搭建 ---------------------------------------------------------- #

    def _build_ui(self) -> None:
        self.grid(row=0, column=0, sticky="nsew")
        self.master.rowconfigure(0, weight=1)
        self.master.columnconfigure(0, weight=1)
        self.rowconfigure(2, weight=1)
        self.columnconfigure(0, weight=1)

        # ---- 顶部：账号 / Token / 状态 ----
        top = ttk.Frame(self)
        top.grid(row=0, column=0, sticky="ew")
        ttk.Label(top, text=f"已登录：{self.account}", foreground="#0a7").pack(
            side="left")

        if self.client.token:
            ttk.Label(top, text="Token:").pack(side="left", padx=(12, 2))
            self.var_token = tk.StringVar(value=self.client.token)
            ttk.Entry(top, textvariable=self.var_token, width=26,
                      state="readonly").pack(side="left")
            ttk.Button(top, text="复制", width=5,
                       command=self._copy_token).pack(side="left", padx=3)

        self.status = tk.StringVar(value="正在载入查询选项 ...")
        ttk.Label(top, textvariable=self.status, foreground="#888").pack(
            side="right")

        # ---- 查询条件 ----
        box = ttk.LabelFrame(self, text="查询条件", padding=8)
        box.grid(row=1, column=0, sticky="ew", pady=(6, 6))

        self.cb_term = self._combo(box, "学年学期", 0, 0, width=16)
        self.cb_mode = self._combo(box, "时间模式", 0, 2, width=16)
        self.cb_college = self._combo(box, "上课院系", 1, 0, width=16)
        self.cb_grade = self._combo(box, "上课年级", 1, 2, width=16)
        self.cb_major = self._combo(box, "上课专业", 2, 0, width=16)

        self.ent_class = self._entry(box, "上课班级(可选)", 2, 2, width=18)

        self.cb_zc1 = self._combo(box, "周次", 3, 0, width=8, inline=True)
        ttk.Label(box, text="至").grid(row=3, column=1, sticky="w")
        self.cb_zc2 = self._combo(box, "", 3, 1, width=8, inline=True,
                                  label_pad=(24, 0))
        self.cb_day1 = self._combo(box, "星期", 3, 2, width=8, inline=True)
        ttk.Label(box, text="至").grid(row=3, column=3, sticky="w")
        self.cb_day2 = self._combo(box, "", 3, 3, width=8, inline=True,
                                   label_pad=(24, 0))
        self.cb_per1 = self._combo(box, "节次", 4, 0, width=8, inline=True)
        ttk.Label(box, text="至").grid(row=4, column=1, sticky="w")
        self.cb_per2 = self._combo(box, "", 4, 1, width=8, inline=True,
                                   label_pad=(24, 0))

        btns = ttk.Frame(box)
        btns.grid(row=4, column=2, columnspan=2, sticky="e")
        self.btn_query = ttk.Button(btns, text="查询", command=self.on_query)
        self.btn_query.pack(side="left", padx=4)
        ttk.Button(btns, text="导出 CSV", command=self.on_export).pack(
            side="left", padx=4)

        # 联动
        self.cb_term.bind("<<ComboboxSelected>>", lambda e: self._reload_periods())
        self.cb_mode.bind("<<ComboboxSelected>>", lambda e: self._reload_periods())
        self.cb_college.bind("<<ComboboxSelected>>", lambda e: self._reload_majors())
        self.cb_grade.bind("<<ComboboxSelected>>", lambda e: self._reload_majors())

        # ---- 结果区 ----
        nb = ttk.Notebook(self)
        nb.grid(row=2, column=0, sticky="nsew")

        # 明细页
        detail = ttk.Frame(nb)
        nb.add(detail, text="明细")
        detail.rowconfigure(1, weight=1)
        detail.columnconfigure(0, weight=1)

        filt = ttk.Frame(detail)
        filt.grid(row=0, column=0, sticky="ew", pady=(2, 4))
        ttk.Label(filt, text="过滤:").pack(side="left")
        self.var_filter = tk.StringVar()
        self.var_filter.trace_add("write", lambda *_: self._render_detail())
        ttk.Entry(filt, textvariable=self.var_filter, width=30).pack(
            side="left", padx=4)
        ttk.Label(filt, text="（按班级/课程/教师/地点即时筛选）",
                  foreground="#888").pack(side="left")

        cols = ("班级", "星期", "节次", "课程", "教师", "周次", "地点")
        self.tree = ttk.Treeview(detail, columns=cols, show="headings")
        widths = {"班级": 200, "星期": 70, "节次": 60, "课程": 240,
                  "教师": 90, "周次": 110, "地点": 200}
        for c in cols:
            self.tree.heading(c, text=c, command=lambda cc=c: self._sort_by(cc))
            self.tree.column(c, width=widths.get(c, 100), anchor="w")
        vs = ttk.Scrollbar(detail, orient="vertical", command=self.tree.yview)
        hs = ttk.Scrollbar(detail, orient="horizontal", command=self.tree.xview)
        self.tree.configure(yscrollcommand=vs.set, xscrollcommand=hs.set)
        self.tree.grid(row=1, column=0, sticky="nsew")
        vs.grid(row=1, column=1, sticky="ns")
        hs.grid(row=2, column=0, sticky="ew")

        # 课表页
        grid_tab = ttk.Frame(nb)
        nb.add(grid_tab, text="课表")
        grid_tab.rowconfigure(1, weight=1)
        grid_tab.columnconfigure(0, weight=1)

        head = ttk.Frame(grid_tab)
        head.grid(row=0, column=0, sticky="ew", pady=(2, 4))
        ttk.Label(head, text="选择班级:").pack(side="left")
        self.cb_class = ttk.Combobox(head, state="readonly", width=28)
        self.cb_class.pack(side="left", padx=4)
        self.cb_class.bind("<<ComboboxSelected>>", lambda e: self._render_grid())

        gcols = ("节次",) + tuple(DAYS)
        self.grid_tree = ttk.Treeview(grid_tab, columns=gcols, show="headings")
        self.grid_tree.heading("节次", text="节次")
        self.grid_tree.column("节次", width=52, anchor="center")
        for d in DAYS:
            self.grid_tree.heading(d, text=d)
            self.grid_tree.column(d, width=170, anchor="w")
        gvs = ttk.Scrollbar(grid_tab, orient="vertical",
                            command=self.grid_tree.yview)
        ghs = ttk.Scrollbar(grid_tab, orient="horizontal",
                            command=self.grid_tree.xview)
        self.grid_tree.configure(yscrollcommand=gvs.set, xscrollcommand=ghs.set)
        self.grid_tree.grid(row=1, column=0, sticky="nsew")
        gvs.grid(row=1, column=1, sticky="ns")
        ghs.grid(row=2, column=0, sticky="ew")

    def _combo(self, parent, label: str, row: int, col: int, width=16,
               inline: bool = False, label_pad=(4, 4)) -> ttk.Combobox:
        if label:
            ttk.Label(parent, text=label).grid(
                row=row, column=col, sticky="e", padx=(0, 4), pady=3)
        cb = ttk.Combobox(parent, state="readonly", width=width)
        cb.grid(row=row, column=col + 1, sticky="w",
                padx=label_pad if inline else (0, 12), pady=3)
        return cb

    def _entry(self, parent, label: str, row: int, col: int,
               width=16) -> ttk.Entry:
        ttk.Label(parent, text=label).grid(row=row, column=col, sticky="e",
                                           padx=(0, 4), pady=3)
        ent = ttk.Entry(parent, width=width)
        ent.grid(row=row, column=col + 1, sticky="w", padx=(0, 12), pady=3)
        return ent

    def _copy_token(self) -> None:
        self.clipboard_clear()
        self.clipboard_append(self.var_token.get())
        self.status.set("Token 已复制到剪贴板")

    # -- 异步执行 ---------------------------------------------------------- #

    def _run_async(self, work: Callable[[], object],
                   done: Callable[..., None],
                   busy: str = "处理中 ...") -> None:
        """在子线程执行 work，结果通过线程安全队列交回主线程。

        注意：Tkinter 不是线程安全的，子线程 **不能** 直接调用 widget 方法，
        因此只往队列里放结果，由主线程轮询处理。
        """
        self.status.set(busy)
        self.btn_query.state(["disabled"])

        def worker():
            try:
                result, err = work(), None
            except Exception as exc:  # noqa: BLE001
                result, err = None, exc
            self._q.put((done, result, err))

        threading.Thread(target=worker, daemon=True).start()

    def _drain_queue(self) -> None:
        try:
            while True:
                try:
                    done, result, err = self._q.get_nowait()
                except queue.Empty:
                    break
                self.btn_query.state(["!disabled"])
                try:
                    done(result, err)
                except Exception as exc:  # noqa: BLE001
                    self.status.set(f"界面刷新出错：{exc}")
        finally:
            self.master.after(80, self._drain_queue)

    # -- 选项加载 ---------------------------------------------------------- #

    def _load_options_async(self) -> None:
        def done(opts, err):
            if err:
                self._error("载入查询选项失败", err)
                return
            self.opts = opts  # 关键：用新抓到的选项替换初始空对象
            self._fill(self.cb_term, opts.terms)
            self._fill(self.cb_mode, opts.modes)
            self._fill(self.cb_college, opts.colleges)
            self._fill(self.cb_grade, opts.grades)
            self.cb_day1["values"] = ["（不限）"] + [t for _, t in opts.days]
            self.cb_day2["values"] = ["（不限）"] + [t for _, t in opts.days]
            self.cb_zc1["values"] = ["（不限）"] + [t for _, t in opts.weeks]
            self.cb_zc2["values"] = ["（不限）"] + [t for _, t in opts.weeks]
            self.cb_day1.current(0)
            self.cb_day2.current(0)
            self.cb_zc1.current(0)
            self.cb_zc2.current(0)
            self.status.set("查询选项已就绪")
            self._reload_periods()

        self._run_async(self.client.fetch_options, done, "正在载入查询选项 ...")

    def _reload_periods(self) -> None:
        term = self.opts.value_of(self.opts.terms, self.cb_term.get())
        mode = self.opts.value_of(self.opts.modes, self.cb_mode.get())

        def done(periods, err):
            if err:
                return
            self.period_opts = periods
            labels = ["（不限）"] + [t for _, t in periods]
            self.cb_per1["values"] = labels
            self.cb_per2["values"] = labels
            self.cb_per1.current(0)
            self.cb_per2.current(0)

        self._run_async(lambda: self.client.fetch_periods(term, mode), done,
                        "正在载入节次 ...")

    def _reload_majors(self) -> None:
        college = self.opts.value_of(self.opts.colleges, self.cb_college.get())
        grade = self.opts.value_of(self.opts.grades, self.cb_grade.get())
        if not college or not grade:
            self.major_opts = []
            self.cb_major["values"] = []
            self.cb_major.set("")
            return

        def done(majors, err):
            if err:
                return
            self.major_opts = majors
            self.cb_major["values"] = [t for _, t in majors]
            if majors:
                self.cb_major.current(0)

        self._run_async(lambda: self.client.fetch_majors(college, grade), done,
                        "正在载入专业 ...")

    @staticmethod
    def _fill(combo: ttk.Combobox, items: list[tuple[str, str]]) -> None:
        combo["values"] = [t for _, t in items]
        if items:
            combo.current(0)

    # -- 查询 -------------------------------------------------------------- #

    def _current_fields(self) -> dict[str, str]:
        o = self.opts

        def val(cb, items, allow_all=True):
            text = cb.get()
            if allow_all and text in ("", "（不限）"):
                return ""
            return o.value_of(items, text)

        return {
            "xnxqh": o.value_of(o.terms, self.cb_term.get()),
            "kbjcmsid": o.value_of(o.modes, self.cb_mode.get()),
            "skyx": o.value_of(o.colleges, self.cb_college.get()),
            "sknj": o.value_of(o.grades, self.cb_grade.get()),
            "skzy": o.value_of(self.major_opts, self.cb_major.get()),
            "skbj": self.ent_class.get().strip(),
            "zc1": val(self.cb_zc1, o.weeks),
            "zc2": val(self.cb_zc2, o.weeks),
            "skxq1": val(self.cb_day1, o.days),
            "skxq2": val(self.cb_day2, o.days),
            "jc1": val(self.cb_per1, self.period_opts),
            "jc2": val(self.cb_per2, self.period_opts),
        }

    def on_query(self) -> None:
        fields = self._current_fields()
        if not fields["kbjcmsid"]:
            messagebox.showwarning(APP_TITLE, "请选择时间模式！")
            return

        def done(lessons, err):
            if err:
                self._error("查询失败", err)
                return
            self.lessons = lessons
            self.status.set(f"查询完成，共 {len(lessons)} 条课程安排")
            self._render_detail()
            self._refresh_class_list()

        self._run_async(lambda: self.client.query(**fields), done, "正在查询 ...")

    # -- 渲染 -------------------------------------------------------------- #

    def _filtered(self) -> list[Lesson]:
        kw = self.var_filter.get().strip().lower()
        if not kw:
            return self.lessons
        out = []
        for ls in self.lessons:
            blob = " ".join([ls.class_name, ls.course, ls.teacher,
                             ls.room, ls.weeks, ls.day]).lower()
            if kw in blob:
                out.append(ls)
        return out

    def _render_detail(self) -> None:
        self.tree.delete(*self.tree.get_children())
        for ls in self._filtered():
            self.tree.insert("", "end", values=(
                ls.class_name, ls.day, ls.period, ls.course,
                ls.teacher, ls.weeks, ls.room))

    def _sort_by(self, col: str) -> None:
        idx = {"班级": 0, "星期": 1, "节次": 2, "课程": 3,
               "教师": 4, "周次": 5, "地点": 6}[col]
        self.lessons.sort(key=lambda ls: (
            [ls.class_name, ls.day_index, ls.period_index, ls.course,
             ls.teacher, ls.weeks, ls.room][idx]))
        self._render_detail()

    def _refresh_class_list(self) -> None:
        names = sorted({ls.class_name for ls in self.lessons})
        self._class_names = names
        self.cb_class["values"] = names
        if names:
            self.cb_class.current(0)
            self._render_grid()
        else:
            self.cb_class.set("")
            self.grid_tree.delete(*self.grid_tree.get_children())

    def _render_grid(self) -> None:
        self.grid_tree.delete(*self.grid_tree.get_children())
        cls = self.cb_class.get()
        if not cls:
            return

        maxp = max((ls.period_end or ls.period_index
                    for ls in self.lessons if ls.class_name == cls), default=12)
        maxp = max(maxp, 1)

        cell: dict[tuple[int, str], list[str]] = {}
        for ls in self.lessons:
            if ls.class_name != cls:
                continue
            end = ls.period_end or ls.period_index
            for p in range(ls.period_index, end + 1):
                cell.setdefault((p, ls.day), []).append(ls.course)

        for p in range(1, maxp + 1):
            values = [f"{p:02d}"]
            for day in DAYS:
                courses = cell.get((p, day), [])
                values.append(" / ".join(dict.fromkeys(courses)))
            self.grid_tree.insert("", "end", values=values)

    # -- 导出 -------------------------------------------------------------- #

    def on_export(self) -> None:
        rows = self._filtered()
        if not rows:
            messagebox.showinfo(APP_TITLE, "没有可导出的数据，请先查询。")
            return
        path = filedialog.asksaveasfilename(
            title="导出 CSV", defaultextension=".csv",
            filetypes=[("CSV 文件", "*.csv")], initialfile="课表.csv")
        if not path:
            return
        try:
            with open(path, "w", newline="", encoding="utf-8-sig") as fh:
                w = csv.writer(fh)
                w.writerow(["班级", "星期", "节次", "课程", "教师", "周次", "地点"])
                for ls in rows:
                    w.writerow([ls.class_name, ls.day, ls.period, ls.course,
                                ls.teacher, ls.weeks, ls.room])
            messagebox.showinfo(APP_TITLE, f"已导出 {len(rows)} 条到：\n{path}")
        except OSError as exc:
            self._error("导出失败", exc)

    def _error(self, title: str, err: Exception) -> None:
        self.status.set(f"{title}: {err}")
        messagebox.showerror(APP_TITLE, f"{title}：\n{err}")


# --------------------------------------------------------------------------- #
# 登录对话框
# --------------------------------------------------------------------------- #

class LoginDialog(tk.Toplevel):
    def __init__(self, master: tk.Tk):
        super().__init__(master)
        self.title("登录 · " + APP_TITLE)
        self.resizable(False, False)
        self.result: tuple[str, str] | None = None

        frm = ttk.Frame(self, padding=16)
        frm.pack(fill="both", expand=True)

        ttk.Label(frm, text="账号（学号）:").grid(row=0, column=0, sticky="e",
                                                 pady=6)
        self.ent_user = ttk.Entry(frm, width=26)
        self.ent_user.grid(row=0, column=1, pady=6)

        ttk.Label(frm, text="密码:").grid(row=1, column=0, sticky="e", pady=6)
        self.ent_pwd = ttk.Entry(frm, width=26, show="●")
        self.ent_pwd.grid(row=1, column=1, pady=6)

        self.var_show = tk.BooleanVar(value=False)
        ttk.Checkbutton(frm, text="显示密码", variable=self.var_show,
                        command=self._toggle).grid(row=2, column=1, sticky="w")

        btns = ttk.Frame(frm)
        btns.grid(row=3, column=0, columnspan=2, pady=(12, 0), sticky="e")
        ttk.Button(btns, text="登录", command=self._ok).pack(side="left", padx=4)
        ttk.Button(btns, text="退出", command=self._cancel).pack(side="left")

        self.bind("<Return>", lambda e: self._ok())
        self.bind("<Escape>", lambda e: self._cancel())
        self.protocol("WM_DELETE_WINDOW", self._cancel)

        # 注意：父窗口 root 处于 withdraw 状态时，若把对话框设为 transient，
        # 在 Windows 上它会被连带隐藏（表现为“运行后没有界面”）。
        # 因此这里不调用 transient，而是显式显示 + 置顶。
        self.update_idletasks()
        self._center()
        self.deiconify()
        self.attributes("-topmost", True)
        self.lift()
        self.ent_user.focus_set()
        self.after(400, lambda: self.attributes("-topmost", False))
        self.grab_set()

    def _center(self) -> None:
        self.update_idletasks()
        w, h = self.winfo_reqwidth(), self.winfo_reqheight()
        x = (self.winfo_screenwidth() - w) // 2
        y = (self.winfo_screenheight() - h) // 3
        self.geometry(f"+{x}+{y}")

    def _toggle(self) -> None:
        self.ent_pwd.configure(show="" if self.var_show.get() else "●")

    def _ok(self) -> None:
        user = self.ent_user.get().strip()
        pwd = self.ent_pwd.get()
        if not user or not pwd:
            messagebox.showwarning(APP_TITLE, "账号和密码都不能为空。", parent=self)
            return
        self.result = (user, pwd)
        self.destroy()

    def _cancel(self) -> None:
        self.result = None
        self.destroy()


# --------------------------------------------------------------------------- #
# 启动
# --------------------------------------------------------------------------- #

def _prompt_login(root: tk.Tk) -> tuple[str, str] | None:
    """弹出登录对话框，返回 (账号, 密码)；用户取消则返回 None。"""
    dialog = LoginDialog(root)
    dialog.update()
    if not dialog.winfo_viewable():  # 兜底：确保真的显示出来
        dialog.deiconify()
        dialog.lift()
    root.wait_window(dialog)
    return dialog.result


def _do_login(root: tk.Tk, client: JwClient,
              account: str, password: str) -> tuple[bool, str]:
    """带进度提示执行登录（网络请求在子线程），返回 (是否成功, 消息)。"""
    result: dict[str, object] = {}

    wait = tk.Toplevel(root)
    wait.title("请稍候")
    ttk.Label(wait, text="正在登录 ...", padding=24).pack()
    # 不能 transient 到 withdraw 的 root，否则窗口不显示
    wait.update_idletasks()
    wait.geometry(
        f"+{(wait.winfo_screenwidth() - wait.winfo_reqwidth()) // 2}"
        f"+{(wait.winfo_screenheight() - wait.winfo_reqheight()) // 3}")
    wait.deiconify()
    wait.attributes("-topmost", True)
    wait.lift()
    wait.grab_set()

    login_q: queue.Queue[tuple[bool, str]] = queue.Queue()

    def worker():
        try:
            ok, msg = client.login(account, password)
        except Exception as exc:  # noqa: BLE001
            ok, msg = False, f"网络错误：{exc}"
        login_q.put((ok, msg))

    def poll():
        try:
            ok, msg = login_q.get_nowait()
        except queue.Empty:
            root.after(100, poll)
            return
        result["ok"], result["msg"] = ok, msg
        wait.destroy()

    threading.Thread(target=worker, daemon=True).start()
    root.after(100, poll)
    root.wait_window(wait)

    return bool(result.get("ok")), str(result.get("msg", ""))


def main() -> int:
    root = tk.Tk()
    root.withdraw()
    root.title(APP_TITLE)

    try:
        ttk.Style().theme_use("vista")
    except tk.TclError:
        pass

    creds = _prompt_login(root)
    if not creds:
        root.destroy()
        return 0

    account, password = creds
    client = JwClient()
    ok, msg = _do_login(root, client, account, password)
    if not ok:
        messagebox.showerror(APP_TITLE, f"登录失败：\n{msg}")
        root.destroy()
        return 0

    root.deiconify()
    root.geometry("1080x680")
    root.update()
    App(root, client, account)
    root.mainloop()
    return 0


if __name__ == "__main__":
    sys.exit(main())
