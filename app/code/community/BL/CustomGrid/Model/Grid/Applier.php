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

class BL_CustomGrid_Model_Grid_Applier extends BL_CustomGrid_Model_Grid_Worker_Abstract
{
    public function getType()
    {
        return BL_CustomGrid_Model_Grid::WORKER_TYPE_APPLIER;
    }
    
    /**
     * Return the store model usable for the given column
     * 
     * @param BL_CustomGrid_Model_Grid_Column $column Grid column
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return Mage_Core_Model_Store
     */
    protected function _getColumnStoreModel(
        BL_CustomGrid_Model_Grid_Column $column,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock
    ) {
        return is_null($column->getStoreId())
            ? $gridBlock->blcg_getStore()
            : Mage::app()->getStore($column->getStoreId());
    }
    
    /**
     * Return the renderer type and parameters usable to render the given collection column
     * 
     * @param BL_CustomGrid_Model_Grid_Column $column Collection column
     * @param array $lockedValues Column locked values
     * @return array
     */
    protected function _getCollectionColumnRendererValues(BL_CustomGrid_Model_Grid_Column $column, array $lockedValues)
    {
        if (isset($lockedValues['renderer'])) {
            $rendererType = $lockedValues['renderer'];
            $rendererParams = ($rendererType == $column->getRendererType())
                ? $column->getRendererParams()
                : array();
        } else {
            $rendererType = $column->getRendererType();
            $rendererParams = $column->getRendererParams();
        }
        return array($rendererType, $rendererParams);
    }
    
    /**
     * Return the values needed to create a column block corresponding to the given collection column
     *
     * @param BL_CustomGrid_Model_Grid_Column $column Grid collection column
     * @param array $baseData Base column data
     * @param array $lockedValues Locked column values
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return array
     */
    protected function _getCollectionColumnBlockValues(
        BL_CustomGrid_Model_Grid_Column $column,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        array $baseData,
        array $lockedValues
    ) {
        if (isset($lockedValues['renderer']) || $column->getRendererType()) {
            /** @var $rendererConfig BL_CustomGrid_Model_Column_Renderer_Config_Collection */
            $rendererConfig = Mage::getSingleton('customgrid/column_renderer_config_collection');
            list($rendererType, $rendererParams) = $this->_getCollectionColumnRendererValues($column, $lockedValues);
            $rendererValues = array();
            
            if ($renderer = $rendererConfig->getRendererModelByCode($rendererType)) {
                if (is_array($decodedParams = $rendererConfig->decodeParameters($rendererParams))) {
                    $renderer->setValues($decodedParams);
                } else {
                    $renderer->setValues(array());
                }
                
                $rendererValues = $renderer->getColumnBlockValues(
                    $column->getIndex(),
                    $gridBlock->blcg_getStore(),
                    $this->getGridModel()
                );
            }
            
            $baseData = array_merge($baseData, $rendererValues);
        }
        return $baseData;
    }
    
    /**
     * Return the renderer usable to render the attribute columns based on the given attribute,
     * prepared with the given parameters
     * 
     * @param Mage_Eav_Model_Entity_Attribute $attribute Column attribute
     * @param string $rendererParams Encoded renderer parameters
     * @return BL_CustomGrid_Column_Renderer_Attribute_Abstract|null
     */
    protected function _getAttributeColumnRenderer(Mage_Eav_Model_Entity_Attribute $attribute, $rendererParams)
    {
        $gridModel = $this->getGridModel();
        /** @var $rendererConfig BL_CustomGrid_Model_Column_Renderer_Config_Attribute */
        $rendererConfig = Mage::getSingleton('customgrid/column_renderer_config_attribute');
        $renderers = $rendererConfig->getRenderersModels();
        $matchingRenderer = null;
        
        foreach ($renderers as $renderer) {
            if ($renderer->isAppliableToAttribute($attribute, $gridModel)) {
                $matchingRenderer = $renderer;
                
                if (is_array($rendererParams = $rendererConfig->decodeParameters($rendererParams))) {
                    $matchingRenderer->setValues($rendererParams);
                } else {
                    $matchingRenderer->setValues(array());
                }
                
                break;
            }
        }
        
        return $matchingRenderer;
    }
    
