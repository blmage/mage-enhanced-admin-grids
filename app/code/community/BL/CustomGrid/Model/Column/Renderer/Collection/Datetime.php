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
 * @copyright  Copyright (c) 2014 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_Column_Renderer_Collection_Datetime
    extends BL_CustomGrid_Model_Column_Renderer_Collection_Abstract
{
    public function getColumnBlockValues($columnIndex, Mage_Core_Model_Store $store,
        BL_CustomGrid_Model_Grid $gridModel)
    {
        $values = array(
            'renderer'    => 'customgrid/widget_grid_column_renderer_datetime',
            'filter'      => 'customgrid/widget_grid_column_filter_datetime',
            'filter_time' => (bool) $this->getData('values/filter_time'),
        );
        
        if ($format = $this->getData('values/format')) {
            try {
                $values['format'] = Mage::app()->getLocale()->getDateTimeFormat($format);
            } catch (Exception $e) {
                $values['format'] = null;
            }
        }
        
        return $values;
    }
}