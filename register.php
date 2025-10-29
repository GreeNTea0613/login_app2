<?php
global $pdo;
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// CSRFチェック
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(400);
    exit('Invalid CSRF token');
}

$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';
$name = trim($_POST['name'] ?? '');

if (!$email || strlen($password) < 8) {
    http_response_code(400);
    exit('Invalid input (email / password length)');
}

// パスワードをハッシュ化する (default: bcrypt or argon2 depending on PHP)
$hash = password_hash($password, PASSWORD_DEFAULT);

// 登録（例外は PDO が投げる）
try {
    $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, name) VALUES (:email, :hash, :name)');
    $stmt->execute([':email' => $email, ':hash' => $hash, ':name' => $name]);
    echo '登録に成功しました。';
} catch (PDOException $e) {
    // ユニーク制約違反など
    error_log($e->getMessage());
    http_response_code(400);
    echo '登録に失敗しました（既に使われているメールかもしれません）';
}
