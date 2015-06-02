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
 * @copyright  Copyright (c) 2015 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

$installer  = $this;
$installer->startSetup();
$connection = $installer->getConnection();

$tables = array(
    'grid'         => $installer->getTable('customgrid/grid'),
    'grid_profile' => $installer->getTable('customgrid/grid_profile'),
    'grid_role'    => $installer->getTable('customgrid/grid_role'),
);

/**
 * Changes to "grid" table
 */

// New columns

$connection->addColumn(
    $tables['grid'],
    'profiles_remembered_session_params',
    'varchar(255) default NULL'
);

// New flag columns

$connection->addColumn(
    $tables['grid'],
    'has_varying_block_id',
    'tinyint(1) unsigned default 0'
);

$flagColumns = array(
    'display_system_part',
    'rss_links_window',
    'hide_original_export_block',
    'hide_filter_reset_button',
);

foreach ($flagColumns as $columnName) {
    $connection->addColumn(
        $tables['grid'],
        $columnName,
        'tinyint(1) unsigned default NULL'
    );
}

/**
 * Changes to "grid_profile" table
 */

// New columns
 
$connection->addColumn(
    $tables['grid_profile'],
    'remembered_session_params',
    'varchar(255) default NULL'
);

/**
 * Changes to "grid_role" table
 */

// New columns
 
$connection->addColumn(
    $tables['grid_role'],
    'base_profile_assigned',
    'tinyint(1) NOT NULL default 0'
);

$installer->endSetup();