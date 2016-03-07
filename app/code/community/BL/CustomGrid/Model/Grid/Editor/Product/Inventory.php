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

class BL_CustomGrid_Model_Grid_Editor_Product_Inventory extends BL_CustomGrid_Model_Grid_Editor_Abstract
{
    const MAX_QTY_VALUE = 99999999.9999;
    const USE_CONFIG_VALUE = '__blcg_use_config__';
    
    public function getDefaultBaseCallbacks(BL_CustomGrid_Model_Grid_Editor_Callback_Manager $callbackManager)
    {
        return array(
            $callbackManager->getInternalMainCallbackFromCallable(
                array($this, 'checkContextValueEditability'),
                self::WORKER_TYPE_ENTITY_UPDATER,
                BL_CustomGrid_Model_Grid_Editor_Entity_Updater::ACTION_TYPE_CHECK_CONTEXT_VALUE_EDITABILITY
            ),
            $callbackManager->getInternalMainCallbackFromCallable(
                array($this, 'getContextUserEditedValue'),
                self::WORKER_TYPE_ENTITY_UPDATER,
                BL_CustomGrid_Model_Grid_Editor_Entity_Updater::ACTION_TYPE_GET_CONTEXT_USER_EDITED_VALUE
            ),
            $callbackManager->getInternalMainCallbackFromCallable(
                array($this, 'applyUserEditedValueToEditedProduct'),
                self::WORKER_TYPE_ENTITY_UPDATER,
                BL_CustomGrid_Model_Grid_Editor_Entity_Updater::ACTION_TYPE_APPLY_USER_EDITED_VALUE_TO_EDITED_ENTITY
            ),
            $callbackManager->getInternalMainCallbackFromCallable(
                array($this, 'saveContextEditedProduct'),
                self::WORKER_TYPE_ENTITY_UPDATER,
                BL_CustomGrid_Model_Grid_Editor_Entity_Updater::ACTION_TYPE_SAVE_CONTEXT_EDITED_ENTITY
            ),
            $callbackManager->getInternalMainCallbackFromCallable(
                array($this, 'getRenderableContextEditedValue'),
                self::WORKER_TYPE_VALUE_RENDERER,
                BL_CustomGrid_Model_Grid_Editor_Value_Renderer::ACTION_TYPE_GET_RENDERABLE_CONTEXT_EDITED_VALUE
            ),
        );
    }
    
    /**
     * Return the inventory helper
     * 
     * @return BL_CustomGrid_Helper_Catalog_Inventory
     */
    public function getInventoryHelper()
    {
        return Mage::helper('customgrid/catalog_inventory');
    }
    
    /**
     * Return the inventory field name from the given value config
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig Value config
     * @return string
     */
    protected function _getValueConfigInventoryFieldName(BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig)
    {
        return $valueConfig->getData('form_field/inventory_field');
    }
    
    /**
     * Return whether the given inventory field is editable for composite products
     * 
     * @param string $fieldName Inventory field name
     * @return bool
     */
    protected function _isFieldEditableForComposite($fieldName)
    {
        return in_array($fieldName, array('is_in_stock', 'qty_increments'));
    }
    
    /**
     * Return whether the given field is editable for the given product
     * in regards to the composite product limitations
     * 
     * @param Mage_Catalog_Model_Product $product Checked product
     * @param string $fieldName Inventory field name
     * @return bool
     */
    protected function _checkCompositeEditability(Mage_Catalog_Model_Product $product, $fieldName)
    {
        return (!$product->isComposite() || $this->_isFieldEditableForComposite($fieldName));
    }
    
    /**
     * Return whether the given inventory field depends on the value of the "Manage Stock" field
     * 
     * @param string $fieldName Inventory field name
     * @return bool
     */
    protected function _isManageStockDependentField($fieldName)
    {
        return in_array(
            $fieldName,
            array('backorders', 'is_in_stock', 'min_qty', 'notify_stock_qty', 'qty', 'qty_increments')
        );
    }
    
