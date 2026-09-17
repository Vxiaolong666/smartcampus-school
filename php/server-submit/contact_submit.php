<?php
/**
 * 官网「联系我们」表单提交接口
 *
 * 访问路径：{站点根}/server-submit/contact_submit.php
 * 请求方式：POST（application/x-www-form-urlencoded / form-data 均可）
 * 参数：name, phone, email, subject, content, tc_ticket, tc_randstr, captcha
 * 返回：{"code":0,"msg":"..."} code=0 成功，其它为失败
 *
 * 校验规则与登录/注册保持一致：
 *   1) 腾讯滑块校验通过（tc_ticket + tc_randstr 非空）
 *   2) 或图形验证码与 $_SESSION['captcha'] 大写比对一致
 * 是否开启表单提交、是否强制验证，由后台「首页配置」开关控制。
 */
$notLogin = true;
include('../Common/Core_brain.php');
include('../Common/home_config.php');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function contactOut($code, $msg) {
    echo json_encode(array('code' => $code, 'msg' => $msg), JSON_UNESCAPED_UNICODE);
    exit;
}

// 数据表是否就绪
$ready = false;
try {
    $chk = @$DB->query("SELECT 1 FROM nteam_contact_msgs LIMIT 1");
    $ready = $chk ? true : false;
} catch (Exception $e) {
    $ready = false;
}
if (!$ready) {
    contactOut(-2, '系统尚未完成初始化，请先导入首页配置相关数据表');
}

$cfg = homeConfig();
// 默认开启（未配置时按开启处理），后台可在「首页配置」中关闭
$formOpen    = isset($cfg['contact_form_open']) && $cfg['contact_form_open'] !== '' ? intval($cfg['contact_form_open']) : 1;
$needCaptcha = isset($cfg['contact_form_captcha']) && $cfg['contact_form_captcha'] !== '' ? intval($cfg['contact_form_captcha']) : 1;

if (!$formOpen) {
    contactOut(-3, '留言功能已关闭，请通过页面展示的联系方式与我们联系');
}

$name    = isset($_POST['name']) ? trim($_POST['name']) : '';
$phone   = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$email   = isset($_POST['email']) ? trim($_POST['email']) : '';
$subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
$content = isset($_POST['content']) ? trim($_POST['content']) : '';

if ($name === '' || $content === '') {
    contactOut(-1, '请填写姓名与留言内容');
}
if (mb_strlen($content, 'UTF-8') > 2000) {
    contactOut(-1, '留言内容过长（最多 2000 字）');
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    contactOut(-1, '邮箱格式不正确');
}

// ===== 人机验证（与 user/ajax.php 保持一致） =====
if ($needCaptcha) {
    $tcTicket  = isset($_POST['tc_ticket']) ? trim($_POST['tc_ticket']) : '';
    $tcRandstr = isset($_POST['tc_randstr']) ? trim($_POST['tc_randstr']) : '';
    $captcha   = isset($_POST['captcha']) ? strtoupper(trim($_POST['captcha'])) : '';

    $captchaValid = false;
    if ($tcTicket !== '' && $tcRandstr !== '') {
        $captchaValid = true;
    } elseif ($captcha !== '') {
        if (isset($_SESSION['captcha']) && strtoupper($_SESSION['captcha']) === $captcha) {
            $captchaValid = true;
            unset($_SESSION['captcha']);
        }
    }
    if (!$captchaValid) {
        contactOut(-1, '请先完成滑块 / 验证码验证');
    }
}

// ===== 写入留言 =====
$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
$ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 250) : '';

$sql = "INSERT INTO nteam_contact_msgs (`name`,`phone`,`email`,`subject`,`content`,`ip`,`ua`,`status`) VALUES ('"
    . addslashes($name) . "','" . addslashes($phone) . "','" . addslashes($email) . "','"
    . addslashes($subject) . "','" . addslashes($content) . "','" . addslashes($ip) . "','"
    . addslashes($ua) . "',0)";

if ($DB->exec($sql) === false) {
    contactOut(-1, '提交失败，请稍后重试');
}

contactOut(0, '提交成功，我们会尽快与你联系');
