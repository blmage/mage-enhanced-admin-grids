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

class BL_CustomGrid_Model_Column_Renderer_Collection_Store
    extends BL_CustomGrid_Model_Column_Renderer_Collection_Abstract
{
    public function getColumnGridValues($index, $store, $grid)
    {
        return array(
            'filter'          => 'customgrid/widget_grid_column_filter_store',
            'renderer'        => 'customgrid/widget_grid_column_renderer_store',
            'skip_website'    => (bool) $this->_getData('skip_website'),
            'skip_store'      => (bool) $this->_getData('skip_store'),
            'skip_store_view' => (bool) $this->_getData('skip_store_view'),
            'skip_all_views'  => (bool) $this->_getData('skip_all_views'),
            'flat_value_key'  => $this->_getData('flat_value_key'),
        );
    }
}