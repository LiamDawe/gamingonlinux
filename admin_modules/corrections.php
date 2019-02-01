<?php
if(!defined('golapp')) 
{
	die('Direct access not permitted');
}

$templating->load('admin_modules/corrections');

$templating->set_previous('title', 'Article correction suggestions' . $templating->get('title', 1)  , 1);

// paging for pagination
if (!isset($_GET['page']))
{
  $page = 1;
}

else if (is_numeric($_GET['page']))
{
  $page = $_GET['page'];
}

$templating->block('top', 'admin_modules/corrections');

// count how many there is in total
$total_pages = $dbl->run("SELECT COUNT(`row_id`) FROM `article_corrections`")->fetchOne();

/* get any spam reported comments in a paginated list here */
$pagination = $core->pagination_link(9, $total_pages, "admin.php?module=corrections", $page);

$res = $dbl->run("SELECT c.row_id, c.`article_id`, c.`user_id`, c.`correction_comment`, u.username, a.title FROM `article_corrections` c LEFT JOIN `users` u ON u.user_id = c.user_id LEFT JOIN `articles` a ON a.article_id = c.article_id ORDER BY c.`row_id` ASC LIMIT ?, 9", array($core->start))->fetch_all();
if ($res)
{
	foreach ($res as $corrections)
    {
		if (empty($corrections['username']))
		{
			$username = 'Guest';
		}
		else
		{
			$username = "<a href=\"/profiles/{$corrections['user_id']}\">{$corrections['username']}</a>";
		}

		$nice_title = core::nice_title($corrections['title']);

		$article_link = '/articles/' . $nice_title . '.' . $corrections['article_id'];

		$templating->block('row', 'admin_modules/corrections');
		$templating->set('username', $username);
		$templating->set('title', $corrections['title']);
    	$templating->set('article_link', $article_link);
		$templating->set('correction', $bbcode->parse_bbcode($corrections['correction_comment']));
		$templating->set('correction_text_plain', $corrections['correction_comment']);
		$templating->set('correction_id', $corrections['row_id']);
		$templating->set('reporter_id', $corrections['user_id']);
	}

    $templating->block('bottom', 'admin_modules/corrections');
    $templating->set('pagination', $pagination);
}
else
{
	$core->message('Nothing to display! There are no suggestions.');
}

if (isset($_POST['act']) && $_POST['act'] == 'delete')
{
	if (!isset($_POST['correction_id']) || !is_numeric($_POST['correction_id']))
	{
		$_SESSION['message'] = 'no_id';
		$_SESSION['message_extra'] = 'correction';
		header("Location: /admin.php?module=corrections");
		die();
	}

	// check correction report still exists
	$check = $dbl->run("SELECT `row_id` FROM `article_corrections` WHERE `row_id` = ?", array($_POST['correction_id']))->fetchOne();
	if (!$check)
	{
		$_SESSION['message'] = 'none_found';
		$_SESSION['message_extra'] = 'correction reports matching that ID (someone already dealt with it?)';
		header("Location: /admin.php?module=corrections");
		die();		
	}

	if (isset($_GET['message']))
	{
		$text = trim($_POST['text']);
		$text = core::make_safe($text);
		$reporter_id = $_POST['reporter_id'];

		$title = 'Your correction on: ' . $_POST['correction_title'];
	
		// check empty
		$check_empty = core::mempty(compact('text'));
		if ($check_empty !== true)
		{
			$_SESSION['message'] = 'empty';
			$_SESSION['message_extra'] = $check_empty;
			header("Location: /admin.php?module=corrections");
			die();
		}
		
		$text = 'Your correction report was: [quote]'.$_POST['correction_text'].'[/quote]' . $text;

		// make the new message
		$dbl->run("INSERT INTO `user_conversations_info` SET `title` = ?, `creation_date` = ?, `author_id` = ?, `owner_id` = ?, `last_reply_date` = ?, `replies` = 0, `last_reply_id` = ?", array($title, core::$date, $_SESSION['user_id'], $_SESSION['user_id'], core::$date, $_SESSION['user_id']));

		$conversation_id = $dbl->new_id();

		$dbl->run("INSERT INTO `user_conversations_messages` SET `conversation_id` = ?, `author_id` = ?, `creation_date` = ?, `message` = ?, `position` = 0", array($conversation_id, $_SESSION['user_id'], core::$date, $text));

		$dbl->run("INSERT INTO `user_conversations_participants` SET `conversation_id` = ?, `participant_id` = ?, unread = 0", array($conversation_id, $_SESSION['user_id']));

		// make the duplicate message for the reporter
		$dbl->run("INSERT INTO `user_conversations_info` SET `conversation_id` = ?, `title` = ?, `creation_date` = ?, `author_id` = ?, `owner_id` = ?, `last_reply_date` = ?, `replies` = 0, `last_reply_id` = ?", array($conversation_id, $title, core::$date, $_SESSION['user_id'], $reporter_id, core::$date, $_SESSION['user_id']));

		$dbl->run("INSERT INTO `user_conversations_participants` SET `conversation_id` = ?, `participant_id` = ?, unread = 1", array($conversation_id, $reporter_id));

		// email user to tell them they have a new convo, if allowed
		$email_data = $dbl->run("SELECT `username`, `email`, `email_on_pm` FROM `users` WHERE `user_id` = ?", array($reporter_id))->fetch();

		if ($email_data['email_on_pm'] == 1)
		{
			// subject
			$subject = 'New conversation started on GamingOnLinux';

			$email_text = $bbcode->email_bbcode($text);

			$message = '';

			// message
			$html_message = "<p>Hello <strong>{$email_data['username']}</strong>,</p>
			<p><strong>{$_SESSION['username']}</strong> has started a new conversation with you on <a href=\"".$core->config('website_url')."private-messages/\" target=\"_blank\">GamingOnLinux</a>, titled \"<a href=\"".$core->config('website_url')."private-messages/{$conversation_id}\" target=\"_blank\"><strong>{$title}</strong></a>\".</p>
			<br style=\"clear:both\">
			<div>
			<hr>
			{$email_text}";

			$plain_message = PHP_EOL."Hello {$email_data['username']}, {$_SESSION['username']} has started a new conversation with you on ".$core->config('website_url')."private-messages/, titled \"{$title}\",\r\n{$_POST['text']}";

			// Mail it
			if ($core->config('send_emails') == 1)
			{
				$mail = new mailer($core);
				$mail->sendMail($email_data['email'], $subject, $html_message, $plain_message);
			}
		}
	}

	// update existing notification
	$core->update_admin_note(array("type" => 'article_correction', 'data' => $_POST['correction_id']));

	// note who deleted it
	$core->new_admin_note(array('completed' => 1, 'content' => ' deleted correction report.'));

	$dbl->run("DELETE FROM `article_corrections` WHERE `row_id` = ?", array($_POST['correction_id']));

	$_SESSION['message'] = 'deleted';
	$_SESSION['message_extra'] = 'correction';
	header("Location: /admin.php?module=corrections");
}
?>
