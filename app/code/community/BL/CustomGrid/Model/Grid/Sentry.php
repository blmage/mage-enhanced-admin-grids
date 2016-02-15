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

class BL_CustomGrid_Model_Grid_Sentry extends BL_CustomGrid_Model_Grid_Worker
{
    /**
     * Grid actions (used to set and check permissions)
     */
    const ACTION_CUSTOMIZE_COLUMNS                  = 'customize';
    const ACTION_USE_CUSTOMIZED_COLUMNS             = 'use_customized';
    const ACTION_VIEW_GRID_INFOS                    = 'view_infos';
    const ACTION_CHOOSE_EDITABLE_COLUMNS            = 'choose_editable';
    const ACTION_EDIT_COLUMNS_VALUES                = 'edit_values';
    const ACTION_EDIT_DEFAULT_PARAMS                = 'edit_default';
    const ACTION_USE_DEFAULT_PARAMS                 = 'use_default';
    const ACTION_EXPORT_RESULTS                     = 'export';
    const ACTION_ENABLE_DISABLE                     = 'enable_disable';
    const ACTION_EDIT_FORCED_TYPE                   = 'edit_forced_type';
    const ACTION_EDIT_CUSTOMIZATION_PARAMS          = 'edit_customization_params';
    const ACTION_EDIT_DEFAULT_PARAMS_BEHAVIOURS     = 'edit_default_params_behaviours';
    const ACTION_EDIT_ROLES_PERMISSIONS             = 'edit_roles_permissions';
    const ACTION_DELETE                             = 'delete';
    const ACTION_ACCESS_ALL_PROFILES                = 'access_all_profiles';
    const ACTION_CHOOSE_OWN_USER_DEFAULT_PROFILE    = 'choose_own_user_default_profile';
    const ACTION_CHOOSE_OTHER_USERS_DEFAULT_PROFILE = 'choose_other_users_default_profile';
    const ACTION_CHOOSE_OWN_ROLE_DEFAULT_PROFILE    = 'choose_own_role_default_profile';
    const ACTION_CHOOSE_OTHER_ROLES_DEFAULT_PROFILE = 'choose_other_roles_default_profile';
    const ACTION_CHOOSE_GLOBAL_DEFAULT_PROFILE      = 'choose_global_default_profile';
    const ACTION_COPY_PROFILES_TO_NEW               = 'copy_profiles_to_new';
    const ACTION_COPY_PROFILES_TO_EXISTING          = 'copy_profiles_to_existing';
    const ACTION_EDIT_PROFILES                      = 'edit_profiles';
    const ACTION_ASSIGN_PROFILES                    = 'assign_profiles';
    const ACTION_DELETE_PROFILES                    = 'delete_profiles';
    
    /**
     * Grid actions options hash
     * 
     * @var string[]
     */
    static protected $_gridActions = null;
    
    /**
     * Grouped grid actions options hashes
     * 
     * @var string[]
     */
    static protected $_groupedGridActions = null;
    
