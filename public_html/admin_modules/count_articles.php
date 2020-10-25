<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted: Admin count articles.');
}

$templating->load('admin_modules/count_articles');
$templating->block('main');

if (isset($_POST['act']) && $_POST['act'] == 'count')
{
	// tool for making a single user account
	$start_date = core::make_safe($_POST['start_date'] . ' ' . $_POST['start_time']);
	$end_date = core::make_safe($_POST['end_date']. ' ' . $_POST['end_time']);

	$check_empty = core::mempty(compact('start_date', 'end_date'));
	if ($check_empty !== true)
	{
		$_SESSION['message'] = 'empty';
		$_SESSION['message_extra'] = $check_empty;
		header("Location: /admin.php?module=count_articles");
		die();
	}

	$start_db = strtotime($start_date);
	$end_db = strtotime($end_date);

	$total = $dbl->run("SELECT COUNT(*) FROM `articles` WHERE `date` >= ? AND `date` <= ? AND `draft` = 0 AND `active` = 1", array($start_db, $end_db))->fetchOne();
	$templating->block('results');
	$templating->set('results', $total);
}
?>