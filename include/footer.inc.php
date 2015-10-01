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
//  | footer.inc.php                                                         |
//  +------------------------------------------------------------------------+
$footer = '<ul id="footer">' . "\n\t";
$footer .= ($cfg['username'] != '') ? '<li><a href="index.php?authenticate=logout">Logout: ' . html($cfg['username']) . '</a></li><!--' . "\n\t" . '-->' : '';
$footer .= '<li><a href="about.php">netjukebox ' . html(NJB_VERSION) . '</a></li><!--' . "\n";
$footer .= "\t" . '--><li><span>Script execution time: ' . executionTime() . '</span></li>' . "\n";
$footer .= '</ul>' . "\n";
$footer .= '<script type="text/javascript">' . (@$_COOKIE['netjukebox_sid'] ? 'init(); sessionCookie(); window.onbeforeunload = sessionCookie;' : 'init();') . '</script>' . "\n";

require_once(NJB_HOME_DIR . 'skin/' . $cfg['skin'] . '/template.footer.php');
exit();
