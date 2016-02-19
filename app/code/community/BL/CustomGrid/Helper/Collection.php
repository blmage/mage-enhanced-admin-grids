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

class BL_CustomGrid_Helper_Collection extends Mage_Core_Helper_Abstract
{
    const COLLECTION_APPLIED_MAP_FLAG  = '_blcg_hc_applied_map_';
    const COLLECTION_PREVIOUS_MAP_FLAG = '_blcg_hc_previous_map_';
    
    const SQL_AND = 'AND';
    const SQL_OR  = 'OR';
    const SQL_XOR = 'XOR';
    
    /**
     * Registered $adapter->quoteIdentifier() callbacks (usable for convenience and readability)
     * 
     * @var callback[]
     */
    protected $_quoteIdentifierCallbacks = array();
    
    /**
     * Count of currently registered $adapter->quoteIdentifier() callbacks
     * 
     * @var integer
     */
    protected $_qiCallbacksCount = 0;
    
    /**
     * Base callbacks to call when building filters map for a given grid block
     * 
     * @var string[]
     */
    protected $_baseFiltersMapCallbacks = array(
        'adminhtml/catalog_product_grid'  => '_prepareCatalogProductFiltersMap',
    );
    
    /**
     * Additional callbacks to call when building filters map for a given grid block
     * 
     * @var string[]
     */
    protected $_additionalFiltersMapCallbacks = array();
    
    /**
     * Cache for describeTable() results
     * 
     * @var array
     */
    protected $_describeTableCache = array();
    
    /**
     * Return the DB adapter used by the given collection
     * 
     * @param Varien_Data_Collection_Db $collection
     * @return Zend_Db_Adapter_Abstract
     */
    public function getCollectionAdapter(Varien_Data_Collection_Db $collection)
    {
        return $collection->getSelect()->getAdapter();
    }
    
    /**
     *  Return the column descriptions for the given collection table
     * 
     * @param Varien_Data_Collection_Db $collection Database collection
     * @param string $tableName Table name
     * @return array
     */
    public function describeCollectionTable(Varien_Data_Collection_Db $collection, $tableName)
    {
        if (!isset($this->_describeTableCache[$tableName])) {
            $adapter = $this->getCollectionAdapter($collection);
            $this->_describeTableCache[$tableName] = $adapter->describeTable($tableName);
        }
        return $this->_describeTableCache[$tableName];
    }
    
    /**
     * Return the most common alias known to be used by the main table of the collections having the same type
     * as the given one
     * 
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param string|null $defaultAlias If set, this alias will be returned instead
     * @return string
     */
    protected function _getDefaultMainTableAlias(Varien_Data_Collection_Db $collection, $defaultAlias = null)
    {
        if (empty($defaultAlias)) {
            if ($collection instanceof Mage_Eav_Model_Entity_Collection_Abstract) {
                $defaultAlias = 'e';
            } else {
                $defaultAlias = 'main_table';
            }
        }
        return $defaultAlias;
    }
    
    /**
     * Return the alias used by the main table of the given collection.
     * 
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param string|null $defaultAlias The alias that should first be searched (if not set, known default will be used)
     * @param string $mainTableName The table that should be assumed as the main one (if default alias search failed)
     * @return string
     */
    public function getCollectionMainTableAlias(
        Varien_Data_Collection_Db $collection,
        $defaultAlias = null,
        $mainTableName = null
    ) {
        $defaultAlias = $this->_getDefaultMainTableAlias($collection, $defaultAlias);
        $fromPart     = $collection->getSelect()->getPart(Zend_Db_Select::FROM);
        $mainAlias    = '';
        $fromAliases  = array();
        
        if (!isset($fromPart[$defaultAlias])
            || ($fromPart[$defaultAlias]['joinType'] != Zend_Db_Select::FROM)) {
            foreach ($fromPart as $alias => $config) {
                if ($config['joinType'] == Zend_Db_Select::FROM) {
                    if ($config['tableName'] === $mainTableName) {
                        $mainAlias = $alias;
                        break;
                    } else {
                        $fromAliases[] = $alias;
                    }
                }
            }
        } else {
            $mainAlias = $defaultAlias;
        }
        
        $fromAliases[] = $defaultAlias;
        return ($mainAlias !== '' ? $mainAlias : array_shift($fromAliases));
    }
    
