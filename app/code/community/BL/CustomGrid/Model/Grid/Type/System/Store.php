<?php

class BL_CustomGrid_Model_Grid_Type_System_Store extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    /**
     * @return string[]|string
     */
    protected function _getSupportedBlockTypes()
    {
        return array('adminhtml/system_store_grid');
    }
}
