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

class BL_CustomGrid_Block_Widget_Grid_Columns_Editor
    extends Mage_Adminhtml_Block_Template
{
    protected function _construct()
    {
        parent::_construct();
        $this->setId($this->helper('core')->uniqHash('blcgEditor'));
        $this->setTemplate('bl/customgrid/widget/grid/columns/editor.phtml');
    }
    
    protected function _toHtml()
    {
        if (!$this->getIsNewGridModel()
            && ($gridModel = $this->getGridModel())
            && $gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid::ACTION_EDIT_COLUMNS_VALUES)
            && ($gridBlock = $this->getGridBlock())
            && $this->helper('customgrid')->isRewritedGridBlock($gridBlock)) {
            return parent::_toHtml();
        }
        return '';
    }
    
    public function getJsObjectName()
    {
        return $this->getId();
    }
    
    public function getGridTableId()
    {
        return $this->getGridBlock()->getId() . '_table';
    }
    
    public function getRowsJsonConfig()
    {
        $config    = array();
        $gridBlock = $this->getGridBlock();
        $gridModel = $this->getGridModel();
        
        if ($gridBlock->getCollection()) {
            foreach ($gridBlock->getCollection() as $row) {
                $config[] = $gridModel->getCollectionRowIdentifiers($row);
                
                // Avoid taking non-consistent rows
                if ($multipleRows = $gridBlock->getMultipleRows($row)) {
                     foreach ($multipleRows as $multiple) {
                         $config[] = false;
                     }
                 }
                 if ($gridBlock->shouldRenderSubTotal($row)) {
                     $config[] = false;
                 }
                
            }
        }
        
        return $this->helper('core')->jsonEncode($config);
    }
    
    protected function _getGridBlockSortedColumns(Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        $columns = $gridBlock->getColumns();
        
        foreach ($columns as $key => $column) {
            if ($column->getBlcgFilterOnly()) {
                unset($columns[$key]);
            }
        }
        
        $orders = $gridBlock->getColumnsOrder();
        
        if ($sorted) {
            $keys   = array_keys($columns);
            $values = array_values($columns);
            
            foreach ($orders as $columnId => $after) {
                if (array_search($after, $keys) !== false) {
                    $positionCurrent = array_search($columnId, $keys);
                    
                    $key = array_splice($keys, $positionCurrent, 1);
                    $value = array_splice($values, $positionCurrent, 1);
                    
                    $positionTarget = array_search($after, $keys) + 1;
                    
                    array_splice($keys, $positionTarget, 0, $key);
                    array_splice($values, $positionTarget, 0, $value);
                    
                    $columns = array_combine($keys, $values);
                }
            }
        }
        
        return $columns;
    }
    
    public function getEditableColumnsJsonConfig()
    {
        $stringHelper = $this->helper('customgrid/string');
        $gridBlock = $this->getGridBlock();
        $gridModel = $this->getGridModel();
        $config = array();
        
        if ($gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid::ACTION_USE_CUSTOMIZED_COLUMNS)) {
            $columns = $gridModel->getSortedColumns(true, false, true, true, true, true);
            
            foreach ($columns as $columnBlockId => $column) {
                if ($column->isOnlyFilterable()) {
                    unset($columns[$columnBlockId]);
                } elseif (!$column->isEditable() || !$column->isEditAllowed()) {
                    $columns[$columnBlockId] = false;
                }
            }
            
        } else {
            $blockColumns = $this->_getGridBlockSortedColumns($gridBlock);
            $modelColumns = $gridModel->getColumns(true);
            $columns = array();
            
            foreach ($blockColumns as $columnBlockId => $columnBlock) {
                if (isset($modelColumns[$columnBlockId]) && $modelColumns[$columnBlockId]->isGrid()) {
                    if ($modelColumns[$columnBlockId]->isOnlyFilterable()) {
                        continue;
                    }
                    $columns[$columnBlockId] = $modelColumns[$columnBlockId];
                } else {
                    $columns[$columnBlockId] = false;
                }
            }
        }
        
        if ($gridModel->hasUserEditPermissions($gridBlock)) {
            foreach ($columns as $column) {
                if ($column !== false) {
                    $config[] = $stringHelper->camelizeArrayKeys($column->getEditConfig()->getEditorBlockData(), false);
                } else {
                    $config[] = false;
                }
            }
        } else {
            $config = array_fill(0, count($columns), false);
        }
        
        return $this->helper('core')->jsonEncode($config);
    }
    
    public function getAdditionalParamsJson()
    {
        return $this->helper('core')
            ->jsonEncode($this->getGridModel()->getAdditionalEditParams($this->getGridBlock()));
    }
    
    public function getGlobalParamsJson()
    {
        return $this->helper('core')
            ->jsonEncode(array(
                'grid_id' => $this->getGridModel()->getId(),
                'profile_id' => $this->getGridModel()->getProfileId(),
                'editor_js_object_name' => $this->getJsObjectName(),
            ));
    }
}