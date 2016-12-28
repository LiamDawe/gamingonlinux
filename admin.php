<?php
error_reporting(E_ALL);

include('includes/header.php');

// stop anyone not allowed in
if ($parray['access_admin'] == 0)
{
	$templating->set_previous('title', 'No Access', 1);
	$core->message('You do not have permissions to view this page! <a href="index.php" class="white-link">Please click here to return to the home page</a>.', NULL, 1);
	include('includes/footer.php');
	die();
}

$templating->set_previous('title', ' - Admin and Editor Control Panel', 1);

$sql_editor = '';
if ($user->check_group(1) == false)
{
	$sql_editor = ' AND `admin_only` = 0';
}

// Here we sort out what modules we are allowed to load
$modules_allowed = '';
$module_links = '';
$get_modules_info = $db->sqlquery("SELECT `module_name`, `module_link`, `module_title`, `show_in_sidebar` FROM `admin_modules` WHERE `activated` = 1 $sql_editor");
while ($modules = $db->fetch())
{
	// modules allowed for loading
	$modules_allowed .= " {$modules['module_name']} ";

	// links
	if ($modules['show_in_sidebar'] == 1)
	{
		$module_links .= "<li><a href=\"{$modules['module_link']}\">{$modules['module_title']}</a></li>";
	}
}

$templating->block('left');

// modules loading, first are we asked to load a module, if not use the default
if (isset($_GET['module']))
{
	$module = $_GET['module'];
}

else
{
	$module = 'home';
}

$modules_check = explode(" ", $modules_allowed);

if (in_array($module, $modules_check))
{
	include("admin_modules/$module.php");
}

else
{
	$core->message('Not a valid module name!');
}

$templating->block('left_end', 'mainpage');

// The block that starts off the html for the left blocks
$templating->block('right', 'mainpage');

$sql_editor = '';
if ($user->check_group(1) == false)
{
	$sql_editor = ' AND `admin_only` = 0';
}

// blocks left
$db->sqlquery("SELECT * FROM `admin_blocks` WHERE `activated` = 1 $sql_editor ORDER BY `block_id` ASC");
$rights = array();
$right_number = 0;
$blocks = $db->fetch_all_rows();

foreach ($blocks as $block)
{
	if ($block['block_link'] != NULL)
	{
			include("admin_blocks/admin_block_{$block['block_link']}.php");
	}

	else if ($block['block_link'] == NULL)
	{
		$templating->merge('blocks/block_custom');
		$templating->set('block_title', $block['block_title']);
		$templating->set('block_content', $block['block_custom_content']);
	}
}

$templating->block('right_end', 'mainpage');

include('includes/footer.php');
?>
