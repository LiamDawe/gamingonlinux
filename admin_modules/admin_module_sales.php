<?php
$templating->merge('admin_modules/admin_module_sales');


if (isset($_GET['view']) && !isset($_POST['act']))
{
	// add article
	if ($_GET['view'] == 'add')
	{
		$templating->block('add');

		// get providers
		$providers_list = '';
		$db->sqlquery("SELECT * FROM `game_sales_provider` ORDER BY `name` ASC");
		while ($providers = $db->fetch())
		{
			if ($providers['provider_id'] == 0)
			{
				$providers_list .= "<option value=\"{$providers['provider_id']}\" selected>{$providers['name']}</option>";
			}

			else
			{
				$providers_list .= "<option value=\"{$providers['provider_id']}\">{$providers['name']}</option>";
			}
		}
		$templating->set('providers_list', $providers_list);
	}

	if ($_GET['view'] == 'edit')
	{
		$id = $_GET['id'];

		// make sure its a number
		if (!is_numeric($id))
		{
			$core->message('That is not a correct ID!');
		}

		else
		{
			if (isset($_GET['error']) && $_GET['error'] == 'fields')
			{
				$core->message('You have to fill in all fields - name, saving, price and website address!', NULL, 1);
			}

			$db->sqlquery("SELECT `id`, `info`, `website`, `provider_id`, `drmfree`, `pr_key`, `steam`, `savings`, `pwyw`, `beat_average`, `pounds`, `pounds_original`, `dollars`, `dollars_original`, `euros`, `euros_original`, `has_screenshot`, `screenshot_filename`, `expires`, `pre-order` FROM `game_sales` WHERE `id` = ?", array($id));

			$item = $db->fetch();

			// get the edit row
			$templating->block('edit', 'admin_modules/admin_module_sales');

			$formaction = '';
			if (isset($_GET['submitted']))
			{
				$formaction = '&submitted';
			}
			$templating->set('formaction', $formaction);

			// get providers
			$providers_list = '';
			$db->sqlquery("SELECT * FROM `game_sales_provider` ORDER BY `name` ASC");
			while ($providers = $db->fetch())
			{
				if ($providers['provider_id'] == $item['provider_id'])
				{
					$providers_list .= "<option value=\"{$providers['provider_id']}\" selected>{$providers['name']}</option>";
				}

				else
				{
					$providers_list .= "<option value=\"{$providers['provider_id']}\">{$providers['name']}</option>";
				}
			}

			$templating->set('providers_list', $providers_list);

			$templating->set('info', htmlentities($item['info'], ENT_QUOTES));

			$screenshot = '';
			$screenshot_delete = '';
			if ($item['has_screenshot'] == 1)
			{
				$screenshot = "<br /><img src=\"/uploads/sales/{$item['screenshot_filename']}\" /><br />";
				$screenshot_delete = " <button class=\"red-button\" name=\"act\" value=\"deletescreenshot\">Delete Screen Shot</button>";
			}

			$templating->set('screenshot', $screenshot);
			$templating->set('screenshot_delete', $screenshot_delete);

			$templating->set('website', $item['website']);
			$templating->set('savings', $item['savings']);

			$pwyw = '';
			if ($item['pwyw'] == 1)
			{
				$pwyw = 'checked';
			}
			$templating->set('pwyw_check', $pwyw);

			$beat_average = '';
			if ($item['beat_average'] == 1)
			{
				$beat_average = 'checked';
			}
			$templating->set('average_check', $beat_average);

			$templating->set('pounds', $item['pounds']);
			$templating->set('dollars', $item['dollars']);
			$templating->set('euros', $item['euros']);
			$templating->set('pounds_original', $item['pounds_original']);
			$templating->set('dollars_original', $item['dollars_original']);
			$templating->set('euros_original', $item['euros_original']);
			$templating->set('id', $item['id']);

			$preorder = '';
			$drmfree = '';
			$pr_key = '';
			$steam = '';

			if ($item['pre-order'] == 1)
			{
				$preorder = 'checked';
			}
			if ($item['drmfree'] == 1)
			{
				$drmfree = 'checked';
			}
			if ($item['pr_key'] == 1)
			{
				$pr_key = 'checked';
			}
			if ($item['steam'] == 1)
			{
				$steam = 'checked';
			}

			$templating->set('preorder_check', $preorder);
			$templating->set('drmfree_check', $drmfree);
			$templating->set('prkey_check', $pr_key);
			$templating->set('steam_check', $steam);

			$expires = '';
			if ($item['expires'] != 0)
			{
				$expires = date("Y-m-d H:i:s", $item['expires']);
			}

			$templating->set('expires', $expires);
		}
	}

	// manage sales
	if ($_GET['view'] == 'manage')
	{
		if (isset($_GET['message']) && $_GET['message'] == 'deleted')
		{
			$core->message('That game sale has now been deleted!');
		}

		$templating->block('manage_top', 'admin_modules/admin_module_sales');

		$db->sqlquery("SELECT s.`id`,s.`info`, s.`website`, s.`date`, s.`provider_id`, s.pwyw, s.beat_average, s.pounds, s.pounds_original, s.dollars, s.dollars_original, s.euros, s.euros_original, s.savings, s.reported, s.has_screenshot, s.screenshot_filename, s.drmfree, s.pr_key, s.steam, s.expires, s.`pre-order`, p.`name` FROM `game_sales` s LEFT JOIN `game_sales_provider` p ON s.provider_id = p.provider_id WHERE s.accepted = 1 ORDER BY s.reported DESC, s.`id` DESC");

		while ($sale = $db->fetch())
		{
			// get the sale row template
			$templating->block('manage_row', 'admin_modules/admin_module_sales');

			// check to see if we need to put in the category name or not
			$provider = '';
			if ($sale['provider_id'] != 0)
			{
				$provider = "<span class=\"sale_provider\">{$sale['name']}</span>";
			}
			$templating->set('provider', $provider);

			$templating->set('info', $sale['info']);
			$templating->set('info_link', $sale['website']);

			$screenshot = '';
			if ($sale['has_screenshot'] == 1)
			{
				$screenshot = "<br /><img src=\"/uploads/sales/{$sale['screenshot_filename']}\" /><br />";
			}

			$templating->set('screenshot', $screenshot);

			$templating->set('id', $sale['id']);
			$templating->set('savings', $sale['savings']);

			$pwyw = '';
			if ($sale['pwyw'] == 1)
			{
				$pwyw = '<span class="badge blue">PWYW</span>';
			}
			$templating->set('pwyw', $pwyw);

			$beat_average = '';
			if ($sale['beat_average'] == 1)
			{
				$beat_average = '<span class="badge blue">Beat Average</span>';
			}
			$templating->set('beat_average', $beat_average);

			$templating->set('pounds', $sale['pounds']);
			$templating->set('dollars', $sale['dollars']);
			$templating->set('euros', $sale['euros']);
			$templating->set('pounds_original', $sale['pounds_original']);
			$templating->set('dollars_original', $sale['dollars_original']);
			$templating->set('euros_original', $sale['euros_original']);

			$templating->set('website', $sale['website']);

			$reported = '';
			if ($sale['reported'] == 1)
			{
				$reported = '<button class="red-button" name="act" value="removeended">Remove Ended Report</button><br />
				<strong>This sale has been reported as having ended please check and delete or remove ended notification!</strong>';
			}

			$templating->set('reported', $reported);

			$preorder = '';
			$drmfree = '';
			$pr_key = '';
			$steam = '';

			if ($sale['pre-order'] == 1)
			{
				$preorder = ' <span class="badge blue">Pre-order</span>';
			}
			if ($sale['drmfree'] == 1)
			{
				$drmfree = ' <span class="badge green">DRM Free</span>';
			}
			if ($sale['pr_key'] == 1)
			{
				$pr_key = ' <span class="badge badge-warning">Requires Product Key</span>';
			}
			if ($sale['steam'] == 1)
			{
				$steam = '<span class="badge badge-default">Steam Key</span>';
			}

			$templating->set('preorder', $preorder);
			$templating->set('drmfree', $drmfree);
			$templating->set('pr_key', $pr_key);
			$templating->set('steam', $steam);

			$expires = '';
			if ($sale['expires'] != 0)
			{
				$expires = date("j-M-Y H:i:s", $sale['expires']);
			}

			$templating->set('expires', $expires);
		}
		$templating->block('manage_bottom', 'admin_modules/admin_module_sales');

	}

	// manage sales
	if ($_GET['view'] == 'managesteam')
	{
		if (isset($_GET['message']))
		{
			if ($_GET['message'] == 'deleted')
			{
				$core->message('That game/bundle has now been deleted!', NULL, 1);
			}

			if ($_GET['message'] == 'added')
			{
				$core->message("{$_GET['name']} game/bundle has now been added!");
			}

			if ($_GET['message'] == 'duplicate')
			{
				$core->message("{$_GET['name']} was already in the list!", NULL, 1);
			}

			if ($_GET['message'] == 'updated')
			{
				$core->message("{$_GET['name']} game/bundle has now been added!");
			}

			if ($_GET['message'] == 'empty')
			{
				$core->message('You have to fill in a name and id!', NULL, 1);
			}
		}

		$templating->block('steam_top', 'admin_modules/admin_module_sales');

		$templating->block('add_steam', 'admin_modules/admin_module_sales');

		// paging for pagination
		if (!isset($_GET['page']))
		{
			$page = 1;
		}

		else if (is_numeric($_GET['page']))
		{
			$page = $_GET['page'];
		}

		// count how many there is in total
		$db->sqlquery("SELECT `steam_appid` FROM `steam_list`");
		$total = $db->num_rows();

		// sort out the pagination link
		$pagination = $core->pagination_link(14, $total, "/admin.php?module=sales&view=managesteam&", $page);

		$db->sqlquery("SELECT `steam_appid`, `name`, `bundle`, `local_id` FROM `steam_list` ORDER BY `local_id` DESC LIMIT ?, 14", array($core->start), 'admin_module_sales.php');

		while ($steam = $db->fetch())
		{
			// get the sale row template
			$templating->block('steam_row', 'admin_modules/admin_module_sales');

			$templating->set('steam_name', htmlentities($steam['name'], ENT_QUOTES));
			$templating->set('steam_id', $steam['steam_appid']);

			$steam_bundle = '';
			if ($steam['bundle'] == 1)
			{
				$steam_bundle = 'checked';
			}
			$templating->set('steam_bundle', $steam_bundle);
			$templating->set('local_id', $steam['local_id']);
		}

		$templating->block('steam_bottom', 'admin_modules/admin_module_sales');
		$templating->set('pagination', $pagination);
	}

	if ($_GET['view'] == 'submitted')
	{
		if (isset($_GET['accepted']))
		{
			$core->message('Sale has been accepted!');
		}

		if (isset($_GET['acceptedalready']))
		{
			$core->message('Sale was accepted by someone else already!');
		}

		$db->sqlquery("SELECT s.`id`,s.`info`, s.`website`, s.`date`, s.`provider_id`, s.pwyw, s.pounds, s.dollars, s.euros, s.savings, s.has_screenshot, s.drmfree, s.pr_key, s.steam, s.expires, s.screenshot_filename, s.submitted_by_id, s.`pre-order`, s.`beat_average`, u.username, p.`name` FROM `game_sales` s LEFT JOIN `game_sales_provider` p ON s.provider_id = p.provider_id LEFT JOIN `users` u ON s.submitted_by_id = u.user_id WHERE s.`accepted` = 0 ORDER BY s.`id` DESC");

		if ($db->num_rows() > 0)
		{
			while ($sale = $db->fetch())
			{
				// get the sale row template
				$templating->block('submitted_row', 'admin_modules/admin_module_sales');

				// check to see if we need to put in the category name or not
				$provider = '';
				if ($sale['provider_id'] != 0)
				{
					$provider = "<span class=\"sale_provider\">{$sale['name']}</span>";
				}
				$templating->set('provider', $provider);

				$templating->set('info', $sale['info']);

				$screenshot = '';
				if ($sale['has_screenshot'] == 1)
				{
					$screenshot = "<br /><img src=\"/uploads/sales/{$sale['screenshot_filename']}\" /><br />";
				}

				$templating->set('screenshot', $screenshot);

				$templating->set('id', $sale['id']);
				$templating->set('savings', $sale['savings']);

				$pwyw = '';
				if ($sale['pwyw'] == 1)
				{
					$pwyw = '<span class="label label-info">PWYW</span>';
				}
				$templating->set('pwyw', $pwyw);

				$beat_average = '';
				if ($sale['beat_average'] == 1)
				{
					$beat_average = '<span class="label label-info">Beat Average</span>';
				}
				$templating->set('beat_average', $beat_average);

				$templating->set('pounds', $sale['pounds']);
				$templating->set('dollars', $sale['dollars']);
				$templating->set('euros', $sale['euros']);
				$templating->set('website', $sale['website']);

				$preorder = '';
				$drmfree = '';
				$pr_key = '';
				$steam = '';
				$assets_only = '';
				if ($sale['pre-order'] == 1)
				{
					$preorder = ' <span class="label label-info">Pre-order</span>';
				}
				if ($sale['drmfree'] == 1)
				{
					$drmfree = ' <span class="label label-success">DRM Free</span>';
				}
				if ($sale['pr_key'] == 1)
				{
					$online = ' <span class="label label-warning">Product Key Required</span>';
				}
				if ($sale['steam'] == 1)
				{
					$steam = '<span class="label label-inverse">Steam</span>';
				}

				$templating->set('preorder', $preorder);
				$templating->set('drmfree', $drmfree);
				$templating->set('pr_key', $pr_key);
				$templating->set('steam', $steam);

				$expires = '';
				if ($sale['expires'] != 0)
				{
					$expires = 'Expiry Set: ' . date("j-M-Y H:i:s", $sale['expires']);
				}

				$templating->set('expires', $expires);

				$submitted_username = 'Guest';
				if ($sale['submitted_by_id'] != 0)
				{
					$submitted_username = $sale['username'];
				}

				$templating->set('submitted_username', $submitted_username);
			}
		}
		else
		{
			$core->message("Nothing to see here :(");
		}
	}

	if ($_GET['view'] == 'providers')
	{
		if (isset($_GET['message']))
		{
			if ($_GET['message'] == 'added')
			{
				$core->message("You have added {$_GET['name']} as a sale provider!");
			}

			if ($_GET['message'] == 'deleted')
			{
				$core->message('That game sale provider has now been deleted!');
			}
		}

		$templating->block('add_provider', 'admin_modules/admin_module_sales');

		// get the current categorys
		$provider_get = $db->sqlquery("SELECT `provider_id`, `name` FROM `game_sales_provider` ORDER BY `name` ASC");
		while ($provider = $db->fetch($provider_get))
		{
			$templating->block('provider_row', 'admin_modules/admin_module_sales');
			$templating->set('provider_name', $provider['name']);
			$templating->set('provider_id', $provider['provider_id']);
		}
	}
}