    /**
     * Grids actions corresponding paths in the ACL configuration
     * 
     * @var string[]
     */
    static protected $_gridActionsAclPaths = array(
        self::ACTION_CUSTOMIZE_COLUMNS                  => 'customgrid/customization/edit_columns',
        self::ACTION_USE_CUSTOMIZED_COLUMNS             => 'customgrid/customization/use_columns',
        self::ACTION_VIEW_GRID_INFOS                    => 'customgrid/customization/view_grid_infos',
        self::ACTION_EDIT_DEFAULT_PARAMS                => 'customgrid/customization/edit_default_params',
        self::ACTION_USE_DEFAULT_PARAMS                 => 'customgrid/customization/use_default_params',
        self::ACTION_EXPORT_RESULTS                     => 'customgrid/customization/export_results',
        self::ACTION_CHOOSE_EDITABLE_COLUMNS            => 'customgrid/editor/choose_columns',
        self::ACTION_EDIT_COLUMNS_VALUES                => 'customgrid/editor/edit_columns',
        self::ACTION_ENABLE_DISABLE                     => 'customgrid/administration/enable_disable',
        self::ACTION_EDIT_FORCED_TYPE                   => 'customgrid/administration/edit_forced_type',
        self::ACTION_EDIT_CUSTOMIZATION_PARAMS          => 'customgrid/administration/edit_customization_params',
        self::ACTION_EDIT_DEFAULT_PARAMS_BEHAVIOURS     => 'customgrid/administration/edit_default_params_behaviours',
        self::ACTION_EDIT_ROLES_PERMISSIONS             => 'customgrid/administration/edit_roles_permissions',
        self::ACTION_DELETE                             => 'customgrid/administration/delete',
        self::ACTION_ACCESS_ALL_PROFILES                => 'customgrid/profiles/access_all',
        self::ACTION_CHOOSE_OWN_USER_DEFAULT_PROFILE    => 'customgrid/profiles/choose_own_user_default',
        self::ACTION_CHOOSE_OTHER_USERS_DEFAULT_PROFILE => 'customgrid/profiles/choose_other_users_default',
        self::ACTION_CHOOSE_OWN_ROLE_DEFAULT_PROFILE    => 'customgrid/profiles/choose_own_role_default',
        self::ACTION_CHOOSE_OTHER_ROLES_DEFAULT_PROFILE => 'customgrid/profiles/choose_other_roles_default',
        self::ACTION_CHOOSE_GLOBAL_DEFAULT_PROFILE      => 'customgrid/profiles/choose_global_default',
        self::ACTION_COPY_PROFILES_TO_NEW               => 'customgrid/profiles/copy_to_new',
        self::ACTION_COPY_PROFILES_TO_EXISTING          => 'customgrid/profiles/copy_to_existing',
        self::ACTION_EDIT_PROFILES                      => 'customgrid/profiles/edit',
        self::ACTION_ASSIGN_PROFILES                    => 'customgrid/profiles/assign',
        self::ACTION_DELETE_PROFILES                    => 'customgrid/profiles/delete',
    );
    
    /**
     * Grid actions permissions flags
     */
    const ACTION_PERMISSION_USE_CONFIG = '0';
    const ACTION_PERMISSION_YES        = '1';
    const ACTION_PERMISSION_NO         = '2';
    
    /**
     * Throw a permission-related exception
     * 
     * @param string|null $message Custom exception message
     */
    public function throwPermissionException($message = null)
    {
        if (is_null($message)) {
            $message = $this->getGridModel()
                ->getHelper()
                ->__('You are not allowed to use this action');
        }
        throw new BL_CustomGrid_Grid_Permission_Exception($message); 
    }
    
    /**
     * Prepare and return the given base values from an user permissions check, ensuring that they are fully suitable
     * for further tests
     * 
     * @param string|array $actions Actions codes
     * @param bool|array|null $aclPermissions Corresponding ACL permissions values
     * return array Array containing the two prepared values
     */
    protected function _prepareUserPermissionsCheckValues($actions, $aclPermissions)
    {
        if (!is_array($actions)) {
            if (!is_null($aclPermissions) && !is_array($aclPermissions)) {
                $aclPermissions = array($actions => $aclPermissions);
            }
            $actions = array($actions);
        }
        if (!is_array($aclPermissions)) {
            $aclPermissions = array();
        }
        return array($actions, $aclPermissions);
    }
    
