<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}
$templating->set_previous('title', 'Block List' . $templating->get('title', 1)  , 1);

if (isset($_POST['act']))
{
	if ($_POST['act'] == 'add')
	{
		$user->block_user($_POST['block_id']);
	}
	
	if ($_POST['act'] == 'remove')
	{
		$user->unblock_user($_POST['block_id']);
	}
}

$templating->load('usercp_modules/block_list');
$templating->block('main');

$templating->block('blocked_list');

$list = '';
foreach ($user->blocked_users as $username => $blocked_id)
{
	$list .= '<li>'.$username.' <form method="post"><button name="act" value="remove" formaction="/usercp.php?module=block_list">Unblock</button><input type="hidden" name="block_id" value="'.$blocked_id[0].'" /></form></li>';
}
$templating->set('list', $list);
?>
