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
    public static function chat(array $messages, array $opts = [])
    {
        $cfg = self::getConfig();

        if ($cfg['token'] === '' || $cfg['base_url'] === '' || $cfg['model'] === '') {
            return ['success' => false, 'msg' => 'AI 未配置：请在后台「AI 配置」中填写 Token、Base URL 与模型'];
        }

        $url = rtrim($cfg['base_url'], '/') . '/chat/completions';
        $payload = [
            'model'       => $cfg['model'],
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
                return ['success' => false, 'msg' => 'AI 返回内容为空（可能是额度或网络波动），请稍后重试'];
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
    public static function chatStream(array $messages, array $opts = [], $onChunk = null)
    {
        if (!is_callable($onChunk)) {
            return ['success' => false, 'msg' => '缺少流式回调'];
        }
        $cfg = self::getConfig();
        if ($cfg['token'] === '' || $cfg['base_url'] === '' || $cfg['model'] === '') {
            return ['success' => false, 'msg' => 'AI 未配置：请在后台「AI 配置」中填写 Token、Base URL 与模型'];
        }

        $url = rtrim($cfg['base_url'], '/') . '/chat/completions';
        $payload = [
            'model'       => $cfg['model'],
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
}
