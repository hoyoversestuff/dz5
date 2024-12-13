<?php
$host = 'localhost';
$db = 'dz5';
$user = 'root';
$pass = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    $browserInfo = $_SERVER['HTTP_USER_AGENT'];

    if (strlen($username) < 10) {
        $errors[] = "Имя пользователя должно содержать минимум 10 символов.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Некорректный адрес электронной почты.";
    }
    if (strlen($message) < 10) {
        $errors[] = "Сообщение должно содержать минимум 10 символов.";
    }
    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO messages (username, email, message, ip_address, browser_info) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $email, $message, $ipAddress, $browserInfo]);
        header("Location: " . $_SERVER['PHP_SELF']); 
        exit();
    }
}

$limit = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$totalMessages = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
$totalPages = ceil($totalMessages / $limit);
$stmt = $pdo->prepare("SELECT * FROM messages ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Гостевая книга</title>
</head>
<body>
    <h1>Гостевая книга</h1>
    <form action="" method="POST">
        <label>Имя пользователя:</label><br>
        <input type="text" name="username" required><br><br>

        <label>Электронная почта:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Сообщение:</label><br>
        <textarea name="message" rows="5" required></textarea><br><br>

        <button type="submit">Добавить сообщение</button>
    </form>

    <?php if ($errors): ?>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <h2>Сообщения</h2>

    <?php foreach ($messages as $msg): ?>
        <div>
            <p><strong>Имя:</strong> <?= htmlspecialchars($msg['username']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($msg['email']) ?></p>
            <p><strong>Сообщение:</strong> <?= nl2br(htmlspecialchars($msg['message'])) ?></p>
            <p><small>Дата: <?= $msg['created_at'] ?></small></p>
            <hr>
        </div>
    <?php endforeach; ?>

    <div>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</body>
</html>
