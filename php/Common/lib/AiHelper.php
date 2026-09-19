<?php
namespace lib;

/**
 * AI 接口封装（兼容 OpenAI / Sensenova 等 /v1/chat/completions 接口）
 *
 * 系统使用同一套配置（存于 nteam_config）：AI_Token / AI_BaseURL / AI_Model
 */
class AiHelper
{
    /**
     * 获取 AI 配置
     * @return array{token:string,base_url:string,model:string}
     */
    public static function getConfig()
    {
        return [
            'token'    => trim((string)\conf('AI_Token')),
            'base_url' => trim((string)\conf('AI_BaseURL')),
            'model'    => trim((string)\conf('AI_Model')),
        ];
    }

    /**
     * 调用兼容 OpenAI /chat/completions 的接口
     * @param array $messages OpenAI 格式消息数组
     * @param array $opts     ['temperature'=>float,'max_tokens'=>int]
     * @return array{success:bool,content?:string,msg?:string}
     */
    public static function chat(array $messages, array $opts = [], $model = null)
    {
        $cfg = self::getConfig();

        $useModel = $model ? $model : $cfg['model'];
        if ($cfg['token'] === '' || $cfg['base_url'] === '' || $useModel === '') {
            return ['success' => false, 'msg' => 'AI 未配置：请在后台「AI 配置」中填写 Token、Base URL 与模型'];
        }

        $url = rtrim($cfg['base_url'], '/') . '/chat/completions';
        $payload = [
            'model'       => $useModel,
            'messages'    => $messages,
            'temperature' => isset($opts['temperature']) ? floatval($opts['temperature']) : 0.7,
            'max_tokens'  => isset($opts['max_tokens']) ? intval($opts['max_tokens']) : 1500,
        ];
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $cfg['token'],
            'Accept: application/json',
        ];

        // 对额度超限(429)/网络波动/空返回做有限次重试，缓解偶发限流导致的“分析结果为空”
        $maxRetries = 2;
        $retryDelay = 2;

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 90,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr  = curl_error($ch);
            curl_close($ch);

            if ($response === false) {
                if ($attempt < $maxRetries) {
                    sleep($retryDelay);
                    continue;
                }
                return ['success' => false, 'msg' => '请求 AI 接口失败：' . $curlErr];
            }

            $data = json_decode($response, true);
            if (!is_array($data) || !isset($data['choices'][0]['message']['content'])) {
                $err = isset($data['error']['message']) ? $data['error']['message'] : $response;
                // 额度/限流/超时类错误可重试
                if ($attempt < $maxRetries
                    && (stripos($err, '429') !== false
                        || stripos($err, 'rate') !== false
                        || stripos($err, 'quota') !== false
                        || stripos($err, 'timeout') !== false
                        || $httpCode == 429)) {
                    sleep($retryDelay);
                    continue;
                }
                return ['success' => false, 'msg' => 'AI 返回异常(' . $httpCode . ')：' . $err];
            }

            $content = trim($data['choices'][0]['message']['content']);
            if ($content === '') {
                // 空内容通常也是额度/限流造成的“软失败”，重试一次
                if ($attempt < $maxRetries) {
                    sleep($retryDelay);
                    continue;
                }
                return ['success' => false, 'msg' => 'AI 连接超时（可能是网络波动），请稍后重试'];
            }

