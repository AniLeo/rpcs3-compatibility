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
