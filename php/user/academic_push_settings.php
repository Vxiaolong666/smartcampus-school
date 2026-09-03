<?php
$title = '学术推送设置';
$requireLogin = true;
include('./head.php');

$userId = $_SESSION['userId'];
$academicAccount = $DB->getRow("SELECT * FROM nteam_academic_accounts WHERE user_id='$userId'");

if (!$academicAccount) {
    echo '<div id="pjax-container">
    <div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-bell me-2"></i>学术推送设置
                </h3>
            </div>
            <div class="block-content block-content-full">
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <i class="fa fa-exclamation-triangle me-2"></i>
                    <div>
                        <strong>未绑定教务账号</strong> - 请先绑定教务账号后再设置学术推送。
                    </div>
                </div>
                <a href="academic_bind.php" class="btn btn-alt-primary">
                    <i class="fa fa-link me-1"></i>立即绑定
                </a>
            </div>
        </div>
    </div>
</div>';
    include './foot.php';
    exit;
}

$pushSettings = $DB->getRow("SELECT * FROM nteam_academic_push_settings WHERE user_id='$userId' AND academic_account_id='{$academicAccount['id']}'");

$pushTypes = $pushSettings ? $pushSettings['push_types'] : 'wechat';
$scheduleReminderEnabled = $pushSettings ? $pushSettings['schedule_reminder_enabled'] : 1;
$scheduleReminderTime = $pushSettings ? $pushSettings['schedule_reminder_time'] : '20:00:00';
$gradeNotifyEnabled = $pushSettings ? $pushSettings['grade_notify_enabled'] : 1;
$isEnabled = $pushSettings ? $pushSettings['is_enabled'] : 0;

$globalConfig = $DB->getRow("SELECT * FROM nteam_academic_push_config WHERE id = 1");
if (!$globalConfig) {
    $globalConfig = [
        'schedule_reminder_enabled' => 1,
        'grade_notify_enabled' => 1,
        'wechat_push_enabled' => 1,
        'email_push_enabled' => 1
    ];
}
?>

<div id="pjax-container">
    <div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-bell me-2"></i>学术推送设置
                </h3>
            </div>
            <div class="block-content block-content-full">
                <?php if ($isEnabled): ?>
                <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                    <i class="fa fa-check-circle me-2"></i>
                    <div>
                        <strong>推送已开启</strong> - 当前账号：<?php echo htmlspecialchars($academicAccount['student_id']); ?>
                        <?php if ($academicAccount['real_name']): ?>
                        <span class="text-muted ms-2">(<?php echo htmlspecialchars($academicAccount['real_name']); ?>)</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
                    <i class="fa fa-info-circle me-2"></i>
                    <div>
                        <strong>推送未开启</strong> - 当前账号：<?php echo htmlspecialchars($academicAccount['student_id']); ?>
                        <?php if ($academicAccount['real_name']): ?>
                        <span class="text-muted ms-2">(<?php echo htmlspecialchars($academicAccount['real_name']); ?>)</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <form id="pushForm" class="mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">推送状态 <span class="text-danger">*</span></label>
                                <select class="form-select" name="is_enabled" id="is_enabled">
                                    <option value="1" <?php echo $isEnabled ? 'selected' : ''; ?>>开启推送</option>
                                    <option value="0" <?php echo !$isEnabled ? 'selected' : ''; ?>>关闭推送</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">推送方式 <span class="text-danger">*</span></label>
                                <?php if ($globalConfig['wechat_push_enabled'] == 1): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="push_types_wechat" value="wechat" id="pushWechat"
                                           <?php echo (strpos($pushTypes, 'wechat') !== false) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="pushWechat">
                                        <i class="fab fa-weixin text-success me-1"></i>微信订阅消息
                                    </label>
                                </div>
                                <?php endif; ?>
                                <?php if ($globalConfig['email_push_enabled'] == 1): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="push_types_email" value="email" id="pushEmail"
                                           <?php echo (strpos($pushTypes, 'email') !== false) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="pushEmail">
                                        <i class="fa fa-envelope text-primary me-1"></i>邮件推送
                                    </label>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">明日课程提醒</label>
                                <div class="input-group">
                                    <select class="form-select" name="schedule_reminder_enabled" style="max-width: 120px;">
                                        <option value="1" <?php echo $scheduleReminderEnabled ? 'selected' : ''; ?>>开启</option>
                                        <option value="0" <?php echo !$scheduleReminderEnabled ? 'selected' : ''; ?>>关闭</option>
                                    </select>
                                    <input type="time" class="form-control" name="schedule_reminder_time" 
                                           value="<?php echo substr($scheduleReminderTime, 0, 5); ?>">
                                    <span class="input-group-text">提醒</span>
                                </div>
                                <div class="form-text">每天定时推送明天的课程安排</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">成绩发布通知</label>
                                <select class="form-select" name="grade_notify_enabled">
                                    <option value="1" <?php echo $gradeNotifyEnabled ? 'selected' : ''; ?>>开启</option>
                                    <option value="0" <?php echo !$gradeNotifyEnabled ? 'selected' : ''; ?>>关闭</option>
                                </select>
                                <div class="form-text">当有新成绩发布时自动推送通知</div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-alt-primary">
                                <i class="fa fa-save me-1"></i> 保存设置
                            </button>
                        </div>
                    </div>
                </form>
                
                <div class="alert alert-warning mt-4" role="alert">
                    <h5 class="alert-heading"><i class="fa fa-exclamation-triangle me-2"></i>注意事项</h5>
                    <hr>
                    <ul class="mb-0">
                        <li>微信订阅消息需要在小程序中授权后才能接收</li>
                        <li>邮件推送将发送到您绑定的邮箱</li>
                        <li>课程提醒将在每天设定时间自动推送</li>
                        <li>成绩通知将在检测到新成绩时自动推送</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$('#pushForm').on('submit', function(e) {
    e.preventDefault();
    
    var pushTypes = [];
    if ($('input[name="push_types_wechat"]').is(':checked')) pushTypes.push('wechat');
    if ($('input[name="push_types_email"]').is(':checked')) pushTypes.push('email');
    
    var data = {
        push_types: pushTypes.join(','),
        schedule_reminder_enabled: $('select[name="schedule_reminder_enabled"]').val(),
        schedule_reminder_time: $('input[name="schedule_reminder_time"]').val(),
        grade_notify_enabled: $('select[name="grade_notify_enabled"]').val(),
        is_enabled: $('#is_enabled').val()
    };
    
    layer.confirm('确定要保存推送设置吗？', {
        btn: ['确定', '取消'],
        icon: 3
    }, function() {
        $.ajax({
            url: 'ajax.php?act=save_academic_push_settings',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(res) {
                if(res.code == 0) {
                    layer.msg(res.msg || '保存成功', {icon: 1, time: 1500}, function() {
                        location.reload();
                    });
                } else {
                    layer.msg(res.msg || '保存失败', {icon: 2});
                }
            },
            error: function() {
                layer.msg('请求失败', {icon: 2});
            }
        });
    });
});
</script>

<?php include 'foot.php'; ?>
