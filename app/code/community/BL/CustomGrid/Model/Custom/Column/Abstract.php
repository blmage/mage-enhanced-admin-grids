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

/**
 * @method string getId() Return the ID of this custom column
 * @method BL_CustomGrid_Model_Custom_Column_Abstract setId(string $id) Set the custom column ID
 * @method BL_CustomGrid_Model_Custom_Column_Abstract setModule(string $module) Set the origin module
 * @method BL_CustomGrid_Model_Custom_Column_Abstract setGroup(string $group) Set the custom column group
 * @method BL_CustomGrid_Model_Custom_Column_Abstract setAllowCustomization(bool $flag) Set whether this column is customizable
 * @method BL_CustomGrid_Model_Custom_Column_Abstract setAllowRenderers(bool $flag) Set whether a renderer can be applied to the corresponding grid columns
 * @method BL_CustomGrid_Model_Custom_Column_Abstract setAllowStore(bool $flag) Set whether a base store view can be applied to the corresponding grid columns
 * @method BL_CustomGrid_Model_Custom_Column_Abstract setCurrentBlockValues(array $currentBlockValues)
 */

abstract class BL_CustomGrid_Model_Custom_Column_Abstract extends BL_CustomGrid_Object
{
    // Grid collection events on which to apply callbacks
    const GC_EVENT_BEFORE_PREPARE     = 'before_prepare';
    const GC_EVENT_AFTER_PREPARE      = 'after_prepare';
    const GC_EVENT_BEFORE_SET_FILTERS = 'before_set_filters';
    const GC_EVENT_AFTER_SET_FILTERS  = 'after_set_filters';
    const GC_EVENT_BEFORE_SET         = 'before_set';
    const GC_EVENT_AFTER_SET          = 'after_set';
    const GC_EVENT_BEFORE_EXPORT_LOAD = 'before_export_load';
    const GC_EVENT_AFTER_EXPORT_LOAD  = 'after_export_load';
    
    const WORKER_TYPE_APPLIER = 'applier';
    const WORKER_TYPE_COLLECTION_HANDLER = 'collection_handler';
    
    protected function _construct()
    {
        parent::_construct();
        $this->_initializeConfig();
    }
    
    /**
     * Return the worker model of the given type
     *
     * @param string $type Worker type
     * @return BL_CustomGrid_Model_Custom_Column_Worker_Abstract
     */
    protected function _getWorker($type)
    {
        /** @var BL_CustomGrid_Helper_Worker $helper */
        $helper = Mage::helper('customgrid/worker');
        return $helper->getModelWorker($this, $type);
    }
    
    /**
     * Return the applier model, usable to apply this custom column to a given grid block
     *
     * @return BL_CustomGrid_Model_Custom_Column_Applier
     */
    public function getApplier()
    {
        return $this->_getWorker(self::WORKER_TYPE_APPLIER);
    }
    
    /**
     * Return the collection handler model
     *
     * @return BL_CustomGrid_Model_Custom_Column_Collection_Handler
     */
    public function getCollectionHandler()
    {
        return $this->_getWorker(self::WORKER_TYPE_COLLECTION_HANDLER);
    }
    
    /**
     * Return the base helper
     * 
     * @return BL_CustomGrid_Helper_Data
     */
    public function getBaseHelper()
    {
        return Mage::helper('customgrid');
    }
    
    /**
     * Return grid block helper
     * 
     * @return BL_CustomGrid_Helper_Grid
     */
    public function getGridHelper()
    {
        return Mage::helper('customgrid/grid');
    }
    
    /**
     * Prepare configuration data
     * 
     * @return BL_CustomGrid_Model_Custom_Column_Abstract
     */
    protected function _prepareConfig()
    {
        return $this;
    }
    
