<?php
$title = '成绩查询';
$requireLogin = true;
include('./head.php');
require_once('../funsion/feature_settings.php');

$userId = $_SESSION['userId'];

if (!isFeatureEnabled($DB, 'grade')) {
    echo '<div id="pjax-container"><div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-content block-content-full">';
    checkFeatureAccess($DB, 'grade');
    echo '</div></div></div>';
    include('./foot.php');
    exit;
}

$academicAccount = $DB->getRow("SELECT * FROM nteam_academic_accounts WHERE user_id='$userId'");

if (!$academicAccount) {
    echo '<div id="pjax-container"><div class="content animated fadeIn">
        <div class="alert alert-warning">
            <h4><i class="fa fa-exclamation-triangle me-2"></i>未绑定教务账号</h4>
            <p>请先绑定教务账号后再使用成绩查询功能。</p>
            <a href="academic_bind.php" class="btn btn-alt-primary mt-2">
                <i class="fa fa-link me-1"></i>立即绑定
            </a>
        </div>
    </div></div>';
    include 'foot.php';
    exit;
}

require_once('../funsion/AcademicSystem.php');

$semester = isset($_GET['semester']) ? $_GET['semester'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$grades = null;
$error = null;
$semesters = [];
$token = null;
$configSemester = '';   // 后台配置的默认学期（推送学期）

$tokenResult = AcademicSystem::getValidToken($DB, $userId, $academicAccount['student_id'], $academicAccount['password']);

if ($tokenResult['success']) {
    $token = $tokenResult['token'];

    // 后台「成绩推送」配置的默认学期（Adminq/academic_push_config.php → 推送学期）
    $cfgRow = $DB->getRow("SELECT grade_semester FROM nteam_academic_push_config WHERE id = 1");
    $configSemester = ($cfgRow && !empty($cfgRow['grade_semester'])) ? $cfgRow['grade_semester'] : '';

    $semesterResult = AcademicSystem::getSemesterList($token);
    if (isset($semesterResult['code']) && $semesterResult['code'] == 1) {
        $semesters = $semesterResult['data'];

        // 默认学期解析顺序（与 uniapp / api.php?act=query_grade 保持一致）：
        // 1) 后台「推送学期」配置  2) 教务系统当前学期（isdqxq=1）  3) 学期列表第一项
        if (empty($semester) && $configSemester !== '') {
            $semester = $configSemester;
        }

        // 配置的学期不在当前学期列表中（如已被归档）时回退，避免下拉框显示与实际查询不一致
        if ($semester !== '' && !empty($semesters)) {
            $semesterExists = false;
            foreach ($semesters as $s) {
                if (isset($s['semesterId']) && $s['semesterId'] == $semester) {
                    $semesterExists = true;
                    break;
                }
            }
            if (!$semesterExists) {
                $semester = '';
            }
        }

        if (empty($semester)) {
            foreach ($semesters as $s) {
                if (isset($s['isdqxq']) && $s['isdqxq'] == '1') {
                    $semester = $s['semesterId'];
                    break;
                }
            }
            if (empty($semester) && !empty($semesters)) {
                $semester = $semesters[0]['semesterId'];
            }
        }
    }
    
    if (!empty($semester)) {
        $gradeResult = AcademicSystem::getGrades($token, $semester, $type);
        
        if (isset($gradeResult['code']) && $gradeResult['code'] == 1) {
            if (isset($gradeResult['data']) && is_array($gradeResult['data']) && !empty($gradeResult['data'])) {
                $grades = $gradeResult['data'][0];
                
                if (isset($grades['achievement']) && is_array($grades['achievement'])) {
                    $totalGpaPoints = 0;
                    $totalCredits = 0;
                    
                    foreach ($grades['achievement'] as &$course) {
                        $score = isset($course['fraction']) ? floatval($course['fraction']) : 0;
                        $credit = isset($course['credit']) ? floatval($course['credit']) : 0;
                        
                        $gpa = 0;
                        if ($score >= 90) $gpa = 4.0;
                        elseif ($score >= 85) $gpa = 3.7;
                        elseif ($score >= 82) $gpa = 3.3;
                        elseif ($score >= 78) $gpa = 3.0;
                        elseif ($score >= 75) $gpa = 2.7;
                        elseif ($score >= 72) $gpa = 2.3;
                        elseif ($score >= 68) $gpa = 2.0;
                        elseif ($score >= 64) $gpa = 1.5;
                        elseif ($score >= 60) $gpa = 1.0;
                        
                        $course['gpa'] = $gpa;
                        
                        if ($score >= 60 && $credit > 0) {
                            $totalGpaPoints += $gpa * $credit;
                            $totalCredits += $credit;
                        }
                    }
                    
                    $avgGpa = $totalCredits > 0 ? round($totalGpaPoints / $totalCredits, 2) : 0;
                    
                    if (empty($grades['pjxfjd']) || floatval($grades['pjxfjd']) == 0) {
                        $grades['pjxfjd'] = $avgGpa;
                    }
                }
            } else {
                $error = '成绩数据为空';
            }
        } else {
            $error = $gradeResult['msg'] ?? $gradeResult['Msg'] ?? '获取成绩失败';
        }
    }
} else {
    $error = $tokenResult['msg'] ?? '登录失败，请检查学工号和密码是否正确';
}

function calculateGpa($score) {
    if ($score >= 90) return '4.0';
    if ($score >= 85) return '3.7';
    if ($score >= 82) return '3.3';
    if ($score >= 78) return '3.0';
    if ($score >= 75) return '2.7';
    if ($score >= 72) return '2.3';
    if ($score >= 68) return '2.0';
    if ($score >= 64) return '1.5';
    if ($score >= 60) return '1.0';
    return '0';
}
?>

<div id="pjax-container">
    <div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-chart-bar me-2"></i>成绩查询
                </h3>
            </div>
            <div class="block-content block-content-full">
                <?php renderFeatureAnnouncement($DB, 'grade'); ?>
                <?php if($error && !$grades): ?>
                <div class="alert alert-danger mb-4">
                    <i class="fa fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <?php if($token): ?>
                <div class="row mb-4">
                    <div class="col-md-8">
                        <form method="GET" action="" class="row g-2">
                            <div class="col-auto">
                                <select name="semester" class="form-select" onchange="this.form.submit()">
                                    <?php foreach($semesters as $s): ?>
                                    <option value="<?php echo $s['semesterId']; ?>" <?php echo $semester == $s['semesterId'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($s['semesterName']); ?><?php echo ($configSemester !== '' && $configSemester == $s['semesterId']) ? '（默认）' : ''; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-auto">
                                <select name="type" class="form-select" onchange="this.form.submit()">
                                    <option value="" <?php echo $type === '' ? 'selected' : ''; ?>>全部成绩</option>
                                    <option value="0" <?php echo $type === '0' ? 'selected' : ''; ?>>首次成绩</option>
                                    <option value="1" <?php echo $type === '1' ? 'selected' : ''; ?>>最好成绩</option>
                                </select>
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
                
                <?php if($grades !== null): ?>
                    <?php 
                    $achievement = isset($grades['achievement']) ? $grades['achievement'] : [];
                    $gpa = isset($grades['pjxfjd']) ? $grades['pjxfjd'] : '-';
                    $credit = isset($grades['yxzxf']) ? $grades['yxzxf'] : '-';
                    ?>
                    
                    <?php if(!empty($achievement)): ?>
                    
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="block block-rounded mb-0 bg-primary">
                                <div class="block-content text-center py-3">
                                    <div class="fs-2 fw-bold text-white">
                                        <?php echo $gpa; ?>
                                    </div>
                                    <div class="text-white opacity-75">平均绩点</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="block block-rounded mb-0 bg-success">
                                <div class="block-content text-center py-3">
                                    <div class="fs-2 fw-bold text-white">
                                        <?php echo $credit; ?>
                                    </div>
                                    <div class="text-white opacity-75">已修学分</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="block block-rounded mb-0 bg-info">
                                <div class="block-content text-center py-3">
                                    <div class="fs-2 fw-bold text-white">
                                        <?php echo count($achievement); ?>
                                    </div>
                                    <div class="text-white opacity-75">课程数量</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end mb-3">
                        <button type="button" class="btn btn-alt-primary" onclick="aiAnalyzeGrades()">
                            <i class="fa fa-robot me-1"></i>AI 智能分析成绩
                        </button>
                    </div>
                    <script>
                        window.__gradeData = <?php echo json_encode([
                            'achievement' => $achievement,
                            'gpa' => $gpa,
                            'credit' => $credit,
                            'semester' => $semester
                        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG); ?>;
                    </script>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-vcenter">
                            <thead>
                                <tr>
                                    <th>课程名称</th>
                                    <th class="text-center" style="width: 80px;">成绩</th>
                                    <th class="text-center" style="width: 80px;">绩点</th>
                                    <th class="text-center" style="width: 80px;">学分</th>
                                    <th class="text-center" style="width: 100px;">课程属性</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($achievement as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['courseName'] ?? ''); ?></td>
                                    <td class="text-center">
                                        <?php 
                                        $score = $item['fraction'] ?? '-';
                                        $scoreClass = '';
                                        if (is_numeric($score)) {
                                            if ($score >= 90) $scoreClass = 'text-success fw-bold';
                                            elseif ($score >= 80) $scoreClass = 'text-primary';
                                            elseif ($score >= 60) $scoreClass = 'text-warning';
                                            else $scoreClass = 'text-danger fw-bold';
                                        }
                                        ?>
                                        <span class="<?php echo $scoreClass; ?>"><?php echo $score; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                        if (isset($item['gpa'])) {
                                            echo $item['gpa'];
                                        } elseif (is_numeric($score)) {
                                            echo calculateGpa(floatval($score));
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td class="text-center"><?php echo htmlspecialchars($item['credit'] ?? '-'); ?></td>
                                    <td class="text-center"><?php echo htmlspecialchars($item['curriculumAttributes'] ?? '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mobile-grades d-md-none">
                        <?php foreach($achievement as $item): ?>
                        <?php 
                        $score = $item['fraction'] ?? '-';
                        $scoreClass = '';
                        if (is_numeric($score)) {
                            if ($score >= 90) $scoreClass = 'text-success';
                            elseif ($score >= 80) $scoreClass = 'text-primary';
                            elseif ($score >= 60) $scoreClass = 'text-warning';
                            else $scoreClass = 'text-danger';
                        }
                        ?>
                        <div class="card mb-2">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="fw-bold"><?php echo htmlspecialchars($item['courseName'] ?? ''); ?></div>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($item['curriculumAttributes'] ?? '-'); ?> · 
                                            <?php echo htmlspecialchars($item['credit'] ?? '-'); ?>学分
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <div class="fs-4 fw-bold <?php echo $scoreClass; ?>"><?php echo $score; ?></div>
                                        <small class="text-muted">
                                            绩点: <?php 
                                            if (isset($item['gpa'])) {
                                                echo $item['gpa'];
                                            } elseif (is_numeric($score)) {
                                                echo calculateGpa(floatval($score));
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle me-2"></i>该学期暂无成绩数据
                    </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="fa fa-info-circle me-2"></i>请选择学期后点击"查询"按钮获取成绩
                </div>
                <?php endif; ?>
                
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 右侧浮动 AI 分析按钮（与 uniapp 端一致） -->
<div class="ai-fab" title="AI 智能分析成绩" onclick="aiAnalyzeGrades()">
    <span class="ai-fab-icon">AI</span>
    <span class="ai-fab-label">分析</span>
</div>

<!-- AI 智能分析成绩 弹窗 -->
<div class="modal fade" id="aiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-robot me-1"></i>AI 智能成绩分析</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="关闭"></button>
            </div>
            <div class="modal-body">
                <pre id="aiResult" style="white-space: pre-wrap; word-break: break-word; font-family: inherit; font-size: 14px; margin: 0;"></pre>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
function aiAnalyzeGrades() {
    AiAnalyze.run({
        url: 'ajax.php?act=ai_analyze_grades',
        modalId: 'aiModal',
        resultId: 'aiResult',
        fabSel: '.ai-fab',
        payload: function () { return { data: JSON.stringify(window.__gradeData) }; },
        cacheKey: function () { return 'grades:' + JSON.stringify(window.__gradeData); },
        validate: function () {
            if (!window.__gradeData || !window.__gradeData.achievement || !window.__gradeData.achievement.length) {
                alert('暂无可分析的成绩数据');
                return false;
            }
            return true;
        }
    });
}
</script>

<?php include 'foot.php'; ?>
<style>
@media (max-width: 768px) {
    .table-responsive {
        display: none;
    }
    .row.mb-4 > .col-md-4 {
        margin-bottom: 0.75rem;
    }
    .fs-2 {
        font-size: 1.5rem !important;
    }
    .form-select {
        font-size: 14px;
    }
}
</style>
