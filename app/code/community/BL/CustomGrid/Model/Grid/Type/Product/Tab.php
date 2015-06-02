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

class BL_CustomGrid_Model_Grid_Type_Product_Tab extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    protected function _getSupportedBlockTypes()
    {
        return array(
            'adminhtml/catalog_product_edit_tab_related',
            'adminhtml/catalog_product_edit_tab_upsell',
            'adminhtml/catalog_product_edit_tab_crosssell',
            'adminhtml/catalog_product_edit_tab_reviews',
            'adminhtml/catalog_product_edit_tab_tag',
            'adminhtml/catalog_product_edit_tab_tag_customer',
        );
    }
    
    public function canExport($blockType)
    {
        /**
         * Regarding products links grids, cloning collection does not clone Zend_Db_Select,
         * and the _beforeLoad() corresponding collections methods join another tables each time,
         * then twice on the same selects (resulting in correlations errors)
         */
        if ($this->isSupportedBlockType($blockType)) {
            return !in_array(
                $blockType,
                array(
                    'adminhtml/catalog_product_edit_tab_related',
                    'adminhtml/catalog_product_edit_tab_upsell',
                    'adminhtml/catalog_product_edit_tab_crosssell',
                )
            );
        }
        return true;
    }
    
    /**
     * Return the ID of the current product
     * 
     * @return int
     */
    protected function _getProductId()
    {
        return ($product = Mage::registry('current_product'))
            /** @var $product Mage_Catalog_Model_Product */
            ? $product->getId()
            : Mage::app()->getRequest()->getParam('id', 0);
    }
    
    protected function _getExportTypes($blockType)
    {
        $exportTypes = parent::_getExportTypes($blockType);
        $productId = $this->_getProductId();
        
        foreach ($exportTypes as $exportType) {
            $exportType->addData(
                array(
                    'url_params/id' => $productId,
                    'url_params/product_id' => $productId,
                )
            );
        }
        
        return $exportTypes;
    }
    
    public function beforeGridExport($format, Mage_Adminhtml_Block_Widget_Grid $gridBlock = null)
    {
        if (is_null($gridBlock)) {
            if (!Mage::registry('current_product')) {
                if ($productId = $this->_getRequest()->getParam('product_id')) {
                    /** @var $product Mage_Catalog_Model_Product */
                    $product = Mage::getModel('catalog/product');
                    $product->load($productId);
                } else {
                    // No product given : use a dummy object
                    $product = new Varien_Object(
                        array(
                            'id'                  => false,
                            'entity_id'           => false,
                            'related_products'    => array(),
                            'up_sell_products'    => array(),
                            'cross_sell_products' => array(),
                        )
                    );
                }
                
                Mage::register('current_product', $product);
            }
        } elseif ($product = Mage::registry('current_product')) {
            $gridBlock->setProductId($product->getId());
        }
        return $this;
    }
}
