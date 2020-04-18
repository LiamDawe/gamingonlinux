<?php
session_start();

define("APP_ROOT", dirname (dirname ( dirname(__FILE__) )));

require APP_ROOT . "/includes/bootstrap.php";

$preview_data = [];

$grouped = 0;
if (isset($_POST['grouped']))
{
	$grouped = 1;
}

$label_counter = 0;
foreach ($_POST['labels'] as $key => $label)
{
	$this_label_colour = NULL;
	if (isset($_POST['colours'][$key]) && !empty($_POST['colours'][$key]))
	{
		$this_label_colour = $_POST['colours'][$key];
	}

	// sort the data out for grouped charts
	if ($grouped == 1)
	{
		$data = preg_split('/(\\n|\\r)/', $_POST['data'][$label_counter], -1, PREG_SPLIT_NO_EMPTY);
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

if ($grouped == 0)
{
	// put in the data
	$data_counter = 0;
	foreach ($_POST['data'] as $dat)
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
				
		$preview_data[]['name'] = $_POST['labels'][$data_counter];
		end($preview_data);
		$last_id=key($preview_data);
		$preview_data[$last_id]['data'] = $total;
		$preview_data[$last_id]['min'] = $min;
		$preview_data[$last_id]['max'] = $max;
				
		$data_counter++;
		unset($min);
		unset($max);
	}
}

if (isset($_POST['order_by_data']))
{
	// sort them from highest to lowest
	usort($preview_data, function($b, $a)
	{
		return $a['data'] - $b['data'];
	});
}

$charts = new charts($dbl);

$counters_inside = 0;
if (isset($_POST['counters_inside']))
{
	$counters_inside = 1;
}

echo '<div class="box"><div class="head">SVG</div><div class="body group">' . $charts->render(['filetype' => 'svg'], ['name' => $_POST['name'], 'sub_title' => $_POST['sub_title'], 'grouped' => $grouped, 'data' => $preview_data, 'h_label' => $_POST['h_label'], 'counters_inside' => $counters_inside]) . '</div></div>';

echo '<div class="box"><div class="head">PNG</div><div class="body group">' . $charts->render(['filetype' => 'png'], ['name' => $_POST['name'], 'sub_title' => $_POST['sub_title'], 'grouped' => $grouped, 'data' => $preview_data, 'h_label' => $_POST['h_label'], 'counters_inside' => $counters_inside]) . '</div></div>';