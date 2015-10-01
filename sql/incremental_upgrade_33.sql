CREATE TABLE `random` (
  `sid` varchar(40) NOT NULL default '',
  `track_id` varchar(20) NOT NULL default '',
  `position` int(10) unsigned NOT NULL default '0',
  `create_time` int(10) unsigned NOT NULL default '0',
  KEY `sid` (`sid`),
  KEY `track_id` (`track_id`),
  KEY `position` (`position`),
  KEY `create_time` (`create_time`));

-- --------------------------------------------------------

--
-- Database version
--

UPDATE `server` SET `value` = '33' WHERE `name` = 'database_version' LIMIT 1;