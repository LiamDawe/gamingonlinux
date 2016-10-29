<?php
session_start();

include('../config.php');

include('../class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('../class_core.php');
$core = new core();

include('../class_user.php');
$user = new user();

if($_POST && isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
{
  if (isset($_POST['comment_id']))
  {
    $pinsid = $_POST['comment_id'];
    $table = 'likes';
    $field = 'comment_id';
  }
  if (isset($_POST['article_id']))
  {
    $pinsid = $_POST['article_id'];
    $table = 'article_likes';
    $field = 'article_id';
  }

  $status=$_POST['sta'];
  $chkpinu = $db->sqlquery("SELECT * FROM `$table` WHERE `$field` = ? AND user_id = ?", array($pinsid, $_SESSION['user_id']));
  $chknum = $db->num_rows();
  if($status=="like")
  {
    if($chknum==0)
    {
      $add = $db->sqlquery("INSERT INTO `$table` SET `$field` = ?, `user_id` = ?", array($pinsid, $_SESSION['user_id']));
      echo 'liked';
      return true;
    }
    echo 2; //Bad Checknum
    return true;
  }
  else if($status=="unlike")
  {
    if($chknum!=0)
    {
      $rem=$db->sqlquery("DELETE FROM `$table` WHERE `$field` = ? AND user_id = ?", array($pinsid, $_SESSION['user_id']));
      echo 'unliked';
      return true;
    }
    echo 2; //Bad Checknum
		return true;
  }
  echo 3; //Bad Status
	return true;
}
echo 5; //Bad Post or Session

return true;
?>
