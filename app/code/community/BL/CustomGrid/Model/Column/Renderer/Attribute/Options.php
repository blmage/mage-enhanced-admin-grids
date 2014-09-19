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

class BL_CustomGrid_Model_Column_Renderer_Attribute_Options
    extends BL_CustomGrid_Model_Column_Renderer_Attribute_Abstract
{
    public function isAppliableToAttribute(Mage_Eav_Model_Entity_Attribute $attribute,
        BL_CustomGrid_Model_Grid $gridModel)
    {
        return ($attribute->getSourceModel() != '')
            || ($attribute->getFrontendInput() == 'select')
            || ($attribute->getFrontendInput() == 'multiselect');
    }
    
    protected function _getAttributeOptions(Mage_Eav_Model_Entity_Attribute $attribute)
    {
        try {
            $options = $attribute->getSource()->getAllOptions(false, true);
        } catch (Exception $e) {
            $options = null;
        }
        return (!empty($options) ? $options : null);
    }
    
    public function getColumnBlockValues(Mage_Eav_Model_Entity_Attribute $attribute, Mage_Core_Model_Store $store,
        BL_CustomGrid_Model_Grid $gridModel)
    {
        $options  = null;
        $multiple = ($attribute->getFrontendInput() == 'multiselect');
        
        if ($this->getData('values/force_default_source')
            || !is_array($options = $this->_getAttributeOptions($attribute))) {
            if (($sourceId = $this->getData('values/default_source_id'))
                && ($source = Mage::getModel('customgrid/options_source')->load($sourceId))
                && $source->getId()) {
                $options = $source->getOptionsArray();
            } else {
                $options = array();
            }
        }
        
        return array(
            'renderer' => 'customgrid/widget_grid_column_renderer_options',
            'filter'   => 'customgrid/widget_grid_column_filter_select',
            'options'  => (is_array($options) ? $options : array()),
            'boolean_filter'     => (bool) $this->getData('values/boolean_filter'),
            'display_full_path'  => (bool) $this->getData('values/display_full_path'),
            'options_separator'  => $this->getData('values/options_separator'),
            'imploded_values'    => $multiple,
            'imploded_separator' => ($multiple ? ',' : null),
            'show_missing_option_values' => (bool) $this->getData('values/show_missing'),
        );
    }
}