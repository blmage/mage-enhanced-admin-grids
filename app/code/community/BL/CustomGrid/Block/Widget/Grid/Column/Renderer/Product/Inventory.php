<?php

class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Product_Inventory
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    protected function _getValue(Varien_Object $row)
    {
        $useConfig = false;
        $fieldType = $this->getColumn()->getFieldType();
        
        if ($this->getColumn()->getCanUseConfig()) {
            if ($row->getData($this->getColumn()->getUseConfigIndex())) {
                $useConfig = true;
                
                if ($this->getColumn()->getFieldName() == 'min_sale_qty') {
                    $data = Mage::helper('cataloginventory/minsaleqty')
                        ->getConfigValue(Mage_Customer_Model_Group::CUST_GROUP_ALL);
                } else {
                    $data = Mage::getStoreConfig($this->getColumn()->getSystemConfigPath());
                }
            }
        }
        if (!$useConfig) {
            $data = $row->getData($this->getColumn()->getIndex());
        }
        if ($fieldType == 'boolean') {
            $data = Mage::helper('customgrid')->__($data ? 'Yes' : 'No');
        } elseif ($fieldType == 'decimal') {
            $data *= 1;
        } elseif (($fieldType == 'options')
                  && is_array($hash = $this->getColumn()->getOptionsHash())
                  && isset($hash[$data])) {
            $data = $hash[$data];
        }
        
        $data = strval($data);
        
        if ($useConfig) {
            if (($text = $this->getColumn()->getUseConfigPrefix()) !== '') {
                $data = $text . ' ' . $data;
            }
            if (($text = $this->getColumn()->getUseConfigSuffix()) !== '') {
                $data .= ' ' . $text;
            }
        }
        
        return $data;
    }
}