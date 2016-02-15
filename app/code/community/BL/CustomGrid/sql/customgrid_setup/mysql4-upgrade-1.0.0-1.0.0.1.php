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
    'grid'         => $installer->getTable('customgrid/grid'),
    'grid_column'  => $installer->getTable('customgrid/grid_column'),
    'grid_profile' => $installer->getTable('customgrid/grid_profile'),
    'grid_role'    => $installer->getTable('customgrid/grid_role'),
    'grid_user'    => $installer->getTable('customgrid/grid_user'),
    'grid_user'    => $installer->getTable('customgrid/grid_user'),
    'role_profile' => $installer->getTable('customgrid/role_profile'),
);

/**
 * Changes to "grid" table
 */

// New columns + constraints

$connection->addColumn(
    $tables['grid'],
    'base_profile_id',
    'int(10) unsigned default NULL'
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

$connection->addColumn(
    $tables['grid'],
    'global_default_profile_id',
    'int(10) unsigned default NULL'
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

/**
 * Drop the "is_global_default" profile flag in favor of a foreign key on the grids
 */

// Adapt the flag values

$defaultProfilesQuery = $connection->select()
    ->from(array('profiles' => $tables['grid_profile']), array('profile_id'))
    ->where('is_global_default = ?', 1)
    ->where($connection->quoteIdentifier('profiles.grid_id') . ' = ' . $connection->quoteIdentifier('grids.grid_id'))
    ->assemble();

$connection->query(
    'UPDATE ' . $connection->quoteTableAs($tables['grid'], 'grids')
    . ' SET ' . $connection->quoteIdentifier('grids.global_default_profile_id') . ' = (' . $defaultProfilesQuery . ')'
);

// Drop the obsolete column

$connection->dropColumn($tables['grid_profile'], 'is_global_default');

/**
 * Base profiles "embodiment"
 */

$connection->beginTransaction();

try {
    // Create a base profile for each grid
    
    $gridsValues = array(
        'name'           => new Zend_Db_Expr($connection->quote('')),
        'is_restricted'  => new Zend_Db_Expr($connection->quote(0)),
        'grid_id'        => 'grid_id',
        'default_page'   => 'default_page',
        'default_limit'  => 'default_limit',
        'default_sort'   => 'default_sort',
        'default_dir'    => 'default_dir',
        'default_filter' => 'default_filter',
    );
    
    $gridsSelect = $connection->select()->from($tables['grid'], $gridsValues);
    
    $connection->query(
        'INSERT INTO ' . $connection->quoteIdentifier($tables['grid_profile'])
        . ' (' . implode(',', array_map(array($connection, 'quoteIdentifier'), array_keys($gridsValues))). ')'
        . ' (' . $gridsSelect->assemble() . ')'
    );
    
    // Mark the base profiles as such
    
    $baseProfilesQuery = $connection->select()
        ->from(array('main' => $tables['grid_profile']), array('profile_id'))
        ->where('name = ?', '')
        ->where($connection->quoteIdentifier('main.grid_id'). ' = ' . $connection->quoteIdentifier('foreign.grid_id'))
        ->assemble();
    
    $connection->query(
        'UPDATE ' . $connection->quoteTableAs($tables['grid'], 'foreign')
        . ' SET ' . $connection->quoteIdentifier('foreign.base_profile_id') . ' = (' . $baseProfilesQuery . ')'
    );
    
    // Assign the corresponding columns to their newly created profiles
    
    $connection->query(
        'UPDATE ' . $connection->quoteTableAs($tables['grid_column'], 'foreign')
        . ' SET ' . $connection->quoteIdentifier('foreign.profile_id') . ' = (' . $baseProfilesQuery . ')'
        . ' WHERE ' . $connection->quoteIdentifier('foreign.profile_id') . ' IS NULL'
    );
    
    // Adapt the default profile IDs for roles
    
    $connection->query(
        'UPDATE ' . $connection->quoteTableAs($tables['grid_role'], 'foreign')
        . ' SET ' . $connection->quoteIdentifier('foreign.default_profile_id') . ' = (' . $baseProfilesQuery . ')'
        . ' WHERE ' . $connection->quoteIdentifier('foreign.default_base_profile') . ' = ' . $connection->quote(1)
    );
    
    // Adapt the default profile IDs for users
    
    $connection->query(
        'UPDATE ' . $connection->quoteTableAs($tables['grid_user'], 'foreign')
        . ' SET ' . $connection->quoteIdentifier('foreign.default_profile_id') . ' = (' . $baseProfilesQuery . ')'
        . ' WHERE ' . $connection->quoteIdentifier('foreign.default_base_profile') . ' = ' . $connection->quote(1)
    );
    
    // Re-create the corresponding roles assignations
    
    $assignedProfilesSelect = $connection->select()
        ->from(array('profiles' => $tables['grid_profile']), array('profile_id'))
        ->joinInner(
            array('roles' => $tables['grid_role']),
            $connection->quoteIdentifier('roles.grid_id') . ' = ' . $connection->quoteIdentifier('profiles.grid_id'),
            array('role_id')
        )
        ->where('name = ?', '')
        ->where('base_profile_assigned = ?', 1);
    
    $connection->query(
        'INSERT INTO ' . $connection->quoteIdentifier($tables['role_profile'])
        . ' (' . $connection->quoteIdentifier('role_id') . ',' . $connection->quoteIdentifier('profile_id') . ')'
        . ' (' . $assignedProfilesSelect->assemble() . ')'
    );
    
    $connection->commit();
    
} catch (Exception $e) {
    $connection->rollback();
    throw $e;
}

// Drop the obsolete columns from the grids table..

$obsoleteGridColumns = array(
    'default_page',
    'default_limit',
    'default_sort',
    'default_dir',
    'default_filter',
);

foreach ($obsoleteGridColumns as $gridColumn) {
    $connection->dropColumn($tables['grid'], $gridColumn);
}

// ..from the roles table..

$obsoleteRoleColumns = array(
    'default_base_profile',
    'base_profile_assigned'
);

foreach ($obsoleteRoleColumns as $gridColumn) {
    $connection->dropColumn($tables['grid_role'], $gridColumn);
}

// .. and from the users table

$connection->dropColumn($tables['grid_user'], 'default_base_profile');
