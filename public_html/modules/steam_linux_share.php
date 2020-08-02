<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->set_previous('title', 'Steam Linux Market Share', 1);
$templating->set_previous('meta_description', 'Steam Linux Market Share', 1);

$templating->load('steam_linux_share');
$templating->block('top');

$data_years = $dbl->run("SELECT DISTINCT YEAR(`date`) FROM `steam_linux_share` ORDER BY `date` ASC")->fetch_all(PDO::FETCH_COLUMN);

$months = array('01','02','03','04','05','06','07','08','09','10','11','12');

//defaults
$from = '2018-01-01';
$to_year = date('Y');
$to_month = date('m');
$sql_from = $from;
$sql_to = $to_year . '-' . $to_month . '-01';

$start_options = '';
$end_options = '';
$dates_to_insert = array();
foreach ($data_years as $year)
{
	foreach($months as $month)
	{
		$dates_to_insert[] = $year . '-' . $month . '-01';
	}
}
foreach ($dates_to_insert as $insert)
{
	if (isset($_POST['filter-date']))
	{
		$selected = '';
		if ($insert == $_POST['start'])
		{
			$selected = ' selected';
		}
		$start_options .= '<option value="'.$insert.'" '.$selected.'>' . $insert . '</option>';

		$selected = '';
		if ($insert == $_POST['end'])
		{
			$selected = ' selected';
		}
		$end_options .= '<option value="'.$insert.'" '.$selected.'>' . $insert . '</option>';		
	}
	else
	{
		$selected = '';
		if ($from == $insert)
		{
			$selected = ' selected';
		}
		$start_options .= '<option value="'.$insert.'" '.$selected.'>' . $insert . '</option>';

		$selected = '';
		if ($to_year . '-' . $to_month . '-01' == $insert)
		{
			$selected = ' selected';
		}
		$end_options .= '<option value="'.$insert.'" '.$selected.'>' . $insert . '</option>';
	}
}

$templating->set('start_options', $start_options);
$templating->set('end_options', $end_options);

function isValidDate($date) {
    return date('Y-m-d', strtotime($date)) === $date;
}

if (isset($_POST['filter-date']))
{
	if (isValidDate($_POST['start']) && isValidDate($_POST['end']))
	{
		$sql_from = $_POST['start'];
		$sql_to = $_POST['end'];
	}
	else
	{
		$_SESSION['message'] = 'invalid-date';
		header("Location: /steam-tracker/");
		die();
	}
}

$data = $dbl->run("SELECT * FROM `steam_linux_share` WHERE `date` BETWEEN ? AND ? ORDER BY `date` ASC", array($sql_from, $sql_to))->fetch_all();

$colours = array(
	'#3999cc',
	'#548c22',
	'#fb9a99',
	'#e31a1c',
	'#fdbf6f',
	'#ff7f00',
	'#cab2d6',
	'#6a3d9a',
	'#1f78b4',
	'#33a02c'
	);

// for holding data on the overall language share across all platforms
$lang_data = array(
	'English' => array(
		'db_field' => 'english_share',
		'data' => array(),
		'linux_db_field' => 'linux_english_share',
		'linux_data' => array()
	),
	'Simplified Chinese' => array(
		'db_field' => 'chinese_share',
		'data' => array(),
		'linux_db_field' => 'linux_chinese_share',
		'linux_data' => array()
	),
	'Russian' => array(
		'db_field' => 'russian_share',
		'data' => array(),
		'linux_db_field' => 'linux_russian_share',
		'linux_data' => array()
	),
	'Spanish - Spain' => array(
		'db_field' => 'spanish_spain_share',
		'data' => array(),
		'linux_db_field' => 'linux_spanish_spain',
		'linux_data' => array()
	)
);

$linux_perc = [];
$linux_wo_chinese = [];
$linux_eng_only = [];
$dates = [];
foreach ($data as $point)
{
	$dates[] = "'". date('M-Y', strtotime($point['date'])) . "'";

	$linux_perc[] = $point['linux_share'];

	foreach($lang_data as $lang => $val)
	{
		// all
		if (isset($point[$val['db_field']]))
		{
			$lang_data[$lang]['data'][] = $point[$val['db_field']];
		}
		else
		{
			$lang_data[$lang]['data'][] = NULL;
		}

		// linux only
		if (isset($point[$val['linux_db_field']]))
		{
			$lang_data[$lang]['linux_data'][] = $point[$val['linux_db_field']];
		}
		else
		{
			$lang_data[$lang]['linux_data'][] = NULL;
		}
	}

	$linux_eng_only[] = round($point['linux_share'] * $point['linux_english_share'] / ($point['english_share']), 2);
}

