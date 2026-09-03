<?php
$title = '学籍查询';
$requireLogin = true;
include('./head.php');
require_once('../funsion/feature_settings.php');

$userId = $_SESSION['userId'];

if (!isFeatureEnabled($DB, 'student_info')) {
    echo '<div id="pjax-container"><div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-content block-content-full">';
    checkFeatureAccess($DB, 'student_info');
    echo '</div></div></div>';
    include('./foot.php');
    exit;
}

$academicAccount = $DB->getRow("SELECT * FROM nteam_academic_accounts WHERE user_id='$userId'");

if (!$academicAccount) {
    echo '<div id="pjax-container"><div class="content animated fadeIn">
        <div class="alert alert-warning">
            <h4><i class="fa fa-exclamation-triangle me-2"></i>未绑定教务账号</h4>
            <p>请先绑定教务账号后再使用学籍查询功能。</p>
            <a href="academic_bind.php" class="btn btn-alt-primary mt-2">
                <i class="fa fa-link me-1"></i>立即绑定
            </a>
        </div>
    </div></div>';
    include 'foot.php';
    exit;
}

require_once('../funsion/AcademicSystem.php');

$studentInfo = null;
$error = null;

$tokenResult = AcademicSystem::getValidToken($DB, $userId, $academicAccount['student_id'], $academicAccount['password']);

if ($tokenResult['success']) {
    $token = $tokenResult['token'];
    
    $infoResult = AcademicSystem::getStudentInfo($token);
    
    if (isset($infoResult['code']) && $infoResult['code'] == 1) {
        if (isset($infoResult['data']) && is_array($infoResult['data']) && !empty($infoResult['data'])) {
            $studentInfo = $infoResult['data'][0];
        } else {
            $error = '学籍数据为空';
        }
    } else {
        $error = $infoResult['msg'] ?? $infoResult['Msg'] ?? '获取学籍信息失败';
    }
} else {
    $error = $tokenResult['msg'] ?? '登录失败，请检查学工号和密码是否正确';
}

$fieldLabels = [
    'xm' => '姓名',
    'xh' => '学号',
    'xb' => '性别',
    'csrq' => '出生日期',
    'mz' => '民族',
    'zzmm' => '政治面貌',
    'xjzt' => '学籍状态',
    'rxrq' => '入学日期',
    'xz' => '学制',
    'nj' => '年级',
    'xy' => '学院',
    'zy' => '专业',
    'bj' => '班级',
    'pycc' => '培养层次',
    'xq' => '校区',
    'jg' => '籍贯',
    'sfzh' => '身份证号',
    'lxdh' => '联系电话',
    'dzxx' => '电子邮箱',
    'jtzz' => '家庭住址',
    'rxcj' => '入学成绩',
    'ksh' => '考生号',
    'dy' => '导师',
    'fdy' => '辅导员',
];
?>

