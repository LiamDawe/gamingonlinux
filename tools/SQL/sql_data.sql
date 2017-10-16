-- phpMyAdmin SQL Dump
-- version 4.6.6
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 08, 2017 at 09:21 AM
-- Server version: 5.6.35
-- PHP Version: 5.6.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `gamingonlinux`
--

--
-- Dumping data for table `admin_blocks`
--

INSERT INTO `admin_blocks` (`block_id`, `block_link`, `block_name`, `activated`, `blocks_custom_content`, `admin_only`) VALUES
(1, 'main_menu', 'Main Menu', 1, '', 0),
(3, 'articles', 'Articles Admin', 1, '', 0),
(10, 'blocks', 'Manage Blocks', 1, NULL, 1),
(11, 'modules', 'Modules Configuration', 1, NULL, 1),
(9, 'forum', 'Forum Admin', 1, NULL, 0),
(7, 'users', 'Users Block', 1, NULL, 0),
(5, 'calendar', 'calendar', 1, NULL, 0),
(8, 'goty', 'goty', 1, NULL, 0),
(4, 'featured', 'featured', 1, NULL, 0),
(2, 'mod_queue', 'Mod Queue', 1, NULL, 0),
(18, 'charts', 'charts', 1, NULL, 0),
(20, 'livestreams', 'livestreams', 1, NULL, 0);

--
-- Dumping data for table `admin_modules`
--

INSERT INTO `admin_modules` (`module_id`, `module_name`, `module_title`, `module_link`, `show_in_sidebar`, `activated`, `admin_only`) VALUES
(1, 'home', 'Admin Home', 'admin.php?module=home', 1, 1, 0),
(2, 'articles', 'Articles Admin', '', 0, 1, 0),
(3, 'categorys', 'Article Categorys', '', 0, 1, 0),
(4, 'blocks', 'Blocks', '', 0, 1, 1),
(5, 'config', 'Configuration', 'admin.php?module=config', 1, 1, 1),
(7, 'modules', 'Module Configuration', NULL, 0, 1, 1),
(8, 'forum', 'Forum Admin', NULL, 0, 1, 0),
(10, 'users', 'Users', NULL, 0, 1, 0),
(28, 'games', 'Games Database', NULL, 0, 1, 0),
(18, 'comments', 'comments', NULL, 0, 1, 0),
(17, 'goty', 'goty', 'goty', 0, 1, 0),
(15, 'notes', 'Notes', 'admin.php?module=notes', 1, 1, 0),
(16, 'featured', 'featured', NULL, 0, 1, 0),
(19, 'more_comments', 'view more editor comments', NULL, 0, 1, 0),
(20, 'calendar', 'calendar', NULL, 0, 1, 0),
(21, 'mod_queue', 'Moderation Queue', '', 0, 1, 0),
(24, 'charts', 'charts', NULL, 0, 1, 1),
(27, 'preview', 'preview', NULL, 0, 1, 0),
(26, 'reviewqueue', 'Admin review queue', NULL, 0, 1, 0),
(29, 'announcements', 'Manage Announcements', 'admin.php?module=announcements&view=manage', 1, 1, 0),
(30, 'add_article', 'Add New Article', NULL, 0, 1, 0),
(31, 'comment_reports', 'Comment Reports', NULL, 0, 1, 0),
(32, 'livestreams', 'Manage Livestreams', NULL, 0, 1, 0),
(33, 'corrections', 'corrections', NULL, 0, 1, 0),
(34, 'article_dump', 'Article Dump', NULL, 0, 1, 0),
(35, 'giveaways', 'Manage Key Giveaways', 'admin.php?module=giveaways', 1, 1, 0),
(36, 'article_history', 'Article History', NULL, 0, 1, 0);

--
-- Dumping data for table `admin_notification_types`
--

