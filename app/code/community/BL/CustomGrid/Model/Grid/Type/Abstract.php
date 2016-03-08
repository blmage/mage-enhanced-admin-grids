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

abstract class BL_CustomGrid_Model_Grid_Type_Abstract extends BL_CustomGrid_Object
{
   /**
     * Return the base helper
     *
     * @return BL_CustomGrid_Helper_Data
     */
    public function getBaseHelper()
    {
        return Mage::helper('customgrid');
    }
    
    /**
     * Return the grid helper
     *
     * @return BL_CustomGrid_Helper_Grid
     */
    public function getGridHelper()
    {
        return Mage::helper('customgrid/grid');
    }
    
    /**
     * Return the current request object
     *
     * @return Mage_Core_Controller_Request_Http
     * @throws Exception
     */
    protected function _getRequest()
    {
        $controller = Mage::app()->getFrontController();
        
        if ($controller) {
            $request = $controller->getRequest();
        } else {
            throw new Exception('Cannot retrieve request object');
        }
        
        return $request;
    }
    
    /**
     * Return the class code of the usable editor model
     * 
     * @return string
     */
    protected function _getEditorModelClassCode()
    {
        return 'customgrid/grid_editor_default';
    }
    
    /**
     * Return the editor model
     * 
     * @return BL_CustomGrid_Model_Grid_Editor_Abstract
     */
    public function getEditor()
    {
        if (!$this->hasData('editor')) {
            $editorModel = Mage::getModel($this->_getEditorModelClassCode());
            
            if (!$editorModel instanceof BL_CustomGrid_Model_Grid_Editor_Abstract) {
                Mage::throwException('Editor models must be instances of BL_CustomGrid_Model_Grid_Editor_Abstract');
            }
            
            /** @var $editor BL_CustomGrid_Model_Grid_Editor_Abstract */
            $editorModel->setGridTypeModel($this);
            $this->setData('editor', $editorModel);
        }
        return $this->_getData('editor');
    }
    
    /**
     * Return which block types this grid type can handle
     * 
     * @return string|array
     */
    protected function _getSupportedBlockTypes()
    {
        return array();
    }
    
    /**
     * Return which block types this grid type can handle
     * Wrapper for _getSupportedBlockTypes(), with cache
     * 
     * @return string[]
     */
    public function getSupportedBlockTypes()
    {
        if (!$this->hasData('supported_block_types')) {
            if (!is_array($blockTypes = $this->_getSupportedBlockTypes())) {
                $blockTypes = array($blockTypes);
            }
            $this->setData('supported_block_types', $blockTypes);
        }
        return $this->_getData('supported_block_types');
    }
    
    /**
     * Return whether the given block type is "officially" supported by this grid type
     * 
     * @param string $blockType Grid block type
     * @return bool
     */
    public function isSupportedBlockType($blockType)
    {
        return in_array($blockType, $this->getSupportedBlockTypes(), true);
    }
    
    /**
     * Return whether this grid type can be used to handle given grid block
     * 
     * @param string $blockType Grid block type
     * @param string $rewritingClassName Name of the class currently rewriting the given block type (if any)
     * @return bool
     */
    public function isAppliableToGridBlock($blockType, $rewritingClassName = '')
    {
        return $this->isSupportedBlockType($blockType);
    }
    
    /**
     * Return whether given grid model matches given grid block type and ID
     * 
     * @param string $blockType Grid block type
     * @param string $blockId Grid block ID
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @return bool
     */
    public function matchGridBlock($blockType, $blockId, BL_CustomGrid_Model_Grid $gridModel)
    {
        $result = false;
        
        if ($blockType == $gridModel->getBlockType()) {
            if ($gridModel->getHasVaryingBlockId()) {
                $helper = $this->getGridHelper();
                
                if ($helper->isVaryingGridBlockId($blockId)) {
                    $result = $helper->checkVaryingGridBlockIdsEquality($blockId, $gridModel->getBlockId());
                }
            } else {
                $result = ($blockId == $gridModel->getBlockId());
            }
        }
        
        return $result;
    }
    
    /**
     * Return locked values for grid columns (user won't be able to change them)
     * Here are the possible array keys to use :
     * - "header"
     * - "width"
     * - "align" (must correspond to BL_CustomGrid_Model_Grid alignment constants)
     * - "renderer" : code of the collection renderer that should be forced,
     *                if the key is set but does not correspond to any renderer,
     *                then no renderer will be choosable nor used
     * - "renderer_label" : if no renderer can be choosen and the given forced renderer can not be found,
     *                      this label will be displayed
     * - "config_values"  : array of other locked values that will be used for the corresponding call to
     *                      Mage_Adminhtml_Block_Widget_Grid::addColumn()
     * 
     * @param string $blockType Grid block type
     * @return array
     */
    protected function _getColumnsLockedValues($blockType)
    {
        return array();
    }
    
