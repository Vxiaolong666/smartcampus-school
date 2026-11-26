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

// 首页轮播图：与小程序/uniapp 使用同一张配置表 nteam_banners
$banners = array();
try {
    $bannerTableChk = $DB->query("SHOW TABLES LIKE 'nteam_banners'");
    if ($bannerTableChk && $bannerTableChk->fetch()) {
        $rows = $DB->getAll("SELECT * FROM nteam_banners WHERE `status`=1 ORDER BY `sort` ASC, `id` ASC");
        foreach ($rows as $row) {
            if (!empty($row['image'])) {
                $banners[] = $row;
            }
        }
    }
} catch (Exception $e) {
    $banners = array();
}

$sceneTypeNames = [
    'schedule_reminder' => '课程提醒',
    'grade_notify' => '成绩通知',
    'power_fee' => '电费提醒',
    'power_report' => '电费报告',
    'subscription_alert' => '额度提醒'
];

$weekdayNames = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];
$todayText = date('n月j日') . ' ' . $weekdayNames[intval(date('w'))];
$emailVerified = intval($userData['email_verified']) === 1;
$academicBound = !empty($academicAccount);
$dormitoryBound = intval($dormitoryCount) > 0;
?>
<style>
/* ===== 用户中心首页：沿用后台卡片与配色体系 ===== */
.user-home-hero {
    background: linear-gradient(135deg, #eef4ff 0%, #f7faff 55%, #ffffff 100%);
    border-bottom: 1px solid #e9edf4;
    padding: 28px 32px;
}
.user-home-avatar {
    width: 64px; height: 64px; border-radius: 16px;
    background: linear-gradient(135deg, #409eff, #337ecc);
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: 32px; box-shadow: 0 6px 18px rgba(64, 158, 255, .25);
    flex-shrink: 0;
}
.user-home-title { color: #1f2740; }
.user-home-stat-icon,
.user-home-quick-icon {
    width: 48px; height: 48px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; flex-shrink: 0;
}
.user-home-stat-icon.primary, .user-home-quick-icon.primary { background: #eaf2ff; color: #337ecc; }
.user-home-stat-icon.info, .user-home-quick-icon.info { background: #e6f7fb; color: #0c8ca8; }
.user-home-stat-icon.warning, .user-home-quick-icon.warning { background: #fff4e5; color: #d97706; }
.user-home-stat-icon.success, .user-home-quick-icon.success { background: #e9f7ef; color: #1e9e6a; }
.user-home-stat-icon.danger, .user-home-quick-icon.danger { background: #fff0f0; color: #d9455f; }
.user-home-quick {
    display: block; height: 100%; padding: 18px;
    border: 1px solid #edf0f5; border-radius: 12px;
    text-decoration: none; transition: all .2s ease;
    background: #fff;
}
.user-home-quick:hover {
    border-color: #cfe0ff; box-shadow: 0 6px 18px rgba(30, 50, 90, .08);
    transform: translateY(-2px); text-decoration: none;
}
.user-home-quick .quick-title { color: #1f2740; font-weight: 600; }
.user-home-quick .quick-desc { color: #8a93a5; font-size: 12px; margin-top: 3px; }
.user-home-quick:hover .quick-title { color: #337ecc; }
.user-home-banner .user-banner-block { max-width: 100%; }
@media (max-width: 575px) {
    .user-home-hero { padding: 20px; }
    .user-home-avatar { width: 52px; height: 52px; font-size: 26px; border-radius: 12px; }
}
</style>

<div id="pjax-container">
    <!-- 欢迎区 -->
    <div class="content animated fadeIn">
        <div class="block block-rounded mb-0 overflow-hidden">
            <div class="user-home-hero">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="user-home-avatar"><i class="fa fa-user-circle"></i></div>
                        <div class="ms-3">
                            <h1 class="h3 fw-bold mb-1 user-home-title">欢迎回来，<?php echo htmlspecialchars($userData['username'], ENT_QUOTES, 'UTF-8'); ?></h1>
                            <div class="fs-sm text-muted">
                                <i class="fa fa-envelope me-1 opacity-50"></i><?php echo htmlspecialchars($userData['email'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php if($emailVerified): ?>
                                    <span class="badge bg-success ms-2">已验证</span>
                                <?php else: ?>
                                    <span class="badge bg-warning ms-2">未验证</span>
                                <?php endif; ?>
                            </div>
                            <div class="mt-2 d-flex align-items-center flex-wrap gap-2">
                                <span class="badge bg-body-light text-muted fw-semibold">
                                    <i class="fa fa-calendar-alt me-1"></i><?php echo $todayText; ?>
                                </span>
                                <span class="badge text-bg-primary fw-semibold" id="userWeekBadge" style="display:none;"></span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 mt-md-0">
                        <a href="profile.php" class="btn btn-alt-primary">
                            <i class="fa fa-user-edit opacity-50 me-1"></i>个人资料
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if(!empty($banners)): ?>
    <style>
        /* 用户中心轮播图（与小程序/uniapp 同一套配置） */
        .user-banner-block {
            max-width: 720px;
            margin: 0 auto;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
            background: #f4f5f9;
        }
        .user-banner-carousel {
            position: relative;
            width: 100%;
            padding-bottom: 48%;
            overflow: hidden;
            touch-action: pan-y;
        }
        .user-banner-track {
            position: absolute; top: 0; left: 0;
            width: 100%; height: 100%;
            display: flex; transition: transform .5s ease; will-change: transform;
        }
        .user-banner-slide { flex: 0 0 100%; height: 100%; }
        .user-banner-slide img {
            width: 100%; height: 100%; object-fit: cover; display: block; cursor: pointer;
            -webkit-user-select: none; user-select: none; -webkit-user-drag: none;
        }
        .user-banner-dots {
            position: absolute; left: 0; right: 0; bottom: 12px;
            display: flex; justify-content: center; align-items: center; gap: 6px;
        }
        .user-banner-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: rgba(255, 255, 255, .55); box-shadow: 0 0 4px rgba(0, 0, 0, .25);
            cursor: pointer; transition: all .3s ease;
        }
        .user-banner-dot.active { width: 20px; border-radius: 4px; background: #fff; }
        .user-banner-arrow {
            position: absolute; top: 50%; transform: translateY(-50%);
            width: 38px; height: 38px; border-radius: 50%;
            background: rgba(0, 0, 0, .25); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; line-height: 1; cursor: pointer; z-index: 2;
            opacity: .85; transition: background .25s ease, opacity .25s ease;
            -webkit-user-select: none; user-select: none;
        }
        .user-banner-arrow:hover { background: rgba(0, 0, 0, .5); opacity: 1; }
        .user-banner-arrow-prev { left: 12px; }
        .user-banner-arrow-next { right: 12px; }
    </style>
    <div class="content user-home-banner animated fadeIn">
        <div class="user-banner-block">
            <div class="user-banner-carousel" id="userBannerCarousel">
                <div class="user-banner-track" id="userBannerTrack">
                    <?php foreach($banners as $b):
                        $bannerImg = $b['image'];
                        if (!empty($bannerImg) && strpos($bannerImg, 'http') !== 0) {
                            $bannerImg = $siteBasePath . $bannerImg;
                        }
                        $bannerUrl = isset($b['url_web']) ? $b['url_web'] : '';
                        if ($bannerUrl === '') {
                            $genericUrl = isset($b['url']) ? $b['url'] : '';
                            if (preg_match('#^https?://#i', $genericUrl)) {
                                $bannerUrl = $genericUrl;
                            }
                        }
                    ?>
                    <div class="user-banner-slide" data-url="<?php echo htmlspecialchars($bannerUrl, ENT_QUOTES, 'UTF-8'); ?>">
                        <img src="<?php echo htmlspecialchars($bannerImg, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($b['title'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if(count($banners) > 1): ?>
                <div class="user-banner-arrow user-banner-arrow-prev" id="userBannerPrev" aria-label="上一张"><i class="fa fa-angle-left"></i></div>
                <div class="user-banner-arrow user-banner-arrow-next" id="userBannerNext" aria-label="下一张"><i class="fa fa-angle-right"></i></div>
                <div class="user-banner-dots" id="userBannerDots">
                    <?php foreach($banners as $i => $b): ?>
                    <span class="user-banner-dot<?php echo $i === 0 ? ' active' : ''; ?>" data-index="<?php echo intval($i); ?>"></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 状态概览 -->
    <div class="content animated fadeIn">
        <div class="row items-push">
            <div class="col-6 col-lg-3">
                <div class="block block-rounded h-100 mb-0">
                    <div class="block-content block-content-full d-flex align-items-center">
                        <div class="user-home-stat-icon primary me-3"><i class="fa fa-graduation-cap"></i></div>
                        <div style="min-width: 0;">
                            <div class="fs-lg fw-bold"><?php echo $academicBound ? '已绑定' : '未绑定'; ?></div>
                            <div class="fs-sm text-muted">教务账号</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="block block-rounded h-100 mb-0">
                    <div class="block-content block-content-full d-flex align-items-center">
                        <div class="user-home-stat-icon info me-3"><i class="fa fa-home"></i></div>
                        <div style="min-width: 0;">
                            <div class="fs-lg fw-bold"><?php echo $dormitoryBound ? $dormitoryCount . ' 个' : '未绑定'; ?></div>
                            <div class="fs-sm text-muted">宿舍绑定</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="block block-rounded h-100 mb-0">
                    <div class="block-content block-content-full d-flex align-items-center">
                        <div class="user-home-stat-icon warning me-3"><i class="fa fa-bell"></i></div>
                        <div style="min-width: 0;">
                            <div class="fs-lg fw-bold"><?php echo count($lowSubscriptions); ?> 项</div>
                            <div class="fs-sm text-muted">订阅待补充</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="block block-rounded h-100 mb-0">
                    <div class="block-content block-content-full d-flex align-items-center">
                        <div class="user-home-stat-icon success me-3"><i class="fa fa-envelope"></i></div>
                        <div style="min-width: 0;">
                            <div class="fs-lg fw-bold"><?php echo $emailVerified ? '已验证' : '未验证'; ?></div>
                            <div class="fs-sm text-muted">邮箱状态</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if(!empty($lowSubscriptions)): ?>
    <div class="content animated fadeIn">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-bell text-warning me-2"></i>订阅次数不足提醒</h3>
            </div>
            <div class="block-content block-content-full">
                <div class="alert alert-warning mb-3">
                    <i class="fa fa-exclamation-triangle me-2"></i>
                    以下订阅消息类型的剩余次数不足，请及时在小程序中增加订阅，以免错过重要通知。
                </div>
                <div class="row items-push">
                    <?php foreach($lowSubscriptions as $sub): ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="d-flex align-items-center p-3 bg-body-light rounded">
                            <div class="flex-grow-1" style="min-width: 0;">
                                <div class="fw-semibold text-truncate"><?php echo htmlspecialchars($sub['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <small class="text-muted">
                                    <?php echo isset($sceneTypeNames[$sub['scene_type']]) ? $sceneTypeNames[$sub['scene_type']] : '通知'; ?>
                                </small>
                            </div>
                            <span class="badge <?php echo $sub['subscribe_count'] > 0 ? 'bg-warning' : 'bg-danger'; ?> fs-6 ms-3">
                                <?php echo intval($sub['subscribe_count']); ?> 次
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-2">
                    <a href="subscription.php" class="btn btn-warning">
                        <i class="fab fa-weixin me-1"></i>查看订阅详情
                    </a>
                    <span class="text-muted ms-3 fs-sm">在微信小程序中点击对应模板的「立即订阅」即可增加次数</span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 快捷入口 -->
    <div class="content animated fadeIn">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-th-large text-primary me-2"></i>快捷入口</h3>
            </div>
            <div class="block-content block-content-full">
                <div class="row items-push">
                    <div class="col-sm-6 col-xl-3">
                        <a class="user-home-quick" href="grade.php">
                            <div class="d-flex align-items-center">
                                <div class="user-home-quick-icon primary"><i class="fa fa-chart-bar"></i></div>
                                <div class="ms-3 flex-grow-1">
                                    <div class="quick-title">成绩查询</div>
                                    <div class="quick-desc">各学期成绩与绩点</div>
                                </div>
                                <i class="fa fa-chevron-right text-muted opacity-50"></i>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <a class="user-home-quick" href="schedule.php">
                            <div class="d-flex align-items-center">
                                <div class="user-home-quick-icon info"><i class="fa fa-calendar-alt"></i></div>
                                <div class="ms-3 flex-grow-1">
                                    <div class="quick-title">课程表</div>
                                    <div class="quick-desc">按周查看课程安排</div>
                                </div>
                                <i class="fa fa-chevron-right text-muted opacity-50"></i>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <a class="user-home-quick" href="student_info.php">
                            <div class="d-flex align-items-center">
                                <div class="user-home-quick-icon success"><i class="fa fa-id-card"></i></div>
                                <div class="ms-3 flex-grow-1">
                                    <div class="quick-title">学籍信息</div>
                                    <div class="quick-desc">查看个人学籍档案</div>
                                </div>
                                <i class="fa fa-chevron-right text-muted opacity-50"></i>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <a class="user-home-quick" href="academic_bind.php">
                            <div class="d-flex align-items-center">
                                <div class="user-home-quick-icon primary"><i class="fa fa-graduation-cap"></i></div>
                                <div class="ms-3 flex-grow-1">
                                    <div class="quick-title">教务账号</div>
                                    <div class="quick-desc"><?php echo $academicBound ? '管理已绑定账号' : '绑定后解锁学业功能'; ?></div>
                                </div>
                                <i class="fa fa-chevron-right text-muted opacity-50"></i>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <a class="user-home-quick" href="power_fee.php">
                            <div class="d-flex align-items-center">
                                <div class="user-home-quick-icon warning"><i class="fa fa-bolt"></i></div>
                                <div class="ms-3 flex-grow-1">
                                    <div class="quick-title">电费查询</div>
                                    <div class="quick-desc">宿舍电费与余额</div>
                                </div>
                                <i class="fa fa-chevron-right text-muted opacity-50"></i>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <a class="user-home-quick" href="dormitory_bind.php">
                            <div class="d-flex align-items-center">
                                <div class="user-home-quick-icon info"><i class="fa fa-home"></i></div>
                                <div class="ms-3 flex-grow-1">
                                    <div class="quick-title">宿舍绑定</div>
                                    <div class="quick-desc"><?php echo $dormitoryBound ? '管理已绑定宿舍' : '绑定宿舍查询电费'; ?></div>
                                </div>
                                <i class="fa fa-chevron-right text-muted opacity-50"></i>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <a class="user-home-quick" href="edit_pwd.php">
                            <div class="d-flex align-items-center">
                                <div class="user-home-quick-icon primary"><i class="fa fa-key"></i></div>
                                <div class="ms-3 flex-grow-1">
                                    <div class="quick-title">修改密码</div>
                                    <div class="quick-desc">保障账号安全</div>
                                </div>
                                <i class="fa fa-chevron-right text-muted opacity-50"></i>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <a class="user-home-quick" href="cancel_account.php">
                            <div class="d-flex align-items-center">
                                <div class="user-home-quick-icon danger"><i class="fa fa-user-slash"></i></div>
                                <div class="ms-3 flex-grow-1">
                                    <div class="quick-title">注销账户</div>
                                    <div class="quick-desc">了解注销规则与冷静期</div>
                                </div>
                                <i class="fa fa-chevron-right text-muted opacity-50"></i>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 账户与安全 -->
    <div class="content animated fadeIn">
        <div class="row items-push">
            <div class="col-lg-7">
                <div class="block block-rounded h-100 mb-0">
                    <div class="block-header block-header-default">
                        <h3 class="block-title"><i class="fa fa-id-badge text-primary me-2"></i>账户信息</h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-borderless table-vcenter mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted fw-semibold" style="width: 130px;">用户名</td>
                                    <td><?php echo htmlspecialchars($userData['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-semibold">邮箱</td>
                                    <td>
                                        <?php echo htmlspecialchars($userData['email'], ENT_QUOTES, 'UTF-8'); ?>
                                        <?php if($emailVerified): ?>
                                            <span class="badge bg-success ms-2">已验证</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning ms-2">未验证</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-semibold">QQ</td>
                                    <td><?php echo htmlspecialchars($userData['qq'] ? $userData['qq'] : '未设置', ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-semibold">教务账号</td>
                                    <td>
                                        <?php if($academicBound): ?>
                                            <?php echo htmlspecialchars($academicAccount['student_id'], ENT_QUOTES, 'UTF-8'); ?>
                                            <?php if(!empty($academicAccount['real_name'])): ?>
                                                <span class="text-muted ms-2">(<?php echo htmlspecialchars($academicAccount['real_name'], ENT_QUOTES, 'UTF-8'); ?>)</span>
                                            <?php endif; ?>
                                            <span class="badge bg-success ms-2">已绑定</span>
                                        <?php else: ?>
                                            <span class="text-muted">未绑定</span>
                                            <a href="academic_bind.php" class="btn btn-sm btn-alt-primary ms-2">
                                                <i class="fa fa-link me-1"></i>立即绑定
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-semibold">绑定时间</td>
                                    <td><?php echo $academicBound && !empty($academicAccount['bind_time']) ? htmlspecialchars($academicAccount['bind_time'], ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-semibold">注册时间</td>
                                    <td><?php echo $userData['register_time'] ? htmlspecialchars($userData['register_time'], ENT_QUOTES, 'UTF-8') : '未知'; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="block block-rounded h-100 mb-0">
                    <div class="block-header block-header-default">
                        <h3 class="block-title"><i class="fa fa-shield-alt text-success me-2"></i>登录与安全</h3>
                    </div>
                    <div class="block-content block-content-full">
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="text-muted fs-sm">最后登录</span>
                            <span class="fw-semibold"><?php echo $userData['last_login_time'] ? htmlspecialchars($userData['last_login_time'], ENT_QUOTES, 'UTF-8') : '未知'; ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="text-muted fs-sm">登录 IP</span>
                            <span class="fw-semibold"><?php echo $userData['last_login_ip'] ? htmlspecialchars($userData['last_login_ip'], ENT_QUOTES, 'UTF-8') : '未知'; ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-2">
                            <span class="text-muted fs-sm">账号状态</span>
                            <span class="badge bg-success">正常</span>
                        </div>
                        <div class="d-grid gap-2 mt-3">
                            <a href="edit_pwd.php" class="btn btn-alt-primary"><i class="fa fa-key opacity-50 me-1"></i>修改登录密码</a>
                            <a href="profile.php" class="btn btn-alt-secondary"><i class="fa fa-user-edit opacity-50 me-1"></i>编辑个人资料</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if(!empty($_SESSION['cancelRevoked'])): nteam_session_unset('cancelRevoked'); ?>
<script>
$(function(){ layer.alert('您的注销申请已自动撤销，账号已恢复正常。', {icon: 1}); });
</script>
<?php endif; ?>

<script>
(function () {
    function initUserBanner() {
        var carousel = document.getElementById('userBannerCarousel');
        if (!carousel || carousel.getAttribute('data-init') === '1') return;
        carousel.setAttribute('data-init', '1');

        var track = carousel.querySelector('.user-banner-track');
        var slides = carousel.querySelectorAll('.user-banner-slide');
        var dots = carousel.querySelectorAll('.user-banner-dot');
        var count = slides.length;
        if (!track || count === 0) return;

        var index = 0;
        var timer = null;
        var INTERVAL = 4000;
        var startX = 0;
        var moved = false;

        function render() {
            track.style.transform = 'translateX(' + (-index * 100) + '%)';
            for (var i = 0; i < dots.length; i++) {
                dots[i].className = 'user-banner-dot' + (i === index ? ' active' : '');
            }
        }
        function goTo(i) {
            index = (i + count) % count;
            render();
        }
        function next() { goTo(index + 1); }
        function stop() {
            if (timer) { clearInterval(timer); timer = null; }
        }
        function play() {
            stop();
            if (count > 1) timer = setInterval(next, INTERVAL);
        }

        for (var d = 0; d < dots.length; d++) {
            (function (di) {
                dots[di].addEventListener('click', function () { goTo(di); play(); });
            })(d);
        }

        var prevBtn = carousel.querySelector('.user-banner-arrow-prev');
        var nextBtn = carousel.querySelector('.user-banner-arrow-next');
        if (prevBtn) prevBtn.addEventListener('click', function () { goTo(index - 1); play(); });
        if (nextBtn) nextBtn.addEventListener('click', function () { goTo(index + 1); play(); });

        for (var s = 0; s < count; s++) {
            (function (slide) {
                slide.addEventListener('click', function () {
                    if (moved) return;
                    var url = slide.getAttribute('data-url');
                    if (!url) return;
                    if (/^https?:\/\//i.test(url)) {
                        window.open(url, '_blank');
                    } else {
                        window.location.href = url;
                    }
                });
            })(slides[s]);
        }

        carousel.addEventListener('touchstart', function (e) {
            startX = e.touches[0].clientX;
            moved = false;
            stop();
        }, { passive: true });
        carousel.addEventListener('touchmove', function (e) {
            if (Math.abs(e.touches[0].clientX - startX) > 10) moved = true;
        }, { passive: true });
        carousel.addEventListener('touchend', function (e) {
            var dx = e.changedTouches[0].clientX - startX;
            if (Math.abs(dx) > 40) goTo(dx < 0 ? index + 1 : index - 1);
            play();
        });

        carousel.addEventListener('mouseenter', stop);
        carousel.addEventListener('mouseleave', play);

        render();
        play();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initUserBanner);
    } else {
        initUserBanner();
    }
    if (window.jQuery) {
        jQuery(document).on('pjax:end pjax:complete', initUserBanner);
    }
})();
</script>

<script>
// 今日日期旁的「当前第*周」：复用首页速览接口（教务未返回周次时保持隐藏）
(function () {
    function initUserWeekBadge() {
        var badge = document.getElementById('userWeekBadge');
        if (!badge || badge.getAttribute('data-init') === '1') return;
        badge.setAttribute('data-init', '1');

        jQuery.get('../api.php?act=get_glance_data', function (res) {
            var week = (res && res.code === 0 && res.data) ? parseInt(res.data.current_week, 10) : 0;
            if (!week || week <= 0) return;
            badge.textContent = '当前第' + week + '周';
            badge.style.display = '';
        }, 'json');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initUserWeekBadge);
    } else {
        initUserWeekBadge();
    }
    if (window.jQuery) {
        jQuery(document).on('pjax:end pjax:complete', initUserWeekBadge);
    }
})();
</script>
<?php include 'foot.php'; ?>