INSERT INTO `admin_notification_types` (`id`, `name`, `text`, `link`) VALUES
(1, 'comment_deleted', 'deleted a comment.', ''),
(2, 'closed_comments', 'closed the comments on an article.', 'index.php?module=articles_full&aid={:article_id}&title={:title}'),
(3, 'reported_comment', 'reported a comment.', ''),
(4, 'deleted_comment_report', 'deleted a comment report.', ''),
(5, 'forum_topic_report', 'reported a forum topic.', ''),
(6, 'forum_reply_report', 'reported a forum reply', ''),
(7, 'deleted_topic_report', 'deleted a forum topic report.', ''),
(8, 'deleted_reply_report', 'deleted a forum reply report.', ''),
(9, 'mod_queue', 'requires approval of their forum post.', 'admin.php?module=mod_queue&view=manage'),
(10, 'mod_queue_approved', 'approved a forum post.', 'index.php?module=viewtopic&topic_id={:topic_id}'),
(11, 'mod_queue_removed', 'removed a forum topic requesting approval.', ''),
(12, 'mod_queue_removed_ban', 'removed a forum topic requesting approval and banned the user.', ''),
(13, 'delete_forum_topic', 'deleted a forum topic.', ''),
(14, 'stuck_forum_topic', 'stickied a forum topic.', ''),
(15, 'unstuck_forum_topic', 'unstuck a forum topic.', ''),
(16, 'locked_forum_topic', 'locked a forum topic.', 'index.php?module=viewtopic&topic_id={:topic_id}'),
(17, 'unlocked_forum_topic', 'unlocked a forum topic.', 'index.php?module=viewtopic&topic_id={:topic_id}'),
(18, 'unlocked_stuck_forum_topic', 'unlocked and stickied a forum topic.', 'index.php?module=viewtopic&topic_id={:topic_id}'),
(19, 'locked_unstuck_forum_topic', 'locked and unstuck a forum topic.', 'index.php?module=viewtopic&topic_id={:topic_id}'),
(20, 'unlocked_unstuck_forum_topic', 'unlocked and unstuck a forum topic.', 'index.php?module=viewtopic&topic_id={:topic_id}'),
(21, 'locked_stuck_forum_topic', 'locked and stickied a forum topic.', 'index.php?module=viewtopic&topic_id={:topic_id}'),
(22, 'edited_user', 'edited a user.', ''),
(23, 'banned_user', 'banned a user.', ''),
(24, 'unbanned_user', 'unbanned a user.', ''),
(25, 'ip_banned', 'banned an IP address.', ''),
(26, 'total_ban', 'banned a user along with their IP address.', ''),
(27, 'unban_ip', 'unbanned an IP address.', ''),
(28, 'delete_user', 'deleted a user account.', ''),
(29, 'deleted_user_content', 'deleted all the content from a user.', ''),
(30, 'calendar_submission', 'submitted a game for the calendar and games database.', 'admin.php?module=calendar&view=submitted'),
(31, 'approved_calendar', 'approved a calendar and games database submission.', ''),
(32, 'game_database_addition', 'added a new game to the calendar and games database', ''),
(33, 'game_database_edit', 'edited a game in the calendar and games database', ''),
(34, 'game_database_deletion', 'deleted a game from the calendar and games database', ''),
(35, 'deleted_article', 'deleted an article.', ''),
(36, 'denied_submitted_article', 'denied a user submitted article.', ''),
(37, 'approve_submitted_article', 'approved a user submitted article.', 'index.php?module=articles_full&aid={:article_id}&title={:title}'),
(38, 'article_admin_queue_approved', 'approved an article from the admin review queue.', 'index.php?module=articles_full&aid={:article_id}&title={:title}'),
(39, 'article_admin_queue', 'sent a new article to the admin review queue.', 'admin.php?module=reviewqueue'),
(40, 'new_article_published', 'published a new article.', 'index.php?module=articles_full&aid={:article_id}&title={:title}'),
(41, 'submitted_article', 'submitted an article.', 'admin.php?module=articles&view=Submitted'),
(42, 'article_correction', 'sent in an article correction.', 'admin.php?module=corrections'),
(43, 'deleted_correction', 'deleted an article correction report.', ''),
(44, 'disabled_article', 'disabled an article.', 'admin.php?module=articles&view=manage&category=inactive'),
(45, 'enabled_article', 're-enabled an article.', 'index.php?module=articles_full&aid={:article_id}&title={:title}'),
(46, 'new_livestream_event', 'added a new livestream event.', ''),
(47, 'edit_livestream_event', 'edited a livestream event.', ''),
(48, 'deleted_livestream_event', 'deleted a livestream event.', ''),
(49, 'new_livestream_submission', 'sent a livestream event for review.', ''),
(50, 'accepted_livestream_submission', 'accepted a livestream submission.', ''),
(51, 'denied_livestream_submission', 'denied a livestream submission.', ''),
(52, 'goty_game_submission', 'submitted a GOTY game for review.', ''),
(53, 'goty_game_added', 'added a GOTY game.', ''),
(54, 'goty_accepted_game', 'accepted a GOTY submission.', ''),
(55, 'goty_denied_game', 'denied a GOTY submission.', ''),
(56, 'goty_finished', 'closed the GOTY awards.', ''),
(57, 'mod_queue_reply', 'requires approval of their forum post.', 'admin.php?module=mod_queue&view=manage'),
(58, 'mod_queue_reply_approved', 'approved a forum reply.', 'index.php?module=viewtopic&topic_id={:topic_id}&post_id={:post_id}'),
(59, 'opened_comments', 'opened the comments on an article.', ''),
(60, 'delete_forum_reply', 'deleted a forum reply', '');

