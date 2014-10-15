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

abstract class BL_CustomGrid_Block_Column_Renderer_Attribute_Abstract extends Mage_Adminhtml_Block_Template
{
    protected $_attributesCodes = null;
    
    protected function _getAttributes()
    {
        $attributes = array();
        
        if ($gridModel = $this->getGridModel()) {
            $attributes = $gridModel->getAvailableAttributes(true, true);
        }
        
        return $attributes;
    }
    
    public function getAttributesCodes()
    {
        if (is_null($this->_attributesCodes)) {
            $this->_attributesCodes = array();
            
            foreach ($this->_getAttributes() as $attribute) {
                $this->_attributesCodes[] = $attribute->getAttributeCode();
            }
        }
        return $this->_attributesCodes;
    }
    
    protected function _getAvailableRenderers($withEmpty = false)
    {
        return Mage::getSingleton('customgrid/column_renderer_config_attribute')->getRenderersArray($withEmpty);
    }
}
