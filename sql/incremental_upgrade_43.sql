INSERT INTO `server` VALUES ('batch_transcode_start_time', '0', 1);

-- --------------------------------------------------------

--
-- Database version
--

UPDATE `server` SET `value` = '' WHERE `name` = 'latest_version' LIMIT 1;
UPDATE `server` SET `value` = '0' WHERE `name` = 'latest_version_idle_time' LIMIT 1;
UPDATE `server` SET `value` = '43' WHERE `name` = 'database_version' LIMIT 1;