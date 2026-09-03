<?php
$notLogin = true;
include('../Common/Core_brain.php');
/** @var array $dbconfig */
/** @var \lib\PdoHelper $DB */
$act=isset($_GET['act'])?daddslashes($_GET['act']):null;

@header('Content-Type: application/json; charset=UTF-8');

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
        
        $updateSql = "UPDATE nteam_users SET last_login_time=NOW(), last_login_ip='$ip' WHERE id='{$userData['id']}'";
        $DB->exec($updateSql);
        
        $_SESSION['userId'] = $userData['id'];
        $_SESSION['userName'] = $userData['username'];
        $_SESSION['userEmail'] = $userData['email'];
        $_SESSION['userQq'] = $userData['qq'];
        
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
    
    default:
        exit('{"code":-4,"msg":"No Act"}');
    break;
}
