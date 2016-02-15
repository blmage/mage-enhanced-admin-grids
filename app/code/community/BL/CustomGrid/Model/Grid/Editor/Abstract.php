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

abstract class BL_CustomGrid_Model_Grid_Editor_Abstract extends BL_CustomGrid_Object
{
    const EDITABLE_TYPE_FIELD           = 'field';
    const EDITABLE_TYPE_ATTRIBUTE       = 'attribute';
    const EDITABLE_TYPE_ATTRIBUTE_FIELD = 'attribute_field';
    const EDITABLE_TYPE_CUSTOM_COLUMN   = 'custom_column';
    
    const WORKER_TYPE_VALUE_CONFIG_BUILDER = 'value_config_builder';
    const WORKER_TYPE_KICKSTARTER          = 'kickstarter';
    const WORKER_TYPE_ENTITY_LOADER        = 'entity_loader';
    const WORKER_TYPE_ENTITY_UPDATER       = 'entity_updater';
    const WORKER_TYPE_SENTRY               = 'sentry';
    const WORKER_TYPE_VALUE_FORM_RENDERER  = 'value_form_renderer';
    const WORKER_TYPE_VALUE_RENDERER       = 'value_renderer';
    
    /**
     * Return the class code of the usable worker model from the given type
     * 
     * @param string $type Worker type
     * @return string
     */
    protected function _getWorkerModelClassCode($type)
    {
        $dataKey = 'worker_model_class_code/' . $type;
        
        if (!$this->hasData($dataKey)) {
            $this->setData($dataKey, 'customgrid/grid_editor_' . $type);
        }
        
        return $this->getData($dataKey);
    }
    
    /**
     * Return the worker model of the given type
     * 
     * @param string $type Worker type
     * @return BL_CustomGrid_Model_Grid_Editor_Worker_Abstract
     */
    protected function _getWorker($type)
    {
        if (!$this->hasData($type)) {
            $worker = Mage::getModel($this->_getWorkerModelClassCode($type));
            
            if (!$worker instanceof BL_CustomGrid_Model_Grid_Editor_Worker_Abstract) {
                Mage::throwException(
                    'Editor workers must be instances of BL_CustomGrid_Model_Grid_Editor_Worker_Abstract'
                    . '("' . $type . '")'
                );
            }
            
            /** @var BL_CustomGrid_Model_Grid_Editor_Worker_Abstract $worker */
            $worker->setEditor($this);
            $this->setData($type, $worker);
        }
        return $this->_getData($type);
    }
    
    /**
     * Return the config builder model, usable to build editable values configs
     * 
     * @return BL_CustomGrid_Model_Grid_Editor_Value_Config_Builder
     */
    public function getValueConfigBuilder()
    {
        return $this->_getWorker(self::WORKER_TYPE_VALUE_CONFIG_BUILDER);
    }
    
    /**
     * Return the kickstarter model, usable to parse edit requests and to initialize everything necessary
     * 
     * @return BL_CustomGrid_Model_Grid_Editor_Kickstarter
     */
    public function getKickstarter()
    {
        return $this->_getWorker(self::WORKER_TYPE_KICKSTARTER);
    }
    
    /**
     * Return the entity loader model, usable to parse entity identifiers from rows and to load edited entities
     * 
     * @return BL_CustomGrid_Model_Grid_Editor_Entity_Loader
     */
    public function getEntityLoader()
    {
        return $this->_getWorker(self::WORKER_TYPE_ENTITY_LOADER);
    }
    
    /**
     * Return the entity updater model, usable to update entity values
     * 
     * @return BL_CustomGrid_Model_Grid_Editor_Entity_Updater
     */
    public function getEntityUpdater()
    {
        return $this->_getWorker(self::WORKER_TYPE_ENTITY_UPDATER);
    }
    
    /**
     * Return the sentry model, usable to check user edit permissions
     * 
     * @return BL_CustomGrid_Model_Grid_Editor_Sentry
     */
    public function getSentry()
    {
        return $this->_getWorker(self::WORKER_TYPE_SENTRY);
    }
    
