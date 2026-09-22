<?php
$mod = 'admin';
$title='注销管理';
/** @var array|null $adminData */
include('../Common/Core_brain.php');
require_once '../funsion/AccountCancellation.php';
if($adminData['adminRank']== 2) {
    echo "您的账号没有权限使用此功能";
    exit;
}
include './head.php';

$cfg = cancelGetConfig();
$modes = cancelVerifyModes();
// 《用户注销协议》状态：0=未配置（沿用内置模板）1=已发布 2=已停用（用户端不展示、无需确认）
$agreementState = 0;
if (function_exists('agreementGet')) {
    $agRow = agreementGet($DB, 'cancel');
    if ($agRow) {
        $agreementState = (intval($agRow['status']) === 1 && trim((string) $agRow['content']) !== '') ? 1 : 2;
    }
}
$agreementConfigured = ($agreementState === 1);

$pendingCount = (int)$DB->getColumn("SELECT COUNT(*) FROM nteam_account_deletions WHERE status='pending'");
$totalCount   = (int)$DB->getColumn("SELECT COUNT(*) FROM nteam_account_deletions");

// 验证方式中文名
$modeName = isset($modes[$cfg['verify_mode']]) ? $modes[$cfg['verify_mode']] : $cfg['verify_mode'];

// 数据表是否存在（未执行更新脚本时友好提示）
$auditTableExists = (bool)$DB->getColumn("SHOW TABLES LIKE 'nteam_account_deletions'");

// ===== 注销记录（分页 + 状态筛选） =====
$recPageSize = 20;
$recStatus = isset($_GET['rstatus']) ? addslashes($_GET['rstatus']) : '';
if (!in_array($recStatus, ['pending', 'cancelled', 'deleted', 'failed'], true)) {
    $recStatus = '';
}
$recWhere = ($recStatus !== '' && $auditTableExists) ? " WHERE status='$recStatus'" : '';
$recTotal = $auditTableExists ? (int)$DB->getColumn("SELECT COUNT(*) FROM nteam_account_deletions" . $recWhere) : 0;
$recPages = max(1, (int)ceil($recTotal / $recPageSize));
$recPage  = isset($_GET['rpage']) ? max(1, intval($_GET['rpage'])) : 1;
if ($recPage > $recPages) { $recPage = $recPages; }
$recOffset = ($recPage - 1) * $recPageSize;
$records = $auditTableExists ? $DB->getAll("SELECT * FROM nteam_account_deletions" . $recWhere . " ORDER BY id DESC LIMIT $recOffset,$recPageSize") : [];
if (!is_array($records)) { $records = []; }

// ===== 注销日志（复用审计表 nteam_account_deletions，按时间线派生：申请 / 撤销 / 正式注销） =====
$logPageSize = 20;
$logUnion = "SELECT id AS record_id, status, user_id, username, 'submit' AS action, cancel_time AS event_time, '提交账号注销申请' AS detail, request_ip AS ip
             FROM nteam_account_deletions WHERE cancel_time IS NOT NULL
             UNION ALL
             SELECT id AS record_id, status, user_id, username, 'revoke' AS action, revoke_time AS event_time, '冷静期内登录，自动撤销注销申请' AS detail, agreement_ip AS ip
             FROM nteam_account_deletions WHERE revoke_time IS NOT NULL
             UNION ALL
             SELECT id AS record_id, status, user_id, username, 'delete' AS action, delete_time AS event_time, '账号及个人数据已注销' AS detail, agreement_ip AS ip
             FROM nteam_account_deletions WHERE delete_time IS NOT NULL";
$logTotal = $auditTableExists ? (int)$DB->getColumn("SELECT COUNT(*) FROM ($logUnion) t") : 0;
$logPages = max(1, (int)ceil($logTotal / $logPageSize));
$logPage  = isset($_GET['lpage']) ? max(1, intval($_GET['lpage'])) : 1;
if ($logPage > $logPages) { $logPage = $logPages; }
$logOffset = ($logPage - 1) * $logPageSize;
$logs = $auditTableExists ? $DB->getAll("SELECT * FROM ($logUnion) t ORDER BY event_time DESC LIMIT $logOffset,$logPageSize") : [];
if (!is_array($logs)) { $logs = []; }

// 状态 / 动作展示映射
$recStatusMap = [
    'pending'   => ['冷静期中', 'warning'],
    'cancelled' => ['已撤销', 'secondary'],
    'deleted'   => ['已注销', 'success'],
    'failed'    => ['删除失败', 'danger'],
];
$logActionMap = [
    'submit' => ['提交申请', 'primary'],
    'revoke' => ['撤销注销', 'info'],
    'delete' => ['正式注销', 'success'],
    'failed' => ['注销失败', 'danger'],
];

