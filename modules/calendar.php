<?php
if (!isset($_GET['year']) || empty($_GET['year']))
{
	$year = date("Y");
}
else if (isset($_GET['year']) && is_numeric($_GET['year']))
{
	$year = $_GET['year'];
}
else if (isset($_GET['year']) && !is_numeric($_GET['year']))
{
	$year = date("Y");
}

if (!isset($_GET['month']) || empty($_GET['month']))
{
	$month = date("n");
}
else if (isset($_GET['month']) && is_numeric($_GET['month']))
{
	$month = $_GET['month'];
}
else if (isset($_GET['month']) && !is_numeric($_GET['month']))
{
	$month = date("n");
}

$templating->set_previous('title', 'Linux Game Release Calendar', 1);
$templating->set_previous('meta_description', 'GamingOnLinux.com maintained list of Linux game releases', 1);

if (isset($_GET['message']))
{
	if ($_GET['message'] == 'sent')
	{
		$core->message("You have sent in a calendar item! Thanks for the help, much appreciated!");
	}
	if ($_GET['message'] == 'edited')
	{
		$core->message("You have edited the games database and calendar item!");
	}
	if ($_GET['message'] == 'deleted')
	{
		$core->message("You have deleted the games database and calendar item!");
	}
}

if (isset($_GET['error']))
{
	if ($_GET['error'] == 'notloggedin')
	{
		$core->message("You are not logged in! You must be logged in to be able to submit calendar games!", NULL, 1);
	}
	if ($_GET['error'] == 'missing')
	{
		$core->message("You have to put at least a name and date in! If you added a link, be sure it contains a valid URL!", NULL, 1);
	}
	if ($_GET['error'] == 'exists')
	{
		$core->message("That game already exists! You can find it <a href=\"/index.php?module=game&game-id={$_GET['id']}\">by clicking here.</a>", NULL, 1);
	}
	if ($_GET['error'] == 'emptysearch')
	{
		$core->message("You cannot search for nothing dummy!", NULL, 1);
	}
}

// cheers stack overflow http://stackoverflow.com/questions/3109978/php-display-number-with-ordinal-suffix
function ordinal($number)
{
	$ends = array('th','st','nd','rd','th','th','th','th','th','th');
	if ((($number % 100) >= 11) && (($number%100) <= 13))
	{
		return $number. 'th';
	}
	else
	{
		return $number. $ends[$number % 10];
	}
}

$templating->merge('calendar');

// count how many there is due this month
$db->sqlquery("SELECT COUNT(id) as count FROM `calendar` WHERE YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE()) AND DAY(date) > DAY(CURDATE()) AND `approved` = 1");
$counter = $db->fetch();

$templating->block('top');
$templating->set('this_month', $counter['count']);

$editor_links = '';
if ($user->check_group(1,2) || $user->check_group(5))
{
	$editor_links = '<span class="fright">Editor Links: <a href="/admin.php?module=games&view=add">Add a game</a></span>';
}
$templating->set('editor_links', $editor_links);

$templating->merge('game-search');
$templating->block('search', 'game-search');
$templating->set('search_text', $search);

if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
{
	$templating->block('submit', 'calendar');
}

$templating->block('picker', 'calendar');

$years_array = range(2014, 2020);
$options = '';
foreach ($years_array as $what_year)
{
	$selected = '';
	if ($what_year == $year)
	{
		$selected = 'SELECTED';
	}
	$options .= '<option value="'.$what_year.'" '.$selected.'>'.$what_year.'</option>';
}
$templating->set('options', $options);

