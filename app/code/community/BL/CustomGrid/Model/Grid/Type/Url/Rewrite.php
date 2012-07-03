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

class BL_CustomGrid_Model_Grid_Type_Url_Rewrite
    extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    public function isAppliableToGrid($type, $rewritingClassName)
    {
        return ($type == 'adminhtml/urlrewrite_grid');
    }
    
    public function checkUserEditPermissions($type, $model, $block=null, $params=array())
    {
        if (parent::checkUserEditPermissions($type, $model, $block, $params)) {
            return Mage::getSingleton('admin/session')->isAllowed('catalog/urlrewrite');
        }
        return false;
    }
    
    public function getRewriteAvailableStores($type, $config, $params, $entity)
    {
        $product  = Mage::registry('current_product');
        $category = Mage::registry('current_category');
        
        $isFilterAllowed = false;
        $stores = Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm();
        $entityStores = array();
        $noStoreError = false;
        
        // Showing websites that only associated to products
        if ($product && $product->getId()) {
            $entityStores = ($product->getStoreIds() ? $product->getStoreIds() : array());
            if  (!$entityStores) {
                Mage::throwException(Mage::helper('adminhtml')->__('Chosen product does not associated with any website, so url rewrite is not possible.'));
            }
            // If category is chosen, reset stores which are not related with this category
            if ($category && $category->getId()) {
                $categoryStores = ($category->getStoreIds() ? $category->getStoreIds() : array());
                $entityStores   = array_intersect($entityStores, $categoryStores);
            }
            $isFilterAllowed = true;
        } elseif ($category && $category->getId()) {
            $entityStores = ($category->getStoreIds() ? $category->getStoreIds() : array());
            if  (!$entityStores) {
                Mage::throwException(Mage::helper('adminhtml')->__('Chosen category does not associated with any website, so url rewrite is not possible.'));
            }
            $isFilterAllowed = true;
        }
        
        /*
         * Stores should be filtered only if product and/or category is specified.
         * If we use custom rewrite, all stores are accepted.
         */
        if ($stores && $isFilterAllowed) {
            foreach ($stores as $i => $store) {
                if (isset($store['value']) && $store['value']) {
                    $found = false;
                    foreach ($store['value'] as $k => $v) {
                        if (isset($v['value']) && in_array($v['value'], $entityStores)) {
                           $found = true;
                        } else {
                            unset($stores[$i]['value'][$k]);
                        }
                    }
                    if (!$found) {
                        unset($stores[$i]);
                    }
                }
            }
        }
        
        return $stores;
    }
    
    protected function _getBaseEditableFields($type)
    {
        $helper = Mage::helper('adminhtml');
        
        $fields = array(
            'id_path' => array(
                'type'     => 'text',
                'required' => true,
            ),
            'request_path' => array(
                'type'     => 'text',
                'required' => true,
            ),
            'target_path' => array(
                'type'     => 'text',
                'required' => true,
            ),
            'options' => array(
                'type'         => 'select',
                'form_options' => array(
                    ''   => $helper->__('No'), // @todo renderer emptiness callback needed for this one
                    'R'  => $helper->__('Temporary (302)'),
                    'RP' => $helper->__('Permanent (301)'),
                ),
            ),
            'description' => array(
                'type'       => 'textarea',
                'in_grid'    => false,
                'form_label' => $helper->__('Description'),
                'form_cols'  => 20,
                'form_rows'  => 5,
                'form_wrap'  => 'soft',
            ),
        );
        
        if (!Mage::app()->isSingleStoreMode()) {
            $fields['store_id'] = array(
                'type'     => 'select',
                'required' => true,
                'form_values_callback' => array($this, 'getRewriteAvailableStores'),
                'render_block_type'    => 'customgrid/widget_grid_editor_renderer_static_store',
            );
        }
        
        return $fields;
    }
    
    protected function _getEntityRowIdentifiersKeys($type)
    {
        return array('url_rewrite_id');
    }
    
    protected function _loadEditedEntity($type, $config, $params)
    {
        if (isset($params['ids']['url_rewrite_id'])) {
            return Mage::getModel('core/url_rewrite')->load($params['ids']['url_rewrite_id']);
        }
        return null;
    }
    
    protected function _registerEditedEntity($type, $config, $params, $entity)
    {
        $productId  = $entity->getProductId();
        $categoryId = $entity->getCategoryId();
        Mage::register('current_product', Mage::getModel('catalog/product')->load($productId));
        Mage::register('current_category', Mage::getModel('catalog/category')->load($categoryId));
        return $this;
    }
    
    protected function _getLoadedEntityName($type, $config, $params, $entity)
    {
        return Mage::helper('customgrid')->__('URL Rewrite');
    }
    
    protected function _checkEntityEditableField($type, $config, $params, $entity)
    {
        if (parent::_checkEntityEditableField($type, $config, $params, $entity)) {
            if ($config['id'] == 'store_id') {
                if ($entity->getIsSystem()) {
                    Mage::throwException(Mage::helper('customgrid')->__('This value is not editable for system rewrites'));
                }
            } elseif (($config['id'] == 'id_path') || ($config['id'] == 'target_path')) {
                if ($entity->getProductId() || $entity->getCategoryId()) {
                    Mage::throwException(Mage::helper('customgrid')->__('This value is not editable for rewrites corresponding to a product and/or a category'));
                }
            }
            return true;
        }
        return false;
    }
    
    protected function _beforeApplyEditedFieldValue($type, $config, $params, $entity, &$value)
    {
        if ($config['id'] == 'request_path') {
            Mage::helper('core/url_rewrite')->validateRequestPath($value);
        }
        return parent::_beforeApplyEditedFieldValue($type, $config, $params, $entity, $value);
    }
    
    protected function _beforeSaveEditedFieldValue($type, $config, $params, $entity, $value)
    {
        $category = (Mage::registry('current_category')->getId() ? Mage::registry('current_category') : null);
        if ($category) {
            $entity->setCategoryId($category->getId());
        }
        $product  = (Mage::registry('current_product')->getId() ? Mage::registry('current_product') : null);
        if ($product) {
            $entity->setProductId($product->getId());
        }
        
        if ($product || $category) {
            $catalogUrlModel = Mage::getSingleton('catalog/url');
            $idPath = $catalogUrlModel->generatePath('id', $product, $category);
            
            // If redirect specified try to find friendly URL
            $found = false;
            if (in_array($entity->getOptions(), array('R', 'RP'))) {
                $rewrite = Mage::getResourceModel('catalog/url')
                    ->getRewriteByIdPath($idPath, $entity->getStoreId());
                if (!$rewrite) {
                    Mage::throwException('Chosen product does not associated with the chosen store or category.');
                }
                if($rewrite->getId() && ($rewrite->getId() != $entity->getId())) {
                    $entity->setIdPath($idPath);
                    $entity->setTargetPath($rewrite->getRequestPath());
                    $found = true;
                }
            }
            
            if (!$found) {
                $entity->setIdPath($idPath);
                $entity->setTargetPath($catalogUrlModel->generatePath('target', $product, $category));
            }
        }
        
        return parent::_beforeSaveEditedFieldValue($type, $config, $params, $entity, $value);
    }
}