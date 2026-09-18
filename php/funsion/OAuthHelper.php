<?php
/**
 * 聚合登录（黎洛云 / u.daib.cn）辅助类
 * 文档：https://u.daib.cn/doc.php#doc4
 */
class OAuthHelper
{
    const API_BASE = 'https://u.daib.cn/connect.php';

    // 支持的登录渠道（type => 名称/图标/主题色）
    const CHANNELS = [
        'qq'        => ['name' => 'QQ', 'icon' => 'fab fa-qq', 'color' => '#12b7f5'],
        'wx'        => ['name' => '微信', 'icon' => 'fab fa-weixin', 'color' => '#07c160'],
        'alipay'    => ['name' => '支付宝', 'icon' => 'fab fa-alipay', 'color' => '#1677ff'],
        'sina'      => ['name' => '微博', 'icon' => 'fab fa-weibo', 'color' => '#e6162d'],
        'baidu'     => ['name' => '百度', 'icon' => 'fa fa-paw', 'color' => '#2932e1'],
        'douyin'    => ['name' => '抖音', 'icon' => 'fab fa-tiktok', 'color' => '#000000'],
        'huawei'    => ['name' => '华为', 'icon' => 'fa fa-mobile-screen', 'color' => '#cf0a2c'],
        'xiaomi'    => ['name' => '小米', 'icon' => 'fa fa-mobile-alt', 'color' => '#ff6900'],
        'google'    => ['name' => 'Google', 'icon' => 'fab fa-google', 'color' => '#db4437'],
        'microsoft' => ['name' => '微软', 'icon' => 'fab fa-microsoft', 'color' => '#0078d4'],
        'facebook'  => ['name' => 'Facebook', 'icon' => 'fab fa-facebook', 'color' => '#1877f2'],
        'twitter'   => ['name' => 'Twitter', 'icon' => 'fab fa-twitter', 'color' => '#000000'],
        'feishu'    => ['name' => '飞书', 'icon' => 'fa fa-paper-plane', 'color' => '#3370ff'],
        'wework'    => ['name' => '企业微信', 'icon' => 'fab fa-weixin', 'color' => '#2e7be4'],
        'dingtalk'  => ['name' => '钉钉', 'icon' => 'fa fa-comment-dots', 'color' => '#1677ff'],
        'gitee'     => ['name' => 'Gitee', 'icon' => 'fa fa-code-branch', 'color' => '#c71d23'],
        'github'    => ['name' => 'GitHub', 'icon' => 'fab fa-github', 'color' => '#181717'],
        'telegram'  => ['name' => 'Telegram', 'icon' => 'fab fa-telegram', 'color' => '#26a5e4'],
    ];

    /**
     * 渠道品牌图标文件（php/images/logo/<file>.svg）
     * 未登记或文件缺失时，前端回退到 CHANNELS 中的图标字体
     */
    const CHANNEL_LOGOS = [
        'qq'      => 'qq',
        'wx'      => 'weixin',
        'alipay'  => 'zifubao',
        'douyin'  => 'douyin',
        'huawei'  => 'huawei',
        'xiaomi'  => 'xiaomi',
        'google'  => 'google',
        'twitter' => 'twitter',
        'feishu'  => 'feishu',
    ];

    /**
     * 读取聚合登录配置（单行）
     */
    public static function getConfig($DB)
    {
        $row = $DB->getRow("SELECT * FROM nteam_oauth_config ORDER BY id DESC LIMIT 1");
        if ($row) {
            $row['channels_arr'] = [];
            if (!empty($row['channels'])) {
                $dec = json_decode($row['channels'], true);
                if (is_array($dec)) {
                    $row['channels_arr'] = $dec;
                }
            }
        }
        return $row;
    }

    /**
     * 聚合登录总开关是否开启（且已配置 AppID / AppKey）
     */
    public static function isEnabled($DB)
    {
        $cfg = self::getConfig($DB);
        return $cfg && intval($cfg['enable']) === 1 && !empty($cfg['appid']) && !empty($cfg['appkey']);
    }

