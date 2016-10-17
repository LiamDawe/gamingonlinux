<?php
if (!isset($_POST['Submit']))
{
	$templating->merge('admin_modules/admin_module_rss');

	$templating->block('main');

	$rss_check = '';
	if (core::config('articles_rss') == 1)
	{
		$rss_check = 'checked';
	}
	$templating->set('rss_check', $rss_check);

	$templating->set('limit', core::config('rss_article_limit'));
}

else if (isset($_POST['Submit']))
{
	// do the check
	$enable = 0;
	if (isset($_POST['enable']))
	{
		$enable = 1;
	}

	// make safe
	$limit = $_POST['limit'];

	if (!is_numeric($limit))
	{
		$core->message('The limit has to be a number!');
	}

	else
	{
		// do the update
		$db->sqlquery("UPDATE `config` SET `data_value` = ? WHERE `data_key` = 'articles_rss'", array($enable));
		$db->sqlquery("UPDATE `config` SET `data_value` = ? WHERE `data_key` = 'rss_article_limit'", array($limit));

		// notify the admin its done
		$core->message('You have updated the articles RSS settings! <a href="admin.php?module=rss">Click here to return.</a>');
	}
}
?>
