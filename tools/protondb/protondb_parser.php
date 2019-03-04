<?php
/* TODO
Can probably check on individuals by CPU+GPU combination?
*/
define('LAST_MONTH', '02');
define('CURRENT_YEAR', '2019');

$file_dir = dirname (dirname( dirname(__FILE__) ));

$db_conf = include $file_dir . '/includes/config.php';

include($file_dir. '/includes/class_db_mysql.php');
$dbl = new db_mysql("mysql:host=".$db_conf['host'].";dbname=".$db_conf['database'],$db_conf['username'],$db_conf['password']);

include($file_dir . '/includes/class_core.php');
$core = new core($dbl, $file_dir);

include($file_dir . '/includes/class_template.php');
$templating = new template($core, $core->config('template'));

// get list of distributions we know
$distros = $dbl->run("SELECT `name` FROM `distributions` ORDER BY `name` ASC")->fetch_all(PDO::FETCH_COLUMN);

$total_reports = 0;
$this_month_reports = 0;

// storage arrays
$rating_name_order = array('Platinum','Gold','Silver','Bronze','Borked');
$rating = array();
$games = array();
$os_raw = array();
$os_clean = array();
$proton = array();
$gpu = array('AMD' => 0, 'NVIDIA' => 0, 'Intel' => 0);
$cpu = array('AMD' => 0, 'Intel' => 0);
$reports_over_time = array();
$platinum_reports = array();
$borked_reports = array();

function contains($str, array $arr)
{
    foreach($arr as $a) {
        if (strpos($str,$a) !== false) return $a;
    }
    return false;
}

$string = file_get_contents(__DIR__ ."/protondb.json");
$json = json_decode($string, true);
foreach ($json as $key => $value)
{
	$total_reports++;

	// reports over time
	// one of the dates is totally messed up and we only want the full month of data (for when data includes a couple days of the newer month)
	if (date('mY', $value['timestamp']) != '080118' && date('mY', $value['timestamp']) != LAST_MONTH + 1 . CURRENT_YEAR) 
	{
		if (array_key_exists(date('mY', $value['timestamp']), $reports_over_time))
		{
			$reports_over_time[date('mY', $value['timestamp'])]++;
		}
		else
		{
			$reports_over_time[date('mY', $value['timestamp'])] = 1;
		}
	}
	
	if(date('m', $value['timestamp']) == LAST_MONTH && date('Y', $value['timestamp']) == CURRENT_YEAR && isset($value['rating']))
	{
		$this_month_reports++;

		// echo date('mY', $value['timestamp']) . "\n"; // for double-checking each item is *really* in this month

		// sort out ratings
		if (array_key_exists($value['rating'], $rating))
		{
			$rating[$value['rating']]++;
		}
		else
		{
			$rating[$value['rating']] = 1;
		}

		$clean_title = preg_replace("/(™|®|©|&trade;|&reg;|&copy;|&#8482;|&#174;|&#169;)/", "", $value['title']); // remove junk

		// find the most highly rated title from new reports
		if ($value['rating'] == 'Platinum')
		{
			if (array_key_exists($clean_title, $platinum_reports))
			{
				$platinum_reports[$clean_title]++;
			}
			else
			{
				$platinum_reports[$clean_title] = 1;
			}			
		}

		// sort out the games
		if (array_key_exists($clean_title, $games))
		{
			$games[$clean_title]++;
		}
		else
		{
			$games[$clean_title] = 1;
		}

		// sort out the OS
		if (array_key_exists($value['os'], $os_raw))
		{
			$os_raw[$value['os']]++;
		}
		else
		{
			$os_raw[$value['os']] = 1;
		}

		// sort out the OS
		if ($clean_name = contains($value['os'], $distros))
		{
			if (array_key_exists($clean_name, $os_clean))
			{
				$os_clean[$clean_name]++;
			}
			else
			{
				$os_clean[$clean_name] = 1;
			}
		}
		// for Arch Linux where it doesn't have a release name, but we know Arch's kernel string bit
		else if (strpos($value['kernel'],'arch1'))
		{
			if (array_key_exists('Arch', $os_clean))
			{
				$os_clean['Arch']++;
			}
			else
			{
				$os_clean['Arch'] = 1;
			}			
		}
		// for Fedora where it doesn't have a release name, but we know Fedora's kernel string bit
		else if (preg_match('/.fc[0-9]{2}./',$value['kernel']))
		{
			if (array_key_exists('Fedora', $os_clean))
			{
				$os_clean['Fedora']++;
			}
			else
			{
				$os_clean['Fedora'] = 1;
			}			
		}
		// for Gentoo where it doesn't have a release name, but we know Gentoos's kernel string bit
		else if (strpos($value['kernel'],'gentoo'))
		{
			if (array_key_exists('Gentoo', $os_clean))
			{
				$os_clean['Gentoo']++;
			}
			else
			{
				$os_clean['Gentoo'] = 1;
			}			
		}
		// All others, bundle them together
		else
		{
			if (array_key_exists('Other', $os_clean))
			{
				$os_clean['Other']++;
			}
			else
			{
				$os_clean['Other'] = 1;
			}				
		}

		// sort out the proton version
		if (array_key_exists($value['protonVersion'], $proton))
		{
			$proton[$value['protonVersion']]++;
		}
		else
		{
			$proton[$value['protonVersion']] = 1;
		}

		// sort out GPU vendor
		if (stripos($value['gpu'],'Intel') !== false || stripos($value['gpu'],'ION/integrated/SSE2') !== false)
		{
			$gpu['Intel']++;
		}
		if (stripos($value['gpu'],'GeForce') !== false || stripos($value['gpu'],'NVIDIA') !== false || stripos($value['gpu'],'nouveau') !== false)
		{
			$gpu['NVIDIA']++;
		}
		if (stripos($value['gpu'],'AMD') !== false || stripos($value['gpu'],'Radeon') !== false)
		{
			$gpu['AMD']++;
		}

		if (stripos($value['gpu'],'AMD') === false && stripos($value['gpu'],'Radeon') === false && stripos($value['gpu'],'GeForce') === false && stripos($value['gpu'],'NVIDIA') === false && stripos($value['gpu'],'nouveau') !== false && stripos($value['gpu'],'Intel') === false && stripos($value['gpu'],'ION/integrated/SSE2') === false)
		{
			echo "\n Special GPU: " . $value['gpu'] . "\n";
		}

		// sort out cpu vendor
		if (stripos($value['cpu'],'Intel') !== false)
		{
			$cpu['Intel']++;
		}
		if (stripos($value['cpu'],'AMD') !== false)
		{
			$cpu['AMD']++;
		}

		//echo 'Report number ' . $this_month_reports . "\n";
	}
}

