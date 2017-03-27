<?php
$templating->merge('admin_modules/admin_module_charts');


if (isset($_GET['view']) && !isset($_POST['act']))
{
	if ($_GET['view'] == 'add')
	{
		$name = '';
		$sub_title = '';
		$labels = '';
		$data = '';
		$h_label = '';

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
			$sub_title = $_SESSION['e_subtitle'];
			$labels = $_SESSION['e_labels'];
			$data = $_SESSION['e_data'];
			$h_label = $_SESSION['e_h_label'];
		}

		$templating->block('add_chart', 'admin_modules/admin_module_charts');
		$templating->set('name', $name);
		$templating->set('sub_title', $sub_title);
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
		$db->sqlquery("SELECT * FROM `charts` WHERE `owner` = ? ORDER BY `id` DESC", array($_SESSION['user_id']));
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
	
	if ($_GET['view'] == 'edit')
	{
		$chart_id = (int) $_GET['id'];
		$db->sqlquery("SELECT `name`, `enabled`, `sub_title` FROM `charts` WHERE `id` = ?", array($chart_id));
		
		if ($db->num_rows() == 1)
		{
			$chart_info = $db->fetch();
			
			$charts = new golchart();
			
			$templating->block('chart', 'admin_modules/admin_module_charts');
			$templating->set('chart', $charts->render($chart_id, NULL, 'charts_labels', 'charts_data'));
			
			$enabled_check = '';
			if ($chart_info['enabled'] == 1)
			{
				$enabled_check = 'checked';
			}
			$templating->set('enabled_check', $enabled_check);
			$templating->set('chart_id', $chart_id);
			$templating->set('name', $chart_info['name']);
			$templating->set('sub_title', $chart_info['sub_title']);
		}
		else
		{
			$_SESSION['message'] = 'none_found';
			$_SESSION['message_extra'] = 'charts';
			header('Location: /admin.php?module=charts&view=manage');		
		}
	}
}

else if (isset($_POST['act']) && !isset($_GET['view']))
{	
	if ($_POST['act'] == 'add_chart')
	{
		$name = core::make_safe($_POST['name']);
		$labels = $_POST['labels'];
		$check_empty = core::mempty(compact('name', 'labels'));
		if ($check_empty !== true)
		{
			$_SESSION['e_name'] = $_POST['name'];
			$_SESSION['e_subtitle'] = $_POST['sub_title'];
			$_SESSION['e_labels'] = $_POST['labels'];
			$_SESSION['e_h_label'] = $_POST['h_label'];

			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = $check_empty;
			header("Location: /admin.php?module=charts&view=add");
			die();
		}

		else
		{
			$sub_title = trim($_POST['sub_title']);
			if (empty($sub_title))
			{
				$sub_title = NULL;
			}
			
			$grouped = 0;
			if (isset($_POST['grouped']))
			{
				$grouped = 1;
			}
			
			foreach ($_POST['labels'] as $key => $label)
			{
				trim($labels);
				if (empty($label))
				{
					$_SESSION['message'] = 'empty';
					$_SESSION['message_extra'] = 'label name';
					header("Location: /admin.php?module=charts&view=add");
					die();
				}
			}
			
			$db->sqlquery("INSERT INTO `charts` SET `owner` = ?, `h_label` = ?, `name` = ?, `sub_title` = ?, `grouped` = ?", array($_SESSION['user_id'], $_POST['h_label'], $_POST['name'], $sub_title, $grouped));

			$new_chart_id = $db->grab_id();

			$label_counter = 1;
			foreach ($_POST['labels'] as $key => $label)
			{
				$label = core::make_safe($label);
				$db->sqlquery("INSERT INTO `charts_labels` SET `chart_id` = ?, `name` = ?", array($new_chart_id, $label));
				$new_label_id = $db->grab_id();

				if (isset($_POST['grouped']))
				{
					$data = preg_split('/(\\n|\\r)/', $_POST['data-'.$label_counter], -1, PREG_SPLIT_NO_EMPTY);
					// put in the data
					foreach ($data as $dat)
					{
						$data_series = explode(',',$dat);
						$db->sqlquery("INSERT INTO `charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?, `data_series` = ?", array($new_chart_id, $new_label_id, $data_series[0], $data_series[1]));

						$core->message("Data $dat added!");
					}
					
				}
				else
				{
					$data_key = $key+1;
					$data = $_POST['data-'.$data_key];
					$db->sqlquery("INSERT INTO `charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?", array($new_chart_id, $new_label_id, $data));
					
					$core->message("Data $data added!");
				}

				$core->message("Label $label and it's data added!");
				$label_counter++;
			}

			$_SESSION['message'] = 'saved';
			$_SESSION['message_extra'] = 'chart';
			header("Location: /admin.php?module=charts&view=edit&id=".$new_chart_id);
			die();
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
	
	if ($_POST['act'] == 'edit_chart')
	{
		$chart_id = (int) $_POST['chart_id'];
		
		if (!core::is_number($chart_id))
		{
			$_SESSION['message'] = 'no_id';
			$_SESSION['message_extra'] = 'chart';
			header('Location: /admin.php?module=charts&view=manage');
			die();
		}
		
		$enabled_check = 0;
		if (isset($_POST['enabled']))
		{
			$enabled_check = 1;
		}
		
		$name = core::make_safe($_POST['name']);
		$sub_title = core::make_safe($_POST['sub_title']);
		
		$check_empty = core::mempty(compact('name'));
		if ($check_empty !== true)
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = $check_empty;
			header("Location: /admin.php?module=charts&view=edit&id=".$chart_id);
			die();
		}
		
		$db->sqlquery("UPDATE `charts` SET `name` = ?, `enabled` = ?, `sub_title` = ? WHERE `id` = ?", array($name, $enabled_check, $sub_title, $chart_id));
		
		$_SESSION['message'] = 'saved';
		$_SESSION['message_extra'] = 'chart';
		header("Location: /admin.php?module=charts&view=edit&id=".$chart_id);
	}
}
?>
