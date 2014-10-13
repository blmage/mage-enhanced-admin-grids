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

class BL_CustomGrid_Block_Column_Renderer_Collection_Js extends BL_CustomGrid_Block_Column_Renderer_Collection_Abstract
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('bl/customgrid/column/renderer/collection/js.phtml');
    }
    
    public function getJsObjectName()
    {
        return $this->getDataSetDefault('js_object_name', $this->helper('core')->uniqHash('blcgCRSM'));
    }
    
    public function getConfigUrl()
    {
        return $this->getUrl('customgrid/column_renderer_collection');
    }
    
    public function getRenderersJsonConfig()
    {
        $config = array();
        $renderers = $this->_getAvailableRenderers();
        
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
        
        return $this->helper('core')->jsonEncode($config);
    }
}
