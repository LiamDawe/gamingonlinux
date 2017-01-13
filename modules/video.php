<?php
$templating->set_previous('title', 'Linux gamer video directory', 1);
$templating->set_previous('meta_description', 'A list of channels to watch for Linux gaming content', 1);

$templating->load('videos');
$templating->block('top');
$templating->set('twitch_key', core::config('twitch_dev_key'));

$db->sqlquery("SELECT `username`, `youtube`, `twitch` FROM `users` WHERE `twitch` != '' OR `youtube` != ''");
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
