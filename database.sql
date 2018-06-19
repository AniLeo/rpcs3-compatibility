-- ----------------------------
-- Table structure for `game_list`
-- ----------------------------
DROP TABLE IF EXISTS `game_list`;
CREATE TABLE `game_list` (
  `key` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Don''t need a primary key, just for good practice',
  `game_title` varchar(250) NOT NULL,
  `alternative_title` varchar(250) DEFAULT NULL,
  `status` enum('Playable','Ingame','Intro','Loadable','Nothing') NOT NULL DEFAULT 'Nothing',
  `build_commit` varchar(64) NOT NULL,
  `last_update` date NOT NULL,
  `gid_EU` varchar(9) DEFAULT NULL,
  `tid_EU` int(11) DEFAULT NULL,
  `gid_US` varchar(9) DEFAULT NULL,
  `tid_US` int(11) DEFAULT NULL,
  `gid_JP` varchar(9) DEFAULT NULL,
  `tid_JP` int(11) DEFAULT NULL,
  `gid_AS` varchar(9) DEFAULT NULL,
  `tid_AS` int(11) DEFAULT NULL,
  `gid_KR` varchar(9) DEFAULT NULL,
  `tid_KR` int(11) DEFAULT NULL,
  `gid_HK` varchar(9) DEFAULT NULL,
  `tid_HK` int(11) DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for `builds_windows`
-- ----------------------------
DROP TABLE IF EXISTS `builds_windows`;
CREATE TABLE `builds_windows` (
  `pr` int(11) NOT NULL,
  `commit` varchar(64) NOT NULL,
  `author` varchar(128) NOT NULL,
  `start_datetime` datetime NOT NULL,
  `merge_datetime` datetime NOT NULL,
  `appveyor` varchar(64) NOT NULL,
  `type` varchar(64) NOT NULL,
  `buildjob` varchar(64) DEFAULT NULL,
  `filename` varchar(128) DEFAULT NULL,
  `additions` int(11) DEFAULT NULL,
  `deletions` int(11) DEFAULT NULL,
  `changed_files` int(11) DEFAULT NULL,
  `checksum` varchar(64) DEFAULT NULL COMMENT 'sha256',
  `size` int(11) DEFAULT NULL,
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
-- Table structure for `ip_whitelist`
-- ----------------------------
DROP TABLE IF EXISTS `ip_whitelist`;
CREATE TABLE `ip_whitelist` (
  `uid` int(11) NOT NULL,
  `ip` varchar(64) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for `game_history`
-- ----------------------------
DROP TABLE IF EXISTS `game_history`;
CREATE TABLE `game_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gid_EU` varchar(9) DEFAULT NULL,
  `gid_US` varchar(9) DEFAULT NULL,
  `gid_JP` varchar(9) DEFAULT NULL,
  `gid_AS` varchar(9) DEFAULT NULL,
  `gid_KR` varchar(9) DEFAULT NULL,
  `gid_HK` varchar(9) DEFAULT NULL,
  `old_status` enum('Playable','Ingame','Intro','Loadable','Nothing') DEFAULT NULL,
  `old_date` date DEFAULT NULL,
  `new_status` enum('Playable','Ingame','Intro','Loadable','Nothing') NOT NULL DEFAULT 'Nothing',
  `new_date` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
