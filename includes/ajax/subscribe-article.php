<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

include($file_dir . '/includes/class_mysql.php');
$db = new mysql(core::$database['host'], core::$database['username'], core::$database['password'], core::$database['database']);

include($file_dir . '/includes/class_user.php');
$user = new user();

include($file_dir . '/includes/class_article.php');
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
