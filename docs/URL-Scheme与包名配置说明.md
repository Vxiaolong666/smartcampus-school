# App URL Scheme 与包名配置说明

> 适用范围：uniapp App（智慧校园）+ 网页端「返回 App / 下载更新」入口
> 相关场景：第三方账号绑定/登录完成后，浏览器里点「返回 App」唤起 App；唤起失败时引导去官网下载。
> 最后更新：2026-09-22

---

## 一、一句话概览

网页要唤起 App，只有三条路：

| 方式 | 依赖 | 本项目是否可用 | 说明 |
|---|---|---|---|
| ① App 内 web-view | HTML5+ (`plus`) 运行时 | ✅ 已实现 | 最可靠，App 自己关掉 web-view，无需任何配置 |
| ② 按**包名**唤起（Android） | Android 包名 | ✅ 已实现 | 浏览器 `intent://` 直接拉起 App，**无需重新打包** |
| ③ 按 **URL Scheme** 唤起 | App 注册 scheme | ✅ 已注册 `smartcampus` | Android/iOS 通用；**必须重新云打包才生效** |

网页端会自动判断环境并依次尝试，**任何失败都会给出明确反馈**，不会出现「点了没反应」。

---

## 二、当前配置（本项目实际值）

| 项目 | 值 | 配置位置 |
|---|---|---|
| App 名称 / AppID | 智慧校园 / `__UNI__4DF7010` | `uniapp/智慧校园/manifest.json` 顶层 |
| **Android 包名** | `com.gdnpc.smartcampus.school` | 打包侧：HBuilderX 云打包界面 / manifest；网页侧：`$DEFAULT_APP_PACKAGE` |
| **URL Scheme** | `smartcampus` | `manifest.json`（Android + iOS）+ `$DEFAULT_APP_SCHEME` |
| 官网下载页 | `{官网地址}/download.php` | 后台「官网配置 → 网站地址」，回退系统「网站域名」 |
| 版本直链兜底 | `nteam_app_versions.download_url` | 后台「版本管理」 |

---

## 三、涉及文件

| 文件 | 作用 |
|---|---|
| `uniapp/智慧校园/manifest.json` | 注册 URL Scheme（Android `schemes` / iOS `urltypes`） |
| `php/user/oauth_bind_result.php` | 结果页：唤起 App、失败反馈、跳官网下载 |
| `php/Common/home_config.php` | 读取官网动态配置（`homeCfg('site_url')`） |
| `php/download.php` | 官网下载页（内容来自后台「官网配置 → 下载方式」） |
| `php/Adminq/home_config.php` | 后台「官网配置」：网站地址 / 下载方式 |
| `php/Adminq/version_manage.php` | 后台「版本管理」：各平台下载地址（兜底用） |

---

## 四、工作原理（`oauth_bind_result.php`）

点「返回 App」后按顺序尝试：

| 顺序 | 运行环境 | 处理方式 |
|---|---|---|
| 1 | uniapp App 内 web-view | 取父 webview 执行 `uni.navigateBack()`；失败则 `plus.webview.close(父页面)` |
| 2 | 微信小程序 web-view | `wx.miniProgram.navigateBack()` |
| 3 | 微信 / QQ 内置浏览器 | 系统禁止唤起外部 App → 直接给指引（右上角「···」→ 在浏览器打开） |
| 4 | Android 浏览器 | `intent://…package=<包名>…` 直接唤起（首次按包名，重试交替用 scheme） |
| 5 | iOS 浏览器 | `location.href = smartcampus://` |
| 6 | 无可用配置 | 直接给出反馈与下载入口 |

**失败判定**：点击后 2.5 秒内页面未进入后台（未触发 `visibilitychange(hidden)` / `pagehide`）即视为唤起失败 → 显示反馈区（含「重试打开 App」）。

**自动前往官网**：Android `intent` 唤起失败时，浏览器会带 `?launch_failed=1` 回到本页，页面会**自动**跳转官网下载页（先提示 1.2 秒），并保留「前往官网下载 / 更新」按钮兜底。

---

## 五、配置项详解

### 5.1 App 端：`uniapp/智慧校园/manifest.json`

