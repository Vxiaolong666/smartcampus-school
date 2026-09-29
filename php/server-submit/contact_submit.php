<?php
/**
 * 「联系我们」留言 / 反馈提交接口（官网留言与网页用户端反馈共用）
 *
 * 访问路径：{站点根}/server-submit/contact_submit.php
 * 请求方式：POST（application/x-www-form-urlencoded / form-data 均可）
 * 参数：name, phone, email, subject, content, tc_ticket, tc_randstr, captcha
 * 返回：{"code":0,"msg":"..."} code=0 成功，其它为失败
 *
 * 校验规则：
 *   1) 已登录用户（用户端提交）：自动关联 user_id 并预填姓名 / 邮箱，来源记为 user；
 *   2) 未登录访客（官网提交）：来源记为 site。
 * 两条链路是否要求人机验证，统一由后台「反馈配置」页面的「提交前滑块 / 验证码验证」开关决定
 * （启用时两者都需通过验证）；是否允许提交由同页的「联系我们表单提交开关」控制。
 */
$notLogin = true;
include('../Common/Core_brain.php');
include('../Common/home_config.php');
require_once('../funsion/ContactMsgs.php');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

function contactOut($code, $msg) {
    echo json_encode(array('code' => $code, 'msg' => $msg), JSON_UNESCAPED_UNICODE);
    exit;
}

// 数据表是否就绪
if (!contactMsgTableReady($DB)) {
    contactOut(-2, '系统尚未完成初始化，请先导入官网配置相关数据表');
}

// 反馈功能字段（user_id / source / handle_status / remark / update_time）缺失时自动补齐
contactMsgEnsureSchema($DB);

$cfg = homeConfig();
// 默认开启（未配置时按开启处理），后台可在「用户反馈 → 反馈配置」（user_feedback.php?tab=config）调整
$formOpen    = isset($cfg['contact_form_open']) && $cfg['contact_form_open'] !== '' ? intval($cfg['contact_form_open']) : 1;
$needCaptcha = isset($cfg['contact_form_captcha']) && $cfg['contact_form_captcha'] !== '' ? intval($cfg['contact_form_captcha']) : 1;

if (!$formOpen) {
    contactOut(-3, '留言功能已关闭，请通过页面展示的联系方式与我们联系');
}

// ===== 登录态：关联用户并预填姓名 / 邮箱（用户端提交） =====
$userId = isset($_SESSION['userId']) ? intval($_SESSION['userId']) : 0;
$userRow = null;
if ($userId > 0) {
    $userRow = $DB->getRow("SELECT id,username,nickname,email FROM nteam_users WHERE id='$userId'");
    if (!$userRow) {
        $userId = 0;   // 账号已注销或不存在，按访客处理
    }
}

$name    = isset($_POST['name']) ? trim($_POST['name']) : '';
$phone   = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$email   = isset($_POST['email']) ? trim($_POST['email']) : '';
$subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
$content = isset($_POST['content']) ? trim($_POST['content']) : '';

if ($userId > 0 && $userRow) {
    if ($name === '') {
        $name = !empty($userRow['nickname']) ? $userRow['nickname'] : $userRow['username'];
    }
    if ($email === '' && !empty($userRow['email'])) {
        $email = $userRow['email'];
    }
}

if ($name === '' || $content === '') {
    contactOut(-1, '请填写姓名与留言内容');
}
if (mb_strlen($content, 'UTF-8') > 2000) {
    contactOut(-1, '留言内容过长（最多 2000 字）');
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    contactOut(-1, '邮箱格式不正确');
}

// ===== 人机验证：后台「反馈配置」开启后，登录用户与访客提交前都必须通过验证 =====
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

// ===== 写入留言（登录用户来源记为 user，官网访客记为 site） =====
$newId = contactMsgAdd($DB, array(
    'name'    => $name,
    'phone'   => $phone,
    'email'   => $email,
    'subject' => $subject,
    'content' => $content,
    'ip'      => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
    'ua'      => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 250) : '',
    'user_id' => $userId,
));

if ($userId > 0) {
    if ($newId === false) {
        contactOut(-1, '提交失败，请稍后重试');
    }
    contactOut(0, '提交成功，我们会尽快处理，处理进度可在「我的反馈」中查看');
}

if ($newId === false) {
    contactOut(-1, '提交失败，请稍后重试');
}
contactOut(0, '提交成功，我们会尽快与你联系');