    /**
     * Return locked values for grid columns (user won't be able to change them)
     * Wrapper for _getColumnsLockedValues(), with cache
     * 
     * @param string $blockType Grid block type
     * @return array
     */
    public function getColumnsLockedValues($blockType)
    {
        if (!$this->hasData('columns_locked_values')
            || !is_array($typeValues = $this->getData('columns_locked_values/' . $blockType))) {
            $typeValues = $this->_getColumnsLockedValues($blockType);
            $this->setData('columns_locked_values/' . $blockType, $typeValues);
        }
        return $typeValues;
    }
    
    /**
     * Return locked values for given column
     * 
     * @param string $blockType Grid block type
     * @param string $columnBlockId Column block ID
     * @return array
     */
    public function getColumnLockedValues($blockType, $columnBlockId)
    {
        $values = $this->getColumnsLockedValues($blockType);
        return (isset($values[$columnBlockId]) ? $values[$columnBlockId] : false);
    }
    
    /**
     * Return whether attribute columns are available
     * 
     * @param string $blockType Grid block type
     * @return bool
     */
    public function canHaveAttributeColumns($blockType)
    {
        return false;
    }
    
    /**
     * Return whether given attribute can be considered as available
     * 
     * @param string $blockType Grid block type
     * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute model
     * @return bool
     */
    protected function _isAvailableAttribute($blockType, Mage_Eav_Model_Entity_Attribute $attribute)
    {
        return (!$attribute->hasIsVisible() || $attribute->getIsVisible())
            && $attribute->getFrontend()->getInputType()
            && ($attribute->getBackend()->getType() != 'static');
    }
    
    /**
     * Return available attributes for given block type
     * 
     * @param string $blockType Grid block type
     * @return Mage_Eav_Model_Entity_Attribute[]
     */
    protected function _getAvailableAttributes($blockType)
    {
        return array();
    }
    
    /**
     * Return available attributes
     * Wrapper for _getAvailableAttributes(), with cache
     * 
     * @param string $blockType Grid block type
     * @param bool $withEditableFlag Whether the editable flag should be set on the attribute models
     * @return Mage_Eav_Model_Entity_Attribute[]
     */
    public function getAvailableAttributes($blockType, $withEditableFlag = false)
    {
        if (!$this->hasData('available_attributes')
            || !is_array($attributes = $this->getData('available_attributes/' . $blockType))) {
            $attributes = $this->_getAvailableAttributes($blockType);
            $response   = new BL_CustomGrid_Object(array('attributes' => $attributes));
            
            Mage::dispatchEvent(
                'blcg_grid_type_available_attributes',
                array(
                    'response'   => $response,
                    'type_model' => $this,
                    'block_type' => $blockType,
                )
            );
            
            $this->setData('available_attributes/' . $blockType, $attributes);
        }
        
        if ($withEditableFlag) {
            $editableAttributes = $this->getEditor()
                ->getEditableValuesConfigs(
                    $blockType,
                    BL_CustomGrid_Model_Grid_Editor_Abstract::EDITABLE_TYPE_ATTRIBUTE
                );
            
            foreach ($attributes as $attribute) {
                $attribute->setIsEditable(isset($editableAttributes[$attribute->getAttributeCode()]));
            }
        }
        
        return $attributes;
    }
    
    /**
     * Return whether grid results are exportable
     * 
     * @param string $blockType Grid block type
     * @return bool
     */
    public function canExport($blockType)
    {
        return true;
    }
    
    /**
     * Return available export types
     * 
     * @param string $blockType Grid block type
     * @return BL_CustomGrid_Object[]
     */
    protected function _getExportTypes($blockType)
    {
        return array(
            'csv' => new BL_CustomGrid_Object(
                array(
                    'route' => 'adminhtml/blcg_grid_export/exportCsv',
                    'label' => $this->getBaseHelper()->__('CSV'),
                )
            ),
            'xml' => new BL_CustomGrid_Object(
                array(
                    'route' => 'adminhtml/blcg_grid_export/exportExcel', 
                    'label' => $this->getBaseHelper()->__('Excel'),
                )
            ),
        );
    }
    
