<?php
$title = '课程查询';
$requireLogin = true;
include('./head.php');
require_once('../funsion/feature_settings.php');

$userId = $_SESSION['userId'];

if (!isFeatureEnabled($DB, 'schedule')) {
    echo '<div id="pjax-container"><div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-content block-content-full">';
    checkFeatureAccess($DB, 'schedule');
    echo '</div></div></div>';
    include('./foot.php');
    exit;
}

$academicAccount = $DB->getRow("SELECT * FROM nteam_academic_accounts WHERE user_id='$userId'");

if (!$academicAccount) {
    echo '<div id="pjax-container"><div class="content animated fadeIn">
        <div class="alert alert-warning">
            <h4><i class="fa fa-exclamation-triangle me-2"></i>未绑定教务账号</h4>
            <p>请先绑定教务账号后再使用课程查询功能。</p>
            <a href="academic_bind.php" class="btn btn-alt-primary mt-2">
                <i class="fa fa-link me-1"></i>立即绑定
            </a>
        </div>
    </div></div>';
    include 'foot.php';
    exit;
}

require_once('../funsion/AcademicSystem.php');
require_once('../funsion/ScheduleTime.php');

$week = isset($_GET['week']) ? $_GET['week'] : '';
$kbjcmsid = isset($_GET['kbjcmsid']) ? $_GET['kbjcmsid'] : '';
$schedule = null;
$error = null;
$modes = [];
$token = null;
$currentWeek = '';

$tokenResult = AcademicSystem::getValidToken($DB, $userId, $academicAccount['student_id'], $academicAccount['password']);

if ($tokenResult['success']) {
    $token = $tokenResult['token'];
    
    $modeResult = AcademicSystem::getScheduleMode($token);
    if (isset($modeResult['code']) && $modeResult['code'] == 1) {
        $modes = $modeResult['data'];
    }
    
    $queryWeek = $week;
    $scheduleResult = AcademicSystem::getSchedule($token, $queryWeek, $kbjcmsid);
        
        if (isset($scheduleResult['code']) && $scheduleResult['code'] == 1) {
            if (isset($scheduleResult['data']) && is_array($scheduleResult['data']) && !empty($scheduleResult['data'])) {
                $schedule = $scheduleResult['data'][0];
                if (!empty($schedule['date'])) {
                    $firstDate = $schedule['date'][0] ?? [];
                    $currentWeek = intval($firstDate['zc'] ?? 0);
                }
            } else {
                $error = '课程数据为空';
            }
        } else {
            $error = $scheduleResult['msg'] ?? $scheduleResult['Msg'] ?? '获取课程失败';
        }
} else {
    $error = $tokenResult['msg'] ?? '登录失败，请检查学工号和密码是否正确';
}

$days = ['周一', '周二', '周三', '周四', '周五', '周六', '周日'];

// 节次时间改为后台「课程时间配置」按课程模式维护（教务接口只返回节数，不返回具体时间）
// 行数取「后台配置节次数 / 数据中实际最大节次 / 默认 11 节」的最大值，保证第 12 节及以后的课程也能展示
$maxSection = scheduleTimeMaxPeriods();
$timeSlots = scheduleTimePeriodsToList(scheduleTimeGetPeriods($DB, $kbjcmsid));

$courseColors = ['bg-primary', 'bg-success', 'bg-info', 'bg-warning', 'bg-danger', 'bg-purple', 'bg-pink', 'bg-indigo', 'bg-teal', 'bg-orange'];

function getCourseColorIndex($courseName) {
    if (empty($courseName)) return 0;
    $hash = 0;
    for ($i = 0; $i < strlen($courseName); $i++) {
        $hash = ord($courseName[$i]) + (($hash << 5) - $hash);
        $hash = $hash & 0x7FFFFFFF;
    }
    return abs($hash) % 10;
}

/**
 * 解析课程节次，返回 0 基索引数组
 * 兼容三种来源：weekNoteDetail（"101,102" / "第1,2节"）、
 * classTime「1+两位节次」编码（105106 = 第5、6节，112 = 第12节）、
 * classTime「星期1位 + 两位节次」（20506 = 周二第5、6节）
 */
