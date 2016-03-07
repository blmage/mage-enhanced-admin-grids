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

class BL_CustomGrid_Model_Grid_Editor_Value_Config_Builder extends BL_CustomGrid_Model_Grid_Editor_Worker_Abstract
{
    const ACTION_TYPE_BUILD_EDITABLE_FIELD_CONFIG = 'build_editable_field_config';
    const ACTION_TYPE_BUILD_EDITABLE_ATTRIBUTE_CONFIG = 'build_editable_attribute_config';
    const ACTION_TYPE_BUILD_EDITABLE_ATTRIBUTE_FIELD_CONFIG = 'build_editable_attribute_field_config';
    
    /**
     * Config data skeleton
     * 
     * @var array
     */
    static protected $_configDataSkeleton = array(
        'global'     => array(),
        'request'    => array(),
        'form'       => array(),
        'form_field' => array(),
        'window'     => array(),
        'updater'    => array(),
        'renderer'   => array(),
    );
    
    /**
     * Base window config values
     *
     * @var array
     */
    static protected $_windowConfigValues = array(
        'width'        => '80%',
        'height'       => '80%',
        'draggable'    => true,
        'resizable'    => true,
        'recenterAuto' => false,
    );
    
    public function getType()
    {
        return BL_CustomGrid_Model_Grid_Editor_Abstract::WORKER_TYPE_VALUE_CONFIG_BUILDER;
    }
    
    /**
     * Return the action URL for the given value of the given origin
     * 
     * @param string $blockType Grid block type
     * @param string $valueOrigin Value origin
     * @param string $valueKey Value key
     * @param string $route Action route
     * @return string
     */
    protected function _getEditorActionUrl($blockType, $valueOrigin, $valueKey, $route)
    {
        $blockTypeCode = str_replace('/', '_', $blockType);
        $routeCode = str_replace('/', '_', $route);
        $dataKey = $valueOrigin . '_action_base_urls/' . $blockTypeCode . '/' . $routeCode;
        
        if (!$baseUrl = $this->getData($dataKey)) {
            /** @var $helper Mage_Core_Helper_Data */
            $coreHelper  = Mage::helper('core');
            /** @var $adminHelper Mage_Adminhtml_Helper_Data */
            $adminHelper = Mage::helper('adminhtml');
            
            $baseUrl = $adminHelper->getUrl(
                $route,
                array(
                    'grid_type'    => $this->getEditor()->getGridTypeModel()->getCode(),
                    'block_type'   => $coreHelper->urlEncode($blockType),
                    'value_id'     => '{{value_key}}',
                    'value_origin' => $valueOrigin,
                )
            );
            
            $this->setData($dataKey, $baseUrl);
        }
        
        return str_replace('{{value_key}}', $valueKey, $baseUrl);
    }
    
    /**
     * Return the form URL for the given value of the given origin
     *
     * @param string $blockType Grid block type
     * @param string $valueOrigin Value origin
     * @param string $valueKey Value key
     * @return string
     */
    protected function _getEditorFormUrl($blockType, $valueOrigin, $valueKey)
    {
        return $this->_getEditorActionUrl($blockType, $valueOrigin, $valueKey, 'adminhtml/blcg_grid_editor/form');
    }
    
    /**
     * Return the save URL for the given value of the given origin
     *
     * @param string $blockType Grid block type
     * @param string $valueOrigin Value origin
     * @param string $valueKey Value key
     * @return string
     */
    protected function _getEditorSaveUrl($blockType, $valueOrigin, $valueKey)
    {
        return $this->_getEditorActionUrl($blockType, $valueOrigin, $valueKey, 'adminhtml/blcg_grid_editor/save');
    }
    
