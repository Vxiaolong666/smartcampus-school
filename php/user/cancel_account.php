<?php
$mod = 'user';
include('../Common/Core_brain.php');
require_once '../funsion/AccountCancellation.php';

if (!isset($_SESSION['userId'])) {
    header('Location:./login.php');
    exit;
}

$userId = intval($_SESSION['userId']);
$user = $DB->getRow("SELECT * FROM nteam_users WHERE id='$userId'");
if (!$user) {
    unset($_SESSION['userId'], $_SESSION['userName'], $_SESSION['userEmail'], $_SESSION['userQq']);
    header('Location:./login.php');
    exit;
}

$cfg = cancelGetConfig();
// 《用户注销协议》未发布（后台已停用）：不展示协议、也无需勾选确认
$agreementPublished = intval($cfg['agreement_published']) === 1 && trim((string) $cfg['agreement']) !== '';
$needPwd   = strpos($cfg['verify_mode'], 'password') !== false;
$needEmail = strpos($cfg['verify_mode'], 'email') !== false;

// 统计将删除的数据（与 App 端共用 cancelGetUserDataSummary，保证口径一致）
$summary     = cancelGetUserDataSummary($DB, $userId);
$dormCount   = $summary['dorm_count'];
$academicRow = $summary['academic_bound'] ? ['student_id' => $summary['academic_student_id']] : null;
$oauthCount  = $summary['oauth_count'];
$subCount    = $summary['sub_count'];
$pushCount   = $summary['push_count'];

