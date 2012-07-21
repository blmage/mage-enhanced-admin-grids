<?php

class BL_CustomGrid_Block_Widget_Grid_Column_Filter_Yesno
    extends BL_CustomGrid_Block_Widget_Grid_Column_Filter_Select
{
    protected function _getOptions()
    {
        return array(
            array(
                'value' => 1,
                'label' => Mage::helper('customgrid')->__('Yes'),
            ),
            array(
                'value' => 0,
                'label' => Mage::helper('customgrid')->__('No'),
            ),
        );
    }
}