    /**
     * Return the value form renderer model, usable to render value forms
     * 
     * @return BL_CustomGrid_Model_Grid_Editor_Value_Form_Renderer
     */
    public function getValueFormRenderer()
    {
        return $this->_getWorker(self::WORKER_TYPE_VALUE_FORM_RENDERER);
    }
    
    /**
     * Return the value renderer model, usable to render properly formatted new values
     * 
     */
    public function getValueRenderer()
    {
        return $this->_getWorker(self::WORKER_TYPE_VALUE_RENDERER);
    }
    
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
     * Return the editor helper
     *
     * @return BL_CustomGrid_Helper_Editor
     */
    public function getEditorHelper()
    {
        return Mage::helper('customgrid/editor');
    }
    
    /**
     * Set the current grid type model
     * 
     * @param BL_CustomGrid_Model_Grid_Type_Abstract $gridTypeModel Grid type model to set as current
     * @return BL_CustomGrid_Model_Grid_Editor_Abstract
     */
    public function setGridTypeModel(BL_CustomGrid_Model_Grid_Type_Abstract $gridTypeModel)
    {
        return $this->setData('grid_type_model', $gridTypeModel);
    }
    
    /**
     * Return the current grid type model
     * 
     * @return BL_CustomGrid_Model_Grid_Type_Abstract
     */
    public function getGridTypeModel()
    {
        if ((!$gridTypeModel = $this->_getData('grid_type_model'))
            || (!$gridTypeModel instanceof BL_CustomGrid_Model_Grid_Type_Abstract)) {
            Mage::throwException('Invalid grid type model for the editor');
        }
        return $gridTypeModel;
    }
    
    /**
     * Return the base config values
     * 
     * @return array
     */
    protected function _getBaseConfigData()
    {
        return array();
    }
    
    /**
     * Return the base config object containing the base config values, potentially expanded by third-party data
     * 
     * @return BL_CustomGrid_Object
     */
    public function getBaseConfig()
    {
        if (!$this->hasData('base_config')) {
            $baseConfig = new BL_CustomGrid_Object($this->_getBaseConfigData());
            $response   = new BL_CustomGrid_Object(array('config' => array()));
            
            Mage::dispatchEvent(
                'blcg_grid_editor_base_config',
                array(
                    'response'     => $response,
                    'type_model'   => $this->getGridTypeModel(),
                    'editor_model' => $this,
                )
            );
            
            $baseConfig->mergeData((array) $response->getData('config'));
            $this->setData('base_config', $baseConfig);
        }
        return $this->_getData('base_config');
    }
    
    /**
     * Return whether the given value is an instance of BL_CustomGrid_Model_Grid_Editor_Callback
     * 
     * @param mixed $value Checked value
     * @return bool
     */
    protected function _isCallbackObject($value)
    {
        return ($value instanceof BL_CustomGrid_Model_Grid_Editor_Callback);
    }
    
    /**
     * Arrange the given callbacks array by worker and action types, then by position,
     * and return the result
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Callback[] $callbacks Callbacks array to arrange
     * @return array
     */
    protected function _arrangeCallbacksArray(array $callbacks)
    {
        $arrangedCallbacks = array();
        $callbacks = array_filter($callbacks, array($this, '_isCallbackObject'));
        
        foreach ($callbacks as $callback) {
            $workerType = $callback->getWorkerType();
            $actionType = $callback->getActionType();
            $position   = $callback->getPosition();
            $arrangedCallbacks[$workerType][$actionType][$position][] = $callback;
        }
        
        return $arrangedCallbacks;
    }
    
    /**
     * Wrap the the given callable into a callback model configured with the given values
     * 
     * @param callable $callable Base callable
     * @param string $workerType Worker type
     * @param string $actionType Action type
     * @param string $position Callback position
     * @param int $priority Callback priority (lower are executed first)
     * @param bool $shouldStopAfter Whether to stop the execution of the next callbacks of the same kind after this one
     * @return BL_CustomGrid_Model_Grid_Editor_Callback
     */
    public function getCallbackFromCallable(
        $callable,
        $workerType,
        $actionType,
        $position,
        $priority,
        $shouldStopAfter = false
    ) {
        return new BL_CustomGrid_Model_Grid_Editor_Callback(
            array(
                'callable'    => $callable,
                'worker_type' => $workerType,
                'action_type' => $actionType,
                'position'    => $position,
                'priority'    => (int) $priority,
                'should_stop_after' => (bool) $shouldStopAfter,
            )
        );
    }
    
