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

abstract class BL_CustomGrid_Model_Custom_Column_Sales_Items_Abstract
    extends BL_CustomGrid_Model_Custom_Column_Abstract
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
    
    protected function _sortItemValues($a, $b)
    {
        return ($a['position'] > $b['position'] ? 1 : ($a['position'] < $b['position'] ? -1 : 0));
    }
    
    protected function _getItemValuesList($baseValues, $amountsKeys, $event=null)
    {
        $itemValues  = array();
        $salesHelper = Mage::helper('sales');
        $position    = 0;
        
        foreach ($baseValues as $key => $value) {
            $itemValues[$key] = array(
                'code'         => $key,
                'name'         => $salesHelper->__($value),
                'description'  => '',
                'default'      => true,
                'position'     => ($position += 100),
                'renderers'    => array(999999 => 'customgrid/widget_grid_column_renderer_sales_items_sub_value_default'),
            );
            
            if (in_array($key, $amountsKeys)) {
                $itemValues[$key]['value_align'] = 'right';
            }
            // Also usable: "header_align" for header label alignment
        }
        
        if (!empty($event)) {
            $response = new Varien_Object(array('item_values' => $itemValues));
            Mage::dispatchEvent($event, array('response' => $response));
            $itemValues = $response->getItemValues();
        }
        
        uasort($itemValues, array($this, '_sortItemValues'));
        
        foreach ($itemValues as $key => $value) {
            $itemValues[$key]['last'] = false;
            sort($itemValues[$key]['renderers'], SORT_NUMERIC);
        }
        if (!is_null($key)) {
            $itemValues[$key]['last'] = true;
        }
        
        return $itemValues;
    }
    
    public function getItemValues()
    {
        return array();
    }
    
    public function initConfig()
    {
        parent::initConfig();
        $helper = Mage::helper('customgrid');
        
        if ($this->_canFilterOnSku()) {
            $this->addCustomParam('filter_on_sku', array(
                'label'        => $helper->__('Filter on Item SKU'),
                'type'         => 'select',
                'source_model' => 'customgrid/system_config_source_yesno',
                'value'        => 0,
            ), 100000);
        }
        
        if ($this->_canFilterOnName()) {
            $this->addCustomParam('filter_on_name', array(
                'label'        => $helper->__('Filter on Item Name'),
                'type'         => 'select',
                'source_model' => 'customgrid/system_config_source_yesno',
                'value'        => 0,
            ), 100010);
        }
        
        if ($this->_canExcludeChildrenFromFilter()) {
            $this->addCustomParam('filter_exclude_child', array(
                'label'        => $helper->__('Exclude Child Items From Filter'),
                'type'         => 'select',
                'source_model' => 'customgrid/system_config_source_yesno',
                'value'        => 0,
            ), 100020);
        }
        
        if ($this->_canAllowSqlWildcardsInFilter()) {
            $this->addCustomParam('allow_sql_wildcards', array(
                'label'        => $helper->__('Allow SQL Wildcards In Filter'),
                'type'         => 'select',
                'source_model' => 'customgrid/system_config_source_yesno',
                'value'        => 0,
            ), 100030);
        }
        
        if ($this->_isCustomizableList()) {
            $itemValues = array_reverse($this->getItemValues());
            $position = -10;
            
            foreach ($itemValues as $key => $value) {
                 $this->addCustomParam('display_'.$key, array(
                    'label'        => $helper->__('Display "%s"', $value['name']),
                    'description'  => $value['description'],
                    'type'         => 'select',
                    'source_model' => 'customgrid/system_config_source_yesno',
                    'value'        => ($value['default'] ? 1 : 0),
                ), ($position -= 10));
            }
            
            $this->addCustomParam('hide_header', array(
                'label'        => $helper->__('Hide Header'),
                'description'  => $helper->__('Choose "Yes" if you do not want the field labels to be displayed in the header'),
                'type'         => 'select',
                'source_model' => 'customgrid/system_config_source_yesno',
            ), 0);
            
            $this->setCustomParamsWindowConfig(array('height' => 500));
        } else {
            $this->setCustomParamsWindowConfig(array('height' => 300));
        }
         
        return $this;
    }
    
    protected function _getOrdersCollection($ordersIds)
    {
        return Mage::getModel('sales/order')
            ->getCollection()
            ->addFieldToFilter('entity_id', array('in' => array_unique($ordersIds)))
            ->load();
    }
    
    protected function _getOrdersItemsCollection($ordersIds, $excludeChildren=true, $event=null)
    {
        $items = Mage::getModel('sales/order_item')
            ->getCollection()
            ->addFieldToFilter('order_id', array('in' => array_unique($ordersIds)));
        
        if ($excludeChildren) {
            $items->filterByParent();
        }
        if (!empty($event)) {
            $response = new Varien_Object(array('items_collection' => $items));
            Mage::dispatchEvent($event, array('response' => $response));
            $items = $response->getItemsCollection();
        }
        
        return $items->load();
    }
    
    abstract public function addItemsToGridCollection($alias, $params, $block, $collection, $firstTime);
    
    protected function _applyToGridCollection($collection, $block, $model, $id, $alias, $params, $store, $renderer=null)
    {
        $block->blcg_addCollectionCallback(
            self::GC_EVENT_AFTER_PREPARE,
            array($this, 'addItemsToGridCollection'),
            array($alias, $params),
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
    
    protected function _addExcludeChildFilterToSelect($select, $itemAlias, $collection, $column, $qi)
    {
        if (!$this->_isOrderItemsList()) {
            $oiAlias = $this->_getUniqueTableAlias('oi');
            
            $select->joinInner(
                    array($oiAlias => $collection->getTable('sales/order_item')),
                    $qi($itemAlias.'.order_item_id').' = '.$qi($oiAlias.'.item_id'),
                    array()
                )
                ->where($qi($oiAlias.'.parent_item_id').' IS NULL');
        } else {
            $select->where($qi($itemAlias.'.parent_item_id').' IS NULL');
        }
        return $this;
    }
    
    public function addFilterToGridCollection($collection, $column)
    {
        list($adapter, $qi) = $this->_getCollectionAdapter($collection, true);
        $mainAlias = $this->_getCollectionMainTableAlias($collection);
        $itemAlias = $this->_getUniqueTableAlias();
        $params    = $column->getBlcgFilterParams();
        
        if (is_array($condition = $column->getFilter()->getCondition())
            && isset($condition['like'])) {
            $condition = $condition['like'];
        } else {
            return $this;
        }
        $textConditions = array();
        
        if ($this->_extractBoolParam($params, 'filter_on_sku')) {
            $textConditions[] = $adapter->quoteInto($qi($itemAlias.'.sku').'  LIKE ?', $condition);
        }
        if ($this->_extractBoolParam($params, 'filter_on_name')) {
            $textConditions[] = $adapter->quoteInto($qi($itemAlias.'.name').'  LIKE ?', $condition);
        }
        if (empty($textConditions)) {
            return $this;
        }
        
        $select = $adapter->select()
            ->from(
                array($itemAlias => $collection->getTable($this->_getItemsTable())),
                array('count' => new Zend_Db_Expr('COUNT(*)'))
            )
            ->where($qi($itemAlias.'.'.$this->_getParentFkFieldName()).' = '.$qi($mainAlias.'.'.$this->_getParentPkFieldName()))
            ->where(implode(' OR ', $textConditions));
        
        if ($this->_extractBoolParam($params, 'filter_exclude_child')) {
            $this->_addExcludeChildFilterToSelect($select, $itemAlias, $collection, $column, $qi);
        }
        
        $collection->getSelect()->where(new Zend_Db_Expr($select).' > 0');
        return $this;
    }
    
    abstract protected function _getGridColumnRenderer();
    
    protected function _getForcedGridValues($block, $model, $id, $alias, $params, $store, $renderer=null)
    {
        $values = array(
            'filter'   => false,
            'renderer' => $this->_getGridColumnRenderer(),
            'sortable' => false,
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
            
            foreach ($itemValues as $key => $value) {
                if (!$this->_extractBoolParam($params, 'display_'.$value['code'], true)) {
                    unset($itemValues[$key]);
                }
            }
            
            $values['hide_header'] = $this->_extractBoolParam($params, 'hide_header', false);
            $values['item_values'] = $itemValues;
        }
        
        return $values;
    }
}
