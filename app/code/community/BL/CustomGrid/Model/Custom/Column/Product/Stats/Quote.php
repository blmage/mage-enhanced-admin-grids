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

class BL_CustomGrid_Model_Custom_Column_Product_Stats_Quote
    extends BL_CustomGrid_Model_Custom_Column_Simple_Abstract
{
    public function initConfig()
    {
        parent::initConfig();
        $helper = Mage::helper('customgrid');
        
        if (!Mage::app()->isSingleStoreMode()) {
            $stores = Mage::getModel('adminhtml/system_config_source_store')
                ->toOptionArray();
            array_unshift($stores, array('value' => '0', 'label' => $helper->__('All')));
            
            $this->addCustomParam('store_id', array(
                'label'   => $helper->__('Store Views'),
                'type'    => 'multiselect',
                'values'  => $stores,
                'value'   => 0,
                'size'    => 4,
            ), 10);
        }
        
        $this->addCustomParam('include_inactive', array(
            'label'        => $helper->__('Include Inactive Carts'),
            'type'         => 'select',
            'source_model' => 'adminhtml/system_config_source_yesno',
            'value'        => 0,
        ), 20);
        
        $this->addCustomParam('only_logged_in', array(
            'label'        => $helper->__('Only Logged In Carts'),
            'type'         => 'select',
            'source_model' => 'adminhtml/system_config_source_yesno',
            'value'        => 0,
        ), 30);
        
        $this->addCustomParam('exclude_child_items', array(
            'label'        => $helper->__('Exclude Child Items'),
            'description'  => $helper->__('Eg : simple products associated to their configurable parents'),
            'type'         => 'select',
            'source_model' => 'adminhtml/system_config_source_yesno',
            'value'        => 0,
        ), 40);
        
        $this->setCustomParamsWindowConfig(array('height' => 330));
        
        return $this;
    }
    
    protected function _getCountSelect($collection, $params, $mode)
    {
        $mode    = ($mode == 'products' ? 'products' : 'quotes');
        $helper  = $this->_getCollectionHelper();
        list($adapter, $qi) = $this->_getCollectionAdapter($collection, true);
        $mainAlias  = $helper->getCollectionMainTableAlias($collection);
        $quoteAlias = $this->_getUniqueTableAlias('_quote_'.$mode);
        $itemAlias  = $this->_getUniqueTableAlias('_item_'.$mode);
        $countField = ($mode == 'products' ? 'SUM('.$qi($itemAlias.'.qty').')' : 'COUNT(DISTINCT '.$qi($itemAlias.'.quote_id').')');
        
        $countSelect = $adapter->select()
            ->from(
                array($quoteAlias => $collection->getTable('sales/quote')),
                array('count' => new Zend_Db_Expr($countField))
            )
            ->joinInner(
                array($itemAlias => $collection->getTable('sales/quote_item')),
                $qi($itemAlias.'.quote_id').' = '.$qi($quoteAlias.'.entity_id'),
                array()
            )
            ->where($qi($itemAlias.'.product_id').' = '.$qi($mainAlias.'.entity_id'))
            ->group($itemAlias.'.product_id');
        
        if (isset($params['store_id'])) {
            if (is_array($params['store_id'])) {
                if (!in_array('0', $params['store_id'], true)) {
                    $countSelect->where($qi($itemAlias.'.store_id').' IN (?)', $params['store_id']);
                }
            } elseif ($params['store_id'] !== '0') {
                $countSelect->where($qi($itemAlias.'.store_id').' = ?', $params['store_id']);
            }
        }
        if (!$this->_extractBoolParam($params, 'include_inactive')) {
            $countSelect->where($qi($quoteAlias.'.is_active').' = 1');
        }
        if ($this->_extractBoolParam($params, 'only_logged_in')) {
            $countSelect->where($qi($quoteAlias.'.customer_id').' > 0');
        }
        if ($this->_extractBoolParam($params, 'exclude_child_items')) {
            $countSelect->where($qi($itemAlias.'.parent_item_id').' IS NULL');
        }
        
        return $countSelect;
    }
    
    public function addFieldToGridCollection($alias, $params, $block, $collection)
    {
        $countQuery = 'IFNULL(('.$this->_getCountSelect($collection, $params, $this->getModelParam('mode')).'), 0)';
        $collection->getSelect()->columns(array($alias => new Zend_Db_Expr($countQuery)));
        return $this;
    }
    
    public function addFilterToGridCollection($collection, $column)
    {
        $field  = ($column->getFilterIndex() ? $column->getFilterIndex() : $column->getIndex());
        $cond   = $column->getFilter()->getCondition();
        $params = $column->getBlcgFilterParams();
        
        if ($field && $cond && is_array($params)) {
            $adapter    = $collection->getSelect()->getAdapter();
            $countQuery = 'IFNULL(('.$this->_getCountSelect($collection, $params, $this->getModelParam('mode')).'), 0)';
            
            if (is_array($cond) && isset($cond['from']) && isset($cond['to'])) {
                $condition = ' BETWEEN '.$adapter->quoteInto('?', $cond['from']).' AND '.$adapter->quoteInto('?', $cond['to']);
            } else {
                $condition = $adapter->quoteInto(' = ?', $cond);
            }
            
            $collection->getSelect()->where(new Zend_Db_Expr($countQuery).$condition);
        }
        
        return $this;
    }
    
    protected function _getForcedGridValues($block, $model, $id, $alias, $params, $store, $renderer=null)
    {
        return array(
            'type' => 'number',
            'blcg_filter_params' => $params,
            'filter_condition_callback' => array($this, 'addFilterToGridCollection'),
        );
    }
}