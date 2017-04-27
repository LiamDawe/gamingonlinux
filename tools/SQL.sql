-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 27, 2017 at 08:41 PM
-- Server version: 10.1.22-MariaDB
-- PHP Version: 7.1.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `goltest`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_blocks`
--

CREATE TABLE `admin_blocks` (
  `block_id` int(11) UNSIGNED NOT NULL,
  `block_link` varchar(32) COLLATE utf8_bin DEFAULT NULL,
  `block_name` varchar(32) COLLATE utf8_bin NOT NULL,
  `activated` int(1) NOT NULL,
  `blocks_custom_content` text COLLATE utf8_bin,
  `admin_only` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `admin_blocks`
--

INSERT INTO `admin_blocks` (`block_id`, `block_link`, `block_name`, `activated`, `blocks_custom_content`, `admin_only`) VALUES
(1, 'main_menu', 'Main Menu', 1, '', 0),
(2, 'articles', 'Articles Admin', 1, '', 0),
(3, 'blocks', 'Manage Blocks', 1, NULL, 1),
(4, 'modules', 'Modules Configuration', 1, NULL, 1),
(5, 'forum', 'Forum Admin', 1, NULL, 0),
(6, 'users', 'Users Block', 1, NULL, 0),
(7, 'calendar', 'calendar', 1, NULL, 0),
(8, 'goty', 'goty', 1, NULL, 0),
(9, 'featured', 'featured', 1, NULL, 0),
(10, 'mod_queue', 'Mod Queue', 1, NULL, 0),
(11, 'charts', 'charts', 1, NULL, 0),
(12, 'livestreams', 'livestreams', 1, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `admin_discussion`
--

CREATE TABLE `admin_discussion` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `text` text NOT NULL,
  `date_posted` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `admin_modules`
--

CREATE TABLE `admin_modules` (
  `module_id` int(11) UNSIGNED NOT NULL,
  `module_name` varchar(32) COLLATE utf8_bin NOT NULL,
  `module_title` varchar(32) COLLATE utf8_bin NOT NULL,
  `module_link` text COLLATE utf8_bin,
  `show_in_sidebar` tinyint(1) NOT NULL DEFAULT '0',
  `activated` tinyint(1) NOT NULL DEFAULT '0',
  `admin_only` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `admin_modules`
--

INSERT INTO `admin_modules` (`module_id`, `module_name`, `module_title`, `module_link`, `show_in_sidebar`, `activated`, `admin_only`) VALUES
(1, 'home', 'Admin Home', 'admin.php?module=home', 1, 1, 0),
(2, 'articles', 'Articles Admin', '', 0, 1, 0),
(3, 'categorys', 'Article Categorys', '', 0, 1, 0),
(4, 'blocks', 'Blocks', '', 0, 1, 1),
(5, 'config', 'Configuration', 'admin.php?module=config', 1, 1, 1),
(6, 'modules', 'Module Configuration', NULL, 0, 1, 1),
(7, 'forum', 'Forum Admin', NULL, 0, 1, 0),
(8, 'users', 'Users', NULL, 0, 1, 0),
(9, 'games', 'Games Database', NULL, 0, 1, 0),
(10, 'comments', 'comments', NULL, 0, 1, 0),
(11, 'goty', 'goty', 'goty', 0, 1, 0),
(12, 'notes', 'Notes', 'admin.php?module=notes', 1, 1, 0),
(13, 'featured', 'featured', NULL, 0, 1, 0),
(14, 'more_comments', 'view more editor comments', NULL, 0, 1, 0),
(15, 'calendar', 'calendar', NULL, 0, 1, 0),
(16, 'mod_queue', 'Moderation Queue', '', 0, 1, 0),
(17, 'charts', 'charts', NULL, 0, 1, 1),
(18, 'preview', 'preview', NULL, 0, 1, 0),
(19, 'reviewqueue', 'Admin review queue', NULL, 0, 1, 0),
(20, 'announcements', 'Manage Announcements', 'admin.php?module=announcements&view=manage', 1, 1, 0),
(21, 'add_article', 'Add New Article', NULL, 0, 1, 0),
(22, 'comment_reports', 'Comment Reports', NULL, 0, 1, 0),
(23, 'livestreams', 'Manage Livestreams', NULL, 0, 1, 0),
(24, 'corrections', 'corrections', NULL, 0, 1, 0),
(25, 'article_dump', 'Article Dump', NULL, 0, 1, 0),
(26, 'giveaways', 'Manage Key Giveaways', 'admin.php?module=giveaways', 1, 1, 0),
(27, 'article_history', 'Article History', NULL, 0, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `admin_notes`
--

CREATE TABLE `admin_notes` (
  `user_id` int(11) UNSIGNED NOT NULL,
  `text` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `admin_notes`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT '0',
  `created_date` int(11) DEFAULT NULL,
  `completed_date` int(11) DEFAULT NULL,
  `type` text,
  `data` text,
  `content` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `admin_notifications`
--


-- --------------------------------------------------------

--
-- Table structure for table `admin_notification_types`
--

CREATE TABLE `admin_notification_types` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `text` text NOT NULL,
  `link` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;

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

-- --------------------------------------------------------

--
-- Table structure for table `admin_user_notes`
--

CREATE TABLE `admin_user_notes` (
  `row_id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `notes` text,
  `last_edited` int(11) DEFAULT NULL,
  `last_edit_by` int(11) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `admin_user_notes`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) UNSIGNED NOT NULL,
  `text` text NOT NULL,
  `author_id` int(11) UNSIGNED NOT NULL,
  `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_groups` text,
  `type` text,
  `modules` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `announcements`
--

-- --------------------------------------------------------

--
-- Table structure for table `articles`
--

