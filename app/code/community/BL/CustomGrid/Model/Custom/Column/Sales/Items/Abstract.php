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
 * @copyright  Copyright (c) 2013 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

abstract class BL_CustomGrid_Model_Custom_Column_Sales_Items_Abstract extends BL_CustomGrid_Model_Custom_Column_Abstract
{
    protected function _canFilterOnSku()
    {
        return true;
    }
    
    protected function _canFilterOnName()
    {
        return true;
    }
    
    protected function _canExcludeChildrenFromFilter()
    {
        return true;
    }
    
    protected function _canAllowSqlWildcardsInFilter()
    {
        return true;
    }
    
    protected function _isCustomizableList()
    {
        return false;
    }
    
    protected function _getItemBaseValues()
    {
        return array();
    }
    
    protected function _getItemAmountsValuesKeys()
    {
        return array();
    }
    
    protected function _getItemValuesEventName()
    {
        return null;
    }
    
    protected function _sortItemValues(BL_CustomGrid_Object $valueA, BL_CustomGrid_Object $valueB)
    {
        return $valueA->compareIntDataTo('position', $valueB);
    }
    
    protected function _getItemValues()
    {
        $salesHelper = Mage::helper('sales');
        $baseValues  = $this->_getItemBaseValues();
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
                    'default'     => true,
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
    
    public function getItemValues()
    {
        if (!$this->hasData('item_values')) {
            $this->setData('item_values', $this->_getItemValues());
        }
        return $this->_getData('item_values');
    }
    
    protected function _prepareConfig()
    {
        $helper = $this->_getBaseHelper();
        
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
                        'group'        => $helper->__('Fields'),
                        'description'  => $itemValue->getDescription(),
                        'type'         => 'select',
                        'source_model' => 'customgrid/system_config_source_yesno',
                        'value'        => ($itemValue->getDefault() ? 1 : 0),
                    ),
                    ($position -= 10)
                );
            }
            
            $this->setCustomizationWindowConfig(array('height' => 500), true);
        } else {
            $this->setCustomizationWindowConfig(array('height' => 300), true);
        }
        
        return parent::_prepareConfig();
    }
    
    protected function _getOrdersCollection($ordersIds)
    {
        return Mage::getModel('sales/order')
            ->getCollection()
            ->addFieldToFilter('entity_id', array('in' => array_unique($ordersIds)))
            ->load();
    }
    
    protected function _getOrdersItemsCollection($ordersIds, $excludeChildren = true, $eventName = null)
    {
        $items = Mage::getModel('sales/order_item')
            ->getCollection()
            ->addFieldToFilter('order_id', array('in' => array_unique($ordersIds)));
        
        if ($excludeChildren) {
            $items->filterByParent();
        }
        if (!empty($event)) {
            $response = new BL_CustomGrid_Object(array('items_collection' => $items));
            Mage::dispatchEvent($eventName, array('response' => $response));
            $items = $response->getItemsCollection();
        }
        
        return $items->load();
    }
    
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
    
    abstract protected function _getItemsTable();
    abstract protected function _getParentFkFieldName();
    abstract protected function _getParentPkFieldName();
    
    protected function _isOrderItemsList()
    {
        return ($this->_getItemsTable() == 'sales/order_item');
    }
    
    protected function _addExcludeChildFilterToSelect(
        Varien_Db_Select $select,
        $itemAlias, 
        Varien_Data_Collection_Db $collection,
        $qi
    ) {
        if (!$this->_isOrderItemsList()) {
            $oiAlias = $this->_getUniqueTableAlias('oi');
            
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
        list($adapter, $qi) = $this->_getCollectionAdapter($collection, true);
        $mainAlias = $this->_getCollectionMainTableAlias($collection);
        $itemAlias = $this->_getUniqueTableAlias();
        $params    = $columnBlock->getBlcgFilterParams();
        
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
                array($itemAlias => $collection->getTable($this->_getItemsTable())),
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
            
            if ($this->_extractBoolParam($params, 'allow_sql_wildcards')) {
                $values['single_wildcard']   = '_';
                $values['multiple_wildcard'] = '%';
            }
        }
        if ($this->_isCustomizableList()) {
            $itemValues = $this->getItemValues();
            
            foreach ($itemValues as $key => $itemValue) {
                $shouldDisplay = $this->_extractBoolParam($params, 'display_' . $itemValue->getCode(), null);
                
                if ((!is_null($shouldDisplay) && !$shouldDisplay) || !$itemValue->getDefault()) {
                    unset($itemValues[$key]);
                }
            }
            
            $values['hide_header'] = $this->_extractBoolParam($params, 'hide_header', false);
            $values['item_values'] = $itemValues;
        }
        
        return $values;
    }
}
