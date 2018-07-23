<?php
session_start();

define("APP_ROOT", dirname( dirname ( dirname ( dirname(__FILE__) ) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

header('Content-Type: application/json');

if(isset($_GET['last_id']) || isset($_GET['last_id']))
{
	if ($user->check_group([1,2,5]))
	{
		$grab_notes = $dbl->run("SELECT * FROM `admin_notifications` WHERE `id` < ? ORDER BY `id` DESC LIMIT 50", array($_GET['last_id']))->fetch_all();

		$templating->load('admin_modules/admin_home');
		$last_id = $_GET['last_id'];
		foreach ($grab_notes as $tracking)
		{
			$templating->block('tracking_row', 'admin_modules/admin_home');

			$completed_indicator = '&#10004;';
			if ($tracking['completed'] == 0)
			{
				$completed_indicator = '<span class="badge badge-important">!</span>';
			}

			$templating->set('editor_action', '<li data-id="'.$tracking['id'].'">' . $completed_indicator . ' ' . $tracking['content'] . ' When: ' . $core->human_date($tracking['created_date']) . '</li>');

			$last_id = $tracking['id'];
		}

		// count if there's any left
		$total_left = $dbl->run("SELECT COUNT(*) FROM `admin_notifications` WHERE `id` < ? ORDER BY `id` DESC", array($last_id))->fetchOne();

		echo json_encode(array("result" => 'done', 'text' => $templating->output(), 'last_id' => $last_id, 'total_left' => $total_left));
	}
}
