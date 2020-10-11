<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->set_previous('title', 'Submit a sale', 1);

$templating->load('submit_sale');

if (!isset($_POST['act']))
{
	$templating->block('main');
	// get stores names and id's
	$stores = $dbl->run("SELECT `id`, `name` FROM `game_stores` ORDER BY `name` ASC")->fetch_all();

	// add bundle
	$templating->set_many(['name' => '', 'total' => '', 'link' => '', 'end_date' => '', 'end_time' => '', 'id' => '']);
	$templating->set('id', 'addbundle');
	$templating->set('act', 'add_bundle');
	$templating->set('button_text', 'Add Bundle');

	$store_options = '';
	foreach ($stores as $store)
	{
		$store_options .= '<option value="'.$store['id'].'">'.$store['name'].'</option>';
	}
	$templating->set('stores', $store_options);

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
		$end_date = trim($_POST['end_date'] . ' ' . $_POST['end_time']);

		$empty_check = core::mempty(compact('name','total','link','end_date', 'end_time'));
		if ($empty_check !== true)
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = $empty_check;
			header("Location: /index.php?module=submit_sale");
			die();
		}

		// make sure end date isn't before today
		if( strtotime('now') > strtotime($end_date) ) 
		{
			$_SESSION['message'] = 'end_date_wrong';
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

		$core->new_admin_note(array('content' => ' submitted a new bundle for the sales page named: <a href="/admin.php?module=sales&view=submitted_bundles">'.$name.'</a>.', 'type' => 'submitted_sale_bundle', 'data' => $bundle_id));

		$_SESSION['message'] = 'bundle_submitted';
		header("Location: /sales.php");
		die();
	}
}