<?php
// get the class to handle it
include('./includes/class_online.php');

// main menu block
$templating->merge('blocks/block_online');

$visitors_online = new usersOnline();

$templating->block('block');

$total_count = $visitors_online->count_users();

if ($total_count == 1) 
{
	$total =  "There is 1 in total online";
}
else 
{
	$total =  "There are " . $total_count . " in total online";
}

$templating->set('total_count', $total);

// now get usernames of people online
$user_list = '';
$user_count = 0;

$db->sqlquery("SELECT distinct o.`user_id`, u.`username` FROM `users` u INNER JOIN `online_list` o ON u.user_id = o.user_id WHERE o.user_id != 0 ORDER BY `username` ASC LIMIT 10");
$count_actual_users = $db->num_rows();
while ($online_list = $db->fetch())
{
	$user_count++;
	
	if ($count_actual_users == 1)
	{
		$user_list = "<a href=\"/profiles/{$online_list['user_id']}\">{$online_list['username']}</a>";
	}
	
	else
	{
		if ($user_count < 10)
		{
			$user_list .= "<a href=\"/profiles/{$online_list['user_id']}\">{$online_list['username']}</a>, ";
		}
		
		else if ($user_count == 10)
		{
			$user_list .= "<a href=\"/profiles/{$online_list['user_id']}\">{$online_list['username']}</a>";
		}
	}
}

$templating->set('users', $user_list);
?>
