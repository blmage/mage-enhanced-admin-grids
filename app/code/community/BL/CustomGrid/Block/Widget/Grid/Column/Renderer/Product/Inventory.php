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

class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Product_Inventory extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Return whether the given row uses config values for the current column,
     * and the corresponding data if appropriate
     * 
     * @param Varien_Object $row Grid row
     * @return array
     */
    protected function _getUseConfigData(Varien_Object $row)
    {
        $data = null;
        $useConfig = false;
        
        if ($this->getColumn()->getCanUseConfig()) {
            if ($row->getData($this->getColumn()->getUseConfigIndex())) {
                $useConfig = true;
                
                if ($this->getColumn()->getFieldName() == 'min_sale_qty') {
                    /** @var $helper Mage_CatalogInventory_Helper_Minsaleqty */
                    $helper = $this->helper('cataloginventory/minsaleqty');
                    $data   = $helper->getConfigValue(Mage_Customer_Model_Group::CUST_GROUP_ALL);
                } else {
                    $data = Mage::getStoreConfig($this->getColumn()->getSystemConfigPath());
                }
            }
        }
        
        return array($useConfig, $data);
    }
    
    /**
     * Render the given data that uses config values
     * 
     * @param mixed $data Data from config
     * @return string
     */
    protected function _renderUseConfigData($data)
    {
        if (($text = $this->getColumn()->getUseConfigPrefix()) !== '') {
            $data = $text . ' ' . $data;
        }
        if (($text = $this->getColumn()->getUseConfigSuffix()) !== '') {
            $data .= ' ' . $text;
        }
        return $data;
    }
    
    protected function _getValue(Varien_Object $row)
    {
        $fieldType = $this->getColumn()->getFieldType();
        list($useConfig, $data) = $this->_getUseConfigData($row);
        
        if (!$useConfig) {
            $data = $row->getData($this->getColumn()->getIndex());
        }
        if ($fieldType == 'boolean') {
            $data = $this->__($data ? 'Yes' : 'No');
        } elseif ($fieldType == 'decimal') {
            $data *= 1;
        } elseif (($fieldType == 'options')
            && is_array($hash = $this->getColumn()->getOptionHash())
            && isset($hash[$data])) {
            $data = $hash[$data];
        }
        
        $data = strval($data);
        
        if ($useConfig) {
            $data = $this->_renderUseConfigData($data);
        }
        
        return $data;
    }
}