    /**
     * Return available export types
     * Wrapper for _getExportTypes(), with cache and some values preparation
     * 
     * @param string $blockType Grid block type
     * @return BL_CustomGrid_Object[]
     */
    public function getExportTypes($blockType)
    {
        if (!is_array($exportTypes = $this->getData('export_types/' . $blockType))) {
            /** @var $urlHelper Mage_Adminhtml_Helper_Data */
            $urlHelper   = Mage::helper('adminhtml');
            $exportTypes = array();
            
            foreach ($this->_getExportTypes($blockType) as $key => $exportType) {
                if (!$exportType instanceof BL_CustomGrid_Object) {
                    if (is_array($exportType)) {
                        $exportType = new BL_CustomGrid_Object($exportType);
                    } else {
                        continue;
                    }
                }
                
                if (!$exportType->hasUrl()) {
                    $urlParams = array('_current' => true, 'isAjax' => null);
                    
                    if (is_array($additionalParams = $exportType->getUrlParams())) {
                        $urlParams = array_merge($urlParams, $additionalParams);
                    }
                    
                    $exportType->setUrl($urlHelper->getUrl($exportType->getRoute(), $urlParams));
                }
                
                $exportTypes[$key] = $exportType;
            }
            
            $this->setData('export_types/' . $blockType, $exportTypes);
        }
        return $exportTypes;
    }
    
    /**
     * Return the additional parameters that should be included in the export forms
     * 
     * @param string $blockType Grid block type
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return array
     */
    public function getAdditionalExportParams($blockType, Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        $params = array();
        
        if ($massactionBlock = $gridBlock->getMassactionBlock()) {
            $selectedIds = $massactionBlock->getSelected();
            
            if (!empty($selectedIds)) {
                $params[$massactionBlock->getFormFieldNameInternal()] = implode(',', $selectedIds);
            }
        }
        
        return $params;
    }
    
