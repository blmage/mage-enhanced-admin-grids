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

class BL_CustomGrid_Block_Widget_Grid_Config_Profiles_Bar extends Mage_Adminhtml_Block_Widget
{
    /**
     * Profiles actions base config data
     *
     * @var array
     */
    static protected $_profilesActionsBaseConfig = array(
        'go_to' => array(
            'label'   => 'Go To',
            'mode'    => 'direct',
            'confirm' => false,
            'applyUrlAction' => 'goTo',
            'appliesToBase'    => true,
            'appliesToCurrent' => false,
            'leftClickable'    => true,
        ),
        'default' => array(
            'label'   => 'Choose As Default',
            'mode'    => 'window',
            'confirm' => false,
            'applyUrlAction'  => 'default',
            'windowUrlAction' => 'defaultForm',
            'windowConfig'      => array('height' => 600),
            'appliesToBase'     => true,
            'appliesToCurrent'  => true,
        ),
        'copy_new' => array(
            'label'     => 'Copy To New Profile',
            'mode'      => 'window',
            'confirm'   => false,
            'applyUrlAction'   => 'copyToNew',
            'windowUrlAction'  => 'copyToNewForm',
            'appliesToBase'    => true,
            'appliesToCurrent' => true,
        ),
        'copy_existing' => array(
            'label'   => 'Copy To Existing Profile',
            'mode'    => 'window',
            'confirm' => false,
            'applyUrlAction'   => 'copyToExisting',
            'windowUrlAction'  => 'copyToExistingForm',
            'appliesToBase'    => true,
            'appliesToCurrent' => true,
        ),
        'edit' => array(
            'label'   => 'Edit',
            'mode'    => 'window',
            'confirm' => false,
            'applyUrlAction'   => 'edit',
            'windowUrlAction'  => 'editForm',
            'windowConfig'     => array('height' => 490),
            'appliesToBase'    => true,
            'appliesToCurrent' => true,
        ),
        'assign' => array(
            'label'   => 'Assign',
            'mode'    => 'window',
            'confirm' => false,
            'applyUrlAction'   => 'assign',
            'windowUrlAction'  => 'assignForm',
            'appliesToBase'    => true,
            'appliesToCurrent' => true,
        ),
        'delete' => array(
            'label'   => 'Delete',
            'mode'    => 'direct',
            'confirm' => 'Are you sure you want to delete this profile?',
            'applyUrlAction'   => 'delete',
            'appliesToBase'    => false,
            'appliesToCurrent' => true,
        ),
    );
    
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('bl/customgrid/widget/grid/config/profiles/bar.phtml');
    }
    
    protected function _toHtml()
    {
        return ($this->getGridModel() ? parent::_toHtml() : '');
    }
    
    /**
     * Return the core helper
     *
     * @return Mage_Core_Helper_Data
     */
    public function getCoreHelper()
    {
        return $this->helper('core');
    }
    
    /**
     * Return the name of the JS object used to handle the profiles bar
     *
     * @return string
     */
    public function getJsObjectName()
    {
        if (!$this->hasData('profiles_js_object_name')) {
            $this->setData('profiles_js_object_name', $this->getCoreHelper()->uniqHash('blcgProfilesBar'));
        }
        return $this->_getData('profiles_js_object_name');
    }
    
    /**
     * Return the placeholder usable to represent a profile ID
     *
     * @return string
     */
    public function getProfileIdPlaceholder()
    {
        return '{{profile_id}}';
    }
    
    /**
     * Return the available profiles, already prepared for JSON usage
     *
     * @return array
     */
    public function getProfiles()
    {
        if (!$this->hasData('profiles')) {
            $profiles = array();
            
            if ($gridModel = $this->getGridModel()) {
                $profiles = $gridModel->getProfiles(true, true);
            }
            
            foreach ($profiles as $key => $profile) {
                $profiles[$key] = array(
                    'id'        => $profile->getId(),
                    'name'      => $profile->getName(),
                    'isBase'    => $profile->isBase(),
                    'isCurrent' => $profile->isCurrent(),
                );
            }
            
            $this->setData('profiles', $profiles);
        }
        return $this->_getData('profiles');
    }
    
    /**
     * Initialize the profiles JSON config data
     *
     * @return BL_CustomGrid_Block_Widget_Grid_Config
     */
    protected function _initProfilesJsonConfigData()
    {
        $profiles = $this->getProfiles();
        $coreHelper = $this->getCoreHelper();
        
        $this->addData(
            array(
                'profiles_json_config' => $coreHelper->jsonEncode((object) $profiles),
                'sorted_profiles_ids_json' => $coreHelper->jsonEncode(array_keys($profiles)),
            )
        );
        
        return $this;
    }
    
    /**
     * Return the JSON config for the available profiles
     *
     * @return string
     */
    public function getProfilesJsonConfig()
    {
        if (!$this->hasData('profiles_json_config')) {
            $this->_initProfilesJsonConfigData();
        }
        return $this->_getData('profiles_json_config');
    }
    
    /**
     * Return the sorted IDs of the available profiles as JSON
     *
     * @return string
     */
    public function getSortedProfilesIdsJson()
    {
        if (!$this->hasData('sorted_profiles_ids_json')) {
            $this->_initProfilesJsonConfigData();
        }
        return $this->_getData('sorted_profiles_ids_json');
    }
    
    /**
     * Return whether the "Choose As Default" profile action is available
     *
     * @return bool
     */
    protected function _isProfilesDefaultActionAvailable()
    {
        if (count($this->getProfiles()) <= 1) {
            return false;
        }
        
        return $this->getGridModel()
            ->checkUserPermissions(
                array(
                    BL_CustomGrid_Model_Grid_Sentry::ACTION_CHOOSE_OWN_USER_DEFAULT_PROFILE,
                    BL_CustomGrid_Model_Grid_Sentry::ACTION_CHOOSE_OTHER_USERS_DEFAULT_PROFILE,
                    BL_CustomGrid_Model_Grid_Sentry::ACTION_CHOOSE_OWN_ROLE_DEFAULT_PROFILE,
                    BL_CustomGrid_Model_Grid_Sentry::ACTION_CHOOSE_OTHER_ROLES_DEFAULT_PROFILE,
                    BL_CustomGrid_Model_Grid_Sentry::ACTION_CHOOSE_GLOBAL_DEFAULT_PROFILE,
                )
            );
    }
    
    /**
     * Return whether the "Copy To New" profile action is available
     *
     * @return bool
     */
    protected function _isProfilesCopyToNewActionAvailable()
    {
        return $this->getGridModel()
            ->checkUserActionPermission(BL_CustomGrid_Model_Grid_Sentry::ACTION_COPY_PROFILES_TO_NEW);
    }
    
    /**
     * Return whether the "Copy To Existing" profile action is available
     *
     * @return bool
     */
    protected function _isProfilesCopyToExistingActionAvailable()
    {
        return (count($this->getProfiles()) > 1)
            ? $this->getGridModel()
                ->checkUserActionPermission(BL_CustomGrid_Model_Grid_Sentry::ACTION_COPY_PROFILES_TO_EXISTING)
            : false;
    }
    
    /**
     * Return whether the "Edit" profile action is available
     *
     * @return bool
     */
    protected function _isProfilesEditActionAvailable()
    {
        return $this->getGridModel()
            ->checkUserActionPermission(BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_PROFILES);
    }
    
    /**
     * Return whether the "Assign" profile action is available
     *
     * @return bool
     */
    protected function _isProfilesAssignActionAvailable()
    {
        return $this->getGridModel()
            ->checkUserActionPermission(BL_CustomGrid_Model_Grid_Sentry::ACTION_ASSIGN_PROFILES);
    }
    
    /**
     * Return whether the "Delete" profile action is available
     *
     * @return bool
     */
    protected function _isProfilesDeleteActionAvailable()
    {
        return $this->getGridModel()
            ->checkUserActionPermission(BL_CustomGrid_Model_Grid_Sentry::ACTION_DELETE_PROFILES);
    }
    
    /**
     * Prepare the given action config data so that it is fully suitable
     *
     * @param array $actionConfig Base action config data
     * @param array $urlParams Base URL parameters
     * @return array
     */
    protected function _prepareProfileActionConfig(array $actionConfig, array $urlParams)
    {
        $actionConfig['label'] = $this->__($actionConfig['label']);
        
        if (is_string($actionConfig['confirm'])) {
            $actionConfig['confirm'] = $this->__($actionConfig['confirm']);
        }
        
        $actionConfig['url'] = $this->getUrl(
            'adminhtml/blcg_grid_profile/' . $actionConfig['applyUrlAction'],
            $urlParams
        );
        
        unset($actionConfig['applyUrlAction']);
        
        if ($actionConfig['mode'] == 'window') {
            $actionConfig['windowUrl'] = $this->getUrl(
                'adminhtml/blcg_grid_profile/' . $actionConfig['windowUrlAction'],
                $urlParams
            );
            
            unset($actionConfig['windowUrlAction']);
        }
        
        return $actionConfig;
    }
    
    /**
     * Return the config for the available profiles actions
     *
     * @return array
     */
    protected function _getProfilesActionsConfig()
    {
        $actions = array();
        $availableActionsCodes = array_keys(
            array_filter(
                array(
                    'go_to'         => true,
                    'default'       => $this->_isProfilesDefaultActionAvailable(),
                    'copy_new'      => $this->_isProfilesCopyToNewActionAvailable(),
                    'copy_existing' => $this->_isProfilesCopyToExistingActionAvailable(),
                    'edit'          => $this->_isProfilesEditActionAvailable(),
                    'assign'        => $this->_isProfilesAssignActionAvailable(),
                    'delete'        => $this->_isProfilesDeleteActionAvailable(),
                )
            )
        );
        
        foreach ($availableActionsCodes as $actionCode) {
            $actions[$actionCode] = $this->_prepareProfileActionConfig(
                self::$_profilesActionsBaseConfig[$actionCode],
                array(
                    'grid_id' => $this->getGridModel()->getId(),
                    'profile_id' => $this->getProfileIdPlaceholder(),
                    'profiles_js_object_name' => $this->getJsObjectName(),
                )
            );
        }
        
        return $actions;
    }
    
    /**
     * Return the JSON config for the available profiles actions
     *
     * @return string
     */
    public function getProfilesActionsJsonConfig()
    {
        if (!$this->hasData('profiles_actions_json_config')) {
            $actions = ($gridModel = $this->getGridModel())
                ? $this->_getProfilesActionsConfig()
                : array();
            $this->setData('profiles_actions_json_config', $this->getCoreHelper()->jsonEncode($actions));
        }
        return $this->_getData('profiles_actions_json_config');
    }
    
    /**
     * Return the JSON config for the profiles bar JS object
     *
     * @return string
     */
    public function getProfilesBarJsonConfig()
    {
        if (!$this->hasData('profiles_bar_json_config')) {
            $config = array(
                'profileIdPlaceholder' => $this->getProfileIdPlaceholder(),
                'profileItemIdPrefix'  => $this->getCoreHelper()->uniqHash('blcg-grid-profile-item-'),
            );
            
            if ($gridModel = $this->getGridModel()) {
                $config['removableUrlParams'] = array_values($gridModel->getBlockVarNames());
            }
            
            $this->setData('profiles_bar_json_config', $this->getCoreHelper()->jsonEncode((object) $config));
        }
        return $this->_getData('profiles_bar_json_config');
    }
}