$title = '注销账户';
include('./head.php');
?>
<style>
    .cancel-steps {
        display: flex;
        align-items: flex-start;
        margin: 0 0 1.75rem;
        padding: 0;
    }
    .cancel-step {
        position: relative;
        flex: 1 1 0;
        min-width: 0;
        text-align: center;
    }
    .cancel-step::after {
        content: '';
        position: absolute;
        top: 15px;
        left: 50%;
        width: 100%;
        height: 2px;
        background: #e5e7eb;
        z-index: 0;
        transition: background-color .25s ease;
    }
    .cancel-step.line-done::after { background: #4c78dd; }
    .cancel-step:last-child::after { display: none; }
    .cancel-step-dot {
        position: relative;
        z-index: 1;
        width: 32px;
        height: 32px;
        line-height: 28px;
        margin: 0 auto;
        border-radius: 50%;
        border: 2px solid #dee2e6;
        background: #fff;
        color: #adb5bd;
        font-size: 13px;
        font-weight: 600;
        text-align: center;
        transition: all .2s ease;
    }
    .cancel-step-dot .chk { display: none; }
    .cancel-step.active .cancel-step-dot {
        border-color: #4c78dd;
        color: #4c78dd;
        box-shadow: 0 0 0 4px rgba(76, 120, 221, .15);
    }
    .cancel-step.done .cancel-step-dot {
        background: #4c78dd;
        border-color: #4c78dd;
        color: #fff;
    }
    .cancel-step.done .cancel-step-dot .num { display: none; }
    .cancel-step.done .cancel-step-dot .chk { display: inline-block; }
    .cancel-step-label {
        margin-top: 6px;
        font-size: 12px;
        line-height: 1.3;
        color: #6c757d;
    }
    .cancel-step.active .cancel-step-label { color: #4c78dd; font-weight: 600; }
    .cancel-step.done .cancel-step-label { color: #495057; }

    .confirm-phrase {
        display: block;
        padding: .625rem .875rem;
        background: #f8f9fa;
        border: 1px dashed #dc3545;
        border-radius: .375rem;
        color: #dc3545;
        font-weight: 600;
        line-height: 1.5;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
    }

    @media (max-width: 575.98px) {
        .cancel-step::after { top: 13px; }
        .cancel-step-dot { width: 28px; height: 28px; line-height: 24px; font-size: 12px; }
        .cancel-step-label { font-size: 11px; }
        .cancel-steps { margin-bottom: 1.25rem; }
    }
</style>
<div id="pjax-container">
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
                <div class="flex-grow-1">
                    <h1 class="h3 fw-bold mb-2"><?php echo $title ?></h1>
                </div>
                <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">
                            <a class="link-fx" href="index.php">用户中心</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page"><?php echo $title ?></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="content animated fadeIn">
        <?php if ($cfg['enable'] !== 1): ?>
            <div class="block block-rounded">
                <div class="block-content">
                    <div class="text-center text-muted py-4">
                        <i class="fa fa-user-slash fa-2x mb-2 d-block opacity-50"></i>
                        账号注销功能维护中，如有相关问题，请联系客服处理
                    </div>
                </div>
            </div>
        <?php else: ?>
        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-user-slash me-2 text-danger"></i>注销账户</h3>
            </div>
            <div class="block-content">

                <!-- 步骤指示器 -->
                <div class="cancel-steps" id="cancelSteps">
                    <div class="cancel-step">
                        <div class="cancel-step-dot"><span class="num">1</span><i class="fa fa-check chk"></i></div>
                        <div class="cancel-step-label"><?php echo $agreementPublished ? '阅读协议' : '协议确认'; ?></div>
                    </div>
                    <div class="cancel-step">
                        <div class="cancel-step-dot"><span class="num">2</span><i class="fa fa-check chk"></i></div>
                        <div class="cancel-step-label">风险告知</div>
                    </div>
                    <div class="cancel-step">
                        <div class="cancel-step-dot"><span class="num">3</span><i class="fa fa-check chk"></i></div>
                        <div class="cancel-step-label">身份核验</div>
                    </div>
                    <div class="cancel-step">
                        <div class="cancel-step-dot"><span class="num">4</span><i class="fa fa-check chk"></i></div>
                        <div class="cancel-step-label">确认提交</div>
                    </div>
                </div>

                <!-- 步骤1：阅读并同意协议（协议未发布时不展示，也无需勾选） -->
                <div class="cancel-panel" id="cancelPanel1">
                    <?php if ($agreementPublished): ?>
                    <div class="border rounded p-3 mb-3 markdown-body" id="agreementContent"
                         style="max-height:300px;overflow-y:auto;background:#fafafa;font-size:14px;line-height:1.7;">
                        <div class="text-muted">加载中…</div>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="agreeAgreement">
                        <label class="form-check-label fw-semibold" for="agreeAgreement">
                            我已完整阅读并同意《用户注销服务协议》
                        </label>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning mb-0">
                        《用户注销服务协议》当前未发布，无需阅读确认，可直接进入下一步。
                    </div>
                    <?php endif; ?>
                </div>

                <!-- 步骤2：风险告知 -->
                <div class="cancel-panel" id="cancelPanel2" style="display:none;">
                    <h5 class="mb-3">注销风险告知</h5>
                    <div class="alert alert-danger">
                        <?php if ($cfg['cooling_enable'] === 1): ?>
                            提交后进入 <strong><?php echo intval($cfg['cooling_days']); ?> 天</strong>冷静期，到期后账号及以下数据将被 <strong>永久删除且无法恢复</strong>；冷静期内登录账号即可撤销。
                        <?php else: ?>
                            提交后账号及以下数据将被 <strong>立即永久删除且无法恢复</strong>。
                        <?php endif; ?>
                    </div>
                    <p class="mb-2 fw-semibold">注销后将删除以下数据：</p>
                    <ul class="mb-3">
                        <li>账号基本资料（用户名、邮箱、昵称、头像、积分、等级、微信绑定等）</li>
                        <li>宿舍绑定：<strong><?php echo $dormCount; ?></strong> 个</li>
                        <li>教务账号绑定：<strong><?php echo $academicRow ? htmlspecialchars($academicRow['student_id']) : '未绑定'; ?></strong></li>
                        <li>第三方登录绑定：<strong><?php echo $oauthCount; ?></strong> 个</li>
                        <li>微信订阅记录：<strong><?php echo $subCount; ?></strong> 条；推送设置：<strong><?php echo $pushCount; ?></strong> 条</li>
                        <li>成绩/课程/电费等各类推送记录与日志</li>
                    </ul>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="agreeRisk">
                        <label class="form-check-label fw-semibold" for="agreeRisk">
                            我已阅读并知晓上述后果，确认自愿注销
                        </label>
                    </div>
                </div>

                <!-- 步骤3：身份核验 -->
                <div class="cancel-panel" id="cancelPanel3" style="display:none;">
                    <h5 class="mb-3">身份核验</h5>

                    <?php if ($needPwd): ?>
                    <div class="mb-3">
                        <label class="form-label">登录密码 <span class="text-danger">*</span></label>
                        <input type="password" class="form-control form-control-lg" id="password" placeholder="请输入当前登录密码" autocomplete="off">
                    </div>
                    <?php endif; ?>

                    <?php if ($needEmail): ?>
                    <div class="mb-3">
                        <label class="form-label">邮箱验证码 <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-lg" id="emailCode" placeholder="请输入邮箱验证码" maxlength="6" autocomplete="off">
                            <button type="button" class="btn btn-outline-primary" id="sendCodeBtn" style="white-space:nowrap;">发送验证码</button>
                        </div>
                        <div class="form-text">验证码将发送至注册邮箱 <?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">安全验证 <span class="text-danger">*</span></label>
                        <button type="button" class="btn btn-alt-primary w-100" id="TencentCaptcha" data-appid="2047155482" data-cbfn="onTCaptchaResult">
                            <i class="fas fa-shield-alt"></i> 进行滑块验证
                        </button>
                        <div id="tcPassedTip" style="display:none; color:#059669; margin-top:8px;">
                            <small><i class="fas fa-check-circle"></i> 安全验证已通过</small>
                        </div>
                        <input type="hidden" id="tc_ticket">
                        <input type="hidden" id="tc_randstr">

                        <div class="captcha-wrapper" id="textCaptcha" style="display:none; margin-top:10px; align-items:center; gap:12px;">
                            <div class="input-group" style="flex:1;">
                                <span class="input-group-text"><i class="fas fa-shield-alt"></i></span>
                                <input type="text" id="captcha" class="form-control form-control-alt" placeholder="验证码">
                            </div>
                            <img src="../server-submit/captcha.php" id="captchaImg" alt="验证码" onclick="refreshCaptcha()" style="cursor:pointer;">
                        </div>
                        <div id="showTextCaptchaLink" style="text-align:right; margin-top:8px; display:none;">
                            <a href="javascript:void(0)" onclick="showTextCaptcha()">滑块异常？改用图片验证码</a>
                        </div>
                    </div>
                </div>

                <!-- 步骤4：最终确认 -->
                <div class="cancel-panel" id="cancelPanel4" style="display:none;">
                    <h5 class="mb-3">最终确认</h5>
                    <div class="alert alert-warning">
                        <?php if ($cfg['cooling_enable'] === 1): ?>
                            确认提交后，账号将进入 <strong><?php echo intval($cfg['cooling_days']); ?> 天</strong>冷静期；冷静期内登录即可撤销。
                        <?php else: ?>
                            确认提交后，账号及全部个人数据将被 <strong>立即永久删除</strong>。
                        <?php endif; ?>
                    </div>
                    <label class="form-label">请手动输入以下文字：<span class="text-danger">*</span></label>
                    <div class="confirm-phrase" id="confirmPhrase"
                         oncopy="return false" oncut="return false" ondragstart="return false"
                         onselectstart="return false" oncontextmenu="return false">我已确认注销后用户数据无法找回并确认注销</div>
                    <input type="text" class="form-control form-control-lg mt-2" id="confirmText"
                           placeholder="请手动输入上方文字" autocomplete="off"
                           onpaste="return false" oncopy="return false" oncut="return false"
                           ondrop="return false" ondragover="return false">
                    <div class="form-text">为防止误操作，请手动输入上方文字。</div>
                </div>

                <!-- 导航按钮 -->
                <div class="d-flex align-items-center justify-content-between mt-4 mb-3">
                    <div>
                        <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='index.php'">取消</button>
                        <button type="button" class="btn btn-secondary ms-1" id="prevBtn" style="display:none;">上一步</button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary" id="nextBtn">下一步</button>
                        <button type="button" class="btn btn-danger" id="submitBtn" style="display:none;">
                            <i class="fa fa-user-slash me-1"></i> 确认注销
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dompurify@3/dist/purify.min.js"></script>
<?php include './foot.php'?>
<script>
var needPwd = <?php echo $needPwd ? 'true' : 'false'; ?>;
var needEmail = <?php echo $needEmail ? 'true' : 'false'; ?>;
var coolingEnabled = <?php echo $cfg['cooling_enable'] === 1 ? 'true' : 'false'; ?>;
var CONFIRM_PHRASE = '我已确认注销后用户数据无法找回并确认注销';
var AGREEMENT_MD = <?php echo json_encode($cfg['agreement'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
// 协议是否已发布：未发布时不展示协议、也无需勾选确认
var AGREEMENT_REQUIRED = <?php echo $agreementPublished ? 'true' : 'false'; ?>;
var currentStep = 1;
var TOTAL_STEPS = 4;
var sendTimer = null;
var identityPassed = false; // 第三步是否已通过服务端身份核验
var verifying = false;      // 第三步核验请求进行中

// 渲染《用户注销服务协议》（Markdown → HTML，并做安全过滤）
function renderAgreement() {
    var raw = AGREEMENT_MD || '';
    var html;
    try {
        if (window.marked) {
            if (typeof marked.setOptions === 'function') {
                marked.setOptions({ breaks: true, gfm: true });
            }
            html = (typeof marked.parse === 'function') ? marked.parse(raw) : marked(raw);
        } else {
            html = $('<div>').text(raw).html().replace(/\n/g, '<br>');
        }
    } catch (e) {
        html = $('<div>').text(raw).html().replace(/\n/g, '<br>');
    }
    if (window.DOMPurify) {
        try { html = DOMPurify.sanitize(html); } catch (e) {}
    }
    $('#agreementContent').html(html);
}

function renderSteps(scrollToTop) {
    $('#cancelSteps .cancel-step').each(function (i) {
        var step = i + 1;
        var $el = $(this);
        $el.removeClass('done active line-done');
        if (step < currentStep) { $el.addClass('done'); }
        if (step === currentStep) { $el.addClass('active'); }
        // 连接线：下一步已完成时高亮
        if ((step + 1) < currentStep) { $el.addClass('line-done'); }
    });

    $('.cancel-panel').hide();
    $('#cancelPanel' + currentStep).show();

    $('#prevBtn').toggle(currentStep > 1);
    $('#nextBtn').toggle(currentStep < TOTAL_STEPS);
    $('#submitBtn').toggle(currentStep === TOTAL_STEPS);

    updateNextState();
    updateSubmitState();

    if (scrollToTop) {
        var top = $('#cancelSteps').offset().top - 90;
        $('html, body').animate({ scrollTop: top > 0 ? top : 0 }, 180);
    }
}

function goNext() {
    if (currentStep === 1 && AGREEMENT_REQUIRED && !$('#agreeAgreement').is(':checked')) {
        layer.alert('请先阅读并同意《用户注销服务协议》', {icon: 2});
        return;
    }
    if (currentStep === 2 && !$('#agreeRisk').is(':checked')) {
        layer.alert('请先勾选"我已阅读并知晓上述后果"', {icon: 2});
        return;
    }
    if (currentStep === 3) {
        // 已核验通过则直接进入下一步，否则先由服务端核验身份
        if (identityPassed) {
            currentStep++;
            renderSteps(true);
            return;
        }
        if (needPwd && $.trim($('#password').val()) === '') {
            layer.alert('请输入登录密码', {icon: 2});
            return;
        }
        if (needEmail && $.trim($('#emailCode').val()) === '') {
            layer.alert('请输入邮箱验证码', {icon: 2});
            return;
        }
        if (!isCaptchaDone()) {
            layer.alert('请先完成安全验证', {icon: 2});
            return;
        }
        verifyIdentity();
        return;
    }
    if (currentStep < TOTAL_STEPS) {
        currentStep++;
        renderSteps(true);
    }
}

// 第三步「下一步」：先核验身份，通过后才进入第四步
function verifyIdentity() {
    if (verifying) { return; }
    verifying = true;
    $('#nextBtn').prop('disabled', true);
    var ii = layer.load(2, {shade: [0.1, '#fff']});
    $.ajax({
        type: 'POST',
        url: 'ajax.php?act=verify_cancel_identity',
        data: {
            password: $('#password').val() || '',
            email_code: $.trim($('#emailCode').val() || ''),
            tc_ticket: $('#tc_ticket').val() || '',
            tc_randstr: $('#tc_randstr').val() || '',
            captcha: $.trim($('#captcha').val() || '')
        },
        dataType: 'json',
        success: function (res) {
            layer.close(ii);
            verifying = false;
            if (res.code == 0) {
                identityPassed = true;
                currentStep++;
                renderSteps(true);
            } else {
                // 图形验证码为一次性，失败后需刷新
                if ($.trim($('#captcha').val() || '') !== '') {
                    refreshCaptcha();
                    $('#captcha').val('');
                }
                layer.alert(res.msg || '身份核验失败', {icon: 2});
                updateNextState();
            }
        },
        error: function () {
            layer.close(ii);
            verifying = false;
            layer.alert('网络错误，请稍后重试', {icon: 2});
            updateNextState();
        }
    });
}

function goPrev() {
    if (currentStep > 1) {
        currentStep--;
        renderSteps(true);
    }
}

// 人机验证是否已完成（滑块票据或图形验证码任一）
function isCaptchaDone() {
    var tcTicket = $('#tc_ticket').val() || '';
    var captcha = $.trim($('#captcha').val() || '');
    return tcTicket !== '' || captcha !== '';
}

// 根据当前步骤的必填项，控制「下一步」是否可点击
function updateNextState() {
    var ok = true;
    if (currentStep === 1) {
        ok = !AGREEMENT_REQUIRED || $('#agreeAgreement').is(':checked');
    } else if (currentStep === 2) {
        ok = $('#agreeRisk').is(':checked');
    } else if (currentStep === 3) {
        if (needPwd && $.trim($('#password').val()) === '') { ok = false; }
        if (needEmail && $.trim($('#emailCode').val()) === '') { ok = false; }
        if (!isCaptchaDone()) { ok = false; }
    }
    $('#nextBtn').prop('disabled', !ok);
}

// 控制「提交注销申请」是否可点击（身份已核验通过且确认短语正确）
function updateSubmitState() {
    var ok = true;
    if (AGREEMENT_REQUIRED && !$('#agreeAgreement').is(':checked')) { ok = false; }
    if (!$('#agreeRisk').is(':checked')) { ok = false; }
    if (!identityPassed) { ok = false; }
    if ($.trim($('#confirmText').val()) !== CONFIRM_PHRASE) { ok = false; }
    $('#submitBtn').prop('disabled', !ok);
}

$(function () {
    renderAgreement();
    renderSteps(false);
    $('#nextBtn').on('click', goNext);
    $('#prevBtn').on('click', goPrev);
    $('#agreeAgreement, #agreeRisk').on('change', function () {
        updateNextState();
        updateSubmitState();
    });
    $('#password, #emailCode, #captcha').on('input change', function () {
        updateNextState();
        updateSubmitState();
    });
    $('#confirmText').on('input change', updateSubmitState);

    // 禁止复制上方确认短语、禁止输入框粘贴
    $('#confirmPhrase').on('copy cut dragstart selectstart contextmenu', function (e) {
        e.preventDefault();
        return false;
    });
    $('#confirmText').on('paste copy cut drop dragover', function (e) {
        e.preventDefault();
        return false;
    });
});

window.onTCaptchaResult = function (res) {
    if (res && res.ret === 0) {
        $('#tc_ticket').val(res.ticket || '');
        $('#tc_randstr').val(res.randstr || '');
        const btn = $('#TencentCaptcha');
        btn.html('<i class="fas fa-check-circle"></i> 验证已通过').css({
            'background': '#ecfdf5', 'color': '#059669', 'border': '1px solid #34d399', 'cursor': 'default'
        });
        $('#tcPassedTip').show();
        $('#showTextCaptchaLink').hide();
        updateNextState();
        updateSubmitState();
    } else {
        $('#showTextCaptchaLink').show();
    }
};

function showTextCaptcha() {
    $('#TencentCaptcha').hide();
    $('#showTextCaptchaLink').hide();
    $('#tcPassedTip').hide();
    $('#textCaptcha').css('display', 'flex');
    $('#tc_ticket').val('');
    $('#tc_randstr').val('');
    refreshCaptcha();
    updateNextState();
    updateSubmitState();
}

function refreshCaptcha() {
    $('#captchaImg').attr('src', '../server-submit/captcha.php?' + Date.now());
}

$('#sendCodeBtn').on('click', function () {
    var btn = $(this);
    if (btn.prop('disabled')) return;
    btn.prop('disabled', true).text('发送中...');
    $.ajax({
        type: 'POST',
        url: 'ajax.php?act=send_cancel_code',
        dataType: 'json',
        success: function (res) {
            if (res.code == 0) {
                layer.msg(res.msg, {icon: 1});
                var left = 60;
                btn.text(left + ' 秒后重发');
                sendTimer = setInterval(function () {
                    left--;
                    if (left <= 0) {
                        clearInterval(sendTimer);
                        btn.prop('disabled', false).text('发送验证码');
                    } else {
                        btn.text(left + ' 秒后重发');
                    }
                }, 1000);
            } else {
                btn.prop('disabled', false).text('发送验证码');
                layer.alert(res.msg, {icon: 2});
            }
        },
        error: function () {
            btn.prop('disabled', false).text('发送验证码');
            layer.alert('网络错误，请稍后重试', {icon: 2});
        }
    });
});

$('#submitBtn').on('click', function () {
    if (AGREEMENT_REQUIRED && !$('#agreeAgreement').is(':checked')) {
        layer.alert('请先阅读并同意《用户注销服务协议》', {icon: 2});
        return false;
    }
    if (!$('#agreeRisk').is(':checked')) {
        layer.alert('请先勾选"我已阅读并知晓上述后果"', {icon: 2});
        return false;
    }
    if (!identityPassed) {
        layer.alert('请先完成身份核验', {icon: 2});
        return false;
    }
    if ($.trim($('#confirmText').val()) !== CONFIRM_PHRASE) {
        layer.alert('确认短语不正确，请手动输入上方短语', {icon: 2});
        return false;
    }

    var warn = coolingEnabled
        ? '确定要提交注销申请吗？提交后将进入冷静期，到期账号将被永久删除。'
        : '确定要注销账号吗？账号及全部个人数据将被立即永久删除，且无法恢复！';

    layer.confirm(warn, {btn: ['确定', '取消'], icon: 3}, function () {
        var btn = $('#submitBtn');
        btn.prop('disabled', true);
        layer.msg('正在处理...', {icon: 16, time: 0});
        $.ajax({
            type: 'POST',
            url: 'ajax.php?act=cancel_account',
            data: {
                agree: 1,
                confirm_text: $.trim($('#confirmText').val())
            },
            dataType: 'json',
            success: function (res) {
                layer.closeAll();
                if (res.code == 0) {
                    layer.alert(res.msg, {icon: 1, closeBtn: false}, function () {
                        window.location.href = 'login.php';
                    });
                } else {
                    btn.prop('disabled', false);
                    if (res.code == -3) {
                        // 核验凭据已失效：退回第三步重新核验
                        identityPassed = false;
                        currentStep = 3;
                        renderSteps(true);
                    }
                    layer.alert(res.msg, {icon: 2});
                }
            },
            error: function () {
                layer.closeAll();
                btn.prop('disabled', false);
                layer.alert('网络错误，请稍后重试', {icon: 2});
            }
        });
    });
});
</script>
</body>
</html>
