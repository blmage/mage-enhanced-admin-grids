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
 * @copyright  Copyright (c) 2016 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_Grid_Type_Product extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    protected function _getSupportedBlockTypes()
    {
        return array(
            'adminhtml/catalog_product_grid',
            'adminhtml/catalog_category_tab_product',
            'adminhtml/catalog_product_edit_tab_related',
            'adminhtml/catalog_product_edit_tab_upsell',
            'adminhtml/catalog_product_edit_tab_crosssell',
            'adminhtml/sales_order_create_search_grid',
        );
    }
    
    public function canExport($blockType)
    {
        return (($blockType == 'adminhtml/catalog_product_grid') || !$this->isSupportedBlockType($blockType));
    }
    
    protected function _getColumnsLockedValues($blockType)
    {
        return array(
            'is_salable' => array(
                'renderer' => '',
                'config_values' => array(
                    'filter'   => false,
                    'sortable' => false
                ),
            ),
        );
    }
    
    public function canHaveAttributeColumns($blockType)
    {
        return true;
    }
    
    protected function _isAvailableAttribute($blockType, Mage_Eav_Model_Entity_Attribute $attribute)
    {
        if (parent::_isAvailableAttribute($blockType, $attribute)) {
            $excludedModels = array(
                'catalog/product_attribute_backend_media',
                'catalog/product_attribute_backend_recurring',
                'catalog/product_attribute_backend_tierprice',
            );
            
            return ($attribute->getFrontend()->getInputType() != 'gallery')
                && !in_array($attribute->getBackendModel(), $excludedModels);
        }
        return false;
    }
    
    protected function _getAvailableAttributes($blockType)
    {
        /** @var $productResource Mage_Catalog_Model_Resource_Eav_Mysql4_Product */
        $productResource = Mage::getResourceModel('catalog/product');
        $attributes = $productResource->loadAllAttributes()->getAttributesByCode();
        $availableAttributes = array();
        
        foreach ($attributes as $attribute) {
            /** @var $attribute Mage_Eav_Model_Entity_Attribute */
            if ($this->_isAvailableAttribute($blockType, $attribute)) {
                $availableAttributes[$attribute->getAttributeCode()] = $attribute;
            }
        }
        
        return $availableAttributes;
    }
    
    protected function _getEditableFields($blockType)
    {
        return array(
            'qty' => array(
                'type'            => 'text',
                'required'        => true,
                'render_reload'   => false,
                'form_class'      => 'validate-number',
                'edit_block_type' => 'customgrid/widget_grid_editor_form_static_product_inventory',
                'inventory_field' => 'qty',
            ),
        );
    }
    
    protected function _checkAttributeEditability($blockType, Mage_Eav_Model_Entity_Attribute $attribute)
    {
        return parent::_checkAttributeEditability($blockType, $attribute)
            && ($attribute->getFrontend()->getInputType() != 'media_image');
    }
    
    protected function _getAdditionalEditableAttributes($blockType)
    {
        /** @var $productResource Mage_Catalog_Model_Resource_Eav_Mysql4_Product */
        $productResource = Mage::getResourceModel('catalog/product');
        return array('sku' => $productResource->getAttribute('sku'));
    }
    
    protected function _prepareEditableAttributeCommonConfig(
        $blockType,
        $attributeCode,
        Mage_Eav_Model_Entity_Attribute $attribute,
        BL_CustomGrid_Model_Grid_Edit_Config $config
    ) {
        if ($attribute->getFrontendInput() == 'weight') {
            $config->setInGrid(true);
        }
        
        $config->addData(
            array(
                'edit_block_type' => 'product',
                'layout_handles'  => array(
                    'blcg_grid_editor_handle_editor',
                    'blcg_grid_editor_handle_product',
                ),
            )
        );
        
        return parent::_prepareEditableAttributeCommonConfig($blockType, $attributeCode, $attribute, $config);
    }
    
    protected function _getBaseEditableAttributeFields($blockType)
    {
        return array(
            'name' => array(
                'attribute' => 'name',
                'config'    => array(
                    'column_params' => array(
                        'column_store_id' => 0,
                    ),
                ),
            ),
            'custom_name' => array('attribute' => 'name'),
            'price'       => array('attribute' => 'price'),
            'sku'         => array('attribute' => 'sku'),
            'status'      => array('attribute' => 'status'),
            'visibility'  => array('attribute' => 'visibility'),
        );
    }
    
    public function getAdditionalEditParams($blockType, Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        return array('store_id' => $gridBlock->blcg_getStore()->getId());
    }
    
    protected function _getEntityRowIdentifiersKeys($blockType)
    {
        return array('entity_id');
    }
    
    /**
     * Return the ID of the default store
     * 
     * @return int
     */
    protected function _getDefaultStoreId()
    {
        return Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID;
    }
    
    /**
     * Return the specified inventory data from the given product
     * 
     * @param Mage_Catalog_Model_Product $product Product
     * @param string $field Inventory field name
     * @param bool $useConfigDefault Whether the field uses config values by default
     * @return mixed
     */
    protected function _getProductInventoryData(Mage_Catalog_Model_Product $product, $field, $useConfigDefault = false)
    {
        if ($stockItem = $product->getStockItem()) {
            /** @var $stockItem Mage_CatalogInventory_Model_Stock_Item */
            if (!$useConfigDefault
                || ($stockItem->getData('use_config_' . $field) == 0)) {
                return $stockItem->getDataUsingMethod($field);
            }
        }
        return Mage::getStoreConfig(Mage_CatalogInventory_Model_Stock_Item::XML_PATH_ITEM . $field);
    }
    
    protected function _loadEditedEntity($blockType, BL_CustomGrid_Object $config, array $params, $entityId)
    {
        $storeId = $this->_getDefaultStoreId();
        
        if (isset($params['additional'])) {
            if (isset($params['additional']['column_store_id'])) {
                $storeId = $params['additional']['column_store_id'];
            } elseif (isset($params['additional']['store_id'])) {
                $storeId = $params['additional']['store_id'];
            }
        }
        
        /** @var $wysiwygConfig Mage_Cms_Model_Wysiwyg_Config */
        $wysiwygConfig = Mage::getSingleton('cms/wysiwyg_config');
        $wysiwygConfig->setStoreId($storeId);
        
        /** @var $product Mage_Catalog_Model_Product */
        $product = Mage::getModel('catalog/product');
        $product->setStoreId($storeId)
            ->setData('_edit_mode', true)
            ->load($entityId);
        
        return $product;
    }
    
    protected function _getEditedEntityRegistryKeys($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        return array('current_product', 'product');
    }
    
    protected function _checkEntityEditableField($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        /** @var $entity Mage_Catalog_Model_Product */
        if (parent::_checkEntityEditableField($blockType, $config, $params, $entity)) {
            if ($config->getValueId() == 'qty') {
                $helper = $this->_getBaseHelper();
                /** @var $coreHelper Mage_Core_Helper_Data */
                $coreHelper = Mage::helper('core');
                
                if (!$coreHelper->isModuleEnabled('Mage_CatalogInventory')) {
                    Mage::throwException($helper->__('The "Mage_CatalogInventory" module is disabled'));
                }
                if ($entity->isComposite()) {
                    Mage::throwException($helper->__('The quantity is not editable for composite products'));
                }
                if ($entity->getInventoryReadonly()) {
                    Mage::throwException($helper->__('The quantity is read-only for this product'));
                }
                if (!$this->_getProductInventoryData($entity, 'manage_stock', true)) {
                    Mage::throwException($helper->__('The quantity is not editable for this product'));
                }
            }
            return true;
        }
        return false;
    }
    
    protected function _checkEntityEditableAttribute($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        /** @var $entity Mage_Catalog_Model_Product */
        if (parent::_checkEntityEditableAttribute($blockType, $config, $params, $entity)) {
            $helper = $this->_getBaseHelper();
            $isEditable = false;
            $productAttributes = $entity->getAttributes();
            
            /** @var $attribute Mage_Eav_Model_Entity_Attribute */
            $attribute = $config->getData('config/attribute');
            $attributeCode = $attribute->getAttributeCode();
            
            foreach ($productAttributes as $attribute) {
                if ($attribute->getAttributeCode() == $attributeCode) {
                    $isEditable = true;
                    break;
                }
            }
            
            if ($entity->hasLockedAttributes()) {
                if (in_array($attributeCode, $entity->getLockedAttributes())) {
                    Mage::throwException($helper->__('This attribute is locked'));
                }
            }
            if (($entity->getStoreId() != $this->_getDefaultStoreId())
                && !in_array($entity->getStoreId(), $entity->getStoreIds())) {
                Mage::throwException($helper->__('The product is not associated to the corresponding website'));
            }
            
            if ($isEditable) {
                if ($entity->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                    if (in_array($attributeCode, array('sku', 'weight', 'price', 'special_price', 'tier_price'))) {
                        Mage::throwException($helper->__('This attribute is not editable for bundle products'));
                    }
                }
                return true;
            }
            
            Mage::throwException($helper->__('This attribute is not editable for this product'));
        }
        return false;
    }
    
    protected function _getEditRequiredAclPermissions($blockType)
    {
        return 'catalog/products';
    }
    
    protected function _getUseDefaultValueForAttribute(Mage_Eav_Model_Entity_Attribute $attribute, $entity)
    {
        /** @var $entity Mage_Catalog_Model_Product */
        if (!$attribute->isScopeGlobal() && $entity->getStoreId()) {
            // This method ensures that the attributes (other than the edited one) using default values
            // for the current store (ie values from higher scopes) will keep this behaviour after save
            $attributeCode = $attribute->getAttributeCode();
            $defaultValue  = $entity->getAttributeDefaultValue($attributeCode);
            
            if (!$entity->getExistsStoreValueFlag($attributeCode)) {
                return true;
            } elseif ($this->_getBaseHelper()->isMageVersionGreaterThan(1, 4)
                && ($entity->getData($attributeCode) == $defaultValue)
                && ($entity->getStoreId() != $this->_getDefaultStoreId())) {
                return false;
            } elseif (($defaultValue === false)
                && !$attribute->getIsRequired()
                && $entity->getData($attributeCode)) {
                return false;
            }
            
            return ($defaultValue === false);
        }
        return false;
    }
    
    protected function _getUseDefaultValueForSave(BL_CustomGrid_Object $config, array $params, $formName = null)
    {
        if (is_null($formName)) {
            /** @var $attribute Mage_Eav_Model_Entity_Attribute */
            $attribute = $config->getData('config/attribute');
            $formName  = $attribute->getAttributeCode();
        }
        return isset($params['global'])
            && isset($params['global']['use_default'])
            && is_array($useDefaultFor = $params['global']['use_default'])
            && in_array($formName, $useDefaultFor);
    }
    
    protected function _prepareDefaultValues(BL_CustomGrid_Object $config, $entity)
    {
        $attributes = $entity->getAttributes();
        /** @var $attribute Mage_Eav_Model_Entity_Attribute */
        $attribute  = $config->getData('config/attribute');
        $editedAttributeCode = $attribute->getAttributeCode();
        
        foreach ($attributes as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            
            if ($attributeCode == $editedAttributeCode) {
                continue;
            }
            if ($this->_getUseDefaultValueForAttribute($attribute, $entity)) {
                $entity->setData($attributeCode, false);
            }
        }
        
        return $this;
    }
    
    protected function _applyEditedFieldValue($blockType, BL_CustomGrid_Object $config, array $params, $entity, $value)
    {
        /** @var $entity Mage_Catalog_Model_Product */
        if ($config->getValueId() == 'qty') {
            $productId = $entity->getId();
            /** @var $stockItem Mage_CatalogInventory_Model_Stock_Item */
            $stockItem = Mage::getModel('cataloginventory/stock_item');
            $stockItem->setData(array());
            $stockItem->loadByProduct($productId)->setProductId($productId);
            
            if (isset($params['original_inventory_qty'])
                && (strlen($params['original_inventory_qty']) > 0)) {
                $stockItem->setQtyCorrection($stockItem->getQty() - $params['original_inventory_qty']);
            }
            
            $stockItem->setQty($value);
            $entity->setData('_blcg_gtp_stock_item', $stockItem);
            return $this;
        }
        return parent::_applyEditedFieldValue($blockType, $config, $params, $entity, $value);
    }
    
    protected function _saveEditedFieldValue($blockType, BL_CustomGrid_Object $config, array $params, $entity, $value)
    {
        /** @var $entity Mage_Catalog_Model_Product */
        if ($config->getValueId() == 'qty') {
            if ($stockItem = $entity->getData('_blcg_gtp_stock_item')) {
                /** @var $stockItem Mage_CatalogInventory_Model_Stock_Item */
                $stockItem->save();
            }
            return true;
        }
        return parent::_saveEditedFieldValue($blockType, $config, $params, $entity, $value);
    }
    
    protected function _getEditedAttributeValue(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        $formName
    ) {
        return $this->_getUseDefaultValueForSave($config, $params)
            ? false // Use "false" to indicate default value (just as base behaviour)
            : parent::_getEditedAttributeValue($blockType, $config, $params, $entity, $formName);
    }
    
    protected function _filterEditedAttributeValue(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        $value
    ) {
        return $this->_getUseDefaultValueForSave($config, $params)
            ? $value // Don't filter when using default value, else it may turn to another value than false
            : parent::_filterEditedAttributeValue($blockType, $config, $params, $entity, $value);
    }
    
    protected function _beforeApplyEditedAttributeValue(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        &$value
    ) {
        /** @var $entity Mage_Catalog_Model_Product */
        
        if (Mage::app()->isSingleStoreMode()) {
            $entity->setWebsiteIds(array(Mage::app()->getStore(true)->getWebsite()->getId()));
        }
        if (isset($params['global']) && isset($params['global']['url_key_create_redirect'])) {
            $entity->setData('save_rewrites_history', (bool) $params['global']['url_key_create_redirect']);
        }
        
        // As we edit only one value once, force using default values for any attribute that require it
        $this->_prepareDefaultValues($config, $entity);
        return parent::_beforeApplyEditedAttributeValue($blockType, $config, $params, $entity, $value);
    }
    
    protected function _applyEditedAttributeValue(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        $value
    ) {
        /** @var $entity Mage_Catalog_Model_Product */
        parent::_applyEditedAttributeValue($blockType, $config, $params, $entity, $value);
        $entity->validate();
        return $this;
    }
    
    protected function _afterSaveEditedAttributeValue(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        $value,
        $result
    ) {
        if ($this->_getUseDefaultValueForSave($config, $params)) {
            // Force product reload if default value was used, to ensure getting the good value for rendering
            $config->setData('config/render_reload', true);
        }
        return parent::_afterSaveEditedAttributeValue($blockType, $config, $params, $entity, $value, $result);
    }
    
    protected function _getSavedFieldValueForRender($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        /** @var $entity Mage_Catalog_Model_Product */
        if ($config->getValueId() == 'qty') {
            if ($stockItem = $entity->getStockItem()) {
                // Reload stock item to get the updated value
                /** @var $stockItem Mage_CatalogInventory_Model_Stock_Item */
                $stockItem->setProductId(null)->assignProduct($entity);
            }
            $value = $this->_getProductInventoryData($entity, 'qty')*1;
            return (strval($value) !== '' ? $value : 0);
        }
        return parent::_getSavedFieldValueForRender($blockType, $config, $params, $entity);
    }
    
    public function beforeGridPrepareCollection(Mage_Adminhtml_Block_Widget_Grid $gridBlock, $firstTime = true)
    {
        $this->setMustCaptureExportedCollection(!$firstTime);
        return $this;
    }
    
    public function afterGridPrepareCollection(Mage_Adminhtml_Block_Widget_Grid $gridBlock, $firstTime = true)
    {
        $this->setMustCaptureExportedCollection(false);
        return $this;
    }
    
    public function afterGridSetCollection(
        Mage_Adminhtml_Block_Widget_Grid $gridBlock, 
        Varien_Data_Collection $collection
    ) {
        if ($this->getMustCaptureExportedCollection()) {
            $clonedCollection = clone $collection;
            $gridBlock->blcg_setExportedCollection($clonedCollection);
        }
        return $this;
    }
    
    public function afterGridExportLoadCollection(
        Mage_Adminhtml_Block_Widget_Grid $gridBlock, 
        Varien_Data_Collection $collection
    ) {
        if (($collection instanceof Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection)
            || ($collection instanceof Mage_Catalog_Model_Resource_Product_Collection)) {
            /** @var $collection Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */
            $collection->addWebsiteNamesToResult();
        }
        return $this;
    }
}
