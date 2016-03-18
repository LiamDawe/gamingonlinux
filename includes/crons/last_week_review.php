<?php
include('/home/gamingonlinux/public_html/includes/config.php');

include('/home/gamingonlinux/public_html/includes/class_mysql.php');
$db = new mysql($database_host, $database_username, $database_password, $database_db);

include('/home/gamingonlinux/public_html/includes/class_core.php');
$core = new core();

include('/home/gamingonlinux/public_html/includes/bbcode.php');

// setup the templating, if not logged in default theme, if logged in use selected theme
include('/home/gamingonlinux/public_html/includes/class_template.php');

$templating = new template();

$db->sqlquery("SELECT a.*
FROM (SELECT a.*, c.category_id
    FROM
        articles a
        LEFT JOIN
            `article_category_reference` c ON a.article_id = c.article_id
        WHERE
            a.`date` BETWEEN UNIX_TIMESTAMP(DATE(NOW() - INTERVAL 7 DAY)) AND UNIX_TIMESTAMP(CURDATE()) AND a.active = 1 AND c.category_id NOT IN (63) group by `a`.`article_id`
        ORDER BY
            a.views ASC
     ) a
ORDER BY views ASC");

$article_count = $db->num_rows();

$title = "A Roundup Of The Last Week For Linux Gaming";

$tagline = "Here is a look back at the last week on GamingOnLinux, an easy way to for you to keep up to date on what has happened in the past week for Linux Gaming!";

$text = "Here is a look back at the last week on GamingOnLinux, an easy way to for you to keep up to date on what has happened in the past week for Linux Gaming! Sorted from lowest to highest to make sure you don't miss the smaller news stories.\r\n
Think of it like reading a handy Linux gaming magazine of the last weeks news all in one lovely place! We don't include stuff from today as that only requires you to look down a little on the home page.\r\n
If you wish to keep track of these overview posts you can with our [url=http://www.gamingonlinux.com/article_rss.php?section=overviews]Overview RSS[/url].\r\n";

$counter = 0;

while ($articles = $db->fetch())
{
	$counter++;
	$nice_link =  $core->nice_title($articles['title']) . '.' . $articles['article_id'];
	$views = number_format($articles['views'], 0, '.', ',');

	if ($counter <= round($article_count/2))
	{
		$tagline_image = '';
		if (!empty($articles['tagline_image']))
		{
			$tagline_image = "http://www.gamingonlinux.com/uploads/articles/tagline_images/thumbnails/{$articles['tagline_image']}";
		}
		else if (!empty($articles['article_top_image_filename']))
		{
			$tagline_image = "http://www.gamingonlinux.com/uploads/articles/topimages/{$articles['article_top_image_filename']}";
		}
		$text .= "\r\n[url=http://www.gamingonlinux.com/articles/$nice_link][img]{$tagline_image}[/img][/url]";
		$text .= "\r\n[url=http://www.gamingonlinux.com/articles/$nice_link]{$articles['title']}[/url] - Views: {$views}";
		$text .= "\r\n[i]{$articles['tagline']}[/i]\r\n\r\n";

		if ($counter == round($article_count/2, 0, PHP_ROUND_HALF_DOWN))
		{
			$text .= "Check out page 2 for even more hot articles!\r\n\r\n";
		}
	}

	else if ($counter >= round($article_count/2+1, 0, PHP_ROUND_HALF_DOWN))
	{
		$tagline_image = '';
		if (!empty($articles['tagline_image']))
		{
			$tagline_image = "http://www.gamingonlinux.com/uploads/articles/tagline_images/thumbnails/{$articles['tagline_image']}";
		}
		else if (!empty($articles['article_top_image_filename']))
		{
			$tagline_image = "http://www.gamingonlinux.com/uploads/articles/topimages/{$articles['article_top_image_filename']}";
		}
		$page2 .= "\r\n[url=http://www.gamingonlinux.com/articles/$nice_link][img]{$tagline_image}[/img][/url]";
		$page2 .= "\r\n[url=http://www.gamingonlinux.com/articles/$nice_link]{$articles['title']}[/url] - Views: {$views}";
		$page2 .= "\r\n[i]{$articles['tagline']}[/i]\r\n\r\n";
	}
}

// Latest sales box on the main page
$sales = '[ul]';
$sale_counter = 0;
$db->sqlquery("SELECT s.`id`,s.`info`, s.`website`, s.`provider_id`, p.`name` FROM `game_sales` s LEFT JOIN `game_sales_provider` p ON s.provider_id = p.provider_id WHERE s.`accepted` = 1 ORDER BY s.`id` DESC LIMIT 10");
while ($home_list = $db->fetch())
{
	$sale_counter++;
	$sale_name = $home_list['info'];

	// check to see if we need to put in the category name or not
	$provider = '';
	if ($home_list['provider_id'] != 0)
	{
		$provider = "<span class=\"label label-info\">{$home_list['name']}</span>";
	}

	if ($sale_counter != 10)
	{
		$sales .= "[li]<a href=\"/sales/{$home_list['id']}\">{$provider} {$sale_name}</a>[/li]";
	}

	else
	{
		$sales .= "[li]<a href=\"/sales/{$home_list['id']}\">{$provider} {$sale_name}</a>[/li]";
	}
}
$sales .= "[/ul]\r\n\r\n";

$page2 .= "\r\nAlso remember to check out our [url=/sales/]Game Sales[/url] page where we syndicate game sales from Humble Store, Itch.io, IndieGameStand, GamersGate and many more stores!\r\n\r\nSee the latest few sales here:\r\n$sales";

$donate_text = "As always if you wish to support us you can [url=http://www.gamingonlinux.com/donate/]Support Us Here[/url] and become a GOL Supporter!\r\n
If you have any ideas to improve these weekly overviews then let us know right away! If you find weekly round-ups useful, please let us know and we will continue!";

$text .= $donate_text;
$page2 .= $donate_text . "\r\nWe hope you enjoy all the news we bring you!";

// DEBUG
echo bbcode($text);
echo bbcode($page2);

$db->sqlquery("INSERT INTO `articles` SET `author_id` = 1844, `date` = ?, `title` = ?, `tagline` = ?, `text` = ?, `page2` = ?, `show_in_menu` = 0, `tagline_image` = 'weeklyoverview.jpg'", array(core::$date, $title, $tagline, $text, $page2));

$article_id = $db->grab_id();

$db->sqlquery("INSERT INTO `article_category_reference` SET `article_id` = ?, `category_id` = 63", array($article_id));
?>
