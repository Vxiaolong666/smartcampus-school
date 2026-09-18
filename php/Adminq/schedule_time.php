<?php
$mod = 'admin';
$title = '课程时间配置';
include('../Common/Core_brain.php');
if ($adminData['adminRank'] == 2) {
    echo "您的账号没有权限使用此功能";
    exit;
}
require_once('../funsion/ScheduleTime.php');
require_once('../funsion/AcademicSystem.php');

// 课程模式下拉选项：取自教务「课程模式」接口（与前台课程表页同一来源），与具体学生无关。
// 会话缓存 10 分钟；接口不可用时退化为手动输入，并在 5 分钟内不重复重试，避免后台页面变慢。
$modeOptions    = [];
$modeFetchError = '';
$refreshModes   = (isset($_GET['refresh_modes']) && $_GET['refresh_modes'] == 1);
$modeCache      = isset($_SESSION['schedule_mode_options']) ? $_SESSION['schedule_mode_options'] : null;
$modeFailAt     = isset($_SESSION['schedule_mode_fail_time']) ? intval($_SESSION['schedule_mode_fail_time']) : 0;

if (!$refreshModes && is_array($modeCache) && !empty($modeCache['list'])
    && (time() - intval($modeCache['time'])) < 600) {
    $modeOptions = $modeCache['list'];
} elseif (!$refreshModes && $modeFailAt > 0 && (time() - $modeFailAt) < 300) {
    $modeFetchError = '教务模式列表暂时获取失败，已跳过后台自动重试';
} else {
    $modeAccounts = $DB->getAll("SELECT user_id, student_id, password FROM nteam_academic_accounts ORDER BY id ASC LIMIT 2");
    if (empty($modeAccounts)) {
        $modeFetchError = '系统中还没有用户绑定教务账号';
    } else {
        try {
            foreach ($modeAccounts as $acc) {
                $tk = AcademicSystem::getValidToken($DB, $acc['user_id'], $acc['student_id'], $acc['password']);
                if (empty($tk['success'])) {
                    $modeFetchError = $tk['msg'] ?? '教务账号登录失败';
                    continue;
                }
                $modeRes = AcademicSystem::getScheduleMode($tk['token']);
                if (isset($modeRes['code']) && $modeRes['code'] == 1 && !empty($modeRes['data']) && is_array($modeRes['data'])) {
                    foreach ($modeRes['data'] as $mo) {
                        $mid = isset($mo['kbjcmsid']) ? trim((string) $mo['kbjcmsid']) : '';
                        if ($mid === '') continue;
                        $modeOptions[$mid] = isset($mo['kbjcmsmc']) ? trim((string) $mo['kbjcmsmc']) : '';
                    }
                }
                if (!empty($modeOptions)) {
                    $modeFetchError = '';
                    break;
                }
                $modeFetchError = '教务接口未返回课程模式';
            }
        } catch (Exception $e) {
            // 教务系统不可达等异常：静默降级为手动填写模式 ID
            $modeOptions    = [];
            $modeFetchError = '教务接口异常：' . $e->getMessage();
        }
    }

    if (!empty($modeOptions)) {
        $_SESSION['schedule_mode_options']   = ['time' => time(), 'list' => $modeOptions];
        $_SESSION['schedule_mode_fail_time'] = 0;
    } else {
        $_SESSION['schedule_mode_fail_time'] = time();
    }
}

include './head.php';

$tableReady = scheduleTimeTableReady($DB);
$modes      = $tableReady ? scheduleTimeListModes($DB) : [];