    /**
     * Initialize and prepare configuration data
     * 
     * @return BL_CustomGrid_Model_Custom_Column_Abstract
     */
    protected function _initializeConfig()
    {
        // Initialize base data keys
        $booleanKeys = array(
            'allow_store',
            'allow_renderers',
            'allow_customization',
            'allow_editor',
        );
        
        $arrayKeys = array(
            'allowed_versions',
            'excluded_versions',
            'allowed_blocks',
            'excluded_blocks',
            'allowed_rewrites',
            'excluded_rewrites',
            'config_params',
            'block_params',
            'customization_params',
            'customization_window_config',
        );
        
        foreach ($booleanKeys as $key) {
            $this->setData($key, false);
        }
        foreach ($arrayKeys as $key) {
            $this->setData($key, array());
        }
        
        // Initialize from XML values if possible
        if (is_array($xmlValues = $this->getData('xml_values'))
            && (($xmlElement = $this->getData('xml_element')) instanceof Varien_Simplexml_Element)) {
            /** @var $configModel BL_CustomGrid_Model_Custom_Column_Config */
            $configModel = Mage::getSingleton('customgrid/custom_column_config');
            $configModel->initializeCustomColumnFromXmlConfig($this, $xmlElement, $xmlValues);
        }
        
        $this->unsetData('xml_element');
        $this->unsetData('xml_values');
        
        // Finish config preparation
        $this->_prepareConfig();
        
        return $this;
    }
    
    /**
     * Set the given array value into the current configuration data
     * 
     * @param string $key Data key
     * @param mixed $value Config value (will be turned into an array if needed)
     * @param bool $merge Whether the given value should be merged with the existing one
     * @return BL_CustomGrid_Model_Custom_Column_Abstract
     */
    protected function _setConfigArrayValue($key, $value, $merge = false)
    {
        if ($merge) {
            if (!is_array($currentValue = $this->_getData($key))) {
                $currentValue = array();
            }
            $value = array_merge($currentValue, (array) $value);
        }
        return $this->setData($key, (array) $value);
    }
    
    /**
     * Set or add allowed Magento versions patterns
     * 
     * @param mixed $versions Allowed Magento versions patterns
     * @param bool $merge Whether given patterns should be merged with the existing ones
     * @return BL_CustomGrid_Model_Custom_Column_Abstract
     */
    public function setAllowedVersions($versions, $merge = false)
    {
        return $this->_setConfigArrayValue('allowed_versions', $versions, $merge);
    }
    
    /**
     * Set or add excluded Magento versions patterns
     * 
     * @param mixed $versions Excluded Magento versions patterns
     * @param bool $merge Whether given patterns should be merged with the existing ones
     * @return BL_CustomGrid_Model_Custom_Column_Abstract
     */
    public function setExcludedVersions($versions, $merge = false)
    {
        return $this->_setConfigArrayValue('excluded_versions', $versions, $merge);
    }
    
    /**
     * Set or add allowed block types patterns
     * 
     * @param mixed $blocks Allowed block types patterns
     * @param bool $merge Whether given patterns should be merged with the existing ones
     * @return BL_CustomGrid_Model_Custom_Column_Abstract
     */
    public function setAllowedBlocks($blocks, $merge = false)
    {
        return $this->_setConfigArrayValue('allowed_blocks', $blocks, $merge);
    }
    
    /**
     * Set or add excluded block types patterns
     * 
     * @param mixed $blocks Excluded block types patterns
     * @param bool $merge Whether given patterns should be merged with the existing ones
     * @return BL_CustomGrid_Model_Custom_Column_Abstract
     */
    public function setExcludedBlocks($blocks, $merge = false)
    {
        return $this->_setConfigArrayValue('excluded_blocks', $blocks, $merge);
    }
    
    /**
     * Set or add allowed rewriting class names patterns
     * 
     * @param mixed $rewrites Allowed rewriting class names patterns
     * @param bool $merge Whether given patterns should be merged with the existing ones
     * @return BL_CustomGrid_Model_Custom_Column_Abstract
     */
    public function setAllowedRewrites($rewrites, $merge = false)
    {
        return $this->_setConfigArrayValue('allowed_rewrites', $rewrites, $merge);
    }
    
    /**
     * Set or add excluded rewriting class names patterns
     * 
     * @param mixed $rewrites Excluded rewriting class names patterns
     * @param bool $merge Whether given patterns should be merged with the existing ones
     * @return BL_CustomGrid_Model_Custom_Column_Abstract
     */
    public function setExcludedRewrites($rewrites, $merge = false)
    {
        return $this->_setConfigArrayValue('excluded_rewrites', $rewrites, $merge);
    }
    
    /**
     * Set or add configuration parameters
     * 
     * @param array $params Config parameters
     * @param bool $merge Whether given parameters should be merged with the existing ones
     * @return BL_CustomGrid_Model_Custom_Column_Abstract
     */
    public function setConfigParams(array $params, $merge = false)
    {
        return $this->_setConfigArrayValue('config_params', $params, $merge);
    }
    