else if (isset($_POST['act']) && !isset($_GET['view']))
{
	if ($_POST['act'] == 'Add')
	{
		// make sure its not empty
		if (empty($_POST['info']) || empty($_POST['website']))
		{
			$core->message('You have to fill in all fields - name, at least one price and website address!', NULL, 1);
		}

		else if ((empty($_POST['pounds']) && empty($_POST['dollars']) && empty($_POST['euros'])) && !isset($_POST['pwyw']))
		{
			$core->message('You must add at least one price!', NULL, 1);
		}

		else
		{
			$preorder = 0;
			$drmfree = 0;
			$pr_key = 0;
			$steam = 0;
			$pwyw = 0;
			$beat_average = 0;
			$game_assets = 0;

			if (isset($_POST['preorder']))
			{
				$preorder = 1;
			}
			if (isset($_POST['drmfree']))
			{
				$drmfree = 1;
			}
			if (isset($_POST['pr_key']))
			{
				$pr_key = 1;
			}
			if (isset($_POST['steam']))
			{
				$steam = 1;
			}
			if (isset($_POST['pwyw']))
			{
				$pwyw = 1;
			}

			if (isset($_POST['beat_average']))
			{
				$beat_average = 1;
			}

			$expires = 0;
			if (isset($_POST['expires']))
			{
				$expires = strtotime(gmdate($_POST['expires']));
			}

			$db->sqlquery("INSERT INTO `game_sales` SET `info` = ?, `website` = ?, `date` = ?, `accepted` = 1, `provider_id` = ?, `drmfree` = ?, `pr_key` = ?, `steam` = ?, `savings` = ?, `pwyw` = ?, `beat_average` = ?, `pounds` = ?, `pounds_original` = ?, `dollars` = ?, `dollars_original` = ?, `euros` = ?, euros_original = ?, `expires` = ?, `pre-order` = ?", array($_POST['info'], $_POST['website'], core::$date, $_POST['provider'], $drmfree, $pr_key, $steam, $_POST['savings'], $pwyw, $beat_average, $_POST['pounds'], $_POST['pounds_original'], $_POST['dollars'], $_POST['dollars_original'], $_POST['euros'], $_POST['euros_original'], $expires, $preorder));

			$sale_id = $db->grab_id();

			if ($core->sale_image($sale_id, $_FILES['new_image']) == true)
			{
				// do nothing as uploaded fine
			}

			else if (!empty($core->error_message))
			{
				$core->message($core->error_message, NULL, 1);
			}

			$core->message('Sale has been posted! <a href="admin.php?module=sales&view=add">Click here to post more</a> or <a href="index.php">click here to go to the site home</a>.');
		}
	}

	if ($_POST['act'] == 'Edit')
	{
		// make sure its not empty
		if (empty($_POST['info']) || empty($_POST['website']))
		{
			header("Location: /admin.php?module=sales&view=edit&id={$_POST['id']}&error=fields");
		}

		else if ((empty($_POST['pounds']) && empty($_POST['dollars']) && empty($_POST['euros'])) && !isset($_POST['pwyw']))
		{
			header("Location: /admin.php?module=sales&view=edit&id={$_POST['id']}&error=fields");
		}

		else
		{
			$preorder = 0;
			$drmfree = 0;
			$pr_key = 0;
			$steam = 0;
			$pwyw = 0;
			$beat_average = 0;
			$game_assets = 0;

			if (isset($_POST['preorder']))
			{
				$preorder = 1;
			}
			if (isset($_POST['drmfree']))
			{
				$drmfree = 1;
			}
			if (isset($_POST['pr_key']))
			{
				$pr_key = 1;
			}
			if (isset($_POST['steam']))
			{
				$steam = 1;
			}
			if (isset($_POST['pwyw']))
			{
				$pwyw = 1;
			}
			if (isset($_POST['beat_average']))
			{
				$beat_average = 1;
			}

			$expires = 0;
			if (isset($_POST['expires']))
			{
				$expires = strtotime(gmdate($_POST['expires']));
			}

			$db->sqlquery("UPDATE `game_sales` SET `info` = ?, `website` = ?, `provider_id` = ?, `drmfree` = ?, `pr_key` = ?, `steam` = ?, `savings` = ?, `pwyw` = ?, `beat_average` = ?, `pounds` = ?, `pounds_original` = ?, `dollars` = ?, `dollars_original` = ?, `euros` = ?, `euros_original` = ?, `expires` = ?, `pre-order` = ? WHERE `id` = ?", array($_POST['info'], $_POST['website'], $_POST['provider'], $drmfree, $pr_key, $steam, $_POST['savings'], $pwyw, $beat_average, $_POST['pounds'], $_POST['pounds_original'], $_POST['dollars'], $_POST['dollars_original'], $_POST['euros'], $_POST['euros_original'], $expires, $preorder, $_POST['id']));

			if ($core->sale_image($_POST['id'], $_FILES['new_image']) == true)
			{
				// do nothing as uploaded fine
			}

			else if (!empty($core->error_message))
			{
				$core->message($core->error_message, NULL, 1);
			}

			if (isset($_GET['submitted']))
			{
				header('Location: admin.php?module=sales&view=submitted');
			}

			else
			{
				$core->message("Sale has been updated: <a href=\"/sales/\">View Sales</a> <a href=\"admin.php?module=sales&view=manage\">Edit More</a> or <a href=\"/\">Website Home</a>.");
			}
		}
	}

	if ($_POST['act'] == 'Delete')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$core->yes_no('Are you sure you want to delete that Sale?', "admin.php?module=sales&amp;id={$_POST['id']}&returntosales={$_GET['returntosales']}", "Delete");
		}

		else if (isset($_POST['no']))
		{
			header("Location: admin.php");
		}

		else if (isset($_POST['yes']))
		{
			if (!is_numeric($_GET['id']))
			{
				$core->message('That is not a correct id!');
			}

			else
			{
				// check post exists
				$db->sqlquery("SELECT `id`, `reported`,`accepted`,`has_screenshot`,`screenshot_filename` FROM `game_sales` WHERE `id` = ?", array($_GET['id']));
				if ($db->num_rows() != 1)
				{
					$core->message('That is not a correct id! Options: <a href="admin.php?module=sales&view=manage">Go back</a>.');
				}

				// Delete now
				else
				{
					$check = $db->fetch();

					$db->sqlquery("DELETE FROM `admin_notifications` WHERE `sale_id` = ?", array($_GET['id']));

					$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `action` = ?, `completed_date` = ? WHERE `sale_id` = ?", array("{$_SESSION['username']} deleted a sale.", core::$date, $_GET['id']));

					if ($check['has_screenshot'] == 1)
					{
						unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/sales/' . $check['screenshot_filename']);
					}

					$db->sqlquery("DELETE FROM `game_sales` WHERE `id` = ?", array($_GET['id']));

					if ($_GET['returntosales'] == 1)
					{
						header("Location: /sales/message=deleted");
					}

					else
					{
						header("Location: admin.php?module=sales&view=manage&message=deleted");
					}
				}
			}
		}
	}

	if ($_POST['act'] == 'removeended')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$core->yes_no('Are you sure you want to delete that ended Sale notification?', "admin.php?module=sales&amp;id={$_POST['id']}", "removeended");
		}

		else if (isset($_POST['no']))
		{
			header("Location: admin.php");
		}

		else if (isset($_POST['yes']))
		{
			if (!is_numeric($_GET['id']))
			{
				$core->message('That is not a correct id!');
			}

			else
			{
				// check post exists
				$db->sqlquery("SELECT `id` FROM `game_sales` WHERE `id` = ?", array($_GET['id']));
				if ($db->num_rows() != 1)
				{
					$core->message('That is not a correct id! Options: <a href="admin.php?module=saless&view=manage">Go back</a>.');
				}

				// Delete now
				else
				{
					$db->sqlquery("UPDATE `game_sales` SET `reported` = 0 WHERE `id` = ?", array($_GET['id']));

					$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `action` = ?, `completed_date` = ? WHERE `sale_id` = ?", array("{$_SESSION['username']} removed a sale ended report.", core::$date, $_GET['id']));

					$core->message('That sale ended notification has now been deleted! Options: <a href="admin.php?module=sales&view=manage">Go back</a>.');
				}
			}
		}
	}

	if ($_POST['act'] == 'Accept')
	{
		// first check it's not accepted already
		$db->sqlquery("SELECT `accepted`, `info` FROM `game_sales` WHERE `id` = ?", array($_POST['id']));
		$sale_check = $db->fetch();

		if ($sale_check['accepted'] == 0)
		{
			$db->sqlquery("UPDATE `game_sales` SET `accepted` = 1, `date` = ? WHERE `id` = ?", array(core::$date, $_POST['id']));

			$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `action` = ?, `completed_date` = ? WHERE `sale_id` = ?", array("{$_SESSION['username']} approved a submitted sale.", core::$date, $_POST['id']));

			header("Location: /admin.php?module=sales&view=submitted&accepted");
		}

		else if ($sale_check['accepted'] == 1)
		{
			header("Location: /admin.php?module=sales&view=submitted&acceptedalready");
		}
	}

	if ($_POST['act'] == 'deletescreenshot')
	{
		if (!isset($_POST['id']))
		{
			$core->message("Not a correct sale id set!", NULL, 1);
		}

		else
		{
			$db->sqlquery("SELECT `has_screenshot`,`screenshot_filename`,`info` FROM `game_sales` WHERE `id` = ?", array($_POST['id']));
			$sale = $db->fetch();

			// remove old avatar
			if ($sale['has_screenshot'] == 1)
			{
				unlink($_SERVER['DOCUMENT_ROOT'] . '/uploads/sales/' . $sale['screenshot_filename']);
			}

			$db->sqlquery("UPDATE `game_sales` SET `has_screenshot` = 0, `screenshot_filename` = '' WHERE `id` = ?", array($_POST['id']));

			$core->message("The sales image has now been deleted from \"{$sale['info']}\"! <a href=\"/sales/\">Back to sales.</a>");
		}
	}

	if ($_POST['act'] == 'add-provider')
	{
		if (empty($_POST['provider_name']))
		{
			$core->message('You have to fill in a name!');
		}

		else
		{
			$db->sqlquery("INSERT INTO `game_sales_provider` SET `name` = ?", array($_POST['provider_name']));

			header("Location: /admin.php?module=sales&view=providers&message=added&name={$_POST['provider_name']}");
		}
	}


	if ($_POST['act'] == 'edit-provider')
	{
		// make sure its not empty
		if (empty($_POST['provider_name']))
		{
			$core->message('You have to fill in a category name it cannot be empty!');
		}

		else
		{

			$db->sqlquery("UPDATE `game_sales_provider` SET `name` = ? WHERE `provider_id` = ?", array($_POST['provider_name'], $_POST['provider_id']));

			$core->message("Sale provider {$_POST['provider_name']} has been updated! <a href=\"admin.php?module=sales&view=providers\">Click here to edit more</a> or <a href=\"index.php\">click here to go to the site home</a>.");
		}
	}

	if ($_POST['act'] == 'delete-provider')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$core->yes_no('Are you sure you want to delete that sale provider?', "admin.php?module=sales&amp;provider_id={$_POST['provider_id']}", "delete-provider");
		}

		else if (isset($_POST['no']))
		{
			header("Location: admin.php?module=sales");
		}

		else if (isset($_POST['yes']))
		{
			if (!is_numeric($_GET['provider_id']))
			{
				$core->message('That is not a correct id!');
			}

			else
			{
				// check category exists
				$db->sqlquery("SELECT `provider_id`, `name` FROM `game_sales_provider` WHERE `provider_id` = ?", array($_GET['provider_id']));
				$get_info = $db->fetch();
				if ($db->num_rows() != 1)
				{
					$core->message('That is not a correct id!');
				}

				// Delete now
				else
				{
					$db->sqlquery("DELETE FROM `game_sales_provider` WHERE `provider_id` = ?", array($_GET['provider_id']));

					// set any sales from this provider to no category
					$db->sqlquery("UPDATE `game_sales` SET `provider_id` = 0 WHERE `provider_id` = ?", array($_GET['provider_id']));

					$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `action` = ?, `completed_date` = ? WHERE `article_id` = ?", array("{$_SESSION['username']} Deleted a sale provider.", core::$date, $_GET['article_id']));

					header("Location: /admin.php?module=sales&view=providers&message=deleted");
				}
			}
		}
	}

	if ($_POST['act'] == 'add-steam')
	{
		if (empty($_POST['name']) || empty($_POST['id']) || !is_numeric($_POST['id']))
		{
			header("Location: /admin.php?module=sales&view=managesteam&message=empty");
		}

		else
		{
			$db->sqlquery("SELECT `steam_appid` FROM `steam_list` WHERE `steam_appid` = ?", array($_POST['id']), 'admin_module_sales.php');
			if ($db->num_rows() == 1)
			{
				header("Location: /admin.php?module=sales&view=managesteam&message=duplicate&name={$_POST['name']}");
			}

			else
			{
				$bundle = 0;
				if (isset($_POST['bundle']))
				{
					$bundle = 1;
				}
				$db->sqlquery("INSERT INTO `steam_list` SET `steam_appid` = ?, `bundle` = ?, `name` = ?", array($_POST['id'],$bundle,$_POST['name']), 'admin_module_sales.php');

				$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `action` = ?, `completed_date` = ? WHERE `article_id` = ?", array("{$_SESSION['username']} added '{$_POST['name']}' to the Steam sales list", core::$date, $_GET['article_id']));

				header("Location: /admin.php?module=sales&view=managesteam&message=added&name={$_POST['name']}");
			}
		}
	}


	if ($_POST['act'] == 'edit-steam')
	{
		if (empty($_POST['name']) || empty($_POST['id']) || !is_numeric($_POST['id']))
		{
			header("Location: /admin.php?module=sales&view=managesteam&message=empty");
		}

		else
		{
			$bundle = 0;
			if (isset($_POST['bundle']))
			{
				$bundle = 1;
			}
			$db->sqlquery("UPDATE `steam_list` SET `name` = ?, `steam_appid` = ?, `bundle` = ? WHERE `local_id` = ?", array($_POST['name'], $_POST['id'], $bundle, $_POST['local_id']));

			$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `action` = ?, `completed_date` = ? WHERE `article_id` = ?", array("{$_SESSION['username']} updated '{$_POST['name']}' on the Steam sales list", core::$date, $_GET['article_id']));

			header("Location: /admin.php?module=sales&view=managesteam&message=updated&name={$_POST['name']}");
		}
	}

	if ($_POST['act'] == 'delete-steam')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$core->yes_no('Are you sure you want to delete that sale provider?', "admin.php?module=sales&amp;steam_id={$_POST['id']}", "delete-steam");
		}

		else if (isset($_POST['no']))
		{
			header("Location: admin.php?module=sales&view=managesteam");
		}

		else if (isset($_POST['yes']))
		{
			if (!is_numeric($_GET['steam_id']))
			{
				$core->message('That is not a correct id!');
			}

			else
			{
				// check category exists
				$db->sqlquery("SELECT `steam_appid`, `name` FROM `steam_list` WHERE `steam_appid` = ?", array($_GET['steam_id']));
				$grab_info = $db->fetch();
				if ($db->num_rows() != 1)
				{
					$core->message('That is not a correct id!');
				}

				// Delete now
				else
				{
					$db->sqlquery("DELETE FROM `steam_list` WHERE `steam_appid` = ?", array($_GET['steam_id']));

					$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `action` = ?, `completed_date` = ? WHERE `article_id` = ?", array("{$_SESSION['username']} deleted '{$grab_info['name']}' from the Steam sales list", core::$date, $_GET['article_id']));

					header("Location: /admin.php?module=sales&view=managesteam&message=deleted");
				}
			}
		}
	}
}
