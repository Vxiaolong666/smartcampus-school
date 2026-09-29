<?php
function daddslashes($string, $force = 0, $strip = FALSE) {
	!defined('MAGIC_QUOTES_GPC') && define('MAGIC_QUOTES_GPC', function_exists('get_magic_quotes_gpc') ? get_magic_quotes_gpc() : false);
	if(!MAGIC_QUOTES_GPC || $force) {
		if(is_array($string)) {
			foreach($string as $key => $val) {
				$string[$key] = daddslashes($val, $force, $strip);
			}
		} else {
			$string = addslashes($strip ? stripslashes($string) : $string);
		}
	}
	return $string;
}
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
	$ckey_length = 4;
	$encrypt_key = defined('ENCRYPT_KEY') ? ENCRYPT_KEY : 'default_encrypt_key';
	$key = md5($key ? $key : $encrypt_key);
	$keya = md5(substr($key, 0, 16));
	$keyb = md5(substr($key, 16, 16));
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';
	$cryptkey = $keya.md5($keya.$keyc);
	$key_length = strlen($cryptkey);
	$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
	$string_length = strlen($string);
	$result = '';
	$box = range(0, 255);
	$rndkey = array();
	for($i = 0; $i <= 255; $i++) {
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}
	for($j = $i = 0; $i < 256; $i++) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}
	for($a = $j = $i = 0; $i < $string_length; $i++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}
	if($operation == 'DECODE') {
		if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return '';
		}
	} else {
		return $keyc.str_replace('=', '', base64_encode($result));
	}
}
function get_curl($url, $post=0, $referer=0, $cookie=0, $header=0, $ua=0, $nobaody=0)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	$httpheader[] = "Accept: */*";
	$httpheader[] = "Accept-Encoding: gzip,deflate,sdch";
	$httpheader[] = "Accept-Language: zh-CN,zh;q=0.8";
	$httpheader[] = "Connection: close";
	curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
	if ($post) {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	}
	if ($header) {
		curl_setopt($ch, CURLOPT_HEADER, true);
	}
	if ($cookie) {
		curl_setopt($ch, CURLOPT_COOKIE, $cookie);
	}
	if($referer){
		if($referer==1){
			curl_setopt($ch, CURLOPT_REFERER, 'http://m.qzone.com/infocenter?g_f=');
		}else{
			curl_setopt($ch, CURLOPT_REFERER, $referer);
		}
	}
	if ($ua) {
		curl_setopt($ch, CURLOPT_USERAGENT, $ua);
	}
	else {
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Linux; U; Android 4.0.4; es-mx; HTC_One_X Build/IMM76D) AppleWebKit/534.30 (KHTML, like Gecko) Version/4.0");
	}
	if ($nobaody) {
		curl_setopt($ch, CURLOPT_NOBODY, 1);
	}
	curl_setopt($ch, CURLOPT_ENCODING, "gzip");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$ret = curl_exec($ch);
	curl_close($ch);
	return $ret;
}
function random($length, $numeric = 0) {
	$seed = base_convert(md5(microtime().$_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
	$seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
	$hash = '';
	$max = strlen($seed) - 1;
	for($i = 0; $i < $length; $i++) {
		$hash .= $seed{mt_rand(0, $max)};
	}
	return $hash;
}
function get_admin_emails() {
	global $DB;
	$emails = conf('Mail_Admin_Emails');
	if (empty($emails)) {
		return [];
	}
	$emailArray = array_map('trim', explode(',', $emails));
	$validEmails = array_filter($emailArray, function($email) {
		return filter_var($email, FILTER_VALIDATE_EMAIL);
	});
	return array_values($validEmails);
}

function send_mail($to, $sub, $msg, $skipIntervalCheck = false) {
	global $DB;
	$Host = conf('Mail_Smtp');
	$Port = conf('Mail_Port');
	$Username = conf('Mail_Name');
	$Password = conf('Mail_Pwd');
	$From = conf('Mail_Name');
	$Nickname = conf('Name');
	$SMTPAuth = 1;
	$Secure = conf('Mail_Secure');
	if (empty($Secure)) {
		$Secure = 'ssl';
	}
	// 自动匹配加密协议
	if ($Secure == 'none' || $Secure == '') {
		if ($Port == 465) {
			$Secure = 'ssl';
		} elseif ($Port == 587) {
			$Secure = 'tls';
		}
	}
	// 基础配置校验
	if (!$Host || !$Port || !$Username || !$Password) {
		$currentTime = date('Y-m-d H:i:s');
		$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
		$detailErrorMsg = 'SMTP配置不完整，请检查地址、端口、账号、授权码';
		$DB->exec("INSERT INTO nteam_mail_log (to_email, subject, content, status, error_msg, ip, create_time) VALUES ('$to', '$sub', '" . mb_substr($msg, 0, 200) . "', 0, '$detailErrorMsg', '$ip', '$currentTime')");
		return 'SMTP配置不完整';
	}
	
	$sameEmailInterval = intval(conf('Mail_Same_Email_Interval'));
	if ($sameEmailInterval == 0) {
		$sameEmailInterval = 60;
	}
	
	$platformPerMinute = intval(conf('Mail_Platform_Per_Minute'));
	if ($platformPerMinute == 0) {
		$platformPerMinute = 10;
	}
	
	$currentTime = date('Y-m-d H:i:s');
	$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
	
	$checkTable = $DB->query("SHOW TABLES LIKE 'nteam_mail_log'")->fetch();
	if ($checkTable && !$skipIntervalCheck) {
		$lastSendTime = $DB->query("SELECT create_time FROM nteam_mail_log WHERE to_email = '$to' AND status = 1 ORDER BY create_time DESC LIMIT 1")->fetch();
		if ($lastSendTime && strtotime($currentTime) - strtotime($lastSendTime['create_time']) < $sameEmailInterval) {
			$waitTime = $sameEmailInterval - (strtotime($currentTime) - strtotime($lastSendTime['create_time']));
			$detailErrorMsg = "同一邮箱发送间隔未满，还需等待 {$waitTime} 秒";
			$DB->exec("INSERT INTO nteam_mail_log (to_email, subject, content, status, error_msg, ip, create_time) VALUES ('$to', '$sub', '" . mb_substr($msg, 0, 200) . "', 0, '$detailErrorMsg', '$ip', '$currentTime')");
			return "该邮箱发送频繁，请稍后重试";
		}
		
		$oneMinuteAgo = date('Y-m-d H:i:s', strtotime($currentTime) - 60);
		$recentCount = $DB->query("SELECT COUNT(*) as cnt FROM nteam_mail_log WHERE create_time > '$oneMinuteAgo' AND status = 1")->fetch();
		if ($recentCount['cnt'] >= $platformPerMinute) {
			$detailErrorMsg = "平台1分钟内发送数量已达上限（当前：{$recentCount['cnt']}，限制：{$platformPerMinute}）";
			$DB->exec("INSERT INTO nteam_mail_log (to_email, subject, content, status, error_msg, ip, create_time) VALUES ('$to', '$sub', '" . mb_substr($msg, 0, 200) . "', 0, '$detailErrorMsg', '$ip', '$currentTime')");
			return "平台邮箱发送服务繁忙，请稍后重试";
		}
	}
	
	require_once ROOT . 'Common/mail/PHPMailer-master/src/Exception.php';
	require_once ROOT . 'Common/mail/PHPMailer-master/src/PHPMailer.php';
	require_once ROOT . 'Common/mail/PHPMailer-master/src/SMTP.php';
	
	$mail = new \PHPMailer\PHPMailer\PHPMailer(true);
	
	try {
		$mail->CharSet = 'UTF-8';
		$mail->Encoding = 'base64';
		$mail->isSMTP();
		$mail->Host = $Host;
		$mail->SMTPAuth = true;
		$mail->AuthType = 'LOGIN';
		$mail->Username = $Username;
		$mail->Password = $Password;
		$mail->SMTPSecure = $Secure == 'none' ? '' : $Secure;
		$mail->Port = $Port;
		$mail->setFrom($From, $Nickname);
		$mail->addAddress($to);
		$mail->Subject = $sub;
		$mail->Body = $msg;
		$mail->isHTML(true);
		// 测试邮件开启调试输出并收集日志
		$smtpDebugLog = '';
		if (strpos($sub, '邮件发送功能测试') !== false) {
			$mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
			$smtpDebugLogRef = &$smtpDebugLog;
			$mail->Debugoutput = function($str, $level) use (&$smtpDebugLogRef) {
				if (strlen($smtpDebugLogRef) < 4000) {
					$smtpDebugLogRef .= $str . "\n";
				}
			};
		} else {
			$mail->SMTPDebug = 0;
		}
		$mail->setLanguage('zh_cn', ROOT . 'Common/mail/PHPMailer-master/language/');
		$mail->SMTPOptions = [
			'ssl' => [
				'verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_signed' => true
			]
		];
		
		if ($mail->send()) {
			$DB->exec("INSERT INTO nteam_mail_log (to_email, subject, content, status, ip, create_time) VALUES ('$to', '$sub', '" . mb_substr($msg, 0, 200) . "', 1, '$ip', '$currentTime')");
			return true;
		} else {
			$errDetail = addslashes($mail->ErrorInfo . (strlen($smtpDebugLog) ? " | DEBUG: " . mb_substr($smtpDebugLog, 0, 800) : ""));
			$DB->exec("INSERT INTO nteam_mail_log (to_email, subject, content, status, error_msg, ip, create_time) VALUES ('$to', '$sub', '" . mb_substr($msg, 0, 200) . "', 0, '" . $errDetail . "', '$ip', '$currentTime')");
			$errorInfoPrimary = $mail->ErrorInfo;
			$fallbackTried = false;
			$fallbackSecure = '';
			$fallbackPort = 0;
			if ($Secure == 'ssl') {
				$fallbackSecure = 'tls';
				$fallbackPort = 587;
				$fallbackTried = true;
			} elseif ($Secure == 'tls') {
				$fallbackSecure = 'ssl';
				$fallbackPort = 465;
				$fallbackTried = true;
			}
			if ($fallbackTried) {
				$mail->clearAddresses();
				$mail->addAddress($to);
				$mail->SMTPSecure = $fallbackSecure;
				$mail->Port = $fallbackPort;
				if ($mail->send()) {
					$DB->exec("INSERT INTO nteam_mail_log (to_email, subject, content, status, ip, create_time) VALUES ('$to', '$sub', '" . mb_substr($msg, 0, 200) . "', 1, '$ip', '$currentTime')");
					return true;
				}
				$errorInfoSecondary = $mail->ErrorInfo;
				return $errorInfoPrimary . ' | ' . $errorInfoSecondary;
			}
			return $errorInfoPrimary;
		}
	} catch (\Exception $e) {
		$DB->exec("INSERT INTO nteam_mail_log (to_email, subject, content, status, error_msg, ip, create_time) VALUES ('$to', '$sub', '" . mb_substr($msg, 0, 200) . "', 0, '" . addslashes($e->getMessage()) . "', '$ip', '$currentTime')");
		return $e->getMessage();
	}
}

function send_mail_to_admins($sub, $msg) {
	$adminEmails = get_admin_emails();
	if (empty($adminEmails)) {
		return false;
	}
	
	$successCount = 0;
	$failedEmails = [];
	
	foreach ($adminEmails as $email) {
		if (send_mail($email, $sub, $msg)) {
			$successCount++;
		} else {
			$failedEmails[] = $email;
		}
	}
	
	return [
		'success' => $successCount,
		'failed' => $failedEmails,
		'total' => count($adminEmails)
	];
}
function conf($name) {
	global $DB;
	$confs=$DB->query("SELECT * FROM nteam_config WHERE id=1");
	while($conf = $confs->fetch()){
		return $conf[$name];
	}
}
function conf_index($name) {
	global $DB;
	$confs=$DB->query("SELECT * FROM nteam_config_theme WHERE id=1");
	while($conf = $confs->fetch()){
		return $conf[$name];
	}
}
function saveSetting($k, $v) {
	global $DB;
	$v = addslashes($v);
	$checkColumn = $DB->query("SHOW COLUMNS FROM nteam_config LIKE '$k'");
	if ($checkColumn->fetch() === false) {
		$DB->exec("ALTER TABLE nteam_config ADD COLUMN `$k` TEXT COMMENT '邮件配置'");
	}
	return $DB->exec("UPDATE nteam_config SET `$k`='$v' WHERE `id`=1");
}
function saveSettings($k, $v) {
	global $DB;
	return $DB->exec("UPDATE nteam_config_theme SET $k='{$v}' WHERE `id`=1");
}
/**
 * 把 Markdown 正文转成纯文本（用于列表页的内容预览）
 * ------------------------------------------------------------------
 * 列表预览不做排版渲染，也不应出现 #、**、![]() 等 Markdown 语法符号，
 * 因此这里做「去语法」处理：
 *   - 图片整段去掉；链接、加粗、删除线、行内代码只保留文字；
 *   - 标题 / 引用 / 列表 / 表格 / 分隔线只保留内容，去掉符号；
 *   - 去掉 HTML 标签、还原 HTML 实体，最后折叠空白并按字数截断。
 *
 * @param string $content 原始 Markdown 文本
 * @param int    $length  截断字数（0 = 不截断），超出时补省略号
 * @return string 纯文本，输出时仍需 htmlspecialchars 转义
 */
function markdownToPlainText($content, $length = 0) {
	$text = (string)$content;
	if ($text === '') {
		return '';
	}
	$text = str_replace(array("\r\n", "\r"), "\n", $text);
	// 代码块围栏（``` 行）去掉，内部文字保留
	$text = preg_replace('/^\s{0,3}`{3,}[^\n]*$/m', '', $text);
	// 图片整段去掉（预览不展示图片与占位文字）
	$text = preg_replace('/!\[[^\]]*\]\([^)]*\)/', '', $text);
	// 链接 [文字](地址) → 文字
	$text = preg_replace('/\[([^\]]*)\]\([^)]*\)/', '$1', $text);
	// 标题、引用、无序/有序列表、分隔线的语法符号
	$text = preg_replace('/^\s{0,3}#{1,6}\s*/m', '', $text);
	$text = preg_replace('/^\s{0,3}>\s?/m', '', $text);
	$text = preg_replace('/^\s{0,3}[-*+]\s+/m', '', $text);
	$text = preg_replace('/^\s{0,3}\d+\.\s+/m', '', $text);
	$text = preg_replace('/^\s{0,3}(?:[-*_]\s*){3,}$/m', '', $text);
	// 表格分隔行：|---|---|、| :---: | --- |
	$text = preg_replace('/^\s*\|?(?:\s*:?-+:?\s*\|)+\s*$/m', '', $text);
	// 富文本标签与 HTML 实体
	$text = strip_tags($text);
	$text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
	// 行内强调：**粗体** / __粗体__ / ~~删除~~ / `代码` → 只留文字
	$text = preg_replace('/\*\*(.+?)\*\*/s', '$1', $text);
	$text = preg_replace('/__(.+?)__/s', '$1', $text);
	$text = preg_replace('/~~(.+?)~~/s', '$1', $text);
	$text = preg_replace('/`([^`]*)`/s', '$1', $text);
	// 残留的强调符号与表格竖线
	$text = str_replace(array('*', '`', '|'), array('', '', ' '), $text);
	// 折叠空白与换行为单个空格，保证预览是连续一行
	$text = preg_replace('/\s+/u', ' ', $text);
	$text = trim($text);
	if ($length > 0 && mb_strlen($text, 'UTF-8') > $length) {
		$text = mb_substr($text, 0, $length, 'UTF-8') . '...';
	}
	return $text;
}
?>
