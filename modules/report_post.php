<?php
$templating->set_previous('title', "Reporting a forum post", 1);
$templating->set_previous('meta_description', 'Reporting a forum post', 1);

if (isset($_GET['view']))
{
  if  ($_GET['view'] == 'reporttopic')
  {
    // first check it's not already reported
    $db->sqlquery("SELECT `reported`, `topic_title` FROM `forum_topics` WHERE `topic_id` = ?", array($_GET['topic_id']));
    $check_report = $db->fetch();
    // send them back
    if ($check_report['reported'] == 1)
    {
      if (core::config('pretty_urls') == 1)
      {
        header("Location: /forum/topic/{$_GET['topic_id']}/message=reported");
      }
      else
      {
        header("Location: /index.php?module=viewtopic&topic_id=" . $_GET['topic_id'] . "&message=reported");
      }
      die();
    }

    // do the report if it isn't already
    else
    {
      if (!isset($_POST['yes']) && !isset($_POST['no']))
      {
        $core->yes_no('Are you sure you want to report the topic titled "'.$check_report['topic_title'].'"?', '/index.php?module=report_post&topic_id='.$_GET['topic_id'], "reporttopic");
      }
    }
  }


  if ($_GET['view'] == 'reportreply')
  {
    // first check it's not already reported
    $db->sqlquery("SELECT `reported` FROM `forum_replies` WHERE `post_id` = ?", array($_GET['post_id']));
    $check_report = $db->fetch();
    // send them back
    if ($check_report['reported'] == 1)
    {
      if (core::config('pretty_urls') == 1)
      {
        header("Location: /forum/topic/{$_GET['topic_id']}/message=reported");
      }
      else
      {
        header("Location: /index.php?module=viewtopic&topic_id=" . $_GET['topic_id'] . '&message=reported');
      }
      die();
    }

    // do the report if it isn't already
    else
    {
      if (!isset($_POST['yes']) && !isset($_POST['no']))
      {
        $core->yes_no('Are you sure you want to report that post?', '/index.php?module=report_post&post_id='.$_GET['post_id'] . '&topic_id=' . $_GET['topic_id'], "reportreply");
      }
    }
  }
}

if (isset($_POST['act']))
{
  if ($_POST['act'] == 'reporttopic')
  {
    if (isset($_POST['no']))
    {
      if (core::config('pretty_urls') == 1)
      {
        header("Location: /forum/topic/{$_GET['topic_id']}/");
      }
      else
      {
        header("Location: /index.php?module=viewtopic&topic_id=" . $_GET['topic_id']);
      }
      die();
    }
    else if (isset($_POST['yes']))
    {
      // update admin notifications
      $db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 0, `action` = ?, `created` = ?, `topic_id` = ?", array("{$_SESSION['username']} reported a forum topic.", core::$date, $_GET['topic_id']));

      // give it a report
      $db->sqlquery("UPDATE `forum_topics` SET `reported` = 1, `reported_by_id` = ? WHERE `topic_id` = ?", array($_SESSION['user_id'], $_GET['topic_id']));

      if (core::config('pretty_urls') == 1)
      {
        header("Location: /forum/topic/{$_GET['topic_id']}/message=reported");
      }
      else
      {
        header("Location: /index.php?module=viewtopic&topic_id=" . $_GET['topic_id'] . "&message=reported");
      }
    }
  }
  if ($_POST['act'] == 'reportreply')
  {
    if (isset($_POST['no']))
    {
      if (core::config('pretty_urls') == 1)
      {
        header("Location: /forum/topic/{$_GET['topic_id']}/");
      }
      else
      {
        header("Location: /index.php?module=viewtopic&topic_id=" . $_GET['topic_id']);
      }
      die();
    }
    else if (isset($_POST['yes']))
    {
      // update admin notifications
      $db->sqlquery("INSERT INTO `admin_notifications` SET `completed` = 0, `action` = ?, `created` = ?, `reply_id` = ?", array("{$_SESSION['username']} reported a forum topic reply.", core::$date, $_GET['post_id']));

      // give it a report
      $db->sqlquery("UPDATE `forum_replies` SET `reported` = 1, `reported_by_id` = ? WHERE `post_id` = ?", array($_SESSION['user_id'], $_GET['post_id']));

      if (core::config('pretty_urls') == 1)
      {
        header("Location: /forum/topic/{$_GET['topic_id']}/message=reported");
      }
      else
      {
        header("Location: /index.php?module=viewtopic&topic_id=" . $_GET['topic_id'] . "&message=reported");
      }
    }
  }
}
?>