echo "\nReports over time:\n";

arsort($reports_over_time, SORT_NATURAL);
print_r($reports_over_time);

echo "There were $this_month_reports for this month and there's $total_reports reports in total!";

echo "This month's ratings:\n";

$ratings_sorted = array();
foreach ($rating_name_order as $key) 
{
    $ratings_sorted[$key] = $rating[$key];
}

print_r($ratings_sorted);

echo "\nThis month's highest games:\n";

asort($games);
$tenHighest = array_slice($games, -15, null, true);
arsort($tenHighest);
print_r($tenHighest);

echo "\nThis month's top Linux distros - RAW:\n";

print_r($os_raw);

echo "\nThis month's top Linux distros - Cleaner:\n";

arsort($os_clean);
print_r($os_clean);

echo "\nThis month's lowest Linux distros:\n";

$lower_distros = array_slice($os_clean, 10, null);
print_r($lower_distros);

$total_lower = 0;
foreach ($lower_distros as $key => $lower)
{
	$total_lower = $total_lower + $lower;
}

echo "\nTotal from lower distros: $total_lower\n";

$os_clean['Other'] = $os_clean['Other'] + $total_lower;

echo "\nCleaned distro list top 10\n";

$highest_distros = array_slice($os_clean, 0, 10, true);

print_r($highest_distros);

echo "\nThis month's top proton versions:\n";

asort($proton);
$proton_highest = array_slice($proton, -10, null, true);
print_r($proton_highest);

echo "\nThis month's top GPUs\n";

print_r($gpu);

echo "\nThis month's top CPUs\n";

print_r($cpu);

echo "\nThis month's platinum reports\n";

arsort($platinum_reports);
$top_plat = array_slice($platinum_reports, 0, 15);
print_r($top_plat);

/* CHART GENERATION */

