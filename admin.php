<?php
session_start();
require_once "config.php";

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !$_SESSION["is_admin"]) {
    header("location: login.php");
    exit;
}

// Get all users
$sql = "SELECT id, username, created_at FROM users WHERE id != ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$users = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get all messages
$sql = "SELECT m.*, u1.username as sender, u2.username as receiver 
        FROM messages m 
        JOIN users u1 ON m.sender_id = u1.id 
        JOIN users u2 ON m.receiver_id = u2.id 
        ORDER BY m.created_at DESC";
$messages = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="admin-container">
        <h2>Admin Panel</h2>
        <nav>
            <a href="index.php">Home</a>
            <a href="logout.php">Logout</a>
        </nav>
        
        <div class="users-section">
            <h3>Users</h3>
            <table>
                <tr>
                    <th>Username</th>
                    <th>Joined Date</th>
                </tr>
                <?php foreach($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="messages-section">
            <h3>Messages</h3>
            <table>
                <tr>
                    <th>From</th>
                    <th>To</th>
                    <th>Message</th>
                    <th>Date</th>
                </tr>
                <?php while($message = mysqli_fetch_assoc($messages)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($message['sender']); ?></td>
                    <td><?php echo htmlspecialchars($message['receiver']); ?></td>
                    <td><?php echo htmlspecialchars($message['message']); ?></td>
                    <td><?php echo htmlspecialchars($message['created_at']); ?></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</body>
</html>
