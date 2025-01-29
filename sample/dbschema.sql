CREATE DATABASE `wildfile`;
USE `wildfile`;

CREATE TABLE `files` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`name` varchar(100) NOT NULL DEFAULT '',
	`mime` varchar(128) NOT NULL DEFAULT '',
	`size` int(10) unsigned NOT NULL DEFAULT 0,
	`checksum` char(64) NOT NULL DEFAULT '',
	`address` varchar(15) NOT NULL DEFAULT '',
	`created` datetime DEFAULT NULL,
	`thumbnail` varchar(100) DEFAULT NULL,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE USER `wildfile`@`localhost` IDENTIFIED BY 'Pa55w0rd';
GRANT DELETE, INSERT, SELECT, UPDATE ON `wildfile`.* TO `wildfile`@`localhost`;