--
-- Dumping data for table `articles_categorys`
--

INSERT INTO `articles_categorys` (`category_id`, `category_name`, `quick_nav`) VALUES
(22, 'Site Info', 0),
(1, 'Editorial', 1),
(2, 'Review', 1),
(3, 'Interview', 1),
(4, 'Game Sale', 0),
(5, 'Steam', 0),
(6, 'Indie Game', 0),
(7, 'Crowdfunding', 0),
(8, 'Game Bundle', 0),
(9, 'Free Game', 0),
(10, 'MMO', 0),
(11, 'Open Source', 0),
(12, 'Unity3D', 0),
(13, 'Desura', 0),
(14, 'Competition', 0),
(15, 'Wine', 0),
(16, 'Initial Thoughts', 0),
(17, 'Coming Soon', 0),
(23, 'Emulation', 0),
(20, 'FPS', 0),
(21, 'Strategy', 0),
(24, 'Hardware', 0),
(35, 'Card Game', 0),
(26, 'Puzzle', 0),
(27, 'Adventure', 0),
(28, 'Jobs', 0),
(29, 'Humble Store', 0),
(30, 'Action', 0),
(31, 'RPG', 0),
(32, 'Tower Defence', 0),
(33, 'Zombies', 0),
(34, 'Roguelike', 0),
(36, 'Educational', 0),
(37, 'GOL Cast', 0),
(38, 'Racing', 0),
(39, 'Sports', 0),
(40, 'Sandbox', 0),
(41, 'Platformer', 0),
(42, 'Simulation', 0),
(43, 'Preview', 0),
(44, 'Demo', 0),
(45, 'Video', 0),
(46, 'HOWTO', 1),
(47, 'MOBA', 0),
(48, 'Greenlight', 0),
(49, 'IndieGameStand', 0),
(50, 'Stealth', 0),
(51, 'Horror', 0),
(52, 'Itch.io', 0),
(53, 'DLC', 0),
(54, 'Teaser', 0),
(55, 'Event', 0),
(56, 'Community', 0),
(57, 'Toolkit', 0),
(58, 'Survival', 0),
(59, 'Game Jam', 0),
(60, 'Early Access', 0),
(61, 'Arcade', 0),
(62, 'Point & Click', 0),
(63, 'Article Overview', 0),
(64, 'Visual Novel', 0),
(65, 'Music', 0),
(67, 'Unreal Engine', 0),
(68, 'CryEngine', 0),
(69, 'Game Maker', 0),
(70, 'Procedural Death Labyrinth', 0),
(71, 'Mod', 0),
(72, 'DRM', 0),
(73, 'GOG', 0),
(74, 'Press Release', 0),
(75, 'Beta', 0),
(76, 'Casual', 0),
(77, 'Flash', 0),
(78, 'Game Engine', 0),
(79, 'Retro', 0),
(81, 'DRM Free', 0),
(82, 'DOSBox', 0),
(83, 'Survey', 0),
(84, 'Benchmark', 0),
(85, 'City Builder', 0),
(86, 'Feral Interactive', 0),
(87, 'Dungeon Crawler', 0),
(88, 'AMD', 0),
(89, 'Vulkan', 0),
(90, 'GOTY', 0),
(91, 'NVIDIA', 0),
(92, 'New Survey', 0),
(93, 'Survey Results', 0),
(94, 'Livestream', 0),
(95, 'Kernel', 0),
(96, 'Drivers', 0),
(97, 'Podcast', 0),
(98, 'Intel', 0),
(99, 'SteamOS', 0),
(100, 'Virtual Reality', 0),
(101, 'BoilingSteam', 0),
(102, 'Alpha', 0),
(103, 'NSFW', 0),
(104, 'Anime', 0),
(105, 'Ubuntu', 0),
(106, 'Virtual Programming', 0),
(107, 'Aspyr Media', 0),
(108, 'Mesa', 0),
(109, 'Fighting', 0),
(110, 'Vampires', 0),
(111, 'Fan Game', 0),
(112, 'Speculation', 0),
(113, 'Text Adventure', 0),
(114, 'Pixel Graphics', 0),
(115, 'Comedy', 0),
(116, 'Cyberpunk', 0),
(118, 'Gore', 0),
(119, 'Realistic', 0),
(120, 'Child', 0),
(121, 'Short', 0),
(122, 'Misc', 0),
(123, 'Rogue-lite', 0),
(124, 'Beat \'em up', 0),
(125, 'Apps', 0),
(126, 'Open World', 0),
(127, 'OpenGL', 0),
(128, 'Board Game', 0),
(129, 'Pay What You Want', 0),
(130, 'Exploration', 0),
(131, 'Hidden Object', 0),
(132, 'Giveaway', 0),
(133, 'Valve', 0),
(134, 'Local co-op', 0);

