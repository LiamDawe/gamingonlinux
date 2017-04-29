<?php
$forum_type = 'normal_forum';
if (isset($_SESSION['user_id']))
{
	$forum_type = $user->get('forum_type', $_SESSION['user_id'])['forum_type'];
}

include($core->config('path') . 'modules/forum/'.$forum_type.'.php');
?>