    /**
     * Return the value of the given configuration parameter
     * 
     * @param string $key Parameter key
     * @param mixed $default Value to return if the given parameter is not set
     * @return mixed
     */
    public function getConfigParam($key, $default = null)
    {
        return ($this->hasData('config_params/' . $key) ? $this->getData('config_params/' . $key) : $default);
    }
    
    /**
     * Set or add block parameters
     * 
     * @param array $params Block parameters
     * @param bool $merge Whether given parameters should be merged with the existing ones
     * @return BL_CustomGrid_Model_Custom_Column_Abstract
     */
    public function setBlockParams(array $params, $merge = false)
    {
        return $this->_setConfigArrayValue('block_params', $params, $merge);
    }
    
    /**
     * Return the value of the given block parameter
     * 
     * @param string $key Parameter key
     * @param mixed $default Value to return if the given parameter is not set
     * @return mixed
     */
    public function getBlockParam($key, $default = null)
    {
        return ($this->hasData('block_params/' . $key) ? $this->getData('block_params/' . $key) : $default);
    }
    
    /**
     * Customization parameters sort callback
     * 
     * @param BL_CustomGrid_Object $paramA One customization parameter
     * @param BL_CustomGrid_Object $paramB Another customization parameter
     * @return int
     */
    protected function _sortCustomizationParams(BL_CustomGrid_Object $paramA, BL_CustomGrid_Object $paramB)
    {
        $aOrder = $paramA->getSortOrder();
        $bOrder = $paramB->getSortOrder();
        return ($aOrder < $bOrder ? -1 : ($aOrder > $bOrder ? 1 : strcmp($paramA->getLabel(), $paramB->getLabel())));
    }
    
    /**
     * Return the customization parameters
     * 
     * @param bool $sorted Whether parameters should be sorted
     * @return BL_CustomGrid_Object[]
     */
    public function getCustomizationParams($sorted = true)
    {
        $params = $this->_getData('customization_params');
        
        if ($sorted) {
            uasort($params, array($this, '_sortCustomizationParams'));
        }
        
        return $params;
    }
    
    /**
     * Add the given customization parameter to the list
     * 
     * @param string $key Parameter key
     * @param array $data Config values
     * @param int $sortOrder Sort order
     * @param bool $override Whether the existing parameter for the same key should be overriden (if appropriate)
     * @return BL_CustomGrid_Model_Custom_Column_Abstract
     */
    public function addCustomizationParam($key, array $data, $sortOrder, $override = true)
    {
        if ($override || !$this->getData('customization_params/' . $key)) {
            $data['sort_order'] = (int) $sortOrder;
            $data['key'] = $key;
            $data['visible'] = (isset($data['visible']) ? (bool) $data['visible'] : true);
            
            $paramObject = new BL_CustomGrid_Object($data);
            $this->setData('customization_params/' . $key, $paramObject);
        }
        return $this;
    }
    
    /**
     * Set or add customization window configuration values
     * 
     * @param array $data Configuration values
     * @param bool $merge Whether given values should be merged with the existing ones
     * @return BL_CustomGrid_Model_Custom_Column_Abstract
     */
    public function setCustomizationWindowConfig(array $data, $merge = false)
    {
        return $this->_setConfigArrayValue('customization_window_config', $data, $merge);
    }
    
    /**
     * Set the name of this custom column,
     * and, if necessary, initialize the title of the customization window accordingly
     * 
     * @param string $name Column name
     * @return BL_CustomGrid_Model_Custom_Column_Abstract
     */
    public function setName($name)
    {
        $this->setData('name', $name);
        
        if (!$this->getData('customization_window_config/title')) {
            $this->setData(
                'customization_window_config/title',
                $this->getBaseHelper()->__('Customization : %s', $name)
            );
        }
        
        return $this;
    }
    
    /**
     * Turn given availability pattern into a ready-to-use regex string
     * ("*" matches any number of characters, "?" exactly one)
     * 
     * @param string $value Availability pattern
     * @return string
     */
    protected function _getAvailabilityRegex($value)
    {
        return str_replace(array('\\*', '\\?'), array('.*', '.'), '#' . preg_quote($value, '#') . '#i');
    }
    
