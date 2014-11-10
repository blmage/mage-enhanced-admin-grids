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

class BL_CustomGrid_Model_Grid_Profile extends BL_CustomGrid_Object
{
    /**
     * Return base helper
     *
     * @return BL_CustomGrid_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('customgrid');
    }
    
    /**
     * Return this profile's ID
     *
     * @return int
     */
    public function getId()
    {
        return (int) $this->_getData('profile_id');
    }
    
    /**
     * Return this profile's grid model
     *
     * @param bool $graceful Whether to throw an exception if the grid model is invalid, otherwise return null
     * @return BL_CustomGrid_Model_Grid|null
     */
    public function getGridModel($graceful = false)
    {
        if (($gridModel = $this->_getData('grid_model')) instanceof BL_CustomGrid_Model_Grid) {
            return $gridModel;
        } elseif (!$graceful) {
            Mage::throwException($this->_getHelper()->__('Invalid grid model'));
        }
        return null;
    }
    
    /**
     * Return whether this is the base profile from the corresponding grid model
     *
     * @return bool
     */
    public function isBase()
    {
        return ($this->getId() === BL_CustomGrid_Model_Grid::BASE_PROFILE_ID);
    }
    
    /**
     * Return whether this is the current profile from the corresponding grid model
     *
     * @return bool
     */
    public function isCurrent()
    {
        return ($this->getId() === $this->getGridModel()->getProfileId());
    }

    public function isDefault() {
        //@todo return proper flag if this profile is the default profile for this grid.
        return false;
    }
    
    /**
     * Return the roles IDs to which this profile is assigned
     *
     * @return array
     */
    public function getAssignedToRoleIds()
    {
        if (!$this->hasData('assigned_to_role_ids')) {
            $rolesIds = array();
            $rolesConfig = $this->getGridModel()->getRolesConfig();
            
            foreach ($rolesConfig as $roleId => $roleConfig) {
                if (is_array($assignedProfilesIds = $roleConfig->getData('assigned_profiles_ids'))
                    && in_array($this->getId(), $assignedProfilesIds, true)) {
                    $rolesIds[] = $roleId;
                }
            }
            
            $this->setData('assigned_to_role_ids', $rolesIds);
        }
        return $this->_getData('assigned_to_role_ids');
    }
    
    /**
     * Return the session parameters that should be restored upon returning to a profile previously used during
     * the same session
     * 
     * @return array
     */
    public function getRememberedSessionParams()
    {
        return is_null($value = $this->_getData('remembered_session_params'))
            ? $this->getGridModel()->getProfilesRememberedSessionParams()
            : explode(',', $value);
    }
    
    /**
     * Check, complete and return the given array of user IDs for which this profile will be set as default
     *
     * @param array $users User IDs
     * @return array
     */
    protected function _getDefaultForUsers(array $users)
    {
        $profileId = $this->getId();
        $gridModel = $this->getGridModel();
        
        $defaultForUsers = array();
        $users = array_filter($users);
        $ownUserId = $gridModel->getSessionUser()->getId();
        $ownChosen = in_array($ownUserId, $users);
        $otherChosenIds = array_diff($users, array($ownUserId));
            
        if ($gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_CHOOSE_OWN_USER_DEFAULT_PROFILE)) {
            if ($ownChosen) {
                $defaultForUsers[] = $ownUserId;
            }
        } elseif ($ownChosen) {
            $gridModel->throwPermissionException();
        } elseif ($gridModel->getUserDefaultProfileId() === $profileId) {
            $defaultForUsers[] = $ownUserId;
        }
        
