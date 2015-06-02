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

abstract class BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Abstract extends Mage_Adminhtml_Block_Abstract
{
    protected function _construct()
    {
        parent::_construct();
        $this->setDefaultValue($this->__('<em>Updated</em>'));
    }
    
    protected function _toHtml()
    {
        return $this->_getRenderedValue();
    }
    
    /**
     * Return the rendered value
     * 
     * @return string
     */
    abstract protected function _getRenderedValue();
    
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