```json
"app-plus": {
    "distribute": {
        "android": {
            /* 可选：Android 包名（也可在 HBuilderX 云打包界面填写） */
            "packagename": "com.gdnpc.smartcampus.school",
            /* URL Scheme：逗号分隔字符串，多个用英文逗号分隔；小写、无特殊字符 */
            "schemes": "smartcampus"
        },
        "ios": {
            /* iOS Bundle ID（字段名是 appid，不是 bundleid） */
            "appid": "com.gdnpc.smartcampus.school",
            /* URL Scheme：与 Android 使用同一个 scheme */
            "urltypes": "smartcampus"
        }
    }
}
```

> 说明：官方「iOS 设置 UrlSchemes」文档中 `urltypes` 为**逗号分隔字符串**；HBuilderX 部分版本可视化界面会写成对象数组形式，两种写法效果相同，**以可视化界面生成的结果为准**，不必手动改回。

### 5.2 网页端：`php/user/oauth_bind_result.php`

```php
// Android 包名：填上即可「按包名」唤起 App（无需重新打包）
$DEFAULT_APP_PACKAGE = 'com.gdnpc.smartcampus.school';
// App URL Scheme：需与 manifest.json 中的 scheme 完全一致
$DEFAULT_APP_SCHEME  = 'smartcampus';
```

支持用 URL 参数临时覆盖（便于换包名/新 scheme 灰度验证）：

```
user/oauth_bind_result.php?type=login&ok=1&msg=登录成功&pkg=com.new.package&scheme=newscheme
```

### 5.3 官网下载页地址（系统动态配置）

取址优先级（`oauth_bind_result.php` 顶部）：

1. 后台「官网配置 → **网站地址**」→ `nteam_home_config.site_url`
2. 回退后台「**网站域名**」→ `nteam_config.Url`
3. 拼接 `/download.php`（官网下载页），例如 `https://mc.xzwmc.com/csgocs/download.php`
4. 以上都为空时，才回退「版本管理」里的直链

> 改地址**不需要动代码**：后台「官网配置 → 网站地址」「系统设置 → 网站域名」改完即生效。
> 想直接跳官网首页（而不是下载页），把 `$downloadUrl` 那行的 `'/download.php'` 去掉即可。

---

## 六、更换 Android 包名（完整步骤）

> 包名 = Android 应用的唯一标识。**已上架的 App 不要随意更换**（换包名 = 新应用，老用户无法覆盖安装、商店需重新提交）。

### 1）打包侧

- 方式 A（推荐）：HBuilderX →「发行 → 原生 App-云打包」→ Android → 填写**包名**、证书、别名、密码；
- 方式 B：直接在 `manifest.json` 写 `app-plus.distribute.android.packagename`。

### 2）网页侧（一次改动，立即生效）

```php
$DEFAULT_APP_PACKAGE = 'com.你的新包名';
```

### 3）验证包名（自动提取，无需装工具）

如果手上有 APK（例如 `uniapp/智慧校园/unpackage/cache/apk/__UNI__4DF7010_cm.apk`），可用下面脚本从 `AndroidManifest.xml` 里读出包名：

```js
// 用法：node apk-scan.js path/to/app.apk
const fs = require('fs');
const buf = fs.readFileSync(process.argv[2]);
const counts = {};
function scan(text) {
    const re = /(?:com|cn|net|io|org)\.[a-zA-Z][a-zA-Z0-9_]*(?:\.[a-zA-Z][a-zA-Z0-9_]*){1,4}/g;
    let m;
    while ((m = re.exec(text)) !== null) counts[m[0]] = (counts[m[0]] || 0) + 1;
}
scan(buf.toString('utf8'));      // UTF-8 字符串池
scan(buf.toString('utf16le'));   // UTF-16 字符串池（AndroidManifest.xml 多为这种）
Object.keys(counts)
    .sort((a, b) => counts[b] - counts[a])
    .slice(0, 15)
    .forEach(k => console.log(counts[k], k));
```

出现次数最多的、形如 `com.公司.应用` 的那个基本就是包名（本项目实测输出 `com.gdnpc.smartcampus.school`）。

### 4）注意

- 包名不影响 scheme；**换包名不必换 scheme**；
- 包名与证书（keystore/别名/密码）必须匹配，否则无法覆盖安装旧版本；
- 网页端「按包名唤起」对**老版本** App 同样有效（老版本没注册 scheme 也能被唤起）。

---

## 七、更换 / 新增 URL Scheme

### 1）三处同步修改

| 位置 | 字段 |
|---|---|
| `manifest.json` | `app-plus.distribute.android.schemes` |
| `manifest.json` | `app-plus.distribute.ios.urltypes` |
| `php/user/oauth_bind_result.php` | `$DEFAULT_APP_SCHEME` |