    /**
     * Wrap the the given callable into a callback model configured with the given values,
     * that will be given priority over the default callback during the main phase of the given action type
     * 
     * @param callable $callable Base callable
     * @param string $workerType Worker type
     * @param string $actionType Action type
     * @param bool $shouldStopAfter Whether to stop the execution of the next callbacks of the same kind after this one
     * @return BL_CustomGrid_Model_Grid_Editor_Callback
     */
    protected function _getInternalMainCallbackFromCallable(
        $callable,
        $workerType,
        $actionType,
        $shouldStopAfter = false
    ) {
        return $this->getCallbackFromCallable(
            $callable,
            $workerType,
            $actionType,
            BL_CustomGrid_Model_Grid_Editor_Callback::POSITION_MAIN,
            BL_CustomGrid_Model_Grid_Editor_Callback::PRIORITY_EDITOR_INTERNAL_HIGH,
            $shouldStopAfter
        );
    }
    
    /**
     * Return the base callbacks used by this editor model
     * 
     * @return BL_CustomGrid_Model_Grid_Editor_Callback[]
     */
    protected function _getBaseCallbacks()
    {
        return array();
    }
    
    /**
     * Return the usable base callbacks, arranged and optionally filtered by worker and action types
     * 
     * @param string $workerType Worker type
     * @param string $actionType Action type
     * @return array
     */
    public function getBaseCallbacks($workerType = null, $actionType = null)
    {
        if (!$this->hasData('base_callbacks')) {
            $callbacks = $this->_getBaseCallbacks();
            $response  = new BL_CustomGrid_Object(array('callbacks' => array()));
            
            Mage::dispatchEvent(
                'blcg_grid_editor_base_callbacks',
                array(
                    'response'     => $response,
                    'type_model'   => $this->getGridTypeModel(),
                    'editor_model' => $this,
                )
            );
            
            $this->setData(
                'base_callbacks',
                $this->_arrangeCallbacksArray(array_merge($callbacks, (array) $response->getData('callbacks')))
            );
        }
        
        $dataKey = 'base_callbacks';
        
        if (!is_null($workerType)) {
            $dataKey .= '/' . $workerType . (!is_null($actionType) ? '/' . $actionType : '');
        }
        
        return $this->getDataSetDefault($dataKey, array());
    }
    
    /**
     * Return the additional callbacks used by this editor model for the given editor context
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return BL_CustomGrid_Model_Grid_Editor_Callback[]
     */
    protected function _getContextAdditionalCallbacks(BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        return array();
    }
    
    /**
     * Return all the usable additional callbacks for the given editor context,
     * arranged and optionally filtered by worker and action types
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @param string $workerType Worker type
     * @param string $actionType Action type
     * @return array
     */
    public function getContextAdditionalCallbacks(
        BL_CustomGrid_Model_Grid_Editor_Context $context,
        $workerType = null,
        $actionType = null
    ) {
        $dataKey = 'context_additional_callbacks/' . $context->getKey();
        
        if (!$this->hasData($dataKey)) {
            $callbacks = $this->_getContextAdditionalCallbacks($context);
            $response  = new BL_CustomGrid_Object(array('callbacks' => array()));
            
            Mage::dispatchEvent(
                'blcg_grid_editor_additional_context_callbacks',
                array(
                    'response'       => $response,
                    'type_model'     => $this->getGridTypeModel(),
                    'editor_model'   => $this,
                    'editor_context' => $context,
                )
            );
            
            $this->setData(
                $dataKey,
                $this->_arrangeCallbacksArray(array_merge($callbacks, (array) $response->getData('callbacks')))
            );
        }
        
        if (!is_null($workerType)) {
            $dataKey .= '/' . $workerType . (!is_null($actionType) ? '/' . $actionType : '');
        }
        
        return $this->getDataSetDefault($dataKey, array());
    }
    
