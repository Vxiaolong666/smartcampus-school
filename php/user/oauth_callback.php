<?php
$notLogin = true;
include('../Common/Core_brain.php');
/** @var \lib\PdoHelper $DB */

require_once '../funsion/OAuthHelper.php';
require_once '../funsion/AccountCancellation.php';

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

// 查找已有的第三方绑定（oauth_type + oauth_uid 唯一）
$bind = $DB->getRow("SELECT * FROM nteam_user_oauth WHERE oauth_type='$type' AND oauth_uid='$socialUid'");

if ($state === 'bind') {
    // ===================== 绑定流程（已登录用户主动绑定） =====================
    // 绑定流程任何时候都不触发登录 / 不切换当前登录账号
    if (!isset($_SESSION['userId'])) {
        header('Location:./login.php?oauth_error=' . urlencode('登录状态已失效，请重新登录后再进行绑定'));
        exit;
    }
    $currentUserId = intval($_SESSION['userId']);
    $chName = OAuthHelper::CHANNELS[$type]['name'] ?? $type;

    if ($bind) {
        // 绑定记录所关联的用户
        $bindUser = $DB->getRow("SELECT * FROM nteam_users WHERE id='{$bind['user_id']}'");

        if ($bindUser && intval($bindUser['id']) === $currentUserId) {
            // 已绑定到当前账号：刷新资料后直接返回成功（不做任何登录操作）
            $DB->exec("UPDATE nteam_user_oauth SET access_token='$accessToken', nickname='$nickname', avatar='$faceimg', gender='$gender', update_time=NOW() WHERE id='{$bind['id']}'");
            header('Location:./oauth_bind.php?oauth_bind=1');
            exit;
        }

        if ($bindUser) {
            // 该第三方账号已被其它有效账号绑定：仅提示错误，禁止绑定且绝不触发登录
            header('Location:./oauth_bind.php?oauth_error=' . urlencode('该' . $chName . '账号已被其它账号绑定，请先解绑后再试'));
            exit;
        }

        // 绑定记录关联的账号已不存在（脏数据）：允许覆盖该特征信息，改绑到当前账号
        $DB->exec("UPDATE nteam_user_oauth SET user_id='$currentUserId', access_token='$accessToken', nickname='$nickname', avatar='$faceimg', gender='$gender', update_time=NOW() WHERE id='{$bind['id']}'");
        header('Location:./oauth_bind.php?oauth_bind=1');
        exit;
    }

    // 该第三方账号尚未绑定：直接绑定到当前账号
    $DB->exec("INSERT INTO nteam_user_oauth (user_id, oauth_type, oauth_uid, access_token, nickname, avatar, gender, create_time, update_time) VALUES ('$currentUserId', '$type', '$socialUid', '$accessToken', '$nickname', '$faceimg', '$gender', NOW(), NOW())");
    header('Location:./oauth_bind.php?oauth_bind=1');
    exit;
}

// ===================== 登录流程 =====================
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
    // 冷静期处理：冷静期内登录即自动撤销注销；已到期则不撤销
    $cancelState = cancelHandleLogin($DB, $user, $Gets->ip());
    if ($cancelState === 'expired') {
        oauthFail('该账号已进入注销流程，无法登录');
    }
    // 更新最新资料并登录
    $DB->exec("UPDATE nteam_user_oauth SET access_token='$accessToken', nickname='$nickname', avatar='$faceimg', gender='$gender', update_time=NOW() WHERE id='{$bind['id']}'");
    loginUser($DB, $user);
    $_SESSION['cancelRevoked'] = ($cancelState === 'revoked') ? 1 : 0;
    header('Location:./');
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
