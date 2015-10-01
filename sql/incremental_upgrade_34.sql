INSERT INTO `server` VALUES ('latest_version', '');
INSERT INTO `server` VALUES ('latest_version_idle_time', '0');

-- --------------------------------------------------------

--
-- Database version
--

UPDATE `server` SET `value` = '34' WHERE `name` = 'database_version' LIMIT 1;