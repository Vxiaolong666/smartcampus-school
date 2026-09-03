<?php
$mod = 'admin';
$title='编辑用户';
include('../Common/Core_brain.php');
if($adminData['adminRank']== 2) {
  echo "您的账号没有权限使用此功能";
  exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user = $DB->query("SELECT * FROM nteam_users WHERE id='$id'")->fetch();

if(!$user) {
    echo "用户不存在";
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
                        <li class="breadcrumb-item">
                            <a class="link-fx" href="ulist.php">用户管理</a>
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
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title"> 编辑用户 </h3>
                <div class="block-options">
                    <button type="button" onclick="window.location.href='ulist.php'" class="btn btn-sm btn-secondary">
                        <i class="fa fa-arrow-left"></i> 返回
                    </button>
                </div>
            </div>
            <div class="block-content">
                <form id="editForm">
                    <input type="hidden" name="id" value="<?php echo $user['id']?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">用户名</label>
                                <input type="text" class="form-control form-control-lg" name="username" value="<?php echo $user['username']?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">邮箱</label>
                                <input type="email" class="form-control form-control-lg" name="email" value="<?php echo $user['email']?>" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">QQ号</label>
                                <input type="text" class="form-control form-control-lg" name="qq" value="<?php echo $user['qq']?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">积分</label>
                                <input type="number" class="form-control form-control-lg" name="points" value="<?php echo $user['points']?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">等级</label>
                                <input type="number" class="form-control form-control-lg" name="level" value="<?php echo $user['level']?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">账号状态</label>
                                <select class="form-select form-select-lg" name="is_banned">
                                    <option value="0" <?php echo $user['is_banned']==0?'selected':''?>>正常</option>
                                    <option value="1" <?php echo $user['is_banned']==1?'selected':''?>>已封禁</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">邮箱验证状态</label>
                        <select class="form-select form-select-lg" name="email_verified">
                            <option value="0" <?php echo $user['email_verified']==0?'selected':''?>>未验证</option>
                            <option value="1" <?php echo $user['email_verified']==1?'selected':''?>>已验证</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='ulist.php'">返回</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-check opacity-50 me-1"></i> 保存
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include 'foot.php'?>
<script>
$(document).ready(function(){
    $('#editForm').submit(function(){
        var formData = $(this).serialize();
        var submitBtn = $(this).find('button[type="submit"]');
        submitBtn.attr('disabled', 'true');
        
        $.ajax({
            type: 'POST',
            url: 'Ajax.php?act=setUser&type=edit',
            data: formData,
            dataType: 'json',
            success: function(data){
                if(data.code == 0){
                    layer.msg('保存成功', {icon: 1});
                    setTimeout(function(){
                        window.location.href = 'ulist.php';
                    }, 1000);
                }else{
                    submitBtn.removeAttr('disabled');
                    layer.msg(data.msg, {icon: 2});
                }
            },
            error: function(){
                submitBtn.removeAttr('disabled');
                layer.msg('网络错误，请稍后重试', {icon: 2});
            }
        });
        
        return false;
    });
});
</script>
</body>
</html>
