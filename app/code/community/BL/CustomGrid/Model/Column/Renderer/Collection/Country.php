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
 * @copyright  Copyright (c) 2016 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_Column_Renderer_Collection_Country extends BL_CustomGrid_Model_Column_Renderer_Collection_Abstract
{
    public function getColumnBlockValues(
        $columnIndex,
        Mage_Core_Model_Store $store,
        BL_CustomGrid_Model_Grid $gridModel
    ) {
        /** @var $countriesCollection Mage_Directory_Model_Mysql4_Country_Collection */
        $countriesCollection = Mage::getResourceModel('directory/country_collection');
        $options = $countriesCollection->load()->toOptionArray(false);
        $implodedSeparator = $this->getData('values/imploded_separator');
        
        if (empty($implodedSeparator) && ($implodedSeparator != '0')) {
            $implodedSeparator = ',';
        }
        
        return array(
            'filter'   => 'customgrid/widget_grid_column_filter_select',
            'renderer' => 'customgrid/widget_grid_column_renderer_options',
            'options'  => $options,
            'empty_option_label'   => $this->_getHelper()->__('All Countries'),
            'imploded_values'      => (bool) $this->getData('values/imploded_values'),
            'imploded_separator'   => $implodedSeparator,
            'filter_mode'          => $this->getData('values/filter_mode'),
            'logical_operator'     => $this->getData('values/filter_logical_operator'),
            'negative_filter'      => (bool) $this->getData('values/negative_filter'),
            'values_separator'     => $this->getDataSetDefault('values/values_separator', ', '),
            'show_missing_option_values' => (bool) $this->getData('values/show_missing'),
        );
    }
}
