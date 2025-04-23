<?php
session_start();
require_once "config.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messaging App</title>
    <link rel="stylesheet" href="style.css">
    <?php include 'includes/header.php' ?>
</head>
<body>
    <?php if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true): ?>
        <div class="welcome-container">
            <h1>Welcome to MessengerApp</h1>
            <p>Connect with people instantly through our messaging platform.</p>
            <div class="auth-buttons">
                <a href="login.php" class="btn">Login</a>
                <a href="register.php" class="btn">Register</a>
            </div>
        </div>
    <?php else: ?>
        <div class="chat-container">
            <div class="sidebar">
                <div class="user-profile">
                    <h2>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></h2>
                </div>
                <nav>
                    <a href="messages.php" class="active">Messages</a>
                    <a href="chat_requests.php">Chat Requests</a>
                    <?php if($_SESSION["is_admin"]): ?>
                        <a href="admin.php">Admin Panel</a>
                    <?php endif; ?>
                    <a href="logout.php">Logout</a>
                </nav>
            </div>
            <div class="main-content">
                <div class="messages-preview">
                    <h3>Your Contacts</h3>
                    <?php
                    // Récupérer uniquement les utilisateurs avec qui on peut discuter
                    $sql = "SELECT DISTINCT u.id, u.username 
                            FROM users u 
                            INNER JOIN chat_permissions cp ON 
                                ((cp.requester_id = ? AND cp.target_id = u.id) OR 
                                (cp.target_id = ? AND cp.requester_id = u.id)) 
                            WHERE u.id != ? AND cp.status = 'accepted' 
                            ORDER BY u.username";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "iii", $_SESSION["id"], $_SESSION["id"], $_SESSION["id"]);
                    mysqli_stmt_execute($stmt);
                    $contacts = mysqli_stmt_get_result($stmt);
                    
                    if(mysqli_num_rows($contacts) > 0):
                        while($contact = mysqli_fetch_assoc($contacts)):
                    ?>
                        <div class="contact-preview">
                            <a href="messages.php?user=<?php echo $contact['id']; ?>" class="contact-link">
                                <?php echo htmlspecialchars($contact['username']); ?>
                            </a>
                        </div>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <p>No contacts yet. Add someone to start chatting!</p>
                    <?php endif; ?>
                </div>
                
                <div class="quick-message">
                    <h3>Start New Conversation</h3>
                    <form action="request_chat.php" method="post">
                        <div class="form-group">
                            <label for="username">Enter Username:</label>
                            <input type="text" name="username" id="username" required placeholder="Enter exact username">
                        </div>
                        <button type="submit" class="btn">Request Chat Permission</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
