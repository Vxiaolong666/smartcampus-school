<?php
$title = '推送设置';
$requireLogin = true;
include('./head.php');
require_once('../funsion/feature_settings.php');

$userId = $_SESSION['userId'];

if (!isFeatureEnabled($DB, 'push_notification')) {
    echo '<div id="pjax-container"><div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-content block-content-full">';
    checkFeatureAccess($DB, 'push_notification');
    echo '</div></div></div>';
    include('./foot.php');
    exit;
}

$dormitories = DormitorySystem::getUserDormitories($DB, $userId);
$pushSettings = $DB->getAll("SELECT * FROM nteam_push_settings WHERE user_id = '$userId'");
$powerConfig = $DB->getRow("SELECT * FROM nteam_power_config WHERE id = 1");

$settingsMap = [];
if ($pushSettings) {
    foreach ($pushSettings as $setting) {
        $settingsMap[$setting['dormitory_binding_id']] = $setting;
    }
}
?>

<div id="pjax-container">
    <div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-bell me-2"></i>电费推送设置
                </h3>
            </div>
            <div class="block-content block-content-full">
                <?php renderFeatureAnnouncement($DB, 'push_notification'); ?>
                
                <?php if (count($dormitories) > 0): ?>
                <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
                    <i class="fa fa-info-circle me-2"></i>
                    <div>开启推送后，系统会根据您的设置自动推送电费相关提醒消息</div>
                </div>
                
                <?php foreach ($dormitories as $dorm): 
                    $setting = isset($settingsMap[$dorm['id']]) ? $settingsMap[$dorm['id']] : null;
                    $notifyTypes = $setting ? explode(',', $setting['notify_types']) : ['daily_summary', 'balance_low', 'balance_critical', 'arrears'];
                    $pushTypes = $setting ? $setting['push_types'] : 'wechat';
                    $isEnabled = $setting ? $setting['is_enabled'] : 0;
                    $balanceLowThreshold = $setting ? $setting['balance_low_threshold'] : ($powerConfig['low_balance_threshold'] ?? 30);
                    $balanceCriticalThreshold = $setting ? $setting['balance_critical_threshold'] : ($powerConfig['critical_balance_threshold'] ?? 10);
                ?>
                <div class="block block-rounded block-bordered mb-4 push-setting-card" data-dorm-id="<?php echo $dorm['id']; ?>">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">
                            <i class="fa fa-home me-2"></i>
                            <?php echo htmlspecialchars(($dorm['alias'] ?: $dorm['building'] . ' ' . $dorm['room'])); ?>
                            <?php if ($dorm['is_default'] == 1): ?><span class="badge bg-primary ms-2">默认</span><?php endif; ?>
                        </h3>
                        <div class="block-options">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="enablePush_<?php echo $dorm['id']; ?>" 
                                       <?php echo $isEnabled ? 'checked' : ''; ?> 
                                       onchange="togglePush(<?php echo $dorm['id']; ?>)">
                                <label class="form-check-label" for="enablePush_<?php echo $dorm['id']; ?>">
                                    <?php echo $isEnabled ? '已开启' : '已关闭'; ?>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="block-content block-content-full push-setting-content" style="<?php echo $isEnabled ? '' : 'display:none;'; ?>">
                        <form id="pushForm_<?php echo $dorm['id']; ?>" onsubmit="return savePushSettings(<?php echo $dorm['id']; ?>)">
                            <input type="hidden" name="dormitory_binding_id" value="<?php echo $dorm['id']; ?>">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">推送方式</label>
                                    <div class="form-check">
                                        <input class="form-check-input push-type-check" type="checkbox" name="push_types_wechat" value="wechat" 
                                               id="pushWechat_<?php echo $dorm['id']; ?>"
                                               <?php echo (strpos($pushTypes, 'wechat') !== false) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="pushWechat_<?php echo $dorm['id']; ?>">
                                            <i class="fab fa-weixin text-success me-1"></i>微信订阅消息
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input push-type-check" type="checkbox" name="push_types_email" value="email" 
                                               id="pushEmail_<?php echo $dorm['id']; ?>"
                                               <?php echo (strpos($pushTypes, 'email') !== false) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="pushEmail_<?php echo $dorm['id']; ?>">
                                            <i class="fa fa-envelope text-primary me-1"></i>邮件推送
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">推送消息类型</label>
                                    <div class="form-check">
                                        <input class="form-check-input notify-type-check" type="checkbox" name="notify_types[]" value="daily_summary" 
                                               id="notifyDaily_<?php echo $dorm['id']; ?>"
                                               <?php echo in_array('daily_summary', $notifyTypes) ? 'checked' : ''; ?>
                                               <?php echo ($powerConfig && !$powerConfig['daily_summary_enabled']) ? 'disabled' : ''; ?>>
                                        <label class="form-check-label" for="notifyDaily_<?php echo $dorm['id']; ?>">
                                            每日用电汇总
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input notify-type-check" type="checkbox" name="notify_types[]" value="balance_low" 
                                               id="notifyLow_<?php echo $dorm['id']; ?>"
                                               <?php echo in_array('balance_low', $notifyTypes) ? 'checked' : ''; ?>
                                               <?php echo ($powerConfig && !$powerConfig['low_balance_enabled']) ? 'disabled' : ''; ?>>
                                        <label class="form-check-label" for="notifyLow_<?php echo $dorm['id']; ?>">
                                            余额不足提醒
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input notify-type-check" type="checkbox" name="notify_types[]" value="balance_critical" 
                                               id="notifyCritical_<?php echo $dorm['id']; ?>"
                                               <?php echo in_array('balance_critical', $notifyTypes) ? 'checked' : ''; ?>
                                               <?php echo ($powerConfig && !$powerConfig['critical_balance_enabled']) ? 'disabled' : ''; ?>>
                                        <label class="form-check-label" for="notifyCritical_<?php echo $dorm['id']; ?>">
                                            余额即将耗尽
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input notify-type-check" type="checkbox" name="notify_types[]" value="arrears" 
                                               id="notifyArrears_<?php echo $dorm['id']; ?>"
                                               <?php echo in_array('arrears', $notifyTypes) ? 'checked' : ''; ?>
                                               <?php echo ($powerConfig && !$powerConfig['arrears_enabled']) ? 'disabled' : ''; ?>>
                                        <label class="form-check-label" for="notifyArrears_<?php echo $dorm['id']; ?>">
                                            欠费提醒
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">余额不足阈值（元）</label>
                                    <input type="number" class="form-control" name="balance_low_threshold" 
                                           value="<?php echo $balanceLowThreshold; ?>" step="0.01" min="0">
                                    <small class="text-muted">低于此金额时发送余额不足提醒</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">余额即将耗尽阈值（元）</label>
                                    <input type="number" class="form-control" name="balance_critical_threshold" 
                                           value="<?php echo $balanceCriticalThreshold; ?>" step="0.01" min="0">
                                    <small class="text-muted">低于此金额时发送紧急提醒</small>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-alt-primary">
                                    <i class="fa fa-save me-1"></i>保存设置
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php else: ?>
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <i class="fa fa-exclamation-triangle me-2"></i>
                    <div>
                        <strong>未绑定宿舍</strong> - 请先<a href="dormitory_bind.php">绑定宿舍</a>后再设置推送
                    </div>
                </div>
                <a href="dormitory_bind.php" class="btn btn-alt-primary">
                    <i class="fa fa-home me-1"></i> 前往绑定宿舍
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function togglePush(dormId) {
    var checkbox = document.getElementById('enablePush_' + dormId);
    var content = document.querySelector('.push-setting-card[data-dorm-id="' + dormId + '"] .push-setting-content');
    var label = checkbox.nextElementSibling;
    
    if (checkbox.checked) {
        content.style.display = '';
        label.textContent = '已开启';
    } else {
        content.style.display = 'none';
        label.textContent = '已关闭';
        savePushSettings(dormId, true);
    }
}

