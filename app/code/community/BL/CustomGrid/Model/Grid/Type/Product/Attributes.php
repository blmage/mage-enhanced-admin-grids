<?php

class BL_CustomGrid_Model_Grid_Type_Product_Attributes extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    /**
     * @return string[]|string
     */
    protected function _getSupportedBlockTypes()
    {
        return array(
            'adminhtml/catalog_product_attribute_grid',
        );
    }
}
