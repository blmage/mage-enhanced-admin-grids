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

class BL_CustomGrid_Model_Column_Renderer_Attribute_Product_Image extends BL_CustomGrid_Model_Column_Renderer_Attribute_Abstract
{
    public function isAppliableToAttribute(
        Mage_Eav_Model_Entity_Attribute $attribute,
        BL_CustomGrid_Model_Grid $gridModel
    ) {
        return ($attribute->getFrontendInput() == 'media_image')
            || ($attribute->getFrontendModel() == 'catalog/product_attribute_frontend_image');
    }
    
    public function getColumnBlockValues(
        Mage_Eav_Model_Entity_Attribute $attribute,
        Mage_Core_Model_Store $store,
        BL_CustomGrid_Model_Grid $gridModel
    ) {
        return array(
            'filter'              => 'customgrid/widget_grid_column_filter_product_image',
            'renderer'            => 'customgrid/widget_grid_column_renderer_product_image',
            'attribute_code'      => $attribute->getAttributeCode(),
            'display_images'      => (bool) $this->getData('values/display_images'),
            'display_images_urls' => (bool) $this->getData('values/display_images_urls'),
            'original_image_link' => (bool) $this->getData('values/original_image_link'),
            'image_width'         => $this->getData('values/image_width'),
            'image_height'        => $this->getData('values/image_height'),
            'browser_resize_only' => (bool) $this->getData('values/browser_resize_only'),
            'filter_on_name'      => (bool) $this->getData('values/filter_on_name'),
        );
    }
}