        if ($gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_CHOOSE_OTHER_USERS_DEFAULT_PROFILE)) {
            $defaultForUsers = array_merge($defaultForUsers, $otherChosenIds);
        } elseif (!empty($otherChosenIds)) {
            $gridModel->throwPermissionException();
        } else {
            $usersIds = Mage::getModel('admin/user')
                ->getCollection()
                ->getAllIds();
            
            foreach ($usersIds as $userId) {
                if (($userId != $ownUserId) && ($gridModel->getUserDefaultProfileId($userId) === $profileId)) {
                    $defaultForUsers[] = $userId;
                }
            }
        }
        
        return $defaultForUsers;
    }
    
    /**
     * Check, complete and return the given array of role IDs for which this profile will be set as default
     *
     * @param array $roles Role IDs
     * @return array
     */
    protected function _getDefaultForRoles(array $roles)
    {
        $profileId = $this->getId();
        $gridModel = $this->getGridModel();
        
        $roles = array_filter($roles);
        $defaultForRoles = array();
        $ownRoleId = $gridModel->getSessionRole()->getId();
        $ownChosen = in_array($ownRoleId, $roles);
        $otherChosenIds = array_diff($roles, array($ownRoleId));
            
        if ($gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_CHOOSE_OWN_ROLE_DEFAULT_PROFILE)) {
            if ($ownChosen) {
                $defaultForRoles[] = $ownRoleId;
            }
        } elseif ($ownChosen) {
            $gridModel->throwPermissionException();
        } elseif ($gridModel->getRoleDefaultProfileId() === $profileId) {
            $defaultForRoles[] = $ownRoleId;
        }
        if ($gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_CHOOSE_OTHER_ROLES_DEFAULT_PROFILE)) {
            $defaultForRoles = array_merge($defaultForRoles, $otherChosenIds);
        } elseif (!empty($otherChosenIds)) {
            $gridModel->throwPermissionException();
        } else {
            $rolesIds = Mage::getModel('admin/roles')
                ->getCollection()
                ->getAllIds();
            
            foreach ($rolesIds as $roleId) {
                if (($roleId != $ownRoleId) && ($gridModel->getRoleDefaultProfileId($roleId) === $profileId)) {
                    $defaultForRoles[] = $roleId;
                }
            }
        }
        
        return $defaultForRoles;
    }
    
    /**
     * (Un-)Choose this profile as default for given users and roles, and globally
     * (expected values and corresponding possibilities depending on permissions)
     *
     * @param array $values Array with "users", "roles" and "global" keys, holding corresponding value(s)
     * @return this
     */
    public function chooseAsDefault(array $values)
    {
        $profileId  = $this->getId();
        $gridModel  = $this->getGridModel();
        $profiles   = $gridModel->getProfiles(true, true);
        $defaultFor = array();
        
        if (!isset($profiles[$profileId])) {
            Mage::throwException($this->_getHelper()->__('This profile is not available'));
        }
        if (isset($values['users']) && is_array($values['users'])) {
            $defaultFor['users'] = $this->_getDefaultForUsers($values['users']);
        }
        if (isset($values['roles']) && is_array($values['roles'])) {
            $defaultFor['roles'] = $this->_getDefaultForRoles($values['roles']);
        }
        if (isset($values['global'])) {
            if ($gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_CHOOSE_GLOBAL_DEFAULT_PROFILE)) {
                $defaultFor['global'] = (bool) $values['global'];
            } else {
                $gridModel->throwPermissionException();
            }
        }
        
        $gridModel->getResource()->chooseProfileAsDefault($gridModel->getId(), $profileId, $defaultFor);
        
        if (isset($defaultFor['users'])) {
             $gridModel->resetUsersConfigValues();
        }
        if (isset($defaultFor['roles'])) {
            $gridModel->resetRolesConfigValues();
        }
        if (isset($defaultFor['global'])) {
            $gridModel->resetProfilesValues();
        }
        
        return $this;
    }
    
    /**
     * Check whether the given profile values would result in a duplicated profile,
     * throw a corresponding exception if this is the case
     * 
     * @param int|null $checkedProfileId Checked profile ID (may be null in case of a new profile)
     * @param array $checkedProfileValues Checked profile values
     * @param array $profiles List of all profiles
     * @return this
     */
    protected function _checkProfileDuplication($checkedProfileId, array $checkedProfileValues, array $profiles)
    {
        foreach ($profiles as $profile) {
            if ((trim($profile->getName()) === $checkedProfileValues['name'])
                && (is_null($checkedProfileId) || ($profile->getId() !== $checkedProfileId))) {
                Mage::throwException(
                    $this->_getHelper()->__('Another profile from the same grid already has this name')
                );
            }
        }
        return $this;
    }
    
    /**
     * Copy this profile to a new one, and return the new profile ID
     *
     * @param array $newValues New profile values
     * @return int New profile ID
     */
    public function copyToNew(array $values)
    {
        $profileId = $this->getId();
        $gridModel = $this->getGridModel();
        $profiles  = $gridModel->getProfiles(true, true);
        
        if (!$gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_COPY_PROFILES_TO_NEW)) {
            $gridModel->throwPermissionException();
        } elseif (!isset($profiles[$profileId])) {
            Mage::throwException($this->_getHelper()->__('The copied profile is not available'));
        } elseif (!isset($values['name'])) {
            Mage::throwException($this->_getHelper()->__('The profile name must be filled'));
        }
        
        $values['name'] = trim($values['name']);
        $this->_checkProfileDuplication(null, $values, $profiles);
        $assignedRolesIds = null;
        
        if ($gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_ASSIGN_PROFILES)) {
            if ((isset($values['is_restricted']) && $values['is_restricted'])
                && (isset($values['assigned_to']) && is_array($values['assigned_to']))) {
                $assignedRolesIds = $values['assigned_to'];
            }
        } elseif ($gridModel->getProfilesDefaultRestricted()) {
            $assignedRolesIds = $gridModel->getProfilesDefaultAssignedTo();
            $sessionRoleId  = $gridModel->getSessionRole()->getId();
            $creatorRoleKey = array_search(
                BL_CustomGrid_Model_System_Config_Source_Admin_Role::CREATOR_ROLE,
                $assignedRolesIds
            );
            
            if ($creatorRoleKey !== false) {
                unset($assignedRolesIds[$creatorRoleKey]);
                
                if (!in_array($sessionRoleId, $assignedRolesIds)) {
                    $assignedRolesIds[] = $sessionRoleId;
                }
            }
        }
        
        $values['is_restricted'] = (is_array($assignedRolesIds) && !empty($assignedRolesIds));
        $values['assigned_to'] = ($values['is_restricted'] ? $assignedRolesIds : null);
        
        $newProfileId = $gridModel->getResource()->copyProfileToNew($gridModel->getId(), $profileId, $values);
        $gridModel->resetProfilesValues();
        
        if ($values['is_restricted']) {
            $gridModel->resetRolesConfigValues();
        }
        
        return (int) $newProfileId;
    }
    
    /**
     * Copy some of this profile's values to another existing one
     *
     * @param int $toProfileId ID of the profile on which to copy the given values
     * @param array $values Copied values (possible values : "columns", and each grid parameter key - eg "page")
     * @return this
     */
    public function copyToExisting($toProfileId, array $values)
    {
        $profileId = $this->getId();
        $gridModel = $this->getGridModel();
        $profiles  = $gridModel->getProfiles(true, true);
        
        if (!$gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_COPY_PROFILES_TO_EXISTING)) {
            $gridModel->throwPermissionException();
        } elseif (!isset($profiles[$profileId])) {
            Mage::throwException($this->_getHelper()->__('The copied profile is not available'));
        } elseif (!isset($profiles[$toProfileId])) {
            Mage::throwException($this->_getHelper()->__('The profile on which to copy is not available'));
        } elseif ($profileId === $toProfileId) {
            Mage::throwException($this->_getHelper()->__('A profile can not be copied to itself'));
        }
        
        $gridModel->getResource()->copyProfileToExisting($gridModel->getId(), $profileId, $toProfileId, $values);
        $gridModel->resetProfilesValues();
        
        return $this;
    }
    
    /**
     * Update this profile's base values
     *
     * @param array $values New values
     * @return this
     */
    public function update(array $values)
    {
        $profileId = $this->getId();
        $gridModel = $this->getGridModel();
        $profiles  = $gridModel->getProfiles(true, true);
        
        if (!$gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_EDIT_PROFILES)) {
            $gridModel->throwPermissionException();
        } elseif (!isset($profiles[$profileId])) {
            Mage::throwException($this->_getHelper()->__('This profile is not available'));
        } elseif ($profileId === $gridModel->getBaseProfileId()) {
            Mage::throwException($this->_getHelper()->__('The base profile can not be edited'));
        } elseif (!isset($values['name'])) {
            Mage::throwException($this->_getHelper()->__('The profile name must be filled'));
        }
        
        $editableKeys = array('name', 'remembered_session_params');
        $values = array_intersect_key($values, array_flip($editableKeys));
        $values['name'] = trim($values['name']);
        
        $this->_checkProfileDuplication($profileId, $values, $profiles);
        
        if (!isset($values['remembered_session_params']) || is_array($values['remembered_session_params'])) {
            $sessionParams = array_intersect($values['remembered_session_params'], $gridModel->getGridParamsKeys(true));
            
            if (in_array(BL_CustomGrid_Model_Grid::GRID_PARAM_NONE, $sessionParams)) {
                $sessionParams = array(BL_CustomGrid_Model_Grid::GRID_PARAM_NONE);
            }
            
            $values['remembered_session_params'] = (empty($sessionParams) ? null : implode(',', $sessionParams));
        } else {
            $values['remembered_session_params'] = null;
        }
        
        $gridModel->getResource()->updateProfile($gridModel->getId(), $profileId, $values, !$this->getIsBulkSaveMode());
        $this->addData($values);
        
        return $this;
    }
    
    /**
     * (Un-)Restrict and/or (un-)assign this profile
     *
     * @param array $values Array with "is_restricted" and "assigned_to" keys, holding corresponding value(s)
     * @return this
     */
    public function assign(array $values)
    {
        $profileId = $this->getId();
        $gridModel = $this->getGridModel();
        $profiles  = $gridModel->getProfiles(true, true);
        
        if (!$gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_ASSIGN_PROFILES)) {
            $gridModel->throwPermissionException();
        } elseif (!isset($profiles[$profileId])) {
            Mage::throwException($this->_getHelper()->__('This profile is not available'));
        } elseif ($profileId === $gridModel->getBaseProfileId()) {
            Mage::throwException($this->_getHelper()->__('The base profile can not be assigned'));
        }
        
        $editableKeys = array('is_restricted', 'assigned_to');
        $values = array_intersect_key($values, array_flip($editableKeys));
        
        if ((isset($values['is_restricted']) && $values['is_restricted'])
            && (isset($values['assigned_to']) && is_array($values['assigned_to']))) {
            $values['is_restricted'] = (is_array($values['assigned_to']) && !empty($values['assigned_to']));
            $values['assigned_to'] = ($values['is_restricted'] ? $values['assigned_to'] : null);
        } else {
            $values['is_restricted'] = false;
            $values['assigned_to'] = null;
        }
        
        $gridModel->getResource()->updateProfile($gridModel->getId(), $profileId, $values, !$this->getIsBulkSaveMode());
        $gridModel->resetProfilesValues();
        $gridModel->resetRolesConfigValues();
        $this->unsetData('assigned_to_role_ids');
        
        return $this;
    }
    
    /**
     * Delete this profile
     *
     * @return this
     */
    public function delete()
    {
        $profileId = $this->getId();
        $gridModel = $this->getGridModel();
        $profiles  = $gridModel->getProfiles(true, true);
        
        if (!$gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_DELETE_PROFILES)) {
            $gridModel->throwPermissionException();
        } elseif (!isset($profiles[$profileId])) {
            Mage::throwException($this->_getHelper()->__('This profile is not available'));
        } elseif ($profileId === $gridModel->getBaseProfileId()) {
            Mage::throwException($this->_getHelper()->__('The base profile can not be deleted'));
        }
        
        $gridModel->getResource()->deleteProfile($gridModel->getId(), $profileId);
        $gridModel->resetProfilesValues();
        $gridModel->resetUsersConfigValues();
        $gridModel->resetRolesConfigValues();
        
        return $this;
    }
}
