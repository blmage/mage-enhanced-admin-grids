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
 * @copyright  Copyright (c) 2014 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

$installer  = $this;
$installer->startSetup();
$connection = $installer->getConnection();

$tables = array(
    'grid' => $installer->getTable('customgrid/grid'),
);

/**
 * Changes to "grid" table
 */

// New flag columns

$flagColumns = array(
    'has_varying_block_id',
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

$installer->endSetup();