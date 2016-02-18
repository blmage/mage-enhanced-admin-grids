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

abstract class BL_CustomGrid_Model_Custom_Column_Sales_Items_Abstract extends BL_CustomGrid_Model_Custom_Column_Abstract
{
    /**
     * Return reflection helper
     * 
     * @return BL_CustomGrid_Helper_Reflection
     */
    protected function _getReflectionHelper()
    {
        return Mage::helper('customgrid/reflection');
    }
    
    /**
     * Return whether filtering on item SKU is allowed
     * 
     * @return bool
     */
    protected function _canFilterOnSku()
    {
        return true;
    }
    
    /**
     * Return whether filtering on item name is allowed
     * 
     * @return bool
     */
    protected function _canFilterOnName()
    {
        return true;
    }
    
    /**
     * Return whether excluding children items from filtering is allowed
     * 
     * @return bool
     */
    protected function _canExcludeChildrenFromFilter()
    {
        return true;
    }
    
    /**
     * Return whether SQL wildcards are allowed for filtering
     * 
     * @return bool
     */
    protected function _canAllowSqlWildcardsInFilter()
    {
        return true;
    }
    
    /**
     * Return whether this is a customizable items list (ie columns are choosable)
     * 
     * @return bool
     */
    protected function _isCustomizableList()
    {
        return false;
    }
    
    /**
     * Return the base item values that are displayable in columns
     * 
     * @return array
     */
    protected function _getItemBaseValues()
    {
        return array();
    }
    
    /**
     * Return the keys of the base item values that should automatically be chosen by default
     * 
     * @return array
     */
    protected function _getItemDefaultBaseValuesKeys()
    {
        return array_keys($this->_getItemBaseValues());
    }
    
    /**
     * Return the keys of the amounts values
     * 
     * @return array
     */
    protected function _getItemAmountsValuesKeys()
    {
        return array();
    }
    
    /**
     * Return the name of the event dispatchable to retrieve additional item values,
     * or null if no event should be dispatched
     * 
     * @return string|null
     */
    protected function _getItemValuesEventName()
    {
        return null;
    }
    
    /**
     * Callback for item values sorting
     * 
     * @param BL_CustomGrid_Object $valueA One item value
     * @param BL_CustomGrid_Object $valueB Another item value
     * @return int
     */
    protected function _sortItemValues(BL_CustomGrid_Object $valueA, BL_CustomGrid_Object $valueB)
    {
        return $valueA->compareIntDataTo('position', $valueB);
    }
    
    /**
     * Return the item values to use as columns
     * 
     * @return array
     */
    protected function _getItemValues()
    {
        /** @var $salesHelper Mage_Sales_Helper_Data */
        $salesHelper = Mage::helper('sales');
        $baseValues  = $this->_getItemBaseValues();
        $defaultBaseValuesKeys = $this->_getItemDefaultBaseValuesKeys();
        $amountsKeys = $this->_getItemAmountsValuesKeys();
        $eventName   = $this->_getItemValuesEventName();
        $itemValues  = array();
        $position    = 0;
        
        foreach ($baseValues as $key => $value) {
            $itemValues[$key] = new BL_CustomGrid_Object(
                array(
                    'code'        => $key,
                    'name'        => $salesHelper->__($value),
                    'description' => '',
                    'default'     => in_array($key, $defaultBaseValuesKeys),
                    'position'    => ($position += 100),
                )
            );
            
            if (in_array($key, $amountsKeys)) {
                $itemValues[$key]->setData('value_align', 'right');
            }
            
            // Also usable:
            // - "header_align": header label alignment (possible values: left, center, right)
            // - "renderers": array of renderer block codes, ordered by priority
        }
        
        if (!empty($eventName)) {
            $response = new BL_CustomGrid_Object(array('item_values' => $itemValues));
            Mage::dispatchEvent($eventName, array('response' => $response));
            $itemValues = $response->getItemValues();
        }
        
        uasort($itemValues, array($this, '_sortItemValues'));
        $itemValue = null;
        
        foreach ($itemValues as $key => $itemValue) {
            if (is_array($itemValue)) {
                $itemValue = new BL_CustomGrid_Object($itemValue);
                $itemValues[$key] = $itemValue;
            }
            if (!is_object($itemValue)) {
                unset($itemValues[$key]);
                continue;
            }
            
            $itemValue->addData(
                array(
                    'last' => false,
                    'renderers/999999' => 'customgrid/widget_grid_column_renderer_sales_items_sub_value_default',
                )
            );
            
            $itemValue->ksortData('renderers', SORT_NUMERIC);
        }
        if (is_object($itemValue)) {
            $itemValue->setData('last', true);
        }
        
        return $itemValues;
    }
    
