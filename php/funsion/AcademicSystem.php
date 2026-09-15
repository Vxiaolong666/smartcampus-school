<?php

define('ACADEMIC_KEY', 'qzkj1kjghd=876&*');
define('TOKEN_EXPIRE_TIME', 7200);

class AcademicSystem {
    
    private static $refreshCredentials = null;
    
    private static function encryptPassword($password) {
        $plaintext = json_encode($password, JSON_UNESCAPED_UNICODE);
        $encrypted = openssl_encrypt(
            $plaintext,
            'aes-128-ecb',
            ACADEMIC_KEY,
            OPENSSL_RAW_DATA,
            ''
        );
        $aes_b64 = base64_encode($encrypted);
        return base64_encode($aes_b64);
    }
    
    private static function makeRequest($url, $token = null, $params = []) {
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $headers = [
            'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1 Edg/147.0.0.0',
            'Accept: application/json, text/plain, */*',
            'Accept-Encoding: gzip, deflate, br, zstd',
            'sec-ch-ua-platform: "iOS"',
            'cache-control: no-cache',
            'sec-ch-ua: "Microsoft Edge";v="147", "Not.A/Brand";v="8", "Chromium";v="147"',
            'sec-ch-ua-mobile: ?1',
            'origin: https://jwh5.gdpnc.cn',
            'sec-fetch-site: same-origin',
            'sec-fetch-mode: cors',
            'sec-fetch-dest: empty',
            'referer: https://jwh5.gdpnc.cn/',
            'accept-language: zh-CN,zh;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6',
            'dnt: 1',
            'sec-gpc: 1',
            'priority: u=1, i',
            'Expect:',
        ];
        
        if ($token) {
            $headers[] = 'token: ' . $token;
        } else {
            $headers[] = 'token: null';
        }
        
        $ch = curl_init();
        
        $curlVersion = curl_version();
        $http2Supported = defined('CURL_HTTP_VERSION_2_0') && ($curlVersion['features'] & CURL_VERSION_HTTP2);
        
        $curlOptions = [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_ENCODING => '',
        ];
        
        if ($http2Supported) {
            $curlOptions[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_2_0;
        }
        
        curl_setopt_array($ch, $curlOptions);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => $error,
                'http_code' => $httpCode
            ];
        }
        
        $data = json_decode($response, true);
        
        return [
            'success' => true,
            'data' => $data,
            'raw' => $response,
            'http_code' => $httpCode
        ];
    }
    
    public static function login($userNo, $password) {
        $encryptedPwd = self::encryptPassword($password);
        
        $url = 'https://jwh5.gdpnc.cn/njwhd/login';
        $params = [
            'userNo' => $userNo,
            'pwd' => $encryptedPwd,
            'encode' => '1',
            'captchaData' => '',
            'codeVal' => ''
        ];
        
        $result = self::makeRequest($url, null, $params);
        
        if (!$result['success']) {
            return [
                'code' => -1,
                'msg' => '请求失败：' . $result['error']
            ];
        }
        
        return $result['data'];
    }
    
    public static function setRefreshCredentials($DB, $userId, $studentId, $password) {
        self::$refreshCredentials = [
            'DB' => $DB,
            'userId' => $userId,
            'studentId' => $studentId,
            'password' => $password
        ];
    }
    
    public static function getValidToken($DB, $userId, $studentId, $password) {
        self::setRefreshCredentials($DB, $userId, $studentId, $password);
        
        $account = $DB->getRow("SELECT token, token_time FROM nteam_academic_accounts WHERE user_id='$userId'");
        
        $token = null;
        $needUpdate = false;
        
        if ($account && !empty($account['token'])) {
            $tokenTime = strtotime($account['token_time']);
            $now = time();
            
            if (($now - $tokenTime) < TOKEN_EXPIRE_TIME) {
                $token = $account['token'];
            } else {
                $needUpdate = true;
            }
        } else {
            $needUpdate = true;
        }
        
        if ($needUpdate || empty($token)) {
            $loginResult = self::login($studentId, $password);
            
            if (isset($loginResult['code']) && $loginResult['code'] == 1) {
                $token = $loginResult['data']['token'];
                $now = date('Y-m-d H:i:s');
                $DB->exec("UPDATE nteam_academic_accounts SET token='$token', token_time='$now' WHERE user_id='$userId'");
            } else {
                return [
                    'success' => false,
                    'token' => null,
                    'msg' => $loginResult['msg'] ?? '登录失败'
                ];
            }
        }
        
        return [
            'success' => true,
            'token' => $token,
            'msg' => ''
        ];
    }
    
    private static function refreshToken() {
        if (!self::$refreshCredentials) {
            return false;
        }
        
        $cred = self::$refreshCredentials;
        $loginResult = self::login($cred['studentId'], $cred['password']);
        
        if (isset($loginResult['code']) && $loginResult['code'] == 1) {
            $token = $loginResult['data']['token'];
            $now = date('Y-m-d H:i:s');
            $cred['DB']->exec("UPDATE nteam_academic_accounts SET token='$token', token_time='$now' WHERE user_id='" . $cred['userId'] . "'");
            return $token;
        }
        
        return false;
    }
    
    private static function isTokenExpired($response) {
        $msg = $response['msg'] ?? $response['Msg'] ?? '';
        if (!empty($msg)) {
            if (strpos($msg, 'token') !== false || 
                strpos($msg, '登录') !== false || 
                strpos($msg, '过期') !== false ||
                strpos($msg, '未授权访问') !== false ||
                strpos($msg, '请先登录') !== false ||
                strpos($msg, '无效') !== false) {
                return true;
            }
        }
        if (isset($response['code']) && $response['code'] == 401) {
            return true;
        }
        return false;
    }
    
    private static function requestWithRetry($url, $token, $params = []) {
        $result = self::makeRequest($url, $token, $params);
        
        if (!$result['success']) {
            return [
                'code' => -1,
                'msg' => '请求失败：' . $result['error']
            ];
        }
        
        $data = $result['data'];
        
        if (self::isTokenExpired($data)) {
            $newToken = self::refreshToken();
            if ($newToken) {
                $result = self::makeRequest($url, $newToken, $params);
                if ($result['success']) {
                    return $result['data'];
                }
            }
        }
        
        return $data;
    }
    
    public static function getGrades($token, $semester = '2025-2026-1', $type = '') {
        $url = 'https://jwh5.gdpnc.cn/njwhd/student/termGPA';
        $params = ['semester' => $semester];
        
        if ($type !== '') {
            $params['type'] = $type;
        }
        
        return self::requestWithRetry($url, $token, $params);
    }
    
    public static function getSchedule($token, $week = '', $kbjcmsid = '') {
        $url = 'https://jwh5.gdpnc.cn/njwhd/student/curriculum';
        $params = [];
        
        if ($week !== '') {
            $params['week'] = $week;
        }
        if ($kbjcmsid !== '') {
            $params['kbjcmsid'] = $kbjcmsid;
        }
        
        return self::requestWithRetry($url, $token, $params);
    }
    
    public static function getStudentInfo($token) {
        $url = 'https://jwh5.gdpnc.cn/njwhd/student/xsXjxx_ck';
        return self::requestWithRetry($url, $token);
    }
    
    public static function getSemesterList($token) {
        $url = 'https://jwh5.gdpnc.cn/njwhd/semesterList';
        return self::requestWithRetry($url, $token);
    }
    
    public static function getScheduleMode($token) {
        $url = 'https://jwh5.gdpnc.cn/njwhd/Get_sjkbms';
        return self::requestWithRetry($url, $token);
    }
}