    /**
     * Return the values needed to create a column block corresponding to the given attribute column
     *
     * @param BL_CustomGrid_Model_Grid_Column $column Grid attribute column
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param array $baseData Base column data
     * @param Mage_Eav_Model_Entity_Attribute[] $attributes Available attributes
     * @param string[] $addedAttributes Attributes that were already added (values format: "[code]_[store_id]")
     * @return array
     */
    protected function _getAttributeColumnBlockValues(
        BL_CustomGrid_Model_Grid_Column $column,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        array $baseData,
        array $attributes,
        array &$addedAttributes
    ) {
        if (isset($attributes[$column->getIndex()])) {
            $gridModel = $this->getGridModel();
            $store = $this->_getColumnStoreModel($column, $gridBlock);
            $attribute = $attributes[$column->getIndex()];
            $attributeKey = $column->getIndex() . '_' . $store->getId();
            
            if (!isset($addedAttributes[$attributeKey])) {
                $baseData['index'] = BL_CustomGrid_Model_Grid::ATTRIBUTE_COLUMN_GRID_ALIAS
                    . str_replace(BL_CustomGrid_Model_Grid::ATTRIBUTE_COLUMN_ID_PREFIX, '', $column->getBlockId());
                
                $gridBlock->blcg_addAdditionalAttribute(
                    array(
                        'alias'     => $baseData['index'],
                        'attribute' => $attribute,
                        'bind'      => 'entity_id',
                        'filter'    => null,
                        'join_type' => 'left',
                        'store_id'  => $store->getId(),
                    )
                );
                
                $addedAttributes[$attributeKey] = $baseData['index'];
            } else {
                $baseData['index'] = $addedAttributes[$attributeKey];
            }
            
            if (($renderer = $this->_getAttributeColumnRenderer($attribute, $column->getRendererParams()))
                && is_array($rendererValues = $renderer->getColumnBlockValues($attribute, $store, $gridModel))) {
                $baseData = array_merge($baseData, $rendererValues);
            }
        }
        
        return $baseData;
    }
    
    /**
     * Return the renderer usable to render the columns based on the given custom column,
     * depending on the selected renderer type, prepared with the given parameters
     * 
     * @param BL_CustomGrid_Model_Custom_Column_Abstract $customColumn Custom column model
     * @param string $rendererType Selected renderer type
     * @param string $rendererParams Encoded renderer parameters
     * @return BL_CustomGrid_Column_Renderer_Collection_Abstract|null
     */
    protected function _getCustomColumnRenderer(
        BL_CustomGrid_Model_Custom_Column_Abstract $customColumn,
        $rendererType,
        $rendererParams
    ) {
        $renderer = null;
        
        if ($customColumn->getAllowRenderers()) {
            if ($customColumn->getLockedRenderer() && ($customColumn->getLockedRenderer() != $rendererType)) {
                $rendererType = $customColumn->getLockedRenderer();
                $rendererParams = null;
            }
            
            /** @var $rendererConfig BL_CustomGrid_Model_Column_Renderer_Config_Collection */
            $rendererConfig = Mage::getSingleton('customgrid/column_renderer_config_collection');
            
            if ($rendererType && ($renderer = $rendererConfig->getRendererModelByCode($rendererType))) {
                if (is_array($rendererParams = $rendererConfig->decodeParameters($rendererParams))) {
                    $renderer->setValues($rendererParams);
                } else {
                    $renderer->setValues(array());
                }
            }
        }
        
        return $renderer;
    }
    
    /**
     * Return the values needed to create a column block corresponding to the given attribute column
     *
     * @param BL_CustomGrid_Model_Grid_Column $column Grid custom column
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param array $baseData Base column data 
     * @return array
     */
    protected function _getCustomColumnBlockValues(
        BL_CustomGrid_Model_Grid_Column $column,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        array $baseData
    ) {
        if ($customColumn = $column->getCustomColumnModel()) {
            $gridModel = $this->getGridModel();
            $baseData['index'] = BL_CustomGrid_Model_Grid::CUSTOM_COLUMN_GRID_ALIAS
                . str_replace(BL_CustomGrid_Model_Grid::CUSTOM_COLUMN_ID_PREFIX, '', $column->getBlockId());
            
            if ($customizationParams = $column->getCustomizationParams()) {
                $customizationParams = $gridModel->getGridTypeConfig()->decodeParameters($customizationParams);
            }
            
            $customColumnValues = $customColumn->getApplier()
                ->applyCustomColumnToGridBlock(
                    $gridBlock,
                    $gridModel,
                    $column->getBlockId(),
                    $baseData['index'],
                    (is_array($customizationParams) ? $customizationParams : array()),
                    $this->_getColumnStoreModel($column, $gridBlock),
                    $this->_getCustomColumnRenderer(
                        $customColumn,
                        $column->getRendererType(),
                        $column->getRendererParams()
                    )
                );
            
            $baseData = (is_array($customColumnValues) ? array_merge($baseData, $customColumnValues) : null);
        }
        return $baseData;
    }
    
