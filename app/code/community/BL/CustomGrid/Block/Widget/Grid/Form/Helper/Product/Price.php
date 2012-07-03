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
 * @copyright  Copyright (c) 2012 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Block_Widget_Grid_Form_Helper_Product_Price
    extends Mage_Adminhtml_Block_Catalog_Product_Helper_Form_Price
{
    protected function getTaxObservingSpanId()
    {
        return 'dynamic-tax-'.$this->getHtmlId();
    }
    
    public function getStore()
    {
        if ($attribute = $this->getEntityAttribute()) {
            if (!($storeId = $attribute->getStoreId())) {
                $storeId = $this->getForm()->getDataObject()->getStoreId();
            }
            return Mage::app()->getStore($storeId);
        }
        return null;
    }
    
    public function getAfterElementHtml()
    {
        $html = Varien_Data_Form_Element_Text::getAfterElementHtml();
        $addJsObserver = false;
        
        if ($attribute = $this->getEntityAttribute()) {
            $store = $this->getStore();
            $html .= '<strong>['.(string)$store->getBaseCurrencyCode().']</strong>';
            if (Mage::helper('tax')->priceIncludesTax($store)) {
                if ($attribute->getAttributeCode() !== 'cost') {
                    $addJsObserver = true;
                    $html.= ' <strong>['.Mage::helper('tax')->__('Inc. Tax').'<span id="'.$this->getTaxObservingSpanId().'"></span>]</strong>';
                }
            }
        }
        if ($addJsObserver) {
            $html .= $this->_getTaxObservingCode($attribute);
        }
        
        return $html;
    }
    
    protected function _getTaxObservingCode($attribute)
    {
        $taxHelper   = Mage::helper('tax');
        $taxRates    = $taxHelper->getAllRatesByProductClass($this->getStore());
        $taxClassId  = $this->getForm()->getDataObject()->getTaxClassId();
        
        if (Mage::helper('customgrid')->isMageVersionGreaterThan(1, 5)) {
            $priceFormat = $taxHelper->getPriceFormat($this->getStore());
        } else {
            $priceFormat = $taxHelper->getPriceFormat();
        }
        
        $html = array();
        
        $recalculateFunction = Mage::helper('core')->uniqHash('recalculateTax');
        $html[] = '<script type="text/javascript">';
        $html[] = '//<![CDATA[';
        $html[] = 'function '.$recalculateFunction.'(){';
        $html[] = 'var span  = $("'.$this->getTaxObservingSpanId().'");';
        $html[] = 'var input = $("'.$this->getHtmlId().'");';
        $html[] = 'if (!input.value) {';
        $html[] = 'span.innerHTML = "";';
        $html[] = 'return;';
        $html[] = '}';
        $html[] = 'var priceFormat = '.$priceFormat.';';
        $html[] = 'var rates = '.$taxRates.', rate=0;';
        $html[] = 'var rate = 0;';
        $html[] = 'eval("var value = rates.value_'.$taxClassId.'");';
        $html[] = 'if (value != undefined) {';
        $html[] = 'rate = value;';
        $html[] = '}';
        $html[] = 'var spanValue = "";';
        $html[] = 'if (rate != 0) {';
        $html[] = 'spanValue = " " + formatCurrency(input.value/(100+rate)*rate, priceFormat);';
        $html[] = '}';
        $html[] = 'span.innerHTML = spanValue;';
        $html[] = '}';
        $html[] = $recalculateFunction.'();';
        $html[] = 'Event.observe($("'.$this->getHtmlId().'"), "change", '.$recalculateFunction.');';
        $html[] = '//]]>';
        $html[] = '</script>';
        
        return implode("\n", $html);
    }
}