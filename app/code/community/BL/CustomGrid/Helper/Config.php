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

class BL_CustomGrid_Helper_Config
    extends Mage_Core_Helper_Abstract
{
    // Global
    const CONFIG_PATH_EXCLUSIONS_LIST = 'customgrid/global/exclusions_list';
    const CONFIG_PATH_EXCEPTIONS_LIST = 'customgrid/global/exceptions_list';
    const CONFIG_PATH_EXCEPTIONS_HANDLING_MODE = 'customgrid/global/exceptions_handling_mode';
    const CONFIG_PATH_STORE_PARAMETER = 'customgrid/global/store_parameter';
    const CONFIG_PATH_SORT_WITH_DND   = 'customgrid/global/sort_with_dnd';
    
    // Customization parameters
    const CONFIG_PATH_IGNORE_CUSTOM_HEADERS    = 'customgrid/customization_params/ignore_custom_headers';
    const CONFIG_PATH_IGNORE_CUSTOM_WIDTHS     = 'customgrid/customization_params/ignore_custom_widths';
    const CONFIG_PATH_IGNORE_CUSTOM_ALIGNMENTS = 'customgrid/customization_params/ignore_custom_alignments';
    const CONFIG_PATH_PAGINATION_VALUES        = 'customgrid/customization_params/pagination_values';
    const CONFIG_PATH_DEFAULT_PAGINATION_VALUE = 'customgrid/customization_params/default_pagination_value';
    const CONFIG_PATH_MERGE_BASE_PAGINATION    = 'customgrid/customization_params/merge_base_pagination';
    const CONFIG_PATH_PIN_HEADER               = 'customgrid/customization_params/pin_header';
    
    // Default parameters behaviours
    const CONFIG_PATH_DEFAULT_PARAMETER_BEHAVIOUR_BASE_KEY = 'customgrid/default_params_behaviours/%s';
    
    // Profiles
    const CONFIG_PATH_PROFILES_DEFAULT_RESTRICTED  = 'customgrid/profiles/default_restricted';
    const CONFIG_PATH_PROFILES_DEFAULT_ASSIGNED_TO = 'customgrid/profiles/default_assigned_to';
    
    // Custom columns
    const CONFIG_PATH_GROUP_IN_CUSTOM_COLUMNS_DEFAULT_HEADER = 'customgrid/custom_columns/group_in_default_header';
    
    const GRID_EXCEPTION_HANDLING_MODE_ALLOW = 'allow';
    const GRID_EXCEPTION_HANDLING_MODE_EXCLUDE = 'exclude';
    
    protected $_configCache = array();
    
    protected function _getHelper()
    {
        return Mage::helper('customgrid');
    }
    
    protected function _getSerializedArrayConfig($path)
    {
        $values = Mage::getStoreConfig($path);
        
        if (!is_array($values)) { 
            $values = $this->_getHelper()->unserializeArray($values);
        }
        
        return $values;
    }
    
    
    protected function _prepareExceptionPattern($pattern)
    {
        return str_replace(array('\\*', '\\.'), array('.*', '.'), '#' . preg_quote($pattern, '#') . '#i');
    }
    
    protected function _getExceptionsListConfig($path)
    {
        $exceptions = $this->_getSerializedArrayConfig($path);
        
        foreach ($exceptions as $key => $exception) {
            // Prepare exceptions patterns
            $exceptions[$key] = array(
                'block_type' => $this->_prepareExceptionPattern($exception['block_type']),
                'rewriting_class_name' => $this->_prepareExceptionPattern($exception['rewriting_class_name']),
            );
        }
        
        return $exceptions;
    }
    
    protected function _matchGridBlockAgainstException($exception, $blockType, $rewritingClassName)
    {
        return preg_match($exception['block_type'], $blockType)
            && preg_match($exception['rewriting_class_name'], $rewritingClassName);
    }
    
    public function addGridToExclusionsList($blockType, $rewritingClassName, $reinitConfig=false)
    {
        $exclusionsList = $this->_getSerializedArrayConfig(self::CONFIG_PATH_EXCLUSIONS_LIST);
        $isExistingExclusion = false;
        
        foreach ($exclusionsList as $exclusion) {
            if (($exclusion['block_type'] === $blockType)
                && ($exclusion['rewriting_class_name'] === $rewritingClassName)) {
                $isExistingExclusion = true;
                break;
            }
        }
        
        if (!$isExistingExclusion) {
            $exclusionsList[] = array(
                'block_type' => $blockType,
                'rewriting_class_name' => $rewritingClassName
            );
            
            Mage::getConfig()->saveConfig(self::CONFIG_PATH_EXCLUSIONS_LIST, serialize($exclusionsList), 'default', 0);
            
            if ($reinitConfig) {
                Mage::getConfig()->reinit();
                Mage::app()->reinitStores();
            }
        }
        
        return $this;
    }
    
    public function getExclusionsList()
    {
        if (!isset($this->_configCache['exclusions_list'])) {
            $this->_configCache['exclusions_list'] = $this->_getExceptionsListConfig(self::CONFIG_PATH_EXCLUSIONS_LIST);
        }
        return $this->_configCache['exclusions_list'];
    }
    
    public function getExceptionsList()
    {
        if (!isset($this->_configCache['exceptions_list'])) {
            $this->_configCache['exceptions_list'] = $this->_getExceptionsListConfig(self::CONFIG_PATH_EXCEPTIONS_LIST);
        }
        return $this->_configCache['exceptions_list'];
    }
    
    public function getExceptionsHandlingMode()
    {
        return Mage::getStoreConfig(self::CONFIG_PATH_EXCEPTIONS_HANDLING_MODE);
    }
    
    public function isExcludedGridBlock($blockType, $rewritingClassName)
    {
        foreach ($this->getExclusionsList() as $exclusion) {
            if ($this->_matchGridBlockAgainstException($exclusion, $blockType, $rewritingClassName)) {
                return true;
            }
        }
        foreach ($this->getExceptionsList() as $exception) {
            if ($this->_matchGridBlockAgainstException($exception, $blockType, $rewritingClassName)) {
                return ($this->getExceptionsHandlingMode() == self::GRID_EXCEPTION_HANDLING_MODE_EXCLUDE);
            }
        }
        return ($this->getExceptionsHandlingMode() == self::GRID_EXCEPTION_HANDLING_MODE_ALLOW);
    }
    
    public function getStoreParameter($default=null)
    {
        $value = trim(Mage::getStoreConfig(self::CONFIG_PATH_STORE_PARAMETER));
        return ($value !== '' ? $value : $default);
    }
    
    public function getSortWithDnd()
    {
        return Mage::getStoreConfigFlag(self::CONFIG_PATH_SORT_WITH_DND);
    }
    
    public function getIgnoreCustomHeaders()
    {
        return Mage::getStoreConfigFlag(self::CONFIG_PATH_IGNORE_CUSTOM_HEADERS);
    }
    
    public function getIgnoreCustomWidths()
    {
        return Mage::getStoreConfigFlag(self::CONFIG_PATH_IGNORE_CUSTOM_WIDTHS);
    }
    
    public function getIgnoreCustomAlignments()
    {
        return Mage::getStoreConfigFlag(self::CONFIG_PATH_IGNORE_CUSTOM_ALIGNMENTS);
    }
    
    public function getPaginationValues()
    {
        if (!isset($this->_configCache['pagination_values'])) {
            $this->_configCache['pagination_values'] = $this->_getHelper()
                ->parseCsvIntArray(Mage::getStoreConfig(self::CONFIG_PATH_PAGINATION_VALUES), true, true, 1);
        }
        return $this->_configCache['pagination_values'];
    }
    
    public function getDefaultPaginationValue()
    {
        return (int) Mage::getStoreConfig(self::CONFIG_PATH_DEFAULT_PAGINATION_VALUE);
    }
    
    public function getMergeBasePagination()
    {
        return Mage::getStoreConfigFlag(self::CONFIG_PATH_MERGE_BASE_PAGINATION);
    }
    
    public function getPinHeader()
    {
        return Mage::getStoreConfigFlag(self::CONFIG_PATH_PIN_HEADER);
    }
    
    public function geDefaultParameterBehaviour($type)
    {
        return Mage::getStoreConfig(sprintf(self::CONFIG_PATH_DEFAULT_PARAMETER_BEHAVIOUR_BASE_KEY, $type));
    }
    
    public function getProfilesDefaultRestricted()
    {
       return Mage::getStoreConfigFlag(self::CONFIG_PATH_PROFILES_DEFAULT_RESTRICTED); 
    }
    
    public function getProfilesDefaultAssignedTo()
    {
        if (!isset($this->_configCache['profiles_default_assigned_to'])) {
            $helper = $this->_getHelper();
            $value  = Mage::getStoreConfig(self::CONFIG_PATH_PROFILES_DEFAULT_ASSIGNED_TO);
            $this->_configCache['profiles_default_assigned_to'] = $helper->parseCsvIntArray($value, true, false, 1);
        }
        return $this->_configCache['profiles_default_assigned_to'];
    }
    
    public function getAddGroupToCustomColumnsDefaultHeader()
    {
        return Mage::getStoreConfigFlag(self::CONFIG_PATH_GROUP_IN_CUSTOM_COLUMNS_DEFAULT_HEADER);
    }
}