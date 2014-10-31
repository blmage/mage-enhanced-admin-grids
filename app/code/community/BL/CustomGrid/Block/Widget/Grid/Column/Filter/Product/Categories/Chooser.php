<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2011 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

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
 * @copyright  Copyright (c) 2014 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Block_Widget_Grid_Column_Filter_Product_Categories_Chooser extends
    Mage_Adminhtml_Block_Catalog_Category_Tree
{
    public function __construct()
    {
        parent::__construct();
        $this->_withProductCount = false;
        $this->setTemplate('bl/customgrid/widget/grid/column/filter/product/categories/chooser.phtml');
    }
    
    public function getCategoryIds()
    {
        if (!is_array($categoryIds = $this->_getData('category_ids'))) {
            $this->setData('category_ids', ($categoryIds = array()));
        }
        return $categoryIds;
    }
    
    public function getIdsString()
    {
        return implode(',', $this->getCategoryIds());
    }
    
    public function getRootNode()
    {
        $root = $this->getRoot();
        
        if ($root && in_array($root->getId(), $this->getCategoryIds())) {
            $root->setChecked(true);
        }
        
        return $root;
    }
    
    protected function _getRoot()
    {
        if (is_null($root = Mage::registry('blcg_wgcfpcc_root'))) {
            $storeId = (int) $this->getRequest()->getParam('store');
            
            if ($storeId) {
                $store  = Mage::app()->getStore($storeId);
                $rootId = $store->getRootCategoryId();
            } else {
                $rootId = Mage_Catalog_Model_Category::TREE_ROOT_ID;
            }
            
            $ids  = $this->getSelectedCategoriesPathIds($rootId);
            $tree = Mage::getResourceSingleton('catalog/category_tree')->loadByIds($ids, false, false);
            
            if ($this->getCategory()) {
                $tree->loadEnsuredNodes($this->getCategory(), $tree->getNodeById($rootId));
            }
            
            $tree->addCollectionData($this->getCategoryCollection());
            $root = $tree->getNodeById($rootId);
            
            if ($root && ($rootId != Mage_Catalog_Model_Category::TREE_ROOT_ID)) {
                $root->setIsVisible(true);
            } elseif ($root && ($root->getId() == Mage_Catalog_Model_Category::TREE_ROOT_ID)) {
                $root->setName($this->helper('catalog')->__('Root'));
            }
            
            Mage::register('blcg_wgcfpcc_root', $root);
        }
        return $root;
    }
    
    public function getRoot($parentNodeCategory = null, $recursionLevel = 3)
    {
        return (!is_null($parentNodeCategory) && $parentNodeCategory->getId())
            ? $this->getNode($parentNodeCategory, $recursionLevel)
            : $this->_getRoot();
    }
    
    protected function _getNodeJson($node, $level = 1)
    {
        $item = parent::_getNodeJson($node, $level);
        $isParent = $this->_isParentSelectedCategory($node);
        
        if ($isParent) {
            $item['expanded'] = true;
        }
        if (in_array($node->getId(), $this->getCategoryIds())) {
            $item['checked'] = true;
        }
        
        return $item;
    }
    
    protected function _isParentSelectedCategory($node)
    {
        foreach ($this->_getSelectedNodes() as $selected) {
            if ($selected) {
                $pathIds = explode('/', $selected->getPathId());
                
                if (in_array($node->getId(), $pathIds)) {
                    return true;
                }
            }
        }
        return false;
    }
    
    protected function _getSelectedNodes()
    {
        if (!$this->hasData('selected_nodes')) {
            $selectedNodes = array();
            
            if ($root = $this->getRoot()) {
                $tree = $root->getTree();
                
                foreach ($this->getCategoryIds() as $categoryId) {
                    $selectedNodes[] = $tree->getNodeById($categoryId);
                }
            }
            
            $this->setData('selected_nodes', $selectedNodes);
        }
        return $this->_getData('selected_nodes');
    }
    
    public function getCategoryChildrenJson($categoryId)
    {
        $category = Mage::getModel('catalog/category')->load($categoryId);
        $node = $this->getRoot($category, 1)->getTree()->getNodeById($categoryId);
        
        if (!$node || !$node->hasChildren()) {
            return '[]';
        }
        
        $children = array();
        
        foreach ($node->getChildren() as $child) {
            $children[] = $this->_getNodeJson($child);
        }
        
        return $this->helper('core')->jsonEncode($children);
    }
    
    public function getSelectedCategoriesPathIds($rootId = false)
    {
        $ids = array();
        $helper = $this->helper('customgrid');
        $fromOneDotSix = $helper->isMageVersionGreaterThan(1, 5);
        $categoryIds = $this->getCategoryIds();
        
        if ($fromOneDotSix && empty($categoryIds)) {
            return array();
        }
        
        $collection = Mage::getResourceModel('catalog/category_collection');
        $collection->addFieldToFilter('entity_id', array('in' => $categoryIds));
        
        foreach ($collection as $item) {
            if ($rootId && !in_array($rootId, $item->getPathIds())) {
                continue;
            }
            foreach ($item->getPathIds() as $id) {
                if (!in_array($id, $ids)) {
                    $ids[] = $id;
                }
            }
        }
        
        return $ids;
    }
    
    public function getLoadTreeUrl($expanded = null)
    {
        return $this->getUrl('customgrid/grid_column_filter/categoriesJson', array('_current' => true));
    }
    
    public function getJsObjectName($sanitize = true)
    {
        if ($jsObjectName = $this->_getData('js_object_name')) {
            $jsObjectName = $this->helper('customgrid/string')->sanitizeJsObjectName($jsObjectName);
        }
        return $jsObjectName;
    }
    
    public function getApplyButtonHtml()
    {
        return !$this->getJsObjectName(false)
            ? ''
            : $this->getLayout()
                  ->createBlock('adminhtml/widget_button')
                  ->setData(
                      array(
                          'label'   => $this->__('Choose'),
                          'onclick' => 'blcgApplyCategories();',
                          'class'   => 'scalable save',
                          'type'    => 'button',
                      )
                  )
                  ->toHtml();
    }
}