    /**
     * Return the item values to use as columns
     * 
     * @return array
     */
    public function getItemValues()
    {
        if (!$this->hasData('item_values')) {
            $this->setData('item_values', $this->_getItemValues());
        }
        return $this->_getData('item_values');
    }
    
    protected function _prepareConfig()
    {
        $helper = $this->getBaseHelper();
        
        if ($this->_canFilterOnSku()) {
            $this->addCustomizationParam(
                'filter_on_sku',
                array(
                    'label'        => $helper->__('Filter on Item SKU'),
                    'group'        => $helper->__('Filtering'),
                    'type'         => 'select',
                    'source_model' => 'customgrid/system_config_source_yesno',
                    'value'        => 0,
                ),
                10
            );
        }
        if ($this->_canFilterOnName()) {
            $this->addCustomizationParam(
                'filter_on_name',
                array(
                    'label'        => $helper->__('Filter on Item Name'),
                    'group'        => $helper->__('Filtering'),
                    'type'         => 'select',
                    'source_model' => 'customgrid/system_config_source_yesno',
                    'value'        => 0,
                ),
                20
            );
        }
        if ($this->_canExcludeChildrenFromFilter()) {
            $this->addCustomizationParam(
                'filter_exclude_child',
                array(
                    'label'        => $helper->__('Exclude Child Items From Filter'),
                    'group'        => $helper->__('Filtering'),
                    'type'         => 'select',
                    'source_model' => 'customgrid/system_config_source_yesno',
                    'value'        => 0,
                ),
                30
            );
        }
        if ($this->_canAllowSqlWildcardsInFilter()) {
            $this->addCustomizationParam(
                'allow_sql_wildcards',
                array(
                    'label'        => $helper->__('Allow SQL Wildcards In Filter'),
                    'group'        => $helper->__('Filtering'),
                    'type'         => 'select',
                    'source_model' => 'customgrid/system_config_source_yesno',
                    'value'        => 0,
                ),
                40
            );
        }
        
        if ($this->_isCustomizableList()) {
            $itemValues = array_reverse($this->getItemValues());
            $position = 10000;
            $hideHeaderDescription = 'Choose "<strong>Yes</strong>" if you do not want the field labels to be '
                . 'displayed in the header';
            
            $this->addCustomizationParam(
                'hide_header',
                array(
                    'label'        => $helper->__('Hide Header'),
                    'group'        => $helper->__('Rendering'),
                    'description'  => $helper->__($hideHeaderDescription),
                    'type'         => 'select',
                    'source_model' => 'customgrid/system_config_source_yesno',
                ),
                50
            );
            
            foreach ($itemValues as $key => $itemValue) {
                $this->addCustomizationParam(
                    'display_' . $key,
                    array(
                        'label'        => $helper->__('Display "%s"', $itemValue->getName()),
                        'group'        => $helper->__('Fields - Choice'),
                        'description'  => $itemValue->getDescription(),
                        'type'         => 'select',
                        'source_model' => 'customgrid/system_config_source_yesno',
                        'value'        => ($itemValue->getDefault() ? 1 : 0),
                    ),
                    ($position -= 10)
                );
                
                $this->addCustomizationParam(
                    'custom_header_' . $key,
                    array(
                        'label' => $helper->__('"%s"', $itemValue->getName()),
                        'group' => $helper->__('Fields - Custom Column Headers'),
                        'type'  => 'text',
                    ),
                    ($position + 10000)
                );
            }
            
            $this->setCustomizationWindowConfig(array('height' => 500), true);
        } else {
            $this->setCustomizationWindowConfig(array('height' => 300), true);
        }
        
        return parent::_prepareConfig();
    }
    
