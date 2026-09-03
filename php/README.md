# 智慧校园服务平台（Campus Service Platform）

面向高校师生的校园生活一站式服务平台，提供课程提醒、成绩查询、电费查询、宿舍管理、学术推送、订阅消息、公告动态等功能。包含**公开落地页**、**用户端**与**管理后台**三套界面，共用同一套后端逻辑与数据库。

---

## 1. 技术栈

| 层面 | 说明 |
| --- | --- |
| 语言 | PHP（原生，无框架，基于 PSR 风格 `lib/` 类库） |
| 数据库 | MySQL / MariaDB，通过 `Common/lib/PdoHelper.php` 访问 |
| 前端框架 | OneUI 5.6（基于 Bootstrap 5）后台模板；用户端使用 OneUI quick-website 风格 |
| 前端依赖 | jQuery、layer.js（弹层/消息）、pjax（局部刷新）、marked.js（Markdown 渲染） |
| 验证码 | 腾讯验证码 TCaptcha（滑块）+ 自绘图形验证码兜底（`server-submit/captcha.php`） |
| 邮件 | PHPMailer（SMTP），见 `Common/PHPMailer/`、`Common/mail/` |
| 消息推送 | 微信公众号模板消息（订阅消息），配置见 `nteam_wx_config` / `nteam_wx_templates` |
| 安全防护 | `Common/360safe/`（网站卫士 WAF，在 `Core_brain.php` 中引入） |

---

## 2. 目录结构

```
php/                         应用根目录（Web 站点根）
├── index.php                公开落地页（首页/功能介绍/最新公告）
├── api.php                  对外 API 入口（供小程序/第三方调用）
├── cron_academic_push.php   定时任务：学术推送
├── cron_power_push.php      定时任务：电费推送/提醒
├── cron_subscription_alert.php  定时任务：订阅消息告警
├── cs.sql                   数据库建表与初始数据（前缀 nteam_）
├── Common/                  公共核心（被所有入口 include）
│   ├── Core_brain.php       全局引导：加载配置、DB、Session、$mod、$siteBasePath、WAF
│   ├── Core_Functions.php   通用函数：conf()、authcode()、send_mail()、get_curl() 等
│   ├── Database_Config.php  数据库连接配置（默认 cs/cs/cs）
│   ├── Autoloader.php       类自动加载
│   ├── lib/
│   │   ├── PdoHelper.php    PDO 数据库封装
│   │   ├── Adminclass.php   管理员相关逻辑（登录/权限）
│   │   └── Gets.php         请求/客户端信息获取（IP、城市等）
│   ├── mail/  PHPMailer/    邮件发送实现
│   └── 360safe/             网站卫士 WAF
├── Adminq/                  管理后台（需管理员登录）
│   ├── head.php / foot.php  后台公共头尾
│   ├── login.php            后台登录（图形验证码）
│   ├── index.php            后台首页
│   ├── set.php              站点基础设置
│   ├── smtp.php             邮件(SMTP)配置
│   ├── wx_config.php / wx_templates.php  微信配置与模板
│   ├── announcements.php / announcement_edit.php  公告管理
│   ├── academic_push_config.php / academic_accounts.php  学术推送
│   ├── power_config.php / dormitory_bindings.php  电费/宿舍
│   ├── subscription_manage.php / mail_log.php / operation_logs.php  订阅/邮件/操作日志
│   ├── ulist.php / uset.php / uset_add.php  用户管理
│   ├── cron_config.php      定时任务配置说明
│   ├── api_doc.php          API 接口文档页
│   ├── feature_settings.php / aset.php / alist.php  功能/应用配置
│   ├── Ajax.php             后台所有异步接口（act 分发）
│   └── assets/              后台静态资源（css/js/字体/图片）
├── user/                    用户端（学生使用）
│   ├── head.php / foot.php  用户端公共头尾
│   ├── login.php / register.php / forgot.php / reset_password.php  认证
│   ├── index.php            用户控制台首页
│   ├── announcements.php / announcement.php  公告列表/详情
│   ├── today_schedule.php / schedule.php / grade.php  课程/成绩
│   ├── power_fee.php / power_fee_batch.php / power_statistics.php  电费
│   ├── dormitory_bind.php / dormitory_add.php  宿舍
│   ├── academic_bind.php / academic_push_settings.php  学术绑定与推送
│   ├── subscription.php / push_settings.php  订阅与推送设置
│   ├── profile.php / edit_pwd.php / student_info.php  个人中心
│   ├── send_verify.php / verify.php  验证与激活
│   ├── ajax.php             用户端所有异步接口（act 分发）
│   └── assets/              用户端静态资源
├── funsion/                 业务系统类
│   ├── AcademicSystem.php   学术/成绩系统对接
│   ├── DormitorySystem.php  宿舍/电费系统对接
│   └── feature_settings.php 功能开关配置
├── server-submit/           图形验证码生成脚本（captcha.php）
└── uploads/                 上传文件目录（公告图片、头像等）
    └── announcements/       公告缩略图/大图
```

