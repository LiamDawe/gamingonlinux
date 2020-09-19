<?php
define("APP_ROOT", dirname(__FILE__));
define('golapp', TRUE);

include(APP_ROOT . '/includes/header.php');

if (!$user->can('access_admin'))
{
	$templating->set_previous('title', 'No Access', 1);
	$core->message('You do not have permissions to view this page! <a href="index.php" class="white-link">Please click here to return to the home page</a>.', 1);
	include(APP_ROOT . '/includes/footer.php');
	die();
}

$templating->set_previous('title', ' - Admin and Editor Control Panel', 1);

$admin = new admin($dbl, $core);

$sql_editor = '';
if ($user->check_group(1) == false)
{
	$sql_editor = ' AND `admin_only` = 0';
}

// Here we sort out what modules we are allowed to load
$modules_allowed = [];
$module_links = '';
$get_modules_info = $dbl->run("SELECT `module_name`, `module_link`, `module_title`, `show_in_sidebar` FROM `admin_modules` WHERE `activated` = 1 $sql_editor")->fetch_all();
foreach ($get_modules_info as $modules)
{
	// modules allowed for loading
	$modules_allowed[] = $modules['module_name'];

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

if (in_array($module, $modules_allowed))
{
	if (isset($_SESSION['message']))
	{
		$extra = NULL;
		if (isset($_SESSION['message_extra']))
		{
			$extra = $_SESSION['message_extra'];
		}
		$message_map->display_message('admin/'.$module, $_SESSION['message'], $extra);
	}
	
	include(APP_ROOT . "/admin_modules/$module.php");
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
$blocks = $dbl->run("SELECT * FROM `admin_blocks` WHERE `activated` = 1 $sql_editor ORDER BY `block_id` ASC")->fetch_all();
$rights = array();
$right_number = 0;
foreach ($blocks as $block)
{
	if ($block['block_link'] != NULL)
	{
			include(APP_ROOT . "/admin_blocks/admin_block_{$block['block_link']}.php");
	}

	else if ($block['block_link'] == NULL)
	{
		$templating->load('blocks/block_custom');
		$templating->set('block_title', $block['block_title']);
		$templating->set('block_content', $block['block_custom_content']);
	}
}

$templating->block('right_end', 'mainpage');

include(APP_ROOT . '/includes/footer.php');
?>
