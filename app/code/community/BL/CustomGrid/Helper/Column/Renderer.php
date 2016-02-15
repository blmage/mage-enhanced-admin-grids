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

class BL_CustomGrid_Helper_Column_Renderer extends Mage_Core_Helper_Abstract
{
    const CURRENCY_TYPE_BASE   = 'base_currency';
    const CURRENCY_TYPE_COLUMN = 'column_currency';
    
    /**
     * Return date(-time) related values
     * 
     * @param BL_CustomGrid_Model_Column_Renderer_Abstract $renderer Column renderer
     * @param bool $isDateRenderer Whether the given renderer is "date-only"
     * @return array
     */
    public function getDateTimeValues(BL_CustomGrid_Model_Column_Renderer_Abstract $renderer, $isDateRenderer = false)
    {
        $values = array(
            'filter'      => 'customgrid/widget_grid_column_filter_datetime',
            'renderer'    => 'customgrid/widget_grid_column_renderer_date' . (!$isDateRenderer ? 'time' : ''),
            'filter_time' => ($isDateRenderer && $renderer->getData('values/filter_time')),
        );
        
        if ($format = $renderer->getData('values/format')) {
            try {
                if ($isDateRenderer) {
                    $values['format'] = Mage::app()->getLocale()->getDateFormat($format);
                } else {
                    $values['format'] = Mage::app()->getLocale()->getDateTimeFormat($format);
                }
            } catch (Exception $e) {
                $values['format'] = null;
            }
        }
        
        return $values;
    }
    
    /**
     * Return number related values
     * 
     * @param BL_CustomGrid_Model_Column_Renderer_Abstract $renderer Column renderer
     */
    public function getNumberValues(BL_CustomGrid_Model_Column_Renderer_Abstract $renderer)
    {
        return array(
            'type' => 'number',
            'show_number_sign' => (bool) $renderer->getData('values/show_number_sign'),
        );
    }
    
    /**
     * Return options related values
     * 
     * @param BL_CustomGrid_Model_Column_Renderer_Abstract $renderer Column renderer
     * @param array $options Options array
     * @param bool $implodedValues Whether values are imploded
     * @param string $implodedSeparator String used to separate imploded values
     * @return array
     */
    public function getOptionsValues(
        BL_CustomGrid_Model_Column_Renderer_Abstract $renderer,
        array $options,
        $implodedValues,
        $implodedSeparator
    ) {
        if (!$renderer->hasData('values/filter_mode') && $renderer->getData('values/boolean_filter')) {
            $renderer->setData(
                'values/filter_mode',
                BL_CustomGrid_Block_Widget_Grid_Column_Filter_Select::MODE_WITH_WITHOUT
            );
        }
        
        return array(
            'filter'   => 'customgrid/widget_grid_column_filter_select',
            'renderer' => 'customgrid/widget_grid_column_renderer_options',
            'options'  => (is_array($options) ? $options : array()),
            'imploded_values'      => $implodedValues,
            'imploded_separator'   => ($implodedValues ? $implodedSeparator : null),
            'filter_mode'          => $renderer->getData('values/filter_mode'),
            'logical_operator'     => $renderer->getData('values/filter_logical_operator'),
            'negative_filter'      => (bool) $renderer->getData('values/negative_filter'),
            'display_full_path'    => (bool) $renderer->getData('values/display_full_path'),
            'values_separator'     => $renderer->getDataSetDefault('values/values_separator', ', '),
            'sub_values_separator' => $renderer->getDataSetDefault('values/sub_values_separator', ' - '),
            'show_missing_option_values' => (bool) $renderer->getData('values/show_missing'),
        );
    }
    
    /**
     * Return sub currency value for price related values
     * 
     * @param BL_CustomGrid_Model_Column_Renderer_Abstract $renderer Column renderer
     * @param mixed $baseCode Base code of the currency value
     * @param Mage_Core_Model_Store $store Column store model
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @return array
     */
    protected function _getPriceCurrencyValue(
        BL_CustomGrid_Model_Column_Renderer_Abstract $renderer,
        $baseCode,
        Mage_Core_Model_Store $store,
        BL_CustomGrid_Model_Grid $gridModel
    ) {
        $isFixedCurrency = true;
        
        if (($currency = $renderer->getData('values/' . $baseCode . '_currency')) == self::CURRENCY_TYPE_BASE) {
            // Base currency
            $currency = $store->getBaseCurrency()->getCode();
            
        } elseif ($currency == self::CURRENCY_TYPE_COLUMN) {
            // Currency from column value
            $columnType = $renderer->getData('values/' . $baseCode . '_currency_column_type');
            $isFixedCurrency = false;
            
            if (($columnType == BL_CustomGrid_Model_Grid_Column::ORIGIN_ATTRIBUTE)
                || ($columnType == BL_CustomGrid_Model_Grid_Column::ORIGIN_CUSTOM)) {
                $currency = $gridModel->getColumnIndexFromCode(
                    $renderer->getData('values/' . $baseCode . '_currency_column_index'),
                    $columnType,
                    (int) $renderer->getData('values/' . $baseCode . '_currency_column_position')
                );
            } else {
                $currency = $gridModel->getColumnIndexFromCode(
                    $renderer->getData('values/' . $baseCode . '_currency_column'),
                    $columnType
                );
            }
        } // Else fixed currency code
        
        if ($isFixedCurrency) {
            $currencySourceName = 'customgrid/column_renderer_source_currency';
            $allowedCurrencies  = Mage::getModel($currencySourceName)->toOptionHash();
            
            if (!isset($allowedCurrencies[$currency])) {
                $currency = $store->getBaseCurrency()->getCode();
            }
            
            $key = $baseCode . '_currency_code';
        } else {
            $key = $baseCode . '_currency';
        }
        
        return array($key => $currency);
    }
    
