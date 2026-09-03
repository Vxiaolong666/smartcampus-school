<?php
$mod = 'admin';
$title='API文档';
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
                        <i class="fa fa-code me-2"></i><?php echo $title ?>
                    </h1>
                </div>
                <nav class="flex-shrink-0 mt-3 mt-sm-0 ms-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-alt">
                        <li class="breadcrumb-item">
                            <a class="link-fx" href="index.php">后台首页</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            <?php echo $title ?>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    
    <div class="content animated fadeIn">
        <div class="row">
            <div class="col-lg-3 mb-4">
                <div class="block block-rounded block-bordered">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">
                            <i class="fa fa-list me-2"></i>目录
                        </h3>
                    </div>
                    <div class="block-content">
                        <nav class="nav nav-pills flex-column">
                            <a class="nav-link" href="#overview">概述</a>
                            <a class="nav-link" href="#auth">认证方式</a>
                            <a class="nav-link" href="#user-api">用户认证API</a>
                            <a class="nav-link" href="#academic-api">教务账号API</a>
                            <a class="nav-link" href="#dormitory-api">宿舍管理API</a>
                            <a class="nav-link" href="#power-api">电费查询API</a>
                            <a class="nav-link" href="#grade-api">成绩查询API</a>
                            <a class="nav-link" href="#schedule-api">课程表API</a>
                            <a class="nav-link" href="#notice-api">通知中心API</a>
                            <a class="nav-link" href="#wx-api">微信订阅消息API</a>
                            <a class="nav-link" href="#push-api">推送设置API</a>
                            <a class="nav-link" href="#feature-api">功能设置API</a>
                            <a class="nav-link" href="#error-code">错误码说明</a>
                        </nav>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-9">
                <div class="block block-rounded block-bordered mb-4" id="overview">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">
                            <i class="fa fa-info-circle me-2"></i>概述
                        </h3>
                    </div>
                    <div class="block-content">
                        <p>本文档描述了系统对外提供的API接口，供小程序、APP等客户端调用。</p>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 150px;">接口地址</th>
                                    <td><code><?php echo conf('Url'); ?>api.php</code></td>
                                </tr>
                                <tr>
                                    <th>请求方式</th>
                                    <td>POST / GET</td>
                                </tr>
                                <tr>
                                    <th>数据格式</th>
                                    <td>JSON</td>
                                </tr>
                                <tr>
                                    <th>编码</th>
                                    <td>UTF-8</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="block block-rounded block-bordered mb-4" id="auth">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">
                            <i class="fa fa-key me-2"></i>认证方式
                        </h3>
                    </div>
                    <div class="block-content">
                        <h5>Token认证</h5>
                        <p>小程序端使用Token进行认证，Token通过登录接口获取。</p>
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle me-2"></i>
                            在请求头中添加：<code>Authorization: Bearer {token}</code>
                        </div>
                        
                        <h5 class="mt-3">Token有效期</h5>
                        <p>Token有效期为30天，过期后需要重新登录获取新Token。</p>
                        
                        <h5 class="mt-3">请求示例</h5>
                        <pre class="bg-light p-3 rounded"><code>POST /api.php?act=get_user_info HTTP/1.1
Host: example.com
Content-Type: application/x-www-form-urlencoded
Authorization: Bearer your_token_here</code></pre>
                        
                        <h5 class="mt-3">小程序调用示例</h5>
                        <pre class="bg-light p-3 rounded"><code>// 登录获取Token
wx.request({
  url: 'https://example.com/api.php?act=login',
  method: 'POST',
  data: {
    username: 'your_username',
    password: md5('your_password')
  },
  success: function(res) {
    if (res.data.code === 0) {
      const token = res.data.data.token;
      wx.setStorageSync('token', token);
    }
  }
});

