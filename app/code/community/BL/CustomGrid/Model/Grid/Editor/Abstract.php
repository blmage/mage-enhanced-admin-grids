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
    
    const WORKER_TYPE_CALLBACK_MANAGER     = 'callback_manager';
    const WORKER_TYPE_ENTITY_LOADER        = 'entity_loader';
    const WORKER_TYPE_ENTITY_UPDATER       = 'entity_updater';
    const WORKER_TYPE_KICKSTARTER          = 'kickstarter';
    const WORKER_TYPE_SENTRY               = 'sentry';
    const WORKER_TYPE_VALUE_CONFIG_BUILDER = 'value_config_builder';
    const WORKER_TYPE_VALUE_FORM_RENDERER  = 'value_form_renderer';
    const WORKER_TYPE_VALUE_RENDERER       = 'value_renderer';
    
    /**
     * Worker types list
     * 
     * @var array
     */
    static protected $_workerTypes = array(
        self::WORKER_TYPE_CALLBACK_MANAGER,
        self::WORKER_TYPE_ENTITY_LOADER,
        self::WORKER_TYPE_ENTITY_UPDATER,
        self::WORKER_TYPE_KICKSTARTER,
        self::WORKER_TYPE_SENTRY,
        self::WORKER_TYPE_VALUE_CONFIG_BUILDER,
        self::WORKER_TYPE_VALUE_FORM_RENDERER,
        self::WORKER_TYPE_VALUE_RENDERER,
    );
    
    /**
     * Return an instance of the editor corresponding to the given class code,
     * on which the specific values of the current editor (grid type model, workers) will have been copied
     * 
     * @param string $classCode Editor class code
     * @return BL_CustomGrid_Model_Grid_Editor_Abstract
     * @throws Mage_Core_Exception
     */
    protected function _getSubEditor($classCode)
    {
        $subEditor = Mage::getModel($classCode);
        
        if (!$subEditor || !($subEditor instanceof BL_CustomGrid_Model_Grid_Editor_Abstract)) {
            Mage::throwException('Invalid sub editor model ("' . $classCode . '")');
        }
        
        $subEditor->setGridTypeModel($this->getGridTypeModel());
        
        foreach (self::$_workerTypes as $workerType) {
            $dataKey = 'worker_model_class_code_' . $workerType;
            $subEditor->setData($dataKey, $this->_getData($dataKey));
        }
        
        return $subEditor;
    }
    
    /**
     * Return the worker model of the given type
     * 
     * @param string $type Worker type
     * @return BL_CustomGrid_Model_Grid_Editor_Worker_Abstract
     */
    protected function _getWorker($type)
    {
        /** @var BL_CustomGrid_Helper_Worker $helper */
        $helper = Mage::helper('customgrid/worker');
        return $helper->getModelWorker($this, $type);
    }
    
    /**
     * Return the callback manager model, usable to manage the editor callbacks
     * 
     * @return BL_CustomGrid_Model_Grid_Editor_Callback_Manager
     */
    public function getCallbackManager()
    {
        return $this->_getWorker(self::WORKER_TYPE_CALLBACK_MANAGER);
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
     * Return the kickstarter model, usable to parse edit requests and to initialize the corresponding editor contexts
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
     * @return BL_CustomGrid_Model_Grid_Editor_Value_Renderer
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
     * Dispatch the given event with the given response object and some common event data
     * 
     * @param string $eventName Event name
     * @param BL_CustomGrid_Object $response Response object
     * @param string|null $blockType Grid block type, if known
     * @return $this
     */
    protected function _dispatchEventWithResponse($eventName, BL_CustomGrid_Object $response, $blockType = null)
    {
        $eventData = array(
            'response'     => $response,
            'type_model'   => $this->getGridTypeModel(),
            'editor_model' => $this,
        );
        
        if (!is_null($blockType)) {
            $eventData['block_type'] = $blockType;
        }
        
        Mage::dispatchEvent($eventName, $eventData);
        return $this;
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
            $this->_dispatchEventWithResponse('blcg_grid_editor_base_config', $response);
            $baseConfig->mergeData((array) $response->getData('config'));
            $this->setData('base_config', $baseConfig);
        }
        return $this->_getData('base_config');
    }
    
    /**
     * Return the default base callbacks used by this editor model
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Callback_Manager $callbackManager Callback manager
     * @return BL_CustomGrid_Model_Grid_Editor_Callback[]
     */
    public function getDefaultBaseCallbacks(BL_CustomGrid_Model_Grid_Editor_Callback_Manager $callbackManager)
    {
        return array();
    }
    
    /**
     * Return the default additional callbacks used by this editor model for the given editor context
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @param BL_CustomGrid_Model_Grid_Editor_Callback_Manager $callbackManager Callback manager
     * @return BL_CustomGrid_Model_Grid_Editor_Callback[]
     */
    public function getContextDefaultAdditionalCallbacks(
        BL_CustomGrid_Model_Grid_Editor_Context $context,
        BL_CustomGrid_Model_Grid_Editor_Callback_Manager $callbackManager
    ) {
        return array();
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
        $this->_dispatchEventWithResponse('blcg_grid_editor_additional_editable_fields', $response, $blockType);
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
        $this->_dispatchEventWithResponse('blcg_grid_editor_additional_editable_attributes', $response, $blockType);
        
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
        
        $this->_dispatchEventWithResponse(
            'blcg_grid_editor_additional_editable_attribute_fields',
            $response,
            $blockType
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
            $editableValuesConfigs = $this->getValueConfigBuilder()
                ->buildEditableValuesConfigs(
                    $blockType,
                    $this->_getEditableFields($blockType),
                    $this->_getEditableAttributes($blockType),
                    $this->_getEditableAttributeFields($blockType)
                );
            
            $response = new BL_CustomGrid_Object(array('configs' => $editableValuesConfigs));
            
            $this->_dispatchEventWithResponse(
                'blcg_grid_editor_prepare_editable_values_configs',
                $response,
                $blockType
            );
            
            $this->setData('editable_values_configs/' . $blockType, $response->getConfigs());
        }
        
        return !is_null($origin)
            ? (isset($editableValuesConfigs[$origin]) ? $editableValuesConfigs[$origin] : array())
            : $editableValuesConfigs;
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
     * Apply the editable values configs to the given grid columns
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Model_Grid_Column[] $columns Grid columns
     * @return BL_CustomGrid_Model_Grid_Editor_Abstract
     */
    public function applyConfigsToColumns($blockType, array $columns)
    {
        $configBuilder = $this->getValueConfigBuilder();
        
        foreach ($columns as $columnBlockId => $column) {
            $hasStoreId  = false;
            
            if ($column->isAttribute()) {
                $hasStoreId  = $column->hasStoreId();
                $valueConfig = $this->getEditableValueConfig(
                    $blockType,
                    $column->getIndex(),
                    self::EDITABLE_TYPE_ATTRIBUTE
                );
            } elseif ($column->isCustom()) {
                $hasStoreId  = $column->hasStoreId();
                $valueConfig = $column->getCustomColumnModel(false)
                    ->getGridColumnEditorConfig($column, $configBuilder);
            } else {
                $valueConfig = $this->getEditableValueConfig($blockType, $columnBlockId, self::EDITABLE_TYPE_FIELD);
                
                if (!$valueConfig) {
                    $valueConfig = $this->getEditableValueConfig(
                        $blockType,
                        $columnBlockId,
                        self::EDITABLE_TYPE_ATTRIBUTE_FIELD
                    );
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
     * Check the given worker action result, throw a relevant exception if necessary
     * 
     * @param mixed $result Worker action result
     * @param string $defaultErrorMessage Default error message
     * @throws Mage_Core_Exception
     */
    protected function _checkWorkerActionResult($result, $defaultErrorMessage)
    {
        if ($result !== true) {
            Mage::throwException(is_string($result) ? $result : $defaultErrorMessage);
        }
    }
    
    /**
     * Initialize the editor action from the given request for the given grid model,
     * then return the corresponding editor context
     * 
     * @param Mage_Core_Controller_Request_Http $request Edit request
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @return BL_CustomGrid_Model_Grid_Editor_Context
     */
    protected function _initializeEditorAction(
        Mage_Core_Controller_Request_Http $request,
        BL_CustomGrid_Model_Grid $gridModel
    ) {
        $context = $this->getKickstarter()->getEditorContextFromRequest($request, $gridModel);
        
        $this->_checkWorkerActionResult(
            $this->getSentry()->isEditAllowedForContext($context),
            $this->getBaseHelper()->__('You are not allowed to edit this value')
        );
        
        $this->_checkWorkerActionResult(
            $this->getEntityUpdater()->isContextValueEditable($context),
            $this->getBaseHelper()->__('This value is not editable')
        );
        
        return $context;
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
        $context = $this->_initializeEditorAction($request, $gridModel);
        
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
        $context = $this->_initializeEditorAction($request, $gridModel);
        $this->getEntityUpdater()->updateContextEditedValue($context);
        return $this->getValueRenderer()->getRenderedContextEditedValue($context);
    }
}
