-- phpMyAdmin SQL Dump
-- version 4.0.10.14
-- http://www.phpmyadmin.net
--
-- Host: localhost:3306
-- Generation Time: Dec 13, 2016 at 11:04 PM
-- Server version: 5.6.34
-- PHP Version: 5.6.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `gamingonlinux`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_blocks`
--

CREATE TABLE IF NOT EXISTS `admin_blocks` (
  `block_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `block_link` varchar(32) COLLATE utf8_bin DEFAULT NULL,
  `block_name` varchar(32) COLLATE utf8_bin NOT NULL,
  `activated` int(1) NOT NULL,
  `blocks_custom_content` text COLLATE utf8_bin,
  `admin_only` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`block_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=20 ;

-- --------------------------------------------------------

--
-- Table structure for table `admin_discussion`
--

CREATE TABLE IF NOT EXISTS `admin_discussion` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `text` text NOT NULL,
  `date_posted` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=448 ;

-- --------------------------------------------------------

--
-- Table structure for table `admin_modules`
--

CREATE TABLE IF NOT EXISTS `admin_modules` (
  `module_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `module_name` varchar(32) COLLATE utf8_bin NOT NULL,
  `module_title` varchar(32) COLLATE utf8_bin NOT NULL,
  `module_link` text COLLATE utf8_bin,
  `show_in_sidebar` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'show a link in the admins main menu, set to 0 if it has a block',
  `activated` tinyint(1) NOT NULL DEFAULT '0',
  `admin_only` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`module_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=35 ;

-- --------------------------------------------------------

--
-- Table structure for table `admin_notes`
--

CREATE TABLE IF NOT EXISTS `admin_notes` (
  `user_id` int(11) unsigned NOT NULL,
  `text` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `admin_notifications`
--

CREATE TABLE IF NOT EXISTS `admin_notifications` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT '0',
  `created_date` int(11) DEFAULT NULL,
  `completed_date` int(11) DEFAULT NULL,
  `type` text,
  `data` text,
  `content` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=116 ;

-- --------------------------------------------------------

--
-- Table structure for table `admin_user_notes`
--

CREATE TABLE IF NOT EXISTS `admin_user_notes` (
  `row_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `notes` text,
  `last_edited` int(11) DEFAULT NULL,
  `last_edit_by` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`row_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=13 ;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE IF NOT EXISTS `announcements` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `text` text NOT NULL,
  `author_id` int(11) unsigned NOT NULL,
  `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=20 ;

-- --------------------------------------------------------

--
-- Table structure for table `articles`
--