    /**
     * Check the given availability patterns against the given reference value
     * 
     * @param mixed $patterns Availability patterns (if not an array or if empty, true will be returned)
     * @param string $referenceValue Reference value
     * @param bool $excluded Whether the availability patterns concern excludable values
     * @return bool If at least one pattern matched the reference value : false for excludable values, true otherwise
     */
    protected function _checkAvailabilityPatterns($patterns, $referenceValue, $excluded = false)
    {
        if (!is_array($patterns) || empty($patterns)) {
            return true;
        }
        
        $matched = false;
        
        foreach ($patterns as $pattern) {
            if (preg_match($this->_getAvailabilityRegex($pattern), $referenceValue)) {
                $matched = true;
                break;
            }
        }
        
        return ($matched ? !$excluded : $excluded);
    }
    
    /**
     * Return whether the custom column is available for the given block type and rewriting class
     * 
     * @param string $blockType Grid block type
     * @param string $rewritingClassName Grid rewriting class
     * @return bool
     */
    public function isAvailable($blockType, $rewritingClassName)
    {
        $mageVersion = Mage::getVersion();
        return ($this->_checkAvailabilityPatterns($this->getAllowedVersions(), $mageVersion)
            && $this->_checkAvailabilityPatterns($this->getExcludedVersions(), $mageVersion, true)
            && $this->_checkAvailabilityPatterns($this->getAllowedBlocks(), $blockType)
            && $this->_checkAvailabilityPatterns($this->getExcludedBlocks(), $blockType, true)
            && $this->_checkAvailabilityPatterns($this->getAllowedRewrites(), $rewritingClassName)
            && $this->_checkAvailabilityPatterns($this->getExcludedRewrites(), $rewritingClassName, true));
    }
    
    /**
     * Return whether the previous filters applied to the grid column should be invalidated
     * 
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param BL_CustomGrid_Model_Grid_Column $columnModel Grid column model
     * @param array $params Customization parameters values
     * @param array $renderers Previous and current renderer codes (keys: "previous" and "current")
     * @return bool
     */
    public function shouldInvalidateFilters(
        BL_CustomGrid_Model_Grid $gridModel,
        BL_CustomGrid_Model_Grid_Column $columnModel,
        array $params,
        array $renderers
    ) {
        return ($renderers['previous'] !== $renderers['current']);
    }
    
    /**
     * Extract a boolean value from the given key of the given array
     * 
     * @param array $params Parameters array
     * @param string $key Value key
     * @param mixed $default Default value to return if value is not set
     * @return mixed
     */
    protected function _extractBoolParam(array $params, $key, $default = false)
    {
        return (isset($params[$key]) ? (bool) $params[$key] : $default);
    }
    
    /**
     * Extract an integer value from the given key of the given array
     * 
     * @param array $params Parameters array
     * @param string $key Value key
     * @param mixed $default Default value to return if value is not set
     * @param bool $notEmpty Whether the default value should be returned if the value is set but is an empty string
     * @return mixed
     */
    protected function _extractIntParam(array $params, $key, $default = null, $notEmpty = false)
    {
        return isset($params[$key])
            ? ((!$notEmpty || (strval($params[$key]) !== '')) ? (int) $params[$key] : $default)
            : $default;
    }
    
    /**
     * Extract a string value from the given key of the given array
     * 
     * @param array $params Parameters array
     * @param string $key Value key
     * @param mixed $default Default value to return if value is not set
     * @param bool $notEmpty Whether the default value should be returned if the value is set but is an empty string
     * @return mixed
     */
    protected function _extractStringParam(array $params, $key, $default = null, $notEmpty = false)
    {
        return isset($params[$key])
            ? ((!$notEmpty || (strval($params[$key]) !== '')) ? strval($params[$key]) : $default)
            : $default;
    }
    
    /**
     * Apply the custom column to the given grid collection
     * 
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param string $columnBlockId Grid column block ID
     * @param string $columnIndex Grid column index
     * @param array $params Customization parameters values
     * @param Mage_Core_Model_Store $store Column store
     * @return BL_CustomGrid_Model_Custom_Column_Abstract
     */
    abstract public function applyToGridCollection(
        Varien_Data_Collection_Db $collection,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        $columnBlockId,
        $columnIndex,
        array $params,
        Mage_Core_Model_Store $store
    );
    
    /**
     * Return the current block values
     * Those values are updated between each call to the different block values getters, and reset afterwards
     * 
     * @return array
     */
    public function getCurrentBlockValues()
    {
        return $this->getDataSetDefault('current_block_values', array());
    }
    
