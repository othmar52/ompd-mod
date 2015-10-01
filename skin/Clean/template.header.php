<?php
//  +------------------------------------------------------------------------+
//  | template.header.php                                                    |
//  +------------------------------------------------------------------------+
if (isset($header) == false)
	exit();
?>
<!doctype html>
<?php echo $header['head']; ?>
<body>
<div id="tooltip"></div>
<div id="wrapper_container">
<div id="menu_container">
<?php
echo $header['menu'];
echo $header['submenu'];
?>
<a href="http://www.netjukebox.nl/" class="netjukebox"></a>
</div><!-- end menu_container -->
<div id="content_container">
<?php
echo $header['no_javascript'];
echo $header['navigator'];
