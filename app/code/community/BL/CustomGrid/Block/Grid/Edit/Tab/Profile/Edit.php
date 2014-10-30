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
    
    protected function _prepareForm()
    {
        $gridModel = $this->getGridModel();
        $gridProfile = $this->getGridProfile();
        
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('blcg_grid_' . $gridModel->getId() . '_profile_edit_' . $gridProfile->getId());
        $form->setFieldNameSuffix('profile_edit');
        $form->setValues($gridProfile->getData());
        $this->setForm($form);
        
        return parent::_prepareForm();
    }
}
