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

class BL_CustomGrid_Helper_Config extends Mage_Core_Helper_Abstract
{
    const GRID_EXCEPTION_HANDLING_MODE_ALLOW = 'allow';
    const GRID_EXCEPTION_HANDLING_MODE_EXCLUDE = 'exclude';
    
    /**
     * Configuration paths
     */
    
    // Global
    const CONFIG_PATH_EXCLUSIONS_LIST = 'customgrid/global/exclusions_list';
    const CONFIG_PATH_EXCEPTIONS_LIST = 'customgrid/global/exceptions_list';
    const CONFIG_PATH_EXCEPTIONS_HANDLING_MODE = 'customgrid/global/exceptions_handling_mode';
    const CONFIG_PATH_STORE_PARAMETER = 'customgrid/global/store_parameter';
    const CONFIG_PATH_SORT_WITH_DND   = 'customgrid/global/sort_with_dnd';
    
    // Customization parameters
    const CONFIG_PATH_DISPLAY_SYSTEM_PART        = 'customgrid/customization_params/display_system_part';
    const CONFIG_PATH_IGNORE_CUSTOM_HEADERS      = 'customgrid/customization_params/ignore_custom_headers';
    const CONFIG_PATH_IGNORE_CUSTOM_WIDTHS       = 'customgrid/customization_params/ignore_custom_widths';
    const CONFIG_PATH_IGNORE_CUSTOM_ALIGNMENTS   = 'customgrid/customization_params/ignore_custom_alignments';
    const CONFIG_PATH_PAGINATION_VALUES          = 'customgrid/customization_params/pagination_values';
    const CONFIG_PATH_DEFAULT_PAGINATION_VALUE   = 'customgrid/customization_params/default_pagination_value';
    const CONFIG_PATH_MERGE_BASE_PAGINATION      = 'customgrid/customization_params/merge_base_pagination';
    const CONFIG_PATH_PIN_HEADER                 = 'customgrid/customization_params/pin_header';
    const CONFIG_PATH_RSS_LINKS_WINDOW           = 'customgrid/customization_params/rss_links_window';
    const CONFIG_PATH_HIDE_ORIGINAL_EXPORT_BLOCK = 'customgrid/customization_params/hide_original_export_block';
    const CONFIG_PATH_HIDE_FILTER_RESET_BUTTON   = 'customgrid/customization_params/hide_filter_reset_button';
    
    // Default parameters behaviours
    const CONFIG_PATH_DEFAULT_PARAMETER_BEHAVIOUR_BASE_KEY = 'customgrid/default_params_behaviours/%s';
    
    // Profiles
    const CONFIG_PATH_PROFILES_DEFAULT_RESTRICTED        = 'customgrid/profiles/default_restricted';
    const CONFIG_PATH_PROFILES_DEFAULT_ASSIGNED_TO       = 'customgrid/profiles/default_assigned_to';
    const CONFIG_PATH_PROFILES_REMEMBERED_SESSION_PARAMS = 'customgrid/profiles/remembered_session_params';
    
    // Custom columns
    const CONFIG_PATH_GROUP_IN_CC_DEFAULT_HEADER         = 'customgrid/custom_columns/group_in_default_header';
    const CONFIG_PATH_CC_UNVERIFIED_BLOCK_BEHAVIOUR      = 'customgrid/custom_columns/unverified_block_behaviour';
    const CONFIG_PATH_CC_UNVERIFIED_COLLECTION_BEHAVIOUR = 'customgrid/custom_columns/unverified_collection_behaviour';
    
    protected $_configCache = array();
    
    /**
     * Return the base helper
     *
     * @return BL_CustomGrid_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('customgrid');
    }
    
    /**
     * Return the array value corresponding to the given config path, unserializing it if needed
     *
     * @param string $path Config path
     * @return array
     */
    protected function _getSerializedArrayConfigValue($path)
    {
        if (!is_array($values = Mage::getStoreConfig($path))) { 
            $values = $this->_getHelper()->unserializeArray($values);
        }
        return $values;
    }
    
    /**
     * Return the PRCE regex corresponding to the given exception pattern
     *
     * @param string $pattern Exception pattern
     * @return string
     */
    protected function _prepareExceptionPattern($pattern)
    {
        return str_replace(array('\\*', '\\.'), array('.*', '.'), '#' . preg_quote($pattern, '#') . '#i');
    }
    
    /**
     * Parse and return the array of exceptions corresponding to the given config path.
     * An exception array consists of two values :
     * - "block_type" : PCRE regex to match against block types
     * - "rewriting_class_name" : PCRE regex to match against rewriting class names
     *
     * @param string $path Config path
     * @return array
     */
    protected function _getExceptionsListConfigValue($path)
    {
        $exceptions = $this->_getSerializedArrayConfigValue($path);
        
        foreach ($exceptions as $key => $exception) {
            $exceptions[$key] = array(
                'block_type' => $this->_prepareExceptionPattern($exception['block_type']),
                'rewriting_class_name' => $this->_prepareExceptionPattern($exception['rewriting_class_name']),
            );
        }
        
        return $exceptions;
    }
    
