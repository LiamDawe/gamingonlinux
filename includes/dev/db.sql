-- Generation Time: Mar 12, 2016 at 11:53 PM
-- Server version: 10.1.7-MariaDB
-- PHP Version: 5.5.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `goltest`
--
CREATE DATABASE IF NOT EXISTS `goltest` DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
USE `goltest`;

-- --------------------------------------------------------

--
-- Table structure for table `admin_blocks`
--

CREATE TABLE IF NOT EXISTS `admin_blocks` (
  `block_id` int(11) NOT NULL,
  `block_link` varchar(32) COLLATE utf8_bin DEFAULT NULL,
  `block_name` varchar(32) COLLATE utf8_bin NOT NULL,
  `activated` int(1) NOT NULL,
  `blocks_custom_content` text COLLATE utf8_bin,
  `admin_only` tinyint(1) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

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
(17, 'sales', 'sales', 1, NULL, 0),
(5, 'calendar', 'calendar', 1, NULL, 0),
(8, 'goty', 'goty', 1, NULL, 0),
(4, 'featured', 'featured', 1, NULL, 0),
(2, 'mod_queue', 'Mod Queue', 1, NULL, 0),
(18, 'charts', 'charts', 1, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `admin_discussion`
--

CREATE TABLE IF NOT EXISTS `admin_discussion` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `text` text NOT NULL,
  `date_posted` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `admin_modules`
--

CREATE TABLE IF NOT EXISTS `admin_modules` (
  `module_id` int(11) NOT NULL,
  `module_name` varchar(32) COLLATE utf8_bin NOT NULL,
  `module_title` varchar(32) COLLATE utf8_bin NOT NULL,
  `module_link` varchar(32) COLLATE utf8_bin DEFAULT NULL,
  `show_in_sidebar` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'show a link in the admins main menu, set to 0 if it has a block',
  `activated` tinyint(1) NOT NULL DEFAULT '0',
  `admin_only` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `admin_notes`
--

CREATE TABLE IF NOT EXISTS `admin_notes` (
  `user_id` int(11) NOT NULL,
  `text` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `admin_notifications`
--

CREATE TABLE IF NOT EXISTS `admin_notifications` (
  `id` int(11) NOT NULL,
  `action` text NOT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT '0',
  `created` int(11) NOT NULL,
  `completed_date` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL,
  `reported_comment` tinyint(1) NOT NULL DEFAULT '0',
  `sale_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `reply_id` int(11) NOT NULL,
  `goty_game_id` int(11) NOT NULL,
  `calendar_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `mod_queue` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE IF NOT EXISTS `announcements` (
  `id` int(11) NOT NULL,
  `text` text NOT NULL,
  `author_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `articles`
--

CREATE TABLE IF NOT EXISTS `articles` (
  `article_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `guest_username` varchar(255) NOT NULL,
  `guest_email` varchar(255) DEFAULT NULL,
  `guest_ip` varchar(100) NOT NULL,
  `date` int(11) NOT NULL,
  `date_submitted` int(11) NOT NULL,
  `title` varchar(120) NOT NULL,
  `slug` text NOT NULL,
  `tagline` text NOT NULL,
  `text` text NOT NULL,
  `comment_count` int(11) NOT NULL DEFAULT '0',
  `active` int(1) NOT NULL DEFAULT '1',
  `show_in_menu` tinyint(1) NOT NULL DEFAULT '0',
  `views` int(11) NOT NULL DEFAULT '0',
  `submitted_article` tinyint(1) NOT NULL DEFAULT '0',
  `admin_review` tinyint(1) NOT NULL DEFAULT '0',
  `reviewed_by_id` int(11) NOT NULL,
  `submitted_unapproved` tinyint(1) NOT NULL DEFAULT '0',
  `article_top_image` tinyint(1) NOT NULL DEFAULT '0',
  `article_top_image_filename` text NOT NULL,
  `comments_open` tinyint(1) NOT NULL DEFAULT '1',
  `draft` tinyint(1) NOT NULL DEFAULT '0',
  `tagline_image` text NOT NULL,
  `featured_image` text NOT NULL,
  `locked` tinyint(1) NOT NULL DEFAULT '0',
  `locked_by` int(11) NOT NULL,
  `locked_date` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `articles_categorys`
--

CREATE TABLE IF NOT EXISTS `articles_categorys` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(32) COLLATE utf8_bin NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `articles_comments`
--

CREATE TABLE IF NOT EXISTS `articles_comments` (
  `comment_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `guest_username` varchar(255) COLLATE utf8_bin NOT NULL,
  `time_posted` int(11) NOT NULL,
  `comment_text` text COLLATE utf8_bin NOT NULL,
  `spam` tinyint(1) NOT NULL DEFAULT '0',
  `spam_report_by` int(11) NOT NULL,
  `guest_ip` varchar(100) COLLATE utf8_bin NOT NULL,
  `last_edited` int(11) NOT NULL DEFAULT '0',
  `last_edited_time` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `articles_subscriptions`
--

CREATE TABLE IF NOT EXISTS `articles_subscriptions` (
  `user_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `emails` tinyint(1) NOT NULL DEFAULT '1',
  `send_email` int(1) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `article_category_reference`
--

CREATE TABLE IF NOT EXISTS `article_category_reference` (
  `ref_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `article_history`
--

CREATE TABLE IF NOT EXISTS `article_history` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `article_images`
--

CREATE TABLE IF NOT EXISTS `article_images` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `filename` text NOT NULL,
  `uploader_id` int(11) NOT NULL,
  `date_uploaded` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `blocks`
--

CREATE TABLE IF NOT EXISTS `blocks` (
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
(4, 'block_article_categorys', 'Article Categorys', 'Articles', '', 1, NULL, 2, 'block', 0, 0),
(27, 'block_calendar', 'calendar', 'calendar', '', 1, NULL, 0, '', 0, 0),
(11, 'block_twitter', 'Twitter Feed', 'Twitter Feed', '', 1, NULL, 4, 'block', 0, 0),
(23, 'block_forum_latest', 'Latest Forum Posts', 'Latest Forum Posts', '', 1, NULL, 7, 'block', 0, 0),
(14, 'block_comments_latest', 'Latest Comments', 'Latest Comments', '', 1, NULL, 6, 'block', 0, 0),
(21, 'block_facebook', 'Facebook', '', '', 1, NULL, 9, 'block', 0, 0),
(22, 'block_online', 'Online List', '', '', 1, NULL, 10, 'block', 0, 0),
(24, 'block_misc', 'Misc', 'Misc', '', 1, NULL, 11, '', 0, 0),
(28, 'block_sales', 'sales', 'sales', '', 1, NULL, 1, '', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `calendar`
--

CREATE TABLE IF NOT EXISTS `calendar` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `edit_date` date NOT NULL,
  `name` text NOT NULL,
  `comment` text NOT NULL,
  `link` text NOT NULL,
  `best_guess` tinyint(1) NOT NULL DEFAULT '0',
  `approved` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `charts`
--

CREATE TABLE IF NOT EXISTS `charts` (
  `id` int(11) NOT NULL,
  `owner` int(11) NOT NULL,
  `name` text NOT NULL,
  `h_label` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `charts_data`
--

CREATE TABLE IF NOT EXISTS `charts_data` (
  `data_id` int(11) NOT NULL,
  `chart_id` int(11) NOT NULL,
  `label_id` int(11) NOT NULL,
  `data` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `charts_labels`
--

CREATE TABLE IF NOT EXISTS `charts_labels` (
  `label_id` int(11) NOT NULL,
  `chart_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

CREATE TABLE IF NOT EXISTS `config` (
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
(4, 'register_captcha', '1'),
(5, 'guests_captcha_submit_articles', '1'),
(6, 'website_url', 'http://www.gamingonlinux.com/'),
(7, 'articles_rss', '1'),
(8, 'register_off_message', 'Sorry but the admin has disabled user registrations.'),
(9, 'rss_article_limit', '15'),
(10, 'avatar_width', '125'),
(11, 'avatar_height', '125'),
(12, 'recaptcha_secret', ''),
(14, 'total_users', '3962'),
(15, 'contact_email', 'liamdawe@gmail.com'),
(16, 'total_articles', '5035'),
(17, 'article_image_max_width', '550'),
(18, 'article_image_max_height', '250'),
(20, 'tw_consumer_key', ''),
(21, 'tw_consumer_skey', ''),
(22, 'summer_time', '1'),
(23, 'editor_picks_limit', '3'),
(24, 'carousel_image_width', '1905'),
(25, 'carousel_image_height', '440'),
(28, 'send_emails', '1'),
(29, 'rules', '<p>-No pirate links, we do not support piracy, please buy games and support developers.<br />\r\n<br />\r\n-We <strong>do moderate comments</strong> to keep the peace, comments that claim we are censoring you will be removed. If we remove or edit your comment, you probably already know why. It''s our website, and our rules. You are free to discuss it with us in the forum if you feel you have been wronged somehow. We very rarely have to do this, and it''s only usually when people have been told not to continue something.<br />\r\n<br />\r\n-Swearing is okay in small amounts, however, if you keep excessive swearing in comments you can and probably will be banned, but only in extreme cases.<br />\r\n<br />\r\n-Linking into the above, try to remain polite and do not attack other commenter''s or the article author, doing so will result in warnings or bans without a warning.<br />\r\n<br />\r\n-Comments that serve to only antagonise an article author probably will be removed without warning, repeatedly doing so will result in a ban from commenting.<br />\r\n<br />\r\n-Distribution wars that have plagued the Linux community are not welcome here. Bans will occur for people who engage in them or talk down to anyone for their choice.<br />\r\n<br />\r\n-No arguments about the naming of "Linux", we call it Linux, no GNU wars here and that''s not negotiable. Anyone who goes on a GNU crusade will be removed.<br />\r\n<br />\r\n-We will update the rules as we see fit at any time</p>'),
(30, 'pretty_urls', '1'),
(31, 'path', '/home/gamingonlinux/public_html/'),
(33, 'adverts', '1'),
(34, 'goty_games_open', '0'),
(35, 'goty_voting_open', '0'),
(36, 'sales_expiry_lastrun', '1457827202'),
(37, 'goty_page_open', '1'),
(38, 'goty_total_votes', '6946'),
(39, 'goty_finished', '1'),
(40, 'show_debug', '0'),
(41, 'max_tagline_image_filesize', '190900');

-- --------------------------------------------------------

--
-- Table structure for table `editor_discussion`
--

CREATE TABLE IF NOT EXISTS `editor_discussion` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `text` text NOT NULL,
  `date_posted` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `forums`
--

CREATE TABLE IF NOT EXISTS `forums` (
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

-- --------------------------------------------------------

--
-- Table structure for table `forum_permissions`
--

CREATE TABLE IF NOT EXISTS `forum_permissions` (
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

-- --------------------------------------------------------

--
-- Table structure for table `forum_replies`
--

CREATE TABLE IF NOT EXISTS `forum_replies` (
  `post_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `creation_date` int(11) NOT NULL,
  `reply_text` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `guest_username` varchar(255) NOT NULL,
  `reported` tinyint(1) NOT NULL DEFAULT '0',
  `reported_by_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `forum_topics`
--

CREATE TABLE IF NOT EXISTS `forum_topics` (
  `topic_id` int(11) NOT NULL,
  `forum_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `topic_title` text COLLATE utf8_bin NOT NULL,
  `topic_text` text COLLATE utf8_bin NOT NULL,
  `creation_date` int(11) NOT NULL,
  `replys` int(11) NOT NULL DEFAULT '0',
  `views` int(11) NOT NULL DEFAULT '0',
  `is_sticky` tinyint(1) NOT NULL DEFAULT '0',
  `is_locked` tinyint(1) NOT NULL DEFAULT '0',
  `last_post_date` int(11) DEFAULT NULL,
  `last_post_id` int(11) DEFAULT NULL,
  `guest_username` varchar(255) COLLATE utf8_bin NOT NULL,
  `reported` tinyint(1) NOT NULL DEFAULT '0',
  `reported_by_id` int(11) NOT NULL,
  `approved` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `forum_topics_subscriptions`
--

CREATE TABLE IF NOT EXISTS `forum_topics_subscriptions` (
  `user_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `emails` tinyint(1) NOT NULL DEFAULT '1',
  `send_email` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `game_list`
--

CREATE TABLE IF NOT EXISTS `game_list` (
  `local_id` int(11) NOT NULL,
  `name` text COLLATE utf8_bin NOT NULL,
  `bundle` tinyint(1) NOT NULL DEFAULT '0',
  `on_sale` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `game_sales`
--

CREATE TABLE IF NOT EXISTS `game_sales` (
  `id` int(11) NOT NULL,
  `list_id` int(11) NOT NULL,
  `provider_id` int(11) NOT NULL,
  `info` text CHARACTER SET utf8 NOT NULL,
  `website` varchar(255) CHARACTER SET utf8 NOT NULL,
  `date` int(11) NOT NULL,
  `reported` tinyint(1) NOT NULL DEFAULT '0',
  `accepted` tinyint(1) NOT NULL DEFAULT '0',
  `savings` varchar(15) CHARACTER SET utf8 NOT NULL,
  `pwyw` tinyint(1) NOT NULL DEFAULT '0',
  `beat_average` tinyint(1) NOT NULL DEFAULT '0',
  `pre-order` tinyint(1) NOT NULL DEFAULT '0',
  `pounds` decimal(4,2) NOT NULL,
  `pounds_original` decimal(4,2) NOT NULL,
  `dollars` decimal(15,2) NOT NULL,
  `dollars_original` decimal(15,2) NOT NULL,
  `euros` decimal(15,2) NOT NULL,
  `euros_original` decimal(15,2) NOT NULL,
  `has_screenshot` tinyint(1) NOT NULL DEFAULT '0',
  `screenshot_filename` varchar(255) CHARACTER SET utf8 NOT NULL,
  `drmfree` tinyint(1) NOT NULL,
  `pr_key` tinyint(1) NOT NULL DEFAULT '0',
  `steam` tinyint(1) NOT NULL,
  `desura` tinyint(1) NOT NULL,
  `expires` int(11) NOT NULL,
  `submitted_by_id` int(11) NOT NULL,
  `bundle` tinyint(1) NOT NULL DEFAULT '0',
  `imported_image_link` text CHARACTER SET utf8 NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `game_sales_provider`
--

CREATE TABLE IF NOT EXISTS `game_sales_provider` (
  `provider_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Stand-in structure for view `getWordsUsedLastMonth`
--
CREATE TABLE IF NOT EXISTS `getWordsUsedLastMonth` (
`characters` decimal(31,0)
,`words` decimal(33,0)
);

-- --------------------------------------------------------

--
-- Table structure for table `goty_category`
--

CREATE TABLE IF NOT EXISTS `goty_category` (
  `category_id` int(11) NOT NULL,
  `category_name` text NOT NULL,
  `description` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `goty_games`
--

CREATE TABLE IF NOT EXISTS `goty_games` (
  `id` int(11) NOT NULL,
  `game` text NOT NULL,
  `votes` int(11) NOT NULL DEFAULT '0',
  `accepted` tinyint(1) NOT NULL DEFAULT '0',
  `category_id` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `goty_votes`
--

CREATE TABLE IF NOT EXISTS `goty_votes` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `ip` varchar(255) NOT NULL,
  `category_id` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `group_permissions`
--

CREATE TABLE IF NOT EXISTS `group_permissions` (
  `id` int(11) NOT NULL,
  `group` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8_bin NOT NULL,
  `value` tinyint(1) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `ipbans`
--

CREATE TABLE IF NOT EXISTS `ipbans` (
  `id` int(11) NOT NULL,
  `ip` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `likes`
--

CREATE TABLE IF NOT EXISTS `likes` (
  `like_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE IF NOT EXISTS `modules` (
  `module_id` int(11) NOT NULL,
  `module_file_name` varchar(32) COLLATE utf8_bin NOT NULL,
  `activated` int(1) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `online_list`
--

CREATE TABLE IF NOT EXISTS `online_list` (
  `user_id` int(11) NOT NULL,
  `session_id` text NOT NULL,
  `timestamp` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset`
--

CREATE TABLE IF NOT EXISTS `password_reset` (
  `user_email` varchar(50) COLLATE utf8_bin NOT NULL,
  `secret_code` varchar(10) COLLATE utf8_bin NOT NULL,
  `expires` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `polls`
--

CREATE TABLE IF NOT EXISTS `polls` (
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

CREATE TABLE IF NOT EXISTS `poll_options` (
  `option_id` int(11) NOT NULL,
  `poll_id` int(11) NOT NULL,
  `option_title` text NOT NULL,
  `votes` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `poll_votes`
--

CREATE TABLE IF NOT EXISTS `poll_votes` (
  `vote_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `poll_id` int(11) NOT NULL,
  `option_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `saved_sessions`
--

CREATE TABLE IF NOT EXISTS `saved_sessions` (
  `user_id` int(11) NOT NULL,
  `session_id` varchar(64) COLLATE utf8_bin NOT NULL,
  `browser_agent` text COLLATE utf8_bin NOT NULL,
  `device-id` text COLLATE utf8_bin NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `steam_list`
--

CREATE TABLE IF NOT EXISTS `steam_list` (
  `local_id` int(11) NOT NULL,
  `steam_appid` int(11) NOT NULL,
  `name` text NOT NULL,
  `dlc` tinyint(1) NOT NULL DEFAULT '0',
  `bundle` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `usercp_blocks`
--

CREATE TABLE IF NOT EXISTS `usercp_blocks` (
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

CREATE TABLE IF NOT EXISTS `usercp_modules` (
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
(5, 'topic_subscriptions', 'Forum Topic Subscriptions', 'usercp.php?module=topic_subscriptions', 1, 1),
(6, 'article_subscriptions', 'Article Subscriptions', 'usercp.php?module=article_subscriptions', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL,
  `register_date` int(11) NOT NULL,
  `email` varchar(233) COLLATE utf8_bin NOT NULL,
  `password` text COLLATE utf8_bin NOT NULL,
  `password_salt` text COLLATE utf8_bin NOT NULL,
  `username` varchar(32) CHARACTER SET utf8 NOT NULL,
  `user_group` int(1) NOT NULL,
  `secondary_user_group` int(11) NOT NULL DEFAULT '0',
  `ip` varchar(255) COLLATE utf8_bin NOT NULL,
  `comment_count` int(11) NOT NULL,
  `avatar` text COLLATE utf8_bin NOT NULL,
  `avatar_uploaded` tinyint(1) NOT NULL,
  `avatar_gravatar` tinyint(1) NOT NULL DEFAULT '0',
  `gravatar_email` varchar(255) COLLATE utf8_bin DEFAULT NULL,
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
  `google_plus` text COLLATE utf8_bin NOT NULL,
  `facebook` text COLLATE utf8_bin NOT NULL,
  `email_options` int(11) NOT NULL DEFAULT '2',
  `activated` tinyint(1) NOT NULL DEFAULT '0',
  `activation_code` varchar(255) COLLATE utf8_bin NOT NULL,
  `twitch` text COLLATE utf8_bin NOT NULL,
  `in_mod_queue` tinyint(1) NOT NULL DEFAULT '1',
  `mod_approved` int(11) NOT NULL DEFAULT '0',
  `login_emails` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `user_conversations_info`
--

CREATE TABLE IF NOT EXISTS `user_conversations_info` (
  `conversation_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `creation_date` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `last_reply_date` int(11) NOT NULL,
  `replies` int(11) NOT NULL,
  `last_reply_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_conversations_messages`
--

CREATE TABLE IF NOT EXISTS `user_conversations_messages` (
  `message_id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `creation_date` int(11) NOT NULL,
  `message` text NOT NULL,
  `position` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_conversations_participants`
--

CREATE TABLE IF NOT EXISTS `user_conversations_participants` (
  `conversation_id` int(11) NOT NULL,
  `participant_id` int(11) NOT NULL,
  `unread` tinyint(1) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_groups`
--

CREATE TABLE IF NOT EXISTS `user_groups` (
  `group_id` int(11) NOT NULL,
  `group_name` varchar(50) COLLATE utf8_bin NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Structure for view `getWordsUsedLastMonth`
--
DROP TABLE IF EXISTS `getWordsUsedLastMonth`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`server.gamingonlinux.com` SQL SECURITY DEFINER VIEW `getWordsUsedLastMonth` AS select sum(length(`a`.`text`)) AS `characters`,sum(((length(`a`.`text`) - length(replace(`a`.`text`,' ',''))) + 1)) AS `words` from `articles` `a` where ((month(from_unixtime(`a`.`date`)) = month((now() - interval 1 month))) and (year(from_unixtime(`a`.`date`)) = year(curdate())) and (`a`.`active` = 1));

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
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`article_id`),
  ADD FULLTEXT KEY `title` (`title`);

--
-- Indexes for table `articles_categorys`
--
ALTER TABLE `articles_categorys`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `articles_comments`
--
ALTER TABLE `articles_comments`
  ADD PRIMARY KEY (`comment_id`);

--
-- Indexes for table `articles_subscriptions`
--
ALTER TABLE `articles_subscriptions`
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `article_category_reference`
--
ALTER TABLE `article_category_reference`
  ADD PRIMARY KEY (`ref_id`);

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
-- Indexes for table `blocks`
--
ALTER TABLE `blocks`
  ADD PRIMARY KEY (`block_id`);

--
-- Indexes for table `calendar`
--
ALTER TABLE `calendar`
  ADD UNIQUE KEY `id` (`id`);

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
-- Indexes for table `editor_discussion`
--
ALTER TABLE `editor_discussion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`);

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
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `game_list`
--
ALTER TABLE `game_list`
  ADD PRIMARY KEY (`local_id`);

--
-- Indexes for table `game_sales`
--
ALTER TABLE `game_sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `accepted` (`accepted`);

--
-- Indexes for table `game_sales_provider`
--
ALTER TABLE `game_sales_provider`
  ADD PRIMARY KEY (`provider_id`);

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
  ADD PRIMARY KEY (`sid`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `steam_list`
--
ALTER TABLE `steam_list`
  ADD PRIMARY KEY (`local_id`),
  ADD UNIQUE KEY `steam_appid` (`steam_appid`);

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_blocks`
--
ALTER TABLE `admin_blocks`
  MODIFY `block_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `admin_discussion`
--
ALTER TABLE `admin_discussion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `admin_modules`
--
ALTER TABLE `admin_modules`
  MODIFY `module_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `articles`
--
ALTER TABLE `articles`
  MODIFY `article_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `articles_categorys`
--
ALTER TABLE `articles_categorys`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `articles_comments`
--
ALTER TABLE `articles_comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `article_category_reference`
--
ALTER TABLE `article_category_reference`
  MODIFY `ref_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `article_history`
--
ALTER TABLE `article_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `article_images`
--
ALTER TABLE `article_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `blocks`
--
ALTER TABLE `blocks`
  MODIFY `block_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `calendar`
--
ALTER TABLE `calendar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `charts`
--
ALTER TABLE `charts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `charts_data`
--
ALTER TABLE `charts_data`
  MODIFY `data_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `charts_labels`
--
ALTER TABLE `charts_labels`
  MODIFY `label_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `config`
--
ALTER TABLE `config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `editor_discussion`
--
ALTER TABLE `editor_discussion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `forums`
--
ALTER TABLE `forums`
  MODIFY `forum_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `forum_replies`
--
ALTER TABLE `forum_replies`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `forum_topics`
--
ALTER TABLE `forum_topics`
  MODIFY `topic_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `game_list`
--
ALTER TABLE `game_list`
  MODIFY `local_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `game_sales`
--
ALTER TABLE `game_sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `game_sales_provider`
--
ALTER TABLE `game_sales_provider`
  MODIFY `provider_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `goty_category`
--
ALTER TABLE `goty_category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `goty_games`
--
ALTER TABLE `goty_games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `goty_votes`
--
ALTER TABLE `goty_votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `group_permissions`
--
ALTER TABLE `group_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `ipbans`
--
ALTER TABLE `ipbans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `likes`
--
ALTER TABLE `likes`
  MODIFY `like_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `module_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `polls`
--
ALTER TABLE `polls`
  MODIFY `poll_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `poll_options`
--
ALTER TABLE `poll_options`
  MODIFY `option_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `poll_votes`
--
ALTER TABLE `poll_votes`
  MODIFY `vote_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `saved_sessions`
--
ALTER TABLE `saved_sessions`
  MODIFY `sid` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `steam_list`
--
ALTER TABLE `steam_list`
  MODIFY `local_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `usercp_blocks`
--
ALTER TABLE `usercp_blocks`
  MODIFY `block_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `usercp_modules`
--
ALTER TABLE `usercp_modules`
  MODIFY `module_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user_conversations_info`
--
ALTER TABLE `user_conversations_info`
  MODIFY `conversation_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user_conversations_messages`
--
ALTER TABLE `user_conversations_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user_groups`
--
ALTER TABLE `user_groups`
  MODIFY `group_id` int(11) NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
