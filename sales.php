<?php
if (isset($_GET['reset']))
{
	header("Location: /sales.php");
}
include('includes/header.php');

require_once("includes/curl_data.php");

$templating->set_previous('title', 'Linux Games On Sale', 1);
$templating->set_previous('meta_description', 'Linux games on sale', 1);

$templating->merge('sales');

$templating->block('main');

if (isset($_POST['action']))
{
	if ($_POST['action'] == 'report')
	{
		if (empty($_POST['id']))
		{
			header('Location: /sales/message=id');
		}

		else
		{
			if ($_SESSION['user_id'] != 0)
			{
				// first make sure it's not reported already, so we don't double up notifications
				$db->sqlquery("SELECT `reported` FROM `game_sales` WHERE `id` = ?", array($_POST['id']), 'sales.php');
				$reported_check = $db->fetch();

				if ($reported_check['reported'] == 1)
				{
					header("Location: /sales/message=reportedalready");
				}
				else
				{
					// update admin notifications
					$db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 0, `action` = ?, `created` = ?, `sale_id` = ?", array("{$_SESSION['username']} reporteda sale.", $core->date, $_POST['id']));

					$db->sqlquery("UPDATE `game_sales` SET `reported` = 1 WHERE `id` = ?", array($_POST['id']), 'sales.php');

					header("Location: /sales/message=reported");
				}
			}
		}
	}

	if ($_POST['action'] == 'submit')
	{
		// make sure its not empty
		if (empty($_POST['info']) || empty($_POST['website']))
		{
			header('Location: /sales/message=empty');
		}

		else if ((empty($_POST['pounds']) && empty($_POST['dollars']) && empty($_POST['euros'])) && !isset($_POST['pwyw']) && !isset($_POST['beat_average']))
		{
			header('Location: /sales/message=empty');
		}

		else
		{
			if ($_SESSION['user_group'] == 4)
			{
				$recaptcha=$_POST['g-recaptcha-response'];
				$google_url="https://www.google.com/recaptcha/api/siteverify";
				$secret='6LcT0gATAAAAAJrRJK0USGyFE4pFo-GdRTYcR-vg';
				$ip=$core->ip;
				$url=$google_url."?secret=".$secret."&response=".$recaptcha."&remoteip=".$ip;
				$res=getCurlData($url);
				$res= json_decode($res, true);
			}

			if ($_SESSION['user_group'] == 4 && !$res['success'])
			{
				header('Location: /sales/message=captcha');
			}

			else if (($_SESSION['user_group'] == 4 && $res['success']) || $_SESSION['user_group'] != 4)
			{
				// if they are not an editor or admin add a notification and set accepted status to 0
				if ($user->check_group(1,2) == false)
				{
					$accepted = 0;
				}

				// they must be an editor or admin then
				if ($user->check_group(1,2) == true)
				{
					$accepted = 1;
				}

				$preorder = 0;
				$drmfree = 0;
				$pr_key = 0;
				$steam = 0;
				$pwyw = 0;
				$beat_average = 0;
				$game_assets = 0;

				if (isset($_POST['pre-order']))
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

				$info_sanatized = htmlspecialchars($_POST['info']);
				$db->sqlquery("INSERT INTO `game_sales` SET `info` = ?, `website` = ?, `date` = ?, `accepted` = ?, `provider_id` = ?, `drmfree` = ?, `pr_key` = ?, `steam` = ?, `pwyw` = ?, `beat_average` = ?, `savings` = ?, `pounds` = ?, `pounds_original` = ?, `dollars` = ?, `dollars_original` = ?, `euros` = ?, `euros_original` = ?, `expires` = ?, `submitted_by_id` = ?, `pre-order` = ?", array($info_sanatized, $_POST['website'], $core->date, $accepted, $_POST['provider'], $drmfree, $pr_key, $steam, $pwyw, $beat_average, $_POST['savings'], $_POST['pounds'],$_POST['pounds_original'], $_POST['dollars'], $_POST['dollars_original'],$_POST['euros'], $_POST['euros_original'],$expires, $_SESSION['user_id'], $preorder), 'sales.php');

				$sale_id = $db->grab_id();
				// if they are not an editor or admin add a notification and set accepted status to 0
				if ($user->check_group(1,2) == false)
				{
					$db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 0, `action` = ?, `created` = ?, `sale_id` = ?", array("A submitted sale.", $core->date, $sale_id));
				}

				if ($core->sale_image($sale_id, $_FILES['new_image']) == true)
				{
					// do nothing as uploaded fine
				}

				else if (!empty($core->error_message))
				{
					$core->message($core->error_message, NULL, 1);
				}

				if ($user->check_group(1,2) == false)
				{
					header('Location: /sales/message=submitted');
				}

				else if ($user->check_group(1,2) == true)
				{
					header('Location: /sales/message=added');
				}
			}
		}
	}
}

