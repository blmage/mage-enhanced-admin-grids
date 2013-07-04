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

class BL_CustomGrid_Model_Column_Renderer_Attribute_Product_Image
    extends BL_CustomGrid_Model_Column_Renderer_Attribute_Abstract
{
    public function isAppliableToColumn($attribute, $grid)
    {
        return ($attribute->getFrontendModel() == 'catalog/product_attribute_frontend_image');
    }
    
    public function getColumnGridValues($attribute, $store, $grid)
    {
        return array(
            'filter'              => 'customgrid/widget_grid_column_filter_product_image',
            'renderer'            => 'customgrid/widget_grid_column_renderer_product_image',
            'display_images'      => (bool)$this->_getData('display_images'),
            'display_images_urls' => (bool)$this->_getData('display_images_urls'),
            'original_image_link' => (bool)$this->_getData('original_image_link'),
            'image_width'         => $this->_getData('image_width'),
            'image_height'        => $this->_getData('image_height'),
            'browser_resize_only' => (bool)$this->_getData('browser_resize_only'),
            'filter_on_name'      => (bool)$this->_getData('filter_on_name'),
            'attribute_code'      => $attribute->getAttributeCode(),
        );
    }
}