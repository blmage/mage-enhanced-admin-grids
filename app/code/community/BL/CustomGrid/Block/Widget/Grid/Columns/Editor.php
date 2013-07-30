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

class BL_CustomGrid_Block_Widget_Grid_Columns_Editor
    extends Mage_Adminhtml_Block_Template
{
    static protected $_instancesNumber = 0; 
    protected $_instanceId = null;
    
    protected function _construct()
    {
        parent::_construct();
        $this->_instanceId = ++self::$_instancesNumber;
        $this->setId(Mage::helper('core')->uniqHash('customGridEditor_'.$this->_instanceId));
        $this->setTemplate('bl/customgrid/widget/grid/columns/editor.phtml');
    }
    
    public function getFormKey()
    {
        return Mage::getSingleton('core/session')->getFormKey();
    }
    
    public function getJsObjectName()
    {
        return $this->getId() . 'JsObject';
    }
    
    public function getGridTableId()
    {
        return $this->getGridBlock()->getId().'_table';
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
        
        return Mage::helper('core')->jsonEncode($config);
    }
    
    protected function _getBlockSortedColumns($block)
    {
        // Get block columns, sort them if needed
        $columns = $block->getColumns();
        
        // Remove only filterable columns
        foreach ($columns as $key => $column) {
            if ($column->getBlcgFilterOnly()) {
                unset($columns[$key]);
            }
        }
        
        $orders  = $block->getColumnsOrder();
        
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
        $block  = $this->getGridBlock();
        $model  = $this->getGridModel();
        $config = array();
        
        if ($model->checkUserActionPermission(BL_CustomGrid_Model_Grid::GRID_ACTION_USE_CUSTOMIZED_COLUMNS)) {
            $columns = $model->getSortedColumns(true, false, true, true, true, true);
            
            // Remove only filterable columns
            foreach ($columns as $key => $column) {
                if ($column['filter_only']) {
                    unset($columns[$key]);
                }
            }
            
        } else {
            $blockColumns = $this->_getBlockSortedColumns($block);
            $modelColumns = $model->getColumns(true);
            $columns = array();
            
            foreach ($blockColumns as $columnId => $column) {
                if (isset($modelColumns[$columnId])
                    && $model->isGridColumnOrigin($modelColumns[$columnId]['origin'])) {
                    if ($modelColumns[$columnId]['filter_only']) {
                        // Skip only filterable columns
                        // @todo check what is / what should be the scope of GRID_ACTION_USE_CUSTOMIZED_COLUMNS
                        continue;
                    }
                    $modelColumn = $modelColumns[$columnId];
                    
                    $columns[$columnId] = array(
                        'allow_edit' => $modelColumn['allow_edit'],
                        'editable'   => (isset($modelColumn['editable']) ? $modelColumn['editable'] : false),
                    );
                } else {
                    $columns[$columnId] = array('allow_edit' => false);
                }
            }
        }
        
        if ($model->hasUserEditPermissions($block)) {
            foreach ($columns as $column) {
                if ($column['allow_edit'] && isset($column['editable']) && is_array($column['editable'])) {
                    $config[] = $column['editable'];
                } else {
                    $config[] = false;
                }
            }
        } else {
            $config = array_fill(0, count($columns), false);
        }
        
        return Mage::helper('core')->jsonEncode($config);
    }
    
    public function getAdditionalParamsJson()
    {
        return Mage::helper('core')->jsonEncode(
            $this->getGridModel()->getAdditionalEditParams($this->getGridBlock())
        );
    }
    
    public function getGlobalParamsJson()
    {
        return Mage::helper('core')->jsonEncode(array(
            'grid_id' => $this->getGridModel()->getId(),
            'editor_js_object_name' => $this->getJsObjectName(),
        ));
    }
    
    public function getErrorMessagesJson()
    {
        return Mage::helper('core')->jsonEncode(
            array(
                'edit_request_failure' => $this->__('Failed to edit value'),
                'save_request_failure' => $this->__('Failed to save value'),
                'save_no_params'       => $this->__('No parameter to save'),
            )
        );
    }
    
    protected function _toHtml()
    {
        if (!$this->getIsNewGridModel()
            && ($model = $this->getGridModel())
            && $model->checkUserActionPermission(BL_CustomGrid_Model_Grid::GRID_ACTION_EDIT_COLUMNS_VALUES)
            && ($grid = $this->getGridBlock())
            && Mage::helper('customgrid')->isRewritedGrid($grid)) {
            return parent::_toHtml();
        }
        return '';
    }
}