--
-- Dumping data for table `articles_tagline_gallery`
--

INSERT INTO `articles_tagline_gallery` (`id`, `filename`, `name`, `uploader_id`) VALUES
(1, 'amd-logo.jpg', 'AMD Logo', 1),
(2, 'editorial.png', 'Editorial', 1),
(3, 'fna.png', 'FNA Logo', 1),
(4, 'gog_logo.jpg', 'GOG Logo', 1),
(5, 'gog-logo-money.jpg', 'GOG Logo Money', 1),
(6, 'goty.png', 'GOTY Logo', 1),
(7, 'humble-indie-bundle.png', 'HIB', 1),
(8, 'humble-pc-and-android.png', 'Humble PC & Android', 1),
(9, 'humble-weekly.png', 'Humble Weekly', 1),
(10, 'interview.png', 'Interview', 1),
(11, 'itch-logo.png', 'itch.io Logo', 1),
(12, 'livestream.jpg', 'Livestream', 1),
(13, 'nvidia-logo.jpg', 'NVIDIA Logo', 1),
(14, 'opengl-logo.jpg', 'OpenGL Logo', 1),
(15, 'podcast.png', 'Podcast', 1),
(16, 'steam-controller.jpg', 'Steam Controller', 1),
(17, 'Steam-Logo.jpg', 'Steam Logo', 1),
(18, 'steamos-logo.jpg', 'SteamOS Logo', 1),
(19, 'UE-logo.jpg', 'Unreal Engine Logo', 1),
(20, 'vulkan.png', 'Vulkan Logo', 1),
(21, 'youtube-logo.jpg', 'Youtube Logo', 1),
(22, 'wine-release.png', 'Wine Release', 1),
(23, 'humblebundle.png', 'Humble Bundle', 1),
(24, 'mesa.png', 'Mesa', 1),
(25, 'gol-logo-money.png', 'GOL Logo Money', 1),
(26, 'defaulttagline.png', 'GOL Penguin', 1),
(27, 'OpenXR.png', 'OpenXR', 1),
(28, 'SteamVR.jpg', 'SteamVR', 1);

--
-- Dumping data for table `avatars_gallery`
--

INSERT INTO `avatars_gallery` (`id`, `filename`) VALUES
(1, '1.png'),
(2, '2.png'),
(3, '3.png'),
(4, '4.png'),
(5, '5.png'),
(6, '6.png');

--
-- Dumping data for table `blocks`
--

INSERT INTO `blocks` (`block_id`, `block_link`, `block_name`, `block_title`, `block_title_link`, `activated`, `block_custom_content`, `order`, `style`, `nonpremium_only`, `homepage_only`) VALUES
(4, 'block_article_categorys', 'Article Categorys', 'Articles', '', 1, NULL, 2, 'block', 0, 0),
(30, 'block_livestreams', 'Livestreams', 'Livestreams', '', 1, NULL, 1, '', 0, 0),
(11, 'block_twitter', 'Twitter Feed', 'Twitter Feed', '', 1, NULL, 4, 'block', 0, 0),
(23, 'block_forum_latest', 'Latest Forum Posts', 'Latest Forum Posts', '', 1, NULL, 7, 'block', 0, 0),
(14, 'block_comments_latest', 'Latest Comments', 'Latest Comments', '', 1, NULL, 6, 'block', 0, 0),
(21, 'block_facebook', 'Facebook', '', '', 1, NULL, 9, 'block', 0, 0),
(24, 'block_misc', 'Misc', 'Misc', '', 1, NULL, 11, '', 0, 0);

