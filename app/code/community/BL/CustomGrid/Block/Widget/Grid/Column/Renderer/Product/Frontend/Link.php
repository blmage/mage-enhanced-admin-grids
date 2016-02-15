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
 * @copyright  Copyright (c) 2016 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Product_Frontend_Link extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Return the product URL from the given row/product
     * 
     * @param Varien_Object $row Result row
     * @return string
     */
    protected function _getRowProductUrl(Varien_Object $row)
    {
        /** @var $row Mage_Catalog_Model_Product */
        if ($this->getColumn()->getUseUrlRewriting()) {
            $blcgRequestPath = $row->getData('blcg_request_path');
            
            $previousUrl = $row->getData('url');
            $previousUrlDataObject  = $row->getData('url_data_object');
            $previousStoreId = $row->getData('store_id');
            $previousRequestPath    = $row->getData('request_path');
            $previousNoCategoryFlag = $row->getData('do_not_use_category_id');
            
            $row->unsetData('url');
            $row->unsetData('url_data_object');
            $row->setData('store_id', $this->getColumn()->getBlcgStoreId());
            $row->setData('request_path', ($blcgRequestPath ? $blcgRequestPath : false));
            $row->setData('do_not_use_category_id', true);
            
            $url = $row->getUrlModel()->getUrl($row, array('_nosid' => true));
            
            $row->setData('url', $previousUrl);
            $row->setData('url_data_object', $previousUrlDataObject);
            $row->setData('store_id', $previousStoreId);
            $row->setData('request_path', $previousRequestPath);
            $row->setData('do_not_use_category_id', $previousNoCategoryFlag);
        } else {
            $url = $row->getUrlModel()
                ->getUrlInstance()
                ->getUrl(
                    'catalog/product/view',
                    array(
                        'id'            => $row->getId(),
                        '_nosid'        => true,
                        '_store'        => $this->getColumn()->getBlcgStoreId(),
                        '_store_to_url' => true,
                    )
                );
        }
        return $url;
    }
    
    public function render(Varien_Object $row)
    {
        $url   = $this->_getRowProductUrl($row);
        $title = $this->getColumn()->getLinkTitle();
        return '<a ' . ($this->getColumn()->getOpenNewWindow() ? 'target="_blank" ' : '') . 'href="' . $url . '">'
            . $this->htmlEscape($title ? $title : $url) . '</a>';
    }
    
    public function renderExport(Varien_Object $row)
    {
        return $this->_getRowProductUrl($row);
    }
}