    /**
     * Return the table alias that would be used by EAV-based collections for the given attribute code
     * 
     * @param string $attributeCode Attribute code
     * @return string
     */
    public function getAttributeTableAlias($attributeCode)
    {
        return '_table_' . $attributeCode;
    }
    
    /**
     * Call a previously registered $adapter->quoteIdentifier() callback
     * 
     * @param string $identifier Identifier to quote
     * @param int $callbackIndex Index of the quote identifier callback to use
     * @return string
     */
    public function callQuoteIdentifier($identifier, $callbackIndex)
    {
        foreach ($this->_quoteIdentifierCallbacks as $callback) {
            if ($callback['index'] == $callbackIndex) {
                $identifier = $callback['adapter']->quoteIdentifier($identifier);
                break;
            }
        }
        return $identifier;
    }
    
    /**
     * Register and return a quoteIdentifier() callback for the given DB adapter.
     * The returned callback can be used as a convenient shortcut to $adapter->quoteIdentifier($identifier)
     * 
     * @param Zend_Db_Adapter_Abstract $adapter Collection DB adapter
     * @return callable
     */
    public function getQuoteIdentifierCallback(Zend_Db_Adapter_Abstract $adapter)
    {
        $adapterHash = spl_object_hash($adapter);
        
        if (!isset($this->_quoteIdentifierCallbacks[$adapterHash])) {
            $index = ++$this->_qiCallbacksCount;
            $code  = 'return Mage::helper(\'customgrid/collection\')->callQuoteIdentifier($v, ' . $index . ');';
            $callback = create_function('$v', $code);
            
            $this->_quoteIdentifierCallbacks[$adapterHash] = array(
                'adapter'  => $adapter,
                'index'    => $index,
                'callback' => $callback,
            );
        }
        
        return $this->_quoteIdentifierCallbacks[$adapterHash]['callback'];
    }
    
    /**
     * Return a condition which can be used with Varien_Data_Collection_Db::addFieldToFilter(),
     * and that will not have any effect
     * 
     * @return array
     */
    public function getIdentityCondition()
    {
        return array(array('null' => true), array('notnull' => true));
    }
    
