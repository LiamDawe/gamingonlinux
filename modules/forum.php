<?php
$forum_type = 'normal_forum';
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0)
{
	$forum_type = $user->get('forum_type', $_SESSION['user_id']);
}

include($core->config('path') . 'modules/forum/'.$forum_type.'.php');
?>
