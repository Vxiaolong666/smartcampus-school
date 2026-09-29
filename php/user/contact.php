<?php
/**
 * 联系我们 / 意见反馈（网页用户端）
 * ------------------------------------------------------------------
 * 与 uniapp 端 components/contact-modal/contact-modal.vue 对应的联系方式展示，
 * 另在网页端补齐（官网 contact-us.php 那套表单的能力）：
 *   1) 登录用户可直接提交反馈 / 留言，自动关联账号（source=user），免人机验证；
 *   2) 提交后可在本页「我的反馈」查看历史记录与处理状态
 *      （待处理 / 处理中 / 已解决 / 已驳回 / 已忽略）及管理员处理备注；
 *   3) 页面底部的联系方式来自后台「官网配置」，顺序与配色和 App / 小程序端一致。
 */
$title = '联系我们';
include('./head.php');
require_once('../Common/home_config.php');
require_once('../funsion/ContactMsgs.php');

if(!function_exists('contactLabelClass')){
    /**
     * 标签配色（与 uniapp contact-modal.vue 的 labelClass 规则保持一致）
     */
    function contactLabelClass($label){
        $l = (string)$label;
        if(strpos($l, '地址') !== false) return 'is-address';
        if(strpos($l, '电话') !== false || strpos($l, '手机') !== false) return 'is-phone';
        if(strpos($l, '邮箱') !== false || strpos($l, '邮件') !== false || stripos($l, 'email') !== false) return 'is-mail';
        if(stripos($l, 'qq') !== false) return 'is-qq';
        if(strpos($l, '微信') !== false || stripos($l, 'wechat') !== false) return 'is-wechat';
        if(strpos($l, '时间') !== false) return 'is-time';
        if(strpos($l, '说明') !== false || strpos($l, '备注') !== false) return 'is-desc';
        return 'is-default';
    }
}

// ===== 当前登录用户（用户端反馈需要账号） =====
$fbUserId = isset($_SESSION['userId']) ? intval($_SESSION['userId']) : 0;
$fbUser = null;
if($fbUserId > 0){
    $fbUser = $DB->getRow("SELECT id,username,nickname,email FROM nteam_users WHERE id='$fbUserId'");
    if(!$fbUser){
        $fbUserId = 0;
    }
}
$fbName  = $fbUser ? (!empty($fbUser['nickname']) ? $fbUser['nickname'] : $fbUser['username']) : '';
$fbEmail = $fbUser && !empty($fbUser['email']) ? $fbUser['email'] : '';

// 留言功能开关与「提交前人机验证」开关（后台「反馈配置」页面维护）
$fbFormOpen    = intval(homeCfg('contact_form_open', 1)) === 1;
$fbNeedCaptcha = intval(homeCfg('contact_form_captcha', 1)) === 1;

