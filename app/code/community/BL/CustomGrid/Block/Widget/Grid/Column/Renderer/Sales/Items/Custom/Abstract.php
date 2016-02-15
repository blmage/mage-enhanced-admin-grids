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

abstract class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Sales_Items_Custom_Abstract extends BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Sales_Items_Abstract
{
    const DEFAULT_ROW_RENDERER = 'customgrid/widget_grid_column_renderer_sales_items_sub_row_default';
    const DEFAULT_RESULT_RENDERER = 'customgrid/widget_grid_column_renderer_sales_items_sub_default';
    
    /**
     * Return the renderer block of the given type
     * 
     * @param string $type Renderer block type
     * @return BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Sales_Items_Sub_Interface
     */
    protected function _getRendererBlock($type)
    {
        $name  = 'blcg_wgcrsica_renderer_' . str_replace('/', '_', $type);
        $block = false;
        
        if (!$this->getData('failed_renderers/' . $name)) {
            $block = $this->getLayout()->getBlock($name);
            
            if (!$block) {
                try {
                    $block = $this->getLayout()->createBlock($type, $name);
                } catch (Exception $e) {
                    Mage::logException($e);
                }
                if (!$block instanceof BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Sales_Items_Sub_Interface) {
                    $failedRenderers = $this->getDataSetDefault('failed_renderers', array());
                    $failedRenderers[$name] = true;
                    $this->setData('failed_renderers', $failedRenderers);
                } else {
                    $block->setItemRendererBlock($this->_getItemsBlock());
                }
            }
        }
        
        return $block;
    }
    
    /**
     * Return the renderer block usable to render the item value found in the given renderable value
     * 
     * @param Varien_Object $value Renderable value
     * @return BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Sales_Items_Sub_Interface|false
     */
    protected function _getItemValueRendererBlock(Varien_Object $value)
    {
        $valueRenderer = false;
        $renderers = $value->getData('item_value/renderers');
        
        foreach ($renderers as $renderer) {
            if (($renderer = $this->_getRendererBlock($renderer)) && $renderer->canRender($value)) {
                $valueRenderer = $renderer;
                break;
            }
        }
        
        return $valueRenderer;
    }
    
    
    /**
     * Render the item value found in the given renderable value
     * 
     * @param Varien_Object $value Renderable value
     * @return string
     */
    protected function _renderItemValue(Varien_Object $value)
    {
        $result = '';
        
        if ($renderer = $this->_getItemValueRendererBlock($value)) {
            $result = $renderer->render($value);
        }
        
        return $result;
    }
    
    /**
     * Return the renderer block usable to render the item row found in the given renderable value
     * 
     * @param Varien_Object $value Renderable value
     * @return BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Sales_Items_Sub_Interface
     */
    protected function _getRowRendererBlock(Varien_Object $value)
    {
        return $this->_getRendererBlock(self::DEFAULT_ROW_RENDERER);
    }
    
    /**
     * Render the item row found in the given renderable value
     * 
     * @param Varien_Object $value Renderable value
     * @return string
     */
    protected function _renderRow(Varien_Object $value)
    {
        $result = '';
        
        if ($renderer = $this->_getRowRendererBlock($value)) {
            $valuesHtml = array();
            $itemValues = $this->getColumn()->getItemValues();
            
            foreach ($itemValues as $itemValue) {
                $value->setItemValue($itemValue);
                $valuesHtml[$itemValue['code']] = $this->_renderItemValue($value);
            }
            
            $value->unsItemValue()->setValuesHtml($valuesHtml);
            $result = $renderer->render($value);
            $value->unsValuesHtml();
        }
        
        return $result;
    }
    
    /**
     * Return the renderer block usable to render the whole items list for the given renderable value
     * 
     * @param Varien_Object $value Renderable value
     * @return BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Sales_Items_Sub_Interface
     */
    protected function _getResultRendererBlock(Varien_Object $value)
    {
        return $this->_getRendererBlock(self::DEFAULT_RESULT_RENDERER);
    }
    
    /**
     * Return the items collection for the given renderable value
     * 
     * @param Varien_Object $value Renderable value
     * @return Mage_Core_Model_Mysql4_Collection_Abstract
     */
    protected function _getItemsCollection(Varien_Object $value)
    {
        return $value->getData($this->_getRowKey())->getAllItems();
    }
    
    /**
     * Return whether the given item is a child item
     * 
     * @param Mage_Core_Model_Abstract $item Item to check
     * @return bool
     */
    protected function _isChildItem(Mage_Core_Model_Abstract $item)
    {
        return ((($orderItem = $item->getOrderItem()) && $orderItem->getParentItem()) || $item->getParentItem());
    }
    
    /**
     * Render the whole items list for the given renderable value
     * 
     * @param Varien_Object $value Renderable value
     * @return string
     */
    protected function _renderResult(Varien_Object $value)
    {
        $result = '';
        
        if ($renderer = $this->_getResultRendererBlock($value)) {
            $rowsHtml = array();
            $itemsCollection = $this->_getItemsCollection($value);
            
            foreach ($itemsCollection as $item) {
                if (!$this->_isChildItem($item)) {
                    $value->setItem($item);
                    $this->_getItemsBlock()->setPriceDataObject($item);
                    $rowsHtml[] = $this->_renderRow($value);
                }
            }
            
            $value->unsItem()->setRowsHtml($rowsHtml);
            $result = $renderer->render($value);
            $value->unsRowsHtml();
        }
        
        return $result;
    }
    
    /**
     * Return the data key that is used to store the current row in the value object containing the renderable value
     * 
     * @return string
     */
    abstract protected function _getRowKey();
    
    protected function _render(Varien_Object $row)
    {
        $result = '';
        
        if (is_array($itemValues = $this->getColumn()->getItemValues()) && !empty($itemValues)) {
            $value = new Varien_Object(
                array(
                    'item_values' => $itemValues,
                    'hide_header' => (bool) $this->getColumn()->getHideHeader(),
                    $this->_getRowKey() => $row,
                )
            );
            
            $result = $this->_renderResult($value);
        }
        
        return $result;
    }
}
