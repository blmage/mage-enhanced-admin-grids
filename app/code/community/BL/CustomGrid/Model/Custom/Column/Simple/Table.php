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

class BL_CustomGrid_Model_Custom_Column_Simple_Table
    extends BL_CustomGrid_Model_Custom_Column_Simple_Abstract
{
    static protected $_tablesAppliedFlags = array();
    
    public function getAppliedFlagKey($alias, $params, $block, $collection, $table)
    {
        return $table;
    }
    
    public function getTableName()
    {
        return $this->getModelParam('table_name');
    }
    
    public function getJoinConditionMainField()
    {
        return $this->getModelParam('join_condition_main_field');
    }
    
    public function getJoinConditionTableField()
    {
        return $this->getModelParam('join_condition_table_field');
    }
    
    public function getTableFieldName()
    {
        return $this->getModelParam('table_field_name');
    }
    
    public function getAdditionalJoinConditions($alias, $params, $block, $collection, $mainAlias, $tableAlias)
    {
        return array();
    }
    
    public function addFieldToGridCollection($alias, $params, $block, $collection)
    {
        $helper    = $this->_getCollectionHelper();
        $table     = $this->getTableName();
        $tableHash = md5($table);
        $flagKey   = $this->getAppliedFlagKey($alias, $params, $block, $collection, $table);
        list($adapter, $qi) = $this->_getCollectionAdapter($collection, true);
        
        if (!isset(self::$_tablesAppliedFlags[$table])) {
            self::$_tablesAppliedFlags[$flagKey] = $this->_getUniqueCollectionFlag('_'.$tableHash);
        }
        $appliedFlag = self::$_tablesAppliedFlags[$flagKey];
        
        if (!$tableAlias = $collection->getFlag($appliedFlag)) {
            $mainAlias  = $helper->getCollectionMainTableAlias($collection);
            $tableAlias = $this->_getUniqueTableAlias('_'.$tableHash);
            
            $collection->getSelect()
                ->joinLeft(
                    array($tableAlias => $collection->getTable($table)),
                    implode(
                        ' AND ',
                        array_merge(
                            array(
                                $qi($tableAlias.'.'.$this->getJoinConditionTableField())
                                    .' = '.$qi($mainAlias.'.'.$this->getJoinConditionMainField()),
                            ),
                            $this->getAdditionalJoinConditions($alias, $params, $block, $collection, $mainAlias, $tableAlias)
                        )
                    ),
                    array()
                );
            
            $collection->setFlag($appliedFlag, $tableAlias);
        }
        
        $field = $this->getTableFieldName();
        $collection->getSelect()->columns(array($alias => $tableAlias.'.'.$field), $tableAlias);
        $helper->addFilterToCollectionMap($collection, $qi($tableAlias.'.'.$field), $alias);
        
        return $this;
    }
}