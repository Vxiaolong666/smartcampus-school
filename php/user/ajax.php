<?php
$notLogin = true;
include('../Common/Core_brain.php');
/** @var array $dbconfig */
/** @var \lib\PdoHelper $DB */
$act=isset($_GET['act'])?daddslashes($_GET['act']):null;

@header('Content-Type: application/json; charset=UTF-8');

require_once '../funsion/AccountCancellation.php';

switch($act){
    case 'register':
        $username = addslashes($_POST['username']);
        $password = md5($_POST['password']);
        $email = addslashes($_POST['email']);
        $qq = addslashes($_POST['qq']);
        $tcTicket = isset($_POST['tc_ticket']) ? addslashes($_POST['tc_ticket']) : '';
        $tcRandstr = isset($_POST['tc_randstr']) ? addslashes($_POST['tc_randstr']) : '';
        $captcha = isset($_POST['captcha']) ? strtoupper(addslashes($_POST['captcha'])) : '';
        
        if ($username=='' || $password=='' || $email=='') {
            exit('{"code":-1,"msg":"请确保每项都不为空"}');
        }
        
        $captchaValid = false;
        
        if (!empty($tcTicket) && !empty($tcRandstr)) {
            $captchaValid = true;
        } elseif (!empty($captcha)) {
            if (isset($_SESSION['captcha']) && strtoupper($_SESSION['captcha']) === $captcha) {
                $captchaValid = true;
                unset($_SESSION['captcha']);
            }
        }
        
        if (!$captchaValid) {
            exit('{"code":-1,"msg":"非法请求，图片验证码错误或超时"}');
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $username)) {
            exit('{"code":-1,"msg":"用户名只能包含字母、数字、下划线，长度4-20位"}');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            exit('{"code":-1,"msg":"邮箱格式不正确"}');
        }
        
        if (strlen($_POST['password']) < 6) {
            exit('{"code":-1,"msg":"密码长度不能少于6位"}');
        }
        
        $checkUser = $DB->getRow("SELECT * FROM nteam_users WHERE username='$username'");
        if ($checkUser) {
            exit('{"code":-1,"msg":"用户名已存在"}');
        }
        
        $checkEmail = $DB->getRow("SELECT * FROM nteam_users WHERE email='$email'");
        if ($checkEmail) {
            exit('{"code":-1,"msg":"邮箱已被注册"}');
        }
        
        $verifyCode = random(32);
        $registerTime = date('Y-m-d H:i:s');
        $verifyExpireTime = date('Y-m-d H:i:s', strtotime('+3 hours'));
        
        $qqValue = empty($qq) ? 'NULL' : "'$qq'";
        $sql = "INSERT INTO `nteam_users` (`username`, `password`, `email`, `qq`, `register_time`) VALUES ('$username', '$password', '$email', $qqValue, '$registerTime')";
        
        if ($DB->exec($sql)) {
            $userId = $DB->lastInsertId();
            
            $tokenSql = "INSERT INTO `nteam_tokens` (`user_id`, `token_type`, `token`, `email`, `expire_time`, `create_time`) VALUES ('$userId', 'email_verify', '$verifyCode', '$email', '$verifyExpireTime', '$registerTime')";
            $DB->exec($tokenSql);
            
            $siteName = conf('Name');
            $siteUrl = conf('Mail_Site_Url') ?: conf('Url');
            $verifyUrl = $siteUrl . 'user/verify.php?code=' . $verifyCode . '&email=' . $email;
            
            $mailContent = '<html>
<head>
    <meta charset="UTF-8">
    <title>验证您的邮箱</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
        <h2 style="color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 10px;">欢迎注册 ' . htmlspecialchars($siteName) . '</h2>
        <p>您好，<strong>' . htmlspecialchars($username) . '</strong>：</p>
        <p>感谢您注册 ' . htmlspecialchars($siteName) . '！</p>
        <p>请点击以下链接验证您的邮箱：</p><br>
        <p><a href="' . htmlspecialchars($verifyUrl) . '" style="background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">验证邮箱</a></p>
        <br><p>如果链接无法点击，请复制到浏览器地址栏访问：</p>
        <p style="background-color: #f8f9fa; padding: 10px; border-radius: 5px; word-break: break-all;">' . htmlspecialchars($verifyUrl) . '</p>
        <p>此链接有效期为3小时，超过时间后将无法使用。</p>
        <p>如果您没有注册 ' . htmlspecialchars($siteName) . '，请忽略此邮件。</p>
        <p style="color: #6c757d; font-size: 12px;">此邮件为系统自动发送，请勿回复。</p>
    </div>
</body>
</html>';
            
            $sendResult = send_mail($email, "验证您的邮箱 - {$siteName}", $mailContent);
            
            if ($sendResult === true) {
                exit('{"code":0,"msg":"注册成功，请查看邮箱验证您的账号"}');
            } else {
                exit('{"code":-1,"msg":"注册成功但邮件发送失败：'.$sendResult.'"}');
            }
        } else {
            exit('{"code":-1,"msg":"注册失败，请稍后重试"}');
        }
    break;
    
    case 'verify_email':
        $code = addslashes($_GET['code']);
        $email = addslashes($_GET['email']);
        
        if ($code=='' || $email=='') {
            exit('{"code":-1,"msg":"参数错误"}');
        }
        
        $currentTime = date('Y-m-d H:i:s');
        $token = $DB->getRow("SELECT * FROM nteam_tokens WHERE token='$code' AND email='$email' AND token_type='email_verify' AND used=0 AND expire_time>'$currentTime'");
        
        if (!$token) {
            exit('{"code":-1,"msg":"验证链接无效或已过期"}');
        }
        
        $user = $DB->getRow("SELECT * FROM nteam_users WHERE id='{$token['user_id']}'");
        
        if (!$user) {
            exit('{"code":-1,"msg":"用户不存在"}');
        }
        
        if ($user['email_verified'] == 1) {
            exit('{"code":-1,"msg":"邮箱已验证，请直接登录"}');
        }
        
        $updateSql = "UPDATE nteam_users SET email_verified=1 WHERE id='{$user['id']}'";
        $tokenUpdateSql = "UPDATE nteam_tokens SET used=1 WHERE id='{$token['id']}'";
        
        if ($DB->exec($updateSql) !== false && $DB->exec($tokenUpdateSql) !== false) {
            exit('{"code":0,"msg":"邮箱验证成功，请登录"}');
        } else {
            exit('{"code":-1,"msg":"验证失败，请稍后重试"}');
        }
    break;
    
    case 'login':
        $username = addslashes($_POST['username']);
        $password = md5($_POST['password']);
        $tcTicket = isset($_POST['tc_ticket']) ? addslashes($_POST['tc_ticket']) : '';
        $tcRandstr = isset($_POST['tc_randstr']) ? addslashes($_POST['tc_randstr']) : '';
        $captcha = isset($_POST['captcha']) ? strtoupper(addslashes($_POST['captcha'])) : '';
        
        if ($username=='' || $password=='') {
            exit('{"code":-1,"msg":"请确保每项都不为空"}');
        }
        
        $captchaValid = false;
        
        if (!empty($tcTicket) && !empty($tcRandstr)) {
            $captchaValid = true;
        } elseif (!empty($captcha)) {
            if (isset($_SESSION['captcha']) && strtoupper($_SESSION['captcha']) === $captcha) {
                $captchaValid = true;
                unset($_SESSION['captcha']);
            }
        }
        
        if (!$captchaValid) {
            exit('{"code":-1,"msg":"非法请求，图片验证码错误或超时"}');
        }
        
        $userData = $DB->getRow("SELECT * FROM nteam_users WHERE username='$username'");
        
        if (!$userData) {
            exit('{"code":-1,"msg":"用户不存在"}');
        }
        
        if ($userData['is_banned'] == 1) {
            exit('{"code":-1,"msg":"该账号已被封禁，请联系管理员"}');
        }
        
        if ($userData['email_verified'] == 0) {
            exit('{"code":-1,"msg":"请先完成邮箱验证后再登录，验证链接已发送至您的邮箱"}');
        }
        
        if ($password != $userData['password']) {
            exit('{"code":-1,"msg":"密码错误"}');
        }
        
        $ip = $Gets->ip();

        // 冷静期处理：冷静期内登录即自动撤销注销；已到期则不撤销
        $cancelState = cancelHandleLogin($DB, $userData, $ip);
        if ($cancelState === 'expired') {
            exit('{"code":-1,"msg":"该账号已进入注销流程，无法登录"}');
        }
        
        $updateSql = "UPDATE nteam_users SET last_login_time=NOW(), last_login_ip='$ip' WHERE id='{$userData['id']}'";
        $DB->exec($updateSql);
        
        $_SESSION['userId'] = $userData['id'];
        $_SESSION['userName'] = $userData['username'];
        $_SESSION['userEmail'] = $userData['email'];
        $_SESSION['userQq'] = $userData['qq'];
        $_SESSION['cancelRevoked'] = ($cancelState === 'revoked') ? 1 : 0;
        
        exit('{"code":0,"msg":"登录成功"}');
    break;
    
    case 'logout':
        unset($_SESSION['userId']);
        unset($_SESSION['userName']);
        unset($_SESSION['userEmail']);
        unset($_SESSION['userQq']);
        exit('{"code":0,"msg":"退出成功"}');
    break;
    
    case 'send_verify_email':
        $email = addslashes($_POST['email']);
        $tcTicket = isset($_POST['tc_ticket']) ? addslashes($_POST['tc_ticket']) : '';
        $tcRandstr = isset($_POST['tc_randstr']) ? addslashes($_POST['tc_randstr']) : '';
        $captcha = isset($_POST['captcha']) ? strtoupper(addslashes($_POST['captcha'])) : '';
        
        if ($email=='') {
            exit('{"code":-1,"msg":"请输入邮箱"}');
        }
        
        $captchaValid = false;
        
        if (!empty($tcTicket) && !empty($tcRandstr)) {
            $captchaValid = true;
        } elseif (!empty($captcha)) {
            if (isset($_SESSION['captcha']) && strtoupper($_SESSION['captcha']) === $captcha) {
                $captchaValid = true;
                unset($_SESSION['captcha']);
            }
        }
        
        if (!$captchaValid) {
            exit('{"code":-1,"msg":"非法请求，图片验证码错误或超时"}');
        }
        
        $user = $DB->getRow("SELECT * FROM nteam_users WHERE email='$email'");
        
        if (!$user) {
            exit('{"code":-1,"msg":"该邮箱未注册"}');
        }
        
        if ($user['email_verified'] == 1) {
            exit('{"code":-1,"msg":"该邮箱已验证"}');
        }
        
        $verifyCode = random(32);
        $currentTime = date('Y-m-d H:i:s');
        $expireTime = date('Y-m-d H:i:s', strtotime('+3 hours'));
        
        $tokenSql = "INSERT INTO `nteam_tokens` (`user_id`, `token_type`, `token`, `email`, `expire_time`, `create_time`) VALUES ('{$user['id']}', 'email_verify', '$verifyCode', '$email', '$expireTime', '$currentTime')";
        $DB->exec($tokenSql);
        
        $siteName = conf('Name');
        $siteUrl = conf('Mail_Site_Url') ?: conf('Url');
        $verifyUrl = $siteUrl . 'user/verify.php?code=' . $verifyCode . '&email=' . $email;
        
        $mailContent = '<html>
