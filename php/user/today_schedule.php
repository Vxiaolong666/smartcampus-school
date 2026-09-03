<?php
$title = '本周课程';
$requireLogin = true;
include('./head.php');
require_once('../funsion/feature_settings.php');

$userId = $_SESSION['userId'];

if (!isFeatureEnabled($DB, 'today_schedule')) {
    echo '<div id="pjax-container"><div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-content block-content-full">';
    checkFeatureAccess($DB, 'today_schedule');
    echo '</div></div></div>';
    include('./foot.php');
    exit;
}

$academicAccount = $DB->getRow("SELECT * FROM nteam_academic_accounts WHERE user_id='$userId'");

if (!$academicAccount) {
    echo '<div id="pjax-container"><div class="content animated fadeIn">
        <div class="alert alert-warning">
            <h4><i class="fa fa-exclamation-triangle me-2"></i>未绑定教务账号</h4>
            <p>请先绑定教务账号后再使用本周课程功能。</p>
            <a href="academic_bind.php" class="btn btn-alt-primary mt-2">
                <i class="fa fa-link me-1"></i>立即绑定
            </a>
        </div>
    </div></div>';
    include 'foot.php';
    exit;
}

require_once('../funsion/AcademicSystem.php');

$error = null;
$dayCourses = [];
$token = null;
$currentWeek = 1;
$semesterStartDate = null;

$dayNames = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];
$dayNamesFull = ['星期日', '星期一', '星期二', '星期三', '星期四', '星期五', '星期六'];

$selectDay = isset($_GET['day']) ? intval($_GET['day']) : -1;
$todayDayOfWeek = date('w');

if ($selectDay < 0 || $selectDay > 6) {
    $selectDay = $todayDayOfWeek;
}

$weekStart = new DateTime('this week');
$weekDates = [];
for ($i = 0; $i < 7; $i++) {
    $date = clone $weekStart;
    $date->modify("+$i day");
    $weekDates[$i] = $date->format('Y-m-d');
}

$selectedDate = $weekDates[$selectDay];
$selectedDateDisplay = date('Y年m月d日', strtotime($selectedDate));

$tokenResult = AcademicSystem::getValidToken($DB, $userId, $academicAccount['student_id'], $academicAccount['password']);

if ($tokenResult['success']) {
    $token = $tokenResult['token'];
    
    $scheduleResult = AcademicSystem::getSchedule($token, '', '');
    
    if (isset($scheduleResult['code']) && $scheduleResult['code'] == 1) {
        if (isset($scheduleResult['data']) && is_array($scheduleResult['data']) && !empty($scheduleResult['data'])) {
            $schedule = $scheduleResult['data'][0];
            $courses = $schedule['courses'] ?? [];
            
            if (!empty($schedule['date'])) {
                $firstDate = $schedule['date'][0] ?? [];
                $apiWeek = intval($firstDate['zc'] ?? 0);
                if ($apiWeek > 0) {
                    $currentWeek = $apiWeek;
                }
            }
            
            $targetDay = $selectDay == 0 ? 7 : $selectDay;
            
            foreach ($courses as $course) {
                $courseDay = intval($course['weekDay'] ?? 0);
                $classWeekDetails = $course['classWeekDetails'] ?? '';
                $weeks = $course['classWeek'] ?? $course['weeks'] ?? '';
                
                if ($courseDay == $targetDay) {
                    $inWeek = false;
                    if (!empty($classWeekDetails)) {
                        $inWeek = strpos($classWeekDetails, ',' . $currentWeek . ',') !== false;
                    } elseif (!empty($weeks)) {
                        if (preg_match('/^(\d+)-(\d+)$/', $weeks, $matches)) {
                            $startWeek = intval($matches[1]);
                            $endWeek = intval($matches[2]);
                            $inWeek = ($currentWeek >= $startWeek && $currentWeek <= $endWeek);
                        } else {
                            $inWeek = strpos($weeks, (string)$currentWeek) !== false;
                        }
                    } else {
                        $inWeek = true;
                    }
                    
                    if ($inWeek) {
                        $dayCourses[] = $course;
                    }
                }
            }
            
            usort($dayCourses, function($a, $b) {
                $timeA = $a['classTime'] ?? '999';
                $timeB = $b['classTime'] ?? '999';
                return strcmp($timeA, $timeB);
            });
        }
    } else {
        $error = $scheduleResult['msg'] ?? $scheduleResult['Msg'] ?? '获取课程失败';
    }
} else {
    $error = $tokenResult['msg'] ?? '登录失败，请检查学工号和密码是否正确';
}