    /**
     * Check if the current user has the required permission for the given action
     * 
     * @param string $action Action code
     * @param array $permissions Role permissions values
     * @param array $aclPermissions ACL permissions values
     * @return bool
     */
    protected function _checkUserActionPermission($action, array $permissions, array $aclPermissions)
    {
        $result = true;
        $actionPermission = (isset($permissions[$action]) ? $permissions[$action] : self::ACTION_PERMISSION_USE_CONFIG);
        
        if ($actionPermission === self::ACTION_PERMISSION_NO) {
            $result = false;
        } elseif ($actionPermission === self::ACTION_PERMISSION_USE_CONFIG) {
            $result = isset($aclPermissions[$action])
                ? (bool) $aclPermissions[$action]
                : (bool) $this->getGridModel()->getAdminSession()->isAllowed(self::$_gridActionsAclPaths[$action]);
        }
        
        return $result;
    }
    
    /**
     * Check if the current user has the required permissions for any or all of the given actions
     *
     * @param string|array $actions Actions codes
     * @param bool|array|null $aclPermissions Corresponding ACL permissions values
     * @param bool $any Whether the user should have any of the given permissions, otherwise all
     * @param bool $graceful Whether no exception should be thrown if the user does not have the required permissions
     * @return bool
     */
    public function checkUserPermissions($actions, $aclPermissions = null, $any = true, $graceful = true)
    {
        $any = (bool) $any;
        $gridModel = $this->getGridModel();
        $session   = $gridModel->getAdminSession();
        
        if (($user = $session->getUser()) && ($role = $user->getRole())) {
            list($actions, $aclPermissions) = $this->_prepareUserPermissionsCheckValues($actions, $aclPermissions);
            $roleId = $role->getId();
            $permissions = $gridModel->getRolePermissions($roleId);
            $result = true;
            
            foreach ($actions as $action) {
                $result = $this->_checkUserActionPermission($action, $permissions, $aclPermissions);
                
                if ($any === $result) {
                    break;
                }
            }
        } else {
            $result = false;
        }
        
        if (!$result && !$graceful) {
            $this->throwPermissionException();
        }
        
        return $result;
    }
    
    /**
     * Check if the current user has the permission for the given action
     * Shortcut for the most common use-case of checkUserPermissions()
     *
     * @param string $action Action code
     * @param bool $graceful Whether no exception should be thrown if the user does not have the required permissions
     * @return bool
     */
    public function checkUserActionPermission($action, $graceful = true)
    {
        return $this->checkUserPermissions($action, null, false, $graceful);
    }
    
    /**
     * Prepare and set the given roles permissions on the current grid model
     *
     * @param array $permissions Roles permissions
     * @return BL_CustomGrid_Model_Grid_Sentry
     */
    public function setGridRolesPermissions(array $permissions)
    {
        $this->checkUserActionPermission(self::ACTION_EDIT_ROLES_PERMISSIONS, false);
        
        $flags = array(
            self::ACTION_PERMISSION_USE_CONFIG,
            self::ACTION_PERMISSION_YES,
            self::ACTION_PERMISSION_NO,
        );
        
        $actions = $this->getGridActions();
        $rolesConfig = $this->getGridModel()->getRolesConfig();
        
        foreach ($permissions as $roleId => $rolePermissions) {
            if (!isset($rolesConfig[$roleId])) {
                $rolesConfig[$roleId] = new BL_CustomGrid_Object(
                    array(
                        'permissions' => array(),
                        'default_profile_id' => null,
                        'assigned_profiles_ids' => array(),
                    )
                );
            }
            if (!is_array($rolePermissions)) {
                $rolePermissions = array();
            }
            
            foreach ($rolePermissions as $action => $flag) {
                $flag = strval($flag);
                
                if (!isset($actions[$action]) || !in_array($flag, $flags, true)) {
                    unset($rolePermissions[$action]);
                }
            }
            
            $rolesConfig[$roleId]->setData('permissions', $rolePermissions);
        }
        
        $this->getGridModel()->setData('roles_config', $rolesConfig);
        return $this;
    }
    
