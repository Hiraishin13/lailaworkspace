-- Create partnerships table
CREATE TABLE IF NOT EXISTS `partnerships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project1_id` int(11) NOT NULL,
  `project2_id` int(11) NOT NULL,
  `status` enum('pending','active','completed','cancelled') DEFAULT 'pending',
  `compatibility_score` int(3) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `project1_id` (`project1_id`),
  KEY `project2_id` (`project2_id`),
  KEY `status` (`status`),
  UNIQUE KEY `unique_partnership` (`project1_id`, `project2_id`),
  CONSTRAINT `partnerships_ibfk_1` FOREIGN KEY (`project1_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `partnerships_ibfk_2` FOREIGN KEY (`project2_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create partnership_suggestions table
CREATE TABLE IF NOT EXISTS `partnership_suggestions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project1_id` int(11) NOT NULL,
  `project2_id` int(11) NOT NULL,
  `compatibility_score` int(3) NOT NULL,
  `suggested_by` int(11) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `project1_id` (`project1_id`),
  KEY `project2_id` (`project2_id`),
  KEY `suggested_by` (`suggested_by`),
  UNIQUE KEY `unique_suggestion` (`project1_id`, `project2_id`),
  CONSTRAINT `partnership_suggestions_ibfk_1` FOREIGN KEY (`project1_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `partnership_suggestions_ibfk_2` FOREIGN KEY (`project2_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `partnership_suggestions_ibfk_3` FOREIGN KEY (`suggested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create partnership_history table
CREATE TABLE IF NOT EXISTS `partnership_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partnership_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `partnership_id` (`partnership_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `partnership_history_ibfk_1` FOREIGN KEY (`partnership_id`) REFERENCES `partnerships` (`id`) ON DELETE CASCADE,
  CONSTRAINT `partnership_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; 