### 2）命名规范（官方要求）

- 只用**小写字母**，不要特殊字符、中文、空格；
- 建议包含品牌词，避免与其它 App 冲突（本项目用 `smartcampus`）；
- 多个 scheme 用英文逗号分隔，如 `"smartcampus,zhxy"`；
- **不要**用 `http` / `https` / 已被大厂占用的常见词（`weixin`、`alipay`、`taobao` 等）。

### 3）生效条件

- **必须重新云打包**（Android 写入 AndroidManifest 的 intent-filter，iOS 写入 Info.plist 的 `CFBundleURLTypes`）；
- iOS 若已上架，改 scheme 后需**重新提审**；
- 真机调试请用**自定义调试基座**（HBuilderX 标准基座只有 `hbuilder://`）。

### 4）验证

```bash
# Android：本机装了 App 时，用浏览器打开
smartcampus://

# 或命令行（adb）
adb shell am start -a android.intent.action.VIEW -d "smartcampus://"
```

---

## 八、生效条件与调试速查

| 事项 | 结论 |
|---|---|
| Android「按包名唤起」 | **改网页配置即生效**，不依赖重新打包 |
| URL Scheme（Android/iOS） | **必须重新云打包** |
| 真机运行（HBuilderX 标准基座） | 只支持 `hbuilder://`，**scheme 无效**，需自定义调试基座 |
| 微信内置浏览器 | 禁止唤起 App，只能提示「在浏览器打开」 |
| 微信小程序 web-view | 走 `wx.miniProgram.navigateBack()`，无需 scheme |
| 网站若是 https | 不影响 scheme；iOS Safari 对非 http(s) 跳转可能弹一次确认框 |

---

## 九、常见问题排查（FAQ）

| 现象 | 原因 / 处理 |
|---|---|
| 点「返回 App」无反应 | ① 检查是否在普通浏览器（微信/QQ 内会被拦，页面已有提示）；② Android 确认 `$DEFAULT_APP_PACKAGE` 与线上包名一致；③ iOS 确认已注册 scheme 并重新打包 |
| Android 唤起失败后自动跳官网 | 正常逻辑：`intent` 回落带 `?launch_failed=1`，说明未安装或无法唤起 |
| iOS 点不动 | 未注册 scheme（`urltypes` 为空）或未重新打包；页面会给出 iOS 专属提示 |
| 换了包名后失效 | 同步修改 `$DEFAULT_APP_PACKAGE`（或临时用 `?pkg=` 验证） |
| 修改 scheme 后无变化 | 必须**重新云打包**；真机调试要用自定义基座 |
| 唤起后 App 没打开到预期页面 | 目前只做「回到 App」；如需深链跳页，可在 App 的 `App.vue → onShow` 里读取 `plus.runtime.arguments` 自行路由 |
| 多个 App 抢同一 scheme | 换成更具品牌特征的 scheme（如 `gdnpcsmartcampus`） |
| 官网下载按钮不显示 | 检查后台「官网配置 → 网站地址」或「系统设置 → 网站域名」是否已填写 |

---

## 十、调试参数一览

| 参数 | 作用 |
|---|---|
| `?pkg=com.xxx.yyy` | 覆盖 Android 包名（临时验证用） |
| `?scheme=xxx` | 覆盖 URL Scheme（临时验证用） |
| `?launch_failed=1` | 由 Android `intent` 回落时自动带上；进页即显示反馈并自动跳官网下载页 |
| `?type=login` / `?ok=1` / `?msg=xxx` | 结果页场景与文案（由 `oauth_callback.php` 跳转时带上） |

---

## 十一、改动清单（本次新增）

1. `manifest.json`：注册 Android `schemes`、iOS `urltypes`（均为 `smartcampus`）；
2. `php/user/oauth_bind_result.php`：
   - 按环境自动选择返回方式（plus / 小程序 / intent 按包名 / scheme / 指引）；
   - 唤起失败检测（2.5s 未离页）+ 反馈区 + 「重试打开 App」；
   - 「前往官网下载 / 更新」按钮，跳官网下载页（地址动态配置）；
   - `?launch_failed=1` 时自动跳官网下载页；
   - 顶部两个常量 `$DEFAULT_APP_PACKAGE` / `$DEFAULT_APP_SCHEME`（支持 URL 参数覆盖）。
