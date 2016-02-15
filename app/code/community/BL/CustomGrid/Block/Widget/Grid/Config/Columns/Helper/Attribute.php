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

class BL_CustomGrid_Block_Widget_Grid_Config_Columns_Helper_Attribute extends Mage_Adminhtml_Block_Template
{
    /**
     * Available attributes codes
     * 
     * @var string[]
     */
    protected $_attributesCodes = null;
    
    protected function _toHtml()
    {
        $html = parent::_toHtml();
        
        if ($this->getOutputAsJs()) {
            /** @var $helper BL_CustomGrid_Helper_Js */
            $helper = $this->helper('customgrid/js');
            $html   = $helper->prepareHtmlForJsOutput($html, true);
        }
        
        return $html;
    }
    
    /**
     * Return core helper
     * 
     * @return Mage_Core_Helper_Data
     */
    protected function _getCoreHelper()
    {
        return $this->helper('core');
    }
    
    /**
     * Return the available attributes, with renderer codes and editable flags
     * 
     * @return Mage_Eav_Model_Entity_Attribute[]
     */
    public function getAttributes()
    {
        $attributes = array();
        
        if ($gridModel = $this->getGridModel()) {
            /** @var $gridModel BL_CustomGrid_Model_Grid */
            $attributes = $gridModel->getAvailableAttributes(true, true);
        }
        
        return $attributes;
    }
    
    /**
     * Return the codes of the available attributes
     * 
     * @return string[]
     */
    public function getAttributesCodes()
    {
        if (is_null($this->_attributesCodes)) {
            $this->_attributesCodes = array();
            
            foreach ($this->getAttributes() as $attribute) {
                $this->_attributesCodes[] = $attribute->getAttributeCode();
            }
        }
        return $this->_attributesCodes;
    }
    
    /**
     * Return the base HTML ID to use for the elements
     * 
     * @return string
     */
    public function getBaseHtmlId()
    {
        if (!$this->hasData('base_html_id')) {
            $this->setData('base_html_id', $this->_getCoreHelper()->uniqHash('blcgARS'));
        }
        return $this->_getData('base_html_id');
    }
    
    /**
     * Return the HTML ID of the attributes select
     * 
     * @return string
     */
    public function getAttributeSelectHtmlId()
    {
        if (!$this->hasData('attribute_select_html_id')) {
            $this->setData('attribute_select_html_id', $this->getBaseHtmlId() . '-attribute-select');
        }
        return $this->_getData('attribute_select_html_id');
    }
    
    /**
     * Return the JSON config for the attributes
     * 
     * @return string
     */
    public function getAttributesJsonConfig()
    {
        $config = array();
        
        foreach ($this->getAttributes() as $attribute) {
            $config[] = array(
                'code' => $attribute->getAttributeCode(),
                'isEditable'   => (bool) $attribute->getIsEditable(),
                'rendererCode' => $attribute->getRendererCode(),
            );
        }
        
        return $this->_getCoreHelper()->jsonEncode($config);
    }
    
    /**
     * Return the JSON config for the editability values
     * 
     * @return string
     */
    public function getEditableJsonConfig()
    {
        return $this->_getCoreHelper()
            ->jsonEncode(
                array(
                    'editableContainerId' => $this->_getData('editable_container_html_id'),
                    'editableCheckboxId'  => $this->_getData('editable_checkbox_html_id'),
                )
            );
    }
    
    /**
     * Return the config model for attribute column renderers
     * 
     * @return BL_CustomGrid_Model_Column_Renderer_Config_Attribute
     */
    protected function _getRendererConfig()
    {
        return Mage::getSingleton('customgrid/column_renderer_config_attribute');
    }
    
    /**
     * Return the attribute renderers models
     * 
     * @param bool $sorted Whether the models should be sorted
     * @return BL_CustomGrid_Model_Column_Renderer_Attribute_Abstract[]
     */
    public function getRenderers($sorted = false)
    {
        return $this->_getRendererConfig()->getRenderersModels($sorted);
    }
    
    /**
     * Return the encoded parameters for the initial column renderer
     * 
     * @return string
     */
    public function getRendererParams()
    {
        if ($this->getAttributeCode() && ($params = $this->_getData('renderer_params'))) {
            /** @var $helper BL_CustomGrid_Helper_String */
            $helper = $this->helper('customgrid/string');
            return $helper->htmlDoubleEscape($params);
        }
        return '';
    }
    
    /**
     * Return the HTML ID of the renderer config button
     * 
     * @return string
     */
    public function getRendererConfigButtonHtmlId()
    {
        if (!$this->hasData('renderer_config_button_html_id')) {
            $this->setData('renderer_config_button_html_id', $this->getBaseHtmlId() . '-config-button');
        }
        return $this->_getData('renderer_config_button_html_id');
    }
    
    /**
     * Return the HTML ID of the target element for the renderer values
     * 
     * @return string
     */
    public function getRendererTargetHtmlId()
    {
        if (!$this->hasData('renderer_target_html_id')) {
            $this->setData('renderer_target_html_id', $this->getBaseHtmlId() . '-renderer-params');
        }
        return $this->_getData('renderer_target_html_id');
    }
    
    /**
     * Return the JSON config for the column renderers
     * 
     * @return string
     */
    public function getRenderersJsonConfig()
    {
        $config = array();
        
        foreach ($this->getRenderers() as $renderer) {
            $values = array(
                'code' => $renderer->getCode(),
                'isCustomizable' => $renderer->isCustomizable(),
            );
            
            if ($renderer->hasConfigWindow()) {
                $values['windowConfig'] = array(
                    'width'  => $renderer->getData('config_window/width'),
                    'height' => $renderer->getData('config_window/height'),
                    'title'  => $this->htmlEscape($renderer->getData('config_window/title')),
                );
            }
            
            $config[] = $values;
        }
        
        return $this->_getCoreHelper()->jsonEncode($config);
    }
    
    /**
     * Return the URL of the renderer config form
     * 
     * @return string
     */
    public function getRendererConfigUrl()
    {
        return $this->getUrl('adminhtml/blcg_column_renderer_attribute');
    }
    
    /**
     * Return the name of the attributes selects manager JS object
     * 
     * @return string
     */
    public function getSelectsManagerJsObjectName()
    {
        if (!$this->hasData('selects_manager_js_object_name')) {
            $this->setData('selects_manager_js_object_name', $this->_getCoreHelper()->uniqHash('blcgASM'));
        }
        return $this->_getData('selects_manager_js_object_name');
    }
}
