<?php
define("APP_ROOT", dirname(__FILE__));
define('golapp', TRUE);
include(APP_ROOT . '/includes/header.php');

$templating->set_previous('title', ' - User Control Panel', 1);

// what to show for the user text in the header
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == 0)
{
	$templating->set_previous('title', 'No Access', 1);
	$core->message('You do not have permissions to view this page! You need to be logged in.');

	$user_session->login_form(core::current_page_url());

	include(APP_ROOT . '/includes/footer.php');
	die();
}

$templating->block('left');

// Here we sort out what modules we are allowed to load
$modules_allowed = [];
$module_links = '';
$get_modules = $dbl->run('SELECT `module_file_name`, `module_link`, `module_title`, `show_in_sidebar`, `activated` FROM `usercp_modules`')->fetch_all(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);
$get_modules = array_map('reset', $get_modules); // strip useless zero index
foreach ($get_modules as $modules)
{
	// links
	if ($modules['show_in_sidebar'] == 1)
	{
		$module_links .= "<li class=\"list-group-item\"><a href=\"{$modules['module_link']}\">{$modules['module_title']}</a></li>\r\n";
	}
}

// modules loading, first are we asked to load a module, if not use the default
if (isset($_GET['module']))
{
	$module = $_GET['module'];
}

else
{
	$module = 'home';
}

if (isset($_SESSION['message']))
{
	$extra = NULL;
	if (isset($_SESSION['message_extra']))
	{
		$extra = $_SESSION['message_extra'];
	}
	$message_map->display_message('usercp/'.$module, $_SESSION['message'], $extra);
}

if (array_key_exists($module, $get_modules))
{
	if ($get_modules[$module]['activated'] == 1)
	{
		include(APP_ROOT . "/usercp_modules/usercp_module_$module.php");
	}
	else
	{
		$core->message('That module is currently turned off!');
	}
}

else
{
	$core->message('Not a valid module name!');
}

$templating->block('left_end', 'mainpage');

// The block that starts off the html for the left blocks
$templating->block('right', 'mainpage');

// get the blocks
$blocks = $dbl->run('SELECT `block_link`, `left`, `block_title_link`, `block_title`, `block_custom_content` FROM `usercp_blocks` WHERE `activated` = 1')->fetch_all();
foreach ($blocks as $block)
{
	if ($block['left'] == 1 && $block['block_link'] != NULL)
	{
		include(APP_ROOT . "/usercp_blocks/{$block['block_link']}.php");
	}

	else if ($block['left'] == 1 && $block['block_link'] == NULL)
	{
		$templating->load('usercp_blocks/block_custom');
		$templating->block('block');
		// any title link?
		if (!empty($block['block_title_link']))
		{
			$title = "<a href=\"{$block['block_title_link']}\" target=\"_blank\">{$block['block_title']}</a>";
		}
		else
		{
			$title = $block['block_title'];
		}

		$templating->set('block_title', $title);
		$templating->set('block_content', $bbcode->parse_bbcode($block['block_custom_content']));
	}
}

$templating->block('right_end', 'mainpage');

include(APP_ROOT . '/includes/footer.php');
?>
