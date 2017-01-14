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

  // loop over each one and do some basic sanity checks to make sure it's a proper URL
  $to_check = array('twitch', 'youtube');
  $total_array = count($to_check);
  $counter = 0;
  foreach ($to_check as $check)
  {
    $check_output = '';
    if (!empty($user_list[$check]))
    {
      $counter++;

      $check_output = $user_list[$check];

      /* just to make sure the link is a proper URL, remove any combination of them, and then set it properly manually
       this way we don't have to work out what has what vs what doesn't have what
       it seems silly, but it's the only real sane way to make sure
      */
      $remove_array = array('www.', 'http://', 'https://');
      $check_output = str_replace($remove_array, '', $check_output);
      $check_output = 'https://www.' . $check_output;

      // make the first letter a capital, as we don't use capitals in the DB
      $service_name = ucfirst($check);

      // grab the twitch username for the online checker
      $additional_classes = '';
      if ($check == 'twitch')
      {
        // their username will be the last thing in the path after the slash (we remove the slash to get it)
        $url = parse_url($check_output);
        if (isset($url['path']))
        {
          $twitch_username = str_replace('/', '', $url['path']);
        }
        $additional_classes = 'class="ltwitch" data-tnick="'.$twitch_username.'"';
      }

      // add a break if it's not the last link in the loop
      $check_output = '<a '.$additional_classes.' href="'.$check_output.'">'.$service_name.'</a> <span></span>';
      if ($counter != $total_array)
      {
        $check_output = $check_output . '<br />';
      }
    }

    // additional stuff per-type of field for special content
    $templating->set($check, $check_output);
  }
}
$templating->block('bottom');
$templating->set('pagination', $pagination);
