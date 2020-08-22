<?php
define("APP_ROOT", dirname( dirname(__FILE__) ) );

require APP_ROOT . "/includes/bootstrap.php";

define('golapp', TRUE);

define('WEBHOOK_URL', $core->config('discord_news_webhook'));

include($core->config('path') . 'includes/discord_poster.php');

post_to_discord(array('title' => 'test plz ignore', 'link' => 'https://www.gamingonlinux.com/', 'tagline' => "look at me, i'm a tagline mom!", 'image' => 'https://www.gamingonlinux.com/uploads/tagline_gallery/GOL%20Cup.jpg'));
?>