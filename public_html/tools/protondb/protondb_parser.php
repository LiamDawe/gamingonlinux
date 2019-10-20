<?php
/* TODO
Can probably check on individuals by CPU+GPU combination?
*/
ini_set("memory_limit", "-1");
define('LAST_MONTH', '09');
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
$game_dates = array(); // to find out when this game first appeared
$os_raw = array();
$os_clean = array();
$proton = array();
$gpu = array('AMD' => 0, 'NVIDIA' => 0, 'Intel' => 0);
$cpu = array('AMD' => 0, 'Intel' => 0);
$reports_over_time = array();
$platinum_reports = array();
$gold_reports = array();
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

	$clean_title = preg_replace("/(™|®|©|&trade;|&reg;|&copy;|&#8482;|&#174;|&#169;)/", "", $value['title']); // remove junk

	// get a list of games along with the first date they were submitted
	if (!isset($game_dates[$clean_title]['date']))
	{
		$game_dates[$clean_title]['date'] = date('Y-m-d', $value['timestamp']);
	}

	// sort out ratings for game dates
	if (isset($value['rating']))
	{
		if (isset($game_dates[$clean_title][$value['rating']]))
		{
			$game_dates[$clean_title][$value['rating']]++;
		}
		else
		{
			$game_dates[$clean_title][$value['rating']] = 1;
		}		
	}


	// reports over time
	// one of the dates is totally messed up and we only want the full month of data (for when data includes a couple days of the newer month)
	if (date('mY', $value['timestamp']) != '080118' && date('mY', $value['timestamp']) != LAST_MONTH + 1 . CURRENT_YEAR) 
	{
		if (array_key_exists(date('Y-m', $value['timestamp']), $reports_over_time))
		{
			$reports_over_time[date('Y-m', $value['timestamp'])]++;
		}
		else
		{
			$reports_over_time[date('Y-m', $value['timestamp'])] = 1;
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

		// find the most highly rated gold reports
		if ($value['rating'] == 'Gold')
		{
			if (array_key_exists($clean_title, $gold_reports))
			{
				$gold_reports[$clean_title]++;
			}
			else
			{
				$gold_reports[$clean_title] = 1;
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

// sort the reports in the correct date order
uksort($reports_over_time, function($a1, $a2) 
{
	$time1 = strtotime($a1);
	$time2 = strtotime($a2);

	return $time1 - $time2;
});

echo '<pre>';
//print_r($reports_over_time);
echo '</pre>';

echo "There were $this_month_reports for this month and there's $total_reports reports in total!";

echo "This month's ratings:\n";

$ratings_sorted = array();
foreach ($rating_name_order as $key) 
{
    $ratings_sorted[$key] = $rating[$key];
}

echo '<pre>';
//print_r($ratings_sorted);
echo '</pre>';

echo "\nThis month's highest games:\n";

asort($games);
$tenHighest = array_slice($games, -20, null, true);
arsort($tenHighest);

function array_orderby()
{
    $args = func_get_args();
    $data = array_shift($args);
    foreach ($args as $n => $field) {
        if (is_string($field)) {
            $tmp = array();
            foreach ($data as $key => $row)
                $tmp[$key] = $row[$field];
            $args[$n] = $tmp;
            }
    }
    $args[] = &$data;
    call_user_func_array('array_multisort', $args);
    return array_pop($args);
}

echo "\nThis month's top Linux distros - RAW:\n";

//print_r($os_raw);

echo "\nThis month's top Linux distros - Cleaner:\n";

arsort($os_clean);
//print_r($os_clean);

echo "\nThis month's lowest Linux distros:\n";

$lower_distros = array_slice($os_clean, 10, null);
//print_r($lower_distros);

$total_lower = 0;
foreach ($lower_distros as $key => $lower)
{
	$total_lower = $total_lower + $lower;
}

echo "\nTotal from lower distros: $total_lower\n";

$os_clean['Other'] = $os_clean['Other'] + $total_lower;

echo "\nCleaned distro list top 10\n";

$highest_distros = array_slice($os_clean, 0, 10, true);

//print_r($highest_distros);

echo "\nThis month's top proton versions:\n";

asort($proton);
$proton_highest = array_slice($proton, -10, null, true);
print_r($proton_highest);

echo "\nThis month's top GPUs\n";

//print_r($gpu);

echo "\nThis month's top CPUs\n";

//print_r($cpu);

echo "\nThis month's platinum reports\n";

arsort($platinum_reports);
$top_plat = array_slice($platinum_reports, 0, 15);
print_r($top_plat);

/* CHART GENERATION */

if (isset($_GET['makecharts']))
{
	// number of reports
	$dbl->run("INSERT INTO `charts` SET `owner` = 1, `name` = 'ProtonDB Steam Play reports - September 2019', `sub_title` = 'Reports Over Time', `h_label` = 'Total number of reports', `enabled` = 1, `order_by_data` = 0");
	$new_chart_id = $dbl->new_id();
	foreach ($reports_over_time as $key => $total)
	{
		$dbl->run("INSERT INTO `charts_labels` SET `chart_id` = ?, `name` = ?", array($new_chart_id, $key));
		$new_label_id = $dbl->new_id();
		$dbl->run("INSERT INTO `charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?", array($new_chart_id, $new_label_id, $total));
	}

	// ratings
	$dbl->run("INSERT INTO `charts` SET `owner` = 1, `name` = 'ProtonDB Steam Play reports - September 2019', `sub_title` = 'Steam Play Rating', `h_label` = 'Total number of reports', `enabled` = 1, `order_by_data` = 0");
	$new_chart_id = $dbl->new_id();
	foreach ($ratings_sorted as $key => $total)
	{
		$dbl->run("INSERT INTO `charts_labels` SET `chart_id` = ?, `name` = ?", array($new_chart_id, $key));
		$new_label_id = $dbl->new_id();
		$dbl->run("INSERT INTO `charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?", array($new_chart_id, $new_label_id, $total));
	}

	// distros
	$dbl->run("INSERT INTO `charts` SET `owner` = 1, `name` = 'ProtonDB Steam Play reports - September 2019', `sub_title` = 'Most Used Linux Distributions - Top 10', `h_label` = 'Total number of reports', `enabled` = 1, `order_by_data` = 1");
	$new_chart_id = $dbl->new_id();
	foreach ($highest_distros as $key => $total)
	{
		$dbl->run("INSERT INTO `charts_labels` SET `chart_id` = ?, `name` = ?", array($new_chart_id, $key));
		$new_label_id = $dbl->new_id();
		$dbl->run("INSERT INTO `charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?", array($new_chart_id, $new_label_id, $total));
	}

	// gpu
	$dbl->run("INSERT INTO `charts` SET `owner` = 1, `name` = 'ProtonDB Steam Play reports - September 2019', `sub_title` = 'Most Used GPU Vendors', `h_label` = 'Total number of reports', `enabled` = 1, `order_by_data` = 1");
	$new_chart_id = $dbl->new_id();
	foreach ($gpu as $key => $total)
	{
		$dbl->run("INSERT INTO `charts_labels` SET `chart_id` = ?, `name` = ?", array($new_chart_id, $key));
		$new_label_id = $dbl->new_id();
		$dbl->run("INSERT INTO `charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?", array($new_chart_id, $new_label_id, $total));
	}

	// cpu
	$dbl->run("INSERT INTO `charts` SET `owner` = 1, `name` = 'ProtonDB Steam Play reports - September 2019', `sub_title` = 'Most Used CPU Vendors', `h_label` = 'Total number of reports', `enabled` = 1, `order_by_data` = 1");
	$new_chart_id = $dbl->new_id();
	foreach ($cpu as $key => $total)
	{
		$dbl->run("INSERT INTO `charts_labels` SET `chart_id` = ?, `name` = ?", array($new_chart_id, $key));
		$new_label_id = $dbl->new_id();
		$dbl->run("INSERT INTO `charts_data` SET `chart_id` = ?, `label_id` = ?, `data` = ?", array($new_chart_id, $new_label_id, $total));
	}
}

/* HTML tables */

// top games
echo "<table class=\"table-stripes-tbody\">
<thead>
<tr>
<th>Name</th>
<th>No. Total</th>
<th>Plat</th>
<th>Gold</th>
</tr>
<tbody>";
foreach ($tenHighest as $key => $total)
{
	$total_good = 0;
	$plat = 0;
	$gold = 0;
	if (isset($platinum_reports[$key]))
	{
		$plat = $platinum_reports[$key];
	}
	if (isset($gold_reports[$key]))
	{
		$gold = $gold_reports[$key];
	}	
	echo "<tr><td>$key</td><td>$total</td><td>{$plat}</td><td>{$gold}</td></tr>";
}
echo "</tbody>
</table>";

// games and their dates
echo '<p>games and their dates</p><pre>';
print_r($game_dates);
echo '</pre>';
 
$new_month = array();

foreach ($game_dates as $name => $data)
{
	$total_good = 0;
	if (isset($data['Platinum']))
	{
		if (date('Y-m', strtotime($data['date'])) == CURRENT_YEAR.'-'.LAST_MONTH)
		{
			//echo $name . ' - ' . $data['date'] . '<br />';

			$total_good = $total_good + $data['Platinum'];
			$new_month[$name]['Platinum'] = $data['Platinum'];
		}
	}
	if (isset($data['Gold']))
	{
		if (date('Y-m', strtotime($data['date'])) == CURRENT_YEAR.'-'.LAST_MONTH)
		{
			//echo $name . ' - ' . $data['date'] . '<br />';
			$total_good = $total_good + $data['Gold'];
			$new_month[$name]['Gold'] = $data['Gold'];
		}
	}
	if ($total_good > 0)
	{
		$new_month[$name]['total_good'] = $total_good;
	}
}

echo '<pre>';
print_r($new_month);
echo '</pre>';

echo "<p>New entries with good reports</p>";

echo "<table class=\"table-stripes-tbody\">
<thead>
<tr>
<th>Name</th>
<th>Total good</th>
</tr>
<tbody>";

array_multisort( array_column($new_month, "total_good"), SORT_DESC, $new_month );

$new_month = array_slice($new_month, 0, 15, true);

foreach($new_month as $name => $new)
{
	echo "<tr><td>$name</td><td>{$new['total_good']}</td></tr>";
}

echo "</tbody>
</table>";

// games with the most borked reports

$new_month = array();

foreach ($game_dates as $name => $data)
{
	if (isset($data['Borked']))
	{
		if (date('Y-m', strtotime($data['date'])) == CURRENT_YEAR.'-'.LAST_MONTH)
		{
			//echo $name . ' - ' . $data['date'] . '<br />';

			$new_month[$name] = $data;
		}
	}
}

//echo '<pre>';
//print_r($new_month);
//echo '</pre>';

echo "<p>New games getting the most Borked reports this month:</p> <table class=\"table-stripes-tbody\">
<thead>
<tr>
<th>Name</th>
<th>Borked (broken)</th>
</tr>
<tbody>";

array_multisort( array_column($new_month, "Borked"), SORT_DESC, $new_month );

$new_month = array_slice($new_month, 0, 10, true);

foreach($new_month as $name => $new)
{
	echo "<tr><td>$name</td><td>{$new['Borked']}</td></tr>";
}

echo "</tbody>
</table>";