    /**
     * Return price related values
     * 
     * @param BL_CustomGrid_Model_Column_Renderer_Abstract $renderer Column renderer
     * @param Mage_Core_Model_Store $store Column store model
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @return array
     */
    public function getPriceValues(
        BL_CustomGrid_Model_Column_Renderer_Abstract $renderer,
        Mage_Core_Model_Store $store,
        BL_CustomGrid_Model_Grid $gridModel
    ) {
        $values = array(
            'filter'   => 'customgrid/widget_grid_column_filter_price',
            'renderer' => 'customgrid/widget_grid_column_renderer_price',
            'default_currency_code' => $store->getBaseCurrency()->getCode(),
        );
        
        $values += $this->_getPriceCurrencyValue($renderer, 'original', $store, $gridModel);
        $values += $this->_getPriceCurrencyValue($renderer, 'display', $store, $gridModel);
        $values += array('apply_rates' => (bool) $renderer->getData('values/apply_rates'));
        
        return $values;
    }
    
    /**
     * Return the wildcards usable for text related values
     * 
     * @param BL_CustomGrid_Model_Column_Renderer_Abstract $renderer Column renderer
     * @return array
     */
    protected function _getTextWildcards(BL_CustomGrid_Model_Column_Renderer_Abstract $renderer)
    {
        /** @var $stringHelper Mage_Core_Helper_String */
        $stringHelper = Mage::helper('core/string');
        $values = array();
        $singleWildcard = strval($renderer->getData('values/single_wildcard'));
        $multipleWildcard = strval($renderer->getData('values/multiple_wildcard'));
        
        if ($stringHelper->strlen($singleWildcard) === 1) {
            $values['single_wildcard'] = $singleWildcard;
        }
        if (($stringHelper->strlen($multipleWildcard) === 1)
            && ($multipleWildcard !== $singleWildcard)) {
            $values['multiple_wildcard'] = $multipleWildcard;
        }
        
        return $values;
    }
    
    /**
     * Return text related values
     * 
     * @param BL_CustomGrid_Model_Column_Renderer_Abstract $renderer Column renderer
     * @return array
     */
    public function getTextValues(BL_CustomGrid_Model_Column_Renderer_Abstract $renderer)
    {
        $values = array(
            'filter'                   => 'customgrid/widget_grid_column_filter_text',
            'renderer'                 => 'customgrid/widget_grid_column_renderer_text',
            'filter_mode_shortcut'     => (bool) $renderer->getDataSetDefault('values/filter_mode_shortcut', true),
            'negative_filter_shortcut' => (bool) $renderer->getDataSetDefault('values/negative_filter_shortcut', true),
            'truncation_mode'          => $renderer->getData('values/truncation_mode'),
            'truncation_at'            => (int) $renderer->getData('values/truncation_at'),
            'truncation_ending'        => $renderer->getData('values/truncation_ending'),
            'exact_truncation'         => (bool) $renderer->getData('values/exact_truncation'),
            'escape_html'              => (bool) $renderer->getData('values/escape_html'),
            'nl2br'                    => (bool) $renderer->getData('values/nl2br'),
            'cms_template_processor'   => $renderer->getData('values/cms_template_processor'),
        );
        
        if ($renderer->hasData('values/filter_mode')) {
            $values['filter_mode'] = $renderer->getData('values/filter_mode');
            $values['negative_filter'] = (bool) $renderer->getData('values/negative_filter');
        } else {
            $values['filter_mode'] = $renderer->getData('values/exact_filter')
                ? BL_CustomGrid_Block_Widget_Grid_Column_Filter_Text::MODE_EXACT_LIKE
                : BL_CustomGrid_Block_Widget_Grid_Column_Filter_Text::MODE_INSIDE_LIKE;
            $values['negative_filter'] = false;
        }
        
        if ($values['filter_mode_shortcut']
            || ($values['filter_mode'] == BL_CustomGrid_Block_Widget_Grid_Column_Filter_Text::MODE_EXACT_LIKE)
            || ($values['filter_mode'] == BL_CustomGrid_Block_Widget_Grid_Column_Filter_Text::MODE_INSIDE_LIKE)) {
            $values += $this->_getTextWildcards($renderer);
        }
        
        return $values;
    }
}
