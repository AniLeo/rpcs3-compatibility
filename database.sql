-- ----------------------------
-- Table structure for `game_list`
-- ----------------------------
DROP TABLE IF EXISTS `game_list`;
CREATE TABLE `game_list` (
  `key` int(11) NOT NULL AUTO_INCREMENT,
  `game_title` varchar(128) NOT NULL,
  `alternative_title` varchar(128) DEFAULT NULL,
  `type` enum('PS3 Game', 'PS3 App') NOT NULL,
  `status` enum('Playable','Ingame','Intro','Loadable','Nothing') NOT NULL DEFAULT 'Nothing',
  `last_update` date NOT NULL,
  `wiki` int(11) DEFAULT NULL,
  `pr` int(11) DEFAULT NULL,
  `build_commit` varchar(64) DEFAULT NULL,
  `network` tinyint(1) NOT NULL DEFAULT '0',
  `3d` tinyint(1) NOT NULL DEFAULT '0',
  `move` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`key`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `filename_mac` varchar(128) DEFAULT NULL,
  `checksum_mac` varchar(64) DEFAULT NULL COMMENT 'sha256',
  `size_mac` int(11) DEFAULT NULL,
  `filename_win_arm64` varchar(128) DEFAULT NULL,
  `checksum_win_arm64` varchar(64) DEFAULT NULL COMMENT 'sha256',
  `size_win_arm64` int(11) DEFAULT NULL,
  `filename_linux_arm64` varchar(128) DEFAULT NULL,
  `checksum_linux_arm64` varchar(64) DEFAULT NULL COMMENT 'sha256',
  `size_linux_arm64` int(11) DEFAULT NULL,
  `filename_mac_arm64` varchar(128) DEFAULT NULL,
  `checksum_mac_arm64` varchar(64) DEFAULT NULL COMMENT 'sha256',
  `size_mac_arm64` int(11) DEFAULT NULL,
  `broken` int(11) DEFAULT NULL,
  `ping_updated` int(11) NOT NULL DEFAULT '0',
  `ping_outdated` int(11) NOT NULL DEFAULT '0',
  `title` varchar(255) DEFAULT NULL,
  `body` mediumtext,
  PRIMARY KEY (`pr`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for `initials_cache`
-- ----------------------------
DROP TABLE IF EXISTS `initials_cache`;
CREATE TABLE `initials_cache` (
  `game_title` varchar(250) NOT NULL,
  `initials` varchar(64) NOT NULL,
  PRIMARY KEY (`game_title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for `debug_whitelist`
-- ----------------------------
DROP TABLE IF EXISTS `debug_whitelist`;
CREATE TABLE `debug_whitelist` (
  `token` varchar(255) NOT NULL,
  `permissions` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for `contributors`
-- ----------------------------
DROP TABLE IF EXISTS `contributors`;
CREATE TABLE `contributors` (
  `id` int(11) NOT NULL,
  `username` varchar(128) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for `game_id`
-- ----------------------------
DROP TABLE IF EXISTS `game_id`;
CREATE TABLE `game_id` (
  `key` int(11) NOT NULL,
  `gid` varchar(9) NOT NULL,
  `tid` int(11) NOT NULL,
  `latest_ver` varchar(16) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for game_patch
-- ----------------------------
DROP TABLE IF EXISTS `game_patch`;
CREATE TABLE `game_patch` (
  `wiki_id` int(11) NOT NULL,
  `version` varchar(4) NOT NULL,
  `touched` binary(14) NOT NULL,
  `patch` mediumtext NOT NULL,
  PRIMARY KEY (`wiki_id`,`version`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for game_update_titlepatch
-- ----------------------------
DROP TABLE IF EXISTS `game_update_titlepatch`;
CREATE TABLE `game_update_titlepatch` (
  `titleid` varchar(9) NOT NULL,
  `status` varchar(16) DEFAULT NULL,
  PRIMARY KEY (`titleid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for game_update_tag
-- ----------------------------
DROP TABLE IF EXISTS `game_update_tag`;
CREATE TABLE `game_update_tag` (
  `name` varchar(16) NOT NULL,
  `popup` varchar(8) NOT NULL,
  `signoff` varchar(8) NOT NULL,
  `hash` varchar(32) NOT NULL,
  `popup_delay` varchar(16) DEFAULT NULL,
  `min_system_ver` varchar(16) DEFAULT NULL,
  PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for game_update_package
-- ----------------------------
DROP TABLE IF EXISTS `game_update_package`;
CREATE TABLE `game_update_package` (
  `tag` varchar(16) NOT NULL,
  `version` varchar(16) NOT NULL,
  `size` varchar(16) NOT NULL,
  `sha1sum` varchar(64) NOT NULL,
  `url` varchar(255) NOT NULL,
  `ps3_system_ver` varchar(16) DEFAULT NULL,
  `drm_type` varchar(64) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for game_update_paramsfo
-- ----------------------------
DROP TABLE IF EXISTS `game_update_paramsfo`;
CREATE TABLE `game_update_paramsfo` (
  `tag` varchar(16) NOT NULL,
  `package_version` varchar(16) NOT NULL,
  `paramsfo_type` varchar(32) NOT NULL,
  `paramsfo_title` varchar(255) NOT NULL,
  PRIMARY KEY (`tag`,`package_version`,`paramsfo_type`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for game_update_paramhip
-- ----------------------------
DROP TABLE IF EXISTS `game_update_paramhip`;
CREATE TABLE `game_update_paramhip` (
  `tag` varchar(16) NOT NULL,
  `package_version` varchar(16) NOT NULL,
  `paramhip_type` varchar(32) NOT NULL,
  `paramhip_url` varchar(255) NOT NULL,
  `paramhip_content` mediumtext NOT NULL,
  PRIMARY KEY (`tag`,`package_version`,`paramhip_type`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for np_players
-- ----------------------------
DROP TABLE IF EXISTS `np_players`;
CREATE TABLE `np_players` (
  `timestamp` datetime DEFAULT NULL,
  `players` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for np_psn_games
-- ----------------------------
DROP TABLE IF EXISTS `np_psn_games`;
CREATE TABLE `np_psn_games` (
  `timestamp` datetime NOT NULL,
  `comm_id` varchar(12) NOT NULL,
  `players` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table structure for np_ticket_games
-- ----------------------------
DROP TABLE IF EXISTS `np_ticket_games`;
CREATE TABLE `np_ticket_games` (
  `timestamp` datetime NOT NULL,
  `content_id` varchar(19) NOT NULL,
  `players` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
