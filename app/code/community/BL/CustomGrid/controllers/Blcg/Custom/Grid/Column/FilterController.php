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

class BL_CustomGrid_Blcg_Custom_Grid_Column_FilterController
    extends Mage_Adminhtml_Controller_Action
{
    public function categoriesAction()
    {
        $this->loadLayout();
        
        if ($block = $this->getLayout()->getBlock('customgrid.filter.categories.chooser')) {
            $jsObject = $this->getRequest()->getParam('js_object', null);
            $categoryIds = array_unique(explode(',', $this->getRequest()->getParam('ids', null)));
            $block->setJsObject($jsObject)->setCategoryIds($categoryIds);
        }
        
        $this->renderLayout();
    }
    
    public function categoriesJsonAction()
    {;
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('customgrid/widget_grid_column_filter_product_categories_chooser')
                ->setCategoryIds(array_unique(explode(',', $this->getRequest()->getParam('ids', null))))
                ->getCategoryChildrenJson($this->getRequest()->getParam('category'))
        );
    }
    
    protected function _isAllowed()
    {
        return true;
    }
}