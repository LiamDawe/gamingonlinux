<?php
$templating->set_previous('title', 'Submit a sale', 1);

$templating->load('submit_sale');

if (!isset($_POST['act']))
{
	$templating->block('main');
	// get stores names and id's
	$stores = $dbl->run("SELECT `id`, `name` FROM `game_stores` ORDER BY `name` ASC")->fetch_all();

	// add bundle
	$templating->set_many(['name' => '', 'total' => '', 'link' => '', 'end_date' => '', 'id' => '']);
	$templating->set('id', 'addbundle');
	$templating->set('act', 'add_bundle');
	$templating->set('button_text', 'Add Bundle');

	$store_options = '';
	foreach ($stores as $store)
	{
		$store_options .= '<option value="'.$store['id'].'">'.$store['name'].'</option>';
	}
	$templating->set('stores', $store_options);

	if ($core->config('pretty_urls') == 0)
	{
		$email_link = '/index.php?module=email_us';
	}
	else if ($core->config('pretty_urls') == 1)
	{
		$email_link = '/email-us/';
	}
	$templating->set('email_link', $email_link);

	// add a normal sale
}

if (isset($_POST['act']))
{
	// add a bundle
	if ($_POST['act'] == 'add_bundle')
	{
		// check empty
		$name = trim($_POST['name']);
		$total = trim($_POST['total']);
		$link = trim($_POST['link']);
		$end_date = trim($_POST['end_date']);

		$empty_check = core::mempty(compact('name','total','link','end_date'));
		if ($empty_check !== true)
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = $empty_check;
			header("Location: /index.php?module=submit_sale");
			die();
		}

		// check exists
		$checker = $dbl->run("SELECT 1 FROM `sales_bundles` WHERE `name` = ?", [$name])->fetch();
		if ($checker)
		{
			$_SESSION['message'] = 'exists';
			$_SESSION['message_extra'] = 'bundle';
			header("Location: /index.php?module=submit_sale");
			die();
		}

		$dbl->run("INSERT INTO `sales_bundles` SET `name` = ?, `linux_total` = ?, `link` = ?, `end_date` = ?, `store_id` = ?, `approved` = 0", [$name, $total, $link, $end_date, $_POST['store']]);

		$bundle_id = $dbl->new_id();

		$dbl->run("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 0, `type` = ?, `created_date` = ?, `data` = ?", array($_SESSION['user_id'], 'submitted_sale_bundle', core::$date, $bundle_id));

		$_SESSION['message'] = 'saved';
		$_SESSION['message_extra'] = 'bundle';
		header("Location: /sales.php");
		die();
	}
}