// 当前 tab（默认显示注销设置）
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'config';

// 分页渲染（$base 形如 '?rstatus=deleted&rpage=' ，末尾直接拼页码）
if (!function_exists('cancelPager')) {
    function cancelPager($page, $pages, $base) {
        if ($pages <= 1) { return ''; }
        $html = '<nav class="mt-3"><ul class="pagination pagination-sm justify-content-center flex-wrap">';
        if ($page > 1) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $base . ($page - 1) . '">上一页</a></li>';
        }
        $start = max(1, $page - 3);
        $end   = min($pages, $page + 3);
        for ($i = $start; $i <= $end; $i++) {
            $active = ($i == $page) ? ' active' : '';
            $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $base . $i . '">' . $i . '</a></li>';
        }
        if ($page < $pages) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $base . ($page + 1) . '">下一页</a></li>';
        }
        $html .= '</ul></nav>';
        return $html;
    }
}
?>
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
                            <a class="link-fx" href="index.php">后台首页</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page"><?php echo $title ?></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="content animated fadeIn">
        <!-- 当前配置概览 -->
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">当前配置概览</h3>
            </div>
            <div class="block-content">
                <div class="row g-3">
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="mb-1 text-muted fs-sm">注销功能</div>
                        <?php if($cfg['enable'] === 1): ?>
                            <span class="badge bg-success">已开启</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">已关闭</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-6 col-md-4 col-xl-3">
                        <div class="mb-1 text-muted fs-sm">身份验证方式</div>
                        <span class="badge bg-primary"><?php echo htmlspecialchars($modeName); ?></span>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="mb-1 text-muted fs-sm">冷静期</div>
                        <?php if($cfg['cooling_enable'] === 1): ?>
                            <span class="badge bg-warning">已开启（<?php echo intval($cfg['cooling_days']); ?> 天）</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">已关闭（立即注销）</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-6 col-md-4 col-xl-3">
                        <div class="mb-1 text-muted fs-sm">邮件通知</div>
                        <span class="badge <?php echo $cfg['mail_submit'] === 1 ? 'bg-success' : 'bg-secondary'; ?>">提交 <?php echo $cfg['mail_submit'] === 1 ? '开' : '关'; ?></span>
                        <span class="badge <?php echo $cfg['mail_deleted'] === 1 ? 'bg-success' : 'bg-secondary'; ?>">成功 <?php echo $cfg['mail_deleted'] === 1 ? '开' : '关'; ?></span>
                        <span class="badge <?php echo $cfg['mail_revoked'] === 1 ? 'bg-success' : 'bg-secondary'; ?>">撤销 <?php echo $cfg['mail_revoked'] === 1 ? '开' : '关'; ?></span>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="mb-1 text-muted fs-sm">服务协议</div>
                        <?php if ($agreementState === 1): ?>
                            <span class="badge bg-success">已发布</span>
                        <?php elseif ($agreementState === 2): ?>
                            <span class="badge bg-warning">已停用</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">未配置</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-text mt-3 mb-2">
                    注销记录：共 <b><?php echo $totalCount; ?></b> 条，其中冷静期中 <b><?php echo $pendingCount; ?></b> 条。
                </div>
            </div>
        </div>

        <!-- 配置表单 -->
        <ul class="nav nav-tabs mt-4" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab === 'config' ? 'active' : ''; ?>" href="?tab=config">注销设置</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab === 'records' ? 'active' : ''; ?>" href="?tab=records">注销记录</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab === 'logs' ? 'active' : ''; ?>" href="?tab=logs">注销日志</a>
            </li>
        </ul>

        <?php if ($activeTab === 'config'): ?>
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">注销设置</h3>
            </div>
            <div class="block-content">
                <form onsubmit="return saveCancelConfig(this)">
                    <div class="mb-4">
                        <label class="form-label"><b>注销功能总开关</b></label>
                        <div class="space-x-2">
                            <div class="form-check form-check-inline">
                                <label class="form-check-label">
                                    <input class="form-check-input" type="radio" name="enable" value="1" <?php echo $cfg['enable'] === 1 ? 'checked' : ''; ?>> 启用
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <label class="form-check-label">
                                    <input class="form-check-input" type="radio" name="enable" value="0" <?php echo $cfg['enable'] === 0 ? 'checked' : ''; ?>> 禁用
                                </label>
                            </div>
                        </div>
                        <div class="form-text">关闭后，用户端注销页将提示"未开启"，相关接口一并拒绝（入口仍保留展示）。</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label"><b>身份验证方式</b></label>
                        <div>
                            <?php foreach($modes as $mk => $mn): ?>
                            <div class="form-check mb-1">
                                <label class="form-check-label">
                                    <input class="form-check-input" type="radio" name="verify_mode" value="<?php echo $mk; ?>" <?php echo $cfg['verify_mode'] === $mk ? 'checked' : ''; ?>>
                                    <?php echo htmlspecialchars($mn); ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-text">三种方式均包含人机验证；选择含"邮件验证码"的方式时，用户端需完成邮箱验证码校验（依赖 SMTP 配置）。</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label"><b>冷静期设置</b></label>
                        <div class="space-x-2 mb-2">
                            <div class="form-check form-check-inline">
                                <label class="form-check-label">
                                    <input class="form-check-input" type="radio" name="cooling_enable" value="1" <?php echo $cfg['cooling_enable'] === 1 ? 'checked' : ''; ?>> 启用
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <label class="form-check-label">
                                    <input class="form-check-input" type="radio" name="cooling_enable" value="0" <?php echo $cfg['cooling_enable'] === 0 ? 'checked' : ''; ?>> 禁用
                                </label>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input type="number" class="form-control" name="cooling_days" id="cooling_days" min="1" max="90" value="<?php echo intval($cfg['cooling_days']); ?>">
                                    <span class="input-group-text">天</span>
                                </div>
                            </div>
                        </div>
                        <div class="form-text">冷静期天数建议 1–90 天，默认 10 天；关闭冷静期时，用户提交后立即注销。</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label"><b>邮件通知设置</b></label>
                        <div class="mb-2">
                            <span class="me-2">提交注销时发送通知邮件：</span>
                            <div class="form-check form-check-inline">
                                <label class="form-check-label"><input class="form-check-input" type="radio" name="mail_submit" value="1" <?php echo $cfg['mail_submit'] === 1 ? 'checked' : ''; ?>> 开</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <label class="form-check-label"><input class="form-check-input" type="radio" name="mail_submit" value="0" <?php echo $cfg['mail_submit'] === 0 ? 'checked' : ''; ?>> 关</label>
                            </div>
                        </div>
                        <div class="mb-2">
                            <span class="me-2">注销成功时发送通知邮件：</span>
                            <div class="form-check form-check-inline">
                                <label class="form-check-label"><input class="form-check-input" type="radio" name="mail_deleted" value="1" <?php echo $cfg['mail_deleted'] === 1 ? 'checked' : ''; ?>> 开</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <label class="form-check-label"><input class="form-check-input" type="radio" name="mail_deleted" value="0" <?php echo $cfg['mail_deleted'] === 0 ? 'checked' : ''; ?>> 关</label>
                            </div>
                        </div>
                        <div class="mb-2">
                            <span class="me-2">注销申请被自动撤销时发送通知邮件：</span>
                            <div class="form-check form-check-inline">
                                <label class="form-check-label"><input class="form-check-input" type="radio" name="mail_revoked" value="1" <?php echo $cfg['mail_revoked'] === 1 ? 'checked' : ''; ?>> 开</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <label class="form-check-label"><input class="form-check-input" type="radio" name="mail_revoked" value="0" <?php echo $cfg['mail_revoked'] === 0 ? 'checked' : ''; ?>> 关</label>
                            </div>
                        </div>
                        <div class="form-text">邮件发送依赖 SMTP（邮箱配置）设置。</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label"><b>《用户注销服务协议》设置</b></label>
                        

                        <div class="mb-2">
                            <?php if ($agreementState === 1): ?>
                                <span class="badge bg-success">已发布</span>
                            <?php elseif ($agreementState === 2): ?>
                                <span class="badge bg-warning">已停用（用户端不展示、无需确认）</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">未配置（将使用内置默认模板）</span>
                            <?php endif; ?>
                            <a class="btn btn-sm btn-alt-primary" href="agreement_manage.php">
                            <i class="fa fa-file-signature me-1"></i>编辑
                            </a>
                        </div>
                    </div>

                    <div class="mb-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-check opacity-50 me-1"></i> <b>保存设置</b>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php endif; ?>

        <?php if ($activeTab === 'records'): ?>
        <!-- 注销记录 -->
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">注销记录</h3>
                <div class="block-options">
                    <button type="button" class="btn btn-sm btn-alt-danger me-2" onclick="clearCancelRecords(false)"><i class="fa fa-trash-alt me-1"></i>清空记录</button>
                    <span class="badge bg-primary">共 <?php echo $recTotal; ?> 条</span>
                </div>
            </div>
            <div class="block-content">
                <div class="mb-3">
                    <a class="btn btn-sm <?php echo $recStatus === '' ? 'btn-primary' : 'btn-alt-primary'; ?>" href="?tab=records">全部</a>
                    <a class="btn btn-sm <?php echo $recStatus === 'pending' ? 'btn-primary' : 'btn-alt-primary'; ?>" href="?tab=records&rstatus=pending">冷静期中</a>
                    <a class="btn btn-sm <?php echo $recStatus === 'cancelled' ? 'btn-primary' : 'btn-alt-primary'; ?>" href="?tab=records&rstatus=cancelled">已撤销</a>
                    <a class="btn btn-sm <?php echo $recStatus === 'deleted' ? 'btn-primary' : 'btn-alt-primary'; ?>" href="?tab=records&rstatus=deleted">已注销</a>
                    <a class="btn btn-sm <?php echo $recStatus === 'failed' ? 'btn-primary' : 'btn-alt-primary'; ?>" href="?tab=records&rstatus=failed">删除失败</a>
                </div>
                <?php if (!$auditTableExists): ?>
                    <div class="alert alert-warning mb-0">未检测到数据表 <code>nteam_account_deletions</code>，请先执行数据库更新脚本。</div>
                <?php elseif (empty($records)): ?>
                    <div class="text-center text-muted py-4">暂无注销记录</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:60px;">ID</th>
                                <th style="min-width:110px;">用户</th>
                                <th class="d-none d-md-table-cell" style="min-width:150px;">邮箱</th>
                                <th class="d-none d-lg-table-cell" style="min-width:120px;">验证方式</th>
                                <th class="d-none d-lg-table-cell text-center" style="min-width:90px;">冷静期</th>
                                <th class="text-center" style="min-width:90px;">状态</th>
                                <th class="d-none d-md-table-cell" style="min-width:150px;">申请时间</th>
                                <th class="d-none d-xl-table-cell" style="min-width:150px;">撤销/删除时间</th>
                                <th class="text-center" style="min-width:80px;">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $r):
                                $st = isset($recStatusMap[$r['status']]) ? $recStatusMap[$r['status']] : [$r['status'], 'secondary'];
                                $vname = isset($modes[$r['verify_mode']]) ? $modes[$r['verify_mode']] : $r['verify_mode'];
                                $coolingText = ((int)$r['cooling_enabled'] === 1) ? ((int)$r['cooling_days'] . ' 天') : '关闭';
                                $endTime = $r['delete_time'] ? $r['delete_time'] : ($r['revoke_time'] ? $r['revoke_time'] : '-');
                            ?>
                            <tr>
                                <td class="text-center"><?php echo intval($r['id']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($r['username']); ?>
                                    <div class="fs-sm text-muted">ID: <?php echo intval($r['user_id']); ?></div>
                                </td>
                                <td class="d-none d-md-table-cell"><?php echo htmlspecialchars($r['email']); ?></td>
                                <td class="d-none d-lg-table-cell"><?php echo htmlspecialchars($vname); ?></td>
                                <td class="d-none d-lg-table-cell text-center"><?php echo $coolingText; ?></td>
                                <td class="text-center"><span class="badge bg-<?php echo $st[1]; ?>"><?php echo htmlspecialchars($st[0]); ?></span></td>
                                <td class="d-none d-md-table-cell"><?php echo htmlspecialchars($r['cancel_time'] ? $r['cancel_time'] : '-'); ?></td>
                                <td class="d-none d-xl-table-cell"><?php echo htmlspecialchars($endTime); ?></td>
                                <td class="text-center">
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <button type="button" class="btn btn-sm btn-alt-secondary" disabled title="冷静期内不可删除">删除</button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-alt-danger" onclick="delCancelRecord(<?php echo intval($r['id']); ?>, false)">删除</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php echo cancelPager($recPage, $recPages, '?tab=records&' . ($recStatus !== '' ? 'rstatus=' . $recStatus . '&' : '') . 'rpage='); ?>
                <?php endif; ?>
            </div>
        </div>

        <?php endif; ?>

        <?php if ($activeTab === 'logs'): ?>
        <!-- 注销日志 -->
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">注销日志</h3>
                <div class="block-options">
                    <button type="button" class="btn btn-sm btn-alt-danger me-2" onclick="clearCancelRecords(true)"><i class="fa fa-trash-alt me-1"></i>清空日志</button>
                    <span class="badge bg-primary">共 <?php echo $logTotal; ?> 条</span>
                </div>
            </div>
            <div class="block-content">
                <?php if (!$auditTableExists): ?>
                    <div class="alert alert-warning mb-0">未检测到数据表 <code>nteam_account_deletions</code>，请先执行数据库更新脚本。</div>
                <?php elseif (empty($logs)): ?>
                    <div class="text-center text-muted py-4">暂无注销日志</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th style="min-width:150px;">时间</th>
                                <th style="min-width:120px;">用户</th>
                                <th class="text-center" style="min-width:100px;">动作</th>
                                <th style="min-width:200px;">说明</th>
                                <th class="d-none d-md-table-cell" style="min-width:120px;">IP</th>
                                <th class="text-center" style="min-width:80px;">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $lg):
                                $ac = isset($logActionMap[$lg['action']]) ? $logActionMap[$lg['action']] : [$lg['action'], 'secondary'];
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($lg['event_time']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($lg['username']); ?>
                                    <span class="text-muted fs-sm">(ID:<?php echo intval($lg['user_id']); ?>)</span>
                                </td>
                                <td class="text-center"><span class="badge bg-<?php echo $ac[1]; ?>"><?php echo htmlspecialchars($ac[0]); ?></span></td>
                                <td><?php echo htmlspecialchars($lg['detail']); ?></td>
                                <td class="d-none d-md-table-cell"><?php echo htmlspecialchars($lg['ip']); ?></td>
                                <td class="text-center">
                                    <?php if ((isset($lg['status']) ? $lg['status'] : '') === 'pending'): ?>
                                        <button type="button" class="btn btn-sm btn-alt-secondary" disabled title="冷静期内不可删除">删除</button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-alt-danger" onclick="delCancelRecord(<?php echo intval($lg['record_id']); ?>, true)">删除</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php echo cancelPager($logPage, $logPages, '?tab=logs&lpage='); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include 'foot.php'?>
<script>
// 删除单条注销记录（日志删除会同时删除其来源记录）
function delCancelRecord(id, isLog) {
    var msg = isLog
        ? '确定要删除该注销日志吗？将同时删除其来源记录（冷静期内的记录不可删除，操作不可恢复）。'
        : '确定要删除该注销记录吗？（冷静期内的记录不可删除，操作不可恢复）';
    layer.confirm(msg, {btn: ['确定删除', '取消'], icon: 3}, function () {
        var ii = layer.load(2, {shade: [0.1, '#fff']});
        $.ajax({
            type: 'POST',
            url: 'Ajax.php?act=delete_cancel_record',
            data: {id: id},
            dataType: 'json',
            success: function (data) {
                layer.close(ii);
                if (data.code == 0) {
                    layer.msg(data.msg, {icon: 1}, function () { location.reload(); });
                } else {
                    layer.alert(data.msg, {icon: 2});
                }
            },
            error: function () {
                layer.close(ii);
                layer.msg('服务器错误');
            }
        });
    });
}

// 一键清空注销记录/日志（保留冷静期内的记录）
function clearCancelRecords(isLog) {
    var msg = isLog
        ? '确定要清空所有注销日志吗？冷静期内的记录不会受影响，操作不可恢复。'
        : '确定要清空所有注销记录吗？冷静期内的记录将被保留，操作不可恢复。';
    layer.confirm(msg, {btn: ['确定清空', '取消'], icon: 3}, function () {
        var ii = layer.load(2, {shade: [0.1, '#fff']});
        $.ajax({
            type: 'POST',
            url: 'Ajax.php?act=clear_cancel_records',
            dataType: 'json',
            success: function (data) {
                layer.close(ii);
                if (data.code == 0) {
                    layer.msg(data.msg, {icon: 1}, function () { location.reload(); });
                } else {
                    layer.alert(data.msg, {icon: 2});
                }
            },
            error: function () {
                layer.close(ii);
                layer.msg('服务器错误');
            }
        });
    });
}

function saveCancelConfig(obj){
    var days = parseInt($(obj).find('input[name="cooling_days"]').val(), 10);
    if (isNaN(days) || days < 1 || days > 90) {
        layer.alert('冷静期天数请输入 1–90 之间的整数', {icon: 2});
        return false;
    }
    var ii = layer.load(2, {shade:[0.1,'#fff']});
    $.ajax({
        type : 'POST',
        url : 'Ajax.php?act=save_cancel_config',
        data : $(obj).serialize(),
        dataType : 'json',
        success : function(data) {
            layer.close(ii);
            if(data.code == 0){
                layer.alert(data.msg, {icon: 1, closeBtn: false}, function(){
                    window.location.reload();
                });
            }else{
                layer.alert(data.msg, {icon: 2});
            }
        },
        error : function(){
            layer.close(ii);
            layer.msg('服务器错误');
        }
    });
    return false;
}
</script>
</body>
</html>
