<?php
session_start();
require_once "config.php";
require_once "includes/header.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Traiter les réponses aux demandes
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_id']) && isset($_POST['action'])) {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['action'];
    
    if ($action === 'accept' || $action === 'reject') {
        $status = $action === 'accept' ? 'accepted' : 'rejected';
        $sql = "UPDATE chat_permissions SET status = ? WHERE id = ? AND target_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sii", $status, $request_id, $_SESSION["id"]);
        mysqli_stmt_execute($stmt);
    }
}
?>

<div class="chat-container">
    <div class="sidebar">
        <div class="user-profile">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?></h2>
        </div>
        <nav>
            <a href="messages.php">Messages</a>
            <a href="chat_requests.php" class="active">Chat Requests</a>
            <?php if($_SESSION["is_admin"]): ?>
                <a href="admin.php">Admin Panel</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="requests-container">
            <h3>Pending Chat Requests</h3>
            <?php
            // Obtenir les demandes en attente
            $sql = "SELECT cp.*, u.username 
                    FROM chat_permissions cp 
                    JOIN users u ON cp.requester_id = u.id 
                    WHERE cp.target_id = ? AND cp.status = 'pending'";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
            mysqli_stmt_execute($stmt);
            $requests = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($requests) > 0):
                while($request = mysqli_fetch_assoc($requests)):
            ?>
                <div class="request-item">
                    <p><?php echo htmlspecialchars($request['username']); ?> wants to chat with you</p>
                    <form method="post" class="request-actions">
                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                        <button type="submit" name="action" value="accept" class="btn accept">Accept</button>
                        <button type="submit" name="action" value="reject" class="btn reject">Reject</button>
                    </form>
                </div>
            <?php
                endwhile;
            else:
            ?>
                <p>No pending chat requests</p>
            <?php endif; ?>
        </div>
        
        <div class="sent-requests">
            <h3>Sent Requests</h3>
            <?php
            // Obtenir les demandes envoyées
            $sql = "SELECT cp.*, u.username 
                    FROM chat_permissions cp 
                    JOIN users u ON cp.target_id = u.id 
                    WHERE cp.requester_id = ? AND cp.status = 'pending'";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
            mysqli_stmt_execute($stmt);
            $sent_requests = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($sent_requests) > 0):
                while($request = mysqli_fetch_assoc($sent_requests)):
            ?>
                <div class="request-item pending">
                    <p>Waiting for <?php echo htmlspecialchars($request['username']); ?> to accept</p>
                </div>
            <?php
                endwhile;
            else:
            ?>
                <p>No pending sent requests</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.requests-container, .sent-requests {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.request-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border-bottom: 1px solid #eee;
}

.request-item:last-child {
    border-bottom: none;
}

.request-actions {
    display: flex;
    gap: 10px;
}

.btn.accept {
    background-color: #4CAF50;
}

.btn.reject {
    background-color: #f44336;
}

.request-item.pending {
    background-color: #f5f5f5;
}
</style>
</body>
</html>
