<?php
$templating->set_previous('title', 'Viewing the history of an article', 1);

$templating->merge('admin_modules/article_history');

if (isset($_GET['id']) && is_numeric($_GET['id']))
{
	$grab_article = $db->sqlquery("SELECT h.`text`, h.`date`, u.`username`, u.`user_id`, a.`title` FROM `article_history` h INNER JOIN `users` u ON h.user_id = u.user_id INNER JOIN `articles` a ON h.article_id = a.article_id WHERE h.id = ?", array($_GET['id']));
	$article = $grab_article->fetch();

	$templating->block('history');
	$templating->set('title', $article['title']);

	$date = $core->format_date($article['date']);
	$templating->set('date', $date);

	$text = bbcode($article['text']);
	$templating->set('text', $text);
}
