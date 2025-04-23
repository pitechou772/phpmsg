USE chat_db;

-- Supprimer tous les messages existants
TRUNCATE TABLE messages;

-- Créer la table des permissions
CREATE TABLE IF NOT EXISTS chat_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    requester_id INT NOT NULL,
    target_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_id) REFERENCES users(id),
    FOREIGN KEY (target_id) REFERENCES users(id),
    UNIQUE KEY unique_permission (requester_id, target_id)
);