    /**
     * Return a loaded orders collection filtered by the given IDs
     * 
     * @param array $ordersIds Orders IDs
     * @return Mage_Sales_Model_Mysql4_Order_Collection
     */
    protected function _getOrdersCollection(array $ordersIds)
    {
        /** @var $collection Mage_Sales_Model_Mysql4_Order_Collection */
        $collection = Mage::getResourceModel('sales/order_collection');
        $collection->addFieldToFilter('entity_id', array('in' => array_unique($ordersIds)));
        return $collection->load();
    }
    
    /**
     * Return a loaded order items collection filtered by the given orders IDs
     * 
     * @param array $ordersIds Parent orders IDs
     * @param bool $excludeChildren Whether children items should be excluded from the collection
     * @param string|null $eventName Name of the event that will be dispatched before the collection is loaded, if any
     * @return Mage_Sales_Model_Mysql4_Order_Item_Collection
     */
    protected function _getOrdersItemsCollection($ordersIds, $excludeChildren = true, $eventName = null)
    {
        /** @var $items Mage_Sales_Model_Mysql4_Order_Item_Collection */
        $items = Mage::getResourceModel('sales/order_item_collection');
        $items->addFieldToFilter('order_id', array('in' => array_unique($ordersIds)));
        
        if ($excludeChildren) {
            $items->filterByParent();
        }
        if (!empty($eventName)) {
            $response = new BL_CustomGrid_Object(array('items_collection' => $items));
            Mage::dispatchEvent($eventName, array('response' => $response));
            $items = $response->getItemsCollection();
        }
        
        return $items->load();
    }
    
    /**
     * Add their items to each sales entity from the given grid collection
     * 
     * @param string $columnIndex Items data key in the sales entities
     * @param array $params Customization params values
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param bool $firstTime Whether the grid collection is prepared for the first time
     * @return BL_CustomGrid_Model_Custom_Column_Sales_Items_Abstract
     */
    abstract public function addItemsToGridCollection(
        $columnIndex,
        array $params,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection_Db $collection,
        $firstTime
    );
    
    public function applyToGridCollection(
        Varien_Data_Collection_Db $collection,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        $columnBlockId,
        $columnIndex,
        array $params,
        Mage_Core_Model_Store $store
    ) {
        $gridBlock->blcg_addCollectionCallback(
            self::GC_EVENT_AFTER_PREPARE,
            array($this, 'addItemsToGridCollection'),
            array($columnIndex, $params),
            true
        );
        return $this;
    }
    
    /**
     * Return the name of the items table
     * 
     * @return string
     */
    abstract protected function _getItemsTableName();
    
    /**
     * Return the name of the foreign key from the items table,
     * that will be used to join this table to the main table of the grid collection
     * 
     * @return string
     */
    abstract protected function _getParentFkFieldName();
    
    /**
     * Return the name of the primary key from the main table of the grid collection,
     * that will be used to join the items table to this table
     * 
     * @return string
     */
    abstract protected function _getParentPkFieldName();
    
    /**
     * Return whether this items list handles order items
     * 
     * @return bool
     */
    protected function _isOrderItemsList()
    {
        return ($this->_getItemsTableName() == 'sales/order_item');
    }
    
    /**
     *  Add a condition excluding children items on the given database select
     * 
     * @param Varien_Db_Select $select Database select
     * @param mixed $itemAlias Alias of the items table
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param callable $qi quoteIdentifier() callback
     * @return BL_CustomGrid_Model_Custom_Column_Sales_Items_Abstract
     */
    protected function _addExcludeChildFilterToSelect(
        Varien_Db_Select $select,
        $itemAlias, 
        Varien_Data_Collection_Db $collection,
        $qi
    ) {
        if (!$this->_isOrderItemsList()) {
            $oiAlias = $this->getCollectionHandler()->getUniqueTableAlias('oi');
            
            $select->joinInner(
                array($oiAlias => $collection->getTable('sales/order_item')),
                $qi($itemAlias . '.order_item_id') . ' = ' . $qi($oiAlias . '.item_id'),
                array()
            );
            
            $select->where($qi($oiAlias . '.parent_item_id') . ' IS NULL');
        } else {
            $select->where($qi($itemAlias . '.parent_item_id') . ' IS NULL');
        }
        return $this;
    }
    
