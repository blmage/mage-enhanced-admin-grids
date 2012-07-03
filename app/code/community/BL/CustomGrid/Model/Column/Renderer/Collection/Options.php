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

class BL_CustomGrid_Model_Column_Renderer_Collection_Options
    extends BL_CustomGrid_Model_Column_Renderer_Collection_Abstract
{
    public function getColumnGridValues($index, $store, $grid)
    {
        if (($sourceId = $this->_getData('source_id'))
             && ($source = Mage::getModel('customgrid/options_source')->load($sourceId))
             && $source->getId()) {
            $options = $source->getOptionsArray();
        } else {
            $options = array();
        }
        
        $implodedSeparator = $this->_getData('imploded_separator');
        if (empty($implodedSeparator) && ($implodedSeparator != '0')) {
            $implodedSeparator = ',';
        }
        
        return array(
            'filter'   => 'customgrid/widget_grid_column_filter_select',
            'renderer' => 'customgrid/widget_grid_column_renderer_options',
            'options'  => $options,
            'boolean_filter'     => (bool) $this->_getData('boolean_filter'),
            'display_full_path'  => (bool) $this->_getData('display_full_path'),
            'options_separator'  => $this->_getData('options_separator'),
            'imploded_values'    => (bool) $this->_getData('imploded_values'),
            'imploded_separator' => $implodedSeparator,
            'show_missing_option_values' => (bool) $this->_getData('show_missing'),
        );
    }
}