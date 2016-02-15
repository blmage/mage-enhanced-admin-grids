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
 * @copyright  Copyright (c) 2016 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_Custom_Column_Product_Stats_Quote extends BL_CustomGrid_Model_Custom_Column_Simple_Abstract
{
    const COUNT_MODE_QUOTES = 'quotes';
    const COUNT_MODE_PRODUCTS = 'products';
    
    protected function _prepareConfig()
    {
        $helper = $this->_getBaseHelper();
        
        if (!Mage::app()->isSingleStoreMode()) {
            /** @var $storeSource Mage_Adminhtml_Model_System_Config_Source_Store */
            $storeSource = Mage::getModel('adminhtml/system_config_source_store');
            $stores = $storeSource->toOptionArray();
            
            array_unshift(
                $stores,
                array(
                    'value' => '0',
                    'label' => $helper->__('All')
                )
            );
            
            $this->addCustomizationParam(
                'store_id',
                array(
                    'label'   => $helper->__('Store Views'),
                    'type'    => 'multiselect',
                    'values'  => $stores,
                    'value'   => 0,
                    'size'    => 4,
                ),
                10
            );
        }
        
        $this->addCustomizationParam(
            'include_inactive',
            array(
                'label'        => $helper->__('Include Inactive Carts'),
                'type'         => 'select',
                'source_model' => 'customgrid/system_config_source_yesno',
                'value'        => 0,
            ),
            20
        );
        
        $this->addCustomizationParam(
            'only_logged_in',
            array(
                'label'        => $helper->__('Only Logged In Carts'),
                'type'         => 'select',
                'source_model' => 'customgrid/system_config_source_yesno',
                'value'        => 0,
            ),
            30
        );
        
        $this->addCustomizationParam(
            'exclude_child_items',
            array(
                'label'        => $helper->__('Exclude Child Items'),
                'description'  => $helper->__('Eg : simple products associated to their configurable parents'),
                'type'         => 'select',
                'source_model' => 'customgrid/system_config_source_yesno',
                'value'        => 0,
            ),
            40
        );
        
        $this->setCustomizationWindowConfig(array('height' => 330), true);
        return parent::_prepareConfig();
    }
    
    protected function _getCountSelect(Varien_Data_Collection_Db $collection, array $params, $countMode)
    {
        /** @var $adapter Zend_Db_Adapter_Abstract */
        list($adapter, $qi) = $this->_getCollectionAdapter($collection, true);
        $helper = $this->_getCollectionHelper();
        $mainAlias  = $helper->getCollectionMainTableAlias($collection);
        $quoteAlias = $this->_getUniqueTableAlias('_quote_' . $countMode);
        $itemAlias  = $this->_getUniqueTableAlias('_item_'  . $countMode);
        
        $countExpression = ($countMode == self::COUNT_MODE_PRODUCTS)
            ? 'SUM(' . $qi($itemAlias . '.qty') . ')'
            : 'COUNT(DISTINCT ' . $qi($itemAlias . '.quote_id') . ')';
        
        $countSelect = $adapter->select()
            ->from(
                array($quoteAlias => $collection->getTable('sales/quote')),
                array('count' => new Zend_Db_Expr($countExpression))
            )
            ->joinInner(
                array($itemAlias => $collection->getTable('sales/quote_item')),
                $qi($itemAlias.'.quote_id') . ' = ' . $qi($quoteAlias . '.entity_id'),
                array()
            )
            ->where($qi($itemAlias . '.product_id') . ' = ' . $qi($mainAlias . '.entity_id'))
            ->group($itemAlias . '.product_id');
        
        if (isset($params['store_id'])) {
            if (is_array($params['store_id'])) {
                if (!in_array('0', $params['store_id'], true)) {
                    $countSelect->where($qi($itemAlias . '.store_id') . ' IN (?)', $params['store_id']);
                }
            } elseif ($params['store_id'] !== '0') {
                $countSelect->where($qi($itemAlias . '.store_id') . ' = ?', $params['store_id']);
            }
        }
        if (!$this->_extractBoolParam($params, 'include_inactive')) {
            $countSelect->where($qi($quoteAlias . '.is_active') . ' = 1');
        }
        if ($this->_extractBoolParam($params, 'only_logged_in')) {
            $countSelect->where($qi($quoteAlias . '.customer_id') . ' > 0');
        }
        if ($this->_extractBoolParam($params, 'exclude_child_items')) {
            $countSelect->where($qi($itemAlias . '.parent_item_id') . ' IS NULL');
        }
        
        return $countSelect;
    }
    
    public function addFieldToGridCollection(
        $columnIndex,
        array $params,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection_Db $collection
    ) {
        $countMode  = $this->getConfigParam('count_mode');
        $countQuery = 'IFNULL((' . $this->_getCountSelect($collection, $params, $countMode) . '), 0)';
        $collection->getSelect()->columns(array($columnIndex => new Zend_Db_Expr($countQuery)));
        return $this;
    }
    
    public function addFilterToGridCollection(
        Varien_Data_Collection_Db $collection,
        Mage_Adminhtml_Block_Widget_Grid_Column $columnBlock
    ) {
        $params = $columnBlock->getBlcgFilterParams();
        $fieldName = ($columnBlock->getFilterIndex() ? $columnBlock->getFilterIndex() : $columnBlock->getIndex());
        $condition = $columnBlock->getFilter()->getCondition();
       
        if ($fieldName && $condition && is_array($params)) {
            $adapter    = $this->_getCollectionAdapter($collection);
            $countMode  = $this->getConfigParam('count_mode');
            $countQuery = 'IFNULL((' . $this->_getCountSelect($collection, $params, $countMode) . '), 0)';
            
            if (is_array($condition) && isset($condition['from']) && isset($condition['to'])) {
                $condition = ' BETWEEN '
                    . $adapter->quote($condition['from'])
                    . ' AND '
                    . $adapter->quote($condition['to']);
            } else {
                $condition = $adapter->quoteInto(' = ?', $condition);
            }
            
            $collection->getSelect()->where(new Zend_Db_Expr($countQuery) . $condition);
        }
        
        return $this;
    }
    
    public function getForcedBlockValues(
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        $columnBlockId,
        $columnIndex,
        array $params,
        Mage_Core_Model_Store $store
    ) {
        return array(
            'type' => 'number',
            'blcg_filter_params' => $params,
            'filter_condition_callback' => array($this, 'addFilterToGridCollection'),
        );
    }
}