    /**
     * Add multiple FIND_IN_SET filters for the given field on the given collection,
     * using the given logical operator, and possibly a custom set separator
     * 
     * @param Varien_Data_Collection_Db $collection Collection on which to apply the filter
     * @param string $fieldName Field name
     * @param array $values Values to search in the set
     * @param string $setSeparator Set values separator
     * @param string $operator Logical operator with which to bind the sub conditions
     * @param bool $negative Whether the global resulting condition should be negated
     * @return BL_CustomGrid_Helper_Collection
     */
    public function addFindInSetFiltersToCollection(
        Varien_Data_Collection_Db $collection,
        $fieldName,
        array $values,
        $setSeparator = ',',
        $operator = self::SQL_OR,
        $negative = false
    ) {
        $adapter = $this->getCollectionAdapter($collection);
        $select  = $collection->getSelect();
        $firstValue = reset($values);
        $quotedFirstValue = $adapter->quote($firstValue);
        
        $previousWherePart = $select->getPart(Zend_Db_Select::WHERE);
        $previousWhereKeys = array_keys($previousWherePart);
        $collection->addFieldToFilter($fieldName, array('finset' => $firstValue));
        
        $newWherePart = $select->getPart(Zend_Db_Select::WHERE);
        $newWhereKeys = array_diff(array_keys($newWherePart), $previousWhereKeys);
        $foundCondition = false;
        $findInSetRegex = '#(find_in_set|FIND_IN_SET)\\(' . preg_quote($quotedFirstValue, '#') . ',\\s*(.+?)\\)#';
        
        foreach ($newWhereKeys as $key) {
            if (preg_match($findInSetRegex, $newWherePart[$key], $matches)) {
                $fieldName = $matches[2];
                $filterParts = array();
                
                if ($setSeparator != ',') {
                    $fieldName = 'REPLACE(' . $fieldName
                        . ',' . $adapter->quote($setSeparator)
                        . ',' . $adapter->quote(',')
                        . ')';
                }
                
                foreach ($values as $value) {
                    $filterParts[] = 'FIND_IN_SET(' . $adapter->quote($value) . ',' . $fieldName . ')';
                }
                
                if (!in_array($operator, array(self::SQL_AND, self::SQL_OR, self::SQL_XOR))) {
                    $operator = self::SQL_OR;
                }
                
                $newWherePart[$key] = '(' . implode(' ' . $operator . ' ', $filterParts) . ')';
                
                if ($negative) {
                    $newWherePart[$key] = '(NOT ' . $newWherePart[$key]. ')';
                }
                
                $foundCondition = true;
                break;
            }
        }
        
        if ($foundCondition) {
            $select->setPart(Zend_Db_Select::WHERE, $newWherePart);
        } else {
            $select->setPart(Zend_Db_Select::WHERE, $previousWherePart);
            Mage::throwException('Could not inject the multiple conditions into the select object');
        }
        
        return $this;
    }
    
    /**
     * Add a regex filter for the given field on the given collection
     * 
     * @param Varien_Data_Collection_Db $collection Collection on which to apply the filter
     * @param string $fieldName Field name
     * @param string $regex Regex
     * @param bool $negative Whether the field value should not match the given regex
     * @return BL_CustomGrid_Helper_Collection
     */
    public function addRegexFilterToCollection(
        Varien_Data_Collection_Db $collection,
        $fieldName,
        $regex,
        $negative = false
    ) {
        $adapter = $this->getCollectionAdapter($collection);
        $select  = $collection->getSelect();
        $quotedRegex = $adapter->quote($regex);
        $hasRegexpKeyword = Mage::helper('customgrid')->isMageVersionGreaterThan(1, 5);
        
        try {
            $adapter->query('SELECT "crash test dummy" REGEXP ' . $quotedRegex);
        } catch (Exception $e) {
            Mage::throwException(Mage::helper('customgrid')->__('Invalid regex : "%s"', $regex));
            return $this;
        }
        
        if ($hasRegexpKeyword) {
            $filterKeyword = 'regexp';
            
            if ($negative) {
                $searchedPart  = '#\\s+(regexp|REGEXP)\\s+' . preg_quote($quotedRegex, '#') . '#';
                $replacingPart = ' NOT REGEXP ' . $quotedRegex;
            }
        } else {
            $filterKeyword = 'like';
            $searchedPart  = '#\\s+(like|LIKE)\\s+' . preg_quote($quotedRegex, '#') . '#';
            $replacingPart = ($negative ? ' NOT' : '') . ' REGEXP ' . $quotedRegex;
        }
        
        $previousWherePart = $select->getPart(Zend_Db_Select::WHERE);
        $previousWhereKeys = array_keys($previousWherePart);
        $collection->addFieldToFilter($fieldName, array($filterKeyword => $regex));
        
        if (!$hasRegexpKeyword || $negative) {
            $newWherePart = $select->getPart(Zend_Db_Select::WHERE);
            $newWhereKeys = array_diff(array_keys($newWherePart), $previousWhereKeys);
            $foundCondition = false;
            
            foreach ($newWhereKeys as $key) {
                if (preg_match($searchedPart, $newWherePart[$key])) {
                    $newWherePart[$key] = preg_replace($searchedPart, $replacingPart, $newWherePart[$key]);
                    $foundCondition = true;
                    break;
                }
            }
            
            if ($foundCondition) {
                $select->setPart(Zend_Db_Select::WHERE, $newWherePart);
            } else {
                $select->setPart(Zend_Db_Select::WHERE, $previousWherePart);
                Mage::throwException('Could not inject the regex into the select object');
            }
        }
        
        return $this;
    }
    
