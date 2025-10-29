<?php
// auth.php
require_once 'config.php';

function require_login() {
    if (empty($_SESSION['user_id'])) {
        // JSON APIなら 401、HTMLならリダイレクト
        header('Location: /login_form.php');
        exit;
    }
    // 必要に応じてセッションの年齢制限を設ける
    $maxIdle = 60 * 60 * 2; // 2時間
    if (isset($_SESSION['logged_in_at']) && (time() - $_SESSION['logged_in_at']) > $maxIdle) {
        // セッションタイムアウト扱い
        session_unset();
        session_destroy();
        header('Location: /login_form.php?timeout=1');
        exit;
    }
}