if (isset($_GET['message']))
{
	if ($_GET['message'] == 'reported')
	{
		$core->message('Thank you for reporting an ended sale it helps to keep us up to date for you!');
	}

	if ($_GET['message'] == 'reportedalready')
	{
		$core->message('Thanks for the report, looks like it\'s already been reported!');
	}

	if ($_GET['message'] == 'added')
	{
		$core->message('Sale has been added!');
	}

	if ($_GET['message'] == 'deleted')
	{
		$core->message('Sale has been deleted!');
	}

	if ($_GET['message'] == 'submitted')
	{
		$core->message('Sale has been submitted, you will need to wait for it to be reviewed! Thank you for helping us!');
	}

	if ($_GET['message'] == 'empty')
	{
		$core->message('You have to give it a title, price and a website link!', NULL, 1);
	}

	if ($_GET['message'] == 'id')
	{
		$core->message('Sorry but there was no Game ID to report try again!', NULL, 1);
	}

	if ($_GET['message'] == 'captcha')
	{
		$core->message("You need to complete the captcha to prove you are human and not a bot! If you login you don't need to do the captcha!", NULL, 1);
	}
}

if (!isset($_GET['order']) || (isset($_GET['order']) && $_GET['order'] == 'desc'))
{
	$order = 'desc';
}

else if (isset($_GET['order']) && $_GET['order'] == 'asc')
{
	$order = 'asc';
}

$templating->block('main2', 'sales');
$templating->set('url', $config['website_url']);

$db->sqlquery("SELECT DISTINCT(`info`) FROM `game_sales`");
$total_count = $db->num_rows();

$templating->set('total_count', $total_count);

// Get the providers jump list
$provider_jump_list = '';
$db->sqlquery("SELECT `provider_id`, `name` FROM `game_sales_provider` ORDER BY `name` ASC");
while ($provider_query = $db->fetch())
{
	if (isset($_GET['stores']) && isset($_GET['search']))
	{
		if (in_array($provider_query['provider_id'], $_GET['stores']))
		{
			$provider_jump_list .= "<option value=\"{$provider_query['provider_id']}\" selected>{$provider_query['name']}</option>";
		}
		else
		{
			$provider_jump_list .= "<option value=\"{$provider_query['provider_id']}\">{$provider_query['name']}</option>\r\n";
		}
	}

	else
	{
		$provider_jump_list .= "<option value=\"{$provider_query['provider_id']}\">{$provider_query['name']}</option>\r\n";
	}
}

// set them
$templating->set('provider_jump_list', $provider_jump_list);

// games on sale stuff
$list = '';