    /**
     * Return a filters map built from the given fields and table alias, by qualifying all of the given fields
     * and associating them to the given aliases (or their own names by default)
     * 
     * @param string[] $fields Fields to map. The keys will be used as aliases when strings, otherwise field names
     * @param string $tableAlias Alias of the table to which belong the given fields
     * @return string[]
     */
    public function buildFiltersMapArray($fields, $tableAlias)
    {
        $filtersMap = array();
        
        foreach ($fields as $index => $field) {
            if (is_string($index)) {
                $filtersMap[$index] = $tableAlias . '.' . $field;
            } else {
                $filtersMap[$field] = $tableAlias . '.' . $field;
            }
        }
        
        return $filtersMap;
    }
    
    /**
     * Add field(s) and corresponding alias(es) to the filters map of the given collection
     * 
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param array|string $field Field name or filters map
     * @param string|null $alias Filter alias (not used if a filters map is given)
     * @return BL_CustomGrid_Helper_Collection
     */
    public function addFilterToCollectionMap(Varien_Data_Collection_Db $collection, $field, $alias = null)
    {
        if (is_null($alias)) {
            if (is_array($field)) {
                foreach ($field as $alias => $subField) {
                    $collection->addFilterToMap($alias, $subField);
                }
            }
        } else {
            $collection->addFilterToMap($alias, $field);
        }
        return $this;
    }
    
    /**
     * Register an additional filters map callback for the given block type
     * 
     * @param string $blockType Grid block type
     * @param callable $callback Filters map callback
     * @param array $params Callback parameters
     * @param bool $addNative Whether the native callback parameters should be appended to the callback call
     * @return BL_CustomGrid_Helper_Collection
     */
    public function addCollectionFiltersMapCallback($blockType, $callback, array $params = array(), $addNative = true)
    {
         $this->_additionalFiltersMapCallbacks[$blockType][] = array(
            'callback'   => $callback,
            'params'     => $params,
            'add_native' => $addNative,
        );
        return $this;
    }
    
    /**
     * Return whether the filters map was already prepared for the given collection
     * 
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @return bool
     */
    public function shouldPrepareCollectionFiltersMap(Varien_Data_Collection_Db $collection)
    {
        return !$collection->hasFlag(self::COLLECTION_APPLIED_MAP_FLAG);
    }
    
    /**
     * Matching tables sort callback
     * 
     * @param array $tableA One table
     * @param array $tableB Another table
     * @return int
     */
    protected function _sortMatchingTables(array $tableA, array $tableB)
    {
        return ($tableA['priority'] > $tableB['priority'] ? 1 : ($tableA['priority'] < $tableB['priority'] ? -1 : 0));
    }
    
    /**
     * Return the reflected filters map property for the given collection.
     * For PHP versions < 5.3.0, only getValue() and setValue() are safely usable,
     * and the returned object is not an instance of ReflectionProperty
     * 
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @return mixed
     */
    protected function _getCollectionFiltersMapProperty(Varien_Data_Collection_Db $collection)
    {
        $mapProperty = null;
        
        if (version_compare(phpversion(), '5.3.0', '<') === true) {
            // ReflectionProperty::setAccessible() was added in PHP 5.3.0
            $collectionClass = get_class($collection);
            $reflectionClass = 'Blcg_Hc_' . $collectionClass;
            
            if (!class_exists($reflectionClass, false)) {
                eval('class ' . $reflectionClass . ' extends ' . $collectionClass . '
{
    public function getValue($collection)
    {
        return $collection->_map;
    }
    
    public function setValue($collection, $value)
    {
        $collection->_map = $value;
    }
}'
                );
            }
            
            $mapProperty = new $reflectionClass();
        } else {
            try {
                $reflectedCollection = new ReflectionObject($collection);
                $mapProperty = $reflectedCollection->getProperty('_map');
                $mapProperty->setAccessible(true);
            } catch (ReflectionException $e) {
                $mapProperty = null;
            }
        }
        
        return $mapProperty;
    }
    
