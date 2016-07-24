<?php
$templating->merge('admin_modules/admin_module_charts');


if (isset($_GET['view']) && !isset($_POST['act']))
{
	// add article
	if ($_GET['view'] == 'add')
	{
		$name = '';
		$labels = '';
		$data = '';
		$h_label = '';

		if (isset($_GET['message']))
		{
			$core->message('You have created the new chart!');
		}

		if (isset($_GET['error']))
		{
			if ($_GET['error'] == 'empty')
			{
				$core->message('You have to fill in all fields', NULL, 1);
			}

			if ($_GET['error'] == 'notenough')
			{
				$core->message('The amount of labels doesn\'t match the amount of data! You might have missed a label, or a bit of data to be included.', NULL, 1);
			}

			$name = $_SESSION['e_name'];
			$labels = $_SESSION['e_labels'];
			$data = $_SESSION['e_data'];
			$h_label = $_SESSION['e_h_label'];
		}

		$templating->block('add_chart', 'admin_modules/admin_module_charts');
		$templating->set('name', $name);
		$templating->set('labels', $labels);
		$templating->set('data', $data);
		$templating->set('h_label', $h_label);
	}

	if ($_GET['view'] == 'manage')
	{
		if (isset($_GET['message']))
		{
			if ($_GET['message'] == 'deleted')
			{
				$core->message('Chart Deleted!');
			}
		}
		$templating->block('manage_charts', 'admin_modules/admin_module_charts');

		$chart_list = '';
		$db->sqlquery("SELECT * FROM `charts` WHERE `owner` = ? AND `user_stats_chart` = 0 ORDER BY `id` DESC", array($_SESSION['user_id']));
		while($charts = $db->fetch())
		{
			$chart_list .= '<div class="box"><div class="body group"><a href="/admin.php?module=charts&view=edit&id='.$charts['id'].'">'.$charts['name'].'</a> - [chart]'.$charts['id'].'[/chart] - Generated: '.$charts['generated_date'].'<br />
			<form method="post">
			<button type="submit" name="act" value="Delete" formaction="/admin.php?module=charts">Delete</button>
			<input type="hidden" name="id" value="'.$charts['id'].'" />
			</form></div></div>';
		}

		$templating->set('chart_list', $chart_list);
	}

	if ($_GET['view'] == 'manage_stats')
	{
		if (isset($_GET['message']))
		{
			if ($_GET['message'] == 'deleted')
			{
				$core->message('Chart Deleted!');
			}
		}
		$templating->block('manage_charts', 'admin_modules/admin_module_charts');

		$chart_list = '';
		$db->sqlquery("SELECT * FROM `user_stats_charts` ORDER BY `id` DESC", array($_SESSION['user_id']));
		$grouping_id = '';
		while($charts = $db->fetch())
		{
			$charts['generated_date'] = date("Y-m-d", strtotime($charts['generated_date']));
			if ($grouping_id != $charts['grouping_id'])
			{
				$grouping_id = $charts['grouping_id'];
				$chart_list .= '<div class="box"><div class="head">Group ID: '.$grouping_id.', Date: '. $charts['generated_date'] .' <form method="post" style="display:inline"><button type="submit" name="act" value="Delete_Full" formaction="/admin.php?module=charts" style="float: none;">Delete Group</button><input type="hidden" name="grouping_id" value="'.$charts['grouping_id'].'" /></form></div></div>';
			}

			$chart_list .= '<div class="box"><div class="body group"><a href="/admin.php?module=charts&view=edit&id='.$charts['id'].'">'.$charts['name'].'</a> - [chart]'.$charts['id'].'[/chart] - Generated: '.$charts['generated_date'].'<br />
			<form method="post">
			<button type="submit" name="act" value="Delete" formaction="/admin.php?module=charts">Delete</button>
			<input type="hidden" name="id" value="'.$charts['id'].'" />
			<input type="hidden" name="stat_chart" value="1" />
			</form></div></div>';
		}

		$templating->set('chart_list', $chart_list);
	}
}

