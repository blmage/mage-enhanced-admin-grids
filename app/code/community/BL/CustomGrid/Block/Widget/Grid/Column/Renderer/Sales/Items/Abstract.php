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

abstract class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Sales_Items_Abstract extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Return the layout handle of the action where the original items list is used,
     * from which to copy as much as possible
     * 
     * @return string
     */
    abstract protected function _getActionLayoutHandle();
    
    /**
     * Return the type of the items block
     * 
     * @return string
     */
    abstract protected function _getItemsBlockType();
    
    /**
     * Return the layout name of the items block
     * 
     * @return string
     */
    abstract protected function _getItemsBlockLayoutName();
    
    /**
     * Return the default template to use for the items block
     * 
     * @return string
     */
    abstract protected function _getItemsBlockDefaultTemplate();
    
    /**
     * Return whether a template is required for the items block
     * 
     * @return bool
     */
    protected function _getRequireItemsBlockTemplate()
    {
        return true;
    }
    
    /**
     * Return whether the initialization success of the items block is required
     * 
     * @return bool
     */
    protected function _getRequireItemsBlockInitSuccess()
    {
        return true;
    }
    
    /**
     * Return the items block
     * 
     * @return Mage_Core_Block_Abstract
     */
    protected function _getItemsBlock()
    {
        if (!$this->hasData('items_block')) {
            try {
                $layout = $this->getLayout();
                $itemsBlock = $layout->createBlock($this->_getItemsBlockType());
                $itemsBlock->setParentBlock($this);
                $blockName = $this->_getItemsBlockLayoutName();
                
                // Copy as much as possible the behaviour from the original items block
                /** @var $layoutUpdate Mage_Core_Model_Layout_Update */
                $layoutUpdate = Mage::getModel('core/layout_update');
                $layoutUpdate->load($this->_getActionLayoutHandle());
                $layoutUpdateXml = $layoutUpdate->asSimplexml();
                
                if ($this->_getRequireItemsBlockTemplate()) {
                    if (is_array($blockNodes = $layoutUpdateXml->xpath("//block[@name='" . $blockName . "']"))
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
                $fakeParent = new Varien_Object(array('block_name' => $itemsBlock->getNameInLayout()));
                
                $actionNodes = $layoutUpdateXml->xpath(
                    '//block[@name=\'' . $blockName . '\']//action|//reference[@name=\'' . $blockName . '\']//action'
                );
                
                /** @var $reflectionHelper BL_CustomGrid_Helper_Reflection */
                $reflectionHelper = $this->helper('customgrid/reflection');
                $generateAction   = $reflectionHelper->getModelReflectionMethod('core/layout::_generateAction');
                
                foreach ($actionNodes as $actionNode) {
                    $generateAction->invoke($layout, $actionNode, $fakeParent);
                }
                
                $this->setData('items_block', $itemsBlock);
                $this->setItemsBlockInitSuccess(true);
                
            } catch (Exception $e) {
                Mage::logException($e);
                $this->setData('items_block', false);
                $this->setItemsBlockInitSuccess(false);
                
                /** @var $session BL_CustomGrid_Model_Session */
                $session = Mage::getSingleton('customgrid/session');
                $session->addError($this->helper('customgrid')->__('An error occurred while initializing items block'));
            }
        }
        return $this->_getData('items_block');
    }
    
    public function setColumn($column)
    {
        $this->_getItemsBlock();
        return parent::setColumn($column);
    }
    
    /**
     * Prepare the items block for the given grid row
     * 
     * @param Varien_Object $row Grid row
     * @return BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Sales_Items_Abstract
     */
    protected function _prepareItemsBlock(Varien_Object $row)
    {
        return $this;
    }
    
    /**
     * Render the items block for the given grid row
     * 
     * @param Varien_Object $row Grid row
     * @return string
     */
    protected function _render(Varien_Object $row)
    {
        return $this->_getItemsBlock()->toHtml();
    }
    
    public function render(Varien_Object $row)
    {
        if (!$row->getData('_blcg_items_init_error')) {
            if (($this->getItemsBlockInitSuccess() || !$this->_getRequireItemsBlockInitSuccess())
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
