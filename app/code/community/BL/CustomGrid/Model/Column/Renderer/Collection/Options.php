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

class BL_CustomGrid_Model_Column_Renderer_Collection_Options
    extends BL_CustomGrid_Model_Column_Renderer_Collection_Abstract
{
    protected $_backwardsMap = array(
        'options_separator' => 'sub_values_separator',
    );
    
    public function getColumnBlockValues($columnIndex, Mage_Core_Model_Store $store,
        BL_CustomGrid_Model_Grid $gridModel)
    {
        if (($sourceId = $this->getData('values/source_id'))
            && ($source = Mage::getModel('customgrid/options_source')->load($sourceId))
            && $source->getId()) {
            $options = $source->getOptionsArray();
        } else {
            $options = array();
        }
        
        $implodedSeparator = $this->getData('values/imploded_separator');
        
        if (empty($implodedSeparator) && ($implodedSeparator != '0')) {
            $implodedSeparator = ',';
        }
        if (!$this->hasData('values/filter_mode') && $this->getData('values/boolean_filter')) {
            $this->setData(
                'values/filter_mode',
                BL_CustomGrid_Block_Widget_Grid_Column_Filter_Select::MODE_WITH_WITHOUT
            );
        }
        
        return array(
            'filter'   => 'customgrid/widget_grid_column_filter_select',
            'renderer' => 'customgrid/widget_grid_column_renderer_options',
            'options'  => (is_array($options) ? $options : array()),
            'imploded_values'      => (bool) $this->getData('values/imploded_values'),
            'imploded_separator'   => $implodedSeparator,
            'filter_mode'          => $this->getData('values/filter_mode'),
            'logical_operator'     => $this->getData('values/filter_logical_operator'),
            'negative_filter'      => (bool) $this->getData('values/negative_filter'),
            'display_full_path'    => (bool) $this->getData('values/display_full_path'),
            'values_separator'     => $this->getDataSetDefault('values/values_separator', ', '),
            'sub_values_separator' => $this->getDataSetDefault('values/sub_values_separator', ' - '),
            'show_missing_option_values' => (bool) $this->getData('values/show_missing'),
        );
    }
}