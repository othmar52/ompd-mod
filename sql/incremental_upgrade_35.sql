ALTER TABLE `favoriteitem` CHANGE `position` `position` SMALLINT UNSIGNED NOT NULL DEFAULT '0';

ALTER TABLE `random` CHANGE `position` `position` SMALLINT UNSIGNED NOT NULL DEFAULT '0';

ALTER TABLE `session` CHANGE `stream_id` `stream_id` TINYINT NOT NULL DEFAULT '-1';
ALTER TABLE `session` CHANGE `download_id` `download_id` TINYINT NOT NULL DEFAULT '-1';
ALTER TABLE `session` CHANGE `player_id` `player_id` TINYINT UNSIGNED NOT NULL DEFAULT '1';

ALTER TABLE `share` RENAME `share_download`;
ALTER TABLE `share_download` ADD `ip` VARCHAR( 255 ) DEFAULT '' NOT NULL FIRST ;
ALTER TABLE `share_download` CHANGE `download_id` `download_id` TINYINT NOT NULL DEFAULT '0';
ALTER TABLE `share_download` ADD INDEX ( `sid` );

CREATE TABLE `share_stream` (
`ip` VARCHAR( 255 ) NOT NULL DEFAULT '',
`sid` VARCHAR( 255 ) NOT NULL DEFAULT '',
`album_id` VARCHAR( 11 ) NOT NULL DEFAULT '',
`stream_id` TINYINT NOT NULL DEFAULT '0',
`expire_time` INT UNSIGNED NOT NULL DEFAULT '0'
);

ALTER TABLE `share_stream` ADD INDEX ( `sid` );
ALTER TABLE `share_stream` ADD INDEX ( `album_id` );
ALTER TABLE `share_stream` ADD INDEX ( `expire_time` );

-- --------------------------------------------------------

--
-- Database version
--

UPDATE `server` SET `value` = '35' WHERE `name` = 'database_version' LIMIT 1;

