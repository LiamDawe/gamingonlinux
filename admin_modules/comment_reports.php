<?php
$templating->merge('admin_modules/comment_reports');

$templating->set_previous('title', 'Article comments' . $templating->get('title', 1)  , 1);
if (!isset($_GET['ip_id']))
{
  // paging for pagination
  if (!isset($_GET['page']))
  {
    $page = 1;
  }

  else if (is_numeric($_GET['page']))
  {
    $page = $_GET['page'];
  }

  $templating->block('comments_top', 'admin_modules/comment_reports');

  // if we have just deleted one tell us
  if (isset($_GET['deleted']) && $_GET['deleted'] == 1)
  {
    $core->message('That comment report has been deleted, and the comment left in place.');
  }

  // count how many there is in total
  $db->sqlquery("SELECT `comment_id` FROM `articles_comments` WHERE `spam` = 1");
  $total_pages = $db->num_rows();

  /* get any spam reported comments in a paginated list here */
  $pagination = $core->pagination_link(9, $total_pages, "admin.php?module=comment_reports", $page);

  $db->sqlquery("SELECT a.*, t.title, u.username, u.user_group, u.`avatar`, u.`avatar_gravatar`, u.`gravatar_email`, u.`avatar_uploaded`, u2.username as reported_by_username FROM `articles_comments` a INNER JOIN `articles` t ON a.article_id = t.article_id LEFT JOIN `users` u ON a.author_id = u.user_id LEFT JOIN `users` u2 on a.spam_report_by = u2.user_id WHERE a.spam = 1 ORDER BY a.`comment_id` ASC LIMIT ?, 9", array($core->start));
  if ($db->num_rows() > 0)
  {
    while ($comments = $db->fetch())
    {
      // make date human readable
      $date = $core->format_date($comments['time_posted']);

      if ($comments['author_id'] == 0)
      {
        $username = $comments['guest_username'];
      }
      else
      {
        $username = "<a href=\"/profiles/{$comments['author_id']}\">{$comments['username']}</a>";
      }

      // sort out the avatar
      $comment_avatar = $user->sort_avatar($comments);

      $editor_bit = '';
      // check if editor or admin
      if ($comments['user_group'] == 1 || $comments['user_group'] == 2)
      {
        $editor_bit = "<span class=\"comments-editor\">Editor</span>";
      }

      $donator_badge = '';
      if (($comments['secondary_user_group'] == 6 || $comments['secondary_user_group'] == 7) && $comments['user_group'] != 1 && $comments['user_group'] != 2)
      {
        $donator_badge = ' <li><span class="badge supporter">GOL Supporter</span></li>';
      }

      $developer_badge = '';
      if ($comments['game_developer'] == 1)
      {
        $developer_badge = ' <li><span class="badge yellow">Game Dev</span></li>';
      }

      $templating->block('article_comments', 'admin_modules/comment_reports');
      $templating->set('user_id', $comments['author_id']);
      $templating->set('username', $username);
      $templating->set('editor', $editor_bit);
      $templating->set('donator_badge', $donator_badge);
      $templating->set('game_developer', $developer_badge);
      $templating->set('comment_avatar', $comment_avatar);
      $templating->set('date', $date);
      $templating->set('text', bbcode($comments['comment_text']));
      $templating->set('reported_by', "<a href=\"/profiles/{$comments['spam_report_by']}\">{$comments['reported_by_username']}</a>");
      $templating->set('comment_id', $comments['comment_id']);
      $templating->set('article_title', $comments['title']);
      $templating->set('article_link', $core->nice_title($comments['title']) . '.' . $comments['article_id']);
    }

    $templating->block('comment_reports_bottom', 'admin_modules/comment_reports');
    $templating->set('pagination', $pagination);
  }
  else
  {
    $core->message('Nothing to display! There are no reported comments.');
  }
}

if (isset($_POST['act']) && $_POST['act'] == 'delete_spam_report')
{
  if (!is_numeric($_GET['comment_id']))
  {
    $core->message("Not a correct id!", NULL, 1);
  }

  else
  {
    $db->sqlquery("SELECT `comment_text` FROM `articles_comments` WHERE `comment_id` = ?", array($_GET['comment_id']));
    $get_comment = $db->fetch();

    $db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `type` = 'reported_comment' AND `data` = ?", array(core::$date, $_GET['comment_id']));
    $db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `created_date` = ?, `completed_date` = ?, `type` = ?, `data` = ?", array($_SESSION['user_id'], core::$date, core::$date, 'deleted_comment_report', $_GET['comment_id']));

    $db->sqlquery("UPDATE `articles_comments` SET `spam` = 0 WHERE `comment_id` = ?", array($_GET['comment_id']));

    header("Location: /admin.php?module=comment_reports&deleted=1");
  }
}
?>
