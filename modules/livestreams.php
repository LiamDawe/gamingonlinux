<?php
$templating->set_previous('title', 'Livestreaming schedule', 1);
$templating->set_previous('meta_description', 'GamingOnLinux livestreaming schedule', 1);

$templating->load('livestreams');

$templating->block('top');
$edit_link = '';
if ($user->check_group(1,2) == true)
{
  $edit_link = '<span class="fright"><a href="admin.php?module=livestreams&amp;view=manage">Edit Livestreams</a></span>';
}
$templating->set('edit_link', $edit_link);

$db->sqlquery("SELECT `row_id`, `title`, `date`, `end_date`, `community_stream`, `streamer_community_name`, `stream_url` FROM `livestreams` WHERE NOW() < `end_date` ORDER BY `date` ASC");
if ($db->num_rows() > 0)
{
  $grab_streams = $db->fetch_all_rows();
  foreach ($grab_streams as $streams)
  {
    $templating->block('item');

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
    $db->sqlquery("SELECT s.`user_id`, u.username FROM `livestream_presenters` s INNER JOIN users u ON u.user_id = s.user_id WHERE `livestream_id` = ?", array($streams['row_id']));
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
        $streamer_list = $streamer_list + ', ' . $streams['streamer_community_name'];
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
else {
  $core->message('There are no livestreams currently planned, or we forgot to update this page. Please <a href="https://www.gamingonlinux.com/forum/2">bug us to update it</a>!');
}
