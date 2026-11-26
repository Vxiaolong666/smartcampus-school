# 智慧校园 Web 端部署说明

本文档适用于根目录下的 PHP Web 项目（用户后台 `user/`、管理员后台 `Adminq/`、开放接口 `api.php` 与定时任务 `cron_*.php`）。

## 1. 环境要求

| 项目 | 要求 |
| --- | --- |
| PHP | PHP 8.0 及以上（开发环境为 8.3.8） |
| PHP 扩展 | `pdo_mysql`、`curl`、`openssl`、`json`、`mbstring`、`session` |
| 数据库 | MySQL / MariaDB |
| Web 服务器 | Nginx + PHP-FPM，或 Apache + mod_php |
| 构建工具 | 无。项目为传统 PHP + 静态资源，不需要 Composer / Node 构建 |

依赖的外部服务（不可达时对应功能降级，不影响后台登录）：

- 教务系统接口：`https://jwh5.gdpnc.cn`（课表、成绩、学籍、学期、课程模式）
- 腾讯验证码：`https://ssl.captcha.qq.com/TCaptcha.js`
- QQ 头像：`https://q2.qlogo.cn`
- 消息通知服务：`https://www.uaovo.com/Ajax.php`（管理员后台 Telegram 通知，可选）

## 2. 目录结构

```
./
├── index.php                 站点首页
├── api.php                   开放接口（App / 小程序调用）
├── 404.html
├── cron_*.php                定时任务入口
├── user/                     用户后台（入口 login.php、index.php）
├── Adminq/                   管理员后台（入口 login.php、index.php）
├── Common/
│   ├── Core_brain.php        公共引导：数据库、登录态、session 锁处理
│   ├── Core_Functions.php    公共函数，conf() 读取站点配置
│   ├── Database_Config.php   数据库连接配置
│   ├── css_guard.php         样式加载守卫（CSS 失败提示）
│   ├── lib/                  数据库与业务基础类
│   └── mail/、PHPMailer/     邮件发送
├── funsion/                  业务函数，AcademicSystem.php 为教务接口封装
├── cache/                    运行缓存（需可写）
│   ├── academic/             教务接口与元数据缓存
│   └── admin_stats.php       管理员后台首页统计缓存
├── uploads/                  上传文件（需可写）
├── logs/                     定时任务日志（需新建并可写）
├── server-submit/            验证码等提交端点
└── css/、js/、images/、webfonts/  静态资源
```

## 3. 部署步骤

### 3.1 上传代码

将项目文件上传到站点根目录。项目支持子目录部署，`Common/Core_brain.php` 会根据请求路径自动推导站点前缀 `siteBasePath`。

### 3.2 准备数据库

项目当前未附带 SQL 安装脚本。请从现有环境导出 `nteam` 数据库结构及基础数据后导入新库，至少需要包含：

- `nteam_config`（`id = 1` 的记录必须存在，`conf()` 依赖它）
- `nteam_admin`、`nteam_users`
- `nteam_academic_accounts`、`nteam_academic_push_config`、`nteam_academic_push_settings`
- `nteam_wx_templates`、`nteam_subscriptions`
- `nteam_dormitory_bindings`、`nteam_push_logs`、`nteam_academic_push_logs`
- 以及业务中用到的其它 `nteam_*` 表

### 3.3 配置数据库

编辑 `Common/Database_Config.php`：

```php
$dbconfig = array(
    'host'  => '127.0.0.1',
    'port'  => '3306',
    'user'  => 'your_db_user',
    'pwd'   => 'your_db_password',
    'dbname'=> 'your_db_name',
    'dbqz'  => 'nteam'
);
```

请勿把该文件提交到公开仓库。

### 3.4 配置站点参数

登录管理员后台后，在「网站配置」「SMTP 邮件配置」「学术推送配置」等页面维护 `nteam_config` 中的字段。重要字段：

- `Name`（站点名称）、`SiteName`、`Url`（站点完整地址，含子目录）、`ICP`、`Keywords`、`Descriptison`
- `Mail_Smtp`、`Mail_Port`、`Mail_Secure`、`Mail_Name`、`Mail_Pwd`、`Mail_Admin_Emails`
- 微信订阅消息模板、学术推送学期与课程模式等，在对应管理页面配置

### 3.5 设置目录权限

Web 运行用户需要对以下目录具备写权限：

```bash
chmod -R 755 cache uploads
mkdir -p logs && chmod 755 logs
chown -R www:www cache uploads logs
```

> `cache/academic/` 下的缓存文件用于避免每次页面打开都同步调用教务接口；目录不可写时会静默降级为原来的同步模式，功能不受影响但首屏会变慢。

### 3.6 Web 服务器配置

所有页面都是真实存在的 `.php` 文件，**不需要伪静态重写**。将站点根目录指向项目根目录即可。

