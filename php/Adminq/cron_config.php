<?php
$mod = 'admin';
$title='定时任务配置';
include('../Common/Core_brain.php');
if($adminData['adminRank']== 2) {
  echo "您的账号没有权限使用此功能";
  exit;
}
include './head.php';
?>
<div id="pjax-container">
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center py-2">
                <div class="flex-grow-1">
                    <h1 class="h3 fw-bold mb-2">
                        <?php echo $title ?>
                    </h1>
                </div>
                <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">
                            <a class="link-fx" href="index.php">后台首页</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">
                            <?php echo $title ?>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="content animated fadeIn">
        <div class="block block-rounded mb-5">
            <div class="block-header block-header-default">
                <h3 class="block-title">电费推送定时任务</h3>
            </div>
            <div class="block-content">
                <div class="alert alert-info">
                    <h5><i class="fa fa-bolt me-2"></i>电费检查推送任务</h5>
                    <p>请在宝塔面板中添加以下定时任务：</p>
                    <ul>
                        <li class="mb-3">
                            <strong>电费检查推送</strong>：每30分钟执行一次（同时记录用电数据）
                            <pre class="bg-light p-2 mt-1 mb-1">php <?php echo dirname(__FILE__); ?>/cron_power_push.php check</pre>
                            <span class="text-muted">或使用URL方式：</span>
                            <a href="../cron_power_push.php?act=check" target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                                <i class="fa fa-play me-1"></i>URL测试
                            </a>
                        </li>
                        <li class="mb-3">
                            <strong>每日用电汇总推送</strong>：每天早上8点执行
                            <pre class="bg-light p-2 mt-1 mb-1">php <?php echo dirname(__FILE__); ?>/cron_power_push.php daily</pre>
                            <span class="text-muted">或使用URL方式：</span>
                            <a href="../cron_power_push.php?act=daily" target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                                <i class="fa fa-play me-1"></i>URL测试
                            </a>
                        </li>
                        <li class="mb-3">
                            <strong>全部执行</strong>：依次执行所有任务
                            <pre class="bg-light p-2 mt-1 mb-1">php <?php echo dirname(__FILE__); ?>/cron_power_push.php all</pre>
                            <span class="text-muted">或使用URL方式：</span>
                            <a href="../cron_power_push.php?act=all" target="_blank" class="btn btn-sm btn-outline-success ms-2">
                                <i class="fa fa-play me-1"></i>URL测试全部
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="block block-rounded mb-5">
            <div class="block-header block-header-default">
                <h3 class="block-title">教务推送定时任务</h3>
            </div>
            <div class="block-content">
                <div class="alert alert-info">
                    <h5><i class="fa fa-graduation-cap me-2"></i>教务推送任务配置</h5>
                    <p>请在宝塔面板中添加以下定时任务：</p>
                    <ul>
                        <li class="mb-3">
                            <strong>明日课程提醒</strong>：每天晚上20:00执行
                            <pre class="bg-light p-2 mt-1 mb-1">php <?php echo dirname(__FILE__); ?>/cron_academic_push.php schedule</pre>
                            <span class="text-muted">或使用URL方式：</span>
                            <a href="../cron_academic_push.php?mode=schedule" target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                                <i class="fa fa-play me-1"></i>URL测试
                            </a>
                        </li>
                        <li class="mb-3">
                            <strong>成绩发布通知</strong>：每30分钟执行一次
                            <pre class="bg-light p-2 mt-1 mb-1">php <?php echo dirname(__FILE__); ?>/cron_academic_push.php grade</pre>
                            <span class="text-muted">或使用URL方式：</span>
                            <a href="../cron_academic_push.php?mode=grade" target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                                <i class="fa fa-play me-1"></i>URL测试
                            </a>
                        </li>
                        <li class="mb-3">
                            <strong>全部执行</strong>：依次执行所有任务
                            <pre class="bg-light p-2 mt-1 mb-1">php <?php echo dirname(__FILE__); ?>/cron_academic_push.php all</pre>
                            <span class="text-muted">或使用URL方式：</span>
                            <a href="../cron_academic_push.php?mode=all" target="_blank" class="btn btn-sm btn-outline-success ms-2">
                                <i class="fa fa-play me-1"></i>URL测试全部
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="block block-rounded mb-5">
            <div class="block-header block-header-default">
                <h3 class="block-title">订阅提醒定时任务</h3>
            </div>
            <div class="block-content">
                <div class="alert alert-info">
                    <h5><i class="fa fa-bell me-2"></i>订阅额度提醒任务</h5>
                    <p>请在宝塔面板中添加以下定时任务：</p>
                    <ul>
                        <li class="mb-3">
                            <strong>订阅额度提醒</strong>：每天执行一次，提醒订阅次数不足的用户
                            <pre class="bg-light p-2 mt-1 mb-1">php <?php echo dirname(__FILE__); ?>/cron_subscription_alert.php</pre>
                            <span class="text-muted">或使用URL方式：</span>
                            <a href="../cron_subscription_alert.php" target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                                <i class="fa fa-play me-1"></i>URL测试
                            </a>
                        </li>
                    </ul>
                    <div class="alert alert-warning mt-3 mb-0">
                        <strong>说明：</strong>此任务会检查所有用户的订阅次数，当订阅次数小于2时发送提醒通知。
                    </div>
                </div>
            </div>
        </div>

        <div class="block block-rounded mb-5">
            <div class="block-header block-header-default">
                <h3 class="block-title">账号注销定时任务</h3>
            </div>
            <div class="block-content">
                <div class="alert alert-info">
                    <h5><i class="fa fa-user-slash me-2"></i>账号注销冷静期到期处理</h5>
                    <p>请在宝塔面板中添加以下定时任务：</p>
                    <ul>
                        <li class="mb-3">
                            <strong>注销到期处理</strong>：建议每小时执行一次，处理冷静期结束的账号（自动删除账号及其个人数据）
                            <pre class="bg-light p-2 mt-1 mb-1">php <?php echo dirname(__FILE__); ?>/cron_account_cancel.php</pre>
                            <span class="text-muted">或使用URL方式：</span>
                            <a href="../cron_account_cancel.php" target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                                <i class="fa fa-play me-1"></i>URL测试
                            </a>
                        </li>
                    </ul>
                    <div class="alert alert-warning mt-3 mb-0">
                        <strong>说明：</strong>仅当后台「注销管理」启用了冷静期时才会产生待处理账号；冷静期内用户重新登录会自动撤销注销，不会进入删除流程。
                    </div>
                </div>
            </div>
        </div>

        <div class="block block-rounded mb-5">
            <div class="block-header block-header-default">
                <h3 class="block-title">配置说明</h3>
            </div>
            <div class="block-content">
                <div class="alert alert-warning">
                    <h5><i class="fa fa-exclamation-triangle me-2"></i>重要提示</h5>
                    <ul class="mb-0">
                        <li>URL测试方式仅用于调试，生产环境请使用宝塔定时任务的CLI方式执行</li>
                        <li>电费检查建议每30分钟执行一次，及时发现余额不足</li>
                        <li>每日用电汇总建议在每天早上8点执行</li>
                        <li>课程提醒建议在每天晚上固定时间执行，如20:00</li>
                        <li>成绩通知建议每30分钟执行一次，及时发现新成绩</li>
                        <li>订阅额度提醒建议每天执行一次，如每天早上9点</li>
                        <li>账号注销到期处理建议每小时执行一次，及时清理冷静期结束的账号（后台「注销管理」启用冷静期后生效）</li>
                        <li>首次使用前请确保已在微信公众平台申请相关订阅消息模板</li>
                    </ul>
                </div>

                <div class="alert alert-info">
                    <h5><i class="fa fa-info-circle me-2"></i>宝塔面板配置步骤</h5>
                    <ol class="mb-0">
                        <li>登录宝塔面板</li>
                        <li>点击左侧菜单"计划任务"</li>
                        <li>点击"添加任务"</li>
                        <li>选择任务类型为"Shell脚本"</li>
                        <li>设置执行周期（根据任务需求选择）</li>
                        <li>将上述命令粘贴到脚本内容中</li>
                        <li>点击"添加"保存任务</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include './foot.php'; ?>
