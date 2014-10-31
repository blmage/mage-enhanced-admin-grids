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

abstract class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Sales_Items_Abstract extends
    Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
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
        if (!$this->hasData('items_block')) {
            try {
                $layout = $this->getLayout();
                $itemsBlock = $layout->createBlock($this->_getItemsBlockType());
                $itemsBlock->setParentBlock($this);
                $blockName = $this->_getItemsBlockLayoutName();
                
                // Copy as much as possible the behaviour from the original items block
                $update = Mage::getModel('core/layout_update')
                    ->load($this->_getActionLayoutHandle())
                    ->asSimplexml();
                
                if ($this->_getNeedItemsBlockTemplate()) {
                    if (is_array($blockNodes = $update->xpath("//block[@name='" . $blockName . "']"))
                        && ($blockNode = array_shift($blockNodes))
                        && ($template = (string)$blockNode['template'])) {
                        $itemsBlock->setTemplate($template);
                    } else {
                        // Use base template as default
                        $itemsBlock->setTemplate($this->_getItemsBlockDefaultTemplate());
                    }
                }
                
                // Retrieve and use all actions applied to the base block
                // (most commonly, "addColumnRender" and "addItemRender")
                $fakeParent  = new Varien_Object(array('block_name' => $itemsBlock->getNameInLayout()));
                
                $actionNodes = $update->xpath(
                    '//block[@name=\'' . $blockName . '\']//action|//reference[@name=\'' . $blockName . '\']//action'
                );
                
                $generateAction = $this->helper('customgrid/reflection')
                    ->getModelReflectionMethod('core/layout::_generateAction');
                
                foreach ($actionNodes as $actionNode) {
                    $generateAction->invoke($layout, $actionNode, $fakeParent);
                }
                
                $this->setData('items_block', $itemsBlock);
                $this->setItemsBlockInitSuccess(true);
                
            } catch (Exception $e) {
                Mage::logException($e);
                $this->setData('items_block', false);
                $this->setItemsBlockInitSuccess(false);
                Mage::getSingleton('customgrid/session')
                    ->addError($this->helper('customgrid')->__('An error occured while initializing items block'));
            }
        }
        return $this->_getData('items_block');
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
        if (!$row->getData('_blcg_items_init_error')) {
            if (($this->getItemsBlockInitSuccess() || !$this->_getNeedItemsBlockInitSuccess())
                && $this->_getItemsBlock()) {
                $this->_prepareItemsBlock($row);
                return $this->_render($row);
            }
        }
        return '';
    }
    
    public function renderExport(Varien_Object $row)
    {
        return '';
    }
}
