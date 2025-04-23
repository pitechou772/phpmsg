<?php
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Get all conversations for the current user
function getConversations($conn, $user_id) {
    $sql = "SELECT DISTINCT 
            u.id, 
            u.username,
            (SELECT message FROM messages 
             WHERE (sender_id = ? AND receiver_id = u.id) 
             OR (sender_id = u.id AND receiver_id = ?) 
             ORDER BY created_at DESC LIMIT 1) as last_message,
            (SELECT created_at FROM messages 
             WHERE (sender_id = ? AND receiver_id = u.id) 
             OR (sender_id = u.id AND receiver_id = ?) 
             ORDER BY created_at DESC LIMIT 1) as last_message_time
            FROM users u
            LEFT JOIN messages m ON (m.sender_id = u.id OR m.receiver_id = u.id)
            WHERE u.id != ?
            GROUP BY u.id
            ORDER BY last_message_time DESC";
            
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

// Format message content
function formatMessage($message) {
    // Convert \r\n to <br>
    $message = nl2br(htmlspecialchars($message));
    // Add emoji support and other formatting if needed
    return $message;
}

$conversations = getConversations($conn, $_SESSION["id"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MessengerApp</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="main-header">
        <nav class="top-nav">
            <div class="nav-container">
                <a href="index.php" class="logo">MessengerApp</a>
                <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                    <div class="nav-links">
                        <a href="index.php">Home</a>
                        <a href="messages.php">Messages</a>
                        <a href="chat_requests.php">Chat Requests</a>
                        <?php if($_SESSION["is_admin"]): ?>
                            <a href="admin.php">Admin Panel</a>
                        <?php endif; ?>
                        <a href="logout.php">Logout</a>
                    </div>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <div class="chat-layout">
        <div class="sidebar">
            <div class="user-profile">
                <h2><?php echo htmlspecialchars($_SESSION["username"]); ?></h2>
                <p>Online</p>
            </div>
            
            <div class="conversations-list">
                <h3>Conversations</h3>
                <?php while($conv = mysqli_fetch_assoc($conversations)): ?>
                    <a href="messages.php?user=<?php echo $conv['id']; ?>" class="conversation">
                        <div class="conversation-info">
                            <h4><?php echo htmlspecialchars($conv['username']); ?></h4>
                            <?php if($conv['last_message']): ?>
                                <p class="last-message"><?php echo htmlspecialchars(substr($conv['last_message'], 0, 30)) . '...'; ?></p>
                                <span class="time"><?php echo date('H:i', strtotime($conv['last_message_time'])); ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
            
            <nav class="bottom-nav">
                <a href="index.php">Home</a>
                <a href="messages.php" class="active">Messages</a>
                <?php if($_SESSION["is_admin"]): ?>
                    <a href="admin.php">Admin</a>
                <?php endif; ?>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
