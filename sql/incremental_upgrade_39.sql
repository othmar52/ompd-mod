ALTER TABLE `user` CHANGE `access_statistics` `access_popular` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';

ALTER TABLE `user` ADD `access_statistics` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `access_record`;
UPDATE `user` SET `access_statistics` = '1' WHERE `access_admin` = '1';

-- --------------------------------------------------------

--
-- Database version
--

UPDATE `server` SET `value` = '' WHERE `name` = 'latest_version' LIMIT 1;
UPDATE `server` SET `value` = '0' WHERE `name` = 'latest_version_idle_time' LIMIT 1;
UPDATE `server` SET `value` = '39' WHERE `name` = 'database_version' LIMIT 1;