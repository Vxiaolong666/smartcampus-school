<?php
$notLogin = true;
include('../Common/Core_brain.php');
/** @var \lib\PdoHelper $DB */

require_once '../funsion/OAuthHelper.php';

$type = isset($_GET['type']) ? daddslashes($_GET['type']) : '';
$code = isset($_GET['code']) ? daddslashes($_GET['code']) : '';
$state = isset($_GET['state']) ? daddslashes($_GET['state']) : 'login';

// 统一跳转到登录页并提示
function oauthFail($msg)
{
    header('Location:./login.php?oauth_error=' . urlencode($msg));
    exit;
}

if (empty($type) || empty($code)) {
    oauthFail('参数缺失');
}

if (!isset(OAuthHelper::CHANNELS[$type])) {
    oauthFail('不支持的登录方式');
}

$cfg = OAuthHelper::getConfig($DB);
if (!$cfg || intval($cfg['enable']) !== 1) {
    oauthFail('聚合登录未开启');
}

$result = OAuthHelper::callback($DB, $type, $code);
if (!$result || $result['code'] != 0) {
    oauthFail($result['msg'] ?? '授权失败');
}

$socialUid = daddslashes($result['social_uid']);
$nickname = isset($result['nickname']) ? daddslashes(mb_substr($result['nickname'], 0, 50)) : '';
$faceimg = isset($result['faceimg']) ? daddslashes($result['faceimg']) : '';
$gender = isset($result['gender']) ? daddslashes($result['gender']) : '';
$accessToken = isset($result['access_token']) ? daddslashes($result['access_token']) : '';

// 查找已有绑定
$bind = $DB->getRow("SELECT * FROM nteam_user_oauth WHERE oauth_type='$type' AND oauth_uid='$socialUid'");

if ($bind) {
    $user = $DB->getRow("SELECT * FROM nteam_users WHERE id='{$bind['user_id']}'");
    if (!$user) {
        // 绑定记录存在但关联用户已不存在，清理脏数据后提示
        $DB->exec("DELETE FROM nteam_user_oauth WHERE id='{$bind['id']}'");
        oauthFail('该第三方账号未关联有效用户，请先登录账号后再绑定');
    }
    if (intval($user['is_banned']) === 1) {
        oauthFail('该账号已被封禁，请联系管理员');
    }
    // 更新最新资料并登录
    $DB->exec("UPDATE nteam_user_oauth SET access_token='$accessToken', nickname='$nickname', avatar='$faceimg', gender='$gender', update_time=NOW() WHERE id='{$bind['id']}'");
    loginUser($DB, $user);
    header('Location:./');
    exit;
}

// 未找到绑定：仅允许已登录用户进行“绑定到当前账号”操作
// 不允许为未绑定的第三方账号自动注册新用户，仅支持已绑定账号登录
if ($state === 'bind' && isset($_SESSION['userId'])) {
    $userId = intval($_SESSION['userId']);
    $chk = $DB->getRow("SELECT * FROM nteam_user_oauth WHERE oauth_type='$type' AND oauth_uid='$socialUid'");
    if (!$chk) {
        $DB->exec("INSERT INTO nteam_user_oauth (user_id, oauth_type, oauth_uid, access_token, nickname, avatar, gender, create_time, update_time) VALUES ('$userId', '$type', '$socialUid', '$accessToken', '$nickname', '$faceimg', '$gender', NOW(), NOW())");
    }
    header('Location:./oauth_bind.php?oauth_bind=1');
    exit;
}

// 未绑定且非绑定流程：仅支持已绑定账号登录，不允许自动注册新用户
oauthFail('该第三方账号尚未绑定，请先使用账号密码登录后在「个人资料」中绑定');

/**
 * 设置登录态
 */
function loginUser($DB, $user)
{
    global $Gets;
    $ip = $Gets->ip();
    $DB->exec("UPDATE nteam_users SET last_login_time=NOW(), last_login_ip='$ip' WHERE id='{$user['id']}'");
    $_SESSION['userId'] = $user['id'];
    $_SESSION['userName'] = $user['username'];
    $_SESSION['userEmail'] = $user['email'];
    $_SESSION['userQq'] = $user['qq'];
}
