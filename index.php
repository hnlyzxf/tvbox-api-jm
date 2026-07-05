<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="keywords" content="TVBox 配置 解密 接口">
    <meta name="description" content="TVBox 配置接口解密工具">
    <title>TVBox 配置解密</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        :root {
            --color-blue: #0071e3;
            --color-blue-hover: #0076df;
            --color-blue-active: #006edb;
            --color-link: #0066cc;
            --color-text: #1d1d1f;
            --color-secondary: #6e6e73;
            --color-tertiary: #86868b;
            --color-bg: #f5f5f7;
            --color-card: #fff;
            --color-border: #d2d2d7;
            --color-success: #34c759;
            --color-error: #ff3b30;
            --radius-button: 980px;
            --radius-card: 18px;
            --content-width: 980px;
            --content-padding: 22px;
            --font: -apple-system, BlinkMacSystemFont, "SF Pro Text", "PingFang SC", "Helvetica Neue", Arial, sans-serif;
            --mono: "SF Mono", Menlo, Consolas, Monaco, monospace;
        }
        html { background: var(--color-bg); }
        body {
            margin: 0;
            color: var(--color-text);
            background: var(--color-bg);
            font-family: var(--font);
            font-size: 17px;
            line-height: 1.4705882353;
            letter-spacing: 0;
            -webkit-font-smoothing: antialiased;
        }
        button, input, textarea { font: inherit; letter-spacing: 0; }
        .localnav {
            position: sticky;
            top: 0;
            z-index: 10;
            height: 52px;
            background: rgba(255, 255, 255, 0.84);
            border-bottom: 1px solid rgba(210, 210, 215, 0.78);
            backdrop-filter: saturate(180%) blur(20px);
            -webkit-backdrop-filter: saturate(180%) blur(20px);
        }
        .localnav-content {
            max-width: var(--content-width);
            height: 100%;
            margin: 0 auto;
            padding: 0 var(--content-padding);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
        }
        .localnav-title {
            font-size: 21px;
            font-weight: 600;
            white-space: nowrap;
        }
        .localnav-status {
            min-width: 0;
            color: var(--color-secondary);
            font-size: 12px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        main {
            max-width: var(--content-width);
            margin: 0 auto;
            padding: 48px var(--content-padding) 56px;
        }
        .workspace {
            display: grid;
            grid-template-columns: minmax(280px, 360px) minmax(0, 1fr);
            gap: 24px;
            align-items: start;
        }
        .panel {
            background: var(--color-card);
            border-radius: var(--radius-card);
            border: 1px solid rgba(210, 210, 215, 0.7);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        }
        .control-panel {
            padding: 24px;
            position: sticky;
            top: 76px;
        }
        .result-panel {
            min-width: 0;
            overflow: hidden;
        }
        .panel-title {
            margin: 0 0 6px;
            font-size: 28px;
            font-weight: 600;
            line-height: 1.14;
        }
        .panel-subtitle {
            margin: 0 0 24px;
            color: var(--color-secondary);
            font-size: 14px;
            line-height: 1.45;
        }
        .field { margin-bottom: 18px; }
        label {
            display: block;
            margin: 0 0 7px;
            color: var(--color-secondary);
            font-size: 12px;
            font-weight: 600;
        }
        input[type="url"] {
            width: 100%;
            min-height: 46px;
            border: 1px solid var(--color-border);
            border-radius: 12px;
            padding: 10px 13px;
            color: var(--color-text);
            background: #fff;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        input[type="url"]:focus {
            border-color: var(--color-blue);
            box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.14);
        }
        .segmented {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
        }
        .segment input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        .segment span {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            min-height: 42px;
            border: 1px solid var(--color-border);
            border-radius: 12px;
            padding: 10px 12px;
            background: #fff;
            color: var(--color-text);
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s, color 0.2s, box-shadow 0.2s;
        }
        .segment span::after {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #c7c7cc;
            flex: 0 0 auto;
        }
        .segment input:checked + span {
            border-color: var(--color-blue);
            background: rgba(0, 113, 227, 0.08);
            box-shadow: inset 0 0 0 1px var(--color-blue);
        }
        .segment input:checked + span::after { background: var(--color-blue); }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 22px;
        }
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 60px;
            min-height: 40px;
            padding: 9px 16px;
            border-radius: var(--radius-button);
            border: 1px solid transparent;
            background: transparent;
            color: var(--color-link);
            text-decoration: none;
            font-size: 14px;
            line-height: 1.28577;
            cursor: pointer;
            transition: background 0.2s, color 0.2s, border-color 0.2s, opacity 0.2s;
            white-space: nowrap;
        }
        .button-primary {
            background: var(--color-blue);
            color: #fff;
        }
        .button-primary:hover { background: var(--color-blue-hover); }
        .button-primary:active { background: var(--color-blue-active); }
        .button-neutral {
            background: #1d1d1f;
            color: #fff;
        }
        .button-neutral:hover { background: #272729; }
        .button-secondary:hover { color: var(--color-blue-hover); }
        .button-outline {
            color: var(--color-text);
            border-color: var(--color-text);
        }
        .button-outline:hover {
            background: var(--color-text);
            color: #fff;
        }
        .button:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }
        .result-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 18px;
            border-bottom: 1px solid rgba(210, 210, 215, 0.7);
        }
        .result-title {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: var(--color-secondary);
        }
        .result-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 8px;
        }
        textarea {
            display: block;
            width: 100%;
            min-height: 500px;
            resize: vertical;
            border: 0;
            outline: none;
            padding: 18px;
            color: #f5f5f7;
            background: #161617;
            font-family: var(--mono);
            font-size: 13px;
            line-height: 1.55;
        }
        .meta {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-top: 18px;
        }
        .stat {
            min-width: 0;
            padding: 12px;
            border: 1px solid rgba(210, 210, 215, 0.8);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.72);
        }
        .stat span {
            display: block;
            color: var(--color-secondary);
            font-size: 11px;
            font-weight: 600;
        }
        .stat strong {
            display: block;
            margin-top: 5px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 14px;
        }
        .status-ok { color: var(--color-success); }
        .status-bad { color: var(--color-error); }
        .footer {
            max-width: var(--content-width);
            margin: 0 auto;
            padding: 0 var(--content-padding) 32px;
            color: var(--color-secondary);
            font-size: 12px;
            line-height: 1.33337;
        }
        .footer-inner {
            border-top: 1px solid var(--color-border);
            padding-top: 18px;
            text-align: center;
        }
        .footer p { margin: 0; }
        .footer a {
            color: var(--color-link);
            text-decoration: none;
        }
        .footer a:hover { color: var(--color-blue-hover); }
        @media (max-width: 880px) {
            main { padding-top: 28px; }
            .workspace { grid-template-columns: 1fr; }
            .control-panel { position: static; }
            .meta { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 540px) {
            .localnav-content { padding: 0 16px; }
            .localnav-status { display: none; }
            main { padding-left: 16px; padding-right: 16px; }
            .result-toolbar { align-items: flex-start; flex-direction: column; }
            .result-actions, .actions, .button { width: 100%; }
            .meta { grid-template-columns: 1fr; }
            textarea { min-height: 420px; }
        }
    </style>
</head>
<body>
<nav class="localnav">
    <div class="localnav-content">
        <div class="localnav-title">TVBox 配置解密</div>
        <div class="localnav-status" id="topStatus">等待输入</div>
    </div>
</nav>

<main>
    <div class="workspace">
        <section class="panel control-panel">
            <h1 class="panel-title">解密控制台</h1>
            <p class="panel-subtitle">选择处理方式，填入配置地址后开始解析。</p>

            <div class="field">
                <label for="url">配置地址</label>
                <input id="url" type="url" inputmode="url" autocomplete="off" placeholder="https://...">
            </div>

            <div class="field">
                <label>解密方式</label>
                <div class="segmented" role="radiogroup" aria-label="解密方式">
                    <label class="segment">
                        <input type="radio" name="method" value="local" checked>
                        <span>本地自动解密</span>
                    </label>
                    <label class="segment">
                        <input type="radio" name="method" value="fongmi">
                        <span>FongMi 接口解密</span>
                    </label>
                    <label class="segment">
                        <input type="radio" name="method" value="fantaiying">
                        <span>饭太硬接口解密</span>
                    </label>
                </div>
            </div>

            <div class="actions">
                <button class="button button-primary" id="decode" type="button">开始解密</button>
                <a class="button button-outline" id="direct" target="_blank" rel="noopener" href="#">纯输出</a>
            </div>

            <div class="meta" aria-live="polite">
                <div class="stat"><span>方式</span><strong id="decodeType">-</strong></div>
                <div class="stat"><span>HTTP</span><strong id="httpStatus">-</strong></div>
                <div class="stat"><span>JSON</span><strong id="jsonType">-</strong></div>
                <div class="stat"><span>数量</span><strong id="counts">-</strong></div>
            </div>
        </section>

        <section class="panel result-panel">
            <div class="result-toolbar">
                <h2 class="result-title">解密结果</h2>
                <div class="result-actions">
                    <button class="button button-secondary" id="copy" type="button">复制</button>
                    <button class="button button-secondary" id="downloadResult" type="button">下载结果</button>
                    <button class="button button-neutral" id="downloadJar" type="button">下载 Jar</button>
                </div>
            </div>
            <textarea id="output" spellcheck="false" readonly></textarea>
        </section>
    </div>
</main>

<footer class="footer">
    <div class="footer-inner">
        <p>Copyright © 2026 <a href="https://github.com/hnlyzxf" target="_blank" rel="noopener noreferrer">木子白白白</a>. All rights reserved.</p>
    </div>
</footer>

<script>
const urlInput = document.getElementById('url');
const output = document.getElementById('output');
const direct = document.getElementById('direct');
const topStatus = document.getElementById('topStatus');

function selectedMethod() {
    return document.querySelector('input[name="method"]:checked').value;
}

function endpointUrl(meta = false) {
    const url = urlInput.value.trim();
    const method = selectedMethod();
    const params = new URLSearchParams({url});

    if (meta) params.set('meta', '1');
    if (method === 'local') {
        return 'local.php?' + params.toString();
    }

    params.set('action', 'crawl');
    params.set('interface', method);
    return 'api.php?' + params.toString();
}

function setStatus(message, type = '') {
    topStatus.textContent = message;
    topStatus.className = 'localnav-status' + (type ? ' status-' + type : '');
}

function setMeta(result) {
    document.getElementById('decodeType').textContent = result.decode_type || result.interface || '-';
    document.getElementById('httpStatus').textContent = result.status || '-';
    const json = result.json || {};
    document.getElementById('jsonType').textContent = json.valid ? json.type : '非标准 JSON';
    document.getElementById('counts').textContent = json.valid
        ? `sites ${json.sites || 0} / parses ${json.parses || 0} / lives ${json.lives || 0} / urls ${json.urls || 0}`
        : '-';
}

function resetMeta() {
    setMeta({json: {valid: false}});
    document.getElementById('httpStatus').textContent = '-';
}

function updateDirect() {
    direct.href = urlInput.value.trim() ? endpointUrl(false) : '#';
}

async function decode() {
    const url = urlInput.value.trim();
    if (!url) {
        setStatus('请输入配置地址', 'bad');
        urlInput.focus();
        return;
    }

    updateDirect();
    resetMeta();
    output.value = '';
    setStatus('正在解密...');
    document.getElementById('decode').disabled = true;

    try {
        const response = await fetch(endpointUrl(false), {cache: 'no-store'});
        const text = await response.text();
        output.value = text;

        if (!response.ok) {
            setStatus('解密失败', 'bad');
            return;
        }

        const metaResponse = await fetch(endpointUrl(true), {cache: 'no-store'});
        if (metaResponse.ok) {
            setMeta(await metaResponse.json());
        }
        setStatus('完成', 'ok');
    } catch (error) {
        setStatus(error.message || '请求异常', 'bad');
    } finally {
        document.getElementById('decode').disabled = false;
    }
}

async function copyResult() {
    if (!output.value) {
        setStatus('没有可复制内容', 'bad');
        return;
    }

    try {
        await navigator.clipboard.writeText(output.value);
        setStatus('已复制', 'ok');
    } catch (error) {
        output.select();
        document.execCommand('copy');
        setStatus('已复制', 'ok');
    }
}

function downloadResult() {
    if (!output.value) {
        setStatus('没有可下载内容', 'bad');
        return;
    }

    let extension = 'txt';
    let mime = 'text/plain;charset=utf-8';
    try {
        JSON.parse(output.value);
        extension = 'json';
        mime = 'application/json;charset=utf-8';
    } catch (error) {
        extension = 'txt';
    }

    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const filename = `tvbox-${selectedMethod()}-${timestamp}.${extension}`;
    const blob = new Blob([output.value], {type: mime});
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    setStatus('结果已下载', 'ok');
}

function downloadJar() {
    const match = output.value.match(/"spider"\s*:\s*"([^"]+)"/);
    if (!match || !match[1]) {
        setStatus('未找到 spider 字段', 'bad');
        return;
    }

    const params = new URLSearchParams({
        action: 'download_jar',
        url: match[1]
    });
    window.location.href = 'api.php?' + params.toString();
}

document.getElementById('decode').addEventListener('click', decode);
document.getElementById('copy').addEventListener('click', copyResult);
document.getElementById('downloadResult').addEventListener('click', downloadResult);
document.getElementById('downloadJar').addEventListener('click', downloadJar);
document.querySelectorAll('input[name="method"]').forEach(input => input.addEventListener('change', updateDirect));
urlInput.addEventListener('input', updateDirect);
urlInput.addEventListener('keydown', event => {
    if (event.key === 'Enter') decode();
});
updateDirect();
</script>
</body>
</html>
