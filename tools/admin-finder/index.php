<?php
error_reporting(0);
set_time_limit(0);

// Default Wordlist
$default_paths = "admin/\nadministrator/\nlogin/\nwp-login.php\nadmin.php\ncpanel/\ndashboard/\nuser/\nauth/\npanel/";

$results = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['target'])) {
    $target_input = trim($_POST['target']);
    // Pastikan URL berakhiran /
    if (substr($target_input, -1) !== '/') {
        $target_input .= '/';
    }

    $paths = explode("\n", str_replace("\r", "", $_POST['paths']));
    $delay = intval($_POST['delay'] ?? 0);

    // User Agents
    $user_agents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/115.0'
    ];

    foreach ($paths as $path) {
        $path = trim($path);
        if (empty($path)) continue;

        $full_url = $target_input . $path;
        
        // Delay logic
        if ($delay > 0) sleep($delay);

        $ch = curl_init($full_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agents[array_rand($user_agents)]);

        $startTime = microtime(true);
        curl_exec($ch);
        $endTime = microtime(true);
        $latency = round(($endTime - $startTime) * 1000);

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // without 404
        if ($http_code != 404 && $http_code != 0) {
            $status_type = 'UNKNOWN';
            if ($http_code == 200) $status_type = 'FOUND';
            if ($http_code == 403) $status_type = 'FORBIDDEN';
            if ($http_code >= 300 && $http_code < 400) $status_type = 'REDIRECT';
            if ($http_code >= 500) $status_type = 'SERVER ERR';

            $results[] = [
                'path' => $path,
                'url' => $full_url,
                'code' => $http_code,
                'type' => $status_type,
                'latency' => $latency
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>6ickzone // Admin Finder</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap');
        :root {
            --bg-color: #050505; --card-bg: #111111; --text-color: #e0e0e0;
            --accent-color: #bc13fe; --accent-glow: rgba(188, 19, 254, 0.4);
            --secondary-accent: #00f0ff; --danger-color: #ff2a6d; --success-color: #00ff41; --border-color: #333;
        }
        body { 
            font-family: 'JetBrains Mono', monospace; background: var(--bg-color); 
            background-image: radial-gradient(circle at 50% 0, #1a0b2e, #000000);
            color: var(--text-color); margin: 0; padding: 40px 20px; min-height: 100vh;
        }
        .container { max-width: 900px; margin: 0 auto; }
        .card { 
            background: rgba(17, 17, 17, 0.95); padding: 30px; border: 1px solid #222; 
            border-radius: 12px; box-shadow: 0 0 30px rgba(0,0,0,0.8); margin-bottom: 25px;
            backdrop-filter: blur(10px); position: relative; overflow: hidden;
        }
        .card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, var(--accent-color), var(--secondary-accent));
            box-shadow: 0 0 10px var(--accent-glow);
        }
        h2 { 
            margin-top: 0; font-size: 1.5rem; color: #fff; text-transform: uppercase; 
            letter-spacing: 3px; border-bottom: 1px solid #333; padding-bottom: 15px;
            display: flex; align-items: center; gap: 10px;
        }
        h2::before { content: '>'; color: var(--accent-color); }
        label { display: block; margin: 20px 0 8px; font-weight: 700; color: #aaa; font-size: 0.9rem; }
        .input-group input, .input-group textarea {
            width: 100%; background: #0a0a0a; border: 1px solid #333; color: #fff; 
            padding: 15px; border-radius: 6px; box-sizing: border-box;
            font-family: 'JetBrains Mono', monospace; font-size: 14px; transition: 0.3s;
        }
        .input-group input:focus, .input-group textarea:focus { outline: none; border-color: var(--accent-color); box-shadow: 0 0 15px var(--accent-glow); }
        .btn-submit {
            background: linear-gradient(45deg, var(--accent-color), #7b2cbf); color: #fff; border: none; 
            padding: 16px 30px; font-weight: bold; cursor: pointer; width: 100%; margin-top: 25px;
            border-radius: 6px; text-transform: uppercase; letter-spacing: 1px; transition: 0.3s;
            box-shadow: 0 5px 20px rgba(188, 19, 254, 0.3);
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(188, 19, 254, 0.5); }
        
        /* Result Styles */
        .result-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 15px; border-bottom: 1px solid #222; transition: 0.2s;
        }
        .result-item:hover { background: #1a1a1a; }
        .code-badge { padding: 5px 10px; border-radius: 4px; font-weight: bold; font-size: 0.9em; min-width: 80px; text-align: center; }
        
        .code-200 { background: rgba(0, 255, 65, 0.1); color: var(--success-color); border: 1px solid var(--success-color); }
        .code-3xx { background: rgba(0, 240, 255, 0.1); color: var(--secondary-accent); border: 1px solid var(--secondary-accent); }
        .code-403 { background: rgba(255, 42, 109, 0.1); color: var(--danger-color); border: 1px solid var(--danger-color); }
        
        a { color: #fff; text-decoration: none; } a:hover { text-decoration: underline; color: var(--accent-color); }
        .meta { font-size: 0.8em; color: #666; margin-left: 10px; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h2>6ickzone // Admin Finder</h2>
        <form method="POST">
            <div class="input-group">
                <label>Target URL</label>
                <input type="text" name="target" value="<?= isset($_POST['target']) ? htmlspecialchars($_POST['target']) : '' ?>" placeholder="http://target.com/" required>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <div class="input-group" style="flex: 1;">
                    <label>Delay (Sec)</label>
                    <input type="number" name="delay" value="<?= isset($_POST['delay']) ? $_POST['delay'] : 0 ?>" min="0">
                </div>
            </div>

            <div class="input-group">
                <label>Wordlist (Paths)</label>
                <textarea name="paths" rows="8"><?= isset($_POST['paths']) ? htmlspecialchars($_POST['paths']) : $default_paths ?></textarea>
            </div>
            
            <button type="submit" class="btn-submit">Scan Targets</button>
        </form>
    </div>

    <?php if (!empty($results)): ?>
    <div class="card">
        <h2>Scan_Results</h2>
        <div style="display: flex; flex-direction: column;">
            <?php foreach ($results as $res): 
                $code_class = 'code-200';
                if ($res['code'] >= 300 && $res['code'] < 400) $code_class = 'code-3xx';
                if ($res['code'] == 403) $code_class = 'code-403';
            ?>
            <div class="result-item">
                <div>
                    <span class="code-badge <?= $code_class ?>"><?= $res['code'] ?></span>
                    <a href="<?= htmlspecialchars($res['url']) ?>" target="_blank" style="margin-left: 15px; font-family: monospace;">
                        /<?= htmlspecialchars($res['path']) ?>
                    </a>
                    <span class="meta">[<?= $res['latency'] ?>ms]</span>
                </div>
                <div style="color: #666; font-size: 0.9em;"><?= $res['type'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
