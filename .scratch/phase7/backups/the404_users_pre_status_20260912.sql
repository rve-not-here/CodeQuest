-- Backup of the404_users before the status migration (requirements backup)
-- Produced 2026-09-12T09:21:53Z via PDO read-only, one-off approved run
-- Rollback stone: full restore of the pre-migration state.

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `the404_users`;

CREATE TABLE `the404_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(128) NOT NULL DEFAULT '',
  `role` enum('student','teacher','admin','operator') NOT NULL DEFAULT 'student',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `the404_users` (`id`,`username`,`password`,`name`,`role`,`created_at`,`updated_at`) VALUES ('1','admin','$2y$12$z85m6L6ob4twOx08/ad9eOnWR7hSVd7DEwyJT7ZiLFWKvDF5K4fhq','System Admin','admin','2026-08-30 19:42:17','2026-08-30 19:42:17');
INSERT INTO `the404_users` (`id`,`username`,`password`,`name`,`role`,`created_at`,`updated_at`) VALUES ('2','operator','$2y$12$v1/HIgFRP3PdUkz2cSJA0.hCLvAtlf2xS9d9RvEpkhaE2qqU78AJm','Operator One','student','2026-08-30 19:42:17','2026-09-12 08:22:24');
INSERT INTO `the404_users` (`id`,`username`,`password`,`name`,`role`,`created_at`,`updated_at`) VALUES ('3','teacher','$2y$12$m/L.RRqlPFqlUdhv6irzaOyPX5.YqJXkWeyZvKL2B626BevdiSr6G','Instructor','teacher','2026-08-30 19:42:17','2026-08-30 19:42:17');

SET FOREIGN_KEY_CHECKS=1;
