<?php
/**
 * 官网配置读取（首页 / 联系我们 / 下载方式 / 常见问题）
 *
 * 依赖：Common/Core_brain.php 已初始化 $DB
 * 说明：在数据库建表前调用会返回空数组，页面回退到原有静态内容，不会报错。
 */

if (!function_exists('homeConfig')) {
    function homeConfig() {
        global $DB;
        static $cache = null;
        if ($cache !== null) return $cache;
        $cache = array();
        $rs = @$DB->query("SELECT * FROM nteam_home_config WHERE id=1");
        if ($rs) {
            $row = $rs->fetch();
            if ($row) $cache = $row;
        }
        return $cache;
    }
}

if (!function_exists('homeCfg')) {
    function homeCfg($key, $default = '') {
        $c = homeConfig();
        return (isset($c[$key]) && $c[$key] !== '' && $c[$key] !== null) ? $c[$key] : $default;
    }
}

if (!function_exists('homeDownloadList')) {
    function homeDownloadList($onlyShow = true) {
        global $DB;
        $list = array();
        $where = $onlyShow ? " WHERE `status`=1" : '';
        $rs = @$DB->query("SELECT * FROM nteam_home_download{$where} ORDER BY `sort` ASC, `id` ASC");
        if ($rs) {
            while ($row = $rs->fetch()) $list[] = $row;
        }
        return $list;
    }
}

if (!function_exists('homeSiteConfig')) {
    /**
     * 供公共头部/底部组件使用的站点配置（注入到 window.SITE_CONFIG）
     */
    function homeSiteConfig() {
        $url = homeCfg('site_url');
        if ($url === '' && function_exists('conf')) {
            $url = (string)@conf('Url');
        }
        return array(
            'site_url' => $url,
            'email'    => homeCfg('contact_email'),
            'phone'    => homeCfg('contact_phone'),
            'qq'       => homeCfg('contact_qq'),
            'address'  => homeCfg('contact_address'),
            'worktime' => homeCfg('contact_worktime'),
        );
    }
}

if (!function_exists('homeFaqList')) {
    function homeFaqList($onlyShow = true) {
        global $DB;
        $list = array();
        $where = $onlyShow ? " WHERE `status`=1" : '';
        $rs = @$DB->query("SELECT * FROM nteam_home_faq{$where} ORDER BY `sort` ASC, `id` ASC");
        if ($rs) {
            while ($row = $rs->fetch()) $list[] = $row;
        }
        return $list;
    }
}

if (!function_exists('homeFaqGrouped')) {
    function homeFaqGrouped() {
        $grouped = array();
        foreach (homeFaqList() as $item) {
            $cat = $item['category'] !== '' ? $item['category'] : '常见问题';
            $grouped[$cat][] = $item;
        }
        return $grouped;
    }
}
