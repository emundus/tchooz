# update utf8mb3 to utf8mb4 falang table and InnoDB

ALTER TABLE `#__falang_tableinfo` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `#__falang_content` MODIFY `modified` datetime NULL DEFAULT NULL;

ALTER TABLE `#__falang_content` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `#__falang_tableinfo` ENGINE=InnoDB;

ALTER TABLE `#__falang_content` ENGINE=InnoDB;