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
 * @copyright  Copyright (c) 2014 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Block_Widget_Grid_Editor_Form_Helper_Product_Price extends
    Mage_Adminhtml_Block_Catalog_Product_Helper_Form_Price
{
    protected function _getTaxHelper()
    {
        return Mage::helper('tax');
    }
    
    protected function getTaxObservingSpanId()
    {
        return 'dynamic-tax-' . $this->getHtmlId();
    }
    
    public function getStore()
    {
        if ($attribute = $this->getEntityAttribute()) {
            if (!$storeId = $attribute->getStoreId()) {
                $storeId = $this->getForm()->getDataObject()->getStoreId();
            }
            return Mage::app()->getStore($storeId);
        }
        return null;
    }
    
    public function getAfterElementHtml()
    {
        $html = Varien_Data_Form_Element_Text::getAfterElementHtml();
        
        if ($attribute = $this->getEntityAttribute()) {
            $store = $this->getStore();
            $html .= '<strong>[' . ((string) $store->getBaseCurrencyCode()) . ']</strong>';
            
            if ($this->_getTaxHelper()->priceIncludesTax($store)
                && ($attribute->getAttributeCode() !== 'cost')) {
                $html.= ' <strong>[' . $this->_getTaxHelper()->__('Inc. Tax')
                    . '<span id="' . $this->getTaxObservingSpanId() . '"></span>'
                    . ']</strong>'
                    . $this->_getTaxObservingCode($attribute);
            }
        }
        
        return $html;
    }
    
    protected function _getTaxObservingCode($attribute)
    {
        $taxHelper  = $this->_getTaxHelper();
        $taxRates   = $taxHelper->getAllRatesByProductClass($this->getStore());
        $taxClassId = $this->getForm()->getDataObject()->getTaxClassId();
        $recalculateFunction = Mage::helper('core')->uniqHash('recalculateTax');
        
        if (Mage::helper('customgrid')->isMageVersionGreaterThan(1, 5)) {
            $priceFormat = $taxHelper->getPriceFormat($this->getStore());
        } else {
            $priceFormat = $taxHelper->getPriceFormat();
        }
        
        return '
<script type="text/javascript">
//<![CDATA[
function ' . $recalculateFunction . '()
{
    var span  = $("' . $this->getTaxObservingSpanId() . '");
    var input = $("' . $this->getHtmlId() . '");
    var spanValue = "";
    
    if (!input.value) {
        span.innerHTML = "";
        return;
    }
    
    var priceFormat = ' . $priceFormat . ';
    var rates = ' . $taxRates . ';
    var rate = 0;
    eval("var value = rates.value_' . $taxClassId . '");
    
    if (value !== undefined) {
        rate = value;
    }
    if (rate != 0) {
        spanValue = " " + formatCurrency(input.value/(100+rate)*rate, priceFormat);
    }
    
    span.innerHTML = spanValue;
}
' . $recalculateFunction . '();
Event.observe($("' . $this->getHtmlId() . '"), "change", ' . $recalculateFunction . ');
//]]>
</script>';
    }
}