    /**
     * Return the sorted callbacks for the given worker and action types, arranged by position (before / main / after).
     * The given additional callbacks will be registered and sorted along with the external callbacks defined for the
     * same positions.
     * 
     * @param string $workerType Worker type
     * @param string $actionType Action type
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context (if any)
     * @param BL_CustomGrid_Model_Grid_Editor_Callback[] $additionalCallbacks Additional (internal) callbacks
     * @return array
     */
    public function getWorkerActionSortedCallbacks(
        $workerType,
        $actionType,
        BL_CustomGrid_Model_Grid_Editor_Context $context = null,
        array $additionalCallbacks = array()
    ) {
        $callbacks = array_merge_recursive(
            $this->getBaseCallbacks($workerType, $actionType),
            (!is_null($context) ? $this->getContextAdditionalCallbacks($context, $workerType, $actionType) : array()),
            $additionalCallbacks
        );
        
        foreach ($callbacks as $position => $positionCallbacks) {
            if (empty($positionCallbacks)) {
                unset($callbacks[$position]);
            } else {
                uasort($callbacks[$position], 'BL_CustomGrid_Model_Grid_Editor_Callback::sortCallbacks');
            }
        }
        
        return $callbacks;
    }
    
    /**
     * Return the base editable fields configs
     * 
     * @param string $blockType Grid block type
     * @return array
     */
    protected function _getBaseEditableFields($blockType)
    {
        return array();
    }
    
    /**
     * Return all the editable fields configs
     * 
     * @param string $blockType Grid block type
     * @return array
     */
    protected function _getEditableFields($blockType)
    {
        $response = new BL_CustomGrid_Object(array('fields' => array()));
        
        Mage::dispatchEvent(
            'blcg_grid_editor_additional_editable_fields',
            array(
                'response'     => $response,
                'type_model'   => $this->getGridTypeModel(),
                'editor_model' => $this,
                'block_type'   => $blockType,
            )
        );
        
        return array_merge($this->_getBaseEditableFields($blockType), $response->getFields());
    }
    
    /**
     * Return whether the given attribute is editable
     * 
     * @param string $blockType Grid block type
     * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute model
     * @return bool
     */
    protected function _checkAttributeEditability($blockType, Mage_Eav_Model_Entity_Attribute $attribute)
    {
        return $attribute->getFrontend()->getInputType()
            && (!$attribute->hasIsVisible() || $attribute->getIsVisible());
    }
    
    /**
     * Return the additional editable attributes,
     * ie attributes that are not necessarily intended to be available for display,
     * but that may for example be used by some corresponding attribute fields
     * 
     * @param string $blockType Grid block type
     * @return Mage_Eav_Model_Entity_Attribute[]
     */
    protected function _getAdditionalEditableAttributes($blockType)
    {
        return array();
    }
    
