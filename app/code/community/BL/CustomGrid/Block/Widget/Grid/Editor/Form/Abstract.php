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

abstract class BL_CustomGrid_Block_Widget_Grid_Editor_Form_Abstract extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('bl/customgrid/widget/grid/editor/form.phtml');
    }
    
    protected function _prepareLayout()
    {
        $returnValue = parent::_prepareLayout();
        $inGrid   = $this->getEditedInGrid();
        $required = $this->getIsRequiredValueEdit();
        
        /** @var $elementRenderer BL_CustomGrid_Widget_Grid_Editor_Form_Renderer_Element */
        $elementRenderer  = $this->getLayout()
            ->createBlock('customgrid/widget_grid_editor_form_renderer_element');
        
        /** @var $elementRenderer BL_CustomGrid_Widget_Grid_Editor_Form_Renderer_Fieldset */
        $fieldsetRenderer = $this->getLayout()
            ->createBlock('customgrid/widget_grid_editor_form_renderer_fieldset');
        
        /** @var $elementRenderer BL_CustomGrid_Widget_Grid_Editor_Form_Renderer_Fieldset_Element */
        $fieldsetElementRenderer = $this->getLayout()
            ->createBlock('customgrid/widget_grid_editor_form_renderer_fieldset_element');
        
        $elementRenderer->setEditedInGrid($inGrid)->setIsRequiredValueEdit($required);
        $fieldsetRenderer->setEditedInGrid($inGrid)->setIsRequiredValueEdit($required);
        $fieldsetElementRenderer->setEditedInGrid($inGrid)->setIsRequiredValueEdit($required);
        
        Varien_Data_Form::setElementRenderer($elementRenderer);
        Varien_Data_Form::setFieldsetRenderer($fieldsetRenderer);
        Varien_Data_Form::setFieldsetElementRenderer($fieldsetElementRenderer);
        
        return $returnValue;
    }
    
    protected function _toHtml()
    {
        $html = parent::_toHtml();
        
        if ($this->getEditedInGrid()) {
            $html .= '<input name="form_key" type="hidden" value="' . $this->getFormKey() . '" />';
        }
        
        return $html;
    }
    
    public function getFormId()
    {
        if (!$this->hasData('form_id')) {
            /** @var $helper Mage_Core_Helper_Data */
            $helper=  $this->helper('core');
            $this->setData('form_id', $helper->uniqHash('blcg_grid_editor_form'));
        }
        return $this->_getData('form_id');
    }
    
    protected function _initializeForm()
    {
        return new Varien_Data_Form(
            array(
                'id'             => $this->getFormId(),
                'method'         => 'post',
                'html_id_prefix' => $this->getFormId(),
                'use_container'  => true,
            )
        );
    }
    
    /**
     * Return whether the edited value is required
     * 
     * @return bool
     */
    abstract public function getIsRequiredValueEdit();
    
    /**
     * Return the current edit config
     * 
     * @return BL_CustomGrid_Model_Grid_Edit_Config
     */
    public function getEditConfig()
    {
        if (!($config = $this->_getData('edit_config')) instanceof BL_CustomGrid_Model_Grid_Edit_Config) {
            Mage::throwException($this->__('Invalid edit config'));
        }
        return $config;
    }
    
    /**
     * Return the current edited attribute
     * 
     * @return Mage_Eav_Model_Entity_Attribute
     */
    public function getEditedAttribute()
    {
        if (!($attribute = $this->_getData('edited_attribute')) instanceof Mage_Eav_Model_Entity_Attribute) {
            Mage::throwException($this->__('Invalid edited attribute'));
        }
        return $attribute;
    }
    
    /**
     * Return the current edited entity
     * 
     * @return Varien_Object
     */
    public function getEditedEntity()
    {
        if (!($entity = $this->_getData('edited_entity')) instanceof Varien_Object) {
            Mage::throwException($this->__('Invalid edited entity'));
        }
        return $entity;
    }
}
