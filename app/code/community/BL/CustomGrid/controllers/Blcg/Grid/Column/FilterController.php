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

class BL_CustomGrid_Blcg_Grid_Column_FilterController extends Mage_Adminhtml_Controller_Action
{
    public function categoriesAction()
    {
        $this->loadLayout();
        
        if ($chooserBlock = $this->getLayout()->getBlock('blcg.filter.categories.chooser')) {
            /** @var $chooserBlock BL_CustomGrid_Block_Widget_Grid_Column_Filter_Product_Categories_Chooser */
            $categoryIds = array_unique(explode(',', $this->getRequest()->getParam('ids', '')));
            $chooserBlock->setJsObjectName($this->getRequest()->getParam('js_object_name'));
            $chooserBlock->setSelectedCategoriesIds($categoryIds);
        }
        
        $this->renderLayout();
    }
    
    public function categoriesJsonAction()
    {
        /** @var $chooserBlock BL_CustomGrid_Block_Widget_Grid_Column_Filter_Product_Categories_Chooser */
        $chooserBlock = $this->getLayout()
            ->createBlock('customgrid/widget_grid_column_filter_product_categories_chooser');
        
        $selectedCategoriesIds = array_unique(explode(',', $this->getRequest()->getParam('ids', '')));
        $chooserBlock->setSelectedCategoriesIds($selectedCategoriesIds);
        
        $mainCategoryId = $this->getRequest()->getParam('category');
        $this->getResponse()->setBody($chooserBlock->getCategoryChildrenJson($mainCategoryId));
    }
    
    protected function _isAllowed()
    {
        return true;
    }
}
