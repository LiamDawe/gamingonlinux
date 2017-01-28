<?php
$file_dir = dirname( dirname( dirname(__FILE__) ) );

include($file_dir . '/includes/class_core.php');
$core = new core($file_dir);

include($file_dir . '/includes/class_mysql.php');
$db = new mysql(core::$database['host'], core::$database['username'], core::$database['password'], core::$database['database']);

include('../class_user.php');
$user = new user();

if($_POST && isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0)
{
  if ($_POST['type'] == 'comment')
  {
    $pinsid = $_POST['comment_id'];
    $table = 'likes';
    $field = 'comment_id';

  }
  if ($_POST['type'] == 'article')
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
    if ($_POST['type'] == 'comment')
    {
      // see if there's a notification already for it
      $db->sqlquery("SELECT `owner_id`, `id`, `total` FROM `user_notifications` WHERE `owner_id` = ? AND `is_like` = 1 AND `comment_id` = ?", array($_POST['author_id'], $_POST['comment_id']));
      if ($db->num_rows() == 1)
      {
        $get_note = $db->fetch();
        $db->sqlquery("UPDATE `user_notifications` SET `date` = ?, `notifier_id` = ?, `seen` = 0, `total` = (total + 1) WHERE `id` = ?", array(core::$date, $_SESSION['user_id'], $get_note['id']));
      }
      else
      {
        $db->sqlquery("INSERT INTO `user_notifications` SET `date` = ?, `owner_id` = ?, `notifier_id` = ?, `article_id` = ?, `comment_id` = ?, `is_like` = 1, `total` = 1", array(core::$date, $_POST['author_id'], $_SESSION['user_id'], $_POST['article_id'], $_POST['comment_id']));
      }
    }
    if($chknum==0)
    {
      $add = $db->sqlquery("INSERT INTO `$table` SET `$field` = ?, `user_id` = ?, `date` = ?", array($pinsid, $_SESSION['user_id'], core::$date));
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
      if ($_POST['type'] == 'comment')
      {
        // see if there's any left already for it
        $db->sqlquery("SELECT `owner_id`, `id`, `total`, `seen`, `seen_date` FROM `user_notifications` WHERE `owner_id` = ? AND `is_like` = 1 AND `comment_id` = ?", array($_POST['author_id'], $_POST['comment_id']));
        $current_likes = $db->fetch();
        if ($current_likes['total'] >= 2)
        {
          // find the last available like now (second to last row)
          $db->sqlquery("SELECT `user_id`, `comment_id`, `date` FROM `likes` where `comment_id` = ? ORDER BY `date` DESC LIMIT 1 OFFSET 1", array($_POST['comment_id']));
          $last_like = $db->fetch();
          $seen = '';

          // if the last time they saw this like notification was before the date of the new last like, they haven't seen it
          if ($last_like['date'] > $current_likes['seen_date'])
          {
            $seen = 0;
          }
          else
          {
            $seen = 1;
          }

          $db->sqlquery("UPDATE `user_notifications` SET `date` = ?, `notifier_id` = ?, `seen` = ?, `total` = (total - 1) WHERE `id` = ?", array($last_like['date'], $last_like['user_id'], $seen, $current_likes['id']));
        }
        // it's the only one, so just delete the notification to completely remove it
        else if ($current_likes['total'] == 1)
        {
          $db->sqlquery("DELETE FROM `user_notifications` WHERE `id` = ?", array($current_likes['id']));
        }
      }
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
