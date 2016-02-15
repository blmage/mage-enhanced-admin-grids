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

class BL_CustomGrid_Model_Custom_Column_Simple_Table extends BL_CustomGrid_Model_Custom_Column_Simple_Abstract
{
    /**
     * Names of the collection flags corresponding to the aliases of the different joined tables,
     * arranged by flag key
     * (a flag key depending on the table name and possibly various other parameters from the current context)
     * 
     * @var array
     */
    static protected $_tablesAppliedFlags = array();
    
    /**
     * Return the name of the joined table
     * 
     * @return string
     */
    public function getTableName()
    {
        return $this->getConfigParam('table_name');
    }
    
    /**
     * Return a hash from the given table name
     * 
     * @param string $tableName Table name
     * @return string
     */
    public function getTableHash($tableName)
    {
        return md5($tableName);
    }
    
    /**
     * Return the name of the field from the main table, that will be used for the tables join
     * 
     * @return string
     */
    public function getJoinConditionMainFieldName()
    {
        return $this->getConfigParam('join_condition_main_field_name');
    }
    
    /**
     * Return the name of the field from the joined table, that will be used for the tables join
     * 
     * @return string
     */
    public function getJoinConditionTableFieldName()
    {
        return $this->getConfigParam('join_condition_table_field_name');
    }
    
    /**
     * Return the name of the displayable field from the joined table
     * 
     * @return string
     */
    public function getTableFieldName()
    {
        return $this->getConfigParam('table_field_name');
    }
    
    /**
     * Return the additional join conditions to use, between the main table and the joined table
     * 
     * @param string $columnIndex Grid column index
     * @param array $params Customization params values
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param mixed $mainAlias Main table alias
     * @param mixed $tableAlias Joined table alias
     * @return array
     */
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
    
    /**
     * Return the flag key usable to arrange the generated alias for the given table name, in the given context
     * 
     * @param string $columnIndex Grid column index
     * @param array $params Customization params values
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param string $tableName Table name
     * @return string
     */
    protected function _getAppliedFlagKey(
        $columnIndex,
        array $params,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection_Db $collection,
        $tableName
    ) {
        return $tableName;
    }
    
    /**
     * Return the alias used for the joined table on the given grid collection (and join it first if necessary)
     * 
     * @param string $columnIndex Grid column index
     * @param array $params Customization params values
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @return string
     */
    protected function _getJoinedTableAlias(
        $columnIndex,
        array $params,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection_Db $collection
    ) {
        $tableName  = $this->getTableName();
        $tableHash  = $this->getTableHash($tableName);
        $flagKey    = $this->_getAppliedFlagKey($columnIndex, $params, $gridBlock, $collection, $tableName);
        list(, $qi) = $this->_getCollectionAdapter($collection, true);
        
        if (!isset(self::$_tablesAppliedFlags[$flagKey])) {
            self::$_tablesAppliedFlags[$flagKey] = $this->_getUniqueCollectionFlag('_' . $tableHash);
        }
        
        $appliedFlag = self::$_tablesAppliedFlags[$flagKey];
        
        if (!$tableAlias = $collection->getFlag($appliedFlag)) {
            $select = $collection->getSelect();
            $mainAlias  = $this->_getCollectionHelper()->getCollectionMainTableAlias($collection);
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
        
        return $tableAlias;
    }
    
    public function addFieldToGridCollection(
        $columnIndex,
        array $params,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection_Db $collection
    ) {
        return $this->_addFieldToSelect(
            $collection->getSelect(),
            $columnIndex,
            $this->getTableFieldName(),
            $this->_getJoinedTableAlias($columnIndex, $params, $gridBlock, $collection),
            $params,
            $gridBlock,
            $collection
        );
    }
}
