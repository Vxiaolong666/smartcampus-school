<?php
$notLogin = true;
include('../Common/Core_brain.php');
/** @var \lib\PdoHelper $DB */
/** @var array|null $adminData */
$act=isset($_REQUEST['act'])?daddslashes($_REQUEST['act']):null;
$ip = $Gets->ip();

require_once '../funsion/OAuthHelper.php';
require_once '../funsion/ScheduleTime.php';
require_once '../funsion/LanzouHelper.php';
require_once '../funsion/Agreements.php';

@header('Content-Type: application/json; charset=UTF-8');

switch($act){
    case 'login':
        $adminUser = addslashes($_POST['adminUser']);
        $adminPwd = md5($_POST['adminPwd']);
        $tcTicket = isset($_POST['tc_ticket']) ? addslashes($_POST['tc_ticket']) : '';
        $tcRandstr = isset($_POST['tc_randstr']) ? addslashes($_POST['tc_randstr']) : '';
        $captcha = isset($_POST['captcha']) ? strtoupper(addslashes($_POST['captcha'])) : '';
        
        if ($adminUser=='' || $adminPwd=='') {
            exit('{"code":0,"msg":"请确保每项都不为空"}');
        }
        
        $captchaValid = false;
        
        if (!empty($tcTicket) && !empty($tcRandstr)) {
            $captchaValid = true;
        } elseif (!empty($captcha)) {
            session_start();
            if (isset($_SESSION['captcha']) && strtoupper($_SESSION['captcha']) === $captcha) {
                $captchaValid = true;
                unset($_SESSION['captcha']);
            }
        }
        
        if (!$captchaValid) {
            exit('{"code":0,"msg":"非法请求，验证码错误或超时"}');
        }
        
        $adminData = $Admin->getAdmin($adminUser);
        
        if(empty($adminData))exit('{"code":0,"msg":"管理员不存在"}');
        if($adminPwd != $adminData['adminPwd'])exit('{"code":0,"msg":"密码错误"}');

        $Admin->loginAdmin($adminUser);
        $_SESSION['adminUser'] = $adminUser;
        $_SESSION['adminId'] = $adminData['id'];
        $_SESSION['adminQq'] = $adminData['adminQq'];
        $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','登录后台中心', NOW(), '".$ip."', '')");
        exit('{"code":1,"msg":"登录成功，请稍后..."}');
    break;
    case 'admininfo':
        if(!$isLogin)exit('{"code":0,"msg":"未登录"}');
        $id = $adminData['id'];
        $adminUser = $adminData['adminUser'];
        $adminQq = addslashes($_POST['adminQq']);
        $adminData = $Admin->getAdmin($adminUser);
        $AdminData = $Admin->getAdminName($adminUser);
        
        $sql = "UPDATE `nteam_admin` SET `adminUser` = '$adminUser',`adminQq` = '$adminQq' WHERE `id` = '$id'";
        if(!empty($_POST['adminPwd'])){
            $adminPwd = md5($_POST['adminPwd']);
            if($adminPwd == $adminData['adminPwd'])exit('{"code":0,"msg":"与原密码相同！"}');
            $sql = "UPDATE `nteam_admin` SET `adminPwd` = '$adminPwd',`adminUser` = '$adminUser',`adminQq` = '$adminQq' WHERE `id` = '$id'";
        }
        $admininfo = $DB->exec($sql);
        if(!$admininfo)exit('{"code":0,"msg":"未修改数据，无需保存！"}');
        if(!empty($_POST['adminPwd'])){$DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','修改账号信息', NOW(), '".$ip."', '')");unset($_SESSION['adminUser']);}
        $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','修改账号信息', NOW(), '".$ip."', '')");
        if(!empty($_POST['adminPwd'])){
            exit('{"code":2,"msg":"修改成功"}');
        }else{
            exit('{"code":1,"msg":"修改成功"}');
        }
    break;
    case 'set':
        if(!$isLogin)exit('{"code":0,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        $result = true;
        foreach($_POST as $k=>$v){
            if(saveSetting($k, $v) === false){
                $result = false;
            }
        }
        if($result){
            $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','修改了网站配置', NOW(), '".$ip."', '')");
            exit('{"code":0,"msg":"设置保存成功！"}');
        }else{ exit('{"code":-1,"msg":"修改设置失败['.$DB->error().']"}');}
    break;
    case 'sets':
        if(!$isLogin)exit('{"code":0,"msg":"未登录"}');
        if($adminData['id']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        $result = true;
        foreach($_POST as $k=>$v){
            if(saveSettings($k, $v) === false){
                $result = false;
            }
        }
        if($result){
            $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','修改了首页配置', NOW(), '".$ip."', '')");
            exit('{"code":0,"msg":"设置保存成功！"}');
        }else{ exit('{"code":-1,"msg":"修改设置失败['.$DB->error().']"}');}
    break;
    case 'AddAdmin':
        if(!$isLogin)exit('{"code":0,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        $data['adminUser'] = addslashes($_POST['adminUser']);
        $data['adminPwd'] = md5($_POST['adminPwd']);
        $data['adminQq'] = $_POST['adminQq'];
        $data['adminRank'] = $_POST['adminRank'];
        if($data['adminUser']==NULL || $data['adminPwd']==NULL || $data['adminQq']==NULL || $data['adminRank']==NULL){
            exit('{"code":-1,"msg":"保存错误,请确保每项都不为空!"}');
        } else {
        $AdminData = $Admin->getAdminName($data['adminUser']);
        if(!empty($AdminData))exit('{"code":-1,"msg":"账号已存在"}');

        if(!$Admin->AddAdmin($data))exit('{"code":-1,"msg":"添加管理员失败！"}');
        $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','添加了一位账号为".$data['adminUser']."管理员', NOW(), '".$ip."', '')");
        exit('{"code":0,"msg":"添加管理员成功！"}');
        }
    break;
    case 'EditAdmin':
        if(!$isLogin)exit('{"code":0,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        $id=intval($_POST['id']);
        $rows=$DB->getRow("select * from nteam_admin where id='$id' limit 1");
        if(!$rows)exit('{"code":-1,"msg":"当前管理员不存在！"}');
        if($id==1 && $adminData['id']!=1){
            exit('{"code":-1,"msg":"去你的 别人信息是你想改就改的？<br>要改自己的请到修改密码页面修改"}');
        }
        $adminUser = addslashes($_POST['adminUser']);
        $adminQq = addslashes($_POST['adminQq']);
        $adminRank = addslashes($_POST['adminRank']);
        $existingAdmin = $Admin->getAdmin($adminUser);
        if($_POST['adminUser']==NULL || $_POST['adminQq']==NULL || $_POST['adminRank']==NULL){
            exit('{"code":-1,"msg":"保存错误,请确保每项都不为空!"}');
        } else {
            if($adminRank>2){
                exit('{"code":-1,"msg":"数据非法！"}');
            }
            if(!empty($existingAdmin) && $existingAdmin['id'] != $id){
                exit('{"code":-1,"msg":"账号已存在！"}');
            }
            $sql = "UPDATE `nteam_admin` SET `adminUser` = '$adminUser',`adminQq` = '$adminQq',`adminRank` = '$adminRank' WHERE `id` = '$id'";
            if(!empty($_POST['adminPwd'])){
                $adminPwd = md5($_POST['adminPwd']);
                if($adminPwd == $existingAdmin['adminPwd'])exit('{"code":-1,"msg":"与原密码相同！"}');
                $sql = "UPDATE `nteam_admin` SET `adminPwd` = '$adminPwd',`adminUser` = '$adminUser',`adminQq` = '$adminQq',`adminRank` = '$adminRank' WHERE `id` = '$id'";
            }
            $admininfo = $DB->exec($sql);
            if(!$admininfo)exit('{"code":-1,"msg":"修改失败。"}');
            $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','修改账号为".$adminUser."的管理员信息', NOW(), '".$ip."', '')");
            exit('{"code":0,"msg":"修改成功！！"}');
        }
    break;
    case 'DelAdmin':
        if(!$isLogin)exit('{"code":0,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        $id=$_POST['id'];
        $rows=$DB->getRow("select * from nteam_admin where id='$id' limit 1");
        if(!$rows)exit('{"code":-1,"msg":"当前管理员不存在"}');
        if(!$Admin->delAdmin($id)){exit('{"code":-1,"msg":"删除管理员失败！"}');}else{
        $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','删除了账号为".$rows['adminUser']."的管理员', NOW(), '".$ip."', '')");
        exit('{"code":0,"msg":"删除管理员成功！"}');}
        break;
    case 'adminfk':
        if(!$isLogin)exit('{"code":0,"msg":"未登录"}');
        if(isset($_SESSION['Tg_submit']) && $_SESSION['Tg_submit']>time()-300){
            exit('{"code":-1,"msg":"请勿频繁提交，要再次申请请等待5分钟!"}');
        }
        $sub = addslashes($_POST['sub']);
        $msg = addslashes($_POST['msg']);
        $qq = addslashes($_POST['qq']);
        $token = addslashes($_POST['token']);
        $yun_url= "https://www.uaovo.com/Ajax.php?act=admintg&sub=".$sub."&msg=".$msg."&qq=".$qq."&token=".$token;
        $yun_get= file_get_contents($yun_url);
        $yun_json= json_decode($yun_get,true);
        if ($yun_json['code'] == 0){
            $_SESSION['Tg_submit']=time();
            exit('{"code":1,"msg":"'.$yun_json['msg'].'"}');
        }else{
            exit('{"code":0,"msg":"'.$yun_json['msg'].'"}');
        }
        break;
    case 'setUser':
        if(!$isLogin)exit('{"code":0,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        $type=addslashes($_GET['type']);
        $id=intval($_POST['id']);
        
        if ($type == 'ban') {
            $sql = "UPDATE nteam_users SET is_banned=1 WHERE id='$id'";
            $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','封禁了用户ID为".$id."的用户', NOW(), '".$ip."', '')");
            if($DB->exec($sql)!==false)exit('{"code":0,"msg":"封禁成功！"}');
            else exit('{"code":-1,"msg":"封禁失败['.$DB->error().']"}');
        }elseif ($type == 'unban') {
            $sql = "UPDATE nteam_users SET is_banned=0 WHERE id='$id'";
            $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','解封了用户ID为".$id."的用户', NOW(), '".$ip."', '')");
            if($DB->exec($sql)!==false)exit('{"code":0,"msg":"解封成功！"}');
            else exit('{"code":-1,"msg":"解封失败['.$DB->error().']"}');
        }elseif ($type == 'del') {
            $rows=$DB->query("select * from nteam_users where id='$id' limit 1")->fetch();
            if(!$rows)exit('{"code":-1,"msg":"用户不存在"}');
            // 级联删除该用户的个人数据（先删子表，最后再删用户，避免残留）
            // 注意：宿舍/电费相关数据予以保留（其他用户可能查询该宿舍电费历史）：
            //   - nteam_power_daily_records：用电历史（按 room_num 存储，不删除）
            //   - nteam_dormitory_bindings：宿舍记录（仅置为停用 status=0，保留记录）
            //   - nteam_push_settings：电费推送设置（仅停用，保留记录）
            //   - nteam_push_logs：电费推送记录（保留）
            $userDataTables = array(
                'nteam_user_oauth',            // 第三方登录授权信息
                'nteam_academic_accounts',     // 教务账号（含明文密码、真实姓名等）
                'nteam_academic_push_settings',// 教务推送设置
                'nteam_academic_push_logs',    // 教务推送日志
                'nteam_tokens',                // 用户令牌（登录/验证）
                'nteam_subscriptions',         // 微信订阅记录（含 openid）
                'nteam_subscription_logs',     // 订阅操作日志
                'nteam_grade_push_records',    // 成绩推送记录
            );
            foreach ($userDataTables as $tbl) {
                if ($DB->exec("DELETE FROM {$tbl} WHERE user_id='$id'") === false) {
                    exit('{"code":-1,"msg":"删除用户数据失败（'.$tbl.'）：'.$DB->error().'"}');
                }
            }
            // 宿舍绑定：软删除（保留宿舍记录，不影响该宿舍电费历史）
            if ($DB->exec("UPDATE nteam_dormitory_bindings SET status=0 WHERE user_id='$id'") === false) {
                exit('{"code":-1,"msg":"停用宿舍绑定失败：'.$DB->error().'"}');
            }
            // 电费推送设置：停用（保留记录，不再推送）
            if ($DB->exec("UPDATE nteam_push_settings SET is_enabled=0 WHERE user_id='$id'") === false) {
                exit('{"code":-1,"msg":"停用电费推送设置失败：'.$DB->error().'"}');
            }
            if($DB->exec("DELETE FROM nteam_users WHERE id='$id'")===false){exit('{"code":-1,"msg":"删除用户失败！"}');}else{
            $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','删除了ID为".$id."的用户及其全部个人数据', NOW(), '".$ip."', '')");
            exit('{"code":0,"msg":"删除用户成功！"}');}
        }elseif ($type == 'edit') {
            $qq = addslashes($_POST['qq']);
            $points = intval($_POST['points']);
            $level = intval($_POST['level']);
            $is_banned = intval($_POST['is_banned']);
            $email_verified = intval($_POST['email_verified']);
            
            $sql="update `nteam_users` set `qq` ='{$qq}',`points` ='{$points}',`level` ='{$level}',`is_banned` ='{$is_banned}',`email_verified` ='{$email_verified}' where `id`='$id'";
            if($DB->exec($sql)!==false){
                $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','修改了ID为".$id."的用户信息', NOW(), '".$ip."', '')");
                exit('{"code":0,"msg":"修改用户信息成功！"}');
            }else{
                exit('{"code":-1,"msg":"修改用户信息失败！"}');
            }
        }elseif ($type == 'add') {
            $username = addslashes($_POST['username']);
            $password = md5($_POST['password']);
            $email = addslashes($_POST['email']);
            $qq = addslashes($_POST['qq']);
            $points = intval($_POST['points']);
            $level = intval($_POST['level']);
            $is_banned = intval($_POST['is_banned']);
            $email_verified = intval($_POST['email_verified']);
            
            if ($username=='' || $password=='' || $email=='') {
                exit('{"code":-1,"msg":"请确保必填项不为空"}');
            }
            
            if (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $username)) {
                exit('{"code":-1,"msg":"用户名只能包含字母、数字、下划线，长度4-20位"}');
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                exit('{"code":-1,"msg":"邮箱格式不正确"}');
            }
            
            $checkUser = $DB->query("SELECT * FROM nteam_users WHERE username='$username'")->fetch();
            if ($checkUser) {
                exit('{"code":-1,"msg":"用户名已存在"}');
            }
            
            $checkEmail = $DB->query("SELECT * FROM nteam_users WHERE email='$email'")->fetch();
            if ($checkEmail) {
                exit('{"code":-1,"msg":"邮箱已被注册"}');
            }
            
            $registerTime = date('Y-m-d H:i:s');
            $sql = "INSERT INTO `nteam_users` (`username`, `password`, `email`, `qq`, `points`, `level`, `is_banned`, `email_verified`, `register_time`) VALUES ('$username', '$password', '$email', '$qq', '$points', '$level', '$is_banned', '$email_verified', '$registerTime')";
            
            if ($DB->exec($sql) !== false) {
                $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','添加了用户".$username."', NOW(), '".$ip."', '')");
                exit('{"code":0,"msg":"添加用户成功！"}');
            } else {
                exit('{"code":-1,"msg":"添加用户失败！"}');
            }
        }else{
            exit('{"code":-1,"msg":"参数错误"}');
        }
    break;
    
    case 'clearMailLogs':
        if(!isset($_SESSION['adminUser'])){
            exit('{"code":-1,"msg":"请先登录"}');
        }
        
        $adminUser = $_SESSION['adminUser'];
        $adminData = $DB->query("SELECT * FROM `nteam_admin` WHERE `adminUser` = '$adminUser'")->fetch();
        if(empty($adminData)){
            exit('{"code":-1,"msg":"管理员信息不存在"}');
        }
        
        if($adminData['adminRank']!=1){
            exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');
        }
        
        $sql = "DELETE FROM `nteam_mail_log`";
        
        if ($DB->exec($sql) !== false) {
            $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','清空了邮件发送日志', NOW(), '".$ip."', '')");
            exit('{"code":0,"msg":"邮件日志已清空"}');
        } else {
            exit('{"code":-1,"msg":"清空失败，请稍后重试"}');
        }
    break;
    
    case 'clear_admin_logs':
        if(!isset($_SESSION['adminUser'])){
            exit('{"code":-1,"msg":"请先登录"}');
        }
        
        $adminUser = $_SESSION['adminUser'];
        $adminData = $DB->query("SELECT * FROM `nteam_admin` WHERE `adminUser` = '$adminUser'")->fetch();
        if(empty($adminData)){
            exit('{"code":-1,"msg":"管理员信息不存在"}');
        }
        
        $sql = "TRUNCATE TABLE `nteam_log`";
        
        if ($DB->exec($sql) !== false) {
            $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','清空了操作日志', NOW(), '".$ip."', '')");
            exit('{"code":0,"msg":"操作日志已清空"}');
        } else {
            exit('{"code":-1,"msg":"清空失败，请稍后重试"}');
        }
    break;
    
    case 'testMail':
        if(!isset($_SESSION['adminUser'])){
            exit('{"code":-1,"msg":"请先登录"}');
        }
        
        $adminUser = $_SESSION['adminUser'];
        $adminData = $DB->query("SELECT * FROM `nteam_admin` WHERE `adminUser` = '$adminUser'")->fetch();
        if(empty($adminData)){
            exit('{"code":-1,"msg":"管理员信息不存在"}');
        }
        
        $testEmail = addslashes($_POST['test_email']);
        
        if (empty($testEmail)) {
            exit('{"code":-1,"msg":"请输入测试邮箱地址"}');
        }
        
        if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            exit('{"code":-1,"msg":"邮箱格式不正确"}');
        }
        
        $subject = '【邮件测试】' . conf('Name') . ' - 邮件发送功能测试';
        $message = '<html>
<head>
    <meta charset="UTF-8">
    <title>邮件测试</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
        <h2 style="color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 10px;">邮件发送测试成功！</h2>
        <p>尊敬的管理员，</p>
        <p>这是一封来自 <strong>' . conf('Name') . '</strong> 的测试邮件。</p>
        <p>如果您收到这封邮件，说明邮件发送功能配置正确，可以正常使用。</p>
        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p><strong>测试信息：</strong></p>
            <ul>
                <li>发送时间：' . date('Y-m-d H:i:s') . '</li>
                <li>发送人：' . $adminUser . '</li>
                <li>接收邮箱：' . htmlspecialchars($testEmail) . '</li>
            </ul>
        </div>
        <p style="color: #6c757d; font-size: 12px;">此邮件为系统自动发送，请勿回复。</p>
    </div>
</body>
</html>';
        
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        try {
            $result = send_mail($testEmail, $subject, $message);
            
            if ($result === true) {
                $checkTable = $DB->query("SHOW TABLES LIKE 'nteam_mail_log'")->fetch();
                if ($checkTable) {
                    $lastLog = $DB->query("SELECT * FROM nteam_mail_log WHERE to_email = '$testEmail' ORDER BY id DESC LIMIT 1")->fetch();
                    if ($lastLog) {
                        exit('{"code":0,"msg":"测试邮件发送成功！请检查您的邮箱（包括垃圾邮件文件夹）。发送时间：' . $lastLog['create_time'] . '"}');
                    }
                }
                exit('{"code":0,"msg":"测试邮件发送成功！请检查您的邮箱（包括垃圾邮件文件夹）"}');
            } else {
                exit('{"code":-1,"msg":"测试邮件发送失败：' . addslashes($result) . '"}');
            }
        } catch (Exception $e) {
            exit('{"code":-1,"msg":"发送邮件时发生错误：' . addslashes($e->getMessage()) . '"}');
        } catch (Error $e) {
            exit('{"code":-1,"msg":"发送邮件时发生错误：' . addslashes($e->getMessage()) . '"}');
        } catch (Throwable $e) {
            exit('{"code":-1,"msg":"发送邮件时发生错误：' . addslashes($e->getMessage()) . '"}');
        }
    break;
    
    case 'update_academic_account':
        $id = intval($_POST['id']);
        $studentId = addslashes(trim($_POST['student_id']));
        $password = $_POST['password'];
        $realName = isset($_POST['real_name']) ? addslashes(trim($_POST['real_name'])) : '';
        
        if ($id <= 0) {
            exit('{"code":-1,"msg":"参数错误"}');
        }
        
        if ($studentId == '' || $password == '') {
            exit('{"code":-1,"msg":"学工号和密码不能为空"}');
        }
        
        $existing = $DB->query("SELECT * FROM nteam_academic_accounts WHERE id='$id'")->fetch();
        if (!$existing) {
            exit('{"code":-1,"msg":"记录不存在"}');
        }
        
        $checkStudentId = $DB->query("SELECT * FROM nteam_academic_accounts WHERE student_id='$studentId' AND id != '$id'")->fetch();
        if ($checkStudentId) {
            exit('{"code":-1,"msg":"该学工号已被其他用户绑定"}');
        }
        
        $realNameValue = empty($realName) ? 'NULL' : "'$realName'";
        $sql = "UPDATE `nteam_academic_accounts` SET `student_id`='$studentId', `password`='$password', `real_name`=$realNameValue WHERE id='$id'";
        
        if ($DB->exec($sql) !== false) {
            exit('{"code":0,"msg":"更新成功"}');
        } else {
            exit('{"code":-1,"msg":"更新失败"}');
        }
    break;
    
    case 'delete_academic_account':
        $id = intval($_POST['id']);
        
        if ($id <= 0) {
            exit('{"code":-1,"msg":"参数错误"}');
        }
        
        $existing = $DB->query("SELECT * FROM nteam_academic_accounts WHERE id='$id'")->fetch();
        if (!$existing) {
            exit('{"code":-1,"msg":"记录不存在"}');
        }
        
        $sql = "DELETE FROM `nteam_academic_accounts` WHERE id='$id'";
        
        if ($DB->exec($sql) !== false) {
            exit('{"code":0,"msg":"删除成功"}');
        } else {
            exit('{"code":-1,"msg":"删除失败"}');
        }
    break;
    
    case 'update_dormitory_binding':
        if(!$isLogin)exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        
        $result = updateDormitoryBinding($DB, $_POST);
        $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','修改了宿舍绑定记录', NOW(), '".$ip."', '')");
        exit(json_encode($result));
    break;
    
    case 'delete_dormitory_binding':
        if(!$isLogin)exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        
        $id = intval($_POST['id']);
        $result = deleteDormitoryBinding($DB, $id);
        $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','删除了宿舍绑定记录', NOW(), '".$ip."', '')");
        exit(json_encode($result));
    break;
    
    case 'upload_announcement_image':
        if(!$isLogin)exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        
        if (!isset($_FILES['file']) || $_FILES['file']['error'] != 0) {
            exit('{"code":-1,"msg":"请选择要上传的图片"}');
        }
        
        $file = $_FILES['file'];
        $type = isset($_POST['type']) ? $_POST['type'] : 'image';
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            exit('{"code":-1,"msg":"只允许上传图片文件"}');
        }
        
        if ($file['size'] > 5 * 1024 * 1024) {
            exit('{"code":-1,"msg":"图片大小不能超过5MB"}');
        }
        
        $uploadDir = '../uploads/announcements/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = date('YmdHis') . '_' . uniqid() . '.' . $ext;
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $url = '/uploads/announcements/' . $filename;
            $displayUrl = $siteBasePath . $url;
            exit('{"code":0,"msg":"上传成功","data":{"url":"' . $url . '","displayUrl":"' . $displayUrl . '"}}');
        } else {
            exit('{"code":-1,"msg":"上传失败"}');
        }
    break;

    // ===== 协议管理：正文图片上传（协议编辑器「插入图片」） =====
    case 'upload_agreement_image':
        if(!$isLogin)exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}

        if (!isset($_FILES['file']) || $_FILES['file']['error'] != 0) {
            exit('{"code":-1,"msg":"请选择要上传的图片"}');
        }

        $file = $_FILES['file'];

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            exit('{"code":-1,"msg":"只允许上传图片文件"}');
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            exit('{"code":-1,"msg":"图片大小不能超过5MB"}');
        }

        $uploadDir = '../uploads/agreements/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = date('YmdHis') . '_' . uniqid() . '.' . $ext;
        $filepath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $url = '/uploads/agreements/' . $filename;
            $displayUrl = $siteBasePath . $url;
            exit('{"code":0,"msg":"上传成功","data":{"url":"' . $url . '","displayUrl":"' . $displayUrl . '"}}');
        } else {
            exit('{"code":-1,"msg":"上传失败"}');
        }
    break;
    
    case 'save_announcement':
        if(!$isLogin)exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        
        $id = intval($_POST['id']);
        $title = addslashes(trim($_POST['title']));
        $content = addslashes($_POST['content']);
        $thumbnail = addslashes(trim($_POST['thumbnail']));
        $image = addslashes(trim($_POST['image']));
        $author = addslashes(trim($_POST['author']));
        $isTop = isset($_POST['is_top']) ? 1 : 0;
        $isPublished = isset($_POST['is_published']) ? 1 : 0;
        
        if (empty($title) || empty($content)) {
            exit('{"code":-1,"msg":"标题和内容不能为空"}');
        }
        
        if ($id > 0) {
            $sql = "UPDATE nteam_announcements SET 
                    title='$title', 
                    content='$content', 
                    thumbnail='$thumbnail', 
                    image='$image', 
                    author='$author', 
                    is_top='$isTop', 
                    is_published='$isPublished' 
                    WHERE id='$id'";
            
            if ($DB->exec($sql) !== false) {
                $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','修改了公告: $title', NOW(), '".$ip."', '')");
                exit('{"code":0,"msg":"修改成功"}');
            } else {
                exit('{"code":-1,"msg":"修改失败"}');
            }
        } else {
            $sql = "INSERT INTO nteam_announcements (title, content, thumbnail, image, author, is_top, is_published) 
                    VALUES ('$title', '$content', '$thumbnail', '$image', '$author', '$isTop', '$isPublished')";
            
            if ($DB->exec($sql) !== false) {
                $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','添加了公告: $title', NOW(), '".$ip."', '')");
                exit('{"code":0,"msg":"添加成功"}');
            } else {
                exit('{"code":-1,"msg":"添加失败"}');
            }
        }
    break;
    
    case 'delete_announcement':
        if(!$isLogin)exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        
        $id = intval($_POST['id']);
        if ($id <= 0) {
            exit('{"code":-1,"msg":"参数错误"}');
        }
        
        $announcement = $DB->getRow("SELECT * FROM nteam_announcements WHERE id='$id'");
        if (!$announcement) {
            exit('{"code":-1,"msg":"公告不存在"}');
        }
        
        if ($DB->exec("DELETE FROM nteam_announcements WHERE id='$id'") !== false) {
            $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','删除了公告: " . addslashes($announcement['title']) . "', NOW(), '".$ip."', '')");
            exit('{"code":0,"msg":"删除成功"}');
        } else {
            exit('{"code":-1,"msg":"删除失败"}');
        }
    break;
    
    case 'update_feature_setting':
        if(!$isLogin)exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        
        $id = intval($_POST['id']);
        $isEnabled = intval($_POST['is_enabled']);
        $announcement = addslashes(trim($_POST['announcement']));
        $announcementType = addslashes($_POST['announcement_type']);
        
        if ($id <= 0) {
            exit('{"code":-1,"msg":"参数错误"}');
        }
        
        $existing = $DB->getRow("SELECT * FROM nteam_feature_settings WHERE id='$id'");
        if (!$existing) {
            exit('{"code":-1,"msg":"功能设置不存在"}');
        }
        
        $sql = "UPDATE nteam_feature_settings SET 
                is_enabled='$isEnabled', 
                announcement='$announcement', 
                announcement_type='$announcementType' 
                WHERE id='$id'";
        
        if ($DB->exec($sql) !== false) {
            $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','修改了功能设置: " . $existing['feature_name'] . "', NOW(), '".$ip."', '')");
            exit('{"code":0,"msg":"保存成功"}');
        } else {
            exit('{"code":-1,"msg":"保存失败"}');
        }
    break;
    
    case 'toggle_feature_status':
        if(!$isLogin)exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        
        $id = intval($_POST['id']);
        $isEnabled = intval($_POST['is_enabled']);
        
        if ($id <= 0) {
            exit('{"code":-1,"msg":"参数错误"}');
        }
        
        $existing = $DB->getRow("SELECT * FROM nteam_feature_settings WHERE id='$id'");
        if (!$existing) {
            exit('{"code":-1,"msg":"功能设置不存在"}');
        }
        
        $sql = "UPDATE nteam_feature_settings SET is_enabled='$isEnabled' WHERE id='$id'";
        
        if ($DB->exec($sql) !== false) {
            $statusText = $isEnabled == 1 ? '启用' : '禁用';
            $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','将功能设置「" . $existing['feature_name'] . "」" . $statusText . "', NOW(), '".$ip."', '')");
            exit('{"code":0,"msg":"已'.$statusText.'"}');
        } else {
            exit('{"code":-1,"msg":"操作失败"}');
        }
    break;
    
    case 'save_wx_config':
        if(!$isLogin) exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1) exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');
        
        $appid = addslashes(trim($_POST['appid']));
        $secret = addslashes(trim($_POST['secret']));
        $name = addslashes(trim($_POST['name']));
        
        if (empty($appid) || empty($secret)) {
            exit('{"code":-1,"msg":"AppID和AppSecret不能为空"}');
        }
        
        $existing = $DB->getRow("SELECT * FROM nteam_wx_config ORDER BY id DESC LIMIT 1");
        
        if ($existing) {
            $sql = "UPDATE nteam_wx_config SET appid='$appid', secret='$secret', name='$name' WHERE id='{$existing['id']}'";
        } else {
            $sql = "INSERT INTO nteam_wx_config (appid, secret, name) VALUES ('$appid', '$secret', '$name')";
        }
        
        if ($DB->exec($sql) !== false) {
            $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','修改了小程序配置', NOW(), '".$ip."', '')");
            exit('{"code":0,"msg":"保存成功"}');
        } else {
            exit('{"code":-1,"msg":"保存失败"}');
        }
    break;
    
    case 'save_wx_template':
        if(!$isLogin) exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1) exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $templateId = addslashes(trim($_POST['template_id']));
        $name = addslashes(trim($_POST['name']));
        $sceneType = addslashes(trim($_POST['scene_type']));
        $description = addslashes(trim($_POST['description']));
        $fields = addslashes(trim($_POST['fields']));
        $defaultData = addslashes(trim($_POST['default_data']));
        $status = intval($_POST['status']);
        
        if (empty($templateId) || empty($name)) {
            exit('{"code":-1,"msg":"模板ID和名称不能为空"}');
        }
        
        if ($id > 0) {
            $existing = $DB->getRow("SELECT * FROM nteam_wx_templates WHERE id='$id'");
            if (!$existing) {
                exit('{"code":-1,"msg":"模板不存在"}');
            }
            $sql = "UPDATE nteam_wx_templates SET template_id='$templateId', name='$name', scene_type='$sceneType', description='$description', fields='$fields', default_data='$defaultData', status='$status' WHERE id='$id'";
        } else {
            $checkDuplicate = $DB->getRow("SELECT * FROM nteam_wx_templates WHERE template_id='$templateId'");
            if ($checkDuplicate) {
                exit('{"code":-1,"msg":"该模板ID已存在"}');
            }
            $sql = "INSERT INTO nteam_wx_templates (template_id, scene_type, name, description, fields, default_data, status) VALUES ('$templateId', '$sceneType', '$name', '$description', '$fields', '$defaultData', '$status')";
        }
        
        if ($DB->exec($sql) !== false) {
            $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','保存了消息模板: $name', NOW(), '".$ip."', '')");
            exit('{"code":0,"msg":"保存成功"}');
        } else {
            exit('{"code":-1,"msg":"保存失败"}');
        }
    break;
    
    case 'delete_wx_template':
        if(!$isLogin) exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1) exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');
        
        $id = intval($_POST['id']);
        
        if ($id <= 0) {
            exit('{"code":-1,"msg":"参数错误"}');
        }
        
        $existing = $DB->getRow("SELECT * FROM nteam_wx_templates WHERE id='$id'");
        if (!$existing) {
            exit('{"code":-1,"msg":"模板不存在"}');
        }
        
        if ($DB->exec("DELETE FROM nteam_wx_templates WHERE id='$id'") !== false) {
            $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','删除了消息模板: " . $existing['name'] . "', NOW(), '".$ip."', '')");
            exit('{"code":0,"msg":"删除成功"}');
        } else {
            exit('{"code":-1,"msg":"删除失败"}');
        }
    break;

    case 'save_power_config':
        if(!$isLogin) exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1) exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');
        
        $pricePerKwh = floatval($_POST['price_per_kwh']);
        $lowBalanceThreshold = floatval($_POST['low_balance_threshold']);
        $criticalBalanceThreshold = floatval($_POST['critical_balance_threshold']);
        $dailySummaryEnabled = intval($_POST['daily_summary_enabled']);
        $lowBalanceEnabled = intval($_POST['low_balance_enabled']);
        $criticalBalanceEnabled = intval($_POST['critical_balance_enabled']);
        $arrearsEnabled = intval($_POST['arrears_enabled']);
        $emailPushEnabled = intval($_POST['email_push_enabled']);
        $wechatPushEnabled = intval($_POST['wechat_push_enabled']);
        $dailySummaryFrequency = addslashes($_POST['daily_summary_frequency'] ?? 'daily');
        $lowBalanceFrequency = addslashes($_POST['low_balance_frequency'] ?? 'daily');
        $criticalBalanceFrequency = addslashes($_POST['critical_balance_frequency'] ?? 'always');
        $arrearsFrequency = addslashes($_POST['arrears_frequency'] ?? 'always');
        $thresholdAmount = floatval($_POST['threshold_amount'] ?? 5);
        $usageSavingThreshold = floatval($_POST['usage_saving_threshold'] ?? 0.5);
        $usageNormalThreshold = floatval($_POST['usage_normal_threshold'] ?? 1.0);
        $quietHoursStart = addslashes($_POST['quiet_hours_start']);
        $quietHoursEnd = addslashes($_POST['quiet_hours_end']);
        $powerFeeTemplateId = isset($_POST['power_fee_template_id']) ? intval($_POST['power_fee_template_id']) : 0;
        $powerReportTemplateId = isset($_POST['power_report_template_id']) ? intval($_POST['power_report_template_id']) : 0;
        
        $existing = $DB->getRow("SELECT * FROM nteam_power_config WHERE id = 1");
        
        $checkColumn = $DB->getRow("SHOW COLUMNS FROM nteam_power_config LIKE 'power_fee_template_id'");
        $hasTemplateColumns = $checkColumn ? true : false;
        
        if ($existing) {
            $sql = "UPDATE nteam_power_config SET 
                price_per_kwh = '$pricePerKwh',
                low_balance_threshold = '$lowBalanceThreshold',
                critical_balance_threshold = '$criticalBalanceThreshold',
                daily_summary_enabled = '$dailySummaryEnabled',
                low_balance_enabled = '$lowBalanceEnabled',
                critical_balance_enabled = '$criticalBalanceEnabled',
                arrears_enabled = '$arrearsEnabled',
                email_push_enabled = '$emailPushEnabled',
                wechat_push_enabled = '$wechatPushEnabled',
                daily_summary_frequency = '$dailySummaryFrequency',
                low_balance_frequency = '$lowBalanceFrequency',
                critical_balance_frequency = '$criticalBalanceFrequency',
                arrears_frequency = '$arrearsFrequency',
                threshold_amount = '$thresholdAmount',
                usage_saving_threshold = '$usageSavingThreshold',
                usage_normal_threshold = '$usageNormalThreshold',
                quiet_hours_start = '$quietHoursStart',
                quiet_hours_end = '$quietHoursEnd'";
            
            if ($hasTemplateColumns) {
                $sql .= ", power_fee_template_id = " . ($powerFeeTemplateId ?: 'NULL') . ", power_report_template_id = " . ($powerReportTemplateId ?: 'NULL');
            }
            
            $sql .= " WHERE id = 1";
        } else {
            if ($hasTemplateColumns) {
                $sql = "INSERT INTO nteam_power_config 
                    (price_per_kwh, low_balance_threshold, critical_balance_threshold, daily_summary_enabled, low_balance_enabled, critical_balance_enabled, arrears_enabled, email_push_enabled, wechat_push_enabled, daily_summary_frequency, low_balance_frequency, critical_balance_frequency, arrears_frequency, threshold_amount, usage_saving_threshold, usage_normal_threshold, quiet_hours_start, quiet_hours_end, power_fee_template_id, power_report_template_id)
                    VALUES ('$pricePerKwh', '$lowBalanceThreshold', '$criticalBalanceThreshold', '$dailySummaryEnabled', '$lowBalanceEnabled', '$criticalBalanceEnabled', '$arrearsEnabled', '$emailPushEnabled', '$wechatPushEnabled', '$dailySummaryFrequency', '$lowBalanceFrequency', '$criticalBalanceFrequency', '$arrearsFrequency', '$thresholdAmount', '$usageSavingThreshold', '$usageNormalThreshold', '$quietHoursStart', '$quietHoursEnd', " . ($powerFeeTemplateId ?: 'NULL') . ", " . ($powerReportTemplateId ?: 'NULL') . ")";
            } else {
                $sql = "INSERT INTO nteam_power_config 
                    (price_per_kwh, low_balance_threshold, critical_balance_threshold, daily_summary_enabled, low_balance_enabled, critical_balance_enabled, arrears_enabled, email_push_enabled, wechat_push_enabled, daily_summary_frequency, low_balance_frequency, critical_balance_frequency, arrears_frequency, threshold_amount, usage_saving_threshold, usage_normal_threshold, quiet_hours_start, quiet_hours_end)
                    VALUES ('$pricePerKwh', '$lowBalanceThreshold', '$criticalBalanceThreshold', '$dailySummaryEnabled', '$lowBalanceEnabled', '$criticalBalanceEnabled', '$arrearsEnabled', '$emailPushEnabled', '$wechatPushEnabled', '$dailySummaryFrequency', '$lowBalanceFrequency', '$criticalBalanceFrequency', '$arrearsFrequency', '$thresholdAmount', '$usageSavingThreshold', '$usageNormalThreshold', '$quietHoursStart', '$quietHoursEnd')";
            }
        }
        
        if ($DB->exec($sql) !== false) {
            $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','修改了电费配置', NOW(), '".$ip."', '')");
            exit('{"code":0,"msg":"保存成功"}');
        } else {
            $error = $DB->errorInfo();
            exit('{"code":-1,"msg":"保存失败: ' . addslashes($error[2] ?? '未知错误') . '"}');
        }
    break;

    case 'get_push_logs':
        if(!$isLogin) exit('{"code":-1,"msg":"未登录"}');
        
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $pageSize = isset($_GET['page_size']) ? intval($_GET['page_size']) : 20;
        $offset = ($page - 1) * $pageSize;
        
        $status = isset($_GET['status']) ? intval($_GET['status']) : -1;
        $notifyType = isset($_GET['notify_type']) ? addslashes($_GET['notify_type']) : '';
        
        $where = "1=1";
        if ($status >= 0) {
            $where .= " AND pl.status = '$status'";
        }
        if (!empty($notifyType)) {
            $where .= " AND pl.notify_type = '$notifyType'";
        }
        
        $total = $DB->getRow("SELECT COUNT(*) as count FROM nteam_push_logs pl WHERE $where");
        $logs = $DB->getAll("SELECT pl.*, u.username, db.room_num, db.school_area, db.building 
            FROM nteam_push_logs pl 
            LEFT JOIN nteam_users u ON pl.user_id = u.id 
            LEFT JOIN nteam_dormitory_bindings db ON pl.dormitory_binding_id = db.id 
            WHERE $where 
            ORDER BY pl.created_at DESC 
            LIMIT $offset, $pageSize");
        
        exit(json_encode([
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'list' => $logs ?: [],
                'total' => $total['count'] ?? 0,
                'page' => $page,
                'page_size' => $pageSize
            ]
        ]));
    break;
    
    case 'save_academic_push_config':
        if(!$isLogin)exit('{"code":0,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        
        $scheduleReminderEnabled = intval($_POST['schedule_reminder_enabled']);
        $gradeNotifyEnabled = intval($_POST['grade_notify_enabled']);
        $wechatPushEnabled = intval($_POST['wechat_push_enabled']);
        $emailPushEnabled = intval($_POST['email_push_enabled']);
        $scheduleReminderTime = addslashes($_POST['schedule_reminder_time']);
        $quietHoursStart = addslashes($_POST['quiet_hours_start']);
        $quietHoursEnd = addslashes($_POST['quiet_hours_end']);
        $gradeSemester = addslashes($_POST['grade_semester'] ?? '');
        $scheduleTemplateId = isset($_POST['schedule_template_id']) ? intval($_POST['schedule_template_id']) : 0;
        $gradeTemplateId = isset($_POST['grade_template_id']) ? intval($_POST['grade_template_id']) : 0;
        $defaultScheduleMode = addslashes($_POST['default_schedule_mode'] ?? '');
        
        if (strlen($scheduleReminderTime) == 5) {
            $scheduleReminderTime .= ':00';
        }
        if (strlen($quietHoursStart) == 5) {
            $quietHoursStart .= ':00';
        }
        if (strlen($quietHoursEnd) == 5) {
            $quietHoursEnd .= ':00';
        }
        
        $exists = $DB->getRow("SELECT id FROM nteam_academic_push_config WHERE id = 1");
        
        $checkColumn = $DB->getRow("SHOW COLUMNS FROM nteam_academic_push_config LIKE 'schedule_template_id'");
        $hasTemplateColumns = $checkColumn ? true : false;

        $checkDefaultCol = $DB->getRow("SHOW COLUMNS FROM nteam_academic_push_config LIKE 'default_schedule_mode'");
        if (!$checkDefaultCol) {
            $DB->exec("ALTER TABLE nteam_academic_push_config ADD COLUMN default_schedule_mode VARCHAR(50) NULL DEFAULT NULL COMMENT '系统默认课程表类型(kbjcmsid)'");
        }
        
        if ($exists) {
            $sql = "UPDATE nteam_academic_push_config SET 
                schedule_reminder_enabled = '$scheduleReminderEnabled',
                grade_notify_enabled = '$gradeNotifyEnabled',
                wechat_push_enabled = '$wechatPushEnabled',
                email_push_enabled = '$emailPushEnabled',
                schedule_reminder_time = '$scheduleReminderTime',
                quiet_hours_start = '$quietHoursStart',
                quiet_hours_end = '$quietHoursEnd',
                grade_semester = '$gradeSemester', default_schedule_mode = '$defaultScheduleMode'";
            
            if ($hasTemplateColumns) {
                $sql .= ", schedule_template_id = " . ($scheduleTemplateId ?: 'NULL') . ", grade_template_id = " . ($gradeTemplateId ?: 'NULL');
            }
            
            $sql .= ", updated_at = NOW() WHERE id = 1";
        } else {
            if ($hasTemplateColumns) {
                $sql = "INSERT INTO nteam_academic_push_config 
                    (id, schedule_reminder_enabled, grade_notify_enabled, wechat_push_enabled, email_push_enabled, schedule_reminder_time, quiet_hours_start, quiet_hours_end, grade_semester, schedule_template_id, grade_template_id, default_schedule_mode)
                    VALUES (1, '$scheduleReminderEnabled', '$gradeNotifyEnabled', '$wechatPushEnabled', '$emailPushEnabled', '$scheduleReminderTime', '$quietHoursStart', '$quietHoursEnd', '$gradeSemester', " . ($scheduleTemplateId ?: 'NULL') . ", " . ($gradeTemplateId ?: 'NULL') . ", '$defaultScheduleMode')";
            } else {
                $sql = "INSERT INTO nteam_academic_push_config 
                    (id, schedule_reminder_enabled, grade_notify_enabled, wechat_push_enabled, email_push_enabled, schedule_reminder_time, quiet_hours_start, quiet_hours_end, grade_semester, default_schedule_mode)
                    VALUES (1, '$scheduleReminderEnabled', '$gradeNotifyEnabled', '$wechatPushEnabled', '$emailPushEnabled', '$scheduleReminderTime', '$quietHoursStart', '$quietHoursEnd', '$gradeSemester', '$defaultScheduleMode')";
            }
        }
        
        if ($DB->exec($sql) !== false) {
            $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','修改了学术推送配置', NOW(), '".$ip."', '')");
            exit('{"code":0,"msg":"保存成功"}');
        } else {
            exit('{"code":-1,"msg":"保存失败"}');
        }
    break;
    
    case 'add_subscription_count':
        if (!$adminData) {
            exit('{"code":-1,"msg":"请先登录"}');
        }
        
        $id = intval($_POST['id']);
        $count = intval($_POST['count']);
        $remark = isset($_POST['remark']) ? addslashes($_POST['remark']) : '';
        
        if ($id <= 0 || $count <= 0) {
            exit('{"code":-1,"msg":"参数错误"}');
        }
        
        $sub = $DB->getRow("SELECT * FROM nteam_subscriptions WHERE id = $id");
        if (!$sub) {
            exit('{"code":-1,"msg":"订阅记录不存在"}');
        }
        
        $newCount = intval($sub['subscribe_count']) + $count;
        $DB->exec("UPDATE nteam_subscriptions SET subscribe_count = $newCount WHERE id = $id");
        
        $DB->exec("INSERT INTO nteam_subscription_logs (user_id, template_id, action, count_change) VALUES ('{$sub['user_id']}', '{$sub['template_id']}', 'admin_add', $count)");
        
        $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','为用户ID {$sub['user_id']} 增加订阅次数 $count 次', NOW(), '".$ip."', '')");
        
        exit('{"code":0,"msg":"增加成功，当前剩余次数：' . $newCount . '"}');
    break;
    
    case 'reset_subscription_count':
        if (!$adminData) {
            exit('{"code":-1,"msg":"请先登录"}');
        }
        
        $id = intval($_POST['id']);
        
        if ($id <= 0) {
            exit('{"code":-1,"msg":"参数错误"}');
        }
        
        $sub = $DB->getRow("SELECT * FROM nteam_subscriptions WHERE id = $id");
        if (!$sub) {
            exit('{"code":-1,"msg":"订阅记录不存在"}');
        }
        
        $DB->exec("UPDATE nteam_subscriptions SET subscribe_count = 0 WHERE id = $id");
        
        $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','重置用户ID {$sub['user_id']} 的订阅次数', NOW(), '".$ip."', '')");
        
        exit('{"code":0,"msg":"重置成功"}');
    break;
    
    case 'get_subscription_logs':
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $pageSize = 20;
        $offset = ($page - 1) * $pageSize;
        
        $logs = $DB->getAll("SELECT l.id, l.user_id, l.template_id, l.action, l.count_change, l.created_at, 
            u.username, u.nickname, t.name as template_name
            FROM nteam_subscription_logs l 
            LEFT JOIN nteam_users u ON l.user_id = u.id 
            LEFT JOIN nteam_wx_templates t ON l.template_id = t.template_id 
            ORDER BY l.id DESC 
            LIMIT $offset, $pageSize");
        
        $total = $DB->getRow("SELECT COUNT(*) as count FROM nteam_subscription_logs");
        
        $actionNames = [
            'subscribe' => '用户订阅',
            'send' => '发送消息',
            'admin_add' => '管理员增加'
        ];
        
        $result = [];
        foreach ($logs as $log) {
            $result[] = [
                'id' => $log['id'],
                'user_id' => $log['user_id'],
                'username' => $log['username'] ?: '未知',
                'nickname' => $log['nickname'] ?: '',
                'template_id' => $log['template_id'],
                'template_name' => $log['template_name'] ?: $log['template_id'],
                'action' => $log['action'],
                'action_name' => isset($actionNames[$log['action']]) ? $actionNames[$log['action']] : $log['action'],
                'count_change' => $log['count_change'],
                'created_at' => $log['created_at']
            ];
        }
        
        exit(json_encode(['code' => 0, 'data' => $result, 'total' => intval($total['count'])]));
    break;
    
    case 'clear_push_logs':
        if (!$adminData) {
            exit('{"code":-1,"msg":"请先登录"}');
        }
        if ($adminData['adminRank'] == 2) {
            exit('{"code":-1,"msg":"无权限操作"}');
        }
        
        $DB->exec("TRUNCATE TABLE nteam_academic_push_logs");
        
        $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','清空推送日志', NOW(), '".$ip."', '')");
        
        exit('{"code":0,"msg":"推送日志已清空"}');
    break;
    
    case 'clear_grade_records':
        if (!$adminData) {
            exit('{"code":-1,"msg":"请先登录"}');
        }
        if ($adminData['adminRank'] == 2) {
            exit('{"code":-1,"msg":"无权限操作"}');
        }
        
        $DB->exec("TRUNCATE TABLE nteam_grade_push_records");
        
        $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','清空成绩推送记录', NOW(), '".$ip."', '')");
        
        exit('{"code":0,"msg":"成绩推送记录已清空"}');
    break;
    
    case 'clear_power_push_logs':
        if (!$adminData) {
            exit('{"code":-1,"msg":"请先登录"}');
        }
        if ($adminData['adminRank'] == 2) {
            exit('{"code":-1,"msg":"无权限操作"}');
        }
        
        $DB->exec("TRUNCATE TABLE nteam_push_logs");
        
        $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','清空电费推送日志', NOW(), '".$ip."', '')");
        
        exit('{"code":0,"msg":"电费推送日志已清空"}');
    break;
    
    case 'save_oauth_config':
        if(!$isLogin) exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        
        $enable = intval($_POST['enable']);
        $appid = addslashes(trim($_POST['appid']));
        $appkey = addslashes(trim($_POST['appkey']));
        
        $rawChannels = isset($_POST['channels']) && is_array($_POST['channels']) ? $_POST['channels'] : [];
        $validChannels = [];
        foreach ($rawChannels as $c) {
            $c = daddslashes($c);
            if (isset(OAuthHelper::CHANNELS[$c])) {
                $validChannels[] = $c;
            }
        }
        $channelsJson = addslashes(json_encode(array_values($validChannels)));
        
        if ($enable == 1 && (empty($appid) || empty($appkey))) {
            exit('{"code":-1,"msg":"开启聚合登录需填写 AppID 和 AppKey"}');
        }
        
        $existing = $DB->getRow("SELECT * FROM nteam_oauth_config ORDER BY id DESC LIMIT 1");
        if ($existing) {
            $sql = "UPDATE nteam_oauth_config SET enable='$enable', appid='$appid', appkey='$appkey', channels='$channelsJson', updated_at=NOW() WHERE id='{$existing['id']}'";
        } else {
            $sql = "INSERT INTO nteam_oauth_config (enable, appid, appkey, channels, created_at, updated_at) VALUES ('$enable', '$appid', '$appkey', '$channelsJson', NOW(), NOW())";
        }
        
        if ($DB->exec($sql) !== false) {
            $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','修改了聚合登录配置', NOW(), '".$ip."', '')");
            exit('{"code":0,"msg":"保存成功"}');
        } else {
            exit('{"code":-1,"msg":"保存失败['.$DB->errorInfo()[2].'"}');
        }
    break;

    case 'delete_oauth_binding':
        if(!$isLogin) exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}

        $id = intval($_POST['id']);
        if ($id <= 0) {
            exit('{"code":-1,"msg":"参数错误"}');
        }
        $existing = $DB->getRow("SELECT * FROM nteam_user_oauth WHERE id='$id'");
        if (!$existing) {
            exit('{"code":-1,"msg":"绑定记录不存在"}');
        }
        if ($DB->exec("DELETE FROM nteam_user_oauth WHERE id='$id'") !== false) {
            $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','解绑了第三方账号绑定(ID:".$id.")', NOW(), '".$ip."', '')");
            exit('{"code":0,"msg":"解绑成功"}');
        } else {
            exit('{"code":-1,"msg":"解绑失败['.$DB->error().']"}');
        }
    break;

    case 'clear_invalid_oauth':
        if(!$isLogin) exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}

        // 删除关联用户已不存在的第三方绑定（脏数据）
        $deleted = $DB->exec("DELETE o FROM nteam_user_oauth o LEFT JOIN nteam_users u ON o.user_id = u.id WHERE u.id IS NULL");
        if ($deleted === false) {
            exit('{"code":-1,"msg":"清除失败['.$DB->error().']"}');
        }
        $deleted = intval($deleted);
        $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','清除了".$deleted."条无效的第三方绑定', NOW(), '".$ip."', '')");
        exit('{"code":0,"msg":"清除成功，共删除 '.$deleted.' 条无效绑定"}');
    break;

    case 'save_cancel_config':
        if(!$isLogin) exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}

        $enable = intval($_POST['enable']) === 1 ? 1 : 0;
        $verifyMode = isset($_POST['verify_mode']) ? $_POST['verify_mode'] : 'password';
        if (!in_array($verifyMode, ['password', 'email', 'password_email'], true)) {
            $verifyMode = 'password';
        }
        $coolingEnable = intval($_POST['cooling_enable']) === 1 ? 1 : 0;
        $coolingDays = intval($_POST['cooling_days']);
        if ($coolingDays < 1 || $coolingDays > 90) {
            $coolingDays = 10;
        }
        $mailSubmit = intval($_POST['mail_submit']) === 1 ? 1 : 0;
        $mailDeleted = intval($_POST['mail_deleted']) === 1 ? 1 : 0;
        $mailRevoked = intval($_POST['mail_revoked']) === 1 ? 1 : 0;
        // 说明：《用户注销服务协议》已迁移到「协议管理」（nteam_agreements.akey='cancel'），此处不再保存

        saveSetting('Cancel_Enable', $enable);
        saveSetting('Cancel_Verify_Mode', $verifyMode);
        saveSetting('Cancel_Cooling_Enable', $coolingEnable);
        saveSetting('Cancel_Cooling_Days', $coolingDays);
        saveSetting('Cancel_Mail_Submit', $mailSubmit);
        saveSetting('Cancel_Mail_Deleted', $mailDeleted);
        saveSetting('Cancel_Mail_Revoked', $mailRevoked);

        $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','修改了注销管理配置', NOW(), '".$ip."', '')");
        exit('{"code":0,"msg":"保存成功"}');
    break;

    case 'delete_cancel_record':
        if(!$isLogin) exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}

        $id = intval($_POST['id']);
        if ($id <= 0) exit('{"code":-1,"msg":"参数错误"}');
        $rec = $DB->getRow("SELECT * FROM nteam_account_deletions WHERE id='$id'");
        if (!$rec) exit('{"code":-1,"msg":"记录不存在"}');
        if ($rec['status'] === 'pending') exit('{"code":-1,"msg":"该记录处于冷静期内，不可删除"}');
        if ($DB->exec("DELETE FROM nteam_account_deletions WHERE id='$id'") === false) {
            exit('{"code":-1,"msg":"删除失败：'.$DB->error().'"}');
        }
        $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','删除了注销记录(ID:".$id.")', NOW(), '".$ip."', '')");
        exit('{"code":0,"msg":"删除成功"}');
    break;

    case 'clear_cancel_records':
        if(!$isLogin) exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}

        // 保留冷静期内（pending）的记录，仅清空其余记录（含其日志）
        $deleted = $DB->exec("DELETE FROM nteam_account_deletions WHERE status <> 'pending'");
        if ($deleted === false) {
            exit('{"code":-1,"msg":"清空失败：'.$DB->error().'"}');
        }
        $deleted = intval($deleted);
        $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','清空了".$deleted."条注销记录（保留冷静期内）', NOW(), '".$ip."', '')");
        exit('{"code":0,"msg":"清空成功，共删除 '.$deleted.' 条（冷静期内记录已保留）"}');
    break;

    // ===== 首页配置（官网：网站地址 / 联系方式 / 下载方式 / 常见问题 / 联系我们留言） =====
    case 'home_config_save':
        if(!$isLogin) exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        {
            $fields = array('site_url','contact_email','contact_phone','contact_qq','contact_address',
                            'contact_worktime','contact_desc','download_intro','faq_intro');
            $sets = array();
            foreach ($fields as $f) {
                $v = isset($_POST[$f]) ? addslashes(trim($_POST[$f])) : '';
                $sets[] = "`{$f}`='{$v}'";
            }
            $sets[] = "`contact_form_open`='"    . (isset($_POST['contact_form_open'])    ? intval($_POST['contact_form_open'])    : 0) . "'";
            $sets[] = "`contact_form_captcha`='" . (isset($_POST['contact_form_captcha']) ? intval($_POST['contact_form_captcha']) : 0) . "'";

            $row = $DB->getRow("SELECT id FROM nteam_home_config WHERE id=1");
            if (!$row) { $DB->exec("INSERT INTO nteam_home_config (`id`) VALUES (1)"); }

            if ($DB->exec("UPDATE nteam_home_config SET " . implode(',', $sets) . " WHERE `id`=1") !== false) {
                exit('{"code":0,"msg":"首页配置保存成功！"}');
            }
            exit('{"code":-1,"msg":"保存失败['.$DB->error().']"}');
        }
    break;

    case 'home_download_save':
        if(!$isLogin) exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        {
            $id    = intval(isset($_POST['id']) ? $_POST['id'] : 0);
            $title = addslashes(trim(isset($_POST['title']) ? $_POST['title'] : ''));
            $url   = addslashes(trim(isset($_POST['url']) ? $_POST['url'] : ''));
            $icon  = addslashes(trim(isset($_POST['icon']) ? $_POST['icon'] : ''));
            $desc  = addslashes(trim(isset($_POST['description']) ? $_POST['description'] : ''));
            $sort  = intval(isset($_POST['sort']) ? $_POST['sort'] : 0);
            $st    = intval(isset($_POST['status']) ? $_POST['status'] : 0);
            if ($title === '' || $url === '') exit('{"code":-1,"msg":"名称与下载地址不能为空"}');

            if ($id > 0) {
                $ok = $DB->exec("UPDATE nteam_home_download SET `title`='$title',`url`='$url',`icon`='$icon',`description`='$desc',`sort`='$sort',`status`='$st' WHERE `id`='$id'");
            } else {
                $ok = $DB->exec("INSERT INTO nteam_home_download (`title`,`url`,`icon`,`description`,`sort`,`status`) VALUES ('$title','$url','$icon','$desc','$sort','$st')");
            }
            if ($ok !== false) exit('{"code":0,"msg":"保存成功！"}');
            exit('{"code":-1,"msg":"保存失败['.$DB->error().']"}');
        }
    break;

    case 'home_download_del':
        if(!$isLogin) exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        {
            $id = intval(isset($_POST['id']) ? $_POST['id'] : 0);
            if ($id <= 0) exit('{"code":-1,"msg":"参数错误"}');
            if ($DB->exec("DELETE FROM nteam_home_download WHERE `id`='$id'") !== false) exit('{"code":0,"msg":"删除成功！"}');
            exit('{"code":-1,"msg":"删除失败['.$DB->error().']"}');
        }
    break;

    case 'home_faq_save':
        if(!$isLogin) exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        {
            $id   = intval(isset($_POST['id']) ? $_POST['id'] : 0);
            $cat  = addslashes(trim(isset($_POST['category']) ? $_POST['category'] : '常见问题'));
            $q    = addslashes(trim(isset($_POST['question']) ? $_POST['question'] : ''));
            $a    = addslashes(isset($_POST['answer']) ? $_POST['answer'] : '');
            $sort = intval(isset($_POST['sort']) ? $_POST['sort'] : 0);
            $st   = intval(isset($_POST['status']) ? $_POST['status'] : 0);
            if ($q === '') exit('{"code":-1,"msg":"问题不能为空"}');
            if ($cat === '') $cat = '常见问题';

            if ($id > 0) {
                $ok = $DB->exec("UPDATE nteam_home_faq SET `category`='$cat',`question`='$q',`answer`='$a',`sort`='$sort',`status`='$st' WHERE `id`='$id'");
            } else {
                $ok = $DB->exec("INSERT INTO nteam_home_faq (`category`,`question`,`answer`,`sort`,`status`) VALUES ('$cat','$q','$a','$sort','$st')");
            }
            if ($ok !== false) exit('{"code":0,"msg":"保存成功！"}');
            exit('{"code":-1,"msg":"保存失败['.$DB->error().']"}');
        }
    break;

    case 'home_faq_del':
        if(!$isLogin) exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        {
            $id = intval(isset($_POST['id']) ? $_POST['id'] : 0);
            if ($id <= 0) exit('{"code":-1,"msg":"参数错误"}');
            if ($DB->exec("DELETE FROM nteam_home_faq WHERE `id`='$id'") !== false) exit('{"code":0,"msg":"删除成功！"}');
            exit('{"code":-1,"msg":"删除失败['.$DB->error().']"}');
        }
    break;

    case 'contact_msg_read':
        if(!$isLogin) exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        {
            $id = intval(isset($_POST['id']) ? $_POST['id'] : 0);
            if ($id <= 0) exit('{"code":-1,"msg":"参数错误"}');
            if ($DB->exec("UPDATE nteam_contact_msgs SET `status`=1 WHERE `id`='$id'") !== false) exit('{"code":0,"msg":"已标记为已读！"}');
            exit('{"code":-1,"msg":"操作失败['.$DB->error().']"}');
        }
    break;

    case 'contact_msg_del':
        if(!$isLogin) exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        {
            $id = intval(isset($_POST['id']) ? $_POST['id'] : 0);
            if ($id <= 0) exit('{"code":-1,"msg":"参数错误"}');
            if ($DB->exec("DELETE FROM nteam_contact_msgs WHERE `id`='$id'") !== false) exit('{"code":0,"msg":"删除成功！"}');
            exit('{"code":-1,"msg":"删除失败['.$DB->error().']"}');
        }
    break;

    case 'ai_config_save':
        if(!$isLogin) exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        {
            $fields = array('AI_Token', 'AI_BaseURL', 'AI_Model');
            foreach ($fields as $f) {
                saveSetting($f, isset($_POST[$f]) ? trim($_POST[$f]) : '');
            }
            exit('{"code":0,"msg":"AI 配置保存成功！"}');
        }
    break;

    // ===== 课程时间（作息）配置：按「课程模式」维护各节次起止时间 =====
    case 'save_schedule_mode':
        if(!$isLogin) exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        {
            $res = scheduleTimeSaveMode($DB, array(
                'id'         => isset($_POST['id']) ? intval($_POST['id']) : 0,
                'mode_name'  => isset($_POST['mode_name']) ? $_POST['mode_name'] : '',
                'mode_id'    => isset($_POST['mode_id']) ? $_POST['mode_id'] : '',
                'is_default' => isset($_POST['is_default']) ? intval($_POST['is_default']) : 0,
                'sort_order' => isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0,
                'periods'    => isset($_POST['periods']) ? $_POST['periods'] : '',
            ));
            if ($res['code'] === 0) {
                $logName = addslashes(trim(isset($_POST['mode_name']) ? $_POST['mode_name'] : ''));
                $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','保存了课程时间配置（{$logName}）', NOW(), '".$ip."', '')");
            }
            exit(json_encode($res, JSON_UNESCAPED_UNICODE));
        }
    break;

    case 'delete_schedule_mode':
        if(!$isLogin) exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        {
            $id = intval(isset($_POST['id']) ? $_POST['id'] : 0);
            if ($id <= 0) exit('{"code":-1,"msg":"参数错误"}');
            $res = scheduleTimeDeleteMode($DB, $id);
            if ($res['code'] === 0) {
                $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','删除了课程时间配置(ID:{$id})', NOW(), '".$ip."', '')");
            }
            exit(json_encode($res, JSON_UNESCAPED_UNICODE));
        }
    break;

    case 'set_default_schedule_mode':
        if(!$isLogin) exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        {
            $id = intval(isset($_POST['id']) ? $_POST['id'] : 0);
            if ($id <= 0) exit('{"code":-1,"msg":"参数错误"}');
            $res = scheduleTimeSetDefaultMode($DB, $id);
            if ($res['code'] === 0) {
                $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','设置了默认课程时间配置(ID:{$id})', NOW(), '".$ip."', '')");
            }
            exit(json_encode($res, JSON_UNESCAPED_UNICODE));
        }
    break;

    // ===== 轮播图配置：小程序/uniapp 首页轮播图管理 =====
    case 'upload_banner_image':
        if(!$isLogin)exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}

        if (!isset($_FILES['file']) || $_FILES['file']['error'] != 0) {
            exit('{"code":-1,"msg":"请选择要上传的图片"}');
        }

        $file = $_FILES['file'];

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            exit('{"code":-1,"msg":"只允许上传图片文件"}');
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            exit('{"code":-1,"msg":"图片大小不能超过5MB"}');
        }

        $uploadDir = '../uploads/banners/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = date('YmdHis') . '_' . uniqid() . '.' . $ext;
        $filepath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $url = '/uploads/banners/' . $filename;
            $displayUrl = $siteBasePath . $url;
            exit('{"code":0,"msg":"上传成功","data":{"url":"' . $url . '","displayUrl":"' . $displayUrl . '"}}');
        } else {
            exit('{"code":-1,"msg":"上传失败"}');
        }
    break;

    case 'banner_save':
        if(!$isLogin)exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        {
            $id    = intval(isset($_POST['id']) ? $_POST['id'] : 0);
            $title = addslashes(trim(isset($_POST['title']) ? $_POST['title'] : ''));
            $image = addslashes(trim(isset($_POST['image']) ? $_POST['image'] : ''));
            $url    = trim(isset($_POST['url']) ? $_POST['url'] : '');
            $urlWx  = trim(isset($_POST['url_wx']) ? $_POST['url_wx'] : '');
            $urlApp = trim(isset($_POST['url_app']) ? $_POST['url_app'] : '');
            $urlWeb = trim(isset($_POST['url_web']) ? $_POST['url_web'] : '');

            // 小程序 / Uniapp：站内页面以 / 开头，或 http(s)://
            $pageUrlCheck = array(
                '通用'   => $url,
                '小程序' => $urlWx,
                'Uniapp' => $urlApp,
            );
            foreach ($pageUrlCheck as $label => $u) {
                if ($u !== '' && !preg_match('#^(https?://|/)#i', $u)) {
                    exit('{"code":-1,"msg":"' . $label . '跳转链接需以 / 或 http(s):// 开头"}');
                }
            }
            // 网页端：允许站内相对路径（如 grade.php），仅拦截脚本协议
            if ($urlWeb !== '' && preg_match('#^(javascript|data|vbscript):#i', $urlWeb)) {
                exit('{"code":-1,"msg":"网页端跳转链接不合法"}');
            }

            $url    = addslashes($url);
            $urlWx  = addslashes($urlWx);
            $urlApp = addslashes($urlApp);
            $urlWeb = addslashes($urlWeb);
            $sort  = intval(isset($_POST['sort']) ? $_POST['sort'] : 0);
            $status = intval(isset($_POST['status']) ? $_POST['status'] : 1) ? 1 : 0;

            if ($image === '') {
                exit('{"code":-1,"msg":"请上传轮播图图片"}');
            }

            if ($id > 0) {
                $sql = "update nteam_banners set `title`='{$title}',`image`='{$image}',`url`='{$url}',`url_wx`='{$urlWx}',`url_app`='{$urlApp}',`url_web`='{$urlWeb}',`sort`={$sort},`status`={$status} where `id`={$id}";
                $ok = $DB->exec($sql) !== false;
                $logType = '修改了轮播图(ID:' . $id . ')';
            } else {
                $sql = "insert into nteam_banners (`title`,`image`,`url`,`url_wx`,`url_app`,`url_web`,`sort`,`status`) values ('{$title}','{$image}','{$url}','{$urlWx}','{$urlApp}','{$urlWeb}',{$sort},{$status})";
                $ok = $DB->exec($sql) !== false;
                $logType = '新增了轮播图';
            }

            if ($ok) {
                $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','{$logType}', NOW(), '".$ip."', '')");
                exit('{"code":0,"msg":"保存成功！"}');
            }
            exit('{"code":-1,"msg":"保存失败"}');
        }
    break;

    case 'banner_del':
        if(!$isLogin)exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        {
            $id = intval(isset($_POST['id']) ? $_POST['id'] : 0);
            if ($id <= 0) exit('{"code":-1,"msg":"参数错误"}');
            if ($DB->exec("delete from nteam_banners where `id`={$id}") !== false) {
                $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','删除了轮播图(ID:{$id})', NOW(), '".$ip."', '')");
                exit('{"code":0,"msg":"删除成功！"}');
            }
            exit('{"code":-1,"msg":"删除失败"}');
        }
    break;

    case 'app_version_save':
        if(!$isLogin)exit('{"code":0,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        $id = intval($_POST['id'] ?? 0);
        $version = addslashes(trim($_POST['version'] ?? ''));
        $version_code = intval($_POST['version_code'] ?? 0);
        $platform = addslashes($_POST['platform'] ?? 'all');
        $force_update = isset($_POST['force_update']) ? 1 : 0;
        $download_url = addslashes(trim($_POST['download_url'] ?? ''));
        $changelog = addslashes(trim($_POST['changelog'] ?? ''));
        $status = intval($_POST['status'] ?? 1);

        if ($version == '' || $version_code <= 0 || $download_url == '') {
            exit('{"code":-1,"msg":"请填写版本号、版本数字和下载地址"}');
        }
        if (!in_array($platform, array('all','android','ios','harmony'))) {
            $platform = 'all';
        }

        if ($id > 0) {
            $sql = "UPDATE nteam_app_versions SET `version`='$version', `version_code`='$version_code', `platform`='$platform', `force_update`='$force_update', `download_url`='$download_url', `changelog`='$changelog', `status`='$status' WHERE `id`='$id'";
            if ($DB->exec($sql) === false) exit('{"code":-1,"msg":"修改失败['.$DB->error().']"}');
            $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','修改了App版本".$version."', NOW(), '".$ip."', '')");
            exit('{"code":0,"msg":"修改成功！"}');
        } else {
            $sql = "INSERT INTO nteam_app_versions (`version`,`version_code`,`platform`,`force_update`,`download_url`,`changelog`,`status`) VALUES ('$version','$version_code','$platform','$force_update','$download_url','$changelog','$status')";
            if ($DB->exec($sql) === false) exit('{"code":-1,"msg":"添加失败['.$DB->error().']"}');
            $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','新增了App版本".$version."', NOW(), '".$ip."', '')");
            exit('{"code":0,"msg":"添加成功！"}');
        }
    break;

    case 'app_version_del':
        if(!$isLogin)exit('{"code":0,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) exit('{"code":-1,"msg":"参数错误"}');
        if ($DB->exec("DELETE FROM nteam_app_versions WHERE `id`='$id'") === false) {
            exit('{"code":-1,"msg":"删除失败['.$DB->error().']"}');
        }
        $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','删除了App版本(ID:{$id})', NOW(), '".$ip."', '')");
        exit('{"code":0,"msg":"删除成功！"}');
    break;

    // 版本管理：测试蓝奏云链接解析（返回直链/文件名/大小，便于管理员核对密码是否正确）
    case 'app_version_test_lanzou':
        if(!$isLogin)exit('{"code":0,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        $raw = trim($_POST['url'] ?? '');
        if ($raw === '') exit('{"code":-1,"msg":"请先填写下载地址"}');
        $split = lanzouSplitPassword($raw);
        if (!lanzouIsUrl($split['url'])) {
            exit('{"code":-1,"msg":"不是蓝奏云链接，无需解析（普通直链客户端会直接下载）"}');
        }
        $resolved = lanzouResolve($split['url'], $split['pwd']);
        if (!$resolved) {
            exit(json_encode(array(
                'code' => -1,
                'msg' => $split['pwd'] === ''
                    ? '解析失败：若该文件设置了分享密码，请在链接末尾追加 &pwd=密码 后重试'
                    : '解析失败：请检查链接是否有效、分享密码是否正确'
            ), JSON_UNESCAPED_UNICODE));
        }
        exit(json_encode(array(
            'code' => 0,
            'msg'  => '解析成功',
            'data' => array(
                'direct_url' => $resolved['directUrl'],
                'name'       => $resolved['name'],
                'filesize'   => $resolved['filesize'],
                'pwd'        => $split['pwd']
            )
        ), JSON_UNESCAPED_UNICODE));
    break;

    // ===== 协议管理：新增 / 修改协议 =====
    case 'agreement_save':
        if(!$isLogin) exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        $agRes = agreementSave($DB, array(
            'id'         => intval($_POST['id'] ?? 0),
            'akey'       => isset($_POST['akey']) ? $_POST['akey'] : '',
            'title'      => isset($_POST['title']) ? $_POST['title'] : '',
            'content'    => isset($_POST['content']) ? $_POST['content'] : '',
            'sort_order' => intval($_POST['sort_order'] ?? 0),
            'status'     => intval($_POST['status'] ?? 1)
        ));
        if ($agRes['code'] !== 0) {
            exit(json_encode(array('code' => -1, 'msg' => $agRes['msg']), JSON_UNESCAPED_UNICODE));
        }
        $actName = intval($_POST['id'] ?? 0) > 0 ? '修改了协议' : '新增了协议';
        $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','{$actName}(ID:" . $agRes['id'] . ")', NOW(), '" . $ip . "', '')");
        exit(json_encode(array('code' => 0, 'msg' => $agRes['msg'], 'id' => $agRes['id']), JSON_UNESCAPED_UNICODE));
    break;

    // ===== 协议管理：删除协议 =====
    case 'agreement_del':
        if(!$isLogin) exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        $agId = intval($_POST['id'] ?? 0);
        if ($agId <= 0) exit('{"code":-1,"msg":"参数错误"}');
        $agRes = agreementDelete($DB, $agId);
        if ($agRes['code'] !== 0) {
            exit(json_encode(array('code' => -1, 'msg' => $agRes['msg']), JSON_UNESCAPED_UNICODE));
        }
        $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','删除了协议(ID:{$agId})', NOW(), '" . $ip . "', '')");
        exit(json_encode(array('code' => 0, 'msg' => $agRes['msg']), JSON_UNESCAPED_UNICODE));
    break;

    // ===== 协议管理：启用 / 停用协议 =====
    case 'agreement_status':
        if(!$isLogin) exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        $agId = intval($_POST['id'] ?? 0);
        $agStatus = intval($_POST['status'] ?? 1);
        if ($agId <= 0) exit('{"code":-1,"msg":"参数错误"}');
        $agRes = agreementSetStatus($DB, $agId, $agStatus);
        if ($agRes['code'] !== 0) {
            exit(json_encode(array('code' => -1, 'msg' => $agRes['msg']), JSON_UNESCAPED_UNICODE));
        }
        exit(json_encode(array('code' => 0, 'msg' => $agRes['msg']), JSON_UNESCAPED_UNICODE));
    break;

    // ===== 开屏广告：图片上传 =====
    case 'upload_splash_image':
        if(!$isLogin)exit('{"code":-1,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}

        if (!isset($_FILES['file']) || $_FILES['file']['error'] != 0) {
            exit('{"code":-1,"msg":"请选择图片"}');
        }
        $file = $_FILES['file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp'))) {
            exit('{"code":-1,"msg":"仅支持 jpg / png / gif / webp 图片"}');
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            exit('{"code":-1,"msg":"图片大小不能超过5MB"}');
        }

        $uploadDir = '../uploads/splash/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $filename = date('YmdHis') . '_' . uniqid() . '.' . $ext;
        $filepath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $url = '/uploads/splash/' . $filename;
            $displayUrl = $siteBasePath . $url;
            exit('{"code":0,"msg":"上传成功","data":{"url":"' . $url . '","displayUrl":"' . $displayUrl . '"}}');
        } else {
            exit('{"code":-1,"msg":"上传失败"}');
        }
    break;

    // ===== 开屏广告：保存 =====
    case 'splash_save':
        if(!$isLogin)exit('{"code":0,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        {
            $id       = intval(isset($_POST['id']) ? $_POST['id'] : 0);
            $title    = addslashes(trim(isset($_POST['title']) ? $_POST['title'] : ''));
            $image    = addslashes(trim(isset($_POST['image']) ? $_POST['image'] : ''));
            $url      = trim(isset($_POST['url']) ? $_POST['url'] : '');
            $urlWx    = trim(isset($_POST['url_wx']) ? $_POST['url_wx'] : '');
            $urlApp   = trim(isset($_POST['url_app']) ? $_POST['url_app'] : '');
            $duration = intval(isset($_POST['duration']) ? $_POST['duration'] : 3);
            $sort     = intval(isset($_POST['sort']) ? $_POST['sort'] : 0);
            $status   = intval(isset($_POST['status']) ? $_POST['status'] : 1) ? 1 : 0;

            if ($image === '') {
                exit('{"code":-1,"msg":"请上传开屏广告图片"}');
            }
            if ($duration < 1)  $duration = 3;
            if ($duration > 15) $duration = 15;

            // 站内页面以 / 开头，或 http(s)://
            $urlCheck = array('通用' => $url, '小程序' => $urlWx, 'Uniapp' => $urlApp);
            foreach ($urlCheck as $label => $u) {
                if ($u !== '' && !preg_match('#^(https?://|/)#i', $u)) {
                    exit('{"code":-1,"msg":"' . $label . '跳转链接需以 / 或 http(s):// 开头"}');
                }
            }

            $url    = addslashes($url);
            $urlWx  = addslashes($urlWx);
            $urlApp = addslashes($urlApp);

            if ($id > 0) {
                $sql = "UPDATE nteam_splash_ads SET `title`='$title', `image`='$image', `url`='$url', `url_wx`='$urlWx', `url_app`='$urlApp', `duration`='$duration', `sort`='$sort', `status`='$status' WHERE `id`='$id'";
                if ($DB->exec($sql) === false) exit('{"code":-1,"msg":"修改失败['.$DB->error().']"}');
                $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','修改了开屏广告(ID:{$id})', NOW(), '".$ip."', '')");
                exit('{"code":0,"msg":"修改成功！"}');
            } else {
                $sql = "INSERT INTO nteam_splash_ads (`title`,`image`,`url`,`url_wx`,`url_app`,`duration`,`sort`,`status`) VALUES ('$title','$image','$url','$urlWx','$urlApp','$duration','$sort','$status')";
                if ($DB->exec($sql) === false) exit('{"code":-1,"msg":"添加失败['.$DB->error().']"}');
                $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','新增了开屏广告', NOW(), '".$ip."', '')");
                exit('{"code":0,"msg":"添加成功！"}');
            }
        }
    break;

    // ===== 开屏广告：删除 =====
    case 'splash_del':
        if(!$isLogin)exit('{"code":0,"msg":"未登录"}');
        if($adminData['adminRank']!=1){exit('{"code":-1,"msg":"您的账号没有权限使用此功能！"}');}
        {
            $id = intval(isset($_POST['id']) ? $_POST['id'] : 0);
            if ($id <= 0) exit('{"code":-1,"msg":"参数错误"}');
            if ($DB->exec("DELETE FROM nteam_splash_ads WHERE `id`='{$id}'") === false) {
                exit('{"code":-1,"msg":"删除失败['.$DB->error().']"}');
            }
            $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('" . $_SESSION['adminUser'] . "','删除了开屏广告(ID:{$id})', NOW(), '".$ip."', '')");
            exit('{"code":0,"msg":"删除成功！"}');
        }
    break;

    default:
        exit('{"code":-4,"msg":"No Act"}');
    break;
}

