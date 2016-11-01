<?php
session_start();

include('../config.php');

include('../class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('../class_core.php');
$core = new core();

include('../class_user.php');
$user = new user();

include('../class_article.php');
$article_class = new article_class();

if($_POST && isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
{
  if (isset($_POST['type']))
  {
    if ($_POST['type'] == 'subscribe')
    {
      $article_class->subscribe($_POST['article-id']);
      echo json_encode(array("result" => "subscribed"));
      return;
    }

    if ($_POST['type'] == 'unsubscribe')
    {
      $article_class->unsubscribe($_POST['article-id']);
      echo json_encode(array("result" => "unsubscribed"));
      return;
    }
  }
}
?>
