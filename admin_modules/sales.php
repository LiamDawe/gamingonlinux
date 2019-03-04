<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted: admin sales config.');
}

$templating->set_previous('title', 'Sales Admin', 1);

$templating->load('admin_modules/admin_module_sales');

if (!isset($_POST['act']) && isset($_GET['view']))
{
	// get stores names and id's
	$stores = $dbl->run("SELECT `id`, `name` FROM `game_stores` ORDER BY `name` ASC")->fetch_all();

	// manage bundles
	if ($_GET['view'] == 'manage_bundles')
	{
		// add bundle
		$templating->block('add_top', 'admin_modules/admin_module_sales');
		$templating->block('item', 'admin_modules/admin_module_sales');
		$templating->set_many(['approve' => '', 'remove' => '', 'name' => '', 'total' => '', 'link' => '', 'end_date' => '', 'id' => '']);
		$templating->set('id', 'addbundle');
		$templating->set('act', 'add_bundle');
		$templating->set('button_text', 'Add Bundle');

		$store_options = '';
		foreach ($stores as $store)
		{
			$store_options .= '<option value="'.$store['id'].'">'.$store['name'].'</option>';
		}
		$templating->set('stores', $store_options);

		// existing bundles
		$templating->block('edit_top', 'admin_modules/admin_module_sales');

		$existing_res = $dbl->run("SELECT b.`id`, b.`name`, b.`store_id`, b.`linux_total`, b.`link`, b.`end_date` FROM `sales_bundles` b LEFT JOIN `game_stores` s ON s.id = b.store_id WHERE b.`approved` = 1 ORDER BY b.`end_date` DESC")->fetch_all();

		foreach ($existing_res as $bundle)
		{
			$templating->block('item', 'admin_modules/admin_module_sales');
			$templating->set_many(['approve' => '', 'name' => $bundle['name'], 'total' => $bundle['linux_total'], 'link' => $bundle['link'], 'end_date' => $bundle['end_date']]);
			$templating->set('id', 'edit_'.$bundle['id']);
			$templating->set('act', 'edit_bundle');
			$templating->set('button_text', 'Edit Bundle');
			$templating->set('remove', '<button name="act" type="submit" value="remove_bundle">Remove</button>');
			$templating->set('return_page', 'manage_bundles');

			$store_options = '';
			foreach ($stores as $store)
			{
				$selected = '';
				if ($bundle['store_id'] == $store['id'])
				{
					$selected = 'selected';
				}
				$store_options .= '<option value="'.$store['id'].'" '.$selected.'>'.$store['name'].'</option>';
			}
			$templating->set('stores', $store_options);

			$templating->set('id', $bundle['id']);
		}
	}

	if ($_GET['view'] == 'submitted_bundles')
	{
		$templating->block('submitted_bundles_top', 'admin_modules/admin_module_sales');

		$existing_res = $dbl->run("SELECT b.`id`, b.`name`, b.`store_id`, b.`linux_total`, b.`link`, b.`end_date` FROM `sales_bundles` b LEFT JOIN `game_stores` s ON s.id = b.store_id WHERE b.`approved` = 0 ORDER BY b.`end_date` DESC")->fetch_all();
		
		foreach ($existing_res as $bundle)
		{
			$templating->block('item', 'admin_modules/admin_module_sales');
			$templating->set_many(['name' => $bundle['name'], 'total' => $bundle['linux_total'], 'link' => $bundle['link'], 'end_date' => $bundle['end_date']]);
			$templating->set('id', 'edit_'.$bundle['id']);
			$templating->set('act', 'edit_bundle');
			$templating->set('approve', '<button name="act" type="submit" value="approve_bundle">Approve</button>');
			$templating->set('button_text', 'Edit Bundle');
			$templating->set('remove', '<button name="act" type="submit" value="remove_bundle">Remove</button>');
			$templating->set('return_page', 'submitted_bundles');
		
			$store_options = '';
			foreach ($stores as $store)
			{
				$selected = '';
				if ($bundle['store_id'] == $store['id'])
				{
					$selected = 'selected';
				}
				$store_options .= '<option value="'.$store['id'].'" '.$selected.'>'.$store['name'].'</option>';
			}
			$templating->set('stores', $store_options);
		
			$templating->set('id', $bundle['id']);
		}
	}
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

		$empty_check = core::mempty(compact('name','link','end_date'));
		if ($empty_check !== true)
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = $empty_check;
			header("Location: /admin.php?module=sales&view=manage_bundles");
			die();
		}

		// check exists
		$checker = $dbl->run("SELECT 1 FROM `sales_bundles` WHERE `name` = ?", [$name])->fetchOne();
		if ($checker)
		{
			$_SESSION['message'] = 'exists';
			$_SESSION['message_extra'] = 'bundle';
			header("Location: /admin.php?module=sales&view=manage_bundles");
			die();
		}

		if (!isset($total) || $total == 0)
		{
			$total = NULL;
		}

		$dbl->run("INSERT INTO `sales_bundles` SET `name` = ?, `linux_total` = ?, `link` = ?, `end_date` = ?, `store_id` = ?, `approved` = 1", [$name, $total, $link, $end_date, $_POST['store']]);

		$core->new_admin_note(array('completed' => 1, 'content' => ' added a new bundle to the <a href="/sales/">sales page</a>.'));

		$_SESSION['message'] = 'saved';
		$_SESSION['message_extra'] = 'bundle';
		header("Location: /admin.php?module=sales&view=manage_bundles");
		die();		
	}

	// edit a bundle
	if ($_POST['act'] == 'edit_bundle')
	{
		// check empty
		$name = trim($_POST['name']);
		$total = trim($_POST['total']);
		$link = trim($_POST['link']);
		$end_date = trim($_POST['end_date']);
		$id = trim($_POST['id']);

		$empty_check = core::mempty(compact('name','link','end_date'));
		if ($empty_check !== true)
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = $empty;
			header("Location: /admin.php?module=sales&view=manage_bundles");
			die();
		}

		if (!isset($total) || $total == 0)
		{
			$total = NULL;
		}

		$dbl->run("UPDATE `sales_bundles` SET `name` = ?, `linux_total` = ?, `link` = ?, `end_date` = ?, `store_id` = ? WHERE `id` = ?", [$name, $total, $link, $end_date, $_POST['store'], $id]);

		$core->new_admin_note(array('completed' => 1, 'content' => ' updated the '.$name.' bundle on the <a href="/sales/">sales page</a>.'));

		$_SESSION['message'] = 'saved';
		$_SESSION['message_extra'] = 'bundle';
		header("Location: /admin.php?module=sales&view=". $_POST['return_page']);
		die();		
	}

	if ($_POST['act'] == 'remove_bundle')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$core->confirmation(['title' => 'Are you sure you wish remove that bundle?', 'act' => 'remove_bundle', 'action_url' => '/admin.php?module=sales&id=' . $_POST['id'], 'act_2_name' => 'return_page', 'act_2_value' => $_POST['return_page']]);		
		}
		else if (isset($_POST['no']))
		{
			header("Location: /admin.php?module=sales&view=" . $_POST['return_page']);
		}
		else if (isset($_POST['yes']))
		{
			$dbl->run("DELETE FROM `sales_bundles` WHERE `id` = ?", [$_GET['id']]);
			if ($_POST['return_page'] == 'submitted_bundles')
			{
				$core->update_admin_note(array('type' => 'submitted_sale_bundle', 'data' => $_GET['id']));

				$core->new_admin_note(array('completed' => 1, 'content' => ' removed a submitted bundle from the <a href="/sales/">sales page</a>.'));
			}
			else if ($_POST['return_page'] == 'manage_bundles')
			{
				$core->new_admin_note(array('completed' => 1, 'content' => ' removed a bundle from the <a href="/sales/">sales page</a>.'));				
			}

			$_SESSION['message'] = 'deleted';
			$_SESSION['message_extra'] = 'sale bundle';
			header("Location: /admin.php?module=sales&view=" . $_POST['return_page']);
		}		
	}

	if ($_POST['act'] == 'approve_bundle')
	{
		$dbl->run("UPDATE `sales_bundles` SET `approved` = 1 WHERE `id` = ?", [$_POST['id']]);

		$core->update_admin_note(array('type' => 'submitted_sale_bundle', 'data' => $_POST['id']));

		$core->new_admin_note(array('completed' => 1, 'content' => ' approved a submitted bundle for the <a href="/sales/">sales page</a>.'));

		$_SESSION['message'] = 'accepted';
		$_SESSION['message_extra'] = 'sale bundle';
		header("Location: /admin.php?module=sales&view=submitted_bundles");
	}
}