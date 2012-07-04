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

$installer  = $this;
$installer->startSetup();
$connection = $installer->getConnection();

$tables = array(
    'grid'         => $installer->getTable('customgrid/grid'),
    'grid_column'  => $installer->getTable('customgrid/grid_column'),
    'grid_profile' => $installer->getTable('customgrid/grid_profile'),
    'grid_role'    => $installer->getTable('customgrid/grid_role'),
    'grid_user'    => $installer->getTable('customgrid/grid_user'),
);

/**
* New tables
*/

// Note: the "grid_profile" one and related columns from "grid_role" / "grid_user" are for potential further use

$installer->run("
CREATE TABLE IF NOT EXISTS `{$tables['grid_profile']}` (
`profile_id` int(10) unsigned NOT NULL auto_increment,
`grid_id` int(10) unsigned NOT NULL,
`name` varchar(255) character set utf8 NOT NULL,
`default_page` int(10) unsigned default NULL,
`default_limit` int(10) unsigned default NULL,
`default_sort` varchar(255) character set utf8 default NULL,
`default_direction` enum('asc', 'desc') character set utf8 default NULL,
`default_filters` text character set utf8 default NULL,
PRIMARY KEY (`profile_id`),
KEY `FK_CUSTOM_GRID_GRID_PROFILE_GRID` (`grid_id`),
CONSTRAINT `FK_CUSTOM_GRID_GRID_PROFILE_GRID` FOREIGN KEY (`grid_id`) REFERENCES `{$tables['grid']}` (`grid_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{$tables['grid_role']}` (
`grid_role_id` int(10) unsigned NOT NULL auto_increment,
`grid_id` int(10) unsigned NOT NULL,
`role_id` int(10) unsigned NOT NULL,
`permissions` text character set utf8 default NULL,
`default_profile_id` int(10) unsigned default NULL,
`available_profiles` text character set utf8 default NULL,
PRIMARY KEY (`grid_role_id`),
KEY `FK_CUSTOM_GRID_GRID_ROLE_GRID` (`grid_id`),
KEY `FK_CUSTOM_GRID_GRID_ROLE_ROLE` (`role_id`),
KEY `FK_CUSTOM_GRID_GRID_ROLE_DEFAULT_PROFILE` (`default_profile_id`),
CONSTRAINT `FK_CUSTOM_GRID_GRID_ROLE_GRID` FOREIGN KEY (`grid_id`) REFERENCES `{$tables['grid']}` (`grid_id`) ON DELETE CASCADE,
CONSTRAINT `FK_CUSTOM_GRID_GRID_ROLE_ROLE` FOREIGN KEY (`role_id`) REFERENCES `{$this->getTable('admin/role')}` (`role_id`) ON DELETE CASCADE,
CONSTRAINT `FK_CUSTOM_GRID_GRID_ROLE_DEFAULT_PROFILE` FOREIGN KEY (`default_profile_id`) REFERENCES `{$tables['grid_profile']}` (`profile_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `{$tables['grid_user']}` (
`grid_user_id` int(10) unsigned NOT NULL auto_increment,
`grid_id` int(10) unsigned NOT NULL,
`user_id` mediumint(9) unsigned NOT NULL,
`default_profile_id` int(10) unsigned default NULL,
PRIMARY KEY (`grid_user_id`),
KEY `FK_CUSTOM_GRID_GRID_USER_GRID` (`grid_id`),
KEY `FK_CUSTOM_GRID_GRID_USER_USER` (`user_id`),
KEY `FK_CUSTOM_GRID_GRID_USER_DEFAULT_PROFILE` (`default_profile_id`),
CONSTRAINT `FK_CUSTOM_GRID_GRID_USER_GRID` FOREIGN KEY (`grid_id`) REFERENCES `{$tables['grid']}` (`grid_id`) ON DELETE CASCADE,
CONSTRAINT `FK_CUSTOM_GRID_GRID_USER_USER` FOREIGN KEY (`user_id`) REFERENCES `{$this->getTable('admin/user')}` (`user_id`) ON DELETE CASCADE,
CONSTRAINT `FK_CUSTOM_GRID_GRID_USER_DEFAULT_PROFILE` FOREIGN KEY (`default_profile_id`) REFERENCES `{$tables['grid_profile']}` (`profile_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

/**
* New columns for "customgrid_grid" table
*/

$connection->addColumn(
    $tables['grid'],
    'max_custom_column_id',
    'int(10) unsigned NOT NULL default 0 AFTER `max_attribute_column_id`'
);

$connection->addColumn(
    $tables['grid'],
    'default_page_behaviour',
    "enum ('default','force_original','force_custom') character set utf8 default NULL"
);

$connection->addColumn(
    $tables['grid'],
    'default_limit_behaviour',
    "enum ('default','force_original','force_custom') character set utf8 default NULL"
);

$connection->addColumn(
    $tables['grid'],
    'default_sort_behaviour',
    "enum ('default','force_original','force_custom') character set utf8 default NULL"
);

$connection->addColumn(
    $tables['grid'],
    'default_dir_behaviour',
    "enum ('default','force_original','force_custom') character set utf8 default NULL"
);

$connection->addColumn(
    $tables['grid'],
    'default_filter_behaviour',
    "enum ('default','force_original','force_custom','merge_default','merge_on_original','merge_on_custom') character set utf8 default NULL"
);

/**
* Columns changes for "customgrid_grid" table
*/

if ($connection->tableColumnExists($tables['grid'], 'default_direction')) {
    $connection->changeColumn(
        $tables['grid'],
        'default_direction',
        'default_dir',
        "enum('asc','desc') character set utf8 default NULL"
    );
}

if ($connection->tableColumnExists($tables['grid'], 'default_filters')) {
    $connection->changeColumn(
        $tables['grid'],
        'default_filters',
        'default_filter',
        'text character set utf8 default NULL'
    );
}

/**
* New column for "customgrid_column" table
*/

// This one is for potential further use

$connection->addColumn(
    $tables['grid_column'],
    'profile_id',
    'int(10) unsigned default NULL'
);

$connection->addConstraint(
    'FK_CUSTOM_GRID_GRID_COLUMN_PROFILE_GRID_PROFILE',
    $tables['grid_column'],
    'profile_id',
    $tables['grid_profile'],
    'profile_id',
    'set null'
);

$connection->addColumn(
    $tables['grid_column'],
    'custom_params',
    'text character set utf8 default NULL'
);

/**
* Column changes for "customgrid_column" table
*/

if ($connection->tableColumnExists($tables['grid_column'], 'origin')) {
    $connection->changeColumn(
        $installer->getTable('customgrid/grid_column'),
        'origin',
        'origin',
        "enum('grid','collection','attribute','custom') character set utf8 NOT NULL default 'grid'"
    );
}

$installer->endSetup();