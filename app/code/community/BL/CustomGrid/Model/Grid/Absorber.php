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
 * @copyright  Copyright (c) 2015 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_Grid_Absorber extends BL_CustomGrid_Model_Grid_Worker_Abstract
{
    public function getType()
    {
        return BL_CustomGrid_Model_Grid::WORKER_TYPE_ABSORBER;
    }
    
    /**
     * Initialize the current grid model's variable names from the given block
     *
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return BL_CustomGrid_Model_Grid_Absorber
     */
    protected function _absorbGridBlockVarNames(Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        $gridModel = $this->getGridModel();
        
        foreach ($gridModel->getBlockVarNameKeys() as $key) {
            $gridModel->setData('var_name_' . $key, $gridBlock->getDataUsingMethod('var_name' . $key));
        }
        
        return $this;
    }
    
    /**
     * Return the column block IDs and column indices used by the grid columns from the current grid model
     * 
     * @return array
     */
    protected function _getGridColumnsMainValues()
    {
        $gridModel = $this->getGridModel();
        $gridColumnBlockIds = $gridModel->getColumnBlockIdsByOrigin(BL_CustomGrid_Model_Grid_Column::ORIGIN_GRID);
        $gridColumnIndices  = array();
        $columns = $gridModel->getColumns();
        
        foreach ($gridColumnBlockIds as $columnBlockId) {
            if (isset($columns[$columnBlockId])) {
                $gridColumnIndices[] = $columns[$columnBlockId]->getIndex();
            }
        }
        
        return array($gridColumnBlockIds, $gridColumnIndices);
    }
    
    /**
     * Check the given column alignment value and return it if it is valid, otherwise return "left"
     *
     * @param string $alignment Alignment value to check
     * @return string
     */
    protected function _getValidColumnAlignment($alignment)
    {
        /** @var $columnModel BL_CustomGrid_Model_Grid_Column */
        $columnModel = Mage::getSingleton('customgrid/grid_column');
        return array_key_exists($alignment, $columnModel->getAlignments())
            ? $alignment
            : BL_CustomGrid_Model_Grid_Column::ALIGNMENT_LEFT;
    }
    
    /**
     * Add a column to the list corresponding to the given column block
     *
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $columnBlock Column block
     * @param int $order Column order
     * @return BL_CustomGrid_Model_Grid_Absorber
     */
    protected function _absorbColumnFromBlock(Mage_Adminhtml_Block_Widget_Grid_Column $columnBlock, $order)
    {
        return $this->getGridModel()
            ->addColumn(
                array(
                    'block_id'             => $columnBlock->getId(),
                    'index'                => $columnBlock->getIndex(),
                    'width'                => $columnBlock->getWidth(),
                    'align'                => $this->_getValidColumnAlignment($columnBlock->getAlign()),
                    'header'               => $columnBlock->getHeader(),
                    'order'                => $order,
                    'origin'               => BL_CustomGrid_Model_Grid_Column::ORIGIN_GRID,
                    'is_visible'           => true,
                    'is_only_filterable'   => false,
                    'is_system'            => (bool) $columnBlock->getIsSystem(),
                    'is_missing'           => false,
                    'store_id'             => null,
                    'renderer_type'        => null,
                    'renderer_params'      => null,
                    'is_edit_allowed'      => true,
                    'customization_params' => null,
                )
            );
    }
    
    /**
     * Add a collection column to the current grid model, corresponding to the given collection row value's index
     *
     * @param string $index Collection row value's index
     * @param int $order Column order
     * @return BL_CustomGrid_Model_Grid_Absorber
     */
    protected function _absorbColumnFromCollection($index, $order)
    {
        return $this->getGridModel()
            ->addColumn(
                array(
                    'block_id'             => $index,
                    'index'                => $index,
                    'width'                => '',
                    'align'                => BL_CustomGrid_Model_Grid_Column::ALIGNMENT_LEFT,
                    'header'               => $this->getGridModel()->getHelper()->getColumnHeaderName($index),
                    'order'                => $order,
                    'origin'               => BL_CustomGrid_Model_Grid_Column::ORIGIN_COLLECTION,
                    'is_visible'           => false,
                    'is_only_filterable'   => false,
                    'is_system'            => false,
                    'is_missing'           => false,
                    'store_id'             => null,
                    'renderer_type'        => null,
                    'renderer_params'      => null,
                    'is_edit_allowed'      => true,
                    'customization_params' => null,
                )
            );
    }
    
    /**
     * Absorb columns from the given grid collection
     * 
     * @param Varien_Data_Collection_Db $gridCollection Grid collection
     * @param int $order Starting order
     * @param int $orderPitch Order pitch
     * @return BL_CustomGrid_Model_Grid_Absorber
     */
    protected function _absorbGridCollectionColumns(Varien_Data_Collection_Db $gridCollection, $order, $orderPitch)
    {
        if ($gridCollection->count() > 0) {
            $item = $gridCollection->getFirstItem();
            list($gridColumnBlockIds, $gridColumnIndices) = $this->_getGridColumnsMainValues();
        
            foreach ($item->getData() as $key => $value) {
                if ((is_scalar($value) || is_null($value))
                    && !in_array($key, $gridColumnIndices, true)
                    && !in_array($key, $gridColumnBlockIds, true)) {
                    $this->_absorbColumnFromCollection($key, ++$order * $orderPitch);
                }
            }
        }
        return $this;
    }
    
    /**
     * Initialize the current grid model values from the given grid block, and save it afterwards
     *
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return BL_CustomGrid_Model_Grid_Absorber
     */
    public function initGridModelFromGridBlock(Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        /** @var $gridHelper BL_CustomGrid_Helper_Grid */
        $gridHelper = Mage::helper('customgrid/grid');
        $gridModel  = $this->getGridModel();
        
        // Reset / Initialization
        $gridModel->setBlockId($gridBlock->getId());
        $gridModel->setHasVaryingBlockId($gridHelper->isVaryingGridBlockId($gridBlock->getId()));
        $gridModel->setBlockType($gridBlock->getType());
        $gridModel->resetColumnsValues();
        $this->_absorbGridBlockVarNames($gridBlock);
        
        $order = 0;
        $orderPitch  = $gridModel->getColumnsOrderPitch();
        
        foreach ($gridBlock->getColumns() as $columnBlock) {
            $this->_absorbColumnFromBlock($columnBlock, ++$order * $orderPitch);
        }
    
        if ($collection = $gridBlock->getCollection()) {
            $this->_absorbGridCollectionColumns($collection, $order, $orderPitch);
        }
        
        $gridModel->setDataChanges(true)->save();
        return $this;
    }
    
    /**
     * Find the missing columns for the given origin by comparing the columns from the current grid model
     * to the given array of column block IDs, and mark them as such
     * 
     * @param string $origin Columns origin
     * @param string[] $foundBlockIds Found column block IDs
     * @return BL_CustomGrid_Model_Grid_Absorber
     */
    protected function _checkMissingOriginColumns($origin, array $foundBlockIds)
    {
        $gridModel = $this->getGridModel();
        $columns   = $gridModel->getColumns();
        $columnBlockIds = $gridModel->getColumnBlockIdsByOrigin($origin);
        
        foreach ($columnBlockIds as $columnBlockId) {
            if (isset($columns[$columnBlockId]) && !in_array($columnBlockId, $foundBlockIds)) {
                $columns[$columnBlockId]->setIsMissing(true);
            }
        }
        
        return $this;
    }
    
    /**
     * Check the grid columns from the current grid model against the given grid block
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return BL_CustomGrid_Model_Grid_Absorber
     */
    protected function _checkGridColumnsAgainstGridBlock(Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        $gridModel = $this->getGridModel();
        $gridIndices = array();
        $foundBlockIds = array();
        
        foreach ($gridBlock->getColumns() as $columnBlock) {
            $columnBlockId = $columnBlock->getId();
            
            if ($gridModel->getColumnByBlockId($columnBlockId)) {
                $gridModel->updateColumn(
                    $columnBlockId,
                    array(
                        'block_id'   => $columnBlockId,
                        'index'      => $columnBlock->getIndex(),
                        'origin'     => BL_CustomGrid_Model_Grid_Column::ORIGIN_GRID,
                        'is_system'  => (bool) $columnBlock->getIsSystem(),
                        'is_missing' => false,
                    )
                );
            } else {
                $this->_absorbColumnFromBlock($columnBlock, $gridModel->getNextColumnOrder());
            }
            
            $gridIndices[] = $columnBlock->getIndex();
            $foundBlockIds[] = $columnBlock->getId();
        }
        
        $this->_checkMissingOriginColumns(BL_CustomGrid_Model_Grid_Column::ORIGIN_GRID, $foundBlockIds);
        return $this;
    }
    
    /**
     * Check the collection columns from the current grid model against the given grid block
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return bool Whether the collection columns have been checked
     */
    protected function _checkCollectionColumnsAgainstGridBlock(Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        $gridModel = $this->getGridModel();
        $checkedCollection  = false;
        $foundBlockIds = array();
        
        if (($collection = $gridBlock->getCollection()) && ($collection->count() > 0)) {
            $checkedCollection = true;
            $item = $collection->getFirstItem();
            list($gridColumnBlockIds, $gridColumnIndices) = $this->_getGridColumnsMainValues();
            
            foreach ($item->getData() as $key => $value) {
                if (is_scalar($value) || is_null($value)) {
                    if (!in_array($key, $gridColumnBlockIds, true) && !in_array($key, $gridColumnIndices, true)) {
                        if ($gridModel->getColumnByBlockId($key)) {
                            $gridModel->updateColumn(
                                $key,
                                array(
                                    'block_id'   => $key,
                                    'index'      => $key,
                                    'origin'     => BL_CustomGrid_Model_Grid_Column::ORIGIN_COLLECTION,
                                    'is_system'  => false,
                                    'is_missing' => false,
                                )
                            );
                        } else {
                            $this->_absorbColumnFromCollection($key, $gridModel->getNextColumnOrder());
                        }
                        
                        $foundBlockIds[] = $key;
                    }
                }
            }
            
            $this->_checkMissingOriginColumns(BL_CustomGrid_Model_Grid_Column::ORIGIN_COLLECTION, $foundBlockIds);
        }
        
        return $checkedCollection;
    }
    
    /**
     * Check the validity of each attribute column from the current grid model
     * 
     * @return BL_CustomGrid_Model_Grid_Absorber
     */
    protected function _checkAttributeColumnsValidity()
    {
        $gridModel = $this->getGridModel();
        $foundBlockIds = array();
        
        if ($gridModel->canHaveAttributeColumns()) {
            $columnBlockIds = $gridModel->getColumnBlockIdsByOrigin(BL_CustomGrid_Model_Grid_Column::ORIGIN_ATTRIBUTE);
            $attributeCodes = $gridModel->getAvailableAttributesCodes();
            $columns = $gridModel->getColumns();
            
            foreach ($columnBlockIds as $columnBlockId) {
                if (isset($columns[$columnBlockId])
                    && in_array($columns[$columnBlockId]->getIndex(), $attributeCodes, true)) {
                    $columns[$columnBlockId]->setIsMissing(false);
                    $foundBlockIds[] = $columnBlockId;
                }
            }
        }
        
        $this->_checkMissingOriginColumns(BL_CustomGrid_Model_Grid_Column::ORIGIN_ATTRIBUTE, $foundBlockIds);
        return $this;
    }
    
    /**
     * Check the validity of each custom column from the current grid model
     * 
     * @return BL_CustomGrid_Model_Grid_Absorber
     */
    protected function _checkCustomColumnsValidity()
    {
        $gridModel = $this->getGridModel();
        $foundBlockIds = array();
        
        if ($gridModel->canHaveCustomColumns()) {
            $columnBlockIds = $gridModel->getColumnBlockIdsByOrigin(BL_CustomGrid_Model_Grid_Column::ORIGIN_CUSTOM);
            $customColumnCodes = $gridModel->getAvailableCustomColumnsCodes(true);
            $columns = $gridModel->getColumns();
            
            foreach ($columnBlockIds as $columnBlockId) {
                if (isset($columns[$columnBlockId])
                    && in_array($columns[$columnBlockId]->getIndex(), $customColumnCodes, true)) {
                    $columns[$columnBlockId]->setIsMissing(false);
                    $foundBlockIds[] = $columnBlockId;
                }
            }
        }
        
        $this->_checkMissingOriginColumns(BL_CustomGrid_Model_Grid_Column::ORIGIN_CUSTOM, $foundBlockIds);
        return $this;
    }
    
    /**
     * Check the current grid model values (columns, etc.) against the given grid block, update what is necessary
     * and save the grid model afterwards
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return bool Whether collection columns have been checked (if false, using them is unsafe)
     */
    public function checkGridModelAgainstGridBlock(Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        $this->_absorbGridBlockVarNames($gridBlock);
        $this->_checkGridColumnsAgainstGridBlock($gridBlock);
        $checkedCollection = $this->_checkCollectionColumnsAgainstGridBlock($gridBlock);
        $this->_checkAttributeColumnsValidity();
        $this->_checkCustomColumnsValidity();
        $this->getGridModel()->setDataChanges(true)->save();
        return $checkedCollection;
    }
}
