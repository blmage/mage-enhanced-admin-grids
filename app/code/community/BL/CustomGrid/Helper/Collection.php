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

class BL_CustomGrid_Helper_Collection
    extends Mage_Core_Helper_Abstract
{
    const COLLECTION_APPLIED_MAP_FLAG   = '_blcg_hc_applied_map_';
    const COLLECTION_PREVIOUS_MAP_FLAG  = '_blcg_hc_previous_map_';
    
    /**
    * Registered $adapter->quoteIdentifier() callbacks (usable for convenience and readability)
    * 
    * @var array
    */
    protected $_quoteIdentifierCallbacks = array();
    
    /**
    * Count of currently registered quoteIdentifier() callbacks
    * 
    * @var integer
    */
    protected $_qiCallbacksCount = 0;
    
    /**
    * Base callbacks to call when building filters map for a given grid block
    * 
    * @var array
    */
    protected $_baseFiltersMapCallbacks  = array(
        'adminhtml/catalog_product_grid'  => '_prepareCatalogProductFiltersMap',
        'adminhtml/sales_order_grid'      => '_prepareSalesOrderFiltersMap',
        'adminhtml/sales_invoice_grid'    => '_prepareSalesInvoiceFiltersMap',
        'adminhtml/sales_shipment_grid'   => '_prepareSalesShipmentFiltersMap',
        'adminhtml/sales_creditmemo_grid' => '_prepareSalesCreditmemoFiltersMap',
    );
    
    /**
    * Additional callbacks to call when building filters map for a given grid block
    * 
    * @var array
    */
    protected $_additionalFiltersMapCallbacks = array();
    
    /**
    * Cache for describeTable() results
    * 
    * @var array
    */
    protected $_describeTableCache = array();
    
    public function getCollectionAdapter($collection)
    {
        return $collection->getSelect()->getAdapter();
    }
    
    public function getCollectionMainTableAlias($collection, $defaultAlias=null, $mainTableName='')
    {
        if (is_null($defaultAlias)) {
            if ($collection instanceof Mage_Eav_Model_Entity_Collection_Abstract) {
                $defaultAlias = 'e';
            } else {
                $defaultAlias = 'main_table';
            }
        }
        
        $fromPart    = $collection->getSelect()->getPart(Zend_Db_Select::FROM);
        $mainAlias   = '';
        $fromAliases = array();
        
        if (!isset($fromPart[$defaultAlias])
            || ($fromPart[$defaultAlias]['joinType'] != Zend_Db_Select::FROM)) {
            foreach ($fromPart as $key => $config) {
                if ($config['joinType'] == Zend_Db_Select::FROM) {
                    if (($mainTableName != '')
                        && ($config['tableName'] === $mainTableName)) {
                        $mainAlias = $key;
                        break;
                    } else {
                        $fromParts[] = $key;
                    }
                }
            }
        } else {
            $mainAlias = $defaultAlias;
        }
        
        return ($mainAlias !== '' ? $mainAlias : (!empty($fromParts) ? array_shift($fromParts) : $defaultAlias));
    }
    
    public function getAttributeTableAlias($attribute)
    {
        return '_table_'.$attribute;
    }
    
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
    
    public function getQuoteIdentifierCallback($adapter)
    {
        $adapterKey = spl_object_hash($adapter);
        
        if (!isset($this->_quoteIdentifierCallbacks[$adapterKey])) {
            $callback = create_function('$v', 'return Mage::helper(\'customgrid/collection\')->callQuoteIdentifier($v, '.++$this->_qiCallbacksCount.');');
            
            $this->_quoteIdentifierCallbacks[$adapterKey] = array(
                'adapter'  => $adapter,
                'index'    => $this->_qiCallbacksCount,
                'callback' => $callback
            );
        }
        
        return $this->_quoteIdentifierCallbacks[$adapterKey]['callback'];
    }
    
    public function buildFiltersMapArray($fields, $tableAlias)
    {
        $result = array();
        
        foreach ($fields as $index => $field) {
            if (is_string($index)) {
                $result[$index] = $tableAlias.'.'.$field;;
            } else {
                $result[$field] = $tableAlias.'.'.$field;
            }
        }
        
        return $result;
    }
    
    public function addFilterToCollectionMap($collection, $filter, $alias=null)
    {
        if (is_null($alias)) {
            if (is_array($filter)) {
                foreach ($filter as $alias => $subFilter) {
                    $collection->addFilterToMap($alias, $subFilter);
                }
            }
        } else {
            $collection->addFilterToMap($alias, $filter);
        }
        return $this;
    }
    
    public function addCollectionFiltersMapCallback($blockType, $callback, $params=array(), $addNative=true)
    {
         $this->_additionalFiltersMapCallbacks[$blockType][] = array(
            'callback'   => $callback,
            'params'     => $params,
            'add_native' => $addNative,
        );
        return $this;
    }
    
    public function shouldPrepareCollectionFiltersMap($collection)
    {
        return !$collection->hasFlag(self::COLLECTION_APPLIED_MAP_FLAG);
    }
    
    protected function _sortMatchingTables($a, $b)
    {
        return ($a['priority'] > $b['priority'] ? 1 : ($a['priority'] < $b['priority'] ? -1 : 0));
    }
    
    protected function _getCollectionFiltersMapProperty($collection)
    {
        $mapProperty = null;
        
        if (version_compare(phpversion(), '5.3.0', '<') === true) {
            // ReflectionProperty::setAccessible() was added in PHP 5.3
            $collectionClass = get_class($collection);
            $reflectedClass  = 'Blcg_Hc_' . $collectionClass;
            
            if (!class_exists($reflectedClass, false)) {
                // Hopefully temporary fix (though there might not be other solutions)
                eval('class '.$reflectedClass.' extends '.$collectionClass.' {
                    public function getValue($collection)
                    {
                        return $collection->_map;
                    }
                    
                    public function setValue($collection, $value)
                    {
                        $collection->_map = $value;
                    }
                }');
            }
            
            return new $reflectedClass();
            
        } else {
            try {
                $reflectedCollection = new ReflectionObject($collection);
                $mapProperty = $reflectedCollection->getProperty('_map');
                $mapProperty->setAccessible(true);
            } catch (ReflectionException $e) {}
        }
        
        return $mapProperty;
    }
    
    protected function _getCollectionFiltersMap($collection)
    {
        $collectionFiltersMap = null;
        
        if ($mapProperty = $this->_getCollectionFiltersMapProperty($collection)) {
            try {
                $collectionFiltersMap = $mapProperty->getValue($collection);
            } catch (ReflectionException $e) {}
        }
        
        return $collectionFiltersMap;
    }
    
    protected function _setCollectionFiltersMap($collection, $filtersMap)
    {
        if ($mapProperty = $this->_getCollectionFiltersMapProperty($collection)) {
            try {
                $mapProperty->setValue($collection, $filtersMap);
            } catch (ReflectionException $e) {}
        }
        return $this;
    }
    
    protected function _handleUnmappedFilters($collection, $block, $model, $filters)
    {
        $collectionFiltersMap = $this->_getCollectionFiltersMap($collection);
        
        if (!is_array($collectionFiltersMap)) {
            // Stop now if we won't be able to determine which fields are mapped, and which are not
            return $this;
        } else {
            // Get mapped fields only
            $collectionFiltersMap = (isset($collectionFiltersMap['fields']) ? $collectionFiltersMap['fields'] : array());
        }
        
        // Check for "potentially dangerous" unmapped fields in applied filters
        $unmappedFields = array();
        
        foreach ($block->getColumns() as $columnId => $column) {
            if (isset($filters[$columnId])
                && (!empty($filters[$columnId]) || strlen($filters[$columnId]) > 0)
                && $column->getFilter()) {
                $field = ($column->getFilterIndex() ? $column->getFilterIndex() : $column->getIndex());
                
                if ((strpos($field, '.') === false) // @todo not completely safe as "." is allowed in quoted identifier
                    && !isset($collectionFiltersMap[$field])
                    && (strpos($field, BL_CustomGrid_Model_Grid::GRID_COLUMN_ATTRIBUTE_GRID_ALIAS) !== 0)
                    && (strpos($field, BL_CustomGrid_Model_Grid::GRID_COLUMN_CUSTOM_GRID_ALIAS) !== 0)) {
                    // Unmapped field name without a table alias, that is not completely sure to not correspond to an actual field
                    $unmappedFields[] = $field;
                }
            }
        }
        
        if (!empty($unmappedFields)) {
            // Search for unmapped fields in each joined table
            $adapter = $collection->getSelect()->getAdapter();
            $matchingTables = array();
            
            foreach ($collection->getSelectSql()->getPart(Zend_Db_Select::FROM) as $tableAlias => $table) {
                $tableName = $table['tableName'];
                
                if (!isset($this->_describeTableCache[$tableName])) {
                    $this->_describeTableCache[$tableName] = $adapter->describeTable($tableName);
                }
                $matchingFields = array_intersect($unmappedFields, array_keys($this->_describeTableCache[$tableName]));
                
                if (!empty($matchingFields)) {
                    $matchingTables[$tableAlias] = array(
                        'fields'   => $matchingFields,
                        // @todo better priority determination if useful
                        'priority' => ($table['joinType'] == Zend_Db_Select::FROM ? 1 : ($table['joinType'] == Zend_Db_Select::LEFT_JOIN ? 100 : 10)),
                    );
                }
            }
            
            uasort($matchingTables, array($this, '_sortMatchingTables'));
            
            foreach ($matchingTables as $tableAlias => $table) {
                $fields = array_intersect($unmappedFields, $table['fields']);
                $unmappedFields = array_diff($unmappedFields, $fields);
                
                foreach ($fields as $field) {
                    $this->addFilterToCollectionMap($collection, $adapter->quoteIdentifier($tableAlias.'.'.$field), $field);
                }
                if (empty($unmappedFields)) {
                    break;
                }
            }
            
            // @todo should it be a toggable feature [at grid level] ?
            // @todo in case of multiple matching tables for a single field, should we inform the user ? (should not be troublesome in almost all cases)
        }
        
        return $this;
    }
    
    public function prepareGridCollectionFiltersMap($collection, $block, $model, $filters)
    {
        if (!$this->shouldPrepareCollectionFiltersMap($collection)) {
            return $this;
        }
        
        $blockType = $model->getBlockType();
        $previousFiltersMap = $this->_getCollectionFiltersMap($collection);
        $collection->setFlag(self::COLLECTION_PREVIOUS_MAP_FLAG, $previousFiltersMap);
        
        if (isset($this->_baseFiltersMapCallbacks[$blockType])) {
            call_user_func(array($this, $this->_baseFiltersMapCallbacks[$blockType]), $collection, $block, $model);
        }
        if (isset($this->_additionalFiltersMapCallbacks[$blockType])) {
            foreach ($this->_additionalFiltersMapCallbacks[$blockType] as $callback) {
                call_user_func_array(
                    $callback['callback'],
                    array_merge(
                        array_values($callback['params']),
                        ($callback['add_native']? array($collection, $block, $model) : array())
                    )
                );
            }
        }
        
        $this->_handleUnmappedFilters($collection, $block, $model, $filters);
        $collection->setFlag(self::COLLECTION_APPLIED_MAP_FLAG, true);
        
        return $this;
    }
    
    public function restoreGridCollectionFiltersMap($collection, $block, $model, $resetAppliedFlag=true)
    {
        if ($previousFiltersMap = $collection->getFlag(self::COLLECTION_PREVIOUS_MAP_FLAG)) {
            $this->_setCollectionFiltersMap($collection, $previousFiltersMap);
            
            if ($resetAppliedFlag) {
                $collection->setFlag(self::COLLECTION_APPLIED_MAP_FLAG, true);
            }
        }
        return $this;
    }
    
    protected function _prepareCatalogProductFiltersMap($collection, $block, $model)
    {
        $this->addFilterToCollectionMap(
            $collection,
            $this->buildFiltersMapArray(array(
                'entity_id',
                'type_id',
                'attribute_set_id',
                'sku',
                'has_options',
                'required_options',
                'created_at',
                'updated_at',
            ), $this->getCollectionMainTableAlias($collection))
        );
        $this->addFilterToCollectionMap($collection, $this->getAttributeTableAlias('qty').'.qty', 'qty');
        return $this;
    }
    
    /**
    * @todo guess that now the below methods aren't really necessary anymore, with the use of _handleUnmappedFilters(),
    * yet it certainly can speed up some related treatments by reducing the number of cases where _handleUnmappedFilters() is actually needed
    * (or even eliminating all of them for the concerned tables)
    */
    
    protected function _prepareSalesOrderFiltersMap($collection, $block, $model)
    {
        $this->addFilterToCollectionMap(
            $collection,
            $this->buildFiltersMapArray(array(
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
            ), $this->getCollectionMainTableAlias($collection))
        );
        return $this;
    }
    
    protected function _prepareSalesInvoiceFiltersMap($collection, $block, $model)
    {
        $this->addFilterToCollectionMap(
            $collection,
            $this->buildFiltersMapArray(array(
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
            ), $this->getCollectionMainTableAlias($collection))
        );
        return $this;
    }
    
    protected function _prepareSalesShipmentFiltersMap($collection, $block, $model)
    {
        $this->addFilterToCollectionMap(
            $collection,
            $this->buildFiltersMapArray(array(
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
            ), $this->getCollectionMainTableAlias($collection))
        );
        return $this;
    }
    
    protected function _prepareSalesCreditmemoFiltersMap($collection, $block, $model)
    {
        $this->addFilterToCollectionMap(
            $collection,
            $this->buildFiltersMapArray(array(
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
            ), $this->getCollectionMainTableAlias($collection))
        );
        return $this;
    }
}