            return ['success' => true, 'content' => $content];
        }

        return ['success' => false, 'msg' => 'AI 请求失败（已重试，仍无有效结果）'];
    }

    /**
     * 流式调用（SSE），逐块通过 $onChunk(string) 回调返回增量内容
     * @param array $messages
     * @param array $opts
     * @param callable $onChunk
     * @return array{success:bool,msg?:string}
     */
    public static function chatStream(array $messages, array $opts = [], $onChunk = null, $model = null)
    {
        if (!is_callable($onChunk)) {
            return ['success' => false, 'msg' => '缺少流式回调'];
        }
        $cfg = self::getConfig();

        $useModel = $model ? $model : $cfg['model'];
        if ($cfg['token'] === '' || $cfg['base_url'] === '' || $useModel === '') {
            return ['success' => false, 'msg' => 'AI 接口未配置：请联系管理员处理'];
        }

        $url = rtrim($cfg['base_url'], '/') . '/chat/completions';
        $payload = [
            'model'       => $useModel,
            'messages'    => $messages,
            'temperature' => isset($opts['temperature']) ? floatval($opts['temperature']) : 0.7,
            'max_tokens'  => isset($opts['max_tokens']) ? intval($opts['max_tokens']) : 2000,
            'stream'      => true,
        ];
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $cfg['token'],
            'Accept: text/event-stream',
        ];

        $maxRetries = 2;
        $retryDelay = 3;

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            $fullBuf = '';
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
                CURLOPT_TIMEOUT        => 180,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_WRITEFUNCTION  => function ($ch, $data) use ($onChunk, &$fullBuf) {
                    $fullBuf .= $data;
                    while (($pos = strpos($fullBuf, "\n")) !== false) {
                        $line = trim(substr($fullBuf, 0, $pos));
                        $fullBuf = substr($fullBuf, $pos + 1);
                        if ($line === '') continue;
                        if (strpos($line, 'data:') === 0) {
                            $json = trim(substr($line, 5));
                            if ($json === '[DONE]') continue;
                            $obj = json_decode($json, true);
                            if ($obj && isset($obj['choices'][0]['delta']['content'])) {
                                $onChunk($obj['choices'][0]['delta']['content']);
                            }
                        } elseif (isset($line[0]) && $line[0] === '{') {
                            // 兼容非流式完整 JSON 兜底
                            $obj = json_decode($line, true);
                            if ($obj && isset($obj['choices'][0]['message']['content'])) {
                                $onChunk($obj['choices'][0]['message']['content']);
                            }
                        }
                    }
                    return strlen($data);
                },
            ]);
            curl_exec($ch);
            $curlErr = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($curlErr !== '') {
                return ['success' => false, 'msg' => '请求 AI 接口失败：' . $curlErr];
            }
            if ($httpCode === 200) {
                return ['success' => true];
            }
            // 额度/限流类错误可重试
            if ($httpCode == 429 && $attempt < $maxRetries) {
                sleep($retryDelay);
                continue;
            }
            return ['success' => false, 'msg' => 'AI 接口返回 HTTP ' . $httpCode];
        }

        return ['success' => false, 'msg' => 'AI 请求失败（已重试，仍无有效结果）'];
    }

    /**
     * 获取提供方实际支持的模型列表（OpenAI 兼容 /v1/models）
     * @return array{success:bool,models?:array,msg?:string}
     */
    public static function listModels()
    {
        $cfg = self::getConfig();
        if ($cfg['token'] === '' || $cfg['base_url'] === '') {
            return ['success' => false, 'msg' => 'AI 接口未配置：请联系管理员处理'];
        }
        $url = rtrim($cfg['base_url'], '/') . '/models';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPGET         => true,
            CURLOPT_HTTPHEADER      => [
                'Authorization: Bearer ' . $cfg['token'],
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);
        if ($response === false) {
            return ['success' => false, 'msg' => '获取模型列表失败：' . $curlErr];
        }
        $data = json_decode($response, true);
        if (!is_array($data) || !isset($data['data']) || !is_array($data['data'])) {
            if (isset($data['models']) && is_array($data['models'])) {
                $data['data'] = $data['models'];
            } else {
                return ['success' => false, 'msg' => '该接口不支持 /models 列表'];
            }
        }
        $models = [];
        foreach ($data['data'] as $m) {
            $id = is_string($m) ? $m : (isset($m['id']) ? $m['id'] : (isset($m['name']) ? $m['name'] : ''));
            $id = trim((string)$id);
            if ($id !== '' && !in_array($id, $models, true)) {
                $models[] = $id;
            }
        }
        if (empty($models)) {
            return ['success' => false, 'msg' => '模型列表为空'];
        }
        return ['success' => true, 'models' => $models, 'default' => $cfg['model']];
    }

    /**
     * 带历史缓存的 AI 分析：优先命中缓存（相同用户 + 业务类型 + 数据指纹），
     * 命中则直接返回，避免重复消耗 AI 额度；未命中才真正调用模型并写回缓存。
     * 与成绩分析（nteam_ai_grade_analysis）思路一致，统一复用同一张缓存表。
     * @param \lib\PdoHelper $DB
     * @param int    $userId
     * @param string $bizType  业务类型标识（如 power_query / power_statistics / schedule_semester / schedule_today）
     * @param string $dataHash 数据指纹（如 md5(json)），用于判断数据是否变化
     * @param array  $messages OpenAI 格式消息
     * @return array{success:bool,content?:string,cached?:int,msg?:string}
     */
    public static function analyzeCached($DB, $userId, $bizType, $dataHash, array $messages)
    {
        $DB->exec("CREATE TABLE IF NOT EXISTS nteam_ai_analysis_cache (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            biz_type VARCHAR(64) NOT NULL DEFAULT '',
            data_hash VARCHAR(64) NOT NULL DEFAULT '',
            content MEDIUMTEXT NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY uniq_user_biz_hash (user_id, biz_type, data_hash)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $userId   = intval($userId);
        $bizType  = strval($bizType);
        $dataHash = strval($dataHash);

        $cached = $DB->getRow(
            "SELECT content FROM nteam_ai_analysis_cache WHERE user_id=? AND biz_type=? AND data_hash=?",
            [$userId, $bizType, $dataHash]
        );
        if ($cached && trim($cached['content']) !== '' && $cached['content'] !== '暂无分析结果') {
            return ['success' => true, 'content' => $cached['content'], 'cached' => 1];
        }

        $result = self::chat($messages);
        if (!$result['success']) {
            return $result;
        }
        $content = isset($result['content']) ? trim($result['content']) : '';
        if ($content === '') {
            return ['success' => false, 'msg' => '分析结果为空，请重试'];
        }

        $now = date('Y-m-d H:i:s');
        if ($cached) {
            $DB->exec(
                "UPDATE nteam_ai_analysis_cache SET content=?, data_hash=?, updated_at=? WHERE user_id=? AND biz_type=? AND data_hash=?",
                [$content, $dataHash, $now, $userId, $bizType, $dataHash]
            );
        } else {
            $DB->exec(
                "INSERT INTO nteam_ai_analysis_cache (user_id, biz_type, data_hash, content, created_at, updated_at) VALUES (?,?,?,?,?,?)",
                [$userId, $bizType, $dataHash, $content, $now, $now]
            );
        }
        return ['success' => true, 'content' => $content, 'cached' => 0];
    }
}
