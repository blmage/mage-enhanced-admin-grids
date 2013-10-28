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

class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Sales_Items_Sub_Value_Default
    extends BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Sales_Items_Sub_Abstract
{
    public function render(Varien_Object $value)
    {
        $code   = $value->getData('item_value/code');
        $item   = $value->getItem();
        $result = '';
        $itemRenderer = $this->getItemRendererBlock();
        
        if ($code !== '') {
            if ($code == 'name') {
                $result = $this->htmlEscape($item->getName());
                
            } elseif ($code == 'sku') {
                $result = implode('<br />', Mage::helper('catalog')->splitSku($this->htmlEscape($item->getSku())));
                
            } elseif ($code == 'quantity') {
                if ($value->hasOrder()) {
                    $result = $itemRenderer->getColumnHtml($item, 'qty');
                } else {
                    $result = $item->getQty()*1;
                }
                
            } elseif (($code == 'original_price')
                || ($code == 'tax_amount')
                || ($code == 'discount_amount')) {
                $result = $itemRenderer->displayPriceAttribute($code);
                
            } elseif ($code == 'tax_percent') {
                $result = $itemRenderer->displayTaxPercent($item);
                
            } elseif ($code == 'row_total') {
                if (Mage::helper('customgrid')->isMageVersionLesserThan(1, 6)) {
                    $result = $itemRenderer->displayPrices(
                        $item->getBaseRowTotal() - $item->getBaseDiscountAmount() + $item->getBaseTaxAmount() + $item->getBaseWeeeTaxAppliedRowAmount(),
                        $item->getRowTotal() - $item->getDiscountAmount() + $item->getTaxAmount() + $item->getWeeeTaxAppliedRowAmount()
                    );
                } else {
                    $result = $itemRenderer->displayPrices(
                        $item->getBaseRowTotal() + $item->getBaseTaxAmount() + $item->getBaseHiddenTaxAmount() + $item->getBaseWeeeTaxAppliedRowAmount() - $item->getBaseDiscountAmount(),
                        $item->getRowTotal() + $item->getTaxAmount() + $item->getHiddenTaxAmount() + $item->getWeeeTaxAppliedRowAmount() - $item->getDiscountAmount()
                    );
                    
                }
                
            } else {
                $result = $item->getDataUsingMethod($code);
            }
        }
        
        return $result;
    }
}