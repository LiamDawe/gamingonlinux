<?php

class article extends base
{
	protected static $table = "articles";
	protected static $primary = "article_id";

	public function comments()
	{
		return comment::where("article_id", $this->${static::$primary});
	}

	public static function latestNews($page=1)
	{
		$page = ($page>0?$page:1);
		$start = $core->start + ($_SESSION['articles-per-page'] * $page-1);

		$db = mysql::getInstance();
		$q = $db->sqlquery("SELECT a.article_id, a.author_id, a.guest_username, a.title, a.tagline, a.text, a.date, a.comment_count, a.article_top_image, a.article_top_image_filename, a.tagline_image, a.show_in_menu, a.slug, u.username FROM `articles` a LEFT JOIN `users` u on a.author_id = u.user_id WHERE a.active = 1 ORDER BY a.`date` DESC LIMIT ?, ?", [$start, $_SESSION['articles-per-page']]);
		return self::oneOrMore($q->fetch_all_rows() );
	}
}