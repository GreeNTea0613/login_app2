<!-- login_form.php -->
<?php require 'config.php'; ?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>ログイン</title></head>
<body>
<form method="post" action="login.php">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES); ?>">
    <label>メール<input type="email" name="email" required></label><br>
    <label>パスワード<input type="password" name="password" required></label><br>
    <button type="submit">ログイン</button>
</form>
</body>
</html>
