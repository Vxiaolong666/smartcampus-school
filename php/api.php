<?php
$notLogin = true;
include('Common/Core_brain.php');
/** @var array $dbconfig */
/** @var \lib\PdoHelper $DB */

@header('Content-Type: application/json; charset=UTF-8');
@header('Access-Control-Allow-Origin: *');
@header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
@header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$act = isset($_GET['act']) ? daddslashes($_GET['act']) : null;

function getTokenFromHeader() {
    $authHeader = null;
    
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $authHeader = $headers['authorization'];
        }
    }
    
    if (!$authHeader && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    }
    
    if (!$authHeader && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
    
    if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return $matches[1];
    }
    
    return null;
}

function authenticateUser($DB) {
    $token = getTokenFromHeader();
    
    if (!$token) {
        if (isset($_SESSION['userId'])) {
            $userId = intval($_SESSION['userId']);
            $userData = $DB->getRow("SELECT id as user_id, username, email, qq, is_banned FROM nteam_users WHERE id = '$userId'");
            if ($userData && $userData['is_banned'] != 1) {
                return $userData;
            }
        }
        return null;
    }
    
    $token = addslashes($token);
    $currentTime = date('Y-m-d H:i:s');
    
    $tokenData = $DB->getRow("SELECT t.*, u.username, u.email, u.qq, u.is_banned 
        FROM nteam_tokens t 
        LEFT JOIN nteam_users u ON t.user_id = u.id 
        WHERE t.token = '$token' AND t.token_type = 'api_access' AND t.used = 0 AND t.expire_time > '$currentTime'");
    
    if (!$tokenData) {
        return null;
    }
    
    if ($tokenData['is_banned'] == 1) {
        return null;
    }
    
    return $tokenData;
}

function generateToken($userId, $DB) {
    $token = bin2hex(random_bytes(16));
    $currentTime = date('Y-m-d H:i:s');
    $expireTime = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    $sql = "INSERT INTO nteam_tokens (user_id, token_type, token, expire_time, create_time) 
            VALUES ('$userId', 'api_access', '$token', '$expireTime', '$currentTime')";
    $DB->exec($sql);
    
    return $token;
}

function jsonResponse($code, $msg, $data = null) {
    $response = [
        'code' => $code,
        'msg' => $msg
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    exit(json_encode($response, JSON_UNESCAPED_UNICODE));
}

switch($act) {
    case 'login':
        $username = addslashes($_POST['username']);
        $password = md5($_POST['password']);
        
        if ($username == '' || $password == '') {
            jsonResponse(-1, '请输入用户名和密码');
        }
        
        $userData = $DB->getRow("SELECT * FROM nteam_users WHERE username='$username'");
        
        if (!$userData) {
            jsonResponse(-1, '用户不存在');
        }
        
        if ($userData['is_banned'] == 1) {
            jsonResponse(-1, '该账号已被封禁');
        }
        
        if ($userData['email_verified'] == 0) {
            jsonResponse(-1, '请先完成邮箱验证');
        }
        
        if ($password != $userData['password']) {
            jsonResponse(-1, '密码错误');
        }
        
        $token = generateToken($userData['id'], $DB);
        
        $ip = $Gets->ip();
        $DB->exec("UPDATE nteam_users SET last_login_time=NOW(), last_login_ip='$ip' WHERE id='{$userData['id']}'");
        
        jsonResponse(0, '登录成功', [
            'token' => $token,
            'user_id' => (int)$userData['id'],
            'username' => $userData['username'],
            'email' => $userData['email'],
            'qq' => $userData['qq']
        ]);
    break;
    
    case 'wx_login':
        $code = isset($_POST['code']) ? trim($_POST['code']) : '';
        
        if (empty($code)) {
            jsonResponse(-1, '请提供登录凭证');
        }
        
        $wxConfig = $DB->getRow("SELECT appid, secret FROM nteam_wx_config ORDER BY id DESC LIMIT 1");
        
        if (!$wxConfig || empty($wxConfig['appid']) || empty($wxConfig['secret'])) {
            jsonResponse(-1, '服务器未配置微信小程序信息');
        }
        
        $appid = $wxConfig['appid'];
        $secret = $wxConfig['secret'];
        
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid={$appid}&secret={$secret}&js_code={$code}&grant_type=authorization_code";
        
        $response = @file_get_contents($url);
        if ($response === false) {
            jsonResponse(-1, '微信服务器连接失败');
        }
        
        $wxData = json_decode($response, true);
        
        if (!isset($wxData['openid'])) {
            jsonResponse(-1, $wxData['errmsg'] ?? '获取OpenID失败');
        }
        
        $openid = addslashes($wxData['openid']);
        
        $userData = $DB->getRow("SELECT * FROM nteam_users WHERE wx_openid='{$openid}'");
        
        if (!$userData) {
            jsonResponse(-1, '该微信未绑定账号，请先使用账号密码登录后绑定微信');
        }
        
        if ($userData['is_banned'] == 1) {
            jsonResponse(-1, '该账号已被封禁');
        }
        
        $token = generateToken($userData['id'], $DB);
        
        $ip = $Gets->ip();
        $DB->exec("UPDATE nteam_users SET last_login_time=NOW(), last_login_ip='$ip' WHERE id='{$userData['id']}'");
        
        jsonResponse(0, '登录成功', [
            'token' => $token,
            'user_id' => (int)$userData['id'],
            'username' => $userData['username'],
            'email' => $userData['email'],
            'qq' => $userData['qq']
        ]);
    break;
    
    case 'bind_wx':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $code = isset($_POST['code']) ? trim($_POST['code']) : '';
        
        if (empty($code)) {
            jsonResponse(-1, '请提供登录凭证');
        }
        
        $wxConfig = $DB->getRow("SELECT appid, secret FROM nteam_wx_config ORDER BY id DESC LIMIT 1");
        
        if (!$wxConfig || empty($wxConfig['appid']) || empty($wxConfig['secret'])) {
            jsonResponse(-1, '服务器未配置微信小程序信息');
        }
        
        $appid = $wxConfig['appid'];
        $secret = $wxConfig['secret'];
        
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid={$appid}&secret={$secret}&js_code={$code}&grant_type=authorization_code";
        
        $response = @file_get_contents($url);
        if ($response === false) {
            jsonResponse(-1, '微信服务器连接失败');
        }
        
        $wxData = json_decode($response, true);
        
        if (!isset($wxData['openid'])) {
            jsonResponse(-1, $wxData['errmsg'] ?? '获取OpenID失败');
        }
        
        $openid = addslashes($wxData['openid']);
        
        $existingBind = $DB->getRow("SELECT * FROM nteam_users WHERE wx_openid='{$openid}' AND id!='{$user['user_id']}'");
        if ($existingBind) {
            jsonResponse(-1, '该微信已绑定其他账号');
        }
        
        $currentBind = $DB->getRow("SELECT wx_openid FROM nteam_users WHERE id='{$user['user_id']}'");
        if (!empty($currentBind['wx_openid'])) {
            jsonResponse(-1, '您已绑定微信，如需更换请先解绑');
        }
        
        if ($DB->exec("UPDATE nteam_users SET wx_openid='{$openid}' WHERE id='{$user['user_id']}'")) {
            jsonResponse(0, '绑定成功');
        } else {
            jsonResponse(-1, '绑定失败');
        }
    break;
    
    case 'unbind_wx':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $currentBind = $DB->getRow("SELECT wx_openid FROM nteam_users WHERE id='{$user['user_id']}'");
        if (empty($currentBind['wx_openid'])) {
            jsonResponse(-1, '您尚未绑定微信');
        }
        
        if ($DB->exec("UPDATE nteam_users SET wx_openid=NULL WHERE id='{$user['user_id']}'")) {
            jsonResponse(0, '解绑成功');
        } else {
            jsonResponse(-1, '解绑失败');
        }
    break;
    
    case 'logout':
        $token = getTokenFromHeader();
        if ($token) {
            $token = addslashes($token);
            $DB->exec("UPDATE nteam_tokens SET used=1 WHERE token='$token'");
        }
        jsonResponse(0, '退出成功');
    break;
    
    case 'change_password':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $oldPassword = trim($_POST['old_password']);
        $newPassword = trim($_POST['new_password']);
        
        if ($oldPassword == '' || $newPassword == '') {
            jsonResponse(-1, '请填写完整信息');
        }
        
        if (strlen($newPassword) < 6) {
            jsonResponse(-1, '新密码长度至少6位');
        }
        
        $userData = $DB->getRow("SELECT password FROM nteam_users WHERE id='{$user['user_id']}'");
        
        if (md5($oldPassword) != $userData['password']) {
            jsonResponse(-1, '原密码错误');
        }
        
        $newPasswordMd5 = md5($newPassword);
        
        if ($DB->exec("UPDATE nteam_users SET password='$newPasswordMd5' WHERE id='{$user['user_id']}'")) {
            $token = getTokenFromHeader();
            if ($token) {
                $token = addslashes($token);
                $DB->exec("UPDATE nteam_tokens SET used=1 WHERE token='$token'");
            }
            jsonResponse(0, '密码修改成功，请重新登录');
        } else {
            jsonResponse(-1, '修改失败，请稍后重试');
        }
    break;
    
    case 'register':
        $username = addslashes(trim($_POST['username']));
        $password = trim($_POST['password']);
        $email = addslashes(trim($_POST['email']));
        $confirmPassword = trim($_POST['confirm_password']);
        
        if ($username == '' || $password == '' || $email == '') {
            jsonResponse(-1, '请填写完整信息');
        }
        
        if ($password !== $confirmPassword) {
            jsonResponse(-1, '两次密码不一致');
        }
        
        if (strlen($password) < 6) {
            jsonResponse(-1, '密码长度至少6位');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(-1, '邮箱格式不正确');
        }
        
        $existing = $DB->getRow("SELECT * FROM nteam_users WHERE username='$username'");
        if ($existing) {
            jsonResponse(-1, '用户名已存在');
        }
        
        $existing = $DB->getRow("SELECT * FROM nteam_users WHERE email='$email'");
        if ($existing) {
            jsonResponse(-1, '邮箱已被注册');
        }
        
        $password = md5($password);
        $registerTime = date('Y-m-d H:i:s');
        $verifyCode = random(32);
        $expireTime = date('Y-m-d H:i:s', strtotime('+3 hours'));
        
        $sql = "INSERT INTO nteam_users (username, password, email, email_verify_code, register_time) 
                VALUES ('$username', '$password', '$email', '$verifyCode', '$registerTime')";
        
        if ($DB->exec($sql)) {
            $currentTime = date('Y-m-d H:i:s');
            $DB->exec("INSERT INTO nteam_tokens (user_id, token_type, token, email, expire_time, create_time) 
                VALUES (LAST_INSERT_ID(), 'email_verify', '$verifyCode', '$email', '$expireTime', '$currentTime')");
            
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
        <p>您好，<strong>' . htmlspecialchars($username) . '</strong>：</p>
        <p>感谢您注册' . htmlspecialchars($siteName) . '，请点击以下链接验证您的邮箱：</p><br>
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
                jsonResponse(0, '注册成功，验证邮件已发送，请查收');
            } else {
                jsonResponse(-1, '注册成功但邮件发送失败：' . $sendResult);
            }
        } else {
            jsonResponse(-1, '注册失败，请稍后重试');
        }
    break;
    
    case 'forgot_password':
        $email = addslashes(trim($_POST['email']));
        
        if ($email == '') {
            jsonResponse(-1, '请输入邮箱');
        }
        
        $user = $DB->getRow("SELECT * FROM nteam_users WHERE email='$email'");
        if (!$user) {
            jsonResponse(-1, '该邮箱未注册');
        }
        
        $token = random(32);
        $currentTime = date('Y-m-d H:i:s');
        $expireTime = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        
        $DB->exec("DELETE FROM nteam_tokens WHERE user_id='{$user['id']}' AND token_type='password_reset'");
        
        $sql = "INSERT INTO nteam_tokens (user_id, token_type, token, email, expire_time, create_time) 
                VALUES ('{$user['id']}', 'password_reset', '$token', '$email', '$expireTime', '$currentTime')";
        
        if ($DB->exec($sql)) {
            $siteName = conf('Name');
            $siteUrl = conf('Mail_Site_Url') ?: conf('Url');
            $resetUrl = $siteUrl . 'user/reset_password.php?token=' . $token . '&email=' . $email;
            
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
                jsonResponse(0, '重置邮件已发送，请查收');
            } else {
                jsonResponse(-1, '邮件发送失败：' . $sendResult);
            }
        } else {
            jsonResponse(-1, '发送失败，请稍后重试');
        }
    break;
    
    case 'send_verify_email':
        $email = addslashes(trim($_POST['email']));
        
        if ($email == '') {
            jsonResponse(-1, '请输入邮箱');
        }
        
        $user = $DB->getRow("SELECT * FROM nteam_users WHERE email='$email'");
        
        if (!$user) {
            jsonResponse(-1, '该邮箱未注册');
        }
        
        if ($user['email_verified'] == 1) {
            jsonResponse(-1, '该邮箱已验证，请直接登录');
        }
        
        $verifyCode = random(32);
        $currentTime = date('Y-m-d H:i:s');
        $expireTime = date('Y-m-d H:i:s', strtotime('+3 hours'));
        
        $DB->exec("INSERT INTO nteam_tokens (user_id, token_type, token, email, expire_time, create_time) 
            VALUES ('{$user['id']}', 'email_verify', '$verifyCode', '$email', '$expireTime', '$currentTime')");
        
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
            jsonResponse(0, '验证邮件已发送，请查收');
        } else {
            jsonResponse(-1, '邮件发送失败：' . $sendResult);
        }
    break;
    
    case 'reset_password':
        $token = addslashes(trim($_POST['token']));
        $password = trim($_POST['password']);
        $confirmPassword = trim($_POST['confirm_password']);
        
        if ($token == '' || $password == '') {
            jsonResponse(-1, '参数错误');
        }
        
        if ($password !== $confirmPassword) {
            jsonResponse(-1, '两次密码不一致');
        }
        
        if (strlen($password) < 6) {
            jsonResponse(-1, '密码长度至少6位');
        }
        
        $tokenData = $DB->getRow("SELECT t.*, u.username FROM nteam_tokens t 
            LEFT JOIN nteam_users u ON t.user_id = u.id 
            WHERE t.token='$token' AND t.token_type='password_reset' AND t.used=0 AND t.expire_time > NOW()");
        
        if (!$tokenData) {
            jsonResponse(-1, '重置链接无效或已过期');
        }
        
        $password = md5($password);
        $DB->exec("UPDATE nteam_users SET password='$password' WHERE id='{$tokenData['user_id']}'");
        $DB->exec("UPDATE nteam_tokens SET used=1 WHERE id='{$tokenData['id']}'");
        
        jsonResponse(0, '密码重置成功');
    break;
    
    case 'get_user_info':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $userData = $DB->getRow("SELECT id, username, email, qq, points, level, register_time, last_login_time, nickname, avatar, wx_openid FROM nteam_users WHERE id='{$user['user_id']}'");
        
        jsonResponse(0, 'success', $userData);
    break;
    
    case 'update_profile':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $nickname = isset($_POST['nickname']) ? addslashes(trim($_POST['nickname'])) : '';
        $avatar = isset($_POST['avatar']) ? addslashes(trim($_POST['avatar'])) : '';
        
        $updates = [];
        
        if (!empty($nickname)) {
            if (mb_strlen($nickname) > 20) {
                jsonResponse(-1, '昵称不能超过20个字符');
            }
            $updates[] = "nickname='$nickname'";
        }
        
        if (!empty($avatar)) {
            $updates[] = "avatar='$avatar'";
        }
        
        if (empty($updates)) {
            jsonResponse(0, '保存成功');
        }
        
        $sql = "UPDATE nteam_users SET " . implode(', ', $updates) . " WHERE id='{$user['user_id']}'";
        
        $result = $DB->exec($sql);
        if ($result !== false) {
            jsonResponse(0, '保存成功');
        } else {
            jsonResponse(-1, '更新失败');
        }
    break;
    
    case 'upload_avatar':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        if (!isset($_FILES['avatar'])) {
            jsonResponse(-1, '请选择要上传的图片');
        }
        
        $file = $_FILES['avatar'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(-1, '文件上传失败');
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            jsonResponse(-1, '只支持JPG、PNG、GIF、WEBP格式的图片');
        }
        
        if ($file['size'] > 2 * 1024 * 1024) {
            jsonResponse(-1, '图片大小不能超过2MB');
        }
        
        $uploadDir = __DIR__ . '/uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . $user['user_id'] . '_' . time() . '.' . $ext;
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $avatarUrl = '/uploads/avatars/' . $filename;
            $DB->exec("UPDATE nteam_users SET avatar='$avatarUrl' WHERE id='{$user['user_id']}'");
            jsonResponse(0, '上传成功', ['avatar' => $avatarUrl]);
        } else {
            jsonResponse(-1, '文件保存失败');
        }
    break;
    
    case 'get_academic':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $academic = $DB->getRow("SELECT id, student_id, real_name, bind_time FROM nteam_academic_accounts WHERE user_id='{$user['user_id']}'");
        
        if (!$academic) {
            jsonResponse(-1, '未绑定教务账号');
        }
        
        jsonResponse(0, 'success', $academic);
    break;
    
    case 'bind_academic':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $studentId = addslashes(trim($_POST['student_id']));
        $password = $_POST['password'];
        $realName = isset($_POST['real_name']) ? addslashes(trim($_POST['real_name'])) : '';
        
        if ($studentId == '' || $password == '') {
            jsonResponse(-1, '请填写完整信息');
        }
        
        $existing = $DB->getRow("SELECT * FROM nteam_academic_accounts WHERE user_id='{$user['user_id']}'");
        if ($existing) {
            jsonResponse(-1, '已绑定教务账号，请先解绑');
        }
        
        $checkStudentId = $DB->getRow("SELECT * FROM nteam_academic_accounts WHERE student_id='$studentId'");
        if ($checkStudentId) {
            jsonResponse(-1, '该学工号已被其他账号绑定');
        }
        
        require_once 'funsion/AcademicSystem.php';
        
        $loginResult = AcademicSystem::login($studentId, $password);
        
        if (!isset($loginResult['code']) || $loginResult['code'] != 1) {
            $errorMsg = $loginResult['Msg'] ?? $loginResult['msg'] ?? '教务账号验证失败，请检查学工号和密码';
            jsonResponse(-1, $errorMsg);
        }
        
        $token = $loginResult['data']['token'] ?? '';
        $bindTime = date('Y-m-d H:i:s');
        
        if (empty($realName)) {
            $infoResult = AcademicSystem::getStudentInfo($token);
            if (isset($infoResult['code']) && $infoResult['code'] == 1 && !empty($infoResult['data'])) {
                $realName = addslashes($infoResult['data'][0]['xm'] ?? '');
            }
        }
        
        $realNameValue = empty($realName) ? 'NULL' : "'$realName'";
        $tokenValue = empty($token) ? 'NULL' : "'" . addslashes($token) . "'";
        
        $sql = "INSERT INTO nteam_academic_accounts (user_id, student_id, password, real_name, token, token_time, bind_time) 
                VALUES ('{$user['user_id']}', '$studentId', '$password', $realNameValue, $tokenValue, '$bindTime', '$bindTime')";
        
        if ($DB->exec($sql)) {
            jsonResponse(0, '绑定成功');
        } else {
            jsonResponse(-1, '绑定失败');
        }
    break;
    
    case 'unbind_academic':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        if ($DB->exec("DELETE FROM nteam_academic_accounts WHERE user_id='{$user['user_id']}'")) {
            jsonResponse(0, '解绑成功');
        } else {
            jsonResponse(-1, '解绑失败');
        }
    break;
    
    case 'get_dormitories':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $dormitories = $DB->getAll("SELECT id, school_area, building, room, room_num, alias, is_default, bind_time 
            FROM nteam_dormitory_bindings 
            WHERE user_id='{$user['user_id']}' AND status=1 
            ORDER BY is_default DESC, id DESC");
        
        foreach ($dormitories as &$dorm) {
            if ($dorm['alias']) {
                $dorm['display_name'] = $dorm['alias'] . '（' . $dorm['school_area'] . $dorm['building'] . ' ' . $dorm['room'] . '）';
            } else {
                $dorm['display_name'] = $dorm['school_area'] . $dorm['building'] . ' ' . $dorm['room'];
            }
        }
        
        jsonResponse(0, 'success', $dormitories ?: []);
    break;
    
    case 'bind_dormitory':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $schoolArea = addslashes(trim($_POST['school_area']));
        $building = addslashes(trim($_POST['building']));
        $room = addslashes(trim($_POST['room']));
        $roomNum = addslashes(trim($_POST['room_num']));
        $alias = isset($_POST['alias']) ? addslashes(trim($_POST['alias'])) : '';
        
        if ($schoolArea == '' || $building == '' || $room == '' || $roomNum == '') {
            jsonResponse(-1, '请填写完整信息');
        }
        
        $bindTime = date('Y-m-d H:i:s');
        $sql = "INSERT INTO nteam_dormitory_bindings (user_id, school_area, building, room, room_num, alias, bind_time) 
                VALUES ('{$user['user_id']}', '$schoolArea', '$building', '$room', '$roomNum', '$alias', '$bindTime')";
        
        if ($DB->exec($sql)) {
            jsonResponse(0, '绑定成功');
        } else {
            jsonResponse(-1, '绑定失败');
        }
    break;
    
    case 'unbind_dormitory':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $id = intval($_POST['id']);
        
        if ($DB->exec("UPDATE nteam_dormitory_bindings SET status=0 WHERE id='$id' AND user_id='{$user['user_id']}'")) {
            jsonResponse(0, '解绑成功');
        } else {
            jsonResponse(-1, '解绑失败');
        }
    break;
    
    case 'set_default_dormitory':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $id = intval($_POST['id']);
        
        $DB->exec("UPDATE nteam_dormitory_bindings SET is_default=0 WHERE user_id='{$user['user_id']}'");
        
        if ($DB->exec("UPDATE nteam_dormitory_bindings SET is_default=1 WHERE id='$id' AND user_id='{$user['user_id']}'")) {
            jsonResponse(0, '设置成功');
        } else {
            jsonResponse(-1, '设置失败');
        }
    break;
    
    case 'get_dorm_areas':
        $areas = $DB->getAll("SELECT DISTINCT school_area FROM dorm_room ORDER BY school_area");
        jsonResponse(0, 'success', array_column($areas, 'school_area'));
    break;
    
    case 'get_dorm_buildings':
        $area = addslashes($_GET['area']);
        if ($area == '') {
            jsonResponse(-3, '请选择校区');
        }
        
        $buildings = $DB->getAll("SELECT DISTINCT building FROM dorm_room WHERE school_area='$area' ORDER BY building");
        jsonResponse(0, 'success', array_column($buildings, 'building'));
    break;
    
    case 'get_dorm_rooms':
        $area = addslashes($_GET['area']);
        $building = addslashes($_GET['building']);
        
        if ($area == '' || $building == '') {
            jsonResponse(-3, '请选择校区和楼栋');
        }
        
        $rooms = $DB->getAll("SELECT room, room_num FROM dorm_room WHERE school_area='$area' AND building='$building' ORDER BY room");
        jsonResponse(0, 'success', $rooms);
    break;
    
    case 'query_power_fee':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $roomNum = addslashes(trim($_POST['room_num']));
        
        if ($roomNum == '') {
            jsonResponse(-3, '请提供宿舍编号');
        }
        
        require_once 'funsion/DormitorySystem.php';
        
        $result = DormitorySystem::getPowerFee($roomNum);
        
        if ($result['code'] == 1) {
            $balance = $result['data']['balance'];
            
            $powerConfig = $DB->getRow("SELECT * FROM nteam_power_config WHERE id = 1");
            $pricePerKwh = $powerConfig ? floatval($powerConfig['price_per_kwh']) : 0.61;
            
            $remainingKwh = null;
            if ($balance !== null && $balance >= 0 && $pricePerKwh > 0) {
                $remainingKwh = round($balance / $pricePerKwh, 2);
            }
            
            $startTime = date('Y-m-d H:i:s', strtotime('-24 hours'));
            $records = $DB->getAll("SELECT * FROM nteam_power_daily_records 
                WHERE room_num = '$roomNum' AND record_time >= '$startTime' 
                ORDER BY record_time ASC");
            
            $timeRange = null;
            if (count($records) >= 2) {
                $firstRecord = $records[0];
                $lastRecord = $records[count($records) - 1];
                $timeRange = [
                    'start' => $firstRecord['record_time'],
                    'end' => $lastRecord['record_time']
                ];
            }
            
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
            
            $status = 'normal';
            $statusText = '电量充足';
            if ($balance === null) {
                $status = 'unknown';
                $statusText = '无法查询';
            } elseif ($balance <= 0) {
                $status = 'arrears';
                $statusText = '已欠费';
            } elseif ($balance < 10) {
                $status = 'low';
                $statusText = '电量不足';
            } elseif ($balance < 30) {
                $status = 'warning';
                $statusText = '电量偏低';
            }
            
            jsonResponse(0, '查询成功', [
                'room_num' => $roomNum,
                'balance' => $balance,
                'remaining_kwh' => $remainingKwh,
                'price_per_kwh' => $pricePerKwh,
                'estimated_days' => $estimatedDays,
                'time_range' => $timeRange,
                'status' => $status,
                'status_text' => $statusText
            ]);
        } else {
            jsonResponse(-1, $result['msg']);
        }
    break;
    
    case 'get_power_statistics':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $roomNum = isset($_REQUEST['room_num']) ? addslashes($_REQUEST['room_num']) : '';
        
        if (empty($roomNum)) {
            $binding = $DB->getRow("SELECT room_num FROM nteam_dormitory_bindings WHERE user_id='{$user['user_id']}' AND status=1 ORDER BY id DESC LIMIT 1");
            if ($binding) {
                $roomNum = $binding['room_num'];
            } else {
                jsonResponse(-1, '请先绑定宿舍');
            }
        }
        
        $config = $DB->getRow("SELECT * FROM nteam_power_config WHERE id = 1");
        $pricePerKwh = floatval($config['price_per_kwh'] ?? 0.61);
        $savingThreshold = floatval($config['usage_saving_threshold'] ?? 0.5);
        $normalThreshold = floatval($config['usage_normal_threshold'] ?? 1.0);
        
        // 按时间筛选（历史电费记录 / 趋势 / 统计均按所选区间）
        $timeWhere = "room_num = '$roomNum'";
        $filterStart = isset($_REQUEST['start']) ? trim($_REQUEST['start']) : '';
        $filterEnd = isset($_REQUEST['end']) ? trim($_REQUEST['end']) : '';
        if ($filterStart !== '' && strtotime($filterStart) !== false) {
            $timeWhere .= " AND record_time >= '" . date('Y-m-d 00:00:00', strtotime($filterStart)) . "'";
        }
        if ($filterEnd !== '' && strtotime($filterEnd) !== false) {
            $timeWhere .= " AND record_time <= '" . date('Y-m-d 23:59:59', strtotime($filterEnd)) . "'";
        }

        $records = $DB->getAll("SELECT * FROM nteam_power_daily_records WHERE $timeWhere ORDER BY record_time ASC");
        
        $totalUsage = 0;
        $totalCost = 0;
        $recordCount = count($records);
        
        $dailyStats = [];
        $processedRecords = [];
        $recentTrend = [];
        
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $today = date('Y-m-d');
        $yesterdayUsage = 0;
        $yesterdayCost = 0;
        $todayUsage = 0;
        $todayCost = 0;
        
        for ($i = 0; $i < count($records); $i++) {
            $record = $records[$i];
            $balance = floatval($record['balance']);
            $usageKwh = 0;
            $usageCost = 0;
            
            if ($i > 0) {
                $prevBalance = floatval($records[$i - 1]['balance']);
                $costDiff = $prevBalance - $balance;
                
                if ($costDiff != 0 && $pricePerKwh > 0) {
                    $usageCost = $costDiff;
                    $usageKwh = $costDiff / $pricePerKwh;
                    if ($costDiff > 0) {
                        $totalUsage += $usageKwh;
                        $totalCost += $usageCost;
                    }
                }
            }
            
            $processedRecords[] = [
                'id' => $record['id'],
                'room_num' => $record['room_num'],
                'record_time' => $record['record_time'],
                'record_date' => $record['record_date'],
                'balance' => $balance,
                'usage_kwh' => round($usageKwh, 4),
                'usage_cost' => round($usageCost, 2)
            ];
            
            $recentTrend[] = [
                'time' => $record['record_time'],
                'usage' => round($usageKwh, 4)
            ];
            
            $date = $record['record_date'] ?? substr($record['record_time'], 0, 10);
            if (!isset($dailyStats[$date])) {
                $dailyStats[$date] = ['date' => $date, 'usage' => 0, 'cost' => 0, 'count' => 0];
            }
            $dailyStats[$date]['usage'] += $usageKwh;
            $dailyStats[$date]['cost'] += $usageCost;
            $dailyStats[$date]['count']++;
            
            if ($date === $yesterday) {
                $yesterdayUsage += $usageKwh;
                $yesterdayCost += $usageCost;
            }
            if ($date === $today) {
                $todayUsage += $usageKwh;
                $todayCost += $usageCost;
            }
        }
        
        $dailyTrend = [];
        $sortedDates = array_keys($dailyStats);
        sort($sortedDates);
        foreach ($sortedDates as $date) {
            $dailyTrend[] = [
                'date' => $date,
                'usage' => round($dailyStats[$date]['usage'], 4),
                'cost' => round($dailyStats[$date]['cost'], 2),
                'count' => $dailyStats[$date]['count']
            ];
        }
        
        $daysWithRecords = count($dailyStats);
        $dailyAvgUsage = $daysWithRecords > 0 ? $totalUsage / $daysWithRecords : 0;
        $dailyAvgCost = $daysWithRecords > 0 ? $totalCost / $daysWithRecords : 0;
        
        $healthStatus = 'normal';
        $healthText = '正常';
        if ($dailyAvgUsage <= $savingThreshold) {
            $healthStatus = 'saving';
            $healthText = '节能';
        } elseif ($dailyAvgUsage > $normalThreshold) {
            $healthStatus = 'high';
            $healthText = '偏高';
        }
        
        $latestRecord = end($processedRecords);
        $latestBalance = $latestRecord ? floatval($latestRecord['balance']) : null;
        
        $estimatedDays = null;
        $startTime = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $recentRecords = $DB->getAll("SELECT * FROM nteam_power_daily_records 
            WHERE room_num = '$roomNum' AND record_time >= '$startTime' 
            ORDER BY record_time ASC");
        
        if (count($recentRecords) >= 5 && $latestBalance !== null && $latestBalance > 0) {
            $firstRecord = $recentRecords[0];
            $lastRecord = $recentRecords[count($recentRecords) - 1];
            
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
                        $estimatedHours = $latestBalance / $avgUsagePerHour;
                        $estimatedDays = round($estimatedHours / 24, 1);
                    }
                }
            }
        }
        
        jsonResponse(0, 'success', [
            'room_num' => $roomNum,
            'record_count' => $recordCount,
            'total_usage_kwh' => round($totalUsage, 4),
            'total_cost' => round($totalCost, 2),
            'daily_avg_usage' => round($dailyAvgUsage, 4),
            'daily_avg_cost' => round($dailyAvgCost, 2),
            'yesterday_usage' => round($yesterdayUsage, 4),
            'yesterday_cost' => round($yesterdayCost, 2),
            'today_usage' => round($todayUsage, 4),
            'today_cost' => round($todayCost, 2),
            'health_status' => $healthStatus,
            'health_text' => $healthText,
            'price_per_kwh' => $pricePerKwh,
            'thresholds' => [
                'saving' => $savingThreshold,
                'normal' => $normalThreshold
            ],
            'recent_trend' => $recentTrend,
            'daily_trend' => $dailyTrend,
            'records' => $processedRecords,
            'latest_balance' => $latestBalance,
            'estimated_days' => $estimatedDays
        ]);
    break;
    
    case 'ai_analyze_grades':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }

        $raw = isset($_POST['data']) ? $_POST['data'] : '';
        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['achievement'])) {
            jsonResponse(-1, '暂无成绩数据');
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

        $userId = intval($user['user_id']);
        $semester = isset($data['semester']) ? strval($data['semester']) : '';
        $dataHash = md5($raw);

        // 命中缓存且数据未变化则直接返回，避免重复生成
        $cached = $DB->getRow("SELECT content, data_hash FROM nteam_ai_grade_analysis WHERE user_id=? AND semester=?", [$userId, $semester]);
        $cacheValid = $cached && $cached['data_hash'] === $dataHash && trim($cached['content']) !== '' && $cached['content'] !== '暂无分析结果';
        if ($cacheValid) {
            jsonResponse(0, 'ok', ['content' => $cached['content'], 'cached' => 1]);
        }

        $lines = [];
        foreach ($data['achievement'] as $c) {
            $lines[] = sprintf(
                "- %s：成绩 %s，学分 %s，绩点 %s，属性 %s",
                $c['courseName'] ?? '',
                $c['fraction'] ?? $c['score'] ?? '-',
                $c['credit'] ?? '-',
                $c['gpa'] ?? '-',
                $c['curriculumAttributes'] ?? $c['courseType'] ?? '-'
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
            jsonResponse(-1, $result['msg']);
        }
        $content = isset($result['content']) ? trim($result['content']) : '';
        if ($content === '') {
            jsonResponse(-1, '分析结果为空，请重试');
        }
        $now = date('Y-m-d H:i:s');
        if ($cached) {
            $DB->exec("UPDATE nteam_ai_grade_analysis SET content=?, data_hash=?, updated_at=? WHERE user_id=? AND semester=?",
                [$content, $dataHash, $now, $userId, $semester]);
        } else {
            $DB->exec("INSERT INTO nteam_ai_grade_analysis (user_id, semester, data_hash, content, created_at, updated_at) VALUES (?,?,?,?,?,?)",
                [$userId, $semester, $dataHash, $content, $now, $now]);
        }
        jsonResponse(0, 'ok', ['content' => $content, 'cached' => 0]);
    break;

    case 'ai_analyze_power':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }

        $raw = isset($_POST['data']) ? $_POST['data'] : '';
        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['type'])) {
            jsonResponse(-1, '暂无电费数据');
        }

        $type = $data['type'];
        if ($type === 'query') {
            $rooms = $data['rooms'] ?? [];
            if (!is_array($rooms) || count($rooms) === 0) {
                jsonResponse(-1, '暂无电费数据');
            }
            $lines = [];
            foreach ($rooms as $i => $r) {
                $lines[] = sprintf(
                    "宿舍%d：%s，余额 %s 元，剩余电量 %s 度，电价 %s 元/度，预计可用 %s 天，状态：%s，统计时段：%s",
                    ($i + 1),
                    $r['name'] ?? '未知',
                    $r['balance'] !== null && $r['balance'] !== '' ? $r['balance'] : '未知',
                    $r['remainingKwh'] !== null && $r['remainingKwh'] !== '' ? $r['remainingKwh'] : '未知',
                    $r['pricePerKwh'] ?? '未知',
                    $r['estimatedDays'] !== null && $r['estimatedDays'] !== '' ? $r['estimatedDays'] : '未知',
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
                $recentLines[] = sprintf(
                    "- %s：用电量 %s 度",
                    $p['label'] ?? $p['time'] ?? '',
                    $p['usage'] ?? ''
                );
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
            jsonResponse(-1, '暂不支持的分析类型');
        }

        $messages = [
            ['role' => 'system', 'content' => '你是一位专业、贴心的校园用电管理顾问，擅长从用电数据中给出实用建议。'],
            ['role' => 'user', 'content' => $prompt],
        ];
        $ai = new \lib\AiHelper();
        $result = $ai->chat($messages);
        if (!$result['success']) {
            jsonResponse(-1, $result['msg']);
        }
        $content = isset($result['content']) ? trim($result['content']) : '';
        if ($content === '') {
            jsonResponse(-1, '分析结果为空，请重试');
        }
        jsonResponse(0, 'ok', ['content' => $content, 'cached' => 0]);
    break;

    case 'ai_analyze_schedule':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }

        $raw = isset($_POST['data']) ? $_POST['data'] : '';
        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['type'])) {
            jsonResponse(-1, '暂无课表数据');
        }

        $type = $data['type'];
        if ($type === 'semester') {
            $courses = isset($data['courses']) ? $data['courses'] : [];
            if (!is_array($courses) || count($courses) === 0) {
                jsonResponse(-1, '暂无课程数据');
            }
            $week = isset($data['week']) ? strval($data['week']) : '当前';
            $lines = [];
            foreach ($courses as $c) {
                $lines[] = sprintf(
                    "- %s：%s 第%s节，地点 %s，教师 %s，周次 %s，学分 %s，性质 %s",
                    $c['name'] ?? '未知',
                    $c['weekDay'] ?? '未知',
                    $c['section'] ?? '-',
                    $c['location'] ?? '待定',
                    $c['teacher'] ?? '待定',
                    $c['weeks'] ?? '-',
                    $c['credit'] ?? '-',
                    $c['nature'] ?? '-'
                );
            }
            $prompt = "你是一位大学学业与时间管理顾问。以下是某学生第 {$week} 周的课程表：\n"
                . implode("\n", $lines) . "\n\n"
                . "请基于以上课程表，给出：1）本周课程负担与时间安排评价（含每天课程密度、是否有早课或晚课）；"
                . "2）空闲时段盘点与合理利用建议（自习、休息、社团活动等）；"
                . "3）学习节奏与作息建议；4）需要注意的时间冲突或过于紧凑的安排提醒。"
                . "请用简体中文、分点清晰作答，可使用 Markdown。";
        } elseif ($type === 'today') {
            $courses = isset($data['courses']) ? $data['courses'] : [];
            if (!is_array($courses) || count($courses) === 0) {
                jsonResponse(-1, '今日暂无课程');
            }
            $dayName = $data['dayName'] ?? '当天';
            $date = $data['date'] ?? '';
            $dateText = $date ? "（{$date}）" : '';
            $lines = [];
            foreach ($courses as $c) {
                $lines[] = sprintf(
                    "- %s：%s，地点 %s，教师 %s",
                    $c['name'] ?? '未知',
                    $c['time'] ?? '待定',
                    $c['location'] ?? '待定',
                    $c['teacher'] ?? '待定'
                );
            }
            $prompt = "你是一位大学时间管理顾问。以下是某学生{$dayName}{$dateText}的课程安排：\n"
                . implode("\n", $lines) . "\n\n"
                . "请基于以上安排，给出：1）今日课程负担评价；2）课间与空档时间的利用建议；"
                . "3）作息与精力分配建议；4）预习与复习安排建议。"
                . "请用简体中文、分点清晰作答，可使用 Markdown。";
        } else {
            jsonResponse(-1, '暂不支持的分析类型');
        }

        $messages = [
            ['role' => 'system', 'content' => '你是一位专业、贴心的大学学业与时间管理顾问，擅长从课表数据给出实用建议。'],
            ['role' => 'user', 'content' => $prompt],
        ];
        $ai = new \lib\AiHelper();
        $result = $ai->chat($messages);
        if (!$result['success']) {
            jsonResponse(-1, $result['msg']);
        }
        $content = isset($result['content']) ? trim($result['content']) : '';
        if ($content === '') {
            jsonResponse(-1, '分析结果为空，请重试');
        }
        jsonResponse(0, 'ok', ['content' => $content, 'cached' => 0]);
    break;

    case 'query_grade':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $academic = $DB->getRow("SELECT * FROM nteam_academic_accounts WHERE user_id='{$user['user_id']}'");
        if (!$academic) {
            jsonResponse(-1, '请先绑定教务账号');
        }
        
        require_once 'funsion/AcademicSystem.php';
        
        $semester = isset($_POST['semester']) ? addslashes($_POST['semester']) : '';
        
        if (empty($semester)) {
            $year = isset($_POST['year']) ? addslashes($_POST['year']) : '';
            $term = isset($_POST['term']) ? addslashes($_POST['term']) : '';
            $semester = $year && $term ? $year . '-' . $term : '';
        }
        
        $tokenResult = AcademicSystem::getValidToken($DB, $user['user_id'], $academic['student_id'], $academic['password']);
        
        if (!$tokenResult['success']) {
            jsonResponse(-1, $tokenResult['msg'] ?? '登录教务系统失败');
        }
        
        $token = $tokenResult['token'];
        
        if (empty($semester)) {
            // 未提交指定学期时，优先使用后台「成绩推送学期」配置
            // （Adminq/academic_push_config.php?tab=config 设置，uniapp 与微信小程序共用）
            $cfgRow = $DB->getRow("SELECT grade_semester FROM nteam_academic_push_config WHERE id = 1");
            if ($cfgRow && !empty($cfgRow['grade_semester'])) {
                $semester = $cfgRow['grade_semester'];
            } else {
                // 未配置则回退为教务系统当前学期
                $semesterResult = AcademicSystem::getSemesterList($token);
                if (isset($semesterResult['code']) && $semesterResult['code'] == 1 && !empty($semesterResult['data'])) {
                    foreach ($semesterResult['data'] as $s) {
                        if (isset($s['isdqxq']) && $s['isdqxq'] == '1') {
                            $semester = $s['semesterId'];
                            break;
                        }
                    }
                    if (empty($semester)) {
                        $semester = $semesterResult['data'][0]['semesterId'];
                    }
                }
            }
        }
        
        $gradeResult = AcademicSystem::getGrades($token, $semester);
        
        if (isset($gradeResult['code']) && $gradeResult['code'] == 1) {
            $data = $gradeResult['data'] ?? [];
            
            foreach ($data as &$semesterData) {
                if (isset($semesterData['achievement']) && is_array($semesterData['achievement'])) {
                    $totalGpaPoints = 0;
                    $totalCredits = 0;
                    
                    foreach ($semesterData['achievement'] as &$course) {
                        $score = isset($course['fraction']) ? floatval($course['fraction']) : 0;
                        $credit = isset($course['credit']) ? floatval($course['credit']) : 0;
                        
                        $gpa = 0;
                        if ($score >= 90) $gpa = 4.0;
                        elseif ($score >= 85) $gpa = 3.7;
                        elseif ($score >= 82) $gpa = 3.3;
                        elseif ($score >= 78) $gpa = 3.0;
                        elseif ($score >= 75) $gpa = 2.7;
                        elseif ($score >= 72) $gpa = 2.3;
                        elseif ($score >= 68) $gpa = 2.0;
                        elseif ($score >= 64) $gpa = 1.5;
                        elseif ($score >= 60) $gpa = 1.0;
                        
                        $course['gpa'] = $gpa;
                        
                        if ($score >= 60 && $credit > 0) {
                            $totalGpaPoints += $gpa * $credit;
                            $totalCredits += $credit;
                        }
                    }
                    
                    $avgGpa = $totalCredits > 0 ? round($totalGpaPoints / $totalCredits, 2) : 0;
                    
                    if (empty($semesterData['pjxfjd']) || floatval($semesterData['pjxfjd']) == 0) {
                        $semesterData['pjxfjd'] = $avgGpa;
                    }
                }
            }
            
            jsonResponse(0, '查询成功', $data);
        } else {
            jsonResponse(-1, $gradeResult['msg'] ?? $gradeResult['Msg'] ?? '获取成绩失败');
        }
    break;
    
    case 'query_schedule':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $academic = $DB->getRow("SELECT * FROM nteam_academic_accounts WHERE user_id='{$user['user_id']}'");
        if (!$academic) {
            jsonResponse(-1, '请先绑定教务账号');
        }
        
        require_once 'funsion/AcademicSystem.php';
        
        $week = isset($_POST['week']) ? addslashes($_POST['week']) : '';
        $kbjcmsid = isset($_POST['kbjcmsid']) ? addslashes($_POST['kbjcmsid']) : '';
        
        $tokenResult = AcademicSystem::getValidToken($DB, $user['user_id'], $academic['student_id'], $academic['password']);
        
        if (!$tokenResult['success']) {
            jsonResponse(-1, $tokenResult['msg'] ?? '登录教务系统失败');
        }
        
        $token = $tokenResult['token'];
        
        // 未指定课程表模式时：
        // - 已配置系统默认类型 → 强制使用该类型（符合「全局使用系统配置」需求，不做回退）
        // - 未配置 → 自动遍历所有模式返回第一个有课的，避免空白
        if ($kbjcmsid === '') {
            // 读取系统默认课程表类型（后台配置）
            $defaultMode = '';
            $cfgRow = $DB->getRow("SELECT default_schedule_mode FROM nteam_academic_push_config WHERE id = 1");
            if ($cfgRow && !empty($cfgRow['default_schedule_mode'])) {
                $defaultMode = $cfgRow['default_schedule_mode'];
            }

            if ($defaultMode) {
                // 已配置：强制使用系统默认类型
                $kbjcmsid = $defaultMode;
            } else {
                // 未配置：自动尝试所有模式，返回第一个有课程的
                $modeIds = [];
                $modeResult = AcademicSystem::getScheduleMode($token);
                if (isset($modeResult['code']) && $modeResult['code'] == 1 && is_array($modeResult['data'])) {
                    foreach ($modeResult['data'] as $m) {
                        if (!empty($m['kbjcmsid'])) {
                            $modeIds[] = $m['kbjcmsid'];
                        }
                    }
                }

                $finalResult = null;
                foreach ($modeIds as $mid) {
                    $r = AcademicSystem::getSchedule($token, $week, $mid);
                    $hasCourse = isset($r['code']) && $r['code'] == 1
                        && !empty($r['data']) && !empty($r['data'][0]['courses']);
                    if ($hasCourse) {
                        $finalResult = $r;
                        break;
                    }
                    if ($finalResult === null) {
                        $finalResult = $r;
                    }
                }

                if ($finalResult !== null) {
                    if (isset($finalResult['code']) && $finalResult['code'] == 1) {
                        jsonResponse(0, '查询成功', $finalResult['data'] ?? []);
                    } else {
                        jsonResponse(-1, $finalResult['msg'] ?? $finalResult['Msg'] ?? '获取课表失败');
                    }
                }
                // 没有任何可尝试的模式，退化为默认模式查询
                $kbjcmsid = '';
            }
        }
        
        $scheduleResult = AcademicSystem::getSchedule($token, $week, $kbjcmsid);
        
        if (isset($scheduleResult['code']) && $scheduleResult['code'] == 1) {
            jsonResponse(0, '查询成功', $scheduleResult['data'] ?? []);
        } else {
            jsonResponse(-1, $scheduleResult['msg'] ?? $scheduleResult['Msg'] ?? '获取课表失败');
        }
    break;
    
    case 'query_student_info':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $academic = $DB->getRow("SELECT * FROM nteam_academic_accounts WHERE user_id='{$user['user_id']}'");
        if (!$academic) {
            jsonResponse(-1, '请先绑定教务账号');
        }
        
        require_once 'funsion/AcademicSystem.php';
        
        $tokenResult = AcademicSystem::getValidToken($DB, $user['user_id'], $academic['student_id'], $academic['password']);
        
        if (!$tokenResult['success']) {
            jsonResponse(-1, $tokenResult['msg'] ?? '登录教务系统失败');
        }
        
        $token = $tokenResult['token'];
        
        $infoResult = AcademicSystem::getStudentInfo($token);
        
        if (isset($infoResult['code']) && $infoResult['code'] == 1) {
            jsonResponse(0, '查询成功', $infoResult['data'] ?? []);
        } else {
            jsonResponse(-1, $infoResult['msg'] ?? $infoResult['Msg'] ?? '获取学籍信息失败');
        }
    break;
    
    case 'get_semester_list':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $academic = $DB->getRow("SELECT * FROM nteam_academic_accounts WHERE user_id='{$user['user_id']}'");
        if (!$academic) {
            jsonResponse(-1, '请先绑定教务账号');
        }
        
        require_once 'funsion/AcademicSystem.php';
        
        $tokenResult = AcademicSystem::getValidToken($DB, $user['user_id'], $academic['student_id'], $academic['password']);
        
        if (!$tokenResult['success']) {
            jsonResponse(-1, $tokenResult['msg'] ?? '登录教务系统失败');
        }
        
        $token = $tokenResult['token'];
        
        $semesterResult = AcademicSystem::getSemesterList($token);
        
        if (isset($semesterResult['code']) && $semesterResult['code'] == 1) {
            $list = $semesterResult['data'] ?? [];
            
            // 解析默认学期：与成绩查询接口一致（后台「成绩推送学期」配置 > 当前学期 > 第一个）
            $defaultId = '';
            $defaultName = '';
            $cfgRow = $DB->getRow("SELECT grade_semester FROM nteam_academic_push_config WHERE id = 1");
            $configSemester = ($cfgRow && !empty($cfgRow['grade_semester'])) ? $cfgRow['grade_semester'] : '';
            if ($configSemester) {
                $defaultId = $configSemester;
            } elseif (!empty($list)) {
                foreach ($list as $s) {
                    if (isset($s['isdqxq']) && $s['isdqxq'] == '1') {
                        $defaultId = $s['semesterId'];
                        break;
                    }
                }
                if ($defaultId === '') {
                    $defaultId = $list[0]['semesterId'];
                }
            }
            if ($defaultId !== '' && !empty($list)) {
                foreach ($list as $s) {
                    if (isset($s['semesterId']) && $s['semesterId'] == $defaultId) {
                        $defaultName = $s['semesterName'] ?? '';
                        break;
                    }
                }
            }
            
            jsonResponse(0, '查询成功', [
                'list' => $list,
                'default' => ['id' => $defaultId, 'name' => $defaultName]
            ]);
        } else {
            jsonResponse(-1, $semesterResult['msg'] ?? $semesterResult['Msg'] ?? '获取学期列表失败');
        }
    break;
    
    case 'get_schedule_mode':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $academic = $DB->getRow("SELECT * FROM nteam_academic_accounts WHERE user_id='{$user['user_id']}'");
        if (!$academic) {
            jsonResponse(-1, '请先绑定教务账号');
        }
        
        require_once 'funsion/AcademicSystem.php';
        
        $tokenResult = AcademicSystem::getValidToken($DB, $user['user_id'], $academic['student_id'], $academic['password']);
        
        if (!$tokenResult['success']) {
            jsonResponse(-1, $tokenResult['msg'] ?? '登录教务系统失败');
        }
        
        $token = $tokenResult['token'];
        
        $modeResult = AcademicSystem::getScheduleMode($token);

        // 读取系统默认课程表类型（后台 academic_push_config 配置）
        $defaultMode = '';
        $cfgRow = $DB->getRow("SELECT default_schedule_mode FROM nteam_academic_push_config WHERE id = 1");
        if ($cfgRow && !empty($cfgRow['default_schedule_mode'])) {
            $defaultMode = $cfgRow['default_schedule_mode'];
        }

        // 将系统默认类型置顶，前端默认取 modes[0] 即为系统配置的类型
        $modes = $modeResult['data'] ?? [];
        if ($defaultMode && is_array($modes)) {
            foreach ($modes as $k => $m) {
                if (isset($m['kbjcmsid']) && $m['kbjcmsid'] === $defaultMode) {
                    $hit = $modes[$k];
                    unset($modes[$k]);
                    array_unshift($modes, $hit);
                    $modes = array_values($modes);
                    break;
                }
            }
        }

        if (isset($modeResult['code']) && $modeResult['code'] == 1) {
            jsonResponse(0, '查询成功', $modes);
        } else {
            jsonResponse(-1, $modeResult['msg'] ?? $modeResult['Msg'] ?? '获取课程表类别失败');
        }
    break;
    
    case 'get_notices':
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $size = isset($_GET['size']) ? intval($_GET['size']) : 10;
        $offset = ($page - 1) * $size;
        
        $notices = $DB->getAll("SELECT id, title, thumbnail as cover, create_time, is_top FROM nteam_announcements WHERE is_published=1 ORDER BY is_top DESC, create_time DESC LIMIT $offset, $size");
        $total = $DB->getRow("SELECT COUNT(*) as count FROM nteam_announcements WHERE is_published=1")['count'];
        
        jsonResponse(0, 'success', [
            'list' => $notices ?: [],
            'total' => (int)$total,
            'page' => $page,
            'size' => $size
        ]);
    break;
    
    case 'get_notice_detail':
        $id = intval($_GET['id']);
        
        if ($id <= 0) {
            jsonResponse(-1, '参数错误');
        }
        
        $notice = $DB->getRow("SELECT id, title, content, thumbnail as cover, image, view_count, author, create_time, is_top FROM nteam_announcements WHERE id='$id' AND is_published=1");
        
        if (!$notice) {
            jsonResponse(-1, '通知不存在');
        }
        
        $DB->exec("UPDATE nteam_announcements SET view_count = view_count + 1 WHERE id='$id'");
        
        jsonResponse(0, 'success', $notice);
    break;
    
    case 'get_openid':
        $code = isset($_POST['code']) ? trim($_POST['code']) : '';
        
        if (empty($code)) {
            jsonResponse(-1, '请提供登录凭证');
        }
        
        $wxConfig = $DB->getRow("SELECT appid, secret FROM nteam_wx_config ORDER BY id DESC LIMIT 1");
        
        if (!$wxConfig || empty($wxConfig['appid']) || empty($wxConfig['secret'])) {
            jsonResponse(-1, '服务器未配置微信小程序信息');
        }
        
        $appid = $wxConfig['appid'];
        $secret = $wxConfig['secret'];
        
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid={$appid}&secret={$secret}&js_code={$code}&grant_type=authorization_code";
        
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if (isset($data['openid'])) {
            $user = authenticateUser($DB);
            if ($user) {
                $openid = addslashes($data['openid']);
                $DB->exec("UPDATE nteam_users SET wx_openid='{$openid}' WHERE user_id='{$user['user_id']}'");
            }
            
            jsonResponse(0, 'success', [
                'openid' => $data['openid'],
                'session_key' => $data['session_key'] ?? ''
            ]);
        } else {
            jsonResponse(-1, $data['errmsg'] ?? '获取OpenID失败');
        }
    break;
    
    case 'save_subscription':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $templateId = isset($_POST['template_id']) ? addslashes(trim($_POST['template_id'])) : '';
        
        if (empty($templateId)) {
            jsonResponse(-1, '请提供模板ID');
        }
        
        $openid = $DB->getRow("SELECT wx_openid FROM nteam_users WHERE id='{$user['user_id']}'");
        
        if (empty($openid['wx_openid'])) {
            jsonResponse(-1, '请先获取OpenID');
        }
        
        $subscribeTime = date('Y-m-d H:i:s');
        $exists = $DB->getRow("SELECT id, subscribe_count FROM nteam_subscriptions WHERE user_id='{$user['user_id']}' AND template_id='{$templateId}'");
        
        if ($exists) {
            $newCount = intval($exists['subscribe_count']) + 1;
            $DB->exec("UPDATE nteam_subscriptions SET subscribe_time='{$subscribeTime}', subscribe_count={$newCount}, status=1 WHERE id='{$exists['id']}'");
        } else {
            $DB->exec("INSERT INTO nteam_subscriptions (user_id, openid, template_id, subscribe_count, subscribe_time, status) VALUES ('{$user['user_id']}', '{$openid['wx_openid']}', '{$templateId}', 1, '{$subscribeTime}', 1)");
        }
        
        $DB->exec("INSERT INTO nteam_subscription_logs (user_id, template_id, action, count_change) VALUES ('{$user['user_id']}', '{$templateId}', 'subscribe', 1)");
        
        $finalCount = $exists ? (intval($exists['subscribe_count']) + 1) : 1;
        jsonResponse(0, '保存成功', ['subscribe_count' => $finalCount]);
    break;
    
    case 'send_subscribe_message':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $templateId = isset($_POST['template_id']) ? addslashes(trim($_POST['template_id'])) : '';
        $openid = isset($_POST['openid']) ? addslashes(trim($_POST['openid'])) : '';
        $customDataStr = isset($_POST['data']) ? $_POST['data'] : '';
        $customData = !empty($customDataStr) ? json_decode($customDataStr, true) : [];
        
        if (empty($templateId) || empty($openid)) {
            jsonResponse(-1, '参数不完整');
        }
        
        $subscription = $DB->getRow("SELECT id, subscribe_count FROM nteam_subscriptions WHERE user_id='{$user['user_id']}' AND template_id='{$templateId}' AND status=1");
        
        if (!$subscription || intval($subscription['subscribe_count']) <= 0) {
            jsonResponse(-1, '订阅次数不足，请先订阅消息');
        }
        
        $wxConfig = $DB->getRow("SELECT appid, secret FROM nteam_wx_config ORDER BY id DESC LIMIT 1");
        
        if (!$wxConfig || empty($wxConfig['appid']) || empty($wxConfig['secret'])) {
            jsonResponse(-1, '服务器未配置微信小程序信息');
        }
        
        $appid = $wxConfig['appid'];
        $secret = $wxConfig['secret'];
        
        $tokenUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
        $tokenResponse = file_get_contents($tokenUrl);
        $tokenData = json_decode($tokenResponse, true);
        
        if (!isset($tokenData['access_token'])) {
            jsonResponse(-1, '获取access_token失败: ' . ($tokenData['errmsg'] ?? '未知错误'));
        }
        
        $accessToken = $tokenData['access_token'];
        
        $sendUrl = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token={$accessToken}";
        
        if (empty($customData)) {
            $template = $DB->getRow("SELECT default_data FROM nteam_wx_templates WHERE template_id='{$templateId}' AND status=1");
            if ($template && !empty($template['default_data'])) {
                $defaultData = json_decode($template['default_data'], true);
                if ($defaultData) {
                    $customData = $defaultData;
                }
            }
            
            if (empty($customData)) {
                $customData = [
                    'thing1' => ['value' => '测试数据'],
                    'thing2' => ['value' => '测试内容'],
                    'thing3' => ['value' => '测试信息'],
                    'thing4' => ['value' => '测试备注'],
                    'thing5' => ['value' => '测试描述'],
                    'time1' => ['value' => date('Y-m-d H:i')],
                    'time2' => ['value' => date('Y-m-d H:i')],
                    'time3' => ['value' => date('Y-m-d H:i')],
                    'date1' => ['value' => date('Y-m-d')],
                    'number1' => ['value' => '1'],
                    'number2' => ['value' => '100'],
                    'amount1' => ['value' => '100.00'],
                    'phrase1' => ['value' => '成功'],
                    'phrase2' => ['value' => '已完成'],
                    'character_string1' => ['value' => 'TEST001'],
                    'character_string2' => ['value' => 'ABC123']
                ];
            }
        }
        
        $data = [
            'touser' => $openid,
            'template_id' => $templateId,
            'page' => 'pages/white-home/index',
            'data' => $customData,
            'miniprogram_state' => 'developer'
        ];
        
        $options = [
            'http' => [
                'header' => "Content-type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($sendUrl, false, $context);
        $resultData = json_decode($result, true);
        
        if (isset($resultData['errcode']) && $resultData['errcode'] == 0) {
            $newCount = intval($subscription['subscribe_count']) - 1;
            $DB->exec("UPDATE nteam_subscriptions SET subscribe_count={$newCount} WHERE id='{$subscription['id']}'");
            jsonResponse(0, '发送成功', ['subscribe_count' => $newCount]);
        } else {
            jsonResponse(-1, '发送失败: ' . ($resultData['errmsg'] ?? '未知错误'));
        }
    break;
    
    case 'get_subscription_count':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $templateId = isset($_GET['template_id']) ? addslashes(trim($_GET['template_id'])) : '';
        
        if (empty($templateId)) {
            jsonResponse(-1, '请提供模板ID');
        }
        
        $subscription = $DB->getRow("SELECT subscribe_count FROM nteam_subscriptions WHERE user_id='{$user['user_id']}' AND template_id='{$templateId}' AND status=1");
        
        $count = $subscription ? intval($subscription['subscribe_count']) : 0;
        jsonResponse(0, 'success', ['subscribe_count' => $count]);
    break;
    
    case 'get_wx_templates':
        $templates = $DB->getAll("SELECT id, template_id, name, description, fields, default_data FROM nteam_wx_templates WHERE status=1 ORDER BY id DESC");
        
        if ($templates) {
            foreach ($templates as &$tpl) {
                $tpl['fields'] = json_decode($tpl['fields'], true);
                $tpl['default_data'] = json_decode($tpl['default_data'], true);
            }
        }
        
        jsonResponse(0, 'success', ['list' => $templates ?: []]);
    break;
    
    case 'add_wx_template':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $templateId = isset($_POST['template_id']) ? addslashes(trim($_POST['template_id'])) : '';
        $name = isset($_POST['name']) ? addslashes(trim($_POST['name'])) : '';
        $description = isset($_POST['description']) ? addslashes(trim($_POST['description'])) : '';
        $fields = isset($_POST['fields']) ? $_POST['fields'] : '';
        $defaultData = isset($_POST['default_data']) ? $_POST['default_data'] : '';
        
        if (empty($templateId) || empty($name)) {
            jsonResponse(-1, '模板ID和名称不能为空');
        }
        
        $exists = $DB->getRow("SELECT id FROM nteam_wx_templates WHERE template_id='{$templateId}'");
        if ($exists) {
            $DB->exec("UPDATE nteam_wx_templates SET name='{$name}', description='{$description}', fields='{$fields}', default_data='{$defaultData}' WHERE id='{$exists['id']}'");
            jsonResponse(0, '更新成功');
        } else {
            $DB->exec("INSERT INTO nteam_wx_templates (template_id, name, description, fields, default_data, status) VALUES ('{$templateId}', '{$name}', '{$description}', '{$fields}', '{$defaultData}', 1)");
            jsonResponse(0, '添加成功');
        }
    break;
    
    case 'delete_wx_template':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) {
            jsonResponse(-1, '参数错误');
        }
        
        $DB->exec("DELETE FROM nteam_wx_templates WHERE id='{$id}'");
        jsonResponse(0, '删除成功');
    break;

    case 'get_push_settings':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        require_once 'funsion/feature_settings.php';
        if (!isFeatureEnabled($DB, 'push_notification')) {
            jsonResponse(-1, '推送功能暂未开放');
        }
        
        $userId = $user['user_id'];
        
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
                if ($b['alias']) {
                    $displayName = $b['alias'] . '（' . $b['school_area'] . $b['building'] . ' ' . $b['room'] . '）';
                } else {
                    $displayName = $b['school_area'] . $b['building'] . ' ' . $b['room'];
                }
                $result[] = [
                    'binding_id' => $b['id'],
                    'user_id' => $b['user_id'],
                    'room_num' => $b['room_num'],
                    'school_area' => $b['school_area'] ?? '',
                    'school_area_no' => $b['school_area_no'] ?? '',
                    'building_no' => $b['building_no'] ?? '',
                    'building' => $b['building'] ?? '',
                    'room' => $b['room'] ?? '',
                    'alias' => $b['alias'] ?? '',
                    'display_name' => $displayName,
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
        
        jsonResponse(0, 'success', [
            'bindings' => $result,
            'config' => $config ?: []
        ]);
    break;

    case 'save_push_settings':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        require_once 'funsion/feature_settings.php';
        if (!isFeatureEnabled($DB, 'push_notification')) {
            jsonResponse(-1, '推送功能暂未开放');
        }
        
        $dormitoryBindingId = isset($_POST['dormitory_binding_id']) ? intval($_POST['dormitory_binding_id']) : 0;
        $pushTypes = isset($_POST['push_types']) ? addslashes($_POST['push_types']) : 'wechat';
        $notifyTypes = isset($_POST['notify_types']) ? addslashes($_POST['notify_types']) : '';
        $balanceLowThreshold = isset($_POST['balance_low_threshold']) ? floatval($_POST['balance_low_threshold']) : 30;
        $balanceCriticalThreshold = isset($_POST['balance_critical_threshold']) ? floatval($_POST['balance_critical_threshold']) : 10;
        $isEnabled = isset($_POST['is_enabled']) ? intval($_POST['is_enabled']) : 1;
        
        if ($dormitoryBindingId <= 0) {
            jsonResponse(-1, '请选择宿舍');
        }
        
        $binding = $DB->getRow("SELECT * FROM nteam_dormitory_bindings WHERE id='{$dormitoryBindingId}' AND user_id='{$user['user_id']}' AND status=1");
        if (!$binding) {
            jsonResponse(-1, '宿舍绑定不存在');
        }
        
        $exists = $DB->getRow("SELECT id FROM nteam_push_settings WHERE user_id='{$user['user_id']}' AND dormitory_binding_id='{$dormitoryBindingId}'");
        
        if ($exists) {
            $sql = "UPDATE nteam_push_settings SET 
                push_types = '{$pushTypes}',
                notify_types = '{$notifyTypes}',
                balance_low_threshold = '{$balanceLowThreshold}',
                balance_critical_threshold = '{$balanceCriticalThreshold}',
                is_enabled = '{$isEnabled}',
                updated_at = NOW()
                WHERE id = '{$exists['id']}'";
        } else {
            $sql = "INSERT INTO nteam_push_settings 
                (user_id, dormitory_binding_id, push_types, notify_types, balance_low_threshold, balance_critical_threshold, is_enabled)
                VALUES ('{$user['user_id']}', '{$dormitoryBindingId}', '{$pushTypes}', '{$notifyTypes}', '{$balanceLowThreshold}', '{$balanceCriticalThreshold}', '{$isEnabled}')";
        }
        
        if ($DB->exec($sql)) {
            jsonResponse(0, '保存成功');
        } else {
            jsonResponse(-1, '保存失败');
        }
    break;

    case 'delete_push_settings':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) {
            jsonResponse(-1, '参数错误');
        }
        
        $DB->exec("DELETE FROM nteam_push_settings WHERE id='{$id}' AND user_id='{$user['user_id']}'");
        jsonResponse(0, '删除成功');
    break;

    case 'get_power_config':
        $config = $DB->getRow("SELECT * FROM nteam_power_config WHERE id = 1");
        if (!$config) {
            $config = [
                'price_per_kwh' => 0.61,
                'low_balance_threshold' => 30,
                'critical_balance_threshold' => 10
            ];
        }
        jsonResponse(0, 'success', $config);
    break;
    
    case 'get_academic_push_settings':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $userId = $user['user_id'];
        $reqAccountId = isset($_GET['academic_account_id']) ? intval($_GET['academic_account_id']) : 0;
        
        // 该用户绑定的所有教务账号
        $accounts = $DB->getAll("SELECT id, student_id, real_name FROM nteam_academic_accounts WHERE user_id='$userId' ORDER BY id ASC");
        $accounts = $accounts ?: [];
        
        // 确定当前查询的账号：优先使用前端传回的 account_id，否则取第一个
        $currentAccount = null;
        foreach ($accounts as $a) {
            if ($reqAccountId && intval($a['id']) === $reqAccountId) {
                $currentAccount = $a;
                break;
            }
        }
        if (!$currentAccount && !empty($accounts)) {
            $currentAccount = $accounts[0];
        }
        $academicAccountId = $currentAccount ? intval($currentAccount['id']) : 0;
        
        // 按具体教务账号查询设置（修复多账号时只取第一条导致显示错误的问题）
        $settings = $academicAccountId
            ? $DB->getRow("SELECT * FROM nteam_academic_push_settings WHERE user_id='$userId' AND academic_account_id='$academicAccountId'")
            : false;
        $academicAccount = $currentAccount;
        
        $globalConfig = $DB->getRow("SELECT schedule_reminder_enabled, grade_notify_enabled, wechat_push_enabled, email_push_enabled FROM nteam_academic_push_config WHERE id = 1");
        if (!$globalConfig) {
            $globalConfig = [
                'schedule_reminder_enabled' => 1,
                'grade_notify_enabled' => 1,
                'wechat_push_enabled' => 1,
                'email_push_enabled' => 1
            ];
        }

        // 若当前账号没有个人设置记录，则基于系统默认配置返回「开启」状态，
        // 避免页面加载时所有开关都错误地显示为关闭
        if (!$settings) {
            $settings = [
                'is_enabled' => 1,
                'push_types' => 'wechat',
                'schedule_reminder_enabled' => !empty($globalConfig['schedule_reminder_enabled']) ? 1 : 0,
                'schedule_reminder_time' => '20:00:00',
                'grade_notify_enabled' => !empty($globalConfig['grade_notify_enabled']) ? 1 : 0
            ];
        }
        
        jsonResponse(0, 'success', [
            'settings' => $settings,
            'student_id' => $academicAccount ? $academicAccount['student_id'] : null,
            'has_academic_account' => $academicAccount ? true : false,
            'academic_accounts' => $accounts,
            'current_account_id' => $academicAccountId,
            'global_config' => $globalConfig
        ]);
    break;
    
    case 'save_academic_push_settings':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $userId = $user['user_id'];
        $reqAccountId = isset($_POST['academic_account_id']) ? intval($_POST['academic_account_id']) : 0;
        
        // 优先使用前端传回的具体教务账号，避免写入错误的账号
        $academicAccount = null;
        if ($reqAccountId) {
            $academicAccount = $DB->getRow("SELECT id FROM nteam_academic_accounts WHERE user_id='$userId' AND id='$reqAccountId'");
        }
        if (!$academicAccount) {
            $academicAccount = $DB->getRow("SELECT id FROM nteam_academic_accounts WHERE user_id='$userId'");
        }
        if (!$academicAccount) {
            jsonResponse(-1, '请先绑定教务账号');
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
            jsonResponse(0, '保存成功');
        } else {
            jsonResponse(-1, '保存失败');
        }
    break;
    
    case 'get_academic_push_logs':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $userId = $user['user_id'];
        $logs = $DB->getAll("SELECT * FROM nteam_academic_push_logs WHERE user_id='$userId' ORDER BY created_at DESC LIMIT 20");
        
        jsonResponse(0, 'success', $logs ?: []);
    break;
    
    case 'get_user_subscriptions':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $userId = $user['user_id'];
        
        $templates = $DB->getAll("SELECT id, template_id, name, description, scene_type FROM nteam_wx_templates WHERE status = 1 ORDER BY id DESC");
        
        $subscriptions = $DB->getAll("SELECT template_id, subscribe_count, subscribe_time FROM nteam_subscriptions WHERE user_id='$userId' AND status = 1");
        
        $subscriptionMap = [];
        foreach ($subscriptions as $sub) {
            $subscriptionMap[$sub['template_id']] = $sub;
        }
        
        $result = [];
        foreach ($templates as $tpl) {
            $sub = isset($subscriptionMap[$tpl['template_id']]) ? $subscriptionMap[$tpl['template_id']] : null;
            $result[] = [
                'id' => $tpl['id'],
                'template_id' => $tpl['template_id'],
                'name' => $tpl['name'],
                'description' => $tpl['description'],
                'scene_type' => $tpl['scene_type'],
                'subscribe_count' => $sub ? intval($sub['subscribe_count']) : 0,
                'subscribe_time' => $sub ? $sub['subscribe_time'] : null
            ];
        }
        
        jsonResponse(0, 'success', ['list' => $result]);
    break;
    
    case 'get_user_subscription_logs':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $userId = $user['user_id'];
        
        $logs = $DB->getAll("SELECT s.*, t.name as template_name FROM nteam_subscription_logs s 
            LEFT JOIN nteam_wx_templates t ON s.template_id = t.template_id 
            WHERE s.user_id='$userId' 
            ORDER BY s.created_at DESC 
            LIMIT 50");
        
        jsonResponse(0, 'success', $logs ?: []);
    break;
    
    case 'get_subscription_history':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }

        $userId = intval($user['user_id']);
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $size = isset($_GET['size']) ? intval($_GET['size']) : 10;
        if ($size <= 0 || $size > 50) {
            $size = 10;
        }
        $type = isset($_GET['type']) ? trim($_GET['type']) : 'all';

        $records = [];

        // 订阅授权 / 发送记录
        if ($type === 'all' || $type === 'subscribe') {
            $subLogs = $DB->getAll("SELECT s.id, s.template_id, s.action, s.count_change, s.created_at, t.name AS template_name
                FROM nteam_subscription_logs s
                LEFT JOIN nteam_wx_templates t ON s.template_id = t.template_id
                WHERE s.user_id = '{$userId}'
                ORDER BY s.created_at DESC
                LIMIT 200");

            if ($subLogs) {
                foreach ($subLogs as $log) {
                    $action = $log['action'];
                    if ($action === 'subscribe') {
                        $category = '订阅授权';
                        $title = $log['template_name'] ? ('订阅了「' . $log['template_name'] . '」') : '订阅消息授权';
                        $statusText = '已授权';
                    } else {
                        $category = '消息发送';
                        $title = $log['template_name'] ? ('发送了「' . $log['template_name'] . '」') : '发送订阅消息';
                        $statusText = '已发送';
                    }
                    $records[] = [
                        'id' => 's_' . $log['id'],
                        'source' => 'subscription',
                        'category' => $category,
                        'title' => $title,
                        'content' => '次数变化：' . ($log['count_change'] > 0 ? '+' : '') . intval($log['count_change']),
                        'time' => $log['created_at'],
                        'status' => 1,
                        'status_text' => $statusText,
                        'error_msg' => ''
                    ];
                }
            }
        }

        // 电费推送记录
        if ($type === 'all' || $type === 'push') {
            $powerNotifyMap = [
                'daily_summary' => '用电汇总',
                'balance_low' => '余额提醒',
                'balance_critical' => '余额告警',
                'arrears' => '欠费提醒'
            ];
            $pushLogs = $DB->getAll("SELECT id, notify_type, title, content, status, error_msg, sent_at, created_at
                FROM nteam_push_logs
                WHERE user_id = '{$userId}'
                ORDER BY created_at DESC
                LIMIT 200");
            if ($pushLogs) {
                foreach ($pushLogs as $log) {
                    $status = intval($log['status']);
                    $statusMap = [0 => '待发送', 1 => '发送成功', 2 => '发送失败'];
                    $records[] = [
                        'id' => 'p_' . $log['id'],
                        'source' => 'power_push',
                        'category' => isset($powerNotifyMap[$log['notify_type']]) ? $powerNotifyMap[$log['notify_type']] : '电费推送',
                        'title' => $log['title'],
                        'content' => $log['content'],
                        'time' => !empty($log['sent_at']) ? $log['sent_at'] : $log['created_at'],
                        'status' => $status,
                        'status_text' => isset($statusMap[$status]) ? $statusMap[$status] : '未知',
                        'error_msg' => $log['error_msg'] ?: ''
                    ];
                }
            }

            // 学业推送记录
            $academicNotifyMap = [
                'schedule_reminder' => '课程提醒',
                'grade_notify' => '成绩通知'
            ];
            $academicLogs = $DB->getAll("SELECT id, notify_type, title, content, status, error_msg, sent_at, created_at
                FROM nteam_academic_push_logs
                WHERE user_id = '{$userId}'
                ORDER BY created_at DESC
                LIMIT 200");
            if ($academicLogs) {
                foreach ($academicLogs as $log) {
                    $status = intval($log['status']);
                    $statusMap = [0 => '待发送', 1 => '发送成功', 2 => '发送失败'];
                    $records[] = [
                        'id' => 'a_' . $log['id'],
                        'source' => 'academic_push',
                        'category' => isset($academicNotifyMap[$log['notify_type']]) ? $academicNotifyMap[$log['notify_type']] : '教务推送',
                        'title' => $log['title'],
                        'content' => $log['content'],
                        'time' => !empty($log['sent_at']) ? $log['sent_at'] : $log['created_at'],
                        'status' => $status,
                        'status_text' => isset($statusMap[$status]) ? $statusMap[$status] : '未知',
                        'error_msg' => $log['error_msg'] ?: ''
                    ];
                }
            }
        }

        // 按时间倒序排序后分页
        usort($records, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });

        $total = count($records);
        $offset = ($page - 1) * $size;
        $list = array_slice($records, $offset, $size);

        jsonResponse(0, 'success', [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'size' => $size
        ]);
    break;
    
    case 'get_feature_settings':
        $keys = isset($_GET['keys']) ? trim($_GET['keys']) : '';
        
        if (!empty($keys)) {
            $keyArray = array_map('addslashes', array_filter(explode(',', $keys)));
            $keyList = "'" . implode("','", $keyArray) . "'";
            $features = $DB->query("SELECT feature_key, feature_name, is_enabled, announcement, announcement_type FROM nteam_feature_settings WHERE feature_key IN ($keyList)")->fetchAll();
        } else {
            $features = $DB->query("SELECT feature_key, feature_name, is_enabled, announcement, announcement_type FROM nteam_feature_settings ORDER BY id ASC")->fetchAll();
        }
        
        $result = [];
        foreach ($features as $feature) {
            $result[$feature['feature_key']] = [
                'feature_name' => $feature['feature_name'],
                'is_enabled' => (int)$feature['is_enabled'],
                'announcement' => $feature['announcement'],
                'announcement_type' => $feature['announcement_type'] ?: 'info'
            ];
        }
        
        jsonResponse(0, 'success', $result);
    break;
    
    case 'get_glance_data':
        $user = authenticateUser($DB);
        if (!$user) {
            jsonResponse(-2, '请先登录');
        }
        
        $userId = $user['user_id'];
        $glanceData = [
            'next_course' => null,
            'all_courses_ended' => false,
            'course_load_error' => false,
            'power_info' => null,
            'no_dormitory' => false
        ];
        
        // 课程速览：查询教务账号和今日课程
        $academic = $DB->getRow("SELECT * FROM nteam_academic_accounts WHERE user_id='{$userId}'");
        if ($academic) {
            try {
                require_once 'funsion/AcademicSystem.php';
                $tokenResult = AcademicSystem::getValidToken($DB, $userId, $academic['student_id'], $academic['password']);
                
                if ($tokenResult['success']) {
                    $token = $tokenResult['token'];
                    $scheduleResult = AcademicSystem::getSchedule($token, '', '');
                    
                    if (isset($scheduleResult['code']) && $scheduleResult['code'] == 1) {
                        $scheduleData = $scheduleResult['data'] ?? [];
                        
                        $timeSlots = [
                            1 => '08:15-08:55', 2 => '09:05-09:45', 3 => '10:05-10:45', 4 => '10:55-11:35',
                            5 => '14:30-15:10', 6 => '15:20-16:00', 7 => '16:15-16:55',
                            8 => '18:50-19:30', 9 => '19:40-20:20', 10 => '20:30-21:10', 11 => '21:20-22:00'
                        ];
                        
                        $currentDay = intval(date('N'));
                        $currentMinutes = intval(date('H')) * 60 + intval(date('i'));
                        
                        $todayCourses = [];
                        if (!empty($scheduleData) && isset($scheduleData[0]['courses'])) {
                            $currentWeek = $scheduleData[0]['week'] ?? 1;
                            
                            foreach ($scheduleData[0]['courses'] as $course) {
                                $classTime = $course['classTime'] ?? '';
                                if (!$classTime || strlen($classTime) < 2) continue;
                                
                                $courseWeekDay = $course['weekDay'] ?? '';
                                if (!empty($courseWeekDay)) {
                                    $weekDay = intval($courseWeekDay);
                                } else {
                                    $weekDay = intval($classTime[0]);
                                }
                                if ($weekDay !== $currentDay) continue;
                                
                                $classWeek = $course['classWeek'] ?? '';
                                if (!empty($classWeek)) {
                                    $weekMatched = false;
                                    if (preg_match('/^(\d+)-(\d+)$/', $classWeek, $m)) {
                                        $weekMatched = ($currentWeek >= intval($m[1]) && $currentWeek <= intval($m[2]));
                                    } else {
                                        $weekMatched = in_array(strval($currentWeek), explode(',', $classWeek));
                                    }
                                    if (!$weekMatched) continue;
                                }
                                
                                if (!empty($courseWeekDay)) {
                                    $timeStr = $classTime;
                                } else {
                                    $timeStr = substr($classTime, 1);
                                }
                                $sections = [];
                                for ($i = 0; $i < strlen($timeStr); $i += 2) {
                                    $sec = intval(substr($timeStr, $i, 2));
                                    if ($sec >= 1 && $sec <= 11) $sections[] = $sec;
                                }
                                if (empty($sections)) {
                                    for ($i = 0; $i < strlen($timeStr); $i++) {
                                        $sec = intval($timeStr[$i]);
                                        if ($sec >= 1 && $sec <= 11) $sections[] = $sec;
                                    }
                                }
                                if (empty($sections)) continue;
                                
                                $firstSection = min($sections);
                                $timeDisplay = $timeSlots[$firstSection] ?? '';
                                $startParts = explode('-', $timeDisplay);
                                $startMinutes = 0;
                                if (!empty($startParts[0])) {
                                    $sp = explode(':', $startParts[0]);
                                    $startMinutes = intval($sp[0]) * 60 + intval($sp[1]);
                                }
                                
                                $todayCourses[] = [
                                    'courseName' => $course['courseName'] ?? '未知课程',
                                    'location' => $course['location'] ?? $course['classroomName'] ?? '',
                                    'teacher' => $course['teacherName'] ?? '',
                                    'firstSection' => $firstSection,
                                    'timeDisplay' => $timeDisplay,
                                    'startMinutes' => $startMinutes
                                ];
                            }
                        }
                        
                        // 按开始时间排序
                        usort($todayCourses, function($a, $b) {
                            return $a['startMinutes'] - $b['startMinutes'];
                        });
                        
                        // 查找下一节课
                        $nextCourse = null;
                        $allEnded = true;
                        $courseDuration = 45; // 每节课45分钟
                        
                        foreach ($todayCourses as $course) {
                            if ($currentMinutes < $course['startMinutes']) {
                                $nextCourse = $course;
                                $allEnded = false;
                                $diff = $course['startMinutes'] - $currentMinutes;
                                if ($diff <= 15) {
                                    $nextCourse['statusText'] = '即将开始';
                                    $nextCourse['statusClass'] = 'tag-urgent';
                                } else {
                                    $nextCourse['statusText'] = $diff . '分钟后';
                                    $nextCourse['statusClass'] = 'tag-normal';
                                }
                                break;
                            } elseif ($currentMinutes < $course['startMinutes'] + $courseDuration) {
                                $nextCourse = $course;
                                $allEnded = false;
                                $nextCourse['statusText'] = '上课中';
                                $nextCourse['statusClass'] = 'tag-active';
                                break;
                            }
                        }
                        
                        $glanceData['next_course'] = $nextCourse;
                        $glanceData['all_courses_ended'] = $allEnded && !empty($todayCourses);
                    }
                }
            } catch (Exception $e) {
                $glanceData['course_load_error'] = true;
            }
        }
        
        // 电费速览：查询默认宿舍的电费余额
        $dormitories = $DB->getAll("SELECT room_num, alias, room, is_default FROM nteam_dormitory_bindings WHERE user_id='{$userId}' AND status=1 ORDER BY is_default DESC, id DESC");
        
        if (!empty($dormitories)) {
            $defaultDorm = null;
            foreach ($dormitories as $d) {
                if ($d['is_default'] == 1) {
                    $defaultDorm = $d;
                    break;
                }
            }
            if (!$defaultDorm) $defaultDorm = $dormitories[0];
            
            $roomNum = $defaultDorm['room_num'];
            $roomName = !empty($defaultDorm['alias']) ? $defaultDorm['alias'] : $defaultDorm['room'];
            
            try {
                require_once 'funsion/DormitorySystem.php';
                $result = DormitorySystem::getPowerFee($roomNum);
                
                if ($result['code'] == 1) {
                    $balance = $result['data']['balance'];
                    $balanceDisplay = $balance !== null ? number_format(floatval($balance), 2, '.', '') : '0.00';
                    
                    $statusText = '余额充足';
                    $statusClass = 'tag-normal';
                    if ($balance === null) {
                        $statusText = '无法查询';
                        $statusClass = 'tag-unknown';
                    } elseif (floatval($balance) <= 0) {
                        $statusText = '已欠费';
                        $statusClass = 'tag-urgent';
                    } elseif (floatval($balance) < 5) {
                        $statusText = '余额不足';
                        $statusClass = 'tag-urgent';
                    } elseif (floatval($balance) < 20) {
                        $statusText = '余额偏低';
                        $statusClass = 'tag-warning';
                    }
                    
                    $glanceData['power_info'] = [
                        'balance' => $balanceDisplay,
                        'roomName' => $roomName,
                        'statusText' => $statusText,
                        'statusClass' => $statusClass
                    ];
                }
            } catch (Exception $e) {
                // 电费查询失败
            }
        } else {
            $glanceData['no_dormitory'] = true;
        }
        
        jsonResponse(0, 'success', $glanceData);
    break;
    
    default:
        jsonResponse(-3, '无效的接口');
}
