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
    protected $_backwardsMap = array(
        'options_separator' => 'sub_values_separator',
    );
    
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
        $options = null;
        $isMultiple = ($attribute->getFrontendInput() == 'multiselect');
        
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
            'imploded_values'      => $isMultiple,
            'imploded_separator'   => ($isMultiple ? ',' : null),
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