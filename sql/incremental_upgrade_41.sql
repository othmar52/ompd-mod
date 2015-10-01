ALTER TABLE `session` ADD `lock_ip` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1' AFTER `idle_time`;
ALTER TABLE `user` ADD `access_search` SMALLINT UNSIGNED NOT NULL DEFAULT '127' AFTER `access_admin`;

ALTER TABLE `server` ADD `is_integer` TINYINT( 1 ) NOT NULL DEFAULT '0';
UPDATE `server` SET `is_integer` = '1' WHERE `name` = 'database_version';
UPDATE `server` SET `is_integer` = '1' WHERE `name` = 'image_quality';
UPDATE `server` SET `is_integer` = '1' WHERE `name` = 'image_size';
UPDATE `server` SET `is_integer` = '1' WHERE `name` = 'latest_version_idle_time';

ALTER TABLE `user` DROP `version`;

-- --------------------------------------------------------

--
-- Database version
--

UPDATE `server` SET `value` = '' WHERE `name` = 'latest_version' LIMIT 1;
UPDATE `server` SET `value` = '0' WHERE `name` = 'latest_version_idle_time' LIMIT 1;
UPDATE `server` SET `value` = '41' WHERE `name` = 'database_version' LIMIT 1;