    public function addFilterToGridCollection(
        Varien_Data_Collection_Db $collection,
        Mage_Adminhtml_Block_Widget_Grid_Column $columnBlock
    ) {
        $collectionHandler = $this->getCollectionHandler();
        $mainAlias = $collectionHandler->getCollectionMainTableAlias($collection);
        $itemAlias = $collectionHandler->getUniqueTableAlias();
        $params    = $columnBlock->getBlcgFilterParams();
        
        /** @var $adapter Zend_Db_Adapter_Abstract */
        list($adapter, $qi) = $collectionHandler->getCollectionAdapter($collection, true);
        
        if (is_array($condition = $columnBlock->getFilter()->getCondition()) && isset($condition['like'])) {
            $condition = $condition['like'];
        } else {
            return $this;
        }
        
        $textConditions = array();
        
        if ($this->_extractBoolParam($params, 'filter_on_sku')) {
            $textConditions[] = $adapter->quoteInto($qi($itemAlias . '.sku') . ' LIKE ?', $condition);
        }
        if ($this->_extractBoolParam($params, 'filter_on_name')) {
            $textConditions[] = $adapter->quoteInto($qi($itemAlias . '.name') . ' LIKE ?', $condition);
        }
        if (empty($textConditions)) {
            return $this;
        }
        
        $fkFieldName = $this->_getParentFkFieldName();
        $pkFieldName = $this->_getParentPkFieldName();
        
        $select = $adapter->select()
            ->from(
                array($itemAlias => $collection->getTable($this->_getItemsTableName())),
                array('count' => new Zend_Db_Expr('COUNT(*)'))
            )
            ->where($qi($itemAlias . '.' . $fkFieldName) . ' = ' . $qi($mainAlias . '.' . $pkFieldName))
            ->where(implode(' OR ', $textConditions));
        
        if ($this->_extractBoolParam($params, 'filter_exclude_child')) {
            $this->_addExcludeChildFilterToSelect($select, $itemAlias, $collection, $qi);
        }
        
        $collection->getSelect()->where(new Zend_Db_Expr($select) . ' > 0');
        return $this;
    }
    
    /**
     * Return the renderer usable for the grid column block
     * 
     * @return string
     */
    abstract protected function _getColumnBlockRenderer();
    
    public function getForcedBlockValues(
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        $columnBlockId,
        $columnIndex,
        array $params,
        Mage_Core_Model_Store $store
    ) {
        $values = array(
            'filter'   => false,
            'sortable' => false,
            'renderer' => $this->_getColumnBlockRenderer(),
            'single_wildcard'   => false,
            'multiple_wildcard' => false,
        );
        
        if ($this->_extractBoolParam($params, 'filter_on_sku')
            || $this->_extractBoolParam($params, 'filter_on_name')) {
            $values['filter'] = 'customgrid/widget_grid_column_filter_text';
            $values['filter_condition_callback'] = array($this, 'addFilterToGridCollection');
            $values['blcg_filter_params'] = $params;
            $values['filter_mode'] = BL_CustomGrid_Block_Widget_Grid_Column_Filter_Text::MODE_INSIDE_LIKE;
            
            if ($this->_extractBoolParam($params, 'allow_sql_wildcards')) {
                $values['single_wildcard']   = '_';
                $values['multiple_wildcard'] = '%';
            }
        }
        if ($this->_isCustomizableList()) {
            $itemValues = $this->getItemValues();
            
            foreach ($itemValues as $key => $itemValue) {
                $valueCode = $itemValue->getCode();
                $shouldDisplay = $this->_extractBoolParam($params, 'display_' . $valueCode, null);
                
                if ((is_null($shouldDisplay) && !$itemValue->getDefault())
                    || (!is_null($shouldDisplay) && !$shouldDisplay)) {
                    unset($itemValues[$key]);
                } elseif ($customHeader = $this->_extractStringParam($params, 'custom_header_' . $valueCode, null)) {
                    $itemValue->setName($customHeader);
                }
            }
            
            $values['hide_header'] = $this->_extractBoolParam($params, 'hide_header', false);
            $values['item_values'] = $itemValues;
        }
        
        return $values;
    }
}
