CREATE TABLE `files` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`filename` varchar(100) NOT NULL,
	`checksum` varchar(30) NOT NULL,
	`created` datetime NOT NULL DEFAULT current_timestamp(),
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
