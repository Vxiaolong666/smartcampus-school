<?php
/**
 * 蓝奏云（Lanzou）链接解析辅助
 * ------------------------------------------------------------------
 * 后台「版本管理」的下载地址允许填写蓝奏云分享链接；客户端检查更新时，
 * 由后端调用同级的 php/lanzhouyun.php 解析为直链再返回，并标注 download_type，
 * 便于 uniapp 判断是否使用内置下载器（下载完成后拉起安装）。
 *
 * 解析接口 php/lanzhouyun.php 的调用方式：
 *   无密码：?url=https://www.lanzouq.com/iGNHA6th9cd
 *   有密码：?url=https://www.lanzous.com/i42Xxebssfg&pwd=1234
 * （不传 type=down 时返回 JSON，字段含 code / downUrl / name / filesize）
 *
 * 后台填写约定：若蓝奏云文件设有密码，请在链接末尾追加「&pwd=密码」，
 * 例如 https://www.lanzouq.com/i42Xxebssfg&pwd=1234
 */

if (!function_exists('lanzouIsUrl')) {
    /**
     * 是否蓝奏云分享链接（覆盖 lanzou*.com / lanzou*.cn / lanzn.com 等常见域名）
     */
    function lanzouIsUrl($url)
    {
        if (!is_string($url) || $url === '') {
            return false;
        }
        return (bool) preg_match('~^https?://([a-z0-9-]+\.)*(lanzou[a-z]*|lanzn)\.(com|cn)(/|$)~i', $url);
    }

    /**
     * 从链接中拆出蓝奏云密码
     * 兼容在链接末尾追加的「&pwd=1234」或「?pwd=1234」，返回去除密码后的干净链接
     *
     * @return array{url:string,pwd:string}
     */
    function lanzouSplitPassword($url)
    {
        $pwd = '';
        $url = (string) $url;
        if (preg_match('~[?&]pwd=([^&\s#]+)~i', $url, $m)) {
            $pwd = trim($m[1]);
            $url = preg_replace('~[?&]pwd=[^&\s#]+~i', '', $url);
            // 清理删除参数后可能残留的结尾 ? 或 &
            $url = preg_replace('~[?&]+$~', '', $url);
        }
        return array('url' => $url, 'pwd' => $pwd);
    }

    /**
     * 计算 php/lanzhouyun.php 的访问地址（自动适配站点根目录 / 子目录部署）
     */
    function lanzouApiUrl()
    {
        // 本文件位于 php/funsion/，lanzhouyun.php 位于其上一级（php/）
        $target = str_replace('\\', '/', dirname(__DIR__) . '/lanzhouyun.php');

        $scheme = 'http';
        if ((!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
            || (isset($_SERVER['SERVER_PORT']) && intval($_SERVER['SERVER_PORT']) === 443)) {
            $scheme = 'https';
        }

        $host = isset($_SERVER['HTTP_HOST']) ? trim($_SERVER['HTTP_HOST']) : '';
        // 仅允许常见主机名/端口字符，避免 Host 头被伪造为异常值（降低内部请求被利用的风险）
        if ($host === '' || !preg_match('~^[a-z0-9.\-]+(:\d+)?$~i', $host)) {
            $host = isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] !== '' ? $_SERVER['SERVER_NAME'] : '127.0.0.1';
        }

        $path = '/lanzhouyun.php';
        $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/\\')) : '';
        if ($docRoot !== '' && strpos($target, $docRoot) === 0) {
            $path = substr($target, strlen($docRoot));
        }

        return $scheme . '://' . $host . $path;
    }

    /**
     * 调用解析接口，把蓝奏云分享链接转换为直链
     *
     * @return array{directUrl:string,name:string,filesize:string}|null 失败返回 null
     */
    // 超时放宽到 25 秒：新版蓝奏解析链路更长（分享页挑战 → 接口换链 → CDN 挑战 →
    // 验证页换直链），其中还有约 3 秒的延时门，15 秒容易在慢网络下被掐断
    function lanzouResolve($url, $pwd = '', $timeout = 25)
    {
        if (!function_exists('curl_init')) {
            return null;
        }

        // 结果缓存：避免「检查更新」高频触发解析（直链有时效，缓存不宜过长）
        $cacheDir = dirname(__DIR__) . '/cache';
        $cacheFile = $cacheDir . '/lanzou_' . md5($url . '|' . $pwd) . '.json';
        $cacheTtl = 300; // 5 分钟
        if (is_file($cacheFile) && (time() - filemtime($cacheFile) < $cacheTtl)) {
            $cached = json_decode(@file_get_contents($cacheFile), true);
            if (is_array($cached) && !empty($cached['directUrl'])) {
                return $cached;
            }
        }

        $params = array('url' => $url);
        if ($pwd !== '') {
            $params['pwd'] = $pwd;
        }
        $api = lanzouApiUrl() . '?' . http_build_query($params);

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $api,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => intval($timeout),
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => 0,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; SmartCampus/1.0)',
        ));
        $res = curl_exec($ch);
        curl_close($ch);

        if (!is_string($res) || $res === '') {
            return null;
        }
        $json = json_decode($res, true);
        if (!is_array($json) || intval(isset($json['code']) ? $json['code'] : 0) !== 200 || empty($json['downUrl'])) {
            return null;
        }

        $result = array(
            'directUrl' => html_entity_decode($json['downUrl'], ENT_QUOTES, 'UTF-8'),
            'name'      => isset($json['name']) ? $json['name'] : '',
            'filesize'  => isset($json['filesize']) ? $json['filesize'] : '',
        );

        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        @file_put_contents($cacheFile, json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $result;
    }
}