else if (isset($_POST['act']) && !isset($_GET['view']))
{
	if ($_POST['act'] == 'add_chart')
	{
		if (empty($_POST['name']) || empty($_POST['labels']) || empty($_POST['data']))
		{
			$_SESSION['e_name'] = $_POST['name'];
			$_SESSION['e_labels'] = $_POST['labels'];
			$_SESSION['e_data'] = $_POST['data'];
			$_SESSION['e_h_label'] = $_POST['h_label'];

			header("Location: /admin.php?module=charts&add&error=empty");
		}

		else
		{
			$db->sqlquery("INSERT INTO `charts` SET `owner` = ?, `h_label` = ?, `name` = ?", array($_SESSION['user_id'], $_POST['h_label'], $_POST['name']));

			$new_chart_id = $db->grab_id();

			// split the labels and data into lines
			$labels = preg_split('/(\\n|\\r)/', $_POST['labels'], -1, PREG_SPLIT_NO_EMPTY);
			$data = preg_split('/(\\n|\\r)/', $_POST['data'], -1, PREG_SPLIT_NO_EMPTY);

			if (sizeof($labels) != sizeof($data))
			{
				$_SESSION['e_name'] = $_POST['name'];
				$_SESSION['e_labels'] = $_POST['labels'];
				$_SESSION['e_data'] = $_POST['data'];
				$_SESSION['e_h_label'] = $_POST['h_label'];

				header("Location: /admin.php?module=charts&add&error=notenough");
				die();
			}

			$label_counter = 0;
			foreach ($labels as $label)
			{
				$db->sqlquery("INSERT INTO `charts_labels` SET `chart_id` = ?, `name` = ?", array($new_chart_id, $label));

				// get the first id
				if ($label_counter == 0)
				{
					$label_counter++;

					$new_label_id = $db->grab_id();
				}

				$core->message("Label $label added!");
			}

			$core->message('Now putting in the Data.');

			$set_label_id = $new_label_id;
			// put in the data
			foreach ($data as $dat)
			{
				$db->sqlquery("INSERT INTO `charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?", array($new_chart_id, $set_label_id, $dat));
				$set_label_id++;
				$core->message("Data $dat added!");
			}

			header("Location: /admin.php?module=charts&view=add&message=done");
		}
	}

	if ($_POST['act'] == 'Delete')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$core->yes_no('Are you sure you want to delete that chart?', '/admin.php?module=charts&id='.$_POST['id'], "Delete");
		}
		else if (isset($_POST['no']))
		{
			if (isset($_POST['stat_chart']) && $_POST['stat_chart'] == 1)
			{
				header("Location: /admin.php?module=charts&view=manage_stats");
			}
			else
			{
				header("Location: /admin.php?module=charts&view=manage");
			}
		}
		else if (isset($_POST['yes']))
		{
			if (isset($_POST['stat_chart']) && $_POST['stat_chart'] == 1)
			{
				$db->sqlquery("DELETE FROM `user_stats_charts` WHERE `id` = ?", array($_GET['id']));
				$db->sqlquery("DELETE FROM `user_stats_charts_data` WHERE `chart_id` = ?", array($_GET['id']));
				$db->sqlquery("DELETE FROM `user_stats_charts_labels` WHERE `chart_id` = ?", array($_GET['id']));
				header("Location: /admin.php?module=charts&view=manage_stats&message=deleted");
			}
			else
			{
				$db->sqlquery("DELETE FROM `charts` WHERE `id` = ?", array($_GET['id']));
				$db->sqlquery("DELETE FROM `charts_data` WHERE `chart_id` = ?", array($_GET['id']));
				$db->sqlquery("DELETE FROM `charts_labels` WHERE `chart_id` = ?", array($_GET['id']));
				header("Location: /admin.php?module=charts&view=manage&message=deleted");
			}
		}
	}

	if ($_POST['act'] == 'Delete_Full')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$core->yes_no('Are you sure you want to delete that group of charts?', '/admin.php?module=charts&grouping_id='.$_POST['grouping_id'], "Delete_Full");
		}
		else if (isset($_POST['no']))
		{
			header("Location: /admin.php?module=charts&view=manage_stats");
		}
		else if (isset($_POST['yes']))
		{
			$db->sqlquery("DELETE FROM `user_stats_charts` WHERE `grouping_id` = ?", array($_GET['grouping_id']));
			$db->sqlquery("DELETE FROM `user_stats_charts_data` WHERE `grouping_id` = ?", array($_GET['grouping_id']));
			$db->sqlquery("DELETE FROM `user_stats_charts_labels` WHERE `grouping_id` = ?", array($_GET['grouping_id']));

			header("Location: /admin.php?module=charts&view=manage_stats&message=deleted");
		}
	}
}
?>