<head>
    <meta charset="UTF-8">
    <title>验证您的邮箱</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
        <h2 style="color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 10px;">验证您的邮箱 - ' . htmlspecialchars($siteName) . '</h2>
        <p>您好，<strong>' . htmlspecialchars($user['username']) . '</strong>：</p>
        <p>请点击以下链接验证您的邮箱：</p><br>
        <p><a href="' . htmlspecialchars($verifyUrl) . '" style="background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">验证邮箱</a></p>
        <br><p>如果链接无法点击，请复制到浏览器地址栏访问：</p>
        <p style="background-color: #f8f9fa; padding: 10px; border-radius: 5px; word-break: break-all;">' . htmlspecialchars($verifyUrl) . '</p>
        <p>此链接有效期为3小时，超过时间后将无法使用。</p>
        <p style="color: #6c757d; font-size: 12px;">此邮件为系统自动发送，请勿回复。</p>
    </div>
</body>
</html>';
        
        $sendResult = send_mail($email, "验证您的邮箱 - {$siteName}", $mailContent);
        
        if ($sendResult === true) {
            exit('{"code":0,"msg":"验证邮件已发送，请查收"}');
        } else {
            exit('{"code":-1,"msg":"邮件发送失败：'.$sendResult.'"}');
        }
    break;
    
    case 'forgot_password':
        $email = addslashes($_POST['email']);
        $tcTicket = isset($_POST['tc_ticket']) ? addslashes($_POST['tc_ticket']) : '';
        $tcRandstr = isset($_POST['tc_randstr']) ? addslashes($_POST['tc_randstr']) : '';
        $captcha = isset($_POST['captcha']) ? strtoupper(addslashes($_POST['captcha'])) : '';
        
        if ($email=='') {
            exit('{"code":-1,"msg":"请输入邮箱"}');
        }
        
        $captchaValid = false;
        
        if (!empty($tcTicket) && !empty($tcRandstr)) {
            $captchaValid = true;
        } elseif (!empty($captcha)) {
            if (isset($_SESSION['captcha']) && strtoupper($_SESSION['captcha']) === $captcha) {
                $captchaValid = true;
                unset($_SESSION['captcha']);
            }
        }
        
        if (!$captchaValid) {
            exit('{"code":-1,"msg":"非法请求，图片验证码错误或超时"}');
        }
        
        $user = $DB->getRow("SELECT * FROM nteam_users WHERE email='$email'");
        
        if (!$user) {
            exit('{"code":-1,"msg":"该邮箱未注册"}');
        }
        
        $resetToken = random(32);
        $currentTime = date('Y-m-d H:i:s');
        $expireTime = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        
        $tokenSql = "INSERT INTO `nteam_tokens` (`user_id`, `token_type`, `token`, `email`, `expire_time`, `create_time`) VALUES ('{$user['id']}', 'password_reset', '$resetToken', '$email', '$expireTime', '$currentTime')";
        $DB->exec($tokenSql);
        
        $siteName = conf('Name');
        $siteUrl = conf('Mail_Site_Url') ?: conf('Url');
        $resetUrl = $siteUrl . 'user/reset_password.php?token=' . $resetToken . '&email=' . $email;
        
        $mailContent = '<html>
<head>
    <meta charset="UTF-8">
    <title>重置密码</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
        <h2 style="color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 10px;">重置密码 - ' . htmlspecialchars($siteName) . '</h2>
        <p>您好，<strong>' . htmlspecialchars($user['username']) . '</strong>：</p>
        <p>我们收到了您的密码重置请求。</p>
        <p>请点击以下链接重置您的密码：</p><br>
        <p><a href="' . htmlspecialchars($resetUrl) . '" style="background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">重置密码</a></p>
        <br><p>如果链接无法点击，请复制到浏览器地址栏访问：</p>
        <p style="background-color: #f8f9fa; padding: 10px; border-radius: 5px; word-break: break-all;">' . htmlspecialchars($resetUrl) . '</p>
        <p>此链接有效期为30分钟，超过时间后将无法使用。</p>
        <p>如果您没有请求重置密码，请忽略此邮件。</p>
        <p style="color: #6c757d; font-size: 12px;">此邮件为系统自动发送，请勿回复。</p>
    </div>