CREATE TABLE IF NOT EXISTS `articles` (
  `article_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `author_id` int(11) NOT NULL,
  `guest_username` varchar(255) DEFAULT NULL,
  `guest_email` varchar(255) DEFAULT NULL,
  `guest_ip` varchar(100) DEFAULT NULL,
  `date` int(11) NOT NULL,
  `date_submitted` int(11) DEFAULT NULL,
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
  `reviewed_by_id` int(11) DEFAULT NULL,
  `submitted_unapproved` tinyint(1) NOT NULL DEFAULT '0',
  `article_top_image` tinyint(1) NOT NULL DEFAULT '0',
  `article_top_image_filename` text,
  `comments_open` tinyint(1) NOT NULL DEFAULT '1',
  `draft` tinyint(1) NOT NULL DEFAULT '0',
  `tagline_image` text,
  `locked` tinyint(1) NOT NULL DEFAULT '0',
  `locked_by` int(11) DEFAULT NULL,
  `locked_date` int(11) DEFAULT NULL,
  `preview_code` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`article_id`),
  KEY `date` (`date`),
  FULLTEXT KEY `title` (`title`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8727 ;

-- --------------------------------------------------------

--
-- Table structure for table `articles_categorys`
--

CREATE TABLE IF NOT EXISTS `articles_categorys` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(32) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=123 ;

-- --------------------------------------------------------

--
-- Table structure for table `articles_comments`
--

CREATE TABLE IF NOT EXISTS `articles_comments` (
  `comment_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
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
  PRIMARY KEY (`comment_id`),
  KEY `author_id` (`author_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=79791 ;

-- --------------------------------------------------------

--
-- Table structure for table `articles_subscriptions`
--

CREATE TABLE IF NOT EXISTS `articles_subscriptions` (
  `user_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `emails` tinyint(1) NOT NULL DEFAULT '1',
  `send_email` int(1) NOT NULL DEFAULT '1',
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `article_category_reference`
--

CREATE TABLE IF NOT EXISTS `article_category_reference` (
  `ref_id` int(11) NOT NULL AUTO_INCREMENT,
  `article_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  PRIMARY KEY (`ref_id`),
  KEY `category_id` (`category_id`),
  KEY `article_id` (`article_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=55310 ;

-- --------------------------------------------------------

--
-- Table structure for table `article_corrections`
--

CREATE TABLE IF NOT EXISTS `article_corrections` (
  `row_id` int(11) NOT NULL AUTO_INCREMENT,
  `article_id` int(11) DEFAULT NULL,
  `date` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `correction_comment` text NOT NULL,
  PRIMARY KEY (`row_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=8 ;

-- --------------------------------------------------------

--
-- Table structure for table `article_game_assoc`
--

CREATE TABLE IF NOT EXISTS `article_game_assoc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `article_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=477 ;

-- --------------------------------------------------------

--
-- Table structure for table `article_history`
--

CREATE TABLE IF NOT EXISTS `article_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `article_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5713 ;

-- --------------------------------------------------------

--
-- Table structure for table `article_images`
--

CREATE TABLE IF NOT EXISTS `article_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `article_id` int(11) NOT NULL,
  `filename` text NOT NULL,
  `uploader_id` int(11) NOT NULL,
  `date_uploaded` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2166 ;

-- --------------------------------------------------------

--
-- Table structure for table `article_likes`
--

CREATE TABLE IF NOT EXISTS `article_likes` (
  `like_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `article_id` int(11) unsigned NOT NULL,
  `date` int(11) DEFAULT NULL,
  PRIMARY KEY (`like_id`),
  UNIQUE KEY `like_id` (`like_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=987 ;

-- --------------------------------------------------------

--
-- Table structure for table `avatars_gallery`
--

CREATE TABLE IF NOT EXISTS `avatars_gallery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;

-- --------------------------------------------------------

--
-- Table structure for table `blocks`
--

CREATE TABLE IF NOT EXISTS `blocks` (
  `block_id` int(11) NOT NULL AUTO_INCREMENT,
  `block_link` varchar(32) COLLATE utf8_bin DEFAULT NULL,
  `block_name` varchar(32) COLLATE utf8_bin NOT NULL,
  `block_title` varchar(32) COLLATE utf8_bin NOT NULL,
  `block_title_link` varchar(255) COLLATE utf8_bin NOT NULL,
  `activated` int(1) NOT NULL DEFAULT '0',
  `block_custom_content` text COLLATE utf8_bin,
  `order` int(11) NOT NULL,
  `style` text COLLATE utf8_bin NOT NULL,
  `nonpremium_only` tinyint(1) NOT NULL DEFAULT '0',
  `homepage_only` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`block_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=31 ;

-- --------------------------------------------------------

--
-- Table structure for table `calendar`
--

CREATE TABLE IF NOT EXISTS `calendar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `edit_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `name` text CHARACTER SET utf8 NOT NULL,
  `description` text COLLATE utf8_bin,
  `link` text COLLATE utf8_bin,
  `gog_link` text COLLATE utf8_bin,
  `steam_link` text COLLATE utf8_bin,
  `best_guess` tinyint(1) NOT NULL DEFAULT '0',
  `approved` tinyint(1) NOT NULL DEFAULT '0',
  `is_dlc` tinyint(1) NOT NULL DEFAULT '0',
  `base_game_id` int(11) DEFAULT NULL,
  UNIQUE KEY `id` (`id`),
  FULLTEXT KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=4282 ;

-- --------------------------------------------------------

--
-- Table structure for table `charts`
--

CREATE TABLE IF NOT EXISTS `charts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner` int(11) NOT NULL DEFAULT '0',
  `name` text NOT NULL,
  `h_label` text NOT NULL,
  `generated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_stats_chart` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=14 ;

-- --------------------------------------------------------

--
-- Table structure for table `charts_data`
--

CREATE TABLE IF NOT EXISTS `charts_data` (
  `data_id` int(11) NOT NULL AUTO_INCREMENT,
  `chart_id` int(11) NOT NULL,
  `label_id` int(11) NOT NULL,
  `data` int(11) NOT NULL,
  PRIMARY KEY (`data_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=122 ;

-- --------------------------------------------------------

--
-- Table structure for table `charts_labels`
--

CREATE TABLE IF NOT EXISTS `charts_labels` (
  `label_id` int(11) NOT NULL AUTO_INCREMENT,
  `chart_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`label_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=122 ;

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

CREATE TABLE IF NOT EXISTS `config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data_key` varchar(50) NOT NULL,
  `data_value` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  KEY `data_key` (`data_key`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=49 ;

-- --------------------------------------------------------

--
-- Table structure for table `desktop_environments`
--

CREATE TABLE IF NOT EXISTS `desktop_environments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=15 ;

-- --------------------------------------------------------

--
-- Table structure for table `distributions`
--

CREATE TABLE IF NOT EXISTS `distributions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `arch-based` tinyint(1) NOT NULL DEFAULT '0',
  `ubuntu-based` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=27 ;

-- --------------------------------------------------------

--
-- Table structure for table `editor_discussion`
--

CREATE TABLE IF NOT EXISTS `editor_discussion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `text` text NOT NULL,
  `date_posted` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2190 ;

-- --------------------------------------------------------

--
-- Table structure for table `editor_picks`
--

CREATE TABLE IF NOT EXISTS `editor_picks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `article_id` int(11) NOT NULL,
  `featured_image` text COLLATE utf8_bin NOT NULL,
  `hits` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=19 ;

-- --------------------------------------------------------

--
-- Table structure for table `forums`
--

CREATE TABLE IF NOT EXISTS `forums` (
  `forum_id` int(11) NOT NULL AUTO_INCREMENT,
  `is_category` tinyint(1) NOT NULL DEFAULT '0',
  `parent_id` int(11) NOT NULL,
  `name` text COLLATE utf8_bin NOT NULL,
  `description` text COLLATE utf8_bin NOT NULL,
  `last_post_time` int(11) DEFAULT NULL,
  `last_post_user_id` int(11) DEFAULT NULL,
  `last_post_topic_id` int(11) NOT NULL DEFAULT '0',
  `posts` int(11) DEFAULT '0',
  `order` int(11) DEFAULT NULL,
  PRIMARY KEY (`forum_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=21 ;

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
  `can_move` int(1) NOT NULL DEFAULT '0',
  KEY `group_id` (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `forum_replies`
--

CREATE TABLE IF NOT EXISTS `forum_replies` (
  `post_id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `creation_date` int(11) NOT NULL,
  `reply_text` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `guest_username` varchar(255) NOT NULL,
  `reported` tinyint(1) NOT NULL DEFAULT '0',
  `reported_by_id` int(11) NOT NULL,
  PRIMARY KEY (`post_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9159 ;

-- --------------------------------------------------------

--
-- Table structure for table `forum_topics`
--

CREATE TABLE IF NOT EXISTS `forum_topics` (
  `topic_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `guest_username` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `reported` tinyint(1) NOT NULL DEFAULT '0',
  `reported_by_id` int(11) DEFAULT NULL,
  `approved` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`topic_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=2466 ;

-- --------------------------------------------------------

--
-- Table structure for table `forum_topics_subscriptions`
--

CREATE TABLE IF NOT EXISTS `forum_topics_subscriptions` (
  `user_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `emails` tinyint(1) NOT NULL DEFAULT '1',
  `send_email` tinyint(1) NOT NULL DEFAULT '1',
  KEY `user_id` (`user_id`)
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
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` text NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=15 ;

-- --------------------------------------------------------

--
-- Table structure for table `goty_games`
--

CREATE TABLE IF NOT EXISTS `goty_games` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `game` text NOT NULL,
  `votes` int(11) NOT NULL DEFAULT '0',
  `accepted` tinyint(1) NOT NULL DEFAULT '0',
  `category_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Table structure for table `goty_votes`
--

CREATE TABLE IF NOT EXISTS `goty_votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `game_id` int(11) NOT NULL,
  `ip` varchar(255) NOT NULL,
  `category_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=66 ;

-- --------------------------------------------------------

--
-- Table structure for table `group_permissions`
--

CREATE TABLE IF NOT EXISTS `group_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8_bin NOT NULL,
  `value` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `group` (`group`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=37 ;

-- --------------------------------------------------------

--
-- Table structure for table `ipbans`
--

CREATE TABLE IF NOT EXISTS `ipbans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=666 ;

-- --------------------------------------------------------

--
-- Table structure for table `likes`
--

CREATE TABLE IF NOT EXISTS `likes` (
  `like_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL,
  `date` int(11) DEFAULT NULL,
  PRIMARY KEY (`like_id`),
  UNIQUE KEY `like_id` (`like_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=47360 ;

-- --------------------------------------------------------

--
-- Table structure for table `livestreams`
--

CREATE TABLE IF NOT EXISTS `livestreams` (
  `row_id` int(11) NOT NULL AUTO_INCREMENT,
  `author_id` int(11) NOT NULL,
  `title` text NOT NULL,
  `date_created` datetime DEFAULT NULL,
  `date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `community_stream` tinyint(1) DEFAULT '0',
  `streamer_community_name` text,
  `stream_url` text,
  `accepted` tinyint(1) NOT NULL,
  PRIMARY KEY (`row_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=26 ;

-- --------------------------------------------------------

--
-- Table structure for table `livestream_presenters`
--

CREATE TABLE IF NOT EXISTS `livestream_presenters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `livestream_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=23 ;

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE IF NOT EXISTS `modules` (
  `module_id` int(11) NOT NULL AUTO_INCREMENT,
  `module_file_name` varchar(32) COLLATE utf8_bin NOT NULL,
  `activated` int(1) NOT NULL,
  PRIMARY KEY (`module_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=44 ;

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
  `expires` int(11) NOT NULL,
  PRIMARY KEY (`user_email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `polls`
--

CREATE TABLE IF NOT EXISTS `polls` (
  `poll_id` int(11) NOT NULL AUTO_INCREMENT,
  `author_id` int(11) NOT NULL,
  `poll_question` text NOT NULL,
  `topic_id` int(11) NOT NULL,
  `poll_open` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`poll_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=10 ;

-- --------------------------------------------------------

--
-- Table structure for table `poll_options`
--

CREATE TABLE IF NOT EXISTS `poll_options` (
  `option_id` int(11) NOT NULL AUTO_INCREMENT,
  `poll_id` int(11) NOT NULL,
  `option_title` text NOT NULL,
  `votes` int(11) NOT NULL,
  PRIMARY KEY (`option_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=35 ;

-- --------------------------------------------------------

--
-- Table structure for table `poll_votes`
--

CREATE TABLE IF NOT EXISTS `poll_votes` (
  `vote_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `poll_id` int(11) NOT NULL,
  `option_id` int(11) NOT NULL,
  PRIMARY KEY (`vote_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=54 ;

-- --------------------------------------------------------

--
-- Table structure for table `saved_sessions`
--

CREATE TABLE IF NOT EXISTS `saved_sessions` (
  `user_id` int(11) NOT NULL,
  `session_id` varchar(64) COLLATE utf8_bin NOT NULL,
  `browser_agent` text COLLATE utf8_bin NOT NULL,
  `device-id` text COLLATE utf8_bin NOT NULL,
  `date` date NOT NULL,
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `usercp_blocks`
--

CREATE TABLE IF NOT EXISTS `usercp_blocks` (
  `block_id` int(11) NOT NULL AUTO_INCREMENT,
  `block_link` varchar(32) COLLATE utf8_bin DEFAULT NULL,
  `block_name` varchar(32) COLLATE utf8_bin NOT NULL,
  `block_title` varchar(32) COLLATE utf8_bin NOT NULL,
  `activated` int(1) NOT NULL DEFAULT '0',
  `left` int(1) NOT NULL DEFAULT '0',
  `right` int(1) NOT NULL DEFAULT '0',
  `block_custom_content` text COLLATE utf8_bin,
  `block_title_link` varchar(255) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`block_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Table structure for table `usercp_modules`
--

CREATE TABLE IF NOT EXISTS `usercp_modules` (
  `module_id` int(11) NOT NULL AUTO_INCREMENT,
  `module_file_name` varchar(32) COLLATE utf8_bin NOT NULL,
  `module_title` varchar(32) COLLATE utf8_bin NOT NULL,
  `module_link` varchar(255) COLLATE utf8_bin NOT NULL,
  `show_in_sidebar` tinyint(1) NOT NULL,
  `activated` tinyint(1) NOT NULL,
  PRIMARY KEY (`module_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=11 ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `register_date` int(11) NOT NULL,
  `email` varchar(233) COLLATE utf8_bin NOT NULL,
  `password` varchar(255) COLLATE utf8_bin NOT NULL,
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
  UNIQUE KEY `user_id_2` (`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=5373 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_conversations_info`
--

CREATE TABLE IF NOT EXISTS `user_conversations_info` (
  `conversation_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `creation_date` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `last_reply_date` int(11) NOT NULL,
  `replies` int(11) NOT NULL,
  `last_reply_id` int(11) NOT NULL,
  KEY `conversation_id` (`conversation_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1092 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_conversations_messages`
--

CREATE TABLE IF NOT EXISTS `user_conversations_messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `conversation_id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `creation_date` int(11) NOT NULL,
  `message` text NOT NULL,
  `position` int(11) NOT NULL,
  PRIMARY KEY (`message_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4995 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_conversations_participants`
--

CREATE TABLE IF NOT EXISTS `user_conversations_participants` (
  `conversation_id` int(11) NOT NULL,
  `participant_id` int(11) NOT NULL,
  `unread` tinyint(1) NOT NULL,
  KEY `conversation_id` (`conversation_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_groups`
--

CREATE TABLE IF NOT EXISTS `user_groups` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(50) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`group_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=8 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_notifications`
--

CREATE TABLE IF NOT EXISTS `user_notifications` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `date` int(11) DEFAULT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `notifier_id` int(11) DEFAULT NULL,
  `article_id` int(11) DEFAULT NULL,
  `comment_id` int(11) DEFAULT NULL,
  `seen` tinyint(1) NOT NULL DEFAULT '0',
  `seen_date` int(11) DEFAULT NULL,
  `is_like` tinyint(1) NOT NULL DEFAULT '0',
  `total` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6698 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_profile_info`
--

CREATE TABLE IF NOT EXISTS `user_profile_info` (
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
  `gamepad` text,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `user_stats_charts`
--

CREATE TABLE IF NOT EXISTS `user_stats_charts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grouping_id` int(11) NOT NULL,
  `name` text NOT NULL,
  `h_label` text NOT NULL,
  `generated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `total_answers` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=124 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_stats_charts_data`
--

CREATE TABLE IF NOT EXISTS `user_stats_charts_data` (
  `data_id` int(11) NOT NULL AUTO_INCREMENT,
  `grouping_id` int(11) NOT NULL,
  `chart_id` int(11) NOT NULL,
  `label_id` int(11) NOT NULL,
  `data` int(11) NOT NULL,
  PRIMARY KEY (`data_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=819 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_stats_charts_labels`
--

CREATE TABLE IF NOT EXISTS `user_stats_charts_labels` (
  `label_id` int(11) NOT NULL AUTO_INCREMENT,
  `grouping_id` int(11) NOT NULL,
  `chart_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`label_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=819 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_stats_full`
--

CREATE TABLE IF NOT EXISTS `user_stats_full` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grouping_id` int(11) NOT NULL,
  `chart_name` text NOT NULL,
  `label` varchar(100) NOT NULL,
  `total` int(11) NOT NULL,
  `percent` decimal(5,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_stats_grouping`
--

CREATE TABLE IF NOT EXISTS `user_stats_grouping` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grouping_id` int(11) NOT NULL,
  `generated_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

-- --------------------------------------------------------

--
-- Structure for view `getWordsUsedLastMonth`
--
DROP TABLE IF EXISTS `getWordsUsedLastMonth`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`server.gamingonlinux.com` SQL SECURITY DEFINER VIEW `getWordsUsedLastMonth` AS select sum(length(`a`.`text`)) AS `characters`,sum(((length(`a`.`text`) - length(replace(`a`.`text`,' ',''))) + 1)) AS `words` from `articles` `a` where ((month(from_unixtime(`a`.`date`)) = month((now() - interval 1 month))) and (year(from_unixtime(`a`.`date`)) = year(curdate())) and (`a`.`active` = 1));

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
