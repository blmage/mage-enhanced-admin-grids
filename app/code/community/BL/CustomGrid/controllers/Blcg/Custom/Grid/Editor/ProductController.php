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

class BL_CustomGrid_Blcg_Custom_Grid_Editor_ProductController
    extends Mage_Adminhtml_Controller_Action
{
    public function wysiwygAction()
    {
        $elementId = $this->getRequest()->getParam('element_id', md5(microtime()));
        $storeId   = $this->getRequest()->getParam('store_id', 0);
        $storeMediaUrl = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
        
        $content = $this->getLayout()->createBlock('customgrid/widget_grid_form_helper_product_wysiwyg_content', '', array(
            'editor_element_id' => $elementId,
            'store_id'          => $storeId,
            'store_media_url'   => $storeMediaUrl,
        ));
        
        $this->getResponse()->setBody($content->toHtml());
    }
    
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')
            ->isAllowed('system/customgrid/editor/edit_columns');
    }
}