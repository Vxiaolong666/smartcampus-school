<?php
$mod = 'admin';
$title='后台管理';
/**
 * @var \lib\PdoHelper $DB
 */
include('../Common/Core_brain.php');
include './head.php';

$stats = $DB->getRow("
    SELECT 
        (SELECT COUNT(*) FROM nteam_admin) as admin_nums,
        (SELECT COUNT(*) FROM nteam_users) as user_nums,
        (SELECT COUNT(*) FROM nteam_academic_accounts) as academic_accounts,
        (SELECT COUNT(*) FROM nteam_dormitory_bindings WHERE status=1) as dormitory_bindings,
        (SELECT COUNT(*) FROM nteam_announcements WHERE is_published=1) as announcements,
        (SELECT COUNT(*) FROM nteam_push_logs WHERE status=1) as push_success,
        (SELECT COUNT(*) FROM nteam_push_logs WHERE status=2) as push_failed,
        (SELECT COUNT(*) FROM nteam_academic_push_logs WHERE status=1) as academic_push_success,
        (SELECT COUNT(*) FROM nteam_academic_push_logs WHERE status=2) as academic_push_failed
");

$mysqlversion = $DB->query("select VERSION()")->fetch()[0];

$todayStart = date('Y-m-d 00:00:00');
$todayStats = $DB->getRow("
    SELECT 
        (SELECT COUNT(*) FROM nteam_users WHERE DATE(register_time) = CURDATE()) as new_users_today,
        (SELECT COUNT(*) FROM nteam_push_logs WHERE DATE(created_at) = CURDATE()) as push_today,
        (SELECT COUNT(*) FROM nteam_academic_push_logs WHERE DATE(created_at) = CURDATE()) as academic_push_today
");
?>
<div id="pjax-container">
    <div class="content animated fadeIn">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-center text-md-start">
            <div class="flex-grow-1 mb-1 mb-md-0">
                <h1 class="h3 fw-bold mb-2">
                    <?php echo conf('Name');?>-后台管理中心
                </h1>
                <h2 class="h6 fw-medium fw-medium text-muted mb-0">
                    欢迎 <a class="fw-semibold" href="javascript:void(0)"><?=$_SESSION['adminUser']?></a>, 一切看起来都很棒。
                </h2>
            </div>
            <?php if($adminData['adminRank']==1){ ?>
            <div class="mt-3 mt-md-0 ms-md-3 space-x-1">
                <a class="btn btn-sm btn-alt-secondary space-x-1" data-pjax href="ulist.php">
                    <i class="fa fa-user opacity-50"></i>
                    <span>用户管理</span>
                </a>
                <a class="btn btn-sm btn-alt-secondary space-x-1" data-pjax href="smtp.php">
                    <i class="fa fa-envelope opacity-50"></i>
                    <span>邮件配置</span>
                </a>
            </div>
            <?php } ?>
        </div>
    </div>
    
    <div class="content animated fadeIn">
        <div class="row items-push">
            <div class="col-12">
                <div class="block block-rounded d-flex flex-column h-100 mb-0">
                    <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                        <dl class="mb-0">
                            <dt class="fs-3 fw-bold" id="clock">--:--:--</dt>
                            <dd class="fs-sm fw-medium text-muted mb-0" id="date">----年--月--日 星期-</dd>
                        </dl>
                        <div class="item item-rounded-lg bg-body-light">
                            <i class="fa fa-clock fs-3 text-primary"></i>
                        </div>
                    </div>
                    <div class="bg-body-light rounded-bottom">
                        <a class="block-content block-content-full block-content-sm fs-sm fw-medium d-flex align-items-center justify-content-between" href="javascript:void(0)" onclick="syncBeijingTime()">
                            <span id="beijing-time">北京时间：--:--:--</span>
                            <i class="fa fa-sync-alt ms-1 opacity-50 fs-base"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="content animated fadeIn">
        <div class="row items-push">
            <div class="col-sm-6 col-xl-3">
                <div class="block block-rounded d-flex flex-column h-100 mb-0">
                    <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                        <dl class="mb-0">
                            <dt class="fs-3 fw-bold"><?php echo $stats['user_nums'];?> 位</dt>
                            <dd class="fs-sm fw-medium text-muted mb-0">用户总数</dd>
                        </dl>
                        <div class="item item-rounded-lg bg-body-light">
                            <i class="fa fa-users fs-3 text-primary"></i>
                        </div>
                    </div>
                    <div class="bg-body-light rounded-bottom">
                        <a class="block-content block-content-full block-content-sm fs-sm fw-medium d-flex align-items-center justify-content-between" data-pjax href="ulist.php">
                            <span>今日新增: <?php echo (int)$todayStats['new_users_today'];?> 位</span>
                            <i class="fa fa-arrow-alt-circle-right ms-1 opacity-25 fs-base"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="block block-rounded d-flex flex-column h-100 mb-0">
                    <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                        <dl class="mb-0">
                            <dt class="fs-3 fw-bold"><?php echo $stats['admin_nums'];?> 位</dt>
                            <dd class="fs-sm fw-medium text-muted mb-0">管理员总数</dd>
                        </dl>
                        <div class="item item-rounded-lg bg-body-light">
                            <i class="fa fa-user-shield fs-3 text-success"></i>
                        </div>
                    </div>
                    <div class="bg-body-light rounded-bottom">
                        <a class="block-content block-content-full block-content-sm fs-sm fw-medium d-flex align-items-center justify-content-between" data-pjax href="alist.php">
                            <span>管理员列表</span>
                            <i class="fa fa-arrow-alt-circle-right ms-1 opacity-25 fs-base"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="block block-rounded d-flex flex-column h-100 mb-0">
                    <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                        <dl class="mb-0">
                            <dt class="fs-3 fw-bold"><?php echo $stats['academic_accounts'];?> 位</dt>
                            <dd class="fs-sm fw-medium text-muted mb-0">教务账号绑定</dd>
                        </dl>
                        <div class="item item-rounded-lg bg-body-light">
                            <i class="fa fa-graduation-cap fs-3 text-info"></i>
                        </div>
                    </div>
                    <div class="bg-body-light rounded-bottom">
                        <a class="block-content block-content-full block-content-sm fs-sm fw-medium d-flex align-items-center justify-content-between" data-pjax href="academic_accounts.php">
                            <span>教务账号管理</span>
                            <i class="fa fa-arrow-alt-circle-right ms-1 opacity-25 fs-base"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="block block-rounded d-flex flex-column h-100 mb-0">
                    <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                        <dl class="mb-0">
                            <dt class="fs-3 fw-bold"><?php echo $stats['dormitory_bindings'];?> 位</dt>
                            <dd class="fs-sm fw-medium text-muted mb-0">宿舍绑定</dd>
                        </dl>
                        <div class="item item-rounded-lg bg-body-light">
                            <i class="fa fa-home fs-3 text-warning"></i>
                        </div>
                    </div>
                    <div class="bg-body-light rounded-bottom">
                        <a class="block-content block-content-full block-content-sm fs-sm fw-medium d-flex align-items-center justify-content-between" data-pjax href="dormitory_bindings.php">
                            <span>宿舍绑定管理</span>
                            <i class="fa fa-arrow-alt-circle-right ms-1 opacity-25 fs-base"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="content animated fadeIn">
        <div class="row items-push">
            <div class="col-sm-6 col-xl-3">
                <div class="block block-rounded d-flex flex-column h-100 mb-0">
                    <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                        <dl class="mb-0">
                            <dt class="fs-3 fw-bold"><?php echo $stats['announcements'];?> 条</dt>
                            <dd class="fs-sm fw-medium text-muted mb-0">公告总数</dd>
                        </dl>
                        <div class="item item-rounded-lg bg-body-light">
                            <i class="fa fa-bullhorn fs-3 text-danger"></i>
                        </div>
                    </div>
                    <div class="bg-body-light rounded-bottom">
                        <a class="block-content block-content-full block-content-sm fs-sm fw-medium d-flex align-items-center justify-content-between" data-pjax href="announcements.php">
                            <span>公告管理</span>
                            <i class="fa fa-arrow-alt-circle-right ms-1 opacity-25 fs-base"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="block block-rounded d-flex flex-column h-100 mb-0">
                    <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                        <dl class="mb-0">
                            <dt class="fs-3 fw-bold text-success"><?php echo $stats['push_success'];?></dt>
                            <dd class="fs-sm fw-medium text-muted mb-0">电费推送成功</dd>
                        </dl>
                        <div class="item item-rounded-lg bg-body-light">
                            <i class="fa fa-bolt fs-3 text-success"></i>
                        </div>
                    </div>
                    <div class="bg-body-light rounded-bottom">
                        <a class="block-content block-content-full block-content-sm fs-sm fw-medium d-flex align-items-center justify-content-between" data-pjax href="power_config.php?tab=logs">
                            <span>失败: <?php echo $stats['push_failed'];?> | 今日: <?php echo (int)$todayStats['push_today'];?></span>
                            <i class="fa fa-arrow-alt-circle-right ms-1 opacity-25 fs-base"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="block block-rounded d-flex flex-column h-100 mb-0">
                    <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                        <dl class="mb-0">
                            <dt class="fs-3 fw-bold text-success"><?php echo $stats['academic_push_success'];?></dt>
                            <dd class="fs-sm fw-medium text-muted mb-0">学术推送成功</dd>
                        </dl>
                        <div class="item item-rounded-lg bg-body-light">
                            <i class="fa fa-graduation-cap fs-3 text-success"></i>
                        </div>
                    </div>
                    <div class="bg-body-light rounded-bottom">
                        <a class="block-content block-content-full block-content-sm fs-sm fw-medium d-flex align-items-center justify-content-between" data-pjax href="academic_push_config.php?tab=logs">
                            <span>失败: <?php echo $stats['academic_push_failed'];?> | 今日: <?php echo (int)$todayStats['academic_push_today'];?></span>
                            <i class="fa fa-arrow-alt-circle-right ms-1 opacity-25 fs-base"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="block block-rounded d-flex flex-column h-100 mb-0">
                    <div class="block-content block-content-full flex-grow-1 d-flex justify-content-between align-items-center">
                        <dl class="mb-0">
                            <dt class="fs-3 fw-bold"><?php echo PHP_VERSION;?></dt>
                            <dd class="fs-sm fw-medium text-muted mb-0">PHP 版本</dd>
                        </dl>
                        <div class="item item-rounded-lg bg-body-light">
                            <i class="fab fa-php fs-3 text-info"></i>
                        </div>
                    </div>
                    <div class="bg-body-light rounded-bottom">
                        <div class="block-content block-content-full block-content-sm fs-sm fw-medium d-flex align-items-center justify-content-between">
                            <span>MySQL: <?php echo $mysqlversion;?></span>
                            <i class="fas fa-database ms-1 opacity-50 fs-base"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="content animated fadeIn">
        <div class="row">
            <div class="col-xl-6 p-3">
                <div class="block block-rounded h-100 mb-4">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">
                            <i class="fa fa-tachometer-alt me-2"></i>快捷操作
                        </h3>
                    </div>
                    <div class="block-content">
                        <div class="row g-3">
                            <div class="col-6">
                                <a data-pjax href="announcements.php" class="btn btn-alt-primary w-100 py-3">
                                    <i class="fa fa-bullhorn me-2"></i>发布公告
                                </a>
                            </div>
                            <div class="col-6">
                                <a data-pjax href="power_config.php" class="btn btn-alt-info w-100 py-3">
                                    <i class="fa fa-bolt me-2"></i>电费配置
                                </a>
                            </div>
                            <div class="col-6">
                                <a data-pjax href="academic_push_config.php" class="btn btn-alt-success w-100 py-3">
                                    <i class="fa fa-graduation-cap me-2"></i>学术推送
                                </a>
                            </div>
                            <div class="col-6">
                                <a data-pjax href="feature_settings.php" class="btn btn-alt-warning w-100 py-3">
                                    <i class="fa fa-cog me-2"></i>功能设置
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="block block-rounded h-100 mb-0">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">
                            <i class="fa fa-server me-2"></i>系统信息
                        </h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-sm table-vcenter">
                            <tbody>
                                <tr>
                                    <td class="text-muted" style="width: 120px;">系统名称</td>
                                    <td class="fw-semibold"><?php echo conf('Name');?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">服务器系统</td>
                                    <td class="fw-semibold"><?php echo PHP_OS;?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Web 服务器</td>
                                    <td class="fw-semibold"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">PHP 版本</td>
                                    <td class="fw-semibold"><?php echo PHP_VERSION;?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">MySQL 版本</td>
                                    <td class="fw-semibold"><?php echo $mysqlversion;?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">服务器时间</td>
                                    <td class="fw-semibold"><?php echo date('Y-m-d H:i:s');?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateClock() {
    var now = new Date();
    var hours = String(now.getHours()).padStart(2, '0');
    var minutes = String(now.getMinutes()).padStart(2, '0');
    var seconds = String(now.getSeconds()).padStart(2, '0');
    
    document.getElementById('clock').textContent = hours + ':' + minutes + ':' + seconds;
    
    var year = now.getFullYear();
    var month = String(now.getMonth() + 1).padStart(2, '0');
    var day = String(now.getDate()).padStart(2, '0');
    var weekDays = ['日', '一', '二', '三', '四', '五', '六'];
    var weekDay = weekDays[now.getDay()];
    
    document.getElementById('date').textContent = year + '年' + month + '月' + day + '日 星期' + weekDay;
}

function syncBeijingTime() {
    var now = new Date();
    var hours = String(now.getHours()).padStart(2, '0');
    var minutes = String(now.getMinutes()).padStart(2, '0');
    var seconds = String(now.getSeconds()).padStart(2, '0');
    
    document.getElementById('beijing-time').textContent = '北京时间：' + hours + ':' + minutes + ':' + seconds;
}

updateClock();
syncBeijingTime();
setInterval(updateClock, 1000);
setInterval(syncBeijingTime, 1000);
</script>

<?php include 'foot.php'?>
