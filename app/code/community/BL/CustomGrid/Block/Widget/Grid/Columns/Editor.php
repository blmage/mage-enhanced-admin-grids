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

class BL_CustomGrid_Block_Widget_Grid_Columns_Editor extends Mage_Adminhtml_Block_Template
{
    protected function _construct()
    {
        parent::_construct();
        $this->setId($this->getCoreHelper()->uniqHash('blcgEditor'));
        $this->setTemplate('bl/customgrid/widget/grid/columns/editor.phtml');
    }
    
    protected function _toHtml()
    {
        /** @var $helper BL_CustomGrid_Helper_Data */
        $helper = $this->helper('customgrid');
        
        if (!$this->getIsNewGridModel()
            && ($gridModel = $this->getGridModel())
            && $gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_COLUMNS_VALUES)
            && ($gridBlock = $this->getGridBlock())
            && $helper->isRewritedGridBlock($gridBlock)) {
            return parent::_toHtml();
        }
        
        return '';
    }
    
    /**
     * Return the core helper
     * 
     * @return Mage_Core_Helper_Data
     */
    public function getCoreHelper()
    {
        return $this->helper('core');
    }
    
    /**
     * Return the current grid model
     * 
     * @return BL_CustomGrid_Model_Grid
     */
    public function getGridModel()
    {
        if (!$this->hasData('grid_model')) {
            return $this->setData('grid_model', Mage::registry('blcg_grid'));
        }
        return $this->_getData('grid_model');
    }
    
    /**
     * Return the current grid block
     * 
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    public function getGridBlock()
    {
        return ($gridBlock = $this->_getData('grid_block'))
            && ($gridBlock instanceof Mage_Adminhtml_Block_Widget_Grid)
            ? $gridBlock
            : null;
    }
    
    /**
     * Return the name of the editor JS object
     * 
     * @return string
     */
    public function getJsObjectName()
    {
        return $this->getId();
    }
    
    /**
     * Return the HTML ID of the grid table
     * 
     * @return string
     */
    public function getGridTableId()
    {
        return $this->getGridBlock()->getId() . '_table';
    }
    
    /**
     * Return the JSON config for the current grid rows
     * 
     * @return string
     */
    public function getRowsJsonConfig()
    {
        $config = array();
        $gridBlock = $this->getGridBlock();
        $gridModel = $this->getGridModel();
        $blockType = $gridModel->getBlockType();
        $entityLoader = $gridModel->getTypeModel()
            ->getEditor()
            ->getEntityLoader();
        
        if ($gridCollection = $gridBlock->getCollection()) {
            foreach ($gridCollection as $row) {
                $config[] = $entityLoader->getEntityRowIdentifiers($blockType, $row);
                
                // Avoid taking non-consistent rows
                if ($multipleRows = $gridBlock->getMultipleRows($row)) {
                    $rowsCount = count($multipleRows);
                    
                    for ($rowIndex = 0; $rowIndex < $rowsCount; $rowIndex++) {
                        $config[] = false;
                    }
                }
                
                if ($gridBlock->shouldRenderSubTotal($row)) {
                    $config[] = false;
                }
            }
        }
        
        return $this->getCoreHelper()->jsonEncode($config);
    }
    
    /**
     * Return the sorted columns from the current grid block
     * 
     * @return Mage_Adminhtml_Block_Widget_Grid_Column[]
     */
    protected function _getGridBlockSortedColumns()
    {
        $columns = $this->getGridBlock()->getColumns();
        
        foreach ($columns as $key => $column) {
            if ($column->getBlcgFilterOnly()) {
                unset($columns[$key]);
            }
        }
        
        return $columns;
    }
    
    /**
     * Return an array with the editable columns from the customized columns list of the current grid model,
     * by column block ID (using false for non-editable columns)
     * 
     * @return (BL_CustomGrid_Model_Grid_Column|false)[]
     */
    protected function _getEditableCustomizedColumns()
    {
        $columns = $this->getGridModel()->getSortedColumns(true, false, true, true, true, true);
        
        foreach ($columns as $columnBlockId => $column) {
            if ($column->isOnlyFilterable()) {
                unset($columns[$columnBlockId]);
            } elseif (!$column->isEditable() || !$column->getIsEditAllowed()) {
                $columns[$columnBlockId] = false;
            }
        }
        
        return $columns;
    }
    
    /**
     * Return an array with the editable columns from the original columns list of the current grid block,
     * by column block ID (using false for non-editable columns)
     * 
     * @return (BL_CustomGrid_Model_Grid_Column|false)[]
     */
    protected function _getEditableDefaultColumns()
    {
        $blockColumns = $this->_getGridBlockSortedColumns();
        $modelColumns = $this->getGridModel()->getColumns(true);
        $columns = array();
        
        foreach (array_keys($blockColumns) as $columnBlockId) {
            if (isset($modelColumns[$columnBlockId]) && $modelColumns[$columnBlockId]->isGrid()) {
                if ($modelColumns[$columnBlockId]->isOnlyFilterable()) {
                    continue;
                }
                $columns[$columnBlockId] = $modelColumns[$columnBlockId];
            } else {
                $columns[$columnBlockId] = false;
            }
        }
        
        return $columns;
    }
    
    /**
     * Return the JSON config for the editable columns
     * 
     * @return string
     */
    public function getEditableColumnsJsonConfig()
    {
        $gridModel = $this->getGridModel();
        $gridBlock = $this->getGridBlock();
        $editor = $gridModel->getTypeModel()->getEditor();
        $config = array();
        
        if ($gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid_Sentry::ACTION_USE_CUSTOMIZED_COLUMNS)) {
            $columns = $this->_getEditableCustomizedColumns();
        } else {
            $columns = $this->_getEditableDefaultColumns();
        }
        if ($editor->getSentry()->checkBaseUserEditPermissions($gridModel, $gridBlock)) {
            foreach ($columns as $column) {
                if ($column !== false) {
                    /** @var $valueConfig BL_CustomGrid_Model_Grid_Editor_Value_Config */
                    $valueConfig = $column->getEditorConfig();
                    $config[] = $valueConfig->getEditorJsData();
                } else {
                    $config[] = false;
                }
            }
        } else {
            $config = array_fill(0, count($columns), false);
        }
        
        return $this->getCoreHelper()->jsonEncode($config);
    }
    
    /**
     * Return the global edit parameters as JSON
     * 
     * @return string
     */
    public function getGlobalParamsJson()
    {
        return $this->getCoreHelper()
            ->jsonEncode(
                array(
                    'grid_id' => $this->getGridModel()->getId(),
                    'profile_id' => $this->getGridModel()->getProfileId(),
                    'editor_js_object_name' => $this->getJsObjectName(),
                )
            );
    }
    
    /**
     * Return the additional edit parameters as JSON
     * 
     * @return string
     */
    public function getAdditionalParamsJson()
    {
        return $this->getCoreHelper()
            ->jsonEncode(
                $this->getGridModel()
                    ->getTypeModel()
                    ->getEditor()
                    ->getAdditionalEditParams($this->getGridModel()->getBlockType(), $this->getGridBlock())
            );
    }
}
