<?php
$templating->merge('admin_modules/corrections');

$templating->set_previous('title', 'Article correction suggestions' . $templating->get('title', 1)  , 1);

// paging for pagination
if (!isset($_GET['page']))
{
  $page = 1;
}

else if (is_numeric($_GET['page']))
{
  $page = $_GET['page'];
}

$templating->block('top', 'admin_modules/corrections');

// count how many there is in total
$db->sqlquery("SELECT `row_id` FROM `article_corrections`");
$total_pages = $db->num_rows();

/* get any spam reported comments in a paginated list here */
$pagination = $core->pagination_link(9, $total_pages, "admin.php?module=corrections", $page);

  $db->sqlquery("SELECT c.row_id, c.`article_id`, c.`user_id`, c.`correction_comment`, u.username, a.title FROM `article_corrections` c LEFT JOIN ".$core->db_tables['users']." u ON u.user_id = c.user_id LEFT JOIN `articles` a ON a.article_id = c.article_id ORDER BY c.`row_id` ASC LIMIT ?, 9", array($core->start));
  if ($db->num_rows() > 0)
  {
    while ($corrections = $db->fetch())
    {
      if (empty($corrections['username']))
      {
        $username = 'Guest';
      }
      else
      {
        $username = "<a href=\"/profiles/{$corrections['user_id']}\">{$corrections['username']}</a>";
      }

      $nice_title = core::nice_title($corrections['title']);

      if ($core->config('pretty_urls') == 1)
      {
        $article_link = '/articles/' . $nice_title . '.' . $corrections['article_id'];
      }
      else 
      {
        $article_link = '/index.php?module=articles_full&aid=' . $corrections['article_id'] . '&title=' . $nice_title;
      }

      $templating->block('row', 'admin_modules/corrections');
      $templating->set('username', $username);
      $templating->set('title', $corrections['title']);
      $templating->set('article_link', $article_link);
      $templating->set('correction', $corrections['correction_comment']);
      $templating->set('correction_id', $corrections['row_id']);
    }

    $templating->block('bottom', 'admin_modules/corrections');
    $templating->set('pagination', $pagination);
  }
else
{
  $core->message('Nothing to display! There are no suggestions.');
}


if (isset($_POST['act']) && $_POST['act'] == 'delete')
{
	if (!isset($_POST['correction_id']) || !is_numeric($_POST['correction_id']))
	{
		$_SESSION['message'] = 'no_id';
		$_SESSION['message_extra'] = 'correction';
		header("Location: /admin.php?module=corrections");
		die();
	}

	else
	{
		$db->sqlquery("UPDATE `admin_notifications` SET `completed` = 1, `completed_date` = ? WHERE `type` = 'article_correction' AND `data` = ?", array(core::$date, $_POST['correction_id']));
		$db->sqlquery("INSERT INTO `admin_notifications` SET `user_id` = ?, `completed` = 1, `created_date` = ?, `completed_date` = ?, `type` = ?, `data` = ?", array($_SESSION['user_id'], core::$date, core::$date, 'deleted_correction', $_POST['correction_id']));

		$db->sqlquery("DELETE FROM `article_corrections` WHERE `row_id` = ?", array($_POST['correction_id']));

		$_SESSION['message'] = 'deleted';
		$_SESSION['message_extra'] = 'correction';
		header("Location: /admin.php?module=corrections&message=deleted");
	}
}
?>