    /**
     * Return whether the given field is editable for the given product
     * in regards to the current value of the "Manage Stock" field
     *
     * @param Mage_Catalog_Model_Product $product Checked product
     * @param string $fieldName Inventory field name
     * @return bool
     */
    protected function _checkStockManagedEditability(Mage_Catalog_Model_Product $product, $fieldName)
    {
        return !$this->_isManageStockDependentField($fieldName)
            || $this->getInventoryHelper()->getProductActualInventoryValue($product, 'manage_stock');
    }
    
    /**
     * Return whether the given inventory field is editable for the given product
     *
     * @param Mage_Catalog_Model_Product $product Checked product
     * @param string $fieldName Edited field name
     * @return bool|string
     */
    protected function _checkContextFieldEditability(Mage_Catalog_Model_Product $product, $fieldName)
    {
        $result = true;
        
        /** @var Mage_Core_Helper_Data $coreHelper */
        $coreHelper = Mage::helper('core');
        $baseHelper = $this->getBaseHelper();
        $inventoryHelper = $this->getInventoryHelper();
        
        if (!$coreHelper->isModuleEnabled('Mage_CatalogInventory')) {
            $result = $baseHelper->__('The "Mage_CatalogInventory" module is disabled');
        } elseif ($product->getInventoryReadonly()) {
            $result = $baseHelper->__('The inventory fields are read-only for this product');
        } elseif (!$this->_checkCompositeEditability($product, $fieldName)) {
            $result = $baseHelper->__('This inventory field is not editable for composite products');
        } elseif (!$this->_checkStockManagedEditability($product, $fieldName)) {
            $result = $baseHelper->__('The stock is not managed for this product');
        } elseif (($fieldName == 'qty_increments')
            && !$inventoryHelper->getProductActualInventoryValue($product, 'enable_qty_increments')) {
            $result = $baseHelper->__('The quantity increments are not enabled for this product');
        }
        
        return $result;
    }
    
    /**
     * Callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Updater::isContextValueEditable()
     *
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @param mixed $previousReturnedValue Value returned by the previous callback
     * @return bool|string
     */
    public function checkContextValueEditability(
        BL_CustomGrid_Model_Grid_Editor_Context $context,
        $previousReturnedValue
    ) {
        $result = (!is_null($previousReturnedValue) ? $previousReturnedValue : true);
        
        if ($result === true) {
            $result = $this->_checkContextFieldEditability(
                $context->getEditedEntity(),
                $this->_getValueConfigInventoryFieldName($context->getValueConfig())
            );
        }
        
        return $result;
    }
    
    /**
     * Callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Updater::getContextUserEditedValue()
     *
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @param BL_CustomGrid_Object $transport Transport object used to hold the user value
     */
    public function getContextUserEditedValue(
        BL_CustomGrid_Model_Grid_Editor_Context $context,
        BL_CustomGrid_Object $transport
    ) {
        $inventoryHelper = $this->getInventoryHelper();
        $fieldName = $this->_getValueConfigInventoryFieldName($context->getValueConfig());
        
        if ($useConfigFieldName = $inventoryHelper->getBaseFieldUseConfigFieldName($fieldName)) {
            $valuesParams = $context->getRequestValuesParams();
            
            if (isset($valuesParams[$useConfigFieldName]) && ($valuesParams[$useConfigFieldName])) {
                $transport->setData('value', self::USE_CONFIG_VALUE);
            }
        }
    }
    
