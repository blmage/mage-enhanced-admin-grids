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

abstract class BL_CustomGrid_Block_Grid_Form_Abstract extends BL_CustomGrid_Block_Widget_Form
{
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        
        if (!$this->isTabForm()) {
            Varien_Data_Form::setFieldsetRenderer(
                $this->getLayout()
                    ->createBlock('customgrid/widget_form_renderer_fieldset')
                    ->setDefaultCollapseState($this->getDefaultFieldsetCollapseState())
            );
        }
        
        return $this;
    }
    
    protected function _prepareForm()
    {
        if (!$this->isTabForm()) {
            $this->setForm(
                new Varien_Data_Form(
                    array(
                        'id'     => $this->getFormId(),
                        'action' => $this->getFormAction(),
                        'method' => 'post',
                        'use_container'     => true,
                        'html_id_prefix'    => $this->_getFormHtmlIdPrefix(),
                        'field_name_suffix' => $this->_getFormFieldNameSuffix(),
                    )
                )
            );
        }
        
        $this->_addFieldsToForm($this->getForm());
        return parent::_prepareForm();
    }
    
    public function isTabForm()
    {
        return ($this instanceof Mage_Adminhtml_Block_Widget_Tab_Interface);
    }
    
    public function getFormId()
    {
        return 'blcg_grid_form';
    }
    
    protected function _getFormHtmlIdPrefix()
    {
        return $this->getFormId() . '_' . $this->_getFormType() . '_';
    }
    
    protected function _getFormFieldNameSuffix()
    {
        return null;
    }
    
    protected function _getFormType()
    {
        return $this->getFormType();
    }
    
    public function getFormAction()
    {
        return '';
    }
    
    public function getDefaultFieldsetCollapseState()
    {
        return true;
    }
    
    public function hasOnlyReadOnlyFields()
    {
        return false;
    }
    
    public function getUseFieldValueForUrl()
    {
        return false;
    }
    
    public function getUseAjaxSubmit()
    {
        return true;
    }
    
    public function getReloadGridAfterSuccess()
    {
        return true;
    }
    
    protected function _addFieldsToForm(Varien_Data_Form $form)
    {
        if (!$this->isTabForm()) {
            $gridModel = $this->getGridModel();
            
            $form->addField(
                'grid_id',
                'hidden',
                array(
                    'name'  => 'grid_id',
                    'value' => $gridModel->getId(),
                )
            );
            
            $form->addField(
                'profile_id',
                'hidden',
                array(
                    'name'  => 'profile_id',
                    'value' => $gridModel->getProfileId(),
                )
            );
        }
        return $this;
    }
}
