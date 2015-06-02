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

class BL_CustomGrid_Block_Widget_Grid_Config_Columns_Helper_Collection extends Mage_Adminhtml_Block_Template
{
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
     * Return the config model for collection column renderers
     * 
     * @return BL_CustomGrid_Model_Column_Renderer_Config_Collection
     */
    protected function _getRendererConfig()
    {
        return Mage::getSingleton('customgrid/column_renderer_config_collection');
    }
    
    /**
     * Return the collection renderers models
     * 
     * @param bool $sorted Whether the models should be sorted
     * @return BL_CustomGrid_Model_Column_Renderer_Collection_Abstract[]
     */
    public function getRenderers($sorted = false)
    {
        return $this->_getRendererConfig()->getRenderersModels($sorted);
    }
    
    /**
     * Return the collection renderer model corresponding to the given code
     * 
     * @param string $code Renderer code
     * @return BL_CustomGrid_Model_Column_Renderer_Collection_Abstract|null
     */
    public function getRendererByCode($code)
    {
        return $this->_getRendererConfig()->getRendererModelByCode($code);
    }
    
    /**
     * Return the initially selected column renderer
     * 
     * @return BL_CustomGrid_Model_Column_Renderer_Collection_Abstract|null
     */
    public function getRenderer()
    {
        return (($code = $this->getRendererCode()) && ($renderer = $this->getRendererByCode($code)))
            ? $renderer
            : null; 
    }
    
    /**
     * Return the encoded parameters for the initially selected column renderer
     * 
     * @return string
     */
    public function getRendererParams()
    {
        if ($this->getRenderer() && ($params = $this->_getData('renderer_params'))) {
            /** @var $helper BL_CustomGrid_Helper_String */
            $helper = $this->helper('customgrid/string');
            return $helper->htmlDoubleEscape($params);
        }
        return '';
    }
    
    /**
     * Return the base HTML ID to use for the elements
     * 
     * @return string
     */
    public function getBaseHtmlId()
    {
        if (!$this->hasData('base_html_id')) {
            $this->setData('base_html_id', $this->_getCoreHelper()->uniqHash('blcgCRS'));
        }
        return $this->_getData('base_html_id');
    }
    
    /**
     * Return the HTML ID of the renderers select
     * 
     * @return string
     */
    public function getRendererSelectHtmlId()
    {
        if (!$this->hasData('renderer_select_html_id')) {
            $this->setData('renderer_select_html_id', $this->getBaseHtmlId() . '-renderer-select');
        }
        return $this->_getData('renderer_select_html_id');
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
        $renderers = $this->getRenderers();
        
        foreach ($renderers as $renderer) {
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
        return $this->getUrl('customgrid/column_renderer_collection');
    }
    
    /**
     * Return the name of the collection renderers selects manager JS object
     * 
     * @return string
     */
    public function getSelectsManagerJsObjectName()
    {
        if (!$this->hasData('selects_manager_js_object_name')) {
            $this->setData('selects_manager_js_object_name', $this->_getCoreHelper()->uniqHash('blcgCRSM'));
        }
        return $this->_getData('selects_manager_js_object_name');
    }
}
