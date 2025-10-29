<?php
// config.php - DB接続とセッション設定
declare(strict_types=1);

// --- 重要: 実運用では環境変数を使うこと ---
$dbHost = '127.0.0.1';
$dbName = 'app';
$dbUser = 'appuser';
$dbPass = 'secret_password';
$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";

// PDO
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    // 運用ではエラーログに出して、ユーザーには一般的なメッセージを返す
    error_log($e->getMessage());
    http_response_code(500);
    exit('DB接続エラー');
}

// --- セッションセキュリティ ---
$secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params([
    'lifetime' => 0,                // ブラウザ終了で消える（必要なら変更）
    'path' => '/',
    'domain' => '',                 // ドメインがあれば指定
    'secure' => $secure,            // HTTPSの場合のみ true
    'httponly' => true,             // JSからアクセス不可
    'samesite' => 'Lax',            // 'Strict' or 'Lax' を検討
]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRFトークンユーティリティ
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
function csrf_token(): string {
    return $_SESSION['csrf_token'];
}
function verify_csrf_token($token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', (string)$token);
}
