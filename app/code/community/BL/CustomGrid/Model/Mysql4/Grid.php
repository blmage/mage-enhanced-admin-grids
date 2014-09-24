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

class BL_CustomGrid_Model_Mysql4_Grid extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('customgrid/grid', 'grid_id');
    }
    
    protected function _getGridsTable()
    {
        return $this->getTable('customgrid/grid');
    }
    
    protected function _getProfilesTable()
    {
        return $this->getTable('customgrid/grid_profile');
    }
    
    protected function _getColumnsTable()
    {
        return $this->getTable('customgrid/grid_column');
    }
    
    protected function _getUsersTable()
    {
        return $this->getTable('customgrid/grid_user');
    }
    
    protected function _getRolesTable()
    {
        return $this->getTable('customgrid/grid_role');
    }
    
    protected function _getRolesProfilesTable()
    {
        return $this->getTable('customgrid/role_profile');
    }
    
    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        if (!$object->getId()) {
            $object->setData('_blcg_is_new_grid_model', true);
        }
        return $this;
    }
    
    protected function _prepareDataForSave(Mage_Core_Model_Abstract $object)
    {
        $data = parent::_prepareDataForSave($object);
        
        if (!$object->getData('_blcg_is_new_grid_model') && !$object->getProfile()->isBase()) {
            // Non-default profile : do not save its specific values in the grids table but in the profiles one
            $profileValues = array();
            
            foreach ($object->getStashableProfileKeys() as $key) {
                if (isset($data[$key])) {
                    $profileValues[$key] = $data[$key];
                    unset($data[$key]);
                } else {
                    $profileValues[$key] = null;
                }
            }
            
            $object->setData('saveable_profile_values', $profileValues);
        }
        
        return $data;
    }
    
    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        if (!$object->getData('_blcg_is_new_grid_model') && !$object->getProfile()->isBase()) {
            $profileValues = $object->getData('saveable_profile_values');
            $object->unsetData('saveable_profile_values');
            
            if (is_array($profileValues)) {
                $this->updateProfile($object->getId(), $object->getProfileId(), $profileValues, false);
            }
        }
        
        $object->unsetData('_blcg_is_new_grid_model');
        $this->_saveColumns($object);
        $this->_saveRolesConfig($object);
        return $this;
    }
    
    protected function _saveColumns(Mage_Core_Model_Abstract $object)
    {
        if (!is_array($columns = $object->getData('columns'))) {
            return $this;
        }
        
        $write  = $this->_getWriteAdapter();
        $columnsTable = $this->_getColumnsTable();
        
        $gridId = $object->getId();
        $profileId = $object->getProfileId();
        $profileId = ($profileId === BL_CustomGrid_Model_Grid::BASE_PROFILE_ID ? null : $profileId);
        $columnsIds  = array();
        $columnsKeys = array_flip(array_keys($write->describeTable($columnsTable)));
        
        foreach ($columns as $column) {
            $column = array_intersect_key($column->getData(), $columnsKeys);
            
            if (isset($column['column_id']) && ($column['column_id'] > 0)) {
                $write->update(
                    $columnsTable,
                    $column,
                    $write->quoteInto('column_id = ?', $column['column_id'])
                );
                
                $columnsIds[] = $column['column_id'];
            } else {
                $column['grid_id'] = $gridId;
                $column['profile_id'] = $profileId;
                $write->insert($columnsTable, $column);
                $columnsIds[] = $write->lastInsertId();
            }
        }
        
        if (empty($columnsIds)) {
            $columnsIds = array(-1);
        }
        
        $write->delete(
            $columnsTable,
            $write->quoteInto('grid_id = ?', $gridId)
                . ' AND '
                . (is_null($profileId) ? 'profile_id IS NULL' : $write->quoteInto('profile_id = ?', $profileId))
                . ' AND '
                . $write->quoteInto('column_id NOT IN (?)', $columnsIds)
        );
        
        return $this;
    }
    
    protected function _saveRolesConfig(Mage_Core_Model_Abstract $object)
    {
        if (!is_array($rolesConfig = $object->getData('roles_config'))) {
            return $this;
        }
        
        $write = $this->_getWriteAdapter();
        $rolesTable = $this->getTable('customgrid/grid_role');
        
        $gridId = $object->getId();
        $rolesValues = array();
        
        if (!empty($rolesConfig)) {
            foreach ($rolesConfig as $roleId => $roleConfig) {
                $rolesValues[] = array(
                    'grid_id' => $gridId,
                    'role_id' => $roleId,
                    'default_profile_id' => $roleConfig->getData('default_profile_id'),
                    'permissions' => serialize($roleConfig->getDataSetDefault('permissions', array())),
                );
            }
            
            $write->insertOnDuplicate($rolesTable, $rolesValues, array('permissions'));
            $rolesIds = array_keys($rolesConfig);
        } else {
            $rolesIds = array(-1);
        }
        
        $write->delete(
            $rolesTable,
            $write->quoteInto('grid_id = ?', $gridId)
                . ' AND '
                . $write->quoteInto('role_id NOT IN (?)', $rolesIds)
        );
        
        return $this;
    }
    
    protected function _arrangeResult(array $values, $valueKey)
    {
        $result = array();
        
         foreach ($values as $key => $value) {
             if (isset($value[$valueKey])) {
                 $key = $value[$valueKey];
             } 
             $result[$key] = $value;
         }
        
        return $result;
    }
    
    protected function _arrangeGroupedResult(array $values, $groupKey, $valueKey=null)
    {
        $result = array();
        
        foreach ($values as $key => $value) {
            if (isset($value[$groupKey])) {
                $group = $value[$groupKey];
                
                if (!empty($valueKey)
                    && isset($value[$valueKey])) {
                    $key = $value[$valueKey];
                }
                if  (isset($result[$group])) {
                    $result[$group][$key] = $value;
                } else {
                    $result[$group] = array($key => $value);
                }
            }
        }
        
        return $result;
    }
    
    public function getGridProfiles($gridId)
    {
        $read   = $this->_getReadAdapter();
        $select = $read->select()->from($this->_getProfilesTable());
        
        if (is_array($gridId)) {
            $select->where('grid_id IN (?)', $gridId);
        } else {
            $select->where('grid_id = ?', $gridId);
        }
        
        $result = $read->fetchAll($select);
        
        if (is_array($gridId)) {
            $result = $this->_arrangeGroupedResult($result, 'grid_id', 'profile_id');
        } else {
            $result = $this->_arrangeResult($result, 'profile_id');
        }
        
        return $result;
    }
    
    public function getGridColumns($gridId, $profileId)
    {
        $read = $this->_getReadAdapter();
        
        $select = $read->select()
            ->from($this->_getColumnsTable())
            ->where('grid_id = ?', $gridId);
        
        if ($profileId === BL_CustomGrid_Model_Grid::BASE_PROFILE_ID) {
            $select->where('profile_id IS NULL');
        } else {
            $select->where('profile_id = ?', $profileId);
        }
        
        return $read->fetchAll($select);
    }
    
    public function getGridRoles($gridId)
    {
        $read = $this->_getReadAdapter();
        $roles  = array();
        
        // Retrieve every existing admin role for each given grid
        $select = $read->select()
            ->distinct()
            ->from(array('cgg' => $this->_getGridsTable()), array('grid_id'))
            ->from(array('ar'  => $this->getTable('admin/role')), array('role_id'))
            ->joinLeft(
                array('cggr' => $this->_getRolesTable()),
                '(cggr.role_id = ar.role_id) AND (cggr.grid_id = cgg.grid_id)',
                array('permissions', 'default_profile_id', 'default_base_profile')
            )
            ->joinLeft(
                array('cggp' => $this->_getProfilesTable()),
                'cggp.grid_id = cgg.grid_id',
                array()
            )
            ->joinLeft(
                array('cgrp' => $this->_getRolesProfilesTable()),
                '(cgrp.role_id = ar.role_id) AND (cgrp.profile_id = cggp.profile_id)',
                array('profile_id')
            )
            ->where('ar.role_type = ?', 'G');
        
        if (is_array($gridId)) {
            $select->where('cgg.grid_id IN (?)', $gridId);
        } else {
            $select->where('cgg.grid_id = ?', $gridId);
        }
        
        $result = $read->fetchAll($select);
        $helper = Mage::helper('customgrid');
        
        foreach ($result as $role) {
            $key = $role['grid_id'] . '_' . $role['role_id'];
            
            if (!isset($roles[$key])) {
                $defaultProfileId = $role['default_base_profile']
                    ? BL_CustomGrid_Model_Grid::BASE_PROFILE_ID
                    : $role['default_profile_id'];
                
                $roles[$key] = array(
                    'grid_id' => $role['grid_id'],
                    'role_id' => $role['role_id'],
                    'permissions' => $helper->unserializeArray($role['permissions']),
                    'default_profile_id' => $defaultProfileId,
                    'assigned_profiles_ids' => array(),
                );
            }
            if (isset($role['profile_id'])) {
                $roles[$key]['assigned_profiles_ids'][] = $role['profile_id'];
            }
        }
        
        if (is_array($gridId)) {
            $roles = $this->_arrangeGroupedResult($roles, 'grid_id', 'role_id');
        } else {
            $roles = $this->_arrangeResult($roles, 'role_id');
        }
        
        return $roles;
    }
    
    public function getGridUsers($gridId)
    {
        $read   = $this->_getReadAdapter();
        $select = $read->select()->from($this->_getUsersTable());
        
        if (is_array($gridId)) {
            $select->where('grid_id IN (?)', $gridId);
        } else {
            $select->where('grid_id = ?', $gridId);
        }
        
        $result = $read->fetchAll($select);
        
        foreach ($result as $key => $row) {
            if ($row['default_base_profile']) {
                $result[$key]['default_profile_id'] = BL_CustomGrid_Model_Grid::BASE_PROFILE_ID;
            }
            unset($result[$key]['default_base_profile']);
        }
        
        if (is_array($gridId)) {
            $result = $this->_arrangeGroupedResult($result, 'grid_id', 'user_id');
        } else {
            $result = $this->_arrangeResult($result, 'user_id');
        }
        
        return $result;
    }
    
    protected function _copyProfileColumns($gridId, $fromProfileId, $toProfileId)
    {
        $write = $this->_getWriteAdapter();
        $columnsTable = $this->_getColumnsTable();
        
        $write->delete(
            $columnsTable,
            $write->quoteInto('grid_id = ?', $gridId)
                . ' AND '
                . ($toProfileId === BL_CustomGrid_Model_Grid::BASE_PROFILE_ID
                    ? 'profile_id IS NULL'
                    : $write->quoteInto('profile_id = ?', $toProfileId))
        );
        
        $select = $write->select()
            ->from($columnsTable)
            ->where('grid_id = ?', $gridId);
        
        if ($fromProfileId === BL_CustomGrid_Model_Grid::BASE_PROFILE_ID) {
            $select->where('profile_id IS NULL');
        } else {
            $select->where('profile_id = ?', $fromProfileId);
        }
        
        $result  = $write->fetchAll($select);
        $columns = array();
        
        if ($toProfileId === BL_CustomGrid_Model_Grid::BASE_PROFILE_ID) {
            $toProfileId = null;
        }
        
        foreach ($result as $column) {
            unset($column['column_id']);
            $column['profile_id'] = $toProfileId;
            $columns[] = $column;
        }
        
        $write->insertMultiple($columnsTable, $columns);
        return $this;
    }
    
    protected function _assignProfile($profileId, array $assignedRolesIds=array())
    {
        $write  = $this->_getWriteAdapter();
        $table  = $this->_getRolesProfilesTable();
        $values = array();
        
        foreach ($assignedRolesIds as $roleId) {
            $values[] = array(
                'role_id' => $roleId,
                'profile_id' => $profileId,
            );
        }
        
        if (!empty($assignedRolesIds)) {
            $write->insertOnDuplicate($table, $values);
        } else {
            $assignedRolesIds = array(-1);
        }
        
        $write->delete(
            $table,
            $write->quoteInto('profile_id = ?', $profileId)
                . ' AND '
                . $write->quoteInto('role_id NOT IN (?)', $assignedRolesIds)
        );
        
        return $this;
    }
    
    public function chooseProfileAsDefault($gridId, $profileId, array $values)
    {
        $write = $this->_getWriteAdapter();
        $isBaseProfile = ($profileId === BL_CustomGrid_Model_Grid::BASE_PROFILE_ID);
        $profileId = ($isBaseProfile ? null : $profileId);
        
        $write->beginTransaction();
        
        try {
            $valuesKeys = array(
                'users' => array(
                    'table' => $this->_getUsersTable(),
                    'key'   => 'user_id',
                ),
                'roles' => array(
                    'table' => $this->_getRolesTable(),
                    'key'   => 'role_id',
                ),
            );
            
            foreach ($valuesKeys as $valuesKey => $config) {
                if (isset($values[$valuesKey]) && is_array($values[$valuesKey])) {
                    if (!empty($values[$valuesKey])) {
                        $insert = array();
                        
                        foreach ($values[$valuesKey] as $valueId) {
                            $insert[] = array(
                                'grid_id' => $gridId,
                                $config['key'] => $valueId,
                                'default_profile_id'   => $profileId,
                                'default_base_profile' => $isBaseProfile,
                            );
                        }
                        
                        $write->insertOnDuplicate(
                            $config['table'],
                            $insert,
                            array('default_profile_id', 'default_base_profile')
                        );
                    } else {
                        $values[$valuesKey] = array(-1);
                    }
                    
                    $write->update(
                        $config['table'],
                        array(
                            'default_profile_id' => null,
                            'default_base_profile' => 0,
                        ),
                        $write->quoteInto('grid_id = ?', $gridId)
                            . ' AND '
                            . $write->quoteInto($config['key'] . ' NOT IN (?)', $values[$valuesKey])
                            . ' AND '
                            . ($isBaseProfile
                                ? 'default_profile_id IS NULL AND ' . $write->quoteInto('default_base_profile = ?', 1)
                                : $write->quoteInto('default_profile_id = ?', $profileId))
                    );
                }
            }
            
            if (isset($values['global'])) {
                $table = $this->_getProfilesTable();
                
                if ($values['global']) {
                    $write->update(
                        $table,
                        array('is_global_default' => 0),
                        $write->quoteInto('grid_id = ?', $gridId)
                            . ' AND '
                            . $write->quoteInto('profile_id != ?', ($isBaseProfile ? -1 : $profileId))
                    );
                }
                if (!$isBaseProfile) {
                    $write->update(
                        $table,
                        array('is_global_default' => ($values['global'] ? 1 : 0)),
                        $write->quoteInto('grid_id = ?', $gridId)
                            . ' AND '
                            . $write->quoteInto('profile_id = ?', $profileId)
                    );
                }
            }
            
            $write->commit();
            
        } catch (Exception $e) {
            $write->rollback();
            throw $e;
        }
        
        return $this;
    }
    
    public function copyProfileToNew($gridId, $fromProfileId, array $values)
    {
        $write = $this->_getWriteAdapter();
        $gridsTable = $this->_getGridsTable();
        $profilesTable = $this->_getProfilesTable();
        
        $write->beginTransaction();
        
        try {
            $profileColumns = array(
                'grid_id',
                'default_page',
                'default_limit',
                'default_sort',
                'default_dir',
                'default_filter',
                'name' => new Zend_Db_Expr($write->quote($values['name'])),
                'is_restricted' => new Zend_Db_Expr($values['is_restricted'] ? 1 : 0),
                'is_global_default' => new Zend_Db_Expr(0),
            );
            
            if ($fromProfileId === BL_CustomGrid_Model_Grid::BASE_PROFILE_ID) {
                $select = $write->select()
                    ->from($gridsTable, $profileColumns)
                    ->where('grid_id = ?', $gridId);
            } else {
                $select = $write->select()
                    ->from($profilesTable, $profileColumns)
                    ->where('grid_id = ?', $gridId)
                    ->where('profile_id = ?', $fromProfileId);
            }
            
            $values = $write->fetchRow($select);
            $toProfileId = null;
            
            if (!empty($values)) {
                $write->insert($profilesTable, $values);
                $toProfileId = $write->lastInsertId();
            }
            if (empty($toProfileId)) {
                Mage::throwException($helper->__('The new profile could not be created'));
            }
            
            $this->_copyProfileColumns($gridId, $fromProfileId, $toProfileId);
            
            if ($values['is_restricted']) {
                $this->_assignProfile($toProfileId, $values['assigned_to']);
            }
            
            $write->commit();
            
        } catch (Exception $e) {
            $write->rollback();
            throw $e;
        }
        
        return $toProfileId;
    }
    
    public function copyProfileToExisting($gridId, $fromProfileId, $toProfileId, array $copiedValues)
    {
        $write = $this->_getWriteAdapter();
        $gridsTable = $this->_getGridsTable();
        $profilesTable = $this->_getProfilesTable();
        
        $write->beginTransaction();
        
        try {
            if (isset($copiedValues['default_params']) && is_array($copiedValues['default_params'])) {
                $params = array_intersect(
                    array_keys(
                        array_filter($copiedValues['default_params'])
                    ),
                    array(
                        'default_page',
                        'default_limit',
                        'default_sort',
                        'default_dir',
                        'default_filter',
                    )
                );
                
                if (!empty($params)) {
                    $select = $write->select();
                    
                    if ($fromProfileId === BL_CustomGrid_Model_Grid::BASE_PROFILE_ID) {
                        $select->from($gridsTable, $params)
                            ->where('grid_id = ?', $gridId);
                    } else {
                        $select->from($profilesTable, $params)
                            ->where('grid_id = ?', $gridId)
                            ->where('profile_id = ?', $fromProfileId);
                    }
                    
                    $values = $write->fetchRow($select);
                    
                    if ($toProfileId === BL_CustomGrid_Model_Grid::BASE_PROFILE_ID) {
                        $write->update(
                            $gridsTable,
                            $values,
                            $write->quoteInto('grid_id = ?', $gridId)
                        );
                    } else {
                        $write->update(
                            $profilesTable,
                            $values,
                            $write->quoteInto('grid_id = ?', $gridId)
                                . ' AND '
                                . $write->quoteInto('profile_id = ?', $toProfileId)
                        );
                    }
                }
            }
            
            if (isset($copiedValues['columns']) && $copiedValues['columns']) {
                $this->_copyProfileColumns($gridId, $fromProfileId, $toProfileId);
            }
            
            $write->commit();
            
        } catch (Exception $e) {
            $write->rollback();
            throw $e;
        }
        
        return $this;
    }
    
    public function updateProfile($gridId, $profileId, array $values, $useTransaction=true)
    {
        $write = $this->_getWriteAdapter();
        
        if ($useTransaction) {
            $write->beginTransaction();
        }
        
        try {
            if (isset($values['is_restricted'])) {
                if ($values['is_restricted']) {
                    if (isset($values['assigned_to']) && is_array($values['assigned_to'])) {
                        $this->_assignProfile($profileId, $values['assigned_to']);
                    }
                } else {
                    $this->_assignProfile($profileId, array());
                }
            }
            if (array_key_exists('assigned_to', $values)) {
                unset($values['assigned_to']);
            }
            
            $write->update(
                $this->_getProfilesTable(),
                $values,
                $write->quoteInto('grid_id = ?', $gridId)
                    . ' AND '
                    . $write->quoteInto('profile_id = ?', $profileId)
            );
            
            if ($useTransaction) {
                $write->commit();
            }
            
        } catch (Exception $e) {
            if ($useTransaction) {
                $write->rollback();
            }
            throw $e;
        }
        
        return $this;
    }
    
    public function deleteProfile($gridId, $profileId)
    {
        $write = $this->_getWriteAdapter();
        
        $write->delete(
            $this->_getProfilesTable(),
            $write->quoteInto('grid_id = ?', $gridId)
                . ' AND '
                . $write->quoteInto('profile_id = ?', $profileId)
        ); 
        
        return $this;
    }
}
