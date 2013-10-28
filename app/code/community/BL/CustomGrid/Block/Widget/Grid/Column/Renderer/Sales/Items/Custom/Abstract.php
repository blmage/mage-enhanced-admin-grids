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

abstract class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Sales_Items_Custom_Abstract
    extends BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Sales_Items_Abstract
{
    const DEFAULT_ROW_RENDERER    = 'customgrid/widget_grid_column_renderer_sales_items_sub_row_default';
    const DEFAULT_RESULT_RENDERER = 'customgrid/widget_grid_column_renderer_sales_items_sub_default';
    
    protected function _getRendererBlock($type)
    {
        $name  = 'blcg_wgcrsica_renderer_'.str_replace('/', '_', $type);
        $block = false;
        
        if (!$this->getData('failed_renderers/'.$name)) {
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
    
    protected function _getItemValueRenderer($value)
    {
        $valueRenderer = false;
        $renderers = $value->getData('item_value/renderers');
        
        foreach ($renderers as $renderer) {
            if (($renderer = $this->_getRendererBlock($renderer))
                && $renderer->canRender($value)) {
                $valueRenderer = $renderer;
                break;
            }
        }
        
        return $valueRenderer;
    }
    
    protected function _renderValue($value)
    {
        $result = '';
        
        if ($renderer = $this->_getItemValueRenderer($value)) {
            $result = $renderer->render($value);
        }
        
        return $result;
    }
    
    protected function _getRowRenderer($value)
    {
        // @todo could allow to propose (and to make propose) different designs / styles
        return $this->_getRendererBlock(self::DEFAULT_ROW_RENDERER);
    }
    
    protected function _renderRow($value)
    {
        $result = '';
        
        if ($renderer = $this->_getRowRenderer($value)) {
            $valuesHtml = array();
            $itemValues = $this->getColumn()->getItemValues();
            
            foreach ($itemValues as $itemValue) {
                $value->setItemValue($itemValue);
                $valuesHtml[$itemValue['code']] = $this->_renderValue($value);
            }
            
            $value->unsItemValue()->setValuesHtml($valuesHtml);
            $result = $renderer->render($value);
            $value->unsValuesHtml();
        }
        
        return $result;
    }
    
    protected function _getResultRenderer($value)
    {
        // @todo same thing :)
        return $this->_getRendererBlock(self::DEFAULT_RESULT_RENDERER);
    }
    
    protected function _getItemsCollection($value)
    {
        return $value->getData($this->_getRowKey())->getAllItems();
    }
    
    protected function _isChildItem($item)
    {
        return ((($orderItem = $item->getOrderItem()) && $orderItem->getParentItem()) || $item->getParentItem());
    }
    
    protected function _renderResult($value)
    {
        $result = '';
        
        if ($renderer = $this->_getResultRenderer($value)) {
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
    
    abstract protected function _getRowKey();
    
    protected function _render(Varien_Object $row)
    {
        $result = '';
        
        if (is_array($itemValues = $this->getColumn()->getItemValues())
            && !empty($itemValues)) {
            $value = new Varien_Object(array(
                'item_values' => $itemValues,
                'hide_header' => (bool) $this->getColumn()->getHideHeader(),
                $this->_getRowKey() => $row,
            ));
            $result = $this->_renderResult($value);
        }
        
        return $result;
    }
}