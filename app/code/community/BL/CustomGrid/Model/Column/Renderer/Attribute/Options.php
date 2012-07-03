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

class BL_CustomGrid_Model_Column_Renderer_Attribute_Options
    extends BL_CustomGrid_Model_Column_Renderer_Attribute_Abstract
{
    public function isAppliableToColumn($attribute, $grid)
    {
        return (($attribute->getSourceModel() != '')
                || ($attribute->getFrontendInput() == 'select')
                || ($attribute->getFrontendInput() == 'multiselect'));
    }
    
    protected function _getAttributeOptions($attribute)
    {
        try {
            $options = $attribute->getSource()->getAllOptions(false, true);
        } catch (Exception $e) {
            $options = null;
        }
        return (!empty($options) ? $options : null);
    }
    
    public function getColumnGridValues($attribute, $store, $grid)
    {
        if (((bool)$this->_getData('force_default_source'))
            || (!is_array($options = $this->_getAttributeOptions($attribute)))) {
            if (($sourceId = $this->_getData('default_source_id'))
                && ($source = Mage::getModel('customgrid/options_source')->load($sourceId))
                && $source->getId()) {
                $options = $source->getOptionsArray();
            } else {
                $options = array();
            }
            $fromAttribute = false;
        } else {
            $fromAttribute = true;
        }
        
        $multiple = ($attribute->getFrontendInput() == 'multiselect');
        
        return array(
            'filter'   => 'customgrid/widget_grid_column_filter_select',
            'renderer' => 'customgrid/widget_grid_column_renderer_options',
            'options'  => (is_array($options) ? $options : array()),
            'boolean_filter'     => (bool) $this->_getData('boolean_filter'),
            'display_full_path'  => (bool) $this->_getData('display_full_path'),
            'options_separator'  => $this->_getData('options_separator'),
            'imploded_values'    => $multiple,
            'imploded_separator' => ($multiple ? ',' : null),
            'show_missing_option_values' => (bool) $this->_getData('show_missing'),
        );
    }
}