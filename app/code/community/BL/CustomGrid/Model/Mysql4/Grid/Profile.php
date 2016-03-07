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

class BL_CustomGrid_Model_Mysql4_Grid_Profile extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('customgrid/grid_profile', 'profile_id');
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
     * Copy the grid columns from one profile to another
     *
     * @param int $gridId Grid model ID
     * @param int $fromProfileId ID of the profile from which to copy the columns
     * @param int $toProfileId ID of the profile into which to copy the columns
     * @return BL_CustomGrid_Model_Mysql4_Grid_Profile
     */
    protected function _copyProfileColumns($gridId, $fromProfileId, $toProfileId)
    {
        $write = $this->_getWriteAdapter();
        $columnsTable = $this->getTable('customgrid/grid_column');
        
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
     * @return BL_CustomGrid_Model_Mysql4_Grid_Profile
     */
    protected function _assignProfile($profileId, array $assignedRolesIds = array())
    {
        $write  = $this->_getWriteAdapter();
        $table  = $this->getTable('customgrid/role_profile');
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
     * @throws Exception
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
     * @return BL_CustomGrid_Model_Mysql4_Grid_Profile
     * @throws Exception
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
     * Return whether the given profile values contain valid assignation values
     *
     * @param array $profileValues Profile values
     * @return bool
     */
    protected function _isAssignableProfileValues(array $profileValues)
    {
        return isset($profileValues['is_restricted'])
        && $profileValues['is_restricted']
        && isset($profileValues['assigned_to'])
        && is_array($profileValues['assigned_to']);
    }
    
    /**
     * Update the given profile values, and possibly assignations
     *
     * @param int $gridId Grid model ID
     * @param int $profileId Updated profile ID
     * @param array $values New profile values (set "is_retricted" to also update assignations)
     * @param bool $useTransaction Whether a transaction should be used
     * @return BL_CustomGrid_Model_Mysql4_Grid_Profile
     * @throws Exception
     */
    public function updateProfile($gridId, $profileId, array $values, $useTransaction = true)
    {
        $write = $this->_getWriteAdapter();
        
        if ($useTransaction) {
            $write->beginTransaction();
        }
        
        try {
            if ($this->_isAssignableProfileValues($values)) {
                $this->_assignProfile($profileId, $values['assigned_to']);
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
     * @return BL_CustomGrid_Model_Mysql4_Grid_Profile
     */
    protected function _chooseProfileAsRoleUserDefault($valuesType, $gridId, $profileId, array $valuesIds)
    {
        $write = $this->_getWriteAdapter();
        
        $tables = array(
            'role' => $this->getTable('customgrid/grid_role'),
            'user' => $this->getTable('customgrid/grid_user'),
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
     * @return BL_CustomGrid_Model_Mysql4_Grid_Profile
     * @throws Exception
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
                $table = $this->getTable('customgrid/grid');
                
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
     * @return BL_CustomGrid_Model_Mysql4_Grid_Profile
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