    /**
     * Prepare the given grid column, assuming it is part of the original grid block columns list
     * 
     * @param BL_CustomGrid_Model_Grid_Column $column Grid column
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return BL_CustomGrid_Model_Grid_Applier
     */
    protected function _prepareOriginalGridBlockColumn(
        BL_CustomGrid_Model_Grid_Column $column,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock
    ) {
        $gridModel = $this->getGridModel();
        
        if ($gridColumn = $gridBlock->getColumn($column->getBlockId())) {
            if (!$gridModel->getIgnoreCustomWidths()) {
                $gridColumn->setWidth($column->getWidth());
            }
            if (!$gridModel->getIgnoreCustomAlignments()) {
                $gridColumn->setAlign($column->getAlign());
            }
            if (!$gridModel->getIgnoreCustomHeaders()) {
                $gridColumn->setHeader($column->getHeader());
            }
        }
        
        return $this;
    }
    
    /**
     * Prepare the given grid column and add it to the given grid block
     * (assuming it is not part of the original columns list from the grid block)
     * 
     * @param BL_CustomGrid_Model_Grid_Column $column Grid column
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param Mage_Eav_Model_Entity_Attribute[] $attributes Available attributes
     * @param string[] $addedAttributes Attributes that were already added (values format: "[code]_[store_id]")
     * @return BL_CustomGrid_Model_Grid_Applier
     */
    protected function _prepareExternalGridBlockColumn(
        BL_CustomGrid_Model_Grid_Column $column,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        array $attributes,
        array &$addedAttributes
    ) {
        $gridModel = $this->getGridModel();
        $lockedValues = $gridModel->getColumnLockedValues($column->getBlockId());
        
        $data = array(
            'header' => $column->getHeader(),
            'align'  => $column->getAlign(),
            'width'  => $column->getWidth(),
            'index'  => $column->getIndex(),
        );
        
        $data = array_merge($data, array_intersect_key($lockedValues, $data));
        
        if ($column->isCollection()) {
            $data = $this->_getCollectionColumnBlockValues($column, $gridBlock, $data, $lockedValues);
        } elseif ($column->isAttribute()) {
            $data = $this->_getAttributeColumnBlockValues($column, $gridBlock, $data, $attributes, $addedAttributes);
        } elseif ($column->isCustom()) {
            $data = $this->_getCustomColumnBlockValues($column, $gridBlock, $data);
        }
        
        if (!empty($data)) {
            if (isset($lockedValues['config_values']) && is_array($lockedValues['config_values'])) {
                $data = array_merge($data, $lockedValues['config_values']);
            }
            $gridBlock->addColumn($column->getBlockId(), $data);
        }
        
        return $this;
    }
    
    /**
     * Arrange the given grid block's columns according to the given sorted column block IDs
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param string[] $sortedBlockIds Sorted column block IDs
     * @return BL_CustomGrid_Model_Grid_Applier
     */
    protected function _arrangeGridBlockColumns(Mage_Adminhtml_Block_Widget_Grid $gridBlock, array $sortedBlockIds)
    {
        $gridBlock->blcg_resetColumnsOrder();
        $previousBlockId = null;
        
        foreach ($sortedBlockIds as $columnBlockId) {
            if (!is_null($previousBlockId)) {
                $gridBlock->addColumnsOrder($columnBlockId, $previousBlockId);
            }
            $previousBlockId = $columnBlockId;
        }
        
        $gridBlock->sortColumnsByOrder();
        return $this;
    }
    
