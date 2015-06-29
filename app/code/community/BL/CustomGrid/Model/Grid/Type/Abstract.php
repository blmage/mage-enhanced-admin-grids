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
    const EDITABLE_TYPE_FIELD = 'static';
    const EDITABLE_TYPE_ATTRIBUTE = 'attribute';
    const EDITABLE_TYPE_ATTRIBUTE_FIELD = 'attribute_field';
    
    /**
     * Return base helper
     *
     * @return BL_CustomGrid_Helper_Data
     */
    protected function _getBaseHelper()
    {
        return Mage::helper('customgrid');
    }
    
    /**
     * Return grid block helper
     *
     * @return BL_CustomGrid_Helper_Grid
     */
    protected function _getGridHelper()
    {
        return Mage::helper('customgrid/grid');
    }
    
    /**
     * Return editor helper
     *
     * @return BL_CustomGrid_Helper_Editor
     */
    protected function _getEditorHelper()
    {
        return Mage::helper('customgrid/editor');
    }
    
    /**
     * Return layout model
     *
     * @return Mage_Core_Model_Layout
     */
    protected function _getLayout()
    {
        return Mage::getSingleton('core/layout');
    }
    
    /**
     * Return current request object
     *
     * @return Mage_Core_Controller_Request_Http
     */
    protected function _getRequest()
    {
        $controller = Mage::app()->getFrontController();
        
        if ($controller) {
            $this->_request = $controller->getRequest();
        } else {
            throw new Exception(Mage::helper('core')->__('Cannot retrieve request object'));
        }
        
        return $this->_request;
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
                $helper = $this->_getGridHelper();
                
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
     * @param bool $withEditableFlag Whether editable flag should be set on attribute models
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
            $editableAttributes = $this->getEditableAttributes($blockType);
            
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
                    'route' => 'customgrid/grid/exportCsv',
                    'label' => $this->_getBaseHelper()->__('CSV'),
                )
            ),
            'xml' => new BL_CustomGrid_Object(
                array(
                    'route' => 'customgrid/grid/exportExcel', 
                    'label' => $this->_getBaseHelper()->__('Excel'),
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
        $route = $request->getModuleName()
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
                'blcg_grid_type_additional_columns',
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
            $groups['g1'] = $this->_getBaseHelper()->__('Others');
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
     * Return action URL for the given value of the given origin
     * 
     * @param string $blockType Grid block type
     * @param string $valueOrigin Value origin
     * @param string $valueKey Value key
     * @param BL_CustomGrid_Model_Grid_Edit_Config $config Value config
     * @param string $route Action route
     * @return string
     */
    protected function _getEditorActionUrl(
        $blockType,
        $valueOrigin,
        $valueKey,
        BL_CustomGrid_Model_Grid_Edit_Config $config,
        $route
    ) {
        $blockTypeCode = str_replace('/', '_', $blockType);
        $routeCode = str_replace('/', '_', $route);
        $dataKey = $valueOrigin . '_action_base_urls/' . $blockTypeCode . '/' . $routeCode;
        
        if (!$baseUrl = $this->getData($dataKey)) {
            /** @var $helper Mage_Core_Helper_Data */
            $coreHelper = Mage::helper('core');
            
            $baseUrl = Mage::helper('adminhtml')->getUrl(
                $route,
                array(
                    'grid_type'   => $this->getCode(),
                    'block_type'  => $coreHelper->urlEncode($blockType),
                    'id'          => '{{value_key}}',
                    'origin'      => $valueOrigin,
                    'is_external' => '{{in_grid}}',
                )
            );
            
            $this->setData($dataKey, $baseUrl);
        }
        
        return str_replace(
            array('{{value_key}}', '{{in_grid}}'),
            array($valueKey, (!$config->getInGrid() ? 1 : 0)),
            $baseUrl
        );
    }
    
    /**
     * Return edit URL for given field
     * 
     * @param string $blockType Grid block type
     * @param string $fieldId Field ID
     * @param BL_CustomGrid_Model_Grid_Edit_Config $config Field config
     * @return string
     */
    protected function _getFieldEditUrl($blockType, $fieldId, BL_CustomGrid_Model_Grid_Edit_Config $config)
    {
        return $this->_getEditorActionUrl(
            $blockType,
            self::EDITABLE_TYPE_FIELD,
            $fieldId,
            $config,
            'customgrid/grid_editor/edit' . ($config->getInGrid() ? 'InGrid' : '')
        );
    }
    
    /**
     * Return save URL for given field
     * 
     * @param string $blockType Grid block type
     * @param string $fieldId Field ID
     * @param BL_CustomGrid_Model_Grid_Edit_Config $config Field config
     * @return string
     */
    protected function _getFieldSaveUrl($blockType, $fieldId, BL_CustomGrid_Model_Grid_Edit_Config $config)
    {
        return $this->_getEditorActionUrl(
            $blockType,
            self::EDITABLE_TYPE_FIELD,
            $fieldId,
            $config,
            'customgrid/grid_editor/save'
        );
    }
    
    /**
     * Return edit URL for given attribute
     * 
     * @param string $blockType Grid block type
     * @param string $attributeCode Attribute code
     * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute object
     * @param BL_CustomGrid_Model_Grid_Edit_Config $config Attribute config
     * @return string
     */
    protected function _getAttributeEditUrl(
        $blockType,
        $attributeCode,
        Mage_Eav_Model_Entity_Attribute $attribute,
        BL_CustomGrid_Model_Grid_Edit_Config $config
    ) {
        return $this->_getEditorActionUrl(
            $blockType,
            self::EDITABLE_TYPE_ATTRIBUTE,
            $attributeCode,
            $config,
            'customgrid/grid_editor/edit' . ($config->getInGrid() ? 'InGrid' : '')
        );
    }
    
    /**
     * Return save URL for given attribute
     * 
     * @param string $blockType Grid block type
     * @param string $attributeCode Attribute code
     * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute object
     * @param BL_CustomGrid_Model_Grid_Edit_Config $config Attribute config
     * @return string
     */
    protected function _getAttributeSaveUrl(
        $blockType,
        $attributeCode,
        Mage_Eav_Model_Entity_Attribute $attribute,
        BL_CustomGrid_Model_Grid_Edit_Config $config
    ) {
        return $this->_getEditorActionUrl(
            $blockType,
            self::EDITABLE_TYPE_ATTRIBUTE,
            $attributeCode,
            $config,
            'customgrid/grid_editor/save'
        );
    }
    
    /**
     * Return base editable fields configs
     * 
     * @param string $blockType Grid block type
     * @return array
     */
    protected function _getBaseEditableFields($blockType)
    {
        return array();
    }
    
    /**
     * Return all editable fields configs
     * 
     * @param string $blockType Grid block type
     * @return array
     */
    protected function _getEditableFields($blockType)
    {
        $response = new BL_CustomGrid_Object(array('fields' => array()));
        
        Mage::dispatchEvent(
            'blcg_grid_type_additional_editable_fields',
            array(
                'response'   => $response,
                'type_model' => $this,
                'block_type' => $blockType,
            )
        );
        
        return array_merge($this->_getBaseEditableFields($blockType), $response->getFields());
    }
    
    /**
     * Return whether given attribute is editable
     * 
     * @param string $blockType Grid block type
     * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute object
     * @return bool
     */
    protected function _checkAttributeEditability($blockType, Mage_Eav_Model_Entity_Attribute $attribute)
    {
        return $attribute->getFrontend()->getInputType()
            && (!$attribute->hasIsVisible() || $attribute->getIsVisible());
    }
    
    /**
     * Return additional editable attributes
     * = attributes that are not necessarily intended to be available for display
     * (to be used when needed by corresponding attribute fields)
     * 
     * @param string $blockType Grid block type
     * @return Mage_Eav_Model_Entity_Attribute[]
     */
    protected function _getAdditionalEditableAttributes($blockType)
    {
        return array();
    }
    
    /**
     * Return all editable attributes
     * 
     * @param string $blockType Grid block type
     * @return Mage_Eav_Model_Entity_Attribute[]
     */
    protected function _getEditableAttributes($blockType)
    {
        $response = new BL_CustomGrid_Object(array('attributes' => array()));
        
        Mage::dispatchEvent(
            'blcg_grid_type_additional_editable_attributes',
            array(
                'response'   => $response,
                'type_model' => $this,
                'block_type' => $blockType,
            )
        );
        
        $attributes = array_merge(
            $this->getAvailableAttributes($blockType),
            $this->_getAdditionalEditableAttributes($blockType),
            $response->getAttributes()
        );
        
        foreach ($attributes as $code => $attribute) {
            if (!$this->_checkAttributeEditability($blockType, $attribute)) {
                unset($attributes[$code]);
            }
        }
        
        return $attributes;
    }
    
    /**
     * Return editable sub configs
     * 
     * @return array
     */
    protected function _getEditableSubConfigs()
    {
        return array(
            'form'     => array('camelize' => false),
            'window'   => array('camelize' => true),
            'renderer' => array('camelize' => false),
        );
    }
    
    /**
     * Build the sub configs arrays for given editable value config
     * 
     * @param string $blockType Grid block type
     * @param string $valueId Value ID
     * @param BL_CustomGrid_Model_Grid_Edit_Config $config Value config
     * @param array $subConfigs Which sub configs must be built (key = config key, value = array of parameters)
     * @return BL_CustomGrid_Model_Grid_Type_Abstract
     */
    protected function _buildEditableValueSubConfigs(
        $blockType,
        $valueId,
        BL_CustomGrid_Model_Grid_Edit_Config $config,
        array $subConfigs
    ) {
        /** @var $stringHelper BL_CustomGrid_Helper_String */
        $stringHelper = Mage::helper('customgrid/string');
        
        foreach ($subConfigs as $subKey => $subParams) {
            $subKeyLength = strlen($subKey);
            
            foreach ($config->getData() as $key => $value) {
                if (!isset($subConfigs[$key])
                    && (substr($key, 0, $subKeyLength) === $subKey)) {
                    $config->unsetData($key);
                    $key = substr($key, $subKeyLength+1);
                    
                    if ($subParams['camelize']) {
                        $key = $stringHelper->camelize($key);
                    }
                    
                    $subValue = $config->getDataSetDefault($subKey, array());
                    
                    if (!isset($subValue[$key])) {
                        $subValue[$key] = $value;
                        $config->setDataUsingMethod($subKey, $subValue);
                    }
                }
            }
        }
        
        return $this;
    }
    
    /**
     * Prepare common config values for given editable field
     * 
     * @param string $blockType Grid block type
     * @param string $fieldId Field ID
     * @param BL_CustomGrid_Model_Grid_Edit_Config $config Field config
     * @return BL_CustomGrid_Model_Grid_Type_Abstract
     */
    protected function _prepareEditableFieldCommonConfig(
        $blockType,
        $fieldId,
        BL_CustomGrid_Model_Grid_Edit_Config $config
    ) {
        if ($config->getType() == 'editor') {
            $handles = $config->getDataSetDefault('layout_handles', array());
            $handles[] = 'blcg_grid_editor_handle_editor';
            $config->setLayoutHandles($handles);
        } elseif ($config->getType() == 'date') {
            $config->setMustFilter(true);
            $config->setRenderReload(true);
        }
        return $this;
    }
    
    /**
     * Build given editable field's complete config object from given base config
     * 
     * @param string $blockType Grid block type
     * @param string $fieldId Field ID
     * @param array $config Base field config
     * @return BL_CustomGrid_Model_Grid_Edit_Config
     */
    protected function _buildEditableFieldConfig($blockType, $fieldId, array $config)
    {
        // Complete current config with minimum required values
        $config += array(
            /*
            Main values
            */
            'type'              => 'text',        // Field (form) type
            'required'          => false,         // Is field required ?
            'field_name'        => $fieldId,      // Field name (used to retrieve its value from edited entity)
            'in_grid'           => true,          // Can the field be edited directly in the grid ?
            'edit_block_type'   => 'default',     // Type of the edit form block (shortcuts available for EAG)
            'render_block_type' => 'default',     // Same thing for saved values rendering
            'ids_key'           => 'identifiers', // Key where to put entities identifiers in request data
            'additional_key'    => 'additional',  // Key where to put additional values in request data
            'values_key'        => 'values',      // Key where to put edited values in request data
            'must_filter'       => false,         // Must the edited values be filtered before saving them ?
            'filter_type'       => null,          // Field type to use for values filtering (if null, main type is used)
            'filter_params'     => array(),       // Parameters to use for values filtering
            'render_reload'     => false,         // Must the entity be reloaded after save ?
                                                  // (so that suitable values can be retrieved for rendering)
            'column_params'     => array(),       // Additional column parameters
                                                  // (will be put in request's additional values)
            'layout_handles'    => array(),       // Layout handles to apply for external edit
            
            /*
            Form values (automatically put in "form" array if preceded by "form_")
            Below  : base form values
            Accept : all values used to create form elements
            */
            'form_id'    => $fieldId,
            'form_name'  => (isset($config['field_name']) ? $config['field_name'] : $fieldId),
            'form_label' => (isset($config['form_title']) ? $config['form_title'] : ''),
            'form_title' => (isset($config['form_label']) ? $config['form_label'] : ''),
            
            /*
            JS window values (for "external" edit) (automatically put in "window" array if preceded by "window_")
            Below  : default values for edit, that differ from JS config base ones
            Accept : all values accepted by corresponding JS class
            (keys must correspond, knowing that they will automatically be camel-cased)
            */
            'window_width'         => '80%',
            'window_height'        => '80%',
            'window_draggable'     => true,
            'window_resizable'     => true,
            'window_recenter_auto' => false,
            'window_title'         => $this->_getBaseHelper()->__('Edit Value'),
            
            /*
            Renderer values (automatically put in "renderer" array if preceded by "renderer_")
            Below  : default empty parameters array, as an example
            Accept : all values needed / used by the renderer block that will be used
            */
            'renderer_params' => array(),
        );
        
        /** @var $config BL_CustomGrid_Model_Grid_Edit_Config */
        $config = Mage::getModel('customgrid/grid_edit_config', $config);
        $this->_prepareEditableFieldCommonConfig($blockType, $fieldId, $config);
        $this->_buildEditableValueSubConfigs($blockType, $fieldId, $config, $this->_getEditableSubConfigs());
        
        if (!$config->hasEditUrl()) {
            $config->setEditUrl($this->_getFieldEditUrl($blockType, $fieldId, $config));
        }
        if (!$config->hasSaveUrl()) {
            $config->setSaveUrl($this->_getFieldSaveUrl($blockType, $fieldId, $config));
        }
        if ($config->getInGrid()) {
            $config->unsetData('window');
        }
        
        return $config;
    }
    
    /**
     * Return whether given attribute can be edited in grid
     * 
     * @param string $blockType Grid block type
     * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute object
     * @return bool
     */
    protected function _checkAttributeInGridEditability($blockType, Mage_Eav_Model_Entity_Attribute $attribute)
    {
        return in_array(
            $attribute->getFrontend()->getInputType(),
            array('date', 'multiselect', 'price', 'select', 'text')
        );
    }
    
    /**
     * Return the base config for given editable attribute
     * 
     * @param string $blockType Grid block type
     * @param string $attributeCode Attribute code
     * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute object
     * @return array
     */
    protected function _getEditableAttributeBaseConfig(
        $blockType,
        $attributeCode,
        Mage_Eav_Model_Entity_Attribute $attribute
    ) {
        // All those values pretty much correspond to the editable field config ones ("missing" ones are induced)
        return array(
            'in_grid'           => $this->_checkAttributeInGridEditability($blockType, $attribute),
            'edit_block_type'   => 'default',
            'render_block_type' => 'default',
            'ids_key'           => 'identifiers',
            'additional_key'    => 'additional',
            'values_key'        => 'values',
            'must_filter'       => false,
            'filter_type'       => null,
            'filter_params'     => array(),
            'render_reload'     => ($attribute->getBackendModel() != ''),
            'column_params'     => array(),
            'layout_handles'    => array(),
            
            'window_width'         => '80%',
            'window_height'        => '80%',
            'window_draggable'     => true,
            'window_resizable'     => true,
            'window_recenter_auto' => false,
            'window_title'         => $this->_getBaseHelper()->__('Edit Value'),
            
            'renderer_params' => array(),
        );
    }
    
    /**
     * Prepare common config values for given editable attribute
     * 
     * @param string $blockType Grid block type
     * @param string $attributeCode Attribute code
     * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute object
     * @param BL_CustomGrid_Model_Grid_Edit_Config $config Attribute config
     * @return BL_CustomGrid_Model_Grid_Type_Abstract
     */
    protected function _prepareEditableAttributeCommonConfig(
        $blockType,
        $attributeCode,
        Mage_Eav_Model_Entity_Attribute $attribute,
        BL_CustomGrid_Model_Grid_Edit_Config $config
    ) {
        if ($attribute->getBackend()->getType() == 'datetime') {
            $config->setMustFilter(true);
            $config->setFilterType('date');
            $config->setRenderReload(true);
        }
        return $this;
    }
    
    /**
     * Build given editable attribute's complete config object
     * 
     * @param string $blockType Grid block type
     * @param string $attributeCode Attribute code
     * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute object
     * @return BL_CustomGrid_Model_Grid_Edit_Config
     */
    protected function _buildEditableAttributeConfig(
        $blockType,
        $attributeCode,
        Mage_Eav_Model_Entity_Attribute $attribute
    ) {
        $config = $this->_getEditableAttributeBaseConfig($blockType, $attributeCode, $attribute);
        $config = Mage::getModel('customgrid/grid_edit_config', $config);
        $config->setAttribute($attribute);
        
        $this->_prepareEditableAttributeCommonConfig($blockType, $attributeCode, $attribute, $config);
        $this->_buildEditableValueSubConfigs($blockType, $attributeCode, $config, $this->_getEditableSubConfigs());
        
        if (!$config->hasEditUrl()) {
            $config->setEditUrl($this->_getAttributeEditUrl($blockType, $attributeCode, $attribute, $config));
        }
        if (!$config->hasSaveUrl()) {
            $config->setSaveUrl($this->_getAttributeSaveUrl($blockType, $attributeCode, $attribute, $config));
        }
        if ($config->getInGrid()) {
            $config->unsetData('window');
        }
        
        return $config;
    }
    
    /**
     * Return base editable attribute fields configs
     * (= all grid / collection columns that do correspond to an attribute, but were not added via EAG)
     * Used keys :
     * - "attribute" : corresponding attribute code
     * - "config" : array of config values that should override editable attribute's config ones
     * 
     * @param string $blockType Grid block type
     * @return array
     */
    protected function _getBaseEditableAttributeFields($blockType)
    {
        return array();
    }
    
    /**
     * Return all editable attribute fields configs
     * 
     * @param string $blockType Grid block type
     * @return array
     */
    protected function _getEditableAttributeFields($blockType)
    {
        $response = new BL_CustomGrid_Object(array('attribute_fields' => array()));
        
        Mage::dispatchEvent(
            'blcg_grid_type_additional_editable_attribute_fields',
            array(
                'response'   => $response,
                'type_model' => $this,
                'block_type' => $blockType,
            )
        );
        
        return array_merge($this->_getBaseEditableAttributeFields($blockType), $response->getAttributeFields());
    }
    
    /**
     * Build given editable attribute field's complete config object from given base config
     * May return null if the base config is invalid
     * (eg if the corresponding attribute does not exist / is not editable)
     * 
     * @param string $blockType Grid block type
     * @param string $fieldId Field ID
     * @param array $config Field config
     * @param BL_CustomGrid_Model_Grid_Edit_Config[] $attributes Attributes editable configs
     * @return BL_CustomGrid_Model_Grid_Edit_Config|null
     */
    protected function _buildEditableAttributeFieldConfig($blockType, $fieldId, array $config, array $attributes)
    {
        if (isset($config['attribute'])
            && isset($attributes[$config['attribute']])) {
            $overridenConfig = (isset($config['config']) ? $config['config'] : array());
            $config = clone $attributes[$config['attribute']];
            $config->mergeData($overridenConfig); 
            $this->_buildEditableValueSubConfigs($blockType, $fieldId, $config, $this->_getEditableSubConfigs());
            return $config;
        }
        return null;
    }
    
    /**
     * Return editable values configs
     * 
     * @param string $blockType Grid block type
     * @param string|null $origin Values origin (if null, all values will be returned)
     * @return array
     */
    public function getEditableValues($blockType, $origin = null)
    {
        if (!$this->hasData('editable_values')
            || !is_array($editableValues = $this->getData('editable_values/' . $blockType))) {
            // Build all base configs
            $fields = $this->_getEditableFields($blockType);
            $attributes = $this->_getEditableAttributes($blockType);
            $attributeFields = $this->_getEditableAttributeFields($blockType);
            
            foreach ($fields as $fieldId => $field) {
                $fields[$fieldId] = $this->_buildEditableFieldConfig($blockType, $fieldId, $field);
            }
            foreach ($attributes as $code => $attribute) {
                $attributes[$code] = $this->_buildEditableAttributeConfig($blockType, $code, $attribute);
            }
            foreach ($attributeFields as $fieldId => $attributeField) {
                $config = $this->_buildEditableAttributeFieldConfig($blockType, $fieldId, $attributeField, $attributes);
                
                if (!is_object($config)) {
                    unset($attributeFields[$fieldId]);
                } else {
                    $attributeFields[$fieldId] = $config;
                }
            }
            
            // Dispatch events for each kind of editable values
            $fieldsResponse = new BL_CustomGrid_Object(array('fields' => $fields));
            $attributesResponse = new BL_CustomGrid_Object(array('attributes' => $attributes));
            $attributeFieldsResponse = new BL_CustomGrid_Object(array('attribute_fields' => $attributeFields));
            
            Mage::dispatchEvent(
                'blcg_grid_type_editable_fields',
                array(
                    'response'   => $fieldsResponse,
                    'type_model' => $this,
                    'block_type' => $blockType,
                )
            );
            
            Mage::dispatchEvent(
                'blcg_grid_type_editable_attributes',
                array(
                    'response'   => $attributesResponse,
                    'type_model' => $this,
                    'block_type' => $blockType,
                )
            );
            
            Mage::dispatchEvent(
                'blcg_grid_type_editable_attribute_fields',
                array(
                    'response'   => $attributeFieldsResponse,
                    'type_model' => $this,
                    'block_type' => $blockType,
                )
            );
            
            $editableValues = array(
                self::EDITABLE_TYPE_FIELD => $fieldsResponse->getFields(),
                self::EDITABLE_TYPE_ATTRIBUTE => $attributesResponse->getAttributes(),
                self::EDITABLE_TYPE_ATTRIBUTE_FIELD => $attributeFieldsResponse->getAttributeFields(),
            );
            
            $this->setData('editable_values/' . $blockType, $editableValues);
        }
        
        return !is_null($origin)
            ? (isset($editableValues[$origin]) ? $editableValues[$origin] : array())
            : $editableValues;
    }
    
    /**
     * Return editable fields configs
     * 
     * @param string $blockType Grid block type
     * @return array
     */
    public function getEditableFields($blockType)
    {
        return $this->getEditableValues($blockType, self::EDITABLE_TYPE_FIELD);
    }
    
    /**
     * Return editable attributes configs
     * 
     * @param string $blockType Grid block type
     * @return array
     */
    public function getEditableAttributes($blockType)
    {
        return $this->getEditableValues($blockType, self::EDITABLE_TYPE_ATTRIBUTE);
    }
    
    /**
     * Return editable attribute fields configs
     * 
     * @param string $blockType Grid block type
     * @return array
     */
    public function getEditableAttributeFields($blockType)
    {
        return $this->getEditableValues($blockType, self::EDITABLE_TYPE_ATTRIBUTE_FIELD);
    }
    
    /**
     * Return editable value config
     * 
     * @param string $blockType Grid block type
     * @param string $valueId Value ID
     * @param string $origin Value origin
     * @return BL_CustomGrid_Model_Grid_Edit_Config
     */
    public function getEditableValue($blockType, $valueId, $origin)
    {
        $editableValues = $this->getEditableValues($blockType, $origin);
        return (isset($editableValues[$valueId]) ? $editableValues[$valueId] : null);
    }
    
    /**
     * Return editable field config
     * 
     * @param string $blockType Grid block type
     * @param string $fieldId Field ID
     * @return BL_CustomGrid_Model_Grid_Edit_Config
     */
    public function getEditableField($blockType, $fieldId)
    {
        return $this->getEditableValue($blockType, $fieldId, self::EDITABLE_TYPE_FIELD);
    }
    
    /**
     * Return editable attribute config
     * 
     * @param string $blockType Grid block type
     * @param string $attributeCode Attribute code
     * @return BL_CustomGrid_Model_Grid_Edit_Config
     */
    public function getEditableAttribute($blockType, $attributeCode)
    {
        return $this->getEditableValue($blockType, $attributeCode, self::EDITABLE_TYPE_ATTRIBUTE);
    }
    
    /**
     * Return editable attribute field config
     * 
     * @param string $blockType Grid block type
     * @param string $fieldId Field ID
     * @return BL_CustomGrid_Model_Grid_Edit_Config
     */
    public function getEditableAttributeField($blockType, $fieldId)
    {
        return $this->getEditableValue($blockType, $fieldId, self::EDITABLE_TYPE_ATTRIBUTE_FIELD);
    }
    
    /**
     * Return whether given value is editable
     * 
     * @param string $blockType Grid block type
     * @param string $valueId Value ID
     * @param string $origin Value origin
     * @return bool
     */
    public function isEditableValue($blockType, $valueId, $origin)
    {
        return array_key_exists($valueId, $this->getEditableValues($blockType, $origin));
    }
    
    /**
     * Return whether given field is editable
     * 
     * @param string $blockType Grid block type
     * @param string $fieldId Field ID
     * @return bool
     */
    public function isEditableField($blockType, $fieldId)
    {
        return $this->isEditableValue($blockType, $fieldId, self::EDITABLE_TYPE_FIELD);
    }
    
    /**
     * Return whether given attribute is editable
     * 
     * @param string $blockType Grid block type
     * @param string $attributeCode Attribute code
     * @return bool
     */
    public function isEditableAttribute($blockType, $attributeCode)
    {
        return $this->isEditableValue($blockType, $attributeCode, self::EDITABLE_TYPE_ATTRIBUTE);
    }
    
    /**
     * Return whether given attribute field is editable
     * 
     * @param string $blockType Grid block type
     * @param string $fieldId Field ID
     * @return bool
     */
    public function isEditableAttributeField($blockType, $fieldId)
    {
        return $this->isEditableValue($blockType, $fieldId, self::EDITABLE_TYPE_ATTRIBUTE_FIELD);
    }
    
    /**
     * Apply editable values configs to given grid columns
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Model_Grid_Column[] $columns Grid columns
     * @return BL_CustomGrid_Model_Grid_Column[]
     */
    public function applyEditConfigsToColumns($blockType, array $columns)
    {
        foreach ($columns as $columnBlockId => $column) {
            $editConfig = null;
            $hasStoreId = false;
            
            if ($column->isAttribute()) {
                $editConfig = $this->getEditableAttribute($blockType, $column->getIndex());
                $hasStoreId = $column->hasStoreId();
            } elseif (!$column->isCustom()) {
                if (!$editConfig = $this->getEditableField($blockType, $columnBlockId)) {
                    $editConfig = $this->getEditableAttributeField($blockType, $columnBlockId);
                }
            }
            
            if ($editConfig instanceof BL_CustomGrid_Model_Grid_Edit_Config) {
                $editConfig = clone $editConfig;
                
                if ($hasStoreId && !$editConfig->hasData('column_params/column_store_id')) {
                    // Apply column's store ID to allow editing values for the corresponding store view
                    $editConfig->setData('column_params/column_store_id', $column->getStoreId());
                }
                
                $editConfig->setData('column_params/column_id', $column->getId());
                $column->setEditConfig($editConfig);
            } else {
                $column->setEditConfig(false);
            }
        }
        return $columns;
    }
    
    /**
     * Return value edit additional parameters
     * 
     * @param string $blockType Grid block type
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return array
     */
    public function getAdditionalEditParams($blockType, Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        return array();
    }
    
    /**
     * Extract edit values from given request
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited value config
     * @param Mage_Core_Controller_Request_Http $request Request object
     * @return array
     */
    protected function _extractRequestEditValues(
        $blockType,
        BL_CustomGrid_Object $config,
        Mage_Core_Controller_Request_Http $request
    ) {
        $idsKey = $config->getDataSetDefault('config/ids_key', 'identifiers');
        $valuesKey = $config->getData('config/values_key', 'values');
        $additionalKey = $config->getData('config/additional_key', 'additional');
        $usedKeys = array_flip(array($idsKey, $valuesKey, $additionalKey));
        
        $params = array(
            'ids'        => $request->getParam($idsKey, array()),
            'values'     => $request->getParam($valuesKey, array()),
            'additional' => $request->getParam($additionalKey, array()),
            'global'     => array_diff_key($request->getParams(), $usedKeys),
        );
        
        return array_map(create_function('$v', 'return (is_array($v) ? $v : array());'), $params);
    }
    
    /**
     * Return entity identifiers keys
     * 
     * @param string $blockType Grid block type
     * @return string[]
     */
    protected function _getEntityRowIdentifiersKeys($blockType)
    {
        return array('id');
    }
    
    /**
     * Return entity identifier from given row
     * 
     * @param string $blockType Grid block type
     * @param Varien_Object $row Entity row
     * @param string $key Identifier key
     * @return mixed
     */
    protected function _getEntityRowIdentifier($blockType, Varien_Object $row, $key)
    {
        return $row->getData($key);
    }
    
    /**
     * Return entity identifiers from given row
     * 
     * @param string $blockType Grid block type
     * @param Varien_Object $row Entity row
     * @return array
     */
    public function getEntityRowIdentifiers($blockType, Varien_Object $row)
    {
        $identifiers = array();
        
        foreach ($this->_getEntityRowIdentifiersKeys($blockType) as $key) {
            $identifiers[$key] = $this->_getEntityRowIdentifier($blockType, $row, $key);
        }
        
        return $identifiers;
    }
    
    /**
     * Return edited entity identifiers
     * (null if none was found in cirrent request parameters, single value if appropriate, else array)
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited value config
     * @param array $params Edit parameters
     * @return mixed
     */
    protected function _getEditedEntityIdentifiers($blockType, BL_CustomGrid_Object $config, array $params)
    {
        $identifiers = array();
        
        if (isset($params['ids'])) {
            foreach ($this->_getEntityRowIdentifiersKeys($blockType) as $key) {
                if (isset($params['ids'][$key])) {
                    $identifiers[$key] = $params['ids'][$key];
                }
            }
        }
        if (empty($identifiers)) {
            $identifiers = null;
        } elseif (count($identifiers) == 1) {
            $identifiers = end($identifiers);
        }
        
        return $identifiers;
    }
    
    /**
     * Load edited entity
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited value config
     * @param array $params Edit parameters
     * @param mixed $entityId Entity ID
     * @return mixed
     */
    protected function _loadEditedEntity($blockType, BL_CustomGrid_Object $config, array $params, $entityId)
    {
        return null;
    }
    
    /**
     * Reload edited entity
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited value config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @return mixed
     */
    protected function _reloadEditedEntity($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        return $entity->load($entity->getId());
    }
    
    /**
     * Return whether edited entity has been successfully loaded
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited value config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @param mixed $entityId Edited entity ID
     * @return bool
     */
    protected function _isEditedEntityLoaded(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        $entityId
    ) {
        return (is_object($entity) ? (bool) $entity->getId() : false);
    }
    
    /**
     * Return registry keys where to put edited entity
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited value config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @return string[]
     */
    protected function _getEditedEntityRegistryKeys($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        return array();
    }
    
    /**
     * Register edited entity
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited value config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @return BL_CustomGrid_Model_Grid_Type_Abstract
     */
    protected function _registerEditedEntity($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        foreach ($this->_getEditedEntityRegistryKeys($blockType, $config, $params, $entity) as $key) {
            Mage::register($key, $entity);
        }
        return $this;
    }
    
    /**
     * Return edited entity name
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited value config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @return string
     */
    protected function _getLoadedEntityName($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        return $entity->getName();
    }
    
    /**
     * Return whether given field is editable for given entity
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited value config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @return bool
     */
    protected function _checkEntityEditableField($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        return true;
    }
    
    /**
     * Return whether given attribute is editable for given entity
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited value config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @return bool
     */
    protected function _checkEntityEditableAttribute(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity
    ) {
        return true;
    }
    
    /**
     * Return whether given value is editable for given entity
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited value config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @return bool
     */
    protected function _checkEntityEditableValue($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        $editable = false;
        
        if ($config->getOrigin() == self::EDITABLE_TYPE_FIELD) {
            $editable = $this->_checkEntityEditableField($blockType, $config, $params, $entity);
        } elseif ($config->getOrigin() == self::EDITABLE_TYPE_ATTRIBUTE) {
            $editable = $this->_checkEntityEditableAttribute($blockType, $config, $params, $entity);
        }
        
        return $editable;
    }
    
    /**
     * Return field edit block instance
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited field config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @return Mage_Core_Block_Abstract
     */
    protected function _getFieldEditBlock($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        $editBlockType = $config->getData('config/edit_block_type');
        
        if (strpos($editBlockType, '/') === false) {
            $editBlockType = 'customgrid/widget_grid_editor_form_static_' . $editBlockType;
        }
        
        return $this->_getLayout()
            ->createBlock(
                $editBlockType,
                '',
                array(
                    'edited_entity'      => $entity,
                    'edited_entity_name' => $this->_getLoadedEntityName($blockType, $config, $params, $entity),
                    'edited_value'       => $config,
                    'edit_config'        => $config->getConfig(),
                    'edited_in_grid'     => (bool) $config->getDataSetDefault('config/in_grid', false),
                    'edit_params'        => $params,
                    'grid_block_type'    => $blockType,
                )
            );
    }
    
    /**
     * Prepare field edit block instance before display
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited field config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @param Mage_Core_Block_Abstract $editBlock Edit block instance
     * @return BL_CustomGrid_Model_Grid_Type_Abstract
     */
    protected function _prepareFieldEditBlock(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        Mage_Core_Block_Abstract $editBlock
    ) {
        return $this;
    }
    
    /**
     * Return layout handles to apply for given field edit
     * (only used for external edit)
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited field config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @return string[]
     */
    protected function _getFieldEditLayoutHandles($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        return $config->getDataSetDefault('config/layout_handles', array());
    }
    
    /**
     * Return attribute edit block instance
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited attribute config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @return Mage_Core_Block_Abstract
     */
    protected function _getAttributeEditBlock($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        $editBlockType = $config->getData('config/edit_block_type');
        
        if (strpos($editBlockType, '/') === false) {
            $editBlockType = 'customgrid/widget_grid_editor_form_attribute_' . $editBlockType;
        }
        
        return $this->_getLayout()
            ->createBlock(
                $editBlockType,
                '',
                array(
                    'edited_entity'      => $entity,
                    'edited_entity_name' => $this->_getLoadedEntityName($blockType, $config, $params, $entity),
                    'edited_attribute'   => $config->getData('config/attribute'),
                    'edited_value'       => $config,
                    'edit_config'        => $config->getConfig(),
                    'edited_in_grid'     => (bool) $config->getDataSetDefault('config/in_grid', false),
                    'edit_params'        => $params,
                    'grid_block_type'    => $blockType,
                )
            );
    }
    
    /**
     * Prepare attribute edit block instance before display
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited attribute config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @param Mage_Core_Block_Abstract $editBlock Edit block instance
     * @return BL_CustomGrid_Model_Grid_Type_Abstract
     */
    protected function _prepareAttributeEditBlock(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        Mage_Core_Block_Abstract $editBlock
    ) {
        return $this;
    }
    
    /**
     * Return layout handles to apply for given attribute edit
     * (only used for external edit)
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited attribute config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @return string[]
     */
    protected function _getAttributeEditLayoutHandles($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        return $config->getDataSetDefault('config/layout_handles', array());
    }
    
    /**
     * Return value edit block instance
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited value config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @return Mage_Core_Block_Abstract
     */
    protected function _getValueEditBlock($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        $editBlock = null;
        
        if ($config->getOrigin() == self::EDITABLE_TYPE_FIELD) {
            if ($editBlock = $this->_getFieldEditBlock($blockType, $config, $params, $entity)) {
                $this->_prepareFieldEditBlock($blockType, $config, $params, $entity, $editBlock);
            }
        } elseif ($config->getOrigin() == self::EDITABLE_TYPE_ATTRIBUTE) {
            if ($editBlock = $this->_getAttributeEditBlock($blockType, $config, $params, $entity)) {
                $this->_prepareAttributeEditBlock($blockType, $config, $params, $entity, $editBlock);
            }
        }
        
        return $editBlock;
    }
    
    /**
     * Return layout handles to apply for given value edit
     * (only used for external edit)
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited value config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @return string[]
     */
    protected function _getValueEditLayoutHandles($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        $handles = array();
        
        if ($config->getOrigin() == self::EDITABLE_TYPE_FIELD) {
            $handles = $this->_getFieldEditLayoutHandles($blockType, $config, $params, $entity);
        } elseif ($config->getOrigin() == self::EDITABLE_TYPE_ATTRIBUTE) {
            $handles = $this->_getAttributeEditLayoutHandles($blockType, $config, $params, $entity);
        }
        
        return $handles;
    }
    
    /**
     * Return the ACL permissions required to edit values
     * 
     * @param string $blockType Grid block type
     * @return string|array
     */
    protected function _getEditRequiredAclPermissions($blockType)
    {
        return null;
    }
    
    /**
     * Check if user has ACL permissions to edit values
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param Mage_Adminhtml_Block_Widget_Grid|null $gridBlock Grid block instance
     * @param array $params Edit parameters (by default, set when grid block is not given)
     * @return bool
     */
    public function checkUserEditPermissions(
        $blockType,
        BL_CustomGrid_Model_Grid $gridModel,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock = null,
        array $params = array()
    ) {
        $isAllowed = $gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_COLUMNS_VALUES);
        
        if ($isAllowed && !is_null($permissions = $this->_getEditRequiredAclPermissions($blockType))) {
            $session = Mage::getSingleton('admin/session');
            $permissions = (is_array($permissions) ? $permissions : array($permissions));
            
            foreach ($permissions as $permission) {
                if (!$session->isAllowed($permission)) {
                    $isAllowed = false;
                    break;
                }
            }
        }
        
        return $isAllowed;
    }
    
    /**
     * Check if given value edit is allowed
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited value config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @return bool
     */
    protected function _canEditValue(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        BL_CustomGrid_Model_Grid $gridModel
    ) {
        if (isset($params['additional'])
            && isset($params['additional']['column_id'])
            && ($column = $gridModel->getColumnById($params['additional']['column_id']))
            && $column->isEditAllowed()) {
            return $this->checkUserEditPermissions($blockType, $gridModel, null, $params);
        }
        return false;
    }
    
    /**
     * Return value edit block corresponding to given value and request,
     * either as instance or HTML output
     * 
     * @param string $blockType Grid block type
     * @param string $valueId Value ID
     * @param string $origin Value origin
     * @param Mage_Core_Controller_Request_Http $request Request object
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param bool $asHtml Whether block HTML oputput should be returned instead of the block instance
     * @param bool $addLayoutHandles Whether suitable layout handles should be applied
     * @return mixed
     */
    public function getValueEditBlock(
        $blockType,
        $valueId,
        $origin,
        Mage_Core_Controller_Request_Http $request,
        BL_CustomGrid_Model_Grid $gridModel,
        $asHtml=true,
        $addLayoutHandles=false
    ) {
        if (!$config = $this->getEditableValue($blockType, $valueId, $origin)) {
            Mage::throwException($this->getHelper()->__('This value is not editable'));
        }
        
        $editConfig = new BL_CustomGrid_Object(
            array(
                'value_id' => $valueId,
                'origin'   => $origin, 
                'config'   => $config,
            )
        );
        
        $editParams = $this->_extractRequestEditValues($blockType, $editConfig, $request);
        $entityId   = $this->_getEditedEntityIdentifiers($blockType, $editConfig, $editParams);
        $entity     = $this->_loadEditedEntity($blockType, $editConfig, $editParams, $entityId);
        
        if (!$this->_isEditedEntityLoaded($blockType, $editConfig, $editParams, $entity, $entityId)) {
            Mage::throwException($this->_getBaseHelper()->__('The edited entity could not be loaded'));
        } else {
            $this->_registerEditedEntity($blockType, $editConfig, $editParams, $entity);
        }
        if (!$this->_canEditValue($blockType, $editConfig, $editParams, $entity, $gridModel)
            || !$this->_checkEntityEditableValue($blockType, $editConfig, $editParams, $entity)) {
            Mage::throwException($this->_getBaseHelper()->__('This value is not editable'));
        }
        if (!$editBlock = $this->_getValueEditBlock($blockType, $editConfig, $editParams, $entity)) {
            Mage::throwException($this->_getBaseHelper()->__('The value edit block could not be retrieved'));
        }
        if ($addLayoutHandles) {
            /*
            Use our observer to add layout handles just before layout load :
            it will ensure that "default" handle (and even most of others, if not all)
            will be handled before our own ones, which is what we actually need
            */
            $layoutHandles = $this->_getValueEditLayoutHandles($blockType, $editConfig, $editParams, $entity);
            
            if (!is_array($layoutHandles)) {
                $layoutHandles = array($layoutHandles);
            }
            
            Mage::getSingleton('customgrid/observer')->registerAdditionalLayoutHandles($layoutHandles);
        }
        
        return ($asHtml ? $editBlock->toHtml() : $editBlock);
    }
    
    /**
     * Dispatch given save event
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited field config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @param mixed $value Edited field value
     * @param string $eventName Dispatched event name
     * @param array $additional Additional parameters
     * @return BL_CustomGrid_Model_Grid_Type_Abstract
     */
    protected function _dispatchSaveEvent(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        $value,
        $eventName,
        array $additional = array()
    ) {
        $eventData = array_merge(
            $additional,
            array(
                'type_model'  => $this,
                'block_type'  => $blockType,
                'edit_config' => $config,
                'edit_params' => $params,
                'entity'      => $entity,
                'value'       => $value,
            )
        );
        
        Mage::dispatchEvent($eventName, $eventData);
        return $this;
    }
    
    /**
     * Filter given edited value
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited value config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @param mixed $value Value to filter
     * @param string $filterType Kind of filter to apply
     * @param array $filterParams Filter parameters
     * @return mixed
     */
    protected function _filterEditedValue(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        $value,
        $filterType,
        array $filterParams
    ) {
        if ($filterType == 'date') {
            $value = Mage::helper('customgrid/editor')->filterDateValue($value);
        }
        return $value;
    }
    
    /**
     * Return edited field value
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited field config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @param string $formName Value form name
     * @return mixed
     */
    protected function _getEditedFieldValue($blockType, BL_CustomGrid_Object $config, array $params, $entity, $formName)
    {
        if (isset($params['values']) && isset($params['values'][$formName])) {
            return $params['values'][$formName];
        }
        Mage::throwException($this->_getBaseHelper()->__('No value given'));
    }
    
    /**
     * Filter edited field value
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited field config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @param mixed $value Edited field value
     * @return mixed
     */
    protected function _filterEditedFieldValue($blockType, BL_CustomGrid_Object $config, array $params, $entity, $value)
    {
        if (!$filterType = $config->getData('config/filter_type')) {
            $filterType = $config->getData('config/type');
        }
        $filterParams = $config->getDataSetDefault('config/filter_params', array());
        return $this->_filterEditedValue($blockType, $config, $params, $entity, $value, $filterType, $filterParams);
    }
    
    /**
     * Do some actions before field value is applied to edited entity (such as filtering when needed)
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited field config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @param mixed $value Edited field value
     * @return BL_CustomGrid_Model_Grid_Type_Abstract
     */
    protected function _beforeApplyEditedFieldValue(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        &$value
    ) {
        if ($config->getData('config/must_filter')) {
            $value = $this->_filterEditedFieldValue($blockType, $config, $params, $entity, $value);
        }
        return $this;
    }
    
    /**
     * Apply edited field value to edited entity
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited field config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @param mixed $value Edited field value
     * @return BL_CustomGrid_Model_Grid_Type_Abstract
     */
    protected function _applyEditedFieldValue($blockType, BL_CustomGrid_Object $config, array $params, $entity, $value)
    {
        $entity->setData($config->getData('config/field_name'), $value);
        return $this;
    }
    
    /**
     * Do some actions before field value is saved
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited field config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @param mixed $value Edited field value
     * @return BL_CustomGrid_Model_Grid_Type_Abstract
     */
    protected function _beforeSaveEditedFieldValue(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        $value
    ) {
        $this->_dispatchSaveEvent(
            $blockType,
            $config,
            $params,
            $entity,
            $value,
            'blcg_grid_type_before_save_field_value'
        );
        return $this;
    }
    
    /**
     * Save edited entity
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited field config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @param mixed $value Edited field value
     * @return bool
     */
    protected function _saveEditedFieldValue($blockType, BL_CustomGrid_Object $config, array $params, $entity, $value)
    {
        $entity->save();
        return true;
    }
    
    /**
     * Do some actions after field value is saved (such as reloading when needed)
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Model_Object $config Edited field config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @param mixed $value Edited field value
     * @param bool $result Save result
     * @return BL_CustomGrid_Model_Grid_Type_Abstract
     */
    protected function _afterSaveEditedFieldValue(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        $value,
        $result
    ) {
        if ($config->getData('config/render_reload')) {
            $this->_reloadEditedEntity($blockType, $config, $params, $entity);
        }
        
        return $this->_dispatchSaveEvent(
            $blockType,
            $config,
            $params,
            $entity,
            $value,
            'blcg_grid_type_after_save_field_value',
            array('result' => $result)
        );
    }
    
    /**
     * Return edited attribute value
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited attribute config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @param string $formName Value form name
     * @return mixed
     */
    protected function _getEditedAttributeValue(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        $formName
    ) {
        if (isset($params['values']) && isset($params['values'][$formName])) {
            return $params['values'][$formName];
        }
        Mage::throwException($this->_getBaseHelper()->__('No value given'));
    }
    
    /**
     * Filter edited attribute value
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited attribute config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @param mixed $value Edited attribute value
     * @return mixed
     */
    protected function _filterEditedAttributeValue(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        $value
    ) {
        if (!$filterType = $config->getData('config/filter_type')) {
            $filterType = $config->getData('config/attribute')
                ->getFrontend()
                ->getInputType();
        }
        $filterParams = $config->getDataSetDefault('config/filter_params', array());
        return $this->_filterEditedValue($blockType, $config, $params, $entity, $value, $filterType, $filterParams);
    }
    
    /**
     * Do some actions before attribute value is applied to edited entity (such as filtering when needed)
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited attribute config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @param mixed $value Edited attribute value
     * @return BL_CustomGrid_Model_Grid_Type_Abstract
     */
    protected function _beforeApplyEditedAttributeValue(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        &$value
    ) {
        if ($config->getData('config/must_filter')) {
            $value = $this->_filterEditedAttributeValue($blockType, $config, $params, $entity, $value);
        }
        return $this;
    }
    
    /**
     * Apply edited attribute value to edited entity
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited attribute config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @param mixed $value Edited attribute value
     * @return BL_CustomGrid_Model_Grid_Type_Abstract
     */
    protected function _applyEditedAttributeValue(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        $value
    ) {
        $entity->setData($config->getData('config/attribute')->getAttributeCode(), $value);
        return $this;
    }
    
    /**
     * Do some actions before attribute value is saved
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited attribute config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @param mixed $value Edited attribute value
     * @return BL_CustomGrid_Model_Grid_Type_Abstract
     */
    protected function _beforeSaveEditedAttributeValue(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        $value
    ) {
        return $this->_dispatchSaveEvent(
            $blockType,
            $config,
            $params,
            $entity,
            $value,
            'blcg_grid_type_before_save_attribute_value'
        );
    }
    
    /**
     * Save edited entity
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Model_Object $config Edited attribute config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @param mixed $value Edited attribute value
     * @return bool
     */
    protected function _saveEditedAttributeValue(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        $value
    ) {
        $entity->save();
        return true;
    }
    
    /**
     * Do some actions after field value is saved (such as reloading when needed)
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited field config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @param mixed $value Edited field value
     * @param bool $result Save result
     * @return BL_CustomGrid_Model_Grid_Type_Abstract
     */
    protected function _afterSaveEditedAttributeValue(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        $value,
        $result
    ) {
        if ($config->getData('config/render_reload')) {
            $this->_reloadEditedEntity($blockType, $config, $params, $entity);
        }
        
        return $this->_dispatchSaveEvent(
            $blockType,
            $config,
            $params,
            $entity,
            $value,
            'blcg_grid_type_after_save_attribute_value',
            array('result' => $result)
        );
    }
    
    /**
     * Save edited value
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited value config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @return bool
     */
    protected function _saveEditedValue($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        $result = false;
        
        if ($config->getOrigin() == self::EDITABLE_TYPE_FIELD) {
            $value = $this->_getEditedFieldValue(
                $blockType,
                $config,
                $params,
                $entity,
                $config->getData('config/form/name')
            );
            
            $result = $this->_beforeApplyEditedFieldValue($blockType, $config, $params, $entity, $value)
                ->_applyEditedFieldValue($blockType, $config, $params, $entity, $value)
                ->_beforeSaveEditedFieldValue($blockType, $config, $params, $entity, $value)
                ->_saveEditedFieldValue($blockType, $config, $params, $entity, $value);
            
            $this->_afterSaveEditedFieldValue($blockType, $config, $params, $entity, $value, $result);
            
        } elseif ($config->getOrigin() == self::EDITABLE_TYPE_ATTRIBUTE) {
            $value = $this->_getEditedAttributeValue(
                $blockType,
                $config,
                $params,
                $entity,
                $config->getData('config/attribute')->getAttributeCode()
            );
            
            $result = $this->_beforeApplyEditedAttributeValue($blockType, $config, $params, $entity, $value)
                ->_applyEditedAttributeValue($blockType, $config, $params, $entity, $value)
                ->_beforeSaveEditedAttributeValue($blockType, $config, $params, $entity, $value)
                ->_saveEditedAttributeValue($blockType, $config, $params, $entity, $value);
            
            $this->_afterSaveEditedAttributeValue($blockType, $config, $params, $entity, $value, $result);
            
        }
        
        return $result;
    }
    
    /**
     * Return saved field value, ready for a future rendering
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited field config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @return mixed
     */
    protected function _getSavedFieldValueForRender($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        return $entity->getData($config->getData('config/field_name'));
    }
    
    /**
     * Return renderer block instance for saved field value
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited field config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @param mixed $value Prepared field value
     * @return Mage_Core_Block_Abstract
     */
    protected function _getSavedFieldValueRendererBlock(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        $value
    ) {
        $renderBlockType = $config->getData('config/render_block_type');
        
        if (strpos($renderBlockType, '/') === false) {
            $renderBlockType = 'customgrid/widget_grid_editor_renderer_static_' . $renderBlockType;
        }
        
        return $this->_getLayout()
        ->createBlock(
            $renderBlockType,
            '',
            array(
                'edited_entity'    => $entity,
                'edited_value'     => $config,
                'edit_config'      => $config->getConfig(),
                'renderable_value' => $value,
                'edit_params'      => $params,
                'grid_block_type'  => $blockType,
            )
        );
    }
    
    /**
     * Prepare field value renderer block before display
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited field config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @param mixed $value Prepared field value
     * @param Mage_Core_Block_Abstract $rendererBlock Renderer block instance
     * @return BL_CustomGrid_Model_Grid_Type_Abstract
     */
    protected function _prepareSavedFieldValueRendererBlock(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        $value,
        Mage_Core_Block_Abstract $rendererBlock
    ) {
        return $this;
    }
    
    /**
     * Return saved field value, adapted for a render directly in the grid
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited field config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @return mixed
     */
    protected function _getRenderableSavedFieldValue($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        $value = $this->_getSavedFieldValueForRender($blockType, $config, $params, $entity);
        
        if ($rendererBlock = $this->_getSavedFieldValueRendererBlock($blockType, $config, $params, $entity, $value)) {
            $this->_prepareSavedFieldValueRendererBlock($blockType, $config, $params, $entity, $value, $rendererBlock);
            return $rendererBlock->toHtml();
        }
        
        return $this->_getBaseHelper()->__('<em>Updated</em>');
    }
    
    /**
     * Return saved attribute value, ready for a future rendering
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited attribute config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @return mixed
     */
    protected function _getSavedAttributeValueForRender(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity
    ) {
        return $config->getData('config/attribute')
            ->getFrontend()
            ->getValue($entity);
    }
    
    /**
     * Return renderer block instance for saved attribute value
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited attribute config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @param mixed $value Prepared attribute value
     * @return Mage_Core_Block_Abstract
     */
    protected function _getSavedAttributeValueRendererBlock(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        $value
    ) {
        $renderBlockType = $config->getData('config/render_block_type');
        
        if (strpos($renderBlockType, '/') === false) {
            $renderBlockType = 'customgrid/widget_grid_editor_renderer_attribute_' . $renderBlockType;
        }
        
        return $this->_getLayout()
            ->createBlock(
                $renderBlockType,
                '',
                array(
                    'edited_entity'    => $entity,
                    'edited_attribute' => $config->getData('config/attribute'),
                    'edited_value'     => $config,
                    'edit_config'      => $config->getConfig(),
                    'renderable_value' => $value,
                    'edit_params'      => $params,
                    'grid_block_type'  => $blockType,
                )
            );
    }
    
    /**
     * Prepare attribute value renderer block before display
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited attribute config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @param mixed $value Prepared attribute value
     * @param Mage_Core_Block_Abstract $rendererBlock Renderer block instance
     * @return BL_CustomGrid_Model_Grid_Type_Abstract
     */
    protected function _prepareSavedAttributeValueRendererBlock(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        $value,
        Mage_Core_Block_Abstract $rendererBlock
    ) {
        return $this;
    }
    
    /**
     * Return saved attribute value, adapted for a render directly in the grid
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited attribute config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @return mixed
     */
    protected function _getRenderableSavedAttributeValue(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity
    ) {
        $value = $this->_getSavedAttributeValueForRender($blockType, $config, $params, $entity);
        
        if ($block = $this->_getSavedAttributeValueRendererBlock($blockType, $config, $params, $entity, $value)) {
            $this->_prepareSavedAttributeValueRendererBlock($blockType, $config, $params, $entity, $value, $block);
            return $block->toHtml();
        }
        
        return $this->_getBaseHelper()->__('<em>Updated</em>');
    }
    
    /**
     * Return saved value, adapted for a render directly in the grid
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited value config
     * @param array $params Edit parameters
     * @param mixed $entity Edited entity
     * @return mixed
     */
    protected function _getRenderableSavedValue($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        $value = '';
        
        if ($config->getOrigin() == self::EDITABLE_TYPE_FIELD) {
            $value = $this->_getRenderableSavedFieldValue($blockType, $config, $params, $entity);
        } elseif ($config->getOrigin() == self::EDITABLE_TYPE_ATTRIBUTE) {
            $value = $this->_getRenderableSavedAttributeValue($blockType, $config, $params, $entity);
        }
        
        return $value;
    }
    
    /**
     * Save edited value depending on given value and request, and return a corresponding renderable value
     * 
     * @param string $blockType Grid block type
     * @param string $valueId Value ID
     * @param string $origin Value origin
     * @param Mage_Core_Controller_Request_Http $request Request object
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @return mixed
     */
    public function saveEditedValue(
        $blockType,
        $valueId,
        $origin,
        Mage_Core_Controller_Request_Http $request,
        BL_CustomGrid_Model_Grid $gridModel
    ) {
        if (!$config = $this->getEditableValue($blockType, $valueId, $origin)) {
            Mage::throwException($this->_getBaseHelper()->__('This value is not editable'));
        }
        
        $editConfig = new BL_CustomGrid_Object(
            array(
                'value_id' => $valueId,
                'origin'   => $origin, 
                'config'   => $config,
            )
        );
        
        $editParams  = $this->_extractRequestEditValues($blockType, $editConfig, $request);
        $entityId    = $this->_getEditedEntityIdentifiers($blockType, $editConfig, $editParams);
        $entity      = $this->_loadEditedEntity($blockType, $editConfig, $editParams, $entityId);
        
        if (!$this->_isEditedEntityLoaded($blockType, $editConfig, $editParams, $entity, $entityId)) {
            Mage::throwException($this->_getBaseHelper()->__('The edited entity could not be loaded'));
        }
        
        $this->_registerEditedEntity($blockType, $editConfig, $editParams, $entity);
        
        if (!$this->_canEditValue($blockType, $editConfig, $editParams, $entity, $gridModel)
            || !$this->_checkEntityEditableValue($blockType, $editConfig, $editParams, $entity)) {
            Mage::throwException($this->_getBaseHelper()->__('This value is not editable'));
        }
        if (!$this->_saveEditedValue($blockType, $editConfig, $editParams, $entity)) {
            Mage::throwException($this->_getBaseHelper()->__('The value could not be saved'));
        }
        
        return $this->_getRenderableSavedValue($blockType, $editConfig, $editParams, $entity);
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