</body>
</html>';
        
        $sendResult = send_mail($email, "重置密码 - {$siteName}", $mailContent);
        
        if ($sendResult === true) {
            exit('{"code":0,"msg":"重置邮件已发送，请查收"}');
        } else {
            exit('{"code":-1,"msg":"邮件发送失败：'.$sendResult.'"}');
        }
    break;
    
    case 'reset_password':
        $token = isset($_POST['token']) ? addslashes($_POST['token']) : '';
        $email = isset($_POST['email']) ? addslashes($_POST['email']) : '';
        $newPassword = md5($_POST['new_password']);
        
        if ($token=='') {
            exit('{"code":-1,"msg":"参数错误"}');
        }
        
        if ($email=='') {
            exit('{"code":-1,"msg":"邮箱参数错误"}');
        }
        
        if ($newPassword=='') {
            exit('{"code":-1,"msg":"请输入新密码"}');
        }
        
        if (strlen($_POST['new_password']) < 6) {
            exit('{"code":-1,"msg":"密码长度不能少于6位"}');
        }
        
        if ($_POST['new_password'] !== $_POST['confirm_password']) {
            exit('{"code":-1,"msg":"两次输入的密码不一致"}');
        }
        
        $currentTime = date('Y-m-d H:i:s');
        $tokenData = $DB->getRow("SELECT * FROM nteam_tokens WHERE token='$token' AND email='$email' AND token_type='password_reset' AND used=0 AND expire_time>'$currentTime'");
        
        if (!$tokenData) {
            exit('{"code":-1,"msg":"重置链接无效或已过期"}');
        }
        
        $user = $DB->getRow("SELECT * FROM nteam_users WHERE id='{$tokenData['user_id']}'");
        
        if (!$user) {
            exit('{"code":-1,"msg":"用户不存在"}');
        }
        
        $updateSql = "UPDATE nteam_users SET password='$newPassword' WHERE id='{$user['id']}'";
        $tokenUpdateSql = "UPDATE nteam_tokens SET used=1 WHERE id='{$tokenData['id']}'";
        
        try {
            $DB->exec($updateSql);
            $DB->exec($tokenUpdateSql);
            exit('{"code":0,"msg":"密码重置成功，请登录"}');
        } catch (Exception $e) {
            error_log("密码重置失败: " . $e->getMessage());
            exit('{"code":-1,"msg":"密码重置失败，请稍后重试"}');
        }
    break;
    
    case 'change_password':
        if(!isset($_SESSION['userId'])){
            exit('{"code":-1,"msg":"请先登录"}');
        }
        
        $userId = $_SESSION['userId'];
        $oldPassword = md5($_POST['old_password']);
        $newPassword = md5($_POST['new_password']);
        $tcTicket = isset($_POST['tc_ticket']) ? addslashes($_POST['tc_ticket']) : '';
        $tcRandstr = isset($_POST['tc_randstr']) ? addslashes($_POST['tc_randstr']) : '';
        $captcha = isset($_POST['captcha']) ? strtoupper(addslashes($_POST['captcha'])) : '';
        
        $captchaValid = false;
        
        if (!empty($tcTicket) && !empty($tcRandstr)) {
            $captchaValid = true;
        } elseif (!empty($captcha)) {
            if (isset($_SESSION['captcha']) && strtoupper($_SESSION['captcha']) === $captcha) {
                $captchaValid = true;
                unset($_SESSION['captcha']);
            }
        }
        
        if (!$captchaValid) {
            exit('{"code":-1,"msg":"非法请求，图片验证码错误或超时"}');
        }
        
        if ($oldPassword=='' || $newPassword=='') {
            exit('{"code":-1,"msg":"请确保每项都不为空"}');
        }
        
        if (strlen($_POST['new_password']) < 6) {
            exit('{"code":-1,"msg":"新密码长度不能少于6位"}');
        }
        
        if ($_POST['old_password'] === $_POST['new_password']) {
            exit('{"code":-1,"msg":"新密码不能与旧密码相同"}');
        }
        
        $userData = $DB->getRow("SELECT * FROM nteam_users WHERE id='$userId'");
        
        if (!$userData) {
            exit('{"code":-1,"msg":"用户不存在"}');
        }
        
        if ($oldPassword != $userData['password']) {
            exit('{"code":-1,"msg":"旧密码错误"}');
        }
        
        $updateSql = "UPDATE nteam_users SET password='$newPassword' WHERE id='$userId'";
        
        if ($DB->exec($updateSql) !== false) {
            unset($_SESSION['userId']);
            unset($_SESSION['userName']);
            unset($_SESSION['userEmail']);
            unset($_SESSION['userQq']);
            exit('{"code":0,"msg":"密码修改成功，请重新登录"}');
        } else {
            exit('{"code":-1,"msg":"密码修改失败，请稍后重试"}');
        }
    break;
    
    case 'bind_academic':
        if(!isset($_SESSION['userId'])){
            exit('{"code":-1,"msg":"请先登录"}');
        }
        
        $userId = $_SESSION['userId'];
        $studentId = addslashes(trim($_POST['student_id']));
        $password = $_POST['password'];
        $realName = isset($_POST['real_name']) ? addslashes(trim($_POST['real_name'])) : '';
        
        if ($studentId == '' || $password == '') {
            exit('{"code":-1,"msg":"学工号和密码不能为空"}');
        }
        
        if (strlen($studentId) > 50) {
            exit('{"code":-1,"msg":"学工号格式不正确"}');
        }
        
        $existing = $DB->getRow("SELECT * FROM nteam_academic_accounts WHERE user_id='$userId'");
        if ($existing) {
            exit('{"code":-1,"msg":"您已绑定过教务账号，请先解绑后再重新绑定"}');
        }
        
        $checkStudentId = $DB->getRow("SELECT * FROM nteam_academic_accounts WHERE student_id='$studentId'");
        if ($checkStudentId) {
            exit('{"code":-1,"msg":"该学工号已被其他账号绑定"}');
        }
        
        require_once('../funsion/AcademicSystem.php');
        
        $loginResult = AcademicSystem::login($studentId, $password);
        
        if (!isset($loginResult['code']) || $loginResult['code'] != 1) {
            $errorMsg = $loginResult['Msg'] ?? $loginResult['msg'] ?? '教务账号验证失败，请检查学工号和密码';
            exit('{"code":-1,"msg":"' . $errorMsg . '"}');
        }
        
        $token = $loginResult['data']['token'] ?? '';
        $currentTime = date('Y-m-d H:i:s');
        
        if (empty($realName)) {
            $infoResult = AcademicSystem::getStudentInfo($token);
            if (isset($infoResult['code']) && $infoResult['code'] == 1 && !empty($infoResult['data'])) {
                $realName = $infoResult['data'][0]['xm'] ?? '';
            }
        }
        
        $realNameValue = empty($realName) ? 'NULL' : "'" . addslashes($realName) . "'";
        $tokenValue = empty($token) ? 'NULL' : "'" . addslashes($token) . "'";
        
        $sql = "INSERT INTO `nteam_academic_accounts` (`user_id`, `student_id`, `password`, `real_name`, `token`, `token_time`, `bind_time`) VALUES ('$userId', '$studentId', '$password', $realNameValue, $tokenValue, '$currentTime', '$currentTime')";
        
        if ($DB->exec($sql)) {
            exit('{"code":0,"msg":"教务账号绑定成功"}');
        } else {
            exit('{"code":-1,"msg":"绑定失败，请稍后重试"}');
        }
    break;
    
    case 'unbind_academic':
        if(!isset($_SESSION['userId'])){
            exit('{"code":-1,"msg":"请先登录"}');
        }
        
        $userId = $_SESSION['userId'];
        
        $existing = $DB->getRow("SELECT * FROM nteam_academic_accounts WHERE user_id='$userId'");
        if (!$existing) {
            exit('{"code":-1,"msg":"您还未绑定教务账号"}');
        }
        
        $sql = "DELETE FROM `nteam_academic_accounts` WHERE user_id='$userId'";
        
        if ($DB->exec($sql)) {
            exit('{"code":0,"msg":"教务账号解绑成功"}');
        } else {
            exit('{"code":-1,"msg":"解绑失败，请稍后重试"}');
        }
    break;
    
    case 'update_academic':
        if(!isset($_SESSION['userId'])){
            exit('{"code":-1,"msg":"请先登录"}');
        }
        
        $userId = $_SESSION['userId'];
        $studentId = addslashes(trim($_POST['student_id']));
        $password = $_POST['password'];
        $realName = isset($_POST['real_name']) ? addslashes(trim($_POST['real_name'])) : '';
        
        if ($studentId == '' || $password == '') {
            exit('{"code":-1,"msg":"学工号和密码不能为空"}');
        }
        
        $existing = $DB->getRow("SELECT * FROM nteam_academic_accounts WHERE user_id='$userId'");
        if (!$existing) {
            exit('{"code":-1,"msg":"您还未绑定教务账号"}');
        }
        
        $checkStudentId = $DB->getRow("SELECT * FROM nteam_academic_accounts WHERE student_id='$studentId' AND user_id != '$userId'");
        if ($checkStudentId) {
            exit('{"code":-1,"msg":"该学工号已被其他账号绑定"}');
        }
        
        require_once('../funsion/AcademicSystem.php');
        
        $loginResult = AcademicSystem::login($studentId, $password);
        
        if (!isset($loginResult['code']) || $loginResult['code'] != 1) {
            $errorMsg = $loginResult['Msg'] ?? $loginResult['msg'] ?? '教务账号验证失败，请检查学工号和密码';
            exit('{"code":-1,"msg":"' . $errorMsg . '"}');
        }
        
        $token = $loginResult['data']['token'] ?? '';
        $currentTime = date('Y-m-d H:i:s');
        
        if (empty($realName)) {
            $infoResult = AcademicSystem::getStudentInfo($token);
            if (isset($infoResult['code']) && $infoResult['code'] == 1 && !empty($infoResult['data'])) {
                $realName = $infoResult['data'][0]['xm'] ?? '';
            }
        }
        
        $realNameValue = empty($realName) ? 'NULL' : "'" . addslashes($realName) . "'";
        $tokenValue = empty($token) ? 'NULL' : "'" . addslashes($token) . "'";
        
        $sql = "UPDATE `nteam_academic_accounts` SET `student_id`='$studentId', `password`='$password', `real_name`=$realNameValue, `token`=$tokenValue, `token_time`='$currentTime' WHERE user_id='$userId'";
        
        if ($DB->exec($sql) !== false) {
            exit('{"code":0,"msg":"教务账号更新成功"}');
        } else {
            exit('{"code":-1,"msg":"更新失败，请稍后重试"}');
        }
    break;
    
    case 'get_academic':
        if(!isset($_SESSION['userId'])){
            exit('{"code":-1,"msg":"请先登录"}');
        }
        
        $userId = $_SESSION['userId'];
        
        $account = $DB->getRow("SELECT * FROM nteam_academic_accounts WHERE user_id='$userId'");
        
        if ($account) {
            exit(json_encode([
                'code' => 0,
                'msg' => '获取成功',
                'data' => [
                    'student_id' => $account['student_id'],
                    'password' => $account['password'],
                    'real_name' => $account['real_name'],
                    'bind_time' => $account['bind_time']
                ]
            ]));
        } else {
            exit('{"code":-1,"msg":"未绑定教务账号"}');
        }
    break;
    
    case 'get_dorm_areas':
        try {
            $pdo = new PDO("mysql:host={$dbconfig['host']};port={$dbconfig['port']};dbname={$dbconfig['dbname']};charset=utf8mb4", $dbconfig['user'], $dbconfig['pwd']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $result = DormitorySystem::getAreas($pdo);
            exit(json_encode($result));
        } catch (PDOException $e) {
            exit('{"code":0,"msg":"数据库连接失败"}');
        }
    break;
    
    case 'get_dorm_buildings':
        $area = isset($_GET['area']) ? $_GET['area'] : '';
        if (empty($area)) {
            exit('{"code":0,"msg":"参数错误"}');
        }
        
        try {
            $pdo = new PDO("mysql:host={$dbconfig['host']};port={$dbconfig['port']};dbname={$dbconfig['dbname']};charset=utf8mb4", $dbconfig['user'], $dbconfig['pwd']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $result = DormitorySystem::getBuildings($pdo, $area);
            exit(json_encode($result));
        } catch (PDOException $e) {
            exit('{"code":0,"msg":"数据库连接失败"}');
        }
    break;
    
    case 'get_dorm_rooms':
        $area = isset($_GET['area']) ? $_GET['area'] : '';
        $buildingNo = isset($_GET['buildingNo']) ? $_GET['buildingNo'] : '';
        
        if (empty($area)) {
            exit('{"code":0,"msg":"参数错误"}');
        }
        
        try {
            $pdo = new PDO("mysql:host={$dbconfig['host']};port={$dbconfig['port']};dbname={$dbconfig['dbname']};charset=utf8mb4", $dbconfig['user'], $dbconfig['pwd']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $result = DormitorySystem::getRooms($pdo, $area, $buildingNo);
            exit(json_encode($result));
        } catch (PDOException $e) {
            exit('{"code":0,"msg":"数据库连接失败"}');
        }
    break;
    
    case 'search_dorm_rooms':
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
        if (empty($keyword)) {
            exit('{"code":0,"msg":"请输入搜索关键词"}');
        }
        
        try {
            $pdo = new PDO("mysql:host={$dbconfig['host']};port={$dbconfig['port']};dbname={$dbconfig['dbname']};charset=utf8mb4", $dbconfig['user'], $dbconfig['pwd']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $result = DormitorySystem::searchRooms($pdo, $keyword);
            exit(json_encode($result));
        } catch (PDOException $e) {
            exit('{"code":0,"msg":"数据库连接失败"}');
        }
    break;
    
    case 'bind_dormitory':
        if(!isset($_SESSION['userId'])){
            exit('{"code":-1,"msg":"请先登录"}');
        }
        
        $userId = $_SESSION['userId'];
        $roomNum = isset($_POST['room_num']) ? addslashes(trim($_POST['room_num'])) : '';
        $alias = isset($_POST['alias']) ? addslashes(trim($_POST['alias'])) : '';
        
        if (empty($roomNum)) {
            exit('{"code":-1,"msg":"请选择要绑定的宿舍"}');
        }
        
        $result = DormitorySystem::bindDormitory($DB, $userId, $roomNum, $alias);
        exit(json_encode($result));
    break;
    
    case 'unbind_dormitory':
        if(!isset($_SESSION['userId'])){
            exit('{"code":-1,"msg":"请先登录"}');
        }
        
        $userId = $_SESSION['userId'];
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($id <= 0) {
            exit('{"code":-1,"msg":"参数错误"}');
        }
        
        $result = DormitorySystem::unbindDormitory($DB, $userId, $id);
        exit(json_encode($result));
    break;
    
    case 'set_default_dormitory':
        if(!isset($_SESSION['userId'])){
            exit('{"code":-1,"msg":"请先登录"}');
        }
        
        $userId = $_SESSION['userId'];
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if ($id <= 0) {
            exit('{"code":-1,"msg":"参数错误"}');
        }
        
        $result = DormitorySystem::setDefaultDormitory($DB, $userId, $id);
        exit(json_encode($result));
    break;
    
    case 'update_dormitory_alias':
        if(!isset($_SESSION['userId'])){
            exit('{"code":-1,"msg":"请先登录"}');
        }
        
        $userId = $_SESSION['userId'];
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $alias = isset($_POST['alias']) ? addslashes(trim($_POST['alias'])) : '';
        
        if ($id <= 0) {
            exit('{"code":-1,"msg":"参数错误"}');
        }
        
        $result = DormitorySystem::updateDormitoryAlias($DB, $userId, $id, $alias);
        exit(json_encode($result));
    break;
    
    case 'get_buildings':
        if(!isset($_SESSION['userId'])){
            exit('{"code":-1,"msg":"请先登录"}');
        }
        
        $area = isset($_POST['area']) ? trim($_POST['area']) : '';
        
        if (empty($area)) {
            exit('{"code":-1,"msg":"请选择校区"}');
        }
        
        try {
            $pdo = new PDO("mysql:host={$dbconfig['host']};port={$dbconfig['port']};dbname={$dbconfig['dbname']};charset=utf8mb4", $dbconfig['user'], $dbconfig['pwd']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $result = DormitorySystem::getBuildings($pdo, $area);
            exit(json_encode($result));
        } catch (PDOException $e) {
            exit('{"code":-1,"msg":"数据库连接失败"}');
        }
    break;
    
    case 'get_rooms':
        if(!isset($_SESSION['userId'])){
            exit('{"code":-1,"msg":"请先登录"}');
        }
        
        $area = isset($_POST['area']) ? trim($_POST['area']) : '';
        $buildingNo = isset($_POST['building_no']) ? trim($_POST['building_no']) : '';
        
        if (empty($area) || empty($buildingNo)) {
            exit('{"code":-1,"msg":"请选择校区和楼栋"}');
        }
        
        try {
            $pdo = new PDO("mysql:host={$dbconfig['host']};port={$dbconfig['port']};dbname={$dbconfig['dbname']};charset=utf8mb4", $dbconfig['user'], $dbconfig['pwd']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $result = DormitorySystem::getRooms($pdo, $area, $buildingNo);
            exit(json_encode($result));
        } catch (PDOException $e) {
            exit('{"code":-1,"msg":"数据库连接失败"}');
        }
    break;
    
    case 'query_power_fee':
        if(!isset($_SESSION['userId'])){
            exit('{"code":-1,"msg":"请先登录"}');
        }
        
        $roomNum = isset($_POST['room_num']) ? trim($_POST['room_num']) : '';
        $schoolAreaNo = isset($_POST['school_area_no']) ? trim($_POST['school_area_no']) : '';
        $buildingNo = isset($_POST['building_no']) ? trim($_POST['building_no']) : '';
        
        if (empty($roomNum)) {
            exit('{"code":-1,"msg":"请选择要查询的宿舍"}');
        }
        
        $result = DormitorySystem::getPowerFee($roomNum, $schoolAreaNo, $buildingNo);
        
        if ($result['code'] == 1) {
            $balance = $result['data']['balance'];
            
            $powerConfig = $DB->getRow("SELECT * FROM nteam_power_config WHERE id = 1");
            $pricePerKwh = $powerConfig ? floatval($powerConfig['price_per_kwh']) : 0.61;
            
            $remainingKwh = null;
            if ($balance !== null && $balance >= 0 && $pricePerKwh > 0) {
                $remainingKwh = round($balance / $pricePerKwh, 2);
            }
            
            $result['data']['remaining_kwh'] = $remainingKwh;
            $result['data']['price_per_kwh'] = $pricePerKwh;
            
            $startTime = date('Y-m-d H:i:s', strtotime('-24 hours'));
            $records = $DB->getAll("SELECT * FROM nteam_power_daily_records 
                WHERE room_num = '$roomNum' AND record_time >= '$startTime' 
                ORDER BY record_time ASC");
            
            $estimatedDays = null;
            if (count($records) >= 5) {
                $firstRecord = $records[0];
                $lastRecord = $records[count($records) - 1];
                
                $firstBalance = floatval($firstRecord['balance']);
                $lastBalance = floatval($lastRecord['balance']);
                
                if ($firstBalance > $lastBalance) {
                    $balanceDiff = $firstBalance - $lastBalance;
                    
                    $firstTime = strtotime($firstRecord['record_time']);
                    $lastTime = strtotime($lastRecord['record_time']);
                    $hoursDiff = ($lastTime - $firstTime) / 3600;
                    
                    if ($hoursDiff > 0) {
                        $avgUsagePerHour = $balanceDiff / $hoursDiff;
                        
                        if ($avgUsagePerHour > 0) {
                            $estimatedHours = $balance / $avgUsagePerHour;
                            $estimatedDays = round($estimatedHours / 24, 1);
                        }
                    }
                }
            }
            
            $result['data']['estimated_days'] = $estimatedDays;
        }
        
        exit(json_encode($result));
    break;
    
    case 'update_profile':
        if(!isset($_SESSION['userId'])){
            exit('{"code":-1,"msg":"请先登录"}');
        }
        
        $userId = $_SESSION['userId'];
        $nickname = isset($_POST['nickname']) ? addslashes(trim($_POST['nickname'])) : '';
        $avatar = isset($_POST['avatar']) ? addslashes(trim($_POST['avatar'])) : '';
        
        $updates = [];
        
        if (!empty($nickname)) {
            if (mb_strlen($nickname) > 20) {
                exit('{"code":-1,"msg":"昵称不能超过20个字符"}');
            }
            $updates[] = "nickname='$nickname'";
        }
        
        if (!empty($avatar)) {
            $updates[] = "avatar='$avatar'";
        }
        
        if (empty($updates)) {
            exit('{"code":0,"msg":"保存成功"}');
        }
        
        $sql = "UPDATE nteam_users SET " . implode(', ', $updates) . " WHERE id='$userId'";
        
        if ($DB->exec($sql) !== false) {
            exit('{"code":0,"msg":"保存成功"}');
        } else {
            exit('{"code":-1,"msg":"更新失败"}');
        }
    break;
    
    case 'upload_avatar':
        if(!isset($_SESSION['userId'])){
            exit('{"code":-1,"msg":"请先登录"}');
        }
        
        $userId = $_SESSION['userId'];
        
        if (!isset($_FILES['avatar'])) {
            exit('{"code":-1,"msg":"请选择要上传的图片"}');
        }
        
        $file = $_FILES['avatar'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            exit('{"code":-1,"msg":"文件上传失败"}');
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            exit('{"code":-1,"msg":"只支持JPG、PNG、GIF、WEBP格式的图片"}');
        }
        
        if ($file['size'] > 2 * 1024 * 1024) {
            exit('{"code":-1,"msg":"图片大小不能超过2MB"}');
        }
        
        $uploadDir = __DIR__ . '/../uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $avatarUrl = '/uploads/avatars/' . $filename;
            $DB->exec("UPDATE nteam_users SET avatar='$avatarUrl' WHERE id='$userId'");
            $displayUrl = $siteBasePath . $avatarUrl;
            exit('{"code":0,"msg":"上传成功","data":{"avatar":"' . $avatarUrl . '","displayUrl":"' . $displayUrl . '"}}');
        } else {
            exit('{"code":-1,"msg":"文件保存失败"}');
        }
    break;
    
    case 'get_push_settings':
        if(!isset($_SESSION['userId'])){
            exit('{"code":-1,"msg":"请先登录"}');
        }
        
        $userId = $_SESSION['userId'];
        
        require_once '../funsion/feature_settings.php';
        if (!isFeatureEnabled($DB, 'push_notification')) {
            exit('{"code":-1,"msg":"推送功能暂未开放"}');
        }
        
        $bindings = $DB->getAll("SELECT * FROM nteam_dormitory_bindings WHERE user_id='$userId' AND status=1 ORDER BY is_default DESC, id DESC");
        
        $settings = $DB->getAll("SELECT * FROM nteam_push_settings WHERE user_id='$userId'");
        
        $settingsMap = [];
        if ($settings) {
            foreach ($settings as $s) {
                $settingsMap[$s['dormitory_binding_id']] = $s;
            }
        }
        
        $result = [];
        if ($bindings) {
            foreach ($bindings as $b) {
                $s = isset($settingsMap[$b['id']]) ? $settingsMap[$b['id']] : null;
                $result[] = [
                    'binding_id' => $b['id'],
                    'user_id' => $b['user_id'],
                    'room_num' => $b['room_num'],
                    'school_area_no' => $b['school_area_no'] ?? '',
                    'building_no' => $b['building_no'] ?? '',
                    'building' => $b['building'] ?? '',
                    'room' => $b['room'] ?? '',
                    'alias' => $b['alias'] ?? '',
                    'is_default' => $b['is_default'],
                    'push_types' => $s ? $s['push_types'] : 'wechat',
                    'notify_types' => $s ? $s['notify_types'] : '',
                    'balance_low_threshold' => $s ? $s['balance_low_threshold'] : 30,
                    'balance_critical_threshold' => $s ? $s['balance_critical_threshold'] : 10,
                    'is_enabled' => $s ? $s['is_enabled'] : 0
                ];
            }
        }
        
        $config = $DB->getRow("SELECT * FROM nteam_power_config WHERE id = 1");
        
        exit(json_encode([
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'bindings' => $result,
                'config' => $config ?: []
            ]
        ]));
    break;
    
    case 'save_push_settings':
        if(!isset($_SESSION['userId'])){
            exit('{"code":-1,"msg":"请先登录"}');
        }
        
        $userId = $_SESSION['userId'];
        
        require_once '../funsion/feature_settings.php';
        if (!isFeatureEnabled($DB, 'push_notification')) {
            exit('{"code":-1,"msg":"推送功能暂未开放"}');
        }
        
        $dormitoryBindingId = isset($_POST['dormitory_binding_id']) ? intval($_POST['dormitory_binding_id']) : 0;
        $pushTypes = isset($_POST['push_types']) ? addslashes($_POST['push_types']) : 'wechat';
        $notifyTypes = isset($_POST['notify_types']) ? addslashes($_POST['notify_types']) : '';
        $balanceLowThreshold = isset($_POST['balance_low_threshold']) ? floatval($_POST['balance_low_threshold']) : 30;
        $balanceCriticalThreshold = isset($_POST['balance_critical_threshold']) ? floatval($_POST['balance_critical_threshold']) : 10;
        $isEnabled = isset($_POST['is_enabled']) ? intval($_POST['is_enabled']) : 1;
        
        if ($dormitoryBindingId <= 0) {
            exit('{"code":-1,"msg":"请选择宿舍"}');
        }
        
        $binding = $DB->getRow("SELECT * FROM nteam_dormitory_bindings WHERE id='$dormitoryBindingId' AND user_id='$userId' AND status=1");
        if (!$binding) {
            exit('{"code":-1,"msg":"宿舍绑定不存在"}');
        }
        
        $exists = $DB->getRow("SELECT id FROM nteam_push_settings WHERE user_id='$userId' AND dormitory_binding_id='$dormitoryBindingId'");
        
        if ($exists) {
            $sql = "UPDATE nteam_push_settings SET 
                push_types = '$pushTypes',
                notify_types = '$notifyTypes',
                balance_low_threshold = '$balanceLowThreshold',
                balance_critical_threshold = '$balanceCriticalThreshold',
                is_enabled = '$isEnabled',
                updated_at = NOW()
                WHERE id = '{$exists['id']}'";
        } else {
            $sql = "INSERT INTO nteam_push_settings 
                (user_id, dormitory_binding_id, push_types, notify_types, balance_low_threshold, balance_critical_threshold, is_enabled)
                VALUES ('$userId', '$dormitoryBindingId', '$pushTypes', '$notifyTypes', '$balanceLowThreshold', '$balanceCriticalThreshold', '$isEnabled')";
        }
        
        if ($DB->exec($sql)) {
            exit('{"code":0,"msg":"保存成功"}');
        } else {
            exit('{"code":-1,"msg":"保存失败"}');
        }
    break;
    
    case 'save_academic_push_settings':
        $userId = $_SESSION['userId'] ?? 0;
        if (!$userId) {
            exit('{"code":-2,"msg":"请先登录"}');
        }
        
        $academicAccount = $DB->getRow("SELECT id FROM nteam_academic_accounts WHERE user_id='$userId'");
        if (!$academicAccount) {
            exit('{"code":-1,"msg":"请先绑定教务账号"}');
        }
        
        $academicAccountId = $academicAccount['id'];
        $pushTypes = isset($_POST['push_types']) ? addslashes($_POST['push_types']) : 'wechat';
        $scheduleReminderEnabled = isset($_POST['schedule_reminder_enabled']) ? intval($_POST['schedule_reminder_enabled']) : 1;
        $scheduleReminderTime = isset($_POST['schedule_reminder_time']) ? addslashes($_POST['schedule_reminder_time']) : '20:00';
        $gradeNotifyEnabled = isset($_POST['grade_notify_enabled']) ? intval($_POST['grade_notify_enabled']) : 1;
        $isEnabled = isset($_POST['is_enabled']) ? intval($_POST['is_enabled']) : 1;
        
        if (strlen($scheduleReminderTime) == 5) {
            $scheduleReminderTime .= ':00';
        }
        
        $exists = $DB->getRow("SELECT id FROM nteam_academic_push_settings WHERE user_id='$userId' AND academic_account_id='$academicAccountId'");
        
        if ($exists) {
            $sql = "UPDATE nteam_academic_push_settings SET 
                push_types = '$pushTypes',
                schedule_reminder_enabled = '$scheduleReminderEnabled',
                schedule_reminder_time = '$scheduleReminderTime',
                grade_notify_enabled = '$gradeNotifyEnabled',
                is_enabled = '$isEnabled',
                updated_at = NOW()
                WHERE id = '{$exists['id']}'";
        } else {
            $sql = "INSERT INTO nteam_academic_push_settings 
                (user_id, academic_account_id, push_types, schedule_reminder_enabled, schedule_reminder_time, grade_notify_enabled, is_enabled)
                VALUES ('$userId', '$academicAccountId', '$pushTypes', '$scheduleReminderEnabled', '$scheduleReminderTime', '$gradeNotifyEnabled', '$isEnabled')";
        }
        
        if ($DB->exec($sql)) {
            exit('{"code":0,"msg":"保存成功"}');
        } else {
            exit('{"code":-1,"msg":"保存失败"}');
        }
    break;
    
    case 'get_academic_push_settings':
        $userId = $_SESSION['userId'] ?? 0;
        if (!$userId) {
            exit('{"code":-2,"msg":"请先登录"}');
        }
        
        $settings = $DB->getRow("SELECT * FROM nteam_academic_push_settings WHERE user_id='$userId'");
        $academicAccount = $DB->getRow("SELECT student_id FROM nteam_academic_accounts WHERE user_id='$userId'");
        
        exit(json_encode([
            'code' => 0,
            'data' => [
                'settings' => $settings,
                'student_id' => $academicAccount['student_id'] ?? null,
                'has_academic_account' => $academicAccount ? true : false
            ]
        ]));
    break;
    
    case 'ai_analyze_grades':
        if (!isset($_SESSION['userId'])) {
            exit(json_encode(['code' => -1, 'msg' => '请先登录']));
        }
        $raw = isset($_POST['data']) ? $_POST['data'] : '';
        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['achievement'])) {
            exit(json_encode(['code' => -1, 'msg' => '暂无成绩数据']));
        }

        // 建表（幂等）
        $DB->exec("CREATE TABLE IF NOT EXISTS nteam_ai_grade_analysis (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            semester VARCHAR(255) NOT NULL DEFAULT '',
            data_hash VARCHAR(64) NOT NULL DEFAULT '',
            content MEDIUMTEXT NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uniq_user_semester (user_id, semester)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $userId = intval($_SESSION['userId']);
        $semester = isset($data['semester']) ? strval($data['semester']) : '';
        $dataHash = md5($raw);

        // 命中缓存且数据未变化则直接返回，避免重复生成
        $cached = $DB->getRow("SELECT content, data_hash FROM nteam_ai_grade_analysis WHERE user_id=? AND semester=?", [$userId, $semester]);
        $cacheValid = $cached && $cached['data_hash'] === $dataHash && trim($cached['content']) !== '' && $cached['content'] !== '暂无分析结果';
        if ($cacheValid) {
            exit(json_encode(['code' => 0, 'msg' => 'ok', 'content' => $cached['content'], 'cached' => 1]));
        }

        $lines = [];
        foreach ($data['achievement'] as $c) {
            $lines[] = sprintf(
                "- %s：成绩 %s，学分 %s，绩点 %s，属性 %s",
                $c['courseName'] ?? '',
                $c['fraction'] ?? '-',
                $c['credit'] ?? '-',
                $c['gpa'] ?? '-',
                $c['curriculumAttributes'] ?? '-'
            );
        }
        $summary = implode("\n", $lines);
        $gpa = $data['gpa'] ?? '-';
        $credit = $data['credit'] ?? '-';
        $semesterText = $semester ? "（学期：{$semester}）" : '';
        $prompt = "你是一位大学学业指导顾问。以下是某学生" . $semesterText . "的成绩数据：\n"
            . "平均成绩绩点(GPA)：{$gpa}，已修学分：{$credit}。\n课程明细：\n{$summary}\n\n"
            . "请对以上成绩进行智能分析，包含：1）整体学业表现评价；2）优势科目与薄弱科目；"
            . "3）针对薄弱科目的改进建议；4）下一阶段学习规划建议。请用简体中文、分点清晰作答，可使用 Markdown。\n"
            . "重要说明：若同一课程出现多条成绩记录（如多次录入），它们通常为补考或重修数据，请据此正确理解并综合分析。";
        $messages = [
            ['role' => 'system', 'content' => '你是一位专业、耐心的大学生学习与学业规划顾问。'],
            ['role' => 'user', 'content' => $prompt],
        ];
        $ai = new \lib\AiHelper();
        $result = $ai->chat($messages);
        if (!$result['success']) {
            exit(json_encode(['code' => -1, 'msg' => $result['msg']]));
        }
        $content = isset($result['content']) ? trim($result['content']) : '';
        if ($content === '') {
            exit(json_encode(['code' => -1, 'msg' => '分析结果为空，请重试']));
        }
        $now = date('Y-m-d H:i:s');
        if ($cached) {
            $DB->exec("UPDATE nteam_ai_grade_analysis SET content=?, data_hash=?, updated_at=? WHERE user_id=? AND semester=?",
                [$content, $dataHash, $now, $userId, $semester]);
        } else {
            $DB->exec("INSERT INTO nteam_ai_grade_analysis (user_id, semester, data_hash, content, created_at, updated_at) VALUES (?,?,?,?,?,?)",
                [$userId, $semester, $dataHash, $content, $now, $now]);
        }
        exit(json_encode(['code' => 0, 'msg' => 'ok', 'content' => $content, 'cached' => 0]));
    break;

    case 'ai_analyze_power':
        if (!isset($_SESSION['userId'])) {
            exit(json_encode(['code' => -1, 'msg' => '请先登录']));
        }
        $userId = intval($_SESSION['userId']);
        $raw = isset($_POST['data']) ? $_POST['data'] : '';
        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['type'])) {
            exit(json_encode(['code' => -1, 'msg' => '暂无电费数据']));
        }

        $type = $data['type'];
        if ($type === 'query') {
            $rooms = isset($data['rooms']) ? $data['rooms'] : [];
            if (!is_array($rooms) || count($rooms) === 0) {
                exit(json_encode(['code' => -1, 'msg' => '暂无电费数据']));
            }
            $lines = [];
            foreach ($rooms as $i => $r) {
                $lines[] = sprintf(
                    "宿舍%d：%s，余额 %s 元，剩余电量 %s 度，电价 %s 元/度，预计可用 %s 天，状态：%s，统计时段：%s",
                    ($i + 1),
                    $r['name'] ?? '未知',
                    ($r['balance'] !== null && $r['balance'] !== '') ? $r['balance'] : '未知',
                    ($r['remainingKwh'] !== null && $r['remainingKwh'] !== '') ? $r['remainingKwh'] : '未知',
                    $r['pricePerKwh'] ?? '未知',
                    ($r['estimatedDays'] !== null && $r['estimatedDays'] !== '') ? $r['estimatedDays'] : '未知',
                    $r['statusText'] ?? '未知',
                    $r['timeRangeText'] ?? '未知'
                );
            }
            $summary = implode("\n", $lines);
            $prompt = "你是一位校园生活用电管理顾问。以下是某学生宿舍的电费查询结果：\n"
                . $summary . "\n\n"
                . "请基于以上数据，给出：1）当前用电/余额状况评价；2）是否存在欠费或电量不足风险；"
                . "3）节能与用电建议；4）是否需要尽快充值及建议充值金额。请用简体中文、分点清晰作答，可使用 Markdown。";
        } elseif ($type === 'statistics') {
            $dorm = $data['dormitory'] ?? '未知';
            $stats = $data['stats'] ?? [];
            $recent = $data['recentTrend'] ?? [];
            $records = $data['records'] ?? [];
            $recentLines = [];
            foreach (array_slice($recent, -15) as $p) {
                $recentLines[] = sprintf("- %s：用电量 %s 度", $p['label'] ?? $p['time'] ?? '', $p['usage'] ?? '');
            }
            $recordLines = [];
            foreach (array_slice($records, 0, 15) as $r) {
                $recordLines[] = sprintf(
                    "- %s：余额 %s 元，用电 %s 度，费用 %s 元",
                    $r['time'] ?? '',
                    $r['balance'] ?? '',
                    $r['usage'] ?? '',
                    $r['cost'] ?? ''
                );
            }
            $prompt = "你是一位校园用电数据分析师。以下是某宿舍（{$dorm}）的用电统计数据：\n"
                . "用电健康度：{$stats['healthText']}；昨日用电量：{$stats['yesterdayUsage']} 度（约 ¥{$stats['yesterdayCost']}）；"
                . "日均用电量：{$stats['dailyAvgUsage']} 度（约 ¥{$stats['dailyAvgCost']}/天）；"
                . "当前余额：{$stats['latestBalance']} 元；预计可用：{$stats['estimatedDays']} 天。\n";
            if ($recentLines) {
                $prompt .= "\n近期用电量趋势：\n" . implode("\n", $recentLines) . "\n";
            }
            if ($recordLines) {
                $prompt .= "\n部分电费记录：\n" . implode("\n", $recordLines) . "\n";
            }
            $prompt .= "\n请基于以上数据，给出：1）用电规律与趋势分析；2）用电高峰/异常提示；"
                . "3）节能降耗建议；4）充值与预算建议。请用简体中文、分点清晰作答，可使用 Markdown。";
        } else {
            exit(json_encode(['code' => -1, 'msg' => '暂不支持的分析类型']));
        }

        $messages = [
            ['role' => 'system', 'content' => '你是一位专业、贴心的校园用电管理顾问，擅长从用电数据中给出实用建议。'],
            ['role' => 'user', 'content' => $prompt],
        ];
        $ai = new \lib\AiHelper();
        $dataHash = md5($raw);
        $bizType = ($type === 'statistics') ? 'power_statistics' : 'power_query';
        $result = $ai->analyzeCached($DB, $userId, $bizType, $dataHash, $messages);
        if (!$result['success']) {
            exit(json_encode(['code' => -1, 'msg' => $result['msg']]));
        }
        exit(json_encode(['code' => 0, 'msg' => 'ok', 'content' => $result['content'], 'cached' => intval($result['cached'] ?? 0)]));
    break;

    case 'get_oauth_channels':
        require_once '../funsion/OAuthHelper.php';
        $enabled = OAuthHelper::isEnabled($DB);
        $channels = $enabled ? OAuthHelper::getEnabledChannels($DB) : [];
        exit(json_encode([
            'code' => 0,
            'enabled' => $enabled ? 1 : 0,
            'channels' => $channels
        ]));
    break;

    case 'get_oauth_url':
        require_once '../funsion/OAuthHelper.php';
        $type = isset($_POST['type']) ? daddslashes($_POST['type']) : '';
        $state = isset($_POST['state']) ? daddslashes($_POST['state']) : 'login';

        if (!OAuthHelper::isChannelEnabled($DB, $type)) {
            exit('{"code":-1,"msg":"该登录渠道不可用"}');
        }

        $callback = OAuthHelper::buildCallbackUrl();
        $redirectUri = $callback . '?type=' . $type . '&state=' . $state;
        $res = OAuthHelper::getLoginUrl($DB, $type, $redirectUri);
        exit(json_encode($res));
    break;

    case 'get_oauth_binds':
        if(!isset($_SESSION['userId'])){
            exit('{"code":-1,"msg":"请先登录"}');
        }
        require_once '../funsion/OAuthHelper.php';
        $userId = intval($_SESSION['userId']);
        $rows = $DB->getAll("SELECT oauth_type, nickname, avatar, create_time FROM nteam_user_oauth WHERE user_id='$userId'");
        $binds = [];
        foreach ($rows as $r) {
            $meta = OAuthHelper::CHANNELS[$r['oauth_type']] ?? ['name' => $r['oauth_type']];
            $binds[] = [
                'type' => $r['oauth_type'],
                'name' => $meta['name'],
                'nickname' => $r['nickname'],
                'avatar' => $r['avatar'],
                'create_time' => $r['create_time']
            ];
        }
        exit(json_encode([
            'code' => 0,
            'binds' => $binds,
            'enabled' => OAuthHelper::isEnabled($DB) ? 1 : 0,
            'channels' => OAuthHelper::isEnabled($DB) ? OAuthHelper::getEnabledChannels($DB) : []
        ]));
    break;

    case 'unbind_oauth':
        if(!isset($_SESSION['userId'])){
            exit('{"code":-1,"msg":"请先登录"}');
        }
        require_once '../funsion/OAuthHelper.php';
        $userId = intval($_SESSION['userId']);
        $type = isset($_POST['type']) ? daddslashes($_POST['type']) : '';
        if (!isset(OAuthHelper::CHANNELS[$type])) {
            exit('{"code":-1,"msg":"参数错误"}');
        }
        $DB->exec("DELETE FROM nteam_user_oauth WHERE user_id='$userId' AND oauth_type='$type'");
        exit('{"code":0,"msg":"解绑成功"}');
    break;

    case 'send_cancel_code':
        if(!isset($_SESSION['userId'])){
            exit('{"code":-1,"msg":"请先登录"}');
        }
        $cfg = cancelGetConfig();
        if ($cfg['enable'] !== 1) {
            exit('{"code":-1,"msg":"注销功能未开启"}');
        }
        if (strpos($cfg['verify_mode'], 'email') === false) {
            exit('{"code":-1,"msg":"当前验证方式无需邮件验证码"}');
        }
        $userId = intval($_SESSION['userId']);
        $user = $DB->getRow("SELECT * FROM nteam_users WHERE id='$userId'");
        if (!$user) {
            exit('{"code":-1,"msg":"请先登录"}');
        }
        if (!empty($_SESSION['cancel_code_time']) && (time() - intval($_SESSION['cancel_code_time'])) < 60) {
            $left = 60 - (time() - intval($_SESSION['cancel_code_time']));
            exit('{"code":-1,"msg":"请勿频繁发送，请 '.$left.' 秒后重试"}');
        }
        if (empty($user['email'])) {
            exit('{"code":-1,"msg":"账号未绑定邮箱，无法发送验证码"}');
        }
        $code = sprintf('%06d', random_int(0, 999999));
        $_SESSION['cancel_code'] = $code;
        $_SESSION['cancel_code_time'] = time();
        $_SESSION['cancel_code_email'] = $user['email'];

        $siteName = conf('Name');
        $mailContent = '<html><head><meta charset="UTF-8"></head><body style="font-family:Arial,sans-serif;line-height:1.6;color:#333;">'
            . '<div style="max-width:600px;margin:0 auto;padding:20px;border:1px solid #ddd;border-radius:5px;">'
            . '<h2 style="color:#dc3545;border-bottom:2px solid #dc3545;padding-bottom:10px;">账号注销邮箱验证码</h2>'
            . '<p>您好，<strong>' . htmlspecialchars($user['username']) . '</strong>：</p>'
            . '<p>您正在申请注销账号，本次验证码为：</p>'
            . '<p style="font-size:28px;font-weight:bold;letter-spacing:6px;color:#dc3545;">' . $code . '</p>'
            . '<p>验证码 10 分钟内有效，请勿泄露给他人。如非本人操作，请忽略本邮件。</p>'
            . '<p style="color:#999;font-size:12px;">—— ' . htmlspecialchars($siteName) . '</p>'
            . '</div></body></html>';
        $res = cancelSendMail($user['email'], '账号注销验证码 - ' . $siteName, $mailContent);
        if ($res !== true) {
            unset($_SESSION['cancel_code'], $_SESSION['cancel_code_time'], $_SESSION['cancel_code_email']);
            exit('{"code":-1,"msg":"验证码发送失败，请稍后重试"}');
        }
        exit('{"code":0,"msg":"验证码已发送至您的邮箱"}');
    break;

    case 'cancellation_status':
        if(!isset($_SESSION['userId'])){
            exit('{"code":-1,"msg":"请先登录"}');
        }
        $userId = intval($_SESSION['userId']);
        $user = $DB->getRow("SELECT is_cancelling, cancel_time, cancel_expire_time FROM nteam_users WHERE id='$userId'");
        $cfg = cancelGetConfig();
        $remain = 0;
        if ($user && !empty($user['is_cancelling']) && !empty($user['cancel_expire_time'])) {
            $remain = max(0, (int)ceil((strtotime($user['cancel_expire_time']) - time()) / 86400));
        }
        exit(json_encode([
            'code' => 0,
            'enable' => $cfg['enable'],
            'verify_mode' => $cfg['verify_mode'],
            'cooling_enable' => $cfg['cooling_enable'],
            'cooling_days' => $cfg['cooling_days'],
            'is_cancelling' => $user ? intval($user['is_cancelling']) : 0,
            'cancel_time' => $user ? $user['cancel_time'] : null,
            'expire_time' => $user ? $user['cancel_expire_time'] : null,
            'remain_days' => $remain
        ]));
    break;

    // 删除消息推送日志：传 id 删除单条；scope=expired 仅清理过期（5 天前）记录
    case 'delete_push_logs':
        if(!isset($_SESSION['userId'])){
            exit('{"code":-1,"msg":"请先登录"}');
        }
        $userId = intval($_SESSION['userId']);
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $scope = isset($_POST['scope']) ? daddslashes($_POST['scope']) : '';
        if ($id > 0) {
            $deleted = $DB->exec("DELETE FROM nteam_push_logs WHERE id='$id' AND user_id='$userId'");
            if ($deleted === false) {
                exit('{"code":-1,"msg":"删除失败['.$DB->error().']"}');
            }
            if (intval($deleted) < 1) {
                exit('{"code":-1,"msg":"记录不存在或已删除"}');
            }
            exit('{"code":0,"msg":"已删除该条日志","deleted":1}');
        }
        if ($scope === 'expired') {
            $deleted = $DB->exec("DELETE FROM nteam_push_logs WHERE user_id='$userId' AND created_at < DATE_SUB(NOW(), INTERVAL 5 DAY)");
            if ($deleted === false) {
                exit('{"code":-1,"msg":"删除失败['.$DB->error().']"}');
            }
            $deleted = intval($deleted);
            exit('{"code":0,"msg":"已清理 '.$deleted.' 条过期日志","deleted":'.$deleted.'}');
        }
        exit('{"code":-1,"msg":"参数错误"}');
    break;

    // 删除订阅记录：传 id 删除单条；scope=expired 仅清理过期（5 天前）记录
    case 'delete_subscription_logs':
        if(!isset($_SESSION['userId'])){
            exit('{"code":-1,"msg":"请先登录"}');
        }
        $userId = intval($_SESSION['userId']);
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $scope = isset($_POST['scope']) ? daddslashes($_POST['scope']) : '';
        if ($id > 0) {
            $deleted = $DB->exec("DELETE FROM nteam_subscription_logs WHERE id='$id' AND user_id='$userId'");
            if ($deleted === false) {
                exit('{"code":-1,"msg":"删除失败['.$DB->error().']"}');
            }
            if (intval($deleted) < 1) {
                exit('{"code":-1,"msg":"记录不存在或已删除"}');
            }
            exit('{"code":0,"msg":"已删除该条记录","deleted":1}');
        }
        if ($scope === 'expired') {
            $deleted = $DB->exec("DELETE FROM nteam_subscription_logs WHERE user_id='$userId' AND created_at < DATE_SUB(NOW(), INTERVAL 5 DAY)");
            if ($deleted === false) {
                exit('{"code":-1,"msg":"删除失败['.$DB->error().']"}');
            }
            $deleted = intval($deleted);
            exit('{"code":0,"msg":"已清理 '.$deleted.' 条过期记录","deleted":'.$deleted.'}');
        }
        exit('{"code":-1,"msg":"参数错误"}');
    break;

    // 第三步「下一步」即完成身份核验（密码 / 邮箱验证码 / 人机验证）
    case 'verify_cancel_identity':
        if(!isset($_SESSION['userId'])){
            exit('{"code":-1,"msg":"请先登录"}');
        }
        $cfg = cancelGetConfig();
        if ($cfg['enable'] !== 1) {
            exit('{"code":-1,"msg":"注销功能未开启"}');
        }
        $userId = intval($_SESSION['userId']);
        $user = $DB->getRow("SELECT * FROM nteam_users WHERE id='$userId'");
        if (!$user) {
            exit('{"code":-1,"msg":"请先登录"}');
        }
        if (!empty($user['is_cancelling']) && intval($user['is_cancelling']) === 1) {
            exit('{"code":-1,"msg":"账号已处于冷静期中"}');
        }
        // 人机验证
        $tcTicket = isset($_POST['tc_ticket']) ? addslashes($_POST['tc_ticket']) : '';
        $tcRandstr = isset($_POST['tc_randstr']) ? addslashes($_POST['tc_randstr']) : '';
        $captcha = isset($_POST['captcha']) ? strtoupper(addslashes($_POST['captcha'])) : '';
        $captchaValid = false;
        if (!empty($tcTicket) && !empty($tcRandstr)) {
            $captchaValid = true;
        } elseif (!empty($captcha)) {
            if (isset($_SESSION['captcha']) && strtoupper($_SESSION['captcha']) === $captcha) {
                $captchaValid = true;
                unset($_SESSION['captcha']);
            }
        }
        if (!$captchaValid) {
            exit('{"code":-1,"msg":"请先完成安全验证"}');
        }
        // 密码 / 邮箱验证码核验（通过后消费验证码，避免重复使用）
        $verify = cancelVerifyIdentity($DB, $user, $cfg, isset($_POST['password']) ? $_POST['password'] : '', isset($_POST['email_code']) ? $_POST['email_code'] : '', true);
        if ($verify !== true) {
            exit('{"code":-1,"msg":"' . $verify . '"}');
        }
        // 签发核验凭据，第四步提交时凭此免重复核验
        $_SESSION['cancel_pass'] = cancelIssueVerifyPass($DB, $userId, isset($user['email']) ? $user['email'] : '');
        exit('{"code":0,"msg":"身份核验通过"}');
    break;

    case 'cancel_account':
        if(!isset($_SESSION['userId'])){
            exit('{"code":-1,"msg":"请先登录"}');
        }
        $cfg = cancelGetConfig();
        if ($cfg['enable'] !== 1) {
            exit('{"code":-1,"msg":"注销功能未开启"}');
        }
        $userId = intval($_SESSION['userId']);
        $user = $DB->getRow("SELECT * FROM nteam_users WHERE id='$userId'");
        if (!$user) {
            unset($_SESSION['userId'], $_SESSION['userName'], $_SESSION['userEmail'], $_SESSION['userQq']);
            exit('{"code":-1,"msg":"请先登录"}');
        }
        if (!empty($user['is_cancelling']) && intval($user['is_cancelling']) === 1) {
            exit('{"code":-1,"msg":"账号已处于冷静期中"}');
        }
        if (intval($_POST['agree'] ?? 0) !== 1) {
            exit('{"code":-1,"msg":"请先阅读并同意《用户注销服务协议》"}');
        }
        $confirmText = isset($_POST['confirm_text']) ? trim($_POST['confirm_text']) : '';
        if ($confirmText !== '我已确认注销后用户数据无法找回并确认注销') {
            exit('{"code":-1,"msg":"确认短语不正确"}');
        }
        // 身份核验：以第三步核验通过后签发的凭据为准（30 分钟内有效、一次性）
        $sessPass = isset($_SESSION['cancel_pass']) ? (string)$_SESSION['cancel_pass'] : '';
        if ($sessPass !== '') {
            // 仅校验不消费：待注销流程成功后再清理，避免中途失败导致用户需重新核验
            if (!cancelCheckVerifyPass($DB, $userId, $sessPass, false)) {
                unset($_SESSION['cancel_pass']);
                exit('{"code":-3,"msg":"身份核验已超时，请返回上一步重新核验"}');
            }
        } else {
            // 兜底：未经过第三步核验的旧流程，仍按配置现场核验
            $tcTicket = isset($_POST['tc_ticket']) ? addslashes($_POST['tc_ticket']) : '';
            $tcRandstr = isset($_POST['tc_randstr']) ? addslashes($_POST['tc_randstr']) : '';
            $captcha = isset($_POST['captcha']) ? strtoupper(addslashes($_POST['captcha'])) : '';
            $captchaValid = false;
            if (!empty($tcTicket) && !empty($tcRandstr)) {
                $captchaValid = true;
            } elseif (!empty($captcha)) {
                if (isset($_SESSION['captcha']) && strtoupper($_SESSION['captcha']) === $captcha) {
                    $captchaValid = true;
                    unset($_SESSION['captcha']);
                }
            }
            if (!$captchaValid) {
                exit('{"code":-1,"msg":"请先完成安全验证"}');
            }
            $verify = cancelVerifyIdentity($DB, $user, $cfg, isset($_POST['password']) ? $_POST['password'] : '', isset($_POST['email_code']) ? $_POST['email_code'] : '', true);
            if ($verify !== true) {
                exit('{"code":-1,"msg":"' . $verify . '"}');
            }
        }
        $ip = $Gets->ip();

        if ($cfg['cooling_enable'] !== 1) {
            // 未启用冷静期：立即注销
            $auditId = cancelCreateAudit($DB, $user, $cfg, $ip, 'pending', null);
            if ($cfg['mail_deleted'] === 1) {
                cancelSendDeleteMail($user);
            }
            $del = deleteUserAccount($DB, $userId, $user);
            if (!$del['success']) {
                $DB->exec("UPDATE nteam_account_deletions SET status='failed' WHERE id='".intval($auditId)."'");
                exit('{"code":-1,"msg":"注销失败['.$del['error'].']"}');
            }
            $DB->exec("UPDATE nteam_account_deletions SET status='deleted', delete_time=NOW() WHERE id='".intval($auditId)."'");
            unset($_SESSION['cancel_pass'], $_SESSION['userId'], $_SESSION['userName'], $_SESSION['userEmail'], $_SESSION['userQq']);
            exit('{"code":0,"msg":"账号已注销","immediate":1}');
        }

        // 启用冷静期
        $days = intval($cfg['cooling_days']);
        $expire = date('Y-m-d H:i:s', strtotime("+{$days} days"));
        $DB->exec("UPDATE nteam_users SET is_cancelling=1, cancel_time=NOW(), cancel_expire_time='$expire' WHERE id='$userId'");
        cancelCreateAudit($DB, $user, $cfg, $ip, 'pending', $expire);
        if ($cfg['mail_submit'] === 1) {
            cancelSendSubmitMail($user, $expire, $days);
        }
        // 注销申请已提交：清理本次核验凭据
        $DB->exec("DELETE FROM nteam_tokens WHERE user_id='$userId' AND token_type='cancel_pass'");
        unset($_SESSION['cancel_pass'], $_SESSION['userId'], $_SESSION['userName'], $_SESSION['userEmail'], $_SESSION['userQq']);
        exit('{"code":0,"msg":"注销申请已提交，账号将在 '.substr($expire, 0, 10).' 正式注销，期间登录可撤销","expire_time":"'.$expire.'"}');
    break;

    case 'ai_analyze_schedule':
        if (!isset($_SESSION['userId'])) {
            exit(json_encode(['code' => -1, 'msg' => '请先登录']));
        }
        $userId = intval($_SESSION['userId']);
        $raw = isset($_POST['data']) ? $_POST['data'] : '';
        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['type'])) {
            exit(json_encode(['code' => -1, 'msg' => '暂无课程数据']));
        }

        $type = $data['type'];
        if ($type === 'semester') {
            $courses = $data['courses'] ?? [];
            if (!is_array($courses) || count($courses) === 0) {
                exit(json_encode(['code' => -1, 'msg' => '暂无课程数据']));
            }
            $lines = [];
            foreach ($courses as $i => $c) {
                $lines[] = sprintf(
                    "课程%d：%s，星期%s，节次%s，地点%s，教师%s，周次%s，学分%s，属性%s",
                    ($i + 1),
                    $c['name'] ?? '',
                    $c['weekDay'] ?? '',
                    $c['section'] ?? '',
                    $c['location'] ?: '待定',
                    $c['teacher'] ?: '待定',
                    $c['weeks'] ?: '未知',
                    $c['credit'] ?: '未知',
                    $c['nature'] ?: '未知'
                );
            }
            $summary = implode("\n", $lines);
            $weekText = isset($data['week']) && $data['week'] !== '' ? ('（第' . intval($data['week']) . '周）') : '';
            $prompt = "你是一位大学学业规划与时间管理顾问。以下是某学生本学期" . $weekText . "的课程表：\n"
                . $summary . "\n\n"
                . "请基于以上课程表，给出：1）整体课程负担评价（课程门数、每日/每周学时分布）；"
                . "2）时间分布特征（集中在哪些天、有无空闲半天、是否需要跨校区或长距离移动）；"
                . "3）空闲时段的合理利用建议；4）学习节奏与作息优化建议；5）是否存在时间冲突或连上多节的疲劳风险提醒。"
                . "请用简体中文、分点清晰作答，可使用 Markdown。";
        } elseif ($type === 'today') {
            $courses = $data['courses'] ?? [];
            $dayName = isset($data['dayName']) ? strval($data['dayName']) : '今天';
            $date = isset($data['date']) ? strval($data['date']) : '';
            if (!is_array($courses) || count($courses) === 0) {
                exit(json_encode(['code' => -1, 'msg' => '今日暂无课程']));
            }
            $lines = [];
            foreach ($courses as $i => $c) {
                $lines[] = sprintf(
                    "课程%d：%s，时间%s，地点%s，教师%s",
                    ($i + 1),
                    $c['name'] ?? '',
                    $c['time'] ?: '待定',
                    $c['location'] ?: '待定',
                    $c['teacher'] ?: '待定'
                );
            }
            $summary = implode("\n", $lines);
            $dateText = $date ? "（{$date}）" : '';
            $prompt = "你是一位贴心的大学学习生活助手。以下是某学生{$dayName}{$dateText}的课程安排：\n"
                . $summary . "\n\n"
                . "请基于以上今日课程，给出：1）今日课程负担整体评价；2）课间空档如何利用（自习/休息/吃饭）；"
                . "3）精力分配与专注建议（结合课程难易与节次）；4）当晚预习/复习建议；5）作息与健康提醒。"
                . "请用简体中文、分点清晰、贴合“当天”场景作答，可使用 Markdown。";
        } else {
            exit(json_encode(['code' => -1, 'msg' => '暂不支持的分析类型']));
        }

        $messages = [
            ['role' => 'system', 'content' => '你是一位专业、贴心的大学学业规划与时间管理顾问。'],
            ['role' => 'user', 'content' => $prompt],
        ];
        $ai = new \lib\AiHelper();
        $dataHash = md5($raw);
        $bizType = ($type === 'semester') ? 'schedule_semester' : 'schedule_today';
        $result = $ai->analyzeCached($DB, $userId, $bizType, $dataHash, $messages);
        if (!$result['success']) {
            exit(json_encode(['code' => -1, 'msg' => $result['msg']]));
        }
        exit(json_encode(['code' => 0, 'msg' => 'ok', 'content' => $result['content'], 'cached' => intval($result['cached'] ?? 0)]));
    break;

    case 'ai_analyze_announcement':
        $userId = isset($_SESSION['userId']) ? intval($_SESSION['userId']) : 0;
        $aid = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($aid <= 0) {
            exit(json_encode(['code' => -1, 'msg' => '参数错误']));
        }
        $row = $DB->getRow("SELECT id, title, content, author, create_time FROM nteam_announcements WHERE id='$aid' AND is_published=1");
        if (!$row) {
            exit(json_encode(['code' => -1, 'msg' => '公告不存在或已下线']));
        }
        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($row['content'])));
        if ($plain === '') {
            exit(json_encode(['code' => -1, 'msg' => '公告内容为空，无法总结']));
        }
        $dataHash = md5($aid . '|' . $plain);
        $prompt = "请用简洁清晰、条理分明的中文，对以下校园公告进行要点总结：先一句话概述主题，再以要点形式列出核心信息，最后标注学生需要特别注意或按时办理的事项。\n\n"
            . "标题：" . $row['title'] . "\n"
            . "发布时间：" . $row['create_time'] . "\n"
            . "正文：\n" . $plain;
        $messages = [
            ['role' => 'system', 'content' => '你是一位专业、贴心的校园信息助手，擅长把冗长的公告提炼为清晰的要点总结。'],
            ['role' => 'user', 'content' => $prompt],
        ];
        $ai = new \lib\AiHelper();
        $result = $ai->analyzeCached($DB, $userId, 'announcement_summary', $dataHash, $messages);
        if (!$result['success']) {
            exit(json_encode(['code' => -1, 'msg' => $result['msg']]));
        }
        exit(json_encode(['code' => 0, 'msg' => 'ok', 'content' => $result['content'], 'cached' => intval($result['cached'] ?? 0)]));
    break;

    default:
        exit('{"code":-4,"msg":"No Act"}');
    break;
}