    /**
     * Prepare some common configuration values on the given editable field config
     * 
     * @param string $blockType Grid block type
     * @param string $fieldId Field ID
     * @param array $config Field config
     * @return BL_CustomGrid_Model_Grid_Editor_Value_Config_Builder
     */
    protected function _prepareEditableFieldCommonConfig($blockType, $fieldId, array &$config)
    {
        if ($config['form_field']['type'] == 'editor') {
            if (!isset($config['form']['no_editor_handle'])
                || !$config['form']['no_editor_handle']) {
                $config['form']['layout_handles'][] = 'blcg_grid_editor_handle_editor';
            }
        } elseif ($config['form_field']['type'] == 'date') {
            $config['updater']['must_filter']  = true;
            $config['renderer']['must_reload'] = true;
        }
        return $this;
    }
    
    /**
     * Default main callback for @see BL_CustomGrid_Model_Grid_Editor_Value_Config_Builder::buildEditableFieldConfig()
     * 
     * @param string $blockType Grid block type
     * @param string $fieldId Field ID
     * @param array $config Base field config
     * @param mixed $previousReturnedValue Value returned by the previous callback
     * @return array
     */
    public function _buildEditableFieldConfig($blockType, $fieldId, array $config, $previousReturnedValue)
    {
        $config  = (is_array($previousReturnedValue) ? $previousReturnedValue : $config);
        $config += self::$_configDataSkeleton;
        
        $config['global'] += array(
            'entity_value_key' => $fieldId, // Key where is stored the edited value data in the edited entity
        );
        
        $config['request'] += array(
            'ids_key'        => 'identifiers', // Key where to store entity identifiers in request data
            'additional_key' => 'additional',  // Key where to store additional parameters in request data
            'values_key'     => 'values',      // Key where to store edited values in request data
            'column_params'  => array(),       // Additional column parameters to put in requests
        );
        
        $config['form'] += array(
            'block_type'     => 'default', // @see BL_CustomGrid_Model_Grid_Editor_Value_Form_Renderer
            'is_in_grid'     => true,      // Whether the field can be edited directly from the grid
            'layout_handles' => array(),   // Appliable layout handles for windowed edit
        );
        
        // Form field values mostly depends on the form block type
        $config['form_field'] += array(
            'type'  => 'text',
            'id'    => $fieldId,
            'name'  => $fieldId,
            'label' => '',
            'title' => '',
            'required' => false,
        );
        
        if (!$config['form']['is_in_grid']) {
            $config['window'] += self::$_windowConfigValues;
            $config['window']['title'] = $this->getBaseHelper()->__('Edit Value');
        } else {
            unset($config['window']);
        }
        
        $config['updater'] += array(
            'must_filter'   => false,   // Whether the new values should be filtered before the entity gets updated
            'filter_type'   => null,    // Field type on which to base the filtering (form type will be used if none)
            'filter_params' => array(),
        );
        
        $config['renderer'] += array(
            'block_type'  => 'default', // @see BL_CustomGrid_Model_Grid_Editor_Value_Renderer
            'must_reload' => false,     // Whether the entity should be reloaded before the new value gets rendered
            'params'      => array(),
        );
        
        if (!isset($config['global']['form_url'])) {
            $config['global']['form_url'] = $this->_getEditorFormUrl(
                $blockType,
                BL_CustomGrid_Model_Grid_Editor_Abstract::EDITABLE_TYPE_FIELD,
                $fieldId
            );
        }
        if (!isset($config['global']['save_url'])) {
            $config['global']['save_url'] = $this->_getEditorSaveUrl(
                $blockType,
                BL_CustomGrid_Model_Grid_Editor_Abstract::EDITABLE_TYPE_FIELD,
                $fieldId
            );
        }
        
        $this->_prepareEditableFieldCommonConfig($blockType, $fieldId, $config);
        return $config;
    }
    
