<?php
$forum_types = ['normal_forum', 'flat_forum'];

$forum_type = $user->get('forum_type', $_SESSION['user_id']);

if (!empty($forum_type) && in_array($forum_type, $forum_types))
{
	include($core->config('path') . 'modules/forum/'.$forum_type.'.php');
}
else
{
	include($core->config('path') . 'modules/forum/normal_forum.php');
}
?>
