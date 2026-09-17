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
                $list[$type] = self::CHANNELS[$type];
            }
        }
        return $list;
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
}
