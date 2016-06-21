<?php
$forum_type = 'normal_forum';
if (isset($_SESSION['forum_type']))
{
	$forum_type = $_SESSION['forum_type'];
}
include(core::config('path') . 'modules/forum/'.$forum_type.'.php')
?>