<div id="pjax-container">
    <div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-id-card me-2"></i>学籍查询
                </h3>
            </div>
            <div class="block-content block-content-full">
                <?php renderFeatureAnnouncement($DB, 'student_info'); ?>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <span class="text-muted">
                            <i class="fa fa-user me-1"></i>
                            学工号：<?php echo htmlspecialchars($academicAccount['student_id']); ?>
                        </span>
                    </div>
                </div>
                
                <?php if($error): ?>
                <div class="alert alert-danger">
                    <i class="fa fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <?php if($studentInfo !== null): ?>
                    <?php if(is_array($studentInfo) && !empty($studentInfo)): ?>
                    
                    <?php if(isset($studentInfo['xm']) || isset($studentInfo['xh'])): ?>
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="profile-header">
                                <div class="profile-avatar">
                                    <i class="fa fa-user-graduate"></i>
                                </div>
                                <div class="profile-info">
                                    <div class="profile-name">
                                        <?php echo htmlspecialchars($studentInfo['xm'] ?? ''); ?>
                                    </div>
                                    <div class="profile-detail">
                                        <span class="badge bg-light text-dark me-2">
                                            <i class="fa fa-id-card me-1"></i><?php echo htmlspecialchars($studentInfo['xh'] ?? ''); ?>
                                        </span>
                                        <?php if(isset($studentInfo['xy'])): ?>
                                        <span class="badge bg-light text-dark me-2">
                                            <i class="fa fa-building me-1"></i><?php echo htmlspecialchars($studentInfo['xy']); ?>
                                        </span>
                                        <?php endif; ?>
                                        <?php if(isset($studentInfo['zy'])): ?>
                                        <span class="badge bg-light text-dark">
                                            <i class="fa fa-book me-1"></i><?php echo htmlspecialchars($studentInfo['zy']); ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="block block-rounded position-relative">
                                <div class="block-header block-header-default">
                                    <h3 class="block-title"><b>基本信息</b></h3>
                                </div>
                                <div class="block-content">
                                    <?php 
                                    $basicFields = ['xm', 'xh', 'xb', 'csrq', 'mz', 'zzmm', 'jg'];
                                    $hasBasicData = false;
                                    foreach($basicFields as $field):
                                        if(isset($studentInfo[$field]) && $studentInfo[$field]):
                                            $hasBasicData = true;
                                            break;
                                        endif;
                                    endforeach;
                                    ?>
                                    <?php if(!$hasBasicData): ?>
                                    <div class="info-empty-overlay">
                                        <i class="fa fa-ban"></i>
                                        <span>暂无信息</span>
                                    </div>
                                    <?php else: ?>
                                    <table class="table table-sm">
                                        <tbody>
                                            <?php 
                                            foreach($basicFields as $field):
                                                if(isset($studentInfo[$field]) && $studentInfo[$field]):
                                            ?>
                                            <tr>
                                                <th style="width: 100px;"><?php echo $fieldLabels[$field] ?? $field; ?></th>
                                                <td><?php echo htmlspecialchars($studentInfo[$field]); ?></td>
                                            </tr>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        </tbody>
                                    </table>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="block block-rounded position-relative">
                                <div class="block-header block-header-default">
                                    <h3 class="block-title"><b>学籍信息</b></h3>
                                </div>
                                <div class="block-content">
                                    <?php 
                                    $academicFields = ['xjzt', 'rxrq', 'xz', 'nj', 'pycc', 'xq'];
                                    $hasAcademicData = false;
                                    foreach($academicFields as $field):
                                        if(isset($studentInfo[$field]) && $studentInfo[$field]):
                                            $hasAcademicData = true;
                                            break;
                                        endif;
                                    endforeach;
                                    ?>
                                    <?php if(!$hasAcademicData): ?>
                                    <div class="info-empty-overlay">
                                        <i class="fa fa-ban"></i>
                                        <span>暂无信息</span>
                                    </div>
                                    <?php else: ?>
                                    <table class="table table-sm">
                                        <tbody>
                                            <?php 
                                            foreach($academicFields as $field):
                                                if(isset($studentInfo[$field]) && $studentInfo[$field]):
                                            ?>
                                            <tr>
                                                <th style="width: 100px;"><?php echo $fieldLabels[$field] ?? $field; ?></th>
                                                <td><?php echo htmlspecialchars($studentInfo[$field]); ?></td>
                                            </tr>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        </tbody>
                                    </table>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="block block-rounded position-relative">
                                <div class="block-header block-header-default">
                                    <h3 class="block-title"><b>院系专业</b></h3>
                                </div>
                                <div class="block-content">
                                    <?php 
                                    $deptFields = ['xy', 'zy', 'bj', 'dy', 'fdy'];
                                    $hasDeptData = false;
                                    foreach($deptFields as $field):
                                        if(isset($studentInfo[$field]) && $studentInfo[$field]):
                                            $hasDeptData = true;
                                            break;
                                        endif;
                                    endforeach;
                                    ?>
                                    <?php if(!$hasDeptData): ?>
                                    <div class="info-empty-overlay">
                                        <i class="fa fa-ban"></i>
                                        <span>暂无信息</span>
                                    </div>
                                    <?php else: ?>
                                    <table class="table table-sm">
                                        <tbody>
                                            <?php 
                                            foreach($deptFields as $field):
                                                if(isset($studentInfo[$field]) && $studentInfo[$field]):
                                            ?>
                                            <tr>
                                                <th style="width: 100px;"><?php echo $fieldLabels[$field] ?? $field; ?></th>
                                                <td><?php echo htmlspecialchars($studentInfo[$field]); ?></td>
                                            </tr>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        </tbody>
                                    </table>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="block block-rounded position-relative">
                                <div class="block-header block-header-default">
                                    <h3 class="block-title"><b>联系方式</b></h3>
                                </div>
                                <div class="block-content">
                                    <?php 
                                    $contactFields = ['lxdh', 'dzxx', 'jtzz'];
                                    $hasContactData = false;
                                    foreach($contactFields as $field):
                                        if(isset($studentInfo[$field]) && $studentInfo[$field]):
                                            $hasContactData = true;
                                            break;
                                        endif;
                                    endforeach;
                                    ?>
                                    <?php if(!$hasContactData): ?>
                                    <div class="info-empty-overlay">
                                        <i class="fa fa-ban"></i>
                                        <span>暂无信息</span>
                                    </div>
                                    <?php else: ?>
                                    <table class="table table-sm">
                                        <tbody>
                                            <?php 
                                            foreach($contactFields as $field):
                                                if(isset($studentInfo[$field]) && $studentInfo[$field]):
                                            ?>
                                            <tr>
                                                <th style="width: 100px;"><?php echo $fieldLabels[$field] ?? $field; ?></th>
                                                <td><?php echo htmlspecialchars($studentInfo[$field]); ?></td>
                                            </tr>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        </tbody>
                                    </table>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle me-2"></i>暂无学籍数据
                    </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="fa fa-info-circle me-2"></i>点击"查询学籍信息"按钮获取学籍信息
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'foot.php'; ?>
<style>
.profile-header {
    background: #fff3e0;
    border-radius: 0.5rem;
    padding: 2rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    color: #e65100;
    border: 1px solid #ffe0b2;
}

