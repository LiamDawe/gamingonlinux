<?php
$templating->set_previous('title', 'Steam Linux Market Share', 1);
$templating->set_previous('meta_description', 'Steam Linux Market Share', 1);

$templating->load('steam_linux_share');
$templating->block('top');

$data = $dbl->run("SELECT * FROM `steam_linux_share` ORDER BY `date` ASC")->fetch_all();

$colours = array(
	'#a6cee3',
	'#1f78b4',
	'#b2df8a',
	'#33a02c',
	'#fb9a99',
	'#e31a1c',
	'#fdbf6f',
	'#ff7f00',
	'#cab2d6',
	'#6a3d9a'
	);

$linux_perc = [];
$english = [];
$chinese = [];
$linux_wo_chinese = [];
$linux_eng_only = [];
$dates = [];
foreach ($data as $point)
{
	$dates[] = "'". date('M-Y', strtotime($point['date'])) . "'";

	$linux_perc[] = $point['linux_share'];
	$english[] = $point['english_share'];
	$chinese[] = $point['chinese_share'];

	$linux_wo_chinese[] = round($point['linux_share'] * 100 / (100 - $point['chinese_share']), 2);
	$linux_eng_only[] = round($point['linux_share'] * 100 / ($point['english_share']), 2);
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

// languages comparison

$languages_data = "
{
	label: 'English',
	fill: false,
	data: [".implode(', ', $english)."],
	backgroundColor: '".$colours[3]."',
	borderColor: '".$colours[3]."',
	borderWidth: 1
},
{
	label: 'Simplified Chinese',
	fill: false,
	data: [".implode(', ', $chinese)."],
	backgroundColor: '".$colours[5]."',
	borderColor: '".$colours[5]."',
	borderWidth: 1
}";

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
	label: 'Linux w/o Simplified Chinese',
	fill: false,
	data: [".implode(', ', $linux_wo_chinese)."],
	backgroundColor: '".$colours[5]."',
	borderColor: '".$colours[5]."',
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

// total number of active steam accounts, currently a guess it could easily be higher
$total_steam_active = 150000000;

$linux_user_total = $total_steam_active/100 * $linux_perc[count($linux_perc)-1];

$templating->set('linux_user_total', number_format($linux_user_total));
$templating->set('linux_latest_total', str_replace("'", '', $dates[count($dates) - 1]) . ' - ' . $linux_perc[count($linux_perc)-1] . '%');