$filter_sql  = '';
$price_sql = '';
$stores_sql = '';
// do the actual queries
if (isset($_GET['search']) && $_GET['search'] == 'on')
{
	$filters = array();
	$filter_counter = false;
	if (isset($_GET['drmfree']) && $_GET['drmfree'] == 'on')
	{
		$filter_counter = true;
		$filters[] = "(s.drmfree = 1)";
	}
	if (isset($_GET['online']) && $_GET['online'] == 'on')
	{
		$filter_counter = true;
		$filters[] = "(s.pr_key = 1)";
	}
	if (isset($_GET['steam']) && $_GET['steam'] == 'on')
	{
		$filter_counter = true;
		$filters[] = "(s.steam = 1)";
	}
	if (isset($_GET['price']) && $_GET['price'] == '$5')
	{
		$filter_counter = true;
		$filters_price = ' AND s.dollars < 5 ';
	}

	if (isset($_GET['price']) && $_GET['price'] == '$10')
	{
		$filter_counter = true;
		$filters_price = ' AND s.dollars < 10 ';
	}

	if (isset($_GET['price']) && $_GET['price'] == '£5')
	{
		$filter_counter = true;
		$filters_price = ' AND s.pounds < 5 ';
	}

	if (isset($_GET['price']) && $_GET['price'] == '£10')
	{
		$filter_counter = true;
		$filters_price = ' AND s.pounds < 10 ';
	}

	if (isset($_GET['price']) && $_GET['price'] == '5€')
	{
		$filter_counter = true;
		$filters_price = ' AND s.euros < 5 ';
	}

	if (isset($_GET['price']) && $_GET['price'] == '10€')
	{
		$filter_counter = true;
		$filters_price = ' AND s.euros < 10 ';
	}

	$stores = '';
	if (isset($_GET['stores']))
	{
		$filter_counter = true;
		$store_count = 0;
		foreach ($_GET['stores'] as $store)
		{
			$delimiter = '';
			if ($store_count > 0)
			{
				$delimiter = ',';
			}
			$stores .= $delimiter . $store;
			$store_count++;
		}
	}

	// if they clicked the filter button without any filters then send them back, stop being morons (it happend A LOT)
	if ($filter_counter == false && !isset($_GET['reset']))
	{
		header("Location: /sales/");
	}
	else
	{
		$filter_sql = '';
		if (!empty($filters))
		{
			$filter_implode = implode(' OR ', $filters);
			$filter_sql = "AND ( $filter_implode )";
		}

		$price_sql = '';
		if (!empty($filters_price))
		{
			$price_sql = $filters_price;
		}

		$stores_sql = '';
		if (!empty($stores))
		{
			$stores_sql = "AND s.provider_id IN ($stores)";
		}

		$db->sqlquery("SELECT s.`id`,s.`info`, s.`website`, s.`date`, s.`provider_id`, s.`drmfree`, s.`pr_key`, s.`steam`, s.savings, s.pwyw, s.beat_average, s.pounds, s.pounds_original, s.dollars, s.dollars_original, s.euros, s.euros_original, s.has_screenshot, s.screenshot_filename, s.`expires`, s.`pre-order`, s.`bundle`, p.`name` FROM `game_sales` s LEFT JOIN `game_sales_provider` p ON s.provider_id = p.provider_id WHERE s.`accepted` = 1  $filter_sql $price_sql $stores_sql ORDER BY s.date $order");
	}
}

else if (!isset($_GET['search']) && !isset($_GET['sale_id']))
{
	$db->sqlquery("SELECT s.`id`,s.`info`, s.`website`, s.`date`, s.`provider_id`, s.`pr_key`, s.`drmfree`, s.`steam`, s.savings, s.pwyw, s.beat_average, s.`pounds`,s.pounds_original, s.dollars,s.dollars_original, s.euros,s.euros_original, s.has_screenshot, s.screenshot_filename, s.`expires`, s.`pre-order`, s.`bundle`, p.`name` FROM `game_sales` s LEFT JOIN `game_sales_provider` p ON s.provider_id = p.provider_id WHERE s.`accepted` = 1 ORDER BY s.id $order");
}

else if (!isset($_GET['search']) && isset($_GET['sale_id']))
{
	$db->sqlquery("SELECT s.`id`,s.`info`, s.`website`, s.`date`, s.`provider_id`, s.`pr_key`, s.`drmfree`, s.`steam`, s.savings, s.pwyw, s.beat_average, s.`pounds`,s.pounds_original, s.dollars,s.dollars_original, s.euros,s.euros_original, s.has_screenshot, s.screenshot_filename, s.`expires`, s.`pre-order`, s.`bundle`, p.`name` FROM `game_sales` s LEFT JOIN `game_sales_provider` p ON s.provider_id = p.provider_id WHERE s.`accepted` = 1 AND s.id = ?", array($_GET['sale_id']));
}