function savePushSettings(dormId, disableOnly = false) {
    var form = document.getElementById('pushForm_' + dormId);
    var formData = new FormData(form);
    
    var pushTypes = [];
    if (formData.get('push_types_wechat')) pushTypes.push('wechat');
    if (formData.get('push_types_email')) pushTypes.push('email');
    
    var notifyTypes = [];
    var notifyCheckboxes = form.querySelectorAll('input[name="notify_types[]"]:checked');
    notifyCheckboxes.forEach(function(cb) {
        notifyTypes.push(cb.value);
    });
    
    var data = {
        dormitory_binding_id: formData.get('dormitory_binding_id'),
        push_types: pushTypes.join(','),
        notify_types: notifyTypes.join(','),
        balance_low_threshold: formData.get('balance_low_threshold'),
        balance_critical_threshold: formData.get('balance_critical_threshold'),
        is_enabled: document.getElementById('enablePush_' + dormId).checked ? 1 : 0
    };
    
    if (disableOnly) {
        data.is_enabled = 0;
    }
    
    $.post('ajax.php?act=save_push_settings', data, function(res) {
        if (res.code == 0) {
            layer.msg('保存成功', {icon: 1});
        } else if (res.code == -2) {
            layer.msg('请先登录', {icon: 2}, function() {
                window.location.href = 'login.php';
            });
        } else {
            layer.msg(res.msg || '保存失败', {icon: 2});
        }
    }, 'json');
    
    return false;
}
</script>

<?php include('./foot.php'); ?>
