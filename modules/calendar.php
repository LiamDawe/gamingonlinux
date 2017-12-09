<?php
if (isset($_GET['today']))
{
	header("Location: /index.php?module=calendar#today");
}

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

$templating->load('calendar');

// count how many there is due this month
$dbl->run("SELECT COUNT(id) as count FROM `calendar` WHERE YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE()) AND DAY(date) > DAY(CURDATE()) AND `approved` = 1 AND `also_known_as` IS NULL");
$counter = $dbl->fetch();

$templating->block('top');
$templating->set('this_month', $counter['count']);

$editor_links = '';
if ($user->check_group([1,2,5]))
{
	$editor_links = '<span class="fright">Editor Links: <a href="/admin.php?module=games&view=add">Add a game</a></span>';
}
$templating->set('editor_links', $editor_links);

$templating->load('game-search');
$templating->block('search', 'game-search');
$templating->set('search_text', '');

if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
{
	$templating->block('submit', 'calendar');
}

$templating->block('picker', 'calendar');

$max_year = date('Y') + 5;

$years_array = range(2010, $max_year);
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
$counter = $dbl->run("SELECT COUNT(id) FROM `calendar` WHERE YEAR(date) = $year AND MONTH(date) = $month AND `approved` = 1 AND `also_known_as` IS NULL")->fetchOne();

$templating->set('month', $months_array[$month] . ' ' . $year . ' (Total: ' . $counter . ')');

$get_listings = $dbl->run("SELECT `id`, `date`, `name`, `best_guess`, `is_dlc`, `link`, `gog_link`, `steam_link`, `itch_link`, `small_picture` FROM `calendar` WHERE YEAR(date) = $year AND MONTH(date) = $month AND `approved` = 1 AND `also_known_as` IS NULL AND (link != '' OR gog_link != '' OR steam_link != '' OR itch_link != '') ORDER BY `date` ASC, `name` ASC")->fetch_all();

// first grab a list of all the genres for each game, so we only do one query instead of one for each
$genre_ids = [];
foreach ($get_listings as $set)
{
	$genre_ids[] = $set['id'];
}
$in  = str_repeat('?,', count($genre_ids) - 1) . '?';
$genre_tag_sql = "SELECT r.`game_id`, g.name FROM `game_genres_reference` r INNER JOIN `game_genres` g ON g.id = r.genre_id WHERE r.`game_id` IN ($in) GROUP BY r.`game_id`, g.name";
$genre_res = $dbl->run($genre_tag_sql, $genre_ids)->fetch_all(PDO::FETCH_COLUMN|PDO::FETCH_GROUP);

$templating->block('head', 'calendar');

$last_date = NULL;
$current_date = NULL;

foreach ($get_listings as $listing)
{
	$current_date = $listing['date'];

	if (isset($last_date) && $current_date != $last_date)
	{
		$templating->block('day_end', 'calendar');
	}

    if (!isset($last_date) || $last_date !== $listing['date']) 
    {
        $templating->block('day', 'calendar');
        $templating->set('group_date', $listing['date']);
        
		// if the date is today
		$today_anchor = '';
		$today_css = '';
		$today_text = '';
        if ($listing['date'] == date('Y-m-d'))
        {
			$today_anchor = '<a class="anchor" id="today"></a>';
			$today_css = 'id="calendar_today"';
			$today_text = '<span class="badge">Releasing Today!</span> ';
		}
		$templating->set('today_anchor', $today_anchor);
		$templating->set('today_css', $today_css);
		$templating->set('today_text', $today_text);
	}
    
	$get_date = date_parse($listing['date']);

	$templating->block('item', 'calendar');

	$small_pic = '';
	if ($listing['small_picture'] != NULL && $listing['small_picture'] != '')
	{
		$small_pic = '<img src="' . $core->config('website_url') . 'uploads/gamesdb/small/' . $listing['small_picture'] . '" alt="" />';
	}
	$templating->set('small_pic', $small_pic);

	$best_guess = '';
	if ($listing['best_guess'] == 1)
	{
		$best_guess = '<span class="tooltip-top badge blue" title="We haven\'t been given an exact date!">Best Guess</span>';
	}
	$templating->set('best_guess', $best_guess);
	$dlc = '';
	if ($listing['is_dlc'] == 1)
	{
		$dlc = '<span class="badge yellow">DLC</span>';
	}
	$templating->set('dlc', $dlc);

	$game_name = $listing['name'];

	$templating->set('name', $game_name);
	
	$links = [];
	if (!empty($listing['link']) && $listing['link'] != NULL)
	{
		$links[] = '<a href="'.$listing['link'].'">Official Site</a>';
	}
	if (!empty($listing['gog_link']) && $listing['gog_link'] != NULL)
	{
		$links[] = '<a href="'.$listing['gog_link'].'">GOG</a>';
	}
	if (!empty($listing['steam_link']) && $listing['steam_link'] != NULL)
	{
		$links[] = '<a href="'.$listing['steam_link'].'">Steam</a>';
	}
	if (!empty($listing['itch_link']) && $listing['itch_link'] != NULL)
	{
		$links[] = '<a href="'.$listing['itch_link'].'">itch.io</a>';
	}
	$templating->set('links', implode(' - ', $links));
		
	$edit = '';
	if ($user->check_group([1,2,5]))
	{
		$edit = ' <a href="/admin.php?module=games&view=edit&id='.$listing['id'].'&return=calendar"><span class="icon edit edit-sale-icon"></span></a>';
	}
	$templating->set('edit', $edit);
	
	$last_date = $listing['date'];

	$genre_output = '';
	$genre_list = [];
	if (isset($genre_res[$listing['id']]))
	{
		$genre_output = $templating->block_store('genres', 'calendar');
		foreach ($genre_res[$listing['id']] as $k => $name)
		{
			$genre_list[] = "<span class=\"badge\">{$name}</span>";
		}

		$genre_output = $templating->store_replace($genre_output, array('genre_list' => 'Tags: ' . implode(' ', $genre_list)));					
	}

	$templating->set('genre_list', $genre_output);
}

