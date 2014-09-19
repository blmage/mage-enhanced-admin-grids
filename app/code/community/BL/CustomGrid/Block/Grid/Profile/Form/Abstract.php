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

abstract class BL_CustomGrid_Block_Grid_Profile_Form_Abstract
    extends Mage_Adminhtml_Block_Widget_Form
{
    abstract protected function _getFormType();
    abstract protected function _addFormFields(Varien_Data_Form $form);
    
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array('id' => 'blcg_grid_profile_form'));
        $form->setHtmlIdPrefix($this->_getFormHtmlIdPrefix());
        $this->_addFormFields($form);
        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
    
    protected function _getFormHtmlIdPrefix()
    {
        return 'blcg_grid_profile_form'
            . '_' . $this->_getFormType()
            . '_' . $this->getGridModel()->getId()
            . '_' . $this->getGridModel()->getProfileId();
    }
    
    public function getGridModel()
    {
        return Mage::registry('blcg_grid');
    }
    
    public function getGridProfile()
    {
        return $this->getGridModel()->getProfile();
    }
}