<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->set_previous('title', 'Livestreaming schedule', 1);
$templating->set_previous('meta_description', 'GamingOnLinux livestreaming schedule', 1);

if (!isset($_POST['act']))
{
	$templating->load('livestreams');

	$templating->block('top', 'livestreams');
	$edit_link = '';
	if ($user->check_group([1,2]))
	{
		$edit_link = '<span class="fright"><a href="admin.php?module=livestreams&amp;view=manage">Edit Livestreams</a></span>';
	}
	$templating->set('edit_link', $edit_link);

	$user_timezone = $user->user_details['timezone'];

	if (isset($_SESSION['user_id']) && $_SESSION['user_id'])
	{
		$templating->block('submit', 'livestreams');
		$timezones = core::timezone_list($user_timezone);
		$templating->set('timezones_list', $timezones);

		// if they have done it before set title, text and tagline
		if (isset($message_map::$error) && $message_map::$error == 1)
		{
			$templating->set('title', $_SESSION['e_title']);
			$templating->set('community_name', $_SESSION['e_community_name']);
			$templating->set('stream_url', $_SESSION['e_stream_url']);
			$templating->set('date', $_SESSION['e_date']);
			$templating->set('end_date', $_SESSION['e_end_date']);
		}

		else
		{
			$templating->set('title', '');
			$templating->set('tagline', '');
			$templating->set('stream_url', '');
			$templating->set('date', '');
			$templating->set('end_date', '');
		}
	}

	$grab_streams = $dbl->run("SELECT `row_id`, `title`, `date`, `end_date`, `community_stream`, `streamer_community_name`, `stream_url`, `author_id` FROM `livestreams` WHERE NOW() < `end_date` AND `accepted` = 1 ORDER BY `date` ASC")->fetch_all();
	if ($grab_streams)
	{
		foreach ($grab_streams as $streams)
		{
			$templating->block('item', 'livestreams');

			$badge = '';
			if ($streams['community_stream'] == 1)
			{
				$badge = '<span class="badge blue">Community Stream</span>';
			}
			else if ($streams['community_stream'] == 0)
			{
				$badge = '<span class="badge editor">Official GOL Stream</span>';
			}
			$templating->set('badge', $badge);

			$stream_url = 'https://www.twitch.tv/gamingonlinux';
			if ($streams['community_stream'] == 1)
			{
				$stream_url = $streams['stream_url'];
			}
			$templating->set('stream_url', $stream_url);

			$templating->set('title', $streams['title']);
			
			$templating->set('local_time', core::adjust_time($streams['date'], 'UTC', $user_timezone));
			$templating->set('local_time_end', core::adjust_time($streams['end_date'], 'UTC', $user_timezone));

			$countdown = '<span id="timer'.$streams['row_id'].'"></span><script type="text/javascript">var timer' . $streams['row_id'] . ' = moment.tz("'.$streams['date'].'", "UTC"); $("#timer'.$streams['row_id'].'").countdown(timer'.$streams['row_id'].'.toDate(),function(event) {$(this).text(event.strftime(\'%D days %H:%M:%S\'));});</script>';
			$templating->set('countdown', $countdown);

			$streamer_list = [];
			$grab_streamers = $dbl->run("SELECT s.`user_id`, u.`username` FROM `livestream_presenters` s INNER JOIN `users` u ON u.`user_id` = s.`user_id` WHERE `livestream_id` = ?", array($streams['row_id']))->fetch_all();
			foreach ($grab_streamers as $streamer)
			{
				$streamer_list[] = '<a href="/profiles/' . $streamer['user_id'] . '">'.$streamer['username'].'</a>';
			}

			if (!empty($streamer_list))
			{
				$streamer_list = implode(', ', $streamer_list);
				if (!empty($streams['streamer_community_name']))
				{
					$streamer_list .= ', ' . $streams['streamer_community_name'];
				}
			}
			else
			{
				$streamer_list = $streams['streamer_community_name'];
			}
			
			$templating->set('profile_links', $streamer_list);

			$options = '';
			if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
			{
				if ($_SESSION['user_id'] == $streams['author_id'] || $user->check_group([1,2]) == true)
				{
					$options = '<input type="hidden" name="livestream_id" value="'.$streams['row_id'].'" /><button formaction="/index.php?module=livestreams" value="Delete" name="act">Delete</button>';
				}
			}
			if (!empty($options))
			{
				$options = '<form method="post">' . $options . '</form>';
			}
			$templating->set('options', $options);
		}
	}
	else
	{
		$core->message('There are no livestreams currently planned, or we forgot to update this page. Please <a href="https://www.gamingonlinux.com/forum/2">bug us to update it</a>!');
	}
}

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'submit')
	{		
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			// wipe any old details
			unset($_SESSION['live_info']);
		
			$start_time = core::adjust_time($_POST['date'], $_POST['timezone'], 'UTC', 0);
			$end_time = core::adjust_time($_POST['end_date'], $_POST['timezone'], 'UTC', 0);
			$title = trim($_POST['title']);
			$title = strip_tags($title);
			$community_name = trim($_POST['community_name']);
			$community_name = strip_tags($community_name);
			$stream_url = trim($_POST['stream_url']);
			$stream_url = strip_tags($stream_url);
			
			$user_ids = [];
			if (isset($_POST['user_ids']) && !empty($_POST['user_ids']))
			{
				$user_ids = $_POST['user_ids'];
			}

			$empty_check = core::mempty(compact('title', 'start_time', 'end_time', 'stream_url'));
			
			if ($empty_check !== true)
			{
				$_SESSION['message'] = 'empty';
				$_SESSION['message_extra'] = $empty_check;
				header("Location: /index.php?module=livestreams");
				die();
			}

			// check their time first
			$check_start = strtotime($start_time);
			$check_end = strtotime($end_time);

			if ($check_end <= $check_start)
			{
				$_SESSION['e_title'] = $title;
				$_SESSION['e_community_name'] = $community_name;
				$_SESSION['e_stream_url'] = $stream_url;
				$_SESSION['e_date'] = $_POST['date'];
				$_SESSION['e_end_date'] = $_POST['end_date'];

				$_SESSION['message'] = 'ends_before_start';
				header("Location: /index.php?module=livestreams");
				die();				
			}
			
			// ask them to check their time before continuing
			$date1 = new DateTime($start_time);
			$date2 = new DateTime($end_time);
			$diff = $date2->diff($date1);
		
			$_SESSION['live_info'] = ['start' => $start_time, 'end' => $end_time, 'title' => $title, 'community_name' => $community_name, 'stream_url' => $stream_url, 'user_ids' => $user_ids];
		
			$confirmation_text = 'The stream will last ' . $diff->format('%a Day and %h Hours') . '<br />Start time: ' . $_POST['date'] . ' ('.$_POST['timezone'].')<br />End time: ' . $_POST['end_date'] . ' ('.$_POST['timezone'].')<br />Title: ' . $title . '<br />Stream url: ' . $stream_url;
			
			$core->confirmation(['title' => 'Please confirm these details are correct!', 'text' => $confirmation_text, 'act' => 'submit', 'action_url' => '/index.php?module=livestreams']);
		}

		else if (isset($_POST['no']))
		{
			header("Location: /index.php?module=livestreams");
			die();
		}

		else if (isset($_POST['yes']))
		{
			$date_created = core::$sql_date_now;

			$mod_queue = $user->user_details['in_mod_queue'];
			$forced_mod_queue = $user->can('forced_mod_queue');

			$approved = 1;
			if ($mod_queue == 1 || $forced_mod_queue == true)
			{
				$approved = 0;
			}

			$dbl->run("INSERT INTO `livestreams` SET `author_id` = ?, `accepted` = ?, `title` = ?, `date_created` = ?, `date` = ?, `end_date` = ?, `community_stream` = 1, `streamer_community_name` = ?, `stream_url` = ?", array($_SESSION['user_id'], $approved, $_SESSION['live_info']['title'], $date_created, $_SESSION['live_info']['start'], $_SESSION['live_info']['end'], $_SESSION['live_info']['community_name'], $_SESSION['live_info']['stream_url']));
			$new_id = $dbl->new_id();

			$core->process_livestream_users($new_id, $_SESSION['live_info']['user_ids']);
			
			// add a new notification for the mod queue
			if ($approved == 0)
			{
				$_SESSION['message'] = 'livestream_submitted';
				$core->new_admin_note(array('content' => ' has submitted a new livestream titled: <a href="/admin.php?module=livestreams&view=submitted">'.$_SESSION['live_info']['title'].'</a>.', 'type' => 'new_livestream_submission', 'data' => $new_id));
			}
			else
			{
				$_SESSION['message'] = 'livestream_accepted';
			}

			unset($_SESSION['live_info']);
			header("Location: /index.php?module=livestreams");
			die();
		}
	}
	if ($_POST['act'] == 'Delete')
	{
		if (!isset($_POST['yes']) && !isset($_POST['no']))
		{
			$core->confirmation(['title' => 'Are you sure you wish to delete that livestream?', 'text' => 'This cannot be undone!', 'act' => 'Delete', 'action_url' => '/index.php?module=livestreams', 'act_2_name' => 'livestream_id', 'act_2_value' => $_POST['livestream_id']]);
		}

		else if (isset($_POST['no']))
		{
			header("Location: /index.php?module=livestreams");
			die();
		}
		else if (isset($_POST['yes']))
		{
			if (!isset($_POST['livestream_id']))
			{
				header("Location: /index.php?module=livestreams");
				die();
			}

			$checkid = $dbl->run("SELECT `author_id` FROM `livestreams` WHERE NOW() < `end_date` AND `accepted` = 1 ORDER BY `date` ASC")->fetchOne();
			if ($checkid)
			{
				if ($checkid != $_SESSION['user_id'] && $user->check_group([1,2]) != true)
				{
					header("Location: /index.php?module=livestreams");
					die();				
				}
				$dbl->run("DELETE FROM `livestreams` WHERE `row_id` = ? AND `author_id` = ?", array($_POST['livestream_id'], $checkid));

				$_SESSION['message'] = 'deleted';
				$_SESSION['message_extra'] = 'livestream entry';
				header("Location: /index.php?module=livestreams");
				die();
			}

		}
	}
}
