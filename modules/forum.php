<?php
if (isset($_GET['message']))
{
  $extra = NULL;
  if (isset($_GET['extra']))
  {
    $extra = $_GET['extra'];
  }
  $message = $message_map->get_message($_GET['message'], $extra);
  $core->message($message['message'], NULL, $message['error']);
}

$forum_types = ['normal_forum', 'flat_forum'];

$forum_type = 'normal_forum';
if (isset($_SESSION['forum_type']))
{
	$forum_type = $_SESSION['forum_type'];
}
if (in_array($_SESSION['forum_type'], $forum_types))
{
	include(core::config('path') . 'modules/forum/'.$forum_type.'.php');
}
else
{
	include(core::config('path') . 'modules/forum/normal_forum.php');
}
?>