> 说明：管理后台与用户端各自维护一份 `assets/`，便于独立部署与样式隔离。

---

## 3. 入口与路由

本系统采用**多入口（按文件）**方式，无集中路由：

- 每个页面是一个独立 PHP 文件，顶部 `include('./head.php')` 或 `include('../Common/Core_brain.php')` 完成引导。
- `Core_brain.php` 通过 `$mod` 区分运行上下文：
  - `'admin'` —— 后台页面（位于 `Adminq/`）
  - `'user'`  —— 用户端页面（位于 `user/`）
  - `'index'` —— 公开落地页（`index.php`）
  - `'install'` —— 安装向导（未安装时跳转 `./install`）
- 异步请求统一走 `Adminq/Ajax.php` 与 `user/ajax.php`，通过 `?act=xxx` 分发动作。

---

## 4. 核心机制

### 4.1 全局引导 `Core_brain.php`
每个请求首先执行，负责：
1. 引入 `Autoloader`、数据库连接（`PdoHelper`）、`Adminclass`、`Gets`；
2. 启动 Session（`session_start()`）；
3. 校验安装状态（`nteam_config` 表存在性）；
4. 计算 **站点子目录基准** `$siteBasePath`：
   - 优先按运行时 `SCRIPT_NAME` 推导（兼容 `/csgocs` 等子目录部署）；
   - 回退到 `conf('Url')` 的路径部分；
   - 用于图片、上传等资源正确拼接前缀。
5. 引入 `360safe` 网站卫士做基础安全防护。

### 4.2 站点配置访问 `conf()`
`Common/Core_Functions.php` 提供：
- `conf($name)`：读取 `nteam_config` 中的站点配置（带缓存）；
- `saveSetting($k,$v)` / `saveSettings($k,$v)`：持久化配置；
- `authcode()`：加解密（用于令牌/凭证）；
- `send_mail()` / `send_mail_to_admins()`：邮件发送；
- `get_curl()`：HTTP 请求封装。

### 4.3 鉴权
- **管理员**：`Adminclass` 维护登录态（`$_SESSION['adminUser']` 等），`adminRank==1` 为超级管理员，部分接口（如公告保存）校验该权限。
- **用户**：用户端登录态由 `user/head.php` 通过 Session 维护。
- **验证码**：登录页默认腾讯滑块验证码；滑块异常时切换为图形验证码（`server-submit/captcha.php`），校验时比对 `$_SESSION['captcha']`（大写）。

---

## 5. 数据库

- 建表语句与初始数据见 **`cs.sql`**，表名前缀为 **`nteam_`**。
- 数据库凭据在 **`Common/Database_Config.php`**（默认 `host=127.0.0.1`、`user=cs`、`pwd=cs`、`dbname=cs`、`dbqz=nteam`）。
- 共 26 张表，核心表：

| 表 | 用途 |
| --- | --- |
| `nteam_config` | 站点全局配置（名称、域名、ICP、SMTP、微信、跳转开关等） |
| `nteam_users` | 注册用户/学生账号 |
| `nteam_admin` | 后台管理员 |
| `nteam_announcements` | 公告（缩略图 `thumbnail`、大图 `image`、置顶、发布状态、浏览量） |
| `nteam_dormitory_bindings` | 宿舍绑定关系 |
| `nteam_power_config` / `nteam_power_daily_records` | 电费配置与每日记录 |
| `nteam_academic_accounts` / `nteam_academic_push_*` | 学术账号与推送配置/日志 |
| `nteam_subscriptions` / `nteam_subscription_logs` | 微信订阅与日志 |
| `nteam_wx_config` / `nteam_wx_templates` | 微信公众号配置与消息模板 |
| `nteam_mail_log` / `nteam_push_logs` | 邮件/推送日志 |
| `nteam_log` | 后台操作日志 |
| `nteam_tokens` / `password_resets` / `email_verifications` | 令牌与验证 |
| `dorm_power_config` / `dorm_room` | 宿舍/房间基础数据 |

