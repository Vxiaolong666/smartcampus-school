<?php
$title = 'AI 配置';
include('./head.php');

if ($adminData['adminRank'] != 1) {
    echo '<div class="alert alert-danger">无权限访问该页面</div>';
    include('./foot.php');
    exit;
}

// 首次访问且未配置时，写入默认配置，确保开箱即用
$defaults = [
    'AI_Token'   => 'sk-B1yXxFfv52GKjmUaWJF7Ota9gvpJ6WxE',
    'AI_BaseURL' => 'https://token.sensenova.cn/v1',
    'AI_Model'   => 'deepseek-v4-flash',
];
if (empty(@\conf('AI_Token'))) {
    foreach ($defaults as $k => $v) {
        saveSetting($k, $v);
    }
}

$saveMsg = '';
if (isset($_POST['submit'])) {
    $fields = ['AI_Token', 'AI_BaseURL', 'AI_Model'];
    foreach ($fields as $f) {
        saveSetting($f, isset($_POST[$f]) ? $_POST[$f] : '');
    }
    $saveMsg = '配置已保存';
}

function aiVal($k) { return htmlspecialchars((string)@\conf($k)); }
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
        <?php if ($saveMsg): ?>
        <div class="alert alert-success">
            <i class="fa fa-check-circle me-2"></i><?php echo $saveMsg; ?>
        </div>
        <?php endif; ?>


        <form method="POST" action="">
            <div class="block block-rounded block-bordered">
                <div class="block-header block-header-default">
                    <h3 class="block-title"><i class="fa fa-robot me-2"></i>AI 配置</h3>
                </div>
                <div class="block-content block-content-full">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">API Token</label>
                                <input type="text" class="form-control" name="AI_Token"
                                       value="<?php echo aiVal('AI_Token'); ?>" placeholder="sk-...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Base URL</label>
                                <input type="text" class="form-control" name="AI_BaseURL"
                                       value="<?php echo aiVal('AI_BaseURL'); ?>" placeholder="https://token.sensenova.cn/v1">
                                <small class="text-muted">填写到 /v1 即可，系统自动拼接 /chat/completions</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">模型 ID</label>
                                <input type="text" class="form-control" name="AI_Model"
                                       value="<?php echo aiVal('AI_Model'); ?>" placeholder="deepseek-v4-flash">
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <button type="submit" name="submit" value="1" class="btn btn-primary me-2">
                            <i class="fa fa-check opacity-50 me-1"></i><b>保存配置</b>
                        </button>
                        <a href="index.php" class="btn btn-alt-secondary">返回首页</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<?php include './foot.php'; ?>
