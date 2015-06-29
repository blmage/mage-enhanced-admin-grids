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

class BL_CustomGrid_Model_Custom_Column_Product_Frontend_Link extends BL_CustomGrid_Model_Custom_Column_Abstract
{
    protected function _prepareConfig()
    {
        $helper = $this->_getBaseHelper();
        
        $descriptions = array(
            'link_title' => 'If no value is set, the URL will be used',
            'default_store_id' => 'Used by default if the current store view for the column is "<strong>All Store '
                . 'Views</strong>" or "<strong>Default Values</strong>". If none is chosen, the first available store '
                . 'view will be used',
        );
        
        $this->addCustomizationParam(
            'use_url_rewriting',
            array(
                'label'        => $helper->__('Use URL Rewriting'),
                'type'         => 'select',
                'source_model' => 'adminhtml/system_config_source_yesno',
                'value'        => 1,
            ),
            10
        );
        
        $this->addCustomizationParam(
            'link_title',
            array(
                'label'       => $helper->__('Link Title'),
                'description' => $helper->__($descriptions['link_title']),
                'type'        => 'text',
                'value'       => '',
            ),
            20
        );
        
        $this->addCustomizationParam(
            'open_new_window',
            array(
                'label'        => $helper->__('Open Link in a New Window'),
                'type'         => 'select',
                'source_model' => 'adminhtml/system_config_source_yesno',
                'value'        => 1,
            ),
            30
        );
        
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addCustomizationParam(
                'default_store_id',
                array(
                    'label'        => $helper->__('Default Store View'),
                    'description'  => $helper->__($descriptions['default_store_id']),
                    'type'         => 'select',
                    'source_model' => 'adminhtml/system_config_source_store',
                ),
                40
            );
        }
        
        $this->setCustomizationWindowConfig(array('height' => 340), true);
        return parent::_prepareConfig();
    }
    
    /**
     * Add request paths from URL rewrites to the given grid collection
     * 
     * @param array $params Customization params values
     * @param array $storeId Store ID from which to retrieve request paths
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @return BL_CustomGrid_Model_Custom_Column_Product_Link
     */
    public function addRequestPathsToGridCollection(
        array $params,
        $storeId,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection_Db $collection
    ) {
        $productIds = array();
        
        foreach ($collection as $product) {
            /** @var $product Mage_Catalog_Model_Product */
            $productIds[] = $product->getId();
        }
        
        $idsCount = count($productIds);
        $sliceLength = 500;
        $slicesCount = ceil($idsCount/$sliceLength);
        
        for ($i=0; $i<$slicesCount; $i++) {
            /** @var $rewritesCollection Mage_Core_Model_Mysql4_Url_Rewrite_Collection */
            $rewritesCollection = Mage::getResourceModel('core/url_rewrite_collection');
            list($adapter, $qi) = $this->_getCollectionAdapter($rewritesCollection, true);
            
            $rewritesCollection->addStoreFilter($storeId)
                ->addFieldToFilter('category_id', array('null' => true))
                ->getSelect()
                ->where(
                    'SUBSTRING(' . $qi('id_path') . ', 9) IN (?)', // 9 = length of "product/"
                    array_slice($productIds, $i*$sliceLength, $sliceLength)
                );
            
            foreach ($rewritesCollection as $urlRewrite) {
                /** @var $urlRewrite Mage_Core_Model_Url_Rewrite */
                if (($product = $collection->getItemById($urlRewrite->getProductId()))
                    && !$product->hasData('blcg_request_path')) {
                    /** @var $product Mage_Catalog_Model_Product */
                    $product->setData('blcg_request_path', $urlRewrite->getRequestPath());
                }
            }
        }
        
        return $this;
    }
    
    /**
     * Return the usable store ID
     * (the ID of the only store on single store mode, otherwise the ID of the given chosen store)
     * 
     * @param Mage_Core_Model_Store $store Column store
     * @param array $params Customization params values
     * @return int
     */
    protected function _getUsableStoreId(Mage_Core_Model_Store $store, array $params)
    {
        if (Mage::app()->isSingleStoreMode()
            || ((!$storeId = $store->getId())
                && (!$storeId = $this->_extractIntParam($params, 'default_store_id', 0)))) {
            $storeId = $this->_getBaseHelper()->getDefaultNonAdminStoreId();
        }
        return $storeId;
    }
    
    public function applyToGridCollection(
        Varien_Data_Collection_Db $collection,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        $columnBlockId,
        $columnIndex,
        array $params,
        Mage_Core_Model_Store $store
    ) {
        if ($this->_extractBoolParam($params, 'use_url_rewriting', true)) {
            if ($gridBlock->blcg_isExport()) {
                $gridBlock->blcg_addCollectionCallback(
                    self::GC_EVENT_AFTER_EXPORT_LOAD,
                    array($this, 'addRequestPathsToGridCollection'),
                    array($params, $this->_getUsableStoreId($store, $params)),
                    true
                );
            } else {
                $gridBlock->blcg_addCollectionCallback(
                    self::GC_EVENT_AFTER_PREPARE,
                    array($this, 'addRequestPathsToGridCollection'),
                    array($params, $this->_getUsableStoreId($store, $params)),
                    true
                );
            }
        }
    }
    
    public function getForcedBlockValues(
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        $columnBlockId,
        $columnIndex,
        array $params,
        Mage_Core_Model_Store $store
    ) {
        return array(
            'renderer'      => 'customgrid/widget_grid_column_renderer_product_frontend_link',
            'filter'        => false,
            'blcg_store_id' => $this->_getUsableStoreId($store, $params),
            'link_title'    => $this->_extractStringParam($params, 'link_title'),
            'open_new_window'   => $this->_extractBoolParam($params, 'open_new_window', true),
            'use_url_rewriting' => $this->_extractBoolParam($params, 'use_url_rewriting', true),
        );
    }
}
