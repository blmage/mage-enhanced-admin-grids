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

class BL_CustomGrid_Block_Grid_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('blcg_grid_edit_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle($this->__('Custom Grid'));
    }
    
    /**
     * Return whether the profile edit tab can be displayed
     * 
     * @return bool
     */
    protected function _canDisplayProfileEditTab()
    {
        return $this->getGridModel()
            ->checkUserActionPermission(BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_PROFILES);
    }
    
    /**
     * Return whether the profile assign tab can be displayed
     * 
     * @return bool
     */
    protected function _canDisplayProfileAssignTab()
    {
        return $this->getGridModel()
            ->checkUserActionPermission(BL_CustomGrid_Model_Grid_Sentry::ACTION_ASSIGN_PROFILES);
    }
    
    /**
     * Return whether the grid informations tab can be displayed
     * 
     * @return bool
     */
    protected function _canDisplayInfosTab()
    {
        return $this->getGridModel()
            ->checkUserPermissions(
                array(
                    BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_FORCED_TYPE,
                    BL_CustomGrid_Model_Grid_Sentry::ACTION_ENABLE_DISABLE,
                )
            );
    }
    
    /**
     * Return whether the grid columns tab can be displayed
     * 
     * @return bool
     */
    protected function _canDisplayColumnsTab()
    {
        return $this->getGridModel()
            ->checkUserActionPermission(BL_CustomGrid_Model_Grid_Sentry::ACTION_CUSTOMIZE_COLUMNS);
    }
    
    /**
     * Return whether the grid settings tab can be displayed
     * 
     * @return bool
     */
    protected function _canDisplaySettingsTab()
    {
        return $this->getGridModel()
            ->checkUserPermissions(
                array(
                    BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_DEFAULT_PARAMS_BEHAVIOURS,
                    BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_CUSTOMIZATION_PARAMS,
                    BL_CustomGrid_Model_Grid_Sentry::ACTION_ASSIGN_PROFILES,
                    BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_PROFILES,
                )
            );
    }
    
    /**
     * Return whether the roles permissions tabs can be displayed
     * 
     * @return bool
     */
    protected function _canDisplayRolesTabs()
    {
        return $this->getGridModel()
            ->checkUserActionPermission(BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_ROLES_PERMISSIONS);
    }
    
    protected function _prepareLayout()
    {
        if ($this->_canDisplayProfileEditTab()) {
            $this->addTab('profile_edit', 'customgrid/grid_edit_tab_profile_edit');
        }
        if ($this->_canDisplayProfileAssignTab()) {
            $this->addTab('profile_assign', 'customgrid/grid_edit_tab_profile_assign');
        }
        if ($this->_canDisplayColumnsTab()) {
            $this->addTab('columns', 'customgrid/grid_edit_tab_columns');
        }
        if ($this->_canDisplayInfosTab()) {
            $this->addTab('infos', 'customgrid/grid_edit_tab_infos');
        }
        if ($this->_canDisplaySettingsTab()) {
            $this->addTab('settings', 'customgrid/grid_edit_tab_settings');
        }
        if ($this->_canDisplayRolesTabs()) {
            /** @var $roles Mage_Admin_Model_Mysql4_Roles_Collection */
            $roles = Mage::getResourceModel('admin/roles_collection');
            
            foreach ($roles as $role) {
                /** @var $role Mage_Admin_Model_Role */
                $this->addTab('role_' . $role->getRoleId(), 'customgrid/grid_edit_tab_role');
                $this->setTabData('role_' . $role->getRoleId(), 'role', $role);
            }
        }
        return parent::_prepareLayout();
    }
    
    protected function _toHtml()
    {
        return $this->getChildHtml('profile_switcher') . parent::_toHtml();
    }
    
    /**
     * Return the current grid model
     * 
     * @return BL_CustomGrid_Model_Grid
     */
    public function getGridModel()
    {
        return Mage::registry('blcg_grid');
    }
    
    /**
     * Return the current grid profile
     * 
     * @return BL_CustomGrid_Model_Grid_Profile
     */
    public function getGridProfile()
    {
        return Mage::registry('blcg_grid_profile');
    }
}
