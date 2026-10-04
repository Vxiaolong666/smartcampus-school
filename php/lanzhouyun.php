<?php
/**
 * @package Lanzou
 * @author Filmy,hanximeng
 * @version 1.3.110
 * @Date 2026-09-01
 * @link https://hanximeng.com
 */
//屏蔽报错
error_reporting(0);
header('Access-Control-Allow-Origin:*');
header('Content-Type:application/json; charset=utf-8');
//========== 缓存配置（可按需自定义） ==========
//缓存目录：解析成功后会把结果写入此处，不存在时会自动创建，请确保 PHP 进程有写入权限
$cacheDir = __DIR__ . '/cache';
//缓存有效时间（秒）：短时间内的重复请求直接返回缓存结果，避免大量重复请求触发蓝奏风控；设为 0 表示关闭缓存
//同时作为缓存清理周期：过期缓存会在下次请求经过一个缓存周期后被清理
$cacheTime = 900;//15分钟
//缓存密钥：参与缓存 key 的生成，可防止缓存 key 被模拟/投毒，建议修改为随机字符串
$cacheSalt = 'LanzouAPI_Cache_5e3a9f1c';
//默认UA
$UserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.121 Safari/537.36';
$url = isset($_GET['url']) ? $_GET['url'] : "";
$pwd = isset($_GET['pwd']) ? $_GET['pwd'] : "";
$type = isset($_GET['type']) ? $_GET['type'] : "";
$webpage = parse_url($url, PHP_URL_QUERY);
//判断传入链接参数是否为空
if (empty($url)) {
	die(
	    json_encode(
	        array(
	            'code' => 400,
	            'msg' => '请输入URL'
	        )
	        , JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
	    );
}
//保留分享链接原始域名。蓝奏云的风控 Cookie 与域名关联，强制换域名会导致校验失败。
$parsedUrl = parse_url($url);
if (!isset($parsedUrl['scheme'], $parsedUrl['host'], $parsedUrl['path']) ||
    !in_array(strtolower($parsedUrl['scheme']), array('http', 'https'), true)) {
	JsonError('URL格式错误');
}
//中转下载仅允许蓝奏云分享链接，避免该模式被当作通用文件代理滥用
if ($type == "file" && !preg_match('~^https?://([a-z0-9-]+\.)*(lanzou[a-z]*|lanzn)\.(com|cn)(/|$)~i', $url)) {
	JsonError('中转下载仅支持蓝奏云分享链接');
}
$origin = 'https://' . $parsedUrl['host'];
$url = $origin . $parsedUrl['path'];
if (!empty($parsedUrl['query'])) {
	$url .= '?' . $parsedUrl['query'];
}
//缓存 key 由解析参数与缓存密钥共同生成，命中时直接返回，不再请求蓝奏云
$cacheKey = md5($cacheSalt . '|' . $url . '|' . $pwd . '|' . (isset($_GET['n']) ? $_GET['n'] : ''));
//中转下载（type=file）需要「解析后立即拉取文件」，不能复用缓存里的旧直链
$cached = ($type == "file") ? false : CacheGet($cacheKey);
if ($cached !== false) {
	if ($type == "down") {
		header("X-Lanzou-Cache: HIT");
		header("Location:" . $cached['downUrl']);
		die;
	}
	die(
	    json_encode(
	        array(
	            'code' => 200,
	            'msg' => '解析成功（缓存结果）',
	            'name' => isset($cached['name']) ? $cached['name'] : "",
	            'filesize' => isset($cached['filesize']) ? $cached['filesize'] : "",
	            'downUrl' => isset($cached['downUrl']) ? $cached['downUrl'] : "",
	            'fromCache' => true
	        )
	        , JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
	    );
}
$cookie="";
$softInfo = MloocCurlGetWithChallenge($url, $UserAgent, $cookie);
//下载接口地址：新版蓝奏把 ajax 接口迁到了独立域名（如 https://apifile.woozooo.com/ajaxfile.php?file=NNN，
//页面里写的是绝对地址），分享域名上已不再提供该接口——POST 过去会返回 407，必须优先使用页面里的绝对地址
preg_match_all("~https?://[a-z0-9.\-]+/ajax(?:m|file)\.php\?file=\d+~i", $softInfo, $ajaxAbsUrl);
$ajaxAbsolute = isset($ajaxAbsUrl[0][0]) ? $ajaxAbsUrl[0][0] : '';
//判断文件链接是否失效
if (strpos($softInfo, "文件取消分享了") !== false || strpos($softInfo, "文件不存在") !== false) {
	die(
	    json_encode(
	        array(
	            'code' => 400,
	            'msg' => '文件取消分享了'
	        )
	        , JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
	    );
}
//取文件名称、大小
preg_match('~style="font-size: 30px;text-align: center;padding: 56px 0px 20px 0px;">(.*?)</div>~', $softInfo, $softName);
if(!isset($softName[1])) {
	preg_match('~<div class="n_box_3fn".*?>(.*?)</div>~', $softInfo, $softName);
}
preg_match('~<div class="n_filesize".*?>大小：(.*?)</div>~', $softInfo, $softFilesize);
if(!isset($softFilesize[1])) {
	preg_match('~<span class="p7">文件大小：</span>(.*?)<br>~', $softInfo, $softFilesize);
}
if(!isset($softName[1])) {
	preg_match('~var filename = \'(.*?)\';~', $softInfo, $softName);
}
if(!isset($softName[1])) {
	preg_match('~div class="b"><span>(.*?)</span></div>~', $softInfo, $softName);
}
//带密码的链接的处理
if(strpos($softInfo, "function down_p(){") != false  && empty($webpage)) {
	if(empty($pwd)) {
		die(
			json_encode(
				array(
					'code' => 400,
					'msg' => '请输入分享密码'
			    )
				, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
			);
	}
	//新版页面：data:{...'sign':(字面量|变量)}，行首锚定 data: 可排除 //data 注释中的伪参数
	preg_match("~^[ \t]*data\s*:\s*\{[^\r\n]*['\"]sign['\"]\s*:\s*(?:(['\"])(.*?)\\1|([A-Za-z_$][\w$]*))~m", $softInfo, $signParam);
	$sign = isset($signParam[2]) ? $signParam[2] : '';
	//sign 为变量（如 isngis）时，查找对应的 var 赋值
	if($sign === '' && !empty($signParam[3])) {
		preg_match_all("~var\s+" . preg_quote($signParam[3], "~") . "\s*=\s*(['\"])(.*?)\\1\s*;~", $softInfo, $signValues);
		$signValues = array_values(array_filter(isset($signValues[2]) ? $signValues[2] : array(), 'strlen'));
		$sign = !empty($signValues) ? end($signValues) : '';
	}
	//兼容旧版字面量 sign
	if($sign === '') {
		preg_match_all("~'sign':'(.*?)',~", $softInfo, $segment);
		$sign = isset($segment[1][1]) ? $segment[1][1] : '';
	}
	preg_match_all("~(?:^|/)(ajax(?:m|file)\.php\?file=\d+)~", $softInfo, $ajaxm);
	$ajaxPath = isset($ajaxm[1][0]) ? $ajaxm[1][0] : '';
	//优先使用页面里的绝对接口地址，取不到时退回「分享域名 + 相对路径」（兼容旧版页面）
	$ajaxUrl = $ajaxAbsolute !== '' ? $ajaxAbsolute : ($ajaxPath !== '' ? $origin . '/' . $ajaxPath : '');
	if(empty($sign) || $ajaxUrl === '') {
		die(
			json_encode(
				array(
					'code' => 400,
					'msg' => '未找到密码页 sign 或下载接口参数'
			    )
				, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
			);
	}
	$post_data = array(
		"action" => "downprocess",
		"sign" => $sign,
		"p" => $pwd,
		"kd" => 1
	);
	$softInfo = MloocCurlPost($post_data, $ajaxUrl, $url, $UserAgent, "acw_sc__v2=".$cookie);
	$nameInfo = json_decode($softInfo, true);
	$softName[1] = (is_array($nameInfo) && isset($nameInfo['inf'])) ? $nameInfo['inf'] : '';
} else {
	//不带密码的链接处理
	preg_match("~\n<iframe.*?name=\"[\s\S]*?\"\ssrc=\"\/(.*?)\"~", $softInfo, $link);
	//蓝奏云新版页面正则规则
	if(empty($link[1])) {
		preg_match("~<iframe.*?name=\"[\s\S]*?\"\ssrc=\"\/(.*?)\"~", $softInfo, $link);
	}
	$ifurl = $origin . "/" . $link[1];
	if(!empty($webpage)){
	    preg_match_all("~'sign':'(.*?)'~", $softInfo, $segment);
	    preg_match_all("~ajaxdata = '(.*?)'~", $softInfo, $signs);
	    preg_match_all("~(?:^|/)(ajax(?:m|file)\.php\?file=\d+)~", $softInfo, $ajaxm);
	    $post_data = array(
		    "action" => "downprocess",
		    "websignkey" => "Em2R",
		    "sign" => $segment[1][1],
		    "websign" => 2,
		    "kd" => 1,
		    "ves" => 1
	    );
	}else{
	    $softInfo = MloocCurlGetWithChallenge($ifurl, $UserAgent, $cookie, $url);
	    preg_match_all("~wp_sign = '(.*?)'~", $softInfo, $segment);
	    preg_match_all("~ajaxdata = '(.*?)'~", $softInfo, $signs);
	    preg_match_all("~(?:^|/)(ajax(?:m|file)\.php\?file=\d+)~", $softInfo, $ajaxm);
	    $post_data = array(
		    "action" => "downprocess",
		    "websignkey" => $signs[1][0],
		    "signs" => $signs[1][0],
		    "sign" => $segment[1][0],
		    "websign" => '',
		    "kd" => 1,
		    "ves" => 1
	    );
	}
	$ajaxmPath = $ajaxm[1][0] ?? '';
	//同上：优先使用页面里的绝对接口地址
	$ajaxUrl = $ajaxAbsolute !== '' ? $ajaxAbsolute : ($ajaxmPath !== '' ? $origin . '/' . $ajaxmPath : '');
	if($ajaxUrl === '') {
		JsonError('未找到下载接口参数');
	}
	$softInfo = MloocCurlPost($post_data, $ajaxUrl, $ifurl, $UserAgent, "acw_sc__v2=".$cookie);
}
//其他情况下的信息输出
$decoded = json_decode($softInfo, true);
if (!is_array($decoded) || !isset($decoded['zt']) || $decoded['zt'] != 1) {
	$errMsg = (is_array($decoded) && isset($decoded['inf'])) ? $decoded['inf'] : '解析失败，蓝奏云返回异常，请稍后重试';
	die(
	    json_encode(
	        array(
	            'code' => 400,
	            'msg' => $errMsg
	        )
	        , JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
	    );
}
$softInfo = $decoded;
//拼接链接
$downUrl1 = $softInfo['dom'] . '/file/' . $softInfo['url'];
//先按新版流程解析（CDN 挑战 +「系统发现您的网络异常」验证页换直链），失败再回退到旧的 302 跳转方式
$downUrl2 = LanzouResolveDirect($downUrl1, $origin, $UserAgent, $cookie);
if ($downUrl2 === '') {
	$downUrl2 = MloocCurlHead($downUrl1, $origin, $UserAgent, "down_ip=1; acw_sc__v2=".$cookie);
}
//判断最终链接是否获取成功，如未成功则使用原链接
if(strpos($downUrl2,"http") === false) {
	$downUrl = $downUrl1;
} else {
	//2025-03-17 新增后缀自定义功能 https://github.com/hanximeng/LanzouAPI/issues/26
	if(!empty($_GET['n'])){
	    preg_match_all("~(.*?)\?fn=(.*?)\\.~", $downUrl2, $rename);
	    $downUrl = (isset($rename['0']['0']) && $rename['0']['0'] !== '') ? $rename['0']['0'].$_GET['n'] : $downUrl2;
	}else{
	    $downUrl = $downUrl2;
	}
}
//2024-12-03 修复pid参数可能导致的服务器ip地址泄露
$downUrl=preg_replace('/pid=(.*?.)&/', '', $downUrl);
//解析成功，写入缓存供短时间内的重复请求复用
CacheSet($cacheKey, array(
	'name' => isset($softName[1]) ? $softName[1] : "",
	'filesize' => isset($softFilesize[1]) ? $softFilesize[1] : "",
	'downUrl' => $downUrl
));
//判断输出方式：JSON（默认）/ 跳转直链（down）/ 服务端中转（file）
if ($type != "down" && $type != "file") {
	die(
	    json_encode(
	        array(
	            'code' => 200,
	            'msg' => '解析成功',
	            'name' => isset($softName[1]) ? $softName[1] : "",
	            'filesize' => isset($softFilesize[1]) ? $softFilesize[1] : "",
	            'downUrl' => $downUrl
	        )
	        , JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
	    );
} else {
	if ($type == "file") {
		//服务端中转：带着解析时的会话 Cookie / 来源立即拉取文件并流式输出。
		//蓝奏云直链短时效且与解析会话（Cookie/IP）绑定，直接把直链交给客户端必然拿到错误页。
		LanzouStream(array($downUrl, $downUrl1), isset($softName[1]) ? $softName[1] : '', $origin, $UserAgent, "down_ip=1; acw_sc__v2=".$cookie);
	}
	header("Location:$downUrl");
	die;
}
/**
 * 服务端中转下载：把解析到的文件流式输出给客户端，客户端只需下载本站地址。
 *
 * @param array  $urls   依次尝试的候选地址（重定向后的直链 / 未重定向的 file 链接）
 * @param string $name   蓝奏云返回的文件名（可能没有后缀）
 * @param string $referer 来源页（解析时使用的域名）
 * @param string $UserAgent
 * @param string $cookie 解析时使用的会话 Cookie
 */
function LanzouStream($urls, $name, $referer, $UserAgent, $cookie)
{
	//文件名兜底并修正后缀：客户端据此识别为安装包（APK 是 ZIP，后缀缺失会导致系统安装器「解析包失败」）
	$fileName = trim((string) $name);
	if ($fileName === '') {
		$fileName = 'app-update.apk';
	} elseif (!preg_match('~\.(apk|ipa|wgt|zip)$~i', $fileName)) {
		$fileName .= '.apk';
	}
	//清理异常字符，避免响应头注入
	$fileName = preg_replace('~[^\x{4e00}-\x{9fa5}\w.\-]+~u', '_', $fileName);

	@set_time_limit(0);
	@ini_set('max_execution_time', '0');

	$lastReason = '';
	$candidates = array_values(array_filter((array) $urls, 'strlen'));
	foreach ($candidates as $index => $url) {
		$cookieHeader = $cookie;
		$info = array();
		for ($pass = 0; $pass < 2; $pass++) {
			if (LanzouStreamAttempt($url, $referer, $UserAgent, $cookieHeader, $fileName, $info)) {
				die; //文件已完整输出
			}
			//命中 CDN（阿里云 ESA / EdgeOne）的 acw_sc_v2 脚本校验：按页面里的 arg1 算出校验 Cookie 后重试，
			//浏览器正是靠执行这段 JS 才被放行，这也是「浏览器能下、服务端直连拿不到文件」的原因。
			if ($pass === 0 && !empty($info['arg1'])) {
				$cookieHeader = 'down_ip=1; acw_sc__v2=' . acw_sc_v2_simple($info['arg1'])
					. (empty($info['setCookie']) ? '' : '; ' . $info['setCookie']);
				continue;
			}
			break;
		}
		$lastReason = isset($info['reason']) ? $info['reason'] : '';
		//失败原因写日志，便于排查（响应仍是 JSON，不能污染输出）
		@file_put_contents(
			__DIR__ . '/cache/lanzou_stream_error.log',
			date('Y-m-d H:i:s') . " 候选#" . $index . " HTTP=" . (isset($info['httpCode']) ? $info['httpCode'] : 0)
				. " ERR=" . (isset($info['curlErr']) ? $info['curlErr'] : '')
				. "\n  原因：" . $lastReason
				. "\n  响应头：" . json_encode(isset($info['headers']) ? $info['headers'] : array(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
				. "\n  挑战arg1：" . (empty($info['arg1']) ? '无' : $info['arg1'])
				. "\n  片段：" . preg_replace('~[^\x20-\x7e\x{4e00}-\x{9fa5}]~u', '?', mb_substr(isset($info['snippet']) ? $info['snippet'] : '', 0, 1200, 'UTF-8'))
				. "\n  URL=" . $url . "\n",
			FILE_APPEND
		);
	}
	JsonError('中转下载失败：' . ($lastReason !== '' ? $lastReason : '直链已失效或被蓝奏云风控拦截，请稍后重试'));
}

/**
 * 单次拉取尝试：先嗅探前 1KB，确认是文件才下发下载响应头并流式输出。
 *
 * @return bool true=文件已完整输出；false=失败（原因/挑战参数见 $info）
 */
function LanzouStreamAttempt($url, $referer, $UserAgent, $cookieHeader, $fileName, &$info)
{
	$sniffSize = 1024;
	$info = array('reason' => '', 'arg1' => '', 'setCookie' => '', 'httpCode' => 0, 'curlErr' => '', 'headers' => array(), 'snippet' => '');
	$isFile = null;   //null=未判定；true=确认是文件；false=错误页/非文件
	$sent = false;    //是否已向客户端输出内容
	$sniff = '';      //嗅探缓冲
	$respHeaders = array();
	$arg1 = '';
	$setCookie = '';
	$ch = curl_init($url);
	curl_setopt_array($ch, array(
		CURLOPT_FOLLOWLOCATION => 1,
		CURLOPT_MAXREDIRS => 5,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_SSL_VERIFYHOST => false,
		CURLOPT_CONNECTTIMEOUT => 15,
		CURLOPT_TIMEOUT => 0, //大文件不限时
		CURLOPT_USERAGENT => $UserAgent,
		CURLOPT_REFERER => $referer,
		CURLOPT_HTTPHEADER => array(
			'Cookie: ' . $cookieHeader,
			'Accept: */*',
			'Accept-Language: zh-CN,zh;q=0.9'
		),
		CURLOPT_ENCODING => '', //自动协商并解压（ESA 会对响应做 gzip/br 压缩）
		CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$respHeaders, &$setCookie) {
			$trim = trim($line);
			if (stripos($trim, 'HTTP/') === 0) {
				//跟随重定向时可能出现多段响应头，重置后再收集
				$respHeaders = array();
				return strlen($line);
			}
			$pos = strpos($trim, ':');
			if ($pos !== false) {
				$key = strtolower(substr($trim, 0, $pos));
				$val = trim(substr($trim, $pos + 1));
				$respHeaders[$key] = $val;
				if ($key === 'set-cookie' && $setCookie === '') {
					$setCookie = trim(current(explode(';', $val))); //只取 name=value 部分
				}
			}
			return strlen($line);
		},
		CURLOPT_WRITEFUNCTION => function ($ch, $chunk) use (&$isFile, &$sent, &$sniff, &$respHeaders, &$arg1, $sniffSize, $fileName) {
			if ($isFile === false) {
				return strlen($chunk); //已判定为错误页，后续内容丢弃
			}
			if ($isFile === null) {
				$sniff .= $chunk;
				if (strlen($sniff) < $sniffSize) {
					return strlen($chunk); //数据不足，继续嗅探
				}
				$head = ltrim($sniff);
				$ctype = isset($respHeaders['content-type']) ? strtolower($respHeaders['content-type']) : '';
				$looksText = ($head !== '' && $head[0] === '<')
					|| strpos($ctype, 'text/html') !== false
					|| strpos($ctype, 'application/json') !== false
					|| strpos($ctype, 'text/plain') !== false;
				if ($looksText) {
					$isFile = false;
					//记录脚本校验页里的 arg1，供上层算出 acw_sc__v2 后重试
					if (preg_match("~var\s+arg1=['\"]([0-9a-f]{40})['\"]~i", $sniff, $m)) {
						$arg1 = $m[1];
					}
					return strlen($chunk);
				}
				//确认是文件：此时才下发下载响应头，并补发嗅探缓冲
				$isFile = true;
				$flush = $sniff;
				$sniff = '';
				if (!headers_sent()) {
					header('Content-Type: application/vnd.android.package-archive');
					header('Content-Disposition: attachment; filename="' . $fileName . '"');
					header('Cache-Control: no-store');
					header('X-Lanzou-Stream: 1');
					//响应被压缩时长度对应压缩后数据，透传会导致客户端认为不完整
					if (!empty($respHeaders['content-length']) && empty($respHeaders['content-encoding'])) {
						header('Content-Length: ' . $respHeaders['content-length']);
					}
				}
				echo $flush;
				@ob_flush();
				@flush();
				$sent = true;
				return strlen($chunk);
			}
			echo $chunk;
			@ob_flush();
			@flush();
			$sent = true;
			return strlen($chunk);
		},
	));
	curl_exec($ch);
	$info['httpCode'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$info['curlErr'] = curl_error($ch);
	curl_close($ch);

	if ($sent) {
		return true;
	}
	if ($isFile === null) {
		//响应体不足嗅探长度：正常安装包不可能这么小，视为失败
		$info['reason'] = '返回内容过小（' . strlen($sniff) . ' 字节），不是安装包';
	} elseif ($arg1 !== '') {
		$info['reason'] = 'CDN 脚本校验未通过（acw_sc_v2）';
	} else {
		$info['reason'] = '返回的是网页/错误页（Content-Type: ' . (isset($respHeaders['content-type']) ? $respHeaders['content-type'] : '空') . '）';
	}
	$info['arg1'] = $arg1;
	$info['setCookie'] = $setCookie;
	$info['snippet'] = $sniff;
	$info['headers'] = array(
		'content-type' => isset($respHeaders['content-type']) ? $respHeaders['content-type'] : '',
		'content-encoding' => isset($respHeaders['content-encoding']) ? $respHeaders['content-encoding'] : '',
		'content-length' => isset($respHeaders['content-length']) ? $respHeaders['content-length'] : '',
		'set-cookie' => $setCookie
	);
	return false;
}
//获取下载链接函数
function MloocCurlGetDownUrl($url) {
	$header = get_headers($url,1);
	if(isset($header['Location'])) {
		return $header['Location'];
	}
	return "";
}
//阿里云 ESA 会先返回 arg1 挑战页。计算 acw_sc__v2 后以同一 UA、Referer 重试。
function MloocCurlGetWithChallenge($url, $UserAgent, &$cookie, $referer = '') {
	$response = MloocCurlGet($url, $UserAgent, $cookie === '' ? '' : 'acw_sc__v2='.$cookie, $referer);
	if (preg_match("~var\\s+arg1=['\"]([0-9a-f]{40})['\"]~i", $response, $match)) {
		$cookie = acw_sc_v2_simple($match[1]);
		$response = MloocCurlGet($url, $UserAgent, 'acw_sc__v2='.$cookie, $referer);
	}
	return $response;
}
/**
 * 精简 GET 请求（不带 X-FORWARDED-FOR 伪造头，CDN 会按真实来源 IP 绑定校验 Cookie）
 *
 * @param string     $url
 * @param string     $UserAgent
 * @param string     $cookie
 * @param string     $referer
 * @param array|null $setCookies 回传响应头下发的 Cookie（name=value）
 * @return string 响应体
 */
function LanzouHttpGet($url, $UserAgent, $cookie, $referer, &$setCookies = null) {
	$setCookies = array();
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($curl, CURLOPT_COOKIE, $cookie);
	if ($UserAgent != "") {
		curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);
	}
	if ($referer != "") {
		curl_setopt($curl, CURLOPT_REFERER, $referer);
	}
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	curl_setopt($curl, CURLOPT_HEADERFUNCTION, function ($ch, $line) use (&$setCookies) {
		$trim = trim($line);
		if (stripos($trim, 'set-cookie:') === 0) {
			$value = trim(substr($trim, 11));
			$semi = strpos($value, ';');
			$setCookies[] = ($semi === false) ? $value : substr($value, 0, $semi);
		}
		return strlen($line);
	});
	$response = curl_exec($curl);
	curl_close($curl);
	return $response;
}

/**
 * 精简 POST 请求（用于 CDN 验证接口，表单编码 + XHR 头，与页面 jQuery 保持一致）
 */
function LanzouHttpPost($url, $data, $referer, $UserAgent, $cookie) {
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
	if ($UserAgent != "") {
		curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);
	}
	curl_setopt($curl, CURLOPT_COOKIE, $cookie);
	if ($referer != "") {
		curl_setopt($curl, CURLOPT_REFERER, $referer);
	}
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Requested-With: XMLHttpRequest'));
	$response = curl_exec($curl);
	curl_close($curl);
	return $response;
}

/**
 * 解析「{$dom}/file/?token」形式的下载地址（新版蓝奏页面结构）
 *
 * 流程：
 *   1. 请求 file 地址时，CDN（阿里云 ESA）先返回带 arg1 的 JS 挑战页；算出 acw_sc__v2 后，
 *      连同响应下发的 acw_tc / cdn_sec_tc 一起重试；
 *   2. 重试后若返回「系统发现您的网络异常，需要验证后下载文件」页，则取页面里的 file / sign，
 *      延时后再 POST 到 {$dom}/file/ajax.php 换成真实直链（蓝奏要求页面出现约 2 秒再提交，
 *      过早提交只会返回 {"zt":1,"url":"?SignError"}）；
 *   3. 页面形态不认识或换取失败返回 ''，由调用方回退到旧的 302 跳转方式。
 *
 * @param string $fileUrl     {$dom}/file/?token
 * @param string $referer     分享页地址（Referer）
 * @param string $UserAgent
 * @param string $shareCookie 分享页挑战得到的 acw_sc__v2 值
 * @return string 直链；失败返回 ''
 */
function LanzouResolveDirect($fileUrl, $referer, $UserAgent, $shareCookie) {
	$parts = parse_url($fileUrl);
	if (!isset($parts['scheme'], $parts['host'])) {
		return '';
	}
	$cdnOrigin = $parts['scheme'] . '://' . $parts['host'];

	// 1. 首次请求：可能是 JS 挑战页，也可能直接就是验证页
	$response = LanzouHttpGet($fileUrl, $UserAgent, 'down_ip=1', $referer, $setCookies);
	$cookie = 'down_ip=1';
	$hasChallenge = false;
	if (preg_match("~var\s+arg1=['\"]([0-9a-f]{40})['\"]~i", $response, $m)) {
		$hasChallenge = true;
		$cookie .= '; acw_sc__v2=' . acw_sc_v2_simple($m[1]);
	}
	foreach ($setCookies as $sc) {
		//只带上 CDN 校验相关 Cookie，避免污染请求
		if (preg_match('~^(acw_tc|cdn_sec_tc)=~i', $sc)) {
			$cookie .= '; ' . $sc;
		}
	}

	// 2. 带挑战 Cookie 重试（挑战页本身没有验证参数，必须换一次再取）
	if ($hasChallenge) {
		$response = LanzouHttpGet($fileUrl, $UserAgent, $cookie, $referer, $ignored);
	}

	// 3. 从验证页取 file / sign
	if (!preg_match("~'file'\s*:\s*'([^']{20,})'~s", $response, $fm) ||
		!preg_match("~'sign'\s*:\s*'([^']+)'~s", $response, $sm)) {
		return '';
	}
	$fileToken = $fm[1];
	$verifySign = $sm[1];

	// 4. 延时后换取直链（el：1/2/3 分别对应页面上三个按钮，网络异常场景是 2）
	sleep(3);
	foreach (array(2, 1, 3) as $el) {
		$raw = LanzouHttpPost(
			$cdnOrigin . '/file/ajax.php',
			array('file' => $fileToken, 'el' => $el, 'sign' => $verifySign),
			$fileUrl,
			$UserAgent,
			$cookie
		);
		$json = json_decode($raw, true);
		if (is_array($json) && !empty($json['url']) && stripos($json['url'], 'http') === 0) {
			return html_entity_decode($json['url'], ENT_QUOTES, 'UTF-8');
		}
	}
	return '';
}

//JSON错误输出
function JsonError($message) {
	die(json_encode(array('code' => 400, 'msg' => $message), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}
//CURL函数
function MloocCurlGet($url = '', $UserAgent = '', $cookie = '', $referer = '') {
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($curl, CURLOPT_COOKIE , $cookie);
	if ($UserAgent != "") {
		curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);
	}
	if ($referer != "") {
		curl_setopt($curl, CURLOPT_REFERER, $referer);
	}
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:'.Rand_IP(), 'CLIENT-IP:'.Rand_IP()));
	#关闭SSL
	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	    curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
	    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	#返回数据不直接显示
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	#超时设置，默认为10秒
	    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	$response = curl_exec($curl);
	return $response;
}
//POST函数
function MloocCurlPost($post_data = '', $url = '', $ifurl = '', $UserAgent = '',$cookie = '') {
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_COOKIE , $cookie);
	curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);
	if ($ifurl != '') {
		curl_setopt($curl, CURLOPT_REFERER, $ifurl);
	}
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:'.Rand_IP(), 'CLIENT-IP:'.Rand_IP()));
	#关闭SSL
	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	#返回数据不直接显示
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	#超时设置，默认为10秒
	    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	curl_setopt($curl, CURLOPT_POST, 1);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
	$response = curl_exec($curl);
	return $response;
}
//直链解析函数
function MloocCurlHead($url,$guise,$UserAgent,$cookie) {
	$headers = array(
		'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
		'Accept-Encoding: gzip, deflate',
		'Accept-Language: zh-CN,zh;q=0.9',
		'Cache-Control: no-cache',
		'Connection: keep-alive',
		'Pragma: no-cache',
		'Upgrade-Insecure-Requests: 1',
		'User-Agent: '.$UserAgent
	);
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_HTTPHEADER,$headers);
	curl_setopt($curl, CURLOPT_REFERER, $guise);
	curl_setopt($curl, CURLOPT_COOKIE , $cookie);
	curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);
	//优先用 HEAD 请求获取跳转地址，避免下载整个文件
	curl_setopt($curl, CURLOPT_NOBODY, 1);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLINFO_HEADER_OUT, TRUE);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	//超时设置，默认为10秒
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	curl_exec($curl);
	$redirectUrl = curl_getinfo($curl, CURLINFO_REDIRECT_URL);
	//部分 CDN 对 HEAD 请求不返回跳转地址，回退为 GET
	if (empty($redirectUrl)) {
		curl_setopt($curl, CURLOPT_NOBODY, 0);
		curl_exec($curl);
		$redirectUrl = curl_getinfo($curl, CURLINFO_REDIRECT_URL);
	}
	return $redirectUrl;
}
//随机IP函数
function Rand_IP() {
	$ip2id = round(rand(600000, 2550000) / 10000);
	$ip3id = round(rand(600000, 2550000) / 10000);
	$ip4id = round(rand(600000, 2550000) / 10000);
	$arr_1 = array("218","218","66","66","218","218","60","60","202","204","66","66","66","59","61","60","222","221","66","59","60","60","66","218","218","62","63","64","66","66","122","211");
	$randarr= mt_rand(0,count($arr_1)-1);
	$ip1id = $arr_1[$randarr];
	return $ip1id.".".$ip2id.".".$ip3id.".".$ip4id;
}
//cookie生成函数(现在好像又不验证了，怕忘记就先留着吧，介意的可以删除这个函数)
function acw_sc_v2_simple($arg1) {
    $posList = [15,35,29,24,33,16,1,38,10,9,19,31,40,27,22,23,25,13,6,11,39,18,20,8,14,21,32,26,2,30,7,4,17,5,3,28,34,37,12,36];
    $mask = '3000176000856006061501533003690027800375';
    $outPutList = array_fill(0, 40, '');
    for ($i = 0; $i < strlen($arg1); $i++) {
        $char = $arg1[$i];
        foreach ($posList as $j => $pos) {
            if ($pos == $i + 1) {
                $outPutList[$j] = $char;
            }
        }
    }
    $arg2 = implode('', $outPutList);
    $result = '';
    $length = min(strlen($arg2), strlen($mask));
    for ($i = 0; $i < $length; $i += 2) {
        $strHex = substr($arg2, $i, 2);
        $maskHex = substr($mask, $i, 2);
        $xorResult = dechex(hexdec($strHex) ^ hexdec($maskHex));
        $result .= str_pad($xorResult, 2, '0', STR_PAD_LEFT);
    }
    return $result;
}
//读取缓存，命中且未过期时返回数据数组，否则返回 false
function CacheGet($key) {
	global $cacheDir, $cacheTime;
	if ($cacheTime <= 0) return false;
	//按周期清理已过期的缓存文件
	CacheClean();
	$file = $cacheDir . '/' . $key . '.json';
	if (!is_file($file)) return false;
	$data = json_decode(@file_get_contents($file), true);
	if (!is_array($data) || !isset($data['time'], $data['data'])) return false;
	if (time() - $data['time'] > $cacheTime) return false;
	return $data['data'];
}
//写入缓存
function CacheSet($key, $data) {
	global $cacheDir, $cacheTime;
	if ($cacheTime <= 0) return;
	if (!is_dir($cacheDir) && !@mkdir($cacheDir, 0755, true)) return;
	$payload = json_encode(array('time' => time(), 'data' => $data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	@file_put_contents($cacheDir . '/' . $key . '.json', $payload);
}
//按缓存有效时间 $cacheTime 周期清理一次已过期的缓存文件，防止失效缓存长期残留
function CacheClean() {
	global $cacheDir, $cacheTime;
	$flagFile = $cacheDir . '/.last_clean';
	$lastClean = is_file($flagFile) ? (int)@file_get_contents($flagFile) : 0;
	if (time() - $lastClean <= $cacheTime) return;
	@file_put_contents($flagFile, time());
	if (!is_dir($cacheDir)) return;
	foreach (glob($cacheDir . '/*.json') as $file) {
		if (time() - filemtime($file) > $cacheTime) @unlink($file);
	}
}
?>