---

## 6. 配置项（关键）

在管理后台 **系统设置（`set.php`）/ SMTP（`smtp.php`）/ 微信配置（`wx_config.php`）** 中维护，存储于 `nteam_config`：

- `Name` / `Url` / `ICP` / `Keywords` / `Description`：站点基础信息；
- `Mail_Smtp` / `Mail_Port` / `Mail_Name` / `Mail_Pwd` / `Mail_Secure`：邮件发送；
- `Mail_Site_Url`：邮件中链接的站点地址（用于拼接完整 URL）；
- `Mail_Admin_Emails`：管理员通知邮箱；
- `Jump`：QQ/微信访问跳出提示页开关；
- `Index_Fang`：防扒站 JS 开关；
- 微信相关：`nteam_wx_config`、`nteam_wx_templates`。

> 腾讯验证码 AppID：`2047155482`（见各登录页 `data-appid`）。

---

## 7. 部署

### 7.1 环境要求
- PHP ≥ 7.x，需启用扩展：`pdo_mysql`、`gd`（图形验证码）、`curl`、`openssl`、`mbstring`。
- MySQL / MariaDB。
- Web 服务器（Nginx / Apache）指向本目录为站点根。

### 7.2 子目录部署
支持部署在子目录（如 `https://domain/csgocs/`）：
- `$siteBasePath` 会在 `Core_brain.php` 中按 `SCRIPT_NAME` 自动推导，无需手动修改；
- 图片/上传/静态资源路径均基于此基准拼接，确保子目录下资源可访问。

### 7.3 初始化步骤
1. 导入数据库：`source cs.sql;`（或借助数据库管理工具导入）；
2. 配置 `Common/Database_Config.php` 中的数据库连接信息；
3. 将站点部署到 Web 根（或子目录）；
4. 访问首页，进入管理后台 `Adminq/login.php` 完成初始配置（站点名称、SMTP、微信等）。

---

## 8. 定时任务（Cron）

服务器需配置定时执行以下脚本（具体命令可在后台 `cron_config.php` 查看，脚本内已生成示例）：

- `cron_power_push.php`：电费余额不足提醒、每日推送；
- `cron_academic_push.php`：课程提醒、成绩发布通知；
- `cron_subscription_alert.php`：订阅消息告警。

---

## 9. 安全说明

- **WAF**：`Common/360safe/` 在每次请求引导时加载，对常见注入/扫描做基础拦截。
- **图形验证码**：`server-submit/captcha.php` 生成 PNG，存入 `$_SESSION['captcha']`；登录校验一次性比对后销毁。
- **腾讯验证码**：登录默认使用 TCaptcha 滑块，失败/异常时降级为图形验证码。
- **上传**：公告图片上传至 `uploads/announcements/`，由 `Adminq/Ajax.php` 的 `upload_announcement_image` 处理（限制图片类型与大小 ≤5MB）。

---

## 10. 开发约定

- 新增后台页面：在 `Adminq/` 下创建 PHP 文件，`include('./head.php')` 开头，`include('foot.php')` 结尾；异步逻辑统一加到 `Adminq/Ajax.php` 的 `switch($act)`。
- 新增用户端页面：在 `user/` 下创建，遵循同样头尾约定；异步加到 `user/ajax.php`。
- 站点级配置统一走 `conf()` / `saveSetting()`，避免硬编码。
- 涉及子目录部署的资源路径，统一依赖 `$siteBasePath` 拼接，不要写死域名根路径。
- 业务系统对接逻辑放在 `funsion/` 下的系统类中，与页面/接口解耦。

---

> 文档随代码演进维护。涉及具体接口字段，请参考 `Adminq/api_doc.php` 与代码中的 `act` 分支。
