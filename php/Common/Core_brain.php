<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE);
if(defined('IN_CRONLITE'))return;
define('IN_CRONLITE', true);
define('SYSTEM_ROOT', dirname(__FILE__).'/');
define('ROOT', dirname(SYSTEM_ROOT).'/');
date_default_timezone_set('PRC');
$date = date("Y年m月d日");
include_once(SYSTEM_ROOT."Autoloader.php");
Autoloader::register();

$isLogin = false;
$isUserLogin = false;
$adminData = null;
$mod = isset($mod) ? $mod : '' ;

if(is_file(SYSTEM_ROOT.'360safe/360webscan.php')){//360网站卫士
    require_once(SYSTEM_ROOT.'360safe/360webscan.php');
}

require SYSTEM_ROOT.'Database_Config.php';
if(!$dbconfig['user']||!$dbconfig['pwd']||!$dbconfig['dbname']){header('Content-type:text/html;charset=utf-8');echo '您还没安装，请<a href="install">立即安装</a>';exit();}
/** @var \lib\PdoHelper $DB */
$DB = new \lib\PdoHelper($dbconfig);
$Admin = new \lib\Adminclass();
$Gets = new \lib\Gets();
if($DB->query("select * from nteam_config where 1")==FALSE){header('Content-type:text/html;charset=utf-8');echo '您还没安装，请<a href="install">立即安装</a>';exit();}
include_once(SYSTEM_ROOT."Core_Functions.php");
session_start();

$siteBasePath = '';
// 根据实际访问路径推导站点子目录前缀（兼容子目录部署，如 /csgocs）
// 部分服务器环境下 $_SERVER['SCRIPT_NAME'] 不会包含二级目录，导致以此推导的
// 地址缺失子路径；因此优先以浏览器实际请求路径（REQUEST_URI）为准。
$scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
$reqUri = isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
if (!empty($reqUri) && basename($reqUri) === basename($scriptName)) {
    // 当前脚本位于 {子目录}/{Adminq|user}/xxx.php，向上两级即为站点根
    $appBase = dirname(dirname($reqUri));
    if ($appBase === '/' || $appBase === '\\' || $appBase === '' || $appBase === '.') {
        $appBase = '';
    }
    $siteBasePath = $appBase;
}
// 回退：使用 SCRIPT_NAME 推导
if ($siteBasePath === '' && $scriptName !== '') {
    $appBase = dirname(dirname($scriptName));
    if ($appBase === '/' || $appBase === '\\' || $appBase === '' || $appBase === '.') {
        $appBase = '';
    }
    $siteBasePath = $appBase;
}
// 再回退：使用站点配置地址中的路径部分
if ($siteBasePath === '') {
    $tmpUrl = conf('Url');
    if (!empty($tmpUrl)) {
        $parsed = parse_url($tmpUrl);
        if (isset($parsed['path'])) {
            $siteBasePath = rtrim($parsed['path'], '/');
        }
    }
}

if($mod != 'install'){
    if(isset($_SESSION['adminUser'])){
        $adminUser = $_SESSION['adminUser'];
        $ip = $Gets->ip();
        $city = $Gets->get_city($ip);
        $adminData = $DB->query("SELECT * FROM `nteam_admin` WHERE `adminUser` = '$adminUser'")->fetch();
        if(!empty($adminData) && $adminData['adminLoginIp'] == $ip)$isLogin = true;
    }
    if(!isset($notLogin) && $mod == 'admin' && !$isLogin)header('Location:./login.php');
}
?>