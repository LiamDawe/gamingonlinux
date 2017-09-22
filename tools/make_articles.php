<?php
define("APP_ROOT", dirname( dirname(__FILE__) ) );

require APP_ROOT . "/includes/bootstrap.php";

// amount of articles to make
$article_total = 50;

// amount of comments each article should get
$comments_total = 999;

for ($i = 1; $i <= $article_total; $i++)
{
  // generate the article
  $title = 'This is a test title from the make articles tool ' . $i;
  $slug = core::nice_title($title);
  $tagline = "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.";
  $text = "Lorem Ipsum is simply <em>dummy text</em> of the printing and <u>typesetting industry</u>. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more [b]recently with desktop publishing software[/b] like Aldus PageMaker including versions of Lorem Ipsum.";

  // tagline image
  $gallery_tagline = rand(1,8);

  $sql = "INSERT INTO `articles` SET
  `author_id` = 1,
  `title` = ?,
  `slug` = ?,
  `tagline` = ?,
  `text` = ?,
  `active` = 1,
  `date` = ?,
  `admin_review` = 0,
  `gallery_tagline` = ?";

  $dbl->run($sql, array($title, $slug, $tagline, $text, core::$date, $gallery_tagline));
  $article_id = $dbl->new_id();

  // generate comments for those articles
  for ($c = 1; $c <= $comments_total; $c++)
  {
    $author_id = rand(1,100);

    $comment_text = "Lorem Ipsum is simply [u]dummy text[/u] of the printing and [i]typesetting industry[/i]. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more [b]recently with desktop publishing software[/b] like Aldus PageMaker including versions of Lorem Ipsum.";

    $comments_sql = "INSERT INTO `articles_comments` SET
    `article_id` = ?,
    `author_id` = ?,
    `time_posted` = ?,
    `comment_text` = ?";

    $dbl->run($comments_sql, array($article_id, $author_id, core::$date, $comment_text));
  }

  $dbl->run("UPDATE `articles` SET `comment_count` = ? WHERE `article_id` = ?", array($comments_total, $article_id));
}
echo 'Done';
?>