// ===== 我的反馈记录（表结构未升级时安全降级为空） =====
$fbSchemaReady = contactMsgSchemaReady($DB);
$fbPage = isset($_GET['fbpage']) ? max(1, intval($_GET['fbpage'])) : 1;
$fbPageSize = 10;
$fbOffset = ($fbPage - 1) * $fbPageSize;
$fbList = array();
$fbTotal = 0;
if($fbUserId > 0 && $fbSchemaReady){
    $fbTotal = intval($DB->getColumn("SELECT COUNT(*) FROM nteam_contact_msgs WHERE `user_id`='$fbUserId'"));
    $fbList = $DB->getAll("SELECT `id`,`subject`,`content`,`handle_status`,`remark`,`create_time`,`update_time`
        FROM nteam_contact_msgs WHERE `user_id`='$fbUserId' ORDER BY `id` DESC LIMIT $fbOffset, $fbPageSize");
    if(!is_array($fbList)){
        $fbList = array();
    }
}
$fbTotalPages = max(1, (int)ceil($fbTotal / $fbPageSize));

// ===== 联系方式（后台「官网配置」，非空项才展示） =====
$contactFields = array(
    '联系电话' => homeCfg('contact_phone'),
    '电子邮箱' => homeCfg('contact_email'),
    'QQ'       => homeCfg('contact_qq'),
    '工作时间' => homeCfg('contact_worktime'),
    '联系地址' => homeCfg('contact_address'),
    '说明'     => homeCfg('contact_desc'),
);
$contactItems = array();
foreach($contactFields as $label => $value){
    $value = trim((string)$value);
    if($value !== ''){
        $contactItems[] = array('label' => $label, 'value' => $value);
    }
}
?>

<div id="pjax-container">
    <div class="content animated fadeIn">

        <!-- ============ 提交反馈 / 留言 ============ -->
        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-comment-dots me-2"></i>提交反馈
                </h3>
            </div>
            <div class="block-content block-content-full">
                <?php if($fbUserId <= 0): ?>
                <div class="text-center py-4">
                    <i class="fa fa-user-circle fa-3x text-muted mb-3"></i>
                    <h4 class="h5">登录后即可提交反馈</h4>
                    <p class="text-muted mb-3">登录后可提交反馈 / 留言，并随时查看处理进度与历史记录。</p>
                    <a class="btn btn-alt-primary btn-sm" href="login.php">
                        <i class="fa fa-sign-in-alt me-1"></i>去登录
                    </a>
                </div>
                <?php elseif(!$fbFormOpen): ?>
                <div class="alert alert-warning mb-0">
                    <i class="fa fa-exclamation-circle me-1"></i>留言功能暂未开放，请通过页面下方的联系方式与我们联系。
                </div>
                <?php else: ?>
                <p class="text-muted fs-sm mb-4">
                    使用中遇到问题或有建议，都可以在这里反馈。提交后会按「待处理 → 处理中 → 已解决 / 已驳回 / 已忽略」流转，处理结果可在下方「我的反馈」中查看。
                </p>
                <form id="feedbackForm" onsubmit="return false;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">姓名 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" maxlength="64"
                                   value="<?php echo htmlspecialchars($fbName, ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">手机号</label>
                            <input type="text" class="form-control" name="phone" maxlength="32" placeholder="选填，便于我们联系你">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">邮箱</label>
                            <input type="email" class="form-control" name="email" maxlength="128"
                                   value="<?php echo htmlspecialchars($fbEmail, ENT_QUOTES, 'UTF-8'); ?>" placeholder="选填">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">主题</label>
                            <input type="text" class="form-control" name="subject" maxlength="100" placeholder="如：电费查询异常">
                        </div>
                        <div class="col-12">
                            <label class="form-label">反馈内容 <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="content" rows="5" maxlength="2000"
                                      placeholder="请描述你遇到的问题或建议（最多 2000 字）" required></textarea>
                        </div>
                    </div>
                    <?php if($fbNeedCaptcha): ?>
                    <!-- 人机验证（后台「反馈配置」启用时展示）：腾讯滑块，异常时可改用图片验证码 -->
                    <div class="mt-4" id="fbCaptchaBox">
                        <label class="form-label">人机验证 <span class="text-danger">*</span></label>
                        <button type="button" class="btn btn-alt-primary w-100" id="TencentCaptcha" data-appid="2047155482" data-cbfn="onTCaptchaResult">
                            <i class="fa fa-shield-alt me-1"></i>进行滑块验证
                        </button>
                        <div id="tcPassedTip" style="display:none; color:#059669; margin-top:8px;">
                            <small><i class="fa fa-check-circle me-1"></i>安全验证已通过</small>
                        </div>
                        <input type="hidden" name="tc_ticket" id="tc_ticket">
                        <input type="hidden" name="tc_randstr" id="tc_randstr">
                        <div class="captcha-wrapper" id="textCaptcha">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa fa-shield-alt"></i></span>
                                <input type="text" name="captcha" id="captcha" class="form-control" maxlength="4"
                                       placeholder="请输入验证码" autocomplete="off">
                            </div>
                            <img src="../server-submit/captcha.php" id="captchaImg" alt="验证码"
                                 title="点击刷新验证码" onclick="refreshCaptcha()">
                        </div>
                        <div id="showTextCaptchaLink" style="text-align:right; margin-top:8px; display:none;">
                            <a href="javascript:void(0)" onclick="showTextCaptcha()">滑块异常？改用图片验证码</a>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex align-items-center justify-content-between mt-4">
                        <small class="text-muted">提交后可在「我的反馈」查看处理状态与处理备注。</small>
                        <button type="submit" class="btn btn-alt-primary" id="feedbackSubmit">
                            <i class="fa fa-paper-plane me-1"></i>提交反馈
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- ============ 我的反馈记录 ============ -->
        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-history me-2"></i>我的反馈
                </h3>
                <?php if($fbUserId > 0 && $fbSchemaReady): ?>
                <div class="block-options">
                    <span class="badge bg-primary">共 <?php echo $fbTotal; ?> 条</span>
                </div>
                <?php endif; ?>
            </div>
            <div class="block-content block-content-full">
                <?php if($fbUserId <= 0): ?>
                <div class="text-center py-4 text-muted">
                    <i class="fa fa-lock fa-2x mb-2"></i>
                    <div>登录后可查看你的历史反馈记录</div>
                </div>
                <?php elseif(!$fbSchemaReady): ?>
                <div class="alert alert-warning mb-0">
                    <i class="fa fa-exclamation-triangle me-1"></i>反馈功能尚未完成初始化，请联系管理员导入 <code>contact_msgs_update.sql</code>。
                </div>
                <?php elseif(empty($fbList)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="fa fa-inbox fa-2x mb-2"></i>
                    <div>还没有提交过反馈</div>
                </div>
                <?php else: ?>
                <div class="fb-list">
                    <?php foreach($fbList as $fb):
                        $fbStatus = intval($fb['handle_status']);
                    ?>
                    <div class="fb-item">
                        <div class="fb-item-head">
                            <span class="fb-item-title">
                                #<?php echo intval($fb['id']); ?>
                                <?php echo htmlspecialchars($fb['subject'] !== '' ? $fb['subject'] : '（无主题）', ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                            <span class="badge <?php echo contactMsgStatusClass($fbStatus); ?>"><?php echo contactMsgStatusLabel($fbStatus); ?></span>
                        </div>
                        <div class="fb-item-body"><?php echo nl2br(htmlspecialchars((string)$fb['content'], ENT_QUOTES, 'UTF-8')); ?></div>
                        <?php if(!empty($fb['remark'])): ?>
                        <div class="fb-item-remark">
                            <i class="fa fa-comment-alt me-1"></i>处理备注：<?php echo nl2br(htmlspecialchars((string)$fb['remark'], ENT_QUOTES, 'UTF-8')); ?>
                        </div>
                        <?php endif; ?>
                        <div class="fb-item-foot">
                            <span><i class="fa fa-clock me-1"></i>提交于 <?php echo htmlspecialchars((string)$fb['create_time'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php if(!empty($fb['update_time'])): ?>
                            <span><i class="fa fa-check-circle me-1"></i>更新于 <?php echo htmlspecialchars((string)$fb['update_time'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if($fbTotalPages > 1): ?>
                <div class="d-flex justify-content-center align-items-center mt-3 gap-2">
                    <?php if($fbPage > 1): ?>
                    <a class="btn btn-sm btn-alt-secondary" href="?fbpage=<?php echo $fbPage - 1; ?>">
                        <i class="fa fa-angle-left me-1"></i>上一页
                    </a>
                    <?php endif; ?>
                    <span class="fs-sm text-muted">第 <?php echo $fbPage; ?> / <?php echo $fbTotalPages; ?> 页</span>
                    <?php if($fbPage < $fbTotalPages): ?>
                    <a class="btn btn-sm btn-alt-secondary" href="?fbpage=<?php echo $fbPage + 1; ?>">
                        下一页<i class="fa fa-angle-right ms-1"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ============ 其他联系方式（后台「官网配置」） ============ -->
        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-headset me-2"></i>联系我们
                </h3>
            </div>
            <div class="block-content block-content-full">
                <?php if(empty($contactItems)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="fa fa-address-book fa-3x mb-3"></i>
                    <h4 class="h5">暂未配置联系方式</h4>
                    <p class="mb-0">管理员尚未在后台「官网配置」中填写联系方式，你可以先通过本页表单提交反馈。</p>
                </div>
                <?php else: ?>
                <div class="row justify-content-center">
                    <div class="col-lg-9">
                        <div class="contact-list">
                            <?php foreach($contactItems as $item): ?>
                            <div class="contact-row">
                                <span class="contact-label <?php echo contactLabelClass($item['label']); ?>"><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="contact-value"><?php echo nl2br(htmlspecialchars($item['value'], ENT_QUOTES, 'UTF-8')); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="alert alert-light mt-4 mb-0 fs-sm">
                            <i class="fa fa-shield-alt me-1"></i>温馨提示：请通过以上官方渠道联系我们，谨防冒充客服的诈骗行为。
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* ===== 联系方式列表：标签胶囊 + 内容（配色与 uniapp 联系客服弹窗一致） ===== */
.contact-row {
    display: flex;
    align-items: flex-start;
    padding: 14px 0;
    border-bottom: 1px solid #f2f3f7;
}
.contact-row:last-child { border-bottom: none; }
.contact-label {
    flex-shrink: 0;
    min-width: 76px;
    max-width: 116px;
    margin-right: 12px;
    margin-top: 2px;
    padding: 3px 10px;
    font-size: 12px;
    text-align: center;
    border-radius: 6px;
    background: rgba(24, 144, 255, .12);
    color: #1890ff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.contact-label.is-phone { background: rgba(24, 144, 255, .12); color: #1890ff; }
.contact-label.is-mail { background: rgba(114, 46, 209, .12); color: #722ed1; }
.contact-label.is-qq { background: rgba(19, 194, 194, .12); color: #13c2c2; }
.contact-label.is-wechat { background: rgba(82, 196, 26, .12); color: #52c41a; }
.contact-label.is-time { background: rgba(250, 140, 22, .12); color: #fa8c16; }
.contact-label.is-address { background: rgba(235, 47, 150, .12); color: #eb2f96; }
.contact-label.is-desc { background: rgba(140, 140, 140, .12); color: #8c8c8c; }
.contact-value {
    flex: 1 1 auto;
    min-width: 0;
    font-size: 14px;
    color: #333;
    line-height: 1.7;
    word-break: break-all;
}

/* ===== 我的反馈记录 ===== */
.fb-item {
    padding: 14px 16px;
    border: 1px solid #eef0f5;
    border-radius: 10px;
    margin-bottom: 12px;
    background: #fcfdff;
}
.fb-item:last-child { margin-bottom: 0; }
.fb-item-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 8px;
}
.fb-item-title {
    font-weight: 600;
    color: #334155;
    word-break: break-all;
}
.fb-item-body {
    font-size: 14px;
    line-height: 1.8;
    color: #475569;
    word-break: break-word;
    white-space: normal;
}
.fb-item-remark {
    margin-top: 10px;
    padding: 8px 12px;
    font-size: 13px;
    line-height: 1.7;
    color: #0f7490;
    background: rgba(19, 194, 194, .08);
    border-left: 3px solid #13c2c2;
    border-radius: 0 6px 6px 0;
    word-break: break-word;
}
.fb-item-foot {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    margin-top: 10px;
    font-size: 12px;
    color: #94a3b8;
}

/* ===== 人机验证：滑块 / 图片验证码（与登录页写法一致） ===== */
.captcha-wrapper {
    display: none;
    align-items: center;
    gap: 12px;
    margin-top: 10px;
}
.captcha-wrapper .input-group { flex: 1 1 auto; min-width: 0; }
.captcha-wrapper img {
    flex: 0 0 auto;
    height: 38px;
    width: auto;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
    cursor: pointer;
}
@media (max-width: 768px) {
    .fb-item-head { flex-wrap: wrap; }
}
</style>

<!-- 腾讯滑块验证 SDK（与登录页一致；仅在开启「提交前人机验证」时加载） -->
<?php if($fbNeedCaptcha): ?>
<script src="https://ssl.captcha.qq.com/TCaptcha.js"></script>
<?php endif; ?>
<script>
// ===== 人机验证：滑块 / 图片验证码（写法与登录页一致） =====
var FB_NEED_CAPTCHA = <?php echo $fbNeedCaptcha ? 'true' : 'false'; ?>;

function refreshCaptcha() {
    var img = document.getElementById('captchaImg');
    if (img) img.src = '../server-submit/captcha.php?' + Date.now();
}

// 滑块异常时改用图片验证码
function showTextCaptcha() {
    document.getElementById('TencentCaptcha').style.display = 'none';
    document.getElementById('showTextCaptchaLink').style.display = 'none';
    document.getElementById('tcPassedTip').style.display = 'none';
    document.getElementById('textCaptcha').style.display = 'flex';
    document.getElementById('tc_ticket').value = '';
    document.getElementById('tc_randstr').value = '';
    refreshCaptcha();
}

window.onTCaptchaResult = function (res) {
    if (res && res.ret === 0) {
        document.getElementById('tc_ticket').value = res.ticket || '';
        document.getElementById('tc_randstr').value = res.randstr || '';
        var btn = document.getElementById('TencentCaptcha');
        btn.innerHTML = '<i class="fa fa-check-circle me-1"></i>验证已通过';
        btn.style.background = '#ecfdf5';
        btn.style.color = '#059669';
        btn.style.border = '1px solid #34d399';
        btn.style.cursor = 'default';
        document.getElementById('tcPassedTip').style.display = 'block';
        document.getElementById('showTextCaptchaLink').style.display = 'none';
    } else {
        document.getElementById('showTextCaptchaLink').style.display = 'block';
    }
};

$(function () {
    var $form = $('#feedbackForm');
    if (!$form.length) return;

    $form.on('submit', function () {
        var name = $.trim($form.find('[name=name]').val() || '');
        var content = $.trim($form.find('[name=content]').val() || '');
        if (!name) { layer.msg('请填写姓名', {icon: 0}); return false; }
        if (!content) { layer.msg('请填写反馈内容', {icon: 0}); return false; }

        // 人机验证（后台「反馈配置」启用时必填）：滑块票据与图片验证码二者其一
        if (FB_NEED_CAPTCHA) {
            var tcTicket  = $.trim($form.find('[name=tc_ticket]').val() || '');
            var tcRandstr = $.trim($form.find('[name=tc_randstr]').val() || '');
            var captcha   = $.trim($form.find('[name=captcha]').val() || '');
            if (!((tcTicket && tcRandstr) || captcha)) {
                layer.msg('请先完成滑块 / 验证码验证', {icon: 0});
                return false;
            }
        }

        var $btn = $('#feedbackSubmit').prop('disabled', true);
        $.ajax({
            type: 'POST',
            url: '../server-submit/contact_submit.php',
            data: $form.serialize(),
            dataType: 'json',
            success: function (res) {
                if (res && res.code == 0) {
                    layer.msg(res.msg || '提交成功', {icon: 1}, function () {
                        window.location.href = 'contact.php';
                    });
                } else {
                    // 失败时刷新图片验证码，滑块票据仍保留（后端只在人机验证不通过时才提示重新验证）
                    if (FB_NEED_CAPTCHA) refreshCaptcha();
                    layer.msg((res && res.msg) ? res.msg : '提交失败，请稍后重试', {icon: 2});
                }
            },
            error: function () {
                layer.msg('网络异常，请稍后重试', {icon: 2});
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
        return false;
    });
});
</script>

<?php include 'foot.php'; ?>
