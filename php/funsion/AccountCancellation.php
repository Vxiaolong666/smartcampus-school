<?php
/**
 * 账号注销（用户端自助注销 + 冷静期）公共逻辑
 *
 * 供 user/ajax.php、cron_account_cancel.php、Adminq/cancel_config.php 等复用。
 * 约定：正式注销时不删除 nteam_academic_accounts（内部规则，见功能文档 §8.5）。
 */

/**
 * 可选的注销身份验证方式
 */
function cancelVerifyModes()
{
    return [
        'password'       => '密码 + 人机验证',
        'email'          => '邮件验证码 + 人机验证',
        'password_email' => '密码 + 人机验证 + 邮件验证码',
    ];
}

/**
 * 安全读取注销相关配置：列不存在时返回默认值（避免 PHP8 undefined key 告警）
 */
function cancelConf($key, $default = null)
{
    global $DB;
    static $cols = null;
    if ($cols === null) {
        $cols = [];
        $rs = $DB->query("SHOW COLUMNS FROM nteam_config");
        if ($rs) {
            while ($r = $rs->fetch()) {
                $cols[] = $r['Field'];
            }
        }
    }
    if (!in_array($key, $cols, true)) {
        return $default;
    }
    $row = $DB->getRow("SELECT `$key` FROM nteam_config WHERE id=1");
    if (!$row) {
        return $default;
    }
    $v = $row[$key];
    if ($v === null || $v === '') {
        return $default;
    }
    return $v;
}

/**
 * 读取注销全部配置（含默认值与合法性校验）
 *
 * @return array{enable:int,verify_mode:string,cooling_enable:int,cooling_days:int,mail_submit:int,mail_deleted:int,mail_revoked:int,agreement:string}
 */
function cancelGetConfig()
{
    $modes = array_keys(cancelVerifyModes());

    $mode = cancelConf('Cancel_Verify_Mode', 'password');
    if (!in_array($mode, $modes, true)) {
        $mode = 'password';
    }

    $coolingDays = intval(cancelConf('Cancel_Cooling_Days', 10));
    if ($coolingDays < 1 || $coolingDays > 90) {
        $coolingDays = 10;
    }

    return [
        'enable'         => intval(cancelConf('Cancel_Enable', 0)) === 1 ? 1 : 0,
        'verify_mode'    => $mode,
        'cooling_enable' => intval(cancelConf('Cancel_Cooling_Enable', 1)) === 1 ? 1 : 0,
        'cooling_days'   => $coolingDays,
        'mail_submit'    => intval(cancelConf('Cancel_Mail_Submit', 1)) === 1 ? 1 : 0,
        'mail_deleted'   => intval(cancelConf('Cancel_Mail_Deleted', 1)) === 1 ? 1 : 0,
        'mail_revoked'   => intval(cancelConf('Cancel_Mail_Revoked', 1)) === 1 ? 1 : 0,
        'agreement'      => (string) cancelConf('Cancel_Agreement', cancelDefaultAgreement()),
    ];
}

/**
 * 默认《用户注销服务协议》模板（Markdown 格式，可在后台编辑）
 */
function cancelDefaultAgreement()
{
    return "# 用户注销服务协议\n\n"
        . "**在提交账号注销申请前，请您务必完整阅读并充分理解本协议的全部内容。**\n\n"
        . "1. **账号注销后**，您将无法再使用该账号登录本平台，与该账号关联的各项服务将同时终止，且无法恢复。\n"
        . "2. **注销生效后**，平台将删除该账号下的个人资料及其关联数据，包括但不限于：个人资料、宿舍绑定、第三方账号绑定、订阅与推送设置、成绩/课程推送记录等。上述数据一经删除无法找回。\n"
        . "3. **身份核验**：为保障账号安全，注销流程需通过密码、邮箱验证码、人机验证等方式核验身份，具体核验方式以平台设置及页面提示为准。\n"
        . "4. **冷静期**：注销申请提交后进入冷静期（具体天数以平台设置为准）。冷静期内您可重新登录以撤销注销；冷静期届满仍未撤销的，账号将被正式注销。\n"
        . "5. **合规处理**：平台将依据相关法律法规及合规要求处理与账号注销有关的事宜。\n"
        . "6. **确认**：您确认已阅读并同意本协议，且系自愿申请注销本账号。\n";
}

/**
 * 发送注销相关邮件（统一入口，默认跳过于邮件频控）
 *
 * @return true|string 成功返回 true，失败返回错误信息
 */
function cancelSendMail($to, $subject, $html)
{
    if (!function_exists('send_mail')) {
        require_once dirname(__DIR__) . '/Common/Core_Functions.php';
    }
    return send_mail($to, $subject, $html, true);
}

/**
 * 处理"登录时"的冷静期状态
 *
 * @return string 'none' 非冷静期 | 'revoked' 已自动撤销 | 'expired' 已到期（不撤销）
 */
function cancelHandleLogin($DB, $user, $ip = '')
{
    if (empty($user['is_cancelling'])) {
        return 'none';
    }
    $expire = !empty($user['cancel_expire_time']) ? strtotime($user['cancel_expire_time']) : 0;
    if ($expire && $expire <= time()) {
        return 'expired';
    }
    cancelRevoke($DB, $user['id'], 'login', $user, $ip);
    return 'revoked';
}

