<?php
define("APP_ROOT", dirname( dirname(__FILE__) ) );

require APP_ROOT . "/includes/bootstrap.php";

$get_articles = $dbl->run("SELECT `article_id` FROM `articles` ORDER BY `article_id` DESC")->fetch_all();
foreach ($get_articles as $article)
{
	$total_alikes = $dbl->run("SELECT COUNT(article_id) as `total` FROM `article_likes` WHERE `article_id` = ?", array($article['article_id']))->fetchOne();

	$dbl->run("UPDATE `articles` SET `total_likes` = ? WHERE `article_id` = ?", array($total_alikes, $article['article_id']));
	
	echo "Article " . $article['article_id'] . " updated!\n";
}

echo 'Done';
?>