    /**
     * Build a complete config object from the given base config data for the given editable field
     * 
     * @param string $blockType Grid block type
     * @param string $fieldId Field ID
     * @param array $config Base config data
     * @return BL_CustomGrid_Model_Grid_Editor_Value_Config
     */
    public function buildEditableFieldConfig($blockType, $fieldId, array $config)
    {
        return new BL_CustomGrid_Model_Grid_Editor_Value_Config(
            (array) $this->_runCallbackedAction(
                self::ACTION_TYPE_BUILD_EDITABLE_FIELD_CONFIG,
                array('blockType' => $blockType, 'fieldId' => $fieldId, 'config' => $config),
                array($this, '_buildEditableFieldConfig'),
                null,
                array(),
                true
            )
        );
    } 
    
    /**
     * Return whether the given attribute can be edited directly from the grid
     * 
     * @param string $blockType Grid block type
     * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute model
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
     * Prepare some common configuration values on the given editable attribute config
     * 
     * @param string $blockType Grid block type
     * @param string $attributeCode Attribute code
     * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute model
     * @param array $config Attribute config
     * @return BL_CustomGrid_Model_Grid_Editor_Value_Config_Builder
     */
    protected function _prepareEditableAttributeCommonConfig(
        $blockType,
        $attributeCode,
        Mage_Eav_Model_Entity_Attribute $attribute,
        array &$config
    ) {
        if ($attribute->getBackend()->getType() == 'datetime') {
            $config['updater']['must_filter']  = true;
            $config['updater']['filter_type']  = 'date';
            $config['renderer']['must_reload'] = true;
        }
        return $this;
    }
    
    /**
     * Default main callback for
     * @see BL_CustomGrid_Model_Grid_Editor_Value_Config_Builder::buildEditableAttributeConfig()
     * 
     * @param string $blockType Grid block type
     * @param string $attributeCode Attribute code
     * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute model
     * @param array $baseConfig Base config data
     * @param mixed $previousReturnedValue Value returned by the previous callback
     * @return array
     */
    public function _buildEditableAttributeConfig(
        $blockType,
        $attributeCode,
        Mage_Eav_Model_Entity_Attribute $attribute,
        array $baseConfig,
        $previousReturnedValue
    ) {
        $config  = (is_array($previousReturnedValue) ? $previousReturnedValue : $baseConfig);
        $config += self::$_configDataSkeleton;
        $attributeCode = $attribute->getAttributeCode();
        
        $config['global']['attribute'] = $attribute;
        
        /** @see BL_CustomGrid_Model_Grid_Editor_Value_Config_Builder::_buildEditableFieldConfig for comments */
        
        $config['request'] += array(
            'ids_key'        => 'identifiers',
            'additional_key' => 'additional',
            'values_key'     => 'values',
            'column_params'  => array(),
        );
        
        $config['form'] += array(
            'block_type'     => 'default',
            'is_in_grid'     => $this->_checkAttributeInGridEditability($blockType, $attribute),
            'layout_handles' => array(),
        );
        
        $config['form_field'] += array('name' => $attribute->getAttributeCode());
        
        if (!$config['form']['is_in_grid']) {
            $config['window'] += self::$_windowConfigValues;
            $config['window']['title'] = $this->getBaseHelper()->__('Edit Value');
        } else {
            unset($config['window']);
        }
        
        $config['updater'] += array(
            'must_filter'   => false,
            'filter_type'   => null,
            'filter_params' => array(),
        );
        
        $config['renderer'] += array(
            'block_type'  => 'default',
            'must_reload' => ($attribute->getBackendModel() != ''),
            'params'      => array(),
        );
        
        
        if (!isset($config['global']['form_url'])) {
           $config['global']['form_url'] = $this->_getEditorFormUrl(
                $blockType,
                BL_CustomGrid_Model_Grid_Editor_Abstract::EDITABLE_TYPE_ATTRIBUTE,
                $attributeCode
            );
        }
        
        if (!isset($config['global']['save_url'])) {
            $config['global']['save_url'] = $this->_getEditorSaveUrl(
                $blockType,
                BL_CustomGrid_Model_Grid_Editor_Abstract::EDITABLE_TYPE_ATTRIBUTE,
                $attributeCode
            );
        }
        
        $this->_prepareEditableAttributeCommonConfig($blockType, $attributeCode, $attribute, $config);
        return $config;
    }
    
