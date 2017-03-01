<?php
$templating->set_previous('title', 'Livestreaming schedule', 1);
$templating->set_previous('meta_description', 'GamingOnLinux livestreaming schedule', 1);

$templating->load('livestreams');

$templating->block('top', 'livestreams');
$edit_link = '';
if ($user->check_group([1,2]))
{
  $edit_link = '<span class="fright"><a href="admin.php?module=livestreams&amp;view=manage">Edit Livestreams</a></span>';
}
$templating->set('edit_link', $edit_link);

if (isset($_SESSION['user_id']) && $_SESSION['user_id'])
{
  $templating->block('submit', 'livestreams');
}

$db->sqlquery("SELECT `row_id`, `title`, `date`, `end_date`, `community_stream`, `streamer_community_name`, `stream_url` FROM `livestreams` WHERE NOW() < `end_date` AND `accepted` = 1 ORDER BY `date` ASC");
if ($db->num_rows() > 0)
{
  $grab_streams = $db->fetch_all_rows();
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
    $templating->set('time', $streams['date']);
    $templating->set('end_time', $streams['end_date']);

    $countdown = '<span id="timer'.$streams['row_id'].'"></span><script type="text/javascript">var timer' . $streams['row_id'] . ' = moment.tz("'.$streams['date'].'", "UTC"); $("#timer'.$streams['row_id'].'").countdown(timer'.$streams['row_id'].'.toDate(),function(event) {$(this).text(event.strftime(\'%D days %H:%M:%S\'));});</script>';
    $templating->set('countdown', $countdown);

    $streamer_list = '';
    $db->sqlquery("SELECT s.`user_id`, u.`username` FROM `livestream_presenters` s INNER JOIN users u ON u.`user_id` = s.`user_id` WHERE `livestream_id` = ?", array($streams['row_id']));
    $total_streamers = $db->num_rows();
    $streamer_counter = 0;
    while ($grab_streamers = $db->fetch())
    {
      $streamer_counter++;
      if (core::config('pretty_urls') == 1)
      {
        $streamer_list .= '<a href="/profiles/' . $grab_streamers['user_id'] . '">'.$grab_streamers['username'].'</a>';
      }
      else
      {
        $streamer_list .= '<a href="/index.php?module=profile&user_id=' . $grab_streamers['user_id'] . '">'.$grab_streamers['username'].'</a>';
      }
      if ($streamer_counter != $total_streamers)
      {
        $streamer_list .= ', ';
      }
    }
    if (!empty($streams['streamer_community_name']))
    {
      if (!empty($streamer_list))
      {
        $streamer_list = $streamer_list . ', ' . $streams['streamer_community_name'];
      }
      else
      {
        $streamer_list = $streams['streamer_community_name'];
      }
    }
    $templating->set('profile_links', $streamer_list);
    $templating->set('users_list', $streamer_list);
  }
}
else
{
  $core->message('There are no livestreams currently planned, or we forgot to update this page. Please <a href="https://www.gamingonlinux.com/forum/2">bug us to update it</a>!');
}

if (isset($_POST['act']))
{
  if ($_POST['act'] == 'submit')
  {
    $title = trim($_POST['title']);
    $title = strip_tags($title);
    $community_name = trim($_POST['community_name']);
    $community_name = strip_tags($community_name);
    $stream_url = trim($_POST['stream_url']);
    $stream_url = strip_tags($stream_url);
    $date = $_POST['date'];
    $end_date = $_POST['end_date'];
    
	$empty_check = core::mempty(compact('title', 'date', 'end_date', 'stream_url'));
	
    if ($empty_check !== true)
    {
		$_SESSION['message'] = 'empty';
		$_SESSION['message_extra'] = $empty_check;
		header("Location: /index.php?module=livestreams");
		die();
    }

    $date = new DateTime($_POST['date']);
    $end_date = new DateTime($_POST['end_date']);

    $date_created = date('Y-m-d H:i:s');

    $db->sqlquery("INSERT INTO `livestreams` SET `author_id` = ?, `accepted` = 0, `title` = ?, `date_created` = ?, `date` = ?, `end_date` = ?, `community_stream` = 1, `streamer_community_name` = ?, `stream_url` = ?", array($_SESSION['user_id'], $title, $date_created, $date->format('Y-m-d H:i:s'), $end_date->format('Y-m-d H:i:s'), $community_name, $stream_url));
    $new_id = $db->grab_id();

    $core->process_livestream_users($new_id);

    $db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `type` = ?, `completed` = 0, `created_date` = ?, `data` = ?", array($_SESSION['user_id'], 'new_livestream_submission', core::$date, $new_id));

	$_SESSION['message'] = 'livestream_submitted';
    header("Location: /index.php?module=livestreams");
  }
}
