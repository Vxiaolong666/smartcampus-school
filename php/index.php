<?php
/**
 * 项目首页（公开落地页）
 * 复用 user/ 端同一套 OneUI 视觉风格，保持全站风格一致
 */
$mod = 'index';
include('./Common/Core_brain.php');

$siteName = conf('Name') ?: '校园服务';
$title = $siteName . ' - 校园服务一站式平台';

// 读取最新公告（容错，表不存在或为空时不影响首页展示）
$announcements = [];
try {
    $rows = $DB->getAll("SELECT id, title, create_time FROM nteam_announcements WHERE is_published=1 ORDER BY is_top DESC, create_time DESC LIMIT 5");
    if ($rows) {
        $announcements = $rows;
    }
} catch (Exception $e) {
    $announcements = [];
}

$features = [
    ['icon' => 'fa-calendar-day',   'color' => 'text-primary',   'title' => '课程提醒',     'desc' => '每日推送明日课程安排，上课时间、地点一目了然，不再错过。',       'url' => './user/today_schedule.php'],
    ['icon' => 'fa-chart-bar',      'color' => 'text-primary',   'title' => '成绩查询',     'desc' => '快速查询各科成绩与绩点变化，学业动态尽在掌握。',               'url' => './user/grade.php'],
    ['icon' => 'fa-bolt',           'color' => 'text-warning',   'title' => '电费查询',     'desc' => '实时查询宿舍用电余额与明细，低余额及时提醒，用电无忧。',       'url' => './user/power_fee.php'],
    ['icon' => 'fa-home',           'color' => 'text-info',      'title' => '宿舍管理',     'desc' => '绑定宿舍并管理多间寝室，统一查看用电统计与推送设置。',         'url' => './user/dormitory_bind.php'],
    ['icon' => 'fa-bell',           'color' => 'text-primary',   'title' => '学术推送',     'desc' => '自定义课程提醒与成绩发布通知，微信/邮件多渠道触达。',          'url' => './user/academic_push_settings.php'],
    ['icon' => 'fab fa-weixin',     'color' => 'text-success',   'title' => '订阅消息',     'desc' => '订阅微信模板消息，重要通知实时送达，不错过任何校园动态。',     'url' => './user/subscription.php'],
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="author" content="一个失踪的Ma.">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <meta name="keywords" content="<?php echo htmlspecialchars(conf('Keywords') ?? ''); ?>">
    <meta name="description" content="<?php echo htmlspecialchars(conf('Description') ?? ''); ?>">
    <link rel="shortcut icon" href="/assets/media/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="./user/assets/css/oneui.min-5.6.css">
    <link rel="stylesheet" href="./user/assets/css/quick-website.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "PingFang SC", "Microsoft YaHei", sans-serif; }
        .hero {
            background: linear-gradient(135deg, #0b2e4f 0%, #146c94 55%, #13c2c2 100%);
            color: #fff;
        }
        .hero .display-4 { letter-spacing: .5px; }
        .feature-icon {
            width: 64px; height: 64px; border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            background: rgba(19,194,194,.12);
        }
        .feature-card { transition: transform .15s ease, box-shadow .15s ease; }
        .feature-card:hover { transform: translateY(-4px); box-shadow: 0 .5rem 1.5rem rgba(0,0,0,.08); }
        .bg-soft-primary { background-color: rgba(19,194,194,.08); }
        .cta-banner {
            background: linear-gradient(135deg, #13c2c2, #146c94);
        }
    </style>
</head>
<body>

<!-- 顶部导航 -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand fw-bold fs-4" href="./index.php">
            <i class="fa fa-graduation-cap me-2"></i><?php echo htmlspecialchars($siteName); ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="切换导航">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="#features">功能特色</a></li>
                <li class="nav-item"><a class="nav-link" href="./user/announcements.php">公告动态</a></li>
                <li class="nav-item"><a class="nav-link" href="#contact">联系我们</a></li>
            </ul>
            <div class="d-flex">
                <a href="./user/login.php" class="btn btn-outline-light me-2">登录</a>
                <a href="./user/register.php" class="btn btn-light">注册</a>
            </div>
        </div>
    </div>
</nav>

<!-- Hero 区 -->
<header class="hero">
    <div class="container py-6 py-md-7 text-center">
        <span class="badge bg-white-10 text-white px-3 py-2 mb-3">校园服务一站式平台</span>
        <h1 class="display-4 fw-bold mb-3"><?php echo htmlspecialchars($siteName); ?></h1>
        <p class="lead fs-4 opacity-75 mb-4">课程提醒 · 成绩通知 · 电费查询 · 宿舍管理，校园生活尽在指尖</p>
        <div class="d-flex flex-column flex-sm-row justify-content-center gap-2">
            <a href="./user/login.php" class="btn btn-light btn-lg px-4">
                <i class="fa fa-sign-in-alt me-1"></i> 进入控制台
            </a>
            <a href="./user/register.php" class="btn btn-lg px-4 text-white" style="background:rgba(255,255,255,.15);">
                <i class="fa fa-user-plus me-1"></i> 免费注册
            </a>
        </div>
    </div>
</header>

<!-- 功能特色 -->
<section class="content" id="features">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">核心功能</h2>
            <p class="text-muted">围绕校园生活场景，提供实用、及时的数字化服务</p>
        </div>
        <div class="row items-push">
            <?php foreach ($features as $f): ?>
            <div class="col-md-6 col-xl-4 mb-4">
                <a class="block block-rounded feature-card h-100 text-decoration-none" href="<?php echo $f['url']; ?>">
                    <div class="block-content p-4">
                        <div class="feature-icon mb-3">
                            <i class="fa <?php echo $f['icon']; ?> fa-2x <?php echo $f['color']; ?>"></i>
                        </div>
                        <h4 class="fw-bold mb-2"><?php echo htmlspecialchars($f['title']); ?></h4>
                        <p class="text-muted mb-3"><?php echo htmlspecialchars($f['desc']); ?></p>
                        <span class="fw-semibold <?php echo $f['color']; ?>">立即体验 <i class="fa fa-arrow-right ms-1"></i></span>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 最新公告 -->
<section class="content bg-body-light" id="announcements">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h2 class="fw-bold mb-0">最新公告</h2>
            <a href="./user/announcements.php" class="btn btn-sm btn-alt-primary">查看全部</a>
        </div>
        <?php if (!empty($announcements)): ?>
            <div class="block block-rounded">
                <div class="block-content block-content-full">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($announcements as $a): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <a class="fw-medium text-dark text-decoration-none" href="./user/announcements.php">
                                <i class="fa fa-bullhorn text-primary me-2"></i><?php echo htmlspecialchars($a['title']); ?>
                            </a>
                            <span class="text-muted fs-sm"><?php echo date('Y-m-d', strtotime($a['create_time'])); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php else: ?>
            <div class="block block-rounded">
                <div class="block-content text-center text-muted py-4">
                    <i class="fa fa-inbox fa-2x mb-2 opacity-50"></i>
                    <p class="mb-0">暂无公告</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- CTA -->
<section class="cta-banner text-white">
    <div class="container py-5 text-center" id="contact">
        <h2 class="fw-bold mb-2">准备好开始了吗？</h2>
        <p class="opacity-75 mb-4">立即注册，绑定你的校园账号，体验智能推送与查询服务。</p>
        <a href="./user/register.php" class="btn btn-light btn-lg px-4">
            <i class="fa fa-rocket me-1"></i> 立即注册体验
        </a>
    </div>
</section>

<!-- 页脚 -->
<footer class="bg-body-light border-top">
    <div class="content py-3">
        <div class="row fs-sm">
            <div class="col-sm-6 order-sm-2 py-1 text-center text-sm-end">
                <a class="fw-semibold" href="https://beian.miit.gov.cn/" target="_blank"><?php echo htmlspecialchars(conf('ICP') ?? ''); ?></a>
            </div>
            <div class="col-sm-6 order-sm-1 py-1 text-center text-sm-start">
                &copy; <?php echo date('Y'); ?> <a class="fw-semibold" href="//<?php echo htmlspecialchars(conf('Url') ?? ''); ?>" target="_blank"><?php echo htmlspecialchars($siteName); ?></a>
            </div>
        </div>
    </div>
</footer>

<script src="./user/assets/js/lib/jquery.min.js"></script>
<script src="./user/assets/js/oneui.app.min-5.6.js"></script>
</body>
</html>
