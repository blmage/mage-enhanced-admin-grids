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

class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Product_Image extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Return the name and the URL of the product image from the given row
     * 
     * @param Varien_Object $row Row product
     * @return array|null
     */
    protected function _getImageUrl(Varien_Object $row)
    {
        if (strlen($image = $this->_getValue($row)) && ($image != 'no_selection')) {
            $attributeCode = $this->getColumn()->getAttributeCode();
            /** @var $dummyProduct Mage_Catalog_Model_Product */
            $dummyProduct  = Mage::getModel('catalog/product');
            /** @var $helper Mage_Catalog_Helper_Image */
            $helper = $this->helper('catalog/image');
            $helper->init($dummyProduct, $attributeCode, $image);
            $helper->placeholder('bl/customgrid/images/catalog/product/placeholder.jpg');
            
            if (!$this->getColumn()->getBrowserResizeOnly()
                && (($width = (int) $this->getColumn()->getImageWidth()) > 0)
                && (($height = (int) $this->getColumn()->getImageHeight()) > 0)) {
                $helper->resize($width, $height);
            }
            
            return array($image, (string) $helper);
        }
        return null;
    }
    
    /**
     * Return the original URL of the product image from the given row
     * 
     * @param Varien_Object $row Row product
     * @return string|null
     */
    protected function _getOriginalImageUrl(Varien_Object $row)
    {
        return (strlen($image = $this->_getValue($row)) && ($image != 'no_selection'))
            ? Mage::getBaseUrl('media') . 'catalog/product/' . $image
            : null;
    }
    
    public function render(Varien_Object $row)
    {
        $result = '';
        $columnBlock = $this->getColumn();
        
        if ($images = $this->_getImageUrl($row)) {
            $image = ($columnBlock->getDisplayImagesUrls() ? $images[1] : $images[0]);
            
            if ($columnBlock->getDisplayImages()) {
                $title  = $this->htmlEscape($image);
                $source = $images[1];
                $dimensions  = ' ';
                
                if ((($width = (int) $columnBlock->getImageWidth()) > 0)
                    && (($height = (int) $columnBlock->getImageHeight()) > 0)) {
                    $dimensions = ' width="' . $width . '" height="' . $height . '" ';
                }
                
                $result = '<img src="' . $source . '" alt="' . $title . '" title="' . $title . '"' . $dimensions . '/>';
            } else {
                $result = $image;
            }
            if ($columnBlock->getOriginalImageLink() && ($imageUrl = $this->_getOriginalImageUrl($row))) {
                $result = '<a href="' . $imageUrl . '" target="_blank">' . $result . '</a>';
            }
        }
        
        return $result;
    }
    
    public function renderExport(Varien_Object $row)
    {
        return ($images = $this->_getImageUrl($row))
            ? ($this->getColumn()->getDisplayImagesUrls() ? $images[1] : $images[0])
            : '';
    }
}