    /**
     * Build a complete config object from the given base config data for the given editable attribute
     * 
     * @param string $blockType Grid block type
     * @param string $attributeCode Attribute code
     * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute model
     * @param array $baseConfig Base config data
     * @return BL_CustomGrid_Model_Grid_Editor_Value_Config
     */
    public function buildEditableAttributeConfig(
        $blockType,
        $attributeCode,
        Mage_Eav_Model_Entity_Attribute $attribute,
        array $baseConfig = array()
    ) {
        return new BL_CustomGrid_Model_Grid_Editor_Value_Config(
            (array) $this->_runCallbackedAction(
                self::ACTION_TYPE_BUILD_EDITABLE_ATTRIBUTE_CONFIG,
                array(
                    'blockType'     => $blockType,
                    'attributeCode' => $attributeCode,
                    'attribute'     => $attribute,
                    'baseConfig'    => $baseConfig,
                ),
                array($this, '_buildEditableAttributeConfig'),
                null,
                array(),
                true
            )
        );
    }
    
    /**
     * Build a complete config object from the given base config data for the given editable attribute field.
     * Will return null if the provided base config is invalid
     * (eg, if the corresponding attribute does not exist or is not editable)
     * 
     * @param string $blockType Grid block type
     * @param string $fieldId Field ID
     * @param array $config Field config data
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config[] $attributesConfigs Editable attributes configs
     * @return BL_CustomGrid_Model_Grid_Editor_Value_Config|null
     */
    public function buildEditableAttributeFieldConfig($blockType, $fieldId, array $config, array $attributesConfigs)
    {
        $fieldConfig = null;
        
        if (isset($config['attribute']) && isset($attributesConfigs[$config['attribute']])) {
            /** @var $attributeConfig BL_CustomGrid_Model_Grid_Editor_Value_Config */
            $attributeConfig = $attributesConfigs[$config['attribute']];
            $fieldConfig = clone $attributeConfig;
            
            if (isset($config['config_override']) && is_array($config['config_override'])) {
                $fieldConfig->mergeData($config['config_override']);
            }
            
            $this->_runCallbackedAction(
                self::ACTION_TYPE_BUILD_EDITABLE_ATTRIBUTE_FIELD_CONFIG,
                array(
                    'blockType'       => $blockType,
                    'fieldId'         => $fieldId,
                    'fieldConfig'     => $fieldConfig,
                    'attributeCode'   => $config['attribute'],
                    'attributeConfig' => $attributeConfig,
                ),
                null,
                null,
                array(),
                true
            );
        }
        
        return $fieldConfig;
    }
    
    /**
     * Prepare the given base config data from an editable custom column, so that it is fully suited
     * 
     * @param array $baseConfig base config data
     * @param string $blockType Grid block type
     * @param string $columnBlockId Column block ID
     * @param string $baseValueId Base value ID
     * @param string $baseValueOrigin Base value origin (field or attribute)
     * @return array
     */
    protected function _prepareEditableCustomColumnBaseConfig(
        array $baseConfig,
        $blockType,
        $columnBlockId,
        $baseValueId,
        $baseValueOrigin
    ) {
        if (!isset($baseConfig['global'])) {
            $baseConfig['global'] = array();
        }
        
        $baseConfig['global']['base_value_id'] = $baseValueId;
        $baseConfig['global']['base_value_origin'] = $baseValueOrigin;
        
        if (!isset($baseConfig['global']['form_url'])) {
            $baseConfig['global']['form_url'] = $this->_getEditorFormUrl(
                $blockType,
                BL_CustomGrid_Model_Grid_Editor_Abstract::EDITABLE_TYPE_CUSTOM_COLUMN,
                $columnBlockId
            );
        }
        if (!isset($baseConfig['global']['save_url'])) {
            $baseConfig['global']['save_url'] = $this->_getEditorSaveUrl(
                $blockType,
                BL_CustomGrid_Model_Grid_Editor_Abstract::EDITABLE_TYPE_CUSTOM_COLUMN,
                $columnBlockId
            );
        }
        
        return $baseConfig;
    }
    
