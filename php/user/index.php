<?php
$title = '用户中心';
$requireLogin = true;
include('./head.php');

$userId = $_SESSION['userId'];
$userData = $DB->getRow("SELECT * FROM nteam_users WHERE id='$userId'");
$academicAccount = $DB->getRow("SELECT * FROM nteam_academic_accounts WHERE user_id='$userId'");
$dormitoryCountRow = $DB->getRow("SELECT COUNT(*) as cnt FROM nteam_dormitory_bindings WHERE user_id='$userId' AND status=1");
$dormitoryCount = $dormitoryCountRow ? $dormitoryCountRow['cnt'] : 0;

$lowSubscriptions = $DB->getAll("SELECT s.subscribe_count, t.name, t.template_id, t.scene_type 
    FROM nteam_subscriptions s 
    JOIN nteam_wx_templates t ON s.template_id = t.template_id 
    WHERE s.user_id='$userId' AND s.status=1 AND s.subscribe_count < 2 AND t.status=1");

$sceneTypeNames = [
    'schedule_reminder' => '课程提醒',
    'grade_notify' => '成绩通知',
    'power_fee' => '电费提醒',
    'power_report' => '电费报告',
    'subscription_alert' => '额度提醒'
];
?>

<div id="pjax-container">
    <div class="content animated fadeIn">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-center text-md-start">
            <div class="flex-grow-1 mb-1 mb-md-0">
                <h1 class="h3 fw-bold mb-2">
                    用户中心
                </h1>
                <h2 class="h6 fw-medium fw-medium text-muted mb-0">
                    欢迎 <a class="fw-semibold" href="javascript:void(0)"><?php echo htmlspecialchars($userData['username']); ?></a>，祝您使用愉快。
                </h2>
            </div>
        </div>
    </div>
    
    <div class="content animated fadeIn">
        <div class="row items-push">
            <div class="col-sm-6 col-xxl-4">
                <div class="block block-rounded d-flex flex-column h-100 mb-0">
                    <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                        <dl class="mb-0">
                            <dt class="fs-3 fw-bold"><?php echo htmlspecialchars($userData['username']); ?></dt>
                            <dd class="fs-sm fw-medium text-muted mb-0">用户名</dd>
                        </dl>
                        <div class="item item-rounded-lg bg-body-light">
                            <i class="fa fa-user fs-3 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xxl-4">
                <div class="block block-rounded d-flex flex-column h-100 mb-0">
                    <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                        <dl class="mb-0" style="min-width: 0; flex: 1;">
                            <dt class="fs-3 fw-bold text-truncate"><?php echo htmlspecialchars($userData['email']); ?></dt>
                            <dd class="fs-sm fw-medium text-muted mb-0">
                                邮箱 
                                <?php if($userData['email_verified'] == 1): ?>
                                    <span class="badge bg-success ms-1">已验证</span>
                                <?php else: ?>
                                    <span class="badge bg-warning ms-1">未验证</span>
                                <?php endif; ?>
                            </dd>
                        </dl>
                        <div class="item item-rounded-lg bg-body-light ms-3 flex-shrink-0">
                            <i class="fa fa-envelope fs-3 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xxl-4">
                <div class="block block-rounded d-flex flex-column h-100 mb-0">
                    <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                        <dl class="mb-0">
                            <dt class="fs-3 fw-bold">
                                <?php if($academicAccount): ?>
                                    <?php echo htmlspecialchars($academicAccount['student_id']); ?>
                                <?php else: ?>
                                    未绑定
                                <?php endif; ?>
                            </dt>
                            <dd class="fs-sm fw-medium text-muted mb-0">
                                教务账号 
                                <?php if($academicAccount): ?>
                                    <span class="badge bg-success ms-1">已绑定</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary ms-1">未绑定</span>
                                <?php endif; ?>
                            </dd>
                        </dl>
                        <div class="item item-rounded-lg bg-body-light">
                            <i class="fa fa-graduation-cap fs-3 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if(!empty($lowSubscriptions)): ?>
    <div class="content animated fadeIn">
        <div class="block block-rounded">
            <div class="block-header block-header-default bg-warning-lt">
                <h3 class="block-title">
                    <i class="fa fa-bell text-warning me-2"></i>
                    <span class="text-warning">订阅次数不足提醒</span>
                </h3>
            </div>
            <div class="block-content block-content-full">
                <div class="alert alert-warning mb-3">
                    <i class="fa fa-exclamation-triangle me-2"></i>
                    以下订阅消息类型的剩余次数不足，请及时在小程序中增加订阅，以免错过重要通知：
                </div>
                <div class="row">
                    <?php foreach($lowSubscriptions as $sub): ?>
                    <div class="col-md-4 col-sm-6 mb-3">
                        <div class="d-flex align-items-center p-3 bg-body rounded">
                            <div class="flex-grow-1">
                                <div class="fw-bold"><?php echo htmlspecialchars($sub['name']); ?></div>
                                <small class="text-muted">
                                    <?php echo isset($sceneTypeNames[$sub['scene_type']]) ? $sceneTypeNames[$sub['scene_type']] : '通知'; ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <span class="badge <?php echo $sub['subscribe_count'] > 0 ? 'bg-warning' : 'bg-danger'; ?> fs-5">
                                    <?php echo $sub['subscribe_count']; ?>次
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-2">
                    <a href="subscription.php" class="btn btn-warning">
                        <i class="fab fa-weixin me-1"></i>查看订阅详情
                    </a>
                    <span class="text-muted ms-3">请在微信小程序中点击对应模板的"立即订阅"按钮增加次数</span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="content animated fadeIn">
        <div class="row">
            <div class="col-md-12">
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title"><b>账户信息</b></h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-bordered table-striped">
                            <tbody>
                                <tr>
                                    <th style="width: 150px;">用户名</th>
                                    <td><?php echo htmlspecialchars($userData['username']); ?></td>
                                </tr>
                                <tr>
                                    <th>邮箱</th>
                                    <td>
                                        <?php echo htmlspecialchars($userData['email']); ?>
                                        <?php if($userData['email_verified'] == 1): ?>
                                            <span class="badge bg-success ms-2">已验证</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning ms-2">未验证</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>QQ</th>
                                    <td><?php echo htmlspecialchars($userData['qq'] ?: '未设置'); ?></td>
                                </tr>
                                <tr>
                                    <th>教务账号</th>
                                    <td>
                                        <?php if($academicAccount): ?>
                                            <?php echo htmlspecialchars($academicAccount['student_id']); ?>
                                            <?php if($academicAccount['real_name']): ?>
                                                <span class="text-muted ms-2">(<?php echo htmlspecialchars($academicAccount['real_name']); ?>)</span>
                                            <?php endif; ?>
                                            <span class="badge bg-success ms-2">已绑定</span>
                                            <span class="text-muted ms-2">绑定时间：<?php echo $academicAccount['bind_time']; ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">未绑定</span>
                                            <a href="academic_bind.php" class="btn btn-sm btn-alt-primary ms-2">
                                                <i class="fa fa-link me-1"></i>立即绑定
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>注册时间</th>
                                    <td><?php echo $userData['register_time'] ?: '未知'; ?></td>
                                </tr>
                                <tr>
                                    <th>最后登录时间</th>
                                    <td><?php echo $userData['last_login_time'] ?: '未知'; ?></td>
                                </tr>
                                <tr>
                                    <th>最后登录IP</th>
                                    <td><?php echo $userData['last_login_ip'] ?: '未知'; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="content animated fadeIn">
        <div class="row">
            <div class="col-md-3">
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title"><b>成绩查询</b></h3>
                    </div>
                    <div class="block-content">
                        <div class="py-3 text-center">
                            <div class="mb-3">
                                <i class="fa fa-chart-bar fa-3x text-primary"></i>
                            </div>
                            <div class="fs-5 fw-semibold">成绩查询</div>
                            <div class="pt-3">
                                <a class="btn btn-alt-primary" href="grade.php">
                                    <i class="fa fa-chart-bar opacity-50 me-1"></i> <b>立即查询</b>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title"><b>课程查询</b></h3>
                    </div>
                    <div class="block-content">
                        <div class="py-3 text-center">
                            <div class="mb-3">
                                <i class="fa fa-calendar-alt fa-3x text-primary"></i>
                            </div>
                            <div class="fs-5 fw-semibold">课程查询</div>
                            <div class="pt-3">
                                <a class="btn btn-alt-primary" href="schedule.php">
                                    <i class="fa fa-calendar-alt opacity-50 me-1"></i> <b>立即查询</b>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title"><b>学籍查询</b></h3>
                    </div>
                    <div class="block-content">
                        <div class="py-3 text-center">
                            <div class="mb-3">
                                <i class="fa fa-id-card fa-3x text-primary"></i>
                            </div>
                            <div class="fs-5 fw-semibold">学籍查询</div>
                            <div class="pt-3">
                                <a class="btn btn-alt-primary" href="student_info.php">
                                    <i class="fa fa-id-card opacity-50 me-1"></i> <b>立即查询</b>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title"><b>教务账号绑定</b></h3>
                    </div>
                    <div class="block-content">
                        <div class="py-3 text-center">
                            <div class="mb-3">
                                <i class="fa fa-graduation-cap fa-3x text-primary"></i>
                            </div>
                            <div class="fs-5 fw-semibold">
                                <?php if($academicAccount): ?>
                                    已绑定
                                <?php else: ?>
                                    未绑定
                                <?php endif; ?>
                            </div>
                            <div class="pt-3">
                                <a class="btn btn-alt-primary" href="academic_bind.php">
                                    <i class="fa fa-graduation-cap opacity-50 me-1"></i> <b><?php echo $academicAccount ? '管理绑定' : '立即绑定'; ?></b>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="content animated fadeIn">
        <div class="row">
            <div class="col-md-3">
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title"><b>电费查询</b></h3>
                    </div>
                    <div class="block-content">
                        <div class="py-3 text-center">
                            <div class="mb-3">
                                <i class="fa fa-bolt fa-3x text-warning"></i>
                            </div>
                            <div class="fs-5 fw-semibold">电费查询</div>
                            <div class="pt-3">
                                <a class="btn btn-alt-warning" href="power_fee.php">
                                    <i class="fa fa-bolt opacity-50 me-1"></i> <b>立即查询</b>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title"><b>宿舍绑定</b></h3>
                    </div>
                    <div class="block-content">
                        <div class="py-3 text-center">
                            <div class="mb-3">
                                <i class="fa fa-home fa-3x text-info"></i>
                            </div>
                            <div class="fs-5 fw-semibold">
                                <?php if($dormitoryCount > 0): ?>
                                    已绑定 <?php echo $dormitoryCount; ?> 个
                                <?php else: ?>
                                    未绑定
                                <?php endif; ?>
                            </div>
                            <div class="pt-3">
                                <a class="btn btn-alt-info" href="dormitory_bind.php">
                                    <i class="fa fa-home opacity-50 me-1"></i> <b><?php echo $dormitoryCount > 0 ? '管理宿舍' : '立即绑定'; ?></b>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title"><b>修改密码</b></h3>
                    </div>
                    <div class="block-content">
                        <div class="py-3 text-center">
                            <div class="mb-3">
                                <i class="fa fa-key fa-3x text-primary"></i>
                            </div>
                            <div class="fs-5 fw-semibold">修改密码</div>
                            <div class="pt-3">
                                <a class="btn btn-alt-primary" href="edit_pwd.php">
                                    <i class="fa fa-key opacity-50 me-1"></i> <b>立即修改</b>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title"><b>账户安全</b></h3>
                    </div>
                    <div class="block-content">
                        <div class="py-3 text-center">
                            <div class="mb-3">
                                <i class="fa fa-shield-alt fa-3x text-success"></i>
                            </div>
                            <div class="fs-5 fw-semibold">安全提示</div>
                            <div class="pt-3">
                                <p class="text-muted small">请定期修改密码</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'foot.php'; ?>
