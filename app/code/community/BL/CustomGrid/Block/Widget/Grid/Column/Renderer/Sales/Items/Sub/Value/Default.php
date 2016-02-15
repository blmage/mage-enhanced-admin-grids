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

class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Sales_Items_Sub_Value_Default extends BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Sales_Items_Sub_Abstract
{
    /**
     * Self render callbacks based on value key
     * 
     * @var string[]
     */
    static protected $_renderMethods = array(
        'name'            => '_renderItemName',
        'sku'             => '_renderItemSku',
        'quantity'        => '_renderItemQty',
        'qty_ordered'     => '_renderItemSingleQty',
        'qty_canceled'    => '_renderItemSingleQty',
        'qty_invoiced'    => '_renderItemSingleQty',
        'qty_shipped'     => '_renderItemSingleQty',
        'qty_refunded'    => '_renderItemSingleQty',
        'original_price'  => '_renderItemPrice',
        'tax_amount'      => '_renderItemPrice',
        'discount_amount' => '_renderItemPrice',
        'tax_percent'     => '_renderItemTaxPercent',
        'row_total'       => '_renderItemRowTotal',
    );
    
    /**
     * Render the name of the current item
     * 
     * @return string
     */
    protected function _renderItemName()
    {
        return $this->htmlEscape($this->getItem()->getName());
    }
    
    /**
     * Render the SKU of the current item
     * 
     * @return string
     */
    protected function _renderItemSku()
    {
        return implode('<br />', $this->helper('catalog')->splitSku($this->htmlEscape($this->getItem()->getSku())));
    }
    
    /**
     * Render the full quantities informations of the current item
     * 
     * @return string
     */
    protected function _renderItemQty()
    {
        return $this->getValue()->hasOrder()
            ? $this->getItemRenderer()->getColumnHtml($this->getItem(), 'qty')
            : $this->_renderItemSingleQty();
    }
    
    /**
     * Render the current quantity value of the current item
     * 
     * @return string
     */
    protected function _renderItemSingleQty()
    {
        $code = $this->getCode();
        return $this->getItem()->getDataUsingMethod($code == 'quantity' ? 'qty' : $code) * 1;
    }
    
    /**
     * Render the current price value of the current item
     * 
     * @return string
     */
    protected function _renderItemPrice()
    {
        return $this->getItemRenderer()->displayPriceAttribute($this->getCode());
    }
    
    /**
     * Render the tax percent of the current item
     * 
     * @return string
     */
    protected function _renderItemTaxPercent()
    {
        return $this->getItemRenderer()->displayTaxPercent($this->getItem());
    }
    
    /**
     * Render the row total of the current item
     * 
     * @return string
     */
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
