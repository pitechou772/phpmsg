<?php
session_start();
require_once "config.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    
    // Vérifier si l'utilisateur existe
    $sql = "SELECT id FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($user = mysqli_fetch_assoc($result)) {
        $target_id = $user['id'];
        
        // Vérifier si une demande existe déjà
        $sql = "SELECT * FROM chat_permissions WHERE 
                (requester_id = ? AND target_id = ?) OR 
                (requester_id = ? AND target_id = ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iiii", $_SESSION["id"], $target_id, $target_id, $_SESSION["id"]);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) == 0) {
            // Créer une nouvelle demande
            $sql = "INSERT INTO chat_permissions (requester_id, target_id) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $_SESSION["id"], $target_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION["message"] = "Chat request sent to " . htmlspecialchars($username);
            } else {
                $_SESSION["error"] = "Error sending chat request";
            }
        } else {
            $permission = mysqli_fetch_assoc($result);
            if ($permission['status'] == 'pending') {
                $_SESSION["message"] = "A chat request is already pending with this user";
            } else if ($permission['status'] == 'accepted') {
                $_SESSION["message"] = "You already have permission to chat with this user";
            } else {
                $_SESSION["error"] = "Your previous request was rejected. Please wait before sending another request";
            }
        }
    } else {
        $_SESSION["error"] = "User not found";
    }
    
    header("location: index.php");
    exit;
}
?>
