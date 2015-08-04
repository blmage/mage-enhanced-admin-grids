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
 * @method this setId(string $id) Set custom column ID
 * @method this setModule(string $module) Set origin module
 * @method this setGroup(string $group) Set custom column group
 * @method this setAllowCustomization(bool $flag) Set whether this columns is customizable
 * @method this setAllowRenderers(bool $flag) Set whether this column allows to choose a renderer
 * @method this setAllowStore(bool $flag) Set whether this column allows to choose a base store view
 * @method BL_CustomGrid_Model_Custom_Column_Abstract setCurrentBlockValues(array $currentBlockValues)
 */
abstract class BL_CustomGrid_Model_Custom_Column_Abstract extends BL_CustomGrid_Object
{
    /**
     * Generated collection flags
     * 
     * @var string[]
     */
    static protected $_uniqueFlags = array();
    
    /**
     * Generated collection aliases
     * 
     * @var string[]
     */
    static protected $_uniqueAliases = array();
    
    // Grid collection events on which to apply callbacks
    const GC_EVENT_BEFORE_PREPARE     = 'before_prepare';
    const GC_EVENT_AFTER_PREPARE      = 'after_prepare';
    const GC_EVENT_BEFORE_SET_FILTERS = 'before_set_filters';
    const GC_EVENT_AFTER_SET_FILTERS  = 'after_set_filters';
    const GC_EVENT_BEFORE_SET         = 'before_set';
    const GC_EVENT_AFTER_SET          = 'after_set';
    const GC_EVENT_BEFORE_EXPORT_LOAD = 'before_export_load';
    const GC_EVENT_AFTER_EXPORT_LOAD  = 'after_export_load';
    
    protected function _construct()
    {
        parent::_construct();
        $this->_initializeConfig();
    }
    
    /**
     * Return the applier model usable to apply this custom column to a grid block
     * 
     * @return BL_CustomGrid_Model_Custom_Column_Applier
     */
    public function getApplier()
    {
        if (!$this->hasData('applier')) {
            /** @var $applier BL_CustomGrid_Model_Custom_Column_Applier */
            $applier = Mage::getModel('customgrid/custom_column_applier');
            $applier->setCustomColumn($this);
            $this->setData('applier', $applier);
        }
        return $this->_getData('applier');
    }
    
    /**
     * Return base helper
     * 
     * @return BL_CustomGrid_Helper_Data
     */
    protected function _getBaseHelper()
    {
        return Mage::helper('customgrid');
    }
    
    /**
     * Return config helper
     * 
     * @return BL_CustomGrid_Helper_Grid
     */
    protected function _getConfigHelper()
    {
        return Mage::helper('customgrid/config');
    }
    
    /**
     * Return grid block helper
     * 
     * @return BL_CustomGrid_Helper_Grid
     */
    protected function _getGridHelper()
    {
        return Mage::helper('customgrid/grid');
    }
    
    /**
     * Return grid collection helper
     * 
     * @return BL_CustomGrid_Helper_Collection
     */
    protected function _getCollectionHelper()
    {
        return Mage::helper('customgrid/collection');
    }
    
    /**
     * Return the alias used for the main table in the given collection
     * 
     * @param Varien_Data_Collection_Db $collection Database collection
     * @return string
     */
    protected function _getCollectionMainTableAlias(Varien_Data_Collection_Db $collection)
    {
        return $this->_getCollectionHelper()->getCollectionMainTableAlias($collection);
    }
    
    /**
     * Return the adapter model used by the given collection
     * If requested, also return a shortcut callback to the adapter's quoteIdentifier() method
     * 
     * @param Varien_Data_Collection_Db $collection Database collection
     * @param bool $withQiCallback Whether a callback to the adapter's quoteIdentifier() method should also be returned
     * @return mixed Adapter model or an array with the adapter model and the callback
     */
    protected function _getCollectionAdapter(Varien_Data_Collection_Db $collection, $withQiCallback = false)
    {
        $helper  = $this->_getCollectionHelper();
        $adapter = $helper->getCollectionAdapter($collection);
        return (!$withQiCallback ? $adapter : array($adapter, $helper->getQuoteIdentifierCallback($adapter)));
    }
    