.profile-avatar {
    width: 80px;
    height: 80px;
    background: #ff9800;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.profile-avatar i {
    font-size: 2.5rem;
    color: white;
}

.profile-info {
    flex: 1;
}

.profile-name {
    font-size: 1.75rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.profile-detail {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.profile-detail .badge {
    font-size: 0.85rem;
    font-weight: normal;
    padding: 0.4rem 0.75rem;
    background: #fff8e1;
    color: #e65100;
    border: 1px solid #ffe082;
}

.info-empty-overlay {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: #9ca3af;
    background: #f9fafb;
    border-radius: 0.375rem;
    border: 2px dashed #e5e7eb;
}

.info-empty-overlay i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

.info-empty-overlay span {
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .profile-header {
        padding: 1.25rem;
        flex-direction: column;
        text-align: center;
    }
    
    .profile-avatar {
        width: 60px;
        height: 60px;
    }
    
    .profile-avatar i {
        font-size: 1.75rem;
    }
    
    .profile-name {
        font-size: 1.25rem;
    }
    
    .profile-detail {
        justify-content: center;
    }
    
    .profile-detail .badge {
        font-size: 0.75rem;
        padding: 0.3rem 0.5rem;
    }
    
    .block-content {
        padding: 0.75rem;
    }
    .table th {
        width: 80px !important;
        font-size: 13px;
    }
    .table td {
        font-size: 13px;
    }
    .row > .col-md-6 {
        margin-bottom: 1rem;
    }
    .block {
        margin-bottom: 1rem;
    }
}

#page-container.dark-mode .profile-header {
    background: rgba(255, 152, 0, 0.15) !important;
    color: #ffb74d !important;
    border-color: rgba(255, 152, 0, 0.3) !important;
}

#page-container.dark-mode .profile-avatar {
    background: #ff9800 !important;
}

#page-container.dark-mode .profile-detail .badge {
    background: rgba(255, 152, 0, 0.2) !important;
    color: #ffb74d !important;
    border-color: rgba(255, 152, 0, 0.3) !important;
}

#page-container.dark-mode .info-empty-overlay {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #94a3b8 !important;
}
</style>
