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
 * @copyright  Copyright (c) 2016 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

$installer  = $this;
$installer->startSetup();
$connection = $installer->getConnection();

$tables = array(
    'admin_role'     => $installer->getTable('admin/role'),
    'admin_user'     => $installer->getTable('admin/user'),
    'grid'           => $installer->getTable('customgrid/grid'),
    'grid_column'    => $installer->getTable('customgrid/grid_column'),
    'grid_profile'   => $installer->getTable('customgrid/grid_profile'),
    'grid_role'      => $installer->getTable('customgrid/grid_role'),
    'grid_user'      => $installer->getTable('customgrid/grid_user'),
    'options_source' => $installer->getTable('customgrid/options_source'),
    'role_profile'   => $installer->getTable('customgrid/role_profile'),
    'source_model'   => $installer->getTable('customgrid/options_source_model'),
    'source_option'  => $installer->getTable('customgrid/options_source_option'),
    'store'          => $installer->getTable('core/store'),
);

$installer->run(
    "
    CREATE TABLE IF NOT EXISTS `{$tables['grid']}` (
    `grid_id` int(10) unsigned NOT NULL auto_increment,
    `type_code` varchar(255) character set utf8 NOT NULL,
    `forced_type_code` varchar(255) character set utf8 default NULL,
    `module_name` varchar(255) character set utf8 NOT NULL,
    `controller_name` varchar(255) character set utf8 NOT NULL,
    `block_type` varchar(255) character set utf8 NOT NULL,
    `rewriting_class_name` varchar(255) character set utf8 default NULL,
    `block_id` varchar(255) character set utf8 default NULL,
    `has_varying_block_id` tinyint(1) unsigned default 0,
    `base_profile_id` int(10) unsigned default NULL,
    `global_default_profile_id` int(10) unsigned default NULL,
    `profiles_default_restricted` tinyint(1) unsigned default NULL,
    `profiles_default_assigned_to` text character set utf8 default NULL,
    `profiles_remembered_session_params` varchar(255) default NULL,
    `max_attribute_column_base_block_id` int(10) unsigned NOT NULL default 0,
    `max_custom_column_base_block_id` int(10) unsigned NOT NULL default 0,
    `ignore_custom_headers` tinyint(1) unsigned default NULL,
    `ignore_custom_widths` tinyint(1) unsigned default NULL,
    `ignore_custom_alignments` tinyint(1) unsigned default NULL,
    `pagination_values` varchar(255) character set utf8 default NULL,
    `default_pagination_value` int(10) unsigned default NULL,
    `merge_base_pagination` tinyint(1) unsigned default NULL,
    `pin_header` tinyint(1) unsigned default NULL,
    `display_system_part` tinyint(1) unsigned default NULL,
    `rss_links_window` tinyint(1) unsigned default NULL,
    `hide_original_export_block` tinyint(1) unsigned default NULL,
    `hide_filter_reset_button` tinyint(1) unsigned default NULL,
    `var_name_page` varchar(255) character set utf8 default NULL,
    `var_name_limit` varchar(255) character set utf8 default NULL,
    `var_name_sort` varchar(255) character set utf8 default NULL,
    `var_name_dir` varchar(255) character set utf8 default NULL,
    `var_name_filter` varchar(255) character set utf8 default NULL,
    `default_page_behaviour` enum ('default', 'force_original', 'force_custom') character set utf8 default NULL,
    `default_limit_behaviour` enum ('default', 'force_original', 'force_custom') character set utf8 default NULL,
    `default_sort_behaviour` enum ('default', 'force_original', 'force_custom') character set utf8 default NULL,
    `default_dir_behaviour` enum ('default', 'force_original', 'force_custom') character set utf8 default NULL,
    `default_filter_behaviour`
        enum ('default', 'force_original', 'force_custom', 'merge_default', 'merge_on_original', 'merge_on_custom')
        character set utf8 default NULL,
    `disabled` tinyint(1) unsigned NOT NULL default 0,
    PRIMARY KEY (`grid_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    CREATE TABLE IF NOT EXISTS `{$tables['grid_profile']}` (
    `profile_id` int(10) unsigned NOT NULL auto_increment,
    `grid_id` int(10) unsigned NOT NULL,
    `name` varchar(255) character set utf8 NOT NULL,
    `is_restricted` tinyint(1) unsigned NOT NULL default 0,
    `remembered_session_params` varchar(255) default NULL,
    `default_page` int(10) unsigned default NULL,
    `default_limit` int(10) unsigned default NULL,
    `default_sort` varchar(255) character set utf8 default NULL,
    `default_dir` enum('asc', 'desc') character set utf8 default NULL,
    `default_filter` text character set utf8 default NULL,
    PRIMARY KEY (`profile_id`),
    KEY `FK_CUSTOM_GRID_GRID_PROFILE_GRID` (`grid_id`),
    CONSTRAINT `FK_CUSTOM_GRID_GRID_PROFILE_GRID`
        FOREIGN KEY (`grid_id`) REFERENCES `{$tables['grid']}` (`grid_id`)
        ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    
    CREATE TABLE IF NOT EXISTS `{$tables['grid_column']}` (
    `column_id` int(10) unsigned NOT NULL auto_increment,
    `grid_id` int(10) unsigned NOT NULL,
    `profile_id` int(10) unsigned default NULL,
    `block_id` varchar(255) character set utf8 NOT NULL,
    `index` varchar(255) character set utf8 default NULL,
    `width` varchar(128) character set utf8 default '',
    `align` enum('left', 'center', 'right') character set utf8 default 'left',
    `header` varchar(255) character set utf8 default '',
    `order` int(10) NOT NULL default 0,
    `origin` enum('grid', 'collection', 'attribute', 'custom') character set utf8 NOT NULL default 'grid',
    `is_visible` tinyint(1) NOT NULL default 1,
    `is_only_filterable` tinyint(1) unsigned NOT NULL default 0,
    `is_edit_allowed` tinyint(1) unsigned NOT NULL default 1,
    `is_system` tinyint(1) NOT NULL default 0,
    `is_missing` tinyint(1) unsigned NOT NULL default 0,
    `store_id` smallint(5) unsigned default NULL,
    `renderer_type` varchar(255) character set utf8 default NULL,
    `renderer_params` text character set utf8 default NULL,
    `customization_params` text character set utf8 default NULL,
    PRIMARY KEY (`column_id`),
    KEY `FK_CUSTOM_GRID_GRID_COLUMN_GRID` (`grid_id`),
    KEY `FK_CUSTOM_GRID_GRID_COLUMN_PROFILE_GRID_PROFILE` (`profile_id`),
    KEY `FK_CUSTOM_GRID_GRID_COLUMN_STORE` (`store_id`),
    CONSTRAINT `FK_CUSTOM_GRID_GRID_COLUMN_GRID`
        FOREIGN KEY (`grid_id`) REFERENCES `{$tables['grid']}` (`grid_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `FK_CUSTOM_GRID_GRID_COLUMN_PROFILE_GRID_PROFILE`
        FOREIGN KEY (`profile_id`) REFERENCES `{$tables['grid_profile']}` (`profile_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `FK_CUSTOM_GRID_GRID_COLUMN_STORE`
        FOREIGN KEY (`store_id`) REFERENCES `{$tables['store']}` (`store_id`)
        ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    CREATE TABLE IF NOT EXISTS `{$tables['grid_role']}` (
    `grid_role_id` int(10) unsigned NOT NULL auto_increment,
    `grid_id` int(10) unsigned NOT NULL,
    `role_id` int(10) unsigned NOT NULL,
    `permissions` text character set utf8 default NULL,
    `default_profile_id` int(10) unsigned default NULL,
    PRIMARY KEY (`grid_role_id`),
    UNIQUE KEY `UNQ_CUSTOM_GRID_ROLE_GRID_ROLE` (`grid_id`, `role_id`),
    KEY `FK_CUSTOM_GRID_GRID_ROLE_GRID` (`grid_id`),
    KEY `FK_CUSTOM_GRID_GRID_ROLE_ROLE` (`role_id`),
    KEY `FK_CUSTOM_GRID_GRID_ROLE_DEFAULT_PROFILE` (`default_profile_id`),
    CONSTRAINT `FK_CUSTOM_GRID_GRID_ROLE_GRID`
        FOREIGN KEY (`grid_id`) REFERENCES `{$tables['grid']}` (`grid_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `FK_CUSTOM_GRID_GRID_ROLE_ROLE`
        FOREIGN KEY (`role_id`) REFERENCES `{$tables['admin_role']}` (`role_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `FK_CUSTOM_GRID_GRID_ROLE_DEFAULT_PROFILE`
        FOREIGN KEY (`default_profile_id`) REFERENCES `{$tables['grid_profile']}` (`profile_id`)
        ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    CREATE TABLE IF NOT EXISTS `{$tables['role_profile']}` (
    `role_profile_id` int(10) unsigned NOT NULL auto_increment,
    `role_id` int(10) unsigned NOT NULL,
    `profile_id` int(10) unsigned NOT NULL,
    PRIMARY KEY (`role_profile_id`),
    UNIQUE KEY `UNQ_CUSTOM_GRID_ROLE_PROFILE_ROLE_PROFILE` (`role_id`, `profile_id`),
    KEY `FK_CUSTOM_GRID_ROLE_PROFILE_ROLE` (`role_id`),
    KEY `FK_CUSTOM_GRID_ROLE_PROFILE_PROFILE` (`profile_id`),
    CONSTRAINT `FK_CUSTOM_GRID_ROLE_PROFILE_ROLE`
        FOREIGN KEY (`role_id`) REFERENCES `{$tables['admin_role']}` (`role_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `FK_CUSTOM_GRID_ROLE_PROFILE_PROFILE`
        FOREIGN KEY (`profile_id`) REFERENCES `{$tables['grid_profile']}` (`profile_id`)
        ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    CREATE TABLE IF NOT EXISTS `{$tables['grid_user']}` (
    `grid_user_id` int(10) unsigned NOT NULL auto_increment,
    `grid_id` int(10) unsigned NOT NULL,
    `user_id` mediumint(9) unsigned NOT NULL,
    `default_profile_id` int(10) unsigned default NULL,
    PRIMARY KEY (`grid_user_id`),
    UNIQUE KEY `UNQ_CUSTOM_GRID_USER_GRID_USER` (`grid_id`, `user_id`),
    KEY `FK_CUSTOM_GRID_GRID_USER_GRID` (`grid_id`),
    KEY `FK_CUSTOM_GRID_GRID_USER_USER` (`user_id`),
    KEY `FK_CUSTOM_GRID_GRID_USER_DEFAULT_PROFILE` (`default_profile_id`),
    CONSTRAINT `FK_CUSTOM_GRID_GRID_USER_GRID`
        FOREIGN KEY (`grid_id`) REFERENCES `{$tables['grid']}` (`grid_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `FK_CUSTOM_GRID_GRID_USER_USER`
        FOREIGN KEY (`user_id`) REFERENCES `{$tables['admin_user']}` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `FK_CUSTOM_GRID_GRID_USER_DEFAULT_PROFILE`
        FOREIGN KEY (`default_profile_id`) REFERENCES `{$tables['grid_profile']}` (`profile_id`)
        ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    CREATE TABLE IF NOT EXISTS `{$tables['options_source']}` (
    `source_id` int(10) unsigned NOT NULL auto_increment,
    `name` varchar(255) character set utf8 NOT NULL,
    `description` text character set utf8 NOT NULL,
    `type` enum('custom_list', 'mage_model') character set utf8 NOT NULL,
    PRIMARY KEY (`source_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    CREATE TABLE IF NOT EXISTS `{$tables['source_model']}` (
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
    CONSTRAINT `FK_CUSTOMGRID_OPTIONS_SOURCE_MODEL_SOURCE`
        FOREIGN KEY (`source_id`) REFERENCES `{$tables['options_source']}` (`source_id`)
        ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    CREATE TABLE IF NOT EXISTS `{$tables['source_option']}` (
    `option_id` int(10) unsigned NOT NULL auto_increment,
    `source_id` int(10) unsigned NOT NULL,
    `value` varchar(255) character set utf8 NOT NULL,
    `label` varchar(255) character set utf8 NOT NULL,
    PRIMARY KEY (`option_id`),
    KEY `FK_CUSTOMGRID_OPTIONS_SOURCE_OPTION_SOURCE` (`source_id`),
    CONSTRAINT `FK_CUSTOMGRID_OPTIONS_SOURCE_OPTION_SOURCE`
        FOREIGN KEY (`source_id`) REFERENCES `{$tables['options_source']}` (`source_id`)
        ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    "
);

$connection->addConstraint(
    'FK_CUSTOM_GRID_GRID_BASE_PROFILE_GRID_PROFILE',
    $tables['grid'],
    'base_profile_id',
    $tables['grid_profile'],
    'profile_id',
    'RESTRICT',
    'CASCADE'
);

$connection->addConstraint(
    'FK_CUSTOM_GRID_GRID_GLOBAL_DEFAULT_PROFILE_GRID_PROFILE',
    $tables['grid'],
    'global_default_profile_id',
    $tables['grid_profile'],
    'profile_id',
    'SET NULL',
    'CASCADE'
);

$installer->endSetup();
