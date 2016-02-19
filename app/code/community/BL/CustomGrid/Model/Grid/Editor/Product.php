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

class BL_CustomGrid_Model_Grid_Editor_Product extends BL_CustomGrid_Model_Grid_Editor_Abstract
{
    const DEFAULT_VALUE_FLAG_CONTEXT_KEY = '_use_default_value_';
    
    protected function _getBaseConfigData()
    {
        return array(
            'entity_row_identifiers_keys' => array('entity_id'),
            'entity_model_class_code'     => 'catalog/product',
            'entity_registry_keys'        => array('current_product', 'product'),
            'grid_block_edit_permissions' => array(
                BL_CustomGrid_Model_Grid_Editor_Sentry::BLOCK_TYPE_ALL => 'catalog/products',
            ),
        );
    }
    
    public function getDefaultBaseCallbacks(BL_CustomGrid_Model_Grid_Editor_Callback_Manager $callbackManager)
    {
        return array(
            $callbackManager->getCallbackFromCallable(
                array($this, 'prepareEditableAttributeConfig'),
                self::WORKER_TYPE_VALUE_CONFIG_BUILDER,
                BL_CustomGrid_Model_Grid_Editor_Value_Config_Builder::ACTION_TYPE_BUILD_EDITABLE_ATTRIBUTE_CONFIG,
                BL_CustomGrid_Model_Grid_Editor_Callback::POSITION_MAIN,
                BL_CustomGrid_Model_Grid_Editor_Callback::PRIORITY_EDITOR_INTERNAL_LOW
            ),
            $callbackManager->getInternalMainCallbackFromCallable(
                array($this, 'loadEditedProduct'),
                self::WORKER_TYPE_ENTITY_LOADER,
                BL_CustomGrid_Model_Grid_Editor_Entity_Loader::ACTION_TYPE_LOAD_EDITED_ENTITY,
                true
            ),
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
                array($this, 'filterUserEditedValue'),
                self::WORKER_TYPE_ENTITY_UPDATER,
                BL_CustomGrid_Model_Grid_Editor_Entity_Updater::ACTION_TYPE_FILTER_USER_EDITED_VALUE
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
    
    protected function _getBaseEditableFields($blockType)
    {
        return array(
            'qty' => array(
                'form' => array(
                    'block_type' => 'customgrid/widget_grid_editor_form_field_product_inventory',
                ),
                'form_field' => array(
                    'type'     => 'text',
                    'class'    => 'validate-number',
                    'required' => true,
                    'inventory_field' => 'qty',
                ),
                'renderer' => array(
                    'must_reload' => false,
                ),
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
        /** @var Mage_Catalog_Model_Resource_Eav_Mysql4_Product $productResource */
        $productResource = Mage::getResourceModel('catalog/product');
        return array('sku' => $productResource->getAttribute('sku'));
    }
    
    /**
     * Callback for @see BL_CustomGrid_Model_Grid_Editor_Value_Config_Builder::buildEditableAttributeConfig()
     *
     * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute model
     * @param mixed $previousReturnedValue Value returned by the previous callback
     * @return array
     */
    public function prepareEditableAttributeConfig(Mage_Eav_Model_Entity_Attribute $attribute, $previousReturnedValue)
    {
        $config = (is_array($previousReturnedValue) ? $previousReturnedValue : array());
        
        if ($attribute->getFrontendInput() == 'weight') {
            $config['form']['is_in_grid'] = true;
        }
        
        $config['form']['block_type'] = 'product';
        $config['form']['layout_handles'] = array(
            'blcg_grid_editor_handle_editor',
            'blcg_grid_editor_handle_product',
        ); 
        
        return $config;
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
     * Callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Loader::loadEditedEntity()
     * 
     * @param int $entityId Product ID
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return Mage_Catalog_Model_Product
     */
    public function loadEditedProduct($entityId, BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        $storeId = $this->_getDefaultStoreId();
        $params  = $context->getRequestParams();
        
        if (isset($params['additional'])) {
            if (isset($params['additional']['column_store_id'])) {
                $storeId = $params['additional']['column_store_id'];
            } elseif (isset($params['additional']['store_id'])) {
                $storeId = $params['additional']['store_id'];
            }
        }
        
        /** @var Mage_Cms_Model_Wysiwyg_Config $wysiwygConfig */
        $wysiwygConfig = Mage::getSingleton('cms/wysiwyg_config');
        $wysiwygConfig->setStoreId($storeId);
        
        /** @var Mage_Catalog_Model_Product $product */
        $product = Mage::getModel('catalog/product');
        $product->setStoreId($storeId)->setData('_edit_mode', true)->load($entityId);
        
        return $product;
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
            /** @var Mage_CatalogInventory_Model_Stock_Item $stockItem */
            if (!$useConfigDefault
                || ($stockItem->getData('use_config_' . $field) == 0)) {
                return $stockItem->getDataUsingMethod($field);
            }
        }
        return Mage::getStoreConfig(Mage_CatalogInventory_Model_Stock_Item::XML_PATH_ITEM . $field);
    }

    /**
     * Return whether the given field is editable for the given product under the given editor context
     * 
     * @param Mage_Catalog_Model_Product $product
     * @param string $fieldId Edited field ID
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return bool|string
     */
    protected function _checkContextProductFieldEditability(
        Mage_Catalog_Model_Product $product,
        $fieldId,
        BL_CustomGrid_Model_Grid_Editor_Context $context
    ) {
        $result = true;
        
        if ($fieldId == 'qty') {
            $helper = $this->getBaseHelper();
            /** @var Mage_Core_Helper_Data $coreHelper */
            $coreHelper = Mage::helper('core');

            if (!$coreHelper->isModuleEnabled('Mage_CatalogInventory')) {
                $result = $helper->__('The "Mage_CatalogInventory" module is disabled');
            } elseif ($product->isComposite()) {
                $result = $helper->__('The quantity is not editable for composite products');
            } elseif ($product->getInventoryReadonly()) {
                $result = $helper->__('The quantity is read-only for this product');
            } elseif (!$this->_getProductInventoryData($product, 'manage_stock', true)) {
                $result = $helper->__('The quantity is not editable for this product');
            }
        }
        
        return $result;
    }

    /**
     * Return whether the given attribute is editable for the given product under the given editor context
     *
     * @param Mage_Catalog_Model_Product $product
     * @param string $attributeCode Edited attribute code
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return bool|string
     */
    protected function _checkContextProductAttributeEditability(
        Mage_Catalog_Model_Product $product,
        $attributeCode,
        BL_CustomGrid_Model_Grid_Editor_Context $context
    ) {
        $helper = $this->getBaseHelper();
        $result = false;
        $productAttributes = $product->getAttributes();
        
        foreach ($productAttributes as $attribute) {
            /** @var Mage_Eav_Model_Entity_Attribute $attribute */
            if ($attribute->getAttributeCode() == $attributeCode) {
                $result = true;
                break;
            }
        }
        
        if ($result) {
            if ($product->hasLockedAttributes()
                && in_array($attributeCode, $product->getLockedAttributes())) {
                $result = $helper->__('This attribute is locked');
            } elseif (($product->getStoreId() != $this->_getDefaultStoreId())
                && !in_array($product->getStoreId(), $product->getStoreIds())) {
                $result = $helper->__('The product is not associated to the corresponding website');
            } else if (($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE)
                && in_array($attributeCode, array('sku', 'weight', 'price', 'special_price', 'tier_price'))) {
                $result = $helper->__('This attribute is not editable for bundle products');
            }
        } else {
            $result = $helper->__('This attribute is not editable for this product');
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
            /** @var Mage_Catalog_Model_Product $product */
            $product = $context->getEditedEntity();
            $valueOrigin = $context->getValueOrigin();
            $valueId = $context->getValueId();
            
            if ($valueOrigin == BL_CustomGrid_Model_Grid_Editor_Abstract::EDITABLE_TYPE_FIELD) {
                $result = $this->_checkContextProductFieldEditability($product, $valueId, $context);
            } elseif ($valueOrigin == BL_CustomGrid_Model_Grid_Editor_Abstract::EDITABLE_TYPE_ATTRIBUTE) {
                $result = $this->_checkContextProductAttributeEditability($product, $valueId, $context);
            }
        }
        
        return $result;
    }
    
    /**
     * Return whether the user edited value from the given editor context corresponds to the default value
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return bool
     */
    protected function _isContextUserEditedValueDefault(BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        $params = $context->getRequestParams();
        return isset($params['global'])
            && isset($params['global']['use_default'])
            && is_array($useDefaultFor = $params['global']['use_default'])
            && in_array($context->getFormFieldName(), $useDefaultFor);
    }
    
    /**
     * Callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Updater::getContextUserEditedValue()
     * 
     * @param BL_CustomGrid_Object $transport Transport object used to hold the user value 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     */
    public function getContextUserEditedValue(
        BL_CustomGrid_Object $transport,
        BL_CustomGrid_Model_Grid_Editor_Context $context
    ) {
        if ($context->isAttributeValueContext() && $this->_isContextUserEditedValueDefault($context)) {
            // Use "false" to indicate the use of the default value (just as the base behaviour)
            $transport->setData('value', false);
            $context->setData(self::DEFAULT_VALUE_FLAG_CONTEXT_KEY, true);
        }
    }
    
    /**
     * Callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Updater::filterUserEditedValue()
     *
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @param BL_CustomGrid_Model_Grid_Editor_Callback $editorCallback Editor callback
     */
    public function filterUserEditedValue(
        BL_CustomGrid_Model_Grid_Editor_Context $context,
        BL_CustomGrid_Model_Grid_Editor_Callback $editorCallback
    ) {
        if ($context->getData(self::DEFAULT_VALUE_FLAG_CONTEXT_KEY)) {
            // Don't filter the user value if it is using the default value, otherwise it may lose this behavior
            $editorCallback->setShouldStopAfter(true);
        }
    }
    
    /**
     * Return whether the given product, in its given state, is store-scoped for the given attribute
     * 
     * @param Mage_Catalog_Model_Product $product Checked product
     * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute model
     * @return bool
     */
    protected function _isProductStoreScopedForAttribute(
        Mage_Catalog_Model_Product $product,
        Mage_Eav_Model_Entity_Attribute $attribute
    ) {
        return (!$attribute->isScopeGlobal() && $product->getStoreId());
    }
    
    /**
     * Return whether the given product, in its given state,
     * uses for the given attribute the corresponding default value
     *
     * @param Mage_Catalog_Model_Product $product Checked product
     * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute model
     * @return bool
     */
    protected function _isProductDefaultValuedForAttribute(
        Mage_Catalog_Model_Product $product,
        Mage_Eav_Model_Entity_Attribute $attribute
    ) {
        if ($this->_isProductStoreScopedForAttribute($product, $attribute)) {
            $attributeCode = $attribute->getAttributeCode();
            $defaultValue  = $product->getAttributeDefaultValue($attributeCode);
            
            /**
             * @see Mage_Adminhtml_Block_Catalog_Form_Renderer_Fieldset_Element::usedDefault()
             */
            if (!$product->getExistsStoreValueFlag($attributeCode)) {
                return true;
            } elseif ($this->getBaseHelper()->isMageVersionGreaterThan(1, 4)
                && ($product->getData($attributeCode) == $defaultValue)
                && ($product->getStoreId() != $this->_getDefaultStoreId())) {
                return false;
            } elseif (($defaultValue === false)
                && !$attribute->getIsRequired()
                && $product->getData($attributeCode)) {
                return false;
            }
            
            return ($defaultValue === false);
        }
        return false;
    }
    
    /**
     * Prepare the default values for each necessary attribute on the given product
     *
     * @param Mage_Catalog_Model_Product $product Edited product
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return BL_CustomGrid_Model_Grid_Editor_Product
     */
    protected function _prepareEditedProductDefaultValues(
        Mage_Catalog_Model_Product $product,
        BL_CustomGrid_Model_Grid_Editor_Context $context
    ) {
        $attributes = $product->getAttributes();
        /** @var Mage_Eav_Model_Entity_Attribute $attribute */
        $attribute  = $context->getValueConfig()->getData('global/attribute');
        $editedAttributeCode = $attribute->getAttributeCode();
        
        foreach ($attributes as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            
            if ($attributeCode == $editedAttributeCode) {
                continue;
            }
            if ($this->_isProductDefaultValuedForAttribute($product, $attribute)) {
                $product->setData($attributeCode, false);
            }
        }
        
        return $this;
    }
    
    /**
     * Apply the given edited field value to the given edited product
     * 
     * @param Mage_Catalog_Model_Product $editedEntity Edited product
     * @param mixed $value Edited value
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return bool
     */
    protected function _applyFieldValueToEditedProduct(
        Mage_Catalog_Model_Product $editedEntity,
        $value,
        BL_CustomGrid_Model_Grid_Editor_Context $context
    ) {
        if ($context->getValueId() == 'qty') {
            $params = $context->getRequestValuesParams();
            $productId = $editedEntity->getId();
        
            /** @var Mage_CatalogInventory_Model_Stock_Item $stockItem */
            $stockItem = Mage::getModel('cataloginventory/stock_item');
            $stockItem->setData(array());
            $stockItem->loadByProduct($productId)->setProductId($productId);
        
            if (isset($params['original_inventory_qty'])
                && (strlen($params['original_inventory_qty']) > 0)) {
                $stockItem->setQtyCorrection($stockItem->getQty() - $params['original_inventory_qty']);
            }
        
            $stockItem->setQty($value);
            $editedEntity->setData('_blcg_gtp_stock_item', $stockItem);
            return true;
        }
        return false;
    }
    
    /**
     * Apply the given edited attribute value to the given edited product
     *
     * @param Mage_Catalog_Model_Product $editedEntity Edited product
     * @param mixed $value Edited value
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return bool
     */
    protected function _applyAttributeValueToEditedProduct(
        Mage_Catalog_Model_Product $editedEntity,
        $value,
        BL_CustomGrid_Model_Grid_Editor_Context $context
    ) {
        $params = $context->getRequestGlobalParams();
    
        if (Mage::app()->isSingleStoreMode()) {
            $editedEntity->setWebsiteIds(array(Mage::app()->getStore(true)->getWebsite()->getId()));
        }
        if (isset($params['url_key_create_redirect'])) {
            $editedEntity->setData('save_rewrites_history', (bool)$params['url_key_create_redirect']);
        }
    
        // As we edit only one value once, force using default values for any attribute that require it
        $this->_prepareEditedProductDefaultValues($editedEntity, $context);
    
        $editedEntity->setData($context->getFormFieldName(), $value);
        $editedEntity->validate();
    
        if ($context->getData(self::DEFAULT_VALUE_FLAG_CONTEXT_KEY)) {
            // Force later product reload if the default value was used, to ensure getting the good value for rendering
            $context->getValueConfig()->setData('config/render_reload', true);
        }
        
        return true;
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
        $result = false;
        
        if ($context->isAttributeValueContext()) {
            $result = $this->_applyAttributeValueToEditedProduct($editedEntity, $userValue, $context);
        } elseif ($context->isFieldValueContext()) {
            $result = $this->_applyFieldValueToEditedProduct($editedEntity, $userValue, $context);
        }
        
        return $result;
    }
    
    /**
     * Callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Updater::saveContextEditedProduct()
     * 
     * @param Mage_Catalog_Model_Product $editedEntity Edited product
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return bool
     */
    public function saveContextEditedProduct(
        Mage_Catalog_Model_Product $editedEntity,
        BL_CustomGrid_Model_Grid_Editor_Context $context
    ) {
        if ($context->isFieldValueContext() && ($context->getValueId() == 'qty')) {
            if ($stockItem = $editedEntity->getData('_blcg_gtp_stock_item')) {
                /** @var Mage_CatalogInventory_Model_Stock_Item $stockItem */
                $stockItem->save();
            }
            return true;
        }
        return false;
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
        if ($context->isFieldValueContext() && ($context->getValueId() == 'qty')) {
            /** @var Mage_Catalog_Model_Product $editedEntity */
            $editedEntity = $context->getEditedEntity();
            
            if ($stockItem = $editedEntity->getStockItem()) {
                // Reload the edited stock item to get the updated stock value
                /** @var $stockItem Mage_CatalogInventory_Model_Stock_Item */
                $stockItem->setProductId(null)->assignProduct($editedEntity);
            }
            
            $value = $this->_getProductInventoryData($editedEntity, 'qty') * 1;
            $transport->setData('value', (strval($value) !== '' ? $value : 0));
        }
    }
}
