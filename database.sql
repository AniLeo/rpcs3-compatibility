-- ----------------------------
-- Table structure for game_list
-- ----------------------------
DROP TABLE IF EXISTS `game_list`;
CREATE TABLE `game_list` (
  `game_id` varchar(9) CHARACTER SET utf8 NOT NULL,
  `game_title` varchar(255) CHARACTER SET utf8 NOT NULL,
  `parent_id` int(11) NOT NULL,
  `thread_id` int(11) NOT NULL,
  `build_commit` varchar(255) CHARACTER SET utf8 NOT NULL,
  `last_edit` date NOT NULL DEFAULT '0000-00-00',
  PRIMARY KEY (`game_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for builds_windows
-- ----------------------------
DROP TABLE IF EXISTS `builds_windows`;
CREATE TABLE `builds_windows` (
  `pr` int(11) NOT NULL,
  `commit` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `start_datetime` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  `merge_datetime` datetime NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  `appveyor` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `buildjob` varchar(255) DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`pr`,`commit`)
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

-- ----------------------------
-- Table structure for game_history
-- ----------------------------
DROP TABLE IF EXISTS `game_history`;
CREATE TABLE `game_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `game_id` varchar(9) NOT NULL,
  `old_status` enum('Playable','Ingame','Intro','Loadable','Nothing') DEFAULT NULL,
  `old_date` date DEFAULT NULL,
  `new_status` enum('Playable','Ingame','Intro','Loadable','Nothing') NOT NULL,
  `new_date` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3386 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for game_status
-- ----------------------------
DROP TABLE IF EXISTS `game_status`;
CREATE TABLE `game_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` enum('Playable','Ingame','Intro','Loadable','Nothing') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2840 DEFAULT CHARSET=utf8;
