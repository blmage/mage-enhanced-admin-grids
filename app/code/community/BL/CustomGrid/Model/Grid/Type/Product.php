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
    
    protected function _getEditorModelClassCode()
    {
        return 'customgrid/grid_editor_product';
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
