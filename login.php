<?php
global $pdo;
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(400);
    exit('Invalid CSRF token');
}

$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$password = $_POST['password'] ?? '';

if (!$email || $password === '') {
    http_response_code(400);
    exit('メールまたはパスワードが不正です');
}

// ロックポリシーの定義（必要に応じてチューニング）
define('MAX_FAILED', 5);
define('LOCK_MINUTES', 15);

try {
    // ユーザー取得（prepared statement）
    $stmt = $pdo->prepare('SELECT id, password_hash, failed_attempts, last_failed_at, is_locked, lock_expires_at FROM users WHERE email = :email');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    // ユーザーなしでも処理を同じくらいかかるようにする（タイミング攻撃対策）
    if (!$user) {
        // ダミー verify（一定の時間を消費）
        password_verify($password, password_hash('dummy_password', PASSWORD_DEFAULT));
        http_response_code(401);
        exit('認証に失敗しました');
    }

    // ロック検査
    if ($user['is_locked']) {
        $now = new DateTimeImmutable('now');
        $expires = $user['lock_expires_at'] ? new DateTimeImmutable($user['lock_expires_at']) : null;
        if ($expires && $expires > $now) {
            http_response_code(403);
            exit('アカウントがロックされています。後ほど再試行してください。');
        } else {
            // ロック解除
            $upd = $pdo->prepare('UPDATE users SET is_locked = 0, failed_attempts = 0, lock_expires_at = NULL WHERE id = :id');
            $upd->execute([':id' => $user['id']]);
            $user['is_locked'] = 0;
            $user['failed_attempts'] = 0;
        }
    }

    // パスワード検証
    if (password_verify($password, $user['password_hash'])) {
        // 成功時: failed_attempts をリセット
        $upd = $pdo->prepare('UPDATE users SET failed_attempts = 0, last_failed_at = NULL WHERE id = :id');
        $upd->execute([':id' => $user['id']]);

        // セッション固定化対策
        session_regenerate_id(true);

        // 最小限のユーザー情報をセッションに保存（必要最低限）
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['logged_in_at'] = time();

        echo 'ログイン成功';
        exit;
    } else {
        // 失敗時: failed_attempts をインクリメント、必要ならロック
        $failed = (int)$user['failed_attempts'] + 1;
        $nowStr = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        $params = [':id' => $user['id'], ':failed' => $failed, ':last_failed' => $nowStr];

        if ($failed >= MAX_FAILED) {
            $lockExpires = (new DateTimeImmutable('now'))->modify('+'.LOCK_MINUTES.' minutes')->format('Y-m-d H:i:s');
            $upd = $pdo->prepare('UPDATE users SET failed_attempts = :failed, last_failed_at = :last_failed, is_locked = 1, lock_expires_at = :lock_expires WHERE id = :id');
            $params[':lock_expires'] = $lockExpires;
            $upd->execute($params);
            http_response_code(403);
            exit('パスワード試行回数が多すぎるためアカウントを一時ロックしました。');
        } else {
            $upd = $pdo->prepare('UPDATE users SET failed_attempts = :failed, last_failed_at = :last_failed WHERE id = :id');
            $upd->execute($params);
            http_response_code(401);
            exit('認証に失敗しました');
        }
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('サーバーエラー');
}
