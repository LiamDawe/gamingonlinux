<?php
$templating->set_previous('title', 'GamingOnLinux livestreaming schedule', 1);
$templating->set_previous('meta_description', 'GamingOnLinux livestreaming schedule', 1);

$templating->load('livestreams');

$templating->block('top');

$db->sqlquery("SELECT l.`row_id`, l.`title`, l.`date`, u.`username`, u.`user_id` FROM `livestreams` l INNER JOIN `users` u ON l.`owner_id` = u.`user_id` ORDER BY `date` ASC");
if ($db->num_rows() > 0)
{
  while ($streams = $db->fetch())
  {
    $templating->block('item');
    $templating->set('title', $streams['title']);
    $templating->set('username', $streams['username']);
    $templating->set('time', $streams['date']);

    $countdown = '<span id="timer'.$streams['row_id'].'"></span><script type="text/javascript">var timer' . $streams['row_id'] . ' = moment.tz("'.$streams['date'].'", "UTC"); $("#timer'.$streams['row_id'].'").countdown(timer'.$streams['row_id'].'.toDate(),function(event) {$(this).text(event.strftime(\'%D days %H:%M:%S\'));});</script>';
    $templating->set('countdown', $countdown);
  }
}
else {
  $core->message('There are no livestreams currently planned, or we forgot to update this page. Please bug us to update it!');
}
