
DROP TABLE IF EXISTS `activate`;
CREATE TABLE `activate` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `activationkey` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `state` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `bookmarks`;
CREATE TABLE `bookmarks` (
  `bid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ts` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `catid` int(11) DEFAULT NULL,
  `user` varchar(255) DEFAULT '0',
  PRIMARY KEY (`bid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `cat`;
CREATE TABLE `cat` (
  `id` varchar(16) DEFAULT NULL,
  `catid` int(11) NOT NULL AUTO_INCREMENT,
  `version` int(11) DEFAULT 0,
  `type` varchar(32) DEFAULT NULL,
  `typeid` int(11) DEFAULT 0,
  `tent` timestamp NULL DEFAULT NULL,
  `tcha` timestamp NULL DEFAULT NULL,
  `ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `oa` int(11) DEFAULT 0,
  `rm` int(11) DEFAULT 0,
  `user` varchar(255) DEFAULT NULL,
  `project` int(11) DEFAULT NULL,
  `access` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT 'project',
  `fixed` int(11) DEFAULT NULL,
  `jsondata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `jsonmeta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `f0` varchar(255) DEFAULT NULL,
  `f1` text DEFAULT NULL,
  `f2` text DEFAULT NULL,
  `f3` text DEFAULT NULL,
  `f4` text DEFAULT NULL,
  `f5` text DEFAULT NULL,
  `f6` varchar(255) DEFAULT NULL,
  `f7` varchar(255) DEFAULT NULL,
  `f8` varchar(255) DEFAULT NULL,
  `f9` varchar(255) DEFAULT NULL,
  `f10` varchar(255) DEFAULT NULL,
  `f11` varchar(255) DEFAULT NULL,
  `f12` varchar(255) DEFAULT NULL,
  `f13` varchar(255) DEFAULT NULL,
  `f14` varchar(255) DEFAULT NULL,
  `f15` varchar(255) DEFAULT NULL,
  `f16` varchar(255) DEFAULT NULL,
  `f17` varchar(128) DEFAULT NULL,
  `f18` varchar(128) DEFAULT NULL,
  `f19` varchar(128) DEFAULT NULL,
  `f20` varchar(128) DEFAULT NULL,
  `f21` varchar(128) DEFAULT NULL,
  `f22` varchar(128) DEFAULT NULL,
  `f23` varchar(128) DEFAULT NULL,
  `f24` varchar(128) DEFAULT NULL,
  `f25` varchar(128) DEFAULT NULL,
  `f26` varchar(128) DEFAULT NULL,
  `f27` varchar(128) DEFAULT NULL,
  `f28` varchar(128) DEFAULT NULL,
  `f29` varchar(128) DEFAULT NULL,
  `f30` varchar(128) DEFAULT NULL,
  `f31` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`catid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `docs`;
/*! SET @saved_cs_client     = @@character_set_client */;
CREATE TABLE `docs` (
  `docid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `catid` int(11) DEFAULT NULL,
  `fdocid` int(11) DEFAULT NULL,
  `fcatid` int(11) DEFAULT NULL,
  `version` int(11) DEFAULT 0,
  `ts` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `rm` int(11) DEFAULT 0,
  `comment` text DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `dir` varchar(255) DEFAULT NULL,
  `mime` varchar(512) DEFAULT NULL,
  `size` int(11) DEFAULT NULL,
  `md5` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`docid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `gen`;
CREATE TABLE `gen` (
  `catid` int(11) DEFAULT NULL,
  `parent` int(11) DEFAULT NULL,
  `gen` int(11) DEFAULT NULL,
  `rm` int(11) DEFAULT 0,
  `ts` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `links`;
CREATE TABLE `links` (
  `lid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ts` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `catid` int(11) DEFAULT NULL,
  `version` int(11) DEFAULT 0,
  `link` int(11) DEFAULT NULL,
  PRIMARY KEY (`lid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `log`;
CREATE TABLE `log` (
  `logid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id` varchar(16) DEFAULT NULL,
  `docid` int(11) unsigned NOT NULL DEFAULT 0,
  `action` varchar(255) DEFAULT NULL,
  `tag` varchar(16) DEFAULT NULL,
  `user` varchar(255) DEFAULT NULL,
  `role` int(11) DEFAULT NULL,
  `src` varchar(255) DEFAULT NULL,
  `ip` varchar(255) DEFAULT NULL,
  `ts` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `comment` text DEFAULT NULL,
  PRIMARY KEY (`logid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `prefs`;
CREATE TABLE `prefs` (
  `pid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(256) DEFAULT NULL,
  `user` varchar(64) DEFAULT NULL,
  `ts` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `type` varchar(64) DEFAULT NULL,
  `prefs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  PRIMARY KEY (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `projectmember`;
CREATE TABLE `projectmember` (
  `pid` int(11) DEFAULT NULL,
  `name` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `projects`;
CREATE TABLE `projects` (
  `pid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `private` int(11) unsigned NOT NULL DEFAULT 0,
  `pname` varchar(32) DEFAULT NULL,
  `comment` varchar(255) DEFAULT '',
  PRIMARY KEY (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `role`;
CREATE TABLE `role` (
  `rid` int(11) unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
  PRIMARY KEY (`rid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `taglog`;
CREATE TABLE `taglog` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tag` varchar(128) DEFAULT NULL,
  `ip` varchar(32) DEFAULT NULL,
  `ts` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tags`;
CREATE TABLE `tags` (
  `id` varchar(16) DEFAULT NULL,
  `tag` varchar(128) DEFAULT NULL,
  `ts` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tmp`;
CREATE TABLE `tmp` (
  `id` varchar(32) DEFAULT NULL,
  `value` text DEFAULT NULL,
  `ts` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tokens`;
CREATE TABLE `tokens` (
  `tid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ts` timestamp NULL DEFAULT current_timestamp(),
  `token` varchar(64) DEFAULT NULL,
  `name` varchar(64) DEFAULT NULL,
  `type` varchar(32) DEFAULT NULL,
  `ip` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`tid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `uid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `passwd` varchar(64) DEFAULT NULL,
  `type` varchar(32) DEFAULT NULL,
  `role` varchar(32) DEFAULT NULL,
  `ts` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `var`;
CREATE TABLE `var` (
  `name` varchar(255) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

LOCK TABLES `role` WRITE;
INSERT INTO `role` VALUES (0,'root');
INSERT INTO `role` VALUES (1,'admin');
INSERT INTO `role` VALUES (2,'user');
INSERT INTO `role` VALUES (3,'limited');
INSERT INTO `role` VALUES (9,'open access');
UNLOCK TABLES;

LOCK TABLES `var` WRITE;
INSERT INTO `var` VALUES ('db_version','1');
UNLOCK TABLES;

LOCK TABLES `user` WRITE;
INSERT INTO `user` VALUES (1,'root',NULL,'$5$ujeeyaequoox$GHJBMnvAIDCzGYifgnTHKbhYwtYubjQ57WXvv9ocWb2','local','0',NULL);
UNLOCK TABLES;

LOCK TABLES `projects` WRITE;
INSERT INTO `projects` VALUES (1,0,'DEFAULT','');
UNLOCK TABLES;
