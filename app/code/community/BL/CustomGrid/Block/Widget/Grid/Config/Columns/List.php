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

/**
 * @method BL_CustomGrid_Model_Grid getGridModel() Return the current grid model
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
    
    /**
     * Return whether the columns list is stand-alone (ie, not displayed along with the corresponding grid block)
     * 
     * @return bool
     */
    public function isStandAlone()
    {
        return false;
    }
    
    public function getId()
    {
        if (!$this->hasData('id')) {
            /** @var $helper Mage_Core_Helper_Data */
            $helper = $this->helper('core');
            $this->setData('id', $helper->uniqHash('blcgConfig'));
        }
        return $this->_getData('id');
    }
    
    /**
     * Return the initial maximum order amongst all the columns
     * 
     * @return int
     */
    public function getColumnsMaxOrder()
    {
        if (!$this->hasData('max_order')) {
            $this->setData('max_order', $this->getGridModel()->getColumnsMaxOrder());
        }
        return $this->_getData('max_order');
    }
    
    /**
     * Return the orders pitch to use between each column
     * 
     */
    public function getColumnsOrderPitch()
    {
        if (!$this->hasData('order_pitch')) {
            $this->setData('order_pitch', $this->getGridModel()->getColumnsOrderPitch());
        }
        return $this->_getData('order_pitch');
    }
    
    /**
     * Return whether drag'n'drop can be used to sort the columns
     * 
     * @return bool
     */
    public function getUseDragNDrop()
    {
        if (!$this->hasData('use_drag_n_drop')) {
            /** @var $helper BL_CustomGrid_Helper_Config */
            $helper = $this->helper('customgrid/config');
            $this->setData('use_drag_n_drop', $helper->getSortWithDnd());
        }
        return $this->_getData('use_drag_n_drop');
    }
    
    /**
     * Return whether attribute columns are supported by the current grid model
     * 
     * @return bool
     */
    public function canHaveAttributeColumns()
    {
        if (!$this->hasData('can_have_attribute_columns')) {
            $this->setData('can_have_attribute_columns', $this->getGridModel()->canHaveAttributeColumns());
        }
        return $this->_getData('can_have_attribute_columns');
    }
    
    /**
     * Return whether the "Editable" part can be displayed for each column
     * 
     * @return bool
     */
    public function canDisplayEditablePart()
    {
        if (!$this->hasData('can_display_editable_part')) {
            $this->setData('can_display_editable_part', $this->getGridModel()->hasEditableColumns());
        }
        return $this->_getData('can_display_editable_part');
    }
    
    /**
     * Return whether the user can choose the editable columns
     * 
     * @return bool
     */
    public function canChooseEditableColumns()
    {
        if (!$this->hasData('can_choose_editable_columns')) {
            $this->setData(
                'can_choose_editable_columns',
                $this->getGridModel()
                    ->checkUserActionPermission(BL_CustomGrid_Model_Grid_Sentry::ACTION_CHOOSE_EDITABLE_COLUMNS)
            );
        }
        return $this->_getData('can_choose_editable_columns');
    }
    
    /**
     * Return whether the "System" part can be displayed for each column
     * 
     * @return bool
     */
    public function canDisplaySystemPart()
    {
        if (!$this->hasData('can_display_system_part')) {
            $this->setData('can_display_system_part', $this->getGridModel()->getDisplaySystemPart());
        }
        return $this->_getData('can_display_system_part');
    }
    
    /**
     * Return whether the "Store View" part can be displayed for each column
     * 
     * @return bool
     */
    public function canDisplayStorePart()
    {
        if (!$this->hasData('can_display_store_part')) {
            $this->setData('can_display_store_part', !Mage::app()->isSingleStoreMode());
        }
        return $this->_getData('can_display_store_part');
    }
    
    /**
     * Return the column alignments as an option hash
     * 
     * @return array
     */
    public function getColumnAlignments()
    {
        if (!$this->hasData('column_alignments')) {
            /** @var $columnModel BL_CustomGrid_Model_Grid_Column */
            $columnModel = Mage::getSingleton('customgrid/grid_column');
            $this->setData('column_alignments', $columnModel->getAlignments());
        }
        return $this->_getData('column_alignments');
    }
    
    /**
     * Return the column origins as an option hash
     * 
     * @return array
     */
    public function getColumnOrigins()
    {
        if (!$this->hasData('column_origins')) {
            /** @var $columnModel BL_CustomGrid_Model_Grid_Column */
            $columnModel = Mage::getSingleton('customgrid/grid_column');
            $this->setData('column_origins', $columnModel->getOrigins());
        }
        return $this->_getData('column_origins');
    }
    
    /**
     * Return the columns list
     * 
     * @return BL_CustomGrid_Model_Grid_Column[]
     */
    public function getColumns()
    {
        if (!$this->hasData('columns')) {
            $this->setData(
                'columns',
                $this->getGridModel()->getSortedColumns(
                    true,
                    true,
                    $this->canHaveAttributeColumns(),
                    true,
                    false,
                    true,
                    true
                )
            );
        }
        return $this->_getData('columns');
    }
    
    /**
     * Return the locked values for the given column block ID
     * 
     * @param string $columnBlockId Column block ID
     * @return array
     */
    public function getColumnLockedValues($columnBlockId)
    {
        $dataKey = 'column_locked_values_' . $columnBlockId;
        
        if (!$this->hasData($dataKey)) {
            $this->setData(
                $dataKey,
                $this->getGridModel()->getColumnLockedValues($columnBlockId)
            );
        }
        
        return $this->_getData($dataKey);
    }
    
    /**
     * Return the value of the given key for the given column block ID
     * 
     * @param string $columnBlockId Column block ID
     * @param string $valueKey Key of the locked value
     * @return mixed
     */
    public function getColumnLockedValue($columnBlockId, $valueKey)
    {
        $lockedValues = $this->getColumnLockedValues($columnBlockId);
        return (isset($lockedValues[$valueKey]) ? $lockedValues[$valueKey] : null);
    }
    
    /**
     * Return the name of the main JS object from the grid block
     * 
     * @return string
     */
    public function getGridJsObjectName()
    {
        return (($gridBlock = $this->getGridBlock()) ? $gridBlock->getJsObjectName() : null);
    }
    
    /**
     * Return the name of the columns config JS object
     * 
     * @return string
     */
    public function getConfigJsObjectName()
    {
        return $this->getId() . 'Config';
    }
    
    /**
     * Return the URL usable to save the columns list
     * 
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('adminhtml/blcg_grid/saveColumns');
    }
    
    /**
     * Return the URL of the custom column configuration form
     * 
     * @return string
     */
    public function getCustomColumnConfigUrl()
    {
        return $this->getUrl('adminhtml/blcg_custom_column_config/index');
    }
    
    /**
     * Return the additional request parameters as JSON
     * 
     * @return string
     */
    public function getAdditionalParamsJsonConfig()
    {
        if (!$this->hasData('additional_params_json_config')) {
            /** @var $helper Mage_Core_Helper_Data */
            $helper = $this->helper('core');
            
            $this->setData(
                'additional_params_json_config',
                $helper->jsonEncode(
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
    
    /**
     * Return the placeholder used to represent dynamic IDs in strings
     * 
     * @return string
     */
    public function getIdPlaceholder()
    {
        return '{{id}}';
    }
    
    /**
     * Return the HTML content of the attribute column addition button
     * 
     * @return string
     */
    public function getAttributeColumnButtonHtml()
    {
        return $this->getButtonHtml(
            $this->__('Add Attribute Column'),
            $this->getConfigJsObjectName() . '.addColumn();',
            'scalable add'
        );
    }
    
    /**
     * Return the HTML content of the columns list save button
     * 
     * @return string
     */
    public function getSaveButtonHtml()
    {
        return $this->getButtonHtml(
            $this->__('Save'),
            $this->getConfigJsObjectName() . '.saveColumns();',
            'scalable save'
        );
    }
    
    /**
     * Return a global HTML ID based on the given suffix
     * 
     * @param string $suffix ID suffix
     * @return string
     */
    protected function _getGlobalHtmlId($suffix)
    {
        return $this->getHtmlId() . '-' . $suffix;
    }
    
    /**
     * Return a HTML ID based on the given column ID and suffix
     * 
     * @param string $suffix ID suffix
     * @param int|null $columnId Column ID (if none is given, ID placeholder will be used)
     * @return string
     */
    protected function _getColumnBasedHtmlId($suffix, $columnId = null)
    {
        return $this->getHtmlId() . '-' . (is_null($columnId) ? $this->getIdPlaceholder() : $columnId). '-' . $suffix;
    }
    
    /**
     * Return the HTML ID of the columns table
     * 
     * @return string
     */
    public function getTableHtmlId()
    {
        return $this->_getGlobalHtmlId('table');
    }
    
    /**
     * Return the HTML ID of the columns table body
     * 
     * @return string
     */
    public function getTableRowsHtmlId()
    {
        return $this->_getGlobalHtmlId('table-rows');
    }
    
    /**
     * Return the HTML ID of the table row corresponding to the given column ID
     * 
     * @param int|null $columnId Column ID (if none is given, ID placeholder will be used)
     * @return string
     */
    public function getTableRowHtmlId($columnId = null)
    {
        return $this->_getColumnBasedHtmlId('table-column', $columnId);
    }
    
    /**
     * Return the HTML ID of the visibility checkbox corresponding to the given column ID
     * 
     * @param int|null $columnId Column ID (if none is given, ID placeholder will be used)
     * @return string
     */
    public function getVisibleCheckboxHtmlId($columnId = null)
    {
        return $this->_getColumnBasedHtmlId('visible-checkbox', $columnId);
    }
    
    /**
     * Return the HTML ID of the filterable-only checkbox corresponding to the given column ID
     * 
     * @param int|null $columnId Column ID (if none is given, ID placeholder will be used)
     * @return string
     */
    public function getFilterOnlyCheckboxHtmlId($columnId = null)
    {
        return $this->_getColumnBasedHtmlId('filter-only-checkbox', $columnId);
    }
    
    /**
     * Return the HTML ID of the editability container corresponding to the given column ID
     * 
     * @param int|null $columnId Column ID (if none is given, ID placeholder will be used)
     * @return string
     */
    public function getEditableContainerHtmlId($columnId = null)
    {
        return $this->_getColumnBasedHtmlId('editable-container', $columnId);
    }
    
    /**
     * Return the HTML ID of the editability checkbox corresponding to the given column ID
     * 
     * @param int|null $columnId Column ID (if none is given, ID placeholder will be used)
     * @return string
     */
    public function getEditableCheckboxHtmlId($columnId = null)
    {
        return $this->_getColumnBasedHtmlId('editable-checkbox', $columnId);
    }
    
    /**
     * Return the HTML ID of the attribute renderer config button corresponding to the given column ID
     * 
     * @param int|null $columnId Column ID (if none is given, ID placeholder will be used)
     * @return string
     */
    public function getAttributeRendererConfigButtonHtmlId($columnId = null)
    {
        return $this->_getColumnBasedHtmlId('config-button', $columnId);
    }
    
    /**
     * Return the HTML ID of the custom column config button corresponding to the given column ID
     * 
     * @param int|null $columnId Column ID (if none is given, ID placeholder will be used)
     * @return string
     */
    public function getCustomColumnConfigButtonHtmlId($columnId = null)
    {
        return $this->_getColumnBasedHtmlId('custom-column-config-button', $columnId);
    }
    
    /**
     * Return the HTML ID of the custom column config target corresponding to the given column ID
     * 
     * @param int|null $columnId Column ID (if none is given, ID placeholder will be used)
     * @return string
     */
    public function getCustomColumnConfigTargetHtmlId($columnId = null)
    {
        return $this->_getColumnBasedHtmlId('custom-column-config-target', $columnId);
    }
    
    /**
     * Return the HTML ID of the order input corresponding to the given column ID
     * 
     * @param int|null $columnId Column ID (if none is given, ID placeholder will be used)
     * @return string
     */
    public function getOrderInputHtmlId($columnId = null)
    {
        return $this->_getColumnBasedHtmlId('order-input', $columnId);
    }
    
    /**
     * Return the store select block
     * 
     * @return BL_CustomGrid_Block_Store_Select
     */
    protected function _getStoreSelectBlock()
    {
        if (!$this->getChild('store_select')) {
            /** @var $storeSelect BL_CustomGrid_Block_Store_Select */
            $storeSelect = $this->getLayout()->createBlock('customgrid/store_select');
            $this->setChild('store_select', $storeSelect);
        }
        return $this->getChild('store_select');
    }
    
    /**
     * Return the HTML content of the store select for the given column,
     * or a JS string representing the same HTML content for a dynamic column (using the ID placeholder)
     * 
     * @param BL_CustomGrid_Model_Grid_Column $column Grid column
     * @return string
     */
    protected function getStoreSelectHtml(BL_CustomGrid_Model_Grid_Column $column = null)
    {
        $jsOutput  = is_null($column);
        $columnId  = ($jsOutput ? $this->getIdPlaceholder() : $column->getId());
        $storeId   = (!$jsOutput ? $column->getStoreId() : null);
        
        return $this->_getStoreSelectBlock()
            ->setHasUseGridOption(true)
            ->setHasUseDefaultOption(true)
            ->setStoreId($storeId)
            ->setSelectName('columns[' . $columnId . '][store_id]')
            ->setSelectClassNames('select')
            ->setOutputAsJs($jsOutput)
            ->toHtml();
    }
    
    /**
     * Return the helper block for collection columns
     * 
     * @return BL_CustomGrid_Block_Widget_Grid_Config_Columns_Helper_Collection
     */
    protected function _getCollectionColumnHelperBlock()
    {
        if (!$this->getChild('collection_column_helper')) {
            $this->setChild(
                'collection_column_helper',
                $this->getLayout()->createBlock('customgrid/widget_grid_config_columns_helper_collection')
            );
        }
        return $this->getChild('collection_column_helper')->setData(array());
    }
    
    /**
     * Return the name of the collection renderers selects manager JS object
     * 
     * @return string
     */
    public function getCRSMJsObjectName()
    {
        return $this->getId() . 'CRSM';
    }
    
    /**
     * Return the JS script corresponding to the initialization of the collection renderers elements
     * 
     * @return string
     */
    public function getCollectionRenderersJsHtml()
    {
        return $this->_getCollectionColumnHelperBlock()
            ->setTemplate('bl/customgrid/widget/grid/config/columns/renderer/collection/js.phtml')
            ->setJsObjectName($this->getCRSMJsObjectName())
            ->toHtml();
    }
    
    /**
     * Return the collection renderer values from the given grid column, if it is consistent
     * 
     * @param BL_CustomGrid_Model_Grid_Column $column Grid column
     * @return array|null Locked renderer flag + locked renderer label + renderer type, or null if not consistent
     */
    protected function _getColumnCollectionRendererValues(BL_CustomGrid_Model_Grid_Column $column)
    {
        $columnBlockId = $column->getBlockId();
        
        if ($column->isCollection()) {
            $lockedValues = $this->getGridModel()->getColumnLockedValues($columnBlockId);
            
            if ($lockedRenderer = isset($lockedValues['renderer'])) {
                $rendererType = $lockedValues['renderer'];
            } else {
                $rendererType = $column->getRendererType();
            }
             
            $lockedLabel = (isset($lockedValues['renderer_label']) ? $lockedValues['renderer_label'] : '');
        } elseif ($column->isCustom() && ($customColumn = $column->getCustomColumnModel())) {
            if ($lockedRenderer = (bool) strlen($customColumn->getLockedRenderer())) {
                $lockedLabel  = $customColumn->getRendererLabel();
                $rendererType = $customColumn->getLockedRenderer();
            } else {
                $lockedLabel  = '';
                $rendererType = $column->getRendererType();
            }
        } else {
            return null;
        }
        
        return array($lockedRenderer, $lockedLabel, $rendererType);
    }
    
    /**
     * Return the HTML content of the collection renderers select for the given grid column
     * 
     * @param BL_CustomGrid_Model_Grid_Column $column Grid column
     * @return string
     */
    public function getCollectionRenderersSelectHtml(BL_CustomGrid_Model_Grid_Column $column)
    {
        $htmlId = $this->getHtmlId();
        $columnId = $column->getId();
        list($lockedRenderer, $lockedLabel, $rendererType) = $this->_getColumnCollectionRendererValues($column);
        $isPreviousRenderer = (!$lockedRenderer || ($rendererType === $column->getRendererType()));
        
        return $this->_getCollectionColumnHelperBlock()
            ->setTemplate('bl/customgrid/widget/grid/config/columns/renderer/collection/select.phtml')
            ->setBaseHtmlId($htmlId . '-' . $columnId . '-crs')
            ->setRendererCode($rendererType)
            ->setIsForcedRenderer($lockedRenderer)
            ->setForcedRendererLabel($lockedLabel)
            ->setRendererParams($isPreviousRenderer ? $column->getRendererParams() : '')
            ->setRendererSelectName('columns[' . $columnId . '][renderer_type]')
            ->setRendererSelectClassNames('select')
            ->setRendererTargetName('columns[' . $columnId . '][renderer_params]')
            ->setSelectsManagerJsObjectName($this->getCRSMJsObjectName())
            ->toHtml();
    }
    
    /**
     * Return the helper block for attribute columns
     * 
     * @return BL_CustomGrid_Block_Widget_Grid_Config_Columns_Helper_Attribute
     */
    protected function _getAttributeColumnHelperBlock()
    {
        if (!$this->getChild('attribute_column_helper')) {
            $this->setChild(
                'attribute_column_helper',
                $this->getLayout()->createBlock('customgrid/widget_grid_config_columns_helper_attribute')
            );
        }
        return $this->getChild('attribute_column_helper')->setData(array());
    }
    
    /**
     * Return the name of the attributes selects manager JS object
     * 
     * @return string
     */
    public function getASMJsObjectName()
    {
        return $this->getId() . 'ASM';
    }
    
    /**
     * Return the JS script corresponding to the initialization of the attribute renderers elements
     * 
     * @return string
     */
    public function getAttributeRenderersJsHtml()
    {
        return $this->_getAttributeColumnHelperBlock()
            ->setTemplate('bl/customgrid/widget/grid/config/columns/renderer/attribute/js.phtml')
            ->setGridModel($this->getGridModel())
            ->setJsObjectName($this->getASMJsObjectName())
            ->toHtml();
    }
    
    /**
     * Return the HTML content of the attributes select for the given grid column,
     * or a JS string representing the same HTML content for a dynamic column (using the ID placeholder)
     * 
     * @param BL_CustomGrid_Model_Grid_Column $column Grid column
     * @return string
     */
    public function getAttributesSelectHtml(BL_CustomGrid_Model_Grid_Column $column = null)
    {
        $htmlId = $this->getHtmlId();
        $jsOutput  = is_null($column);
        $columnId  = ($jsOutput ? $this->getIdPlaceholder() : $column->getId());
        $attributeCode  = (!$jsOutput ? $column->getIndex() : null);
        $rendererParams = (!$jsOutput ? $column->getRendererParams() : '');
        
        return $this->_getAttributeColumnHelperBlock()
            ->setTemplate('bl/customgrid/widget/grid/config/columns/attribute/select.phtml')
            ->setBaseHtmlId($htmlId . '-' . $columnId . '-ars')
            ->setGridModel($this->getGridModel())
            ->setAttributeCode($attributeCode)
            ->setOutputAsJs($jsOutput)
            ->setRendererParams($rendererParams)
            ->setAttributeSelectName('columns[' . $columnId . '][index]')
            ->setAttributeSelectClassNames('select')
            ->setEditableContainerHtmlId($this->getEditableContainerHtmlId($columnId))
            ->setEditableCheckboxHtmlId($this->getEditableCheckboxHtmlId($columnId))
            ->setRendererTargetName('columns[' . $columnId . '][renderer_params]')
            ->setUseExternalConfigButton(true)
            ->setRendererConfigButtonHtmlId($this->getAttributeRendererConfigButtonHtmlId($columnId))
            ->setSelectsManagerJsObjectName($this->getASMJsObjectName())
            ->toHtml();
    }
}