    /**
     * 指定渠道是否在启用状态
     */
    public static function isChannelEnabled($DB, $type)
    {
        if (!self::isEnabled($DB)) {
            return false;
        }
        $cfg = self::getConfig($DB);
        return $cfg && in_array($type, $cfg['channels_arr'], true);
    }

    /**
     * 返回已启用渠道的元数据（用于前端渲染按钮）
     */
    public static function getEnabledChannels($DB)
    {
        if (!self::isEnabled($DB)) {
            return [];
        }
        $cfg = self::getConfig($DB);
        $list = [];
        foreach ($cfg['channels_arr'] as $type) {
            if (isset(self::CHANNELS[$type])) {
                $meta = self::CHANNELS[$type];
                // 附带品牌 SVG 图标地址（相对站点根，兼容子目录部署）
                $meta['svg'] = self::getChannelIcon($type);
                $list[$type] = $meta;
            }
        }
        return $list;
    }

    /**
     * 渠道品牌 SVG 图标地址
     *
     * 图标统一存放于 php/images/logo/，文件不存在时返回空串，调用方回退到图标字体。
     *
     * @return string 形如 "/images/logo/qq.svg"（子目录部署时自动带前缀），或 ''
     */
    public static function getChannelIcon($type)
    {
        // 优先取映射表；未登记时按"渠道标识同名文件"查找（如 github.svg）
        $name = isset(self::CHANNEL_LOGOS[$type]) ? self::CHANNEL_LOGOS[$type] : $type;
        if (!preg_match('/^[a-z0-9_]+$/', $name)) {
            return '';
        }
        if (!is_file(dirname(__DIR__) . '/images/logo/' . $name . '.svg')) {
            return '';
        }
        global $siteBasePath;
        return ($siteBasePath ?: '') . '/images/logo/' . $name . '.svg';
    }

    /**
     * 获取第三方平台授权跳转地址
     */
    public static function getLoginUrl($DB, $type, $redirectUri)
    {
        $cfg = self::getConfig($DB);
        if (!$cfg) {
            return ['code' => -1, 'msg' => '聚合登录未配置'];
        }
        if (intval($cfg['enable']) !== 1) {
            return ['code' => -1, 'msg' => '聚合登录未开启'];
        }
        if (!isset(self::CHANNELS[$type])) {
            return ['code' => -1, 'msg' => '不支持的登录方式'];
        }
        if (!in_array($type, $cfg['channels_arr'], true)) {
            return ['code' => -1, 'msg' => '该登录渠道未启用'];
        }

        $url = self::API_BASE . '?act=login&appid=' . urlencode($cfg['appid'])
            . '&appkey=' . urlencode($cfg['appkey']) . '&type=' . urlencode($type)
            . '&redirect_uri=' . urlencode($redirectUri);

        $resp = get_curl($url);
        $data = json_decode($resp, true);
        if (!is_array($data) || $data['code'] != 0) {
            return ['code' => -1, 'msg' => '获取登录地址失败：' . ($data['msg'] ?? '未知错误')];
        }

        return ['code' => 0, 'url' => $data['url'], 'qrcode' => $data['qrcode'] ?? ''];
    }

    /**
     * 通过 Authorization Code 换取第三方用户信息
     */
    public static function callback($DB, $type, $code)
    {
        $cfg = self::getConfig($DB);
        if (!$cfg) {
            return ['code' => -1, 'msg' => '聚合登录未配置'];
        }

        $url = self::API_BASE . '?act=callback&appid=' . urlencode($cfg['appid'])
            . '&appkey=' . urlencode($cfg['appkey']) . '&type=' . urlencode($type)
            . '&code=' . urlencode($code);

        $resp = get_curl($url);
        $data = json_decode($resp, true);
        if (!is_array($data) || $data['code'] != 0) {
            return ['code' => -1, 'msg' => '获取用户信息失败：' . ($data['msg'] ?? '未知错误')];
        }

        return $data;
    }