// 供前端「编辑」使用
$modesJs = [];
foreach ($modes as $m) {
    $periods = scheduleTimeNormalizePeriods(isset($m['periods']) ? $m['periods'] : '');
    $modesJs[] = [
        'id'         => intval($m['id']),
        'mode_id'    => isset($m['mode_id']) ? (string) $m['mode_id'] : '',
        'mode_name'  => isset($m['mode_name']) ? (string) $m['mode_name'] : '',
        'is_default' => isset($m['is_default']) ? intval($m['is_default']) : 0,
        'sort_order' => isset($m['sort_order']) ? intval($m['sort_order']) : 0,
        'periods'    => $periods,
    ];
}
$defaultPeriods = scheduleTimeDefaultPeriods();
?>
<div id="pjax-container">
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
                <div class="flex-grow-1">
                    <h1 class="h3 fw-bold mb-2">
                        <?php echo $title ?>
                    </h1>
                </div>
                <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">
                            <a class="link-fx" href="index.php">后台首页</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">
                            <?php echo $title ?>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="content animated fadeIn">
        <div class="alert alert-info">
            <p class="mb-1 fw-semibold"><i class="fa fa-circle-info me-1"></i>为什么课程时间要在这里配置？</p>
            <p class="mb-0">
                教务系统接口只返回「节数」（例如 classTime = 105106 表示第 5、6 节），并不返回每节课的起止时间，因此需要在后台按
                <strong>课程模式</strong> 维护作息时间。课程表、今日课程、AI 课表分析都会使用这里配置的时间；
                未匹配到具体模式的课程模式时，统一使用「<strong>默认模式</strong>」的时间。
            </p>
        </div>

        <?php if (!$tableReady): ?>
        <div class="alert alert-warning">
            <p class="mb-1 fw-semibold"><i class="fa fa-triangle-exclamation me-1"></i>配置表尚未导入</p>
            <p class="mb-0">
                请先在数据库中执行 <code>php/schedule_time_update.sql</code>（创建 <code>nteam_schedule_modes</code> 表并写入默认作息），
                执行后刷新本页面即可配置。未导入期间前台会继续使用内置默认作息，功能不受影响。
            </p>
        </div>
        <?php endif; ?>

        <div class="block block-rounded mb-4">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-table-list me-1"></i>课程模式与节次时间</h3>
                <div class="block-options">
                    <button type="button" class="btn btn-sm btn-alt-primary" onclick="openCreateMode()" <?php echo $tableReady ? '' : 'disabled'; ?>>
                        <i class="fa fa-plus me-1"></i>新增课程模式
                    </button>
                </div>
            </div>
            <div class="block-content">
                <?php if (!$tableReady): ?>
                <div class="text-center text-muted py-4">配置表未导入，暂无法配置</div>
                <?php elseif (empty($modes)): ?>
                <div class="text-center text-muted py-4">暂无课程模式，点击右上角「新增课程模式」开始配置</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th style="min-width: 180px;">课程模式名称</th>
                                <th style="width: 100px;">课程模式</th>
                                <th class="text-center" style="min-width: 90px;">节次数</th>
                                <th class="text-center" style="min-width: 150px;">作息范围</th>
                                <th class="text-center" style="min-width: 80px;">默认</th>
                                <th class="text-center" style="min-width: 150px;">更新时间</th>
                                <th class="text-center" style="min-width: 250px;">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($modes as $m): ?>
                            <?php
                            $periods = scheduleTimeNormalizePeriods(isset($m['periods']) ? $m['periods'] : '');
                            $count   = count($periods);
                            $range   = '';
                            if ($count > 0) {
                                $range = $periods[0]['start'] . ' ~ ' . $periods[$count - 1]['end'];
                            }
                            ?>
                            <tr>
                                <td>
                                    <span class="fw-semibold"><?php echo htmlspecialchars($m['mode_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php if (intval($m['is_default']) === 1): ?>
                                    <span class="badge bg-success ms-1">默认</span>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width: 100px;">
                                    <?php
                                    $mId      = trim((string) $m['mode_id']);
                                    $mIdName  = ($mId !== '' && isset($modeOptions[$mId])) ? $modeOptions[$mId] : '';
                                    // ID 超长时截断显示，完整值与教务模式名放在悬浮提示里
                                    $mIdShort = $mId;
                                    if ($mId !== '' && mb_strlen($mId, 'UTF-8') > 8) {
                                        $mIdShort = mb_substr($mId, 0, 8, 'UTF-8') . '…';
                                    }
                                    $mIdTitle = $mId . ($mIdName !== '' ? '（' . $mIdName . '）' : '');
                                    ?>
                                    <?php if ($mId === ''): ?>
                                    <span class="text-muted">通用</span>
                                    <?php else: ?>
                                    <code title="<?php echo htmlspecialchars($mIdTitle, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($mIdShort, ENT_QUOTES, 'UTF-8'); ?></code>
                                    <?php if ($mIdName !== ''): ?>
                                    <div class="fs-sm text-muted text-truncate" title="<?php echo htmlspecialchars($mIdName, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($mIdName, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?php echo $count; ?> 节</td>
                                <td class="text-center"><small class="text-muted"><?php echo htmlspecialchars($range, ENT_QUOTES, 'UTF-8'); ?></small></td>
                                <td class="text-center">
                                    <?php if (intval($m['is_default']) === 1): ?>
                                    <i class="fa fa-circle-check text-success"></i>
                                    <?php else: ?>
                                    <i class="fa fa-circle-minus text-muted opacity-50"></i>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><small><?php echo htmlspecialchars((string) ($m['updated_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-alt-primary me-1" onclick="openEditMode(<?php echo intval($m['id']); ?>)">
                                        <i class="fa fa-pen me-1"></i>编辑
                                    </button>
                                    <?php if (intval($m['is_default']) !== 1): ?>
                                    <button type="button" class="btn btn-sm btn-alt-secondary me-1" onclick="setDefaultMode(<?php echo intval($m['id']); ?>)">
                                        <i class="fa fa-star me-1"></i>设为默认
                                    </button>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-sm btn-alt-danger" onclick="deleteMode(<?php echo intval($m['id']); ?>)">
                                        <i class="fa fa-trash me-1"></i>删除
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="block block-rounded mb-4" id="modeFormBlock" style="display: none;">
            <div class="block-header block-header-default">
                <h3 class="block-title" id="modeFormTitle"><i class="fa fa-clock me-1"></i>新增课程模式</h3>
                <div class="block-options">
                    <button type="button" class="btn btn-sm btn-alt-secondary" onclick="closeModeForm()">
                        <i class="fa fa-angle-up me-1"></i>收起
                    </button>
                </div>
            </div>
            <div class="block-content">
                <form id="modeForm" onsubmit="return false;">
                    <input type="hidden" id="modeId" value="0">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">课程模式名称 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modeName" maxlength="50" placeholder="例如：默认作息（标准）">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">课程模式</label>
                            <?php if (!empty($modeOptions)): ?>
                            <select class="form-select" id="modeKbjcmsid" onchange="onModeIdChange(this)">
                                <option value="">留空（通用作息 / 默认模式）</option>
                                <?php foreach ($modeOptions as $mid => $mname): ?>
                                <option value="<?php echo htmlspecialchars($mid, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-name="<?php echo htmlspecialchars($mname, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo $mname !== '' ? htmlspecialchars($mname, ENT_QUOTES, 'UTF-8') . '（' . htmlspecialchars($mid, ENT_QUOTES, 'UTF-8') . '）' : htmlspecialchars($mid, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                共 <?php echo count($modeOptions); ?> 个教务课程模式，选择后该模式课表将使用这里的作息；
                                <a href="?refresh_modes=1">刷新列表</a>
                            </div>
                            <?php else: ?>
                            <input type="text" class="form-control" id="modeKbjcmsid" maxlength="50" placeholder="教务课程模式下拉的 kbjcmsid，可留空">
                            <div class="form-text">
                                <?php echo $modeFetchError !== '' ? htmlspecialchars($modeFetchError, ENT_QUOTES, 'UTF-8') . '，可手动填写模式 ID；' : '' ?>
                                与教务「课程模式」下拉里的值一致，留空表示通用作息。
                                <a href="?refresh_modes=1">重新获取列表</a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">排序</label>
                            <input type="number" class="form-control" id="modeSort" value="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label d-block">默认模式</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="modeIsDefault" value="1">
                                <label class="form-check-label" for="modeIsDefault">未匹配时使用</label>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center mb-2">
                        <h5 class="fw-semibold mb-2 mb-sm-0"><i class="fa fa-list-ol me-1"></i>节次时间</h5>
                        <div class="d-flex flex-wrap gap-2" id="periodActions">
                            <button type="button" class="btn btn-sm btn-alt-primary" onclick="addPeriodRow()">
                                <i class="fa fa-plus me-1"></i>加课
                            </button>
                            <button type="button" class="btn btn-sm btn-alt-success" data-bs-toggle="modal" data-bs-target="#quickSetupModal">
                                <i class="fa fa-wand-magic-sparkles me-1"></i>快捷设置
                            </button>
                            <button type="button" class="btn btn-sm btn-alt-secondary" onclick="resetPeriods()">
                                <i class="fa fa-rotate-left me-1"></i>重置
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-vcenter" id="periodTable">
                            <thead>
                                <tr>
                                    <th style="min-width: 160px;">节次名称</th>
                                    <th style="min-width: 140px;">开始时间</th>
                                    <th style="min-width: 140px;">结束时间</th>
                                    <th class="text-center" style="width: 110px;">操作</th>
                                </tr>
                            </thead>
                            <tbody id="periodRows"></tbody>
                        </table>
                    </div>
                    <div class="text-muted fs-sm mb-3">
                        时间格式 HH:MM；课程表中第 N 行即第 N 节，请按教务节次顺序填写（最多 <?php echo scheduleTimeMaxPeriods(); ?> 节）。
                    </div>

                    <div class="d-flex flex-wrap gap-2 mt-3 mb-2">
                        <button type="button" class="btn btn-primary" onclick="saveMode()">
                            <i class="fa fa-check me-1"></i>保存
                        </button>
                        <button type="button" class="btn btn-alt-secondary" onclick="closeModeForm()">取消</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 快捷设置弹窗：按上午 / 下午 / 晚上的起止时间自动计算每节课上下课时间 -->
    <div class="modal fade" id="quickSetupModal" tabindex="-1" aria-labelledby="quickSetupModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="quickSetupModalLabel">
                        <i class="fa fa-wand-magic-sparkles me-1"></i>快捷设置节次时间
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted fs-sm mb-3">
                        适合「每节课时长相同、课间相同」的作息：填写每节课时长与课间，再填上午 / 下午 / 晚上的
                        <strong>节数</strong>与<strong>开始时间</strong>，点「计算并预览」即可自动算出每一节的上下课时间。
                        结束时间可留空（按节数自动推算）；若填了结束时间，则以结束时间为准反推每节课时长，并在提示里说明差异。
                    </p>

                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-3">
                            <label class="form-label">每节课时长（分钟）</label>
                            <input type="number" class="form-control" id="qsDuration" value="40" min="10" max="180">
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label">课间休息（分钟）</label>
                            <input type="number" class="form-control" id="qsBreak" value="10" min="0" max="60">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">时段</th>
                                    <th style="width: 110px;">节数</th>
                                    <th>开始时间</th>
                                    <th>结束时间</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-semibold">上午</td>
                                    <td><input type="number" class="form-control form-control-sm qs-count" data-block="morning" value="4" min="0" max="12"></td>
                                    <td><input type="time" class="form-control form-control-sm qs-start" data-block="morning" value="08:15"></td>
                                    <td><input type="time" class="form-control form-control-sm qs-end" data-block="morning" placeholder="留空自动推算"></td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">下午</td>
                                    <td><input type="number" class="form-control form-control-sm qs-count" data-block="afternoon" value="3" min="0" max="12"></td>
                                    <td><input type="time" class="form-control form-control-sm qs-start" data-block="afternoon" value="14:30"></td>
                                    <td><input type="time" class="form-control form-control-sm qs-end" data-block="afternoon"></td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">晚上</td>
                                    <td><input type="number" class="form-control form-control-sm qs-count" data-block="evening" value="4" min="0" max="12"></td>
                                    <td><input type="time" class="form-control form-control-sm qs-start" data-block="evening" value="18:50"></td>
                                    <td><input type="time" class="form-control form-control-sm qs-end" data-block="evening"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="text-end">
                        <button type="button" class="btn btn-alt-primary btn-sm" onclick="quickSetupPreview()">
                            <i class="fa fa-calculator me-1"></i>计算并预览
                        </button>
                    </div>

                    <div id="qsResult" style="display: none;">
                        <hr>
                        <h6 class="fw-semibold mb-2"><i class="fa fa-list-ol me-1"></i>计算结果预览</h6>
                        <div id="qsHints"></div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-vcenter">
                                <thead>
                                    <tr>
                                        <th style="width: 90px;">节次</th>
                                        <th style="width: 70px;">时段</th>
                                        <th>上课 — 下课</th>
                                        <th class="text-center" style="width: 90px;">时长</th>
                                    </tr>
                                </thead>
                                <tbody id="qsPreviewRows"></tbody>
                            </table>
                        </div>
                        <p class="text-muted fs-sm mb-0">确认无误后点右下角「应用到节次列表」，再点表单里的「保存」即可生效。</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-alt-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-primary" id="qsApplyBtn" onclick="quickSetupApply()" disabled>
                        <i class="fa fa-check me-1"></i>应用到节次列表
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var MODES = <?php echo json_encode($modesJs, JSON_UNESCAPED_UNICODE); ?>;
var DEFAULT_PERIODS = <?php echo json_encode($defaultPeriods, JSON_UNESCAPED_UNICODE); ?>;

function escHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
}

function findMode(id) {
    var found = null;
    (MODES || []).forEach(function (m) { if (m.id == id) found = m; });
    return found;
}

function periodRowHtml(name, start, end) {
    return '<tr>' +
        '<td data-label="节次名称"><input type="text" class="form-control form-control-sm p-name" maxlength="20" value="' + escHtml(name) + '" placeholder="第1节"></td>' +
        '<td data-label="开始时间"><input type="time" class="form-control form-control-sm p-start" value="' + escHtml(start) + '"></td>' +
        '<td data-label="结束时间"><input type="time" class="form-control form-control-sm p-end" value="' + escHtml(end) + '"></td>' +
        '<td class="text-center period-actions"><button type="button" class="btn btn-sm btn-alt-danger" onclick="removePeriodRow(this)"><i class="fa fa-trash me-1"></i>删除</button></td>' +
        '</tr>';
}

function renderPeriodRows(periods) {
    var html = '';
    (periods || []).forEach(function (p) {
        html += periodRowHtml(p.name, p.start, p.end);
    });
    if (!html) html = periodRowHtml('第1节', '', '');
    $('#periodRows').html(html);
}

function addPeriodRow() {
    $('#periodRows').append(periodRowHtml('', '', ''));
}

function removePeriodRow(btn) {
    if ($('#periodRows tr').length <= 1) {
        layer.msg('至少保留一节课', { icon: 2 });
        return;
    }
    $(btn).closest('tr').remove();
}

function resetPeriods() {
    renderPeriodRows(DEFAULT_PERIODS);
}

/* ===== 快捷设置：按上午 / 下午 / 晚上的起止时间自动计算每节课上下课时间 ===== */
var QS_BLOCKS = [
    { key: 'morning',   label: '上午' },
    { key: 'afternoon', label: '下午' },
    { key: 'evening',   label: '晚上' }
];
var qsComputed = [];

function qsToMinutes(value) {
    if (!value) return null;
    var parts = String(value).split(':');
    if (parts.length < 2) return null;
    var h = parseInt(parts[0], 10);
    var m = parseInt(parts[1], 10);
    if (isNaN(h) || isNaN(m)) return null;
    return h * 60 + m;
}

function qsToHm(minutes) {
    minutes = Math.max(0, Math.round(minutes));
    var h = Math.floor(minutes / 60) % 24;
    var m = minutes % 60;
    return ('0' + h).slice(-2) + ':' + ('0' + m).slice(-2);
}

function quickSetupPreview() {
    var duration = parseInt($('#qsDuration').val(), 10);
    var breakMin = parseInt($('#qsBreak').val(), 10);
    if (!duration || duration <= 0) { layer.msg('请填写每节课时长（分钟）', { icon: 2 }); return; }
    if (isNaN(breakMin) || breakMin < 0) breakMin = 0;

    var periods = [];
    var hints = [];
    var hasBlock = false;

    QS_BLOCKS.forEach(function (block) {
        var count = parseInt($('.qs-count[data-block="' + block.key + '"]').val(), 10) || 0;
        if (count <= 0) return;
        if (count > 12) { count = 12; $('.qs-count[data-block="' + block.key + '"]').val(12); }

        var startMin = qsToMinutes($('.qs-start[data-block="' + block.key + '"]').val());
        if (startMin === null) {
            hints.push('<div class="alert alert-warning py-2 mb-2">' + block.label + '：未填写开始时间，已跳过该时段</div>');
            return;
        }

        var oneDuration = duration;
        var endVal = $('.qs-end[data-block="' + block.key + '"]').val();
        var endMin = qsToMinutes(endVal);
        if (endMin !== null) {
            var total = endMin - startMin;
            if (total < count + (count - 1) * breakMin) {
                hints.push('<div class="alert alert-warning py-2 mb-2">' + block.label + '：' + qsToHm(startMin) + ' ~ ' + endVal
                    + ' 时长不足以排 ' + count + ' 节课，已按每节 ' + duration + ' 分钟计算</div>');
            } else {
                var avg = Math.floor((total - (count - 1) * breakMin) / count);
                if (avg !== duration) {
                    hints.push('<div class="alert alert-info py-2 mb-2">' + block.label + '：以结束时间 ' + endVal
                        + ' 为准反推，每节课为 ' + avg + ' 分钟（设定值 ' + duration + ' 分钟）</div>');
                }
                oneDuration = avg;
            }
        }

        var blockEnd = startMin + count * oneDuration + (count - 1) * breakMin;
        for (var i = 0; i < count; i++) {
            var st = startMin + i * (oneDuration + breakMin);
            periods.push({
                name: '第' + (periods.length + 1) + '节',
                label: block.label,
                start: qsToHm(st),
                end: qsToHm(st + oneDuration),
                duration: oneDuration
            });
        }
        hints.push('<div class="alert alert-success py-2 mb-2">' + block.label + '：' + count + ' 节，'
            + qsToHm(startMin) + ' ~ ' + qsToHm(blockEnd) + '（每节 ' + oneDuration + ' 分钟，课间 ' + breakMin + ' 分钟）</div>');
        hasBlock = true;
    });

    if (!hasBlock || periods.length === 0) {
        qsComputed = [];
        $('#qsHints').html(hints.join(''));
        $('#qsResult').hide();
        $('#qsApplyBtn').prop('disabled', true);
        layer.msg('请至少填写一个时段的节数与开始时间', { icon: 2 });
        return;
    }

    qsComputed = periods;
    var rows = '';
    periods.forEach(function (p) {
        rows += '<tr><td>' + p.name + '</td><td>' + p.label + '</td><td><code>' + p.start + '-' + p.end
            + '</code></td><td class="text-center">' + p.duration + ' 分钟</td></tr>';
    });
    $('#qsPreviewRows').html(rows);
    $('#qsHints').html(hints.join(''));
    $('#qsResult').show();
    $('#qsApplyBtn').prop('disabled', false);
}

function quickSetupApply() {
    if (!qsComputed.length) { layer.msg('请先点「计算并预览」', { icon: 2 }); return; }

    renderPeriodRows(qsComputed.map(function (p) {
        return { name: p.name, start: p.start, end: p.end };
    }));

    var el = document.getElementById('quickSetupModal');
    if (window.bootstrap && bootstrap.Modal && el) {
        var inst = bootstrap.Modal.getInstance(el);
        if (!inst) inst = new bootstrap.Modal(el);
        inst.hide();
    } else if (window.jQuery && $.fn.modal) {
        $('#quickSetupModal').modal('hide');
    } else if (el) {
        el.classList.remove('show');
        el.style.display = 'none';
        el.setAttribute('aria-hidden', 'true');
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right', '');
    }

    $('#modeFormBlock').show();
    layer.msg('已按计算结果填充 ' + qsComputed.length + ' 节课，请确认后点「保存」', { icon: 1, time: 2500 });
    scrollToForm();
}

function scrollToForm() {
    var top = $('#modeFormBlock').offset().top - 80;
    $('html, body').animate({ scrollTop: top }, 200);
}

function openCreateMode() {
    $('#modeFormTitle').html('<i class="fa fa-clock me-1"></i>新增课程模式');
    $('#modeId').val(0);
    $('#modeName').val('');
    setModeIdValue('');
    $('#modeSort').val(0);
    $('#modeIsDefault').prop('checked', (MODES || []).length === 0);
    renderPeriodRows(DEFAULT_PERIODS);
    $('#modeFormBlock').show();
    scrollToForm();
}

// 选中教务课程模式后，若「课程模式名称」为空则自动带出模式名
function onModeIdChange(el) {
    var name = $(el).find('option:selected').attr('data-name') || '';
    if (name && $.trim($('#modeName').val() || '') === '') {
        $('#modeName').val(name);
    }
}

// 课程模式 ID 可能是下拉（取到教务列表时）或文本框（取不到列表时）
function setModeIdValue(value) {
    var $field = $('#modeKbjcmsid');
    value = (value === undefined || value === null) ? '' : String(value);
    if ($field.is('select')) {
        // 教务列表里没有的 ID（历史手工填写 / 教务已调整）动态补选项，避免编辑保存时被清空
        var found = false;
        $field.find('option').each(function () { if (this.value === value) found = true; });
        if (!found && value !== '') {
            $field.append('<option value="' + escHtml(value) + '">' + escHtml(value) + '（不在教务列表中）</option>');
        }
    }
    $field.val(value);
}

function openEditMode(id) {
    var m = findMode(id);
    if (!m) return;
    $('#modeFormTitle').html('<i class="fa fa-clock me-1"></i>编辑课程模式：' + escHtml(m.mode_name));
    $('#modeId').val(m.id);
    $('#modeName').val(m.mode_name);
    setModeIdValue(m.mode_id);
    $('#modeSort').val(m.sort_order);
    $('#modeIsDefault').prop('checked', parseInt(m.is_default, 10) === 1);
    renderPeriodRows(m.periods && m.periods.length ? m.periods : DEFAULT_PERIODS);
    $('#modeFormBlock').show();
    scrollToForm();
}

function closeModeForm() {
    $('#modeFormBlock').hide();
}

function collectPeriods() {
    var list = [];
    $('#periodRows tr').each(function () {
        var name = $.trim($(this).find('.p-name').val() || '');
        var start = $.trim($(this).find('.p-start').val() || '');
        var end = $.trim($(this).find('.p-end').val() || '');
        if (start === '' && end === '') return;
        list.push({ name: name, start: start, end: end });
    });
    return list;
}

function saveMode() {
    var name = $.trim($('#modeName').val() || '');
    if (name === '') { layer.msg('请填写课程模式名称', { icon: 2 }); return; }

    var periods = collectPeriods();
    if (periods.length === 0) { layer.msg('请至少填写一节课的时间', { icon: 2 }); return; }
    for (var i = 0; i < periods.length; i++) {
        if (!periods[i].start || !periods[i].end) {
            layer.msg('第 ' + (i + 1) + ' 节的开始/结束时间未填写完整', { icon: 2 });
            return;
        }
    }

    var ii = layer.load(2, { shade: [0.1, '#fff'] });
    $.ajax({
        type: 'POST',
        url: 'Ajax.php?act=save_schedule_mode',
        dataType: 'json',
        data: {
            id: $('#modeId').val(),
            mode_name: name,
            mode_id: $.trim($('#modeKbjcmsid').val() || ''),
            sort_order: $('#modeSort').val() || 0,
            is_default: $('#modeIsDefault').is(':checked') ? 1 : 0,
            periods: JSON.stringify(periods)
        },
        success: function (res) {
            layer.close(ii);
            if (res.code == 0) {
                layer.msg(res.msg, { icon: 1, time: 1200 }, function () { location.reload(); });
            } else {
                layer.msg(res.msg || '保存失败', { icon: 2 });
            }
        },
        error: function () {
            layer.close(ii);
            layer.msg('网络错误，请稍后重试', { icon: 2 });
        }
    });
}

function setDefaultMode(id) {
    var m = findMode(id);
    if (!m) return;
    layer.confirm('将「' + escHtml(m.mode_name) + '」设为默认模式？未匹配到课程模式时将使用它的作息时间。',
        { btn: ['确定', '取消'], icon: 3 }, function () {
            var ii = layer.load(2, { shade: [0.1, '#fff'] });
            $.ajax({
                type: 'POST',
                url: 'Ajax.php?act=set_default_schedule_mode',
                dataType: 'json',
                data: { id: id },
                success: function (res) {
                    layer.close(ii);
                    if (res.code == 0) {
                        layer.msg(res.msg, { icon: 1, time: 1200 }, function () { location.reload(); });
                    } else {
                        layer.msg(res.msg || '操作失败', { icon: 2 });
                    }
                },
                error: function () {
                    layer.close(ii);
                    layer.msg('网络错误，请稍后重试', { icon: 2 });
                }
            });
        });
}

function deleteMode(id) {
    var m = findMode(id);
    if (!m) return;
    layer.confirm('确定删除课程模式「' + escHtml(m.mode_name) + '」吗？删除后该模式将回落到默认作息，此操作不可恢复。',
        { btn: ['确定删除', '取消'], icon: 3 }, function () {
            var ii = layer.load(2, { shade: [0.1, '#fff'] });
            $.ajax({
                type: 'POST',
                url: 'Ajax.php?act=delete_schedule_mode',
                dataType: 'json',
                data: { id: id },
                success: function (res) {
                    layer.close(ii);
                    if (res.code == 0) {
                        layer.msg(res.msg, { icon: 1, time: 1200 }, function () { location.reload(); });
                    } else {
                        layer.msg(res.msg || '删除失败', { icon: 2 });
                    }
                },
                error: function () {
                    layer.close(ii);
                    layer.msg('网络错误，请稍后重试', { icon: 2 });
                }
            });
        });
}
</script>

<style>
    /* ===== 节次时间表格：移动端改为卡片式堆叠，避免横向滚动与输入框过窄 ===== */
    @media (max-width: 767.98px) {
        #periodTable { border: 0; }
        #periodTable thead { display: none; }
        #periodTable tbody,
        #periodTable tr,
        #periodTable td { display: block; width: 100%; }

        #periodTable tr {
            border: 1px solid #e5e7f0;
            border-radius: 10px;
            background: #fff;
            padding: 12px 14px;
            margin-bottom: 12px;
        }
        #periodTable td {
            border: 0 !important;
            padding: 0 0 10px;
        }
        /* 每个字段用小标题代替表头，避免依赖横向表头 */
        #periodTable td[data-label]::before {
            content: attr(data-label);
            display: block;
            font-size: 12px;
            color: #948eb3;
            margin-bottom: 4px;
        }
        #periodTable td.period-actions {
            padding-bottom: 0;
            text-align: right !important;
        }
        /* 「增加一节 / 恢复内置默认」在移动端等宽铺满 */
        #periodActions { display: flex; }
        #periodActions .btn { flex: 1 1 0; }
    }

    /* 暗黑模式下的卡片配色 */
    @media (max-width: 767.98px) {
        #page-container.dark-mode #periodTable tr {
            background: #1e293b;
            border-color: #334155;
        }
        #page-container.dark-mode #periodTable td[data-label]::before {
            color: #94a3b8;
        }
    }
</style>

<?php include 'foot.php'; ?>
