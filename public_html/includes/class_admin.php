<?php

class admin
{	
	protected $dbl;	
    protected $core;

	function __construct($dbl, $core)
	{	
        $this->dbl = $dbl;
        $this->core = $core;
	}

	// check redis is installed and running
	public function add_editor_chat($text)
	{
		$date = core::$date;
		$this->dbl->run("INSERT INTO `editor_discussion` SET `user_id` = ?, `text` = ?, `date_posted` = ?", array($_SESSION['user_id'], $text, $date));

		$grab_editors = $this->dbl->run("SELECT m.`user_id`, u.`email`, u.`username`, u.`admin_comment_alerts` FROM `user_group_membership` m INNER JOIN `users` u ON m.`user_id` = u.`user_id` WHERE m.`group_id` IN (1,2,5) AND u.`user_id` != ?", [$_SESSION['user_id']])->fetch_all();

		foreach ($grab_editors as $emailer)
		{
			$subject = "A new editor area comment on GamingOnLinux.com";

			// message
			$html_message = "<p>Hello {$emailer['username']}, there's a new message from {$_SESSION['username']} on the GamingOnLinux <a href=\"https://www.gamingonlinux.com/admin.php\">editor panel</a>:</p>
			<hr>
			<p>{$text}</p>";

			$plain_message = "Hello {$emailer['username']}, there's a new message from {$_SESSION['username']} on the GamingOnLinux editor panel: https://www.gamingonlinux.com/admin.php";
			
			// Mail it
			if ($this->core->config('send_emails') == 1)
			{
				$mail = new mailer($this->core);
				$mail->sendMail($emailer['email'], $subject, $html_message, $plain_message);
            }
            
            if ($emailer['admin_comment_alerts'] == 1)
			{
				// check for existing notification
				$check_notes = $this->dbl->run("SELECT `id` FROM `user_notifications` WHERE `type` = 'editor_comment' AND `seen` = 0 AND `owner_id` = ?", array($emailer['user_id']))->fetchOne();
				// they have one, add to the total + set you as the last person
				if ($check_notes)
				{
					// they already have one, refresh it as if it's literally brand new (don't waste the row id)
					$this->dbl->run("UPDATE `user_notifications` SET `last_date` = ?, `seen` = 0, `total` = (total + 1), `notifier_id` = ? WHERE `id` = ?", array(core::$sql_date_now, $_SESSION['user_id'], $check_notes));
				}
				// insert notification as there was none
				else
				{
					$this->dbl->run("INSERT INTO `user_notifications` SET `type` = 'editor_comment', `owner_id` = ?, `notifier_id` = ?, `total` = 1", array($emailer['user_id'], $_SESSION['user_id']));
				}
			}
        }
        $_SESSION['message'] = 'saved';
        $_SESSION['message_extra'] = 'editor comment';
	}
}
?>
