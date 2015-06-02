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

class BL_CustomGrid_Block_Grid_Form_Container extends BL_CustomGrid_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->setTemplate('bl/customgrid/grid/form/container.phtml');
        parent::__construct();
        
        $this->_removeButtons(array('back', 'delete', 'reset'));
        
        $this->_updateButton(
            'save',
            null,
            array(
                'label'      => $this->getButtonLabel(),
                'onclick'    => 'blcgGridForm.submit();',
                'class'      => 'save',
                'sort_order' => 0,
            )
        );
    }
    
    protected function _prepareLayout()
    {
        return Mage_Adminhtml_Block_Widget_Container::_prepareLayout();
    }
    
    protected function _beforeToHtml()
    {
        if ($this->hasOnlyReadOnlyFields()) {
            $this->_removeButton('save');
        }
        return parent::_beforeToHtml();
    }
    
    public function setFormType($formType)
    {
        return $this->setData('form_type', $formType)
            ->setChild(
                'form',
                $this->getLayout()
                    ->createBlock('customgrid/grid_form_' . $formType)
                    ->setFormType($formType)
                    ->addData((array) $this->getDataSetDefault('form_data', array()))
            );
    }
    
    public function hasOnlyReadOnlyFields()
    {
        return ($form = $this->getChild('form'))
            ? $form->hasOnlyReadOnlyFields()
            : false;
    }
    
    public function getHeaderText()
    {
        return '';
    }
    
    public function getButtonLabel()
    {
        return $this->__('Apply');
    }
    
    public function getSaveUrl()
    {
        return ($form = $this->getChild('form'))
            ? $form->getFormAction()
            : '';
    }
    
    public function getUseFieldValueForUrl()
    {
        return ($form = $this->getChild('form'))
            ? $form->getUseFieldValueForUrl()
            : false;
    }
    
    public function getUseAjaxSubmit()
    {
        return ($form = $this->getChild('form'))
            ? $form->getUseAjaxSubmit()
            : false;
    }
    
    public function getReloadGridAfterSuccess()
    {
        return ($form = $this->getChild('form'))
            ? $form->getReloadGridAfterSuccess()
            : true;
    }
    
    public function getGridJsObjectName()
    {
        return $this->_getJsObjectName('grid_js_object_name');
    }
}