    /**
     * Return all the editable attributes configs
     * 
     * @param string $blockType Grid block type
     * @return Mage_Eav_Model_Entity_Attribute[]
     */
    protected function _getEditableAttributes($blockType)
    {
        $response = new BL_CustomGrid_Object(array('attributes' => array()));
        
        Mage::dispatchEvent(
            'blcg_grid_editor_additional_editable_attributes',
            array(
                'response'     => $response,
                'type_model'   => $this->getGridTypeModel(),
                'editor_model' => $this,
                'block_type'   => $blockType,
            )
        );
        
        $attributes = array_merge(
            $this->getGridTypeModel()->getAvailableAttributes($blockType),
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
     * Return the base editable attribute fields configs
     * (ie all grid/collection columns that actually correspond to an attribute, but were not added via EAG)
     * Used keys :
     * - "attribute" : corresponding attribute code
     * - "config" : array of config values that should override the ones from the editable attribute config
     * 
     * @param string $blockType Grid block type
     * @return array
     */
    protected function _getBaseEditableAttributeFields($blockType)
    {
        return array();
    }
    
    /**
     * Return all the editable attribute fields configs
     * 
     * @param string $blockType Grid block type
     * @return array
     */
    protected function _getEditableAttributeFields($blockType)
    {
        $response = new BL_CustomGrid_Object(array('attribute_fields' => array()));
        
        Mage::dispatchEvent(
            'blcg_grid_editor_additional_editable_attribute_fields',
            array(
                'response'     => $response,
                'type_model'   => $this->getGridTypeModel(),
                'editor_model' => $this,
                'block_type'   => $blockType,
            )
        );
        
        return array_merge($this->_getBaseEditableAttributeFields($blockType), $response->getAttributeFields());
    }
    
    /**
     * Return the configs for all the editable values, possibly filtered on value origin
     * 
     * @param string $blockType Grid block type
     * @param string|null $origin Values origin (if null, all values will be returned)
     * @return BL_CustomGrid_Model_Grid_Editor_Value_Config[]
     */
    public function getEditableValuesConfigs($blockType, $origin = null)
    {
        if (!$this->hasData('editable_values_configs')
            || !is_array($editableValuesConfigs = $this->getData('editable_values_configs/' . $blockType))) {
            $gridTypeModel = $this->getGridTypeModel();
            $configBuilder = $this->getValueConfigBuilder();
            
            // Build all base configs
            $fields = $this->_getEditableFields($blockType);
            $attributes = $this->_getEditableAttributes($blockType);
            $attributeFields = $this->_getEditableAttributeFields($blockType);
            
            foreach ($fields as $fieldId => $field) {
                $fields[$fieldId] = $configBuilder->buildEditableFieldConfig($blockType, $fieldId, $field);
            }
            
            foreach ($attributes as $code => $attribute) {
                $attributes[$code] = $configBuilder->buildEditableAttributeConfig($blockType, $code, $attribute);
            }
            
            foreach ($attributeFields as $fieldId => $attributeField) {
                $config = $configBuilder->buildEditableAttributeFieldConfig(
                    $blockType,
                    $fieldId,
                    $attributeField,
                    $attributes
                );
                
                if (!$config instanceof BL_CustomGrid_Model_Grid_Editor_Value_Config) {
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
                'blcg_grid_editor_prepare_editable_fields',
                array(
                    'response'     => $fieldsResponse,
                    'type_model'   => $gridTypeModel,
                    'editor_model' => $this,
                    'block_type'   => $blockType,
                )
            );
            
            Mage::dispatchEvent(
                'blcg_grid_editor_prepare_editable_attributes',
                array(
                    'response'     => $attributesResponse,
                    'type_model'   => $gridTypeModel,
                    'editor_model' => $this,
                    'block_type'   => $blockType,
                )
            );
            
            Mage::dispatchEvent(
                'blcg_grid_editor_prepare_editable_attribute_fields',
                array(
                    'response'     => $attributeFieldsResponse,
                    'type_model'   => $gridTypeModel,
                    'editor_model' => $this,
                    'block_type'   => $blockType,
                )
            );
            
            $editableValuesConfigs = array(
                self::EDITABLE_TYPE_FIELD => $fieldsResponse->getFields(),
                self::EDITABLE_TYPE_ATTRIBUTE => $attributesResponse->getAttributes(),
                self::EDITABLE_TYPE_ATTRIBUTE_FIELD => $attributeFieldsResponse->getAttributeFields(),
            );
            
            $this->setData('editable_values_configs/' . $blockType, $editableValuesConfigs);
        }
        
        return !is_null($origin)
            ? (isset($editableValuesConfigs[$origin]) ? $editableValuesConfigs[$origin] : array())
            : $editableValuesConfigs;
    }
    
    /**
     * Return the configs for all the editable fields
     * 
     * @param string $blockType Grid block type
     * @return BL_CustomGrid_Model_Grid_Editor_Value_Config[]
     */
    public function getEditableFieldsConfigs($blockType)
    {
        return $this->getEditableValuesConfigs($blockType, self::EDITABLE_TYPE_FIELD);
    }
    
    /**
     * Return the configs for all the editable attributes
     * 
     * @param string $blockType Grid block type
     * @return BL_CustomGrid_Model_Grid_Editor_Value_Config[]
     */
    public function getEditableAttributesConfigs($blockType)
    {
        return $this->getEditableValuesConfigs($blockType, self::EDITABLE_TYPE_ATTRIBUTE);
    }
    
    /**
     * Return the configs for all the editable attribute fields
     * 
     * @param string $blockType Grid block type
     * @return BL_CustomGrid_Model_Grid_Editor_Value_Config[]
     */
    public function getEditableAttributeFieldsConfigs($blockType)
    {
        return $this->getEditableValuesConfigs($blockType, self::EDITABLE_TYPE_ATTRIBUTE_FIELD);
    }
    
    /**
     * Return the config for the given value ID
     * 
     * @param string $blockType Grid block type
     * @param string $valueId Value ID
     * @param string $origin Value origin
     * @return BL_CustomGrid_Model_Grid_Editor_Value_Config|null
     */
    public function getEditableValueConfig($blockType, $valueId, $origin)
    {
        $editableValues = $this->getEditableValuesConfigs($blockType, $origin);
        return (isset($editableValues[$valueId]) ? $editableValues[$valueId] : null);
    }
    
    /**
     * Return the config corresponding to the value from the given context, if any
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return BL_CustomGrid_Model_Grid_Editor_Value_Config|null
     */
    public function getContextEditableValueConfig(BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        return $this->getEditableValueConfig(
            $context->getBlockType(),
            $context->getValueId(),
            $context->getValueOrigin()
        );
    }
    
    /**
     * Return the config for the given field ID
     * 
     * @param string $blockType Grid block type
     * @param string $fieldId Field ID
     * @return BL_CustomGrid_Model_Grid_Editor_Value_Config|null
     */
    public function getEditableFieldConfig($blockType, $fieldId)
    {
        return $this->getEditableValueConfig($blockType, $fieldId, self::EDITABLE_TYPE_FIELD);
    }
    
    /**
     * Return the config for the given attribute code
     * 
     * @param string $blockType Grid block type
     * @param string $attributeCode Attribute code
     * @return BL_CustomGrid_Model_Grid_Editor_Value_Config|null
     */
    public function getEditableAttributeConfig($blockType, $attributeCode)
    {
        return $this->getEditableValueConfig($blockType, $attributeCode, self::EDITABLE_TYPE_ATTRIBUTE);
    }
    
    /**
     * Return the config for the given attribute field ID
     * 
     * @param string $blockType Grid block type
     * @param string $fieldId Field ID
     * @return BL_CustomGrid_Model_Grid_Editor_Value_Config|null
     */
    public function getEditableAttributeFieldConfig($blockType, $fieldId)
    {
        return $this->getEditableValueConfig($blockType, $fieldId, self::EDITABLE_TYPE_ATTRIBUTE_FIELD);
    }
    
    /**
     * Return whether the given value is editable
     * 
     * @param string $blockType Grid block type
     * @param string $valueId Value ID
     * @param string $origin Value origin
     * @return bool
     */
    public function isEditableValue($blockType, $valueId, $origin)
    {
        return !is_null($this->getEditableValueConfig($blockType, $valueId, $origin));
    }
    
    /**
     * Return whether the edited value from the given context is actually editable
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Checked editor context
     * @return bool
     */
    public function isEditableContext(BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        return $this->isEditableValue($context->getBlockType(), $context->getValueId(), $context->getValueOrigin());
    }
    
    /**
     * Return whether the given field is editable
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
     * Return whether the given attribute is editable
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
     * Return whether the given attribute field is editable
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
     * Apply editable values configs to the given grid columns
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Model_Grid_Column[] $columns Grid columns
     * @return BL_CustomGrid_Model_Grid_Editor_Abstract
     */
    public function applyConfigsToColumns($blockType, array $columns)
    {
        foreach ($columns as $columnBlockId => $column) {
            $valueConfig = null;
            $hasStoreId  = false;
            
            if ($column->isAttribute()) {
                $valueConfig = $this->getEditableAttributeConfig($blockType, $column->getIndex());
                $hasStoreId  = $column->hasStoreId();
            } elseif (!$column->isCustom()) {
                if (!$valueConfig = $this->getEditableFieldConfig($blockType, $columnBlockId)) {
                    $valueConfig = $this->getEditableAttributeFieldConfig($blockType, $columnBlockId);
                }
            }
            
            if ($valueConfig instanceof BL_CustomGrid_Model_Grid_Editor_Value_Config) {
                $valueConfig = clone $valueConfig;
                
                if ($hasStoreId && !$valueConfig->hasData('request/column_params/column_store_id')) {
                    // Apply user-defined store ID to allow editing values for the corresponding store view
                    $valueConfig->setData('request/column_params/column_store_id', $column->getStoreId());
                }
                
                $valueConfig->setData('request/column_params/column_id', $column->getId());
                $column->setEditorConfig($valueConfig);
            } else {
                $column->setEditorConfig(false);
            }
        }
        return $this;
    }
    
    /**
     * Return the global additional parameters required for editing values
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
     * Return the form block corresponding to the given edit request made on the given grid model
     *
     * @param Mage_Core_Controller_Request_Http $request Edit request
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @return Mage_Core_Block_Abstract
     */
    public function getValueFormBlock(Mage_Core_Controller_Request_Http $request, BL_CustomGrid_Model_Grid $gridModel)
    {
        $context = $this->getKickstarter()->getEditorContextFromRequest($request, $gridModel);
        
        if (($result = $this->getSentry()->isEditAllowedForContext($context)) !== true) {
            $errorMessage = is_string($result)
                ? $result
                : $this->getBaseHelper()->__('You are not allowed to edit this value');
            Mage::throwException($errorMessage);
        }
        if (($result = $this->getEntityUpdater()->isContextValueEditable($context)) !== true) {
            $errorMessage = is_string($result)
                ? $result
                : $this->getBaseHelper()->__('This value is not editable');
            Mage::throwException($errorMessage);
        }
        if (!$valueFormBlock = $this->getValueFormRenderer()->getContextValueFormBlock($context)) {
            Mage::throwException($this->getBaseHelper()->__('The value form block could not be retrieved'));
        }
        
        $isInGrid = (bool) $context->getValueConfig()->getData('form/is_in_grid');
        
        if (!$isInGrid) {
            /** @var BL_CustomGrid_Model_Observer $observer */
            $observer = Mage::getSingleton('customgrid/observer');
            /**
             * Use our observer to add layout handles just before layout load :
             * it will ensure that the "default" handle (and even most of others, if not all)
             * will be handled before our own ones, which is what we actually need
             */
            $layoutHandles = $this->getValueFormRenderer()->getContextValueFormLayoutHandles($context);
            $observer->registerAdditionalLayoutHandles(array_filter((array) $layoutHandles));
        }
        
        return ($isInGrid ? $valueFormBlock->toHtml() : $valueFormBlock);
    }

    /**
     * Apply the value update corresponding to the given edit request made on the given grid model,
     * and return the rendered new value
     *
     * @param Mage_Core_Controller_Request_Http $request Edit request
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @return mixed
     */
    public function updateEditedValue(Mage_Core_Controller_Request_Http $request, BL_CustomGrid_Model_Grid $gridModel)
    {
        $context = $this->getKickstarter()->getEditorContextFromRequest($request, $gridModel);
        
        if (($result = $this->getSentry()->isEditAllowedForContext($context)) !== true) {
            $errorMessage = is_string($result)
                ? $result
                : $this->getBaseHelper()->__('You are not allowed to edit this value');
            Mage::throwException($errorMessage);
        }
        if (($result = $this->getEntityUpdater()->isContextValueEditable($context)) !== true) {
            $errorMessage = is_string($result)
                ? $result
                : $this->getBaseHelper()->__('This value is not editable');
            Mage::throwException($errorMessage);
        }
        
        $this->getEntityUpdater()->updateContextEditedValue($context);
        return $this->getValueRenderer()->getRenderedContextEditedValue($context);
    }
}
