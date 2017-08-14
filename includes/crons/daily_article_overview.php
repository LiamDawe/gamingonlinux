<?php
define("APP_ROOT", dirname( dirname( dirname(__FILE__) ) ) );

require APP_ROOT . "/includes/bootstrap.php";

// gather a list of users who want a daily email of articles
$email_users = $dbl->run("SELECT `username`, `email` FROM `users` WHERE `email_articles` = 'daily'")->fetch_all();

$beginOfDay = strtotime("midnight yesterday");
$endOfDay   = strtotime("midnight today") - 1;

// gather a list of articles in the last day
$article_list = $dbl->run("SELECT `title`, `article_id` FROM `articles` WHERE `date` >= $beginOfDay AND `date` <= $endOfDay AND `active` = 1 AND `draft` = 0")->fetch_all();
if ($article_list)
{
	$email_article_list = '';
	$email_article_list_plain = '';
	foreach ($article_list as $article)
	{
		$link = $article_class->get_link($article['article_id'], $article['title']);
		$email_article_list .= '<p><a href="'.$link.'">'.$article['title'].'</a></p>';
		$email_article_list_plain .= $article['title'] .': ' . $link . "\r\n\r\n";
	}

	foreach ($email_users as $email)
	{
		// subject
		$subject = "Your daily Linux gaming news fix from GamingOnLinux";

		// message
		$html_message = "<p>Hello <strong>{$email['username']}</strong>,</p>
		<p>Here is your daily news digest from GamingOnLinux!</p>" . $email_article_list . '<p><em>Please be aware we generally post less on weekends as we take a little time off!</em></p>';
		
		$plain_message = PHP_EOL."Hello {$email['username']}, here is your daily news digest from GamingOnLinux!" . $email_article_list_plain . PHP_EOL . 'Please be aware we generally post less on weekends as we take a little time off!';

		// Mail it
		if ($core->config('send_emails') == 1)
		{
			$mail = new mailer($core);
			$mail->sendMail($email['email'], $subject, $html_message, $plain_message);
		}	
	}
}
