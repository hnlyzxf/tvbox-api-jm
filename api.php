<?php

class InterfaceDecoder
{
    private $interfaces = array(
        'fongmi' => array(
            'name' => 'FongMi',
            'url' => 'https://ua.fongmi.eu.org/box.php?url=',
        ),
        'fantaiying' => array(
            'name' => '饭太硬',
            'url' => 'https://www.xn--sss604efuw.cc/jm/jiemi.php?url=',
        ),
    );

    public function decode($targetUrl, $interface)
    {
        if (!isset($this->interfaces[$interface])) {
            return array('ok' => false, 'error' => 'Unknown interface.', 'status' => 0);
        }

        $apiUrl = $this->interfaces[$interface]['url'] . urlencode($targetUrl);
        $fetch = $this->fetch($apiUrl);
        if (!$fetch['ok']) {
            return array(
                'ok' => false,
                'error' => $fetch['error'],
                'interface' => $this->interfaces[$interface]['name'],
                'request_url' => $apiUrl,
                'status' => isset($fetch['status']) ? $fetch['status'] : 0,
            );
        }

        $content = $this->trimJsonNoise($this->toUtf8($fetch['body']));

        return array(
            'ok' => true,
            'interface' => $this->interfaces[$interface]['name'],
            'decode_type' => 'interface-' . $interface,
            'request_url' => $apiUrl,
            'status' => $fetch['status'],
            'content_type' => $fetch['content_type'],
            'length' => strlen($content),
            'json' => $this->inspectJson($content),
            'content' => $content,
        );
    }

    public function downloadJar($fileUrl)
    {
        if (strpos($fileUrl, ';md5;') !== false) {
            $parts = explode(';md5;', $fileUrl, 2);
            $fileUrl = $parts[0];
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="spider.jar"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        $ch = curl_init($fileUrl);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'okhttp/3.12.0',
        ));
        curl_exec($ch);
        curl_close($ch);
        exit;
    }

    private function fetch($url)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_ENCODING => '',
            CURLOPT_USERAGENT => 'okhttp/3.12.0',
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
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($body === false) {
            return array('ok' => false, 'error' => $error ?: 'Request failed.', 'status' => $status);
        }

        if ($status >= 400) {
            return array('ok' => false, 'error' => 'Remote returned HTTP ' . $status, 'status' => $status);
        }

        return array(
            'ok' => true,
            'status' => $status,
            'content_type' => $contentType,
            'body' => $body,
        );
    }

    private function inspectJson($content)
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

    private function trimJsonNoise($content)
    {
        $firstBrace = strpos($content, '{');
        $firstBracket = strpos($content, '[');
        if ($firstBrace === false && $firstBracket === false) {
            return trim($content);
        }

        if ($firstBrace !== false && $firstBracket !== false) {
            $start = min($firstBrace, $firstBracket);
        } else {
            $start = $firstBrace !== false ? $firstBrace : $firstBracket;
        }

        return trim(substr($content, $start));
    }

    private function cleanJsonText($content)
    {
        $clean = trim($content);
        $clean = preg_replace('/^\xEF\xBB\xBF/', '', $clean);
        $clean = preg_replace('/^\s*\/\/[^\r\n]*(\r?\n)/m', '', $clean);
        $clean = preg_replace('/,\s*([}\]])/', '$1', $clean);
        return trim($clean);
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
}

function jsonResponse($payload, $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

$action = isset($_GET['action']) ? trim($_GET['action']) : '';
$decoder = new InterfaceDecoder();

if ($action === 'download_jar') {
    $fileUrl = isset($_GET['url']) ? trim($_GET['url']) : '';
    if ($fileUrl === '') {
        jsonResponse(array('ok' => false, 'error' => 'Missing url parameter.'), 400);
    }
    $decoder->downloadJar($fileUrl);
}

if ($action !== 'crawl') {
    jsonResponse(array('ok' => false, 'error' => 'Unknown action.'), 400);
}

$targetUrl = isset($_GET['url']) ? trim($_GET['url']) : '';
$interface = isset($_GET['interface']) ? trim($_GET['interface']) : '';

if ($targetUrl === '') {
    jsonResponse(array('ok' => false, 'error' => 'Missing url parameter.'), 400);
}

$result = $decoder->decode($targetUrl, $interface);
if (!$result['ok']) {
    jsonResponse($result, 502);
}

if (isset($_GET['meta'])) {
    $meta = $result;
    unset($meta['content']);
    jsonResponse($meta);
}

$isJson = isset($result['json']['valid']) && $result['json']['valid'];
header('X-TVBox-Decode-Type: ' . $result['decode_type']);
header('Content-Type: ' . ($isJson ? 'application/json' : 'text/plain') . '; charset=utf-8');
echo $result['content'];
