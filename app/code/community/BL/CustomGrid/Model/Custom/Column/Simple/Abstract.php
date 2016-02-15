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

abstract class BL_CustomGrid_Model_Custom_Column_Simple_Abstract extends BL_CustomGrid_Model_Custom_Column_Abstract
{
    /**
     * Add the column base field to the given grid collection
     * 
     * @param string $columnIndex Grid column index
     * @param array $params Customization params values
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @return BL_CustomGrid_Model_Custom_Column_Simple_Abstract
     */
    abstract public function addFieldToGridCollection(
        $columnIndex,
        array $params,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection_Db $collection
    );
    
    /**
     * Return whether a sort on the given column from the given grid block should be enforced
     * 
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param string $columnBlockId Grid column block ID
     * @param string $columnIndex Grid column index
     * @param array $params Customization params values
     * @return bool
     */
    protected function _shouldForceFieldSort(
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
    
    /**
     * Enforce the sort on the given column for the given grid block
     * 
     * @param string $columnBlockId Grid column block ID
     * @param string $columnIndex Grid column index
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @return BL_CustomGrid_Model_Custom_Column_Simple_Abstract
     */
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
        
        if ($this->_shouldForceFieldSort($collection, $gridBlock, $gridModel, $columnBlockId, $columnIndex, $params)) {
            $gridBlock->blcg_addCollectionCallback(
                self::GC_EVENT_AFTER_SET,
                array($this, 'addSortToGridCollection'),
                array($columnBlockId, $columnIndex),
                true
            );
        }
        
        return $this;
    }
    
    /**
     * Add the given field to the given collection
     * 
     * @param Varien_Db_Select $select Grid collection select
     * @param string $columnIndex Grid column index
     * @param string $fieldName Field name
     * @param string $tableAlias Field table alias
     * @param array $params Customization params
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @return BL_CustomGrid_Model_Custom_Column_Simple_Abstract
     */
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
