-- ----------------------------
-- Table structure for `game_list`
-- ----------------------------
DROP TABLE IF EXISTS `game_list`;
CREATE TABLE `game_list` (
	`key` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Don''t need a primary key, just for good practice',
	`game_title` varchar(128) NOT NULL,
	`alternative_title` varchar(128) DEFAULT NULL,
	`status` enum('Playable','Ingame','Intro','Loadable','Nothing') NOT NULL DEFAULT 'Nothing',
	`build_commit` varchar(64) NOT NULL,
	`wiki` int(11) DEFAULT NULL,
	`last_update` date NOT NULL,
	`network` tinyint(1) NOT NULL DEFAULT '0',
	PRIMARY KEY (`key`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for `builds`
-- ----------------------------
DROP TABLE IF EXISTS `builds`;
CREATE TABLE `builds` (
  `pr` int(11) NOT NULL,
  `commit` varchar(64) NOT NULL,
  `version` varchar(64) NOT NULL,
  `author` int(11) NOT NULL,
  `start_datetime` datetime NOT NULL,
  `merge_datetime` datetime NOT NULL,
  `type` varchar(64) NOT NULL,
  `buildjob` varchar(64) DEFAULT NULL,
  `additions` int(11) DEFAULT NULL,
  `deletions` int(11) DEFAULT NULL,
  `changed_files` int(11) DEFAULT NULL,
  `filename_win` varchar(128) DEFAULT NULL,
  `checksum_win` varchar(64) DEFAULT NULL COMMENT 'sha256',
  `size_win` int(11) DEFAULT NULL,
  `filename_linux` varchar(128) DEFAULT NULL,
  `checksum_linux` varchar(64) DEFAULT NULL COMMENT 'sha256',
  `size_linux` int(11) DEFAULT NULL,
  PRIMARY KEY (`pr`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for `initials_cache`
-- ----------------------------
DROP TABLE IF EXISTS `initials_cache`;
CREATE TABLE `initials_cache` (
	`game_title` varchar(250) NOT NULL,
	`initials` varchar(64) NOT NULL,
	PRIMARY KEY (`game_title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for `debug_whitelist`
-- ----------------------------
DROP TABLE IF EXISTS `debug_whitelist`;
CREATE TABLE `debug_whitelist` (
  `token` varchar(255) NOT NULL,
  `permissions` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for `game_history`
-- ----------------------------
DROP TABLE IF EXISTS `game_history`;
CREATE TABLE `game_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `game_key` int(11) NOT NULL,
  `old_status` enum('Playable','Ingame','Intro','Loadable','Nothing') DEFAULT NULL,
  `old_date` date DEFAULT NULL,
  `new_status` enum('Playable','Ingame','Intro','Loadable','Nothing') NOT NULL DEFAULT 'Nothing',
  `new_date` date NOT NULL,
  `new_gid` varchar(9) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for `contributors`
-- ----------------------------
DROP TABLE IF EXISTS `contributors`;
CREATE TABLE `contributors` (
	`id` int(11) NOT NULL,
	`username` varchar(128) NOT NULL,
	PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for `game_id`
-- ----------------------------
DROP TABLE IF EXISTS `game_id`;
CREATE TABLE `game_id` (
  `key` int(11) NOT NULL,
  `gid` varchar(9) NOT NULL,
  `tid` int(11) NOT NULL,
  `latest_ver` varchar(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
