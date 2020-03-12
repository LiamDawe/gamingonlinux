<?php
session_start();

define("APP_ROOT", dirname( dirname ( dirname ( dirname(__FILE__) ) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

header('Content-Type: application/json');

if(isset($_GET['last_id']) || isset($_GET['last_id']))
{
	if ($user->check_group([1,2,5]))
	{
		$templating->load('admin_modules/admin_home');

		$grab_notes = $notifications->load_admin_notifications($_GET['last_id']);
		
		foreach ($grab_notes['rows'] as $tracking)
		{
			$templating->block('tracking_row', 'admin_modules/admin_home');

			$templating->set('editor_action', $tracking);
		}

		$last_id = $grab_notes['last_id'];

		// count if there's any left
		$total_left = $dbl->run("SELECT COUNT(*) FROM `admin_notifications` WHERE `id` < ? ORDER BY `id` DESC", array($last_id))->fetchOne();

		echo json_encode(array("result" => 'done', 'text' => $templating->output(), 'last_id' => $last_id, 'total_left' => $total_left));
	}
}