$timeSlots = [
    '101' => ['name' => '第1节', 'time' => '08:15-08:55'],
    '102' => ['name' => '第2节', 'time' => '09:05-09:45'],
    '103' => ['name' => '第3节', 'time' => '10:05-10:45'],
    '104' => ['name' => '第4节', 'time' => '10:55-11:35'],
    '105' => ['name' => '第5节', 'time' => '14:30-15:10'],
    '106' => ['name' => '第6节', 'time' => '15:20-16:00'],
    '107' => ['name' => '第7节', 'time' => '16:15-16:55'],
    '108' => ['name' => '第8节', 'time' => '18:50-19:30'],
    '109' => ['name' => '第9节', 'time' => '19:40-20:20'],
    '110' => ['name' => '第10节', 'time' => '20:30-21:10'],
    '111' => ['name' => '第11节', 'time' => '21:20-22:00'],
];

function getCourseTimeSlots($classTime, $weekNoteDetail = '') {
    $slots = [];
    
    if (!empty($weekNoteDetail)) {
        $parts = explode(',', $weekNoteDetail);
        foreach ($parts as $part) {
            $part = trim($part);
            if (strlen($part) >= 2) {
                $num = intval(substr($part, -2));
                if ($num >= 1 && $num <= 11) {
                    $slots[] = $num;
                }
            }
        }
        if (!empty($slots)) {
            return array_unique($slots);
        }
    }
    
    for ($i = 1; $i <= 11; $i++) {
        $code = '1' . sprintf('%02d', $i);
        if (strpos($classTime, $code) !== false) {
            $slots[] = $i;
        }
    }
    
    return array_unique($slots);
}

$currentTime = date('H:i');
$currentSection = null;
$currentCourse = null;
$inBreak = false;
$nextCourse = null;
$firstCourse = null;
$lastCourse = null;
$allCoursesEnded = false;
$notStarted = false;
$minutesToNext = 0;
$minutesToEnd = 0;

foreach ($timeSlots as $code => $slot) {
    $times = explode('-', $slot['time']);
    if (count($times) == 2) {
        $startTime = $times[0];
        $endTime = $times[1];
        if ($currentTime >= $startTime && $currentTime <= $endTime) {
            $currentSection = $code;
            break;
        }
    }
}

$isToday = ($selectDay == $todayDayOfWeek);