    /**
     * 构建回调地址
     */
    public static function buildCallbackUrl()
    {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        global $siteBasePath;
        $base = $siteBasePath ?: '';
        return $scheme . '://' . $host . $base . '/user/oauth_callback.php';
    }

    /**
     * 签发 App/WebView 绑定票据（10 分钟有效、一次性）
     *
     * App 端在 WebView / 外部浏览器中完成授权，回调时没有登录会话，
     * 因此用票据（包含渠道类型）来识别发起绑定的用户。
     *
     * @return string 票据，形如 "qq.1a2b3c..."
     */
    public static function issueBindTicket($DB, $userId, $type)
    {
        $userId = intval($userId);
        $ticket = $type . '.' . bin2hex(random_bytes(12));
        $now    = date('Y-m-d H:i:s');
        $expire = date('Y-m-d H:i:s', time() + 600);
        $DB->exec("DELETE FROM nteam_tokens WHERE user_id='$userId' AND token_type='oauth_bind'");
        $DB->exec("INSERT INTO nteam_tokens (user_id, token_type, token, expire_time, used, create_time) VALUES ('$userId', 'oauth_bind', '$ticket', '$expire', 0, '$now')");
        return $ticket;
    }

    /**
     * 校验并消费 App 绑定票据
     *
     * @return int 票据对应的用户 ID；无效返回 0
     */
    public static function resolveBindTicket($DB, $ticket, $type)
    {
        $ticket = addslashes((string) $ticket);
        if ($ticket === '' || strpos($ticket, $type . '.') !== 0) {
            return 0;
        }
        $now = date('Y-m-d H:i:s');
        $row = $DB->getRow("SELECT * FROM nteam_tokens WHERE token_type='oauth_bind' AND token='$ticket' AND used=0 AND expire_time > '$now' ORDER BY id DESC LIMIT 1");
        if (!$row) {
            return 0;
        }
        $DB->exec("UPDATE nteam_tokens SET used=1 WHERE id='" . intval($row['id']) . "'");
        return intval($row['user_id']);
    }

    /**
     * 签发 App/WebView 登录票据（10 分钟有效）
     *
     * App 端在 WebView / 外部浏览器中完成第三方授权，回调时没有登录会话，
     * 因此用票据标识本次登录：回调写入 user_id 表示"已授权"，
     * App 轮询到 user_id 后由 api.php 换取正式登录令牌（票据一次性消费）。
     *
     * @return string 票据，形如 "qq.1a2b3c..."
     */
    public static function issueLoginTicket($DB, $type)
    {
        $ticket = $type . '.' . bin2hex(random_bytes(16));
        $now    = date('Y-m-d H:i:s');
        $expire = date('Y-m-d H:i:s', time() + 600);
        // 顺带清理已过期票据，避免堆积
        $DB->exec("DELETE FROM nteam_tokens WHERE token_type='oauth_login' AND expire_time < '$now'");
        $DB->exec("INSERT INTO nteam_tokens (user_id, token_type, token, expire_time, used, create_time) VALUES (0, 'oauth_login', '$ticket', '$expire', 0, '$now')");
        return $ticket;
    }

    /**
     * 查找有效的登录票据（未被消费、未过期、渠道匹配）
     *
     * @return array|null 票据记录，或 null
     */
    public static function findLoginTicket($DB, $ticket, $type)
    {
        $ticket = addslashes((string) $ticket);
        if ($ticket === '' || strpos($ticket, $type . '.') !== 0) {
            return null;
        }
        $now = date('Y-m-d H:i:s');
        $row = $DB->getRow("SELECT * FROM nteam_tokens WHERE token_type='oauth_login' AND token='$ticket' AND used=0 AND expire_time > '$now' ORDER BY id DESC LIMIT 1");
        return $row ? $row : null;
    }
}
