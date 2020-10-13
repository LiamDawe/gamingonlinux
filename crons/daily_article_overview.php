<?php
define("APP_ROOT", dirname( dirname(__FILE__) ) . '/public_html');

require APP_ROOT . "/includes/bootstrap.php";

// gather a list of users who want a daily email of articles
$email_users = $dbl->run("SELECT `username`, `email`, `mailing_list_key`,`user_id` FROM `users` WHERE `email_articles` = 'daily'")->fetch_all();
$guest_subs = $dbl->run("SELECT `email`,`id`,`unsub_key` FROM `mailing_list` WHERE `activated` = 1")->fetch_all();

$beginOfDay = strtotime("midnight yesterday");
$endOfDay   = strtotime("midnight today") - 1;

// gather a list of articles in the last day
$article_list = $dbl->run("SELECT `title`, `article_id`, `date`, `slug` FROM `articles` WHERE `date` >= $beginOfDay AND `date` <= $endOfDay AND `active` = 1 AND `draft` = 0")->fetch_all();
if ($article_list)
{
	$email_article_list = '';
	$email_article_list_plain = '';
	foreach ($article_list as $article)
	{
		$link = $article_class->article_link(array('date' => $article['date'], 'slug' => $article['slug']));
		$email_article_list .= '<p><a href="'.$link.'">'.$article['title'].'</a></p>';
		$email_article_list_plain .= $article['title'] .': ' . $link . "\r\n\r\n";
	}

	$html_addition = '';
	$plain_addition = '';
	$day = date('D', strtotime('-1 day'));
	if ($day == 'Sat' || $day == 'Sun')
	{
		$html_addition = '<p><em>Please be aware we generally post less on weekends as we take a little time off!</em></p>';
		$plain_addition = 'Please be aware we generally post less on weekends as we take a little time off!' . PHP_EOL . PHP_EOL;
	}

	foreach ($email_users as $email)
	{
		$unsub_link = $core->config('website_url') . 'index.php?module=mailing_list&type=remove_user&key=' . $email['mailing_list_key'] . '&id=' . $email['user_id'];
		
		// subject
		$subject = "Your daily Linux gaming news fix from GamingOnLinux";

		// message
		$html_message = "<p>Hello <strong>{$email['username']}</strong>,</p>
		<p>Here is your daily news digest from GamingOnLinux!</p>" . $email_article_list . $html_addition . '<p>You can unsubscribe by <a href="'.$unsub_link.'">clicking here</a>.</p>';
		
		$plain_message = PHP_EOL."Hello {$email['username']}, here is your daily news digest from GamingOnLinux!" . $email_article_list_plain . PHP_EOL . $plain_addition . 'You can unsubscribe any time go going here: ' . $unsub_link;

		// Mail it
		if ($core->config('send_emails') == 1)
		{
			$mail = new mailer($core);
			$mail->sendMail($email['email'], $subject, $html_message, $plain_message);
		}	
	}
	
	foreach ($guest_subs as $email)
	{
		// subject
		$subject = "Your daily Linux gaming news fix from GamingOnLinux";
		
		$unsub_link = $core->config('website_url') . 'index.php?module=mailing_list&type=remove_guest&key=' . $email['unsub_key'] . '&id=' . $email['id'];

		// message
		$html_message = "<p>Hello,</p>
		<p>Here is your daily news digest from GamingOnLinux!</p>" . $email_article_list . '<p><em>Please be aware we generally post less on weekends as we take a little time off!</em></p><p>You can unsubscribe any time by just <a href="'.$unsub_link.'">clicking here</a>.</p>';
		
		$plain_message = PHP_EOL."Hello, here is your daily news digest from GamingOnLinux!" . $email_article_list_plain . PHP_EOL . 'Please be aware we generally post less on weekends as we take a little time off! You can unsubscribe any time go going here: ' . $unsub_link;

		// Mail it
		if ($core->config('send_emails') == 1)
		{
			$mail = new mailer($core);
			$mail->sendMail($email['email'], $subject, $html_message, $plain_message);
		}	
	}
}
