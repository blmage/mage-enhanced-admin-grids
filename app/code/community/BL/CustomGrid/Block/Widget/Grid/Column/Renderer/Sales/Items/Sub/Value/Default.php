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

class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Sales_Items_Sub_Value_Default extends
    BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Sales_Items_Sub_Abstract
{
    static protected $_renderMethods = array(
        'name'            => '_renderItemName',
        'sku'             => '_renderItemSku',
        'quantity'        => '_renderItemQty',
        'original_price'  => '_renderItemPrice',
        'tax_amount'      => '_renderItemPrice',
        'discount_amount' => '_renderItemPrice',
        'tax_percent'     => '_renderItemTaxPercent',
        'row_total'       => '_renderItemRowTotal',
    );
    
    protected function _renderItemName()
    {
        return $this->htmlEscape($this->getItem()->getName());
    }
    
    protected function _renderItemSku()
    {
        return implode('<br />', $this->helper('catalog')->splitSku($this->htmlEscape($this->getItem()->getSku())));
    }
    
    protected function _renderItemQty()
    {
        return $this->getValue()->hasOrder()
            ? $this->getItemRenderer()->getColumnHtml($this->getItem(), 'qty')
            : $this->getItem()->getQty()*1;
    }
    
    protected function _renderItemPrice()
    {
        return $this->getItemRenderer()->displayPriceAttribute($this->getCode());
    }
    
    protected function _renderItemTaxPercent()
    {
        return $this->getItemRenderer()->displayTaxPercent($this->getItem());
    }
    
    protected function _renderItemRowTotal()
    {
        $item = $this->getItem();
        
        if ($this->helper('customgrid')->isMageVersionLesserThan(1, 6)) {
            $result = $this->getItemRenderer()
                ->displayPrices(
                    $item->getBaseRowTotal() - $item->getBaseDiscountAmount() + $item->getBaseTaxAmount()
                    + $item->getBaseWeeeTaxAppliedRowAmount(),
                    $item->getRowTotal() - $item->getDiscountAmount() + $item->getTaxAmount()
                    + $item->getWeeeTaxAppliedRowAmount()
                );
        } else {
            $result = $this->getItemRenderer()
                ->displayPrices(
                    $item->getBaseRowTotal() + $item->getBaseTaxAmount() + $item->getBaseHiddenTaxAmount()
                    + $item->getBaseWeeeTaxAppliedRowAmount() - $item->getBaseDiscountAmount(),
                    $item->getRowTotal() + $item->getTaxAmount() + $item->getHiddenTaxAmount()
                    + $item->getWeeeTaxAppliedRowAmount() - $item->getDiscountAmount()
                );
        }
        
        return $result;
    }
    
    public function render(Varien_Object $value)
    {
        $code   = $value->getData('item_value/code');
        $item   = $value->getItem();
        $result = '';
        $itemRenderer = $this->getItemRendererBlock();
        
        if ($code !== '') {
            if (isset(self::$_renderMethods[$code])) {
                $this->setData('code', $code)
                    ->setData('item', $item)
                    ->setData('item_renderer', $itemRenderer)
                    ->setData('value', $value);
                
                $result = call_user_func(array($this, self::$_renderMethods[$code]));
                
                $this->unsetData('code')
                    ->unsetData('item')
                    ->unsetData('item_renderer')
                    ->unsetData('value');
            } else {
                $result = $item->getDataUsingMethod($code);
            }
        }
        
        return $result;
    }
}