    /**
     * Return grid actions options hash
     *
     * @param bool $grouped Whether actions should be grouped by general category
     * @return array
     */
    public function getGridActions($grouped = false)
    {
        if (is_null(self::$_groupedGridActions)) {
            /** @var $helper BL_CustomGrid_Helper_Data */
            $helper = Mage::helper('customgrid');
            
            self::$_groupedGridActions = array(
                'customization' => array(
                    'label'   => $helper->__('Customization'),
                    'actions' => array(
                        self::ACTION_CUSTOMIZE_COLUMNS       => 'Customize Columns',
                        self::ACTION_USE_CUSTOMIZED_COLUMNS  => 'Use Customized Columns',
                        self::ACTION_VIEW_GRID_INFOS         => 'View Grids Informations',
                        self::ACTION_EDIT_DEFAULT_PARAMS     => 'Edit Default Parameters',
                        self::ACTION_USE_DEFAULT_PARAMS      => 'Use Default Parameters',
                        self::ACTION_EXPORT_RESULTS          => 'Export Results',
                        self::ACTION_CHOOSE_EDITABLE_COLUMNS => 'Choose Editable Columns',
                        self::ACTION_EDIT_COLUMNS_VALUES     => 'Edit Columns Values',
                    ),
                ),
                'administration' => array(
                    'label'   => $helper->__('Administration'),
                    'actions' => array(
                        self::ACTION_ENABLE_DISABLE                 => 'Enable / Disable',
                        self::ACTION_EDIT_FORCED_TYPE               => 'Edit Forced Type',
                        self::ACTION_EDIT_CUSTOMIZATION_PARAMS      => 'Edit Customization Parameters', 
                        self::ACTION_EDIT_DEFAULT_PARAMS_BEHAVIOURS => 'Edit Default Parameters Behaviours', 
                        self::ACTION_EDIT_ROLES_PERMISSIONS         => 'Edit Roles Permissions', 
                        self::ACTION_DELETE                         => 'Delete', 
                    ),
                ),
                'profiles' => array(
                    'label'   => $helper->__('Profiles'),
                    'actions' => array(
                        self::ACTION_ACCESS_ALL_PROFILES                => 'Access All Profiles',
                        self::ACTION_CHOOSE_OWN_USER_DEFAULT_PROFILE    => 'Choose Default Profile (Own User)',
                        self::ACTION_CHOOSE_OTHER_USERS_DEFAULT_PROFILE => 'Choose Default Profile (Other Users)',
                        self::ACTION_CHOOSE_OWN_ROLE_DEFAULT_PROFILE    => 'Choose Default Profile (Own Role)',
                        self::ACTION_CHOOSE_OTHER_ROLES_DEFAULT_PROFILE => 'Choose Default Profile (Other Roles)',
                        self::ACTION_CHOOSE_GLOBAL_DEFAULT_PROFILE      => 'Choose Default Profile (Global)',
                        self::ACTION_COPY_PROFILES_TO_NEW               => 'Copy Profiles (To New)',
                        self::ACTION_COPY_PROFILES_TO_EXISTING          => 'Copy Profiles (To Existing)',
                        self::ACTION_EDIT_PROFILES                      => 'Edit Profiles',
                        self::ACTION_ASSIGN_PROFILES                    => 'Assign Profiles To Roles',
                        self::ACTION_DELETE_PROFILES                    => 'Delete Profiles',
                    ),
                ),
            );
            
            foreach (self::$_groupedGridActions as $groupKey => $values) {
                foreach ($values['actions'] as $actionKey => $actionLabel) {
                    self::$_groupedGridActions[$groupKey]['actions'][$actionKey] = $helper->__($actionLabel);
                }
            }
        }
        
        if ($grouped) {
            return self::$_groupedGridActions;
        }
        
        if (is_null(self::$_gridActions)) {
            self::$_gridActions = array();
            
            foreach (self::$_groupedGridActions as $actionsGroup) {
                foreach ($actionsGroup['actions'] as $key => $value) {
                    self::$_gridActions[$key] = $value;
                }
            }
        }
        
        return self::$_gridActions;
    }
}