function parseClassTime($classTime, $weekNoteDetail = '', $maxSection = 20) {
    $sections = [];

    if (!empty($weekNoteDetail)) {
        $parts = explode(',', $weekNoteDetail);
        foreach ($parts as $part) {
            $part = trim($part);
            if (strlen($part) >= 2) {
                $num = intval(substr($part, -2));
                if ($num >= 1 && $num <= $maxSection) {
                    $sections[] = $num - 1;
                }
            }
        }
        if (!empty($sections)) {
            return array_unique($sections);
        }
    }

    if (empty($classTime)) return $sections;

    // 1) 「1 + 两位节次」编码（101=第1节 ... 112=第12节）
    for ($i = 1; $i <= $maxSection; $i++) {
        $code = '1' . sprintf('%02d', $i);
        if (strpos($classTime, $code) !== false) {
            $sections[] = $i - 1;
        }
    }
    if (!empty($sections)) {
        return array_unique($sections);
    }

    // 2) 「星期(1位) + 两位节次」（如 20506 = 周二第5、6节）
    $timeStr = $classTime;
    if (strlen($timeStr) > 2 && strlen($timeStr) % 2 === 1) {
        $timeStr = substr($timeStr, 1);
    }
    for ($i = 0; $i + 1 < strlen($timeStr); $i += 2) {
        $num = intval(substr($timeStr, $i, 2));
        if ($num >= 1 && $num <= $maxSection) {
            $sections[] = $num - 1;
        }
    }
    if (!empty($sections)) {
        return array_unique($sections);
    }

    // 3) 兜底：按数字片段解析（兼容单节次写法）
    preg_match_all('/\d+/', $classTime, $matches);
    foreach ($matches[0] as $m) {
        $num = intval($m);
        if ($num >= 1 && $num <= $maxSection) {
            $sections[] = $num - 1;
        }
    }

    return array_unique($sections);
}

// 先解析课程与所在节次，再按实际数据决定课程表行数
$parsedCourses = [];
$maxRowIndex = -1;
$aiCourses = [];
if ($schedule && !empty($schedule['courses'])) {
    $courses = $schedule['courses'];
    $courseMap = [];
    
    foreach ($courses as $course) {
        $key = ($course['weekDay'] ?? '') . '-' . ($course['classTime'] ?? '') . '-' . ($course['courseName'] ?? '');
        if (!isset($courseMap[$key])) {
            $courseMap[$key] = $course;
        }
    }
    
    foreach ($courseMap as $course) {
        $day = intval($course['weekDay'] ?? 0);
        if ($day < 1 || $day > 7) continue;
        
        $classTime = $course['classTime'] ?? '';
        $weekNoteDetail = $course['weekNoteDetail'] ?? '';
        $sections = parseClassTime($classTime, $weekNoteDetail, $maxSection);
        
        $parsedCourses[] = ['course' => $course, 'day' => $day, 'sections' => $sections];
        if (!empty($sections)) {
            $maxRowIndex = max($maxRowIndex, max($sections));
        }
        
        $aiCourses[] = [
            'name' => $course['courseName'] ?? '',
            'weekDay' => $course['weekDay'] ?? '',
            'section' => $course['classTime'] ?? '',
            'location' => $course['location'] ?? '',
            'teacher' => $course['teacherName'] ?? $course['teacher'] ?? '',
            'weeks' => $course['classWeek'] ?? '',
            'credit' => $course['credit'] ?? '',
            'nature' => $course['courseNature'] ?? '',
        ];
    }
}

// 行数 = max(后台配置节次数, 数据中最大节次 + 1, 11 节)，后台未配置的节次补占位行
$rowCount = max(count($timeSlots), $maxRowIndex + 1, 11);
for ($i = count($timeSlots); $i < $rowCount; $i++) {
    $timeSlots[] = ['name' => '第' . ($i + 1) . '节', 'time' => ''];
}

$grid = [];
for ($i = 0; $i < $rowCount; $i++) {
    $grid[$i] = [];
    for ($d = 0; $d <= 7; $d++) {
        $grid[$i][$d] = [];
    }
}

foreach ($parsedCourses as $item) {
    foreach ($item['sections'] as $s) {
        if (isset($grid[$s])) {
            $grid[$s][$item['day']][] = $item['course'];
        }
    }
}
?>

