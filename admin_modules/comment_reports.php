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
    $core->message('That comment report has been deleted (you marked it as not spam).');
  }

  // count how many there is in total
  $db->sqlquery("SELECT `comment_id` FROM `articles_comments` WHERE `spam` = 1");
  $total_pages = $db->num_rows();

  /* get any spam reported comments in a paginated list here */
  $pagination = $core->pagination_link(9, $total_pages, "admin.php?module=comment_reports", $page);

  $db->sqlquery("SELECT a.*, t.title, u.username, u.user_group, u.`avatar`, u.`avatar_gravatar`, u.`gravatar_email`, u.`avatar_uploaded`, u2.username as reported_by_username FROM `articles_comments` a INNER JOIN `articles` t ON a.article_id = t.article_id LEFT JOIN `users` u ON a.author_id = u.user_id LEFT JOIN `users` u2 on a.spam_report_by = u2.user_id WHERE a.spam = 1 ORDER BY a.`comment_id` ASC LIMIT ?, 9", array($core->start));
  if ($db->num_rows > 0)
  {

    while ($comments = $db->fetch())
    {
      // make date human readable
      $date = $core->format_date($comments['time_posted']);

      if ($comments['author_id'] == 0)
      {
        $username = $comments['guest_username'];
        $quote_username = $comments['guest_username'];
      }
      else
      {
        $username = "<a href=\"/profiles/{$comments['author_id']}\">{$comments['username']}</a>";
        $quote_username = $comments['username'];
      }

      // sort out the avatar
      // either no avatar (gets no avatar from gravatars redirect) or gravatar set
      if (empty($comments['avatar']) || $comments['avatar_gravatar'] == 1)
      {
        $comment_avatar = "https://www.gravatar.com/avatar/" . md5( strtolower( trim( $comments['gravatar_email'] ) ) ) . "?d=https://www.gamingonlinux.com/uploads/avatars/no_avatar.png";
      }

      // either uploaded or linked an avatar
      else
      {
        $comment_avatar = $comments['avatar'];
        if ($comments['avatar_uploaded'] == 1)
        {
          $comment_avatar = "/uploads/avatars/{$comments['avatar']}";
        }
      }

      $editor_bit = '';
      // check if editor or admin
      if ($comments['user_group'] == 1 || $comments['user_group'] == 2)
      {
        $editor_bit = "<span class=\"comments-editor\">Editor</span>";
      }

      $templating->block('article_comments', 'admin_modules/comment_reports');
      $templating->set('user_id', $comments['author_id']);
      $templating->set('username', $username);
      $templating->set('editor', $editor_bit);
      $templating->set('comment_avatar', $comment_avatar);
      $templating->set('date', $date);
      $templating->set('text', bbcode($comments['comment_text']));
      $templating->set('quote_username', $quote_username);
      $templating->set('reported_by', "<a href=\"/profiles/{$comments['spam_report_by']}\">{$comments['reported_by_username']}");
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

if ($_POST['act'] == 'delete_spam_report')
{
  if (!is_numeric($_GET['comment_id']))
  {
    $core->message("Not a correct id!", NULL, 1);
  }

  else
  {
    $db->sqlquery("SELECT `comment_text` FROM `articles_comments` WHERE `comment_id` = ?", array($_GET['comment_id']));
    $get_comment = $db->fetch();

    $db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `action` = ?, `completed_date` = ?, `content` = ? WHERE `comment_id` = ?", array("{$_SESSION['username']} deleted a comment report.", core::$date, $get_comment['comment_text'], $_GET['comment_id']));

    $db->sqlquery("UPDATE `articles_comments` SET `spam` = 0 WHERE `comment_id` = ?", array($_GET['comment_id']));

    header("Location: /admin.php?module=comment_reports&deleted=1");
  }
}
?>
