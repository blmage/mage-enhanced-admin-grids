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

class BL_CustomGrid_Model_Mysql4_Grid extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('customgrid/grid', 'grid_id');
    }
    
    /**
     * Return the grids table name
     * 
     * @return string
     */
    protected function _getGridsTable()
    {
        return $this->getTable('customgrid/grid');
    }
    
    /**
     * Return the profiles table name
     * 
     * @return string
     */
    protected function _getProfilesTable()
    {
        return $this->getTable('customgrid/grid_profile');
    }
    
    /**
     * Return the columns table name
     * 
     * @return string
     */
    protected function _getColumnsTable()
    {
        return $this->getTable('customgrid/grid_column');
    }
    
    /**
     * Return the users table name
     * 
     * @return string
     */
    protected function _getUsersTable()
    {
        return $this->getTable('customgrid/grid_user');
    }
    
    /**
     * Return the roles table name
     * 
     * @return string
     */
    protected function _getRolesTable()
    {
        return $this->getTable('customgrid/grid_role');
    }
    
    /**
     * Return the roles -  profiles table name
     * 
     * @return string
     */
    protected function _getRolesProfilesTable()
    {
        return $this->getTable('customgrid/role_profile');
    }
    
    /**
     * Save the current profile, the columns and the roles config from the given grid model being saved
     * 
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @return BL_CustomGrid_Model_Mysql4_Grid
     */
    protected function _afterSave(Mage_Core_Model_Abstract $gridModel)
    {
        $this->_saveProfile($gridModel);
        $this->_saveColumns($gridModel);
        $this->_saveRolesConfig($gridModel);
        return $this;
    }
    
    /**
     * Reset the base profile ID before deletion, to prevent a circular dependency problem
     *
     * @param BL_CustomGrid_Model_Grid $gridModel
     * @return BL_CustomGrid_Model_Mysql4_Grid
     */
    protected function _beforeDelete(Mage_Core_Model_Abstract $gridModel)
    {
        $write = $this->_getWriteAdapter();
        
        $write->update(
            $this->_getGridsTable(),
            array('base_profile_id' => null),
            $write->quoteInto('grid_id = ?', $gridModel->getId())
        );
        
        return $this;
    }
    
    /**
     * Save the current profile for the given grid (base profile for a new grid / current profile otherwise)
     * 
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @return BL_CustomGrid_Model_Mysql4_Grid
     */
    protected function _saveProfile(Mage_Core_Model_Abstract $gridModel)
    {
        if (!$gridModel->getBaseProfileId()) {
            $write = $this->_getWriteAdapter();
            
            $write->insert(
                $this->_getProfilesTable(),
                array(
                    'grid_id' => $gridModel->getId(),
                    'name'    => '',
                    'is_restricted' => false,
                )
            );
            
            $baseProfileId = $write->lastInsertId();
            
            $write->update(
                $this->_getGridsTable(),
                array('base_profile_id' => $baseProfileId),
                $write->quoteInto('grid_id = ?', $gridModel->getId())
            );
            
            $gridModel->setData('base_profile_id', $baseProfileId);
        } else {
            $profileData = $gridModel->getProfile()->getData();
            
            if (isset($profileData['is_restricted'])) {
                unset($profileData['is_restricted']);
            }
            
            $this->updateProfile(
                $gridModel->getId(),
                $gridModel->getProfileId(),
                $profileData,
                false
            );
        }
        return $this;
    }
    
    /**
     * Save the columns from the given grid model
     * 
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @return BL_CustomGrid_Model_Mysql4_Grid
     */
    protected function _saveColumns(Mage_Core_Model_Abstract $gridModel)
    {
        if (!is_array($columns = $gridModel->getData('columns'))) {
            return $this;
        }
        
        $write = $this->_getWriteAdapter();
        $columnsTable = $this->_getColumnsTable();
        
        $gridId = $gridModel->getId();
        $profileId   = $gridModel->getProfileId();
        $columnsIds  = array(-1);
        $columnsKeys = array_flip(array_keys($write->describeTable($columnsTable)));
        
        foreach ($columns as $column) {
            /** @var $column BL_CustomGrid_Model_Grid_Column */
            $columnValues = array_intersect_key($column->getData(), $columnsKeys);
            
            if (isset($columnValues['column_id']) && ($columnValues['column_id'] > 0)) {
                $write->update(
                    $columnsTable,
                    $columnValues,
                    $write->quoteInto('column_id = ?', $columnValues['column_id'])
                );
                
                $columnsIds[] = $columnValues['column_id'];
            } else {
                $columnValues['grid_id'] = $gridId;
                $columnValues['profile_id'] = $profileId;
                $write->insert($columnsTable, $columnValues);
                $columnsIds[] = $write->lastInsertId();
            }
        }
        
        $write->delete(
            $columnsTable,
            $write->quoteInto('grid_id = ?', $gridId)
            . ' AND '
            . $write->quoteInto('profile_id = ?', $profileId)
            . ' AND '
            . $write->quoteInto('column_id NOT IN (?)', $columnsIds)
        );
        
        return $this;
    }
    
    /**
     * Save the roles config from the given grid model
     * 
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @return BL_CustomGrid_Model_Mysql4_Grid
     */
    protected function _saveRolesConfig(Mage_Core_Model_Abstract $gridModel)
    {
        if (!is_array($rolesConfig = $gridModel->getData('roles_config'))) {
            return $this;
        }
        
        $write = $this->_getWriteAdapter();
        $rolesTable = $this->getTable('customgrid/grid_role');
        
        $gridId = $gridModel->getId();
        $rolesValues = array();
        
        if (!empty($rolesConfig)) {
            foreach ($rolesConfig as $roleId => $roleConfig) {
                /** @var $roleConfig BL_CustomGrid_Object */
                $rolesValues[] = array(
                    'grid_id' => $gridId,
                    'role_id' => $roleId,
                    'permissions' => serialize($roleConfig->getDataSetDefault('permissions', array())),
                    'default_profile_id' => $roleConfig->getData('default_profile_id'),
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
    
    /**
     * Return the given array of arrays, re-keyed with the sub-arrays values corresponding to the given key
     * 
     * @param array $values Array of arrays
     * @param string $valueKey Key of the values to use as the new keys for the main array
     * @return array
     */
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
    
    /**
     * Regroup the sub-arrays from the given array under the sub-arrays values corresponding to the given group key,
     * possibly re-keying them with the sub-arrays values corresponding to the given value key
     * 
     * @param array $values Array of arrays
     * @param string $groupKey Key of the values to use as the keys under which to regroup the corresponding sub-arrays
     * @param string $valueKey Key of the values to use as the new keys for the new regroupment arrays, if needed
     * @return array
     */
    protected function _arrangeGroupedResult(array $values, $groupKey, $valueKey = null)
    {
        $result = array();
        
        foreach ($values as $key => $value) {
            if (isset($value[$groupKey])) {
                $group = $value[$groupKey];
                
                if (!empty($valueKey) && isset($value[$valueKey])) {
                    $key = $value[$valueKey];
                }
                if (isset($result[$group])) {
                    $result[$group][$key] = $value;
                } else {
                    $result[$group] = array($key => $value);
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Return the grid profiles belonging to the given grid model ID(s)
     * 
     * @param int|array $gridId Grid model ID(s)
     * @return array
     */
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
    
    /**
     * Return the grid columns belonging to the given grid model ID(s)
     * 
     * @param int|array $gridId Grid model ID(s)
     * @return array
     */
    public function getGridColumns($gridId, $profileId)
    {
        $read = $this->_getReadAdapter();
        
        $select = $read->select()
            ->from($this->_getColumnsTable())
            ->where('grid_id = ?', $gridId)
            ->where('profile_id = ?', $profileId);
        
        return $read->fetchAll($select);
    }
    
    /**
     * Return the grid roles belonging to the given grid model ID(s)
     * 
     * @param int|array $gridId Grid model ID(s)
     * @return array
     */
    public function getGridRoles($gridId)
    {
        $read  = $this->_getReadAdapter();
        $roles = array();
        
        // Retrieve every existing admin role for each given grid
        $select = $read->select()
            ->distinct()
            ->from(array('cgg' => $this->_getGridsTable()), array('grid_id'))
            ->from(array('ar'  => $this->getTable('admin/role')), array('role_id'))
            ->joinLeft(
                array('cggr' => $this->_getRolesTable()),
                '(cggr.role_id = ar.role_id) AND (cggr.grid_id = cgg.grid_id)',
                array('permissions', 'default_profile_id')
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
                $roles[$key] = array(
                    'grid_id' => $role['grid_id'],
                    'role_id' => $role['role_id'],
                    'permissions' => $helper->unserializeArray($role['permissions']),
                    'default_profile_id' => $role['default_profile_id'],
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
    
    /**
     * Return the grid users belonging to the given grid model ID(s)
     * 
     * @param int|array $gridId Grid model ID(s)
     * @return array
     */
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
        
        if (is_array($gridId)) {
            $result = $this->_arrangeGroupedResult($result, 'grid_id', 'user_id');
        } else {
            $result = $this->_arrangeResult($result, 'user_id');
        }
        
        return $result;
    }
    
    /**
     * Copy the grid columns from one profile to another
     * 
     * @param int $gridId Grid model ID
     * @param int $fromProfileId ID of the profile from which to copy the columns
     * @param int $toProfileId ID of the profile into which to copy the columns
     * @return BL_CustomGrid_Model_Mysql4_Grid
     */
    protected function _copyProfileColumns($gridId, $fromProfileId, $toProfileId)
    {
        $write = $this->_getWriteAdapter();
        $columnsTable = $this->_getColumnsTable();
        
        $write->delete(
            $columnsTable,
            $write->quoteInto('grid_id = ?', $gridId)
            . ' AND '
            . $write->quoteInto('profile_id = ?', $toProfileId)
        );
        
        $select = $write->select()
            ->from($columnsTable)
            ->where('grid_id = ?', $gridId)
            ->where('profile_id = ?', $fromProfileId);
        
        $result  = $write->fetchAll($select);
        $columns = array();
        
        foreach ($result as $column) {
            unset($column['column_id']);
            $column['profile_id'] = $toProfileId;
            $columns[] = $column;
        }
        
        $write->insertMultiple($columnsTable, $columns);
        return $this;
    }
    
    /**
     * Assign the given profile ID to the given roles IDs
     * 
     * @param int $profileId ID of the profile to be assigned
     * @param int[] $assignedRolesIds Roles IDs to assign
     * @return BL_CustomGrid_Model_Mysql4_Grid
     */
    protected function _assignProfile($profileId, array $assignedRolesIds = array())
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
    
    /**
     * Copy the given profile to a new profile, with different name and assignations
     * 
     * @param int $gridId Grid model ID
     * @param int $fromProfileId ID of the profile to copy
     * @param array $values Alternative values ("name" and "is_restricted" / "assigned_to")
     * @return int ID of the new profile
     */
    public function copyProfileToNew($gridId, $fromProfileId, array $values)
    {
        $write = $this->_getWriteAdapter();
        $profilesTable = $this->_getProfilesTable();
        
        $write->beginTransaction();
        
        try {
            $select = $write->select()
                ->from(
                    $profilesTable,
                    array(
                        'grid_id',
                        'default_page',
                        'default_limit',
                        'default_sort',
                        'default_dir',
                        'default_filter',
                        'remembered_session_params',
                        'name' => new Zend_Db_Expr($write->quote($values['name'])),
                        'is_restricted' => new Zend_Db_Expr($values['is_restricted'] ? 1 : 0),
                    )
                )
                ->where('grid_id = ?', $gridId)
                ->where('profile_id = ?', $fromProfileId);
            
            $values = $write->fetchRow($select);
            $toProfileId = null;
            
            if (!empty($values)) {
                $write->insert($profilesTable, $values);
                $toProfileId = $write->lastInsertId();
            }
            if (empty($toProfileId)) {
                Mage::throwException(Mage::helper('customgrid')->__('The new profile could not be created'));
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
    
    /**
     * Copy the values from the given types from one profile to another profile
     * 
     * @param int $gridId Grid model ID
     * @param int $fromProfileId ID of the profile from which to copy the values
     * @param int $toProfileId ID of the profile into which to copy the values
     * @param array $copiedValues Types of copied values
     * @return BL_CustomGrid_Model_Mysql4_Grid
     */
    public function copyProfileToExisting($gridId, $fromProfileId, $toProfileId, array $copiedValues)
    {
        $write = $this->_getWriteAdapter();
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
                    $select = $write->select()
                        ->from($profilesTable, $params)
                        ->where('grid_id = ?', $gridId)
                        ->where('profile_id = ?', $fromProfileId);
                    
                    $write->update(
                        $profilesTable,
                        $write->fetchRow($select),
                        $write->quoteInto('grid_id = ?', $gridId)
                        . ' AND '
                        . $write->quoteInto('profile_id = ?', $toProfileId)
                    );
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
    
    /**
     * Update the given profile values, and possibly assignations
     * 
     * @param int $gridId Grid model ID
     * @param int $profileId Updated profile ID
     * @param array $values New profile values (set "is_retricted" to also update assignations)
     * @param bool $useTransaction Whether a transaction should be used
     * @return BL_CustomGrid_Model_Mysql4_Grid
     */
    public function updateProfile($gridId, $profileId, array $values, $useTransaction = true)
    {
        $write = $this->_getWriteAdapter();
        
        if ($useTransaction) {
            $write->beginTransaction();
        }
        
        try {
            if (isset($values['is_restricted'])) {
                if ($values['is_restricted'] && isset($values['assigned_to']) && is_array($values['assigned_to'])) {
                    $this->_assignProfile($profileId, $values['assigned_to']);
                }
            }
            
            $profilesTable = $this->_getProfilesTable();
            $profilesColumns = $write->describeTable($profilesTable);
            $values = array_intersect_key($values, array_flip(array_keys($profilesColumns)));
            
            $write->update(
                $profilesTable,
                $values,
                $write->quoteInto('profile_id = ?', $profileId)
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
    
    /**
     * (Un-)Set the given profile as default for the given IDs, corresponding to either roles or users
     * 
     * @param string $valuesType Values type ("role" or "user")
     * @param int $gridId Grid model ID
     * @param int $profileId Default profile ID
     * @param int[] $valuesIds Values IDs
     * @return BL_CustomGrid_Model_Mysql4_Grid
     */
    protected function _chooseProfileAsRoleUserDefault($valuesType, $gridId, $profileId, array $valuesIds)
    {
        $write = $this->_getWriteAdapter();
        
        $tables = array(
            'role' => $this->_getRolesTable(),
            'user' => $this->_getUsersTable(),
        );
        $idFieldNames = array(
            'role' => 'role_id',
            'user' => 'user_id',
        );
        
        if (!empty($valuesIds)) {
            $insert = array();
            
            foreach ($valuesIds as $valueId) {
                $insert[] = array(
                    'grid_id' => $gridId,
                    'default_profile_id' => $profileId,
                    $idFieldNames[$valuesType] => $valueId,
                );
            }
            
            $write->insertOnDuplicate(
                $tables[$valuesType],
                $insert,
                array('default_profile_id')
            );
        } else {
            $valuesIds = array(-1);
        }
        
        $write->update(
            $tables[$valuesType],
            array('default_profile_id' => null),
            $write->quoteInto('grid_id = ?', $gridId)
            . ' AND '
            . $write->quoteInto('default_profile_id = ?', $profileId)
            . ' AND '
            . $write->quoteInto($idFieldNames[$valuesType] . ' NOT IN (?)', $valuesIds)
        );
        
        return $this;
    }
    
    /**
     * (Un-)Set the given profile as default for roles, users, and globally
     * 
     * @param int $gridId Grid model ID
     * @param int $profileId Default profile ID
     * @param array $values Values for which the given profile should be set as default ("global" / "roles" / "users")
     * @return BL_CustomGrid_Model_Mysql4_Grid
     */
    public function chooseProfileAsDefault($gridId, $profileId, array $values)
    {
        $write = $this->_getWriteAdapter();
        $write->beginTransaction();
        
        try {
            if (isset($values['users']) && is_array($values['users'])) {
                $this->_chooseProfileAsRoleUserDefault('user', $gridId, $profileId, $values['users']);
            }
            if (isset($values['roles']) && is_array($values['roles'])) {
                $this->_chooseProfileAsRoleUserDefault('role', $gridId, $profileId, $values['roles']);
            }
            if (isset($values['global'])) {
                $table = $this->_getGridsTable();
                
                if ($values['global']) {
                    $write->update(
                        $table,
                        array('global_default_profile_id' => $profileId),
                        $write->quoteInto('grid_id = ?', $gridId)
                    );
                } else {
                    $write->update(
                        $table,
                        array('global_default_profile_id' => null),
                        $write->quoteInto('grid_id = ?', $gridId)
                        . ' AND '
                        . $write->quoteInto('global_default_profile_id = ?', $profileId)
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
    
    /**
     * Delete the given profile
     * 
     * @param int $gridId Grid model ID
     * @param int $profileId Deleted profile ID
     * @return BL_CustomGrid_Model_Mysql4_Grid
     */
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