    /**
     * Build a complete config object for an editable custom column from the given base config
     *
     * @param string $blockType Grid block type
     * @param string $fieldId Field ID
     * @param array $config Base field config
     * @return BL_CustomGrid_Model_Grid_Editor_Value_Config
     */
    public function buildEditableCustomColumnFieldConfig(
        $blockType,
        $columnBlockId,
        $fieldId,
        array $config
    ) {
        return $this->buildEditableFieldConfig(
            $blockType,
            $fieldId,
            $this->_prepareEditableCustomColumnBaseConfig(
                $config,
                $blockType,
                $columnBlockId,
                $fieldId,
                BL_CustomGrid_Model_Grid_Editor_Abstract::EDITABLE_TYPE_FIELD
            )
        );
    }
    
    /**
     * Build a complete config object for an editable custom column from the given attribute
     *
     * @param string $blockType Grid block type
     * @param string $columnBlockId Column block ID
     * @param string $attributeCode Attribute code
     * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute model
     * @return BL_CustomGrid_Model_Grid_Editor_Value_Config
     */
    public function buildEditableCustomColumnAttributeConfig(
        $blockType,
        $columnBlockId,
        $attributeCode,
        Mage_Eav_Model_Entity_Attribute $attribute
    ) {
        return $this->buildEditableAttributeConfig(
            $blockType,
            $attributeCode,
            $attribute,
            $this->_prepareEditableCustomColumnBaseConfig(
                array(),
                $blockType,
                $columnBlockId,
                $attributeCode,
                BL_CustomGrid_Model_Grid_Editor_Abstract::EDITABLE_TYPE_ATTRIBUTE
            )
        );
    }
    
    /**
     * Build complete config objects for each of the given editable values
     * 
     * @param array $editableFields Editable fields
     * @param array $editableAttributes Editable attributes
     * @param array $editableAttributeFields Editable attribute fields
     * @param string $blockType Grid block type
     * @return array
     */
    public function buildEditableValuesConfigs(
        $blockType,
        array $editableFields,
        array $editableAttributes,
        array $editableAttributeFields
    ) {
        foreach ($editableFields as $fieldId => $field) {
            $editableFields[$fieldId] = $this->buildEditableFieldConfig($blockType, $fieldId, $field);
        }
        
        foreach ($editableAttributes as $code => $attribute) {
            $editableAttributes[$code] = $this->buildEditableAttributeConfig($blockType, $code, $attribute);
        }
        
        foreach ($editableAttributeFields as $fieldId => $attributeField) {
            $config = $this->buildEditableAttributeFieldConfig(
                $blockType,
                $fieldId,
                $attributeField,
                $editableAttributes
            );
            
            if (!$config instanceof BL_CustomGrid_Model_Grid_Editor_Value_Config) {
                unset($editableAttributeFields[$fieldId]);
            } else {
                $editableAttributeFields[$fieldId] = $config;
            }
        }
        
        return array(
            BL_CustomGrid_Model_Grid_Editor_Abstract::EDITABLE_TYPE_FIELD => $editableFields,
            BL_CustomGrid_Model_Grid_Editor_Abstract::EDITABLE_TYPE_ATTRIBUTE => $editableAttributes,
            BL_CustomGrid_Model_Grid_Editor_Abstract::EDITABLE_TYPE_ATTRIBUTE_FIELD => $editableAttributeFields,
        );
    }
}
