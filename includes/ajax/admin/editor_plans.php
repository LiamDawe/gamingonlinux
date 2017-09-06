<?php
session_start();

define("APP_ROOT", dirname ( dirname ( dirname ( dirname(__FILE__) ) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

$user->check_session();

if(isset($_POST))
{
	if ($_POST['type'] == 'add_plan')
	{
		$text = trim($_POST['text']);
		$text = core::make_safe($text);

		if (empty($text))
		{
			echo json_encode(array("message" => 'Empty comment'));
			return;
		}

		$date = core::$sql_date_now;
		$dbl->run("INSERT INTO `editor_plans` SET `user_id` = ?, `text` = ?, `date_posted` = ?", array($_SESSION['user_id'], $text, $date));

		$grab_admins = $dbl->run("SELECT m.`user_id`, u.`email`, u.`username`, u.`admin_comment_alerts` FROM `user_group_membership` m INNER JOIN `users` u ON m.`user_id` = u.`user_id` WHERE m.`group_id` IN (1,2,5) AND u.`user_id` != ?", [$_SESSION['user_id']])->fetch_all();
		foreach ($grab_admins as $emailer)
		{
			if ($emailer['admin_comment_alerts'] == 1)
			{			
				// check for existing notification
				$check_notes = $dbl->run("SELECT `id` FROM `user_notifications` WHERE `type` = 'editor_plan' AND `seen` = 0 AND `owner_id` = ?", array($emailer['user_id']))->fetchOne();
				// they have one, add to the total + set you as the last person
				if ($check_notes)
				{
					$dbl->run("UPDATE `user_notifications` SET `date` = ?, `total` = (total + 1), `notifier_id` = ? WHERE `id` = ?", array(core::$date, $_SESSION['user_id'], $check_notes));
				}
				// insert notification as there was none
				else
				{
					$dbl->run("INSERT INTO `user_notifications` SET `date` = ?, `type` = 'editor_plan', `owner_id` = ?, `notifier_id` = ?, `total` = 1", array(core::$date, $emailer['user_id'], $_SESSION['user_id']));
				}
			}
		}
		
		$templating->load('admin_modules/admin_home');

		// editor plans
		$grab_comments = $dbl->run("SELECT p.`id`, p.`text`, p.`date_posted`, u.`user_id`, u.`username` FROM `editor_plans` p INNER JOIN `users` u ON p.`user_id` = u.`user_id` ORDER BY p.`id` DESC")->fetch_all();
		$templating->block('plans', 'admin_modules/admin_home');
		$plans_list = [];
		if ($grab_comments)
		{
			foreach ($grab_comments as $comments)
			{
				$comment_text = $bbcode->parse_bbcode($comments['text'], 0);
				$date = $core->human_date(strtotime($comments['date_posted']));
				
				$plans = $templating->block_store('plan_row', 'admin_modules/admin_home');
				
				$delete_icon = '';
				if (($_SESSION['user_id'] == $comments['user_id']) || $user->check_group(1))
				{
					$delete_icon = '<span class="fright"><a href="#" class="delete_editor_plan" title="Delete Plan" data-note-id="'.$comments['id'].'" data-owner-id="'.$comments['user_id'].'">&#10799;</a></span>';
				}
				
				$plans = $templating->store_replace($plans, array('id' => $comments['id'], 'user_id' => $comments['user_id'], 'username' => $comments['username'], 'date' => $date, 'text' => $comments['text'], 'delete_icon' => $delete_icon));
				
				$plans_list[] = $plans;
			}
			$templating->set('editor_plans', implode('', $plans_list));
		}
		else
		{
			$templating->set('editor_plans', '<li>None</li>');
		}
		
		echo json_encode(array("result" => 'done', 'text' => $templating->output(), 'type' => 'admin'));
	}
	
	if ($_POST['type'] == 'remove')
	{
		if ($_POST['owner_id'] == $_SESSION['user_id'] || $user->check_group(1))
		{
			$grab_editors = $dbl->run("SELECT m.`user_id`, u.`email`, u.`username`, u.`admin_comment_alerts` FROM `user_group_membership` m INNER JOIN `users` u ON m.`user_id` = u.`user_id` WHERE m.`group_id` IN (1,2,5) AND u.`user_id` != ?", [$_SESSION['user_id']])->fetch_all();
			foreach ($grab_editors as $emailer)
			{
				if ($emailer['admin_comment_alerts'] == 1)
				{			
					// check for existing notification
					$check_notes = $dbl->run("SELECT `id`, `total` FROM `user_notifications` WHERE `type` = 'editor_plan' AND `seen` = 0 AND `owner_id` = ? AND `notifier_id` = ?", array($emailer['user_id'], $_POST['owner_id']))->fetch();
					if ($check_notes)
					{
						// the have only one, just remove it
						if ($check_notes['total'] == 1)
						{
							$dbl->run("DELETE FROM `user_notifications` WHERE `owner_id` = ? AND `id` = ?", array($emailer['user_id'], $check_notes['id']));
						}
						// more than one has put up a plan, adjust their existing notification
						else if ($check_notes['total'] > 1)
						{
							// find the last available plan now (second to last row)
							$last_plan = $dbl->run("SELECT `user_id`, `date_posted` FROM `editor_plans` ORDER BY `date_posted` DESC LIMIT 1 OFFSET 1")->fetch();
						
							$older_date = strtotime($last_plan['date_posted']);
							
							$dbl->run("UPDATE `user_notifications` SET `date` = ?, `total` = (total - 1), `notifier_id` = ? WHERE `id` = ?", array($older_date, $last_plan['user_id'], $check_notes['id']));
						}
					}
				}
			}
			
			$dbl->run("DELETE FROM `editor_plans` WHERE `id` = ?", array($_POST['note_id']));
			
			echo json_encode(array("result" => 'done'));

		}
		else
		{
			echo json_encode(array("result" => 'error', 'message' => 'You do not have permission to remove their article plan!'));
		}
	}
}
