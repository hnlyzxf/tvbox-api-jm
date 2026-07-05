<?php

class TvboxDecoder
{
    private $defaultUserAgent = 'okhttp/3.15';

    public function fetchAndDecode($url, $userAgent = null)
    {
        $fetch = $this->fetch($url, $userAgent ?: $this->defaultUserAgent);
        if (!$fetch['ok']) {
            return array(
                'ok' => false,
                'error' => $fetch['error'],
                'url' => $url,
                'final_url' => isset($fetch['final_url']) ? $fetch['final_url'] : null,
                'status' => isset($fetch['status']) ? $fetch['status'] : 0,
            );
        }

        $decoded = $this->decodeContent($fetch['body']);
        $content = $decoded['content'];
        $jsonInfo = $this->inspectJson($content);

        return array(
            'ok' => true,
            'url' => $url,
            'request_url' => $fetch['request_url'],
            'final_url' => $fetch['final_url'],
            'status' => $fetch['status'],
            'content_type' => $fetch['content_type'],
            'decode_type' => $decoded['type'],
            'decode_notes' => $decoded['notes'],
            'key' => isset($decoded['key']) ? $decoded['key'] : null,
            'iv' => isset($decoded['iv']) ? $decoded['iv'] : null,
            'length' => strlen($content),
            'json' => $jsonInfo,
            'content' => $content,
        );
    }

    public function decodeContent($body, $depth = 0)
    {
        if ($depth > 3) {
            return array('type' => 'plain', 'notes' => array('decode depth limit'), 'content' => $this->toUtf8($body));
        }

        $imagePayload = $this->extractImagePayload($body);
        if ($imagePayload !== null) {
            $nested = $this->decodeContent($imagePayload, $depth + 1);
            array_unshift($nested['notes'], 'extracted payload after image eoi');
            if ($nested['type'] === 'plain') {
                $nested['type'] = 'image-base64';
            }
            return $nested;
        }

        $text = trim($this->toUtf8($body));
        if ($this->isTvboxEncrypted($text)) {
            return $this->decryptTvboxHex($text);
        }

        $base64Payload = $this->tryBase64Payload($text);
        if ($base64Payload !== null) {
            $nested = $this->decodeContent($base64Payload, $depth + 1);
            array_unshift($nested['notes'], 'decoded base64 payload');
            if ($nested['type'] === 'plain') {
                $nested['type'] = 'base64';
            }
            return $nested;
        }

        return array('type' => 'plain', 'notes' => array(), 'content' => $text);
    }

    public function decryptTvboxHex($hex)
    {
        $hex = preg_replace('/\s+/', '', $hex);
        $start = strpos($hex, '2423');
        $split = strpos($hex, '2324', $start === false ? 0 : $start + 4);

        if ($start === false || $split === false) {
            throw new RuntimeException('Invalid TVBox encrypted format.');
        }

        $keyHex = substr($hex, $start + 4, $split - $start - 4);
        $ivHex = substr($hex, -26);
        $dataHex = substr($hex, $split + 4, strlen($hex) - ($split + 4) - 26);

        $key = $this->hexToString($keyHex);
        $iv = $this->hexToString($ivHex);
        $cipher = @hex2bin($dataHex);
        if ($cipher === false || $cipher === '') {
            throw new RuntimeException('Invalid encrypted payload.');
        }

        $paddedKey = $this->rightPadBytes($key, '0', 16);
        $paddedIv = $this->rightPadBytes($iv, '0', 16);

        $plain = openssl_decrypt($cipher, 'AES-128-CBC', $paddedKey, OPENSSL_RAW_DATA, $paddedIv);
        if ($plain === false) {
            $raw = openssl_decrypt($cipher, 'AES-128-CBC', $paddedKey, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $paddedIv);
            if ($raw === false) {
                throw new RuntimeException('AES decrypt failed.');
            }
            $plain = $this->removePkcs7Padding($raw);
        }

        return array(
            'type' => 'tvbox-aes-128-cbc',
            'notes' => array(),
            'key' => $key,
            'iv' => $iv,
            'content' => trim($this->toUtf8($plain)),
        );
    }

