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

abstract class BL_CustomGrid_Model_Custom_Column_Simple_Abstract extends BL_CustomGrid_Model_Custom_Column_Abstract
{
    abstract public function addFieldToGridCollection(
        $columnIndex,
        array $params,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection_Db $collection
    );
    
    protected function _shouldForceFieldOrder(
        Varien_Data_Collection_Db $collection,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        $columnBlockId,
        $columnIndex,
        array $params
    ) {
        return ($gridBlock->blcg_getSort(false) === $columnBlockId)
            && $this->_getGridHelper()->isEavEntityGrid($gridBlock, $gridModel);
    }
    
    public function addSortToGridCollection(
        $columnBlockId,
        $columnIndex,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection_Db $collection
    ) {
        $collection->getSelect()->order(new Zend_Db_Expr($columnIndex . ' ' . $gridBlock->blcg_getDir()));
        return $this;
    }
    
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
            self::GC_EVENT_AFTER_SET,
            array($this, 'addFieldToGridCollection'),
            array($columnIndex, $params),
            true
        );
        
        if ($this->_shouldForceFieldOrder($collection, $gridBlock, $gridModel, $columnBlockId, $columnIndex, $params)) {
            $gridBlock->blcg_addCollectionCallback(
                self::GC_EVENT_AFTER_SET,
                array($this, 'addSortToGridCollection'),
                array($columnBlockId, $columnIndex),
                true
            );
        }
        
        return $this;
    }
    
    protected function _addFieldToSelect(
        Varien_Db_Select $select,
        $columnIndex,
        $fieldName,
        $tableAlias,
        array $params,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection_Db $collection
    ) {
        $helper = $this->_getCollectionHelper();
        list(, $qi) = $this->_getCollectionAdapter($collection, true);
        $select->columns(array($columnIndex => $tableAlias . '.' . $fieldName), $tableAlias);
        $helper->addFilterToCollectionMap($collection, $qi($tableAlias . '.' . $fieldName), $columnIndex);
        return $this;
    }
}
