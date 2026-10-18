<?php
/**
 * 每日例行维护时段（网页版 user 端）
 *
 * 与客户端保持一致：
 *  - 时段取自 nteam_config.Maintenance_Start / Maintenance_End（后台「网站信息配置 → 每日维护时段」）；
 *  - 留空 / 非法值 → 内置默认 23:00 ~ 06:00（与 api.php 的 get_maintenance_config 相同）；
 *  - 支持跨天（如 22:00 ~ 次日 08:00）。
 *
 * 与「管理员手动维护」的关系：
 *  - 本文件只负责「每日例行维护时段」对指定页面的访问限制；
 *  - 管理员手动维护（feature_settings 的 weihu_open）逻辑不受影响，仍按原有方式处理。
 *
 * 维护时段内仅以下页面禁止访问，其余页面正常使用：
 *  本周课程 / 成绩查询 / 课程查询 / 学籍查询 / 学术推送 / 电费查询
 */

// 维护公告需要复用功能公告的取值逻辑（含「总公告」覆盖 / follow_global）
require_once(__DIR__ . '/feature_settings.php');

if (!defined('MAINTENANCE_WINDOW_INC')) {
    define('MAINTENANCE_WINDOW_INC', true);

    if (!defined('MAINTENANCE_DEFAULT_START')) {
        define('MAINTENANCE_DEFAULT_START', '23:00');
        define('MAINTENANCE_DEFAULT_END', '06:00');
    }

    /**
     * 维护期间始终放行的页面（登录 / 注册 / 找回密码等账号入口）
     *
     * 这些页面目前都不经过 user/head.php，本不受维护守卫影响；
     * 这里显式列出，避免后续改动把它们接入公共头部后被误拦。
     */
    function maintenanceExemptPages()
    {
        return array(
            'login.php',          // 登录
            'register.php',       // 注册
            'forgot.php',         // 找回密码
            'reset_password.php', // 重置密码
            'verify.php',         // 邮箱验证
            'send_verify.php',    // 发送验证码
        );
    }

    /**
     * 当前脚本是否属于维护期间始终放行的页面
     *
     * @return bool
     */
    function isMaintenanceExemptPage()
    {
        $script = isset($_SERVER['SCRIPT_NAME']) ? basename($_SERVER['SCRIPT_NAME']) : '';
        if ($script === '') return false;
        return in_array($script, maintenanceExemptPages(), true);
    }

    /**
     * 每日维护期间禁止访问的页面（脚本名 => 展示名称）
     * 注意：仅加入教务 / 宿舍查询类页面，其他页面不在此列。
     */
    function maintenanceBlockedPages()
    {
        return array(
            'today_schedule.php'         => '本周课程',
            'grade.php'                  => '成绩查询',
            'schedule.php'               => '课程查询',
            'student_info.php'           => '学籍查询',
            'academic_push_settings.php' => '学术推送',
            'power_fee.php'              => '电费查询',
        );
    }

    /**
     * 读取每日维护时段
     *
     * @param \lib\PdoHelper $DB
     * @return array ['start' => 'HH:MM', 'end' => 'HH:MM']
     */
    function maintenanceWindow($DB)
    {
        $start = '';
        $end   = '';
        try {
            $rs = @$DB->query("SELECT `Maintenance_Start`, `Maintenance_End` FROM nteam_config WHERE id=1");
            if ($rs) {
                $row = $rs->fetch();
                if ($row) {
                    $start = trim((string)$row['Maintenance_Start']);
                    $end   = trim((string)$row['Maintenance_End']);
                }
            }
        } catch (Exception $e) {
            // 字段未升级时走内置默认值
        }
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $start)) $start = MAINTENANCE_DEFAULT_START;
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $end))   $end   = MAINTENANCE_DEFAULT_END;
        return array('start' => $start, 'end' => $end);
    }

    /** HH:MM 转当日分钟数 */
    function maintenanceToMinutes($hhmm)
    {
        $parts = explode(':', (string)$hhmm);
        $h = isset($parts[0]) ? intval($parts[0]) : 0;
        $m = isset($parts[1]) ? intval($parts[1]) : 0;
        return $h * 60 + $m;
    }

    /**
     * 当前时间是否处于维护时段（支持跨天）
     *
     * @param string      $start 开始时间 HH:MM
     * @param string      $end   结束时间 HH:MM
     * @param string|null $now   指定当前时间 HH:MM，null 取服务器当前时间
     * @return bool
     */
    function isInMaintenanceWindow($start, $end, $now = null)
    {
        $startMin = maintenanceToMinutes($start);
        $endMin   = maintenanceToMinutes($end);
        if ($startMin === $endMin) return false; // 起止相同 → 视为未启用
        $nowMin = ($now === null)
            ? (intval(date('H')) * 60 + intval(date('i')))
            : maintenanceToMinutes($now);
        if ($startMin < $endMin) {
            // 同日区间，如 08:00 ~ 12:00
            return $nowMin >= $startMin && $nowMin < $endMin;
        }
        // 跨天区间，如 23:00 ~ 次日 06:00
        return $nowMin >= $startMin || $nowMin < $endMin;
    }

    /**
     * 判断「当前请求的页面」是否被每日维护拦截
     *
     * @param \lib\PdoHelper $DB
     * @return array|null ['page' => 页面名, 'start' => 'HH:MM', 'end' => 'HH:MM']，不受限时返回 null
     */
    function dailyMaintenanceBlockedInfo($DB)
    {
        $pages = maintenanceBlockedPages();
        $script = isset($_SERVER['SCRIPT_NAME']) ? basename($_SERVER['SCRIPT_NAME']) : '';
        if ($script === '' || !isset($pages[$script])) return null;

        $win = maintenanceWindow($DB);
        if (!isInMaintenanceWindow($win['start'], $win['end'])) return null;

        return array(
            'page'  => $pages[$script],
            'start' => $win['start'],
            'end'   => $win['end'],
        );
    }

    /**
     * 输出维护提示内容（放在 #pjax-container 内，兼容 PJAX 局部刷新）
     *
     * @param array $info dailyMaintenanceBlockedInfo() 的返回值
     */
    function renderDailyMaintenanceBlocked($info)
    {
        $page   = htmlspecialchars($info['page'], ENT_QUOTES, 'UTF-8');
        $start  = htmlspecialchars($info['start'], ENT_QUOTES, 'UTF-8');
        $end    = htmlspecialchars($info['end'], ENT_QUOTES, 'UTF-8');
        $now    = date('H:i');
        $windowText = (maintenanceToMinutes($info['start']) > maintenanceToMinutes($info['end']))
            ? ('每日 ' . $start . ' - 次日 ' . $end)
            : ('每日 ' . $start . ' - ' . $end);
        ?>
<div id="pjax-container">
    <div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-clock-o me-2"></i><?php echo $page;?>
                </h3>
            </div>
            <div class="block-content block-content-full">
                <div class="py-5 text-center">
                    <div class="mb-3">
                        <i class="fa fa-hourglass-half fa-2x text-warning"></i>
                    </div>
                    <h4 class="mb-2"><?php echo $page;?>暂不可用</h4>
                    <p class="text-muted mb-1">受学校教务系统每日例行维护影响，该功能在维护时段内暂停访问。</p>
                    <p class="text-muted mb-4">维护结束后自动恢复，其他功能不受影响。</p>

                    <div class="row justify-content-center">
                        <div class="col-md-6">
                            <div class="block block-rounded block-bordered mb-0">
                                <div class="block-content">
                                    <div class="d-flex justify-content-between py-2">
                                        <span class="text-muted">维护时段</span>
                                        <span class="fw-semibold"><?php echo $windowText;?></span>
                                    </div>
                                    <div class="d-flex justify-content-between py-2 border-top">
                                        <span class="text-muted">当前时间</span>
                                        <span class="fw-semibold"><?php echo $now;?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <a href="index.php" class="btn btn-alt-primary mt-4">
                        <i class="fa fa-home me-1"></i>返回用户中心
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
        <?php
    }

    /**
     * 管理员手动维护（后台「维护」功能开关 weihu_open 启用）
     *
     * 与客户端一致：开启后网页版全站进入维护状态，仅放行首页 index.php。
     * 公告沿用 getFeatureAnnouncement 的取值逻辑（含「总公告」覆盖 / follow_global）。
     *
     * @param \lib\PdoHelper $DB
     * @return array|null ['feature_name'=>string, 'announcement'=>array|null, 'contact'=>array]
     */
    function manualMaintenanceInfo($DB)
    {
        if (empty($DB) || !is_object($DB)) return null;
        try {
            $setting = $DB->getRow("SELECT * FROM nteam_feature_settings WHERE feature_key='weihu_open'");
        } catch (Exception $e) {
            return null;
        }
        // 未配置 / 未启用 → 非手动维护状态
        if (!$setting || intval($setting['is_enabled']) !== 1) return null;

        $announcement = null;
        if (function_exists('getFeatureAnnouncement')) {
            $announcement = getFeatureAnnouncement($DB, 'weihu_open');
        }

        return array(
            'feature_name' => !empty($setting['feature_name']) ? $setting['feature_name'] : '系统服务',
            'announcement' => $announcement,
            'contact'      => maintenanceContactInfo($DB),
        );
    }

    /**
     * 读取维护提示页展示的联系方式（后台「官网配置」）
     *
     * @param \lib\PdoHelper $DB
     * @return array ['email'=>string, 'phone'=>string, 'qq'=>string]
     */
    function maintenanceContactInfo($DB)
    {
        $info = array('email' => '', 'phone' => '', 'qq' => '');
        try {
            $rs = @$DB->query("SELECT contact_email, contact_phone, contact_qq FROM nteam_home_config WHERE id=1");
            if ($rs) {
                $row = $rs->fetch();
                if ($row) {
                    $info['email'] = trim((string)$row['contact_email']);
                    $info['phone'] = trim((string)$row['contact_phone']);
                    $info['qq']    = trim((string)$row['contact_qq']);
                }
            }
        } catch (Exception $e) {
            // 表结构缺失时忽略
        }
        return $info;
    }

    /**
     * 输出「管理员手动维护」提示（放在 #pjax-container 内，兼容 PJAX 局部刷新）
     *
     * @param array $info manualMaintenanceInfo() 的返回值
     */
    function renderManualMaintenanceBlocked($info)
    {
        $featureName = htmlspecialchars(isset($info['feature_name']) ? $info['feature_name'] : '系统服务', ENT_QUOTES, 'UTF-8');
        $ann = isset($info['announcement']) ? $info['announcement'] : null;
        $contact = isset($info['contact']) ? $info['contact'] : array('email' => '', 'phone' => '', 'qq' => '');

        $alertClass = 'alert-info';
        $icon = 'fa-info-circle';
        if ($ann && !empty($ann['type'])) {
            if ($ann['type'] === 'success') {
                $alertClass = 'alert-success';
                $icon = 'fa-check-circle';
            } elseif ($ann['type'] === 'warning') {
                $alertClass = 'alert-warning';
                $icon = 'fa-exclamation-triangle';
            } elseif ($ann['type'] === 'danger') {
                $alertClass = 'alert-danger';
                $icon = 'fa-times-circle';
            }
        }

        $contacts = array();
        if (!empty($contact['email'])) $contacts[] = '邮箱：' . htmlspecialchars($contact['email'], ENT_QUOTES, 'UTF-8');
        if (!empty($contact['phone'])) $contacts[] = '电话：' . htmlspecialchars($contact['phone'], ENT_QUOTES, 'UTF-8');
        if (!empty($contact['qq']))    $contacts[] = 'QQ：' . htmlspecialchars($contact['qq'], ENT_QUOTES, 'UTF-8');
        ?>
<div id="pjax-container">
    <div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-wrench me-2"></i><?php echo $featureName;?>
                </h3>
            </div>
            <div class="block-content block-content-full">
                <div class="py-5 text-center">
                    <div class="mb-3">
                        <i class="fa fa-cogs fa-2x text-warning"></i>
                    </div>
                    <h4 class="mb-2">系统维护中</h4>
                    <p class="text-muted mb-1">管理员已开启维护模式，网站功能暂时无法使用。</p>
                    <p class="text-muted mb-4">请稍后再试，维护完成后将自动恢复。</p>

                    <?php if ($ann && trim((string)$ann['content']) !== ''): ?>
                    <div class="row justify-content-center mb-4">
                        <div class="col-md-8 text-start">
                            <div class="alert <?php echo $alertClass;?> mb-0">
                                <i class="fa <?php echo $icon;?> me-2"></i>
                                <?php echo nl2br(htmlspecialchars($ann['content'], ENT_QUOTES, 'UTF-8'));?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($contacts)): ?>
                    <p class="text-muted fs-sm mb-4">
                        如需帮助请联系：<?php echo implode('　', $contacts);?>
                    </p>
                    <?php endif; ?>

                    <div class="mt-2">
                        <button type="button" class="btn btn-alt-primary" onclick="location.reload()">
                            <i class="fa fa-refresh me-1"></i>重新检测
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
        <?php
    }
}
