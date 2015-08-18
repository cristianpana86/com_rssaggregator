CREATE TABLE IF NOT EXISTS `#__rssaggregator_source` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,

`rss_link` VARCHAR(255)  NOT NULL ,
`rss_name` VARCHAR(255)  NOT NULL ,
`no_of_posts` VARCHAR(255)  NOT NULL ,
`author` INT(11)  NOT NULL ,
`category` INT NOT NULL ,
`featured` VARCHAR(255)  NOT NULL ,
`show_graphic` VARCHAR(255)  NOT NULL ,
`allow_links` VARCHAR(255)  NOT NULL ,
`split_after_x` VARCHAR(12)  NOT NULL ,
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8_general_ci;

