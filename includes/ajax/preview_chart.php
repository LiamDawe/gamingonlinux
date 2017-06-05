<?php
define("APP_ROOT", dirname (dirname ( dirname(__FILE__) )));

$db_conf = include APP_ROOT . '/includes/config.php';

require APP_ROOT . "/includes/bootstrap.php";

$charts = new charts($dbl);

$preview_data = [];

$grouped = 0;
if (isset($_POST['grouped']))
{
	$grouped = 1;
}

$label_counter = 1;
foreach ($_POST['labels'] as $key => $label)
{
	$this_label_colour = NULL;
	if (isset($_POST['colours'][$key]) && !empty($_POST['colours'][$key]))
	{
		$this_label_colour = $_POST['colours'][$key];
	}

	// sort the data out for grouped charts
	if (isset($_POST['grouped']))
	{
		$data = preg_split('/(\\n|\\r)/', $_POST['data-'.$label_counter], -1, PREG_SPLIT_NO_EMPTY);
		// put in the data
		foreach ($data as $dat)
		{
			$data_series = explode(',',$dat);
			
			$total = $data_series[0] + 0;
			$total = bcdiv($total, 1, 2);
						
			$min = NULL;
			if (isset($data_series[2]) && is_numeric($data_series[2]))
			{
				$min = $data_series[2] + 0;
				$min = bcdiv($min, 1, 2);
			}
						
			$max = NULL;
			if (isset($data_series[3]) && is_numeric($data_series[3]))
			{
				$max = $data_series[3] + 0;
				$max = bcdiv($max, 1, 2);
			}
			
			$preview_data[]['name'] = $label;
			end($preview_data);
			$last_id=key($preview_data);
			$preview_data[$last_id]['data'] = $total;
			$preview_data[$last_id]['min'] = $min;
			$preview_data[$last_id]['max'] = $max;
			$preview_data[$last_id]['data_series'] = trim($data_series[1]);
			
			unset($min);
			unset($max);
		}
	}
	$label_counter++;
}

// sort them from highest to lowest, by their actual data
usort($preview_data, function($b, $a)
{
	return $a['data'] - $b['data'];
});

echo $charts->render(NULL, ['name' => $_POST['name'], 'sub_title' => $_POST['sub_title'], 'grouped' => $grouped, 'data' => $preview_data]);
