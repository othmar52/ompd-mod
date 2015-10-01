INSERT INTO `server` ( `name` , `value` ) VALUES ('getid3_hash', 'd41d8cd98f00b204e9800998ecf8427e');

-- --------------------------------------------------------

--
-- Database version
--

UPDATE `server` SET `value` = '' WHERE `name` = 'latest_version' LIMIT 1;
UPDATE `server` SET `value` = '0' WHERE `name` = 'latest_version_idle_time' LIMIT 1;
UPDATE `server` SET `value` = '38' WHERE `name` = 'database_version' LIMIT 1;