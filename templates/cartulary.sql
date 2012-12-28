-- phpMyAdmin SQL Dump
-- version 3.4.10.1deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Dec 28, 2012 at 06:16 PM
-- Server version: 5.5.24
-- PHP Version: 5.3.10-1ubuntu3.4

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `cartulary`
--

-- --------------------------------------------------------

--
-- Table structure for table `adminlog`
--

CREATE TABLE IF NOT EXISTS `adminlog` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'Unique id for this post.',
  `url` varchar(255) NOT NULL COMMENT 'The url for this post.',
  `content` text CHARACTER SET utf8 NOT NULL COMMENT 'The post content.',
  `createdon` varchar(64) NOT NULL COMMENT 'Unix epoch of when the post was created.',
  `title` varchar(255) NOT NULL COMMENT 'The title of the post.',
  `enclosure` blob NOT NULL COMMENT 'Holds an array of enclosures.',
  `sourceurl` varchar(1024) NOT NULL COMMENT 'The url of the source feed',
  `sourcetitle` varchar(255) NOT NULL COMMENT 'The title of the source feed',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='The main article vault.' AUTO_INCREMENT=58 ;

-- --------------------------------------------------------

--
-- Table structure for table `articles`
--

CREATE TABLE IF NOT EXISTS `articles` (
  `id` varchar(128) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'Unique id for article.',
  `url` varchar(767) NOT NULL COMMENT 'Original url of the article.',
  `shorturl` varchar(255) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `content` mediumtext CHARACTER SET utf8 NOT NULL COMMENT 'The sanitized article content.',
  `analysis` mediumtext CHARACTER SET utf8 NOT NULL COMMENT 'An array of words occuring in the content.',
  `lastviewed` varchar(64) NOT NULL COMMENT 'Unix epoch of last time article was viewed..',
  `contenthash` varchar(128) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'A hash of the content so we don''t duplicate.',
  `createdon` varchar(64) NOT NULL COMMENT 'Unix epoch of when the article was created.',
  `title` varchar(255) NOT NULL COMMENT 'The title of the article.',
  `enclosure` blob NOT NULL,
  `sourceurl` varchar(1024) NOT NULL,
  `sourcetitle` varchar(255) NOT NULL,
  PRIMARY KEY (`url`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='The main article vault.';

-- --------------------------------------------------------

--
-- Table structure for table `catalog`
--

CREATE TABLE IF NOT EXISTS `catalog` (
  `userid` varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'The user this article belongs to.',
  `articleid` varchar(128) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'The id of the article.',
  `public` tinyint(4) NOT NULL COMMENT 'Is this article public?',
  `linkedon` varchar(64) NOT NULL COMMENT 'Unix epoch of when link was made.',
  PRIMARY KEY (`userid`,`articleid`),
  KEY `articleid` (`articleid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Ties articles to users.';

-- --------------------------------------------------------

--
-- Table structure for table `feedstats`
--

CREATE TABLE IF NOT EXISTS `feedstats` (
  `id` varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `checkcount` bigint(20) NOT NULL DEFAULT '0',
  `checktime` bigint(20) NOT NULL DEFAULT '0' COMMENT 'Total time spent checking this feed.',
  `avgchecktime` int(11) NOT NULL DEFAULT '0',
  `redirectsto` varchar(1024) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `pingsperday` int(11) NOT NULL DEFAULT '0',
  `avgnewitems` int(11) NOT NULL DEFAULT '0' COMMENT 'Average number of new items per check.',
  `newitems` bigint(20) NOT NULL DEFAULT '0' COMMENT 'Total number of new items for this feed.',
  `avgnewinterval` int(11) NOT NULL DEFAULT '0' COMMENT 'Average number of checks between new items appearing.',
  `lastnewtime` varchar(64) NOT NULL COMMENT 'The last time this feed had a new item.',
  `subscribers` int(11) NOT NULL DEFAULT '0' COMMENT 'A count of how many subscribe to this feed.',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Records statistical info about feeds.';

-- --------------------------------------------------------

--
-- Table structure for table `flags`
--

CREATE TABLE IF NOT EXISTS `flags` (
  `name` varchar(32) NOT NULL,
  `value` int(11) NOT NULL,
  `timeset` varchar(64) NOT NULL,
  `setby` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Holds system flags for operational states.';

-- --------------------------------------------------------

--
-- Table structure for table `listfeeds`
--

CREATE TABLE IF NOT EXISTS `listfeeds` (
  `listid` varchar(128) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `feedid` varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `linkedon` varchar(64) NOT NULL,
  UNIQUE KEY `listid_2` (`listid`,`feedid`),
  KEY `listid` (`listid`),
  KEY `feedid` (`feedid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `mbcatalog`
--

CREATE TABLE IF NOT EXISTS `mbcatalog` (
  `userid` varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'The user this post belongs to.',
  `postid` varchar(128) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'The id of the post.',
  `public` tinyint(4) NOT NULL COMMENT 'Is this post public?',
  `linkedon` varchar(64) NOT NULL COMMENT 'Unix epoch of when link was made.',
  PRIMARY KEY (`userid`,`postid`),
  KEY `postid` (`postid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Ties articles to users.';

-- --------------------------------------------------------

--
-- Table structure for table `microblog`
--

CREATE TABLE IF NOT EXISTS `microblog` (
  `id` varchar(128) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'Unique id for this post.',
  `url` varchar(255) NOT NULL COMMENT 'The url for this post.',
  `content` text CHARACTER SET utf8 NOT NULL COMMENT 'The post content.',
  `analysis` longtext CHARACTER SET utf8 NOT NULL COMMENT 'An array of words occuring in the content.',
  `lastviewed` varchar(64) NOT NULL COMMENT 'Unix epoch of last time post was viewed..',
  `contenthash` varchar(128) NOT NULL COMMENT 'A hash of the content so we don''t duplicate.',
  `createdon` varchar(64) NOT NULL COMMENT 'Unix epoch of when the post was created.',
  `title` varchar(255) NOT NULL COMMENT 'The title of the post.',
  `shorturl` varchar(64) NOT NULL COMMENT 'A shortened url for this post.',
  `enclosure` blob NOT NULL COMMENT 'Holds an array of enclosures.',
  `sourceurl` varchar(1024) NOT NULL COMMENT 'The url of the source feed',
  `sourcetitle` varchar(255) NOT NULL COMMENT 'The title of the source feed',
  `twitter` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='The main article vault.';

-- --------------------------------------------------------

--
-- Table structure for table `newsfeeds`
--

CREATE TABLE IF NOT EXISTS `newsfeeds` (
  `id` varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `url` varchar(767) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `lastcheck` varchar(64) NOT NULL,
  `lastupdate` varchar(64) NOT NULL,
  `lastmod` varchar(64) NOT NULL,
  `createdon` varchar(64) NOT NULL,
  `content` mediumtext NOT NULL,
  `title` varchar(255) NOT NULL,
  `link` varchar(255) NOT NULL,
  `oid` varchar(128) NOT NULL COMMENT 'Outline id this feed ties to.',
  `errors` int(11) NOT NULL COMMENT 'Error count during parsing.',
  `rsscloudregurl` varchar(1024) NOT NULL COMMENT 'Url to send rssCloud registration to.',
  `rsscloudlastreg` varchar(64) NOT NULL COMMENT 'Last time a registration was done.',
  `rsscloudreglastresp` varchar(1024) NOT NULL COMMENT 'Last registration response.',
  `updated` tinyint(4) NOT NULL COMMENT 'A flag on whether the feed needs refreshing.',
  `lastitemid` varchar(128) NOT NULL COMMENT 'The most current item id.',
  `pubdate` varchar(64) NOT NULL COMMENT 'The date given by the feed itself.',
  `avatarurl` varchar(1024) NOT NULL COMMENT 'Link to an avatar for this feed.',
  PRIMARY KEY (`id`),
  KEY `createdon` (`createdon`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Holds all the feeds being checked by users.';

-- --------------------------------------------------------

--
-- Table structure for table `nfcatalog`
--

CREATE TABLE IF NOT EXISTS `nfcatalog` (
  `userid` varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `feedid` varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `outlineid` varchar(128) CHARACTER SET latin1 COLLATE latin1_bin DEFAULT NULL COMMENT 'Is this an outline link?',
  `linkedon` varchar(64) NOT NULL,
  `outlinecolor` varchar(8) NOT NULL COMMENT 'What color to give this outline?',
  `purge` int(11) NOT NULL COMMENT 'Purge this feed link?',
  `sticky` tinyint(4) NOT NULL COMMENT 'Whether items from this feed should be sticky',
  `hidden` tinyint(4) NOT NULL COMMENT 'Hide this feed for this user.',
  PRIMARY KEY (`userid`,`feedid`),
  KEY `outlineid` (`outlineid`),
  KEY `feedid` (`feedid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Maps feeds to users.';

-- --------------------------------------------------------

--
-- Table structure for table `nfitemprops`
--

CREATE TABLE IF NOT EXISTS `nfitemprops` (
  `itemid` varchar(128) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `userid` varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `sticky` tinyint(4) NOT NULL,
  `hidden` tinyint(4) NOT NULL COMMENT 'Is item hidden?',
  PRIMARY KEY (`itemid`,`userid`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='User level attributes for individual news items.';

-- --------------------------------------------------------

--
-- Table structure for table `nfitems`
--

CREATE TABLE IF NOT EXISTS `nfitems` (
  `id` varchar(128) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `feedid` varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `title` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `guid` varchar(767) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL COMMENT 'A unique id for this item.',
  `timestamp` varchar(64) NOT NULL,
  `timeadded` varchar(64) NOT NULL,
  `enclosure` blob NOT NULL COMMENT 'Any enclosure as a php object.',
  `purge` int(11) NOT NULL COMMENT 'Is this item marked for purging?',
  `sourceurl` varchar(1024) NOT NULL COMMENT 'The url of the source feed',
  `sourcetitle` varchar(255) NOT NULL COMMENT 'The title of the source feed',
  `old` tinyint(4) NOT NULL COMMENT 'Item no longer appears in the feed.',
  `sticky` tinyint(4) NOT NULL COMMENT 'Is this item sticky in the river?',
  `author` varchar(255) NOT NULL,
  PRIMARY KEY (`feedid`,`guid`),
  KEY `timeadded` (`timeadded`),
  KEY `feedid` (`feedid`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Holds all the news feed items.';

-- --------------------------------------------------------

--
-- Table structure for table `prefs`
--

CREATE TABLE IF NOT EXISTS `prefs` (
  `uid` varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'User id these prefs apply to.',
  `linkblog` varchar(255) NOT NULL COMMENT 'User''s external linkblog url.',
  `publicdefault` tinyint(4) NOT NULL COMMENT 'Should articles be public by default?',
  `publicrss` tinyint(4) NOT NULL COMMENT 'Is rss feed public?',
  `publicopml` tinyint(4) NOT NULL COMMENT 'Is opml feed public?',
  `sourceurlrt` tinyint(4) NOT NULL COMMENT 'Use source url in rt''s?',
  `sourceurlrss` tinyint(4) NOT NULL COMMENT 'Use source url in rss?',
  `stylesheet` varchar(255) NOT NULL COMMENT 'Url of external stylesheet.',
  `maxlist` int(11) NOT NULL COMMENT 'Max number of entries to show by default.',
  `s3key` varchar(255) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'User''s amazon key.',
  `s3secret` varchar(255) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'User''s amazon secret.',
  `s3bucket` varchar(255) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'The s3 bucket to store feed in.',
  `s3cname` varchar(255) NOT NULL,
  `twitterkey` varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'Twitter app key.',
  `twittersecret` varchar(128) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'Twitter app secret.',
  `twittertoken` varchar(128) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `twittertokensecret` varchar(128) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `urlshortener` varchar(255) NOT NULL,
  `avatarurl` varchar(1024) NOT NULL COMMENT 'Url of a picture of the user.',
  `riverheadlinecart` tinyint(4) NOT NULL COMMENT 'Make headlines cartulize?',
  `homepagelink` varchar(1024) NOT NULL COMMENT 'Link to users home page.',
  `s3shortbucket` varchar(255) NOT NULL COMMENT 'Bucket to hold short url files.',
  `lastshortcode` varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL DEFAULT '1' COMMENT 'The last short url code used.',
  `shortcart` tinyint(4) NOT NULL COMMENT 'Shorten article urls?',
  `riverhours` int(11) NOT NULL DEFAULT '6' COMMENT 'Range of hours to display in river',
  `tweetcart` tinyint(4) NOT NULL COMMENT 'Tweet cartulized articles?',
  `microblogtitle` varchar(255) NOT NULL COMMENT 'User defined blog title.',
  `cartularytitle` varchar(255) NOT NULL COMMENT 'User defined cartulary title.',
  `mbfilename` varchar(255) NOT NULL,
  `cartfilename` varchar(255) NOT NULL,
  `mobilehidebigpics` tinyint(4) NOT NULL DEFAULT '1',
  `mbarchivecss` varchar(1024) NOT NULL,
  `mobilehidepics` tinyint(4) NOT NULL DEFAULT '0',
  `mblinkhome` tinyint(4) NOT NULL,
  `mbreturnhome` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'Go to home page after blog post.',
  `maxriversize` int(11) NOT NULL DEFAULT '200' COMMENT 'Maximum number of river items.',
  `maxriversizemobile` int(11) NOT NULL DEFAULT '50' COMMENT 'Maximum number of river items on mobile.',
  `timezone` varchar(64) NOT NULL DEFAULT 'UTC' COMMENT 'Timezone this user is in.',
  `fulltextriver` tinyint(11) NOT NULL DEFAULT '0' COMMENT 'Show entire item text in river.',
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Holds prefs for each user.';

-- --------------------------------------------------------

--
-- Table structure for table `rivers`
--

CREATE TABLE IF NOT EXISTS `rivers` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `userid` varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `lastbuild` varchar(32) NOT NULL,
  `river` mediumblob NOT NULL,
  `conthash` varchar(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'A has of the content for change checking.',
  `firstid` varchar(128) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'Id of most recent river item.',
  `updated` tinyint(4) NOT NULL COMMENT 'A flag on whether the river needs refreshing.',
  `mriver` mediumblob NOT NULL COMMENT 'Mobile version of the river array.',
  PRIMARY KEY (`userid`),
  KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Holds pre-built river arrays for each user.' AUTO_INCREMENT=162369 ;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(128) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `userid` varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `lastactivity` varchar(64) NOT NULL COMMENT 'Unix epoch of last session activity.',
  `created` varchar(64) NOT NULL COMMENT 'Unix epoch of when the session started.',
  `firstsourceip` varchar(128) NOT NULL,
  `lastsourceip` varchar(128) NOT NULL,
  `firstbrowser` varchar(128) NOT NULL,
  `lastbrowser` varchar(128) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sopmlcatalog`
--

CREATE TABLE IF NOT EXISTS `sopmlcatalog` (
  `uid` varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'User id.',
  `oid` varchar(128) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'Outline id.',
  `linkedon` varchar(64) NOT NULL COMMENT 'Time the subscription was made.',
  `color` varchar(8) NOT NULL COMMENT 'Hex color value for outline.',
  PRIMARY KEY (`uid`,`oid`),
  KEY `oid` (`oid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sopmlfeeds`
--

CREATE TABLE IF NOT EXISTS `sopmlfeeds` (
  `userid` varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `url` varchar(700) NOT NULL,
  `linkedon` varchar(64) NOT NULL,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`userid`,`url`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Feeds being published by social outlines.';

-- --------------------------------------------------------

--
-- Table structure for table `sopmlitems`
--

CREATE TABLE IF NOT EXISTS `sopmlitems` (
  `id` varchar(128) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'A unique id for this outline item.',
  `content` longtext NOT NULL COMMENT 'The content of the outline item.',
  `attributes` varchar(1024) NOT NULL COMMENT 'Any attributes on this item.',
  `oid` varchar(128) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'The owning outline''s id.',
  `timeadded` varchar(64) NOT NULL COMMENT 'Time item was added.',
  `conthash` varchar(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'A hash of the item content.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `oid` (`oid`,`conthash`),
  KEY `oid_2` (`oid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Items from outlines.';

-- --------------------------------------------------------

--
-- Table structure for table `sopmlnotify`
--

CREATE TABLE IF NOT EXISTS `sopmlnotify` (
  `id` varchar(128) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'A unique id for this registration.',
  `host` varchar(32) NOT NULL COMMENT 'Hostname to ping.',
  `url` varchar(255) NOT NULL COMMENT 'Url to ping.',
  `guid` varchar(128) NOT NULL COMMENT 'The graph to monitor.',
  `lastping` varchar(64) NOT NULL COMMENT 'Timestamp of last successful ping.',
  `failures` bigint(20) NOT NULL DEFAULT '0' COMMENT 'Number of consecutive ping failures.',
  `key` varchar(64) NOT NULL COMMENT 'A verification code.',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='A list of hosts requesting change notifications.';

-- --------------------------------------------------------

--
-- Table structure for table `sopmloutlines`
--

CREATE TABLE IF NOT EXISTS `sopmloutlines` (
  `id` varchar(128) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'A unique id for this outline.',
  `title` varchar(1024) NOT NULL COMMENT 'Title of the outline.',
  `url` varchar(1024) NOT NULL COMMENT 'The canonical url of the outline.',
  `type` varchar(12) NOT NULL COMMENT 'Type of outline this is.',
  `content` longtext NOT NULL COMMENT 'The content of the outline.',
  `lastcheck` varchar(64) NOT NULL COMMENT 'Last time url was checked.',
  `lastupdate` varchar(64) NOT NULL COMMENT 'Last time the feed was checked.',
  `lastmod` varchar(64) NOT NULL COMMENT 'The last-modified time of the url.',
  `avatarurl` varchar(1024) NOT NULL COMMENT 'Avatar pic if sopml',
  `ownername` varchar(128) NOT NULL COMMENT 'The ownerName of outline.',
  `ownerid` varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'User id of outline''s owner.',
  `control` varchar(12) NOT NULL COMMENT 'Where is the locus of control.',
  `filename` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='A list of opml content subscribed to by users.';

-- --------------------------------------------------------

--
-- Table structure for table `strings`
--

CREATE TABLE IF NOT EXISTS `strings` (
  `type` int(11) NOT NULL COMMENT 'What kind of message is it?',
  `id` int(11) NOT NULL COMMENT 'The unique message id.',
  `message` varchar(255) NOT NULL COMMENT 'The text of the message.',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='A list of commonly used character strings.';


INSERT INTO `strings` (`type`, `id`, `message`) VALUES
(2, 0, 'You can''t have a blank password or username.'),
(2, 1, 'No article id given in the request.  Cartulize failure?'),
(2, 2, 'That user and password isn''t valid.'),
(2, 4, 'Could not create the session.'),
(2, 6, 'That user and password isn''t valid.'),
(2, 8, 'Too many bad login attempts.  You must wait 5 minutes to login again.'),
(2, 13, 'You are not allowed to view that article.'),
(2, 30, 'The username or password wasn''t a sane length.');

-- --------------------------------------------------------

--
-- Table structure for table `sysprefs`
--

CREATE TABLE IF NOT EXISTS `sysprefs` (
  `system_name` varchar(255) NOT NULL COMMENT 'How the system refers to itself.',
  `system_url` varchar(255) NOT NULL COMMENT 'The system''s external url.',
  `default_style_sheet` varchar(255) NOT NULL COMMENT 'Url of external stylesheet.',
  `s3key` varchar(255) NOT NULL COMMENT 'User''s amazon key.',
  `s3secret` varchar(255) NOT NULL COMMENT 'User''s amazon secret.',
  `s3bucket` varchar(255) NOT NULL COMMENT 'The s3 bucket to store feed in.',
  `s3cname` varchar(255) NOT NULL,
  `twitterkey` varchar(64) NOT NULL COMMENT 'Twitter app key.',
  `twittersecret` varchar(128) NOT NULL COMMENT 'Twitter app secret.',
  `twittertoken` varchar(128) NOT NULL,
  `twittertokensecret` varchar(128) NOT NULL,
  `urlshortener` varchar(255) NOT NULL,
  `default_avatar_url` varchar(1024) NOT NULL COMMENT 'Url of a picture of the user.',
  `s3shortbucket` varchar(255) NOT NULL COMMENT 'Bucket to hold short url files.',
  `lastshortcode` varchar(64) NOT NULL DEFAULT '1' COMMENT 'The last short url code used.',
  `riverhours` int(11) NOT NULL DEFAULT '6' COMMENT 'Range of hours to display in river',
  `microblogtitle` varchar(255) NOT NULL COMMENT 'User defined blog title.',
  `cartularytitle` varchar(255) NOT NULL COMMENT 'User defined cartulary title.',
  `mbfilename` varchar(255) NOT NULL,
  `cartfilename` varchar(255) NOT NULL,
  PRIMARY KEY (`system_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Holds prefs for each user.';

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'Unique id for each user.',
  `name` varchar(128) NOT NULL COMMENT 'Users display name.',
  `password` varchar(128) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'Password to log in with.',
  `email` varchar(128) NOT NULL COMMENT 'Users email.',
  `lastlogin` varchar(64) NOT NULL COMMENT 'Unix epoch of last login time.',
  `browser` varchar(128) NOT NULL COMMENT 'User agent string of browser.',
  `lastsourceip` varchar(128) NOT NULL COMMENT 'Source IP addr of last login.',
  `admin` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'Is this user an admin?',
  `lastsessionid` varchar(128) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL COMMENT 'Last valid session id.',
  `active` tinyint(4) NOT NULL COMMENT 'Is user disabled?',
  `badlogins` int(11) NOT NULL DEFAULT '0' COMMENT 'How many failed login tries?',
  `inside` tinyint(4) NOT NULL COMMENT 'Is this an inside user?',
  `stage` int(11) NOT NULL COMMENT 'What step in the setup wizard are we?',
  `lastpasschange` bigint(20) NOT NULL COMMENT 'Time of last password change.',
  `username` varchar(64) NOT NULL COMMENT 'An optional username.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='List of users.';

-- --------------------------------------------------------

--
-- Table structure for table `userstats`
--

CREATE TABLE IF NOT EXISTS `userstats` (
  `userid` varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `feedid` varchar(64) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `clicks` bigint(20) NOT NULL,
  KEY `userid` (`userid`),
  KEY `feedid` (`feedid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Tracks things that happen to feeds of users.';

--
-- Constraints for dumped tables
--

--
-- Constraints for table `catalog`
--
ALTER TABLE `catalog`
  ADD CONSTRAINT `catalog_ibfk_2` FOREIGN KEY (`articleid`) REFERENCES `articles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `catalog_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `feedstats`
--
ALTER TABLE `feedstats`
  ADD CONSTRAINT `feedstats_ibfk_1` FOREIGN KEY (`id`) REFERENCES `newsfeeds` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `listfeeds`
--
ALTER TABLE `listfeeds`
  ADD CONSTRAINT `listfeeds_ibfk_2` FOREIGN KEY (`feedid`) REFERENCES `newsfeeds` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `listfeeds_ibfk_1` FOREIGN KEY (`listid`) REFERENCES `sopmloutlines` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `mbcatalog`
--
ALTER TABLE `mbcatalog`
  ADD CONSTRAINT `mbcatalog_ibfk_2` FOREIGN KEY (`postid`) REFERENCES `microblog` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `mbcatalog_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `nfcatalog`
--
ALTER TABLE `nfcatalog`
  ADD CONSTRAINT `nfcatalog_ibfk_3` FOREIGN KEY (`outlineid`) REFERENCES `sopmloutlines` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `nfcatalog_ibfk_1` FOREIGN KEY (`feedid`) REFERENCES `newsfeeds` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `nfcatalog_ibfk_2` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `nfitemprops`
--
ALTER TABLE `nfitemprops`
  ADD CONSTRAINT `nfitemprops_ibfk_2` FOREIGN KEY (`itemid`) REFERENCES `nfitems` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `nfitemprops_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `nfitems`
--
ALTER TABLE `nfitems`
  ADD CONSTRAINT `nfitems_ibfk_1` FOREIGN KEY (`feedid`) REFERENCES `newsfeeds` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `prefs`
--
ALTER TABLE `prefs`
  ADD CONSTRAINT `prefs_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rivers`
--
ALTER TABLE `rivers`
  ADD CONSTRAINT `rivers_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sopmlcatalog`
--
ALTER TABLE `sopmlcatalog`
  ADD CONSTRAINT `sopmlcatalog_ibfk_2` FOREIGN KEY (`oid`) REFERENCES `sopmloutlines` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `sopmlcatalog_ibfk_1` FOREIGN KEY (`uid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sopmlfeeds`
--
ALTER TABLE `sopmlfeeds`
  ADD CONSTRAINT `sopmlfeeds_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sopmlitems`
--
ALTER TABLE `sopmlitems`
  ADD CONSTRAINT `sopmlitems_ibfk_1` FOREIGN KEY (`oid`) REFERENCES `sopmloutlines` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sopmlnotify`
--
ALTER TABLE `sopmlnotify`
  ADD CONSTRAINT `sopmlnotify_ibfk_1` FOREIGN KEY (`id`) REFERENCES `sopmloutlines` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `userstats`
--
ALTER TABLE `userstats`
  ADD CONSTRAINT `userstats_ibfk_2` FOREIGN KEY (`feedid`) REFERENCES `newsfeeds` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `userstats_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