    /**
     * Prepare config data
     * 
     * @return BL_CustomGrid_Model_Custom_Column_Abstract
     */
    protected function _prepareConfig()
    {
        return $this;
    }
    
    /**
     * Initialize and prepare config data
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
     * Set array value into data
     * 
     * @param string $key Data key
     * @param mixed $value Config value (will be turned into an array if needed)
     * @param bool $merge Whether the given value should be merged with the existing one
     */
    protected function _setConfigArrayValue($key, $value, $merge = false)
    {
        if (!is_array($value)) {
            $value = array($value);
        }
        if ($merge) {
            if (!is_array($currentValue = $this->_getData($key))) {
                $currentValue = array();
            }
            $value = array_merge($currentValue, $value);
        }
        return $this->setData($key, $value);
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
     * Set or add config params
     * 
     * @param array $params Config params
     * @param bool $merge Whether given params should be merged with the existing ones
     * @return BL_CustomGrid_Model_Custom_Column_Abstract
     */
    public function setConfigParams(array $params, $merge = false)
    {
        return $this->_setConfigArrayValue('config_params', $params, $merge);
    }
    
    /**
     * Return config param value
     * 
     * @param string $key Param key
     * @param mixed $default Value to return if given param is not set
     * @return mixed
     */
    public function getConfigParam($key, $default = null)
    {
        return ($this->hasData('config_params/' . $key) ? $this->getData('config_params/' . $key) : $default);
    }
    
    /**
     * Set or add block params
     * 
     * @param array $params Block params
     * @param bool $merge Whether given params should be merged with the existing ones
     * @return BL_CustomGrid_Model_Custom_Column_Abstract
     */
    public function setBlockParams(array $params, $merge = false)
    {
        return $this->_setConfigArrayValue('block_params', $params, $merge);
    }
    
    /**
     * Return block param value
     * 
     * @param string $key Param key
     * @param mixed $default Value to return if given param is not set
     * @return mixed
     */
    public function getBlockParam($key, $default = null)
    {
        return ($this->hasData('block_params/' . $key) ? $this->getData('block_params/' . $key) : $default);
    }
    
    /**
     * Customization params sort callback
     * 
     * @param BL_CustomGrid_Object $paramA One customization param
     * @param BL_CustomGrid_Object $paramB Another customization param
     * @return int
     */
    protected function _sortCustomizationParams(BL_CustomGrid_Object $paramA, BL_CustomGrid_Object $paramB)
    {
        $aOrder = $paramA->getSortOrder();
        $bOrder = $paramB->getSortOrder();
        return ($aOrder < $bOrder ? -1 : ($aOrder > $bOrder ? 1 : strcmp($paramA->getLabel(), $paramB->getLabel())));
    }
    
    /**
     * Return customization params
     * 
     * @param bool $sorted Whether params should be sorted
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
     * Return the minimum order used amongst all customization params
     * 
     * @return int
     */
    public function getCustomizationParamsMinSortOrder()
    {
        $minOrder  = null;
        
        foreach ($this->getCustomizationParamsConfig(false) as $param) {
            $sortOrder = $param->getSortOrder();
            $minOrder  = (is_null($minOrder) ? $sortOrder : min($minOrder, $sortOrder));
        }
        
        return $minOrder;
    }
    
    /**
     * Return the maximum order used amongst all customization params
     * 
     * @return int
     */
    public function getCustomizationParamsMaxSortOrder()
    {
        $maxOrder  = null;
        
        foreach ($this->getCustomizationParamsConfig(false) as $param) {
            $sortOrder = $param->getSortOrder();
            $maxOrder  = (is_null($maxOrder) ? $sortOrder : max($maxOrder, $sortOrder));
        }
        
        return $maxOrder;
    }
    
    /**
     * Add customization param
     * 
     * @param string $key Param key
     * @param array $data Config values
     * @param mixed $sortOrder Sort order (can be "first", "last" or an integer)
     * @param bool $override Whether the existing param for the same key should be overriden (if appropriate)
     * @return BL_CustomGrid_Model_Custom_Column_Abstract
     */
    public function addCustomizationParam($key, array $data, $sortOrder = 'last', $override = true)
    {
        if ($override || !$this->getData('customization_params/' . $key)) {
            if ($sortOrder === 'last') {
                $data['sort_order'] = $this->getCustomizationParamsMaxSortOrder() +1;
            } elseif ($sortOrder === 'first') {
                $data['sort_order'] = $this->getCustomizationParamsMinSortOrder() -1;
            } else {
                $data['sort_order'] = (int) $sortOrder;
            }
            
            $data['key'] = $key;
            $data['visible'] = (isset($data['visible']) ? (bool) $data['visible'] : true);
            
            $param = new BL_CustomGrid_Object($data);
            $this->setData('customization_params/' . $key, $param);
        }
        return $this;
    }
    
    /**
     * Set or add customization window config values
     * 
     * @param array $data Config values
     * @param bool $merge Whether given values should be merged with the existing ones
     * @return BL_CustomGrid_Model_Custom_Column_Abstract
     */
    public function setCustomizationWindowConfig(array $data, $merge = false)
    {
        return $this->_setConfigArrayValue('customization_window_config', $data, $merge);
    }
    
    /**
     * Set custom column name
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
                $this->_getBaseHelper()->__('Customization : %s', $name)
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
     * Check availability patterns against reference value
     * 
     * @param mixed $values Availability patterns (if not an array or if empty, true will be returned)
     * @param string $reference Reference value
     * @param bool $excluded Whether the availability patterns concern excludable values
     * @return bool If at least one pattern matched the reference value, false for excludable values, true otherwise
     */
    protected function _checkAvailabilityValues($values, $reference, $excluded = false)
    {
        if (!is_array($values) || empty($values)) {
            return true;
        }
        
        $matched = false;
        
        foreach ($values as $value) {
            if (preg_match($this->_getAvailabilityRegex($value), $reference)) {
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
        return ($this->_checkAvailabilityValues($this->getAllowedVersions(), Mage::getVersion())
            && $this->_checkAvailabilityValues($this->getExcludedVersions(), Mage::getVersion(), true)
            && $this->_checkAvailabilityValues($this->getAllowedBlocks(), $blockType)
            && $this->_checkAvailabilityValues($this->getExcludedBlocks(), $blockType, true)
            && $this->_checkAvailabilityValues($this->getAllowedRewrites(), $rewritingClassName)
            && $this->_checkAvailabilityValues($this->getExcludedRewrites(), $rewritingClassName, true));
    }
    
    /**
     * Return whether previous filters should be invalidated
     * 
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param BL_CustomGrid_Model_Grid_Column $columnModel Grid column model
     * @param array $params Customization params values
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
     * Extract boolean value from array
     * 
     * @param array $params Params array
     * @param string $key Value key
     * @param mixed $default Default value to return if value is not set
     * @return mixed
     */
    protected function _extractBoolParam(array $params, $key, $default = false)
    {
        return (isset($params[$key]) ? (bool) $params[$key] : $default);
    }
    
    /**
     * Extract integer value from array
     * 
     * @param array $params Params array
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
     * Extract string value from array
     * 
     * @param array $params Params array
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
     * Generate a string that is unique across all custom columns, basing on the values that already exist
     * 
     * @param string $suffix String suffix
     * @param string[] $existing Already existing strings
     * @return string
     */
    protected function _generateUniqueString($suffix, array $existing)
    {
        $class = get_class($this);
        $alias = '_' . strtolower(preg_replace('#[^A-Z]#', '', $class) . $suffix);
        $base  = $alias;
        $index = 1;
        
        while (in_array($alias, $existing)) {
            $alias = $base . '_' . $index++;
        }
        
        return $alias;
    }
    
    /**
     * Generate an unique string, suitable for collection flags (for consistency and safe uniqueness)
     * 
     * @param string $suffix String suffix
     * @return string
     */
    protected function _getUniqueCollectionFlag($suffix = '')
    {
        $flag = $this->_generateUniqueString($suffix . '_applied', self::$_uniqueFlags);
        self::$_uniqueFlags[] = $flag;
        return $flag;
    }
    
    /**
     * Generate an unique string, suitable for table aliases (for consistency and safe uniqueness)
     * 
     * @param string $suffix String suffix
     * @return string
     */
    protected function _getUniqueTableAlias($suffix = '')
    {
        $alias = $this->_generateUniqueString($suffix, self::$_uniqueAliases);
        self::$_uniqueAliases[] = $alias;
        return $alias;
    }
    
    /**
     * Prepare the filters map for the given grid collection
     * Used to prevent ambiguous filters and other problems of the same kind
     * 
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param array $filters Current filters
     * @return BL_CustomGrid_Model_Custom_Column_Abstract
     */
    public function prepareGridCollectionFiltersMap(
        BL_CustomGrid_Model_Grid $gridModel,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection_Db $collection,
        array $filters
    ) {
        $this->_getCollectionHelper()->prepareGridCollectionFiltersMap($collection, $gridBlock, $gridModel, $filters);
        return $this;
    }
    
    /**
     * Restore the original filters map for the given grid collection, after it was previously prepared
     * Used to prevent undesired side effects from the filters map preparation
     * 
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param array $filters Current filters
     * @return BL_CustomGrid_Model_Custom_Column_Abstract
     */
    public function restoreGridCollectionFiltersMap(
        BL_CustomGrid_Model_Grid $gridModel,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection_Db $collection,
        array $filters
    ) {
        $this->_getCollectionHelper()->restoreGridCollectionFiltersMap($collection, $gridBlock, $gridModel, $filters);
        return $this;
    }
    
    /**
     * Prepare the given grid collection to prevent any potential problem that could occur within it
     * after the custom column will have been applied to it (such as ambiguous filters)
     * 
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param string $columnBlockId Grid column block ID
     * @param string $columnIndex Grid column index
     * @param array $params Customization params values
     * @param Mage_Core_Model_Store $store Column store
     * @return BL_CustomGrid_Model_Custom_Column_Abstract
     */
    public function prepareGridCollection(
        Varien_Data_Collection_Db $collection,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        $columnBlockId,
        $columnIndex,
        array $params,
        Mage_Core_Model_Store $store
    ) {
        if (!$gridBlock->getData('_blcg_added_collection_prepare_callbacks')) {
            $gridBlock->blcg_addCollectionCallback(
                self::GC_EVENT_BEFORE_SET_FILTERS,
                array($this, 'prepareGridCollectionFiltersMap'),
                array($gridModel),
                true
            );
            
            if ($this->_getBaseHelper()->isMageVersionGreaterThan(1, 6)
                && $this->_getGridHelper()->isEavEntityGrid($gridBlock, $gridModel)) {
                /**
                 * Fix for Mage_Eav_Model_Entity_Collection_Abstract::_renderOrders() on 1.7+,
                 * which fails to handle qualified field names, as it forces the use of addAttributeToSort() :
                 * when this method is applied on mapped fields,
                 * the fact that they are qualified makes them unrecognizable as attributes or static fields
                 * Note that this does not affect filters applied on custom columns derived from
                 * BL_CustomGrid_Model_Custom_Column_Simple_Abstract, as it forces field orders on EAV entity grids
                 */
                $gridBlock->blcg_addCollectionCallback(
                    self::GC_EVENT_AFTER_SET_FILTERS,
                    array($this, 'restoreGridCollectionFiltersMap'),
                    array($gridModel),
                    true
                );
            }
            
            $gridBlock->setData('_blcg_added_collection_prepare_callbacks', true);
        }
        return $this;
    }
    
    /**
     * Apply the custom column to the given grid collection
     * 
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param string $columnBlockId Grid column block ID
     * @param string $columnIndex Grid column index
     * @param array $params Customization params values
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
     * @param array $params Customization params values
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
     * @param array $params Customization params values
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
     * @param array $params Customization params values
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
}
