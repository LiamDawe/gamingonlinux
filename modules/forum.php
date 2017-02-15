<?php
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
