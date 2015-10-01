ALTER TABLE `album` CHANGE `discs` `discs` TINYINT( 2 ) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `bitmap` CHANGE `flag` `flag` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `bitmap` CHANGE `image_front_width` `image_front_width` MEDIUMINT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `bitmap` CHANGE `image_front_height` `image_front_height` MEDIUMINT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `cache` CHANGE `profile` `profile` TINYINT( 3 ) NOT NULL DEFAULT '0';
ALTER TABLE `counter` CHANGE `flag` `flag` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `country` CHANGE `code` `code` SMALLINT( 3 ) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` CHANGE `player_type` `player_type` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` CHANGE `mute_volume` `mute_volume` TINYINT( 3 ) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` CHANGE `player_id` `player_id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `session` MODIFY COLUMN `random_blacklist` VARCHAR( 255 ) NOT NULL DEFAULT '' AFTER `download_id`;
ALTER TABLE `session` MODIFY COLUMN `skin` VARCHAR( 255 ) NOT NULL DEFAULT 'Clean' AFTER `random_blacklist`;
ALTER TABLE `session` MODIFY COLUMN `player_id` INT( 10 ) UNSIGNED NOT NULL DEFAULT '1' AFTER `thumbnail_size`;

ALTER TABLE `share_download` CHANGE `download_id` `download_id` TINYINT( 3 ) NOT NULL DEFAULT '0';
ALTER TABLE `share_stream` CHANGE `stream_id` `stream_id` TINYINT( 3 ) NOT NULL DEFAULT '0';
ALTER TABLE `track` CHANGE `video_resolution_x` `video_resolution_x` MEDIUMINT( 8 ) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `track` CHANGE `video_resolution_y` `video_resolution_y` MEDIUMINT( 8 ) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `track` CHANGE `video_framerate` `video_framerate` SMALLINT( 5 ) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `user` CHANGE `access_search` `access_search` TINYINT( 3 ) UNSIGNED NOT NULL DEFAULT '127';

-- --------------------------------------------------------

--
-- Database version
--

UPDATE `server` SET `value` = '' WHERE `name` = 'latest_version' LIMIT 1;
UPDATE `server` SET `value` = '0' WHERE `name` = 'latest_version_idle_time' LIMIT 1;
UPDATE `server` SET `value` = '42' WHERE `name` = 'database_version' LIMIT 1;