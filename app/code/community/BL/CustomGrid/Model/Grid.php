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

class BL_CustomGrid_Model_Grid
    extends Mage_Core_Model_Abstract
{
    /**
     * Session keys
     */
    const SESSION_BASE_KEY_CURRENT_PROFILE = '_blcg_session_key_current_profile_';
    const SESSION_BASE_KEY_APPLIED_FILTERS = '_blcg_session_key_applied_filters_';
    const SESSION_BASE_KEY_REMOVED_FILTERS = '_blcg_session_key_removed_filters_';
    const SESSION_BASE_KEY_TOKEN = '_blcg_session_key_token_';
    
    /**
     * Parameter name to use to hold grid token value (used for filters verification)
     */
    const GRID_TOKEN_PARAM_NAME  = '_blcg_token_';
    
    /**
     * Base profile ID
     */
    const BASE_PROFILE_ID = 0;
    
    /**
     * Attribute columns base keys
     */
    const ATTRIBUTE_COLUMN_ID_PREFIX  = '_blcg_attribute_column_';
    const ATTRIBUTE_COLUMN_GRID_ALIAS = 'blcg_attribute_field_';
    
    /**
     * Custom columns base keys
     */
    const CUSTOM_COLUMN_ID_PREFIX  = '_blcg_custom_column_';
    const CUSTOM_COLUMN_GRID_ALIAS = 'blcg_custom_field_';
    
    /**
     * Pitch to put between each column order at initialization
     * 
     * @var int
     */
    const COLUMNS_ORDER_PITCH = 10;
    
    /**
     * Default pagination values (usually hard-coded in grid template)
     * 
     * @var array
     */
    static protected $_defaultPaginationValues = array(20, 30, 50, 100, 200);
    
    /**
     * Possible column alignments values
     */
    const COLUMN_ALIGNMENT_LEFT   = 'left';
    const COLUMN_ALIGNMENT_CENTER = 'center';
    const COLUMN_ALIGNMENT_RIGHT  = 'right';
    
    /**
     * Column alignments options hash
     * 
     * @var array
     */
    static protected $_columnAlignments = null;
    
    /**
     * Column origins
     */
    const COLUMN_ORIGIN_GRID       = 'grid';
    const COLUMN_ORIGIN_COLLECTION = 'collection';
    const COLUMN_ORIGIN_ATTRIBUTE  = 'attribute';
    const COLUMN_ORIGIN_CUSTOM     = 'custom';
    
    /**
     * Column origins options hash
     * 
     * @var array
     */
    static protected $_columnOrigins = null;
    
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
     * @var array
     */
    static protected $_gridActions = null;
    
    /**
     * Grouped grid actions options hashes
     * 
     * @var array
     */
    static protected $_groupedGridActions = null;
    
    /**
     * Grids actions corresponding paths in the ACL configuration
     * 
     * @var array
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
     * Default parameters behaviours
     */
    const DEFAULT_PARAM_DEFAULT             = 'default';
    const DEFAULT_PARAM_FORCE_ORIGINAL      = 'force_original';
    const DEFAULT_PARAM_FORCE_CUSTOM        = 'force_custom';
    const DEFAULT_PARAM_MERGE_DEFAULT       = 'merge_default'; 
    const DEFAULT_PARAM_MERGE_BASE_ORIGINAL = 'merge_on_original';
    const DEFAULT_PARAM_MERGE_BASE_CUSTOM   = 'merge_on_custom';
    
    /**
     * Keys of values that can be redefined at profile-level
     * (and therefore should be stashed after load to remember the base state)
     *
     * @var array
     */
    static protected $_stashableProfileKeys = array(
        'default_page',
        'default_limit',
        'default_sort',
        'default_dir',
        'default_filter'
    );
    
    protected function _construct()
    {
        parent::_construct();
        $this->_init('customgrid/grid');
        $this->setIdFieldName('grid_id');
    }
    
    /**
     * Throw a permission-related exception
     * 
     * @param string|null $message Custom exception message
     */
    protected function _throwPermissionException($message=null)
    {
        $message = (is_null($message) ? $this->_getHelper()->__('You are not allowed to use this action') : $message);
        throw new BL_CustomGrid_Grid_Permission_Exception($message); 
    }
    
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
     * Return config helper
     * 
     * @return BL_CustomGrid_Helper_Config
     */
    protected function _getConfigHelper()
    {
        return Mage::helper('customgrid/config');
    }
    
    /**
     * Return admin session model
     * 
     * @return Mage_Admin_Model_Session
     */
    protected function _getAdminSession()
    {
        return Mage::getSingleton('admin/session');
    }
    
    /**
     * Return adminhtml session model
     * 
     * @return Mage_Adminhtml_Model_Session
     */
    protected function _getAdminhtmlSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }
    
    /**
     * Return our own session model
     * 
     * @return BL_CustomGrid_Model_Session
     */
    protected function _getBlcgSession()
    {
        return Mage::getSingleton('customgrid/session');
    }
    
    /**
     * Return currently logged-in user
     * 
     * @return Mage_Admin_Model_User|null
     */
    public function getSessionUser()
    {
        $user = $this->_getAdminSession()->getUser();
        return ($user && $user->getId() ? $user : null);
    }
    
    /**
     * Return the role of the currently logged-in user
     * 
     * @return Mage_Admin_Model_Role|null
     */
    public function getSessionRole()
    {
        return (($user = $this->getSessionUser()) ? $user->getRole() : null);
    }
    
    /**
     * Reset given data keys
     * 
     * @param array $keys Data keys to reset
     * @return this
     */
    protected function _resetKeys(array $keys)
    {
        foreach ($keys as $key) {
            $this->unsetData($key);
        }
        return $this;
    }
    
    /**
     * Reset data keys associated to grid type values
     *
     * @return this
     */
    protected function _resetTypeValues()
    {
        return $this->_resetKeys(array('type', 'type_model'));
    }
    
    /**
     * Reset data keys associated to columns values
     *
     * @return this
     */
    protected function _resetColumnsValues()
    {
        return $this->_resetKeys(array('columns', 'max_order', 'origin_ids', 'appliable_default_filter'));
    }
    
    /**
     * Reset data keys associated to users config values
     *
     * @return this
     */
    protected function _resetUsersConfigValues()
    {
        return $this->_resetKeys(array('users_config'));
    }
    
    /**
     * Reset data keys associated to roles config values
     *
     * @return this
     */
    protected function _resetRolesConfigValues()
    {
        return $this->_resetKeys(array('roles_config'));
    }
    
    /**
     * Reset data keys associated to profiles values
     *
     * @return this
     */
    protected function _resetProfilesValues()
    {
        $this->_resetAvailableProfilesValues();
        return $this->_resetKeys(array('base_profile', 'profiles', 'profile_id'));
    }
    
    /**
     * Reset data keys associated to available profiles
     * 
     * @return this
     */
    protected function _resetAvailableProfilesValues()
    {
        return $this->_resetKeys(array('available_profiles_ids'));
    }
    
    /**
     * Reset all data keys associated to sub values
     *
     * @return this
     */
    protected function _resetSubValues()
    {
        $this->_resetTypeValues();
        $this->_resetColumnsValues();
        $this->_resetRolesConfigValues();
        $this->_resetUsersConfigValues();
        $this->_resetProfilesValues();
        return $this;
    }
    
    /**
     * Reset data before load
     * 
     * @param mixed $id
     * @param mixed $field
     * @return this
     */
    protected function _beforeLoad($id, $field=null)
    {
        $this->setData(array());
        return parent::_beforeLoad($id, $field);
    }
    
    /**
     * Stash base values after load
     * 
     * @return this
     */
    protected function _afterLoad()
    {
        $this->_stashBaseProfileValues();
        return parent::_afterLoad();
    }
    
    /**
     * Set default values to uninitialized data keys before save
     *
     * @return this
     */
    protected function _beforeSave()
    {
        parent::_beforeSave();
        $this->getDataSetDefault('max_attribute_column_base_block_id', 0);
        $this->getDataSetDefault('max_custom_column_base_block_id', 0);
        return $this;
    }
    
    /**
     * Reset all sub values after save
     *
     * @return this
     */
    protected function _afterSave()
    {
        parent::_afterSave();
        $this->_resetSubValues();
        return $this;
    }
    
    /**
     * Ensures deletion is allowed before delete
     *
     * @return this
     */
    protected function _beforeDelete()
    {
        return !$this->checkUserActionPermission(self::ACTION_DELETE)
            ? $this->_throwPermissionException()
            : parent::_beforeDelete();
    }
    
    /**
     * Returns the result of Mage_Core_Model_Abstract::_getData(),
     * but previously ensures that the current profile is loaded
     * if the requested value can be overriden at profile-level
     *
     * @param string $key Data key
     * @return mixed
     */
    protected function _getData($key)
    {
        if (in_array($key, $this->getStashableProfileKeys())) {
            // Ensures the current profile is loaded if it can possibly "override" the requested value
            $this->getProfileId();
        }
        return parent::_getData($key);
    }
    
    /**
     * Returns the result of Mage_Core_Model_Abstract::getData(),
     * but previously ensures that the current profile is loaded
     * if the requested value can be overriden at profile-level
     *
     * @param  string $key Data key
     * @param string|int $index Value index
     * @return mixed
     */
    public function getData($key='', $index=null)
    {
        if (in_array($key, $this->getStashableProfileKeys())) {
            // Ensures the current profile is loaded if it can possibly "override" the requested value
            $this->getProfileId();
        }
        return parent::getData($key, $index);
    }
    
    /**
     * Set grid block type
     *
     * @param string $blockType Grid block type (eg: "adminhtml/catalog_product_grid")
     * @return this
     */
    public function setBlockType($blockType)
    {
        if ($blockType != $this->getBlockType()) {
            // Reset type model if the block type has changed
            $this->_resetTypeValues();
            $this->setData('block_type', $blockType);
        }
        return $this;
    }
    
    /**
     * Disable / enable the grid
     * 
     * @param bool $disabled Whether the grid is disabled or not
     * @return this
     */
    public function setDisabled($disabled)
    {
        return !$this->checkUserActionPermission(self::ACTION_ENABLE_DISABLE)
            ? $this->_throwPermissionException()
            : $this->setData('disabled', (bool) $disabled);
    }
    
    /**
     * Stash base values that can be redefined at profile-level
     *
     * @return this
     */
    protected function _stashBaseProfileValues()
    {
        foreach (self::$_stashableProfileKeys as $key) {
            $this->setData('base_' . $key, (isset($this->_data[$key]) ? $this->_data[$key] : null));
        }
        return $this;
    }
    
    /**
     * Return stashed base values
     *
     * @return array
     */
    protected function _getStashedBaseProfileValues()
    {
        $values = array();
        
        foreach (self::$_stashableProfileKeys as $key) {
            $values[$key] = $this->getData('base_' . $key);
        }
        
        return $values;
    }
    
    /**
     * Restore stashed base values
     *
     * @return this
     */
    protected function _restoreBaseProfileValues()
    {
        $this->addData($this->_getStashedBaseProfileValues());
        return $this;
    }
    
    /**
     * Return stashable profile keys
     *
     * @return array
     */
    public function getStashableProfileKeys()
    {
        return self::$_stashableProfileKeys;
    }
    
    /**
     * Return the keys corresponding to the variable names used by grid blocks
     * 
     * @return array
     */
    public function getBlockVarNameKeys()
    {
        return array('page', 'limit', 'sort', 'dir', 'filter');
    }
    
    /**
     * Return the default variable names used by grid blocks
     *
     * @return array
     */
    public function getBlockVarNameDefaults()
    {
        return array(
            'page'   => 'page',
            'limit'  => 'limit',
            'sort'   => 'sort',
            'dir'    => 'dir',
            'filter' => 'filter'
        );
    }
    
    /**
     * Set variable names, retrieved from the given block
     *
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return this
     */
    protected function _setVarNamesFromBlock(Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        foreach ($this->getBlockVarNameKeys() as $key) {
            $this->setData('var_name_' . $key, $gridBlock->getDataUsingMethod('var_name' . $key));
        }
        return $this;
    }
    
    /**
     * Return block variable name
     *
     * @param string $key Variable key
     * @return string
     */
    public function getBlockVarName($key)
    {
        $defaults = $this->getBlockVarNameDefaults();
        return (!isset($defaults[$key]) ? null : $this->getDataSetDefault('var_name_' . $key, $defaults[$key]));
    }
    
    /**
     * Return block variable names
     *
     * @return array
     */
    public function getBlockVarNames()
    {
        $varNames = array();
        
        foreach ($this->getBlockVarNameKeys() as $key) {
            $varNames[$key] = $this->getBlockVarName($key);
        }
        
        return $varNames;
    }
    
    /**
     * Return grid block session key for given parameter
     *
     * @param string $param Grid block parameter (should correspond to variable names)
     * @return string|null
     */
    public function getBlockParamSessionKey($param)
    {
        /**
         * Note: some grids may have a dynamic ID, but as it should be based in those cases on uniqHash(),
         * returning an old ID should not imply any potential conflict with any other ID
         */
        return (($blockId = $this->_getData('block_id')) ? $blockId . $param : null); 
    }
    
    /**
     *  Return grid type model
     *
     * @return BL_CustomGrid_Model_Grid_Type_Abstract|null
     */
    public function getTypeModel()
    {
        if (!$this->hasData('type_model')) {
            if ($blockType = $this->_getData('block_type')) {
                $rewritingClassName = $this->_getData('rewriting_class_name');
                $typeModels = Mage::getSingleton('customgrid/grid_type_config')->getTypesInstances();
                
                foreach ($typeModels as $code => $typeModel) {
                    if ($typeModel->isAppliableToGridBlock($blockType, $rewritingClassName)) {
                        $this->addData(array(
                            'type_code'  => $code,
                            'type_model' => $typeModel,
                        ));
                        break;
                    }
                }
                
                if (($forcedTypeCode = $this->_getData('forced_type_code'))
                    && isset($typeModels[$forcedTypeCode])) {
                    // @todo little note somewhere about potential risks (also, when juggling with types)
                    $this->setData('type_model', $typeModels[$forcedTypeCode]);
                } 
            } else {
                $this->unsetData('type_code');
            }
        }
        return $this->_getData('type_model');
    }
    
    /**
     * Return type model name, or given default value if the grid is associated to no type model
     *
     * @param string $default Default value
     * @return mixed
     */
    public function getTypeModelName($default='')
    {
        return (($typeModel = $this->getTypeModel()) ? $typeModel->getName() : $default);
    }
    
    /**
     * Set profiles
     * 
     * @param array $profiles Grid profiles
     * @return this
     */
    public function setProfiles(array $profiles)
    {
        $this->_resetColumnsValues();
        $this->_resetProfilesValues();
        
        foreach ($profiles as $key => $profile) {
            if (is_array($profile)) {
                $profiles[$key] = Mage::getModel('customgrid/grid_profile', $profile);
            }
            if (!is_object($profiles[$key])) {
                unset($profiles[$key]);
                continue;
            }
            $profiles[$key]->setData('grid_model', $this);
        }
        
        return $this->setData('profiles', $profiles);
    }
    
    /**
     * Return base profile ID (differentiating from null for comparison purposes)
     *
     * @return int
     */
    public function getBaseProfileId()
    {
        return self::BASE_PROFILE_ID;
    }
    
    /**
     * Return base profile name
     *
     * @return string
     */
    public function getBaseProfileName()
    {
        return $this->_getHelper()->__('Default');
    }
    
    /**
     * Return base profile model
     *
     * @return BL_CustomGrid_Model_Grid_Profile
     */
    protected function _getBaseProfile()
    {
        if (!$this->hasData('base_profile')) {
            $this->setData(
                'base_profile',
                Mage::getModel('customgrid/grid_profile')
                    ->setData(array_merge(
                        array(
                            'profile_id'    => $this->getBaseProfileId(),
                            'grid_id'       => $this->getId(),
                            'name'          => $this->getBaseProfileName(),
                            'is_restricted' => false,
                            'is_default'    => false,
                            'grid_model'    => $this,
                        ),
                        $this->_getStashedBaseProfileValues()
                    ))
            );
        }
        return $this->_getData('base_profile');
    }
    
    /**
     * Return all profiles
     *
     * @return array
     */
    protected function _getProfiles()
    {
        if (!$this->hasData('profiles')) {
            $profiles = (($id = $this->getId()) ? $this->_getResource()->getGridProfiles($id) : array());
            $this->setProfiles($profiles);
        }
        return $this->_getData('profiles');
    }
    
    /**
     * Profiles sort callback
     *
     * @param BL_CustomGrid_Model_Grid_Profile $a One profile
     * @param BL_CustomGrid_Model_Grid_Profile $b Another profile
     * @return int
     */
    protected function _sortProfiles($a, $b)
    {
        return ($a->isBase() ? -1 : ($b->isBase() ? 1 : strcasecmp($a->getName(), $b->getName())));
    }
    
    /**
     * Return profiles
     *
     * @param bool $includeBase Whether the base profile should also be returned
     * @param bool $onlyAvailable Whether only the profiles available to the current user should be returned
     * @param bool $sorted Whether profiles should be sorted
     * @return array
     */
    public function getProfiles($includeBase=false, $onlyAvailable=false, $sorted=false)
    {
        $profiles = $this->_getProfiles();
        
        if ($onlyAvailable) {
            $profiles = array_intersect_key($profiles, array_flip($this->getAvailableProfilesIds()));
        }
        if ($includeBase) {
            $profiles[$this->getBaseProfileId()] = $this->_getBaseProfile();
        }
        if ($sorted) {
            uasort($profiles, array($this, '_sortProfiles'));
        }
        
        return $profiles;
    }
    
    /**
     * Return IDs of the profiles assigned to the given role
     *
     * @param int|null $roleId Role ID (if null, the role of the current user will be used)
     * @return array
     */
    public function getRoleAssignedProfilesIds($roleId=null)
    {
        $assignedProfilesIds = array();
        
        if (is_null($roleId)) {
            $roleId = (($role = $this->getSessionRole()) ? $role->getId() : null);
        }
        if (!is_null($roleId)
            && ($roleConfig = $this->getRolesConfig($roleId))) {
            $assignedProfilesIds = $roleConfig->getDataSetDefault('assigned_profiles_ids', array());
        }
        
        return $assignedProfilesIds;
    }
    
    /**
     * Return available profiles IDs
     * 
     * @param bool $includeBase Whether the base profile ID should also be returned
     * @return array
     */
    public function getAvailableProfilesIds($includeBase=false)
    {
        if (!$this->hasData('available_profiles_ids')) {
            $profiles = $this->_getProfiles();
            
            if (!$this->checkUserActionPermission(self::ACTION_ACCESS_ALL_PROFILES)) {
                $assignedProfilesIds = $this->getRoleAssignedProfilesIds();
                
                foreach ($profiles as $key => $profile) {
                    if ($profile->isRestricted()
                        && !in_array($profile->getId(), $assignedProfilesIds, true)) {
                        unset($profiles[$key]);
                    }
                }
            }
            
            $this->setData('available_profiles_ids', array_keys($profiles));
        }
        
        $profilesIds = $this->_getData('available_profiles_ids');
        
        if ($includeBase) {
            $profilesIds[] = $this->getBaseProfileId();
        }
        
        return $profilesIds;
    }
    
    /**
     * Return whether given profile ID is available for the current user
     * 
     * @param int $profileId Profile Id
     * @return bool
     */
    public function isAvailableProfile($profileId)
    {
        return in_array($profileId, $this->getAvailableProfilesIds(true), true);
    }
    
    /**
     * Set current profile ID (either for temporary or "permanent" use)
     *
     * @param int $profileId (New) Current profile Id
     * @param bool $temporary Whether the profile ID should only be set temporary (= not in session / no session check)
     * @param bool $forced Whether the given profile ID is "forced" (ie, was not determined automatically)
     * @return this
     */
    public function setProfileId($profileId, $temporary=false, $forced=true)
    {
        $profileId = (int) $profileId;
        $profiles  = $this->getProfiles(true, true);
        
        if (!isset($profiles[$profileId])) {
            Mage::throwException($this->_getHelper()->__('This profile is not available'));
        }
        
        $this->_resetColumnsValues();
        $this->_restoreBaseProfileValues();
        $this->setData('profile_id', $profileId);
        
        if ($profileId !== $this->getBaseProfileId()) {
            $this->addData(array_intersect_key(
                $profiles[$profileId]->getData(),
                array_flip(self::$_stashableProfileKeys)
            ));
        }
        if (!$temporary) {
            $session = $this->_getAdminhtmlSession();
            $sessionKey = $this->_getSessionProfileIdKey();
            
            if ($session->hasData($sessionKey)) {
                $previousProfileId = $this->getSessionProfileId();
                $session->setData($sessionKey, $profileId);
                
                if ($profileId !== $previousProfileId) {
                    /**
                     * Remove all session parameters that were specific to the previous profile in some way
                     * Doing so (and removing parameters from the URL - what is done by the JS part of the extension)
                     * ensures that the new profile's default parameters will be used
                     */
                    // @todo we could save those values and restore them if the previous profile gets used again later
                    // @todo knowing that this could be a togglable option at global and grid level
                    
                    foreach ($this->getBlockVarNames() as $varName) {
                        if ($sessionKey = $this->getBlockParamSessionKey($varName)) {
                            $session->unsetData($sessionKey);
                        }
                    }
                    
                    // Ensure that the next filters verification won't mess with the new filters
                    $session->unsetData($this->_getAppliedFiltersSessionKey($previousProfileId));
                    $session->unsetData($this->_getRemovedFiltersSessionKey($previousProfileId));
                }
            } else {
                $session->setData($sessionKey, $profileId);
            }
        }
        
        return $this;
    }
    
    /**
     * Return session key corresponding to the current profile ID
     *
     * @return string
     */
    protected function _getSessionProfileIdKey()
    {
        return self::SESSION_BASE_KEY_CURRENT_PROFILE . '_' . $this->getId();
    }
    
    /**
     * Return the current profile ID in session
     *
     * @return int|null
     */
    public function getSessionProfileId()
    {
        $profileId = $this->_getAdminhtmlSession()->getData($this->_getSessionProfileIdKey());
        
        if (!is_null($profileId)) {
            $profileId = (int) $profileId;
        }
        
        return $profileId;
    }
    
    /**
     * Return default profile ID for given user
     *
     * @param int|null $userId User ID (if null, the current user will be used)
     * @return int|null
     */
    public function getUserDefaultProfileId($userId=null)
    {
        $defaultProfileId = null;
        
        if (is_null($userId)) {
            $userId = (($user = $this->getSessionUser()) ? $user->getId() : null);
        }
        if (!is_null($userId)
            && ($userConfig = $this->getUsersConfig($userId))) {
            $defaultProfileId = $userConfig->getData('default_profile_id');
        }
        
        return $defaultProfileId;
    }
    
    /**
     * Return default profile ID for given role
     *
     * @param int|null $roleId Role ID (if null, the role of the current user will be used)
     * @return int|null
     */
    public function getRoleDefaultProfileId($roleId=null)
    {
        $defaultProfileId = null;
        
        if (is_null($roleId)) {
            $roleId = (($role = $this->getSessionRole()) ? $role->getId() : null);
        }
        if (!is_null($roleId)
            && ($roleConfig = $this->getRolesConfig($roleId))) {
            $defaultProfileId = $roleConfig->getData('default_profile_id');
        }
        
        return $defaultProfileId;
    }
    
    /**
     * Return the global default profile ID (which can not be the base profile)
     *
     * @return int|null
     */
    public function getGlobalDefaultProfileId()
    {
        $defaultProfileId = null;
        $profiles = $this->getProfiles();
        
        foreach ($profiles as $profile) {
            if ($profile->isGlobalDefault()) {
                $defaultProfileId = $profile->getId();
                break;
            }
        }
        
        return $defaultProfileId;
    }
    
    /**
     * Return the current profile ID
     *
     * @return int
     */
    protected function _getProfileId()
    {
        if (!$this->hasData('profile_id')) {
            if (!$this->getId()) {
                // Force base profile without resetting anything if nothing has been saved yet
                $this->setData('profiles', array());
                $this->setData('profile_id', $this->getBaseProfileId());
            } else {
                $profiles  = $this->getProfiles(true, true);
                $profileId = $this->getBaseProfileId();
                $sessionProfileId = $this->getSessionProfileId();
                
                $defaultProfilesIds = array(
                    $sessionProfileId,
                    $this->getUserDefaultProfileId(),
                    $this->getRoleDefaultProfileId(),
                    $this->getGlobalDefaultProfileId(),
                );
                
                foreach ($defaultProfilesIds as $defaultProfileId) {
                    if (!is_null($defaultProfileId)
                        && isset($profiles[$defaultProfileId])) {
                        $profileId = (int) $defaultProfileId;
                        break;
                    }
                }
                
                if (is_int($sessionProfileId)
                    && ($sessionProfileId !== $profileId)) {
                    $this->_getBlcgSession()
                        ->addNotice($this->_getHelper()->__('The previous profile is not available anymore'));
                }
                
                $this->setProfileId($profileId, false, false);
            }
        }
        return $this->_getData('profile_id');
    }
    
    /**
     * Return the current profile ID
     *
     * @return int
     */
    public function getProfileId()
    {
        return $this->_getProfileId();
    }
    
    /**
     * Return a profile by its ID
     *
     * @param int|null $profileId Profile ID (if null, the current profile will be returned)
     * @return array
     */
    public function getProfile($profileId=null)
    {
        $profiles = $this->getProfiles(true, true);
        
        if (is_null($profileId)) {
            $profileId = $this->getProfileId();
        }
        if (!isset($profiles[$profileId])) {
            Mage::throwException($this->_getHelper()->__('This profile is not available'));
        }
        
        return $profiles[$profileId];
    }
    
    /**
     * Return whether profiles created by users who do not have the permission to assign profiles
     * should be restricted by default
     * 
     * @return bool
     */
    public function getProfilesDefaultRestricted()
    {
        return is_null($value = $this->_getData('profiles_default_restricted'))
            ? $this->_getConfigHelper()->getProfilesDefaultRestricted()
            : (bool) $value;
    }
    
    /**
     * Return the roles IDs to which should be assigned the profiles created by users
     * who do not have the permission to do so
     * 
     * @return array
     */
    public function getProfilesDefaultAssignedTo()
    {
        return is_null($value = $this->_getData('profiles_default_assigned_to'))
            ? $this->_getConfigHelper()->getProfilesDefaultAssignedTo()
            : $this->_getHelper()->parseCsvIntArray($value, true, false, 1);
    }
    
    /**
     * Update profiles default values
     * 
     * @param array $defaults New profiles default values
     * @return this
     */
    public function updateProfilesDefaults(array $defaults)
    {
        $assignKeys = array('restricted', 'assigned_to');
        $assignValues = array_intersect_key($defaults, array_flip($assignKeys));
        
        if (!empty($assignValues)) {
            if (!$this->checkUserActionPermission(self::ACTION_ASSIGN_PROFILES)) {
                $this->_throwPermissionException();
            }
            if (isset($assignValues['restricted'])) {
                $value = ($assignValues['restricted'] !== '' ? (bool) $assignValues['restricted'] : null);
                $this->setData('profiles_default_restricted', $value);
            }
            if (isset($assignValues['assigned_to'])) {
                $value = (is_array($assignValues['assigned_to']) ? implode(',', $assignValues['assigned_to']) : null);
                $this->setData('profiles_default_assigned_to', $value);
            }
        }
        
        return $this;
    }
    
    /**
     * (Un-)Choose given profile as default for given users and roles, and globally
     * (expected values and corresponding possibilities depending on permissions)
     * 
     * @param int $profileId ID of the profile to (un-)choose as default
     * @param array $values Array with "users", "roles" and "global" keys, holding corresponding value(s)
     * @return this
     */
    public function chooseProfileAsDefault($profileId, array $values)
    {
        $helper = $this->_getHelper();
        $profiles = $this->getProfiles(true, true);
        $defaultFor = array();
        
        if (!isset($profiles[$profileId])) {
            Mage::throwException($helper->__('This profile is not available'));
        }
        
        if (isset($values['users']) && is_array($values['users'])) {
            $values['users'] = array_filter($values['users']);
            $defaultFor['users'] = array();
            $ownUserId = $this->getSessionUser()->getId();
            $ownChosen = in_array($ownUserId, $values['users']);
            $otherChosenIds = array_diff($values['users'], array($ownUserId));
            
            if ($this->checkUserActionPermission(self::ACTION_CHOOSE_OWN_USER_DEFAULT_PROFILE)) {
                if ($ownChosen) {
                    $defaultFor['users'][] = $ownUserId;
                }
            } elseif ($ownChosen) {
                $this->_throwPermissionException();
            } elseif ($this->getUserDefaultProfileId() === $profileId) {
                $defaultFor['users'][] = $ownUserId;
            }
            
            if ($this->checkUserActionPermission(self::ACTION_CHOOSE_OTHER_USERS_DEFAULT_PROFILE)) {
                $defaultFor['users'] = array_merge($defaultFor['users'], $otherChosenIds);
            } elseif (!empty($otherChosenIds)) {
                $this->_throwPermissionException();
            } else {
                $usersIds = Mage::getModel('admin/user')
                    ->getCollection()
                    ->getAllIds();
                
                foreach ($usersIds as $userId) {
                    if (($userId != $ownUserId)
                        && ($this->getUserDefaultProfileId($userId) === $profileId)) {
                        $defaultFor['users'][] = $userId;
                    }
                }
            }
        }
        if (isset($values['roles']) && is_array($values['roles'])) {
            $values['roles'] = array_filter($values['roles']);
            $defaultFor['roles'] = array();
            $ownRoleId = $this->getSessionRole()->getId();
            $ownChosen = in_array($ownRoleId, $values['roles']);
            $otherChosenIds = array_diff($values['roles'], array($ownRoleId));
            
            if ($this->checkUserActionPermission(self::ACTION_CHOOSE_OWN_ROLE_DEFAULT_PROFILE)) {
                if ($ownChosen) {
                    $defaultFor['roles'][] = $ownRoleId;
                }
            } elseif ($ownChosen) {
                $this->_throwPermissionException();
            } elseif ($this->getRoleDefaultProfileId() === $profileId) {
                $defaultFor['roles'][] = $ownUserId;
            }
            
            if ($this->checkUserActionPermission(self::ACTION_CHOOSE_OTHER_ROLES_DEFAULT_PROFILE)) {
                $defaultFor['roles'] = array_merge($defaultFor['roles'], $otherChosenIds);
            } elseif (!empty($otherChosenIds)) {
                $this->_throwPermissionException();
            } else {
                $rolesIds = Mage::getModel('admin/roles')
                    ->getCollection()
                    ->getAllIds();
                
                foreach ($rolesIds as $roleId) {
                    if (($roleId != $ownRoleId)
                        && ($this->getRoleDefaultProfileId($roleId) === $profileId)) {
                        $defaultFor['roles'][] = $roleId;
                    }
                }
            }
        }
        if (isset($values['global'])) {
            if ($this->checkUserActionPermission(self::ACTION_CHOOSE_GLOBAL_DEFAULT_PROFILE)) {
                $defaultFor['global'] = (bool) $values['global'];
            } else {
                $this->_throwPermissionException();
            }
        }
        
        $this->_getResource()->chooseProfileAsDefault($this->getId(), $profileId, $defaultFor);
        
        if (isset($defaultFor['users'])) {
             $this->_resetUsersConfigValues();
        }
        if (isset($defaultFor['roles'])) {
            $this->_resetRolesConfigValues();
        }
        if (isset($defaultFor['global'])) {
            $this->_resetProfilesValues();
        }
        
        return $this;
    }
    
    /**
     * Copy given profile to a new one
     *
     * @param int $profileId Copied profile ID
     * @param array $newValues New profile values
     * @return int New profile ID
     */
    public function copyProfileToNew($profileId, array $values)
    {
        $helper = $this->_getHelper();
        $profiles = $this->getProfiles(true, true);
        
        if (!$this->checkUserActionPermission(self::ACTION_COPY_PROFILES_TO_NEW)) {
            $this->_throwPermissionException();
        } elseif (!isset($profiles[$profileId])) {
            Mage::throwException($helper->__('The copied profile is not available'));
        } elseif (!isset($values['name'])) {
            Mage::throwException($helper->__('The profile name must be filled'));
        }
        
        $values['name'] = trim($values['name']);
        $assignedRolesIds = null;
        
        foreach ($profiles as $profile) {
            if (trim($profile->getName()) === $values['name']) {
                Mage::throwException($helper->__('Another profile from the same grid already has this name'));
            }
        }
        
        if ($this->checkUserActionPermission(self::ACTION_ASSIGN_PROFILES)) {
            if ((isset($values['is_restricted']) && $values['is_restricted'])
                && (isset($values['assigned_to']) && is_array($values['assigned_to']))) {
                $assignedRolesIds = $values['assigned_to'];
            }
        } elseif ($this->getProfilesDefaultRestricted()) {
            $assignedRolesIds = $this->getProfilesDefaultAssignedTo();
            $sessionRoleId  = $this->getSessionRole()->getId();
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
        
        $newProfileId = $this->_getResource()->copyProfileToNew($this->getId(), $profileId, $values);
        $this->_resetProfilesValues();
        
        if ($values['is_restricted']) {
            $this->_resetRolesConfigValues();
        }
        
        return (int) $newProfileId;
    }
    
    /**
     * Copy given profile to another existing one
     * 
     * @param int $profileId ID of the copied profile
     * @param int $toProfileId ID of the profile on which to copy
     * @param array $values Copied values (columns and/or default parameters)
     * @return this
     */
    public function copyProfileToExisting($profileId, $toProfileId, array $values)
    {
        $helper = $this->_getHelper();
        $profiles = $this->getProfiles(true, true);
        
        if (!$this->checkUserActionPermission(self::ACTION_COPY_PROFILES_TO_EXISTING)) {
            $this->_throwPermissionException();
        } elseif (!isset($profiles[$profileId])) {
            Mage::throwException($helper->__('The copied profile is not available'));
        } elseif (!isset($profiles[$toProfileId])) {
            Mage::throwException($helper->__('The profile on which to copy is not available'));
        } elseif ($profileId === $toProfileId) {
            Mage::throwException($helper->__('A profile can not be copied to itself'));
        }
        
        $this->_getResource()->copyProfileToExisting($this->getId(), $profileId, $toProfileId, $values);
        $this->_resetProfilesValues();
        
        return $this;
    }
    
    /**
     * Update profile values
     * 
     * @param int $profileId Updated profile ID
     * @param array $values New profile values
     * @return this
     */
    public function updateProfile($profileId, array $values)
    {
        $helper = $this->_getHelper();
        $profiles = $this->getProfiles(true, true);
        
        if (!$this->checkUserActionPermission(self::ACTION_EDIT_PROFILES)) {
            $this->_throwPermissionException();
        } elseif (!isset($profiles[$profileId])) {
            Mage::throwException($helper->__('This profile is not available'));
        } elseif ($profileId === $this->getBaseProfileId()) {
            Mage::throwException($helper->__('The base profile can not be edited'));
        } elseif (!isset($values['name'])) {
            Mage::throwException($helper->__('The profile name must be filled'));
        }
        
        $editableKeys = array('name');
        $values = array_intersect_key($values, array_flip($editableKeys));
        $values['name'] = trim($values['name']);
        
        foreach ($profiles as $profile) {
            if ((trim($profile->getName()) === $values['name'])
                && ($profile->getId() !== $profileId)) {
                Mage::throwException($helper->__('Another profile from the same grid already has this name'));
            }
        }
        
        $this->_getResource()->updateProfile($this->getId(), $profileId, $values);
        $profiles[$profileId]->addData($values);
        
        return $this;
    }
    
    /**
     * (Un-)Restrict and/or (un-)assign given profile
     * 
     * @param int $profileId ID of the profile to (un-)restrict and/or (un-)assign
     * @param array $values Array with "is_restricted" and "assigned_to" keys, holding corresponding value(s)
     * @return this
     */
    public function assignProfile($profileId, array $values)
    {
        $helper = $this->_getHelper();
        $profiles = $this->getProfiles(true, true);
        
        if (!$this->checkUserActionPermission(self::ACTION_ASSIGN_PROFILES)) {
            $this->_throwPermissionException();
        } elseif (!isset($profiles[$profileId])) {
            Mage::throwException($helper->__('This profile is not available'));
        } elseif ($profileId === $this->getBaseProfileId()) {
            Mage::throwException($helper->__('The base profile can not be assigned'));
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
        
        $this->_getResource()->updateProfile($this->getId(), $profileId, $values);
        $this->_resetProfilesValues();
        $this->_resetRolesConfigValues();
        
        return $this;
    }
    
    /**
     * Delete given profile
     * 
     * @param int $profileId ID of the profile to delete
     * @return this
     */
    public function deleteProfile($profileId)
    {
        $helper = $this->_getHelper();
        $profiles = $this->getProfiles(true, true);
        
        if (!$this->checkUserActionPermission(self::ACTION_DELETE_PROFILES)) {
            $this->_throwPermissionException();
        } elseif (!isset($profiles[$profileId])) {
            Mage::throwException($helper->__('This profile is not available'));
        } elseif ($profileId === $this->getBaseProfileId()) {
            Mage::throwException($helper->__('The base profile can not be deleted'));
        }
        
        $this->_getResource()->deleteProfile($this->getId(), $profileId);
        $this->_resetProfilesValues();
        $this->_resetUsersConfigValues();
        $this->_resetRolesConfigValues();
        
        return $this;
    }
    
    /**
     * Return columns IDs by column origin
     *
     * @return array
     */
    public function getColumnIdsByOrigin()
    {
        return $this->getDataSetDefault('column_ids_by_origin', array(
            self::COLUMN_ORIGIN_GRID       => array(),
            self::COLUMN_ORIGIN_COLLECTION => array(),
            self::COLUMN_ORIGIN_ATTRIBUTE  => array(),
            self::COLUMN_ORIGIN_CUSTOM     => array(),
            
        ));
    }
    
    /**
     * Return default interval between two columns order values
     *
     * @return int
     */
    public function getColumnsOrderPitch()
    {
        return self::COLUMNS_ORDER_PITCH;
    }
    
    /**
     * Return the maximum order value amongst all columns
     *
     * @return int
     */
    public function getColumnsMaxOrder()
    {
        return $this->getDataSetDefault('columns_max_order', 0);
    }
    
    /**
     * Recompute columns maximum order
     *
     * @var int $newOrder If set, the new maximum order will only be computed from the current value and the given one
     * @return this
     */
    protected function _recomputeColumnsMaxOrder($newOrder=null)
    {
        if (is_null($newOrder)) {
            $maxOrder = ~PHP_INT_MAX;
            
            foreach ($this->getColumns() as $column) {
                $maxOrder = max($maxOrder, $column->getOrder());
            }
            
            $this->setData('columns_max_order', $maxOrder);
        } else {
            $this->setData('columns_max_order', max($this->getColumnsMaxOrder(), $newOrder));
        }
        return $this;
    }
    
    /**
     * Increase maximum order by the order pitch and return the new value
     *
     * @return int
     */
    protected function _getNextColumnOrder()
    {
        $this->setData('columns_max_order', $this->getColumnsMaxOrder() + $this->getColumnsOrderPitch());
        return $this->getColumnsMaxOrder();
    }
    
    /**
     * Add a column to the columns list
     *
     * @param array $column Column values
     * @return this
     */
    protected function _addColumn(array $column)
    {
        $this->getColumns();
        $this->getColumnIdsByOrigin();
        $column['grid_model'] = $this;
        $blockId = $column['block_id'];
        $this->_data['columns'][$blockId] = Mage::getModel('customgrid/grid_column', $column);
        $this->_data['column_ids_by_origin'][$column['origin']][] = $blockId;
        $this->_recomputeColumnsMaxOrder($column['order']);
        $this->setDataChanges(true);
        return $this;
    }
    
    /**
     * Set columns
     *
     * @param array $columns Grid columns
     * @return this
     */
    public function setColumns(array $columns)
    {
        $this->_resetColumnsValues();
        $this->setData('columns', array());
        
        foreach ($columns as $column) {
            if (isset($column['block_id'])) {
                $this->_addColumn($column);
            }
        }
        
        return $this;
    }
    
    /**
     * Return all columns
     *
     * @return array
     */
    protected function _getColumns()
    {
        if (!$this->hasData('columns')) {
            $columns = array();
            
            if ($id = $this->getId()) {
                $columns = $this->_getResource()->getGridColumns($id, $this->getProfileId());
            }
            
            $this->setColumns($columns);
        }
        return $this->_getData('columns');
    }
    
    /**
     * Return all columns, possibly with some additional informations
     * 
     * @param bool $withEditConfigs Whether edit configs should be added to the corresponding columns
     * @param bool $withCustomColumnModels Whether custom columns models should be added to the corresponding columns
     * @return array
     */
    public function getColumns($withEditConfigs=false, $withCustomColumnModels=false)
    {
        $columns = $this->_getColumns();
        
        if (($withEditConfigs || $withCustomColumnModels)
            && ($typeModel = $this->getTypeModel())) {
            if ($withEditConfigs) {
                $columns = $typeModel->applyEditConfigsToColumns($this->getBlockType(), $columns);
            }
            if ($withCustomColumnModels) {
                $originIds = $this->getColumnIdsByOrigin();
                $customColumns = $this->getAvailableCustomColumns(false, true);
                
                foreach ($originIds[self::COLUMN_ORIGIN_CUSTOM] as $blockId) {
                    if (isset($customColumns[$columns[$blockId]->getIndex()])) {
                        $columns[$blockId]->setCustomColumnModel($customColumns[$columns[$blockId]->getIndex()]);
                    }
                }
            }
        }
        
        return $columns;
    }
    
    /**
     * Columns sort callback
     *
     * @param BL_CustomGrid_Model_Grid_Column $a One column
     * @param BL_CustomGrid_Model_Grid_Column $b Another column
     * @return int
     */
    protected function _sortColumns(BL_CustomGrid_Model_Grid_Column $a, BL_CustomGrid_Model_Grid_Column $b)
    {
        return $a->compareOrderTo($b);
    }
    
    /**
     * Return sorted columns, possibly filtered and with some additional informations
     *
     * @param bool $includeValid Whether valid columns should be returned (ie not missing ones)
     * @param bool $includeMissing Whether missing columns should be returned
     * @param bool $includeAttribute Whether attribute columns should be returned
     * @param bool $includeCustom Whether custom columns should be returned
     * @param bool $onlyVisible Whether only visible columns should be returned
     * @param bool $withEditConfigs Whether edit configs should be added to the corresponding columns
     * @param bool $withCustomColumnModels Whether custom columns models should be added to the corresponding columns
     * @return array
     */
    public function getSortedColumns($includeValid=true, $includeMissing=true, $includeAttribute=true,
        $includeCustom=true, $onlyVisible=false, $withEditConfigs=false, $withCustomColumnModels=false)
    {
        $columns = array();
        
        foreach ($this->getColumns($withEditConfigs, $withCustomColumnModels) as $columnBlockId => $column) {
            if (($onlyVisible && !$column->isVisible())
                || (!$includeMissing && $column->isMissing())
                || (!$includeValid && !$column->isMissing())
                || (!$includeAttribute && $column->isAttribute())
                || (!$includeCustom && $column->isCustom())) {
                continue;
            }
            $columns[$columnBlockId] = $column;
        }
        
        uasort($columns, array($this, '_sortColumns'));
        return $columns;
    }
    
    /**
     * Return the column corresponding to the given internal ID
     *
     * @param int $columnId Column internal ID
     * @return BL_CustomGrid_Model_Grid_Column|null
     */
    public function getColumnById($columnId)
    {
        $foundColumn = null;
        
        foreach ($this->getColumns() as $column) {
            if ($column->getId() == $columnId) {
                $foundColumn = $column;
                break;
            }
        }
        
        return $foundColumn;
    }
    
    /**
     * Return the column corresponding to the given block ID
     *
     * @param string $blockId Column block ID
     * @return BL_CustomGrid_Model_Grid_Column|null
     */
    public function getColumnByBlockId($blockId)
    {
        $columns = $this->getColumns();
        return (isset($columns[$blockId]) ? $columns[$blockId] : null);
    }
    
    /**
     * Return a column index from given code, origin and position (if applying)
     *
     * @param string $code Column code
     * @param string $origin Column origin
     * @param int $position Column position (used for attribute and custom origins)
     * @return string|null
     */
    public function getColumnIndexFromCode($code, $origin, $position=null)
    {
        $columns = $this->getColumns();
        $originIds = $this->getColumnIdsByOrigin();
        
        if (($origin == self::COLUMN_ORIGIN_ATTRIBUTE) || ($origin == self::COLUMN_ORIGIN_CUSTOM)) {
            // Assume given code corresponds to attribute/custom column code
            $foundColumn = null;
            $correspondingColumns = array();
            
            foreach ($originIds[$origin] as $columnId) {
                if ($columns[$columnId]->getIndex() == $code) {
                    $correspondingColumns[] = $columns[$columnId];
                }
            }
            
            usort($correspondingColumns, '_sortColumns');
            $columnsCount = count($correspondingColumns);
            
            // If column is found, return the actual index that will be used for the grid block
            if (($position >= 1) && ($position <= $columnsCount)) {
                $foundColumn = $correspondingColumns[$position-1];
            } elseif ($columnsCount > 0) {
                $foundColumn = $correspondingColumns[0];
            }
            
            if (!is_null($foundColumn)) {
                if ($origin == self::COLUMN_ORIGIN_ATTRIBUTE) {
                    return self::ATTRIBUTE_COLUMN_GRID_ALIAS
                        . str_replace(self::ATTRIBUTE_COLUMN_ID_PREFIX, '', $foundColumn->getBlockId());
                } else {
                    return self::CUSTOM_COLUMN_GRID_ALIAS
                        . str_replace(self::CUSTOM_COLUMN_ID_PREFIX, '', $foundColumn->getBlockId());
                }
            }
        } elseif (array_key_exists($origin, $this->getColumnOrigins())) {
            // Assume given code corresponds to column block ID
            if (isset($columns[$code]) && in_array($code, $originIds[$origin], true)) {
                // Return column index only if column exists and comes from wanted origin
                return $columns[$code]->getIndex();
            }
        }
        
        return null;
    }
    
    /**
     * Extract column values from given array
     *
     * @param array $column Column values
     * @param bool $allowStore Whether store ID value is allowed
     * @param bool $allowRenderer Whether renderer values are allowed
     * @param bool $requireRendererType Whether renderer type is required
     * @param bool $allowEditable Whether editability value is allowed
     * @param bool $allowCustomizationParams Whether customization parameters are allowed
     * @return array
     */
    protected function _extractColumnValues(array $column, $allowStore=false, $allowRenderer=false,
        $requireRendererType=true, $allowEditable=false, $allowCustomizationParams=false)
    {
        $values = array();
        
        if ($values['is_visible'] = (isset($column['is_visible']) && $column['is_visible'])) {
            $values['is_only_filterable'] = (isset($column['filter_only']) && $column['filter_only']);
        }  else {
            $values['is_only_filterable'] = false;
        }
        if (isset($column['align']) && array_key_exists($column['align'], $this->getColumnAlignments())) {
            $values['align'] = $column['align'];
        }
        if (isset($column['header'])) {
            $values['header'] = $column['header'];
        }
        if (isset($column['order'])) {
            $values['order'] = (int) $column['order'];
        }
        if (isset($column['width'])) {
            $values['width'] = $column['width'];
        }
        if ($allowStore && isset($column['store_id']) && ($column['store_id'] !== '')) {
            $values['store_id'] = $column['store_id'];
        } else {
            $values['store_id'] = null;
        }
        if ($allowRenderer
            && (!$requireRendererType || (isset($column['renderer_type']) && ($column['renderer_type'] !== '')))) {
             $values['renderer_type'] = ($requireRendererType ? $column['renderer_type'] : null);
             
             if (isset($column['renderer_params']) && ($column['renderer_params'] !== '')) {
                 $values['renderer_params'] = $column['renderer_params'];
             } else {
                 $values['renderer_params'] = null;
             }
        } else {
            $values['renderer_type'] = null;
            $values['renderer_params'] = null;
        }
        if ($allowEditable) {
            $values['is_edit_allowed'] = (isset($column['editable']) && $column['editable']);
        }
        if ($allowCustomizationParams
            && isset($column['customization_params']) && ($column['customization_params'] !== '')) {
            $values['customization_params'] = $column['customization_params'];
        } else {
            $values['customization_params'] = null;
        }
        
        return $values;
    }
    
    /**
     * Update columns according to given values
     *
     * @param array $columns New column values
     * @return this
     */
    public function updateColumns(array $columns)
    {
        if (!$this->checkUserActionPermission(self::ACTION_CUSTOMIZE_COLUMNS)) {
            $this->_throwPermissionException();
        }
        
        $this->getColumns(true);
        $this->getColumnIdsByOrigin();
        $allowEditable = $this->checkUserActionPermission(self::ACTION_CHOOSE_EDITABLE_COLUMNS);
        $availableAttributeCodes = $this->getAvailableAttributesCodes();
        
        // Update existing columns
        foreach ($this->getColumns(true, true) as $columnBlockId => $column) {
            $columnId = $column->getId();
            
            if (isset($columns[$columnId])) {
                $newColumn     = $columns[$columnId]; 
                $isCollection  = $column->isCollection();
                $isAttribute   = $column->isAttribute();
                $isCustom      = $column->isCustom();
                $customColumn  = ($isCustom ? $column->getCustomColumnModel() : null);
                
                $this->_data['columns'][$columnBlockId]->addData($this->_extractColumnValues(
                    $newColumn,
                    ($isCustom || ($customColumn && $customColumn->getAllowStore())),
                    ($isCollection || $isAttribute || ($customColumn && $customColumn->getAllowRenderers())),
                    ($isCollection || $isCustom),
                    ($allowEditable && $column->isEditable()),
                    $isCustom
                ));
                
                if ($isAttribute
                    && isset($newColumn['index'])
                    && in_array($newColumn['index'], $availableAttributeCodes, true)) {
                    // Update index if possible for attribute columns
                    $column->setIndex($newColumn['index']);
                }
                
                // At the end, there should only remain in $columns new attribute columns (without a valid ID yet)
                unset($columns[$columnId]);
            } else {
                // Assume deleted column
                $columnOrigin = $this->_data['columns'][$columnBlockId]->getOrigin();
                $originKey = array_search($columnBlockId, $this->_data['column_ids_by_origin'][$columnOrigin]);
                
                if ($originKey !== false) {
                    unset($this->_data['column_ids_by_origin'][$columnOrigin][$originKey]);
                }
                
                unset($this->_data['columns'][$columnBlockId]);
            }
        }
        
        // Add new attribute columns
        if ($this->canHaveAttributeColumns()) {
            foreach ($columns as $columnId => $column) {
                if (($columnId < 0) // Concerned columns IDs should be < 0, so assume others IDs are inexisting ones
                    && isset($column['index'])
                    && in_array($column['index'], $availableAttributeCodes, true)) {
                    $newColumnBlockId = $this->_getNextAttributeColumnBlockId();
                    
                    $newColumn = array_merge(
                        array(
                            'grid_id'             => $this->getId(),
                            'block_id'             => $newColumnBlockId,
                            'index'                => $column['index'],
                            'width'                => '',
                            'align'                => self::COLUMN_ALIGNMENT_LEFT,
                            'header'               => '',
                            'order'                => 0,
                            'origin'               => self::COLUMN_ORIGIN_ATTRIBUTE,
                            'is_visible'           => true,
                            'is_only_filterable'   => false,
                            'is_system'            => false,
                            'is_missing'           => false,
                            'store_id'             => null,
                            'renderer_type'        => null,
                            'renderer_params'      => null,
                            'is_edit_allowed'      => true,
                            'customization_params' => null,
                        ),
                        $this->_extractColumnValues($column, true, true, false, $allowEditable)
                    );
                    
                    $this->_addColumn($newColumn);
                }
            }
        }
        
        $this->setDataChanges(true);
        return $this;
    }
    
    /**
     * Return whether attribute columns are available
     *
     * @return bool
     */
    public function canHaveAttributeColumns()
    {
        return (($typeModel = $this->getTypeModel()) && $typeModel->canHaveAttributeColumns($this->getBlockType()));
    }
    
    /**
     * Return available attributes
     *
     * @param bool $withRendererCodes Whether renderers codes should be added to the attributes
     * @param bool $withEditableFlags Whether editable flag should be added to the attributes
     * @return array
     */
    public function getAvailableAttributes($withRendererCodes=false, $withEditableFlags=false)
    {
        $attributes = array();
        
        if ($typeModel = $this->getTypeModel()) {
            $attributes = $typeModel->getAvailableAttributes($this->getBlockType(), $withEditableFlags);
            
            if ($withRendererCodes) {
                $renderers = Mage::getSingleton('customgrid/column_renderer_config_attribute')->getRenderersInstances();
                
                foreach ($attributes as $attribute) {
                    $attribute->setRendererCode(null);
                    
                    foreach ($renderers as $code => $renderer) {
                        if ($renderer->isAppliableToAttribute($attribute, $this)) {
                            $attribute->setRendererCode($code);
                            break;
                        }
                    }
                }
            }
        }
        
        return $attributes;
    }
    
    /**
     * Return available attributes codes
     *
     * @return array
     */
    public function getAvailableAttributesCodes()
    {
        return array_keys($this->getAvailableAttributes());
    }
    
    /**
     * Return renderer types codes from available attributes
     *
     * @return array
     */
    public function getAvailableAttributesRendererTypes()
    {
        $rendererTypes = array();
        $attributes = $this->getAvailableAttributes(true);
        
        foreach ($attributes as $code => $attribute) {
            $rendererTypes[$code] = $attribute->getRendererCode();
        }
        
        return $rendererTypes;
    }
    
    /**
     * Return next attribute column block ID (auto-generated ones)
     *
     * @return string
     */
    protected function _getNextAttributeColumnBlockId()
    {
        if (($maxId = $this->getMaxAttributeColumnBaseBlockId()) > 0) {
            $baseBlockId = $maxId + 1;
        } else {
            $baseBlockId = 1;
        }
        $this->setMaxAttributeColumnBaseBlockId($baseBlockId);
        return self::ATTRIBUTE_COLUMN_ID_PREFIX . $baseBlockId;
    }
    
    /**
     * Return whether some custom columns are available
     *
     * @return bool
     */
    public function canHaveCustomColumns()
    {
        return ($typeModel = $this->getTypeModel())
             && $typeModel->canHaveCustomColumns($this->getBlockType(), $this->getRewritingClassName());
    }
    
    /**
     * Add grid type code to given custom column code
     *
     * @param string $code Column code
     * @param string $typeCode Grid type code
     * @return this
     */
    protected function _addTypeToCustomColumnCode(&$code, $typeCode=null)
    {
        if (strpos($code, '/') === false) {
            if (is_null($typeCode)) {
                if ($typeModel = $this->getTypeModel()) {
                    $typeCode = $typeModel->getCode();
                } else {
                    return $this;
                }
            }
            $code = $typeCode . '/' . $code;
        }
        return $this;
    }
    
    /**
     * Return currently used custom columns codes
     *
     * @param bool $includeTypeCode Whether column codes should include the grid type code
     * @return array
     */
    public function getUsedCustomColumnsCodes($includeTypeCode=false)
    {
        if ($typeModel = $this->getTypeModel()) {
            $typeCode = $typeModel->getCode();
        } else {
            return array();
        }
        
        $codes = array();
        $columns = $this->getColumns();
        $originIds = $this->getColumnIdsByOrigin();
        
        foreach ($originIds[self::COLUMN_ORIGIN_CUSTOM] as $blockId) {
            $parts = explode('/', $columns[$blockId]->getIndex());
            
            if ($parts[0] == $typeCode) {
                $codes[] = $parts[1];
            }
        }
        if ($includeTypeCode) {
            array_walk($codes, array($this, '_addTypeToCustomColumnCode'), $typeCode);
        }
        
        return $codes;
    }
    
    /**
     * Return available custom columns
     *
     * @param bool $grouped Whether columns should be arranged by group
     * @param bool $includeTypeCode Whether column codes should include the grid type code
     * @return array
     */
    public function getAvailableCustomColumns($grouped=false, $includeTypeCode=false)
    {
        $availableColumns = array();
        
        if ($typeModel = $this->getTypeModel()) {
            $customColumns = $typeModel->getCustomColumns($this->getBlockType(), $this->getRewritingClassName());
            $usedCodes = $this->getUsedCustomColumnsCodes();
            $typeCode  = $typeModel->getCode();
            
            if ($grouped) {
                foreach ($customColumns as $code => $customColumn) {
                    if (!isset($availableColumns[$customColumn->getGroupId()])) {
                        $availableColumns[$customColumn->getGroupId()] = array();
                    }
                    if (in_array($code, $usedCodes)) {
                        $customColumn->setSelected(true);
                    }
                    if ($includeTypeCode) {
                        $this->_addTypeToCustomColumnCode($code, $typeCode);
                    }
                    
                    $availableColumns[$customColumn->getGroupId()][$code] = $customColumn;
                }
            } elseif ($includeTypeCode) {
                foreach ($customColumns as $code => $customColumn) {
                    $this->_addTypeToCustomColumnCode($code, $typeCode);
                    $availableColumns[$code] = $customColumn;
                }
            } else {
                $availableColumns = $customColumns;
            }
        }
        
        return $availableColumns;
    }
    
    /**
     * Return available custom columns codes
     * 
     * @param bool $includeTypeCode Whether column codes should include the grid type code
     * @return array
     */
    public function getAvailableCustomColumnsCodes($includeTypeCode=false)
    {
        return array_keys($this->getAvailableCustomColumns(false, $includeTypeCode));
    }
    
    /**
     * Return custom column groups
     *
     * @param bool $onlyUsed Whether only groups which contain available custom columns should be returned
     * @return array
     */
    public function getCustomColumnsGroups($onlyUsed=true)
    {
        $groups = array();
        
        if ($typeModel = $this->getTypeModel()) {
            $groups = $typeModel->getCustomColumnsGroups();
            
            if ($onlyUsed) {
                $groupsIds = array();
                
                foreach ($this->getAvailableCustomColumns() as $column) {
                    $groupsIds[] = $column->getGroupId();
                }
                
                $groupsIds = array_unique($groupsIds);
                $groups = array_intersect_key($groups, array_flip($groupsIds));
            }
        }
        
        return $groups;
    }
    
    /**
     * Return next custom column block ID (auto-generated ones)
     *
     * @return string
     */
    protected function _getNextCustomColumnBlockId()
    {
        if (($maxId = $this->getMaxCustomColumnBaseBlockId()) > 0) {
            $baseBlockId = $maxId + 1;
        } else {
            $baseBlockId = 1;
        }
        $this->setMaxCustomColumnBaseBlockId($baseBlockId);
        return self::CUSTOM_COLUMN_ID_PREFIX . $baseBlockId;
    }
    
    /**
     * Update available custom columns
     *
     * @param array $columnsCodes New custom columns codes
     * @return this
     */
    public function updateCustomColumns(array $columnsCodes)
    {
        if (!$this->checkUserActionPermission(self::ACTION_CUSTOMIZE_COLUMNS)) {
            $this->_throwPermissionException();
        }
        
        if ($typeModel = $this->getTypeModel()) {
            $typeCode = $typeModel->getCode();
        } else {
            return $this;
        }
        
        $this->getColumns();
        $this->getColumnIdsByOrigin();
        $helper = $this->_getHelper();
        
        $availableColumns = $this->getAvailableCustomColumns();
        $availableCodes   = array_keys($availableColumns);
        
        $appliedCodes = $columnsCodes;
        $currentCodes = array();
        $removedBlockIds = array();
        
        foreach ($this->_data['column_ids_by_origin'][self::COLUMN_ORIGIN_CUSTOM] as $columnBlockId) {
            if (!is_null($typeCode)) {
                $parts = explode('/', $this->_data['columns'][$columnBlockId]->getIndex());
                
                if (($typeCode == $parts[0])
                    && in_array($parts[1], $appliedCodes)
                    && in_array($parts[1], $availableCodes)) {
                    $currentCodes[] = $parts[1];
                } else {
                    $removedBlockIds[] = $columnBlockId;
                }
            } else {
                $removedBlockIds[] = $columnBlockId;
            }
        }
        
        $newCodes = array_intersect($availableCodes, array_diff($appliedCodes, $currentCodes));
        $columnsGroups = $this->getCustomColumnsGroups();
        
        foreach ($newCodes as $code) {
            $newColumnBlockId = $this->_getNextCustomColumnBlockId();
            $columnModel = $availableColumns[$code];
            
            if (isset($columnsGroups[$columnModel->getGroupId()])
                && $this->_getConfigHelper()->getAddGroupToCustomColumnsDefaultHeader()) {
                $header = $helper->__('%s (%s)', $columnModel->getName(), $columnsGroups[$columnModel->getGroupId()]);
            } else {
                $header = $columnModel->getName();
            }
            
            $newColumn = array(
                'grid_id'              => $this->getId(),
                'block_id'             => $newColumnBlockId,
                'index'                => $typeCode . '/' . $code,
                'width'                => '',
                'align'                => self::COLUMN_ALIGNMENT_LEFT,
                'header'               => $header,
                'order'                => $this->_getNextColumnOrder(),
                'origin'               => self::COLUMN_ORIGIN_CUSTOM,
                'is_visible'           => true,
                'is_only_filterable'   => false,
                'is_system'            => false,
                'is_missing'           => false,
                'store_id'             => null,
                'renderer_type'        => null,
                'renderer_params'      => null,
                'is_edit_allowed'      => false,
                'customization_params' => null,
            );
            
            $this->_addColumn($newColumn);
        }
        
        foreach ($removedBlockIds as $columnBlockId) {
            unset($this->_data['columns'][$columnBlockId]);
        }
        
        $this->_recomputeColumnsMaxOrder();
        $this->setDataChanges(true);
        
        return $this;
    }
    
    /**
     * Return column header
     *
     * @param string $columnBlockId Column block ID
     * @return string|null
     */
    public function getColumnHeader($columnBlockId)
    {
        return ($column = $this->getColumnByBlockId($columnBlockId))
            ? $column->getHeader()
            : null;
    }
    
    /**
     * Return column locked values (ie that should not be user-defined)
     *
     * @param string $columnBlockId Column block ID
     * @return array
     */
    public function getColumnLockedValues($columnBlockId)
    {
        $values = array();
        
        if (($typeModel = $this->getTypeModel())
            && (($column = $this->getColumnByBlockId($columnBlockId)) && $column->isCollection())) {
            // @todo rewriting class name should be given more importance
            $values = $typeModel->getColumnLockedValues($this->getBlockType(), $columnBlockId);
        }
        
        return (is_array($values) ? $values : array());
    }
    
    /**
     * Check the given alignment value, return "left" alignment by default if the given one is unknown
     *
     * @param string $alignment Alignment value to check
     * @return string
     */
    protected function _getValidAlignment($alignment)
    {
        return array_key_exists($alignment, $this->getColumnAlignments())
            ? $alignment
            : self::COLUMN_ALIGNMENT_LEFT;
    }
    
    /**
     * Add a column to the list corresponding to the given column block instance
     *
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $columnBlock Column block instance
     * @param int $order Column order
     * @return this
     */
    protected function _addColumnFromBlock(Mage_Adminhtml_Block_Widget_Grid_Column $columnBlock, $order)
    {
        return $this->_addColumn(array(
            'block_id'             => $columnBlock->getId(),
            'index'                => $columnBlock->getIndex(),
            'width'                => $columnBlock->getWidth(),
            'align'                => $this->_getValidAlignment($columnBlock->getAlign()),
            'header'               => $columnBlock->getHeader(),
            'order'                => $order,
            'origin'               => self::COLUMN_ORIGIN_GRID,
            'is_visible'           => true,
            'is_only_filterable'   => false,
            'is_system'            => (bool) $columnBlock->getIsSystem(),
            'is_missing'           => false,
            'store_id'             => null,
            'renderer_type'        => null,
            'renderer_params'      => null,
            'is_edit_allowed'      => true,
            'customization_params' => null,
        ));
    }
    
    /**
     * Add a column to the list from collection row value
     *
     * @param string $key Row value key
     * @param int $order Column order
     * @return this
     */
    protected function _addColumnFromCollection($key, $order)
    {
        return $this->_addColumn(array(
            'block_id'             => $key,
            'index'                => $key,
            'width'                => '',
            'align'                => self::COLUMN_ALIGNMENT_LEFT,
            'header'               => $this->_getHelper()->getColumnHeaderName($key),
            'order'                => $order,
            'origin'               => self::COLUMN_ORIGIN_COLLECTION,
            'is_visible'           => false,
            'is_only_filterable'   => false,
            'is_system'            => false,
            'is_missing'           => false,
            'store_id'             => null,
            'renderer_type'        => null,
            'renderer_params'      => null,
            'is_edit_allowed'      => true,
            'customization_params' => null,
        ));
    }
    
    /**
     * Return whether given block type and block ID correspond to this grid
     *
     * @param string $blockType Block type
     * @param string $blockId Block ID in layout
     * @return bool
     */
    public function matchGridBlock($blockType, $blockId)
    {
        return (($typeModel = $this->getTypeModel()) && $typeModel->matchGridBlock($blockType, $blockId, $this));
    }
    
    /**
     * Init values from grid block instance
     *
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return this
     */
    public function initWithGridBlock(Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        // Reset / Initialization
        $this->setBlockId($gridBlock->getId());
        $this->setBlockType($gridBlock->getType());
        $this->_resetColumnsValues();
        $this->getColumnIdsByOrigin();
        $this->_setVarNamesFromBlock($gridBlock);
        
        $order = 0;
        $gridIndices = array();
        
        foreach ($gridBlock->getColumns() as $columnBlock) {
            // Take all columns from grid
            $order++;
            $this->_addColumnFromBlock($columnBlock, $order * $this->getColumnsOrderPitch(), self::COLUMN_ORIGIN_GRID);
            $gridIndices[] = $columnBlock->getIndex();
        }
        
        if ($gridBlock->getCollection() && ($gridBlock->getCollection()->count() > 0)) {
            // Initialize collection columns if possible
            $item = $gridBlock->getCollection()->getFirstItem();
            
            foreach ($item->getData() as $key => $value) {
                if (!in_array($key, $gridIndices, true) 
                    && !in_array($key, $this->_data['column_ids_by_origin'][self::COLUMN_ORIGIN_GRID], true)
                    && (is_scalar($value) || is_null($value))) {
                    $order++;
                    $this->_addColumnFromCollection($key, $order * $this->getColumnsOrderPitch());
                }
            }
        }
        
        return $this;
    }
    
    /**
     * Check values (columns, etc.) against grid block instance, and save up-to-date values
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return bool Whether collection columns have been checked (if false, using them could be "dangerous")
     */
    public function checkColumnsAgainstGridBlock(Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        $foundGridIds = array();
        $gridIndices  = array();
        $this->getColumns();
        $this->getColumnIdsByOrigin();
        $this->_setVarNamesFromBlock($gridBlock);
        
        // Grid columns
        foreach ($gridBlock->getColumns() as $columnBlock) {
            $columnBlockId = $columnBlock->getId();
            
            if (isset($this->_data['columns'][$columnBlockId])) {
                $previousOrigin = $this->_data['columns'][$columnBlockId]->getOrigin();
                
                $this->_data['columns'][$columnBlockId]->addData(array(
                    'block_id'   => $columnBlockId,
                    'index'      => $columnBlock->getIndex(),
                    'origin'     => self::COLUMN_ORIGIN_GRID,
                    'is_system'  => (bool) $columnBlock->getIsSystem(),
                    'is_missing' => false,
                ));
                
                if ($previousOrigin != self::COLUMN_ORIGIN_GRID) {
                    $previousKey = array_search($columnBlockId, $this->_data['column_ids_by_origin'][$previousOrigin]);
                    unset($this->_data['column_ids_by_origin'][$previousOrigin][$previousKey]);
                    $this->_data['column_ids_by_origin'][self::COLUMN_ORIGIN_GRID][] = $columnBlockId;
                }
            } else {
                $this->_addColumnFromBlock($columnBlock, $this->_getNextColumnOrder());
            }
            
            $gridIndices[]  = $columnBlock->getIndex();
            $foundGridIds[] = $columnBlockId;
        }
        
        $foundCollectionIds = array();
        $checkedCollection  = false;
        
        // Collection columns
        if ($gridBlock->getCollection() && ($gridBlock->getCollection()->count() > 0)) {
            $item = $gridBlock->getCollection()->getFirstItem();
            $checkedCollection = true;
            
            foreach ($item->getData() as $key => $value) {
                if (is_scalar($value) || is_null($value)) {
                    if (isset($this->_data['columns'][$key])) {
                        $previousOrigin = $this->_data['columns'][$key]->getOrigin();
                        
                        if (!in_array($key, $foundGridIds, true)) {
                            if (!in_array($key, $gridIndices, true)) {
                                $this->_data['columns'][$key]->addData(array(
                                    'block_id'   => $key,
                                    'index'      => $key,
                                    'origin'     => self::COLUMN_ORIGIN_COLLECTION,
                                    'is_system'  => false,
                                    'is_missing' => false,
                                ));
                                
                                if ($previousOrigin != self::COLUMN_ORIGIN_COLLECTION) {
                                    $previousKey = array_search(
                                        $key,
                                        $this->_data['column_ids_by_origin'][$previousOrigin]
                                    );
                                    unset($this->_data['column_ids_by_origin'][$previousOrigin][$previousKey]);
                                    $this->_data['column_ids_by_origin'][self::COLUMN_ORIGIN_COLLECTION][] = $key;
                                }
                                
                                $foundCollectionIds[] = $key;
                            } else {
                                unset($this->_data['columns'][$key]);
                            }
                        }
                    } elseif (!in_array($key, $foundGridIds, true) && !in_array($key, $gridIndices, true)) {
                        $this->_addColumnFromCollection($key, $this->_getNextColumnOrder());
                        $foundCollectionIds[] = $key;
                    }
                }
            }
        }
        
        // Attribute columns
        $foundAttributesIds = array();
        
        if ($this->canHaveAttributeColumns()) {
            $columnsBlockIds = $this->_data['column_ids_by_origin'][self::COLUMN_ORIGIN_ATTRIBUTE];
            $attributes = $this->getAvailableAttributesCodes();
            
            foreach ($columnsBlockIds as $columnBlockId) {
                // Verify attributes existences
                if (in_array($this->_data['columns'][$columnBlockId]->getIndex(), $attributes, true)) {
                    $this->_data['columns'][$columnBlockId]->setIsMissing(false);
                    $foundAttributesIds[] = $columnBlockId;
                }
            }
        }
        
        // Custom columns
        $foundCustomIds = array();
        
        if ($this->canHaveCustomColumns()) {
            $columnsBlockIds = $this->_data['column_ids_by_origin'][self::COLUMN_ORIGIN_CUSTOM];
            $availableCodes  = $this->getAvailableCustomColumnsCodes(true);
            
            foreach ($columnsBlockIds as $columnBlockId) {
                // Verify custom columns existence / match
                if (in_array($this->_data['columns'][$columnBlockId]->getIndex(), $availableCodes, true)) {
                    $this->_data['columns'][$columnBlockId]->setIsMissing(false);
                    $foundCustomIds[] = $columnBlockId;
                }
            }
        }
        
        // Mark found to be missing columns as such
        $foundIds = array_merge($foundGridIds, $foundCollectionIds, $foundAttributesIds, $foundCustomIds);
        $missingIds = array_diff(array_keys($this->_data['columns']), $foundIds);
        
        foreach ($missingIds as $missingId) {
            if ($checkedCollection
                || !$this->_data['columns'][$missingId]->isCollection()) {
                $this->_data['columns'][$missingId]->setIsMissing(true);
            }
        }
        
        $this->setDataChanges(true)->save();
        return $checkedCollection;
    }
    
    /**
     * Return whether grid results can be exported
     *
     * @return bool
     */
    public function canExport()
    {
        return (($typeModel = $this->getTypeModel()) && $typeModel->canExport($this->getBlockType()));
    }
    
    /**
     * Return available export types
     *
     * @return array
     */
    public function getExportTypes()
    {
        return ($typeModel = $this->getTypeModel())
            ? $typeModel->getExportTypes($this->getBlockType())
            : array();
    }
    
    /**
     * @todo 
     * Turn _exportFile() into public exportFile()
     * Remove all explicit calls such as export[Format]File() from here and custom grid controller (make it give format)
     * Do export in grid type model, so new export types could "simply" be added
     * Add CSV / XML export to abstract grid type model
     */
    
    /**
     * Return whether current request corresponds to an export request for the active grid
     *
     * @param Mage_Core_Controller_Request_Http $request Request object
     * @return bool
     */
    public function isExportRequest(Mage_Core_Controller_Request_Http $request)
    {
        return (($typeModel = $this->getTypeModel()) && $typeModel->isExportRequest($this->getBlockType(), $request));
    }
    
    /**
     * Export grid data in given format
     *
     * @param string $format Export format
     * @param array|null $config Export configuration
     * @return mixed
     */
    protected function _exportFile($format, $config=null)
    {
        if (!$this->checkUserActionPermission(self::ACTION_EXPORT_RESULTS)) {
            $this->_throwPermissionException();
        }
        if ($typeModel = $this->getTypeModel()) {
            $typeModel->beforeGridExport($format, null);
        }
        
        $gridBlock = Mage::getSingleton('core/layout')->createBlock($this->getBlockType());
        $exportOutput = '';
        
        if (!is_null($config)) {
            $gridBlock->blcg_setExportConfig($config);
        }
        if ($typeModel) {
            $typeModel->beforeGridExport($format, $gridBlock);
        }
        
        switch ($format) {
            case 'csv':
                $exportOutput = $gridBlock->getCsvFile();
                break;
            case 'xml':
                $exportOutput = $gridBlock->getExcelFile();
                break;
            default:
                $exportOutput = null;
                break;
        }
        
        if ($typeModel) {
            $typeModel->afterGridExport($format, $gridBlock);
        }
        
        return $exportOutput;
    }
    
    /**
     * Export grid data in CSV format
     *
     * @param array|null $config Export configuration
     * @return mixed
     */
    public function exportCsvFile($config=null)
    {
        return $this->_exportFile('csv', $config);
    }
    
    /**
     * Export grid data in XML Excel format
     *
     * @param array|null $config Export configuration
     * @return mixed
     */
    public function exportExcelFile($config=null)
    {
        return $this->_exportFile('xml', $config);
    }
    
    /**
     * Return whether the grid has editable columns
     *
     * @return bool
     */
    public function hasEditableColumns()
    {
        $hasEditableColumns = false;
        
        if ($typeModel = $this->getTypeModel()) {
            $editableValues = $typeModel->getEditableAttributes($this->getBlockType());
            
            if (!empty($editableValues)) {
                $hasEditableColumns = true;
            } else {
                $editableValues = array_merge(
                    $typeModel->getEditableFields($this->getBlockType()),
                    $typeModel->getEditableAttributeFields($this->getBlockType())
                );
                
                if (!empty($editableValues)) {
                    $columns = $this->getSortedColumns(true, true, false, false);
                    $editableValues = array_intersect_key($columns, $editableValues);
                    $hasEditableColumns = !empty($editableValues);
                }
            }
        }
        
        return $hasEditableColumns;
    }
    
    /**
     * Return whether the current user has edit permissions over the grid columns
     *
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return bool
     */
    public function hasUserEditPermissions(Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        return ($typeModel = $this->getTypeModel())
            ? $typeModel->checkUserEditPermissions($this->getBlockType(), $this, $gridBlock)
            : $this->checkUserActionPermission(self::ACTION_EDIT_COLUMNS_VALUES);
    }
    
    /**
     * Return additional parameters needed for edit, corresponding to given grid block
     *
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return array
     */
    public function getAdditionalEditParams(Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        return ($typeModel = $this->getTypeModel())
            ? $typeModel->getAdditionalEditParams($this->getBlockType(), $gridBlock)
            : array();
    }
    
    /**
     * Return grid collection row identifiers
     *
     * @param Varien_Object $row Grid row
     * @return array
     */
    public function getCollectionRowIdentifiers(Varien_Object $row)
    {
        return ($typeModel = $this->getTypeModel())
            ? $typeModel->getEntityRowIdentifiers($this->getBlockType(), $row)
            : array();
    }
    
    /**
     * Encode filters array
     *
     * @param array $filters Filters values
     * @return string
     */
    public function encodeGridFiltersArray(array $filters)
    {
        return base64_encode(http_build_query($filters));
    }
    
    /**
     * Decode filters string
     *
     * @param string $filters Encoded filters string
     * @return array
     */
    public function decodeGridFiltersString($filters)
    {
        return (is_string($filters) ? Mage::helper('adminhtml')->prepareFilterString($filters) : $filters);
    }
    
    /**
     * Compare grid filter values
     *
     * @param mixed $a One filter value
     * @param mixed $b Another filter value
     * @return bool Whether given values are equal
     */
    public function compareGridFilterValues($a, $b)
    {
        if (is_array($a) && is_array($b)) {
            ksort($a);
            ksort($b);
            $a = $this->encodeGridFiltersArray($a);
            $b = $this->encodeGridFiltersArray($b);
            return ($a == $b);
        }
        return ($a === $b);
    }
    
    /**
     * Return filters token session key
     *
     * @return string
     */
    protected function _getFiltersTokenSessionKey()
    {
        return self::SESSION_BASE_KEY_TOKEN . $this->getId();
    }
    
    /**
     * Return applied filters session key
     *
     * @param int|null $profileId ID of the profile under which the filters are applied
     * @return string
     */
    protected function _getAppliedFiltersSessionKey($profileId=null)
    {
        if (is_null($profileId)) {
            $profileId = $this->getProfileId();
        }
        return self::SESSION_BASE_KEY_APPLIED_FILTERS . $this->getId() . '_' . $profileId;
    }
    
    /**
     * Return removed filters session key
     *
     * @param int|null $profileId ID of the profile under which the filters are removed
     * @return string
     */
    protected function _getRemovedFiltersSessionKey($profileId=null)
    {
        if (is_null($profileId)) {
            $profileId = $this->getProfileId();
        }
        return self::SESSION_BASE_KEY_REMOVED_FILTERS . $this->getId() . '_' . $profileId;
    }
    
    /**
     * Verify validities of filters applied to given grid block,
     * and return safely appliable filters
     * Mostly used for collection and custom columns, which may have their renderer changed at any time
     * (and the new renderers may crash when given unexpected kind of filter values)
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param array $filters Applied filters
     * @return array
     */
    public function verifyGridBlockFilters(Mage_Adminhtml_Block_Widget_Grid $gridBlock, array $filters)
    {
        // Get previous filtering informations from session
        $session = Mage::getSingleton('adminhtml/session');
        $tokenSessionKey   = $this->_getFiltersTokenSessionKey();
        $appliedSessionKey = $this->_getAppliedFiltersSessionKey();
        $removedSessionKey = $this->_getRemovedFiltersSessionKey();
        
        if (!is_array($sessionAppliedFilters = $session->getData($appliedSessionKey))) {
            $sessionAppliedFilters = array();
        }
        if (!is_array($sessionRemovedFilters = $session->getData($removedSessionKey))) {
            $sessionRemovedFilters = array();
        }
        
        $foundFilterBlocksIds = array();
        $removedFilterBlocksIds = array();
        $attributesRenderers = $this->getAvailableAttributesRendererTypes();
        
        /*
        Verify grid tokens, if request one does not correspond to session one,
        then it is almost sure that we currently come from anywhere but from an acual grid action
        (such as search, sort, export, pagination, ...)
        May be too restrictive, but at the moment, rather be too restrictive than not enough
        */
        if ($gridBlock->getRequest()->has(self::GRID_TOKEN_PARAM_NAME)
            && $session->hasData($tokenSessionKey)) {
            $requestValue = $gridBlock->getRequest()->getParam(self::GRID_TOKEN_PARAM_NAME, null);
            $sessionValue = $session->getData($tokenSessionKey);
            $isGridAction = ($requestValue == $sessionValue);
        } else {
            $isGridAction = false;
        }  
        
        $columns = $this->getColumns(false, true);
        $typeModel = Mage::getSingleton('customgrid/grid_type_config');
        
        foreach ($filters as $columnBlockId => $data) {
            if (isset($columns[$columnBlockId])) {
                $column = $columns[$columnBlockId];
                $columnIndex = $column->getIndex();
                
                if (isset($sessionAppliedFilters[$columnBlockId])) {
                    /*
                    Check if the column has changed significantly since the filter was applied
                    (it may have became incompatible)
                    If so, unvalidate the corresponding filter data for the current and upcoming requests,
                    (until a new filter is applied on the same column)
                    */
                    $hasColumnChanged = false;
                    $sessionFilter = $sessionAppliedFilters[$columnBlockId];
                    
                    if ($sessionFilter['origin'] != $column->getOrigin()) {
                        $hasColumnChanged = true;
                    } elseif ($column->isCollection()) {
                        $hasColumnChanged = ($sessionFilter['renderer_type'] != $column->getRendererType());
                    } elseif ($column->isAttribute()) {
                        $previousIndex = $sessionFilter['index'];
                        
                        if (isset($attributesRenderers[$previousIndex])) {
                            $previousRenderer = $attributesRenderers[$previousIndex];
                            $columnRenderer   = $attributesRenderers[$columnIndex];
                            $hasColumnChanged = ($previousRenderer != $columnRenderer);
                        } else {
                            $hasColumnChanged = true;
                        }
                    } elseif ($column->isCustom()) {
                        $previousIndex = $sessionFilter['index'];
                        
                        $rendererTypes = array(
                            'previous' => $sessionFilter['renderer_type'],
                            'current'  => $column->getRendererType(),
                        );
                        $customizationParams = array(
                            'previous' => $typeModel->decodeParameters($sessionFilter['customization_params'], true),
                            'current'  => $typeModel->decodeParameters($column->getCustomizationParams(), true),
                        );
                        
                        if (($previousIndex != $columnIndex)
                            || (!$customColumn = $column->getCustomColumnModel())) {
                            $hasColumnChanged = true;
                        } else {
                            $hasColumnChanged = $customColumn->shouldInvalidateFilters(
                                $this,
                                $column,
                                $customizationParams,
                                $rendererTypes
                            );
                        }
                    }
                    
                    if ($hasColumnChanged) {
                        unset($filters[$columnBlockId]);
                        unset($sessionAppliedFilters[$columnBlockId]);
                        $sessionRemovedFilters[$columnBlockId] = $data;
                        $removedFilterBlocksIds[] = $columnBlockId;
                    }
                } elseif (isset($sessionRemovedFilters[$columnBlockId]) && !$isGridAction) {
                    if ($this->compareGridFilterValues($sessionRemovedFilters[$columnBlockId], $data)) {
                        // The same filter was invalidated before, remove it again
                        unset($filters[$columnBlockId]);
                    }
                } else {
                    $sessionAppliedFilters[$columnBlockId] = array(
                        'index'  => $column->getIndex(),
                        'origin' => $column->getOrigin(),
                        'renderer_type' => $column->getRendererType(),
                        'customization_params' => $column->getCustomizationParams(),
                    );
                }
                
                $foundFilterBlocksIds[] = $columnBlockId;
            } else {
                // Unexisting column : unneeded filter
                unset($filters[$columnBlockId]);
            }
        }
        
        /**
         * Note : adding new parameters to the request object will make them be added to, eg,
         * URLs retrieved later with the use of the current values
         * (eg. with Mage::getUrl('module/controller/action', array('_current' => true)))
         */
        
        /*
        Add our token to current request and session
        Use ":" in hash to force Varien_Db_Adapter_Pdo_Mysql::query() using a bind param instead of full request path,
        (as it uses this condition : strpos($sql, ':') !== false),
        when querying core_url_rewrite table, else the query could be too long, 
        making Zend_Db_Statement::_stripQuoted() sometimes crash on one of its call to preg_replace()
        */
        $tokenValue = Mage::helper('core')->uniqHash('blcg:');
        $gridBlock->getRequest()->setParam(self::GRID_TOKEN_PARAM_NAME, $tokenValue);
        $session->setData($tokenSessionKey, $tokenValue);
        
        // Remove obsolete filters and save up-to-date filters array to session
        $obsoleteFilterBlocksIds = array_diff(array_keys($sessionAppliedFilters), $foundFilterBlocksIds);
        
        foreach ($obsoleteFilterBlocksIds as $columnBlockId) {
            unset($sessionAppliedFilters[$columnBlockId]);
        }
        
        $session->setData($appliedSessionKey, $sessionAppliedFilters);
        
        if ($isGridAction) {
            /*
            Apply newly removed filters only when a grid action is done
            The only remaining potential source of "maybe wrong" filters could come from  the use of an old URL with
            obsolete filter(s) in it (eg from browser history), but there is no way at the moment to detect them
            (at least not any simple one with only few impacts)
            */
            $session->setData($removedSessionKey, array_intersect_key($sessionRemovedFilters, $removedFilterBlocksIds));
        } else {
            $session->setData($removedSessionKey, $sessionRemovedFilters);
        }
        
        $filterParam = $this->encodeGridFiltersArray($filters);
        
        if ($gridBlock->blcg_getSaveParametersInSession()) {
            $session->setData($gridBlock->blcg_getSessionParamKey($gridBlock->getVarNameFilter()), $filterParam);
        }
        if ($gridBlock->getRequest()->has($gridBlock->getVarNameFilter())) {
            $gridBlock->getRequest()->setParam($gridBlock->getVarNameFilter(), $filterParam);
        }
        
        $gridBlock->blcg_setFilterParam($filterParam);
        return $filters;
    }
    
    /**
    * Return the value of "default_filter", stripped of all the obsolete or invalid values
    * 
    * @return null|array
    */
    public function getAppliableDefaultFilter()
    {
        if (!$this->hasData('appliable_default_filter')) {
            $appliableDefaultFilter = null;
            
            if (($filters = $this->_getData('default_filter')) && is_array($filters = @unserialize($filters))) {
                $columns = $this->getColumns(false, true);
                $appliableDefaultFilter = array();
                $attributesRenderers = $this->getAvailableAttributesRendererTypes();
                
                foreach ($filters as $columnBlockId => $filter) {
                    if (isset($columns[$columnBlockId])) {
                        $column = $columns[$columnBlockId];
                        $columnIndex = $column->getIndex();
                        
                        // Basically, those are the same verifications than the ones used in verifyGridBlockFilters()
                        if ($filter['column']['origin'] != $column->getOrigin()) {
                            continue;
                        }
                        
                        $isValidFilter = true;
                        $previousRendererType = $filter['column']['renderer_type'];
                        $previousCustomizationParams = $filter['column']['customization_params'];
                        
                        if ($column->isCollection()) {
                            $isValidFilter = ($previousRendererType == $column->getRendererType());
                        } elseif ($column->isAttribute()) {
                            $previousIndex = $filter['column']['index'];
                            
                            if (isset($attributesRenderers[$previousIndex])) {
                                $previousRenderer = $attributesRenderers[$previousIndex];
                                $columnRenderer = $attributesRenderers[$columnIndex];
                                $isValidFilter  = ($previousRenderer == $columnRenderer);
                            } else {
                                $isValidFilter = false;
                            }
                        } elseif ($column->isCustom()) {
                            $previousIndex = $filter['column']['index'];
                            $typeModel = Mage::getSingleton('customgrid/grid_type_config');
                            
                            $rendererTypes = array(
                                'previous' => $previousRendererType,
                                'current'  => $column->getRendererType(),
                            );
                            $customizationParams = array(
                                'previous' => $typeModel->decodeParameters($previousCustomizationParams, true),
                                'current'  => $typeModel->decodeParameters($column->getCustomizationParams(), true),
                            );
                            
                            if (($previousIndex != $columnIndex)
                                || (!$customColumn = $column->getCustomColumnModel())) {
                                $isValidFilter = false;
                            } else {
                                $isValidFilter = !$customColumn->shouldInvalidateFilters(
                                    $this,
                                    $column,
                                    $customizationParams,
                                    $rendererTypes
                                );
                            }
                        }
                        
                        if ($isValidFilter) {
                            $appliableDefaultFilter[$columnBlockId] = $filter['value'];
                        }
                        
                        /*
                        @todo (see method below) when it is consistent with the current behaviour chosen for default
                        filters, use a flag telling that obsolete filters should be kept (and associated to false, eg),
                        in order to at least remove the corresponding original ones when they exist, and then have no
                        default value at all ? (instead of letting the original ones be used)
                        */
                    }
                }
            }
            
            $this->setData('appliable_default_filter', $appliableDefaultFilter);
        }
        return $this->_getData('appliable_default_filter');
    }
    
    /**
     * Return appliable default parameter value depending on the available values and the defined behaviour
     *
     * @param string $type Parameter type (eg "limit" or "filter")
     * @param mixed $blockValue Base value
     * @param mixed $customValue User-defined value
     * @param bool $fromCustomSetter Whether this function is called from a setter applying user-defined values
     * @param mixed $originalValue Current value (to be replaced)
     * @return mixed
     */
    public function getGridBlockDefaultParamValue($type, $blockValue, $customValue=null, $fromCustomSetter=false,
        $originalValue=null)
    {
        // @todo review this code (seems to be working correctly, but what should actually be the correct way ?)
        // @todo in the meantime, greatly improve corresponding hints / descriptions, make it as intuitive as possible
        $value = $blockValue;
        
        if (!$fromCustomSetter) {
            if ($type == 'filter') {
                $customValue = $this->getAppliableDefaultFilter();
           } else {
               $customValue = $this->getData('default_' . $type);
           }
        }
        
        if (!$behaviour = $this->_getData('default_' . $type . '_behaviour')) {
            $behaviour = $this->_getConfigHelper()->geDefaultParameterBehaviour($type);
        }
        if ($behaviour == self::DEFAULT_PARAM_FORCE_CUSTOM) {
            if (!is_null($customValue)) {
                $value = $customValue;
            }
        } elseif ($behaviour == self::DEFAULT_PARAM_FORCE_ORIGINAL) {
            if (is_null($blockValue) && $fromCustomSetter) {
                $value = $blockValue;
            }
        } elseif (($type == 'filter')
                  && (($behaviour == self::DEFAULT_PARAM_MERGE_DEFAULT)
                      || ($behaviour == self::DEFAULT_PARAM_MERGE_BASE_CUSTOM)
                      || ($behaviour == self::DEFAULT_PARAM_MERGE_BASE_ORIGINAL))) {
            $blockFilters  = (is_array($blockValue)  ? $blockValue  : array());
            $customFilters = (is_array($customValue) ? $customValue : array());
            
            if ($behaviour == self::DEFAULT_PARAM_MERGE_BASE_CUSTOM) {
                $value = array_merge($customFilters, $blockFilters);
            } elseif ($behaviour == self::DEFAULT_PARAM_MERGE_BASE_ORIGINAL) {
                $value = array_merge($blockFilters, $customFilters);
            } elseif ($fromCustomSetter) {
                $value = array_merge($blockFilters, $customFilters);
            } else {
                $value = array_merge($customFilters, $blockFilters);
            }
        } else {
            if (!is_null($customValue) && $fromCustomSetter) {
                $value = $customValue;
            }
        }
        
        if ($type == 'limit') {
            if (!in_array($value, $this->getAppliablePaginationValues())) {
                $value = (is_null($originalValue) ? $blockValue : $originalValue);
            }
        }
        
        return $value;
    }
    
    /**
     * Apply base default limit to the given grid block instance (possibly based on custom pagination values)
     *
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return this
     */
    public function applyBaseDefaultLimitToGridBlock(Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        $customLimit  = $this->getDefaultPaginationValue();
        $blockLimit   = $gridBlock->getDefaultLimit();
        $defaultLimit = null;
        $values = $this->getAppliablePaginationValues();
        
        if (!empty($customLimit) && in_array($customLimit, $values)) {
            $defaultLimit = $customLimit;
        } elseif (!empty($blockLimit) && in_array($blockLimit, $values)) {
            $defaultLimit = $blockLimit;
        } else {
            $defaultLimit = array_shift($values);
        }
        
        $gridBlock->blcg_setDefaultLimit($defaultLimit, true);
        return $this;
    }
    
    /**
     * Apply default parameters to given grid block instance
     *
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return this
     */
    public function applyDefaultsToGridBlock(Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        if ($defaultValue = $this->_getData('default_page')) {
            $gridBlock->blcg_setDefaultPage($defaultValue);
        }
        if ($defaultValue = $this->_getData('default_limit')) {
            $gridBlock->blcg_setDefaultLimit($defaultValue);
        }
        if ($defaultValue = $this->_getData('default_sort')) {
            $gridBlock->blcg_setDefaultSort($defaultValue);
        }
        if ($defaultValue = $this->_getData('default_dir')) {
            $gridBlock->blcg_setDefaultDir($defaultValue);
        }
        if (is_array($defaultValue = $this->getAppliableDefaultFilter())) {
            $gridBlock->blcg_setDefaultFilter($defaultValue);
        }
        return $this;
    }
    
    /**
     * Update default parameters
     *
     * @param array $appliable Appliable values
     * @param array $removable Removable values
     * @return this
     */
    public function updateDefaultParameters(array $appliable, array $removable)
    {
        if (!$this->checkUserActionPermission(self::ACTION_EDIT_DEFAULT_PARAMS)) {
            $this->_throwPermissionException();
        }
        
        $this->getColumns();
        
        if (isset($appliable['page'])) {
            $this->setData('default_page', (int) $appliable['page']);
        }
        if (isset($appliable['limit'])) {
            $this->setData('default_limit', (int) $appliable['limit']);
        }
        if (isset($appliable['sort'])) {
            if (isset($this->_data['columns'][$appliable['sort']])) {
                $this->setData('default_sort', $appliable['sort']);
            } else {
                $this->setData('default_sort', null);
            }
        }
        if (isset($appliable['dir'])) {
            if (($appliable['dir'] == 'asc') || ($appliable['dir'] == 'desc')) {
                $this->setData('default_dir', $appliable['dir']);
            } else {
                $this->setData('default_dir', null);
            }
        }
        if (isset($appliable['filter'])) {
            $filters = $appliable['filter'];
            
            if (!is_array($filters)) {
                $filters = $this->decodeGridFiltersString($filters);
            }
            if (is_array($filters) && !empty($filters)) {
                // Add some informations from current column values to filters, to later be able to check their validity
                $columns = $this->getColumns();
                $attributesRenderers = $this->getAvailableAttributesRendererTypes();
                
                foreach ($filters as $columnBlockId => $value) {
                    if (isset($columns[$columnBlockId])) {
                        $column = $columns[$columnBlockId];
                        
                        if ($column->isCollection()) {
                            $rendererType = $column->getRendererType();
                        } elseif ($column->isAttribute()) {
                            $rendererType = $attributesRenderers[$column->getIndex()];
                        } elseif ($column->isCustom()) {
                            $rendererType = $column->getRendererType();
                        } else {
                            $rendererType = null;
                        }
                        
                        $filters[$columnBlockId] = array(
                            'value'  => $value,
                            'column' => array(
                                'origin' => $column->getOrigin(),
                                'index'  => $column->getIndex(),
                                'renderer_type' => $rendererType,
                                'customization_params' => $column->getCustomizationParams(),
                            ),
                        );
                    } else {
                        unset($filters[$columnBlockId]);
                    }
                }
                
                $this->setData('default_filter', serialize($filters));
            } else {
                $this->setData('default_filter', null);
            }
        }
        
        if (is_array($removable)) {
            $keys = array('page', 'limit', 'sort', 'dir', 'filter');
            
            foreach ($keys as $key) {
                if (isset($removable[$key]) && $removable[$key]) {
                    $this->setData('default_' . $key, null);
                }
            }
        }
        
        $this->setDataChanges(true);
        return $this;
    }
    
    /**
     * Update default parameters behaviours
     *
     * @param array $behaviours New behaviours
     * @return this
     */
    public function updateDefaultParametersBehaviours(array $behaviours)
    {
        if (!$this->checkUserActionPermission(self::ACTION_EDIT_DEFAULT_PARAMS_BEHAVIOURS)) {
            $this->_throwPermissionException();
        }
        
        $keys = array(
            'page'   => false,
            'limit'  => false,
            'sort'   => false,
            'dir'    => false,
            'filter' => true,
        );
        $scalarValues = array(
            self::DEFAULT_PARAM_DEFAULT,
            self::DEFAULT_PARAM_FORCE_CUSTOM,
            self::DEFAULT_PARAM_FORCE_ORIGINAL,
        );
        $arrayValues = array(
            self::DEFAULT_PARAM_MERGE_DEFAULT,
            self::DEFAULT_PARAM_MERGE_BASE_CUSTOM,
            self::DEFAULT_PARAM_MERGE_BASE_ORIGINAL,
        );
        
        foreach ($keys as $key => $isArray) {
            if (isset($behaviours[$key])) {
                $value = null;
                
                if (in_array($behaviours[$key], $scalarValues)
                    || ($isArray && in_array($behaviours[$key], $arrayValues))) {
                    $value = $behaviours[$key];
                }
                
                $this->setData('default_' . $key . '_behaviour', $value);
            }
        }
        
        $this->setDataChanges(true);
        return $this;
    }
    
    /**
     * Set users config
     *
     * @param array $usersConfig Users config
     * @return this
     */
    public function setUsersConfig(array $usersConfig)
    {
        $this->_resetUsersConfigValues();
        
        foreach ($usersConfig as $key => $userConfig) {
            if (is_array($userConfig)) {
                $userConfig = new BL_CustomGrid_Object($userConfig);
                $usersConfig[$key] = $userConfig;
            }
            if (!is_object($userConfig)) {
                unset($usersConfig[$key]);
                continue;
            }
            if (!is_null($defaultProfileId = $userConfig->getData('default_profile_id'))) {
                $userConfig->setData('default_profile_id', (int) $defaultProfileId);
            }
        }
        
        return $this->setData('users_config', $usersConfig);
    }
    
    /**
     * Return users config
     *
     * @return array
     */
    protected function _getUsersConfig()
    {
        if (!$this->hasData('users_config')) {
            $usersConfig = (($id = $this->getId()) ? $this->_getResource()->getGridUsers($id) : array());
            $this->setUsersConfig($usersConfig);
        }
        return $this->_getData('users_config');
    }
    
    /**
     * Return possibly filtered users config
     *
     * @param int|null $userId User ID on which to filter, if not set the whole users config will be returned
     * @return array
     */
    public function getUsersConfig($userId=null)
    {
        $usersConfig = $this->_getUsersConfig();
        return !is_null($userId)
            ? (isset($usersConfig[$userId]) ? $usersConfig[$userId] : null)
            : $usersConfig;
    }
    
    /**
     * Set roles config
     *
     * @param array $rolesConfig Roles config
     * @return this
     */
    public function setRolesConfig(array $rolesConfig)
    {
        $this->_resetRolesConfigValues();
        
        foreach ($rolesConfig as $key => $roleConfig) {
             if (is_array($roleConfig)) {
                $roleConfig = new BL_CustomGrid_Object($roleConfig);
                $rolesConfig[$key] = $roleConfig;
            }
            if (!is_object($roleConfig)) {
                unset($rolesConfig[$key]);
                continue;
            }
            if (!is_array($permissions = $roleConfig->getData('permissions'))) {
                $permissions = array();
            }
            if (!is_null($defaultProfileId = $roleConfig->getData('default_profile_id'))) {
                $defaultProfileId = (int) $defaultProfileId;
            }
            if (!is_array($assignedProfilesIds = $roleConfig->getData('assigned_profiles_ids'))) {
                $assignedProfilesIds = array();
            }
            
            $roleConfig->addData(array(
                'permissions' => $permissions,
                'default_profile_id' => $defaultProfileId,
                'assigned_profiles_ids' => array_map('intval', $assignedProfilesIds),
            ));
        }
        
        return $this->setData('roles_config', $rolesConfig);
    }
    
    /**
     * Return roles config
     *
     * @return array
     */
    protected function _getRolesConfig()
    {
        if (!$this->hasData('roles_config')) {
            $rolesConfig = (($id = $this->getId()) ? $this->_getResource()->getGridRoles($id) : array());
            $this->setRolesConfig($rolesConfig);
        }
        return $this->_getData('roles_config');
    }
    
    /**
     * Return possibly filtered roles config
     *
     * @param int $roleId Role ID on which to filter, if not set the whole roles config will be returned
     * @return mixed
     */
    public function getRolesConfig($roleId=null)
    {
        $rolesConfig = $this->_getRolesConfig();
        return !is_null($roleId)
            ? (isset($rolesConfig[$roleId]) ? $rolesConfig[$roleId] : null)
            : $rolesConfig;
    }
    
    /**
     * Return given role's permissions
     *
     * @param int $roleId Role ID
     * @param mixed $default Default value to return if there is no permissions for the given role ID
     * @return mixed
     */
    public function getRolePermissions($roleId, $default=array())
    {
        return ($roleConfig = $this->getRolesConfig($roleId))
            ? $roleConfig->getDataSetDefault('permissions', array())
            : $default;
    }
    
    /**
     * Update grid roles permissions
     *
     * @param array $permissions Roles permissions
     * @return this
     */
    public function updateRolesPermissions(array $permissions)
    {
        if (!$this->checkUserActionPermission(self::ACTION_EDIT_ROLES_PERMISSIONS)) {
            $this->_throwPermissionException();
        }
        
        $flags = array(
            self::ACTION_PERMISSION_USE_CONFIG,
            self::ACTION_PERMISSION_YES,
            self::ACTION_PERMISSION_NO,
        );
        
        $actions = self::getGridActions();
        $rolesConfig = $this->getRolesConfig();
        
        foreach ($permissions as $roleId => $rolePermissions) {
            if (!isset($rolesConfig[$roleId])) {
                $rolesConfig[$roleId] = new BL_CustomGrid_Object(array(
                    'permissions' => array(),
                    'default_profile_id' => null,
                    'assigned_profiles_ids' => array(),
                ));
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
        
        $this->setData('roles_config', $rolesConfig);
        return $this;
    }
    
    /**
     * Check if the current user has the required permission for the given action
     *
     * @param string $action Action code
     * @param bool|null $aclPermission Corresponding ACL permission value
     * @return bool
     */
    public function checkUserActionPermission($action, $aclPermission=null)
    {
        $session = $this->_getAdminSession();
        
        if (($user = $session->getUser()) && ($role = $user->getRole())) {
            $roleId = $role->getId();
        } else {
            return false;
        }
        
        if (is_array($permissions = $this->getRolePermissions($roleId, false))) {
            $permission = (isset($permissions[$action]) ? $permissions[$action] : self::ACTION_PERMISSION_USE_CONFIG);
        } else {
            $permission = self::ACTION_PERMISSION_USE_CONFIG;
        }
        
        if ($permission === self::ACTION_PERMISSION_NO) {
            return false;
        } elseif ($permission === self::ACTION_PERMISSION_YES) {
            return true;
        }
        
        return is_null($aclPermission)
            ? $session->isAllowed(self::$_gridActionsAclPaths[$action])
            : (bool) $aclPermission;
    }
    
    /**
     * Return whether custom headers should be ignored for columns coming from grid block
     *
     * @return bool
     */
    public function getIgnoreCustomHeaders()
    {
        return is_null($value = $this->_getData('ignore_custom_headers'))
            ? $this->_getConfigHelper()->getIgnoreCustomHeaders()
            : (bool) $value;
    }
    
    /**
     * Return whether custom widths should be ignored for columns coming from grid block
     *
     * @return bool
     */
    public function getIgnoreCustomWidths()
    {
        return is_null($value = $this->_getData('ignore_custom_widths'))
            ? $this->_getConfigHelper()->getIgnoreCustomWidths()
            : (bool) $value;
    }
    
    /**
     * Return whether custom alignments should be ignored for columns coming from grid block
     *
     * @return bool
     */
    public function getIgnoreCustomAlignments()
    {
        return is_null($value = $this->_getData('ignore_custom_alignments'))
            ? $this->_getConfigHelper()->getIgnoreCustomAlignments()
            : (bool) $value;
    }
    
    /**
     * Return whether custom pagination values should be merged with the base ones
     *
     * @return bool
     */
    public function getMergeBasePagination()
    {
        return is_null($value = $this->_getData('merge_base_pagination'))
            ? $this->_getConfigHelper()->getMergeBasePagination()
            : (bool) $value;
    }
    
    /**
     * Return custom pagination values
     *
     * @return array
     */
    public function getPaginationValues()
    {
        return is_null($value = $this->_getData('pagination_values'))
            ? $this->_getConfigHelper()->getPaginationValues()
            : $this->_getHelper()->parseCsvIntArray($value, true, true, 1);
    }
    
    /**
     * Return default pagination value
     *
     * @return int
     */
    public function getDefaultPaginationValue()
    {
        return is_null($this->_getData('default_pagination_value'))
            ? $this->_getConfigHelper()->getDefaultPaginationValue()
            : (int) $this->_getData('default_pagination_value');
    }
    
    /**
     * Return appliable pagination values
     *
     * @return array
     */
    public function getAppliablePaginationValues()
    {
        if (!$this->hasData('appliable_pagination_values')) {
            $values = $this->getPaginationValues();
            
            if (!is_array($values) || empty($values)) {
                $values = self::$_defaultPaginationValues;
            } elseif ($this->getMergeBasePagination()) {
                $values = array_unique(array_merge($values, self::$_defaultPaginationValues));
                sort($values, SORT_NUMERIC);
            }
            
            $this->setData('appliable_pagination_values', $values);
        }
        return $this->_getData('appliable_pagination_values');
    }
    


    /**
     * Return whether the grid header should be pinned (pager / export / mass-actions block)
     *
     * @return bool
     */
    public function getPinHeader()
    {
        return is_null($this->_getData('pin_header'))
            ? $this->_getConfigHelper()->getPinHeader()
            : (bool) $this->_getData('pin_header');
    }
    
    public function updateCustomizationParameters(array $params)
    {
        if (!$this->checkUserActionPermission(self::ACTION_EDIT_CUSTOMIZATION_PARAMS)) {
            $this->_throwPermissionException();
        }
        
        $booleanKeys = array(
            'ignore_custom_headers',
            'ignore_custom_widths',
            'ignore_custom_aligments',
            'merge_base_pagination',
            'pin_header',
        );
        
        foreach ($booleanKeys as $key) {
            if (isset($params[$key])) {
                $this->setData($key, ($params[$key] !== '' ? (bool) $params[$key] : null));
            }
        }
        
        if (isset($params['pagination_values'])) {
            $value = ($params['pagination_values'] !== '' ? $params['pagination_values'] : null);
            $this->setData('pagination_values', $value);
        }
        if (isset($params['default_pagination_value'])) {
            $value = ($params['default_pagination_value'] !== '' ? (int) $params['default_pagination_value'] : null);
            $this->setData('default_pagination_value', $value);
        }
        
        return $this;
    }
    
    /**
     * Return column block values for given collection column
     *
     * @param string $index Column index
     * @param string $rendererType Renderer type code
     * @param string $rendererParams Encoded renderer parameters
     * @param Mage_Core_Model_Store $store Current store
     * @return array
     */
    protected function _getCollectionColumnBlockValues($index, $rendererType, $rendererParams,
        Mage_Core_Model_Store $store)
    {
        $config = Mage::getSingleton('customgrid/column_renderer_config_collection');
        
        if ($renderer = $config->getRendererInstanceByCode($rendererType)) {
            if (is_array($params = $config->decodeParameters($rendererParams))) {
                $renderer->setValues($params);
            } else {
                $renderer->setValues(array());
            }
            
            return $renderer->getColumnBlockValues($index, $store, $this);
        }
        
        return array();
    }
    
    /**
     * Return column block values for given attribute column
     * 
     * @param Mage_Eav_Model_Entity_Attribute $attribute Corresponding attribute model
     * @param string $rendererParams Encoded renderer parameters
     * @param Mage_Core_Model_Store $store Current store
     * @return array
     */
    protected function _getAttributeColumnBlockValues($attribute, $rendererParams, Mage_Core_Model_Store $store)
    {
        $config = Mage::getSingleton('customgrid/column_renderer_config_attribute');
        $renderers = $config->getRenderersInstances();
        $values = array();
        
        foreach ($renderers as $renderer) {
            if ($renderer->isAppliableToAttribute($attribute, $this)) {
                if (is_array($params = $config->decodeParameters($rendererParams))) {
                    $renderer->setValues($params);
                } else {
                    $renderer->setValues(array());
                }
                
                $values = $renderer->getColumnBlockValues($attribute, $store, $this);
                $values = (is_array($values) ? $values : array());
                break;
            }
        }
        
        return $values;
    }
    
    /**
     * Return column block values for given custom column
     *
     * @param string $columnBlockId Column block ID
     * @param string $columnIndex Column index
     * @param BL_CustomGrid_Model_Custom_Column_Abstract $customColumn Custom column model
     * @param string $rendererType Renderer type code
     * @param string $rendererParams Encoded renderer parameters
     * @param string $customizationParams Encoded customization parameters
     * @param Mage_Core_Model_Store $store Current store
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return array
     */
    protected function _getCustomColumnBlockValues($columnBlockId, $columnIndex,
        BL_CustomGrid_Model_Custom_Column_Abstract $customColumn, $rendererType, $rendererParams,
        $customizationParams, Mage_Core_Model_Store $store, Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        if ($customColumn->getAllowRenderers()) {
            if ($customColumn->getLockedRenderer()
                && ($customColumn->getLockedRenderer() != $rendererType)) {
                $rendererType = $customColumn->getLockedRenderer();
                $rendererParams = null;
            }
            
            $config = Mage::getSingleton('customgrid/column_renderer_config_collection');
            
            if ($renderer = $config->getRendererInstanceByCode($rendererType)) {
                if (is_array($params = $config->decodeParameters($rendererParams))) {
                    $renderer->setValues($params);
                } else {
                    $renderer->setValues(array());
                }
            }
        } else {
            $renderer = null;
        }
        
        if (!empty($customizationParams)) {
            $customizationParams = Mage::getSingleton('customgrid/grid_type_config')
                ->decodeParameters($customizationParams);
        } else {
            $customizationParams = array();
        }
        
        return $customColumn->applyToGridBlock(
            $gridBlock,
            $this,
            $columnBlockId,
            $columnIndex,
            (is_array($customizationParams) ? $customizationParams : array()),
            $store,
            $renderer
        );
    }
    
    /**
     * Apply columns customization to the given grid block instance
     *
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param bool $applyFromCollection Whether collection columns should be added to the grid block
     * @return this
     */
    public function applyColumnsToGridBlock(Mage_Adminhtml_Block_Widget_Grid $gridBlock, $applyFromCollection)
    {
        $gridIds = array_keys($gridBlock->getColumns());
        $columnsOrders = array();
        $columns = $this->getColumns(false, true);
        uasort($columns, array($this, '_sortColumns'));
        $attributes = $this->getAvailableAttributes();
        $addedAttributes = array();
        
        foreach ($columns as $column) {
            if (!in_array($column->getBlockId(), $gridIds, true)) {
                if ($column->isVisible() && !$column->isMissing()
                    && (!$column->isCollection() || $applyFromCollection)) {
                    $lockedValues = $this->getColumnLockedValues($column->getBlockId());
                    
                    $data = array(
                        'header' => $column->getHeader(),
                        'align'  => $column->getAlign(),
                        'width'  => $column->getWidth(),
                        'index'  => $column->getIndex(),
                    );
                    $data = array_merge($data, array_intersect_key($lockedValues, $data));
                    
                    if ($column->isCollection()) {
                        if (isset($lockedValues['renderer']) || $column->getRendererType()) {
                            if (isset($lockedValues['renderer'])) {
                                $rendererType = $lockedValues['renderer'];
                                $rendererParams = ($rendererType == $column->getRendererType())
                                    ? $column->getRendererParams()
                                    : array();
                            } else {
                                $rendererType = $column->getRendererType();
                                $rendererParams = $column->getRendererParams();
                            }
                            
                            $data = array_merge(
                                $data, 
                                $this->_getCollectionColumnBlockValues(
                                    $column->getIndex(),
                                    $rendererType,
                                    $rendererParams,
                                    $gridBlock->blcg_getStore()
                                )
                            );
                        }
                        
                    } elseif ($column->isAttribute()) {
                        if (!isset($attributes[$column->getIndex()])) {
                            continue;
                        }
                        
                        $store = is_null($column->getStoreId())
                            ? $gridBlock->blcg_getStore()
                            : Mage::app()->getStore($column->getStoreId());
                        $attributeKey = $column->getIndex() . '_' . $store->getId();
                        
                        if (!isset($addedAttributes[$attributeKey])) {
                            $data['index'] = $alias = self::ATTRIBUTE_COLUMN_GRID_ALIAS
                                . str_replace(self::ATTRIBUTE_COLUMN_ID_PREFIX, '', $column->getBlockId());
                            
                            $gridBlock->blcg_addAdditionalAttribute(array(
                                'alias'     => $alias,
                                'attribute' => $attributes[$column->getIndex()],
                                'bind'      => 'entity_id',
                                'filter'    => null,
                                'join_type' => 'left',
                                'store_id'  => $store->getId(),
                            ));
                            
                            $addedAttributes[$attributeKey] = $alias;
                        } else {
                            $data['index'] = $addedAttributes[$attributeKey];
                        }
                        
                        $data = array_merge(
                            $data,
                            $this->_getAttributeColumnBlockValues(
                                $attributes[$column->getIndex()],
                                $column->getRendererParams(),
                                $store
                            )
                        );
                        
                    } elseif ($column->isCustom()) {
                        if (!$customColumn = $column->getCustomColumnModel()) {
                            continue;
                        } 
                        
                        $store = is_null($column->getStoreId())
                            ? $gridBlock->blcg_getStore()
                            : Mage::app()->getStore($column->getStoreId());
                        
                         $data['index'] = $alias = self::CUSTOM_COLUMN_GRID_ALIAS
                            . str_replace(self::CUSTOM_COLUMN_ID_PREFIX, '', $column->getBlockId());
                        
                        $customValues = $this->_getCustomColumnBlockValues(
                            $column->getBlockId(),
                            $data['index'],
                            $customColumn,
                            $column->getRendererType(),
                            $column->getRendererParams(),
                            $column->getCustomizationParams(),
                            $store,
                            $gridBlock
                        );
                        
                        if (!is_array($customValues)) {
                            continue;
                        }
                        
                        $data = array_merge($data, $customValues);
                    }
                    
                    if (isset($lockedValues['config_values']) && is_array($lockedValues['config_values'])) {
                        $data = array_merge($data, $lockedValues['config_values']);
                    }
                    
                    $gridBlock->addColumn($column->getBlockId(), $data);
                    $columnsOrders[] = $column->getBlockId();
                }
                
            } else {
                if ($column->isVisible()) {
                    if ($gridColumn = $gridBlock->getColumn($column->getBlockId())) {
                        if (!$this->getIgnoreCustomWidths()) {
                            $gridColumn->setWidth($column->getWidth());
                        }
                        if (!$this->getIgnoreCustomAlignments()) {
                            $gridColumn->setAlign($column->getAlign());
                        }
                        if (!$this->getIgnoreCustomHeaders()) {
                            $gridColumn->setHeader($column->getHeader());
                        }
                    }
                    $columnsOrders[] = $column->getBlockId();
                    
                } else {
                    $gridBlock->blcg_removeColumn($column->getBlockId());
                }
            }
            
            if ($column->isOnlyFilterable()
                && ($columnBlock = $gridBlock->getColumn($column->getBlockId()))) {
                $columnBlock->setBlcgFilterOnly(true);
                
                if ($gridBlock->blcg_isExport()) {
                    // Columns with is_system flag set won't be exported, so forcing it will save us two overloads
                    $columnBlock->setIsSystem(true);
                }
            }
        }
        
        $gridBlock->blcg_resetColumnsOrder();
        $previousBlockId = null;
        
        foreach ($columnsOrders as $columnBlockId) {
            if (!is_null($previousBlockId)) {
                $gridBlock->addColumnsOrder($columnBlockId, $previousBlockId);
            }
            $previousBlockId = $columnBlockId;
        }
        
        $gridBlock->sortColumnsByOrder();
        
        return $this;
    }
    
    /**
     * Return column alignments options hash
     *
     * @return array
     */
    public function getColumnAlignments()
    {
        if (is_null(self::$_columnAlignments)) {
            $helper = Mage::helper('customgrid');
            
            self::$_columnAlignments = array(
                self::COLUMN_ALIGNMENT_LEFT   => $helper->__('Left'),
                self::COLUMN_ALIGNMENT_CENTER => $helper->__('Middle'),
                self::COLUMN_ALIGNMENT_RIGHT  => $helper->__('Right'),
            );
        }
        return self::$_columnAlignments;
    }
    
    /**
     * Return column origins options hash
     *
     * @return array
     */
    public function getColumnOrigins()
    {
        if (is_null(self::$_columnOrigins)) {
            $helper = Mage::helper('customgrid');
            
            self::$_columnOrigins = array(
                self::COLUMN_ORIGIN_GRID       => $helper->__('Grid'),
                self::COLUMN_ORIGIN_COLLECTION => $helper->__('Collection'),
                self::COLUMN_ORIGIN_ATTRIBUTE  => $helper->__('Attribute'),
                self::COLUMN_ORIGIN_CUSTOM     => $helper->__('Custom'),
            );
        }
        return self::$_columnOrigins;
    }
    
    /**
     * Return grid actions options hash
     *
     * @param bool $grouped Whether actions should be grouped by general category
     * @return array
     */
    public function getGridActions($grouped=false)
    {
        if (is_null(self::$_groupedGridActions)) {
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