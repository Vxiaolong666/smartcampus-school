<?php
$title = '教务账号绑定';
$requireLogin = true;
include('./head.php');

$userId = $_SESSION['userId'];

if (!isFeatureEnabled($DB, 'academic_bind')) {
    echo '<div id="pjax-container"><div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-content block-content-full">';
    checkFeatureAccess($DB, 'academic_bind');
    echo '</div></div></div>';
    include('./foot.php');
    exit;
}

$academicAccount = $DB->getRow("SELECT * FROM nteam_academic_accounts WHERE user_id='$userId'");
?>

<div id="pjax-container">
    <div class="content animated fadeIn">
        <div class="block block-rounded block-bordered">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-graduation-cap me-2"></i>教务账号绑定
                </h3>
            </div>
            <div class="block-content block-content-full">
                <?php renderFeatureAnnouncement($DB, 'academic_bind'); ?>
                <?php if ($academicAccount): ?>
                <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                    <i class="fa fa-check-circle me-2"></i>
                    <div>
                        <strong>已绑定教务账号</strong> - 绑定时间：<?php echo $academicAccount['bind_time']; ?>
                    </div>
                </div>
                
                <form id="updateAcademicForm" class="mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">学工号 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="student_id" id="student_id" value="<?php echo htmlspecialchars($academicAccount['student_id']); ?>" required placeholder="请输入学工号">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">教务密码 <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="password" id="password" value="<?php echo htmlspecialchars($academicAccount['password']); ?>" required placeholder="请输入教务系统密码">
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">
                                        <i class="fa fa-eye" id="passwordIcon"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">真实姓名 <span class="text-muted">(可选)</span></label>
                                <input type="text" class="form-control" name="real_name" id="real_name" value="<?php echo htmlspecialchars($academicAccount['real_name'] ?: ''); ?>" placeholder="请输入真实姓名">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">绑定时间</label>
                                <input type="text" class="form-control" value="<?php echo $academicAccount['bind_time']; ?>" disabled>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-alt-primary">
                                <i class="fa fa-save me-1"></i> 更新绑定
                            </button>
                            <button type="button" class="btn btn-alt-danger ms-2" onclick="unbindAccount()">
                                <i class="fa fa-unlink me-1"></i> 解除绑定
                            </button>
                        </div>
                    </div>
                </form>
                
                <?php else: ?>
                <div class="alert alert-info d-flex align-items-center" role="alert">
                    <i class="fa fa-info-circle me-2"></i>
                    <div>
                        <strong>未绑定教务账号</strong> - 绑定后可使用教务系统相关功能
                    </div>
                </div>
                
                <form id="bindAcademicForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">学工号 <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="student_id" id="student_id" required placeholder="请输入学工号">
                                <div class="form-text">请输入您的教务系统学工号</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">教务密码 <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="password" id="password" required placeholder="请输入教务系统密码">
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">
                                        <i class="fa fa-eye" id="passwordIcon"></i>
                                    </button>
                                </div>
                                <div class="form-text">请输入您的教务系统登录密码</div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">真实姓名 <span class="text-muted">(可选)</span></label>
                                <input type="text" class="form-control" name="real_name" id="real_name" placeholder="请输入真实姓名">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-alt-primary">
                                <i class="fa fa-link me-1"></i> 绑定账号
                            </button>
                        </div>
                    </div>
                </form>
                <?php endif; ?>
                
                <div class="alert alert-warning mt-4" role="alert">
                    <h5 class="alert-heading"><i class="fa fa-exclamation-triangle me-2"></i>注意事项</h5>
                    <hr>
                    <ul class="mb-0">
                        <li>请确保您输入的学工号和密码正确无误</li>
                        <li>您的教务账号信息将被安全存储</li>
                        <li>如需更换绑定账号，请先解绑后重新绑定</li>
                        <li>如有问题请联系管理员</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    var passwordInput = document.getElementById('password');
    var passwordIcon = document.getElementById('passwordIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordIcon.classList.remove('fa-eye');
        passwordIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        passwordIcon.classList.remove('fa-eye-slash');
        passwordIcon.classList.add('fa-eye');
    }
}

<?php if ($academicAccount): ?>
$('#updateAcademicForm').on('submit', function(e) {
    e.preventDefault();
    
    var formData = $(this).serialize();
    
    layer.confirm('确定要更新教务账号绑定信息吗？', {
        btn: ['确定', '取消'],
        icon: 3
    }, function() {
        $.ajax({
            url: 'ajax.php?act=update_academic',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if(res.code == 0) {
                    layer.msg(res.msg, {icon: 1, time: 1500}, function() {
                        location.reload();
                    });
                } else {
                    layer.msg(res.msg, {icon: 2});
                }
            },
            error: function() {
                layer.msg('请求失败', {icon: 2});
            }
        });
    });
});

function unbindAccount() {
    layer.confirm('确定要解除教务账号绑定吗？解绑后需要重新绑定才能使用相关功能。', {
        btn: ['确定', '取消'],
        icon: 3
    }, function() {
        $.ajax({
            url: 'ajax.php?act=unbind_academic',
            type: 'POST',
            dataType: 'json',
            success: function(res) {
                if(res.code == 0) {
                    layer.msg(res.msg, {icon: 1, time: 1500}, function() {
                        location.reload();
                    });
                } else {
                    layer.msg(res.msg, {icon: 2});
                }
            },
            error: function() {
                layer.msg('请求失败', {icon: 2});
            }
        });
    });
}
<?php else: ?>
$('#bindAcademicForm').on('submit', function(e) {
    e.preventDefault();
    
    var formData = $(this).serialize();
    
    $.ajax({
        url: 'ajax.php?act=bind_academic',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(res) {
            if(res.code == 0) {
                layer.msg(res.msg, {icon: 1, time: 1500}, function() {
                    location.reload();
                });
            } else {
                layer.msg(res.msg, {icon: 2});
            }
        },
        error: function() {
            layer.msg('请求失败', {icon: 2});
        }
    });
});
<?php endif; ?>
</script>

<?php include 'foot.php'; ?>
