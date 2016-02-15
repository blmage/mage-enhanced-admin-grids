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
    'grid'          => $installer->getTable('customgrid/grid'),
    'grid_column'   => $installer->getTable('customgrid/grid_column'),
    'grid_profile'  => $installer->getTable('customgrid/grid_profile'),
    'grid_role'     => $installer->getTable('customgrid/grid_role'),
    'grid_user'     => $installer->getTable('customgrid/grid_user'),
    'role_profile'  => $installer->getTable('customgrid/role_profile'),
    'system_config' => $installer->getTable('core/config_data'),
);

/**
 * Changes to "grid" table
 */

// New columns

$connection->addColumn(
    $tables['grid'],
    'forced_type_code',
    'varchar(255) character set utf8 default NULL'
);

$varNameColumns = array('page', 'limit', 'sort', 'dir', 'filter');

foreach ($varNameColumns as $varNameColumn) {
    $connection->addColumn(
        $tables['grid'],
        'var_name_' . $varNameColumn,
        'varchar(255) character set utf8 default NULL'
    );
}

$connection->addColumn(
    $tables['grid'],
    'ignore_custom_headers',
    'tinyint(1) unsigned default NULL'
);

$connection->addColumn(
    $tables['grid'],
    'ignore_custom_widths',
    'tinyint(1) unsigned default NULL'
);

$connection->addColumn(
    $tables['grid'],
    'ignore_custom_alignments',
    'tinyint(1) unsigned default NULL'
);

$connection->addColumn(
    $tables['grid'],
    'pagination_values',
    'varchar(255) character set utf8 default NULL'
);

$connection->addColumn(
    $tables['grid'],
    'default_pagination_value',
    'int(10) unsigned default NULL'
);

$connection->addColumn(
    $tables['grid'],
    'merge_base_pagination',
    'tinyint(1) unsigned default NULL'
);

$connection->addColumn(
    $tables['grid'],
    'pin_header',
    'tinyint(1) unsigned default NULL'
);

$connection->addColumn(
    $tables['grid'],
    'profiles_default_restricted',
    'tinyint(1) unsigned default NULL'
);

$connection->addColumn(
    $tables['grid'],
    'profiles_default_assigned_to',
    'text character set utf8 default NULL'
);

// Consistency renamings

$connection->changeColumn(
    $tables['grid'],
    'type',
    'type_code',
    'varchar(255) character set utf8 NOT NULL'
);

$connection->changeColumn(
    $tables['grid'],
    'max_attribute_column_id',
    'max_attribute_column_base_block_id',
    'int(10) unsigned NOT NULL default 0'
);

$connection->changeColumn(
    $tables['grid'],
    'max_custom_column_id',
    'max_custom_column_base_block_id',
    'int(10) unsigned NOT NULL default 0'
);

// Update serialized default filters (renamings)

$grids = $connection->select()
    ->from($tables['grid'])
    ->where('default_filter IS NOT NULL');

$grids = $connection->fetchAll($grids);

foreach ($grids as $grid) {
    if (is_array($defaultFilter = @unserialize($grid['default_filter']))) {
        foreach ($defaultFilter as $key => $data) {
            if (isset($data['column'])
                && is_array($data['column'])
                && array_key_exists('custom_params', $data['column'])) {
                $defaultFilter[$key]['column']['customization_params'] = $data['column']['custom_params'];
                unset($defaultFilter[$key]['column']['custom_params']);
            }
        }
    } else {
        $defaultFilter = array();
    }
    
    $connection->update(
        $tables['grid'],
        array('default_filter' => serialize($defaultFilter)),
        $connection->quoteInto('grid_id = ?', $grid['grid_id'])
    );
}

/**
 * Changes to "grid_column" table
 */

// New columns

$connection->addColumn(
    $tables['grid_column'],
    'filter_only',
    'tinyint(1) unsigned NOT NULL default 0'
);

// Clean-up the temporary solution

$connection->update(
    $tables['grid_column'],
    array('is_visible' => 1, 'filter_only' => 1),
    $connection->quoteInto('is_visible = ?', 2)
);

// Consistency renamings

$connection->changeColumn(
    $tables['grid_column'],
    'id',
    'block_id',
    'varchar(255) character set utf8 NOT NULL'
);

