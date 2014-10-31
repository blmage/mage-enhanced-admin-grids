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

class BL_CustomGrid_Block_Widget_Grid_Config_Columns_List extends Mage_Adminhtml_Block_Widget
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('bl/customgrid/widget/grid/config/columns/list.phtml');
    }
    
    protected function _toHtml()
    {
        return $this->getGridModel()
            ? parent::_toHtml()
            : '';
    }
    
    public function isStandAlone()
    {
        return false;
    }
    
    public function getId()
    {
        return $this->getDataSetDefault('id', $this->helper('core')->uniqHash('blcgConfig'));
    }
    
    public function getColumnsMaxOrder()
    {
        return $this->getDataSetDefault('max_order', $this->getGridModel()->getColumnsMaxOrder());
    }
    
    public function getColumnsOrderPitch()
    {
        return $this->getDataSetDefault('order_pitch', $this->getGridModel()->getColumnsOrderPitch());
    }
    
    public function getUseDragNDrop()
    {
        return $this->getDataSetDefault('use_drag_n_drop', $this->helper('customgrid/config')->getSortWithDnd());
    }
    
    public function canHaveAttributeColumns()
    {
        return $this->getDataSetDefault('can_have_attribute_columns', $this->getGridModel()->canHaveAttributeColumns());
    }
    
    public function canDisplayEditablePart()
    {
        return $this->getDataSetDefault('can_display_editable_part', $this->getGridModel()->hasEditableColumns());
    }
    
    public function canChooseEditableColumns()
    {
        return $this->getDataSetDefault(
            'can_choose_editable_columns',
            $this->getGridModel()->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_CHOOSE_EDITABLE_COLUMNS)
        );
    }
    
    public function canDisplaySystemPart()
    {
        return $this->getDataSetDefault('can_display_system_part', $this->getGridModel()->getDisplaySystemPart());
    }
    
    public function canDisplayStorePart()
    {
        return $this->getDataSetDefault('can_display_store_part', !Mage::app()->isSingleStoreMode());
    }
    
    public function getColumnAlignments()
    {
        return $this->getDataSetDefault(
            'column_alignments',
            Mage::getSingleton('customgrid/grid_column')->getAlignments()
        );
    }
    
    public function getColumnOrigins()
    {
        return $this->getDataSetDefault(
            'column_origins',
            Mage::getSingleton('customgrid/grid_column')->getOrigins()
        );
    }
    
    public function getColumns()
    {
        return $this->getDataSetDefault(
            'columns',
            $this->getGridModel()->getSortedColumns(
                true,
                true,
                $this->canHaveAttributeColumns(),
                true,
                false,
                $this->canChooseEditableColumns(),
                true
            )
        );
    }
    
    public function getColumnLockedValues($columnBlockId)
    {
        return $this->getDataSetDefault(
            'column_locked_values' . $columnBlockId,
            $this->getGridModel()->getColumnLockedValues($columnBlockId)
        );
    }
    
    public function getColumnLockedValue($columnBlockId, $value)
    {
        $lockedValues = $this->getColumnLockedValues($columnBlockId);
        return (isset($lockedValues[$value]) ? $lockedValues[$value] : null);
    }
    
    public function getGridJsObjectName()
    {
        return (($gridBlock = $this->getGridBlock()) ? $gridBlock->getJsObjectName() : null);
    }
    
    public function getConfigJsObjectName()
    {
        return $this->getId() . 'Config';
    }
    
    public function getSaveUrl()
    {
        return $this->getUrl('customgrid/grid/saveColumns');
    }
    
    public function getCustomColumnConfigUrl()
    {
        return $this->getUrl('customgrid/custom_column_config/index');
    }
    
    public function getAdditionalParamsJsonConfig()
    {
        if (!$this->hasData('additional_params_json_config')) {
            $this->setData(
                'additional_params_json_config',
                Mage::helper('core')->jsonEncode(
                    array(
                        'form_key'   => $this->getFormKey(),
                        'grid_id'    => $this->getGridModel()->getId(),
                        'profile_id' => $this->getGridModel()->getProfileId(),
                    )
                )
            );
        }
        return $this->_getData('additional_params_json_config');
    }
    
    public function getIdPlaceholder()
    {
        return '{{id}}';
    }
    
    public function getAttributeColumnButtonHtml()
    {
        return $this->getButtonHtml(
            $this->__('Add Attribute Column'),
            $this->getConfigJsObjectName() . '.addColumn();',
            'scalable add'
        );
    }
    
    public function getSaveButtonHtml()
    {
        return $this->getButtonHtml(
            $this->__('Save'),
            $this->getConfigJsObjectName() . '.saveColumns();',
            'scalable save'
        );
    }
    
    protected function _getGlobalCssId($suffix)
    {
        return $this->getHtmlId() . '-' . $suffix;
    }
    
    protected function _getColumnBasedCssId($suffix, $columnId = null)
    {
        return $this->getHtmlId() . '-' . (is_null($columnId) ? $this->getIdPlaceholder() : $columnId). '-' . $suffix;
    }
    
    public function getTableCssId()
    {
        return $this->_getGlobalCssId('table');
    }
    
    public function getTableRowsCssId()
    {
        return $this->_getGlobalCssId('table-rows');
    }
    
    public function getTableRowCssId($columnId = null)
    {
        return $this->_getColumnBasedCssId('table-column', $columnId);
    }
    
    public function getVisibleCheckboxCssId($columnId = null)
    {
        return $this->_getColumnBasedCssId('visible-checkbox', $columnId);
    }
    
    public function getFilterOnlyCheckboxCssId($columnId = null)
    {
        return $this->_getColumnBasedCssId('filter-only-checkbox', $columnId);
    }
    
    public function getEditableContainerCssId($columnId = null)
    {
        return $this->_getColumnBasedCssId('editable-container', $columnId);
    }
    
    public function getEditableCheckboxCssId($columnId = null)
    {
        return $this->_getColumnBasedCssId('editable-checkbox', $columnId);
    }
    
    public function getAttributeRendererConfigButtonCssId($columnId = null)
    {
        return $this->_getColumnBasedCssId('config-button', $columnId);
    }
    
    public function getCustomColumnConfigButtonCssId($columnId = null)
    {
        return $this->_getColumnBasedCssId('custom-column-config-button', $columnId);
    }
    
    public function getCustomColumnConfigTargetCssId($columnId = null)
    {
        return $this->_getColumnBasedCssId('custom-column-config-target', $columnId);
    }
    
    public function getOrderInputCssId($columnId = null)
    {
        return $this->_getColumnBasedCssId('order-input', $columnId);
    }
    
    protected function _getStoreSelect()
    {
        if (!$this->getChild('store_select')) {
            $this->setChild(
                'store_select',
                $this->getLayout()->createBlock('customgrid/store_select')
            );
        }
        return $this->getChild('store_select');
    }
    
    protected function getStoreSelectHtml(BL_CustomGrid_Model_Grid_Column $column = null)
    {
        $jsOutput  = is_null($column);
        $columnId  = ($jsOutput ? $this->getIdPlaceholder() : $column->getId());
        $storeId   = (!$jsOutput ? $column->getStoreId() : null);
        
        return $this->_getStoreSelect()
            ->setHasUseGridOption(true)
            ->setHasUseDefaultOption(true)
            ->setStoreId($storeId)
            ->setSelectName('columns[' . $columnId . '][store_id]')
            ->setSelectClassNames('select')
            ->setOutputAsJs($jsOutput)
            ->toHtml();
    }
    
    public function getCRSMJsObjectName()
    {
        return $this->getId() . 'CRSM';
    }
    
    public function getCollectionRenderersJsHtml()
    {
        return $this->getLayout()
            ->createBlock('customgrid/column_renderer_collection_js')
            ->setJsObjectName($this->getCRSMJsObjectName())
            ->toHtml();
    }
    
    protected function _getCollectionRenderersSelect()
    {
        if (!$this->getChild('collection_renderer_select')) {
            $this->setChild(
                'collection_renderer_select',
                $this->getLayout()->createBlock('customgrid/column_renderer_collection_select')
            );
        }
        return $this->getChild('collection_renderer_select');
    }
    
    public function getCollectionRenderersSelectHtml(BL_CustomGrid_Model_Grid_Column $column)
    {
        $htmlId = $this->getHtmlId();
        $columnId = $column->getId();
        $columnBlockId = $column->getBlockId();
        
        if ($column->isCollection()) {
            $lockedValues = $this->getGridModel()->getColumnLockedValues($columnBlockId);
            
            if ($lockedRenderer = isset($lockedValues['renderer'])) {
                $rendererType = $lockedValues['renderer'];
            } else {
                $rendererType = $column->getRendererType();
            }
             
            $lockedLabel  = (isset($lockedValues['renderer_label']) ? $lockedValues['renderer_label'] : '');
        } elseif ($column->isCustom() && ($customColumn = $column->getCustomColumnModel())) {
            if ($lockedRenderer = (bool) strlen($customColumn->getLockedRenderer())) {
                $lockedLabel  = $customColumn->getRendererLabel();
                $rendererType = $customColumn->getLockedRenderer();
            } else {
                $lockedLabel  = '';
                $rendererType = $column->getRendererType();
            }
        } else {
            return '';
        }
        
        $isPreviousRenderer = (!$lockedRenderer || ($rendererType === $column->getRendererType()));
        
        return $this->_getCollectionRenderersSelect()
            ->setData(array())
            ->setId($htmlId . '-' . $columnId . '-crs')
            ->setRendererCode($rendererType)
            ->setIsForcedRenderer($lockedRenderer)
            ->setForcedRendererLabel($lockedLabel)
            ->setRendererParams($isPreviousRenderer ? $column->getRendererParams() : '')
            ->setSelectName('columns[' . $columnId . '][renderer_type]')
            ->setSelectClassNames('select')
            ->setRendererTargetName('columns[' . $columnId . '][renderer_params]')
            ->setSelectsManagerJsObjectName($this->getCRSMJsObjectName())
            ->toHtml();
    }
    
    public function getARSMJsObjectName()
    {
        return $this->getId() . 'ARSM';
    }
    
    public function getAttributeRenderersJsHtml()
    {
        return $this->getLayout()
            ->createBlock('customgrid/column_renderer_attribute_js')
            ->setGridModel($this->getGridModel())
            ->setJsObjectName($this->getARSMJsObjectName())
            ->toHtml();
    }
    
    protected function _getAttributesSelect()
    {
        if (!$this->getChild('attribute_renderer_select')) {
            $this->setChild(
                'attribute_renderer_select',
                $this->getLayout()->createBlock('customgrid/column_renderer_attribute_select')
            );
        }
        return $this->getChild('attribute_renderer_select');
    }
    
    public function getAttributesSelectHtml(BL_CustomGrid_Model_Grid_Column $column = null)
    {
        $htmlId = $this->getHtmlId();
        $jsOutput  = is_null($column);
        $columnId  = ($jsOutput ? $this->getIdPlaceholder() : $column->getId());
        $attributeCode  = (!$jsOutput ? $column->getIndex() : null);
        $rendererParams = (!$jsOutput ? $column->getRendererParams() : '');
        
        return $this->_getAttributesSelect()
            ->setData(array())
            ->setId($htmlId . '-' . $columnId . '-ars')
            ->setGridModel($this->getGridModel())
            ->setAttributeCode($attributeCode)
            ->setOutputAsJs($jsOutput)
            ->setRendererParams($rendererParams)
            ->setSelectName('columns[' . $columnId . '][index]')
            ->setSelectClassNames('select')
            ->setEditableContainerId($this->getEditableContainerCssId($columnId))
            ->setEditableCheckboxId($this->getEditableCheckboxCssId($columnId))
            ->setRendererTargetName('columns[' . $columnId . '][renderer_params]')
            ->setUseExternalConfigButton(true)
            ->setConfigButtonId($this->getAttributeRendererConfigButtonCssId($columnId))
            ->setSelectsManagerJsObjectName($this->getARSMJsObjectName())
            ->toHtml();
    }
}
