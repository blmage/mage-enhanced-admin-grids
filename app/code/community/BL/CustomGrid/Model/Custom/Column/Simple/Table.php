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
 * @copyright  Copyright (c) 2014 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_Custom_Column_Simple_Table extends BL_CustomGrid_Model_Custom_Column_Simple_Abstract
{
    static protected $_tablesAppliedFlags = array();
    
    public function getAppliedFlagKey(
        $columnIndex,
        array $params,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection_Db $collection,
        $tableName
    ) {
        return $tableName;
    }
    
    public function getTableName()
    {
        return $this->getConfigParam('table_name');
    }
    
    public function getTableHash($tableName)
    {
        return md5($tableName);
    }
    
    public function getJoinConditionMainFieldName()
    {
        return $this->getConfigParam('join_condition_main_field_name');
    }
    
    public function getJoinConditionTableFieldName()
    {
        return $this->getConfigParam('join_condition_table_field_name');
    }
    
    public function getTableFieldName()
    {
        return $this->getConfigParam('table_field_name');
    }
    
    protected function _getAdditionalJoinConditions(
        $columnIndex,
        array $params,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection_Db $collection,
        $mainAlias,
        $tableAlias
    ) {
        return array();
    }
    
    protected function _addFieldToSelect(
        Varien_Db_Select $select,
        $columnIndex,
        $tableAlias,
        array $params,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection_Db $collection
    ) {
        $helper = $this->_getCollectionHelper();
        list($adapter, $qi) = $this->_getCollectionAdapter($collection, true);
        $fieldName = $this->getTableFieldName();
        $select->columns(array($columnIndex => $tableAlias . '.' . $fieldName), $tableAlias);
        $helper->addFilterToCollectionMap($collection, $qi($tableAlias . '.' . $fieldName), $columnIndex);
        return $this;
    }
    
    public function addFieldToGridCollection(
        $columnIndex,
        array $params,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection_Db $collection
    ) {
        $helper    = $this->_getCollectionHelper();
        $mainAlias = $helper->getCollectionMainTableAlias($collection);
        $tableName = $this->getTableName();
        $tableHash = $this->getTableHash($tableName);
        $flagKey   = $this->getAppliedFlagKey($columnIndex, $params, $gridBlock, $collection, $tableName);
        list($adapter, $qi) = $this->_getCollectionAdapter($collection, true);
        
        if (!isset(self::$_tablesAppliedFlags[$tableName])) {
            self::$_tablesAppliedFlags[$flagKey] = $this->_getUniqueCollectionFlag('_' . $tableHash);
        }
        
        $appliedFlag = self::$_tablesAppliedFlags[$flagKey];
        $select = $collection->getSelect();
        
        if (!$tableAlias = $collection->getFlag($appliedFlag)) {
            $tableAlias = $this->_getUniqueTableAlias('_' . $tableHash);
            $mainFieldName  = $this->getJoinConditionMainFieldName();
            $tableFieldName = $this->getJoinConditionTableFieldName();
            
            $joinConditions = array_merge(
                array($qi($tableAlias . '.' . $tableFieldName) . ' = ' . $qi($mainAlias . '.' . $mainFieldName)),
                $this->_getAdditionalJoinConditions(
                    $columnIndex,
                    $params,
                    $gridBlock,
                    $collection,
                    $mainAlias,
                    $tableAlias
                )
            );
            
            $select->joinLeft(
                array($tableAlias => $collection->getTable($tableName)),
                implode(' AND ', $joinConditions),
                array()
            );
            
            $collection->setFlag($appliedFlag, $tableAlias);
        }
        
        return $this->_addFieldToSelect($select, $columnIndex, $tableAlias, $params, $gridBlock, $collection);
    }
    
    public function getForcedBlockValues(
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        $columnBlockId,
        $columnIndex,
        array $params,
        Mage_Core_Model_Store $store
    ) {
        $collection = $gridBlock->getCollection();
        $tableName  = $this->getTableName();
        $flagKey = $this->getAppliedFlagKey($columnIndex, $params, $gridBlock, $collection, $tableName);
        $values  = array();
        
        if (isset(self::$_tablesAppliedFlags[$flagKey])
            && ($tableAlias = $collection->getFlag(self::$_tablesAppliedFlags[$flagKey]))) {
            $values['blcg_table_alias'] = $tableAlias;
        }
        
        return $values;
    }
}