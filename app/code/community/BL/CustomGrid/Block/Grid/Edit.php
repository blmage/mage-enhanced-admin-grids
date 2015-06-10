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
 * @copyright  Copyright (c) 2015 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Block_Grid_Edit extends BL_CustomGrid_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId   = 'grid_id';
        $this->_blockGroup = 'customgrid';
        $this->_controller = 'grid';
        parent::__construct();
        
        if (!$this->getGridModel()->checkUserActionPermission(BL_CustomGrid_Model_Grid_Sentry::ACTION_DELETE)) {
            $this->_removeButton('delete');
        }
        
        $this->_addSaveAndContinueButton();
    }
    
    public function isTabbedFormContainer()
    {
        return true;
    }
    
    public function getFormTabsBlock()
    {
        return $this->getLayout()->getBlock('blcg.grid.edit.tabs');
    }
    
    public function getHeaderText()
    {
        $gridModel  = $this->getGridModel();
        $headerText = $this->__('Custom Grid: %s', $gridModel->getBlockType()) . ' - ';
        
        if ($gridModel->getRewritingClassName()) {
            $headerText .= $gridModel->getRewritingClassName();
        } else {
            $headerText .= $this->__('Base Class');
        }
        
        return $headerText;
    }
    
    /**
     * Return the edited grid model
     * 
     * @return BL_CustomGrid_Model_Grid
     */
    public function getGridModel()
    {
        return Mage::registry('blcg_grid');
    }
}
