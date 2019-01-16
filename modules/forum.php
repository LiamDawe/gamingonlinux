<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$forum_types = ['normal_forum', 'flat_forum'];

$forum_type = $user->user_details['forum_type'];

if (!empty($forum_type) && in_array($forum_type, $forum_types))
{
	include($core->config('path') . 'modules/forum/'.$forum_type.'.php');
}
else
{
	include($core->config('path') . 'modules/forum/normal_forum.php');
}
?>
