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

class BL_CustomGrid_Block_Grid_Edit_Tabs
    extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('blcg_grid_edit_tabs');
        $this->setDestElementId('blcg_grid_edit_form');
        $this->setTitle($this->__('Custom Grid'));
    }
    
    protected function _prepareLayout()
    {
        $gridModel = $this->getGridModel();
        
        if ($gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid::ACTION_CUSTOMIZE_COLUMNS)) {
            $this->addTab('columns', 'customgrid/grid_edit_tab_columns');
        }
        
        if ($gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid::ACTION_EDIT_DEFAULT_PARAMS_BEHAVIOURS)
            || $gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid::ACTION_EDIT_CUSTOMIZATION_PARAMS)
            || $gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid::ACTION_ASSIGN_PROFILES)) {
            $this->addTab('settings', 'customgrid/grid_edit_tab_settings');
        }
        
        if ($gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid::ACTION_EDIT_ROLES_PERMISSIONS)) {
            $roles = Mage::getModel('admin/roles')->getCollection();
            
            foreach ($roles as $role) {
                $this->addTab('role_' . $role->getRoleId(), array(
                    'label'   => $this->__('%s Role', $role->getRoleName()),
                    'active'  => false,
                    'content' => $this->getLayout()
                        ->createBlock('customgrid/grid_edit_tab_role')
                        ->setRole($role)
                        ->toHtml(),
                ));
            }
        }
        
        return parent::_prepareLayout();
    }
    
    protected function _toHtml()
    {
        return $this->getChildHtml('profile_switcher') . parent::_toHtml();
    }
    
    public function getGridModel()
    {
        return Mage::registry('blcg_grid');
    }
}
