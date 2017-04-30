-- ----------------------------
-- Table structure for `rpcs3`
-- ----------------------------
DROP TABLE IF EXISTS `rpcs3`;
CREATE TABLE `rpcs3` (
  `game_id` varchar(9) NOT NULL,
  `game_title` varchar(255) NOT NULL,
  `build_commit` varchar(255) NOT NULL,
  `thread_id` int(11) NOT NULL,
  `status` enum('Playable','Ingame','Intro','Loadable','Nothing') NOT NULL,
  `last_edit` date NOT NULL DEFAULT '0000-00-00',
  PRIMARY KEY (`game_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for commit_cache
-- ----------------------------
DROP TABLE IF EXISTS `commit_cache`;
CREATE TABLE `commit_cache` (
  `commit_id` varchar(255) NOT NULL,
  `valid` int(11) NOT NULL,
  PRIMARY KEY (`commit_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for builds_windows
-- ----------------------------
DROP TABLE IF EXISTS `builds_windows`;
CREATE TABLE `builds_windows` (
  `pr` int(11) NOT NULL,
  `author` varchar(255) NOT NULL,
  `start_datetime` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  `merge_datetime` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  `appveyor` varchar(255) NOT NULL,
  PRIMARY KEY (`pr`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for initials_cache
-- ----------------------------
DROP TABLE IF EXISTS `initials_cache`;
CREATE TABLE `initials_cache` (
  `game_title` varchar(255) NOT NULL,
  `initials` varchar(255) NOT NULL,
  PRIMARY KEY (`game_title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for ip_whitelist
-- ----------------------------
DROP TABLE IF EXISTS `ip_whitelist`;
CREATE TABLE `ip_whitelist` (
  `uid` int(11) NOT NULL,
  `ip` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;