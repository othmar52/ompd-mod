<?php
//  +------------------------------------------------------------------------+
//  | netjukebox, Copyright © 2001-2015 Willem Bartels                       |
//  |                                                                        |
//  | http://www.netjukebox.nl                                               |
//  | http://forum.netjukebox.nl                                             |
//  |                                                                        |
//  | This program is free software: you can redistribute it and/or modify   |
//  | it under the terms of the GNU General Public License as published by   |
//  | the Free Software Foundation, either version 3 of the License, or      |
//  | (at your option) any later version.                                    |
//  |                                                                        |
//  | This program is distributed in the hope that it will be useful,        |
//  | but WITHOUT ANY WARRANTY; without even the implied warranty of         |
//  | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the          |
//  | GNU General Public License for more details.                           |
//  |                                                                        |
//  | You should have received a copy of the GNU General Public License      |
//  | along with this program.  If not, see <http://www.gnu.org/licenses/>.  |
//  +------------------------------------------------------------------------+




//  +------------------------------------------------------------------------+
//  | mysqli.inc.php                                                         |
//  +------------------------------------------------------------------------+
$db = @mysqli_connect(
	$cfg['mysqli_host'],
	$cfg['mysqli_user'],
	$cfg['mysqli_password'],
	null,
	($cfg['mysqli_port'] == '') ? null : $cfg['mysqli_port'],
	($cfg['mysqli_socket'] == '') ? null : $cfg['mysqli_socket']
) or message(__FILE__, __LINE__, 'error', '[b]Failed to connect to MySQL server on:[/b][br]' . $cfg['mysqli_host'] . (($cfg['mysqli_port'] == '') ? '' : ':' . $cfg['mysqli_port']) );

unset($cfg['mysqli_user'], $cfg['mysqli_password']);

// select database
@mysqli_select_db($db, $cfg['mysqli_db']) or createDatabase();

// check database version
loadServerSettings();
if ($cfg['database_version'] != NJB_DATABASE_VERSION)
	upgradeDatabase();




//  +------------------------------------------------------------------------+
//  | Create database                                                        |
//  +------------------------------------------------------------------------+
function createDatabase() {
	global $cfg, $db;
	
	if ($cfg['mysqli_auto_create_db']) {		
		@mysqli_query($db, 'CREATE DATABASE ' . mysqli_real_escape_string($db, $cfg['mysqli_db']))
			or message(__FILE__, __LINE__, 'error', '[b]Failed to create database:[/b][br]' . $cfg['mysqli_db']);
		
		@mysqli_select_db($db, $cfg['mysqli_db'])
			or message(__FILE__, __LINE__, 'error', '[b]Failed to select database:[/b][br]' . $cfg['mysqli_db']);
		
		$file = NJB_HOME_DIR . 'sql/netjukebox_' . str_pad(NJB_DATABASE_VERSION, 2, '0', STR_PAD_LEFT) . '.sql';
		
		querySqlFile($file);
		loadServerSettings();
		
		message(__FILE__, __LINE__, 'ok', '[b]Database ' . $cfg['mysqli_db'] . '@' . $cfg['mysqli_host'] . ' created successfully.[/b][br]'
			. 'For security reason it is advisable to change the admin password.[br][br]'
			. '[url=index.php?authenticate=logout][b]Login netjukebox:[/b][/url]'
			. '[list][*][b]username:[/b] admin[*][b]password:[/b] admin[/list]');
	}
	else {		
		message(__FILE__, __LINE__, 'error', '[b]MySQL error[/b][br]'
			. 'Database [i]' . $cfg['mysqli_db'] . '[/i] does not exist![br]'
			. 'Set [i]$cfg[\'mysqli_auto_create_db\'] = true;[/i] in the [i]include/config.inc.php[/i] file[br]'
			. 'or create the [i]' . $cfg['mysqli_db'] . '[/i] database manually and load the [i]sql/netjukebox_' . NJB_DATABASE_VERSION . '.sql[/i] file');
	}
}