建议禁止直接访问运行目录（缓存文件已带 `<?php exit;?>` 前缀，但仍建议从 Web 层限制）：

```nginx
location ~ ^/(cache|logs|Common)/ {
    deny all;
}
```

PHP 建议开启 OPcache，并关闭 `display_errors`，错误写入日志。

### 3.7 访问入口

- 用户后台：`/user/login.php`
- 管理员后台：`/Adminq/login.php`
- 开放接口：`/api.php`

首次部署请确认默认管理员账号密码已修改。

## 4. 定时任务

需配置系统定时任务（crontab），示例（路径按实际修改，频率按业务需要调整）：

```bash
*/5  * * * * cd /www/wwwroot/site && php cron_subscription_alert.php >> /dev/null 2>&1
*/10 * * * * cd /www/wwwroot/site && php cron_academic_push.php  >> /dev/null 2>&1
0  8,20 * * * cd /www/wwwroot/site && php cron_power_push.php     >> /dev/null 2>&1
0  3  * * * cd /www/wwwroot/site && php cron_account_cancel.php   >> /dev/null 2>&1
```

脚本会把执行日志写入 `logs/`（例如 `cron_academic_push.php` 写入 `logs/academic_push_cron.log`），排障时优先查看该目录。

## 5. 运行机制与性能说明

- **Session 锁**：`Common/Core_brain.php` 在读取登录态后立即释放 session 文件锁，避免一个慢页面阻塞同用户的其它页面。后续需要写登录态时必须使用 `nteam_session_set()` / `nteam_session_unset()`，不要再直接写 `$_SESSION`。
- **教务缓存**：`funsion/AcademicSystem.php` 对教务接口结果做文件缓存，目录为 `cache/academic/`。缓存有效期：学期/课程模式 1 小时，学籍 30 分钟，课表/成绩 10 分钟；失败结果 60 秒，登录失败熔断 60 秒。缓存 TTL 在 `AcademicSystem::cacheTtl()` 中调整。
- **后台下拉异步加载**：管理员后台的学期、课程模式下拉不再阻塞页面渲染，由 `Ajax.php?act=academic_meta_refresh` 异步回填。
- **首页统计缓存**：管理员后台首页的多表 `COUNT` 统计缓存在 `cache/admin_stats.php`，有效期 5 分钟。
- **样式守卫**：`Common/css_guard.php` 在主 CSS 未就绪时隐藏页面内容，失败后自动重试一次并给出提示。所有后台页面入口均已接入。
- **前端 pjax**：`user/assets/js/app.js` 与 `Adminq/assets/js/app.js` 已统一处理 pjax 失败/超时、遮罩关闭与脚本去重。

## 6. 部署后验证清单

1. 用户、管理员分别登录与退出，确认登录态保持正常。
2. 用户中心首页：轮播自动播放、教学周徽章、快捷入口跳转、账户信息显示。
3. 管理员后台首页统计正常；学术推送配置页、课程时间配置页的学期/课程模式下拉能自动填充。
4. 连续打开课表、成绩页面两次，第二次应明显更快（命中教务缓存）。
5. 手动清理 `cache/academic/` 下的 `.php` 文件，确认页面能重新拉取数据。
6. 检查 `logs/` 是否生成定时任务日志。
7. 模拟阻断 `oneui.min-5.6.css`，确认显示样式加载失败提示而不是错乱页面。

## 7. 常见问题

| 现象 | 排查方向 |
| --- | --- |
| 登录后立即跳回登录页 | 新增代码是否绕过了 `nteam_session_set()` 直接写 `$_SESSION`；session 目录是否可写 |
| 一个页面卡住后其它页面也卡 | 检查 `Common/Core_brain.php` 中的 `session_write_close()` 是否仍在；教务接口是否被改回渲染期同步调用 |
| 教务数据一直不更新 | 删除 `cache/academic/` 下对应缓存文件；确认目录可写；检查教务接口是否可达 |
| 后台首页统计不更新 | 删除 `cache/admin_stats.php`，或等待 5 分钟缓存过期 |
| 页面样式加载失败提示频繁出现 | 检查静态资源路径、站点 `Url` 配置、子目录部署时的相对路径 |
| 定时任务没有执行 | 检查 crontab 服务、`logs/` 写权限、CLI 下 PHP 是否加载了 pdo_mysql/curl 扩展 |
| 邮件发送失败 | 检查 `Mail_*` 配置、SMTP 端口与加密方式、邮箱授权码 |

## 8. 安全建议

- 限制 `Common/`、`cache/`、`logs/`、`uploads/` 的直接 Web 访问，仅允许必要文件。
- 管理员后台限制来源 IP 或增加二次验证；定期更换密码。
- 生产环境关闭 `display_errors`，定期备份数据库与 `uploads/`。
- `Database_Config.php` 含数据库明文密码，设置文件权限为仅 Web 用户可读。
