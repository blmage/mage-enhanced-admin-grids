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
     * and the corresponding value if appropriate
     * 
     * @param Varien_Object $row Grid row
     * @return array
     */
    protected function _getUseConfigValue(Varien_Object $row)
    {
        $value = null;
        $useConfig = false;
        
        if ($this->getColumn()->getCanUseConfig()) {
            if ($row->getData($this->getColumn()->getUseConfigIndex())) {
                $useConfig = true;
                $fieldName = $this->getColumn()->getFieldName();
                
                if ($fieldName == 'min_sale_qty') {
                    /** @var Mage_CatalogInventory_Helper_Minsaleqty $helper */
                    $helper = Mage::helper('cataloginventory/minsaleqty');
                    $value  = $helper->getConfigValue(Mage_Customer_Model_Group::CUST_GROUP_ALL);
                } else {
                    /** @var BL_CustomGrid_Helper_Catalog_Inventory $helper */
                    $helper = Mage::helper('customgrid/catalog_inventory');
                    $value  = $helper->getDefaultConfigInventoryValue($fieldName);
                }
            }
        }
        
        return array($useConfig, $value);
    }
    
    /**
     * Render the given row value, assumed to be inherited from the system configuration
     * 
     * @param mixed $value Base value
     * @return string
     */
    protected function _renderUseConfigValue($value)
    {
        if (($text = $this->getColumn()->getUseConfigPrefix()) !== '') {
            $value = $text . ' ' . $value;
        }
        if (($text = $this->getColumn()->getUseConfigSuffix()) !== '') {
            $value = $value . ' ' . $text;
        }
        return $value;
    }
    
    /**
     * Return the renderable value corresponding to the given options-based raw value
     * 
     * @param mixed $value Raw value
     * @return mixed
     */
    protected function _getOptionsRenderableValue($value)
    {
        return (is_array($hash = $this->getColumn()->getOptionHash()) && isset($hash[$value]))
            ? $hash[$value]
            : $value;
    }
    
    protected function _getValue(Varien_Object $row)
    {
        $fieldType = $this->getColumn()->getFieldType();
        list($useConfig, $value) = $this->_getUseConfigValue($row);
        
        if (!$useConfig) {
            $value = $row->getData($this->getColumn()->getIndex());
        }
        
        if ($fieldType == 'boolean') {
            $value = $this->__($value ? 'Yes' : 'No');
        } elseif ($fieldType == 'decimal') {
            $value *= 1;
        } elseif ($fieldType == 'options') {
            $value = $this->_getOptionsRenderableValue($value);
        }
    
        $value = strval($value);
        
        if ($useConfig) {
            $value = $this->_renderUseConfigValue($value);
        }
        
        return $value;
    }
}