// linux share only
$linux_share_data = "
{
	label: 'Linux',
	fill: false,
	data: [".implode(', ', $linux_perc)."],
	backgroundColor: '".$colours[1]."',
	borderColor: '".$colours[1]."',
	borderWidth: 1,
	trendlineLinear: {
		style: '#FF0000',
		lineStyle: 'dotted',
		width: 2
	}
}";

$linuxonly = "<div class=\"chartjs-container\"><canvas class=\"chartjs\" id=\"linuxonly\" width=\"400\" height=\"200\"></canvas></div>";

core::$user_chart_js .= "var linuxonly = document.getElementById('linuxonly');
var Chartlinuxonly = new Chart(linuxonly, {
type: 'line',
data: {
labels: [".implode(',', $dates)."],
datasets: [$linux_share_data]
	},
	options: {
		legend: {
			display: true
		},responsive: true, maintainAspectRatio: false,
scales: {
yAxes: [{
	ticks: {
	beginAtZero:true
	},
				scaleLabel: {
			display: true,
			labelString: 'Percentage of Steam users'
		}
}]
},
		tooltips:
		{
			callbacks: {
				label: function(tooltipItem, data) {
	var value = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
					var label = data.datasets[tooltipItem.datasetIndex].label;
	return label + ' ' + value + '%';
		}
		},
		},
}
});";

$templating->set('linuxonly', $linuxonly);

// daily active users
/*
$get_daily = $dbl->run("SELECT d.`date`, d.`total`, s.`linux_share` from `steam_daily_active` d INNER JOIN `steam_linux_share` s ON d.date = s.date ORDER BY d.`date` ASC")->fetch_all();

$daily_dates = array();

foreach ($get_daily as $daily_point)
{
	$daily_dates[] = "'". date('M-Y', strtotime($daily_point['date'])) . "'";

	$daily_linux[] = $daily_point['total'] / 100 * $daily_point['linux_share'];
}

$linux_daily_data = "
{
	label: 'Linux',
	fill: false,
	data: [".implode(', ', $daily_linux)."],
	backgroundColor: '".$colours[1]."',
	borderColor: '".$colours[1]."',
	borderWidth: 1
}";

$dailyactive = "<div class=\"chartjs-container\"><canvas id=\"dailyshare\" width=\"400\" height=\"200\"></canvas></div><script>
var dailyshare = document.getElementById('dailyshare');
var Chartdailyshare = new Chart(dailyshare, {
type: 'line',
data: {
labels: [".implode(',', $daily_dates)."],
datasets: [$linux_daily_data]
	},
	options: {
		legend: {
			display: true
		},responsive: true, maintainAspectRatio: false,
scales: {
yAxes: [{
	ticks: {
	beginAtZero:true
	},
				scaleLabel: {
			display: true,
			labelString: 'Daily active user count'
		}
}]
},
		tooltips:
		{
			callbacks: {
				label: function(tooltipItem, data) {
	var value = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
					var label = data.datasets[tooltipItem.datasetIndex].label;
	return label + ' ' + value + ' Linux users';
		}
		},
		},
}
});
</script>";

$templating->set('dailyactive', $dailyactive);
*/
// languages comparison - ALL platforms

$languages_data = '';
$counter = 0;
$total_languages = count($lang_data);
foreach ($lang_data as $key => $data)
{
	$languages_data .= "{
		label: '".$key."',
		fill: false,
		data: [".implode(', ', $data['data'])."],
		backgroundColor: '".$colours[$counter]."',
		borderColor: '".$colours[$counter]."',
		borderWidth: 1
	}";

	if ($counter != ($total_languages - 1))
	{
		$languages_data .= ',';
	}

	$counter++;
}

$languages = "<div class=\"chartjs-container\"><canvas class=\"chartjs\" id=\"languages\" width=\"400\" height=\"200\"></canvas></div>";

