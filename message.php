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
//  | message.php                                                            |
//  +------------------------------------------------------------------------+
require_once('include/initialize.inc.php');
header('Expires: Mon, 9 Oct 2000 18:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');

$message					= @$_GET['message'];
$type						= @$_GET['type'];
$skin						= @$_GET['skin'];
$file						= @$_GET['file'];
$line						= @$_GET['line'];
$timestamp					= @$_GET['timestamp'];
$cfg['menu']				= @$_GET['menu'];							// required for header
$cfg['username']			= @$_GET['username'];						// required for footer
$cfg['sign']				= rawurlencode(@$_GET['sign']);				// required for header
$cfg['img']					= 'skin/' . rawurlencode($skin) . '/img/';	// required for header
$cfg['skin']				= $skin;									// required for header

if (validateSkin($skin) == false)
	exit('<!doctype html><html><head><title></title></head><body><h1>Wrong value</h1><p>Unsupported input value for <em>skin</em></p></body></html>');

if (in_array($type, array('ok', 'warning', 'error')) == false)
	$type = 'warning';

if (in_array($cfg['menu'], array('favorite', 'playlist', 'config')) == false)
	$cfg['menu'] = 'media';

$message = bbcode($message);

if ($cfg['debug']) {
	$message .= "\n";
	$message .= "\t" . '<div class="debug">' . "\n";
	$message .= "\t\t" . '<strong>time stamp:</strong> ' . html(date('r', hexdec($timestamp))) . '<br>' . "\n";
	$message .= "\t\t" . '<strong>file:</strong> ' . html($file) . '<br>' . "\n";
	$message .= "\t\t" . '<strong>line:</strong> ' . (int) $line . "\n";
	$message .= "\t" . '</div>' . "\n";
}

require_once('include/header.inc.php');
?>
<table class="<?php echo $type; ?>">
<tr>
	<td><img src="<?php echo $cfg['img']; ?>medium_message_<?php echo $type; ?>.png" alt=""></td>
	<td><?php echo $message ?></td>
</tr>
</table>
<?php
require_once('include/footer.inc.php');