$months_array = array(1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December');

$month_options = '';
foreach ($months_array as $key => $what_month)
{
	$month_selected = '';
	if ($key == $month)
	{
		$month_selected = 'SELECTED';
	}
	$month_options .= '<option value="'.$key.'" '.$month_selected.'>'.$what_month.'</option>';
}
$templating->set('month_options', $month_options);

$templating->block('head', 'calendar');

	$prev_month = $month - 1;
	$next_month = $month + 1;
	$prev_year = $year;
	$next_year = $year;

	if ($month == 1)
	{
		$prev_month = 12;
		$prev_year = $year - 1;
	}

	if ($month == 12)
	{
		$next_month = 1;
		$next_year = $year + 1;
	}

	$templating->set('prev', $prev_month);
	$templating->set('next', $next_month);
	$templating->set('prev_year', $prev_year);
	$templating->set('next_year', $next_year);

	// count how many there is
	$db->sqlquery("SELECT COUNT(id) as count FROM `calendar` WHERE YEAR(date) = $year AND MONTH(date) = $month AND `approved` = 1");
	$counter = $db->fetch();

	$templating->set('month', $months_array[$month] . ' ' . $year . ' (Total: ' . $counter['count'] . ')');

	$db->sqlquery("SELECT `id`, `date`, `name`, `best_guess` FROM `calendar` WHERE YEAR(date) = $year AND MONTH(date) = $month AND `approved` = 1 ORDER BY `date` ASC, `name` ASC");
	while ($listing = $db->fetch())
	{
		$get_date = date_parse($listing['date']);
		$current_day = ordinal($get_date['day']);

		$templating->block('item', 'calendar');
		$best_guess = '';
		if ($listing['best_guess'] == 1)
		{
			$best_guess = '<span class="tooltip-top badge blue" title="We haven\'t been given an exact date!">Best Guess</span>';
		}
		$templating->set('best_guess', $best_guess);
		$templating->set('day', $current_day);

		$today = '';
		if ($get_date['day'] == date('d') && $get_date['month'] == date('m'))
		{
			$today = '<span class="badge green">Releasing Today!</span> ';
		}

		$game_name = $today . '<a href="/index.php?module=game&amp;game-id='.$listing['id'].'">'.$listing['name'].'</a>';

		$templating->set('name', $game_name);

		$edit = '';
		if ($user->check_group(1,2) == true || $user->check_group(5) == true)
		{
			$edit = ' - <a href="/admin.php?module=games&view=edit&id='.$listing['id'].'&return=calendar">Edit</a>';
		}
		$templating->set('edit', $edit);
	}

	$templating->block('head', 'calendar');
	$templating->set('prev', $prev_month);
	$templating->set('next', $next_month);
	$templating->set('prev_year', $prev_year);
	$templating->set('next_year', $next_year);
	$templating->set('month', $months_array[$month] . ' ' . $year . ' (Total: ' . $counter['count'] . ')');

	$templating->block('bottom', 'calendar');

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'submit')
	{
		if (!isset($_SESSION['user_id']))
		{
			header("Location: /index.php?module=calendar&error=notloggedin");
			exit;
		}

		if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == 0)
		{
			header("Location: /index.php?module=calendar&error=notloggedin");
			exit;
		}

		$name = trim($_POST['name']);
		$name = htmlspecialchars($name);

		if (empty($name) || empty($_POST['date']))
		{
			header("Location: /index.php?module=calendar&error=missing");
			exit;
		}

		if (!empty($_POST['link']))
		{
			if (strpos($_POST['link'], "www.") === false && strpos($_POST['link'], "http") === false)
			{
				header("Location: /index.php?module=calendar&error=missing");
				exit;
			}
		}

		$db->sqlquery("SELECT `id`, `name` FROM `calendar` WHERE `name` = ?", array($name));
		if ($db->num_rows() == 1)
		{
			$game = $db->fetch();
			header("Location: /index.php?module=calendar&error=exists&id=" . $game['id']);
			exit;
		}

		$date = new DateTime($_POST['date']);

		$guess = 0;
		if (isset($_POST['guess']))
		{
			$guess = 1;
		}

		$db->sqlquery("INSERT INTO `calendar` SET `name` = ?, `date` = ?, `link` = ?, `best_guess` = ?, `approved` = 0", array($name, $date->format('Y-m-d'), $_POST['link'], $guess));

		$new_id = $db->grab_id();

		$db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 0, `action` = ?, `created` = ?, `calendar_id` = ?", array("{$_SESSION['username']} added a new game to the release calendar.", core::$date, $new_id));

		header("Location: /index.php?module=calendar&message=sent");
	}
}