    /**
     * Return the filters map value from the given collection
     * 
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @return string[]|null
     */
    protected function _getCollectionFiltersMap(Varien_Data_Collection_Db $collection)
    {
        $filtersMap = null;
        
        if ($mapProperty = $this->_getCollectionFiltersMapProperty($collection)) {
            try {
                $filtersMap = $mapProperty->getValue($collection);
            } catch (ReflectionException $e) {
                $filtersMap = null;
            }
        }
        
        return $filtersMap;
    }
    
    /**
     * Set the filters map value for the given collection
     * 
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param string[] $filtersMap Filters map value
     * @return BL_CustomGrid_Helper_Collection
     */
    protected function _setCollectionFiltersMap(Varien_Data_Collection_Db $collection, array $filtersMap)
    {
        if ($mapProperty = $this->_getCollectionFiltersMapProperty($collection)) {
            try {
                $mapProperty->setValue($collection, $filtersMap);
            } catch (ReflectionException $e) {
                // Can this ever happen ?
            }
        }
        return $this;
    }
    
    /**
     * Return whether the given filter field should be considered as being unmapped
     * 
     * @param string $field Filter field name
     * @param array $filtersMap Collection filters map
     * @return bool
     */
    protected function _isUnmappedFilterFied($field, array $filtersMap)
    {
        return (strpos($field, '.') === false) // Not completely safe as "." is allowed in quoted identifier
            && !isset($filtersMap[$field])
            && (strpos($field, BL_CustomGrid_Model_Grid::ATTRIBUTE_COLUMN_GRID_ALIAS) !== 0)
            && (strpos($field, BL_CustomGrid_Model_Grid::CUSTOM_COLUMN_GRID_ALIAS) !== 0);
    }
    
    /**
     * Return the unmapped fields from the given collection that are used in the given filters
     * 
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param Mage_Adminhtml_Block_Widget_Grid Grid block
     * @param array $filters Applied filters
     * @return string[]
     */
    protected function _getUnmappedFiltersFields(
        Varien_Data_Collection_Db $collection,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        array $filters
    ) {
        /** @var BL_CustomGrid_Helper_Grid $gridHelper */
        $gridHelper = Mage::helper('customgrid/grid');
        $unmappedFields = array();
        
        if (is_array($filtersMap = $this->_getCollectionFiltersMap($collection))) {
            // Search for "potentially dangerous" unmapped fields in applied filters
            $filtersMap = (isset($filtersMap['fields']) ? $filtersMap['fields'] : array());
            $filtersIndexes = $gridHelper->getGridBlockActiveFiltersIndexes($gridBlock, $filters);
            
            foreach ($filtersIndexes as $filterIndex) {
                if ($this->_isUnmappedFilterFied($filterIndex, $filtersMap)) {
                    $unmappedFields[] = $filterIndex;
                }
            }
        }
        
        return $unmappedFields;
    }
    
    /**
     * Return each table used by the given collection, which contains one or more of the given unmapped fields,
     * sorted by priority
     * 
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param string[] $unmappedFields Unmapped fields
     * @return array (keys : table aliases / values : contained unmapped fields)
     */
    protected function _getUnmappedFieldsMatchingTables(Varien_Data_Collection_Db $collection, array $unmappedFields)
    {
        $matchingTables = array();
        
        foreach ($collection->getSelectSql()->getPart(Zend_Db_Select::FROM) as $tableAlias => $table) {
            $tableFields = array_keys($this->describeCollectionTable($collection, $table['tableName']));
            $matchingFields = array_intersect($unmappedFields, $tableFields);
            
            if (!empty($matchingFields)) {
                $matchingTables[$tableAlias] = array(
                    'fields'   => $matchingFields,
                    'priority' => ($table['joinType'] == Zend_Db_Select::FROM)
                            ? 1 : ($table['joinType'] == Zend_Db_Select::LEFT_JOIN ? 100 : 10),
                );
            }
        }
        
        uasort($matchingTables, array($this, '_sortMatchingTables'));
        
        foreach ($matchingTables as $tableAlias => $values) {
            $matchingTables[$tableAlias] = $values['fields'];
        }
        
        return $matchingTables;
    }
    
