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

class BL_CustomGrid_Block_Widget_Grid_Form_Static_Product_Inventory
    extends BL_CustomGrid_Block_Widget_Grid_Form_Static_Default
{
    protected function _getProductInventoryData($product, $field)
    {
        if ($product->getStockItem()) {
            return $product->getStockItem()->getDataUsingMethod($field);
        }
        return Mage::getStoreConfig(Mage_CatalogInventory_Model_Stock_Item::XML_PATH_ITEM . $field);
    }
    
    protected function _prepareAddedField($id, $type, $name, $editedConfig, $field)
    {
        if (isset($editedConfig['inventory_field'])) {
            if ($editedConfig['inventory_field'] == 'qty') {
                $entity   = $this->getEditedEntity();
                $qtyValue = $this->_getProductInventoryData($entity, 'qty')*1;
                
                 $field->setValue($qtyValue)
                    ->setAfterElementHtml(
                         $field->getAfterElementHtml()
                         . '<input type="hidden" name="'.$editedConfig['values_key'].'[original_inventory_qty]" value="'.$qtyValue.'" />'
                     );
            }
        }
        return parent::_prepareAddedField($id, $type, $name, $editedConfig, $field);
    }
    
    protected function _initFormValues()
    {
        return $this;
    }
}