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

class BL_CustomGrid_Block_Custom_Grid_Edit_Tabs
    extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('custom_grid_info_tabs');
        $this->setDestElementId('custom_grid_edit_form');
        $this->setTitle($this->__('Custom Grid'));
    }
    
    protected function _prepareLayout()
    {
        $grid = $this->getCustomGrid();
        
        if ($grid->checkUserActionPermission(BL_CustomGrid_Model_Grid::GRID_ACTION_CUSTOMIZE_COLUMNS)) {
            $this->addTab('columns', array(
                'label'   => $this->__('Columns'),
                'content' => $this->getLayout()->createBlock('customgrid/custom_grid_edit_tab_columns')->toHtml(),
                'active'  => true,
            ));
        }
        
        $this->addTab('settings', array(
            'label'   => $this->__('Settings'),
            'content' => $this->getLayout()->createBlock('customgrid/custom_grid_edit_tab_settings')->toHtml(),
            'active'  => false,
        ));
        
        $roles = Mage::getModel('admin/roles')->getCollection();
        
        foreach ($roles as $role) {
            $this->addTab('role_'.$role->getRoleId(), array(
                'label'   => $this->__('%s Role', $role->getRoleName()),
                'content' => $this->getLayout()->createBlock('customgrid/custom_grid_edit_tab_role')->setRole($role)->toHtml(),
                'active'  => false,
            ));
        }
        
        return parent::_prepareLayout();
    }
    
    public function getCustomGrid()
    {
        if (!$this->_getData('custom_grid') instanceof BL_CustomGrid_Model_Grid) {
            $this->setData('custom_grid', Mage::registry('custom_grid'));
        }
        return $this->_getData('custom_grid');
    }
}