$count = $db->num_rows();

$counter = 0;

while ($list = $db->fetch())
{
	$counter++;

	$templating->block('row', 'sales');

	// check to see if we need to put in the category name or not
	$provider = '';
	if ($list['provider_id'] != 0)
	{
		$provider = $list['name'];
	}

	$templating->set('provider', $provider);

	// sort out the pricing for each row, correct for missing signs
	if ($list['pwyw'] == 1)
	{
		$templating->set('pounds', '<span class="badge blue">PWYW</span>');

		$templating->set('pounds_original', '');

		$templating->set('dollars', '<span class="badge blue">PWYW</span>');

		$templating->set('dollars_original', '');

		$templating->set('euros', '<span class="badge blue">PWYW</span>');

		$templating->set('euros_original', '');
	}

	else if ($list['beat_average'] == 1)
	{
		$templating->set('pounds', '<span class="badge blue">Beat Average</span>');

		$templating->set('pounds_original', '');

		$templating->set('dollars', '<span class="badge blue">Beat Average</span>');

		$templating->set('dollars_original', '');

		$templating->set('euros', '<span class="badge blue">Beat Average</span>');

		$templating->set('euros_original', '');
	}

	else
	{
		$savings_pounds = '';
		if ($list['pounds_original'] != 0)
		{
			$savings = 1 - ($list['pounds'] / $list['pounds_original']);
			$savings_pounds = '<span class="badge blue">' . round($savings * 100) . '% off</span>';
		}

		else if (!empty($list['savings']))
		{
			$savings_pounds = "<span class=\"badge blue\">{$list['savings']}</span>";
		}

		$savings_dollars = '';
		if ($list['dollars_original'] != 0)
		{
			$savings = 1 - ($list['dollars'] / $list['dollars_original']);
			$savings_dollars = '<span class="badge blue">' . round($savings * 100) . '% off</span>';
		}

		else if (!empty($list['savings']))
		{
			$savings_dollars = "<span class=\"badge blue\">{$list['savings']}</span>";
		}

		$savings_euros = '';
		if ($list['euros_original'] != 0)
		{
			$savings = 1 - ($list['euros'] / $list['euros_original']);
			$savings_euros = '<span class="badge blue">' . round($savings * 100) . '% off</span>';
		}

		else if (!empty($list['savings']))
		{
			$savings_euros = "<span class=\"badge blue\">{$list['savings']}</span>";
		}

		if ($list['pounds_original'] == 0)
		{
			$templating->set('pounds_original', '');
		}

		else
		{
			$templating->set('pounds_original', '<strike>£' . $list['pounds_original'] . '</strike><br />');
		}

		if ($list['pounds'] == 0)
		{
			if ($list['pounds_original'] == 0)
			{
				$templating->set('pounds', '');
			}

			else
			{
				$templating->set('pounds', '£0.00<br />' . $savings_pounds);
			}
		}
		else
		{
			$templating->set('pounds', '£' . $list['pounds'] . '<br />' . $savings_pounds);
		}

		if ($list['dollars_original'] == 0)
		{
			$templating->set('dollars_original', '');
		}
		else
		{
			$templating->set('dollars_original', '<strike>$' . $list['dollars_original'] . '</strike><br />');
		}

		if ($list['dollars'] == 0)
		{
			if ($list['dollars_original'] == 0)
			{
				$templating->set('dollars', '');
			}

			else
			{
				$templating->set('dollars', '$0.00<br />' . $savings_dollars);
			}
		}
		else
		{
			$templating->set('dollars', '$' . $list['dollars'] . '<br />' . $savings_dollars);
		}

		if ($list['euros_original'] == 0)
		{
			$templating->set('euros_original', '');
		}
		else
		{
			$templating->set('euros_original', '<strike>' . $list['euros_original'] . '€</strike><br />');
		}

		if ($list['euros'] == 0)
		{
			if ($list['euros_original'] == 0)
			{
				$templating->set('euros', '');
			}

			else
			{
				$templating->set('euros', '0.00€<br />' . $savings_euros);
			}
		}
		else
		{
			$templating->set('euros', $list['euros'] . '€' . '<br />' . $savings_euros);
		}
	}

	$templating->set('pound_plain', $list['pounds']);
	$templating->set('dollar_plain', $list['dollars']);
	$templating->set('euro_plain', $list['euros']);

	// give an edit link if editor or admin
	$edit_link = '';
	if ($user->check_group(1,2) == true)
	{
		$edit_link = " <a href=\"/admin.php?module=sales&view=edit&id={$list['id']}\"><i class=\"icon-pencil\"></i>Edit</a>";
	}

	$templating->set('edit_link', $edit_link);

	// give a report link if logged in
	$report = '';
	if ($_SESSION['user_id'] != 0)
	{
		if ($user->check_group(1,2) == true)
		{
			$report = "<br /><form method=\"post\"><button type=\"submit\" name=\"act\" formaction=\"/admin.php?module=sales&returntosales=1\" value=\"Delete\" class=\"btn btn-danger\">Delete</button>
			<input type=\"hidden\" name=\"id\" value=\"{$list['id']}\" />
			</form>";
		}

		else
		{
			$report = "<br /><form method=\"post\" action=\"/sales.php\"><button class=\"btn btn-link\" name=\"action\" value=\"report\"><i class=\"icon-exclamation-sign\"></i> Report Sale Ended</button><input type=\"hidden\" name=\"id\" value=\"{$list['id']}\" /></form>";
		}
	}

	$templating->set('report_button', $report);

	$templating->set('sale_id', $list['id']);

	$website = $list['website'];
	if ($list['provider_id'] == 1 && $list['bundle'] == 1)
	{
		$steam_appid = filter_var($list['website'], FILTER_SANITIZE_NUMBER_INT);
		$website = "http://store.steampowsiered.com/sub/{$steam_appid}/";
	}
	$info = "<a href=\"{$website}\" target=\"_blank\">" . $list['info'] . "</a>";

	$templating->set('info', $info);
	$templating->set('name', $list['info']);

	$expires = '';
	if ($list['expires'] > 0)
	{
		$expires = $core->getRemaining($core->date, $list['expires']);
	}
	$templating->set('expires', $expires);

	// if there is no expire time, set it really high so they are displayed after games with an expire time
	if ($list['expires'] == 0)
	{
		$expire_data = 99999999999;
	}
	else
	{
		$expire_data = $list['expires'];
	}
	$templating->set('expire_time', $expire_data);

	$preorder = '';
	$drmfree = '';
	$pr_key = '';
	$steam = '';
	$game_assets = '';
	$bundle = '';

	if ($list['pre-order'] == 1)
	{
		$preorder = ' <span class="badge blue">Pre-order</span> ';
	}

	if ($list['drmfree'] == 1)
	{
		$drmfree = ' <span class="badge green">DRM Free</span> ';
	}
	if ($list['pr_key'] == 1)
	{
		$pr_key = ' <span class="badge badge-warning">Requires Product Key</span> ';
	}
	if ($list['steam'] == 1)
	{
		$steam = ' <span class="badge badge-default">Steam Key</span> ';
	}
	if ($list['bundle'] == 1)
	{
		$bundle = ' <span class="badge blue">Game Bundle</span> ';
	}

	$templating->set('pre-order', $preorder);
	$templating->set('drmfree_check', $drmfree);
	$templating->set('prkey_check', $pr_key);
	$templating->set('steam_check', $steam);
	$templating->set('bundle_check', $bundle);
}

$templating->block('bottom', 'sales');
$templating->set('url', $config['website_url']);

// get providers
$providers_list = '';
$db->sqlquery("SELECT `provider_id`, `name` FROM `game_sales_provider` ORDER BY `name` ASC");
while ($providers = $db->fetch())
{
	$providers_list .= "<option value=\"{$providers['provider_id']}\">{$providers['name']}</option>";
}

$templating->set('providers_list', $providers_list);

include('includes/footer.php');
