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
        return $this->_getRenderedValue($this->getRenderableValue());
    }
    
    /**
     * Return the rendered value
     * 
     * @param mixed $renderableValue Renderable value
     * @return string
     */
    abstract protected function _getRenderedValue($renderableValue);
    
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
}
