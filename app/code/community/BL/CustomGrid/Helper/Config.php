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

class BL_CustomGrid_Helper_Config extends Mage_Core_Helper_Abstract
{
    const XML_GLOBAL_EXCEPTIONS_HANDLING_MODE         = 'customgrid/global/exceptions_handling_mode';
    const XML_GLOBAL_EXCEPTIONS_LIST                  = 'customgrid/global/exceptions_list';
    const XML_GLOBAL_EXCLUSIONS_LIST                  = 'customgrid/global/exclusions_list';
    const XML_GLOBAL_FORCE_GRID_REWRITES              = 'customgrid/global/force_grid_rewrites';
    const XML_GLOBAL_STORE_PARAMETER                  = 'customgrid/global/store_parameter';
    const XML_GLOBAL_SORT_WITH_DND                    = 'customgrid/global/sort_with_dnd';
    const XML_CUSTOM_PARAMS_IGNORE_CUSTOM_HEADERS     = 'customgrid/customization_params/ignore_custom_headers';
    const XML_CUSTOM_PARAMS_IGNORE_CUSTOM_WIDTHS      = 'customgrid/customization_params/ignore_custom_widths';
    const XML_CUSTOM_PARAMS_IGNORE_CUSTOM_ALIGNMENTS  = 'customgrid/customization_params/ignore_custom_alignments';
    const XML_CUSTOM_PARAMS_PAGINATION_VALUES         = 'customgrid/customization_params/pagination_values';
    const XML_CUSTOM_PARAMS_DEFAULT_PAGINATION_VALUE  = 'customgrid/customization_params/default_pagination_value';
    const XML_CUSTOM_PARAMS_MERGE_BASE_PAGINATION     = 'customgrid/customization_params/merge_base_pagination';
    const XML_CUSTOM_PARAMS_PIN_HEADER                = 'customgrid/customization_params/pin_header';
    const XML_CUSTOM_DEFAULT_PARAM_BEHAVIOUR_BASE_KEY = 'customgrid/custom_default_params/%s';
    const XML_CUSTOM_COLUMNS_GROUP_IN_DEFAULT_HEADER  = 'customgrid/custom_columns/group_in_default_header';
    
    const GRID_EXCEPTION_HANDLING_EXCLUDE = 'exclude';
    const GRID_EXCEPTION_HANDLING_ALLOW   = 'allow';
    
    protected $_configCache = array();
    
    protected function _prepareExceptionPattern($pattern)
    {
        return str_replace(array('\\*', '\\.'), array('.*', '.'), '#' . preg_quote($pattern, '#') . '#i');
    }
    
    protected function _getSerializedArrayConfig($key)
    {
        $values = Mage::getStoreConfig($key);
        
        if (!is_array($values)) {
            /* 
            Unserialize values if needed 
            (should always be the case, as _afterLoad is not called with getStoreConfig)
            */
            $values = Mage::helper('customgrid')->unserializeArray($values);
        }
        
        return $values;
    }
    
    protected function _getExceptionsListConfig($key)
    {
        $exceptions = $this->_getSerializedArrayConfig($key);
        
        foreach ($exceptions as $key => $exception) {
            // Prepare exceptions patterns
            $exceptions[$key] = array(
                'block_type' => $this->_prepareExceptionPattern($exception['block_type']),
                'rewriting_class_name' => $this->_prepareExceptionPattern($exception['rewriting_class_name']),
            );
        }
        
        return $exceptions;
    }
    
    protected function _matchGridAgainstException($exception, $blockType, $rewritingClassName)
    {
        return (preg_match($exception['block_type'], $blockType)
            && preg_match($exception['rewriting_class_name'], $rewritingClassName));
    }
    
