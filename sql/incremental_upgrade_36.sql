ALTER TABLE `track` ADD `audio_compression_ratio` DOUBLE UNSIGNED DEFAULT '0' NOT NULL AFTER `audio_lossless`;
ALTER TABLE `track` DROP `audio_raw_decoded`;
TRUNCATE TABLE `track`;

-- --------------------------------------------------------

--
-- Database version
--

UPDATE `server` SET `value` = '36' WHERE `name` = 'database_version' LIMIT 1;