    /**
     * Match the given block type and its rewriting class name against the given exception
     *
     * @param array $exception Parsed exception
     * @param string $blockType Grid block type
     * @param string $rewritingClassName Rewriting class name
     * @return bool
     */
    protected function _matchGridBlockAgainstException($exception, $blockType, $rewritingClassName)
    {
        return preg_match($exception['block_type'], $blockType)
            && preg_match($exception['rewriting_class_name'], $rewritingClassName);
    }
    
    /**
     * Getter for the config value "General" > "Global Exclusions"
     *
     * @return array
     */
    public function getExclusionsList()
    {
        if (!isset($this->_configCache['exclusions_list'])) {
            $value = $this->_getExceptionsListConfigValue(self::CONFIG_PATH_EXCLUSIONS_LIST);
            $this->_configCache['exclusions_list'] = $value;
        }
        return $this->_configCache['exclusions_list'];
    }
    
    /**
     * Getter for the config value "General" > "Complementary Exceptions"
     *
     * @return array
     */
    public function getExceptionsList()
    {
        if (!isset($this->_configCache['exceptions_list'])) {
            $value = $this->_getExceptionsListConfigValue(self::CONFIG_PATH_EXCEPTIONS_LIST);
            $this->_configCache['exceptions_list'] = $value;
        }
        return $this->_configCache['exceptions_list'];
    }
    
    /**
     * Getter for the config value "General" > "Complementary Exceptions Handling Mode"
     *
     * @return array
     */
    public function getExceptionsHandlingMode()
    {
        return Mage::getStoreConfig(self::CONFIG_PATH_EXCEPTIONS_HANDLING_MODE);
    }
    
    /**
     * Return whether the given grid block should not be rewrited
     *
     * @param string $blockType Grid block type
     * @param string $rewritingClassName Rewriting class name
     * @return bool
     */
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
    
    /**
     * Getter for the config value "General" > "Store View ID Parameter"
     *
     * @param mixed $default Default value to return if the config value is not set
     * @return mixed
     */
    public function getStoreParameter($default = null)
    {
        $value = trim(Mage::getStoreConfig(self::CONFIG_PATH_STORE_PARAMETER));
        return ($value !== '' ? $value : $default);
    }
    
    /**
     * Getter for the config value "General" > "Use drag'n'drop for sorting columns"
     *
     * @return bool
     */
    public function getSortWithDnd()
    {
        return Mage::getStoreConfigFlag(self::CONFIG_PATH_SORT_WITH_DND);
    }
    
    /**
     * Getter for the config value "Customization Parameters - Default Values" > "Display "System" Column"
     *
     * @return bool
     */
    public function getDisplaySystemPart()
    {
        return Mage::getStoreConfigFlag(self::CONFIG_PATH_DISPLAY_SYSTEM_PART);
    }
    
    /**
     * Getter for the config value "Customization Parameters - Default Values" > "Ignore Custom Headers"
     *
     * @return bool
     */
    public function getIgnoreCustomHeaders()
    {
        return Mage::getStoreConfigFlag(self::CONFIG_PATH_IGNORE_CUSTOM_HEADERS);
    }
    
    /**
     * Getter for the config value "Customization Parameters - Default Values" > "Ignore Custom Widths"
     *
     * @return bool
     */
    public function getIgnoreCustomWidths()
    {
        return Mage::getStoreConfigFlag(self::CONFIG_PATH_IGNORE_CUSTOM_WIDTHS);
    }
    
    /**
     * Getter for the config value "Customization Parameters - Default Values" > "Ignore Custom Alignments"
     *
     * @return bool
     */
    public function getIgnoreCustomAlignments()
    {
        return Mage::getStoreConfigFlag(self::CONFIG_PATH_IGNORE_CUSTOM_ALIGNMENTS);
    }
    
    /**
     * Getter for the config value "Customization Parameters - Default Values" > "Custom Pagination Values"
     *
     * @return int[]
     */
    public function getPaginationValues()
    {
        if (!isset($this->_configCache['pagination_values'])) {
            $this->_configCache['pagination_values'] = $this->_getHelper()
                ->parseCsvIntArray(Mage::getStoreConfig(self::CONFIG_PATH_PAGINATION_VALUES), true, true, 1);
        }
        return $this->_configCache['pagination_values'];
    }
    