    /**
     * Return whether given request corresponds to an export request from this extension
     * 
     * @param string $blockType Grid block type
     * @param Mage_Core_Controller_Request_Http $request Request object
     * @return bool
     */
    public function isExportRequest($blockType, Mage_Core_Controller_Request_Http $request)
    {
        $route = $request->getRouteName()
            . '/' . $request->getControllerName()
            . '/' . $request->getActionName();
        
        foreach ($this->getExportTypes($blockType) as $exportType) {
            if ($exportType->getRoute() == $route) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Custom columns sort callback
     *
     * @param BL_CustomGrid_Model_Custom_Column_Abstract $columnA One custom column
     * @param BL_CustomGrid_Model_Custom_Column_Abstract $columnB Another custom column
     * @return int
     */
    protected function _sortCustomColumns($columnA, $columnB)
    {
        return strcmp($columnA->getName(), $columnB->getName());
    }
    
    /**
     * Return additional custom columns (on top of those defined in the XML configuration)
     *
     * @return BL_CustomGrid_Model_Custom_Column_Abstract[]
     */
    protected function _getAdditionalCustomColumns()
    {
        return array();
    }
    
    /**
     * Return available custom columns
     * Wrapper for _getCustomColumns(), with filtering possibilities
     *
     * @param string|null $blockType Grid block type (if null, all custom columns will be returned)
     * @param string $rewritingClassName Grid rewriting class name
     * @param bool $nullIfNone Whether null should be returned instead of an empty array, if appropriate
     * @return BL_CustomGrid_Model_Custom_Column_Abstract[]|null
     */
    public function getCustomColumns($blockType = null, $rewritingClassName = '', $nullIfNone = false)
    {
        if (!$this->hasData('custom_columns')) {
            $code = $this->getCode();
            /** @var $gridTypeConfig BL_CustomGrid_Model_Grid_Type_Config */
            $gridTypeConfig = Mage::getSingleton('customgrid/grid_type_config');
            $configColumns  = $gridTypeConfig->getCustomColumnsByTypeCode($code);
            $response = new BL_CustomGrid_Object(array('columns' => array()));
            
            Mage::dispatchEvent(
                'blcg_grid_type_additional_custom_columns',
                array(
                    'response'   => $response,
                    'type_model' => $this,
                )
            );
            
            $customColumns = array_filter(
                array_merge(
                    $this->_getAdditionalCustomColumns(),
                    $configColumns,
                    $response->getColumns()
                ),
                create_function('$value', 'return ($value instanceof BL_CustomGrid_Model_Custom_Column_Abstract);')
            );
            
            uasort($customColumns, array($this, '_sortCustomColumns'));
            $this->setData('custom_columns', $customColumns);
            $this->getCustomColumnsGroups(); // Force the initialization of the columns groups
        }
        
        if (is_null($blockType)) {
            $customColumns = $this->_getData('custom_columns');
        } else {
            $blockKey = $blockType . '/' . ($rewritingClassName !== '' ? (string) $rewritingClassName : '!');
            
            if (!$this->hasData('block_type_custom_columns')
                || !is_array($customColumns = $this->getData('block_type_custom_columns/' . $blockKey))) {
                $customColumns = array();
                
                foreach ($this->_getData('custom_columns') as $code => $customColumn) {
                    if ($customColumn->isAvailable($blockType, $rewritingClassName)) {
                        $customColumns[$code] = $customColumn;
                    }
                }
                
                $this->setData('block_type_custom_columns/' . $blockKey, $customColumns);
            }
        }
        
        return (!empty($customColumns) ? $customColumns : ($nullIfNone ? null : array()));
    }
    
    /**
     * Return custom column by code
     *
     * @param string $code Custom column code
     * @return BL_CustomGrid_Model_Custom_Column_Abstract|null
     */
    public function getCustomColumn($code)
    {
        $customColumns = $this->getCustomColumns();
        return (isset($customColumns[$code]) ? $customColumns[$code] : null);
    }
    
    /**
     * Return available custom columns groups
     *
     * @return string[]
     */
    public function getCustomColumnsGroups()
    {
        if (!$this->hasData('custom_columns_groups')) {
            // Initialize columns groups
            $defaultGroupId = 1;
            $currentGroupId = 2;
            $groups = array();
            
            if (is_array($customColumns = $this->getCustomColumns())) {
                foreach ($customColumns as $customColumn) {
                    if ($customColumn->hasGroup()) {
                        if (!$groupId = array_search($customColumn->getGroup(), $groups)) {
                            $groupId = 'g' . $currentGroupId++;
                            $groups[$groupId] = $customColumn->getGroup();
                        }
                        $customColumn->setGroupId($groupId);
                    } else {
                        $customColumn->setGroupId('g' . $defaultGroupId);
                    }
                }
            }
            
            uasort($groups, 'strcmp');
            $groups['g1'] = $this->getBaseHelper()->__('Others');
            $this->setData('custom_columns_groups', $groups);
        }
        return $this->_getData('custom_columns_groups');
    }
    
    /**
     * Return whether custom columns are available
     *
     * @param string $blockType Grid block type
     * @param string $rewritingClassName Grid rewriting class name
     * @return bool
     */
    public function canHaveCustomColumns($blockType, $rewritingClassName = '')
    {
        return is_array($this->getCustomColumns($blockType, $rewritingClassName, true));
    }
    
    /**
     * Do some actions before grid is exported
     * 
     * @param string $format Export format
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block, null at first call (before block creation)
     * @return BL_CustomGrid_Model_Grid_Type_Abstract
     */
    public function beforeGridExport($format, Mage_Adminhtml_Block_Widget_Grid $gridBlock = null)
    {
        return $this;
    }
    
    /**
     * Do some actions after grid is exported
     * 
     * @param string $format Export format
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return BL_CustomGrid_Model_Grid_Type_Abstract
     */
    public function afterGridExport($format, Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        return $this;
    }
    
    /**
     * Do some actions before grid collection is prepared
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param bool $firstTime Whether this is the first (= incomplete) grid collection preparation
     * @return BL_CustomGrid_Model_Grid_Type_Abstract
     */
    public function beforeGridPrepareCollection(Mage_Adminhtml_Block_Widget_Grid $gridBlock, $firstTime = true)
    {
        return $this;
    }
    
    /**
     * Do some actions after grid collection is prepared
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param bool $firstTime Whether this is the first (= incomplete) grid collection preparation
     * @return BL_CustomGrid_Model_Grid_Type_Abstract
     */
    public function afterGridPrepareCollection(Mage_Adminhtml_Block_Widget_Grid $gridBlock, $firstTime = true)
    {
        return $this;
    }
    
    /**
     * Do some actions before given collection is set on given grid
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param Varien_Data_Collection $collection Grid collection
     * @return BL_CustomGrid_Model_Grid_Type_Abstract
     */
    public function beforeGridSetCollection(
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection $collection
    ) {
        return $this;
    }
    
    
    /**
     * Do some actions after given collection was set on given grid
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param Varien_Data_Collection $collection Grid collection
     * @return BL_CustomGrid_Model_Grid_Type_Abstract
     */
    public function afterGridSetCollection(
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection $collection
    ) {
        return $this;
    }
    
    /**
     * Do some actions before given grid loads given collection for export
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param Varien_Data_Collection $collection Grid collection
     * @return BL_CustomGrid_Model_Grid_Type_Abstract
     */
    public function beforeGridExportLoadCollection(
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection $collection
    ) {
        return $this;
    }
    
    /**
     * Do some actions after given grid has loaded given collection for export
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block instance
     * @param Varien_Data_Collection $collection Grid collection
     * @return BL_CustomGrid_Model_Grid_Type_Abstract
     */
    public function afterGridExportLoadCollection(
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection $collection
    ) {
        return $this;
    }
}
