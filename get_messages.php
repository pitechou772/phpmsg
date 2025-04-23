<?php
session_start();
require_once "config.php";

if(!isset($_SESSION["loggedin"]) || !isset($_GET['user'])) {
    exit;
}

$selected_user = intval($_GET['user']);
$last_message_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;

$sql = "SELECT m.*, u.username as sender_name 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        WHERE ((m.sender_id = ? AND m.receiver_id = ?) 
        OR (m.sender_id = ? AND m.receiver_id = ?))
        AND m.id > ?
        ORDER BY m.created_at ASC";
        
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iiiii", $_SESSION["id"], $selected_user, $selected_user, $_SESSION["id"], $last_message_id);
mysqli_stmt_execute($stmt);
$messages = mysqli_stmt_get_result($stmt);

$response = [];
while($message = mysqli_fetch_assoc($messages)) {
    $message_text = str_replace('\r\n', "\n", $message['message']); // Normalize line endings
    $response[] = [
        'id' => $message['id'],
        'message' => nl2br(htmlspecialchars($message_text)),
        'sender_id' => $message['sender_id'],
        'is_sent' => $message['sender_id'] == $_SESSION['id'],
        'time' => date('H:i', strtotime($message['created_at'])),
        'date' => date('Y-m-d', strtotime($message['created_at']))
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
?>
