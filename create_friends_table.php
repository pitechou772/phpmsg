<?php
require_once "config.php";

// Create friends table
$sql = "CREATE TABLE IF NOT EXISTS friend_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (receiver_id) REFERENCES users(id),
    UNIQUE KEY unique_request (sender_id, receiver_id)
)";

if (mysqli_query($conn, $sql)) {
    echo "Friend requests table created successfully";
} else {
    echo "Error creating table: " . mysqli_error($conn);
}
?>