CREATE TABLE `articles` (
  `article_id` int(11) UNSIGNED NOT NULL,
  `author_id` int(11) UNSIGNED NOT NULL,
  `guest_username` varchar(255) DEFAULT NULL,
  `guest_email` varchar(255) DEFAULT NULL,
  `guest_ip` varchar(100) DEFAULT NULL,
  `date` int(11) NOT NULL,
  `date_submitted` int(11) DEFAULT NULL,
  `title` varchar(120) NOT NULL,
  `slug` text NOT NULL,
  `tagline` text NOT NULL,
  `text` text CHARACTER SET utf8mb4 NOT NULL,
  `comment_count` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `active` int(1) NOT NULL DEFAULT '1',
  `show_in_menu` tinyint(1) NOT NULL DEFAULT '0',
  `views` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `submitted_article` tinyint(1) NOT NULL DEFAULT '0',
  `admin_review` tinyint(1) NOT NULL DEFAULT '0',
  `reviewed_by_id` int(11) UNSIGNED DEFAULT NULL,
  `submitted_unapproved` tinyint(1) NOT NULL DEFAULT '0',
  `comments_open` tinyint(1) NOT NULL DEFAULT '1',
  `draft` tinyint(1) NOT NULL DEFAULT '0',
  `tagline_image` text,
  `gallery_tagline` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `locked` tinyint(1) NOT NULL DEFAULT '0',
  `locked_by` int(11) UNSIGNED DEFAULT NULL,
  `locked_date` int(11) DEFAULT NULL,
  `preview_code` varchar(10) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `articles`
--

-- --------------------------------------------------------

--
-- Table structure for table `article_images`
--

CREATE TABLE `article_images` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `filename` text NOT NULL,
  `uploader_id` int(11) NOT NULL,
  `date_uploaded` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `article_images`
--

-- --------------------------------------------------------

--
-- Table structure for table `article_likes`
--

CREATE TABLE `article_likes` (
  `like_id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `article_id` int(11) UNSIGNED NOT NULL,
  `date` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `avatars_gallery`
--

CREATE TABLE `avatars_gallery` (
  `id` int(11) NOT NULL,
  `filename` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `avatars_gallery`
--

-- --------------------------------------------------------

--
-- Table structure for table `blocks`
--

CREATE TABLE `blocks` (
  `block_id` int(11) NOT NULL,
  `block_link` varchar(32) COLLATE utf8_bin DEFAULT NULL,
  `block_name` varchar(32) COLLATE utf8_bin NOT NULL,
  `block_title` varchar(32) COLLATE utf8_bin NOT NULL,
  `block_title_link` varchar(255) COLLATE utf8_bin NOT NULL,
  `activated` int(1) NOT NULL DEFAULT '0',
  `block_custom_content` text COLLATE utf8_bin,
  `order` int(11) NOT NULL,
  `style` text COLLATE utf8_bin NOT NULL,
  `nonpremium_only` tinyint(1) NOT NULL DEFAULT '0',
  `homepage_only` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `blocks`
--

INSERT INTO `blocks` (`block_id`, `block_link`, `block_name`, `block_title`, `block_title_link`, `activated`, `block_custom_content`, `order`, `style`, `nonpremium_only`, `homepage_only`) VALUES
(1, 'block_article_categorys', 'Article Categorys', 'Articles', '', 1, NULL, 2, 'block', 0, 0),
(2, 'block_livestreams', 'Livestreams', 'Livestreams', '', 1, NULL, 1, '', 0, 0),
(3, 'block_twitter', 'Twitter Feed', 'Twitter Feed', '', 1, NULL, 4, 'block', 0, 0),
(4, 'block_forum_latest', 'Latest Forum Posts', 'Latest Forum Posts', '', 1, NULL, 7, 'block', 0, 0),
(5, 'block_comments_latest', 'Latest Comments', 'Latest Comments', '', 1, NULL, 6, 'block', 0, 0),
(6, 'block_facebook', 'Facebook', '', '', 1, NULL, 9, 'block', 0, 0),
(7, 'block_misc', 'Misc', 'Misc', '', 1, NULL, 11, '', 0, 0),
(8, 'block_games', 'games', 'games', '', 1, NULL, 0, '', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `calendar`
--

CREATE TABLE `calendar` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `edit_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `name` text CHARACTER SET utf8 NOT NULL,
  `description` text COLLATE utf8_bin,
  `link` text COLLATE utf8_bin,
  `gog_link` text COLLATE utf8_bin,
  `steam_link` text COLLATE utf8_bin,
  `itch_link` text COLLATE utf8_bin,
  `best_guess` tinyint(1) NOT NULL DEFAULT '0',
  `approved` tinyint(1) NOT NULL DEFAULT '0',
  `is_dlc` tinyint(1) NOT NULL DEFAULT '0',
  `base_game_id` int(11) DEFAULT NULL,
  `also_known_as` int(10) UNSIGNED DEFAULT NULL,
  `free_game` tinyint(1) NOT NULL DEFAULT '0',
  `license` text COLLATE utf8_bin
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `calendar`
--

-- --------------------------------------------------------

--
-- Table structure for table `charts`
--

CREATE TABLE `charts` (
  `id` int(11) NOT NULL,
  `owner` int(11) NOT NULL DEFAULT '0',
  `name` text NOT NULL,
  `sub_title` text,
  `h_label` text NOT NULL,
  `generated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `grouped` tinyint(1) NOT NULL DEFAULT '0',
  `enabled` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `charts`
--

-- --------------------------------------------------------

--
-- Table structure for table `charts_data`
--

CREATE TABLE `charts_data` (
  `data_id` int(11) NOT NULL,
  `chart_id` int(11) NOT NULL,
  `label_id` int(11) NOT NULL,
  `data` int(11) NOT NULL,
  `data_series` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `charts_data`
--

-- --------------------------------------------------------

--
-- Table structure for table `charts_labels`
--

CREATE TABLE `charts_labels` (
  `label_id` int(11) NOT NULL,
  `chart_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `colour` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `charts_labels`
--

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

CREATE TABLE `config` (
  `id` int(11) NOT NULL,
  `data_key` varchar(50) NOT NULL,
  `data_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `config`
--

INSERT INTO `config` (`id`, `data_key`, `data_value`) VALUES
(1, 'template', 'default'),
(2, 'default_module', 'home'),
(3, 'allow_registrations', '1'),
(4, 'register_captcha', '0'),
(5, 'guests_captcha_submit_articles', '1'),
(6, 'website_url', '/'),
(7, 'articles_rss', '1'),
(8, 'register_off_message', 'Sorry but the admin has disabled user registrations.'),
(10, 'avatar_width', '125'),
(11, 'avatar_height', '125'),
(12, 'recaptcha_secret', ''),
(14, 'total_users', '0'),
(15, 'contact_email', 'contact@site.com'),
(16, 'total_articles', '0'),
(17, 'article_image_max_width', '550'),
(18, 'article_image_max_height', '250'),
(20, 'tw_consumer_key', ''),
(21, 'tw_consumer_skey', ''),
(23, 'editor_picks_limit', '5'),
(24, 'carousel_image_width', '1300'),
(25, 'carousel_image_height', '440'),
(28, 'send_emails', '0'),
(29, 'rules', ''),
(30, 'pretty_urls', '0'),
(31, 'path', '/'),
(34, 'goty_games_open', '0'),
(35, 'goty_voting_open', '1'),
(37, 'goty_page_open', '1'),
(38, 'goty_total_votes', '0'),
(39, 'goty_finished', '0'),
(40, 'show_debug', '1'),
(41, 'max_tagline_image_filesize', '190900'),
(42, 'telegram_bot_key', ''),
(44, 'comments_open', '1'),
(45, 'forum_posting_open', '1'),
(46, 'cookie_domain', 'yoursite.com'),
(47, 'total_featured', '0'),
(48, 'captcha_disabled', '1'),
(49, 'twitch_dev_key', ''),
(50, 'hot-article-viewcount', '2000'),
(51, 'default-comments-per-page', '10'),
(52, 'tagline-max-length', '400'),
(53, 'limit_youtube', '5'),
(54, 'ip_ban_length', '30'),
(55, 'site_title', 'GOL Portal'),
(56, 'meta_keywords', 'test'),
(57, 'meta_homepage_title', 'GOL Portal'),
(58, 'meta_description', 'GOL Portal new install'),
(59, 'mailer_email', 'noreply@localhost'),
(60, 'navbar_logo_icon', 'icon.svg'),
(61, 'quick_nav', '1'),
(62, 'twitter_username', ''),
(63, 'forum_rss', '1'),
(64, 'telegram_group', ''),
(65, 'discord', ''),
(66, 'steam_group', ''),
(67, 'youtube_channel', ''),
(68, 'gplus_page', ''),
(69, 'facebook_page', ''),
(70, 'twitch_channel', ''),
(71, 'google_login', '0'),
(72, 'google_login_public', ''),
(73, 'google_login_secret', ''),
(74, 'twitter_login', ''),
(75, 'steam_login', '');

-- --------------------------------------------------------

--
-- Table structure for table `desktop_environments`
--

CREATE TABLE `desktop_environments` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `desktop_environments`
--

-- --------------------------------------------------------

--
-- Table structure for table `distributions`
--

CREATE TABLE `distributions` (
  `id` int(11) NOT NULL,
  `name` text NOT NULL,
  `arch-based` tinyint(1) NOT NULL DEFAULT '0',
  `ubuntu-based` tinyint(1) NOT NULL DEFAULT '0',
  `fedora-based` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `distributions`
--

-- --------------------------------------------------------

--
-- Table structure for table `editor_discussion`
--

CREATE TABLE `editor_discussion` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `text` text NOT NULL,
  `date_posted` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `editor_discussion`
--

-- --------------------------------------------------------

--
-- Table structure for table `editor_picks`
--

CREATE TABLE `editor_picks` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `featured_image` text COLLATE utf8_bin NOT NULL,
  `hits` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `editor_picks`
--

-- --------------------------------------------------------

--
-- Table structure for table `forums`
--

CREATE TABLE `forums` (
  `forum_id` int(11) NOT NULL,
  `is_category` tinyint(1) NOT NULL DEFAULT '0',
  `parent_id` int(11) NOT NULL,
  `name` text COLLATE utf8_bin NOT NULL,
  `description` text COLLATE utf8_bin NOT NULL,
  `last_post_time` int(11) DEFAULT NULL,
  `last_post_user_id` int(11) DEFAULT NULL,
  `last_post_topic_id` int(11) NOT NULL DEFAULT '0',
  `posts` int(11) DEFAULT '0',
  `order` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `forums`
--

-- --------------------------------------------------------

--
-- Table structure for table `forum_permissions`
--

CREATE TABLE `forum_permissions` (
  `forum_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `can_view` int(1) NOT NULL DEFAULT '1',
  `can_topic` int(1) NOT NULL DEFAULT '0',
  `can_reply` int(1) NOT NULL DEFAULT '0',
  `can_lock` int(1) NOT NULL DEFAULT '0',
  `can_sticky` int(1) NOT NULL DEFAULT '0',
  `can_delete` int(1) NOT NULL DEFAULT '0',
  `can_delete_own` int(1) NOT NULL DEFAULT '0',
  `can_avoid_floods` int(1) NOT NULL DEFAULT '0',
  `can_move` int(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `forum_permissions`
--

-- --------------------------------------------------------

--
-- Table structure for table `forum_replies`
--

CREATE TABLE `forum_replies` (
  `post_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `creation_date` int(11) NOT NULL,
  `reply_text` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `guest_username` varchar(255) NOT NULL,
  `reported` tinyint(1) NOT NULL DEFAULT '0',
  `reported_by_id` int(11) NOT NULL,
  `approved` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `forum_replies`
--

-- --------------------------------------------------------

--
-- Table structure for table `forum_topics`
--

CREATE TABLE `forum_topics` (
  `topic_id` int(11) NOT NULL,
  `forum_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `topic_title` text CHARACTER SET utf8 NOT NULL,
  `topic_text` text COLLATE utf8_bin NOT NULL,
  `creation_date` int(11) NOT NULL,
  `replys` int(11) NOT NULL DEFAULT '0',
  `views` int(11) NOT NULL DEFAULT '0',
  `is_sticky` tinyint(1) NOT NULL DEFAULT '0',
  `is_locked` tinyint(1) NOT NULL DEFAULT '0',
  `last_post_date` int(11) DEFAULT NULL,
  `last_post_id` int(11) DEFAULT NULL,
  `guest_username` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `reported` tinyint(1) NOT NULL DEFAULT '0',
  `reported_by_id` int(11) DEFAULT NULL,
  `approved` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `forum_topics`
--

-- --------------------------------------------------------

--
-- Table structure for table `forum_topics_subscriptions`
--

CREATE TABLE `forum_topics_subscriptions` (
  `sub_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `emails` tinyint(1) NOT NULL DEFAULT '1',
  `send_email` tinyint(1) NOT NULL DEFAULT '1',
  `secret_key` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `forum_topics_subscriptions`
--

-- --------------------------------------------------------

--
-- Table structure for table `game_genres`
--

CREATE TABLE `game_genres` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `accepted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `game_genres`
--

-- --------------------------------------------------------

--
-- Table structure for table `game_genres_reference`
--

CREATE TABLE `game_genres_reference` (
  `id` int(10) UNSIGNED NOT NULL,
  `game_id` int(10) UNSIGNED NOT NULL,
  `genre_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `game_genres_reference`
--

INSERT INTO `game_genres_reference` (`id`, `game_id`, `genre_id`) VALUES
(1, 575, 3),
(2, 574, 5);

-- --------------------------------------------------------

--
-- Table structure for table `game_giveaways`
--

CREATE TABLE `game_giveaways` (
  `id` int(10) UNSIGNED NOT NULL,
  `game_name` text CHARACTER SET utf8 NOT NULL,
  `date_created` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `game_giveaways`
--

-- --------------------------------------------------------

--
-- Table structure for table `game_giveaways_keys`
--

CREATE TABLE `game_giveaways_keys` (
  `id` int(10) UNSIGNED NOT NULL,
  `game_id` int(10) UNSIGNED NOT NULL,
  `game_key` text NOT NULL,
  `claimed` tinyint(1) NOT NULL DEFAULT '0',
  `claimed_by_id` int(10) UNSIGNED DEFAULT NULL,
  `claimed_date` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `game_giveaways_keys`
--

-- --------------------------------------------------------

--
-- Table structure for table `game_servers`
--

CREATE TABLE `game_servers` (
  `id` int(10) UNSIGNED NOT NULL,
  `game_id` int(10) UNSIGNED NOT NULL,
  `connection_info` text NOT NULL,
  `official` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Stand-in structure for view `getWordsUsedLastMonth`
-- (See below for the actual view)
--
CREATE TABLE `getWordsUsedLastMonth` (
`characters` decimal(31,0)
,`words` decimal(33,0)
);

-- --------------------------------------------------------

--
-- Table structure for table `goty_category`
--

CREATE TABLE `goty_category` (
  `category_id` int(11) NOT NULL,
  `category_name` text NOT NULL,
  `description` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `goty_games`
--

CREATE TABLE `goty_games` (
  `id` int(11) UNSIGNED NOT NULL,
  `game` text CHARACTER SET utf8mb4 NOT NULL,
  `votes` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `accepted` tinyint(1) NOT NULL DEFAULT '0',
  `category_id` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `accepted_by` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `goty_votes`
--

CREATE TABLE `goty_votes` (
  `id` int(11) UNSIGNED NOT NULL,
  `game_id` int(11) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `category_id` int(11) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `group_permissions`
--

CREATE TABLE `group_permissions` (
  `id` int(11) NOT NULL,
  `group` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8_bin NOT NULL,
  `value` tinyint(1) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `group_permissions`
--

INSERT INTO `group_permissions` (`id`, `group`, `name`, `value`) VALUES
(1, 1, 'access_admin', 1),
(2, 2, 'access_admin', 1),
(3, 3, 'access_admin', 0),
(4, 4, 'access_admin', 0),
(5, 1, 'comment_on_articles', 1),
(6, 2, 'comment_on_articles', 1),
(7, 3, 'comment_on_articles', 1),
(8, 4, 'comment_on_articles', 1),
(9, 1, 'article_comments_captcha', 0),
(10, 2, 'article_comments_captcha', 0),
(11, 3, 'article_comments_captcha', 0),
(12, 4, 'article_comments_captcha', 1),
(13, 5, 'access_admin', 1),
(14, 5, 'comment_on_articles', 1),
(15, 5, 'article_comments_captcha', 0),
(16, 1, 'skip_submission_queue', 1),
(17, 2, 'skip_submission_queue', 1),
(18, 3, 'skip_submission_queue', 0),
(19, 4, 'skip_submission_queue', 0),
(20, 5, 'skip_submission_queue', 1),
(21, 6, 'access_admin', 0),
(22, 6, 'article_comments_captcha', 0),
(23, 6, 'skip_submission_queue', 0),
(24, 6, 'comment_on_articles', 1),
(25, 1, 'contact_captcha', 0),
(26, 2, 'contact_captcha', 0),
(27, 3, 'contact_captcha', 0),
(28, 4, 'contact_captcha', 1),
(29, 5, 'contact_captcha', 0),
(30, 6, 'contact_captcha', 0),
(31, 1, 'submit_article_captcha', 0),
(32, 2, 'submit_article_captcha', 0),
(33, 3, 'submit_article_captcha', 0),
(34, 4, 'submit_article_captcha', 1),
(35, 5, 'submit_article_captcha', 0),
(36, 6, 'submit_article_captcha', 0);

-- --------------------------------------------------------

--
-- Table structure for table `ipbans`
--

CREATE TABLE `ipbans` (
  `id` int(11) NOT NULL,
  `ip` text NOT NULL,
  `ban_date` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `likes`
--

CREATE TABLE `likes` (
  `like_id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `data_id` int(11) UNSIGNED NOT NULL,
  `date` int(11) DEFAULT NULL,
  `type` text
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `likes`
--

-- --------------------------------------------------------

--
-- Table structure for table `livestreams`
--

CREATE TABLE `livestreams` (
  `row_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `title` text NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `community_stream` tinyint(1) DEFAULT '0',
  `streamer_community_name` text,
  `stream_url` text,
  `accepted` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `livestreams`
--

-- --------------------------------------------------------

--
-- Table structure for table `livestream_presenters`
--

CREATE TABLE `livestream_presenters` (
  `id` int(11) NOT NULL,
  `livestream_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `livestream_presenters`
--

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `module_id` int(11) NOT NULL,
  `module_file_name` varchar(32) COLLATE utf8_bin NOT NULL,
  `activated` int(1) NOT NULL,
  `nice_title` text CHARACTER SET utf8,
  `nice_link` text COLLATE utf8_bin,
  `sections_link` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `modules`
--

INSERT INTO `modules` (`module_id`, `module_file_name`, `activated`, `nice_title`, `nice_link`, `sections_link`) VALUES
(1, 'home', 1, 'Home', NULL, 0),
(2, 'login', 1, 'Login', NULL, 0),
(3, 'register', 1, 'Register', NULL, 0),
(4, 'articles', 1, 'Articles', NULL, 0),
(5, 'articles_full', 1, 'Articles Full', NULL, 0),
(17, 'search', 1, 'Search', NULL, 0),
(7, 'forum', 1, 'Forum', NULL, 0),
(8, 'viewforum', 1, 'View Forum', NULL, 0),
(9, 'newtopic', 1, 'New Topic', NULL, 0),
(10, 'viewtopic', 1, 'View Topic', NULL, 0),
(11, 'newreply', 1, 'New Reply', NULL, 0),
(12, 'profile', 1, 'Profile', NULL, 0),
(13, 'editpost', 1, 'Edit Post', NULL, 0),
(14, 'contact', 1, 'Contact Us', NULL, 0),
(15, 'messages', 1, 'Private Messages', NULL, 0),
(16, 'support_us', 1, 'Support Us', 'support-us/', 1),
(21, 'email_us', 1, 'Email Us', NULL, 0),
(23, 'about_us', 1, 'About Us', NULL, 0),
(25, 'comments_latest', 1, 'Latest Comments', NULL, 0),
(26, 'search_forum', 1, 'Search Forum', NULL, 0),
(27, 'account_links', 1, 'Account Links', NULL, 0),
(28, 'rules', 1, 'Rules', NULL, 0),
(30, 'guidelines', 1, 'Article Guidelines', NULL, 0),
(31, 'activate_user', 1, 'Activate User', NULL, 0),
(32, 'calendar', 1, 'Release Calendar', NULL, 1),
(35, 'submit_article', 1, 'Submit Article', NULL, 0),
(36, 'statistics', 1, 'Statistics', 'users/statistics', 1),
(38, 'game', 1, 'Games Database', NULL, 1),
(39, 'report_post', 1, 'Report Post', NULL, 0),
(40, 'game-search', 1, 'Game Search', NULL, 0),
(41, 'unlike_all', 1, 'Unlike All', NULL, 0),
(42, 'livestreams', 1, 'Livestreams', NULL, 1),
(43, 'website_stats', 1, 'Website Stats', NULL, 0),
(44, 'video', 1, 'Video Directory', NULL, 0),
(45, 'game_servers', 1, 'Game Servers', NULL, 1),
(46, 'irc', 1, 'IRC', NULL, 0),
(47, '404', 1, '404', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `online_list`
--

CREATE TABLE `online_list` (
  `user_id` int(11) NOT NULL,
  `session_id` text NOT NULL,
  `timestamp` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset`
--

CREATE TABLE `password_reset` (
  `user_email` varchar(50) COLLATE utf8_bin NOT NULL,
  `secret_code` varchar(10) COLLATE utf8_bin NOT NULL,
  `expires` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `plugins`
--

CREATE TABLE `plugins` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `plugins`
--

-- --------------------------------------------------------

--
-- Table structure for table `polls`
--

CREATE TABLE `polls` (
  `poll_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `poll_question` text NOT NULL,
  `topic_id` int(11) NOT NULL,
  `poll_open` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `poll_options`
--

CREATE TABLE `poll_options` (
  `option_id` int(11) NOT NULL,
  `poll_id` int(11) NOT NULL,
  `option_title` text NOT NULL,
  `votes` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `poll_votes`
--

CREATE TABLE `poll_votes` (
  `vote_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `poll_id` int(11) NOT NULL,
  `option_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `saved_sessions`
--

CREATE TABLE `saved_sessions` (
  `user_id` int(11) NOT NULL,
  `session_id` varchar(64) COLLATE utf8_bin NOT NULL,
  `browser_agent` text COLLATE utf8_bin NOT NULL,
  `device-id` text COLLATE utf8_bin NOT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `saved_sessions`
--

-- --------------------------------------------------------

--
-- Table structure for table `trollgame_highscores`
--

CREATE TABLE `trollgame_highscores` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `score` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `usercp_blocks`
--

CREATE TABLE `usercp_blocks` (
  `block_id` int(11) NOT NULL,
  `block_link` varchar(32) COLLATE utf8_bin DEFAULT NULL,
  `block_name` varchar(32) COLLATE utf8_bin NOT NULL,
  `block_title` varchar(32) COLLATE utf8_bin NOT NULL,
  `activated` int(1) NOT NULL DEFAULT '0',
  `left` int(1) NOT NULL DEFAULT '0',
  `right` int(1) NOT NULL DEFAULT '0',
  `block_custom_content` text COLLATE utf8_bin,
  `block_title_link` varchar(255) COLLATE utf8_bin NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `usercp_blocks`
--

INSERT INTO `usercp_blocks` (`block_id`, `block_link`, `block_name`, `block_title`, `activated`, `left`, `right`, `block_custom_content`, `block_title_link`) VALUES
(1, 'block_usercp_menu', 'User Menu', 'User Menu', 1, 1, 0, NULL, '');

-- --------------------------------------------------------

--
-- Table structure for table `usercp_modules`
--

CREATE TABLE `usercp_modules` (
  `module_id` int(11) NOT NULL,
  `module_file_name` varchar(32) COLLATE utf8_bin NOT NULL,
  `module_title` varchar(32) COLLATE utf8_bin NOT NULL,
  `module_link` varchar(255) COLLATE utf8_bin NOT NULL,
  `show_in_sidebar` tinyint(1) NOT NULL,
  `activated` tinyint(1) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

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
(7, 'notifications', 'Notifications', 'usercp.php?module=notifications', 1, 1),
(8, 'notification_preferences', 'Notification Preferences', 'usercp.php?module=notification_preferences', 1, 1),
(9, 'bookmarks', 'Bookmarks', 'usercp.php?module=bookmarks', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `register_date` int(11) NOT NULL,
  `email` varchar(233) COLLATE utf8_bin NOT NULL,
  `password` varchar(255) COLLATE utf8_bin NOT NULL,
  `username` varchar(32) CHARACTER SET utf8 NOT NULL,
  `user_group` int(1) NOT NULL,
  `secondary_user_group` int(11) NOT NULL DEFAULT '0',
  `ip` varchar(255) COLLATE utf8_bin NOT NULL,
  `comment_count` int(11) NOT NULL,
  `avatar` text COLLATE utf8_bin NOT NULL,
  `avatar_uploaded` tinyint(1) NOT NULL,
  `avatar_gravatar` tinyint(1) NOT NULL DEFAULT '0',
  `gravatar_email` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `avatar_gallery` text COLLATE utf8_bin,
  `forum_posts` int(11) NOT NULL DEFAULT '0',
  `steam` varchar(255) COLLATE utf8_bin NOT NULL,
  `article_bio` text COLLATE utf8_bin NOT NULL,
  `twitter_on_profile` varchar(120) COLLATE utf8_bin NOT NULL,
  `banned` tinyint(1) NOT NULL DEFAULT '0',
  `oauth_uid` varchar(200) COLLATE utf8_bin NOT NULL,
  `oauth_provider` varchar(200) COLLATE utf8_bin NOT NULL,
  `twitter_username` varchar(200) COLLATE utf8_bin NOT NULL,
  `last_login` int(11) NOT NULL,
  `website` text COLLATE utf8_bin NOT NULL,
  `auto_subscribe` tinyint(1) NOT NULL DEFAULT '1',
  `auto_subscribe_email` tinyint(1) NOT NULL DEFAULT '0',
  `email_on_pm` tinyint(1) NOT NULL DEFAULT '1',
  `theme` varchar(32) COLLATE utf8_bin NOT NULL DEFAULT 'light',
  `supporter_link` text COLLATE utf8_bin NOT NULL,
  `premium-ends-date` int(11) NOT NULL,
  `hide_developer_status` tinyint(1) NOT NULL DEFAULT '0',
  `youtube` text COLLATE utf8_bin NOT NULL,
  `steam_id` bigint(20) NOT NULL,
  `steam_username` text COLLATE utf8_bin NOT NULL,
  `distro` text COLLATE utf8_bin NOT NULL,
  `public_email` tinyint(1) NOT NULL DEFAULT '0',
  `auto_subscribe_new_article` tinyint(1) NOT NULL DEFAULT '0',
  `google_plus` text COLLATE utf8_bin,
  `facebook` text COLLATE utf8_bin,
  `email_options` int(11) NOT NULL DEFAULT '2',
  `activated` tinyint(1) NOT NULL DEFAULT '0',
  `activation_code` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `twitch` text COLLATE utf8_bin,
  `in_mod_queue` tinyint(1) NOT NULL DEFAULT '1',
  `mod_approved` int(11) NOT NULL DEFAULT '0',
  `login_emails` tinyint(1) NOT NULL DEFAULT '1',
  `pc_info_public` tinyint(1) NOT NULL DEFAULT '1',
  `pc_info_filled` tinyint(1) NOT NULL DEFAULT '0',
  `per-page` int(11) NOT NULL DEFAULT '10',
  `articles-per-page` int(11) NOT NULL DEFAULT '15',
  `forum_type` varchar(15) COLLATE utf8_bin NOT NULL DEFAULT 'normal_forum',
  `single_article_page` tinyint(1) NOT NULL DEFAULT '0',
  `submission_emails` tinyint(1) NOT NULL DEFAULT '0',
  `game_developer` tinyint(1) NOT NULL DEFAULT '0',
  `display_comment_alerts` tinyint(1) NOT NULL DEFAULT '1',
  `timezone` text COLLATE utf8_bin,
  `google_id` text COLLATE utf8_bin,
  `google_email` text COLLATE utf8_bin
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `users`
--

-- --------------------------------------------------------

--
-- Table structure for table `user_bookmarks`
--

CREATE TABLE `user_bookmarks` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` text NOT NULL,
  `data_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `user_bookmarks`
--

-- --------------------------------------------------------

--
-- Table structure for table `user_conversations_info`
--

CREATE TABLE `user_conversations_info` (
  `conversation_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `creation_date` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `last_reply_date` int(11) NOT NULL,
  `replies` int(11) NOT NULL,
  `last_reply_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `user_conversations_info`
--

-- --------------------------------------------------------

--
-- Table structure for table `user_conversations_messages`
--

CREATE TABLE `user_conversations_messages` (
  `message_id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `creation_date` int(11) NOT NULL,
  `message` text NOT NULL,
  `position` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `user_conversations_messages`
--

-- --------------------------------------------------------

--
-- Table structure for table `user_conversations_participants`
--

CREATE TABLE `user_conversations_participants` (
  `conversation_id` int(11) NOT NULL,
  `participant_id` int(11) NOT NULL,
  `unread` tinyint(1) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `user_conversations_participants`
--

-- --------------------------------------------------------

--
-- Table structure for table `user_groups`
--

CREATE TABLE `user_groups` (
  `group_id` int(11) NOT NULL,
  `group_name` varchar(50) CHARACTER SET utf8 NOT NULL,
  `show_badge` tinyint(1) NOT NULL DEFAULT '0',
  `badge_text` text COLLATE utf8_bin,
  `badge_colour` text COLLATE utf8_bin
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

--
-- Dumping data for table `user_groups`
--

INSERT INTO `user_groups` (`group_id`, `group_name`, `show_badge`, `badge_text`, `badge_colour`) VALUES
(1, 'Admin', 1, 'Admin', 'red'),
(2, 'Editor', 1, 'Editor', 'pale-green'),
(3, 'Member', 0, NULL, NULL),
(4, 'Guest', 0, NULL, NULL),
(5, 'Contributing Editor', 1, 'Contributing Editor', 'pale-green'),
(6, 'Supporter', 1, 'Supporter', 'orange');

-- --------------------------------------------------------

--
-- Table structure for table `user_notifications`
--

CREATE TABLE `user_notifications` (
  `id` int(11) UNSIGNED NOT NULL,
  `date` int(11) DEFAULT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `notifier_id` int(11) DEFAULT NULL,
  `article_id` int(11) DEFAULT NULL,
  `comment_id` int(11) DEFAULT NULL,
  `seen` tinyint(1) NOT NULL DEFAULT '0',
  `seen_date` int(11) DEFAULT NULL,
  `is_like` tinyint(1) NOT NULL DEFAULT '0',
  `is_quote` tinyint(1) NOT NULL DEFAULT '0',
  `total` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user_notifications`
--

-- --------------------------------------------------------

--
-- Table structure for table `user_profile_info`
--

CREATE TABLE `user_profile_info` (
  `user_id` int(11) NOT NULL DEFAULT '0',
  `date_updated` datetime DEFAULT NULL,
  `desktop_environment` text NOT NULL,
  `what_bits` varchar(5) DEFAULT NULL,
  `dual_boot` text,
  `cpu_vendor` varchar(5) DEFAULT NULL,
  `cpu_model` text,
  `gpu_vendor` varchar(6) DEFAULT NULL,
  `gpu_model` text,
  `gpu_driver` text,
  `ram_count` int(11) DEFAULT NULL,
  `monitor_count` int(11) DEFAULT NULL,
  `resolution` varchar(10) DEFAULT NULL,
  `gaming_machine_type` text,
  `gamepad` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user_profile_info`
--

-- --------------------------------------------------------

--
-- Table structure for table `user_stats_charts`
--

CREATE TABLE `user_stats_charts` (
  `id` int(11) NOT NULL,
  `grouping_id` int(11) NOT NULL,
  `name` text NOT NULL,
  `sub_title` text,
  `h_label` text NOT NULL,
  `generated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `total_answers` int(11) DEFAULT NULL,
  `grouped` tinyint(1) NOT NULL DEFAULT '0',
  `enabled` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user_stats_charts`
--

-- --------------------------------------------------------

--
-- Table structure for table `user_stats_charts_data`
--

CREATE TABLE `user_stats_charts_data` (
  `data_id` int(11) NOT NULL,
  `grouping_id` int(11) NOT NULL,
  `chart_id` int(11) NOT NULL,
  `label_id` int(11) NOT NULL,
  `data` int(11) NOT NULL,
  `data_series` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user_stats_charts_data`
--

-- --------------------------------------------------------

--
-- Table structure for table `user_stats_charts_labels`
--

CREATE TABLE `user_stats_charts_labels` (
  `label_id` int(11) NOT NULL,
  `grouping_id` int(11) NOT NULL,
  `chart_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `colour` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user_stats_charts_labels`
--

-- --------------------------------------------------------

--
-- Table structure for table `user_stats_full`
--

CREATE TABLE `user_stats_full` (
  `id` int(11) NOT NULL,
  `grouping_id` int(11) NOT NULL,
  `chart_name` text NOT NULL,
  `label` varchar(100) NOT NULL,
  `total` int(11) NOT NULL,
  `percent` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_stats_grouping`
--

CREATE TABLE `user_stats_grouping` (
  `id` int(11) NOT NULL,
  `grouping_id` int(11) NOT NULL,
  `generated_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user_stats_grouping`
--

-- --------------------------------------------------------

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_blocks`
--
ALTER TABLE `admin_blocks`
  ADD PRIMARY KEY (`block_id`);

--
-- Indexes for table `admin_discussion`
--
ALTER TABLE `admin_discussion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `admin_modules`
--
ALTER TABLE `admin_modules`
  ADD PRIMARY KEY (`module_id`);

--
-- Indexes for table `admin_notes`
--
ALTER TABLE `admin_notes`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `admin_notification_types`
--
ALTER TABLE `admin_notification_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_user_notes`
--
ALTER TABLE `admin_user_notes`
  ADD PRIMARY KEY (`row_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`article_id`),
  ADD KEY `date` (`date`),
  ADD KEY `author_id` (`author_id`);
ALTER TABLE `articles` ADD FULLTEXT KEY `title` (`title`);

--
-- Indexes for table `articles_categorys`
--
ALTER TABLE `articles_categorys`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `articles_comments`
--
ALTER TABLE `articles_comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `last_edited` (`last_edited`);

--
-- Indexes for table `articles_subscriptions`
--
ALTER TABLE `articles_subscriptions`
  ADD PRIMARY KEY (`sub_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `articles_tagline_gallery`
--
ALTER TABLE `articles_tagline_gallery`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `article_category_reference`
--
ALTER TABLE `article_category_reference`
  ADD PRIMARY KEY (`ref_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `article_id` (`article_id`);

--
-- Indexes for table `article_corrections`
--
ALTER TABLE `article_corrections`
  ADD PRIMARY KEY (`row_id`);

--
-- Indexes for table `article_game_assoc`
--
ALTER TABLE `article_game_assoc`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `article_history`
--
ALTER TABLE `article_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `article_images`
--
ALTER TABLE `article_images`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `article_likes`
--
ALTER TABLE `article_likes`
  ADD PRIMARY KEY (`like_id`),
  ADD UNIQUE KEY `like_id` (`like_id`);

--
-- Indexes for table `avatars_gallery`
--
ALTER TABLE `avatars_gallery`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blocks`
--
ALTER TABLE `blocks`
  ADD PRIMARY KEY (`block_id`);

--
-- Indexes for table `calendar`
--
ALTER TABLE `calendar`
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `also_known_as` (`also_known_as`);
ALTER TABLE `calendar` ADD FULLTEXT KEY `name` (`name`);

--
-- Indexes for table `charts`
--
ALTER TABLE `charts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `charts_data`
--
ALTER TABLE `charts_data`
  ADD PRIMARY KEY (`data_id`);

--
-- Indexes for table `charts_labels`
--
ALTER TABLE `charts_labels`
  ADD PRIMARY KEY (`label_id`);

--
-- Indexes for table `config`
--
ALTER TABLE `config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `data_key` (`data_key`);

--
-- Indexes for table `desktop_environments`
--
ALTER TABLE `desktop_environments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `distributions`
--
ALTER TABLE `distributions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `editor_discussion`
--
ALTER TABLE `editor_discussion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

--
-- Indexes for table `editor_picks`
--
ALTER TABLE `editor_picks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `forums`
--
ALTER TABLE `forums`
  ADD PRIMARY KEY (`forum_id`);

--
-- Indexes for table `forum_permissions`
--
ALTER TABLE `forum_permissions`
  ADD KEY `group_id` (`group_id`);

--
-- Indexes for table `forum_replies`
--
ALTER TABLE `forum_replies`
  ADD PRIMARY KEY (`post_id`);

--
-- Indexes for table `forum_topics`
--
ALTER TABLE `forum_topics`
  ADD PRIMARY KEY (`topic_id`);

--
-- Indexes for table `forum_topics_subscriptions`
--
ALTER TABLE `forum_topics_subscriptions`
  ADD PRIMARY KEY (`sub_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `game_genres`
--
ALTER TABLE `game_genres`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `game_genres_reference`
--
ALTER TABLE `game_genres_reference`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `game_giveaways`
--
ALTER TABLE `game_giveaways`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `game_giveaways_keys`
--
ALTER TABLE `game_giveaways_keys`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `game_servers`
--
ALTER TABLE `game_servers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `goty_category`
--
ALTER TABLE `goty_category`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `goty_games`
--
ALTER TABLE `goty_games`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `goty_votes`
--
ALTER TABLE `goty_votes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `group_permissions`
--
ALTER TABLE `group_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group` (`group`);

--
-- Indexes for table `ipbans`
--
ALTER TABLE `ipbans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`like_id`),
  ADD UNIQUE KEY `like_id` (`like_id`);

--
-- Indexes for table `livestreams`
--
ALTER TABLE `livestreams`
  ADD PRIMARY KEY (`row_id`);

--
-- Indexes for table `livestream_presenters`
--
ALTER TABLE `livestream_presenters`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`module_id`);

--
-- Indexes for table `password_reset`
--
ALTER TABLE `password_reset`
  ADD PRIMARY KEY (`user_email`);

--
-- Indexes for table `plugins`
--
ALTER TABLE `plugins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `polls`
--
ALTER TABLE `polls`
  ADD PRIMARY KEY (`poll_id`);

--
-- Indexes for table `poll_options`
--
ALTER TABLE `poll_options`
  ADD PRIMARY KEY (`option_id`);

--
-- Indexes for table `poll_votes`
--
ALTER TABLE `poll_votes`
  ADD PRIMARY KEY (`vote_id`);

--
-- Indexes for table `saved_sessions`
--
ALTER TABLE `saved_sessions`
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `trollgame_highscores`
--
ALTER TABLE `trollgame_highscores`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `usercp_blocks`
--
ALTER TABLE `usercp_blocks`
  ADD PRIMARY KEY (`block_id`);

--
-- Indexes for table `usercp_modules`
--
ALTER TABLE `usercp_modules`
  ADD PRIMARY KEY (`module_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD UNIQUE KEY `user_id_2` (`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_bookmarks`
--
ALTER TABLE `user_bookmarks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_conversations_info`
--
ALTER TABLE `user_conversations_info`
  ADD KEY `conversation_id` (`conversation_id`);

--
-- Indexes for table `user_conversations_messages`
--
ALTER TABLE `user_conversations_messages`
  ADD PRIMARY KEY (`message_id`);

--
-- Indexes for table `user_conversations_participants`
--
ALTER TABLE `user_conversations_participants`
  ADD KEY `conversation_id` (`conversation_id`);

--
-- Indexes for table `user_groups`
--
ALTER TABLE `user_groups`
  ADD PRIMARY KEY (`group_id`);

--
-- Indexes for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_profile_info`
--
ALTER TABLE `user_profile_info`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `user_stats_charts`
--
ALTER TABLE `user_stats_charts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_stats_charts_data`
--
ALTER TABLE `user_stats_charts_data`
  ADD PRIMARY KEY (`data_id`);

--
-- Indexes for table `user_stats_charts_labels`
--
ALTER TABLE `user_stats_charts_labels`
  ADD PRIMARY KEY (`label_id`);

--
-- Indexes for table `user_stats_full`
--
ALTER TABLE `user_stats_full`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_stats_grouping`
--
ALTER TABLE `user_stats_grouping`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_blocks`
--
ALTER TABLE `admin_blocks`
  MODIFY `block_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;
--
-- AUTO_INCREMENT for table `admin_discussion`
--
ALTER TABLE `admin_discussion`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `admin_modules`
--
ALTER TABLE `admin_modules`
  MODIFY `module_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;
--
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `admin_notification_types`
--
ALTER TABLE `admin_notification_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;
--
-- AUTO_INCREMENT for table `admin_user_notes`
--
ALTER TABLE `admin_user_notes`
  MODIFY `row_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `articles`
--
ALTER TABLE `articles`
  MODIFY `article_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `articles_categorys`
--
ALTER TABLE `articles_categorys`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `articles_comments`
--
ALTER TABLE `articles_comments`
  MODIFY `comment_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `articles_subscriptions`
--
ALTER TABLE `articles_subscriptions`
  MODIFY `sub_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `articles_tagline_gallery`
--
ALTER TABLE `articles_tagline_gallery`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `article_category_reference`
--
ALTER TABLE `article_category_reference`
  MODIFY `ref_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `article_corrections`
--
ALTER TABLE `article_corrections`
  MODIFY `row_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `article_game_assoc`
--
ALTER TABLE `article_game_assoc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `article_history`
--
ALTER TABLE `article_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `article_images`
--
ALTER TABLE `article_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `article_likes`
--
ALTER TABLE `article_likes`
  MODIFY `like_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `avatars_gallery`
--
ALTER TABLE `avatars_gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `blocks`
--
ALTER TABLE `blocks`
  MODIFY `block_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;
--
-- AUTO_INCREMENT for table `calendar`
--
ALTER TABLE `calendar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `charts`
--
ALTER TABLE `charts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `charts_data`
--
ALTER TABLE `charts_data`
  MODIFY `data_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `charts_labels`
--
ALTER TABLE `charts_labels`
  MODIFY `label_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `config`
--
ALTER TABLE `config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;
--
-- AUTO_INCREMENT for table `desktop_environments`
--
ALTER TABLE `desktop_environments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `distributions`
--
ALTER TABLE `distributions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `editor_discussion`
--
ALTER TABLE `editor_discussion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `editor_picks`
--
ALTER TABLE `editor_picks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `forums`
--
ALTER TABLE `forums`
  MODIFY `forum_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `forum_replies`
--
ALTER TABLE `forum_replies`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `forum_topics`
--
ALTER TABLE `forum_topics`
  MODIFY `topic_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `forum_topics_subscriptions`
--
ALTER TABLE `forum_topics_subscriptions`
  MODIFY `sub_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `game_genres`
--
ALTER TABLE `game_genres`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `game_genres_reference`
--
ALTER TABLE `game_genres_reference`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `game_giveaways`
--
ALTER TABLE `game_giveaways`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `game_giveaways_keys`
--
ALTER TABLE `game_giveaways_keys`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `game_servers`
--
ALTER TABLE `game_servers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `goty_category`
--
ALTER TABLE `goty_category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `goty_games`
--
ALTER TABLE `goty_games`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `goty_votes`
--
ALTER TABLE `goty_votes`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `group_permissions`
--
ALTER TABLE `group_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;
--
-- AUTO_INCREMENT for table `ipbans`
--
ALTER TABLE `ipbans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `likes`
--
ALTER TABLE `likes`
  MODIFY `like_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `livestreams`
--
ALTER TABLE `livestreams`
  MODIFY `row_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `livestream_presenters`
--
ALTER TABLE `livestream_presenters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `module_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;
--
-- AUTO_INCREMENT for table `plugins`
--
ALTER TABLE `plugins`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `polls`
--
ALTER TABLE `polls`
  MODIFY `poll_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `poll_options`
--
ALTER TABLE `poll_options`
  MODIFY `option_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `poll_votes`
--
ALTER TABLE `poll_votes`
  MODIFY `vote_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `trollgame_highscores`
--
ALTER TABLE `trollgame_highscores`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `usercp_blocks`
--
ALTER TABLE `usercp_blocks`
  MODIFY `block_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `usercp_modules`
--
ALTER TABLE `usercp_modules`
  MODIFY `module_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `user_bookmarks`
--
ALTER TABLE `user_bookmarks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `user_conversations_info`
--
ALTER TABLE `user_conversations_info`
  MODIFY `conversation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `user_conversations_messages`
--
ALTER TABLE `user_conversations_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `user_groups`
--
ALTER TABLE `user_groups`
  MODIFY `group_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
--
-- AUTO_INCREMENT for table `user_notifications`
--
ALTER TABLE `user_notifications`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `user_stats_charts`
--
ALTER TABLE `user_stats_charts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `user_stats_charts_data`
--
ALTER TABLE `user_stats_charts_data`
  MODIFY `data_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `user_stats_charts_labels`
--
ALTER TABLE `user_stats_charts_labels`
  MODIFY `label_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `user_stats_full`
--
ALTER TABLE `user_stats_full`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user_stats_grouping`
--
ALTER TABLE `user_stats_grouping`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