    /**
     * Map as much as possible of the given unmapped fields from the given collection,
     * according to the given matching tables
     * 
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param string[] $unmappedFields Unmapped fields
     * @param array $matchingTables Tables that contain or or more of the unmapped fields, sorted by priority
     * @return BL_CustomGrid_Helper_Collection
     */
    protected function _mapUnmappedFields(
        Varien_Data_Collection_Db $collection,
        array $unmappedFields,
        array $matchingTables
    ) {
        $adapter = $this->getCollectionAdapter($collection);
        
        foreach ($matchingTables as $tableAlias => $tableFields) {
            $fields = array_intersect($unmappedFields, $tableFields);
            $unmappedFields = array_diff($unmappedFields, $fields);
            
            foreach ($fields as $fieldName) {
                $this->addFilterToCollectionMap(
                    $collection,
                    $adapter->quoteIdentifier($tableAlias . '.' . $fieldName),
                    $fieldName
                );
            }
            
            if (empty($unmappedFields)) {
                break;
            }
        }
        
        return $this;
    }
    
    /**
     * Search in the given filters for occurences that correspond to unqualified fields in the given collection,
     * and in the collection for the most relevant table containing a corresponding field,
     * to add the resulting association in the collection filters map.
     * This is used to prevent potential ambiguous filters on fields that would not have been handled by the prepare
     * callbacks (and that are not qualified by default because not any problematical join is used).
     * 
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param array $filters Applied filters
     * @return BL_CustomGrid_Helper_Collection
     */
    protected function _handleUnmappedFilters(
        Varien_Data_Collection_Db $collection,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        array $filters
    ) {
        $unmappedFields = $this->_getUnmappedFiltersFields($collection, $gridBlock, $filters);
        
        if (!empty($unmappedFields)) {
            $matchingTables = $this->_getUnmappedFieldsMatchingTables($collection, $unmappedFields);
            
            if (!empty($matchingTables)) {
                $this->_mapUnmappedFields($collection, $unmappedFields, $matchingTables);
            }
        }
        
        return $this;
    }
    
    /**
     * Prepare the filters map for the given collection, to reduce as much as possible chances of ambiguous filters.
     * This should be called before the filters are actually applied to the grid block.
     * 
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param array $filters Applied filters
     * @return BL_CustomGrid_Helper_Collection
     */
    public function prepareGridCollectionFiltersMap(
        Varien_Data_Collection_Db $collection,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        array $filters
    ) {
        if (!$this->shouldPrepareCollectionFiltersMap($collection)) {
            return $this;
        }
        
        $blockType = $gridModel->getBlockType();
        $previousFiltersMap = $this->_getCollectionFiltersMap($collection);
        $collection->setFlag(self::COLLECTION_PREVIOUS_MAP_FLAG, $previousFiltersMap);
        
        if (isset($this->_baseFiltersMapMainTableFields[$blockType])) {
            $this->addFilterToCollectionMap(
                $collection,
                $this->buildFiltersMapArray(
                    $this->_baseFiltersMapMainTableFields[$blockType],
                    $this->getCollectionMainTableAlias($collection)
                )
            );
        }
        
        if (isset($this->_baseFiltersMapCallbacks[$blockType])) {
            call_user_func(
                array($this, $this->_baseFiltersMapCallbacks[$blockType]),
                $collection,
                $gridBlock,
                $gridModel
            );
        }
        
        if (isset($this->_additionalFiltersMapCallbacks[$blockType])) {
            foreach ($this->_additionalFiltersMapCallbacks[$blockType] as $callback) {
                call_user_func_array(
                    $callback['callback'],
                    array_merge(
                        array_values($callback['params']),
                        ($callback['add_native']? array($collection, $gridBlock, $gridModel) : array())
                    )
                );
            }
        }
        
        $this->_handleUnmappedFilters($collection, $gridBlock, $gridModel, $filters);
        $collection->setFlag(self::COLLECTION_APPLIED_MAP_FLAG, true);
        
        return $this;
    }
    