    /**
     * Return default grid column block values
     * (check BL_CustomGrid_Model_Custom_Column_Abstract::getBlockValues() for the priorities
     *  of the different methods related to block values)
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param string $columnBlockId Grid column block ID
     * @param string $columnIndex Grid column index
     * @param array $params Customization parameters values
     * @param Mage_Core_Model_Store $store Column store
     * @return array
     */
    public function getDefaultBlockValues(
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        $columnBlockId,
        $columnIndex,
        array $params,
        Mage_Core_Model_Store $store
    ) {
        return array('index' => $columnIndex);
    }
    
    /**
     * Return base grid column block values
     * (check BL_CustomGrid_Model_Custom_Column_Abstract::getBlockValues() for the priorities
     *  of the different methods related to block values)
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param string $columnBlockId Grid column block ID
     * @param string $columnIndex Grid column index
     * @param array $params Customization parameters values
     * @param Mage_Core_Model_Store $store Column store
     * @return array
     */
    public function getBlockValues(
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        $columnBlockId,
        $columnIndex,
        array $params,
        Mage_Core_Model_Store $store
    ) {
        return array();
    }
    
    /**
     * Return forced grid column block values
     * (you can check BL_CustomGrid_Model_Custom_Column_Abstract::getBlockValues() for the priorities
     *  of the different methods related to block values)
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param string $columnBlockId Grid column block ID
     * @param string $columnIndex Grid column index
     * @param array $params Customization parameters values
     * @param Mage_Core_Model_Store $store Column store
     * @return array
     */
    public function getForcedBlockValues(
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        $columnBlockId,
        $columnIndex,
        array $params,
        Mage_Core_Model_Store $store
    ) {
        return array();
    }
    
    /**
     * Build the editor config for the given grid column based on the given config data
     * 
     * @param BL_CustomGrid_Model_Grid_Column $gridColumn Grid column
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config_Builder $configBuilder Editor config builder
     * @param array $baseConfig Base config data
     * @return BL_CustomGrid_Model_Grid_Editor_Value_Config
     */
    protected function _buildGridColumnEditableFieldConfig(
        BL_CustomGrid_Model_Grid_Column $gridColumn,
        BL_CustomGrid_Model_Grid_Editor_Value_Config_Builder $configBuilder,
        array $baseConfig
    ) {
        return $configBuilder->buildEditableCustomColumnFieldConfig(
            $gridColumn->getGridModel()->getBlockType(),
            $gridColumn->getBlockId(),
            substr($gridColumn->getIndex(), strrpos($gridColumn->getIndex(), '/') + 1),
            $baseConfig
        );
    }
    
    /**
     * Build the editor config for the given grid column based on the given attribute
     *
     * @param BL_CustomGrid_Model_Grid_Column $gridColumn Grid column
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config_Builder $configBuilder Editor config builder
     * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute model
     * @return BL_CustomGrid_Model_Grid_Editor_Value_Config
     */
    protected function _buildGridColumnEditableAttributeConfig(
        BL_CustomGrid_Model_Grid_Column $gridColumn,
        BL_CustomGrid_Model_Grid_Editor_Value_Config_Builder $configBuilder,
        Mage_Eav_Model_Entity_Attribute $attribute
    ) {
        return $configBuilder->buildEditableCustomColumnAttributeConfig(
            $gridColumn->getGridModel()->getBlockType(),
            $gridColumn->getBlockId(),
            substr($gridColumn->getIndex(), strrpos($gridColumn->getIndex(), '/') + 1),
            $attribute
        );
    }
    
    /**
     * Return the editor config for the given grid column
     * 
     * @param BL_CustomGrid_Model_Grid_Column $gridColumn Grid column
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config_Builder $configBuilder Editor config builder
     * @return BL_CustomGrid_Model_Grid_Editor_Value_Config|false
     */
    public function getGridColumnEditorConfig(
        BL_CustomGrid_Model_Grid_Column $gridColumn,
        BL_CustomGrid_Model_Grid_Editor_Value_Config_Builder $configBuilder
    ) {
        return false;
    }
    
    /**
     * Return the additional editor callbacks to register for the given editor context
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @param BL_CustomGrid_Model_Grid_Editor_Callback_Manager $callbackManager Editor callback manager
     * @return array
     */
    public function getEditorContextAdditionalCallbacks(
        BL_CustomGrid_Model_Grid_Editor_Context $context,
        BL_CustomGrid_Model_Grid_Editor_Callback_Manager $callbackManager
    ) {
        return array();
    }
}
