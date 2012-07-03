<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   BL
 * @package    BL_CustomGrid
 * @copyright  Copyright (c) 2012 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

$installer = $this;
$installer->startSetup();

$installer->run("
CREATE TABLE IF NOT EXISTS `{$this->getTable('customgrid/grid')}` (
`grid_id` int(10) unsigned NOT NULL auto_increment,
`type` varchar(255) character set utf8 NOT NULL,
`module_name` varchar(255) character set utf8 NOT NULL,
`controller_name` varchar(255) character set utf8 NOT NULL,
`block_type` varchar(255) character set utf8 NOT NULL,
`rewriting_class_name` varchar(255) character set utf8 default NULL,
`block_id` varchar(255) character set utf8 default NULL,
`max_attribute_column_id` int(10) unsigned NOT NULL default 0,
`default_page` int(10) unsigned default NULL,
`default_limit` int(10) unsigned default NULL,
`default_sort` varchar(255) character set utf8 default NULL,
`default_direction` enum('asc', 'desc') character set utf8 default NULL,
`default_filters` text character set utf8 default NULL,
`disabled` tinyint(1) unsigned NOT NULL default 0,
PRIMARY KEY (`grid_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{$this->getTable('customgrid/grid_column')}` (
`column_id` int(10) unsigned NOT NULL auto_increment,
`grid_id` int(10) unsigned NOT NULL,
`id` varchar(255) character set utf8 NOT NULL,
`index` varchar(255) character set utf8 default NULL,
`width` varchar(128) character set utf8 default '',
`align` enum('left', 'center', 'right') character set utf8 default 'left',
`header` varchar(255) character set utf8 default '',
`order` int(10) NOT NULL default 0,
`origin` enum('grid', 'collection', 'attribute') character set utf8 NOT NULL default 'grid',
`is_visible` tinyint(1) NOT NULL default 1,
`is_system` tinyint(1) NOT NULL default 0,
`missing` tinyint(1) unsigned NOT NULL default 0,
`store_id` smallint(5) unsigned default NULL,
`renderer_type` varchar(255) character set utf8 default NULL,
`renderer_params` text character set utf8 default NULL,
PRIMARY KEY (`column_id`),
KEY `FK_CUSTOM_GRID_GRID_COLUMN_GRID` (`grid_id`),
KEY `FK_CUSTOM_GRID_GRID_COLUMN_STORE` (`store_id`),
CONSTRAINT `FK_CUSTOM_GRID_GRID_COLUMN_GRID` FOREIGN KEY (`grid_id`) REFERENCES `{$this->getTable('customgrid/grid')}` (`grid_id`) ON DELETE CASCADE,
CONSTRAINT `FK_CUSTOM_GRID_GRID_COLUMN_STORE` FOREIGN KEY (`store_id`) REFERENCES `{$this->getTable('core/store')}` (`store_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{$installer->getTable('customgrid/options_source')}` (
`source_id` int(10) unsigned NOT NULL auto_increment,
`name` varchar(255) character set utf8 NOT NULL,
`description` text character set utf8 NOT NULL,
`type` enum('custom_list', 'mage_model') character set utf8 NOT NULL,
PRIMARY KEY (`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{$installer->getTable('customgrid/options_source_model')}` (
`model_id` int(10) unsigned NOT NULL auto_increment,
`source_id` int(10) unsigned NOT NULL,
`model_name` varchar(255) character set utf8 NOT NULL,
`model_type` enum('model', 'resource_model', 'singleton') character set utf8 NOT NULL,
`method` varchar(255) character set utf8 NOT NULL,
`return_type` enum('options_hash', 'options_array', 'vo_collection') character set utf8 NOT NULL,
`value_key` varchar(255) character set utf8 default NULL,
`label_key` varchar(255) character set utf8 default NULL,
PRIMARY KEY (`model_id`),
KEY `FK_CUSTOMGRID_OPTIONS_SOURCE_MODEL_SOURCE` (`source_id`),
CONSTRAINT `FK_CUSTOMGRID_OPTIONS_SOURCE_MODEL_SOURCE` FOREIGN KEY (`source_id`) REFERENCES `{$this->getTable('customgrid_options_source')}` (`source_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{$installer->getTable('customgrid/options_source_option')}` (
`option_id` int(10) unsigned NOT NULL auto_increment,
`source_id` int(10) unsigned NOT NULL,
`value` varchar(255) character set utf8 NOT NULL,
`label` varchar(255) character set utf8 NOT NULL,
PRIMARY KEY (`option_id`),
KEY `FK_CUSTOMGRID_OPTIONS_SOURCE_OPTION_SOURCE` (`source_id`),
CONSTRAINT `FK_CUSTOMGRID_OPTIONS_SOURCE_OPTION_SOURCE` FOREIGN KEY (`source_id`) REFERENCES `{$this->getTable('customgrid_options_source')}` (`source_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();
