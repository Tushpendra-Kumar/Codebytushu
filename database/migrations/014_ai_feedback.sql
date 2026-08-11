CREATE TABLE IF NOT EXISTS ai_feedback (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id VARCHAR(255) NULL,
  session_id VARCHAR(255) NOT NULL,
  message_id VARCHAR(255) NOT NULL,
  feedback_type ENUM('like', 'dislike') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_message_feedback (session_id, message_id)
);
