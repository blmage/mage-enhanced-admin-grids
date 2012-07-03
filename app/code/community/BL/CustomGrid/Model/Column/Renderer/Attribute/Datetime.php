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

class BL_CustomGrid_Model_Column_Renderer_Attribute_Datetime
    extends BL_CustomGrid_Model_Column_Renderer_Attribute_Abstract
{
    public function isAppliableToColumn($attribute, $grid)
    {
        return ($attribute->getBackendType() == 'datetime');
    }
    
    public function getColumnGridValues($attribute, $store, $grid)
    {
        $values = array(
            'filter' => 'customgrid/widget_grid_column_filter_datetime',
        );
        
        if ($attribute->getFrontendInput() == 'date') {
            $values['renderer'] = 'customgrid/widget_grid_column_renderer_date';
            $values['filter_time'] = false;
            
            if ($format = $this->_getData('format')) {
                try {
                    $values['format'] = Mage::app()->getLocale()->getDateFormat($format);
                } catch (Exception $e) {
                    $values['format'] = null;
                }
            }
        } else {
            $values['renderer'] = 'customgrid/widget_grid_column_renderer_datetime';
            $values['filter_time'] = ($this->_getData('filter_time') ? true : false);
            
            if ($format = $this->_getData('format')) {
                try {
                    $values['format'] = Mage::app()->getLocale()->getDateTimeFormat($format);
                } catch (Exception $e) {
                    $values['format'] = null;
                }
            }
        }
        
        return $values;
    }
}