function updateDormitoryBinding($DB, $data) {
    $id = intval($data['id']);
    $schoolArea = addslashes($data['school_area']);
    $building = addslashes($data['building']);
    $room = addslashes($data['room']);
    $alias = addslashes($data['alias']);
    $isDefault = isset($data['is_default']) && $data['is_default'] == '1' ? 1 : 0;
    
    if ($id <= 0) {
        return ['code' => -1, 'msg' => '参数错误'];
    }
    
    $existing = $DB->getRow("SELECT * FROM nteam_dormitory_bindings WHERE id='$id'");
    if (!$existing) {
        return ['code' => -1, 'msg' => '记录不存在'];
    }
    
    if ($isDefault == 1) {
        $userId = $existing['user_id'];
        $DB->exec("UPDATE nteam_dormitory_bindings SET is_default=0 WHERE user_id='$userId'");
    }
    
    $sql = "UPDATE nteam_dormitory_bindings SET 
            school_area='$schoolArea', 
            building='$building', 
            room='$room', 
            alias='$alias', 
            is_default='$isDefault' 
            WHERE id='$id'";
    
    if ($DB->exec($sql) !== false) {
        return ['code' => 0, 'msg' => '修改成功'];
    } else {
        return ['code' => -1, 'msg' => '修改失败'];
    }
}

function deleteDormitoryBinding($DB, $id) {
    $id = intval($id);
    
    if ($id <= 0) {
        return ['code' => -1, 'msg' => '参数错误'];
    }
    
    $existing = $DB->getRow("SELECT * FROM nteam_dormitory_bindings WHERE id='$id'");
    if (!$existing) {
        return ['code' => -1, 'msg' => '记录不存在'];
    }
    
    if ($DB->exec("UPDATE nteam_dormitory_bindings SET status=0 WHERE id='$id'") !== false) {
        return ['code' => 0, 'msg' => '删除成功'];
    } else {
        return ['code' => -1, 'msg' => '删除失败'];
    }
}