/**
 * 撤销注销（清空冷静期字段并更新审计；按配置发送撤销通知邮件）
 *
 * @param array|null $userInfo 已知用户记录（含 email/username），为空时自动查询
 * @param string     $ip       触发撤销的登录 IP
 */
function cancelRevoke($DB, $userId, $reason = 'login', $userInfo = null, $ip = '')
{
    $userId = intval($userId);
    $DB->exec("UPDATE nteam_users SET is_cancelling=0, cancel_time=NULL, cancel_expire_time=NULL WHERE id='$userId'");
    $DB->exec("UPDATE nteam_account_deletions SET status='cancelled', revoke_time=NOW() WHERE user_id='$userId' AND status='pending'");

    // 撤销通知邮件（后台开关控制）
    $cfg = cancelGetConfig();
    if ($cfg['mail_revoked'] === 1) {
        if (empty($userInfo) || empty($userInfo['email'])) {
            $userInfo = $DB->getRow("SELECT id, username, email FROM nteam_users WHERE id='$userId'");
        }
        if ($userInfo) {
            cancelSendRevokeMail($userInfo, $ip);
        }
    }
    return true;
}

/**
 * 写入注销审计记录
 *
 * @return int 审计记录ID
 */
function cancelCreateAudit($DB, $user, $cfg, $ip, $status = 'pending', $expireTime = null)
{
    $uid      = intval($user['id']);
    $username = addslashes($user['username']);
    $email    = addslashes($user['email']);
    $ipEsc    = addslashes($ip);
    $mode     = addslashes($cfg['verify_mode']);
    $cooling  = intval($cfg['cooling_enable']);
    $days     = intval($cfg['cooling_days']);
    $statusEsc = addslashes($status);
    $expireSql = $expireTime ? "'" . addslashes($expireTime) . "'" : 'NULL';

    $DB->exec("INSERT INTO nteam_account_deletions
        (user_id, username, email, method, status, verify_mode, cooling_enabled, cooling_days,
         agreement_agreed, agreement_time, agreement_ip, academic_retained, request_ip, cancel_time, expire_time)
        VALUES ('$uid', '$username', '$email', 'self', '$statusEsc', '$mode', '$cooling', '$days',
         1, NOW(), '$ipEsc', 1, '$ipEsc', NOW(), $expireSql)");

    return intval($DB->lastInsertId());
}

/**
 * 发送"注销申请已提交（进入冷静期）"通知邮件
 */
function cancelSendSubmitMail($user, $expireTime, $days)
{
    if (empty($user['email'])) {
        return false;
    }
    $siteName = function_exists('conf') ? conf('Name') : '智慧校园';
    $dateStr  = substr($expireTime, 0, 10);
    $html = '<html><head><meta charset="UTF-8"></head><body style="font-family:Arial,sans-serif;line-height:1.6;color:#333;">'
        . '<div style="max-width:600px;margin:0 auto;padding:20px;border:1px solid #ddd;border-radius:5px;">'
        . '<h2 style="color:#dc3545;border-bottom:2px solid #dc3545;padding-bottom:10px;">账号注销申请已提交</h2>'
        . '<p>您好，<strong>' . htmlspecialchars($user['username']) . '</strong>：</p>'
        . '<p>您的账号注销申请已提交成功，将进入 <strong>' . intval($days) . ' 天</strong>冷静期。</p>'
        . '<p>账号预计于 <strong>' . $dateStr . '</strong> 正式注销，届时与该账号关联的账号及个人数据将被删除且无法恢复。</p>'
        . '<p>在冷静期内，您只需<strong>登录本账号</strong>即可自动撤销此次注销申请；如非本人操作，请尽快登录撤销并修改密码。</p>'
        . '<p style="color:#999;font-size:12px;">—— ' . htmlspecialchars($siteName) . '</p>'
        . '</div></body></html>';
    return cancelSendMail($user['email'], '账号注销申请已提交 - ' . $siteName, $html);
}

/**
 * 发送"账号已注销"通知邮件
 */
function cancelSendDeleteMail($user)
{
    if (empty($user['email'])) {
        return false;
    }
    $siteName = function_exists('conf') ? conf('Name') : '智慧校园';
    $html = '<html><head><meta charset="UTF-8"></head><body style="font-family:Arial,sans-serif;line-height:1.6;color:#333;">'
        . '<div style="max-width:600px;margin:0 auto;padding:20px;border:1px solid #ddd;border-radius:5px;">'
        . '<h2 style="color:#6c757d;border-bottom:2px solid #6c757d;padding-bottom:10px;">账号已注销</h2>'
        . '<p>您好，<strong>' . htmlspecialchars($user['username']) . '</strong>：</p>'
        . '<p>您的账号及全部个人数据已删除，与该账号关联的各项服务已终止。</p>'
        . '<p>感谢您的使用，期待与您再次相遇。</p>'
        . '<p style="color:#999;font-size:12px;">—— ' . htmlspecialchars($siteName) . '</p>'
        . '</div></body></html>';
    return cancelSendMail($user['email'], '账号已注销 - ' . $siteName, $html);
}

/**
 * 发送"注销申请已自动撤销（账号已恢复）"通知邮件
 *
 * @param string $ip   触发撤销的登录 IP
 * @param string $time 触发撤销的登录时间（为空取当前时间）
 */
function cancelSendRevokeMail($user, $ip = '', $time = '')
{
    if (empty($user['email'])) {
        return false;
    }
    $siteName = function_exists('conf') ? conf('Name') : '智慧校园';
    $ipText   = ($ip !== '' && $ip !== null) ? $ip : '未知';
    $timeText = ($time !== '' && $time !== null) ? $time : date('Y-m-d H:i:s');
    $html = '<html><head><meta charset="UTF-8"></head><body style="font-family:Arial,sans-serif;line-height:1.6;color:#333;">'
        . '<div style="max-width:600px;margin:0 auto;padding:20px;border:1px solid #ddd;border-radius:5px;">'
        . '<h2 style="color:#0d6efd;border-bottom:2px solid #0d6efd;padding-bottom:10px;">注销申请已自动撤销</h2>'
        . '<p>您好，<strong>' . htmlspecialchars($user['username']) . '</strong>：</p>'
        . '<p>您此前提交的账号注销申请，因您于 <strong>' . htmlspecialchars($timeText) . '</strong> 在 <strong>' . htmlspecialchars($ipText) . '</strong> 登录，已为您自动撤销注销申请，账号已恢复正常，可继续正常使用。</p>'
        . '<p>如非本人操作，请及时修改账号密码，以保障账号安全。</p>'
        . '<p style="color:#999;font-size:12px;">—— ' . htmlspecialchars($siteName) . '</p>'
        . '</div></body></html>';
    return cancelSendMail($user['email'], '注销申请已自动撤销 - ' . $siteName, $html);
}

/**
 * 正式注销：级联删除用户及其全部个人数据（保留 nteam_academic_accounts）
 *
 * @param \lib\PdoHelper $DB
 * @param int            $userId
 * @param array          $userInfo 用户记录（用于删除头像等），至少包含 avatar
 * @return array{success:bool,error:string}
 */
function deleteUserAccount($DB, $userId, $userInfo = [])
{
    $userId = intval($userId);
    if ($userId <= 0) {
        return ['success' => false, 'error' => '无效的用户ID'];
    }

    // 需硬删除的用户数据表（账号注销时清理）
    // 说明：以下与"宿舍/电费"相关的数据按需求予以保留，不做删除：
    //   - nteam_power_daily_records：用电历史（按 room_num 存储，其他用户可能查询该宿舍电费历史）
    //   - nteam_dormitory_bindings：宿舍记录（仅置为停用 status=0，保留记录）
    //   - nteam_push_settings：电费推送设置（仅停用，保留记录）
    //   - nteam_push_logs：电费推送记录（保留）
    // 另：nteam_academic_accounts 按内部规则保留，不在此列表中
    $userDataTables = [
        'nteam_user_oauth',
        'nteam_academic_push_settings',
        'nteam_academic_push_logs',
        'nteam_tokens',
        'nteam_subscriptions',
        'nteam_subscription_logs',
        'nteam_grade_push_records',
    ];

    $DB->db->beginTransaction();
    try {
        foreach ($userDataTables as $tbl) {
            if ($DB->exec("DELETE FROM {$tbl} WHERE user_id='$userId'") === false) {
                $DB->db->rollBack();
                return ['success' => false, 'error' => "删除 {$tbl} 失败：" . $DB->error()];
            }
        }
        // 宿舍绑定：软删除（保留宿舍记录，不影响该宿舍电费历史）
        if ($DB->exec("UPDATE nteam_dormitory_bindings SET status=0 WHERE user_id='$userId'") === false) {
            $DB->db->rollBack();
            return ['success' => false, 'error' => '停用宿舍绑定失败：' . $DB->error()];
        }
        // 电费推送设置：停用（保留记录，不再推送）
        if ($DB->exec("UPDATE nteam_push_settings SET is_enabled=0 WHERE user_id='$userId'") === false) {
            $DB->db->rollBack();
            return ['success' => false, 'error' => '停用电费推送设置失败：' . $DB->error()];
        }
        if ($DB->exec("DELETE FROM nteam_users WHERE id='$userId'") === false) {
            $DB->db->rollBack();
            return ['success' => false, 'error' => '删除用户失败：' . $DB->error()];
        }
        $DB->db->commit();
    } catch (Exception $e) {
        try {
            $DB->db->rollBack();
        } catch (Exception $e2) {
        }
        return ['success' => false, 'error' => $e->getMessage()];
    }

    // 删除头像文件（仅处理本站上传目录下的文件）
    if (!empty($userInfo['avatar'])) {
        $avatar = $userInfo['avatar'];
        $pos = strpos($avatar, '/uploads/');
        if ($pos !== false) {
            $rel  = substr($avatar, $pos + 1); // uploads/...
            $path = dirname(__DIR__) . '/' . $rel;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    return ['success' => true];
}
