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
            <div class="d-lg-none w-100 mb-3">
                <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#apiDocToc" aria-expanded="false" aria-controls="apiDocToc">
                    <i class="fa fa-list"></i> 接口目录
                </button>
            </div>
            <div class="col-lg-3 mb-4" id="api-doc-toc">
                <div class="block block-rounded block-bordered collapse d-lg-block" id="apiDocToc">
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
                            <a class="nav-link" href="#ai-api">AI分析API</a>
                            <a class="nav-link" href="#notice-api">通知中心API</a>
                            <a class="nav-link" href="#wx-api">微信订阅消息API</a>
                            <a class="nav-link" href="#push-api">推送设置API</a>
                            <a class="nav-link" href="#feature-api">功能设置API</a>
                            <a class="nav-link" href="#app-version-api">App 版本更新API</a>
                            <a class="nav-link" href="#splash-api">开屏广告API</a>
                            <a class="nav-link" href="#cancel-api">账号注销API</a>
                            <a class="nav-link" href="#oauth-bind-api">第三方账号绑定API</a>
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
                                发送邮箱验证邮件
                            </h5>
                            <p><strong>接口：</strong><code>?act=send_verify_email</code></p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>email</td><td>string</td><td>是</td><td>注册时使用的邮箱</td></tr>
                                </tbody>
                            </table>
                            <p><strong>返回：</strong></p>
                            <pre class="bg-light p-2 rounded"><code>{
    "code": 0,
    "msg": "验证邮件已发送，请查收"
}</code></pre>
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
                                    <tr><td>record_sort</td><td>string</td><td>否</td><td>历史电费记录时间排序：desc=最新在前（默认），asc=最早在前</td></tr>
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
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-primary me-2">GET</span>
                                获取课程节次时间（课程时间配置）
                            </h5>
                            <p><strong>接口：</strong><code>?act=get_schedule_times</code></p>
                            <p><strong>需要认证：</strong>是</p>
                            <p><strong>说明：</strong>教务系统只返回「节数」（例如 classTime = 105106 表示第 5、6 节），不返回每节课的起止时间。本接口返回后台「课程时间配置」里按 <strong>课程模式</strong> 维护的节次时间；不传 <code>mode_id</code> 时返回「默认模式」的作息。</p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>mode_id</td><td>string</td><td>否</td><td>课程模式 ID（教务课程模式下拉的 kbjcmsid）；留空使用默认模式</td></tr>
                                </tbody>
                            </table>
                            <p><strong>返回：</strong></p>
                            <pre class="bg-light p-2 rounded"><code>{
    "code": 0,
    "msg": "查询成功",
    "data": {
        "mode_id": "",
        "mode_name": "默认作息（标准）",
        "is_default": 1,
        "total": 11,
        "periods": [
            {"index": 1, "code": "101", "name": "第1节", "start": "08:15", "end": "08:55", "time": "08:15-08:55"},
            {"index": 2, "code": "102", "name": "第2节", "start": "09:05", "end": "09:45", "time": "09:05-09:45"}
        ]
    }
}</code></pre>
                        </div>
                    </div>
                </div>
                
                <div class="block block-rounded block-bordered mb-4" id="ai-api">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">
                            <i class="fa fa-robot me-2"></i>AI分析API
                        </h3>
                    </div>
                    <div class="block-content">
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                AI 成绩分析
                            </h5>
                            <p><strong>接口：</strong><code>?act=ai_analyze_grades</code></p>
                            <p><strong>需要认证：</strong>是</p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>data</td><td>string(JSON)</td><td>是</td><td>JSON 字符串，结构见下</td></tr>
                                </tbody>
                            </table>
                            <p><strong>data 结构：</strong></p>
                            <pre class="bg-light p-2 rounded"><code>{
    "semester": "2024-2025-1",
    "gpa": "3.5",
    "credit": "60",
    "achievement": [
        {"courseName":"高等数学","fraction":"90","credit":"4","gpa":"4.0","curriculumAttributes":"必修"},
        {"courseName":"大学英语","fraction":"82","credit":"3","gpa":"3.3","curriculumAttributes":"必修"}
    ]
}</code></pre>
                            <p><strong>返回：</strong></p>
                            <pre class="bg-light p-2 rounded"><code>{
    "code": 0,
    "msg": "ok",
    "data": {
        "content": "（AI 生成的 Markdown 分析报告）",
        "cached": 0
    }
}</code></pre>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                AI 用电分析
                            </h5>
                            <p><strong>接口：</strong><code>?act=ai_analyze_power</code></p>
                            <p><strong>需要认证：</strong>是</p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>data</td><td>string(JSON)</td><td>是</td><td>JSON 字符串，type 为 statistics 或 query</td></tr>
                                </tbody>
                            </table>
                            <p><strong>data 结构（type=statistics 宿舍统计）：</strong></p>
                            <pre class="bg-light p-2 rounded"><code>{
    "type": "statistics",
    "dormitory": "1栋-101",
    "stats": {"healthText":"良好","yesterdayUsage":5.2,"yesterdayCost":3,"dailyAvgUsage":4.8,"dailyAvgCost":2.8,"latestBalance":30,"estimatedDays":12},
    "recentTrend": [{"label":"09-01","usage":5.1}],
    "records": [{"time":"2024-09-01","balance":35,"usage":5.1,"cost":3}]
}</code></pre>
                            <p><strong>data 结构（type=query 电费查询）：</strong></p>
                            <pre class="bg-light p-2 rounded"><code>{
    "type": "query",
    "rooms": [
        {"name":"1栋-101","balance":"30","remainingKwh":"120","pricePerKwh":"0.6","estimatedDays":"60","statusText":"正常","timeRangeText":"本月"}
    ]
}</code></pre>
                            <p><strong>返回：</strong></p>
                            <pre class="bg-light p-2 rounded"><code>{
    "code": 0,
    "msg": "ok",
    "data": {
        "content": "（AI 生成的 Markdown 分析报告）",
        "cached": 0
    }
}</code></pre>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                AI 课表分析
                            </h5>
                            <p><strong>接口：</strong><code>?act=ai_analyze_schedule</code></p>
                            <p><strong>需要认证：</strong>是</p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>data</td><td>string(JSON)</td><td>是</td><td>JSON 字符串，type 为 semester 或 today</td></tr>
                                </tbody>
                            </table>
                            <p><strong>data 结构（type=semester 整周课表）：</strong></p>
                            <pre class="bg-light p-2 rounded"><code>{
    "type": "semester",
    "week": "10",
    "courses": [
        {"name":"高等数学","weekDay":"周一","section":"1-2","location":"A101","teacher":"张三","weeks":"1-16","credit":"4","nature":"必修"}
    ]
}</code></pre>
                            <p><strong>data 结构（type=today 今日安排）：</strong></p>
                            <pre class="bg-light p-2 rounded"><code>{
    "type": "today",
    "dayName": "周一",
    "date": "2024-09-09",
    "courses": [
        {"name":"高等数学","time":"08:15-09:55","location":"A101","teacher":"张三"}
    ]
}</code></pre>
                            <p><strong>返回：</strong></p>
                            <pre class="bg-light p-2 rounded"><code>{
    "code": 0,
    "msg": "ok",
    "data": {
        "content": "（AI 生成的 Markdown 分析报告）",
        "cached": 0
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
                                <span class="badge bg-success me-2">POST</span>
                                添加/更新微信模板
                            </h5>
                            <p><strong>接口：</strong><code>?act=add_wx_template</code></p>
                            <p><strong>需要认证：</strong>是</p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>template_id</td><td>string</td><td>是</td><td>微信模板ID</td></tr>
                                    <tr><td>name</td><td>string</td><td>是</td><td>模板名称</td></tr>
                                    <tr><td>description</td><td>string</td><td>否</td><td>模板描述</td></tr>
                                    <tr><td>fields</td><td>string</td><td>否</td><td>模板字段（JSON 字符串）</td></tr>
                                    <tr><td>default_data</td><td>string</td><td>否</td><td>默认数据（JSON 字符串）</td></tr>
                                </tbody>
                            </table>
                            <p><strong>返回：</strong></p>
                            <pre class="bg-light p-2 rounded"><code>{
    "code": 0,
    "msg": "添加成功"
}</code></pre>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-success me-2">POST</span>
                                删除微信模板
                            </h5>
                            <p><strong>接口：</strong><code>?act=delete_wx_template</code></p>
                            <p><strong>需要认证：</strong>是</p>
                            <p><strong>参数：</strong></p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数名</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>id</td><td>int</td><td>是</td><td>模板记录ID</td></tr>
                                </tbody>
                            </table>
                            <p><strong>返回：</strong></p>
                            <pre class="bg-light p-2 rounded"><code>{
    "code": 0,
    "msg": "删除成功"
}</code></pre>
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
                    <div class="mb-4">
                        <h5 class="text-primary">
                            <span class="badge bg-primary me-2">GET</span>
                            获取首页速览数据
                        </h5>
                        <p><strong>接口：</strong><code>?act=get_glance_data</code></p>
                        <p><strong>需要认证：</strong>是</p>
                        <p><strong>返回：</strong></p>
                        <pre class="bg-light p-2 rounded"><code>{
    "code": 0,
    "msg": "success",
    "data": {
        "next_course": {"name":"高等数学","location":"A101","time":"10:05-11:35"},
        "all_courses_ended": false,
        "course_load_error": false,
        "power_info": {"balance":30,"estimatedDays":12},
        "no_dormitory": false
    }
}</code></pre>
                    </div>
                    </div>
                </div>
                
                <div class="block block-rounded block-bordered mb-4" id="app-version-api">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">
                            <i class="fa fa-cloud-download-alt me-2"></i>App 版本更新API
                        </h3>
                    </div>
                    <div class="block-content">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle me-2"></i>
                            用于 Uniapp / 小程序 / App 端检查版本更新与查看更新日志。是否返回更新信息由后台「版本管理」的「开启 App 版本更新检测」开关控制，且版本记录需为「上架」状态。以下接口均为<strong>免登录</strong>。
                        </div>

                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-primary me-2">GET</span>
                                检查版本更新
                            </h5>
                            <p><strong>接口：</strong><code>?act=get_app_version</code></p>
                            <p><strong>需要认证：</strong>否</p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>version_code</td><td>int</td><td>否</td><td>当前客户端数字版本号（如 1001），用于比较大小；默认 0</td></tr>
                                    <tr><td>platform</td><td>string</td><td>否</td><td>平台：all / android / ios / harmony，默认 all；非法值按 all 处理</td></tr>
                                </tbody>
                            </table>
                            <p><strong>返回：</strong></p>
                            <pre class="bg-light p-2 rounded"><code>{
    "code": 0,
    "msg": "success",
    "data": {
        "has_update": true,
        "force_update": 0,
        "version": "1.3.26",
        "version_code": 1002,
        "download_url": "https://xxx.com/app.apk",
        "changelog": "1. 新增xxx\n2. 修复xxx"
    }
}</code></pre>
                            <p>说明：无更新或未开启检测时 <code>has_update</code> 为 <code>false</code>；<code>force_update</code> 为 1 表示强制更新。</p>
                        </div>

                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-primary me-2">GET</span>
                                获取更新日志
                            </h5>
                            <p><strong>接口：</strong><code>?act=get_app_version_log</code></p>
                            <p><strong>需要认证：</strong>否</p>
                            <p>说明：返回后台「版本管理」中<strong>已上架</strong>版本的更新日志列表（按数字版本号倒序，最多 20 条）。</p>
                            <p><strong>返回：</strong></p>
                            <pre class="bg-light p-2 rounded"><code>{
    "code": 0,
    "msg": "success",
    "data": [
        {
            "version": "1.3.26",
            "version_code": 1002,
            "platform": "android",
            "download_url": "https://xxx.com/app.apk",
            "download_type": "direct",
            "download_source": "direct",
            "download_pwd": "",
            "download_page": "",
            "changelog": "1. 新增xxx\n2. 修复xxx",
            "created_at": "2026-09-19 12:00:00"
        }
    ]
}</code></pre>
                            <p>说明：<code>platform</code> 取值 all（全平台）/ android / ios / harmony（鸿蒙）。</p>
                            <p>说明：下载地址与「检查更新」接口使用同一套解析规则——蓝奏云分享链接会转成本站中转直链并返回 <code>download_pwd</code>（下载密码）与 <code>download_page</code>（原分享页）；apk/ipa/wgt 直链 <code>download_type</code> 为 direct；其它地址按外部页处理。客户端仅在浏览器中打开，不提供应用内直接下载。</p>
                        </div>
                    </div>
                </div>

                <div class="block block-rounded block-bordered mb-4" id="splash-api">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">
                            <i class="fa fa-tv me-2"></i>开屏广告API
                        </h3>
                    </div>
                    <div class="block-content">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle me-2"></i>
                            用于小程序 / Uniapp 启动时拉取开屏广告。是否返回广告由后台「开屏广告」的「开启开屏广告」开关控制，且需存在「启用」状态的广告记录。接口免登录。
                        </div>
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <span class="badge bg-primary me-2">GET</span>
                                获取开屏广告
                            </h5>
                            <p><strong>接口：</strong><code>?act=get_splash_ad</code></p>
                            <p><strong>需要认证：</strong>否</p>
                            <table class="table table-bordered table-sm">
                                <thead><tr><th>参数</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                                <tbody>
                                    <tr><td>client</td><td>string</td><td>否</td><td>客户端：wx（小程序）/ app（Uniapp）/ all（通用），默认 all</td></tr>
                                </tbody>
                            </table>
                            <p><strong>返回（有广告）：</strong></p>
                            <pre class="bg-light p-2 rounded"><code>{
    "code": 0,
    "msg": "success",
    "data": {
        "id": 1,
        "title": "开学季开屏",
        "image": "/uploads/splash/xxx.jpg",
        "image_url": "https://xxx.com/uploads/splash/xxx.jpg",
        "url": "/pages/academic-grade/index",
        "duration": 3,
        "site_name": "智慧校园"
    }
}</code></pre>
                            <p>无广告或未开启时 <code>data</code> 为 <code>null</code>；<code>duration</code> 为展示时长（秒，1~15）。</p>
                        </div>
                    </div>
                </div>

                <div class="block block-rounded block-bordered mb-4" id="cancel-api">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">
                            <i class="fa fa-user-slash me-2"></i>账号注销API（用户端）
                        </h3>
                    </div>
                    <div class="block-content">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle me-2"></i>
                            账号注销为用户端（Web）流程，接口位于 <code>{站点}/user/ajax.php</code>，依赖登录态（Session）。是否开启、身份验证方式（密码 / 邮件验证码 / 人机验证组合）、冷静期开关与天数，均由后台「注销管理」配置决定。
                        </div>
                        <p><strong>流程：</strong>阅读并同意《用户注销服务协议》→ 风险告知 → 身份核验（按后台配置；<strong>点击「下一步」即完成核验</strong>，通过后才可进入下一步，并签发 30 分钟一次性核验凭据）→ 提交注销申请（仅校验核验凭据 + 确认短语）→ 启用冷静期时进入冷静期（期间登录即自动撤销）→ 冷静期到期由定时任务 <code>cron_account_cancel.php</code> 正式注销。</p>

                        <h5 class="text-primary mt-4">1. 发送邮箱验证码</h5>
                        <p><strong>接口：</strong><code>/user/ajax.php?act=send_cancel_code</code>（POST）</p>
                        <p>说明：仅当后台验证方式包含"邮件验证码"时可用；发送至当前登录用户的注册邮箱；60 秒冷却，验证码 10 分钟内有效。</p>
                        <p><strong>返回：</strong></p>
                        <pre class="bg-light p-2 rounded"><code>{
    "code": 0,
    "msg": "验证码已发送至您的邮箱"
}</code></pre>

                        <h5 class="text-primary mt-4">2. 身份核验（第三步「下一步」）</h5>
                        <p><strong>接口：</strong><code>/user/ajax.php?act=verify_cancel_identity</code>（POST）</p>
                        <p>说明：点击注销流程第三步的「下一步」时调用，完成密码 / 邮箱验证码 / 人机验证核验；<strong>核验通过才允许进入第四步</strong>，通过后服务端签发 30 分钟有效的一次性核验凭据（Web 端存于会话），后续提交无需重复输入。</p>
                        <table class="table table-bordered table-sm">
                            <thead><tr><th>参数</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                            <tbody>
                                <tr><td>password</td><td>string</td><td>条件</td><td>验证方式含密码时必填（明文，服务端 MD5 后比对）</td></tr>
                                <tr><td>email_code</td><td>string</td><td>条件</td><td>验证方式含邮件验证码时必填（校验通过即作废）</td></tr>
                                <tr><td>tc_ticket / tc_randstr</td><td>string</td><td>否*</td><td>腾讯滑块票据</td></tr>
                                <tr><td>captcha</td><td>string</td><td>否*</td><td>图形验证码（滑块不可用时；与 tc_ticket 二者至少一个）</td></tr>
                            </tbody>
                        </table>
                        <p><strong>返回：</strong></p>
                        <pre class="bg-light p-2 rounded"><code>{
    "code": 0,
    "msg": "身份核验通过"
}</code></pre>
                        <p>失败返回 <code>code: -1</code>，提示 <code>密码错误</code> / <code>邮箱验证码错误或已失效</code> / <code>请先完成安全验证</code>。</p>

                        <h5 class="text-primary mt-4">3. 提交注销申请</h5>
                        <p><strong>接口：</strong><code>/user/ajax.php?act=cancel_account</code>（POST）</p>
                        <table class="table table-bordered table-sm">
                            <thead><tr><th>参数</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                            <tbody>
                                <tr><td>agree</td><td>int</td><td>是</td><td>固定为 1，表示已同意《用户注销服务协议》</td></tr>
                                <tr><td>confirm_text</td><td>string</td><td>是</td><td>确认短语，须等于"我已确认注销后用户数据无法找回并确认注销"</td></tr>
                                <tr><td>password / email_code</td><td>string</td><td>否</td><td><strong>兜底参数</strong>：仅当无第三步核验凭据（旧流程）时才现场核验</td></tr>
                                <tr><td>tc_ticket / tc_randstr</td><td>string</td><td>否</td><td><strong>兜底参数</strong>：同上</td></tr>
                                <tr><td>captcha</td><td>string</td><td>否</td><td><strong>兜底参数</strong>：同上</td></tr>
                            </tbody>
                        </table>
                        <p><strong>返回（启用冷静期）：</strong></p>
                        <pre class="bg-light p-2 rounded"><code>{
    "code": 0,
    "msg": "注销申请已提交，账号将在 2026-09-28 正式注销，期间登录可撤销",
    "expire_time": "2026-09-28 12:00:00"
}</code></pre>
                        <p><strong>返回（未启用冷静期，立即注销）：</strong></p>
                        <pre class="bg-light p-2 rounded"><code>{
    "code": 0,
    "msg": "账号已注销",
    "immediate": 1
}</code></pre>

                        <p><strong>说明：</strong>默认仅校验第三步签发的核验凭据（30 分钟有效、一次性）；凭据过期返回 <code>code: -3</code>「身份核验已超时，请返回上一步重新核验」，前端应退回第三步重新核验。</p>

                        <h5 class="text-primary mt-4">4. 查询注销状态</h5>
                        <p><strong>接口：</strong><code>/user/ajax.php?act=cancellation_status</code>（POST）</p>
                        <p><strong>返回：</strong></p>
                        <pre class="bg-light p-2 rounded"><code>{
    "code": 0,
    "enable": 1,
    "verify_mode": "password",
    "cooling_enable": 1,
    "cooling_days": 10,
    "is_cancelling": 0,
    "cancel_time": null,
    "expire_time": null,
    "remain_days": 0
}</code></pre>

                        <div class="alert alert-warning mt-3 mb-0">
                            <strong>注意：</strong>冷静期内没有独立的"撤销"接口——用户在冷静期内<strong>重新登录即自动撤销</strong>注销；冷静期到期后由定时任务执行正式注销，届时账号及个人数据将被删除。
                        </div>

                        <h5 class="text-primary mt-4">5. App / 客户端接口（api.php，Token 鉴权）</h5>
                        <p>供小程序 / App 调用，需在请求头携带 <code>Authorization: Bearer {token}</code>：</p>
                        <table class="table table-bordered table-sm">
                            <thead><tr><th>接口</th><th>方法</th><th>参数</th><th>说明</th></tr></thead>
                            <tbody>
                                <tr><td><code>api.php?act=get_cancel_config</code></td><td>GET</td><td>-</td><td>返回注销开关、验证方式、冷静期、协议全文与当前注销状态</td></tr>
                                <tr><td><code>api.php?act=send_cancel_code</code></td><td>POST</td><td>-</td><td>发送邮箱验证码（60s 冷却，10 分钟有效）</td></tr>
                                <tr><td><code>api.php?act=verify_cancel_identity</code></td><td>POST</td><td>password、email_code</td><td>第三步「下一步」核验身份；成功后返回 <code>pass</code> 核验凭据（30 分钟、一次性）</td></tr>
                                <tr><td><code>api.php?act=cancel_account</code></td><td>POST</td><td>agree、confirm_text、<strong>pass</strong></td><td>提交注销申请（App 端不含人机验证；以第三步签发的核验凭据 + 确认短语核验。旧客户端可不传 pass，改传 password/email_code 现场核验）</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="block block-rounded block-bordered mb-4" id="oauth-bind-api">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">
                            <i class="fa fa-link me-2"></i>第三方账号绑定API（聚合登录）
                        </h3>
                    </div>
                    <div class="block-content">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle me-2"></i>
                            可绑定的渠道与总开关由后台「聚合登录配置」决定。Web 端接口位于 <code>{站点}/user/ajax.php</code>（Session 登录态）；App / 小程序端位于 <code>{站点}/api.php</code>（Token 鉴权），两者参数与返回结构一致。
                        </div>
                        <p><strong>绑定流程（App / 小程序）：</strong>调用 <code>get_oauth_url</code> 获取授权地址 → 在 WebView 中打开并完成第三方授权 → 服务端回调 <code>user/oauth_callback.php</code> 完成绑定 → 返回 App 后刷新绑定状态。WebView 内没有登录会话，服务端通过<strong>一次性绑定票据</strong>（<code>state=bindapp_{ticket}</code>，10 分钟有效、绑定即失效）识别发起绑定的用户；App 端绑定结果页为 <code>user/oauth_bind_result.php</code>。</p>

                        <h5 class="text-primary mt-4">1. 获取绑定列表</h5>
                        <p><strong>接口：</strong><code>/api.php?act=get_oauth_binds</code>（GET，需 Token）／<code>/user/ajax.php?act=get_oauth_binds</code>（POST，需登录）</p>
                        <p><strong>返回：</strong></p>
                        <pre class="bg-light p-2 rounded"><code>{
    "code": 0,
    "msg": "success",
    "data": {
        "enabled": 1,
        "channels": {"qq": {"name": "QQ", "icon": "fab fa-qq", "color": "#12b7f5", "svg": "/images/logo/qq.svg"}},
        "binds": [
            {"type": "qq", "name": "QQ", "color": "#12b7f5", "nickname": "小明", "avatar": "", "create_time": "2026-09-18 10:00:00"}
        ]
    }
}</code></pre>

                        <p><code>channels</code> 中每个渠道包含 <code>name</code>（名称）、<code>icon</code>（图标字体 class，如 <code>fab fa-qq</code>）、<code>color</code>（品牌色）、<code>svg</code>（品牌 SVG 图标地址，位于 <code>php/images/logo/</code>，兼容子目录部署；该渠道暂无 SVG 时为空串，前端回退用 <code>icon</code> 渲染）。</p>

                        <h5 class="text-primary mt-4">2. 获取授权地址（App / 小程序）</h5>
                        <p><strong>接口：</strong><code>/api.php?act=get_oauth_url</code>（POST，需 Token）</p>
                        <table class="table table-bordered table-sm">
                            <thead><tr><th>参数</th><th>类型</th><th>必填</th><th>说明</th></tr></thead>
                            <tbody>
                                <tr><td>type</td><td>string</td><td>是</td><td>渠道标识，如 <code>qq</code> / <code>wx</code> / <code>github</code>（须为后台已启用渠道）</td></tr>
                            </tbody>
                        </table>
                        <p><strong>Web 端对应接口：</strong><code>/user/ajax.php?act=get_oauth_url</code>（POST，Session 登录态），参数 <code>type</code> 与 <code>state</code>（<code>login</code> 登录 / <code>bind</code> 绑定），返回结构相同；Web 端绑定页为 <code>/user/oauth_bind.php</code>。</p>
                        <p><strong>返回：</strong></p>
                        <pre class="bg-light p-2 rounded"><code>{
    "code": 0,
    "msg": "success",
    "data": {"url": "https://...", "qrcode": "https://..."}
}</code></pre>

                        <h5 class="text-primary mt-4">3. 解除绑定</h5>
                        <p><strong>接口：</strong><code>/api.php?act=unbind_oauth</code>（POST，需 Token）／<code>/user/ajax.php?act=unbind_oauth</code>（POST，需登录）</p>
                        <p><strong>参数：</strong><code>type</code>（渠道标识，必填）</p>
                        <p><strong>返回：</strong><code>{"code":0,"msg":"解绑成功"}</code></p>

                        <h5 class="text-primary mt-4">4. 第三方登录（登录页，无需鉴权）</h5>
                        <p>App / 小程序登录页可通过聚合登录完成第三方一键登录：回调时同样没有登录会话，因此用<strong>登录票据</strong>标识本次登录——回调把票据标记为"已授权"，App 轮询到后由服务端签发正式登录令牌（票据一次性消费，10 分钟有效）。</p>
                        <table class="table table-bordered table-sm">
                            <thead><tr><th>接口</th><th>方法</th><th>参数</th><th>说明</th></tr></thead>
                            <tbody>
                                <tr><td><code>api.php?act=get_oauth_channels</code></td><td>GET</td><td>-</td><td>返回已启用的登录渠道，供登录页渲染第三方登录按钮</td></tr>
                                <tr><td><code>api.php?act=get_oauth_login_url</code></td><td>POST</td><td>type</td><td>返回授权地址 <code>url</code> 与登录票据 <code>ticket</code>（10 分钟有效）</td></tr>
                                <tr><td><code>api.php?act=oauth_login_poll</code></td><td>POST</td><td>ticket</td><td>轮询登录结果：<code>status=pending</code> 表示用户尚未完成授权；<code>status=ok</code> 时返回正式登录 <code>token</code> 及用户信息</td></tr>
                            </tbody>
                        </table>
                        <p><strong>返回示例（授权完成）：</strong></p>
                        <pre class="bg-light p-2 rounded"><code>{
    "code": 0,
    "msg": "登录成功",
    "data": {
        "status": "ok",
        "token": "…",
        "user_id": 1,
        "username": "demo",
        "email": "demo@example.com",
        "qq": "",
        "cancel_revoked": 0
    }
}</code></pre>
                        <p><code>cancel_revoked=1</code> 表示该账号此前处于注销冷静期，本次登录已<strong>自动撤销注销</strong>，客户端应提示用户。返回 <code>code:-1</code> 时（票据失效 / 账号被封禁或已注销）客户端应停止轮询并提示重新登录。</p>
                        <p><strong>Web 端：</strong>登录页通过 <code>/user/ajax.php?act=get_oauth_channels</code> 与 <code>get_oauth_url</code>（<code>state=login</code>）完成，回调后服务端直接写入会话并跳转用户中心，无需轮询。</p>

                        <div class="alert alert-warning mt-3 mb-0">
                            <strong>注意：</strong>一个第三方账号只能绑定一个本平台账号；若该第三方账号已被其它有效账号绑定，绑定流程只返回错误提示，<strong>绝不会切换当前登录账号</strong>。第三方登录要求该第三方账号<strong>已绑定</strong>本平台账号（不支持自动注册），未绑定时返回提示并引导用户先用账号密码登录后绑定。小程序端使用 WebView 打开授权页时，需将聚合登录服务域名（<code>u.daib.cn</code>）及各平台授权域名加入小程序<strong>业务域名白名单</strong>。
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

                <!-- API文档专属：PC端左侧目录固定 + 返回顶部按钮 -->
                <style id="apiDocExtraStyle">
                @media (min-width: 992px) {
                    #api-doc-toc .block { position: sticky; top: 80px; }
                }
                #backToTop {
                    position: fixed; right: 24px; bottom: 24px; z-index: 1080;
                    display: none; width: 44px; height: 44px; padding: 0;
                    border-radius: 50%; box-shadow: 0 2px 8px rgba(0,0,0,.2);
                }
                @media (max-width: 991.98px) {
                    .block-content table { display: block; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
                }
                </style>
                <button id="backToTop" type="button" class="btn btn-primary" title="返回顶部">
                    <i class="fa fa-arrow-up"></i>
                </button>
                <script>
                (function(){
                    var btn = document.getElementById('backToTop');
                    if (btn && !btn.dataset.bound) {
                        btn.dataset.bound = '1';
                        btn.addEventListener('click', function(){ window.scrollTo({ top: 0, behavior: 'smooth' }); });
                    }
                    var toc = document.getElementById('apiDocToc');
                    if (toc && !toc.dataset.bound) {
                        toc.dataset.bound = '1';
                        toc.querySelectorAll('.nav-link').forEach(function(link){
                            link.addEventListener('click', function(){
                                if (window.innerWidth < 992) { toc.classList.remove('show'); }
                            });
                        });
                    }
                    if (!window.__apiDocScrollBound) {
                        window.__apiDocScrollBound = true;
                        var sync = function(){ if (btn) { btn.style.display = (window.scrollY > 300) ? 'block' : 'none'; } };
                        window.addEventListener('scroll', sync, { passive: true });
                        sync();
                    }
                })();
                </script>

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
