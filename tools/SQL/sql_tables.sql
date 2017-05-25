-- phpMyAdmin SQL Dump
-- version 4.6.6
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 08, 2017 at 09:18 AM
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
  `show_in_sidebar` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'show a link in the admins main menu, set to 0 if it has a block',
  `activated` tinyint(1) NOT NULL DEFAULT '0',
  `admin_only` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `admin_notes`
--

CREATE TABLE `admin_notes` (
  `user_id` int(11) UNSIGNED NOT NULL,
  `text` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

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

-- --------------------------------------------------------

--
-- Table structure for table `articles_categorys`
--

CREATE TABLE `articles_categorys` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(32) CHARACTER SET utf8 NOT NULL,
  `quick_nav` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `articles_comments`
--

CREATE TABLE `articles_comments` (
  `comment_id` int(11) UNSIGNED NOT NULL,
  `article_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `guest_username` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `time_posted` int(11) NOT NULL,
  `comment_text` text COLLATE utf8_bin NOT NULL,
  `spam` tinyint(1) NOT NULL DEFAULT '0',
  `spam_report_by` int(11) DEFAULT NULL,
  `guest_ip` varchar(100) COLLATE utf8_bin NOT NULL,
  `last_edited` int(11) NOT NULL DEFAULT '0',
  `last_edited_time` int(11) DEFAULT NULL,
  `edit_counter` int(11) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `articles_subscriptions`
--

CREATE TABLE `articles_subscriptions` (
  `sub_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `emails` tinyint(1) NOT NULL DEFAULT '1',
  `send_email` int(1) NOT NULL DEFAULT '1',
  `secret_key` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `articles_tagline_gallery`
--

CREATE TABLE `articles_tagline_gallery` (
  `id` int(10) UNSIGNED NOT NULL,
  `filename` text NOT NULL,
  `name` text NOT NULL,
  `uploader_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `article_category_reference`
--

CREATE TABLE `article_category_reference` (
  `ref_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `article_corrections`
--

CREATE TABLE `article_corrections` (
  `row_id` int(11) NOT NULL,
  `article_id` int(11) DEFAULT NULL,
  `date` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `correction_comment` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `article_game_assoc`
--

CREATE TABLE `article_game_assoc` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `article_history`
--

CREATE TABLE `article_history` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` int(11) NOT NULL,
  `text` text
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------


--
-- Table structure for table `articles_categorys`
--

CREATE TABLE `articles_categorys` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(32) CHARACTER SET utf8 NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `articles_comments`
--

CREATE TABLE `articles_comments` (
  `comment_id` int(11) UNSIGNED NOT NULL,
  `article_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `guest_username` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `time_posted` int(11) NOT NULL,
  `comment_text` text COLLATE utf8_bin NOT NULL,
  `spam` tinyint(1) NOT NULL DEFAULT '0',
  `spam_report_by` int(11) DEFAULT NULL,
  `guest_ip` varchar(100) COLLATE utf8_bin NOT NULL,
  `last_edited` int(11) NOT NULL DEFAULT '0',
  `last_edited_time` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `articles_subscriptions`
--

CREATE TABLE `articles_subscriptions` (
  `user_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `emails` tinyint(1) NOT NULL DEFAULT '1',
  `send_email` int(1) NOT NULL DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `articles_tagline_gallery`
--

CREATE TABLE `articles_tagline_gallery` (
  `id` int(10) UNSIGNED NOT NULL,
  `filename` text NOT NULL,
  `name` text NOT NULL,
  `uploader_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `article_category_reference`
--

CREATE TABLE `article_category_reference` (
  `ref_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `article_corrections`
--

CREATE TABLE `article_corrections` (
  `row_id` int(11) NOT NULL,
  `article_id` int(11) DEFAULT NULL,
  `date` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `correction_comment` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `article_game_assoc`
--

CREATE TABLE `article_game_assoc` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `article_history`
--

CREATE TABLE `article_history` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` int(11) NOT NULL,
  `text` text
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

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

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

CREATE TABLE `config` (
  `id` int(11) NOT NULL,
  `data_key` varchar(50) NOT NULL,
  `data_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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

-- --------------------------------------------------------

--
-- Table structure for table `forum_topics_subscriptions`
--

CREATE TABLE `forum_topics_subscriptions` (
  `user_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `emails` tinyint(1) NOT NULL DEFAULT '1',
  `send_email` tinyint(1) NOT NULL DEFAULT '1',
  `secret_key` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `game_genres`
--

CREATE TABLE `game_genres` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `accepted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `game_genres_reference`
--

CREATE TABLE `game_genres_reference` (
  `id` int(10) UNSIGNED NOT NULL,
  `game_id` int(10) UNSIGNED NOT NULL,
  `genre_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `game_giveaways`
--

CREATE TABLE `game_giveaways` (
  `id` int(10) UNSIGNED NOT NULL,
  `game_name` text CHARACTER SET utf8 NOT NULL,
  `date_created` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
-- Table structure for table `gpu_models`
--

CREATE TABLE `gpu_models` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `vendor` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

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

-- --------------------------------------------------------

--
-- Table structure for table `livestream_presenters`
--

CREATE TABLE `livestream_presenters` (
  `id` int(11) NOT NULL,
  `livestream_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
  `name` text NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(64) COLLATE utf8_bin NOT NULL,
  `browser_agent` text COLLATE utf8_bin NOT NULL,
  `device-id` text COLLATE utf8_bin NOT NULL,
  `date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `trollgame_highscores`
--

CREATE TABLE `trollgame_highscores` (
  `id` int(10) UNSIGNED NOT NULL,
  `score` decimal(50,0) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `register_date` int(11) DEFAULT NULL,
  `email` varchar(233) CHARACTER SET utf8 NOT NULL,
  `password` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `password_salt` text COLLATE utf8_bin NOT NULL,
  `username` varchar(32) CHARACTER SET utf8 NOT NULL,
  `user_group` int(1) NOT NULL DEFAULT '3',
  `secondary_user_group` int(11) NOT NULL DEFAULT '0',
  `ip` varchar(255) COLLATE utf8_bin NOT NULL,
  `comment_count` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `avatar` text COLLATE utf8_bin,
  `avatar_uploaded` tinyint(1) NOT NULL DEFAULT '0',
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
  `last_login` int(11) DEFAULT NULL,
  `website` text COLLATE utf8_bin NOT NULL,
  `auto_subscribe` tinyint(1) NOT NULL DEFAULT '1',
  `auto_subscribe_email` tinyint(1) NOT NULL DEFAULT '0',
  `email_on_pm` tinyint(1) NOT NULL DEFAULT '1',
  `theme` varchar(32) COLLATE utf8_bin NOT NULL DEFAULT 'default',
  `supporter_link` text COLLATE utf8_bin NOT NULL,
  `hide_developer_status` tinyint(1) NOT NULL DEFAULT '0',
  `youtube` text COLLATE utf8_bin NOT NULL,
  `steam_id` bigint(20) DEFAULT NULL,
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
  `google_email` text COLLATE utf8_bin NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

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

-- --------------------------------------------------------

--
-- Table structure for table `user_conversations_participants`
--

CREATE TABLE `user_conversations_participants` (
  `conversation_id` int(11) NOT NULL,
  `participant_id` int(11) NOT NULL,
  `unread` tinyint(1) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_groups`
--

CREATE TABLE `user_groups` (
  `group_id` int(11) NOT NULL,
  `group_name` varchar(50) CHARACTER SET utf8 NOT NULL,
  `show_badge` tinyint(1) NOT NULL DEFAULT '0',
  `badge_text` text COLLATE utf8_bin,
  `badge_colour` text COLLATE utf8_bin,
  `remote_group` tinyint(1) NOT NULL DEFAULT '0',
  `universal` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `user_group_membership`
--

CREATE TABLE `user_group_membership` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `group_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_group_permissions`
--

CREATE TABLE `user_group_permissions` (
  `id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8_bin NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `user_group_permissions_membership`
--

CREATE TABLE `user_group_permissions_membership` (
  `group_id` int(10) UNSIGNED NOT NULL,
  `permission_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_notifications`
--

CREATE TABLE `user_notifications` (
  `id` int(11) UNSIGNED NOT NULL,
  `date` int(11) DEFAULT NULL,
  `owner_id` int(11) UNSIGNED DEFAULT NULL,
  `notifier_id` int(11) UNSIGNED DEFAULT NULL,
  `article_id` int(11) UNSIGNED DEFAULT NULL,
  `comment_id` int(11) UNSIGNED DEFAULT NULL,
  `seen` tinyint(1) NOT NULL DEFAULT '0',
  `seen_date` int(11) DEFAULT NULL,
  `is_like` tinyint(1) NOT NULL DEFAULT '0',
  `is_quote` tinyint(1) NOT NULL DEFAULT '0',
  `total` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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

-- --------------------------------------------------------

--
-- Structure for view `getWordsUsedLastMonth`
--
DROP TABLE IF EXISTS `getWordsUsedLastMonth`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`server.gamingonlinux.com` SQL SECURITY DEFINER VIEW `getWordsUsedLastMonth`  AS  select sum(length(`a`.`text`)) AS `characters`,sum(((length(`a`.`text`) - length(replace(`a`.`text`,' ',''))) + 1)) AS `words` from `articles` `a` where ((month(from_unixtime(`a`.`date`)) = month((now() - interval 1 month))) and (year(from_unixtime(`a`.`date`)) = year(curdate())) and (`a`.`active` = 1)) ;

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
  ADD KEY `date` (`date`);
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
  ADD KEY `author_id` (`author_id`);

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
-- Indexes for table `gpu_models`
--
ALTER TABLE `gpu_models`
  ADD PRIMARY KEY (`id`);

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
  ADD PRIMARY KEY (`id`),
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
-- Indexes for table `user_group_permissions`
--
ALTER TABLE `user_group_permissions`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `block_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `admin_discussion`
--
ALTER TABLE `admin_discussion`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `admin_modules`
--
ALTER TABLE `admin_modules`
  MODIFY `module_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `admin_notification_types`
--
ALTER TABLE `admin_notification_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `admin_user_notes`
--
ALTER TABLE `admin_user_notes`
  MODIFY `row_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `articles`
--
ALTER TABLE `articles`
  MODIFY `article_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `articles_categorys`
--
ALTER TABLE `articles_categorys`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `articles_comments`
--
ALTER TABLE `articles_comments`
  MODIFY `comment_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `articles_subscriptions`
--
ALTER TABLE `articles_subscriptions`
  MODIFY `sub_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `articles_tagline_gallery`
--
ALTER TABLE `articles_tagline_gallery`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `article_category_reference`
--
ALTER TABLE `article_category_reference`
  MODIFY `ref_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `article_corrections`
--
ALTER TABLE `article_corrections`
  MODIFY `row_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `article_game_assoc`
--
ALTER TABLE `article_game_assoc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
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
-- AUTO_INCREMENT for table `article_likes`
--
ALTER TABLE `article_likes`
  MODIFY `like_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `avatars_gallery`
--
ALTER TABLE `avatars_gallery`
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
-- AUTO_INCREMENT for table `desktop_environments`
--
ALTER TABLE `desktop_environments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `distributions`
--
ALTER TABLE `distributions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `editor_discussion`
--
ALTER TABLE `editor_discussion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `editor_picks`
--
ALTER TABLE `editor_picks`
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
-- AUTO_INCREMENT for table `game_genres`
--
ALTER TABLE `game_genres`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `game_genres_reference`
--
ALTER TABLE `game_genres_reference`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `game_giveaways`
--
ALTER TABLE `game_giveaways`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `game_giveaways_keys`
--
ALTER TABLE `game_giveaways_keys`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `game_servers`
--
ALTER TABLE `game_servers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `goty_category`
--
ALTER TABLE `goty_category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;
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
-- AUTO_INCREMENT for table `gpu_models`
--
ALTER TABLE `gpu_models`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `ipbans`
--
ALTER TABLE `ipbans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `likes`
--
ALTER TABLE `likes`
  MODIFY `like_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `livestreams`
--
ALTER TABLE `livestreams`
  MODIFY `row_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `livestream_presenters`
--
ALTER TABLE `livestream_presenters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `module_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `plugins`
--
ALTER TABLE `plugins`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `trollgame_highscores`
--
ALTER TABLE `trollgame_highscores`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
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
-- AUTO_INCREMENT for table `user_bookmarks`
--
ALTER TABLE `user_bookmarks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
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
--
-- AUTO_INCREMENT for table `user_group_permissions`
--
ALTER TABLE `user_group_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user_notifications`
--
ALTER TABLE `user_notifications`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user_stats_charts`
--
ALTER TABLE `user_stats_charts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user_stats_charts_data`
--
ALTER TABLE `user_stats_charts_data`
  MODIFY `data_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user_stats_charts_labels`
--
ALTER TABLE `user_stats_charts_labels`
  MODIFY `label_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user_stats_full`
--
ALTER TABLE `user_stats_full`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user_stats_grouping`
--
ALTER TABLE `user_stats_grouping`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