//  +------------------------------------------------------------------------+
//  | Upgrade database                                                       |
//  +------------------------------------------------------------------------+
function upgradeDatabase() {
	global $cfg, $db;

	if ($cfg['database_version'] < 32 || $cfg['database_version'] > NJB_DATABASE_VERSION) {
		message(__FILE__, __LINE__, 'error', '[b]MySQL upgrade error[/b][br]'
			. 'Incremental upgrade is not supported from this database version.'
			. '[list][*]Delete your old database.[*]On the next start netjukebox automatic creates a new MySQL database and table structure.[/list]');
	}
	else {
		for ($i = $cfg['database_version'] + 1; $i <= NJB_DATABASE_VERSION; $i++)
			querySqlFile(NJB_HOME_DIR . 'sql/incremental_upgrade_' . str_pad($i, 2, '0', STR_PAD_LEFT) . '.sql');
		
		loadServerSettings();
		message(__FILE__, __LINE__, 'ok', '[b]Incremental database upgrade successfuly on ' . $cfg['mysqli_db'] . '@' . $cfg['mysqli_host'] . '[/b][br]'
			. 'It is advisable to update de database now.'
			. '[list][*]Login with admin rights[*]Than select menu Config > Update[/list]');
	}
}




//  +------------------------------------------------------------------------+
//  | Query SQL file                                                         |
//  +------------------------------------------------------------------------+
function querySqlFile($file) {
	global $db;
	
	$query_array = @file_get_contents($file) or message(__FILE__, __LINE__, 'error', '[b]Failed to open file:[/b][br]' . $file . '[list][*]Check file permission[/list]');
	$query_array = explode(';', $query_array);
	unset($query_array[count($query_array) - 1]);
	
	foreach ($query_array as $key => $query) {
		@mysqli_query($db, $query) or message(__FILE__, __LINE__, 'error', '[b]MySQL create/upgarde error[/b][br]File:' . $file . '[br]Query: ' . $query);
	}
}




//  +------------------------------------------------------------------------+
//  | Load server settings                                                   |
//  +------------------------------------------------------------------------+
function loadServerSettings() {
	global $cfg, $db;
	
	$query = @mysqli_query($db, 'SELECT name, value, is_integer FROM server');
	if ($query) {
		while ($server = mysqli_fetch_assoc($query))
			$cfg[$server['name']] = ($server['is_integer']) ? (int) $server['value'] : $server['value'];
	}
	else {
		$query = @mysqli_query($db, 'SELECT name, value FROM server') or message(__FILE__, __LINE__, 'error', '[b]Failed to load MySQL server settings[/b][br]'
																		. 'When creating the database manually[br]'
																		. 'also load the [i]sql/netjukebox_' . NJB_DATABASE_VERSION . '.sql[/i] file manually.');
		while ($server = mysqli_fetch_assoc($query))
			$cfg[$server['name']] = $server['value'];
		
		$cfg['database_version'] = (int) $cfg['database_version'];
	}
	
	if (isset($cfg['server_seed']) == false && $cfg['database_version'] == NJB_DATABASE_VERSION) {
		$cfg['server_seed'] = randomSeed();	
		@mysqli_query($db, 'INSERT INTO server (name, value) VALUES ("server_seed", "' . mysqli_real_escape_string($db, $cfg['server_seed']) . '")')
			or message(__FILE__, __LINE__, 'error', '[b]MySQL create/upgarde error[/b][br]Failed to create server_seed');		
	}
}




//  +------------------------------------------------------------------------+
//  | MySQLi real escape like                                                |
//  +------------------------------------------------------------------------+
function mysqli_real_escape_like($db, $string) {
	$string = str_replace('%', '\%', $string);
	$string = str_replace('_', '\_', $string);
	
	return mysqli_real_escape_string($db, $string);
}
