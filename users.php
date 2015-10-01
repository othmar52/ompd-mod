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
//  | users.php                                                              |
//  +------------------------------------------------------------------------+
require_once('include/initialize.inc.php');
$cfg['menu'] = 'config';

$action		= @$_REQUEST['action'];
$user_id	= @$_REQUEST['user_id'];

if		($action == '')						home();

elseif	($action == 'editUser')				editUser($user_id);
elseif	($action == 'updateUser')			{updateUser($user_id);	home();}
elseif	($action == 'deleteUser')			{deleteUser($user_id);	home();}

elseif	($action == 'currentUser')			currentUser();

elseif	($action == 'online')				online();
elseif	($action == 'resetSessions')		{resetSessions();		online();}

elseif	($action == 'userStatistics')		userStatistics();
elseif	($action == 'resetUserStatistics')	{resetUserStatistics();	userStatistics();}

else	message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]action');
exit();




//  +------------------------------------------------------------------------+
//  | Home                                                                   |
//  +------------------------------------------------------------------------+
function home() {
	global $cfg, $db;
	authenticate('access_admin');
	
	// Navigator
	$nav			= array();
	$nav['name'][]	= 'Configuration';
	$nav['url'][]	= 'config.php';
	$nav['name'][]	= 'Users';
	require_once('include/header.inc.php');
?>
<table class="border">
<tr class="header">
	<td class="space"></td>
	<td>Username</td>
	<td class="textspace"></td>
	<td class="matrix">Media</td>
	<td class="matrix">Popular</td>
	<td class="matrix">Favorite</td>
	<td class="matrix">Playlist</td>
	<td class="matrix">Play</td>
	<td class="matrix">Add</td>
	<td class="matrix">Stream</td>
	<td class="matrix">Download</td>
	<td class="matrix">Cover</td>
	<td class="matrix">Record</td>
	<td class="matrix">Statistics</td>
	<td class="matrix">Admin</td>
	<td class="space"></td>
	<td><a href="users.php?action=editUser&amp;user_id=0" title="Add a new user"><img src="<?php echo $cfg['img']; ?>small_header_new.png" alt="" class="small"></a></td>
	<td class="space"></td>
</tr>
<?php
	$i=0;
	$check = '<img src="' . $cfg['img'] . 'small_check.png" alt="" class="small space">';
	$uncheck = '<img src="' . $cfg['img'] . 'small_uncheck.png" alt="" class="small space">';
	$query = mysqli_query($db, 'SELECT username, access_media, access_popular, access_cover, access_stream, access_playlist, access_play, access_add, access_record, access_download, access_favorite, access_statistics, access_admin, user_id FROM user ORDER BY username');
	while ($user = mysqli_fetch_assoc($query)) { ?>
<tr class="<?php if ($cfg['username'] == $user['username']) echo 'select'; else echo ($i & 1) ? 'even mouseover' : 'odd mouseover'; $i++ ?>">
	<td></td>
	<td><a href="users.php?action=editUser&amp;user_id=<?php echo $user['user_id']; ?>"><img src="<?php echo $cfg['img']; ?>small_user.png" alt="" class="small space"><?php echo html($user['username']); ?></a></td>
	<td></td>
	<td class="text-align-center" <?php echo accessInfoTitle('media'); ?>><?php echo $user['access_media'] ? $check : $uncheck; ?></td>
	<td class="text-align-center" <?php echo accessInfoTitle('popular'); ?>><?php echo $user['access_popular'] ? $check : $uncheck; ?></td>
	<td class="text-align-center" <?php echo accessInfoTitle('favorite'); ?>><?php echo $user['access_favorite'] ? $check : $uncheck; ?></td>
	<td class="text-align-center" <?php echo accessInfoTitle('playlist'); ?>><?php echo $user['access_playlist'] ? $check : $uncheck; ?></td>
	<td class="text-align-center" <?php echo accessInfoTitle('play'); ?>><?php echo $user['access_play'] ? $check : $uncheck; ?></td>
	<td class="text-align-center" <?php echo accessInfoTitle('add'); ?>><?php echo $user['access_add'] ? $check : $uncheck; ?></td>
	<td class="text-align-center" <?php echo accessInfoTitle('stream'); ?>><?php echo $user['access_stream'] ? $check : $uncheck; ?></td>
	<td class="text-align-center" <?php echo accessInfoTitle('download'); ?>><?php echo $user['access_download'] ? $check : $uncheck; ?></td>
	<td class="text-align-center" <?php echo accessInfoTitle('cover'); ?>><?php echo $user['access_cover'] ? $check : $uncheck; ?></td>
	<td class="text-align-center" <?php echo accessInfoTitle('record'); ?>><?php echo $user['access_record'] ? $check : $uncheck; ?></td>
	<td class="text-align-center" <?php echo accessInfoTitle('statistics'); ?>><?php echo $user['access_statistics'] ? $check : $uncheck; ?></td>
	<td class="text-align-center" <?php echo accessInfoTitle('admin'); ?>><?php echo $user['access_admin'] ? $check : $uncheck; ?></td>
	<td></td>
	<td><a href="users.php?action=deleteUser&amp;user_id=<?php echo $user['user_id']; ?>&amp;sign=<?php echo $cfg['sign']; ?>" onclick="return confirm('Are you sure you want to delete user: <?php echo addslashes(html($user['username'])); ?>?');"><img src="<?php echo $cfg['img']; ?>small_delete.png" alt="" class="small"></a></td>
	<td></td>
</tr>
<?php
	}
	echo '</table>' . "\n";
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | Edit user                                                              |
//  +------------------------------------------------------------------------+
function editUser($user_id) {
	global $cfg, $db;
	authenticate('access_admin');
	
	if ($user_id == '0') {
		// Add user configuraton
		$user['username']			= 'user_' . sprintf('%04x', mt_rand(0, 0xffff));
		$user['access_media']		= true;
		$user['access_popular']		= false;
		$user['access_favorite']	= false;
		$user['access_cover']		= false;
		$user['access_stream']		= false;
		$user['access_download']	= false;
		$user['access_playlist']	= false;
		$user['access_play']		= false;
		$user['access_add']			= false;
		$user['access_record']		= false;
		$user['access_statistics']	= false;
		$user['access_admin']		= false;
		$user['access_search']		= 255;
		// $txt_menu					= 'Add user';
		$txt_password				= 'Password:';
	}
	else {
		// Edit user configutaion
		$query = mysqli_query($db, 'SELECT
			username,
			access_media,
			access_popular,
			access_favorite,
			access_cover,
			access_stream,
			access_download,
			access_playlist,
			access_play,
			access_add,
			access_record,
			access_statistics,
			access_admin,
			access_search
			FROM user
			WHERE user_id = ' . (int) $user_id);
		$user = mysqli_fetch_assoc($query);
		if ($user == false)
			message(__FILE__, __LINE__, 'error', '[b]Error[/b][br]user_id not found in database');
		
		$txt_password	= 'New password:';
	}
	
	// Navigator
	$nav			= array();
	$nav['name'][]	= 'Configuration';
	$nav['url'][]	= 'config.php';
	$nav['name'][]	= 'Users';
	$nav['url'][]	= 'users.php';
	$nav['name'][]	= $user['username'];
	require_once('include/header.inc.php');
	
	// Store seed temporarily in the session database
	// After acepting a new password copy the seed to the user database
	$session_seed = randomSeed();
	mysqli_query($db, 'UPDATE session
		SET seed	= "' . mysqli_real_escape_string($db, $session_seed) . '"
		WHERE sid	= BINARY "' . mysqli_real_escape_string($db, $cfg['sid']) . '"');
?>
<form id="userform" action="users.php" method="post" autocomplete="off">
	<input type="hidden" name="action" value="updateUser">
	<input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
	<input type="hidden" name="sign" value="<?php echo $cfg['sign']; ?>">
<table class="border bottom_space">
<tr class="header">
	<td class="space"></td>
	<td>Access</td>
	<td class="space"></td>
</tr>
<tr class="odd" <?php echo accessInfoTitle('media'); ?>>
	<td></td>
	<td><label><input type="checkbox" name="access_media" value="1" class="space"<?php if ($user['access_media']) echo ' checked'; ?>>Media</label></td>
	<td></td>
</tr>
<tr class="even" <?php echo accessInfoTitle('popular'); ?>>
	<td></td>
	<td><label><input type="checkbox" name="access_popular" value="1" class="space"<?php if ($user['access_popular']) echo ' checked'; ?>>Popular</label></td>
	<td></td>
</tr>
<tr class="odd" <?php echo accessInfoTitle('favorite'); ?>>
	<td></td>
	<td><label><input type="checkbox" name="access_favorite" value="1" class="space"<?php if ($user['access_favorite']) echo ' checked'; ?>>Favorite</label></td>
	<td></td>
</tr>
<tr class="even" <?php echo accessInfoTitle('playlist'); ?>>
	<td></td>
	<td><label><input type="checkbox" name="access_playlist" value="1" class="space"<?php if ($user['access_playlist']) echo ' checked'; ?>>Playlist</label></td>
	<td></td>
</tr>
<tr class="odd" <?php echo accessInfoTitle('play'); ?>>
	<td></td>
	<td><label><input type="checkbox" name="access_play" value="1" class="space"<?php if ($user['access_play']) echo ' checked'; ?>>Play</label></td>
	<td></td>
</tr>
<tr class="even" <?php echo accessInfoTitle('add'); ?>>
	<td></td>
	<td><label><input type="checkbox" name="access_add" value="1" class="space"<?php if ($user['access_add']) echo ' checked'; ?>>Add</label></td>
	<td></td>
</tr>
<tr class="odd" <?php echo accessInfoTitle('stream'); ?>>
	<td></td>
	<td><label><input type="checkbox" name="access_stream" value="1" class="space"<?php if ($user['access_stream']) echo ' checked'; ?>>Stream</label></td>
	<td></td>
</tr>
<tr class="even" <?php echo accessInfoTitle('download'); ?>>
	<td></td>
	<td><label><input type="checkbox" name="access_download" value="1" class="space"<?php if ($user['access_download']) echo ' checked'; ?>>Download</label></td>
	<td></td>
</tr>
<tr class="odd" <?php echo accessInfoTitle('cover'); ?>>
	<td></td>
	<td><label><input type="checkbox" name="access_cover" value="1" class="space"<?php if ($user['access_cover']) echo ' checked'; ?>>Cover</label></td>
	<td></td>
</tr>
<tr class="even" <?php echo accessInfoTitle('record'); ?>>
	<td></td>
	<td><label><input type="checkbox" name="access_record" value="1" class="space"<?php if ($user['access_record']) echo ' checked'; ?>>Record</label></td>
	<td></td>
</tr>
<tr class="odd" <?php echo accessInfoTitle('statistics'); ?>>
	<td></td>
	<td><label><input type="checkbox" name="access_statistics" value="1" class="space"<?php if ($user['access_statistics']) echo ' checked'; ?>>Statistics</label></td>
	<td></td>
</tr>
<tr class="even" <?php echo accessInfoTitle('admin'); ?>>
	<td></td>
	<td><label><input type="checkbox" name="access_admin" value="1" class="space"<?php if ($user['access_admin']) echo ' checked'; ?>>Admin</label></td>
	<td></td>
</tr>
<tr class="section">
	<td class="space"></td>
	<td>Internet search</td>
	<td class="space"></td>
</tr>
<?php
	for ($i = 0; $i < count($cfg['search_name']); $i++) {
?>
<tr class="<?php echo ($i & 1) ? 'even' : 'odd'; ?>">
	<td></td>
	<td><label><input type="checkbox" name="access_search[]" value="<?php echo pow(2,$i); ?>" class="space"<?php if (pow(2,$i) & $user['access_search']) echo ' checked'; ?>><?php echo html($cfg['search_name'][$i]); ?></label></td>
	<td></td>
</tr>
<?php
	}
?>	
<tr class="footer">
	<td></td>
	<td>Username:</td>
	<td></td>
</tr>
<tr class="footer">
	<td></td>
	<td><input type="text" name="new_username" value="<?php echo html($user['username']); ?>" maxlength="255" <?php echo ($user['username'] == $cfg['anonymous_user']) ? 'readonly class="short readonly" onfocus="this.blur();"' : 'class="short"'; ?>></td>
	<td></td>
</tr>
<tr class="footer">
	<td></td>
	<td><?php echo $txt_password; ?></td>
	<td></td>
</tr>
<tr class="footer">
	<td></td>
	<td><input type="password" name="new_password" <?php echo ($user['username'] == $cfg['anonymous_user']) ? 'readonly class="short readonly" onfocus="this.blur();"' : 'class="short"'; ?>></td>
	<td></td>
</tr>
<tr class="footer">
	<td></td>
	<td>Confirm password:</td>
	<td></td>
</tr>
<tr class="footer">
	<td></td>
	<td><input type="password" name="chk_password" <?php echo ($user['username'] == $cfg['anonymous_user']) ? 'readonly class="short readonly" onfocus="this.blur();"' : 'class="short"'; ?>></td>
	<td></td>
</tr>
<tr class="footer"><td colspan="3"></td></tr>
</table>
<a href="javascript:hashPassword();" class="button space">save</a><!--
--><a href="users.php" class="button">cancel</a>
</form>


<script type="text/javascript">
function hashPassword()	{
	userform.new_username.className = 'short readonly';
	userform.new_password.className = 'short readonly';
	userform.chk_password.className = 'short readonly';
	userform.new_password.value = hmacsha1(hmacsha1(userform.new_password.value, '<?php echo $session_seed; ?>'), '<?php echo $session_seed; ?>');
	userform.chk_password.value = hmacsha1(hmacsha1(userform.chk_password.value, '<?php echo $session_seed; ?>'), '<?php echo $session_seed; ?>');
	userform.submit();
}
</script>
<?php
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | Update user                                                            |
//  +------------------------------------------------------------------------+
function updateUser($user_id) {
	global $cfg, $db;
	authenticate('access_admin', false, true, true);
	
	$new_username		= @$_POST['new_username'];
	$new_password		= @$_POST['new_password'];
	$chk_password		= @$_POST['chk_password'];
	$access_media		= @$_POST['access_media']		? 1 : 0;
	$access_popular		= @$_POST['access_popular']		? 1 : 0;
	$access_favorite	= @$_POST['access_favorite']	? 1 : 0;
	$access_playlist	= @$_POST['access_playlist']	? 1 : 0;
	$access_play		= @$_POST['access_play']		? 1 : 0;
	$access_add			= @$_POST['access_add']			? 1 : 0;
	$access_stream		= @$_POST['access_stream']		? 1 : 0;
	$access_download	= @$_POST['access_download']	? 1 : 0;
	$access_cover		= @$_POST['access_cover']		? 1 : 0;
	$access_record		= @$_POST['access_record']		? 1 : 0;
	$access_statistics	= @$_POST['access_statistics']	? 1 : 0;
	$access_admin		= @$_POST['access_admin']		? 1 : 0;
	$access_search_array= @$_POST['access_search'];

	$access_search = 0;
	
	for ($i = 0; $i < count($access_search_array) && $i < 7; $i++)
		$access_search += (int) $access_search_array[$i];
	
	$query = mysqli_query($db, 'SELECT user_id FROM user WHERE user_id = ' . (int) $user_id);
	if (mysqli_fetch_row($query) == false && $user_id != '0')
		message(__FILE__, __LINE__, 'error', '[b]Error[/b][br]user_id not found in database');
	
	$query = mysqli_query($db, 'SELECT user_id FROM user WHERE user_id != ' . (int) $user_id . ' AND username = "' . mysqli_real_escape_string($db, $new_username) . '"');
	if (mysqli_fetch_row($query))
		message(__FILE__, __LINE__, 'warning', '[b]Username already exist[/b][br]Choose another username[br][url=users.php?action=editUser&user_id='. rawurlencode($user_id) . '][img]small_back.png[/img]Back to previous page[/url]');
	
	
	if ($new_password == hmacsha1(hmacsha1('', $cfg['session_seed']), $cfg['session_seed']))	$password_set = false;
	else																						$password_set = true;
	
	if (preg_match('#^[0-9a-f]{40}$#', $new_password) == false)							message(__FILE__, __LINE__, 'error', '[b]Password error[/b][br]This is not a valid hash');
	if ($new_password != $chk_password) 												message(__FILE__, __LINE__, 'warning', '[b]Passwords are not identical[/b][br][url=users.php?action=editUser&user_id='. rawurlencode($user_id) .'][img]small_back.png[/img]Back to previous page[/url]');
	if (!$password_set && $user_id == '0' && $new_username != $cfg['anonymous_user'])	message(__FILE__, __LINE__, 'warning', '[b]Password must be set for a new user[/b][br][url=users.php?action=editUser&user_id=0][img]small_back.png[/img]Back to previous page[/url]');
	if ($new_username == '') 															message(__FILE__, __LINE__, 'warning', '[b]Username must be set[/b][br][url=users.php?action=editUser&user_id='. rawurlencode($user_id) .'][img]small_back.png[/img]Back to previous page[/url]');
	if ($access_admin == false) {
		if (checkAdminAcount($user_id) == false)
				message(__FILE__, __LINE__, 'warning', '[b]There must be at least one user with admin privilege[/b][br][url=users.php?action=editUser&user_id='. rawurlencode($user_id) .'][img]small_back.png[/img]Back to previous page[/url]');
	}
	
	if (($password_set || $user_id == '0') && $new_username == $cfg['anonymous_user']) {
		$new_password = hmacsha1(hmacsha1($cfg['anonymous_user'], $cfg['session_seed']), $cfg['session_seed']);
		$password_set = true;
	}
	
	if ($user_id == '0') {
		mysqli_query($db, 'INSERT INTO user (username) VALUES ("")');
		$user_id = mysqli_insert_id($db);
	}
	
	if ($password_set) {
		mysqli_query($db, 'UPDATE user SET
			username			= "' . mysqli_real_escape_string($db, $new_username) . '",
			password			= "' . mysqli_real_escape_string($db, $new_password) . '",
			seed				= "' . mysqli_real_escape_string($db, $cfg['session_seed']) . '",
			access_media		= ' . (int) $access_media . ',
			access_popular		= ' . (int) $access_popular . ',
			access_favorite 	= ' . (int) $access_favorite . ',
			access_playlist		= ' . (int) $access_playlist . ',
			access_play			= ' . (int) $access_play . ',
			access_add			= ' . (int) $access_add . ',
			access_stream		= ' . (int) $access_stream . ',
			access_download 	= ' . (int) $access_download . ',
			access_cover		= ' . (int) $access_cover . ',
			access_record		= ' . (int) $access_record . ',
			access_statistics	= ' . (int) $access_statistics . ',
			access_admin		= ' . (int) $access_admin . ',
			access_search		= ' . (int) $access_search . '
			WHERE user_id		= ' . (int) $user_id);
		
		mysqli_query($db, 'UPDATE session
			SET logged_in	= 0
			WHERE user_id	= ' . (int) $user_id);
	}
	else {
		mysqli_query($db, 'UPDATE user SET
			username			= "' . mysqli_real_escape_string($db, $new_username) . '",
			access_media		= ' . (int) $access_media . ',
			access_popular		= ' . (int) $access_popular . ',
			access_favorite		= ' . (int) $access_favorite . ',
			access_playlist		= ' . (int) $access_playlist . ',
			access_play			= ' . (int) $access_play . ',
			access_add			= ' . (int) $access_add . ',
			access_stream		= ' . (int) $access_stream . ',
			access_download 	= ' . (int) $access_download . ',
			access_cover		= ' . (int) $access_cover . ',
			access_record		= ' . (int) $access_record . ',
			access_statistics	= ' . (int) $access_statistics . ',
			access_admin		= ' . (int) $access_admin . ',
			access_search		= ' . (int) $access_search . '
			WHERE user_id		= ' . (int) $user_id);
	}
}




//  +------------------------------------------------------------------------+
//  | Delete user                                                            |
//  +------------------------------------------------------------------------+
function deleteUser($user_id) {
	global $db;
	authenticate('access_admin', false, true, true);
	if (checkAdminAcount($user_id) == false)
		message(__FILE__, __LINE__, 'warning', '[b]There must be at least one user with admin privilege[/b][br][url=users.php][img]small_back.png[/img]Back to previous page[/url]');
	mysqli_query($db, 'DELETE FROM user WHERE user_id = ' . (int) $user_id);
	mysqli_query($db, 'DELETE FROM session WHERE user_id = ' . (int) $user_id);
}




//  +------------------------------------------------------------------------+
//  | Check admin acount                                                     |
//  +------------------------------------------------------------------------+
function checkAdminAcount($user_id) {
	global $db;
	$query = mysqli_query($db, 'SELECT user_id 
		FROM user 
		WHERE user_id != ' . (int) $user_id . '
		AND access_admin');
	$user = mysqli_fetch_assoc($query);
	if ($user['user_id'] == '') return false;
	else						return true;
}




//  +------------------------------------------------------------------------+
//  | Current user                                                           |
//  +------------------------------------------------------------------------+
function currentUser() {
	global $cfg;
	authenticate('access_logged_in');
	
	// Navigator
	$nav			= array();
	$nav['name'][]	= 'Configuration';
	$nav['url'][]	= 'config.php';
	$nav['name'][]	= 'User';
	$nav['url'][]	= '';
	$nav['name'][]	= $cfg['username'];
	$nav['url'][]	= '';
	require_once('include/header.inc.php');
	
	$check = '<img src="' . $cfg['img'] . 'small_check.png" alt="" class="small space">';
	$uncheck = '<img src="' . $cfg['img'] . 'small_uncheck.png" alt="" class="small space">';
?>
<table class="border">
<tr class="header">
	<td class="space"></td>
	<td>Access&nbsp;right</td>
	<td class="space"></td>
</tr>
<tr class="odd mouseover" <?php echo accessInfoTitle('media'); ?>>
	<td></td>
	<td><?php echo $cfg['access_media'] ? $check : $uncheck; ?>Media</td>
	<td></td>
</tr>
<tr class="even mouseover" <?php echo accessInfoTitle('popular'); ?>>
	<td></td>
	<td><?php echo $cfg['access_popular'] ? $check : $uncheck; ?>Popular</td>
	<td></td>
</tr>
<tr class="odd mouseover" <?php echo accessInfoTitle('favorite'); ?>>
	<td></td>
	<td><?php echo $cfg['access_favorite'] ? $check : $uncheck; ?>Favorite</td>
	<td></td>
</tr>
<tr class="even mouseover" <?php echo accessInfoTitle('playlist'); ?>>
	<td></td>
	<td><?php echo $cfg['access_playlist'] ? $check : $uncheck; ?>Playlist</td>
	<td></td>
</tr>
<tr class="odd mouseover" <?php echo accessInfoTitle('play'); ?>>
	<td></td>
	<td><?php echo $cfg['access_play'] ? $check : $uncheck; ?>Play</td>
	<td></td>
</tr>
<tr class="even mouseover" <?php echo accessInfoTitle('add'); ?>>
	<td></td>
	<td><?php echo $cfg['access_add'] ? $check : $uncheck; ?>Add</td>
	<td></td>
</tr>
<tr class="odd mouseover" <?php echo accessInfoTitle('stream'); ?>>
	<td></td>
	<td><?php echo $cfg['access_stream'] ? $check : $uncheck; ?>Stream</td>
	<td></td>
</tr>
<tr class="even mouseover" <?php echo accessInfoTitle('download'); ?>>
	<td></td>
	<td><?php echo $cfg['access_download'] ? $check : $uncheck; ?>Download</td>
	<td></td>
</tr>
<tr class="odd mouseover" <?php echo accessInfoTitle('cover'); ?>>
	<td></td>
	<td><?php echo $cfg['access_cover'] ? $check : $uncheck; ?>Cover</td>
	<td></td>
</tr>
<tr class="even mouseover" <?php echo accessInfoTitle('record'); ?>>
	<td></td>
	<td><?php echo $cfg['access_record'] ? $check : $uncheck; ?>Record</td>
	<td></td>
</tr>
<tr class="odd mouseover" <?php echo accessInfoTitle('statistics'); ?>>
	<td></td>
	<td><?php echo $cfg['access_statistics'] ? $check : $uncheck; ?>Statistics</td>
	<td></td>
</tr>
<tr class="even mouseover" <?php echo accessInfoTitle('admin'); ?>>
	<td></td>
	<td><?php echo $cfg['access_admin'] ? $check : $uncheck; ?>Admin</td>
	<td></td>
</tr>
<?php /*
	if ($cfg['username'] != $cfg['anonymous_user']) { ?>
<tr class="header">
	<td class="space"></td>
	<td>Settings</td>
	<td class="space"></td>
</tr>
<tr class="odd mouseover" title="Change current password">
	<td></td>
	<td><a href="todo"><img src="<?php echo $cfg['img']; ?>small_user.png" alt="" class="small space">Change password</a></td>
	<td></td>
</tr>
<?php
	}  */?>
</table>
<?php
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | Online                                                                 |
//  +------------------------------------------------------------------------+
function online() {
	global $cfg, $db;
	authenticate('access_admin');
		
	// Navigator
	$nav			= array();
	$nav['name'][]	= 'Configuration';
	$nav['url'][]	= 'config.php';
	$nav['name'][]	= 'Online';
	require_once('include/header.inc.php');	
?>
<table class="border bottom_space">
<tr class="header">
	<td class="space"></td>
	<td>User</td>
	<td class="textspace"></td>
	<td class="text-align-right">Visit</td>
	<td class="textspace"></td>
	<td class="text-align-right">Hit</td>
	<td class="textspace"></td>
	<td>IP</td>
	<td class="textspace"></td>
	<td>Country</td>
	<td class="textspace"></td>
	<td class="text-align-right">Idle</td>
	<td class="space"></td>
</tr>
<?php
	$i = 0;
	$cfg['ip_tools'] = str_replace('&', '&amp;', $cfg['ip_tools']);
	$query = mysqli_query($db, 'SELECT logged_in, hit_counter, visit_counter, idle_time, ip, user_agent,
		user.username,
		user.user_id
		FROM session, user
		WHERE idle_time > ' . (int) (time() - 86400) . '
		AND hit_counter > 0
		AND session.user_id = user.user_id
		ORDER BY idle_time DESC');
	while ($session = mysqli_fetch_assoc($query)) {
		$country_name = '';
		// Get local network
		$ip = array();
		$ip['lower'][]	= '192.168.0.0';
		$ip['upper'][]	= '192.168.255.255';
		$ip['name'][]	= 'Local area network';
		
		$ip['lower'][]	= '172.16.0.0';
		$ip['upper'][]	= '172.31.255.255';
		$ip['name'][]	= 'Local area network';
				
		$ip['lower'][]	= '10.0.0.0';
		$ip['upper'][]	= '10.255.255.255';
		$ip['name'][]	= 'Local area network';
				
		$ip['lower'][]	= '169.254.0.0';
		$ip['upper'][]	= '169.254.255.255';
		$ip['name'][]	= 'Automatic private IP range';
		
		$ip['lower'][]	= '127.0.0.0';
		$ip['upper'][]	= '127.255.255.255';
		$ip['name'][]	= 'Loopback';
		
		$session_ip = ip2long($session['ip']);
		foreach ($ip['name'] as $key => $value) {
			if ($session_ip >= ip2long($ip['lower'][$key]) && $session_ip <= ip2long($ip['upper'][$key])) {
				$country_name = $ip['name'][$key];
				$flag = 'unknown';
				break;
			}
		}
		
		if (in_array($session['ip'], array('::1', '0:0:0:0:0:0:0:1'))) {
			$country_name = 'Loopback';
			$flag = 'unknown';
		}
			
		if ($country_name == '') {
			// Get country code
			$reverse_ip = explode('.', $session['ip']);
			$reverse_ip = array_reverse($reverse_ip);
			$reverse_ip = implode('.', $reverse_ip);
			$lookup = $reverse_ip . '.zz.countries.nerd.dk';
			$code = @gethostbyname($lookup);
			if ($code != $lookup) {
				$code = explode('.', $code);
				$code = 256 * (int) $code[2] + (int) $code[3];
				$query3 = mysqli_query($db, 'SELECT iso, name FROM country WHERE code = ' . (int) $code);
				$country = mysqli_fetch_assoc($query3);
				$country_name = $country['name'];
				$flag = $country['iso'];
			}
		}
		
		if ($country_name == '') {
			$country_name = 'Unresolved / Unknown';
			$flag = $cfg['img'] . 'unknown';
		}
?>
<tr class="<?php echo ($i++ & 1) ? 'even' : 'odd'; ?> mouseover">
	<td></td>
	<td><a href="users.php?action=editUser&amp;user_id=<?php echo $session['user_id'];?>" title="<?php echo html($session['user_agent']); ?>"><img src="<?php echo $cfg['img']; ?>small_<?php echo ($session['logged_in']) ? 'login' : 'logout'; ?>.png" alt="" class="small space"><?php echo html($session['username']); ?></a></td>	
	<td></td>
	<td class="text-align-right"><?php echo $session['visit_counter']; ?></td>	
	<td></td>
	<td class="text-align-right"><?php echo $session['hit_counter']; ?></td>
	<td></td>
	<td><a href="<?php echo str_replace('%ip', rawurlencode($session['ip']), $cfg['ip_tools']); ?>"><?php echo html($session['ip']); ?></a></td>
	<td></td>
	<td><span class="flag <?php echo $flag; ?>"></span><?php echo html($country_name); ?></td>
	<td></td>
	<td class="text-align-right"><?php echo formattedTime((time() - $session['idle_time']) * 1000); ?></td>
	<td></td>
</tr>
<?php
	}
	$query = mysqli_query($db, 'SELECT idle_time AS start_time FROM session WHERE logged_in ORDER BY idle_time ASC LIMIT 1');
	$session = mysqli_fetch_assoc($query);
?>
<tr class="footer">
	<td class="space"></td>
	<td colspan="11">Visit and hit count since: <?php echo date($cfg['date_format'], $session['start_time']); ?></td>
	<td class="space"></td>
</tr>
</table>
<a href="users.php?action=online" class="button space">refresh</a><!--
--><a href="users.php?action=resetSessions&amp;sign=<?php echo $cfg['sign']; ?>" onclick="return confirm('Are you sure you want to reset all sessions?')" class="button">reset</a>
<?php
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | Reset sessions                                                         |
//  +------------------------------------------------------------------------+
function resetSessions() {
	global $db;
	authenticate('access_admin', false, true, true);
	
	mysqli_query($db, 'TRUNCATE TABLE session');
}




//  +------------------------------------------------------------------------+
//  | User statistics                                                        |
//  +------------------------------------------------------------------------+
function userStatistics() {
	global $cfg, $db;
	authenticate('access_admin');
	
	$period = @$_GET['period'];
	
	if		($period == 'week')		$timestamp = time() - 86400 * 7;
	elseif	($period == 'month')	$timestamp = time() - 86400 * 31;
	elseif	($period == 'year')		$timestamp = time() - 86400 * 365;
	elseif	($period == 'overall')	$timestamp = 0;
	else							message(__FILE__, __LINE__, 'error', '[b]Unsupported input value for[/b][br]period');
	
	// Navigator
	$nav			= array();
	$nav['name'][]	= 'Configuration';
	$nav['url'][]	= 'config.php';
	$nav['name'][]	= 'User statistics';
	require_once('include/header.inc.php');
?>
<table class="bottom_space"><tr><td><!-- table tab wrapper -->
<ul id="tab">
	<li class="tab <?php echo ($period == 'week') ? 'on' : 'off'; ?>" onclick="location.href='users.php?action=userStatistics&amp;period=week';">Week</li>
	<li class="tab <?php echo ($period == 'month') ? 'on' : 'off'; ?>" onclick="location.href='users.php?action=userStatistics&amp;period=month';">Month</li>
	<li class="tab <?php echo ($period == 'year') ? 'on' : 'off'; ?>" onclick="location.href='users.php?action=userStatistics&amp;period=year';">Year</li>
	<li class="tab <?php echo ($period == 'overall') ? 'on' : 'off'; ?>" onclick="location.href='users.php?action=userStatistics&amp;period=overall';">Overall</li>
</ul>
<table class="tab">
<tr class="header">
	<td class="space"></td>
	<td>Username</td>
	<td class="textspace"></td>
	<td class="matrix">Play</td>
	<td class="matrix">Stream</td>
	<td class="matrix">Download</td>
	<td class="matrix">Cover</td>
	<td class="matrix">Record</td>
	<td class="space"></td>
</tr>
<?php
	$i= 0;
	$query = mysqli_query($db, 'SELECT username, access_play, access_add, access_stream, access_download, access_cover, access_record, user_id FROM user ORDER BY username');
	while ($user = mysqli_fetch_assoc($query)) {
		$n[0] = $n[1] = $n[2] = $n[3] = $n[4] = 0;
		$query2 = mysqli_query($db, 'SELECT
			flag,
			COUNT(*) AS counter 
			FROM counter 
			WHERE user_id = "' . (int) $user['user_id'] . '" 
			AND time > ' . (int) $timestamp . '
			GROUP BY flag');
		while ($album = mysqli_fetch_assoc($query2)) {
			$n[ $album['flag'] ] = $album['counter'];
		}
?>
<tr class="<?php if ($cfg['username'] == $user['username']) echo 'select'; else echo ($i & 1) ? 'even mouseover' : 'odd mouseover'; $i++ ?>">
	<td ></td>
	<td class="nowrap"><a href="users.php?action=editUser&amp;user_id=<?php echo $user['user_id']; ?>"><img src="<?php echo $cfg['img']; ?>small_user.png" alt="" class="small space"><?php echo html($user['username']); ?></a></td>
	<td></td>
	<td class="matrix"><?php echo ($user['access_play'] || $user['access_add'])	? '<a href="index.php?action=viewPopular&amp;flag=0&amp;period=' . $period . '&amp;user_id=' . $user['user_id'] . '">' . $n[0] . '</a>' : '<img src="' . $cfg['img'] . 'small_uncheck.png" alt="" class="small">'; ?></td>
	<td class="matrix"><?php echo ($user['access_stream'])						? '<a href="index.php?action=viewPopular&amp;flag=1&amp;period=' . $period . '&amp;user_id=' . $user['user_id'] . '">' . $n[1] . '</a>' : '<img src="' . $cfg['img'] . 'small_uncheck.png" alt="" class="small">'; ?></td>
	<td class="matrix"><?php echo ($user['access_download'])					? '<a href="index.php?action=viewPopular&amp;flag=2&amp;period=' . $period . '&amp;user_id=' . $user['user_id'] . '">' . $n[2] . '</a>' : '<img src="' . $cfg['img'] . 'small_uncheck.png" alt="" class="small">'; ?></td>
	<td class="matrix"><?php echo ($user['access_cover'])						? '<a href="index.php?action=viewPopular&amp;flag=3&amp;period=' . $period . '&amp;user_id=' . $user['user_id'] . '">' . $n[3] . '</a>' : '<img src="' . $cfg['img'] . 'small_uncheck.png" alt="" class="small">'; ?></td>
	<td class="matrix"><?php echo ($user['access_record'])						? '<a href="index.php?action=viewPopular&amp;flag=4&amp;period=' . $period . '&amp;user_id=' . $user['user_id'] . '">' . $n[4] . '</a>' : '<img src="' . $cfg['img'] . 'small_uncheck.png" alt="" class="small">'; ?></td>
	<td></td>
</tr>
<?php
	}
	$n[0] = $n[1] = $n[2] = $n[3] = $n[4] = 0;
	$query = mysqli_query($db, 'SELECT
		flag,
		COUNT(*) AS counter 
		FROM counter 
		WHERE time > ' . (int) $timestamp . '
		GROUP BY flag');
	while ($album = mysqli_fetch_assoc($query)) {
		$n[ $album['flag'] ] = $album['counter'];
	}
?>
<tr class="footer">
	<td></td>
	<td><img src="<?php echo $cfg['img']; ?>small_users.png" alt="" class="small space">All users</td>
	<td></td>
	<td class="matrix"><?php echo $n[NJB_COUNTER_PLAY]; ?></td>
	<td class="matrix"><?php echo $n[NJB_COUNTER_STREAM]; ?></td>
	<td class="matrix"><?php echo $n[NJB_COUNTER_DOWNLOAD]; ?></td>
	<td class="matrix"><?php echo $n[NJB_COUNTER_COVER]; ?></td>
	<td class="matrix"><?php echo $n[NJB_COUNTER_RECORD]; ?></td>
	<td></td>
</tr>
</table>
</td></tr></table><!-- table tab wrapper -->
<a href="users.php?action=userStatistics&amp;period=<?php echo $period; ?>" class="button space">refresh</a><!--
--><a href="users.php?action=resetUserStatistics&amp;period=<?php echo $period; ?>&amp;sign=<?php echo $cfg['sign']; ?>" onclick="return confirm('Are you sure you want to reset all user statistics?')" class="button">reset</a>
<?php
	require_once('include/footer.inc.php');
}




//  +------------------------------------------------------------------------+
//  | Reset user statistics                                                  |
//  +------------------------------------------------------------------------+
function resetUserStatistics() {
	global $db;
	authenticate('access_admin', false, true, true);
	
	mysqli_query($db, 'TRUNCATE TABLE counter');
}
