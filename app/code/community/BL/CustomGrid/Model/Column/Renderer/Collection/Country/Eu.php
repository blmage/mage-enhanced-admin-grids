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
 * @copyright  Copyright (c) 2013 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_Column_Renderer_Collection_Country_Eu
    extends BL_CustomGrid_Model_Column_Renderer_Collection_Abstract
{
    protected function _getSystemConfigCountries($path)
    {
        if (!is_array($countries = Mage::getStoreConfig($path))) {
            $countries = explode(',', $countries);
        }
        return $countries;
    }
    
    public function getColumnGridValues($index, $store, $grid)
    {
        $values = array(
            'renderer' => 'customgrid/widget_grid_column_renderer_country_eu',
            'filter'   => 'customgrid/widget_grid_column_filter_country_eu',
            'base_display_format'   => $this->_getData('base_display_format'),
            'export_display_format' => $this->_getData('export_display_format'),
        );
        
        $euCountries  = array();
        $allCountries = array();
        
        // Get EU countries
        if ((bool) $this->_getData('custom_source')) {
            if ((bool) $this->_getData('system_config_source')) {
                if ($path = $this->_getData('system_config_source_path')) {
                    // System configuration value
                    $euCountries = $this->_getSystemConfigCountries($path);
                }
            } elseif ($euCountries = $this->_getData('eu_countries')) {
                // User-defined value
                if (!is_array($euCountries)) {
                    $euCountries = explode(',', $euCountries);
                }
            }
        } elseif (Mage::helper('customgrid')->isMageVersionGreaterThan(1, 6)) {
            // Magento base value
            $euCountries = $this->_getSystemConfigCountries('general/country/eu_countries');
        }
        
        // Clean EU countries, prepare all countries
        $countries = Mage::getResourceModel('directory/country_collection')
            ->loadData()
            ->toOptionArray(false);
        
        $countries   = Mage::helper('customgrid')->getOptionsHashFromOptionsArray($countries);
        $isEuCountry = array();
        
        foreach ($euCountries as $key => $code) {
            if (!isset($countries[$code])) {
                unset($euCountries[$key]);
            } else {
                $isEuCountry[$code] = true;
            }
        }
        foreach ($countries as $code => $name) {
            $allCountries[$code] = new Varien_Object(array(
                'code'  => $code,
                'name'  => $name,
                'is_eu' => isset($isEuCountry[$code]),
            ));
        }
        
        $values['eu_countries']  = array_unique($euCountries);
        $values['all_countries'] = $allCountries;
        
        return $values;
    }
}