    public function addGridToExclusionsList($blockType, $rewritingClassName, $reinit=false)
    {
        $list  = $this->_getSerializedArrayConfig(self::XML_GLOBAL_EXCLUSIONS_LIST);
        $found = false;
        
        foreach ($list as $exclusion) {
            if (($exclusion['block_type'] === $blockType)
                && ($exclusion['rewriting_class_name'] === $rewritingClassName)) {
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $list[] = array(
                'block_type' => $blockType,
                'rewriting_class_name' => $rewritingClassName
            );
            
            Mage::getConfig()->saveConfig(self::XML_GLOBAL_EXCLUSIONS_LIST, serialize($list), 'default', 0);
            
            if ($reinit) {
                Mage::getConfig()->reinit();
                Mage::app()->reinitStores();
            }
        }
        
        return $this;
    }
    
    public function getExclusionsList()
    {
        if (!isset($this->_configCache['exclusions_list'])) {
            $this->_configCache['exclusions_list'] = $this->_getExceptionsListConfig(self::XML_GLOBAL_EXCLUSIONS_LIST);
        }
        return $this->_configCache['exclusions_list'];
    }
    
    public function getExceptionsHandlingMode()
    {
        return Mage::getStoreConfig(self::XML_GLOBAL_EXCEPTIONS_HANDLING_MODE);
    }
    
    public function getExceptionsList()
    {
        if (!isset($this->_configCache['exceptions_list'])) {
            $this->_configCache['exceptions_list'] = $this->_getExceptionsListConfig(self::XML_GLOBAL_EXCEPTIONS_LIST);
        }
        return $this->_configCache['exceptions_list'];
    }
    
    public function isExcludedGrid($blockType, $rewritingClassName)
    {
        foreach ($this->getExclusionsList() as $exclusion) {
            if ($this->_matchGridAgainstException($exclusion, $blockType, $rewritingClassName)) {
                return true;
            }
        }
        foreach ($this->getExceptionsList() as $exception) {
            if ($this->_matchGridAgainstException($exception, $blockType, $rewritingClassName)) {
                return ($this->getExceptionsHandlingMode() == self::GRID_EXCEPTION_HANDLING_EXCLUDE);
            }
        }
        return ($this->getExceptionsHandlingMode() == self::GRID_EXCEPTION_HANDLING_ALLOW);
    }
    
    public function getForceGridRewrites()
    {
        return Mage::getStoreConfigFlag(self::XML_GLOBAL_FORCE_GRID_REWRITES);
    }
    
    public function getStoreParameter($default=null)
    {
        return ((($value = Mage::getStoreConfig(self::XML_GLOBAL_STORE_PARAMETER)) !== '') ? $value : $default);
    }
    
    public function getSortWithDnd()
    {
        return Mage::getStoreConfig(self::XML_GLOBAL_SORT_WITH_DND);
    }
    
    public function getIgnoreCustomHeaders()
    {
        return Mage::getStoreConfigFlag(self::XML_CUSTOM_PARAMS_IGNORE_CUSTOM_HEADERS);
    }
    
    public function getIgnoreCustomWidths()
    {
        return Mage::getStoreConfigFlag(self::XML_CUSTOM_PARAMS_IGNORE_CUSTOM_WIDTHS);
    }
    
    public function getIgnoreCustomAlignments()
    {
        return Mage::getStoreConfigFlag(self::XML_CUSTOM_PARAMS_IGNORE_CUSTOM_ALIGNMENTS);
    }
    
    public function getPaginationValues()
    {
        if (!isset($this->_configCache['pagination_values'])) {
            $this->_configCache['pagination_values'] = Mage::helper('customgrid')
                ->parseCsvIntArray(Mage::getStoreConfig(self::XML_CUSTOM_PARAMS_PAGINATION_VALUES), true, true, 1);
        }
        return $this->_configCache['pagination_values'];
    }
    
    public function getDefaultPaginationValue($checkInList=true, $smallestDefault=true, $valuesList=null)
    {
        /*
        $value = intval(Mage::getStoreConfig(self::XML_CUSTOM_PARAMS_DEFAULT_PAGINATION_VALUE));
        
        if (is_null($valuesList) && ($smallestDefault || ($value && $checkInList))) {
            $valuesList = $this->getPaginationValues();
        }
        if ($value) {
            if ($checkInList && !in_array($value, $valuesList)) {
                $value = false;
            }
        }
        if (!$value && $smallestDefault && !empty($valuesList)) {
            $value = array_reduce($valuesList, 'min');
        } else {
            $value = false;
        }
        
        return $value;
        */
        return intval(Mage::getStoreConfig(self::XML_CUSTOM_PARAMS_DEFAULT_PAGINATION_VALUE));
    }
    
    public function getMergeBasePagination()
    {
        return Mage::getStoreConfigFlag(self::XML_CUSTOM_PARAMS_MERGE_BASE_PAGINATION);
    }
    
    public function getPinHeader()
    {
        return Mage::getStoreConfigFlag(self::XML_CUSTOM_PARAMS_PIN_HEADER);
    }
    
    public function getCustomDefaultParamBehaviour($type)
    {
        return Mage::getStoreConfig(sprintf(self::XML_CUSTOM_DEFAULT_PARAM_BEHAVIOUR_BASE_KEY, $type));
    }
    
    public function getAddGroupToCustomColumnsDefaultHeader()
    {
        return Mage::getStoreConfigFlag(self::XML_CUSTOM_COLUMNS_GROUP_IN_DEFAULT_HEADER);
    }
}