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

class BL_CustomGrid_Model_Grid_Type_Product_Tab
    extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    public function isAppliableToGrid($type, $rewritingClassName)
    {
        return in_array(
            $type,
            array(
                'adminhtml/catalog_product_edit_tab_related',
                'adminhtml/catalog_product_edit_tab_upsell',
                'adminhtml/catalog_product_edit_tab_crosssell',
                'adminhtml/catalog_product_edit_tab_reviews',
                'adminhtml/catalog_product_edit_tab_tag',
                'adminhtml/catalog_product_edit_tab_tag_customer',
            )
        );
    }
    
    protected function _getProductId()
    {
        if ($product = Mage::registry('current_product')) {
            return $product->getId();
        } else {
            return Mage::app()->getRequest()->getParam('id', 0);
        }
    }
    
    public function canExport($type)
    {
        /*
        For products links grids, cloning collection does not clone Zend_Db_Select,
        and the _beforeLoad() corresponding collections methods join another tables each time,
        then on the same selects = errors on correlations
        */
        return !in_array(
            $type,
            array(
                'adminhtml/catalog_product_edit_tab_related',
                'adminhtml/catalog_product_edit_tab_upsell',
                'adminhtml/catalog_product_edit_tab_crosssell',
            )
        );
    }
    
    protected function _getExportTypes($gridType)
    {
        $exportTypes = parent::_getExportTypes($gridType);
        
        foreach ($exportTypes as $key => $type) {
            if (!isset($type['params'])) {
                $exportTypes[$key]['params'] = array();
            }
            $exportTypes[$key]['params'] = array_merge(
                $exportTypes[$key]['params'],
                array(
                    'id'         => $this->_getProductId(),
                    'product_id' => $this->_getProductId(),
                )
            );
        }
        
        return $exportTypes;
    }
    
    public function beforeGridExport($format, $grid=null)
    {
        if (is_null($grid)) {
            // Register current product if needed
            if (!Mage::registry('current_product')) {
                if ($this->_getRequest()->getParam('product_id')) {
                    $product = Mage::getModel('catalog/product')
                        ->load($this->_getRequest()->getParam('product_id'));
                } else {
                    // No product given : use a dummy unexisting one
                    $product = new Varien_Object(array(
                        'id' => false,
                        'related_products' => array(),
                        'up_sell_products' => array(),
                        'cross_sell_products' => array(),
                    ));
                }
                Mage::register('current_product', $product);
            }
        } else {
            // Add product ID to grid block
            if ($product = Mage::registry('current_product')) {
                $grid->setProductId($product->getId());
            }
        }
    }
}