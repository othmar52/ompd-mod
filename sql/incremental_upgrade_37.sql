ALTER TABLE `cache` ADD `tag_hash` VARCHAR( 32 ) DEFAULT '' NOT NULL AFTER `filemtime`;
ALTER TABLE `cache` ADD `zip_hash` VARCHAR( 32 ) DEFAULT '' NOT NULL AFTER `tag_hash`;

INSERT INTO `server` ( `name` , `value` ) VALUES ('escape_char_hash', 'd41d8cd98f00b204e9800998ecf8427e');

ALTER TABLE `track` DROP `combined`;

-- --------------------------------------------------------

--
-- Database version
--

UPDATE `server` SET `value` = '' WHERE `name` = 'latest_version' LIMIT 1;
UPDATE `server` SET `value` = '0' WHERE `name` = 'latest_version_idle_time' LIMIT 1;
UPDATE `server` SET `value` = '37' WHERE `name` = 'database_version' LIMIT 1;