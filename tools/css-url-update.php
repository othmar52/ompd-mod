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
//  | CSS image update                                                       |
//  +------------------------------------------------------------------------+
header('Expires: Mon, 9 Oct 2000 18:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');




echo '<!doctype html><html><head><title></title></head><body>' . "\n";
exit('<strong>BEFORE USE MAKE A BACKUP</strong><br><br>netjukebox css url update.<br>Comment out line ' . __LINE__ . ' to run this script.</body></html>');




//  +------------------------------------------------------------------------+
//  | Get skin directory                                                     |
//  +------------------------------------------------------------------------+
$directory			= dirname(__FILE__);
$directory			= realpath($directory . '/../skin');
$cfg['skin_dir']	= str_replace('\\', '/', $directory) . '/';




//  +------------------------------------------------------------------------+
//  | Convert                                                                |
//  +------------------------------------------------------------------------+
recursiveScan($cfg['skin_dir']);
echo 'Ready' . "\n";
echo '</body></html>';



//  +------------------------------------------------------------------------+
//  | Recursive convert                                                      |
//  +------------------------------------------------------------------------+
function recursiveScan($dir) {
	global $cfg;
	
	$entries = @scandir($dir) or exit('Failed to open directory:<br>' . htmlspecialchars($dir, ENT_COMPAT) . '</body></html>');
	foreach ($entries as $entry) {
		if ($entry[0] != '.' && !in_array($entry, array('lost+found', 'Temporary Items', 'Network Trash Folder', 'System Volume Information', 'RECYCLER', '$RECYCLE.BIN'))) {
			if (is_dir($dir . $entry . '/'))
				recursiveScan($dir . $entry . '/');
			else {
				if ($entry == 'style.css') {
					$file = $dir . $entry;
					$cfg['dir'] = $dir;
					$content = file_get_contents($file);
					$content = preg_replace_callback('#url\(((?:fonts/|img/).*?)\)#s', 'callback', $content);
					file_put_contents($file, $content);
					echo $file . '<br>';
				}
			}
		}
	}
}


function callback($maches) {
	global $cfg;
	$mach = $maches[1];
	@list($file, $hash) = explode('?', $mach, 2);
	$file .= '?' . base_convert(filemtime($cfg['dir'] . $file), 10, 36);
	return 'url(' . $file . ')';
}