// number of reports
$dbl->run("INSERT INTO `charts` SET `owner` = 1, `name` = 'ProtonDB Steam Play reports - February 2019', `sub_title` = 'Reports Over Time', `h_label` = 'Total number of reports', `enabled` = 1, `order_by_data` = 1");
$new_chart_id = $dbl->new_id();
foreach ($reports_over_time as $key => $total)
{
	$dbl->run("INSERT INTO `charts_labels` SET `chart_id` = ?, `name` = ?", array($new_chart_id, $key));
	$new_label_id = $dbl->new_id();
	$dbl->run("INSERT INTO `charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?", array($new_chart_id, $new_label_id, $total));
}

// ratings
$dbl->run("INSERT INTO `charts` SET `owner` = 1, `name` = 'ProtonDB Steam Play reports - February 2019', `sub_title` = 'Steam Play Rating', `h_label` = 'Total number of reports', `enabled` = 1, `order_by_data` = 0");
$new_chart_id = $dbl->new_id();
foreach ($ratings_sorted as $key => $total)
{
	$dbl->run("INSERT INTO `charts_labels` SET `chart_id` = ?, `name` = ?", array($new_chart_id, $key));
	$new_label_id = $dbl->new_id();
	$dbl->run("INSERT INTO `charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?", array($new_chart_id, $new_label_id, $total));
}

// top games
$dbl->run("INSERT INTO `charts` SET `owner` = 1, `name` = 'ProtonDB Steam Play reports - February 2019', `sub_title` = 'Most Rated Games', `h_label` = 'Total number of reports', `enabled` = 1, `order_by_data` = 0");
$new_chart_id = $dbl->new_id();
foreach ($tenHighest as $key => $total)
{
	$dbl->run("INSERT INTO `charts_labels` SET `chart_id` = ?, `name` = ?", array($new_chart_id, $key));
	$new_label_id = $dbl->new_id();
	$dbl->run("INSERT INTO `charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?", array($new_chart_id, $new_label_id, $total));
}

// distros
$dbl->run("INSERT INTO `charts` SET `owner` = 1, `name` = 'ProtonDB Steam Play reports - February 2019', `sub_title` = 'Most Used Linux Distributions - Top 10', `h_label` = 'Total number of reports', `enabled` = 1, `order_by_data` = 1");
$new_chart_id = $dbl->new_id();
foreach ($highest_distros as $key => $total)
{
	$dbl->run("INSERT INTO `charts_labels` SET `chart_id` = ?, `name` = ?", array($new_chart_id, $key));
	$new_label_id = $dbl->new_id();
	$dbl->run("INSERT INTO `charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?", array($new_chart_id, $new_label_id, $total));
}

// gpu
$dbl->run("INSERT INTO `charts` SET `owner` = 1, `name` = 'ProtonDB Steam Play reports - February 2019', `sub_title` = 'Most Used GPU Vendors', `h_label` = 'Total number of reports', `enabled` = 1, `order_by_data` = 1");
$new_chart_id = $dbl->new_id();
foreach ($gpu as $key => $total)
{
	$dbl->run("INSERT INTO `charts_labels` SET `chart_id` = ?, `name` = ?", array($new_chart_id, $key));
	$new_label_id = $dbl->new_id();
	$dbl->run("INSERT INTO `charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?", array($new_chart_id, $new_label_id, $total));
}

// cpu
$dbl->run("INSERT INTO `charts` SET `owner` = 1, `name` = 'ProtonDB Steam Play reports - February 2019', `sub_title` = 'Most Used CPU Vendors', `h_label` = 'Total number of reports', `enabled` = 1, `order_by_data` = 1");
$new_chart_id = $dbl->new_id();
foreach ($cpu as $key => $total)
{
	$dbl->run("INSERT INTO `charts_labels` SET `chart_id` = ?, `name` = ?", array($new_chart_id, $key));
	$new_label_id = $dbl->new_id();
	$dbl->run("INSERT INTO `charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?", array($new_chart_id, $new_label_id, $total));
}

// top platinum games reported
$dbl->run("INSERT INTO `charts` SET `owner` = 1, `name` = 'ProtonDB Steam Play reports - February 2019', `sub_title` = 'Top Titles Reported As Platinum', `h_label` = 'Total number of reports', `enabled` = 1, `order_by_data` = 1");
$new_chart_id = $dbl->new_id();
foreach ($top_plat as $key => $total)
{
	$dbl->run("INSERT INTO `charts_labels` SET `chart_id` = ?, `name` = ?", array($new_chart_id, $key));
	$new_label_id = $dbl->new_id();
	$dbl->run("INSERT INTO `charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?", array($new_chart_id, $new_label_id, $total));
}