    /**
     * Getter for the config value "Customization Parameters - Default Values" > "Default Pagination Value"
     *
     * @return int
     */
    public function getDefaultPaginationValue()
    {
        return (int) Mage::getStoreConfig(self::CONFIG_PATH_DEFAULT_PAGINATION_VALUE);
    }
    
    /**
     * Getter for the config value "Customization Parameters - Default Values" > "Merge Base Pagination Values"
     *
     * @return bool
     */
    public function getMergeBasePagination()
    {
        return Mage::getStoreConfigFlag(self::CONFIG_PATH_MERGE_BASE_PAGINATION);
    }
    
    /**
     * Getter for the config value "Customization Parameters - Default Values" > "Pin Pager And Mass-Actions Block"
     *
     * @return bool
     */
    public function getPinHeader()
    {
        return Mage::getStoreConfigFlag(self::CONFIG_PATH_PIN_HEADER);
    }
    
    /**
     * Getter for the config value "Customization Parameters - Default Values" > "Move RSS Links In Dedicated Window"
     *
     * @return bool
     */
    public function getUseRssLinksWindow()
    {
        return Mage::getStoreConfigFlag(self::CONFIG_PATH_RSS_LINKS_WINDOW);
    }
    
    /**
     * Getter for the config value "Customization Parameters - Default Values" > "Hide Original Export Block"
     *
     * @return bool
     */
    public function getHideOriginalExportBlock()
    {
        return Mage::getStoreConfigFlag(self::CONFIG_PATH_HIDE_ORIGINAL_EXPORT_BLOCK);
    }
    
    /**
     * Getter for the config value "Customization Parameters - Default Values" > "Hide Original Filter Reset Button"
     *
     * @return bool
     */
    public function getHideFilterResetButton()
    {
        return Mage::getStoreConfigFlag(self::CONFIG_PATH_HIDE_FILTER_RESET_BUTTON);
    }
    
    /**
     * Getter for the config values under "Default Parameters Behaviours - Default Values"
     *
     * @param string $type Config field key (can be : page, limit, sort, dir, filter)
     * @return string
     */
    public function geDefaultParamBehaviour($type)
    {
        return Mage::getStoreConfig(sprintf(self::CONFIG_PATH_DEFAULT_PARAMETER_BEHAVIOUR_BASE_KEY, $type));
    }
    
    /**
     * Getter for the config value "Profiles - Default Values" > "Restricted"
     *
     * @return true
     */
    public function getProfilesDefaultRestricted()
    {
        return Mage::getStoreConfigFlag(self::CONFIG_PATH_PROFILES_DEFAULT_RESTRICTED); 
    }
    
    /**
     * Getter for the config value "Profiles - Default Values" > "Remembered Session Parameters"
     *
     * @return string[]
     */
    public function getProfilesRememberedSessionParams()
    {
        if (!isset($this->_configCache['profiles_remembered_session_params'])) {
            $value = Mage::getStoreConfig(self::CONFIG_PATH_PROFILES_REMEMBERED_SESSION_PARAMS);
            $this->_configCache['profiles_remembered_session_params'] = explode(',', $value);
        }
        return $this->_configCache['profiles_remembered_session_params'];
    }
    
    /**
     * Getter for the config value "Profiles - Default Values" > "Assigned To"
     *
     * @return int[]
     */
    public function getProfilesDefaultAssignedTo()
    {
        if (!isset($this->_configCache['profiles_default_assigned_to'])) {
            $helper = $this->_getHelper();
            $value  = Mage::getStoreConfig(self::CONFIG_PATH_PROFILES_DEFAULT_ASSIGNED_TO);
            $this->_configCache['profiles_default_assigned_to'] = $helper->parseCsvIntArray($value, true, false, 1);
        }
        return $this->_configCache['profiles_default_assigned_to'];
    }
    
    /**
     * Getter for the config value "Custom Columns" > "Add Group Name In Default Header"
     *
     * @return bool
     */
    public function getAddGroupToCustomColumnsDefaultHeader()
    {
        return Mage::getStoreConfigFlag(self::CONFIG_PATH_GROUP_IN_CC_DEFAULT_HEADER);
    }
    
    /**
     * Getter for the config value "Custom Columns" > "Behaviour When Unverified Grid Block"
     *
     * @return string
     */
    public function getCustomColumnsUnverifiedBlockBehaviour()
    {
        return Mage::getStoreConfig(self::CONFIG_PATH_CC_UNVERIFIED_BLOCK_BEHAVIOUR);
    }
    
    /**
     * Getter for the config value "Custom Columns" > "Behaviour When Unverified Grid Collection"
     *
     * @return string
     */
    public function getCustomColumnsUnverifiedCollectionBehaviour()
    {
        return Mage::getStoreConfig(self::CONFIG_PATH_CC_UNVERIFIED_COLLECTION_BEHAVIOUR);
    }
}