    /**
     * Apply the given quantity value to the given edited stock item
     * 
     * @param Mage_CatalogInventory_Model_Stock_Item $stockItem Edited stock item
     * @param float $qtyValue Quantity value
     * @param array $params Request values parameters
     */
    protected function _applyQtyToEditedStockItem(
        Mage_CatalogInventory_Model_Stock_Item $stockItem,
        $qtyValue,
        array $params
    ) {
        if (isset($params['original_inventory_qty'])
            && (strlen($params['original_inventory_qty']) > 0)) {
            $stockItem->setQtyCorrection($stockItem->getQty() - $params['original_inventory_qty']);
        }
        
        $qtyValue = (float) $qtyValue;
        $stockItem->setQty($qtyValue > self::MAX_QTY_VALUE ? self::MAX_QTY_VALUE : $qtyValue);
    }
    
    /**
     * Callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Updater::applyUserEditedValueToEditedEntity()
     *
     * @param Mage_Catalog_Model_Product $editedEntity Edited product
     * @param mixed $userValue User-defined value
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return bool
     */
    public function applyUserEditedValueToEditedProduct(
        Mage_Catalog_Model_Product $editedEntity,
        $userValue,
        BL_CustomGrid_Model_Grid_Editor_Context $context
    ) {
        $fieldName = $this->_getValueConfigInventoryFieldName($context->getValueConfig());
        $params = $context->getRequestValuesParams();
        $productId = $editedEntity->getId();
        
        /** @var Mage_CatalogInventory_Model_Stock_Item $stockItem */
        $stockItem = Mage::getModel('cataloginventory/stock_item');
        $stockItem->setData(array());
        $stockItem->loadByProduct($productId)->setProductId($productId);
        
        if ($fieldName == 'qty') {
            $this->_applyQtyToEditedStockItem($stockItem, $userValue, $params);
        } else {
            $inventoryHelper = $this->getInventoryHelper();
            
            if (($fieldName == 'min_qty') && ($userValue < 0)) {
                $userValue = 0;
            }
            
            $stockItem->setData($fieldName, $userValue);
            
            if ($useConfigFieldName = $inventoryHelper->getBaseFieldUseConfigFieldName($fieldName)) {
                if ($userValue === self::USE_CONFIG_VALUE) {
                    $stockItem->unsetData($fieldName);
                    $stockItem->setData($useConfigFieldName, true);
                } else {
                    $stockItem->setData($useConfigFieldName, false);
                }
            }
        }
        
        $editedEntity->setData('_blcg_gtp_stock_item', $stockItem);
        return true;
    }
    
    /**
     * Callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Updater::saveContextEditedProduct()
     *
     * @param Mage_Catalog_Model_Product $editedEntity Edited product
     * @return bool
     */
    public function saveContextEditedProduct(Mage_Catalog_Model_Product $editedEntity)
    {
        if ($stockItem = $editedEntity->getData('_blcg_gtp_stock_item')) {
            /** @var Mage_CatalogInventory_Model_Stock_Item $stockItem */
            $stockItem->save();
        }
        return true;
    }
    
    /**
     * Callback for @see BL_CustomGrid_Model_Grid_Editor_Value_Renderer::getRenderableContextEditedValue()
     *
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @param BL_CustomGrid_Object $transport Transport object used to hold the renderable value
     */
    public function getRenderableContextEditedValue(
        BL_CustomGrid_Model_Grid_Editor_Context $context,
        BL_CustomGrid_Object $transport
    ) {
        /** @var Mage_Catalog_Model_Product $editedEntity */
        $editedEntity = $context->getEditedEntity();
        
        if ($stockItem = $editedEntity->getStockItem()) {
            // Reload the edited stock item to get the updated stock value
            /** @var $stockItem Mage_CatalogInventory_Model_Stock_Item */
            $stockItem->setProductId(null)->assignProduct($editedEntity);
        }
        
        $inventoryHelper = $this->getInventoryHelper();
        $fieldName = $this->_getValueConfigInventoryFieldName($context->getValueConfig());
        $value = $inventoryHelper->getProductActualInventoryValue($editedEntity, $fieldName);
        $transport->setData('value', $inventoryHelper->prepareFormInventoryValue($fieldName, $value));
    }
}
