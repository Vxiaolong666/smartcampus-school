<?php
/**
 * 协议中心（网页用户端）
 * ------------------------------------------------------------------
 * 与 uniapp 端 pages/agreement/index.vue 对应：
 *   1) 清单取后台「协议管理」（nteam_agreements，仅启用项，按 sort_order 排序）；
 *   2) 每项跳转到通用详情页 agreement_detail.php?key=xxx（后台新增协议无需改页面）；
 *   3) 后台未发布任何协议 / 协议表未初始化时展示「暂无协议」空态。
 */
$title = '协议中心';
include('./head.php');
require_once('../funsion/Agreements.php');

// 协议清单（仅启用项）与协议表状态
$agList = agreementList($DB, true);
$agTableReady = agreementTableReady($DB);

// 内置协议说明（与 uniapp utils/agreements.js 的 LIST 保持一致）
$agDescMap = array(
    'service'    => '使用本服务前请先阅读并同意本协议',
    'privacy'    => '了解我们如何收集、使用与保护您的信息',
    'cancel'     => '账号注销的影响、流程与注意事项',
    'disclaimer' => '关于信息来源、AI 内容与责任限制的说明',
);
?>

<div id="pjax-container">
    <div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-file-contract me-2"></i>协议中心
                </h3>
            </div>
            <div class="block-content block-content-full">
                <p class="text-muted fs-sm mb-4">
                    这里汇总了本服务的全部协议与声明。请在使用相关服务前仔细阅读并充分理解相关内容，点击即可查看完整条款。
                </p>

                <?php if(empty($agList)): ?>
                <div class="text-center py-5">
                    <i class="fa fa-file-alt fa-4x text-muted mb-3"></i>
                    <h4>暂无协议</h4>
                    <p class="text-muted mb-0">
                        <?php echo $agTableReady
                            ? '当前没有已发布的协议，如有疑问请联系管理员。'
                            : '协议数据尚未初始化，请联系管理员执行更新脚本。'; ?>
                    </p>
                </div>
                <?php else: ?>
                <div class="row g-3">
                    <?php foreach($agList as $ag):
                        $akey = $ag['akey'];
                        // 内置说明优先，后台新增的协议显示「最近更新：YYYY-MM-DD」
                        $desc = isset($agDescMap[$akey]) ? $agDescMap[$akey] : '';
                        if($desc === '' && !empty($ag['updated_at'])){
                            $desc = '最近更新：' . date('Y-m-d', strtotime($ag['updated_at']));
                        }
                    ?>
                    <div class="col-md-6">
                        <?php /* 卡片用 Bootstrap card（与公告列表一致的观感）：
                                主题里 .block .block 会被去掉阴影，卡片嵌在面板 .block 内会「看不到卡片」 */ ?>
                        <a class="card h-100 agreement-card text-decoration-none"
                           href="agreement_detail.php?key=<?php echo urlencode($akey); ?>">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 me-3">
                                        <i class="fa fa-file-alt fa-2x text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold text-dark"><?php echo htmlspecialchars($ag['title']); ?></div>
                                        <?php if($desc !== ''): ?>
                                        <div class="fs-sm text-muted mt-1"><?php echo htmlspecialchars($desc); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <i class="fa fa-angle-right text-muted ms-2"></i>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="alert alert-info mt-4 mb-3 fs-sm">
                    <i class="fa fa-info-circle me-1"></i>继续使用本服务，即表示您已阅读并同意上述协议与声明。
                </div>

                <a class="btn btn-alt-primary btn-sm" href="contact.php">
                    <i class="fa fa-headset me-1"></i>联系客服
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* ===== 协议卡片：默认白底描边，hover 用主题青色描边 + 轻投影提示可点击 ===== */
.agreement-card {
    transition: border-color .15s ease-out, box-shadow .15s ease-out;
}
.agreement-card:hover {
    border-color: rgba(19, 194, 194, .55);
    box-shadow: 0 6px 16px rgba(19, 194, 194, .12);
}
</style>

<?php include 'foot.php'; ?>
