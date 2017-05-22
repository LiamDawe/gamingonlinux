<?php
$templating->set_previous('title', 'Your bookmarked content', 1);
$templating->set_previous('meta_description', 'Your personal bookmarked content', 1);

$templating->load('usercp_modules/bookmarks');

$templating->block('top');

$db->sqlquery("SELECT
  b.`type`,
  b.`data_id`,
  b.`parent_id`,
  a.`title` as article_title,
  a2.`title` as `comment_title`,
  a.`slug`,
  t.topic_title,
  t2.topic_title as reply_title
  FROM `user_bookmarks` b
  LEFT JOIN `articles` a ON b.`data_id` = a.`article_id` AND b.type ='article'
  LEFT JOIN `articles_comments` c ON c.comment_id = b.data_id AND b.type ='comment'
  LEFT JOIN `articles` a2 ON c.article_id = a2.article_id
  LEFT JOIN `forum_topics` t ON t.topic_id = b.data_id AND b.type = 'forum_topic'
  LEFT JOIN `forum_topics` t2 ON t2.topic_id = b.data_id AND b.type = 'forum_reply'
  WHERE b.`user_id` = ?", array($_SESSION['user_id']));
$total = $db->num_rows();
if ($total > 0)
{
  while ($data = $db->fetch())
  {
    $templating->block('row');

    $link = '';
    $title = '';
    $type = '';
    $data_type = '';

    // article bookmarks
    if ($data['type'] == 'article')
    {
      $data_type = 'article';
      $type = 'Article: ';
      $title = $data['article_title'];
      if ($core->config('pretty_urls') == 1)
      {
        $link = '/articles/' . $data['slug'] . '.' . $data['data_id'];
      }
      else
      {
        $link = '/index.php?module=articles_full&aid=' . $data['data_id'] . '&title=' . $data['slug'];
      }
    }

    // comment bookmarks
    if ($data['type'] == 'comment')
    {
      $data_type = 'comment';
      $type = 'Comment: ';
      $title = $data['comment_title'];
      $link = '/index.php?module=articles_full&aid=' . $data['parent_id'] . '&comment_id=' . $data['data_id'];
    }

    // forum topic bookmarks
    if ($data['type'] == 'forum_topic')
    {
      $data_type = 'forum_topic';
      $type = 'Forum Topic: ';
      $title = $data['topic_title'];
      if ($core->config('pretty_urls') == 1)
      {
        $link = '/forum/topic/' . $data['data_id'];
      }
      else
      {
        $link = '/index.php?module=viewtopic&topic_id=' . $data['data_id'];
      }

    }

    // forum reply bookmarks
    if ($data['type'] == 'forum_reply')
    {
      $data_type = 'forum_reply';
      $type = 'Forum Post: ';
      $title = $data['reply_title'];
      $link = '/index.php?module=viewtopic&topic_id=' . $data['parent_id'] . '&post_id=' . $data['data_id'];
    }

    $templating->set('type', $type);
    $templating->set('title', $title);
    $templating->set('link', $link);
    $templating->set('data_type', $data_type);
    $templating->set('id', $data['data_id']);
  }
}
else
{
  $core->message('You don\'t have anything bookmarked!');
}
?>
