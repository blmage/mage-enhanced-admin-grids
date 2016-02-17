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
        $isInGrid    = $this->getIsEditedInGrid();
        $isRequired  = $this->getIsRequiredValueEdit();
        
        /** @var $elementRenderer BL_CustomGrid_Widget_Grid_Editor_Form_Renderer_Element */
        $elementRenderer  = $this->getLayout()
            ->createBlock('customgrid/widget_grid_editor_form_renderer_element');
        
        /** @var $elementRenderer BL_CustomGrid_Widget_Grid_Editor_Form_Renderer_Fieldset */
        $fieldsetRenderer = $this->getLayout()
            ->createBlock('customgrid/widget_grid_editor_form_renderer_fieldset');
        
        /** @var $elementRenderer BL_CustomGrid_Widget_Grid_Editor_Form_Renderer_Fieldset_Element */
        $fieldsetElementRenderer = $this->getLayout()
            ->createBlock('customgrid/widget_grid_editor_form_renderer_fieldset_element');
        
        $elementRenderer->setIsEditedInGrid($isInGrid)->setIsRequiredValueEdit($isRequired);
        $fieldsetRenderer->setIsEditedInGrid($isInGrid)->setIsRequiredValueEdit($isRequired);
        $fieldsetElementRenderer->setIsEditedInGrid($isInGrid)->setIsRequiredValueEdit($isRequired);
        
        Varien_Data_Form::setElementRenderer($elementRenderer);
        Varien_Data_Form::setFieldsetRenderer($fieldsetRenderer);
        Varien_Data_Form::setFieldsetElementRenderer($fieldsetElementRenderer);
        
        return $returnValue;
    }
    
    protected function _toHtml()
    {
        $html = parent::_toHtml();
        
        if ($this->getIsEditedInGrid()) {
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
     * Return the editor context
     * 
     * @return BL_CustomGrid_Model_Grid_Editor_Context
     */
    public function getEditorContext()
    {
        if (!($editorContext = $this->_getData('editor_context')) instanceof BL_CustomGrid_Model_Grid_Editor_Context) {
            Mage::throwException('Invalid editor context');
        }
        return $editorContext;
    }
    
    /**
     * Return the edited value config
     * 
     * @return BL_CustomGrid_Model_Grid_Editor_Value_Config
     */
    public function getValueConfig()
    {
        if (!($valueConfig = $this->_getData('value_config')) instanceof BL_CustomGrid_Model_Grid_Editor_Value_Config) {
            Mage::throwException('Invalid edited value config');
        }
        return $valueConfig;
    }
    
    /**
     * Return the attribute on which is based the edited value
     * 
     * @return Mage_Eav_Model_Entity_Attribute
     */
    public function getEditedAttribute()
    {
        if (!($attribute = $this->_getData('edited_attribute')) instanceof Mage_Eav_Model_Entity_Attribute) {
            Mage::throwException('Invalid edited attribute');
        }
        return $attribute;
    }
    
    /**
     * Return the edited entity
     * 
     * @return Varien_Object
     */
    public function getEditedEntity()
    {
        if (!($entity = $this->_getData('edited_entity')) instanceof Varien_Object) {
            Mage::throwException('Invalid edited entity');
        }
        return $entity;
    }
    
    /**
     * Return a base fieldset legend from the given field label
     * 
     * @param string $fieldLabel Edited field label
     * @param bool $isRequiredField Whether the edited field requires a value
     * @return string
     */
    public function getBaseFieldsetLegend($fieldLabel, $isRequiredField)
    {
        $fieldsetLegend = $this->__(
            '%s : %s',
            $this->getDataSetDefault('edited_entity_name', $this->__('Edited entity')),
            $fieldLabel
        );
    
        if ($isRequiredField) {
            $fieldsetLegend .= ' (<span class="blcg-editor-required-marker">' . $this->__('Required') . '</span>)';
        }
        
        return $fieldsetLegend;
    }
    
    /**
     * Return whether the edited value is required
     * 
     * @return bool
     */
    abstract public function getIsRequiredValueEdit();
}