--
-- Dumping data for table `config`
--

INSERT INTO `config` (`id`, `data_key`, `data_value`) VALUES
(1, 'template', 'default'),
(2, 'default_module', 'home'),
(3, 'allow_registrations', '1'),
(4, 'register_captcha', '1'),
(5, 'guests_captcha_submit_articles', '1'),
(6, 'website_url', ''),
(7, 'articles_rss', '1'),
(8, 'register_off_message', 'Sorry but the admin has disabled user registrations.'),
(10, 'avatar_width', '125'),
(11, 'avatar_height', '125'),
(12, 'recaptcha_secret', ''),
(14, 'total_users', '0'),
(15, 'contact_email', ''),
(16, 'total_articles', '0'),
(17, 'article_image_max_width', '550'),
(18, 'article_image_max_height', '250'),
(20, 'tw_consumer_key', ''),
(21, 'tw_consumer_skey', ''),
(23, 'editor_picks_limit', '5'),
(24, 'carousel_image_width', '1300'),
(25, 'carousel_image_height', '300'),
(28, 'send_emails', '1'),
(29, 'rules', ''),
(30, 'pretty_urls', '0'),
(31, 'path', '/some/path'),
(34, 'goty_games_open', '0'),
(35, 'goty_voting_open', '0'),
(37, 'goty_page_open', '0'),
(38, 'goty_total_votes', '4815'),
(39, 'goty_finished', '1'),
(40, 'show_debug', '0'),
(41, 'max_tagline_image_filesize', '190900'),
(42, 'telegram_bot_key', ''),
(44, 'comments_open', '1'),
(45, 'forum_posting_open', '1'),
(46, 'cookie_domain', 'localhost'),
(47, 'total_featured', '0'),
(48, 'captcha_disabled', '0'),
(49, 'twitch_dev_key', ''),
(50, 'default-comments-per-page', '10'),
(51, 'hot-article-viewcount', '1500'),
(52, 'tagline-max-length', '400'),
(53, 'limit_youtube', '3'),
(54, 'ip_ban_length', '30'),
(55, 'meta_keywords', 'linux, steamos, gaming, linux gaming, linux games, linux game reviews, linux game news, games, reviews, interviews'),
(56, 'site_title', 'GamingOnLinux'),
(57, 'meta_homepage_title', 'Linux & SteamOS gaming community'),
(58, 'meta_description', 'GamingOnLinux is the home of Linux and SteamOS gaming. Covering Linux Games, SteamOS, Reviews and more.'),
(59, 'twitter_username', 'gamingonlinux'),
(60, 'about_text', ''),
(61, 'mailer_email', 'noreply@site.com'),
(63, 'quick_nav', '1'),
(64, 'recaptcha_public', ''),
(65, 'steam_openid_key', ''),
(66, 'forum_rss', '1'),
(67, 'telegram_group', ''),
(68, 'discord', ''),
(69, 'steam_group', ''),
(70, 'youtube_channel', ''),
(71, 'gplus_page', ''),
(72, 'facebook_page', ''),
(73, 'twitch_channel', ''),
(74, 'twitter_login', '0'),
(75, 'steam_login', '0'),
(76, 'support_us_text', ''),
(77, 'telegram_news_channel', ''),
(78, 'google_login_secret', ''),
(79, 'google_login_public', ''),
(80, 'google_login', '0'),
(81, 'local_users', '1'),
(82, 'remote_users_database', ''),
(83, 'remote_sql_prefix', ''),
(84, 'user_group_prefix', '');

--
-- Dumping data for table `desktop_environments`
--

INSERT INTO `desktop_environments` (`id`, `name`) VALUES
(1, 'Cinnamon'),
(2, 'Unity'),
(3, 'KDE Plasma'),
(4, 'GNOME'),
(5, 'MATE'),
(6, 'XFCE'),
(7, 'LXDE'),
(9, 'Budgie'),
(10, 'Enlightenment'),
(11, 'LXQt'),
(12, 'Not Listed'),
(13, 'Window Manager Only'),
(14, 'Pantheon Shell'),
(15, 'Deepin Desktop Environment');

