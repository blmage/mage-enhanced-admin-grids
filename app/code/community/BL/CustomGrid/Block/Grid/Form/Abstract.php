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

abstract class BL_CustomGrid_Block_Grid_Form_Abstract extends BL_CustomGrid_Block_Widget_Form
{
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        
        if (!$this->isTabForm()) {
            /** @var $fieldsetRenderer BL_CustomGrid_Block_Widget_Form_Renderer_Fieldset */
            $fieldsetRenderer = $this->getLayout()->createBlock('customgrid/widget_form_renderer_fieldset');
            $fieldsetRenderer->setDefaultCollapseState($this->getDefaultFieldsetCollapseState());
            Varien_Data_Form::setFieldsetRenderer($fieldsetRenderer);
        }
        
        return $this;
    }
    
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(
            array(
                'html_id_prefix'    => $this->_getFormHtmlIdPrefix(),
                'field_name_suffix' => $this->_getFormFieldNameSuffix(),
            )
        );
        
        if (!$this->isTabForm()) {
            $form->addData(
                array(
                    'id'     => $this->getFormId(),
                    'action' => $this->getFormAction(),
                    'method' => 'post',
                    'use_container' => true,
                )
            );
        }
        
        $this->setForm($form);
        $this->_addFieldsToForm($this->getForm());
        
        if (is_array($values = $this->_getFormValues())) {
            $form->setValues($values);
        }
        
        return parent::_prepareForm();
    }
    
    /**
     * Return whether this form is part of a tab
     * 
     * @return bool
     */
    public function isTabForm()
    {
        return ($this instanceof Mage_Adminhtml_Block_Widget_Tab_Interface);
    }
    
    /**
     * Return the form ID
     * 
     * @return string
     */
    public function getFormId()
    {
        return 'blcg_grid_form';
    }
    
    /**
     * Return the HTML ID prefix
     * 
     * @return string
     */
    protected function _getFormHtmlIdPrefix()
    {
        return $this->getFormId() . '_' . $this->_getFormType() . '_';
    }
    
    /**
     * Return the field name suffix
     * 
     * @return string|null
     */
    protected function _getFormFieldNameSuffix()
    {
        return null;
    }
    
    /**
     * Return the form type
     * 
     * @return string
     */
    protected function _getFormType()
    {
        return $this->getFormType();
    }
    
    /**
     * Return the form action
     * 
     * @return string
     */
    public function getFormAction()
    {
        return '';
    }
    
    /**
     * Return the form fields values
     * 
     * @return array|null
     */
    protected function _getFormValues()
    {
        return null;
    }
    
    /**
     * Return the default collapsed state of the form fieldsets
     * 
     * @return bool
     */
    public function getDefaultFieldsetCollapseState()
    {
        return true;
    }
    
    /**
     * Return whether this form only contains read-only fields
     * 
     * @return bool
     */
    public function hasOnlyReadOnlyFields()
    {
        return false;
    }
    
    /**
     * Return the field ID from which to retrieve the form submit URL, or false if the submit URL corresponds to
     * the form action
     * 
     * @return string|false
     */
    public function getUseFieldValueForUrl()
    {
        return false;
    }
    
    /**
     * Return whether this form is submitted via Ajax
     * 
     */
    public function getUseAjaxSubmit()
    {
        return true;
    }
    
    /**
     * Return whether the corresponding grid block should be reloaded upon submit success
     * 
     * @return bool
     */
    public function getReloadGridAfterSuccess()
    {
        return true;
    }
    
    /**
     * Add form fields to the given form
     * 
     * @param Varien_Data_Form $form Form
     * @return BL_CustomGrid_Block_Grid_Form_Abstract
     */
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
    
    /**
     * Return yes/no values as an option array
     * 
     * @return array
     */
    protected function _getYesNoOptionArray()
    {
        /** @var $source BL_CustomGrid_Model_System_Config_Source_Yesno */
        $source = Mage::getSingleton('customgrid/system_config_source_yesno');
        return $source->toOptionArray();
    }
    
    /**
     * Return grid parameters as an option array
     * 
     * @param bool $withNone Whether "None" option should be included
     * @return array
     */
    protected function _getGridParamsOptionArray($withNone = true)
    {
        /** @var $source BL_CustomGrid_Model_System_Config_Source_Grid_Param */
        $source = Mage::getSingleton('customgrid/system_config_source_grid_param');
        return $source->toOptionArray($withNone);
    }
    
    /**
     * Return admin roles as an option array
     * 
     * @param bool $includeCreatorRole Whether "Creator Role" option should be included
     * @return array
     */
    protected function _getAdminRolesOptionArray($includeCreatorRole = true)
    {
        /** @var $source BL_CustomGrid_Model_System_Config_Source_Admin_Role */
        $source = Mage::getSingleton('customgrid/system_config_source_admin_role');
        return $source->toOptionArray($includeCreatorRole);
    }
    
    /**
     * Return admin users as an option array
     * 
     * @return array
     */
    protected function _getAdminUsersOptionArray()
    {
        /** @var $source BL_CustomGrid_Model_System_Config_Source_Admin_User */
        $source = Mage::getSingleton('customgrid/system_config_source_admin_user');
        return $source->toOptionArray();
    }
}
