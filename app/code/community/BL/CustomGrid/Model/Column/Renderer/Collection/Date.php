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

class BL_CustomGrid_Model_Column_Renderer_Collection_Date
    extends BL_CustomGrid_Model_Column_Renderer_Collection_Abstract
{
    public function getColumnGridValues($index, $store, $grid)
    {
        $values = array(
            /*
            Use datetime filter, as there doesnt seem to be any inconvenience for it,
            and because using a simple date filter can cause problems when we have date fields 
            stored on datetime database values, and for which there a time is set, different than 0:00,
            depending on timezone, which lead to excluding rows from search that should be taken
            */
            'filter'      => 'customgrid/widget_grid_column_filter_datetime',
            'renderer'    => 'customgrid/widget_grid_column_renderer_date',
            'filter_time' => false,
        );
        
        if ($format = $this->_getData('format')) {
            try {
                $values['format'] = Mage::app()->getLocale()->getDateFormat($format);
            } catch (Exception $e) {
                $values['format'] = null;
            }
        }
        
        return $values;
    }
}