--
-- Dumping data for table `distributions`
--

INSERT INTO `distributions` (`id`, `name`, `arch-based`, `ubuntu-based`, `fedora-based`) VALUES
(1, 'Antergos', 1, 0, 0),
(2, 'Arch', 1, 0, 0),
(3, 'Chakra', 0, 0, 0),
(4, 'Debian', 0, 0, 0),
(5, 'Elementary', 0, 1, 0),
(6, 'Fedora', 0, 0, 0),
(7, 'Gentoo', 0, 0, 0),
(8, 'Kubuntu', 0, 1, 0),
(9, 'Lubuntu', 0, 1, 0),
(10, 'Mageia', 0, 0, 0),
(11, 'Manjaro', 1, 0, 0),
(12, 'Mint', 0, 1, 0),
(13, 'openSUSE', 0, 0, 0),
(14, 'Sabayon', 0, 0, 0),
(15, 'Slackware', 0, 0, 0),
(16, 'SteamOS', 0, 0, 0),
(17, 'Solus', 0, 0, 0),
(18, 'Ubuntu', 0, 1, 0),
(19, 'Ubuntu-GNOME', 0, 1, 0),
(20, 'Ubuntu-MATE', 0, 1, 0),
(21, 'Xubuntu', 0, 1, 0),
(22, 'Not Listed', 0, 0, 0),
(23, 'ZorinOS', 0, 1, 0),
(24, 'Netrunner', 0, 0, 0),
(25, 'PCLinuxOS', 0, 0, 0),
(26, 'KDE neon', 0, 1, 0),
(27, 'Exherbo', 0, 0, 0),
(28, 'Peppermint', 0, 1, 0),
(29, 'Korora', 0, 0, 1),
(30, 'Void', 0, 0, 0);

--
-- Dumping data for table `game_genres`
--

INSERT INTO `game_genres` (`id`, `name`, `accepted`) VALUES
(1, 'Strategy', 1),
(2, 'Action', 1),
(3, 'MMO', 1),
(4, 'Adventure', 1),
(5, 'FPS', 1),
(6, 'Puzzle', 1),
(7, 'Platformer', 1),
(8, 'RPG', 1),
(9, 'Casual', 1),
(10, 'Simulation', 1),
(11, 'Visual Novel', 1),
(12, 'Beat &#039;Em Up', 1),
(13, 'Roguelike', 1),
(14, 'Text-Based', 1),
(15, 'Racing', 1),
(16, 'Valve', 1),
(17, 'Horror', 1),
(18, 'Tower Defence', 1),
(19, 'Sandbox', 1),
(20, 'Survival', 1);

--
-- Dumping data for table `modules`
--

INSERT INTO `modules` (`module_id`, `module_file_name`, `activated`, `nice_title`, `nice_link`, `sections_link`) VALUES
(1, 'home', 1, 'Home', NULL, 0),
(2, 'login', 1, 'Login', NULL, 0),
(3, 'register', 1, 'Register', NULL, 0),
(4, 'articles', 1, 'Articles List', NULL, 0),
(5, 'articles_full', 1, 'Articles Full', NULL, 0),
(16, 'search', 1, 'Search', NULL, 0),
(6, 'forum', 1, 'Forum', NULL, 0),
(7, 'viewforum', 1, 'View Forum', NULL, 0),
(8, 'newtopic', 1, 'New Topic', NULL, 0),
(9, 'viewtopic', 1, 'View Topic', NULL, 0),
(10, 'newreply', 1, 'New Reply', NULL, 0),
(11, 'profile', 1, 'Profile', NULL, 0),
(12, 'editpost', 1, 'Edit Post', NULL, 0),
(13, 'contact', 1, 'Contact Us', NULL, 0),
(14, 'messages', 1, 'Private Messages', NULL, 0),
(15, 'support_us', 1, 'Support Us', 'support-us/', 1),
(17, 'email_us', 1, 'Email Us', NULL, 0),
(18, 'about_us', 1, 'About Us', NULL, 0),
(19, 'comments_latest', 1, 'Latest Comments', NULL, 0),
(20, 'search_forum', 1, 'Forum Search', NULL, 0),
(21, 'account_links', 1, 'Account Links', NULL, 0),
(22, 'rules', 1, 'Rules', NULL, 0),
(23, 'guidelines', 1, 'Article Guidelnes', NULL, 0),
(24, 'activate_user', 1, 'Activate User', NULL, 0),
(25, 'calendar', 1, 'Release Calendar', NULL, 1),
(26, 'submit_article', 1, 'Submit Article', NULL, 0),
(27, 'statistics', 1, 'Statistics', 'users/statistics', 1),
(28, 'report_post', 1, 'Report Post', NULL, 0),
(29, 'game-search', 1, 'Game Search', NULL, 0),
(30, 'unlike_all', 1, 'Unlike All', NULL, 0),
(31, 'livestreams', 1, 'Livestreams', NULL, 1),
(32, 'website_stats', 1, 'Website Stats', NULL, 0),
(33, 'video', 1, 'Video Directory', NULL, 0),
(34, 'game_servers', 1, 'Game Servers', NULL, 1),
(35, 'irc', 1, 'IRC', NULL, 0),
(36, '404', 1, '404', NULL, 0),
(37, 'user_search', 1, 'User Search', NULL, 0);

