############################
#  Shadow DB Architecture  #
############################

CREATE TABLE `shadow_meta` (
  `id` bigint NOT NULL auto_increment,
  `namespace` varbinary(25) NOT NULL,
  `type` varchar(255) default NULL,
  `object_id` varchar(255) NOT NULL,
  `object_key` varchar(255) NOT NULL,
  `count` int(11) NOT NULL,
  `parent` int(11) default NULL,
  `expires` timestamp NULL default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=0;

CREATE TABLE `shadow_objects` (
  `id` bigint NOT NULL auto_increment,
  `operation` varchar(10) default NULL,
  `namespace` varchar(25) default NULL,
  `type` varchar(255) default NULL,
  `object_id` varchar(255) NOT NULL,
  `count` int(11) NOT NULL default '0',
  `positive` int(11) NOT NULL default '0',
  `negative` int(11) NOT NULL default '0',
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=0;

CREATE TABLE `shadow_object_relations` (
  `id` bigint NOT NULL auto_increment,
  `user_id` int(11) NOT NULL,
  `real_object_id` int(11) NOT NULL,
  `value` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `user_id` (`user_id`),
  KEY `real_object_id` (`real_object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=0;