<div id="pjax-container">
    <div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-calendar-alt me-2"></i>课程查询
                </h3>
                <div class="block-options">
                    <button type="button" class="btn btn-alt-primary btn-sm" id="aiAnalyzeBtn" onclick="aiAnalyzeSchedule()">
                        <i class="fa fa-robot me-1"></i>AI 智能分析课表
                    </button>
                </div>
            </div>
            <div class="block-content block-content-full">
                <?php renderFeatureAnnouncement($DB, 'schedule'); ?>
                <?php if($error && !$schedule): ?>
                <div class="alert alert-danger mb-4">
                    <i class="fa fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <?php if($token): ?>
                <div class="row mb-4">
                    <div class="col-md-8">
                        <form method="GET" action="" class="row g-2">
                            <div class="col-auto">
                                <select name="week" class="form-select" onchange="this.form.submit()">
                                    <option value="">当前周</option>
                                    <?php for($i = 1; $i <= 20; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($week == $i || (empty($week) && $currentWeek == $i)) ? 'selected' : ''; ?>>
                                        第<?php echo $i; ?>周
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-auto">
                                <select name="kbjcmsid" class="form-select" onchange="this.form.submit()">
                                    <?php if(!empty($modes)): ?>
                                        <?php foreach($modes as $mode): ?>
                                        <option value="<?php echo htmlspecialchars($mode['kbjcmsid'] ?? ''); ?>" <?php echo $kbjcmsid == ($mode['kbjcmsid'] ?? '') ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($mode['kbjcmsmc'] ?? ''); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                    <option value="">默认</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-auto">
                                <a href="?week=<?php echo $week; ?>&kbjcmsid=<?php echo $kbjcmsid; ?>" class="btn btn-alt-secondary">
                                    <i class="fa fa-redo me-1"></i>刷新
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php if($schedule !== null): ?>
                    <?php 
                    $dates = isset($schedule['date']) ? $schedule['date'] : [];
                    $courses = isset($schedule['courses']) ? $schedule['courses'] : [];
                    $dateByXq = [];
                    foreach ($dates as $d) {
                        $xq = trim($d['xqmc'] ?? '');
                        if ($xq !== '') $dateByXq[$xq] = $d['mxrq'] ?? '';
                    }
                    ?>
                    
                    <?php if(!empty($courses)): ?>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0 schedule-table" style="table-layout: fixed;">
                            <thead>
                                <tr class="bg-body-light">
                                    <th class="text-center align-middle slot-col" style="width: 70px;">节次</th>
                                    <?php foreach($days as $day): ?>
                                    <?php $xq = mb_substr($day, 1, 1, 'UTF-8'); ?>
                                    <th class="text-center" style="width: 90px;">
                                        <?php echo $day; ?>
                                        <div class="text-muted" style="font-weight: normal; font-size: 10px; line-height: 1.2;"><?php echo htmlspecialchars($dateByXq[$xq] ?? ''); ?></div>
                                    </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($timeSlots as $rowIndex => $slot): ?>
                                <tr style="height: 60px;">
                                    <td class="text-center align-middle bg-body-light">
                                        <div class="fw-semibold"><?php echo $slot['name']; ?></div>
                                        <small class="text-muted" style="font-size: 10px;"><?php echo $slot['time']; ?></small>
                                    </td>
                                    <?php for($day = 1; $day <= 7; $day++): ?>
                                    <td class="p-1 align-top">
                                        <?php
                                        $cellCourses = $grid[$rowIndex][$day] ?? [];
                                        if (!empty($cellCourses)) {
                                            foreach ($cellCourses as $course) {
                                                $courseName = $course['courseName'] ?? '';
                                                $location = $course['location'] ?? '';
                                                $teacher = $course['teacherName'] ?? $course['teacher'] ?? '';
                                                $colorIndex = getCourseColorIndex($courseName);
                                                $colorClass = isset($courseColors[$colorIndex]) ? $courseColors[$colorIndex] : 'bg-primary';
                                        ?>
                                        <div class="<?php echo $colorClass; ?> text-white rounded p-1 mb-1 course-card" style="font-size: 11px; cursor: pointer;" role="button"
                                             data-bs-toggle="modal" data-bs-target="#courseModal"
                                             data-name="<?php echo htmlspecialchars($courseName); ?>"
                                             data-location="<?php echo htmlspecialchars($location); ?>"
                                             data-teacher="<?php echo htmlspecialchars($teacher); ?>"
                                             data-time="<?php echo htmlspecialchars(($slot['name'] ?? '') . ' ' . ($slot['time'] ?? '')); ?>"
                                             data-day="<?php echo htmlspecialchars($days[$day - 1] ?? ''); ?>"
                                             data-date="<?php echo htmlspecialchars($dateByXq[mb_substr($days[$day - 1], 1, 1, 'UTF-8')] ?? ''); ?>"
                                             data-week="<?php echo htmlspecialchars($currentWeek); ?>">
                                            <div class="fw-bold text-truncate" title="<?php echo htmlspecialchars($courseName); ?>">
                                                <?php echo htmlspecialchars($courseName); ?>
                                            </div>
                                            <?php if($location): ?>
                                            <div class="text-truncate opacity-75" style="font-size: 10px;">
                                                <i class="fa fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($location); ?>
                                            </div>
                                            <?php endif; ?>
                                            <?php if(!empty($teacher)): ?>
                                            <div class="text-truncate opacity-75" style="font-size: 10px;">
                                                <i class="fa fa-user me-1"></i><?php echo htmlspecialchars($teacher); ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php 
                                            }
                                        } 
                                        ?>
                                    </td>
                                    <?php endfor; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle me-2"></i>暂无课程数据
                    </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="fa fa-info-circle me-2"></i>请选择周次和节次模式后点击"查询"按钮获取课程表
                </div>
                <?php endif; ?>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 右侧浮动 AI 分析按钮（与 uniapp 端一致） -->
<div class="ai-fab" title="AI 智能分析课表" onclick="aiAnalyzeSchedule()">
    <span class="ai-fab-icon">AI</span>
    <span class="ai-fab-label">分析</span>
</div>

<!-- AI 智能分析课表 弹窗 -->
<div class="modal fade" id="aiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-robot me-1"></i>AI 智能课表分析</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
            </div>
            <div class="modal-body">
                <pre id="aiResult" style="white-space: pre-wrap; word-break: break-word; font-family: inherit; font-size: 14px; margin: 0;"></pre>
            </div>
        </div>
    </div>
</div>

<!-- 课程详情 弹窗 -->
<div class="modal fade" id="courseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-book me-1"></i>课程详情</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
            </div>
            <div class="modal-body" id="courseModalBody"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
window.__scheduleData = {
    week: <?php echo json_encode($currentWeek); ?>,
    courses: <?php echo json_encode($aiCourses, JSON_UNESCAPED_UNICODE); ?>
};
function aiAnalyzeSchedule() {
    AiAnalyze.run({
        url: 'ajax.php?act=ai_analyze_schedule',
        modalId: 'aiModal',
        resultId: 'aiResult',
        fabSel: '.ai-fab',
        payload: function () {
            return {
                data: JSON.stringify({
                    type: 'semester',
                    week: window.__scheduleData.week,
                    courses: window.__scheduleData.courses
                })
            };
        },
        cacheKey: function () { return 'schedule_semester:' + JSON.stringify(window.__scheduleData); },
        validate: function () {
            if (!window.__scheduleData || !window.__scheduleData.courses || !window.__scheduleData.courses.length) {
                alert('暂无可分析的课程数据');
                return false;
            }
            return true;
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    var courseModalEl = document.getElementById('courseModal');
    if (!courseModalEl) return;
    courseModalEl.addEventListener('show.bs.modal', function(e) {
        var t = e.relatedTarget;
        if (!t) return;
        var week = t.getAttribute('data-week');
        var rows = [
            ['课程名称', t.getAttribute('data-name')],
            ['上课时间', (t.getAttribute('data-day') || '') + ' ' + (t.getAttribute('data-time') || '')],
            ['上课地点', t.getAttribute('data-location')],
            ['授课教师', t.getAttribute('data-teacher')],
            ['日期', t.getAttribute('data-date')],
            ['周次', week ? ('第' + week + '周') : '']
        ];
        var html = '<div class="mb-3"><h4 class="h5 mb-1">' + escapeHtml(t.getAttribute('data-name')) + '</h4></div>';
        html += '<dl class="row mb-0">';
        rows.forEach(function(r) {
            var val = r[1];
            if (val === null || val === '') val = '<span class="text-muted">—</span>';
            else val = escapeHtml(val);
            html += '<dt class="col-sm-4 text-muted">' + r[0] + '</dt><dd class="col-sm-8">' + val + '</dd>';
        });
        html += '</dl>';
        document.getElementById('courseModalBody').innerHTML = html;
    });
    function escapeHtml(s) {
        if (s === null || s === undefined) return '';
        return String(s).replace(/[&<>"']/g, function(c) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
        });
    }
});
</script>

<style>
.table td {
    vertical-align: top;
}
.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.bg-purple { background-color: #6f42c1 !important; }
.bg-pink { background-color: #d63384 !important; }
.bg-indigo { background-color: #6610f2 !important; }
.bg-teal { background-color: #20c997 !important; }
.bg-orange { background-color: #fd7e14 !important; }

/* 移动端：保留课程表表格，横向滚动查看，紧凑显示 */
@media (max-width: 991.98px) {
    .schedule-table {
        min-width: 640px;
        font-size: 11px;
    }
    .schedule-table th,
    .schedule-table td {
        padding: 4px 3px !important;
    }
    .schedule-table .slot-col {
        width: 52px !important;
    }
    .schedule-table thead th:not(.slot-col) {
        width: 84px !important;
    }
    .form-select {
        font-size: 14px;
    }
}
</style>

<?php include 'foot.php'; ?>