--
-- Dumping data for table `usercp_blocks`
--

INSERT INTO `usercp_blocks` (`block_id`, `block_link`, `block_name`, `block_title`, `activated`, `left`, `right`, `block_custom_content`, `block_title_link`) VALUES
(1, 'block_usercp_menu', 'User Menu', 'User Menu', 1, 1, 0, NULL, '');

--
-- Dumping data for table `usercp_modules`
--

INSERT INTO `usercp_modules` (`module_id`, `module_file_name`, `module_title`, `module_link`, `show_in_sidebar`, `activated`) VALUES
(1, 'home', 'User CP Home', 'usercp.php?module=home', 1, 1),
(2, 'email', 'Change Email', 'usercp.php?module=email', 1, 1),
(3, 'password', 'Change Password', 'usercp.php?module=password', 1, 1),
(4, 'avatar', 'Avatar', 'usercp.php?module=avatar', 1, 1),
(5, 'topic_subscriptions', 'Manage Forum Subscriptions', 'usercp.php?module=topic_subscriptions', 1, 1),
(6, 'article_subscriptions', 'Manage Article Subscriptions', 'usercp.php?module=article_subscriptions', 1, 1),
(8, 'pcinfo', 'PC Info', 'usercp.php?module=pcinfo', 1, 1),
(9, 'notifications', 'Notifications', 'usercp.php?module=notifications', 1, 1),
(10, 'notification_preferences', 'Notification Preferences', 'usercp.php?module=notification_preferences', 1, 1),
(11, 'bookmarks', 'Bookmarks', 'usercp.php?module=bookmarks', 1, 1);

--
-- Dumping data for table `user_groups`
--

INSERT INTO `user_groups` (`group_id`, `group_name`, `show_badge`, `badge_text`, `badge_colour`, `remote_group`, `universal`) VALUES
(1, 'Admin', 1, 'Admin', 'red', 0, 0),
(2, 'Editor', 1, 'Editor', 'pale-green', 0, 0),
(3, 'Member', 0, NULL, NULL, 0, 0),
(4, 'Guest', 0, NULL, NULL, 0, 0),
(5, 'Contributing Editor', 1, 'Contributing Editor', 'pale-green', 0, 0),
(6, 'Supporter', 1, 'Supporter', 'orange', 0, 1);

--
-- Dumping data for table `user_group_permissions`
--

INSERT INTO `user_group_permissions` (`id`, `name`) VALUES
(1, 'access_admin'),
(2, 'comment_on_articles'),
(5, 'skip_contact_captcha'),
(6, 'skip_submit_article_captcha'),
(7, 'article_submission_emails');

INSERT INTO `user_group_permissions_membership` (`group_id`, `permission_id`) VALUES
(1, 1),
(1, 2),
(1, 5),
(1, 6),
(1, 7),
(5, 1),
(5, 2),
(5, 5),
(5, 6),
(5, 7),
(2, 1),
(2, 2),
(2, 5),
(2, 6),
(2, 7),
(3, 2),
(3, 5),
(3, 6),
(6, 2),
(6, 5),
(6, 6);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
