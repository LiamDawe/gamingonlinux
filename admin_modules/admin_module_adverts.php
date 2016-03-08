<?php
$templating->merge('admin_modules/admin_module_adverts');

if (isset($_GET['action']))
{
	if ($_GET['action'] == 'add')
	{
		if (empty($_POST['name']) || empty($_POST['url']) || empty($_POST['expires']) || empty($_POST['image']))
		{
			$core->message('You need to enter a name, url, main image and expire date!', NULL, 1);
		}
		
		else
		{
			$expires = 0;
			if (isset($_POST['expires']))
			{
				$expires = strtotime(gmdate($_POST['expires']));
			}
			
			$advert_check = 0;
			if (isset($_POST['active']))
			{
				$advert_check = 1;
			}
			
			$db->sqlquery("INSERT INTO `adverts` SET `name` = ?, `url` = ?, `user_id` = ?, `expire_date` = ?, `active` = ?, `position` = ?, `advert_image` = ?, `advert_image_smaller` = ? ", array($_POST['name'], $_POST['url'], $_POST['user_id'], $expires, $advert_check, $_POST['position'], $_POST['image'], $_POST['smaller_image']));
			
			header("Location: admin.php?module=adverts&message=added");
		}
	}
	
	if ($_GET['action'] == 'update')
	{
		if (empty($_POST['name']) || empty($_POST['url']) || empty($_POST['expires']) || empty($_POST['image']))
		{
			$core->message('You need to enter a name, url, main image and expire date!', NULL, 1);
		}
		
		else
		{
			$expires = 0;
			if (isset($_POST['expires']))
			{
				$expires = strtotime(gmdate($_POST['expires']));
			}
			
			$advert_check = 0;
			if (isset($_POST['active']))
			{
				$advert_check = 1;
			}
			
			$db->sqlquery("UPDATE `adverts` SET `name` = ?, `url` = ?, `user_id` = ?, `expire_date` = ?, `active` = ?, `position` = ?, `advert_image` = ?, `advert_image_smaller` = ? WHERE `advert_id` = ?", array($_POST['name'], $_POST['url'], $_POST['user_id'], $expires, $advert_check, $_POST['position'], $_POST['image'], $_POST['smaller_image'], $_GET['id']));
			
			header("Location: admin.php?module=adverts&message=edited");
		}
	}
	
	if ($_GET['action'] == 'delete')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$core->yes_no('Are you sure you want to delete that advert?', "admin.php?module=adverts&action=delete&id={$_GET['id']}");
		}

		else if (isset($_POST['no']))
		{
			header("Location: admin.php?module=adverts");
		}

		else if (isset($_POST['yes']))
		{
			$db->sqlquery("DELETE FROM `adverts` WHERE `advert_id` = ?", array($_GET['id']));
			
			header("Location: admin.php?module=adverts&message=deleted");
		}
	}
}

if (!isset($_GET['action']))
{
	if (isset($_GET['message']))
	{
		if ($_GET['message'] == 'added')
		{
			$core->message('That advert has been added!');
		}
		
		if ($_GET['message'] == 'edited')
		{
			$core->message('That advert has been updated!');
		}
		
		if ($_GET['message'] == 'deleted')
		{
			$core->message('Advert deleted!', NULL, 1);
		}
	}
	$templating->block('top', 'admin_modules/admin_module_adverts');
	
	$templating->block('add', 'admin_modules/admin_module_adverts');

	// list all adverts
	$db->sqlquery("SELECT `advert_id`, `name`, `total_clicks`, `url`, `user_id`, `expire_date`, `active`, `position`, `advert_image`, `advert_image_smaller` FROM `adverts` ORDER BY `advert_id` ASC");

	while ($advert = $db->fetch())
	{
		$templating->block('row', 'admin_modules/admin_module_adverts');
		$templating->set('advert_id', $advert['advert_id']);
		$templating->set('name', $advert['name']);
		$templating->set('user_id', $advert['user_id']);
		$templating->set('total_clicks', $advert['total_clicks']);
		$templating->set('url', $advert['url']);
	
		$advert_check = '';
		if ($advert['active'] == 1)
		{
			$advert_check = 'checked';
		}
		$templating->set('active_check', $advert_check);
	
		$expires = date("d-m-Y H:i:s", $advert['expire_date']);
		$templating->set('expires', $expires);
	
		$positions = '';
		if ($advert['position'] == 'top')
		{
			$positions .= '<option value="top" selected>top</option>"';
			$positions .= '<option value="sidebar">sidebar</option>"';
		}
	
		else if ($advert['position'] == 'sidebar')
		{
			$positions .= '<option value="top">top</option>"';
			$positions .= '<option value="sidebar" selected>sidebar</option>"';
		}
	
		$templating->set('positions', $positions);
	
		$templating->set('image', $advert['advert_image']);
		$templating->set('image_smaller', $advert['advert_image_smaller']);
	}
}
