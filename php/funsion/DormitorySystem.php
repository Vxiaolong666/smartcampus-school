<?php

define('DORM_POWER_TOKEN', 'node01l4gc2e5yg5hw1bx0iaqhx5r439905');

class DormitorySystem {
    
    private static function makeRequest($url, $params = [], $method = 'GET') {
        $headers = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 MicroMessenger/7.0.20.1781(0x6700143B) NetType/WIFI MiniProgramEnv/Windows WindowsWechat/WMPF WindowsWechat(0x63090a13) UnifiedPCWindowsWechat(0xf2541917) XWEB/19749 miniProgram/wxd487794bc6372286',
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'X-Requested-With: XMLHttpRequest',
            'Origin: https://ykt.gdpnc.cn',
            'Sec-Fetch-Site: same-origin',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Dest: empty',
            'Accept-Language: zh-CN,zh;q=0.9',
        ];
        
        $ch = curl_init();
        
        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        
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
    
    public static function getAreas($pdo) {
        try {
            $stmt = $pdo->query("SELECT DISTINCT school_area FROM dorm_room ORDER BY school_area");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return [
                'code' => 1,
                'data' => array_column($data, 'school_area')
            ];
        } catch (PDOException $e) {
            return [
                'code' => 0,
                'msg' => '数据库查询失败：' . $e->getMessage()
            ];
        }
    }
    
    public static function getBuildings($pdo, $area) {
        try {
            $stmt = $pdo->prepare("SELECT DISTINCT building_no, building FROM dorm_room WHERE school_area = ? ORDER BY building_no");
            $stmt->execute([$area]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return [
                'code' => 1,
                'data' => $data
            ];
        } catch (PDOException $e) {
            return [
                'code' => 0,
                'msg' => '数据库查询失败：' . $e->getMessage()
            ];
        }
    }
    
    public static function getRooms($pdo, $area, $buildingNo = '') {
        try {
            $sql = "SELECT * FROM dorm_room WHERE school_area = ?";
            $params = [$area];
            
            if (!empty($buildingNo)) {
                $sql .= " AND building_no = ?";
                $params[] = $buildingNo;
            }
            
            $sql .= " ORDER BY building_no, room";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'code' => 1,
                'data' => $data
            ];
        } catch (PDOException $e) {
            return [
                'code' => 0,
                'msg' => '数据库查询失败：' . $e->getMessage()
            ];
        }
    }
    
