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
 * @copyright  Copyright (c) 2015 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Block_Widget_Grid_Column_Filter_Product_Categories_Chooser extends Mage_Adminhtml_Block_Catalog_Category_Tree
{
    public function __construct()
    {
        parent::__construct();
        $this->_withProductCount = false;
        $this->setTemplate('bl/customgrid/widget/grid/column/filter/product/categories/chooser.phtml');
    }
    
    /**
     * Return the IDs of the selected categories
     * 
     * @return array
     */
    public function getSelectedCategoriesIds()
    {
        if (!is_array($categoryIds = $this->_getData('selected_categories_ids'))) {
            $categoryIds = array();
            $this->setData('selected_categories_ids', $categoryIds);
        }
        return $categoryIds;
    }
    
    /**
     * Return the IDs of the selected categories as a CSV string
     * 
     * @return string
     */
    public function getSelectedCategoriesIdsString()
    {
        return implode(',', $this->getSelectedCategoriesIds());
    }
    
    /**
     * Return the path IDs of the selected categories
     * 
     * @param mixed $rootId
     */
    public function getSelectedCategoriesPathIds($rootId = false)
    {
        /** @var $helper BL_CustomGrid_Helper_Data */
        $helper  = $this->helper('customgrid');
        
        $fromOneDotSix = $helper->isMageVersionGreaterThan(1, 5);
        $pathIds = array();
        $categoryIds   = $this->getSelectedCategoriesIds();
        
        if ($fromOneDotSix && empty($categoryIds)) {
            return array();
        }
        
        /** @var $collection Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection */
        $collection = Mage::getResourceModel('catalog/category_collection');
        $collection->addFieldToFilter('entity_id', array('in' => $categoryIds));
        
        foreach ($collection as $item) {
            /** @var $item Mage_Catalog_Model_Category */
            if ($rootId && !in_array($rootId, $item->getPathIds())) {
                continue;
            }
            foreach ($item->getPathIds() as $pathId) {
                if (!in_array($pathId, $pathIds)) {
                    $pathIds[] = $pathId;
                }
            }
        }
        
        return $pathIds;
    }
    
    /**
     * Return the current root category node
     * 
     * @return Varien_Data_Tree_Node
     */
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
            
            $pathIds = $this->getSelectedCategoriesPathIds($rootId);
            
            /** @var $tree Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Tree */
            $tree = Mage::getResourceSingleton('catalog/category_tree');
            $tree->loadByIds($pathIds, false, false);
            
            if ($category = $this->getCategory()) {
                $tree->loadEnsuredNodes($category, $tree->getNodeById($rootId));
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
    
    /**
     * Return the current root category node with a full recursion level,
     * or the node corresponding to the given category with the given recursion level
     * 
     * @param Mage_Catalog_Model_Category|null Category for which to return the corresponding node
     * @param int $recursionLevel Recursion level that will be used if a specific category is given
     * @return Varien_Data_Tree_Node
     */
    public function getRoot($parentNodeCategory = null, $recursionLevel = 3)
    {
        return (!is_null($parentNodeCategory) && $parentNodeCategory->getId())
            ? $this->getNode($parentNodeCategory, $recursionLevel)
            : $this->_getRoot();
    }
    
    /**
     * Return the current root category node
     * 
     * @return Varien_Data_Tree_Node
     */
    public function getRootNode()
    {
        $root = $this->getRoot();
        
        if ($root && in_array($root->getId(), $this->getSelectedCategoriesIds())) {
            $root->setChecked(true);
        }
        
        return $root;
    }
    
    /**
     * Return the selected categories nodes
     * 
     * @return Varien_Data_Tree_Node[]
     */
    protected function _getSelectedCategoriesNodes()
    {
        if (!$this->hasData('selected_nodes')) {
            $selectedNodes = array();
            
            if ($root = $this->getRoot()) {
                $tree = $root->getTree();
                
                foreach ($this->getSelectedCategoriesIds() as $categoryId) {
                    $selectedNodes[] = $tree->getNodeById($categoryId);
                }
            }
            
            $this->setData('selected_nodes', $selectedNodes);
        }
        return $this->_getData('selected_nodes');
    }
    
    protected function _getNodeJson($node, $level = 1)
    {
        $item = parent::_getNodeJson($node, $level);
        $isParent = $this->_isParentSelectedCategory($node);
        
        if ($isParent) {
            $item['expanded'] = true;
        }
        if (in_array($node->getId(), $this->getSelectedCategoriesIds())) {
            $item['checked'] = true;
        }
        
        return $item;
    }
    
    protected function _isParentSelectedCategory($parentNode)
    {
        foreach ($this->_getSelectedCategoriesNodes() as $selectedNode) {
            if ($selectedNode) {
                $pathIds = explode('/', $selectedNode->getPathId());
                
                if (in_array($parentNode->getId(), $pathIds)) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * Return the JSON config for the children categories nodes of the given category ID
     * 
     * @param int $categoryId Parent category ID
     * @return string
     */
    public function getCategoryChildrenJson($categoryId)
    {
        /** @var $category Mage_Catalog_Model_Category */
        $category = Mage::getModel('catalog/category');
        $category->load($categoryId);
        $node = $this->getRoot($category, 1)->getTree()->getNodeById($categoryId);
        
        if (!$node || !$node->hasChildren()) {
            return '[]';
        }
        
        $children = array();
        
        foreach ($node->getChildren() as $child) {
            $children[] = $this->_getNodeJson($child);
        }
        
        /** @var $helper Mage_Core_Helper_Data */
        $helper = $this->helper('core');
        return $helper->jsonEncode($children);
    }
    
    /**
     * Return the URL usable to load a part of the category tree
     * 
     * @param bool $expanded Not used
     * @return string
     */
    public function getLoadTreeUrl($expanded = null)
    {
        return $this->getUrl('customgrid/grid_column_filter/categoriesJson', array('_current' => true));
    }
    
    /**
     * Return the name of the categories filter JS object
     * 
     * @param bool $sanitize Whether the object name should be sanitized
     * @return string
     */
    public function getJsObjectName($sanitize = true)
    {
        if ($jsObjectName = $this->_getData('js_object_name')) {
            /** @var $helper BL_CustomGrid_Helper_String */
            $helper = $this->helper('customgrid/string');
            $jsObjectName = $helper->sanitizeJsObjectName($jsObjectName);
        }
        return $jsObjectName;
    }
    
    /**
     * Return the HTML content of the "Choose" button
     * 
     * @return string
     */
    public function getApplyButtonHtml()
    {
        if ($this->getJsObjectName(false)) {
            /** @var $applyButton Mage_Adminhtml_Block_Widget_Button */
            $applyButton = $this->getLayout()->createBlock('adminhtml/widget_button');
            
            $applyButton->setData(
                  array(
                      'label'   => $this->__('Choose'),
                      'onclick' => 'blcgApplyCategories();',
                      'class'   => 'scalable save',
                      'type'    => 'button',
                  )
              );
              
              return $applyButton->toHtml();
        }
        return '';
    }
}
