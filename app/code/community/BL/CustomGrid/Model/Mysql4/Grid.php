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

class BL_CustomGrid_Model_Mysql4_Grid extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('customgrid/grid', 'grid_id');
    }
    
    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        $this->_saveColumns($object);
        $this->_saveRolesConfig($object);
        return $this;
    }
    
    protected function _saveColumns(Mage_Core_Model_Abstract $object)
    {
        $gridId = $object->getId();
        $write  = $this->_getWriteAdapter();
        $columnsTable = $this->getTable('customgrid/grid_column');
        $columnsIds   = array();
        
        foreach ($object->getColumns() as $column) {
            if (isset($column['filter_only'])) {
                if ($column['filter_only']
                    && isset($column['is_visible'])
                    && $column['is_visible']) {
                    $column['is_visible'] = 2;
                }
                unset($column['filter_only']); // No new database field to avoid a new setup
            }
            if (isset($column['column_id']) && ($column['column_id'] > 0)) {
                // Update existing columns
                $write->update($columnsTable, $column, $write->quoteInto('column_id = ?', $column['column_id']));
                $columnsIds[] = $column['column_id'];
            } else {
                // Insert new columns
                $column['grid_id'] = $gridId;
                $write->insert($columnsTable, $column);
                $columnsIds[] = $write->lastInsertId();
            }
        }
        
        // Delete obsolete columns (all not inserted / updated)
        if (empty($columnsIds)) {
            $columnsIds = array(0);
        }
        
        $write->delete(
            $columnsTable,
            $write->quoteInto('grid_id = ' . $gridId  . ' AND column_id NOT IN (?)', $columnsIds)
        );
        
        return $this;
    }
    
    protected function _saveRolesConfig(Mage_Core_Model_Abstract $object)
    {
        $helper = Mage::helper('customgrid');
        $gridId = $object->getId();
        $write  = $this->_getWriteAdapter();
        $rolesTable = $this->getTable('customgrid/grid_role');
        $rolesIds   = array();
        
        foreach ($object->getRolesConfig() as $roleId => $config) {
            $select = $write->select()
                ->from($rolesTable, 'grid_role_id')
                ->where('grid_id = ?', $gridId)
                ->where('role_id = ?', $roleId);
            
            if ($gridRoleId = $write->fetchOne($select)) {
                // Update existing role config
                $write->update($rolesTable, array(
                        'default_profile_id' => null, // $config['default_profile_id'],
                        'available_profiles' => null, // $helper->implodeArray($config['available_profiles']),
                        'permissions' => serialize($config['permissions']),
                    ),
                    $write->quoteInto('grid_role_id = ?', $gridRoleId)
                );
                $rolesIds[] = $gridRoleId;
            } else {
                // Insert new role config
                $write->insert($rolesTable, array(
                    'grid_id'     => (int) $gridId,
                    'role_id'     => (int) $roleId,
                    'default_profile_id' => null, // $config['default_profile_id'],
                    'available_profiles' => null, // $helper->implodeArray($config['available_profiles']),
                    'permissions' => serialize($config['permissions']),
                ));
                $rolesIds[] = $write->lastInsertId();
            }
        }
        
        // Delete obsolete permissions (all not inserted / updated)
        if (empty($rolesIds)) {
            $rolesIds = array(0);
        }
        
        $write->delete(
            $rolesTable,
            $write->quoteInto('grid_id = ' . $gridId  . ' AND grid_role_id NOT IN (?)', $rolesIds)
        );
        
        return $this;
    }
    
    public function getGridProfiles()
    {
        $read = $this->_getReadAdapter();
        $profilesTable = $this->getTable('customgrid/grid_profile');
        
        return $read->fetchAll($read->select()
            ->from($profilesTable)
            ->where('grid_id = ?', $gridId));
    }
    
    public function getGridColumns($gridId)
    {
        $read = $this->_getReadAdapter();
        $columnsTable = $this->getTable('customgrid/grid_column');
        
        return $read->fetchAll($read->select()
            ->from($columnsTable)
            ->columns('*')
            ->columns(array('is_visible'  => new Zend_Db_Expr('IF(is_visible=2, 1, is_visible)')))
            ->columns(array('filter_only' => new Zend_Db_Expr('IF(is_visible=2, 1, 0)')))
            ->where('grid_id = ?', $gridId));
    }
    
    public function getGridRoles($gridId)
    {
        $helper = Mage::helper('customgrid');
        $read   = $this->_getReadAdapter();
        $roles  = array();
        $rolesTable = $this->getTable('customgrid/grid_role');
        
        $result = $read->fetchAll($read->select()
            ->from($rolesTable)
            ->where('grid_id = ?', $gridId));
        
        foreach ($result as $config) {
            $roles[$config['role_id']] = array(
                'available_profiles' => array(), // explode(',', $config['available_profiles']),
                'default_profile_id' => null, // $config['default_profile_id'],
                'permissions' => $helper->unserializeArray($config['permissions']),
            );
        }
        
        return $roles;
    }
}