$connection->changeColumn(
    $tables['grid_column'],
    'missing',
    'is_missing',
    'tinyint(1) unsigned NOT NULL default 0'
);

$connection->changeColumn(
    $tables['grid_column'],
    'allow_edit',
    'is_edit_allowed',
    'tinyint(1) unsigned NOT NULL default 1'
);

$connection->changeColumn(
    $tables['grid_column'],
    'custom_params',
    'customization_params',
    'text character set utf8 default NULL'
);

$connection->changeColumn(
    $tables['grid_column'],
    'filter_only',
    'is_only_filterable',
    'tinyint(1) unsigned NOT NULL default 0'
);

// Fix wrong constraint type

$connection->addConstraint(
    'FK_CUSTOM_GRID_GRID_COLUMN_PROFILE_GRID_PROFILE',
    $tables['grid_column'],
    'profile_id',
    $tables['grid_profile'],
    'profile_id',
    'CASCADE',
    'CASCADE'
);

/**
 * Changes to "grid_profile" table
 */

// Consistency renamings

$connection->changeColumn(
    $tables['grid_profile'],
    'default_direction',
    'default_dir',
    "enum('asc', 'desc') character set utf8 default NULL"
);

$connection->changeColumn(
    $tables['grid_profile'],
    'default_filters',
    'default_filter',
    'text character set utf8 default NULL'
);

// New column

$connection->addColumn(
    $tables['grid_profile'],
    'is_restricted',
    'tinyint(1) unsigned NOT NULL default 0'
);

$connection->addColumn(
    $tables['grid_profile'],
    'is_global_default',
    'tinyint(1) unsigned NOT NULL default 0'
);

/**
 * Changes to "grid_role" table
 */

// New column

$connection->addColumn(
    $tables['grid_role'],
    'default_base_profile',
    'tinyint(1) unsigned NOT NULL default 0'
);

// Unique key

$connection->addKey(
    $tables['grid_role'],
    'UNQ_CUSTOM_GRID_ROLE_GRID_ROLE',
    array('grid_id', 'role_id'),
    'unique'
);

// Useless column

$connection->dropColumn($tables['grid_role'], 'available_profiles');

/**
 * Changes to "grid_user" table
 */

// New column

$connection->addColumn(
    $tables['grid_user'],
    'default_base_profile',
    'tinyint(1) unsigned NOT NULL default 0'
);

// Unique key

$connection->addKey(
    $tables['grid_user'],
    'UNQ_CUSTOM_GRID_USER_GRID_USER',
    array('grid_id', 'user_id'),
    'unique'
);

/**
 * New "role_profile" table
 */

$installer->run(
    "
    CREATE TABLE IF NOT EXISTS `{$tables['role_profile']}` (
    `role_profile_id` int(10) unsigned NOT NULL auto_increment,
    `role_id` int(10) unsigned NOT NULL,
    `profile_id` int(10) unsigned NOT NULL,
    PRIMARY KEY (`role_profile_id`),
    UNIQUE KEY `UNQ_CUSTOM_GRID_ROLE_PROFILE_ROLE_PROFILE` (`role_id`, `profile_id`),
    KEY `FK_CUSTOM_GRID_ROLE_PROFILE_ROLE` (`role_id`),
    KEY `FK_CUSTOM_GRID_ROLE_PROFILE_PROFILE` (`profile_id`),
    CONSTRAINT `FK_CUSTOM_GRID_ROLE_PROFILE_ROLE`
        FOREIGN KEY (`role_id`) REFERENCES `{$this->getTable('admin/role')}` (`role_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `FK_CUSTOM_GRID_ROLE_PROFILE_PROFILE`
        FOREIGN KEY (`profile_id`) REFERENCES `{$tables['grid_profile']}` (`profile_id`)
        ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    "
);

/**
 * Changes to the system configuration
 */

// Paths

$connection->update(
    $tables['system_config'],
    array('path' => new Zend_Db_Expr('REPLACE(path, "custom_default_params", "default_params_behaviours")')),
    $connection->quoteInto('path LIKE ?', 'customgrid/custom_default_params/%')
);

$connection->update(
    $tables['system_config'],
    array('path' => new Zend_Db_Expr('REPLACE(path, "customgrid/editor_sitemap", "customgrid_editors/sitemap")')),
    $connection->quoteInto('path LIKE ?', 'customgrid/editor_sitemap/%')
);

$installer->endSetup();
