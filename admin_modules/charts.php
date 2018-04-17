<?php
$templating->load('admin_modules/admin_module_charts');


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
				$core->message('You have to fill in all fields', 1);
			}

			if ($_GET['error'] == 'notenough')
			{
				$core->message('The amount of labels doesn\'t match the amount of data! You might have missed a label, or a bit of data to be included.', 1);
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
		$res = $dbl->run("SELECT * FROM `charts` WHERE `owner` = ? ORDER BY `id` DESC", array($_SESSION['user_id']))->fetch_all();
		foreach($res as $charts)
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
		$res = $dbl->run("SELECT * FROM `user_stats_charts` ORDER BY `id` DESC", array($_SESSION['user_id']))->fetch_all();
		$grouping_id = '';
		foreach ($res as $charts)
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
		$chart_info = $dbl->run("SELECT `name`, `enabled`, `sub_title`, `order_by_data`, `h_label`,`counters_inside`, `grouped` FROM `charts` WHERE `id` = ?", array($chart_id))->fetch();

		$labels = $dbl->run("SELECT `label_id`, `name` FROM `charts_labels` WHERE `chart_id` = ?", array($chart_id))->fetch_all();
		$datas = $dbl->run("SELECT `data_id`, `data`, `min`, `max`, `data_series` FROM `charts_data` WHERE `chart_id` = ?", array($chart_id))->fetch_all();
		
		if ($chart_info)
		{
			$charts = new charts($dbl);
			
			$templating->block('chart', 'admin_modules/admin_module_charts');
			$templating->set('chart_name', $chart_info['name']);

			$templating->set('chart', $charts->render(['filetype' => 'png'], ['id' => $chart_id]));
			
			$enabled_check = '';
			if ($chart_info['enabled'] == 1)
			{
				$enabled_check = 'checked';
			}

			$grouped_check = '';
			if ($chart_info['grouped'] == 1)
			{
				$grouped_check = 'checked';
			}

			$counters_check = '';
			if ($chart_info['counters_inside'] == 1)
			{
				$counters_check = 'checked';
			}
			
			$data_order_check = '';
			if ($chart_info['order_by_data'] == 1)
			{
				$data_order_check = 'checked';
			}
			$templating->set('counters_check', $counters_check);
			$templating->set('data_order_check', $data_order_check);
			$templating->set('enabled_check', $enabled_check);
			$templating->set('grouped_check', $grouped_check);
			$templating->set('chart_id', $chart_id);
			$templating->set('name', $chart_info['name']);
			$templating->set('sub_title', $chart_info['sub_title']);
			$templating->set('h_label', $chart_info['h_label']);

			$label_list = '';
			$counter = 1;
			foreach ($labels as $label)
			{
				$label_list .= '<div id="label-'.$counter.'" class="input-field box fleft" style="width: 50%"><span class="addon">Label #'.$counter.':</span><input class="labels" type="text" name="labels['.$label['label_id'].']" value="'.$label['name'].'" /></div><div id="colour-'.$counter.'" class="input-field box fleft" style="width: 50%"><span class="addon">Colour #'.$counter.':</span><input class="colours" type="text" name="colours[]" placeholder="#ffffff" /></div>';
				$counter++;

				if ($chart_info['grouped'] == 1)
				{
					
				}
			}
			$templating->set('labels_list', $label_list);

			if ($chart_info['grouped'] == 0)
			{
				$data_list = '';
				$counter = 1;
				foreach ($datas as $data)
				{
					$data_sorted = $data['data'];
					if ($data['data_series'] != NULL)
					{
						$data_sorted .= ','.$data['data_series'];
					}
					if ($data['min'] != NULL)
					{
						$data_sorted .= ','.$data['min'];
					}
					if ($data['max'] != NULL)
					{
						$data_sorted .= ','.$data['max'];
					}
					$data_list .= '<div id="data-'.$counter.'" class="box">Data for Label #'.$counter.'<input class="data" name="data['.$data['data_id'].']" value="'.$data_sorted.'"></div>';
					$counter++;
				}
			}
			$templating->set('data_list', $data_list);

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
				trim($label);
				if (empty($label))
				{
					$_SESSION['message'] = 'empty';
					$_SESSION['message_extra'] = 'label name';
					header("Location: /admin.php?module=charts&view=add");
					die();
				}
			}
			
			$order_by_data = 0;
			if (isset($_POST['order_by_data']))
			{
				$order_by_data = 1;
			}
			
			$dbl->run("INSERT INTO `charts` SET `owner` = ?, `h_label` = ?, `name` = ?, `sub_title` = ?, `grouped` = ?, `order_by_data` = ?", array($_SESSION['user_id'], $_POST['h_label'], $_POST['name'], $sub_title, $grouped, $order_by_data));

			$new_chart_id = $dbl->new_id();

			$label_counter = 0;
			$label_ids = [];
			foreach ($_POST['labels'] as $key => $label)
			{
				$this_label_colour = NULL;
				if (isset($_POST['colours'][$key]) && !empty($_POST['colours'][$key]))
				{
					$this_label_colour = $_POST['colours'][$key];
				}

				$label = core::make_safe($label);
				$dbl->run("INSERT INTO `charts_labels` SET `chart_id` = ?, `name` = ?, `colour` = ?", array($new_chart_id, $label, $this_label_colour));
				$new_label_id = $dbl->new_id();
				$label_ids[] = $new_label_id;

				// sort the data out for grouped charts, has to be done with the labels to get them in the right place
				if (isset($_POST['grouped']))
				{
					$data = preg_split('/(\\n|\\r)/', $_POST['data'][$label_counter], -1, PREG_SPLIT_NO_EMPTY);
					// put in the data
					foreach ($data as $dat)
					{
						$data_series = explode(',',$dat);
			
						$total = $data_series[0] + 0;
						$total = bcdiv($total, 1, 2);
											
						$min = NULL;
						if (isset($data_series[1]) && is_numeric($data_series[1]))
						{
							$min = $data_series[1] + 0;
							$min = bcdiv($min, 1, 2);
						}
											
						$max = NULL;
						if (isset($data_series[2]) && is_numeric($data_series[2]))
						{
							$max = $data_series[2] + 0;
							$max = bcdiv($max, 1, 2);
						}
						
						$dbl->run("INSERT INTO `charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?, `data_series` = ?, `min` = ?, `max` = ?", array($new_chart_id, $new_label_id, $data_series[0], trim($data_series[1]), $min, $max));

						$core->message("Data $dat added!");
					}
				}
				$core->message("Label $label added!");
				$label_counter++;
			}

			// not grouped, so we just enter them directly using the label id's array to help
			if (!isset($_POST['grouped']))
			{
				// put in the data
				$data_counter = 0;
				foreach ($_POST['data'] as $dat)
				{
					$data_series = explode(',',$dat);
							
					$min = NULL;
					if (isset($data_series[1]) && is_numeric($data_series[1]))
					{
						$min = $data_series[1];
					}
							
					$max = NULL;
					if (isset($data_series[2]) && is_numeric($data_series[2]))
					{
						$max = $data_series[2];
					}
							
					$dbl->run("INSERT INTO `charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?, `min` = ?, `max` = ?", array($new_chart_id, $label_ids[$data_counter], $data_series[0], $min, $max));

					$data_counter++;
					$core->message("Data $dat added!");
				}
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
				$dbl->run("DELETE FROM `user_stats_charts` WHERE `id` = ?", array($_GET['id']));
				$dbl->run("DELETE FROM `user_stats_charts_data` WHERE `chart_id` = ?", array($_GET['id']));
				$dbl->run("DELETE FROM `user_stats_charts_labels` WHERE `chart_id` = ?", array($_GET['id']));
				header("Location: /admin.php?module=charts&view=manage_stats&message=deleted");
			}
			else
			{
				$dbl->run("DELETE FROM `charts` WHERE `id` = ?", array($_GET['id']));
				$dbl->run("DELETE FROM `charts_data` WHERE `chart_id` = ?", array($_GET['id']));
				$dbl->run("DELETE FROM `charts_labels` WHERE `chart_id` = ?", array($_GET['id']));
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
			$dbl->run("DELETE FROM `user_stats_charts` WHERE `grouping_id` = ?", array($_GET['grouping_id']));
			$dbl->run("DELETE FROM `user_stats_charts_data` WHERE `grouping_id` = ?", array($_GET['grouping_id']));
			$dbl->run("DELETE FROM `user_stats_charts_labels` WHERE `grouping_id` = ?", array($_GET['grouping_id']));

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

		$h_label = NULL;
		if (isset($_POST['h_label']) && !empty($_POST['h_label']))
		{
			$h_label = $_POST['h_label'];
		}

		$counters_inside = 0;
		if (isset($_POST['counters_inside']))
		{
			$counters_inside = 1;
		}
		
		$order_by_data = 0;
		if (isset($_POST['order_by_data']))
		{
			$order_by_data = 1;
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
		
		$dbl->run("UPDATE `charts` SET `name` = ?, `enabled` = ?, `sub_title` = ?, `order_by_data` = ?, `h_label` = ?, `counters_inside` = ? WHERE `id` = ?", array($name, $enabled_check, $sub_title, $order_by_data, $h_label, $counters_inside, $chart_id));

		if (isset($_POST['grouped']))
		{
			$data_ids = $dbl->run("SELECT `data_id` FROM `charts_data` WHERE `chart_id` = ?", array($chart_id))->fetch_all(PDO::FETCH_COLUMN, 0);
		}

		foreach ($_POST['labels'] as $label_id => $name)
		{
			$dbl->run("UPDATE `charts_labels` SET `name` = ? WHERE `label_id` = ?", array($name, $label_id));

			// sort the data out for grouped charts, has to be done with the labels to get them in the right place
			if (isset($_POST['grouped']))
			{
				$data = preg_split('/(\\n|\\r)/', $_POST['data'][$label_counter], -1, PREG_SPLIT_NO_EMPTY);
				// put in the data
				$data_counter = 0;
				foreach ($data as $dat)
				{
					$data_series = explode(',',$dat);
			
					$total = $data_series[0] + 0;
					$total = bcdiv($total, 1, 2);
											
					$min = NULL;
					if (isset($data_series[1]) && is_numeric($data_series[1]))
					{
						$min = $data_series[1] + 0;
						$min = bcdiv($min, 1, 2);
					}
											
					$max = NULL;
					if (isset($data_series[2]) && is_numeric($data_series[2]))
					{
						$max = $data_series[2] + 0;
						$max = bcdiv($max, 1, 2);
					}

					echo $data_ids[$data_counter];

					die();
						
					$dbl->run("UPDATE `charts_data` SET `data` = ?, `data_series` = ?, `min` = ?, `max` = ? WHERE `data_id` = ?", array($data_series[0], trim($data_series[1]), $min, $max, $data_ids[$data_counter]));
					$data_counter++;
				}
			}
		}

		if (!isset($_POST['grouped']))
		{
			foreach ($_POST['data'] as $data_id => $data)
			{
				$data_series = explode(',',$data);
							
				$min = NULL;
				if (isset($data_series[1]) && is_numeric($data_series[1]))
				{
					$min = $data_series[1];
				}
						
				$max = NULL;
				if (isset($data_series[2]) && is_numeric($data_series[2]))
				{
					$max = $data_series[2];
				}

				$dbl->run("UPDATE `charts_data` SET `data` = ?, `min` = ?, `max` = ? WHERE `data_id` = ?", array($data_series[0], $min, $max, $data_id));
			}
		}
		
		$_SESSION['message'] = 'saved';
		$_SESSION['message_extra'] = 'chart';
		header("Location: /admin.php?module=charts&view=edit&id=".$chart_id);
	}
}
?>
