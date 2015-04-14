<?php

class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Csv extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        // pretty up the CSV for human consumption
        $value = parent::_getValue($row);
        $values = explode(",", $value);
        return implode(", ", $values);
    }

    public function renderExport(Varien_Object $row)
    {
        return parent::_getValue($row);
    }
}
