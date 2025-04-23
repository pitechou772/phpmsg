<?php
session_start();
require_once "config.php";
require_once "includes/header.php";

// Get selected user if any
$selected_user = isset($_GET['user']) ? intval($_GET['user']) : null;

// Verify chat permission if a user is selected
if ($selected_user) {
    $sql = "SELECT status FROM chat_permissions WHERE 
            ((requester_id = ? AND target_id = ?) OR 
            (requester_id = ? AND target_id = ?)) AND 
            status = 'accepted'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iiii", $_SESSION["id"], $selected_user, $selected_user, $_SESSION["id"]);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 0) {
        header("location: index.php");
        exit;
    }
}

// Send message
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['receiver']) && isset($_POST['message'])) {
    $receiver_id = mysqli_real_escape_string($conn, $_POST['receiver']);
    $message = str_replace('\r\n', "\n", $_POST['message']); // Normalize line endings
    $message = mysqli_real_escape_string($conn, $message);
    
    $sql = "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iis", $_SESSION["id"], $receiver_id, $message);
    mysqli_stmt_execute($stmt);

    // Redirect to keep the conversation open
    header("location: messages.php?user=" . $receiver_id);
    exit;
}

// Get messages for selected conversation
if ($selected_user) {
    $sql = "SELECT m.*, u.username as sender_name 
            FROM messages m 
            JOIN users u ON m.sender_id = u.id 
            WHERE (m.sender_id = ? AND m.receiver_id = ?) 
            OR (m.sender_id = ? AND m.receiver_id = ?) 
            ORDER BY m.created_at ASC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iiii", $_SESSION["id"], $selected_user, $selected_user, $_SESSION["id"]);
    mysqli_stmt_execute($stmt);
    $messages = mysqli_stmt_get_result($stmt);

    // Get selected user info
    $sql = "SELECT username FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $selected_user);
    mysqli_stmt_execute($stmt);
    $user_result = mysqli_stmt_get_result($stmt);
    $selected_user_info = mysqli_fetch_assoc($user_result);
}
?>

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

            <div class="chat-main">
                <?php if($selected_user): ?>
                <div class="chat-header">
                    <h2>Chat with <?php echo htmlspecialchars($selected_user_info['username']); ?></h2>
                </div>

                <div class="messages-container" id="messages">
                    <div id="messages-content">
                        <?php if($messages && mysqli_num_rows($messages) > 0): ?>
                            <?php 
                            $current_date = '';
                            $last_message_id = 0;
                            while($message = mysqli_fetch_assoc($messages)): 
                                $msg_date = date('Y-m-d', strtotime($message['created_at']));
                                if($msg_date != $current_date):
                                    $current_date = $msg_date;
                                    echo "<div class='date-separator'>".date('F j, Y', strtotime($current_date))."</div>";
                                endif;
                                $last_message_id = max($last_message_id, $message['id']);
                            ?>
                            <div class="message <?php echo ($message['sender_id'] == $_SESSION['id']) ? 'sent' : 'received'; ?>">
                                <div class="message-content">
                                    <p><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                    <span class="time"><?php echo date('H:i', strtotime($message['created_at'])); ?></span>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="no-messages">
                                <p>No messages yet. Start a conversation!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <div class="message-input">
                <form method="post" id="message-form" onsubmit="return sendMessage(event)">
                    <input type="hidden" name="receiver" value="<?php echo $selected_user; ?>">
                    <div class="input-group">
                        <textarea name="message" required placeholder="Type your message..." onkeydown="if(event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); sendMessage(event); }"></textarea>
                        <button type="submit" class="send-btn">Send</button>
                    </div>
                </form>
            </div>
            <?php else: ?>
            <div class="select-conversation">
                <h2>Select a conversation</h2>
                <p>Choose a contact from the sidebar to start chatting</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    let lastMessageId = <?php echo isset($last_message_id) ? $last_message_id : 0; ?>;
    let currentDate = '';

    function addMessage(message) {
        const messagesContent = document.getElementById('messages-content');
        
        if (message.date !== currentDate) {
            currentDate = message.date;
            const dateDiv = document.createElement('div');
            dateDiv.className = 'date-separator';
            const date = new Date(message.date);
            dateDiv.textContent = date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
            messagesContent.appendChild(dateDiv);
        }

        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${message.is_sent ? 'sent' : 'received'}`;
        messageDiv.innerHTML = `
            <div class="message-content">
                <p>${message.message}</p>
                <span class="time">${message.time}</span>
            </div>
        `;
        messagesContent.appendChild(messageDiv);
        scrollToBottom();
    }

    function scrollToBottom() {
        const messages = document.getElementById('messages');
        if (messages) {
            messages.scrollTop = messages.scrollHeight;
        }
    }

    async function sendMessage(event) {
        event.preventDefault();
        const form = document.getElementById('message-form');
        const formData = new FormData(form);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                form.reset();
                // The page will update automatically through the polling
            }
        } catch (error) {
            console.error('Error sending message:', error);
        }
        return false;
    }

    function pollMessages() {
        if (!document.hidden) {
            fetch(`get_messages.php?user=<?php echo $selected_user; ?>&last_id=${lastMessageId}`)
                .then(response => response.json())
                .then(messages => {
                    messages.forEach(message => {
                        addMessage(message);
                        lastMessageId = Math.max(lastMessageId, message.id);
                    });
                })
                .catch(error => console.error('Error polling messages:', error));
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        scrollToBottom();

        // Auto-resize textarea
        const textarea = document.querySelector('textarea');
        if (textarea) {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        }

        // Start polling for new messages
        setInterval(pollMessages, 2000);
    });
    </script>
</body>
</html>
</body>
</html>
