<?php
$templating->set_previous('title', 'Linux gamer video directory', 1);
$templating->set_previous('meta_description', 'A list of channels to watch for Linux gaming content', 1);

$templating->load('videos');
$templating->block('top');
$templating->set('twitch_key', core::config('twitch_dev_key'));

// paging for pagination
$page = 1;
if (!isset($_GET['page']) || $_GET['page'] == 0)
{
  $page = 1;
}

else if (is_numeric($_GET['page']))
{
  $page = $_GET['page'];
}

$db->sqlquery("SELECT COUNT(user_id) as count FROM `users` WHERE `twitch` != '' OR `youtube` != ''");
$counter = $db->fetch();

// sort out the pagination link
$pagination = $core->pagination_link(30, $counter['count'], "/index.php?module=video&amp;", $page);

$db->sqlquery("SELECT `username`, `youtube`, `twitch` FROM `users` WHERE `twitch` != '' OR `youtube` != '' LIMIT ?, 30", array($core->start));
while ($user_list = $db->fetch())
{
  $templating->block('user');
  $templating->set('username', $user_list['username'] . '<br />');

  $twitch = '';
  if (!empty($user_list['twitch']))
  {
    if(strpos($user_list['twitch'], "http://") === false && strpos($user_list['twitch'], "https://") === false )
    {
      $user_list['twitch'] = 'https://' . $user_list['twitch'];
    }
    if(strpos($user_list['twitch'], "www.") === false)
    {
      $user_list['twitch'] = 'www.' . $user_list['twitch'];
    }
    $url = parse_url($user_list['twitch']);
    if (isset($url['path']))
    {
      $twitch_username = str_replace('/', '', $url['path']);
    }
    $twitch = '<a class="ltwitch" data-tnick="'.$twitch_username.'" href="' . $user_list['twitch'] . '">Twitch</a> <span></span><br />';
  }
  $templating->set('twitch', $twitch);

  $youtube = '';
  if (!empty($user_list['youtube']))
  {
    $youtube = '<a href="'.$user_list['youtube'].'">Youtube</a><br />';
  }
  $templating->set('youtube', $youtube);

}
$templating->block('bottom');
$templating->set('pagination', $pagination);
