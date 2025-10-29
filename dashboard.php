<?php
global $pdo;
require_once 'auth.php';   // ログインしていなければリダイレクトされる
require_login();

// 必要に応じてユーザー情報を取得
require_once 'config.php';
$stmt = $pdo->prepare('SELECT id, email, name, created_at FROM users WHERE id = :id');
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    // あり得ないが、セッションとDBの整合が取れない場合は強制ログアウト
    header('Location: logout.php');
    exit;
}
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>マイページ | 安全ログインシステム</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background-color: #f9f9f9;
            color: #333;
            margin: 0;
            padding: 40px;
        }
        .container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: auto;
            padding: 30px;
        }
        h1 {
            color: #0078D7;
        }
        .info {
            margin: 1em 0;
            line-height: 1.8;
        }
        a.button {
            display: inline-block;
            background: #0078D7;
            color: #fff;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
        }
        a.button:hover {
            background: #005fa3;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>ようこそ、<?php echo htmlspecialchars($user['name'] ?: $user['email'], ENT_QUOTES, 'UTF-8'); ?> さん</h1>

    <div class="info">
        <strong>メールアドレス:</strong> <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?><br>
        <strong>登録日:</strong> <?php echo htmlspecialchars($user['created_at'], ENT_QUOTES, 'UTF-8'); ?><br>
        <strong>ユーザーID:</strong> <?php echo htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8'); ?>
    </div>

    <p>このページはログインしているユーザーのみが閲覧できます。</p>

    <p><a href="logout.php" class="button">ログアウト</a></p>
</div>
</body>
</html>
