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
 * @copyright  Copyright (c) 2013 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Block_Widget_Grid_Columns_Filters
    extends Mage_Adminhtml_Block_Template
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('bl/customgrid/widget/grid/columns/filters.phtml');
    }
    
    public function getColumns()
    {
        $columns = array();
        
        if ($gridBlock = $this->getGridBlock()) {
            foreach ($gridBlock->getColumns() as $column) {
                if ($column->getBlcgFilterOnly()) {
                    $columns[] = $column;
                }
            }
        }
        
        return $columns;
    }
    
    protected function _toHtml()
    {
        if (!$this->getIsNewGridModel()
            && ($gridBlock = $this->getGridBlock())
            && $gridBlock->getFilterVisibility()) {
            return parent::_toHtml();
        }
        return '';
    }
    
    // @todo next step: either enable sortability, either only apply filters, do not select values when it's not needed (eg for attribute columns and custom columns with sub-queries)
}