    /**
     * Apply the columns customization from the current grid model to the given grid block
     *
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param bool $applyFromCollection Whether collection columns should be added to the grid block
     * @return BL_CustomGrid_Model_Grid_Applier
     */
    public function applyGridModelColumnsToGridBlock(Mage_Adminhtml_Block_Widget_Grid $gridBlock, $applyFromCollection)
    {
        $gridModel = $this->getGridModel();
        $columns = $gridModel->getColumns(false, true);
        uasort($columns, array($gridModel, 'sortColumns'));
        $sortedBlockIds = array();
        $gridColumnIds  = array_keys($gridBlock->getColumns());
        
        $attributes = $gridModel->getAvailableAttributes();
        $addedAttributes = array();
        
        foreach ($columns as $column) {
            if (!in_array($column->getBlockId(), $gridColumnIds, true)) {
                if ($column->isVisible() && !$column->isMissing()
                    && (!$column->isCollection() || $applyFromCollection)) {
                    $this->_prepareExternalGridBlockColumn(
                        $column,
                        $gridBlock,
                        $attributes,
                        $addedAttributes
                    );
                }
            } elseif ($column->isVisible()) {
                $this->_prepareOriginalGridBlockColumn($column, $gridBlock);
            } else {
                $gridBlock->blcg_removeColumn($column->getBlockId());
            }
            if ($columnBlock = $gridBlock->getColumn($column->getBlockId())) {
                if ($column->isOnlyFilterable()) {
                    $columnBlock->setBlcgFilterOnly(true);
                    
                    if ($gridBlock->blcg_isExport()) {
                        // Columns with is_system flag set won't be exported, so forcing it will save us two overloads
                        $columnBlock->setIsSystem(true);
                    }
                } else {
                    $sortedBlockIds[] = $column->getBlockId();
                }
            }
        }
        
        $this->_arrangeGridBlockColumns($gridBlock, $sortedBlockIds);
        return $this;
    }
    
    /**
     * Prepare the given output grid block by setting our own children blocks, rearranging the filter buttons,
     * and applying our corresponding custom template
     *
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param bool $isNewGridModel Whether the corresponding grid model has just been created
     * @return BL_CustomGrid_Model_Grid_Applier
     */
    public function prepareOutputGridBlock(Mage_Adminhtml_Block_Widget_Grid $gridBlock, $isNewGridModel)
    {
        $gridModel = $this->getGridModel();
        $helper = $this->getBaseHelper();
        $layout = $gridBlock->getLayout();
        
        // Apply main blocks
        
        /** @var $configBlock BL_CustomGrid_Block_Widget_Grid_Config */
        $configBlock  = $layout->createBlock('customgrid/widget_grid_config');
        /** @var $editorBlock BL_CustomGrid_Block_Widget_Grid_Columns_Editor */
        $editorBlock  = $layout->createBlock('customgrid/widget_grid_columns_editor');
        /** @var $filtersBlock BL_CustomGrid_Block_Widget_Grid_Columns_Filters */
        $filtersBlock = $layout->createBlock('customgrid/widget_grid_columns_filters');
        
        $gridBlock->setChild(
            'blcg_grid_config',
            $configBlock->setGridBlock($gridBlock)
                ->setGridModel($gridModel)
                ->setIsNewGridModel($isNewGridModel)
        );
        
        $gridBlock->setChild(
            'blcg_grid_columns_editor',
            $editorBlock->setGridBlock($gridBlock)
                ->setGridModel($gridModel)
                ->setIsNewGridModel($isNewGridModel)
        );
        
        $gridBlock->setChild(
            'blcg_grid_columns_filters',
            $filtersBlock->setGridBlock($gridBlock)
                ->setGridModel($gridModel)
                ->setIsNewGridModel($isNewGridModel)
        );
        
        if (!$isNewGridModel) {
            // Rearrange filter buttons
            
            /** @var $filterButtonsList Mage_Core_Block_Text_List */
            $filterButtonsList   = $layout->createBlock('core/text_list');
            $resetFilterButton   = $gridBlock->getChild('reset_filter_button');
            $defaultFilterButton = $layout->createBlock('customgrid/widget_grid_button_default_filter_reapply');
            /** @var $defaultFilterButton BL_CustomGrid_Block_Widget_Grid_Button_Default_Filter_Reapply */
            $defaultFilterButton->setGridBlock($gridBlock)->setGridModel($gridModel);
            
            if (!$gridModel->getHideFilterResetButton() && $resetFilterButton) {
                $filterButtonsList->append($resetFilterButton);
            }
            
            $filterButtonsList->append($defaultFilterButton);
            $gridBlock->setChild('reset_filter_button', $filterButtonsList);
        }
        
        // Apply custom template
        
        if ($helper->isMageVersionGreaterThan(1, 5)) {
            $gridBlock->setTemplate('bl/customgrid/widget/grid/16.phtml');
        } elseif ($helper->isMageVersion15()) {
            $gridBlock->setTemplate('bl/customgrid/widget/grid/15.phtml');
        } else {
            $revision = $helper->getMageVersionRevision();
            $gridBlock->setTemplate('bl/customgrid/widget/grid/14' . ((int) $revision) . '.phtml');
        }
        
        return $this;
    }
}
