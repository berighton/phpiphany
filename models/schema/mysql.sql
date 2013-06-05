--
-- phpiphany database schema
--

-- --------------------------------------------------------
-- --------------------------------------------------------


SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------

--
-- Access Control
--

DROP TABLE IF EXISTS `pip_access`;
CREATE TABLE `pip_access` (
  `user_guid` varchar(12) COLLATE utf8_unicode_ci DEFAULT NULL,
  `group_guid` varchar(12) COLLATE utf8_unicode_ci DEFAULT NULL,
  `object_guid` varchar(12) COLLATE utf8_unicode_ci DEFAULT NULL,
  KEY `user_guid` (`user_guid`),
  KEY `group_guid` (`group_guid`),
  KEY `object_guid` (`object_guid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Config
--

DROP TABLE IF EXISTS `pip_config`;
CREATE TABLE `pip_config` (
  `name` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`name`),
  KEY `value` (`value`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Entities
--

DROP TABLE IF EXISTS `pip_entities`;
CREATE TABLE `pip_entities` (
  `guid` varchar(12) COLLATE utf8_unicode_ci NOT NULL,
  `type` enum('object','user','group') COLLATE utf8_unicode_ci NOT NULL,
  `subtype` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `owner_guid` varchar(12) COLLATE utf8_unicode_ci NOT NULL,
  `active` enum('yes','no') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'yes',
  `archived` enum('yes','no') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  `updated` datetime DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`guid`),
  KEY `type` (`type`),
  KEY `subtype` (`subtype`),
  KEY `owner_guid` (`owner_guid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Entity Subtypes
--

DROP TABLE IF EXISTS `pip_entity_subtypes`;
CREATE TABLE `pip_entity_subtypes` (
  `type` enum('object','user','group') COLLATE utf8_unicode_ci NOT NULL,
  `subtype` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `class` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`subtype`),
  UNIQUE KEY `type` (`type`,`subtype`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `pip_entity_subtypes`
--

INSERT INTO `pip_entity_subtypes` (`type`, `subtype`, `class`) VALUES
('user', 'admin', ''),
('group', 'admins', ''),
('object', 'file', ''),
('group', 'general', ''),
('user', 'guest', '');

-- --------------------------------------------------------

--
-- Uploaded and system generated files
--

DROP TABLE IF EXISTS `pip_files`;
CREATE TABLE `pip_files` (
  `guid` varchar(12) COLLATE utf8_unicode_ci NOT NULL,
  `filename` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `original_name` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `mime_type` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `extension` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL,
  `size` int(11) DEFAULT '0',
  `path` text COLLATE utf8_unicode_ci NOT NULL,
  `download_counter` int(10) DEFAULT '0',
  PRIMARY KEY (`guid`),
  KEY `filename` (`filename`(50)),
  KEY `path` (`path`(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Groups
--

DROP TABLE IF EXISTS `pip_groups`;
CREATE TABLE `pip_groups` (
  `guid` varchar(12) COLLATE utf8_unicode_ci NOT NULL,
  `parent_guid` varchar(12) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` text COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `access` enum('Public','Private') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Private',
  PRIMARY KEY (`guid`),
  KEY `name` (`name`(50)),
  KEY `description` (`description`(50)),
  KEY `parent_guid` (`parent_guid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- System maintenance mode
--

DROP TABLE IF EXISTS `pip_maintenance`;
CREATE TABLE `pip_maintenance` (
  `type` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  `msg` text COLLATE utf8_unicode_ci NOT NULL,
  `reason` varchar(250) COLLATE utf8_unicode_ci DEFAULT '',
  `complete_in` mediumint(5) DEFAULT NULL COMMENT 'value in minutes',
  `creator_guid` varchar(12) COLLATE utf8_unicode_ci NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `message` (`msg`(100)),
  KEY `creator` (`creator_guid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- User group memberships
--

DROP TABLE IF EXISTS `pip_memberships`;
CREATE TABLE `pip_memberships` (
  `user_guid` varchar(12) COLLATE utf8_unicode_ci NOT NULL,
  `group_guid` varchar(12) COLLATE utf8_unicode_ci NOT NULL,
  UNIQUE KEY `relationship` (`user_guid`,`group_guid`),
  KEY `group_guid` (`group_guid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Objects
--

DROP TABLE IF EXISTS `pip_objects`;
CREATE TABLE `pip_objects` (
  `guid` varchar(12) COLLATE utf8_unicode_ci NOT NULL,
  `group_guid` varchar(12) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` text COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`guid`),
  KEY `name` (`name`(50)),
  KEY `description` (`description`(50)),
  KEY `group_guid` (`group_guid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Modular plugins
--

DROP TABLE IF EXISTS `pip_plugins`;
CREATE TABLE `pip_plugins` (
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `version` varchar(25) COLLATE utf8_unicode_ci DEFAULT NULL,
  `author` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `path` text COLLATE utf8_unicode_ci,
  `active` enum('yes','no') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'yes',
  `group_guid` varchar(12) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_installed` datetime DEFAULT NULL,
  PRIMARY KEY (`name`),
  KEY `description` (`description`(50)),
  KEY `group_guid` (`group_guid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- News Reel (Feeds)
--

DROP TABLE IF EXISTS `pip_reel`;
CREATE TABLE `pip_reel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_guid` varchar(12) COLLATE utf8_unicode_ci DEFAULT NULL,
  `action` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `object_guid` varchar(12) COLLATE utf8_unicode_ci DEFAULT NULL,
  `link` text COLLATE utf8_unicode_ci NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `subject_guid` (`subject_guid`),
  KEY `action` (`action`),
  KEY `object_guid` (`object_guid`),
  KEY `created` (`created`),
  KEY `sub_ob_guids` (`subject_guid`,`object_guid`),
  KEY `ob_sub_guids` (`object_guid`,`subject_guid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Users
--

DROP TABLE IF EXISTS `pip_users`;
CREATE TABLE `pip_users` (
  `guid` varchar(12) COLLATE utf8_unicode_ci NOT NULL,
  `fname` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `lname` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `username` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `salt` varchar(16) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `language` varchar(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'en',
  `code` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `admin` enum('yes','no') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  `last_login` datetime DEFAULT NULL,
  `prev_last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`guid`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `fullname` (`fname`,`lname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------


--
-- Constraints for dumped tables
--

--
-- Constraints for table `pip_entities`
--
ALTER TABLE `pip_entities`
  ADD CONSTRAINT `entity_subtype` FOREIGN KEY (`subtype`) REFERENCES `pip_entity_subtypes` (`subtype`) ON UPDATE CASCADE;

--
-- Constraints for table `pip_memberships`
--
ALTER TABLE `pip_memberships`
  ADD CONSTRAINT `relation_user_guid` FOREIGN KEY (`user_guid`) REFERENCES `pip_entities` (`guid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `relation_group_guid` FOREIGN KEY (`group_guid`) REFERENCES `pip_entities` (`guid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pip_groups`
--
ALTER TABLE `pip_groups`
  ADD CONSTRAINT `group_guid` FOREIGN KEY (`guid`) REFERENCES `pip_entities` (`guid`) ON UPDATE CASCADE,
  ADD CONSTRAINT `parent_group_guid` FOREIGN KEY (`parent_guid`) REFERENCES `pip_entities` (`guid`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `pip_objects`
--
ALTER TABLE `pip_objects`
  ADD CONSTRAINT `object_guid` FOREIGN KEY (`guid`) REFERENCES `pip_entities` (`guid`) ON UPDATE CASCADE,
  ADD CONSTRAINT `owner_group_guid` FOREIGN KEY (`group_guid`) REFERENCES `pip_groups` (`guid`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `pip_plugins`
--
ALTER TABLE `pip_plugins`
  ADD CONSTRAINT `plugin_access_group` FOREIGN KEY (`group_guid`) REFERENCES `pip_groups` (`guid`) ON UPDATE CASCADE;

--
-- Constraints for table `pip_reel`
--
ALTER TABLE `pip_reel`
  ADD CONSTRAINT `reel_object_guid` FOREIGN KEY (`object_guid`) REFERENCES `pip_entities` (`guid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `reel_subject_guid` FOREIGN KEY (`subject_guid`) REFERENCES `pip_entities` (`guid`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pip_files`
--
ALTER TABLE `pip_files`
  ADD CONSTRAINT `upload_guid` FOREIGN KEY (`guid`) REFERENCES `pip_entities` (`guid`) ON UPDATE CASCADE;

--
-- Constraints for table `pip_users`
--
ALTER TABLE `pip_users`
  ADD CONSTRAINT `user_guid` FOREIGN KEY (`guid`) REFERENCES `pip_entities` (`guid`) ON UPDATE CASCADE;
