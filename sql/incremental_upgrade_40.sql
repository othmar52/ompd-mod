ALTER TABLE `album` ADD `image_id` VARCHAR( 30 ) NOT NULL DEFAULT '' AFTER `discs`;

ALTER TABLE `bitmap` DROP `image50`;
ALTER TABLE `bitmap` DROP `image100`;
ALTER TABLE `bitmap` DROP `image200`;
ALTER TABLE `bitmap` ADD `image` MEDIUMBLOB NOT NULL FIRST;
ALTER TABLE `bitmap` ADD `image_id` VARCHAR( 30 ) NOT NULL AFTER `image_back`;

INSERT INTO `server` VALUES ('image_quality', '0');
INSERT INTO `server` VALUES ('image_size', '0');

-- --------------------------------------------------------

--
-- Database version
--

UPDATE `server` SET `value` = '' WHERE `name` = 'latest_version' LIMIT 1;
UPDATE `server` SET `value` = '0' WHERE `name` = 'latest_version_idle_time' LIMIT 1;
UPDATE `server` SET `value` = '40' WHERE `name` = 'database_version' LIMIT 1;