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
 * @copyright  Copyright (c) 2015 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_Column_Renderer_Collection_Country_Eu extends BL_CustomGrid_Model_Column_Renderer_Collection_Abstract
{
    protected function _getSystemConfigCountries($configPath)
    {
        return !is_array($countries = Mage::getStoreConfig($configPath))
            ? explode(',', $countries)
            : $countries;
    }
    
    protected function _getEuCountries()
    {
        $euCountries = array();
        
        if ($this->getData('values/custom_source')) {
            if ($this->getData('values/system_config_source')) {
                if ($configPath = $this->getData('values/system_config_source_path')) {
                    $euCountries = $this->_getSystemConfigCountries($configPath);
                }
            } elseif ($euCountries = $this->getData('values/eu_countries')) {
                if (!is_array($euCountries)) {
                    $euCountries = explode(',', $euCountries);
                }
            }
        } elseif ($this->_getHelper()->isMageVersionGreaterThan(1, 6)) {
            $euCountries = $this->_getSystemConfigCountries('general/country/eu_countries');
        }
        
        return $euCountries;
    }
    
    public function getColumnBlockValues(
        $columnIndex,
        Mage_Core_Model_Store $store,
        BL_CustomGrid_Model_Grid $gridModel
    ) {
        $values = array(
            'filter'   => 'customgrid/widget_grid_column_filter_country_eu',
            'renderer' => 'customgrid/widget_grid_column_renderer_country_eu',
            'base_display_format'   => $this->getData('values/base_display_format'),
            'export_display_format' => $this->getData('values/export_display_format'),
        );
        
        $euCountries  = $this->_getEuCountries();
        $allCountries = array();
        /** @var $countriesCollection Mage_Directory_Model_Mysql4_Country_Collection */
        $countriesCollection = Mage::getResourceModel('directory/country_collection');
        $countries = $countriesCollection->loadData()->toOptionArray(false);
        
        $countries  = $this->_getHelper()->getOptionHashFromOptionArray($countries);
        $isEuCountry = array();
        
        foreach ($euCountries as $key => $code) {
            if (!isset($countries[$code])) {
                unset($euCountries[$key]);
            } else {
                $isEuCountry[$code] = true;
            }
        }
        foreach ($countries as $code => $name) {
            $allCountries[$code] = new BL_CustomGrid_Object(
                array(
                    'code'  => $code,
                    'name'  => $name,
                    'is_eu' => isset($isEuCountry[$code]),
                )
            );
        }
        
        $values['eu_countries']  = array_unique($euCountries);
        $values['all_countries'] = $allCountries;
        return $values;
    }
}
