CREATE TABLE channel_subscriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    channel_id INT NOT NULL,
    user_identifier VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (channel_id) REFERENCES channels(id) ON DELETE CASCADE,
    UNIQUE KEY unique_subscription (channel_id, user_identifier)
);