// 使用Token请求数据
wx.request({
  url: 'https://example.com/api.php?act=get_user_info',
  method: 'POST',
  header: {
    'Authorization': 'Bearer ' + wx.getStorageSync('token')
  },
  success: function(res) {
    console.log(res.data);
  }
});</code></pre>
                    </div>
                </div>
                
                <div class="block block-rounded block-bordered mb-4" id="user-api">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">
                            <i class="fa fa-user me-2"></i>用户认证API
                        </h3>
                    </div>
                    <div class="block-content">
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                登录
                            </h5>
                            <p><strong>接口：</strong><code>?act=login</code></p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>username</td><td>string</td><td>是</td><td>用户名</td></tr>
                                    <tr><td>password</td><td>string</td><td>是</td><td>密码（MD5加密后）</td></tr>
                                </tbody>
                            </table>
                            <p><strong>返回：</strong></p>
                            <pre class="bg-light p-2 rounded"><code>{
    "code": 0,
    "msg": "登录成功",
    "data": {
        "token": "xxx",
        "user_id": 1,
        "username": "test",
        "email": "test@example.com"
    }
}</code></pre>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                注册
                            </h5>
                            <p><strong>接口：</strong><code>?act=register</code></p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>username</td><td>string</td><td>是</td><td>用户名（4-20位字母数字下划线）</td></tr>
                                    <tr><td>password</td><td>string</td><td>是</td><td>密码（至少6位）</td></tr>
                                    <tr><td>email</td><td>string</td><td>是</td><td>邮箱地址</td></tr>
                                    <tr><td>qq</td><td>string</td><td>否</td><td>QQ号</td></tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                退出登录
                            </h5>
                            <p><strong>接口：</strong><code>?act=logout</code></p>
                            <p><strong>需要认证：</strong>是</p>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                获取用户信息
                            </h5>
                            <p><strong>接口：</strong><code>?act=get_user_info</code></p>
                            <p><strong>需要认证：</strong>是</p>
                            <p><strong>返回：</strong></p>
                            <pre class="bg-light p-2 rounded"><code>{
    "code": 0,
    "msg": "success",
    "data": {
        "user_id": 1,
        "username": "test",
        "email": "test@example.com",
        "qq": "123456789",
        "register_time": "2024-01-01 00:00:00"
    }
}</code></pre>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                修改密码
                            </h5>
                            <p><strong>接口：</strong><code>?act=change_password</code></p>
                            <p><strong>需要认证：</strong>是</p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>old_password</td><td>string</td><td>是</td><td>原密码</td></tr>
                                    <tr><td>new_password</td><td>string</td><td>是</td><td>新密码（至少6位）</td></tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                发送找回密码验证码
                            </h5>
                            <p><strong>接口：</strong><code>?act=forgot_password</code></p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>email</td><td>string</td><td>是</td><td>注册时使用的邮箱</td></tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                重置密码
                            </h5>
                            <p><strong>接口：</strong><code>?act=reset_password</code></p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>email</td><td>string</td><td>是</td><td>邮箱地址</td></tr>
                                    <tr><td>code</td><td>string</td><td>是</td><td>验证码</td></tr>
                                    <tr><td>password</td><td>string</td><td>是</td><td>新密码（至少6位）</td></tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                微信登录
                            </h5>
                            <p><strong>接口：</strong><code>?act=wx_login</code></p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>code</td><td>string</td><td>是</td><td>微信登录凭证</td></tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                绑定微信
                            </h5>
                            <p><strong>接口：</strong><code>?act=bind_wx</code></p>
                            <p><strong>需要认证：</strong>是</p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>code</td><td>string</td><td>是</td><td>微信登录凭证</td></tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                解绑微信
                            </h5>
                            <p><strong>接口：</strong><code>?act=unbind_wx</code></p>
                            <p><strong>需要认证：</strong>是</p>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                更新用户资料
                            </h5>
                            <p><strong>接口：</strong><code>?act=update_profile</code></p>
                            <p><strong>需要认证：</strong>是</p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>nickname</td><td>string</td><td>否</td><td>昵称（最多20字符）</td></tr>
                                    <tr><td>avatar</td><td>string</td><td>否</td><td>头像路径</td></tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                上传头像
                            </h5>
                            <p><strong>接口：</strong><code>?act=upload_avatar</code></p>
                            <p><strong>需要认证：</strong>是</p>
                            <p><strong>参数：</strong>multipart/form-data</p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>avatar</td><td>file</td><td>是</td><td>头像图片（JPG/PNG/GIF/WEBP，最大2MB）</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="block block-rounded block-bordered mb-4" id="academic-api">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">
                            <i class="fa fa-graduation-cap me-2"></i>教务账号API
                        </h3>
                    </div>
                    <div class="block-content">
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-primary me-2">GET</span>
                                获取教务账号绑定状态
                            </h5>
                            <p><strong>接口：</strong><code>?act=get_academic</code></p>
                            <p><strong>需要认证：</strong>是</p>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                绑定教务账号
                            </h5>
                            <p><strong>接口：</strong><code>?act=bind_academic</code></p>
                            <p><strong>需要认证：</strong>是</p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>student_id</td><td>string</td><td>是</td><td>学号</td></tr>
                                    <tr><td>password</td><td>string</td><td>是</td><td>教务系统密码</td></tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                解绑教务账号
                            </h5>
                            <p><strong>接口：</strong><code>?act=unbind_academic</code></p>
                            <p><strong>需要认证：</strong>是</p>
                        </div>
                    </div>
                </div>
                
                <div class="block block-rounded block-bordered mb-4" id="dormitory-api">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">
                            <i class="fa fa-home me-2"></i>宿舍管理API
                        </h3>
                    </div>
                    <div class="block-content">
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                获取已绑定宿舍
                            </h5>
                            <p><strong>接口：</strong><code>?act=get_dormitories</code></p>
                            <p><strong>需要认证：</strong>是</p>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                绑定宿舍
                            </h5>
                            <p><strong>接口：</strong><code>?act=bind_dormitory</code></p>
                            <p><strong>需要认证：</strong>是</p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>school_area</td><td>string</td><td>是</td><td>校区</td></tr>
                                    <tr><td>building</td><td>string</td><td>是</td><td>楼栋</td></tr>
                                    <tr><td>room</td><td>string</td><td>是</td><td>房间号</td></tr>
                                    <tr><td>room_num</td><td>string</td><td>是</td><td>宿舍编号</td></tr>
                                    <tr><td>alias</td><td>string</td><td>否</td><td>别名</td></tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                解绑宿舍
                            </h5>
                            <p><strong>接口：</strong><code>?act=unbind_dormitory</code></p>
                            <p><strong>需要认证：</strong>是</p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>id</td><td>int</td><td>是</td><td>宿舍绑定ID</td></tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                设置默认宿舍
                            </h5>
                            <p><strong>接口：</strong><code>?act=set_default_dormitory</code></p>
                            <p><strong>需要认证：</strong>是</p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>id</td><td>int</td><td>是</td><td>宿舍绑定ID</td></tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-primary me-2">GET</span>
                                获取校区列表
                            </h5>
                            <p><strong>接口：</strong><code>?act=get_dorm_areas</code></p>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-primary me-2">GET</span>
                                获取楼栋列表
                            </h5>
                            <p><strong>接口：</strong><code>?act=get_dorm_buildings</code></p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>area</td><td>string</td><td>是</td><td>校区名称</td></tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-primary me-2">GET</span>
                                获取房间列表
                            </h5>
                            <p><strong>接口：</strong><code>?act=get_dorm_rooms</code></p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>area</td><td>string</td><td>是</td><td>校区名称</td></tr>
                                    <tr><td>building</td><td>string</td><td>是</td><td>楼栋名称</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="block block-rounded block-bordered mb-4" id="power-api">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">
                            <i class="fa fa-bolt me-2"></i>电费查询API
                        </h3>
                    </div>
                    <div class="block-content">
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                查询电费
                            </h5>
                            <p><strong>接口：</strong><code>?act=query_power_fee</code></p>
                            <p><strong>需要认证：</strong>是</p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>room_num</td><td>string</td><td>是</td><td>宿舍编号</td></tr>
                                </tbody>
                            </table>
                            <p><strong>返回：</strong></p>
                            <pre class="bg-light p-2 rounded"><code>{
    "code": 0,
    "msg": "查询成功",
    "data": {
        "room_num": "xxx",
        "balance": 50.5,
        "remaining_kwh": 82.79,
        "price_per_kwh": 0.61,
        "status": "normal",
        "status_text": "电量充足"
    }
}</code></pre>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                获取用电统计
                            </h5>
                            <p><strong>接口：</strong><code>?act=get_power_statistics</code></p>
                            <p><strong>需要认证：</strong>是</p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>room_num</td><td>string</td><td>是</td><td>宿舍编号</td></tr>
                                </tbody>
                            </table>
                            <p><strong>返回：</strong></p>
                            <pre class="bg-light p-2 rounded"><code>{
    "code": 0,
    "msg": "success",
    "data": {
        "health_status": "normal",
        "health_text": "用电正常",
        "yesterday_usage": 1.5,
        "yesterday_cost": 0.92,
        "daily_avg_usage": 1.2,
        "daily_avg_cost": 0.73,
        "latest_balance": 50.5,
        "record_count": 30,
        "recent_trend": [...],
        "daily_trend": [...],
        "records": [...]
    }
}</code></pre>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-primary me-2">GET</span>
                                获取电费配置
                            </h5>
                            <p><strong>接口：</strong><code>?act=get_power_config</code></p>
                            <p><strong>需要认证：</strong>是</p>
                        </div>
                    </div>
                </div>
                
                <div class="block block-rounded block-bordered mb-4" id="grade-api">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">
                            <i class="fa fa-chart-bar me-2"></i>成绩查询API
                        </h3>
                    </div>
                    <div class="block-content">
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                查询成绩
                            </h5>
                            <p><strong>接口：</strong><code>?act=query_grade</code></p>
                            <p><strong>需要认证：</strong>是（需绑定教务账号）</p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>semester</td><td>string</td><td>否</td><td>学期ID，不传则返回所有学期</td></tr>
                                </tbody>
                            </table>
                            <p><strong>返回：</strong></p>
                            <pre class="bg-light p-2 rounded"><code>{
    "code": 0,
    "msg": "success",
    "data": {
        "grades": [
            {
                "course_name": "高等数学",
                "credit": 4.0,
                "grade": "85",
                "grade_point": "3.5",
                "semester": "2024-2025-1"
            }
        ],
        "gpa": 3.5,
        "total_credits": 30
    }
}</code></pre>
                        </div>
                    </div>
                </div>
                
                <div class="block block-rounded block-bordered mb-4" id="schedule-api">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">
                            <i class="fa fa-calendar-alt me-2"></i>课程表API
                        </h3>
                    </div>
                    <div class="block-content">
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                查询课程表
                            </h5>
                            <p><strong>接口：</strong><code>?act=query_schedule</code></p>
                            <p><strong>需要认证：</strong>是（需绑定教务账号）</p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>week</td><td>string</td><td>否</td><td>周次，不传则返回当前周</td></tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                查询学籍信息
                            </h5>
                            <p><strong>接口：</strong><code>?act=query_student_info</code></p>
                            <p><strong>需要认证：</strong>是（需绑定教务账号）</p>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-primary me-2">GET</span>
                                获取学期列表
                            </h5>
                            <p><strong>接口：</strong><code>?act=get_semester_list</code></p>
                            <p><strong>需要认证：</strong>是（需绑定教务账号）</p>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-primary me-2">GET</span>
                                获取课程表模式
                            </h5>
                            <p><strong>接口：</strong><code>?act=get_schedule_mode</code></p>
                            <p><strong>需要认证：</strong>是（需绑定教务账号）</p>
                            <p><strong>返回：</strong></p>
                            <pre class="bg-light p-2 rounded"><code>{
    "code": 0,
    "msg": "success",
    "data": {
        "mode": "1",
        "mode_name": "标准模式",
        "weeks": 20,
        "current_week": 10
    }
}</code></pre>
                        </div>
                    </div>
                </div>
                
                <div class="block block-rounded block-bordered mb-4" id="notice-api">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">
                            <i class="fa fa-bell me-2"></i>通知中心API
                        </h3>
                    </div>
                    <div class="block-content">
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-primary me-2">GET</span>
                                获取公告列表
                            </h5>
                            <p><strong>接口：</strong><code>?act=get_notices</code></p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>page</td><td>int</td><td>否</td><td>页码，默认1</td></tr>
                                    <tr><td>limit</td><td>int</td><td>否</td><td>每页数量，默认10</td></tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-primary me-2">GET</span>
                                获取公告详情
                            </h5>
                            <p><strong>接口：</strong><code>?act=get_notice_detail</code></p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>id</td><td>int</td><td>是</td><td>公告ID</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="block block-rounded block-bordered mb-4" id="wx-api">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">
                            <i class="fab fa-weixin me-2"></i>微信订阅消息API
                        </h3>
                    </div>
                    <div class="block-content">
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                获取OpenID
                            </h5>
                            <p><strong>接口：</strong><code>?act=get_openid</code></p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>code</td><td>string</td><td>是</td><td>微信登录凭证</td></tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                保存订阅
                            </h5>
                            <p><strong>接口：</strong><code>?act=save_subscription</code></p>
                            <p><strong>需要认证：</strong>是</p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>template_id</td><td>string</td><td>是</td><td>模板ID</td></tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                发送订阅消息
                            </h5>
                            <p><strong>接口：</strong><code>?act=send_subscribe_message</code></p>
                            <p><strong>需要认证：</strong>是</p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>template_id</td><td>string</td><td>是</td><td>模板ID</td></tr>
                                    <tr><td>openid</td><td>string</td><td>是</td><td>用户OpenID</td></tr>
                                    <tr><td>data</td><td>string</td><td>否</td><td>自定义数据JSON</td></tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-primary me-2">GET</span>
                                获取微信模板列表
                            </h5>
                            <p><strong>接口：</strong><code>?act=get_wx_templates</code></p>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-primary me-2">GET</span>
                                获取订阅次数
                            </h5>
                            <p><strong>接口：</strong><code>?act=get_subscription_count</code></p>
                            <p><strong>需要认证：</strong>是</p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>template_id</td><td>string</td><td>是</td><td>模板ID</td></tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-primary me-2">GET</span>
                                获取用户订阅列表
                            </h5>
                            <p><strong>接口：</strong><code>?act=get_user_subscriptions</code></p>
                            <p><strong>需要认证：</strong>是</p>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-primary me-2">GET</span>
                                获取用户订阅日志
                            </h5>
                            <p><strong>接口：</strong><code>?act=get_user_subscription_logs</code></p>
                            <p><strong>需要认证：</strong>是</p>
                        </div>

                        <hr>

                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-primary me-2">GET</span>
                                获取订阅历史记录
                            </h5>
                            <p><strong>接口：</strong><code>?act=get_subscription_history</code></p>
                            <p><strong>需要认证：</strong>是</p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>type</td><td>string</td><td>否</td><td>记录类型：all(全部)、subscribe(订阅授权/发送)、push(消息推送)</td></tr>
                                    <tr><td>page</td><td>int</td><td>否</td><td>页码，默认1</td></tr>
                                    <tr><td>size</td><td>int</td><td>否</td><td>每页数量，默认10，最大50</td></tr>
                                </tbody>
                            </table>
                            <p><strong>返回：</strong></p>
                            <pre class="bg-light p-2 rounded"><code>{
    "code": 0,
    "msg": "success",
    "data": {
        "list": [
            {
                "id": "s_26",
                "source": "subscription",
                "category": "订阅授权",
                "title": "订阅了「电费余额提醒」",
                "content": "次数变化：+1",
                "time": "2026-05-08 23:09:41",
                "status": 1,
                "status_text": "已授权",
                "error_msg": ""
            },
            {
                "id": "p_55",
                "source": "power_push",
                "category": "余额提醒",
                "title": "【余额提醒】11829 电费不足",
                "content": "您的宿舍 11829 电费余额为 23.97 元，建议及时充值。",
                "time": "2026-05-15 08:20:03",
                "status": 1,
                "status_text": "发送成功",
                "error_msg": ""
            }
        ],
        "total": 2,
        "page": 1,
        "size": 10
    }
}</code></pre>
                            <p class="mt-2"><strong>字段说明：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>字段</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>source</td><td>记录来源：subscription(订阅操作)、power_push(电费推送)、academic_push(教务推送)</td></tr>
                                    <tr><td>category</td><td>分类标签：订阅授权/消息发送/用电汇总/余额提醒/余额告警/欠费提醒/课程提醒/成绩通知</td></tr>
                                    <tr><td>status</td><td>状态：0-待发送，1-成功，2-失败</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="block block-rounded block-bordered mb-4" id="push-api">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">
                            <i class="fa fa-paper-plane me-2"></i>推送设置API
                        </h3>
                    </div>
                    <div class="block-content">
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-primary me-2">GET</span>
                                获取推送设置
                            </h5>
                            <p><strong>接口：</strong><code>?act=get_push_settings</code></p>
                            <p><strong>需要认证：</strong>是</p>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                保存推送设置
                            </h5>
                            <p><strong>接口：</strong><code>?act=save_push_settings</code></p>
                            <p><strong>需要认证：</strong>是</p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>is_enabled</td><td>int</td><td>否</td><td>是否开启推送（0/1）</td></tr>
                                    <tr><td>daily_summary_enabled</td><td>int</td><td>否</td><td>每日汇总开关</td></tr>
                                    <tr><td>low_balance_enabled</td><td>int</td><td>否</td><td>余额不足提醒开关</td></tr>
                                    <tr><td>critical_balance_enabled</td><td>int</td><td>否</td><td>余额耗尽提醒开关</td></tr>
                                    <tr><td>arrears_enabled</td><td>int</td><td>否</td><td>欠费提醒开关</td></tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                删除推送设置
                            </h5>
                            <p><strong>接口：</strong><code>?act=delete_push_settings</code></p>
                            <p><strong>需要认证：</strong>是</p>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-primary me-2">GET</span>
                                获取教务推送设置
                            </h5>
                            <p><strong>接口：</strong><code>?act=get_academic_push_settings</code></p>
                            <p><strong>需要认证：</strong>是</p>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                保存教务推送设置
                            </h5>
                            <p><strong>接口：</strong><code>?act=save_academic_push_settings</code></p>
                            <p><strong>需要认证：</strong>是</p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>is_enabled</td><td>int</td><td>否</td><td>是否开启推送（0/1）</td></tr>
                                    <tr><td>schedule_reminder_enabled</td><td>int</td><td>否</td><td>课程提醒开关</td></tr>
                                    <tr><td>grade_notify_enabled</td><td>int</td><td>否</td><td>成绩通知开关</td></tr>
                                    <tr><td>schedule_reminder_time</td><td>string</td><td>否</td><td>提醒时间（HH:mm）</td></tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-primary me-2">GET</span>
                                获取教务推送日志
                            </h5>
                            <p><strong>接口：</strong><code>?act=get_academic_push_logs</code></p>
                            <p><strong>需要认证：</strong>是</p>
                        </div>
                    </div>
                </div>
                
                <div class="block block-rounded block-bordered mb-4" id="feature-api">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">
                            <i class="fa fa-toggle-on me-2"></i>功能设置API
                        </h3>
                    </div>
                    <div class="block-content">
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-primary me-2">GET</span>
                                获取功能设置
                            </h5>
                            <p><strong>接口：</strong><code>?act=get_feature_settings</code></p>
                            <p><strong>返回：</strong></p>
                            <pre class="bg-light p-2 rounded"><code>{
    "code": 0,
    "msg": "success",
    "data": [
        {
            "feature_key": "grade",
            "feature_name": "成绩查询",
            "is_enabled": 1,
            "announcement": "功能维护中"
        }
    ]
}</code></pre>
                        </div>
                    </div>
                </div>
                
                <div class="block block-rounded block-bordered mb-4" id="error-code">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">
                            <i class="fa fa-exclamation-circle me-2"></i>错误码说明
                        </h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>错误码</th>
                                    <th>说明</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>0</code></td>
                                    <td>成功</td>
                                </tr>
                                <tr>
                                    <td><code>-1</code></td>
                                    <td>系统错误</td>
                                </tr>
                                <tr>
                                    <td><code>1</code></td>
                                    <td>参数错误</td>
                                </tr>
                                <tr>
                                    <td><code>2</code></td>
                                    <td>未登录或Token无效</td>
                                </tr>
                                <tr>
                                    <td><code>3</code></td>
                                    <td>权限不足</td>
                                </tr>
                                <tr>
                                    <td><code>4</code></td>
                                    <td>数据不存在</td>
                                </tr>
                                <tr>
                                    <td><code>5</code></td>
                                    <td>操作失败</td>
                                </tr>
                                <tr>
                                    <td><code>6</code></td>
                                    <td>账号或密码错误</td>
                                </tr>
                                <tr>
                                    <td><code>7</code></td>
                                    <td>账号已被禁用</td>
                                </tr>
                                <tr>
                                    <td><code>8</code></td>
                                    <td>验证码错误</td>
                                </tr>
                                <tr>
                                    <td><code>9</code></td>
                                    <td>邮箱已被注册</td>
                                </tr>
                                <tr>
                                    <td><code>10</code></td>
                                    <td>用户名已被注册</td>
                                </tr>
                                <tr>
                                    <td><code>11</code></td>
                                    <td>教务账号未绑定</td>
                                </tr>
                                <tr>
                                    <td><code>12</code></td>
                                    <td>宿舍未绑定</td>
                                </tr>
                                <tr>
                                    <td><code>13</code></td>
                                    <td>功能未开放</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.nav-pills .nav-link {
    color: #6b7280;
    padding: 0.5rem 1rem;
    border-radius: 0.375rem;
    margin-bottom: 0.25rem;
}

.nav-pills .nav-link:hover {
    background: #f3f4f6;
    color: #1f2937;
}

pre {
    overflow-x: auto;
    font-size: 0.875rem;
}

code {
    color: #e83e8c;
    background: #f8f9fa;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
}

pre code {
    background: transparent;
    padding: 0;
    color: inherit;
}

#page-container.dark-mode .nav-pills .nav-link:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #e5e7eb;
}

#page-container.dark-mode pre {
    background: #1e293b !important;
}

#page-container.dark-mode code {
    background: rgba(255, 255, 255, 0.1);
}
</style>

<?php include './foot.php'; ?>
