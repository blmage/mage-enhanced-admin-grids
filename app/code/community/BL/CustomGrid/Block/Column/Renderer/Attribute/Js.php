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

class BL_CustomGrid_Block_Column_Renderer_Attribute_Js extends BL_CustomGrid_Block_Column_Renderer_Attribute_Abstract
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('bl/customgrid/column/renderer/attribute/js.phtml');
    }
    
    public function getJsObjectName()
    {
        return $this->getDataSetDefault('js_object_name', $this->helper('core')->uniqHash('blcgARSM'));
    }
    
    public function getAttributesJsonConfig()
    {
        $config = array();
        
        foreach ($this->_getAttributes() as $attribute) {
            $config[] = array(
                'code' => $attribute->getAttributeCode(),
                'isEditable' => (bool) $attribute->getIsEditable(),
                'rendererCode' => $attribute->getRendererCode(),
            );
        }
        
        return $this->helper('core')->jsonEncode($config);
    }
    
    public function getRenderersJsonConfig($withEmpty=false)
    {
        $config = array();
        
        foreach ($this->_getAvailableRenderers($withEmpty) as $renderer) {
            $values = array(
                'code' => $renderer['code'],
                'isCustomizable' => $renderer['is_customizable'],
            );
            
            if (isset($renderer['config_window'])) {
                $values['windowConfig'] = array(
                    'width'  => $renderer['config_window']['width'],
                    'height' => $renderer['config_window']['height'],
                    'title'  => $this->htmlEscape($renderer['config_window']['title']),
                );
            }
            
            $config[] = $values;
        }
        
        return $this->helper('core')->jsonEncode($config);
    }
    
    public function getConfigUrl()
    {
        return $this->getUrl('customgrid/column_renderer_attribute');
    }
}
