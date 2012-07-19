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

class BL_CustomGrid_Model_Grid_Type_Product
    extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    protected $_attributes = null;
    protected $_mustCaptureExportedCollection = false;
    
    public function isAppliableToGrid($type, $rewritingClassName)
    {
        return (($type == 'adminhtml/catalog_product_grid')
            || ($type == 'adminhtml/catalog_category_tab_product'));
    }
    
    public function canExport($type)
    {
        // @todo implement export for category products tab
        return ($type == 'adminhtml/catalog_product_grid');
    }
    
    protected function _getColumnsLockedValues($type)
    {
        return array(
            'is_salable' => array(
                'renderer'      => '',
                'config_values' => array(
                    'filter'   => false,
                    'sortable' => false,
                ),
            ),
        );
    }
    
    public function canHaveAttributeColumns($type)
    {
        return true;
    }
    
    protected function _isAvailableAttribute($type, $attribute)
    {
        if (parent::_isAvailableAttribute($type, $attribute)) {
            // @todo for this and editability, put allowed models rather than excluded ones ?
            $excludedModels = array(
                'catalog/product_attribute_backend_media',
                'catalog/product_attribute_backend_recurring',
                'catalog/product_attribute_backend_tierprice',
            );
            if (($attribute->getFrontend()->getInputType() != 'gallery')
                && !in_array($attribute->getBackendModel(), $excludedModels)) {
                return true;
            }
        }
        return false;
    }
    
    protected function _getAvailableAttributes($type)
    {
        $attributes = Mage::getModel('catalog/product')->getResource()
            ->loadAllAttributes()
            ->getAttributesByCode();
        $keptAttributes = array();
        
        foreach ($attributes as $attribute) {
            if ($this->_isAvailableAttribute($type, $attribute)) {
                $keptAttributes[$attribute->getAttributeCode()] = $attribute;
            }
        }
        
        return $keptAttributes;
    }
    
    protected function _getAdditionalCustomColumns()
    {
        return array();
    }
    
    public function checkUserEditPermissions($type, $model, $block=null, $params=array())
    {
        if (parent::checkUserEditPermissions($type, $model, $block, $params)) {
            return Mage::getSingleton('admin/session')->isAllowed('catalog/products');
        }
        return false;
    }
    
    protected function _getAdditionalEditableAttributes($type)
    {
        return array(
            'sku' => Mage::getResourceModel('catalog/product')->getAttribute('sku'),
        );
    }
    
    protected function _checkAttributeEditability($type, $attribute)
    {
        if (parent::_checkAttributeEditability($type, $attribute)) {
            return ($attribute->getFrontend()->getInputType() != 'media_image');
        }
        return false;
    }
    
    protected function _prepareEditableAttributeCommonConfig($type, $code, $attribute, $config)
    {
        return array_merge(
            parent::_prepareEditableAttributeCommonConfig($type, $code, $attribute, $config),
            array(
                'edit_block_type' => 'product',
                'layout_handles'  => array(
                    'custom_grid_editor_handle_editor',
                    'custom_grid_editor_handle_product',
                ),
            )
        );
    }
    
    protected function _getBaseEditableAttributeFields($type)
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
    
    public function getAdditionalEditParams($type, $grid)
    {
        return array('store_id' => $grid->blcg_getStore()->getId());
    }
    
    protected function _getEntityRowIdentifiersKeys($type)
    {
        return array('entity_id');
    }
    
    protected function _getDefaultStoreId()
    {
        return Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID;
    }
    
    protected function _loadEditedEntity($type, $config, $params)
    {
        if (isset($params['ids']['entity_id'])) {
            if (isset($params['additional']['column_store_id'])) {
                $storeId = $params['additional']['column_store_id'];
            } elseif (isset($params['additional']['store_id'])) {
                $storeId = $params['additional']['store_id'];
            } else {
                $storeId = 0;
            }
            
            Mage::getSingleton('cms/wysiwyg_config')->setStoreId($storeId);
            
            return Mage::getModel('catalog/product')
                ->setStoreId($storeId)
                ->setData('_edit_mode', true)
                ->load($params['ids']['entity_id']);
        }
        return null;
    }
    
    protected function _getEditedEntityRegistryKeys($type, $config, $params, $entity)
    {
        return array('current_product', 'product');
    }
    
    protected function _checkEntityEditableAttribute($type, $config, $params, $entity)
    {
        if (parent::_checkEntityEditableAttribute($type, $config, $params, $entity)) {
            $productAttributes = $entity->getAttributes();
            $searchedCode = $config['config']['attribute']->getAttributeCode();
            $isEditable   = false;
            
            foreach ($productAttributes as $attribute) {
                if ($attribute->getAttributeCode() == $searchedCode) {
                    $isEditable = true;
                    break;
                }
            }
            
            if ($entity->hasLockedAttributes()) {
                if (in_array($searchedCode, $entity->getLockedAttributes())) {
                    Mage::throwException(Mage::helper('customgrid')->__('This attribute is locked'));
                }
            }
            
            if (($entity->getStoreId() != 0)
                && !in_array($entity->getStoreId(), $entity->getStoreIds())) {
                Mage::throwException(Mage::helper('customgrid')->__('The product is not associated to the corresponding website'));
            }
            
            if ($isEditable) {
                if ($entity->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                    // @todo all MAP stuff from 1.6, and handle attributes with "Use config" (such as gift_message_available too)
                    if (in_array($searchedCode, array('price', 'sku', 'special_price', 'tier_price', 'weight'))) {
                        Mage::throwException(Mage::helper('customgrid')->__('This attribute is not editable for bundle products'));
                    }
                }
                return true;
            } else {
                Mage::throwException(Mage::helper('customgrid')->__('This attribute is not editable for this product'));
            }
        }
        return false;
    }
    
    protected function _mustUseDefaultValueForSave($config, $params, $formName=null)
    {
        $formName = (is_null($formName) ? $config['config']['attribute']->getAttributeCode() : $formName);
        return (isset($params['global']['use_default'])
                && is_array($default = $params['global']['use_default'])
                && in_array($formName, $default));
    }
    
    protected function _mustUseDefaultValueForAttribute($attribute, $entity)
    {
        if (!$attribute->isScopeGlobal() && $entity->getStoreId()) {
            $attributeCode = $attribute->getAttributeCode();
            $defaultValue  = $entity->getAttributeDefaultValue($attributeCode);
            
            if (!$entity->getExistsStoreValueFlag($attributeCode)) {
                return true;
            } elseif (Mage::helper('customgrid')->isMageVersionGreaterThan(1, 4)
                      && ($entity->getData($attributeCode) == $defaultValue)
                      && ($entity->getStoreId() != $this->_getDefaultStoreId())) {
                return false;
            }
            if (($defaultValue === false)
                && !$attribute->getIsRequired()
                && $product->getData($attributeCode)) {
                return false;
            }
            return ($defaultValue === false);
        }
        return false;
    }
    
    protected function _prepareDefaultValues($config, $entity)
    {
        $attributes = $entity->getAttributes();
        
        foreach ($attributes as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            if ($config['config']['attribute']->getAttributeCode() == $attributeCode) {
                continue;
            }
            if ($this->_mustUseDefaultValueForAttribute($attribute, $entity)) {
                $entity->setData($attributeCode, false);
            }
        }
        
        return $this;
    }
    
    protected function _getEditedAttributeValue($type, $config, $params, $entity, $formName)
    {
        if ($this->_mustUseDefaultValueForSave($config, $params)) {
            // Use "false" to indicate default value (just as base behaviour)
            return false;
        } else {
            return parent::_getEditedAttributeValue($type, $config, $params, $entity, $formName);
        }
    }
    
    protected function _filterEditedAttributeValue($type, $config, $params, $entity, $value)
    {
        if (!$this->_mustUseDefaultValueForSave($config, $params)) {
            return parent::_filterEditedAttributeValue($type, $config, $params, $entity, $value);
        }
        // Don't filter when using default value, else it may turn to another value than "false"
        return $value;
    }
    
    protected function _beforeApplyEditedAttributeValue($type, $config, $params, $entity, &$value)
    {
        if (Mage::app()->isSingleStoreMode()) {
            $entity->setWebsiteIds(array(Mage::app()->getStore(true)->getWebsite()->getId()));
        }
        if (isset($params['global']['url_key_create_redirect'])) {
            $entity->setData('save_rewrites_history', (bool) $params['global']['url_key_create_redirect']);
        }
        if ($config['config']['attribute']->getBackendModel() == 'catalog/product_attribute_backend_boolean') {
            $test = 1;
        }
        
        // As we edit only one value once, force using default value when needed
        $this->_prepareDefaultValues($config, $entity);
        return parent::_beforeApplyEditedAttributeValue($type, $config, $params, $entity, $value);
    }
    
    protected function _applyEditedAttributeValue($type, $config, $params, $entity, $value)
    {
        parent::_applyEditedAttributeValue($type, $config, $params, $entity, $value);
        $entity->validate(); // @todo catch exceptions and format them ? (not yet done in core)
        return $this;
    }
    
    protected function _afterSaveEditedAttributeValue($type, $config, $params, $entity, $value, $result)
    {
        if ($this->_mustUseDefaultValueForSave($config, $params)) {
            // Force product reload if default value was used, to ensure getting the good (default) value for rendering
            $config['config']['render_reload'] = true;
        }
        /*
        @todo from 1.5, but what about giving the choice to the user ? and for which attributes ?
        (not just all, as it is certainly not useful in most of the cases)
        // Mage::getModel('catalogrule/rule')->applyAllRulesToProduct($productId);
        */
        return parent::_afterSaveEditedAttributeValue($type, $config, $params, $entity, $value, $result);
    }
    
    public function beforeGridPrepareCollection($grid, $firstTime=true)
    {
        $this->_mustCaptureExportedCollection = !$firstTime;
        return $this;
    }
    
    public function afterGridPrepareCollection($grid, $firstTime=true)
    {
        $this->_mustCaptureExportedCollection = false;
        return $this;
    }
    
    public function afterGridSetCollection($grid, $collection)
    {
        if ($this->_mustCaptureExportedCollection) {
            $clone = clone $collection;
            $grid->blcg_setExportedCollection($clone);
        }
        return $this;
    }
    
    public function afterGridExportLoadCollection($grid, $collection)
    {
        if ($collection instanceof Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection) {
            $collection->addWebsiteNamesToResult();
        }
        return $this;
    }
}