$templating->block('bottom', 'calendar');

$templating->block('picker', 'calendar');
$templating->set('prev', $prev_month);
$templating->set('next', $next_month);
$templating->set('prev_year', $prev_year);
$templating->set('next_year', $next_year);
$templating->set('month', $months_array[$month] . ' ' . $year . ' (Total: ' . $counter . ')');

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'submit')
	{
		if ( (!isset($_SESSION['user_id'])) || (isset($_SESSION['user_id']) && $_SESSION['user_id'] == 0) || (!core::is_number($_SESSION['user_id'])))
		{
			header("Location: /index.php?module=calendar&message=notloggedin");
			die();
		}

		$name = core::make_safe($_POST['name']);
		$date = core::make_safe($_POST['date']);
		$link = core::make_safe($_POST['link']);

		$check_empty = core::mempty(compact('name', 'date', 'link'));

		if ($check_empty !== true)
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = $check_empty;
			header("Location: /index.php?module=calendar");
			die();
		}

		if (!empty($_POST['link']))
		{
			if (strpos($_POST['link'], "www.") === false && strpos($_POST['link'], "http") === false)
			{
				$_SESSION['message'] = 'empty';
				$_SESSION['message_extra'] = 'link';
				header("Location: /index.php?module=calendar");
				die();
			}
		}

		$check = $dbl->run("SELECT `id`, `name` FROM `calendar` WHERE `name` = ?", array($name));
		if ($check)
		{
			$game = $dbl->fetch();
			$_SESSION['message'] = 'exists';
			$_SESSION['message_extra'] = $game['id'];
			header("Location: /index.php?module=calendar");
			die();
		}

		$date = new DateTime($_POST['date']);

		$guess = 0;
		if (isset($_POST['guess']))
		{
			$guess = 1;
		}

		$dbl->run("INSERT INTO `calendar` SET `name` = ?, `date` = ?, `link` = ?, `best_guess` = ?, `approved` = 0", array($name, $date->format('Y-m-d'), $_POST['link'], $guess));

		$new_id = $dbl->grab_id();
		
		if (isset($_POST['genre_ids']) && is_array($_POST['genre_ids']) && core::is_number($_POST['genre_ids']))
		{
			foreach ($_POST['genre_ids'] as $genre_id)
			{
				$dbl->run("INSERT INTO `game_genres_reference` SET `game_id` = ?, `genre_id` = ?", array($new_id, $genre_id));
			}
		}

		$dbl->run("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 0, `type` = ?, `created_date` = ?, `data` = ?", array($_SESSION['user_id'], 'calendar_submission', core::$date, $new_id));

		$_SESSION['message'] = 'game_submitted';
		header("Location: /index.php?module=calendar");
	}
}