    public function fetch($url, $userAgent)
    {
        $url = trim($url);
        if ($url === '') {
            return array('ok' => false, 'error' => 'URL is required.');
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'http://' . $url;
        }

        $requestUrl = $this->normalizeIdnUrl($url);
        $ch = curl_init($requestUrl);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_ENCODING => '',
            CURLOPT_USERAGENT => $userAgent,
            CURLOPT_HTTPHEADER => array(
                'Accept: */*',
                'Connection: keep-alive',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
            ),
        ));

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($body === false) {
            return array(
                'ok' => false,
                'error' => $error ?: 'Request failed.',
                'request_url' => $requestUrl,
                'final_url' => $finalUrl,
                'status' => $status,
            );
        }

        if ($status >= 400) {
            return array(
                'ok' => false,
                'error' => 'Remote returned HTTP ' . $status,
                'request_url' => $requestUrl,
                'final_url' => $finalUrl,
                'status' => $status,
            );
        }

        return array(
            'ok' => true,
            'request_url' => $requestUrl,
            'final_url' => $finalUrl,
            'status' => $status,
            'content_type' => $contentType,
            'body' => $body,
        );
    }

    public function inspectJson($content)
    {
        $clean = $this->cleanJsonText($content);
        $data = json_decode($clean, true);
        if (!is_array($data)) {
            return array('valid' => false, 'error' => json_last_error_msg());
        }

        return array(
            'valid' => true,
            'type' => isset($data['urls']) ? 'warehouse' : 'config',
            'sites' => isset($data['sites']) && is_array($data['sites']) ? count($data['sites']) : 0,
            'parses' => isset($data['parses']) && is_array($data['parses']) ? count($data['parses']) : 0,
            'lives' => isset($data['lives']) && is_array($data['lives']) ? count($data['lives']) : 0,
            'urls' => isset($data['urls']) && is_array($data['urls']) ? count($data['urls']) : 0,
        );
    }

    private function extractImagePayload($body)
    {
        $jpegEnd = strrpos($body, "\xFF\xD9");
        if ($jpegEnd === false) {
            return null;
        }

        $tail = trim(substr($body, $jpegEnd + 2));
        if ($tail === '') {
            return null;
        }

        $marker = strpos($tail, '**');
        if ($marker !== false) {
            $tail = substr($tail, $marker + 2);
        }

        return $this->base64DecodeLoose($tail);
    }

    private function tryBase64Payload($text)
    {
        $candidate = $text;
        if (stripos($candidate, 'base64://') === 0) {
            $candidate = substr($candidate, 9);
        }
        if (strlen($candidate) < 24) {
            return null;
        }

        $decoded = $this->base64DecodeLoose($candidate);
        if ($decoded === null) {
            return null;
        }

        $peek = ltrim($this->toUtf8($decoded));
        if ($this->looksLikeConfig($peek) || $this->isTvboxEncrypted($peek)) {
            return $decoded;
        }

        return null;
    }

    private function base64DecodeLoose($text)
    {
        $candidate = preg_replace('/\s+/', '', $text);
        if ($candidate === '' || preg_match('/[^A-Za-z0-9+\/=_-]/', $candidate)) {
            return null;
        }

        $candidate = strtr($candidate, '-_', '+/');
        $mod = strlen($candidate) % 4;
        if ($mod > 0) {
            $candidate .= str_repeat('=', 4 - $mod);
        }

        $decoded = base64_decode($candidate, true);
        return $decoded === false ? null : $decoded;
    }

    private function isTvboxEncrypted($text)
    {
        $hex = preg_replace('/\s+/', '', $text);
        return strlen($hex) > 64
            && strpos($hex, '2423') === 0
            && strpos($hex, '2324') !== false
            && preg_match('/^[0-9a-fA-F]+$/', $hex);
    }

    private function looksLikeConfig($text)
    {
        $text = ltrim($text);
        return $text !== '' && ($text[0] === '{' || $text[0] === '[');
    }

    private function toUtf8($text)
    {
        if ($text === '') {
            return $text;
        }
        if (function_exists('mb_check_encoding') && mb_check_encoding($text, 'UTF-8')) {
            return $text;
        }

        if (function_exists('mb_convert_encoding')) {
            $converted = @mb_convert_encoding($text, 'UTF-8', 'GB18030,GBK,GB2312,BIG5,UTF-8');
            if ($converted !== false && $converted !== '') {
                return $converted;
            }
        }

        $converted = @iconv('GB18030', 'UTF-8//IGNORE', $text);
        return $converted === false ? $text : $converted;
    }

    private function rightPadBytes($text, $char, $length)
    {
        if (strlen($text) >= $length) {
            return substr($text, 0, $length);
        }
        return str_pad($text, $length, $char);
    }

    private function removePkcs7Padding($text)
    {
        $length = strlen($text);
        if ($length === 0) {
            return $text;
        }
        $pad = ord($text[$length - 1]);
        if ($pad < 1 || $pad > 16 || $pad > $length) {
            return $text;
        }
        if (substr($text, -$pad) !== str_repeat(chr($pad), $pad)) {
            return $text;
        }
        return substr($text, 0, $length - $pad);
    }

    private function hexToString($hex)
    {
        $bin = @hex2bin($hex);
        if ($bin === false) {
            throw new RuntimeException('Invalid hex string.');
        }
        return $bin;
    }

    private function normalizeIdnUrl($url)
    {
        $map = array(
            'www.饭太硬.cc' => 'www.xn--sss604efuw.cc',
            '饭太硬.cc' => 'xn--sss604efuw.cc',
            'www.饭太硬.net' => 'www.xn--sss604efuw.net',
            '饭太硬.net' => 'xn--sss604efuw.net',
            '肥猫.net' => 'xn--z7x900a.net',
            'www.肥猫.net' => 'www.xn--z7x900a.net',
        );

        return preg_replace_callback('~^(https?://)([^/:?#]+)(:\d+)?(.*)$~iu', function ($m) use ($map) {
            $host = $m[2];
            if (isset($map[$host])) {
                $host = $map[$host];
            }
            return $m[1] . $host . (isset($m[3]) ? $m[3] : '') . (isset($m[4]) ? $m[4] : '');
        }, $url);
    }

    private function cleanJsonText($content)
    {
        $clean = trim($content);
        $clean = preg_replace('/^\xEF\xBB\xBF/', '', $clean);
        $clean = preg_replace('/^\s*\/\/[^\r\n]*(\r?\n)/m', '', $clean);
        $clean = preg_replace('/,\s*([}\]])/', '$1', $clean);
        return trim($clean);
    }
}

function jsonResponse($payload, $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

$url = isset($_GET['url']) ? trim($_GET['url']) : '';
$ua = isset($_GET['ua']) ? trim($_GET['ua']) : null;

if ($url === '') {
    jsonResponse(array('ok' => false, 'error' => 'Missing url parameter.'), 400);
}

try {
    $decoder = new TvboxDecoder();
    $result = $decoder->fetchAndDecode($url, $ua);
    if (!$result['ok']) {
        jsonResponse($result, 502);
    }

    if (isset($_GET['meta'])) {
        $meta = $result;
        unset($meta['content']);
        jsonResponse($meta);
    }

    $content = $result['content'];
    $isJson = isset($result['json']['valid']) && $result['json']['valid'];
    header('X-TVBox-Decode-Type: ' . $result['decode_type']);
    header('Content-Type: ' . ($isJson ? 'application/json' : 'text/plain') . '; charset=utf-8');
    echo $content;
} catch (Throwable $e) {
    jsonResponse(array('ok' => false, 'error' => $e->getMessage()), 500);
}
