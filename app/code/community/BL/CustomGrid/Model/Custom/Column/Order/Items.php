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
 
class BL_CustomGrid_Model_Custom_Column_Order_Items
    extends BL_CustomGrid_Model_Custom_Column_Abstract
{
    public function initConfig()
    {
        parent::initConfig();
        $helper = Mage::helper('customgrid');
        
        $this->addCustomParam('filter_on_sku', array(
            'label'        => $helper->__('Filter on Item SKU'),
            'type'         => 'select',
            'source_model' => 'customgrid/system_config_source_yesno',
            'value'        => 0,
        ), 10);
        
        $this->addCustomParam('filter_on_name', array(
            'label'        => $helper->__('Filter on Item Name'),
            'type'         => 'select',
            'source_model' => 'customgrid/system_config_source_yesno',
            'value'        => 0,
        ), 20);
        
        
        $this->addCustomParam('filter_exclude_child', array(
            'label'        => $helper->__('Exclude Child Items From Filter'),
            'type'         => 'select',
            'source_model' => 'customgrid/system_config_source_yesno',
            'value'        => 0,
        ), 30);
        
        $this->addCustomParam('allow_sql_wildcards', array(
            'label'        => $helper->__('Allow SQL Wildcards In Filter'),
            'type'         => 'select',
            'source_model' => 'customgrid/system_config_source_yesno',
            'value'        => 0,
        ), 40);
        
        $this->setCustomParamsWindowConfig(array('height' => 300));
        
        return $this;
    }
    
    public function addItemsToGridCollection($alias, $params, $block, $collection, $firstTime)
    {
        if (!$firstTime && !$block->blcg_isExport()) {
            $ordersIds = array();
            
            foreach ($collection as $order) {
                $ordersIds[] = $order->getId();
            }
            
            $items = Mage::getModel('sales/order_item')
                ->getCollection()
                ->addFieldToFilter('order_id', array('in' => $ordersIds))
                ->filterByParent()
                ->load();
            
            $orderReflection = new ReflectionClass('Mage_Sales_Model_Order');
            $itemsProperty   = $orderReflection->getProperty('_items');
            $itemsProperty->setAccessible(true);
            
            foreach ($collection as $order) {
                $orderItems = clone $items;
                
                foreach ($orderItems as $item) {
                    if ($item->getOrderId() != $order->getId()) {
                        $orderItems->removeItemByKey($item->getId());
                    } else {
                        $item->setOrder($order);
                    }
                }
                
                $itemsProperty->setValue($order, $orderItems);
            }
        }
        return $this;
    }
    
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
    
    public function addFilterToGridCollection($collection, $column)
    {
        $helper    = $this->_getCollectionHelper();
        $mainAlias = $this->_getCollectionMainTableAlias($collection);
        list($adapter, $qi) = $this->_getCollectionAdapter($collection, true);
        $oiAlias   = $this->_getUniqueTableAlias();
        $params    = $column->getBlcgFilterParams();
        
        if (is_array($condition = $column->getFilter()->getCondition())
            && isset($condition['like'])) {
            $condition = $condition['like'];
        } else {
            return $this;
        }
        $textConditions = array();
        
        if ($this->_extractBoolParam($params, 'filter_on_sku')) {
            $textConditions[] = $adapter->quoteInto($qi($oiAlias.'.sku').'  LIKE ?', $condition);
        }
        if ($this->_extractBoolParam($params, 'filter_on_name')) {
            $textConditions[] = $adapter->quoteInto($qi($oiAlias.'.name').'  LIKE ?', $condition);
        }
        if (empty($textConditions)) {
            return $this;
        }
        
        $select = $adapter->select()
            ->from(
                array($oiAlias => $collection->getTable('sales/order_item')),
                array('count' => new Zend_Db_Expr('COUNT(*)'))
            )
            ->where($qi($oiAlias.'.order_id').' = '.$qi($mainAlias.'.entity_id'))
            ->where(implode(' OR ', $textConditions));
        
        if ($this->_extractBoolParam($params, 'filter_exclude_child')) {
            $select->where($qi($oiAlias.'.parent_item_id').' IS NULL');
        }
        
        $collection->getSelect()->where(new Zend_Db_Expr($select).' > 0');
        return $this;
    }
    
    protected function _getForcedGridValues($block, $model, $id, $alias, $params, $store, $renderer=null)
    {
        $values = array(
            'filter'   => false,
            'renderer' => $this->getModelParam('renderer'),
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
        
        return $values;
    }
}