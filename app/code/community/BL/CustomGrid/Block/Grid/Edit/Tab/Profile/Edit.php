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

class BL_CustomGrid_Block_Grid_Edit_Tab_Profile_Edit extends BL_CustomGrid_Block_Grid_Profile_Form_Edit implements
    Mage_Adminhtml_Block_Widget_Tab_Interface
{
    public function getTabLabel()
    {
        return $this->__('Informations (Profile)');
    }
    
    public function getTabTitle()
    {
        return $this->__('Informations (Profile)');
    }
    
    public function canShowTab()
    {
        return true;
    }
    
    public function isHidden()
    {
        return false;
    }
    
    protected function _getFormHtmlIdPrefix()
    {
        return 'blcg_grid_' . $this->getGridModel()->getId() . '_profile_edit_' . $this->getGridProfile()->getId();
    }
    
    protected function _getFormFieldNameSuffix()
    {
        return 'profile_edit';
    }
    
    protected function _getFormValues()
    {
        return $this->getGridProfile()->getData();
    }
}
