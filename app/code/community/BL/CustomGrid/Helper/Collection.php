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
    const COLLECTION_APPLIED_MAP_FLAG = '_blcg_hc_applied_map_';
    
    /**
    * Registered $adapter->quote*() callbacks (usable for readability)
    * 
    * @var array
    */
    protected $_quoteIdentifierCallbacks = array();
    
    /**
    * Base callbacks to call when building filters map for a given grid block
    * 
    * @var array
    */
    protected $_baseFiltersMapCallbacks  = array(
        'adminhtml/catalog_product_grid' => '_prepareCatalogProductFiltersMap',
        'adminhtml/sales_order_grid'     => '_prepareSalesOrderFiltersMap',
    );
    
    /**
    * Additional callbacks to call when building filters map for a given grid block
    * 
    * @var array
    */
    protected $_additionalFiltersMapCallbacks = array();
    
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
    
    public function callQuoteIdentifierCallback($callback, $identifier)
    {
        if (isset($this->_quoteIdentifierCallbacks[$callback])) {
            return $callback($this->_quoteIdentifierCallbacks[$callback]['adapter'], $identifier);
        }
        return $identifier;
    }
    
    public function getQuoteIdentifierCallback($adapter)
    {
        $quoteCallback = create_function('$a, $i', 'return $a->quoteIdentifier($i);');
        $callCallback  = create_function('$i', 'return Mage::helper(\'customgrid/collection\')->callQuoteIdentifierCallback(\''.$quoteCallback.'\', $i);');
        $this->_quoteIdentifierCallbacks[$quoteCallback] = array('adapter' => $adapter);
        return $callCallback;
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
    
    public function prepareGridCollectionFiltersMap($collection, $block, $model)
    {
        if (!$this->shouldPrepareCollectionFiltersMap($collection)) {
            return $this;
        }
        
        $blockType = $model->getBlockType();
        
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
        
        $collection->setFlag(self::COLLECTION_APPLIED_MAP_FLAG, true);
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
}