<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->set_previous('title', 'Steam Linux Market Share', 1);
$templating->set_previous('meta_description', 'Steam Linux Market Share', 1);

$templating->load('steam_linux_share');
$templating->block('top');

$data = $dbl->run("SELECT * FROM `steam_linux_share` WHERE `date` > '2016-04-01' ORDER BY `date` ASC")->fetch_all();

$colours = array(
	'#a6cee3',
	'#b2df8a',
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
	'English' => array(),
	'Simplified Chinese' => array(),
	'Russian' => array()
);

// for holding data on the overall language share across JUST Linux
$lang_data_linux = array(
	'English' => array(),
	'Simplified Chinese' => array(),
	'Russian' => array()
);

$linux_perc = [];
$linux_wo_chinese = [];
$linux_eng_only = [];
$dates = [];
foreach ($data as $point)
{
	$dates[] = "'". date('M-Y', strtotime($point['date'])) . "'";

	$linux_perc[] = $point['linux_share'];

	// overall share
	$lang_data['English'][] = $point['english_share'];
	$lang_data['Simplified Chinese'][] = $point['chinese_share'];
	$lang_data['Russian'][] = $point['russian_share'];

	// linux only
	$lang_data_linux['English'][] = $point['linux_english_share'];
	$lang_data_linux['Simplified Chinese'][] = $point['linux_chinese_share'];
	$lang_data_linux['Russian'][] = $point['linux_russian_share'];

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
	borderWidth: 1
}";

$linuxonly = "<div class=\"chartjs-container\"><canvas id=\"linuxonly\" width=\"400\" height=\"200\"></canvas></div><script>
var linuxonly = document.getElementById('linuxonly');
var Chartlinuxonly = new Chart.Line(linuxonly, {
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
});
</script>";

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
var Chartdailyshare = new Chart.Line(dailyshare, {
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
		data: [".implode(', ', $data)."],
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

$languages = "<div class=\"chartjs-container\"><canvas id=\"languages\" width=\"400\" height=\"200\"></canvas></div><script>
var languages = document.getElementById('languages');
var Chartlanguages = new Chart.Line(languages, {
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
});
</script>";

$templating->set('languages', $languages);

// languages comparison - Linux ONLY

$languages_linux_data = '';
$counter_linux = 0;
$total_languages_linux = count($lang_data_linux);
foreach ($lang_data_linux as $key => $data)
{
	//print_r($data);
	$languages_linux_data .= "{
		label: '".$key."',
		fill: false,
		data: [".implode(', ', $data)."],
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

$languages_linux = "<div class=\"chartjs-container\"><canvas id=\"languages_linux\" width=\"400\" height=\"200\"></canvas></div><script>
var languages_linux = document.getElementById('languages_linux');
var Chartlanguages_linux = new Chart.Line(languages_linux, {
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
});
</script>";

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

$threesome = "<div class=\"chartjs-container\"><canvas id=\"threesome\" width=\"400\" height=\"200\"></canvas></div><script>
var threesome = document.getElementById('threesome');
var Chartthreesome = new Chart.Line(threesome, {
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
});
</script>";

$templating->set('threesome', $threesome);

// total number of monthly active steam accounts, currently a guess it could easily be higher
$total_steam_active = 90000000;

$linux_user_total = $total_steam_active/100 * $linux_perc[count($linux_perc)-1];

$templating->set('linux_user_total', number_format($linux_user_total));
$templating->set('linux_latest_total', str_replace("'", '', $dates[count($dates) - 1]) . ' - ' . $linux_perc[count($linux_perc)-1] . '%');
