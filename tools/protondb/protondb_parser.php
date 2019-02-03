<?php
/* TODO
*/
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
$rating = array();
$games = array();
$os_raw = array();
$os_clean = array();
$proton = array();
$gpu = array('AMD' => 0, 'NVIDIA' => 0, 'Intel' => 0);
$cpu = array('AMD' => 0, 'Intel' => 0);
$reports_over_time = array();

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

	//echo date('Y', $value['timestamp']) . "\n";

	// reports over time
	if (array_key_exists(date('mY', $value['timestamp']), $reports_over_time))
	{
		$reports_over_time[date('mY', $value['timestamp'])]++;
	}
	else
	{
		$reports_over_time[date('mY', $value['timestamp'])] = 1;
	}
	
	if(date('m', $value['timestamp']) == '01' && date('Y', $value['timestamp']) == '2019')
	{
		$this_month_reports++;

		// sort out ratings
		if (array_key_exists($value['rating'], $rating))
		{
			$rating[$value['rating']]++;
		}
		else
		{
			$rating[$value['rating']] = 1;
		}

		$value['title'] = preg_replace("/(™|®|©|&trade;|&reg;|&copy;|&#8482;|&#174;|&#169;)/", "", $value['title']); // remove junk

		// sort out the games
		if (array_key_exists($value['title'], $games))
		{
			$games[$value['title']]++;
		}
		else
		{
			$games[$value['title']] = 1;
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

ksort($reports_over_time, SORT_NATURAL);
print_r($reports_over_time);

echo "There were $this_month_reports for this month and there's $total_reports reports in total!";

echo "This month's ratings:\n";

foreach ($rating as $key => $value)
{
	echo $key . ': ' . $value . "\n";
}

echo "\nThis month's highest games:\n";

asort($games);
$tenHighest = array_slice($games, -15, null, true);
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