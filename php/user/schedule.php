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

$timeSlots = [
    ['name' => '第1节', 'time' => '08:15-08:55'],
    ['name' => '第2节', 'time' => '09:05-09:45'],
    ['name' => '第3节', 'time' => '10:05-10:45'],
    ['name' => '第4节', 'time' => '10:55-11:35'],
    ['name' => '第5节', 'time' => '14:30-15:10'],
    ['name' => '第6节', 'time' => '15:20-16:00'],
    ['name' => '第7节', 'time' => '16:15-16:55'],
    ['name' => '第8节', 'time' => '18:50-19:30'],
    ['name' => '第9节', 'time' => '19:40-20:20'],
    ['name' => '第10节', 'time' => '20:30-21:10'],
    ['name' => '第11节', 'time' => '21:20-22:00'],
];

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

function parseClassTime($classTime, $weekNoteDetail = '') {
    $sections = [];
    
    if (!empty($weekNoteDetail)) {
        $parts = explode(',', $weekNoteDetail);
        foreach ($parts as $part) {
            $part = trim($part);
            if (strlen($part) >= 2) {
                $num = intval(substr($part, -2));
                if ($num >= 1 && $num <= 11) {
                    $sections[] = $num - 1;
                }
            }
        }
        if (!empty($sections)) {
            return array_unique($sections);
        }
    }
    
    if (empty($classTime)) return $sections;
    
    preg_match_all('/10\d|0\d|\d+/', $classTime, $matches);
    
    if (!empty($matches[0])) {
        foreach ($matches[0] as $m) {
            $num = intval($m);
            if ($num >= 1 && $num <= 11) {
                $sections[] = $num - 1;
            }
        }
    }
    
    return array_unique($sections);
}

$grid = [];
for ($i = 0; $i < 11; $i++) {
    $grid[$i] = [];
    for ($d = 0; $d <= 7; $d++) {
        $grid[$i][$d] = [];
    }
}

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
        $sections = parseClassTime($classTime, $weekNoteDetail);
        
        if (!empty($sections)) {
            foreach ($sections as $s) {
                $grid[$s][$day][] = $course;
            }
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
                    <div class="col-md-4 text-md-end">
                        <span class="text-muted">
                            <i class="fa fa-user me-1"></i>
                            学工号：<?php echo htmlspecialchars($academicAccount['student_id']); ?>
                        </span>
                    </div>
                </div>
                
                <?php if($schedule !== null): ?>
                    <?php 
                    $dates = isset($schedule['date']) ? $schedule['date'] : [];
                    $courses = isset($schedule['courses']) ? $schedule['courses'] : [];
                    ?>
                    
                    <?php if(!empty($dates)): ?>
                    <div class="alert alert-info mb-3">
                        <i class="fa fa-calendar me-2"></i>
                        <?php if(!empty($currentWeek)): ?>
                        <strong class="me-3">第<?php echo htmlspecialchars($currentWeek); ?>周</strong>
                        <?php endif; ?>
                        <?php foreach($dates as $d): ?>
                            <span class="me-2"><?php echo htmlspecialchars($d['xqmc'] ?? ''); ?> <?php echo htmlspecialchars($d['mxrq'] ?? ''); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($courses)): ?>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered" style="table-layout: fixed;">
                            <thead>
                                <tr class="bg-body-light">
                                    <th class="text-center align-middle" style="width: 70px;">节次</th>
                                    <?php foreach($days as $day): ?>
                                    <th class="text-center"><?php echo $day; ?></th>
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
                                        <div class="<?php echo $colorClass; ?> text-white rounded p-1 mb-1" style="font-size: 11px;">
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
                    
                    <div class="mobile-schedule">
                        <?php for($day = 1; $day <= 7; $day++): ?>
                        <?php 
                        $dayCourses = [];
                        for ($row = 0; $row < 11; $row++) {
                            $cellCourses = $grid[$row][$day] ?? [];
                            foreach ($cellCourses as $course) {
                                $key = $course['courseName'] ?? '';
                                if (!isset($dayCourses[$key])) {
                                    $dayCourses[$key] = $course;
                                }
                            }
                        }
                        ?>
                        <?php if(!empty($dayCourses)): ?>
                        <div class="mobile-day-section">
                            <div class="mobile-day-header">
                                <i class="fa fa-calendar-day me-2"></i><?php echo $days[$day-1]; ?>
                            </div>
                            <?php foreach($dayCourses as $course): ?>
                            <?php 
                            $courseName = $course['courseName'] ?? '';
                            $location = $course['location'] ?? '';
                            $teacher = $course['teacherName'] ?? $course['teacher'] ?? '';
                            $colorIndex = getCourseColorIndex($courseName);
                            $colorClass = isset($courseColors[$colorIndex]) ? $courseColors[$colorIndex] : 'bg-primary';
                            ?>
                            <div class="mobile-course-item">
                                <div class="mobile-course-name <?php echo str_replace('bg-', 'text-', $colorClass); ?>">
                                    <?php echo htmlspecialchars($courseName); ?>
                                </div>
                                <?php if($location): ?>
                                <div class="mobile-course-info">
                                    <i class="fa fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($location); ?>
                                </div>
                                <?php endif; ?>
                                <?php if(!empty($teacher)): ?>
                                <div class="mobile-course-info">
                                    <i class="fa fa-user me-1"></i><?php echo htmlspecialchars($teacher); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <?php endfor; ?>
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

@media (max-width: 768px) {
    .table-responsive {
        display: none;
    }
    .mobile-schedule {
        display: block !important;
    }
    .mobile-day-section {
        margin-bottom: 1rem;
    }
    .mobile-day-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.5rem 1rem;
        font-weight: bold;
        border-radius: 0.375rem 0.375rem 0 0;
    }
    .mobile-course-item {
        border: 1px solid #e4e7ed;
        border-top: none;
        padding: 0.75rem;
        background: white;
    }
    .mobile-course-item:last-child {
        border-radius: 0 0 0.375rem 0.375rem;
    }
    .mobile-course-name {
        font-weight: bold;
        margin-bottom: 0.25rem;
    }
    .mobile-course-info {
        font-size: 12px;
        color: #6c757d;
    }
    .mobile-course-info i {
        width: 14px;
    }
    .form-select {
        font-size: 14px;
    }
}
@media (min-width: 769px) {
    .mobile-schedule {
        display: none !important;
    }
}
</style>

<?php include 'foot.php'; ?>