    /**
     * Restore the filters map to its original value for the given grid collection
     * (ie the value it had prior to the call to prepareGridCollectionFiltersMap()).
     * This can be useful to prevent incompatibilities in some part of the code that do not expect qualified fields.
     * 
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param bool $resetAppliedFlag Whether the filters map should not be considered to have been prepared anymore
     * @return BL_CustomGrid_Helper_Collection
     */
    public function restoreGridCollectionFiltersMap(
        Varien_Data_Collection_Db $collection,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        $resetAppliedFlag = true
    ) {
        if ($previousFiltersMap = $collection->getFlag(self::COLLECTION_PREVIOUS_MAP_FLAG)) {
            $this->_setCollectionFiltersMap($collection, $previousFiltersMap);
            
            if ($resetAppliedFlag) {
                $collection->setFlag(self::COLLECTION_APPLIED_MAP_FLAG, true);
            }
        }
        return $this;
    }
    
    /**
     * Base filters map callback for catalog product grids
     *
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @return BL_CustomGrid_Helper_Collection
     */
    protected function _prepareCatalogProductFiltersMap(
        Varien_Data_Collection_Db $collection,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel
    ) {
        return $this->addFilterToCollectionMap($collection, $this->getAttributeTableAlias('qty') . '.qty', 'qty');
    }
    
    /**
     * Base main table fields to use when building filters map for a given grid block
     * 
     * @var array
     */
    protected $_baseFiltersMapMainTableFields = array(
        'adminhtml/catalog_product_grid' => array(
            'entity_id',
            'type_id',
            'attribute_set_id',
            'sku',
            'has_options',
            'required_options',
            'created_at',
            'updated_at',
        ),
        'adminhtml/sales_order_grid' => array(
            'entity_id',
            'status',
            'store_id',
            'store_name',
            'customer_id',
            'base_grand_total',
            'base_total_paid',
            'grand_total',
            'total_paid',
            'increment_id',
            'base_currency_code',
            'order_currency_code',
            'shipping_name',
            'billing_name',
            'created_at',
            'updated_at',
        ),
        'adminhtml/sales_invoice_grid' => array(
            'entity_id',
            'store_id',
            'base_grand_total',
            'grand_total',
            'order_id',
            'state',
            'store_currency_code',
            'order_currency_code',
            'base_currency_code',
            'global_currency_code',
            'increment_id',
            'order_increment_id',
            'created_at',
            'order_created_at',
            'billing_name',
        ),
        'adminhtml/sales_shipment_grid' => array(
            'entity_id',
            'store_id',
            'total_qty',
            'order_id',
            'shipment_status',
            'increment_id',
            'order_increment_id',
            'created_at',
            'order_created_at',
            'shipping_name',
        ),
        'adminhtml/sales_creditmemo_grid' => array(
            'entity_id',
            'store_id',
            'store_to_order_rate',
            'base_to_order_rate',
            'grand_total',
            'store_to_base_rate',
            'base_to_global_rate',
            'base_grand_total',
            'order_id',
            'creditmemo_status',
            'state',
            'invoice_id',
            'store_currency_code',
            'order_currency_code',
            'base_currency_code',
            'global_currency_code',
            'increment_id',
            'order_increment_id',
            'created_at',
            'order_created_at',
            'billing_name',
        ),
    );
}
