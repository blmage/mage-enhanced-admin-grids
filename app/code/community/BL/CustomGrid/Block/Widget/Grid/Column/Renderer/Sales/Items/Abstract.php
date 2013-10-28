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
 * @copyright  Copyright (c) 2013 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

abstract class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Sales_Items_Abstract
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    protected $_itemsBlock = null;
    
    abstract protected function _getItemsBlockType();
    abstract protected function _getActionLayoutHandle();
    abstract protected function _getItemsBlockLayoutName();
    abstract protected function _getItemsBlockDefaultTemplate();
    
    protected function _getNeedItemsBlockTemplate()
    {
        return true;
    }
    
    protected function _getNeedItemsBlockInitSuccess()
    {
        return true;
    }
    
    protected function _getItemsBlock()
    {
        if (is_null($this->_itemsBlock)) {
            try {
                $layout = $this->getLayout();
                $this->_itemsBlock = $layout->createBlock($this->_getItemsBlockType());
                $this->_itemsBlock->setParentBlock($this);
                $blockName = $this->_getItemsBlockLayoutName();
                
                // Copy as much as possible the behaviour from the original items block
                $update = Mage::getModel('core/layout_update')
                    ->load($this->_getActionLayoutHandle())
                    ->asSimplexml();
                
                if ($this->_getNeedItemsBlockTemplate()) {
                    if (is_array($blockNodes = $update->xpath("//block[@name='".$blockName."']"))
                        && ($blockNode = array_shift($blockNodes))
                        && ($template = (string)$blockNode['template'])) {
                        $this->_itemsBlock->setTemplate($template);
                    } else {
                        // Use base template as default
                        $this->_itemsBlock->setTemplate($this->_getItemsBlockDefaultTemplate());
                    }
                }
                
                // Retrieve and use all actions applied to the base block (most commonly, "addColumnRender" and "addItemRender")
                $fakeParent = new Varien_Object(array('block_name' => $this->_itemsBlock->getNameInLayout()));
                $actionNodes = $update->xpath("//block[@name='".$blockName."']//action|//reference[@name='".$blockName."']//action");
                
                $layoutReflection = new ReflectionClass($layout);
                $generateAction   = $layoutReflection->getMethod('_generateAction');
                
                if (!method_exists($generateAction, 'setAccessible')) {
                    // PHP < 5.3.2
                    $generateAction = Mage::getSingleton('customgrid/reflection_method_core_layout_generateaction');
                } else {
                    $generateAction->setAccessible(true);
                }
                
                foreach ($actionNodes as $actionNode) {
                    $generateAction->invoke($layout, $actionNode, $fakeParent);
                }
                
                $this->setItemsBlockInitSuccess(true);
                
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_itemsBlock = false;
                $this->setItemsBlockInitSuccess(false);
            }
        }
        return $this->_itemsBlock;
    }
    
    public function setColumn($column)
    {
        $this->_getItemsBlock();
        return parent::setColumn($column);
    }
    
    protected function _prepareItemsBlock(Varien_Object $row)
    {
        return $this;
    }
    
    protected function _render(Varien_Object $row)
    {
        return $this->_getItemsBlock()->toHtml();
    }
    
    public function render(Varien_Object $row)
    {
        if (($this->getItemsBlockInitSuccess()
             || !$this->_getNeedItemsBlockInitSuccess())
            && $this->_getItemsBlock()) {
            $this->_prepareItemsBlock($row);
            return $this->_render($row);
        }
        return $this->helper('customgrid')->__('An error occured while initializing items block');
    }
    
    public function renderExport(Varien_Object $row)
    {
        return '';
    }
}