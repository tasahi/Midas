-- Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

-- MySQL database for the oauth module, version 1.0.0

CREATE TABLE IF NOT EXISTS `oauth_client` (
    `client_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL DEFAULT '',
    `secret` varchar(64) NOT NULL,
    `owner_id` bigint(20) NOT NULL,
    `creation_date` timestamp,
    PRIMARY KEY (`client_id`),
    KEY (`owner_id`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `oauth_code` (
    `code_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `code` varchar(64) NOT NULL,
    `scopes` varchar(255) NOT NULL,
    `user_id` bigint(20) NOT NULL,
    `client_id` bigint(20) NOT NULL,
    `creation_date` timestamp,
    `expiration_date` timestamp,
    PRIMARY KEY (`code_id`),
    KEY (`code`)
) DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `oauth_token` (
    `token_id` bigint(20) NOT NULL AUTO_INCREMENT,
    `client_id` bigint(20) NOT NULL,
    `user_id` bigint(20) NOT NULL,
    `token` varchar(64) NOT NULL,
    `scopes` varchar(255) NOT NULL,
    `type` tinyint(4) NOT NULL,
    `creation_date` timestamp,
    `expiration_date` timestamp,
    PRIMARY KEY (`token_id`),
    KEY (`token`),
    KEY (`user_id`)
) DEFAULT CHARSET=utf8;
