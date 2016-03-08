<?php
include('includes/header.php');

$templating->set_previous('title', ' - User Control Panel', 1);

// what to show for the user text in the header
if ($_SESSION['user_id'] == 0)
{
	$core->message('You do not have permissions to view this page! <a href="index.php">Please click here to return to the home page</a>.');
	$templating->merge('footer');
	$templating->block('end');
	echo $templating->output();
	die();
}

$templating->block('left');

if ($user->check_group(6) == false)
{
	$templating->block('mainad');
}

// Here we sort out what modules we are allowed to load
$modules_allowed = '';
$module_links = '';
$db->sqlquery('SELECT `module_file_name`, `module_link`, `module_title`, `show_in_sidebar` FROM `usercp_modules` WHERE `activated` = 1');
while ($modules = $db->fetch())
{	
	// modules allowed for loading
	$modules_allowed .= " {$modules['module_file_name']} ";

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

$modules_check = explode(" ", $modules_allowed);

if (in_array($module, $modules_check))
{
	include("usercp_modules/usercp_module_$module.php");
}

else
{
	$core->message('Not a valid module name!');
}

$templating->block('left_end', 'mainpage');

// The block that starts off the html for the left blocks
$templating->block('right', 'mainpage');

// get the blocks
$db->sqlquery('SELECT `block_link`, `left`, `block_title_link`, `block_title`, `block_custom_content` FROM `usercp_blocks` WHERE `activated` = 1');
$blocks = $db->fetch_all_rows();

foreach ($blocks as $block)
{
	if ($block['left'] == 1 && $block['block_link'] != NULL)
	{
		include("usercp_blocks/{$block['block_link']}.php");
	}

	else if ($block['left'] == 1 && $block['block_link'] == NULL)
	{
		$templating->merge('usercp_blocks/block_custom');
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
		$templating->set('block_content', bbcode($block['block_custom_content']));
	}
}

$templating->block('right_end', 'mainpage');

include('includes/footer.php');
?>