    public static function searchRooms($pdo, $keyword) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM dorm_room WHERE room_num LIKE ? OR school_area LIKE ? OR building LIKE ? OR room LIKE ? LIMIT 50");
            $searchKeyword = "%{$keyword}%";
            $stmt->execute([$searchKeyword, $searchKeyword, $searchKeyword, $searchKeyword]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'code' => 1,
                'data' => $data
            ];
        } catch (PDOException $e) {
            return [
                'code' => 0,
                'msg' => '数据库查询失败：' . $e->getMessage()
            ];
        }
    }
    
    public static function getRoomInfo($pdo, $roomNum) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM dorm_room WHERE room_num = ? LIMIT 1");
            $stmt->execute([$roomNum]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                return [
                    'code' => 1,
                    'data' => $data
                ];
            } else {
                return [
                    'code' => 0,
                    'msg' => '宿舍不存在'
                ];
            }
        } catch (PDOException $e) {
            return [
                'code' => 0,
                'msg' => '数据库查询失败：' . $e->getMessage()
            ];
        }
    }
    
    public static function getPowerFee($roomNum, $schoolAreaNo = '', $buildingNo = '') {
        $apiUrl = 'https://ykt.gdpnc.cn/user/powerfee/getBalance';
        $implType = 'CGCOMMON0001';
        
        $postData = [
            'implType' => $implType,
            'roomNum' => $roomNum,
            'from' => 'wxminiprogram',
            'token' => DORM_POWER_TOKEN
        ];
        
        if ($schoolAreaNo !== '' && $schoolAreaNo !== null) {
            $postData['schoolAreaNo'] = $schoolAreaNo;
        }
        
        if ($buildingNo !== '' && $buildingNo !== null) {
            $postData['buildingNo'] = $buildingNo;
        }
        
        $result = self::makeRequest($apiUrl, $postData, 'POST');
        
        if (!$result['success']) {
            return [
                'code' => 0,
                'msg' => '请求失败：' . $result['error']
            ];
        }
        
        $data = $result['data'];
        
        if (isset($data['ret']) && $data['ret'] === true && isset($data['obj'])) {
            $obj = $data['obj'];
            $balance = $obj['powerBalance'] ?? $obj['balance'] ?? null;
            
            if ($balance !== null && is_numeric($balance)) {
                $balance = floatval($balance);
                
                if ($balance < 10) {
                    $status = 'low';
                    $msg = '电量不足，请及时充值';
                } elseif ($balance < 30) {
                    $status = 'warning';
                    $msg = '电量偏低';
                } else {
                    $status = 'normal';
                    $msg = '电量充足';
                }
                
                $result = [
                    'code' => 1,
                    'data' => [
                        'roomNum' => $roomNum,
                        'balance' => round($balance, 2),
                        'unit' => '度',
                        'status' => $status,
                        'msg' => $msg
                    ]
                ];
                
                if (isset($obj['waterBalance']) && $obj['waterBalance'] !== null) {
                    $result['data']['waterBalance'] = floatval($obj['waterBalance']);
                }
                if (isset($obj['hotWaterBalance']) && $obj['hotWaterBalance'] !== null) {
                    $result['data']['hotWaterBalance'] = floatval($obj['hotWaterBalance']);
                }
                
                return $result;
            }
        }
        
        if (isset($data['code']) && ($data['code'] == '203')) {
            return [
                'code' => -1,
                'msg' => '未登录或Token已过期'
            ];
        }
        
        return [
            'code' => 0,
            'msg' => $data['msg'] ?? $data['message'] ?? '无法获取电费余额'
        ];
    }
    
    public static function bindDormitory($DB, $userId, $roomNum, $alias = '', $isDefault = 0) {
        $existing = $DB->getRow("SELECT id FROM nteam_dormitory_bindings WHERE user_id='$userId' AND room_num='$roomNum' AND status=1");
        
        if ($existing) {
            return [
                'code' => 0,
                'msg' => '该宿舍已绑定'
            ];
        }
        
        $countRow = $DB->getRow("SELECT COUNT(*) as cnt FROM nteam_dormitory_bindings WHERE user_id='$userId' AND status=1");
        $count = $countRow ? $countRow['cnt'] : 0;
        
        if ($count >= 5) {
            return [
                'code' => 0,
                'msg' => '最多只能绑定5个宿舍'
            ];
        }
        
        try {
            $dbconfig = $GLOBALS['dbconfig'] ?? null;
            if (!$dbconfig) {
                global $dbconfig;
            }
            $pdo = new PDO("mysql:host={$dbconfig['host']};port={$dbconfig['port']};dbname={$dbconfig['dbname']};charset=utf8mb4", $dbconfig['user'], $dbconfig['pwd']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $roomInfo = self::getRoomInfo($pdo, $roomNum);
            
            if ($roomInfo['code'] != 1) {
                return $roomInfo;
            }
            
            $room = $roomInfo['data'];
            $schoolAreaNo = $room['school_area_no'] ?? '';
            $buildingNo = $room['building_no'] ?? '';
            $schoolArea = $room['school_area'] ?? '';
            $building = $room['building'] ?? '';
            $roomName = $room['room'] ?? '';
            
        } catch (PDOException $e) {
            return [
                'code' => 0,
                'msg' => '获取宿舍信息失败'
            ];
        }
        
        $alias = addslashes($alias);
        $now = date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO nteam_dormitory_bindings (user_id, room_num, school_area_no, building_no, school_area, building, room, alias, is_default, bind_time, status) 
                VALUES ('$userId', '$roomNum', '$schoolAreaNo', '$buildingNo', '$schoolArea', '$building', '$roomName', '$alias', '$isDefault', '$now', 1)";
        
        if ($DB->exec($sql)) {
            $lastId = $DB->lastInsertId();
            return [
                'code' => 1,
                'msg' => '绑定成功',
                'data' => [
                    'id' => $lastId,
                    'room_num' => $roomNum,
                    'school_area' => $schoolArea,
                    'building' => $building,
                    'room' => $roomName
                ]
            ];
        } else {
            return [
                'code' => 0,
                'msg' => '绑定失败'
            ];
        }
    }
    
    public static function unbindDormitory($DB, $userId, $bindingId) {
        $sql = "UPDATE nteam_dormitory_bindings SET status=0 WHERE id='$bindingId' AND user_id='$userId'";
        
        if ($DB->exec($sql)) {
            return [
                'code' => 1,
                'msg' => '解绑成功'
            ];
        } else {
            return [
                'code' => 0,
                'msg' => '解绑失败'
            ];
        }
    }
    
    public static function getUserDormitories($DB, $userId) {
        $dormitories = $DB->getAll("SELECT * FROM nteam_dormitory_bindings WHERE user_id='$userId' AND status=1 ORDER BY is_default DESC, bind_time DESC");
        
        return $dormitories ? $dormitories : [];
    }
    
    public static function setDefaultDormitory($DB, $userId, $bindingId) {
        $DB->exec("UPDATE nteam_dormitory_bindings SET is_default=0 WHERE user_id='$userId'");
        
        $sql = "UPDATE nteam_dormitory_bindings SET is_default=1 WHERE id='$bindingId' AND user_id='$userId'";
        
        if ($DB->exec($sql)) {
            return [
                'code' => 1,
                'msg' => '设置成功'
            ];
        } else {
            return [
                'code' => 0,
                'msg' => '设置失败'
            ];
        }
    }
    
    public static function updateDormitoryAlias($DB, $userId, $bindingId, $alias) {
        $alias = addslashes($alias);
        $sql = "UPDATE nteam_dormitory_bindings SET alias='$alias' WHERE id='$bindingId' AND user_id='$userId'";
        
        if ($DB->exec($sql)) {
            return [
                'code' => 1,
                'msg' => '更新成功'
            ];
        } else {
            return [
                'code' => 0,
                'msg' => '更新失败'
            ];
        }
    }
}