if (!empty($dayCourses)) {
    $allFirstSlots = [];
    $allLastSlots = [];
    
    foreach ($dayCourses as $course) {
        $classTime = $course['classTime'] ?? '';
        $weekNoteDetail = $course['weekNoteDetail'] ?? '';
        $slots = getCourseTimeSlots($classTime, $weekNoteDetail);
        
        if (!empty($slots)) {
            $allFirstSlots[] = ['slot' => min($slots), 'course' => $course];
            $allLastSlots[] = ['slot' => max($slots), 'course' => $course];
        }
        
        foreach ($slots as $slot) {
            $code = '1' . sprintf('%02d', $slot);
            if ($code == $currentSection) {
                $currentCourse = $course;
                break 2;
            }
        }
    }
    
    if (!empty($allFirstSlots)) {
        usort($allFirstSlots, function($a, $b) {
            return $a['slot'] - $b['slot'];
        });
        $firstCourse = $allFirstSlots[0]['course'];
        $firstSlotNum = $allFirstSlots[0]['slot'];
        $firstCode = '1' . sprintf('%02d', $firstSlotNum);
        $firstStartTime = explode('-', $timeSlots[$firstCode]['time'] ?? '00:00-00:00')[0];
    }
    
    if (!empty($allLastSlots)) {
        usort($allLastSlots, function($a, $b) {
            return $b['slot'] - $a['slot'];
        });
        $lastCourse = $allLastSlots[0]['course'];
        $lastSlotNum = $allLastSlots[0]['slot'];
        $lastCode = '1' . sprintf('%02d', $lastSlotNum);
        $lastEndTime = explode('-', $timeSlots[$lastCode]['time'] ?? '00:00-00:00')[1];
    }
    
    if ($isToday && isset($lastEndTime)) {
        if ($currentTime > $lastEndTime) {
            $allCoursesEnded = true;
        }
    }
    
    if ($isToday && isset($firstStartTime)) {
        if ($currentTime < $firstStartTime) {
            $notStarted = true;
            $currentParts = explode(':', $currentTime);
            $firstParts = explode(':', $firstStartTime);
            $currentMinutes = intval($currentParts[0]) * 60 + intval($currentParts[1]);
            $firstMinutes = intval($firstParts[0]) * 60 + intval($firstParts[1]);
            $minutesToNext = $firstMinutes - $currentMinutes;
        }
    }
    
    if (!$currentCourse && $currentSection) {
        $currentSlotNum = intval(substr($currentSection, 1));
        foreach ($dayCourses as $course) {
            $classTime = $course['classTime'] ?? '';
            $weekNoteDetail = $course['weekNoteDetail'] ?? '';
            $slots = getCourseTimeSlots($classTime, $weekNoteDetail);
            
            if (!empty($slots)) {
                $firstSlot = min($slots);
                if ($firstSlot > $currentSlotNum) {
                    if (!$nextCourse || $firstSlot < getCourseTimeSlots($nextCourse['classTime'] ?? '', $nextCourse['weekNoteDetail'] ?? '')[0]) {
                        $nextCourse = $course;
                    }
                }
            }
        }
        $inBreak = true;
        
        if ($nextCourse && !$allCoursesEnded) {
            $ncClassTime = $nextCourse['classTime'] ?? '';
            $ncWeekNoteDetail = $nextCourse['weekNoteDetail'] ?? '';
            $ncSlots = getCourseTimeSlots($ncClassTime, $ncWeekNoteDetail);
            if (!empty($ncSlots)) {
                $ncFirstSlot = min($ncSlots);
                $ncFirstCode = '1' . sprintf('%02d', $ncFirstSlot);
                $ncFirstTime = $timeSlots[$ncFirstCode]['time'] ?? '';
                $ncFirstParts = explode('-', $ncFirstTime);
                if (count($ncFirstParts) == 2) {
                    $ncStartTime = $ncFirstParts[0];
                    $currentParts = explode(':', $currentTime);
                    $ncParts = explode(':', $ncStartTime);
                    $currentMinutes = intval($currentParts[0]) * 60 + intval($currentParts[1]);
                    $ncMinutes = intval($ncParts[0]) * 60 + intval($ncParts[1]);
                    $minutesToNext = $ncMinutes - $currentMinutes;
                }
            }
        }
    }
    
    if ($currentCourse) {
        $ccClassTime = $currentCourse['classTime'] ?? '';
        $ccWeekNoteDetail = $currentCourse['weekNoteDetail'] ?? '';
        $ccSlots = getCourseTimeSlots($ccClassTime, $ccWeekNoteDetail);
        if (!empty($ccSlots)) {
            $ccLastSlot = max($ccSlots);
            $ccLastCode = '1' . sprintf('%02d', $ccLastSlot);
            $ccLastTime = $timeSlots[$ccLastCode]['time'] ?? '';
            $ccLastParts = explode('-', $ccLastTime);
            if (count($ccLastParts) == 2) {
                $ccEndTime = $ccLastParts[1];
                $currentParts = explode(':', $currentTime);
                $ccParts = explode(':', $ccEndTime);
                $currentMinutes = intval($currentParts[0]) * 60 + intval($currentParts[1]);
                $ccMinutes = intval($ccParts[0]) * 60 + intval($ccParts[1]);
                $minutesToEnd = $ccMinutes - $currentMinutes;
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
                    <i class="fa fa-calendar-day me-2"></i>本周课程
                </h3>
                <div class="block-options">
                    <a href="schedule.php" class="btn btn-sm btn-alt-secondary">
                        <i class="fa fa-calendar-alt me-1"></i>完整课表
                    </a>
                </div>
            </div>
            <div class="block-content block-content-full">
                <?php renderFeatureAnnouncement($DB, 'today_schedule'); ?>
                <?php if($error): ?>
                <div class="alert alert-danger mb-4">
                    <i class="fa fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex flex-wrap gap-2 justify-content-center">
                            <?php for($i = 0; $i < 7; $i++): ?>
                            <?php 
                                $isCurrentDay = ($i == $selectDay);
                                $isTodayBtn = ($i == $todayDayOfWeek);
                                $btnClass = $isCurrentDay ? 'btn-primary' : ($isTodayBtn ? 'btn-outline-primary' : 'btn-outline-secondary');
                            ?>
                            <a href="?day=<?php echo $i; ?>" class="btn <?php echo $btnClass; ?>">
                                <div class="text-center">
                                    <div style="font-size: 12px;"><?php echo $dayNames[$i]; ?></div>
                                    <div style="font-size: 14px; font-weight: bold;"><?php echo date('d', strtotime($weekDates[$i])); ?></div>
                                    <?php if($isTodayBtn): ?>
                                    <div style="font-size: 10px;">今天</div>
                                    <?php endif; ?>
                                </div>
                            </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="alert alert-info d-flex align-items-center mb-0">
                            <i class="fa fa-calendar me-3 fa-2x"></i>
                            <div>
                                <div class="fw-bold fs-5"><?php echo $dayNamesFull[$selectDay]; ?><?php echo $isToday ? ' (今天)' : ''; ?></div>
                                <div class="text-muted">
                                    <?php echo $selectedDateDisplay; ?> · 第<?php echo $currentWeek; ?>周
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-secondary d-flex align-items-center mb-0">
                            <i class="fa fa-clock me-3 fa-2x"></i>
                            <div>
                                <div class="fw-bold fs-5" id="currentTime"><?php echo $currentTime; ?></div>
                                <div class="text-muted" id="currentStatus">
                                    <?php if($allCoursesEnded && $isToday): ?>
                                        <span class="text-success"><i class="fa fa-check-circle me-1"></i>今日的课上完了</span>
                                    <?php elseif($notStarted && $isToday): ?>
                                        <span class="text-info"><i class="fa fa-sun me-1"></i>课程未开始，还有<?php echo $minutesToNext; ?>分钟上课</span>
                                    <?php elseif($currentCourse): ?>
                                        <span class="text-success"><i class="fa fa-book me-1"></i>正在上: <?php echo htmlspecialchars($currentCourse['courseName'] ?? ''); ?><?php if($minutesToEnd > 0): ?>，还有<?php echo $minutesToEnd; ?>分钟下课<?php endif; ?></span>
                                    <?php elseif($inBreak && $nextCourse): ?>
                                        <span class="text-warning"><i class="fa fa-coffee me-1"></i>课间休息 · 下节: <?php echo htmlspecialchars($nextCourse['courseName'] ?? ''); ?><?php if($minutesToNext > 0): ?>，还有<?php echo $minutesToNext; ?>分钟上课<?php endif; ?></span>
                                    <?php elseif($inBreak): ?>
                                        <span class="text-info"><i class="fa fa-coffee me-1"></i>课间休息</span>
                                    <?php else: ?>
                                        <span class="text-muted"><?php echo $isToday ? '课余时间' : '非今日'; ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if($testMode): ?>
                                <div class="small text-warning mt-1"><i class="fa fa-flask me-1"></i>测试模式</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if($allCoursesEnded && $isToday): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-success">
                            <div class="card-body text-center py-4">
                                <i class="fa fa-check-circle text-success fa-3x mb-3"></i>
                                <h5 class="text-success mb-2">今日的课上完了</h5>
                                <p class="text-muted mb-0">共<?php echo count($dayCourses); ?>节课，辛苦了！好好休息吧~</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php elseif($notStarted && $isToday && $firstCourse): ?>
                <?php
                    $fcName = $firstCourse['courseName'] ?? '';
                    $fcLocation = $firstCourse['location'] ?? '';
                    $fcTeacher = $firstCourse['teacherName'] ?? $firstCourse['teacher'] ?? '';
                    $fcClassTime = $firstCourse['classTime'] ?? '';
                    $fcWeekNoteDetail = $firstCourse['weekNoteDetail'] ?? '';
                    $fcSlots = getCourseTimeSlots($fcClassTime, $fcWeekNoteDetail);
                    $fcFirst = !empty($fcSlots) ? min($fcSlots) : 0;
                    $fcLast = !empty($fcSlots) ? max($fcSlots) : 0;
                    $fcTimeDisplay = '';
                    if ($fcFirst > 0 && $fcLast > 0) {
                        $fcFirstCode = '1' . sprintf('%02d', $fcFirst);
                        $fcLastCode = '1' . sprintf('%02d', $fcLast);
                        $fcFirstTime = $timeSlots[$fcFirstCode]['time'] ?? '';
                        $fcLastTime = $timeSlots[$fcLastCode]['time'] ?? '';
                        $fcFirstParts = explode('-', $fcFirstTime);
                        $fcLastParts = explode('-', $fcLastTime);
                        if (count($fcFirstParts) == 2 && count($fcLastParts) == 2) {
                            $fcTimeDisplay = $fcFirstParts[0] . '-' . $fcLastParts[1];
                        }
                    }
                ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold"><i class="fa fa-sun me-1"></i>今日首节课</span>
                                    <small><?php echo $fcTimeDisplay; ?></small>
                                </div>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title fw-bold"><?php echo htmlspecialchars($fcName); ?></h5>
                                <?php if($fcLocation): ?>
                                <p class="card-text text-muted mb-1"><i class="fa fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($fcLocation); ?></p>
                                <?php endif; ?>
                                <?php if(!empty($fcTeacher)): ?>
                                <p class="card-text text-muted mb-0"><i class="fa fa-user me-1"></i><?php echo htmlspecialchars($fcTeacher); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php elseif(!empty($dayCourses) && ($currentCourse || $nextCourse)): ?>
                <div class="row mb-4">
                    <?php if($currentCourse): ?>
                    <?php
                        $ccName = $currentCourse['courseName'] ?? '';
                        $ccLocation = $currentCourse['location'] ?? '';
                        $ccTeacher = $currentCourse['teacherName'] ?? $currentCourse['teacher'] ?? '';
                        $ccClassTime = $currentCourse['classTime'] ?? '';
                        $ccWeekNoteDetail = $currentCourse['weekNoteDetail'] ?? '';
                        $ccSlots = getCourseTimeSlots($ccClassTime, $ccWeekNoteDetail);
                        $ccFirst = !empty($ccSlots) ? min($ccSlots) : 0;
                        $ccLast = !empty($ccSlots) ? max($ccSlots) : 0;
                        $ccTimeDisplay = '';
                        if ($ccFirst > 0 && $ccLast > 0) {
                            $ccFirstCode = '1' . sprintf('%02d', $ccFirst);
                            $ccLastCode = '1' . sprintf('%02d', $ccLast);
                            $ccFirstTime = $timeSlots[$ccFirstCode]['time'] ?? '';
                            $ccLastTime = $timeSlots[$ccLastCode]['time'] ?? '';
                            $ccFirstParts = explode('-', $ccFirstTime);
                            $ccLastParts = explode('-', $ccLastTime);
                            if (count($ccFirstParts) == 2 && count($ccLastParts) == 2) {
                                $ccTimeDisplay = $ccFirstParts[0] . '-' . $ccLastParts[1];
                            }
                        }
                    ?>
                    <div class="col-md-6">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold"><i class="fa fa-play-circle me-1"></i>正在上课</span>
                                    <small><?php echo $ccTimeDisplay; ?><?php if($minutesToEnd > 0): ?> · 还有<?php echo $minutesToEnd; ?>分钟下课<?php endif; ?></small>
                                </div>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title fw-bold"><?php echo htmlspecialchars($ccName); ?></h5>
                                <?php if($ccLocation): ?>
                                <p class="card-text text-muted mb-1"><i class="fa fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($ccLocation); ?></p>
                                <?php endif; ?>
                                <?php if(!empty($ccTeacher)): ?>
                                <p class="card-text text-muted mb-0"><i class="fa fa-user me-1"></i><?php echo htmlspecialchars($ccTeacher); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($nextCourse): ?>
                    <?php
                        $ncName = $nextCourse['courseName'] ?? '';
                        $ncLocation = $nextCourse['location'] ?? '';
                        $ncTeacher = $nextCourse['teacherName'] ?? $nextCourse['teacher'] ?? '';
                        $ncClassTime = $nextCourse['classTime'] ?? '';
                        $ncWeekNoteDetail = $nextCourse['weekNoteDetail'] ?? '';
                        $ncSlots = getCourseTimeSlots($ncClassTime, $ncWeekNoteDetail);
                        $ncFirst = !empty($ncSlots) ? min($ncSlots) : 0;
                        $ncLast = !empty($ncSlots) ? max($ncSlots) : 0;
                        $ncTimeDisplay = '';
                        if ($ncFirst > 0 && $ncLast > 0) {
                            $ncFirstCode = '1' . sprintf('%02d', $ncFirst);
                            $ncLastCode = '1' . sprintf('%02d', $ncLast);
                            $ncFirstTime = $timeSlots[$ncFirstCode]['time'] ?? '';
                            $ncLastTime = $timeSlots[$ncLastCode]['time'] ?? '';
                            $ncFirstParts = explode('-', $ncFirstTime);
                            $ncLastParts = explode('-', $ncLastTime);
                            if (count($ncFirstParts) == 2 && count($ncLastParts) == 2) {
                                $ncTimeDisplay = $ncFirstParts[0] . '-' . $ncLastParts[1];
                            }
                        }
                    ?>
                    <div class="col-md-6">
                        <div class="card border-warning">
                            <div class="card-header bg-warning text-dark">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold"><i class="fa fa-clock me-1"></i>下节课程</span>
                                    <small><?php echo $ncTimeDisplay; ?><?php if($minutesToNext > 0): ?> · 还有<?php echo $minutesToNext; ?>分钟上课<?php endif; ?></small>
                                </div>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title fw-bold"><?php echo htmlspecialchars($ncName); ?></h5>
                                <?php if($ncLocation): ?>
                                <p class="card-text text-muted mb-1"><i class="fa fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($ncLocation); ?></p>
                                <?php endif; ?>
                                <?php if(!empty($ncTeacher)): ?>
                                <p class="card-text text-muted mb-0"><i class="fa fa-user me-1"></i><?php echo htmlspecialchars($ncTeacher); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if(!empty($dayCourses)): ?>
                <h5 class="mb-3">
                    <i class="fa fa-book me-2"></i><?php echo $dayNamesFull[$selectDay]; ?>课程 (共<?php echo count($dayCourses); ?>节)
                </h5>
                
                <div class="row">
                    <?php foreach($dayCourses as $index => $course): ?>
                    <?php 
                        $courseName = $course['courseName'] ?? '';
                        $location = $course['location'] ?? '';
                        $teacher = $course['teacherName'] ?? $course['teacher'] ?? '';
                        $classTime = $course['classTime'] ?? '';
                        $weekNoteDetail = $course['weekNoteDetail'] ?? '';
                        $weeks = $course['weeks'] ?? '';
                        
                        $slots = getCourseTimeSlots($classTime, $weekNoteDetail);
                        $firstSlot = !empty($slots) ? min($slots) : 0;
                        $lastSlot = !empty($slots) ? max($slots) : 0;
                        
                        $timeDisplay = '';
                        if ($firstSlot > 0 && $lastSlot > 0) {
                            $firstCode = '1' . sprintf('%02d', $firstSlot);
                            $lastCode = '1' . sprintf('%02d', $lastSlot);
                            $firstTime = $timeSlots[$firstCode]['time'] ?? '';
                            $lastTime = $timeSlots[$lastCode]['time'] ?? '';
                            $firstParts = explode('-', $firstTime);
                            $lastParts = explode('-', $lastTime);
                            if (count($firstParts) == 2 && count($lastParts) == 2) {
                                $timeDisplay = $firstParts[0] . '-' . $lastParts[1];
                            }
                        }
                        
                        $isCurrent = false;
                        $isPast = false;
                        
                        if ($isToday && $currentSection) {
                            foreach ($slots as $slot) {
                                $code = '1' . sprintf('%02d', $slot);
                                if ($code == $currentSection) {
                                    $isCurrent = true;
                                    break;
                                }
                            }
                            
                            if (!$isCurrent && $firstSlot > 0) {
                                $currentSlotNum = intval(substr($currentSection, 1));
                                if ($lastSlot < $currentSlotNum) {
                                    $isPast = true;
                                }
                            }
                        }
                    ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card h-100 <?php echo $isCurrent ? 'border-primary' : ($isPast ? 'opacity-50' : ''); ?>">
                            <div class="card-header <?php echo $isCurrent ? 'bg-primary text-white' : 'bg-light'; ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">
                                        <?php if($isCurrent): ?>
                                        <i class="fa fa-play-circle me-1"></i>进行中
                                        <?php elseif($isPast): ?>
                                        <i class="fa fa-check-circle me-1"></i>已结束
                                        <?php elseif($firstSlot > 0): ?>
                                        <i class="fa fa-clock me-1"></i>第<?php echo $firstSlot; ?><?php if($lastSlot != $firstSlot): ?>-<?php echo $lastSlot; ?><?php endif; ?>节
                                        <?php else: ?>
                                        <i class="fa fa-clock me-1"></i>待定
                                        <?php endif; ?>
                                    </span>
                                    <small><?php echo $timeDisplay; ?></small>
                                </div>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title fw-bold"><?php echo htmlspecialchars($courseName); ?></h6>
                                <?php if($location): ?>
                                <p class="card-text text-muted mb-1">
                                    <i class="fa fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($location); ?>
                                </p>
                                <?php endif; ?>
                                <?php if(!empty($teacher)): ?>
                                <p class="card-text text-muted mb-0">
                                    <i class="fa fa-user me-1"></i><?php echo htmlspecialchars($teacher); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fa fa-coffee fa-4x text-muted mb-3"></i>
                    <h4><?php echo $dayNamesFull[$selectDay]; ?>没有课程</h4>
                    <p class="text-muted">
                        <?php if(!$isToday): ?>
                        这一天没有安排课程，看看其他天吧~
                        <?php else: ?>
                        今天没有课程，好好休息一下吧~
                        <?php endif; ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
<?php if(!$testMode): ?>
function updateTime() {
    var now = new Date();
    var hours = String(now.getHours()).padStart(2, '0');
    var minutes = String(now.getMinutes()).padStart(2, '0');
    $('#currentTime').text(hours + ':' + minutes);
}

setInterval(updateTime, 1000);
<?php endif; ?>
</script>

<?php include 'foot.php'; ?>
<style>
@media (max-width: 768px) {
    .day-btn-group .btn {
        padding: 0.375rem 0.5rem;
        font-size: 12px;
    }
    .day-btn-group .btn div:first-child {
        font-size: 11px !important;
    }
    .day-btn-group .btn div:nth-child(2) {
        font-size: 13px !important;
    }
    .day-btn-group .btn div:last-child {
        font-size: 9px !important;
    }
    .card-header {
        padding: 0.5rem 0.75rem;
    }
    .card-body {
        padding: 0.75rem;
    }
    .alert {
        padding: 0.75rem;
    }
    .alert i.fa-2x {
        font-size: 1.5rem;
    }
    .fs-5 {
        font-size: 1rem !important;
    }
    .row.mb-4 > .col-md-6 {
        margin-bottom: 0.75rem;
    }
}
</style>