core::$user_chart_js .= "var languages = document.getElementById('languages');
var Chartlanguages = new Chart(languages, {
type: 'line',
data: {
labels: [".implode(',', $dates)."],
datasets: [$languages_data]
	},
	options: {
		legend: {
			display: true
		},responsive: true, maintainAspectRatio: false,
scales: {
yAxes: [{
	ticks: {
	beginAtZero:true
	},
				scaleLabel: {
			display: true,
			labelString: 'Percentage of Steam users'
		}
}]
},
		tooltips:
		{
			callbacks: {
				label: function(tooltipItem, data) {
	var value = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
					var label = data.datasets[tooltipItem.datasetIndex].label;
	return label + ' ' + value + '%';
		}
		},
		},
}
});";

$templating->set('languages', $languages);

// languages comparison - Linux ONLY

$languages_linux_data = '';
$counter_linux = 0;
$total_languages_linux = count($lang_data);
foreach ($lang_data as $key => $data)
{
	//print_r($data);
	$languages_linux_data .= "{
		label: '".$key."',
		fill: false,
		data: [".implode(', ', $data['linux_data'])."],
		backgroundColor: '".$colours[$counter_linux]."',
		borderColor: '".$colours[$counter_linux]."',
		borderWidth: 1
	}";

	if ($counter_linux != ($total_languages_linux - 1))
	{
		$languages_linux_data .= ',';
	}

	$counter_linux++;
}

$languages_linux = "<div class=\"chartjs-container\"><canvas class=\"chartjs\" id=\"languages_linux\" width=\"400\" height=\"200\"></canvas></div>";

core::$user_chart_js .= "var languages_linux = document.getElementById('languages_linux');
var Chartlanguages_linux = new Chart(languages_linux, {
type: 'line',
data: {
labels: [".implode(',', $dates)."],
datasets: [$languages_linux_data]
	},
	options: {
		legend: {
			display: true
		},responsive: true, maintainAspectRatio: false,
scales: {
yAxes: [{
	ticks: {
	beginAtZero:true
	},
				scaleLabel: {
			display: true,
			labelString: 'Percentage of Steam users'
		}
}]
},
		tooltips:
		{
			callbacks: {
				label: function(tooltipItem, data) {
	var value = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
					var label = data.datasets[tooltipItem.datasetIndex].label;
	return label + ' ' + value + '%';
		}
		},
		},
}
});";

$templating->set('languages_linux', $languages_linux);

// all together now badumtish

$threesome_data = "
{
	label: 'Linux Overall',
	fill: false,
	data: [".implode(', ', $linux_perc)."],
	backgroundColor: '".$colours[3]."',
	borderColor: '".$colours[3]."',
	borderWidth: 1
},
{
	label: 'Linux, English Only',
	fill: false,
	data: [".implode(', ', $linux_eng_only)."],
	backgroundColor: '".$colours[1]."',
	borderColor: '".$colours[1]."',
	borderWidth: 1
}";

$threesome = "<div class=\"chartjs-container\"><canvas class=\"chartjs\" id=\"threesome\" width=\"400\" height=\"200\"></canvas></div>";

core::$user_chart_js .= "var threesome = document.getElementById('threesome');
var Chartthreesome = new Chart(threesome, {
type: 'line',
data: {
labels: [".implode(',', $dates)."],
datasets: [$threesome_data]
	},
	options: {
		legend: {
			display: true
		},responsive: true, maintainAspectRatio: false,
scales: {
yAxes: [{
	ticks: {
	beginAtZero:true
	},
				scaleLabel: {
			display: true,
			labelString: 'Percentage of Steam users'
		}
}]
},
		tooltips:
		{
			callbacks: {
				label: function(tooltipItem, data) {
	var value = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
					var label = data.datasets[tooltipItem.datasetIndex].label;
	return label + ' ' + value + '%';
		}
		},
		},
}
});";

$templating->set('threesome', $threesome);

// total number of monthly active steam accounts
$total_steam_active = 95000000;

$linux_user_total = $total_steam_active/100 * $linux_perc[count($linux_perc)-1];

$templating->set('linux_user_total', number_format($linux_user_total));
$templating->set('linux_latest_total', str_replace("'", '', $dates[count($dates) - 1]) . ' - ' . $linux_perc[count($linux_perc)-1] . '%');
