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

abstract class BL_CustomGrid_Model_Grid_Type_Abstract extends Varien_Object
{
    /**
    * Available attributes per block type
    * 
    * @var array
    */
    protected $_attributes     = array();
    /**
    * Locked collection columns values
    * 
    * @var array
    */
    protected $_lockedValues   = array();
    /**
    * Available export types
    * 
    * @var array
    */
    protected $_exportTypes    = array();
    /**
    * Editable values per block type
    * 
    * @var array
    */
    protected $_editableValues = array();
    /**
    * Available custom columns
    * 
    * @var array
    */
    protected $_customColumns  = null;
    /**
    * Custom columns groups
    * 
    * @var array
    */
    protected $_columnsGroups  = array();
    /**
    * Available custom columns per block type
    * 
    * @var array
    */
    protected $_blocksColumns  = array();
    
    const EDITABLE_TYPE_FIELD           = 'static';
    const EDITABLE_TYPE_ATTRIBUTE       = 'attribute';
    const EDITABLE_TYPE_ATTRIBUTE_FIELD = 'attribute_field';
    
    /**
    * Return whether this grid type can be used to handle given custom grid
    * 
    * @param string $type Grid block type
    * @param string $rewritingClassName Name of the class rewriting given block type
    * @return bool
    */
    abstract public function isAppliableToGrid($type, $rewritingClassName);
    
    protected function _getLayout()
    {
        return Mage::getSingleton('core/layout');
    }
    
    protected function _getRequest()
    {
        $controller = Mage::app()->getFrontController();
        
        if ($controller) {
            $this->_request = $controller->getRequest();
        } else {
            throw new Exception(Mage::helper('core')->__('Can\'t retrieve request object'));
        }
        
        return $this->_request;
    }
    
    /**
    * Return whether given grid model matches given grid block type and ID
    * 
    * @param string $blockType Grid block type
    * @param string $blockId Grid block ID
    * @param BL_CustomGrid_Model_Grid $model Grid Model
    * @return bool
    */
    public function matchGridBlock($blockType, $blockId, $model)
    {
        return (($blockType == $model->getBlockType()) && ($blockId == $model->getBlockId()));
    }
    
    /**
    * Return locked values for grid columns (user won't be able to change them)
    * Here are the possible array keys to use :
    * - "header"   : header title
    * - "width"    : width
    * - "align"    : alignment (must correspond to BL_CustomGrid_Model_Grid aligment constants)
    * - "renderer" : code of the collection renderer  to force using,
    *                if the key is set but does not correspond to any renderer, then no renderer will be choosable at all
    * - "renderer_label" : if no renderer can be choosen and forced renderer is not found, 
    *                      this label will be displayed instead of renderer select
    * - "config_values"  : array of other locked values to put in column config array,
    *                      when corresponding column is added to grid
    * 
    * @param string $type Grid block type
    * @return array
    */
    protected function _getColumnsLockedValues($type)
    {
        return array();
    }
    
    /**
    * Return locked values for grid columns (user won't be able to change them)
    * Wrapper for _getColumnsLockedValues(), with cache
    * 
    * @param string $type Grid block type
    * @return array
    */
    public function getColumnsLockedValues($type)
    {
        if (!isset($this->_lockedValues[$type])) {
            $values = $this->_getColumnsLockedValues($type);
            $this->_lockedValues[$type] = (is_array($values) ? $values : array());
        }
        return $this->_lockedValues[$type];
    }
    
    /**
    * Return locked values for a given column (user won't be able to change them)
    * 
    * @param string $type Grid block type
    * @param string $columnId Column ID
    * @return array
    */
    public function getColumnLockedValues($type, $columnId)
    {
        $values = $this->getColumnsLockedValues($type);
        return (isset($values[$columnId]) ? $values[$columnId] : false);
    }
    
    /**
    * Return whether attribute columns are available
    * 
    * @param string $type Grid block type
    * @return bool
    */
    public function canHaveAttributeColumns($type)
    {
        return false;
    }
    
    /**
    * Return whether given attribute can be considered as available
    * 
    * @param string $type Grid block type
    * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute model
    * @return bool
    */
    protected function _isAvailableAttribute($type, $attribute)
    {
        return ((!$attribute->hasIsVisible() || $attribute->getIsVisible())
                && ($attribute->getBackend()->getType() != 'static')
                && $attribute->getFrontend()->getInputType());
    }
    
    /**
    * Return available attributes
    * 
    * @param string $type Grid block type
    * @return array
    */
    protected function _getAvailableAttributes($type)
    {
        return array();
    }
    
    /**
    * Return available attributes
    * Wrapper for _getAvailableAttributes(), with cache
    * 
    * @param string $type Grid block type
    * @return array
    */
    public function getAvailableAttributes($type, $withEditableFlag=false)
    {
        if (!isset($this->_attributes[$type])) {
            $attributes = $this->_getAvailableAttributes($type);
            $response   = new Varien_Object(array('attributes' => $attributes));
            
            Mage::dispatchEvent('blcg_grid_type_available_attributes', array(
                'response'   => $response,
                'type_model' => $this,
                'block_type' => $type,
            ));
            
            $this->_attributes[$type] = $response->getAttributes();
        }
        
        $attributes = $this->_attributes[$type];
        
        if ($withEditableFlag) {
            $editable = $this->getEditableAttributes($type);
            foreach ($attributes as $attribute) {
                $attribute->setEditableValues(isset($editable[$attribute->getAttributeCode()]));
            }
        }
        
        return $attributes;
    }
    
    /**
    * Return whether grid results are exportable with this module
    * 
    * @param string $type Grid block type
    * @return bool
    */
    public function canExport($type)
    {
        return true;
    }
    
    /**
    * Return available export types
    * 
    * @param string $type Grid block type
    * @return array
    */
    protected function _getExportTypes($type)
    {
        return array(
            'csv' => array(
                'url'   => 'adminhtml/blcg_custom_grid/exportCsv',
                'label' => Mage::helper('customgrid')->__('CSV'),
            ),
            'xml' => array(
                'url'   => 'adminhtml/blcg_custom_grid/exportExcel', 
                'label' => Mage::helper('customgrid')->__('Excel'),
            ),
        );
    }
    
    /**
    * Return available export types
    * Wrapper for _getExportTypes(), with cache and some values preparation
    * 
    * @param string $gridType Grid block type
    * @return array
    */
    public function getExportTypes($gridType)
    {
        if (!isset($this->_exportTypes[$gridType])) {
            $exportTypes = array();
            foreach ($this->_getExportTypes($gridType) as $type) {
                if (is_array($type)) {
                    $params = array('_current' => true);
                    
                    if (isset($type['params']) && is_array($type['params'])) {
                        $params = array_merge($params, $type['params']);
                    }
                    
                    $exportTypes[] = new Varien_Object(array(
                        'url'   => Mage::helper('adminhtml')->getUrl($type['url'], $params),
                        'label' => $type['label']
                    ));
                }
            }
            $this->_exportTypes[$gridType] = $exportTypes;
        }
        return $this->_exportTypes[$gridType];
    }
    
    /**
    * Return whether request corresponds to an export request from our module for handled grid
    * 
    * @param Mage_Core_Controller_Request_Http $request Request object
    * @param string $gridType Grid block type
    * @return bool
    */
    public function isExportRequest($request, $gridType)
    {
        $action = $request->getRouteName()
            . '/' . $request->getControllerName()
            . '/' . $request->getActionName();
            
        foreach ($this->_getExportTypes($gridType) as $type) {
            if ($type['url'] == $action) {
                return true;
            }
        }
        
        return false;
    }
    
    protected function _sortCustomColumns($a, $b)
    {
        return strcmp($a->getName(), $b->getName());
    }
    
    protected function _getAdditionalCustomColumns()
    {
        return array();
    }
    
    protected function _getCustomColumns()
    {
        if (is_null($this->_customColumns)) {
            // Initialize custom columns from the different available sources
            $xmlColumns =  Mage::getSingleton('customgrid/grid_type')
                ->getTypeCustomColumnsByCode($this->getCode());
            
            $response = new Varien_Object(array('columns' => array()));
            Mage::dispatchEvent('blcg_grid_type_additional_columns', array(
                'response'   => $response,
                'type_model' => $this,
            ));
            
            $this->_customColumns = array_filter(
                array_merge(
                    $this->_getAdditionalCustomColumns(),
                    $xmlColumns,
                    $response->getColumns()
                ),
                create_function('$m', 'return ($m instanceof BL_CustomGrid_Model_Custom_Column_Abstract);')
            );
            
            uasort($this->_customColumns, array($this, '_sortCustomColumns'));
            
            // Initialize corresponding groups
            $defaultGroupId = 1;
            $currentGroupId = 2;
            $this->_columnsGroups = array();
            
            foreach ($this->_customColumns as $column) {
                if ($column->hasGroup()) {
                    if (!$groupId = array_search($column->getGroup(), $this->_columnsGroups)) {
                        $groupId = 'g'.$currentGroupId++;
                        $this->_columnsGroups[$groupId] = $column->getGroup();
                    }
                    $column->setGroupId($groupId);
                } else {
                    $column->setGroupId('g'.$defaultGroupId);
                }
            }
            
            uasort($this->_columnsGroups, 'strcmp');
            $this->_columnsGroups['g1'] = Mage::helper('customgrid')->__('Others');
            $this->_blocksColumns = array();
        }
        return $this->_customColumns;
    }
    
    public function getCustomColumns($blockType=null, $rewritingClassName='', $emptyArray=true)
    {
        if (is_null($blockType)) {
            return $this->_getCustomColumns();
        }
        if (!isset($this->_blocksColumns[$blockType])) {
            $this->_blocksColumns[$blockType] = array();
        }
        if (!isset($this->_blocksColumns[$blockType][$rewritingClassName])) {
            $this->_blocksColumns[$blockType][$rewritingClassName] = array();
            
            foreach ($this->_getCustomColumns() as $id => $column) {
                if ($column->isAvailable($blockType, $rewritingClassName)) {
                    $this->_blocksColumns[$blockType][$rewritingClassName][$id] = $column;
                }
            }
            
            if (empty($this->_blocksColumns[$blockType][$rewritingClassName])) {
                $this->_blocksColumns[$blockType][$rewritingClassName] = false;
            }
        }
        return ($this->_blocksColumns[$blockType][$rewritingClassName]
            ? $this->_blocksColumns[$blockType][$rewritingClassName]
            : ($emptyArray ? array() : null));
    }
    
    public function getCustomColumn($code)
    {
        $columns = $this->_getCustomColumns();
        return (isset($columns[$code]) ? $columns[$code] : null);
    }
    
    public function getCustomColumnsGroups()
    {
        $this->_getCustomColumns();
        return $this->_columnsGroups;
    }
    
    public function canHaveCustomColumns($blockType, $rewritingClassName='')
    {
        return is_array($this->getCustomColumns($blockType, $rewritingClassName, false));
    }
    
    /**
    * @todo refactor all the editor methods - certainly accept [exclusive or not] callbacks for each action (found in each field config)
    *       this will be at least needed by custom columns system
    */
    
    /**
    * Return action URL for given field
    * 
    * @param string $type Grid block type
    * @param string $id Field ID
    * @param array $config Field config
    * @param string $route Action route
    * @return string
    */
    protected function _getFieldUrl($type, $id, $config, $route)
    {
        return Mage::helper('adminhtml')
            ->getUrl($route, array(
                'grid_type'   => $this->getCode(),
                'block_type'  => Mage::helper('core')->urlEncode($type),
                'id'          => $id,
                'origin'      => self::EDITABLE_TYPE_FIELD,
                'is_external' => (!$config['in_grid'] ? 1 : 0),
            ));
    }
    
    /**
    * Return edit URL for given field
    * 
    * @param string $type Grid block type
    * @param string $id Field ID
    * @param array $config Field config
    * @return string
    */
    protected function _getFieldEditUrl($type, $id, $config)
    {
        return $this->_getFieldUrl(
            $type, $id, $config,
            'adminhtml/blcg_custom_grid_editor/edit' . ($config['in_grid'] ? 'InGrid' : '')
        );
    }
    
    /**
    * Return save URL for given field
    * 
    * @param string $type Grid block type
    * @param string $id Field ID
    * @param array $config Field config
    * @return string
    */
    protected function _getFieldSaveUrl($type, $id, $config)
    {
        return $this->_getFieldUrl(
            $type, $id, $config,
            'adminhtml/blcg_custom_grid_editor/save'
        );
    }
    
    /**
    * Return action URL for given attribute
    * 
    * @param string $type Grid block type
    * @param string $code Attribute code
    * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute object
    * @param array $config Attribute config
    * @param string $route Action route
    * @return string
    */
    protected function _getAttributeUrl($type, $code, $attribute, $config, $route)
    {
        return Mage::helper('adminhtml')
            ->getUrl($route, array(
                'grid_type'   => $this->getCode(),
                'block_type'  => Mage::helper('core')->urlEncode($type),
                'id'          => $code,
                'origin'      => self::EDITABLE_TYPE_ATTRIBUTE,
                'is_external' => (!$config['in_grid'] ? 1 : 0),
            ));
    }
    
    /**
    * Return edit URL for given attribute
    * 
    * @param string $type Grid block type
    * @param string $code Attribute code
    * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute object
    * @param array $config Attribute config
    * @return string
    */
    protected function _getAttributeEditUrl($type, $code, $attribute, $config)
    {
        return $this->_getAttributeUrl(
            $type, $code, $attribute, $config,
            'adminhtml/blcg_custom_grid_editor/edit' . ($config['in_grid'] ? 'InGrid' : '')
        );
    }
    
    /**
    * Return save URL for given attribute
    * 
    * @param string $type Grid block type
    * @param string $code Attribute code
    * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute object
    * @param array $config Attribute config
    * @return string
    */
    protected function _getAttributeSaveUrl($type, $code, $attribute, $config)
    {
        return $this->_getAttributeUrl(
            $type, $code, $attribute, $config,
            'adminhtml/blcg_custom_grid_editor/save'
        );
    }
    
    /**
    * Return base editable fields configs
    * 
    * @param string $type Grid block type
    * @return array
    */
    protected function _getBaseEditableFields($type)
    {
        return array();
    }
    
    /**
    * Return all editable fields configs
    * 
    * @param string $type Grid block type
    * @return array
    */
    protected function _getEditableFields($type)
    {
        $response = new Varien_Object(array('fields' => array()));
        
        Mage::dispatchEvent('blcg_grid_type_additional_editable_fields', array(
            'response'   => $response,
            'type_model' => $this,
            'block_type' => $type,
        ));
        
        return array_merge(
            $this->_getBaseEditableFields($type),
            $response->getFields()
        );
    }
    
    /**
    * Return whether given attribute is editable
    * 
    * @param string $type Grid block type
    * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute object
    * @return bool
    */
    protected function _checkAttributeEditability($type, $attribute)
    {
        return ((!$attribute->hasIsVisible() || $attribute->getIsVisible())
            && $attribute->getFrontend()->getInputType());
    }
    
    /**
    * Return additional editable attributes
    * = attributes that are not necessarily intended to be available for display
    * (to use when needed by corresponding attribute fields)
    * 
    * @param string $type Grid block type
    * @return array
    */
    protected function _getAdditionalEditableAttributes($type)
    {
        return array();
    }
    
    /**
    * Return all editable attributes
    * 
    * @param string $type Grid block type
    * @return array
    */
    protected function _getEditableAttributes($type)
    {
        $response = new Varien_Object(array('attributes' => array()));
        Mage::dispatchEvent('blcg_grid_type_additional_editable_attributes', array(
            'response'   => $response,
            'type_model' => $this,
            'block_type' => $type,
        ));
        
        $attributes = array_merge(
            $this->getAvailableAttributes($type),
            $this->_getAdditionalEditableAttributes($type),
            $response->getAttributes()
        );
        
        if (empty($attributes)) {
            return array();
        }
        foreach ($attributes as $code => $attribute) {
            if (!$this->_checkAttributeEditability($type, $attribute)) {
                unset($attributes[$code]);
            }
        }
        
        return $attributes;
    }
    
    /**
    * Build the sub configs arrays for given editable value config
    * 
    * @param string $type Grid block type
    * @param string $id Value ID
    * @param array $config Value config
    * @param array $subConfigs Which sub configs to build (key = config key, value = array of parameters)
    * @return array
    */
    protected function _buildEditableValueSubConfigs($type, $id, $config, $subConfigs)
    {
        $helper = Mage::helper('customgrid/string');
        foreach ($subConfigs as $configKey => $params) {
            $length = strlen($configKey);
            if (!isset($config[$configKey])) {
                $config[$configKey] = array();
            }
            foreach ($config as $key => $value) {
                if (!isset($subConfigs[$key])) {
                    if (substr($key, 0, $length) == $configKey) {
                        unset($config[$key]);
                        $key = substr($key, $length+1);
                        if ($params['camelize']) {
                            $key = $helper->camelize($key);
                        }
                        if (!isset($config[$configKey][$key])) {
                            $config[$configKey][$key] = $value;
                        }
                    }
                }
            }
        }
        return $config;
    }
    
    /**
    * Prepare common config values of given editable field
    * 
    * @param string $type Grid block type
    * @param string $id Field ID
    * @param array $config Field config
    * @return array
    */
    protected function _prepareEditableFieldCommonConfig($type, $id, $config)
    {
        if ($config['type'] == 'editor') {
            $config['layout_handles'][] = 'custom_grid_editor_handle_editor';
        } elseif ($config['type'] == 'date') {
            $config['render_reload'] = true;
            $config['must_filter']   = true;
        }
        return $config;
    }
    
    /**
    * Build given editable field full config from given (maybe incomplete) config
    * 
    * @param string $type Grid block type
    * @param string $id Field ID
    * @param array $config Field config
    * @return array
    */
    protected function _buildEditableFieldConfig($type, $id, $config)
    {
        if (!is_array($config)) {
            $config = array();
        }
        
        // Complete current config with minimum required values
        $config += array(
            /*
            Main values
            */
            'type'              => 'text',        // Field (form) type
            'required'          => false,         // Is field required ?
            'field_name'        => $id,           // Field name (to retrieve from entity)
            'in_grid'           => true,          // Can field be edited directly in the grid ?
            'edit_block_type'   => 'default',     // Type of the edit form block (shortcuts available for this module)
            'render_block_type' => 'default',     // Same thing for saved values rendering
            'ids_key'           => 'identifiers', // Key where to put entities identifiers in request data
            'additional_key'    => 'additional',  // Key where to put additional values in request data
            'values_key'        => 'values',      // Key where to put edited values in request data
            'must_filter'       => false,         // Must edited values be filtered before save ?
            'filter_type'       => null,          // Field type to use for values filtering (if null, main type is used)
            'filter_params'     => array(),       // Parameters to use for values filtering
            'render_reload'     => false,         // Must entity be reloaded after save ? (so suitable value can be retrieved for rendering)
            'column_params'     => array(),       // Additional column parameters (put in additional values - override existing ones)
            'layout_handles'    => array(),       // Layout handles to apply for external edit
            
            /*
            Form values (automatically put in "form" array if preceded by "form_")
            Below  : base form values
            Accept : all values used to create form elements
            */
            'form_id'    => $id,
            'form_name'  => (isset($config['field_name']) ? $config['field_name'] : $id),
            'form_label' => (isset($config['form_title']) ? $config['form_title'] : ''),
            'form_title' => (isset($config['form_label']) ? $config['form_label'] : ''),
            
            /*
            JS window values (for "external edit") (automatically put in "window" array if preceded by "window_")
            Below  : default values for edit, that change from default ones
            Accept : all values accepted by corresponding JS class, keys must correspond (automatically camel-cased)
            */
            'window_width'         => '80%',
            'window_height'        => '80%',
            'window_draggable'     => true,
            'window_resizable'     => true,
            'window_recenter_auto' => false,
            'window_title'         => Mage::helper('customgrid')->__('Edit Value'),
            
            /*
            Renderer values (automatically put in "renderer" array if preceded by "renderer_")
            Below  : default empty parameters array, as an example
            Accept : all values needed/used by current renderer block
            */
            'renderer_params' => array(),
        );
        
        // Finish to prepare config
        $config = $this->_buildEditableValueSubConfigs(
            $type, $id,
            $this->_prepareEditableFieldCommonConfig($type, $id, $config),
            array(
                'form'     => array('camelize' => false),
                'window'   => array('camelize' => true),
                'renderer' => array('camelize' => false),
            )
        );
        if (!isset($config['edit_url'])) {
            $config['edit_url'] = $this->_getFieldEditUrl($type, $id, $config);
        }
        if (!isset($config['save_url'])) {
            $config['save_url'] = $this->_getFieldSaveUrl($type, $id, $config);
        }
        if ($config['in_grid']) {
            unset($config['window']);
        }
        
        return $config;
    }
    
    /**
    * Return the base config for given editable attribute
    * 
    * @param string $type Grid block type
    * @param string $code Attribute code
    * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute object
    * @return array
    */
    protected function _getEditableAttributeBaseConfig($type, $code, $attribute)
    {
        // All those values pretty much correspond to the editable fields configs ones ("missing" ones are induced)
        return array(
            'in_grid' => in_array(
                    $attribute->getFrontend()->getInputType(), 
                    array('date', 'multiselect', 'price', 'select', 'text')
                ),
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
            'window_title'         => Mage::helper('customgrid')->__('Edit Value'),
            
            'renderer_params' => array(),
        );
    }
    
    /**
    * Prepare common config values of given editable attribute
    * 
    * @param string $type Grid block type
    * @param string $code Attribute code
    * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute object
    * @param array $config Field config
    * @return array
    */
    protected function _prepareEditableAttributeCommonConfig($type, $code, $attribute, $config)
    {
        if ($attribute->getBackend()->getType() == 'datetime') {
            $config['render_reload'] = true;
            $config['must_filter']   = true;
            $config['filter_type']   = 'date';
        }
        return $config;
    }
    
    /**
    * Build given editable attribute full config
    * 
    * @param string $type Grid block type
    * @param string $code Attribute code
    * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute object
    * @return array
    */
    protected function _buildEditableAttributeConfig($type, $code, $attribute)
    {
        $config = array_merge(
            $this->_getEditableAttributeBaseConfig($type, $code, $attribute),
            array('attribute' => $attribute)
        );
        
        $config = $this->_buildEditableValueSubConfigs(
            $type, $code,
            $this->_prepareEditableAttributeCommonConfig($type, $code, $attribute, $config),
            array(
                'window'   => array('camelize' => true),
                'renderer' => array('camelize' => false),
            )
        );
        
        if (!isset($config['edit_url'])) {
            $config['edit_url'] = $this->_getAttributeEditUrl($type, $code, $attribute, $config);
        }
        if (!isset($config['save_url'])) {
            $config['save_url'] = $this->_getAttributeSaveUrl($type, $code, $attribute, $config);
        }
        if ($config['in_grid']) {
            unset($config['window']);
        }
        
        return $config;
    }
    
    /**
    * Return base editable attribute fields configs
    * (= all columns that do correspond to an attribute)
    * Used keys :
    * - "attribute" : corresponding attribute code
    * - "config" : array of config values that should override attribute's ones
    * 
    * @param string $type Grid block type
    * @return array
    */
    protected function _getBaseEditableAttributeFields($type)
    {
        return array();
    }
    
    /**
    * Return all editable attribute fields configs
    * 
    * @param string $type Grid block type
    * @return array
    */
    protected function _getEditableAttributeFields($type)
    {
        $response = new Varien_Object(array('attribute_fields' => array()));
        Mage::dispatchEvent('blcg_grid_type_additional_editable_attribute_fields', array(
            'response'   => $response,
            'type_model' => $this,
            'block_type' => $type,
        ));
        
        return array_merge(
            $this->_getBaseEditableAttributeFields($type),
            $response->getAttributeFields()
        );
    }
    
    /**
    * Return editable values configs
    * 
    * @param string $type Grid block type
    * @param string $origin Values origin (if null, all values will be returned)
    * @return array
    */
    public function getEditableValues($type, $origin=null)
    {
        if (!isset($this->_editableValues[$type])) {
            // Build all base configs
            $fields = $this->_getEditableFields($type);
            foreach ($fields as $id => $field) {
               $fields[$id] = $this->_buildEditableFieldConfig($type, $id, $field);
            }
            
            $attributes = $this->_getEditableAttributes($type);
            foreach ($attributes as $code => $attribute) {
                $attributes[$code] = $this->_buildEditableAttributeConfig($type, $code, $attribute);
            }
            
            // Dispatch events for each kind of editable values
            $fieldsResponse = new Varien_Object(array('fields' => $fields));
            $attributesResponse = new Varien_Object(array('attributes' => $attributes));
            $attributeFieldsResponse = new Varien_Object(array('attribute_fields' => $this->_getEditableAttributeFields($type)));
            
            Mage::dispatchEvent('blcg_grid_type_editable_fields', array(
                'response'   => $fieldsResponse,
                'type_model' => $this,
                'block_type' => $type,
            ));
            Mage::dispatchEvent('blcg_grid_type_editable_attributes', array(
                'response'   => $attributesResponse,
                'type_model' => $this,
                'block_type' => $type,
            ));
            Mage::dispatchEvent('blcg_grid_type_editable_attribute_fields', array(
                'response'   => $attributeFieldsResponse,
                'type_model' => $this,
                'block_type' => $type,
            ));
            
            // Cache the results
            $this->_editableValues[$type] = array(
                self::EDITABLE_TYPE_FIELD           => $fieldsResponse->getFields(),
                self::EDITABLE_TYPE_ATTRIBUTE       => $attributesResponse->getAttributes(),
                self::EDITABLE_TYPE_ATTRIBUTE_FIELD => $attributeFieldsResponse->getAttributeFields(),
            );
        }
        if (!is_null($origin)) {
            if (isset($this->_editableValues[$type][$origin])) {
                return $this->_editableValues[$type][$origin];
            } else {
                return array();
            }
        } else {
            return $this->_editableValues[$type];
        }
    }
    
    /**
    * Return editable fields configs
    * 
    * @param string $type Grid block type
    * @return array
    */
    public function getEditableFields($type)
    {
        return $this->getEditableValues($type, self::EDITABLE_TYPE_FIELD);
    }
    
    /**
    * Return editable attributes configs
    * 
    * @param string $type Grid block type
    * @return array
    */
    public function getEditableAttributes($type)
    {
        return $this->getEditableValues($type, self::EDITABLE_TYPE_ATTRIBUTE);
    }
    
    /**
    * Return editable attribute fields configs
    * 
    * @param string $type Grid block type
    * @return array
    */
    public function getEditableAttributeFields($type)
    {
        return $this->getEditableValues($type, self::EDITABLE_TYPE_ATTRIBUTE_FIELD);
    }
    
    /**
    * Apply editable values configs to given grid columns
    * 
    * @param string $type Grid block type
    * @param array $columns Grid columns
    * @param BL_CustomGrid_Model_Grid $gridModel Custom grid model
    * @return array
    */
    public function applyEditableConfigsToColumns($type, $columns, $gridModel)
    {
        // Keep only interesting/consistent values
        $keptKeys = array(
            'in_grid',
            'ids_key',
            'additional_key',
            'edit_url', 
            'save_url',
            'window',
            'column_params'
        );
        
        foreach ($columns as $columnId => $column) {
            $column['editable'] = false;
            
            if ($gridModel->isAttributeColumnOrigin($column['origin'])) {
                // Editable attributes
                if ($this->isEditableAttribute($type, $column['index'])) {
                    $columns[$columnId]['editable'] = $this->getEditableAttribute($type, $column['index'], $keptKeys);
                    if (isset($column['store_id']) && $column['store_id']
                        && !isset($columns[$columnId]['editable']['column_params']['column_store_id'])) {
                        // Apply column's store ID (if not admin) to columns params, to allow editing values by store view
                        $columns[$columnId]['editable']['column_params']['column_store_id'] = $column['store_id'];
                    }
                }
            } else {
                // Editable (attribute) fields
                if ($this->isEditableField($type, $columnId)) {
                    $columns[$columnId]['editable'] = $this->getEditableField($type, $columnId, $keptKeys);
                } elseif ($this->isEditableAttributeField($type, $columnId)){
                    $columns[$columnId]['editable'] = $this->getEditableAttributeField($type, $columnId, $keptKeys, true, false);
                }
            }
            
            if (isset($columns[$columnId]['editable'])
                && is_array($columns[$columnId]['editable'])) {
                $columns[$columnId]['editable']['column_params']['column_id'] = $column['column_id'];
            }
        }
        
        return $columns;
    }
    
    /**
    * Return editable value config
    * 
    * @param string $type Grid block type
    * @param string $id Value ID
    * @param string $origin Value origin
    * @param array $onlyKeys If set, only those config keys will be returned
    * @return array
    */
    public function getEditableValue($type, $id, $origin, $onlyKeys=null)
    {
        $editable = $this->getEditableValues($type, $origin);
        return (isset($editable[$id]) 
            ? (is_array($onlyKeys) ? array_intersect_key($editable[$id], array_flip($onlyKeys)) : $editable[$id])
            : null);
    }
    
    /**
    * Return editable field config
    * 
    * @param string $type Grid block type
    * @param string $id Field ID
    * @param array $onlyKeys If set, only those config keys will be returned
    * @return array
    */
    public function getEditableField($type, $id, $onlyKeys=null)
    {
       return $this->getEditableValue($type, $id, self::EDITABLE_TYPE_FIELD, $onlyKeys);
    }
    
    /**
    * Return editable attribute config
    * 
    * @param string $type Grid block type
    * @param string $code Attribute code
    * @param array $onlyKeys If set, only those config keys will be returned
    * @return array
    */
    public function getEditableAttribute($type, $code, $onlyKeys=null)
    {
        return $this->getEditableValue($type, $code, self::EDITABLE_TYPE_ATTRIBUTE, $onlyKeys);
    }
    
    /**
    * Return editable attribute field config
    * 
    * @param string $type Grid block type
    * @param string $id Field ID
    * @param array $onlyKeys If set, only those config keys will be returned
    * @param bool $attribute Whether corresponding attribute config should be returned
    * @param mixed $default Default value to return if attribute field or corresponding attribute doesn't exist
    * @return array
    */
    public function getEditableAttributeField($type, $id, $onlyKeys=null, $attribute=false, $default=null)
    {
        if ($config = $this->getEditableValue($type, $id, self::EDITABLE_TYPE_ATTRIBUTE_FIELD)) {
            if (isset($config['attribute'])
                && $this->isEditableAttribute($type, $config['attribute'])) {
                return array_merge_recursive(
                    $this->getEditableAttribute($type, $config['attribute'], $onlyKeys),
                    (isset($config['config']) ? $config['config'] : array())
                );
            }
        }
        return $default;
    }
    
    /**
    * Return whether given value is editable
    * 
    * @param string $type Grid block type
    * @param string $id Value ID
    * @param string $origin Value origin
    * @return bool
    */
    public function isEditableValue($type, $id, $origin)
    {
        return array_key_exists($id, $this->getEditableValues($type, $origin));
    }
    
    /**
    * Return whether given field is editable
    * 
    * @param string $type Grid block type
    * @param string $id Field ID
    * @return bool
    */
    public function isEditableField($type, $id)
    {
        return $this->isEditableValue($type, $id, self::EDITABLE_TYPE_FIELD);
    }
    
    /**
    * Return whether given attribute is editable
    * 
    * @param string $type Grid block type
    * @param string $code Attribute code
    * @return bool
    */
    public function isEditableAttribute($type, $code)
    {
        return $this->isEditableValue($type, $code, self::EDITABLE_TYPE_ATTRIBUTE);
    }
    
    /**
    * Return whether given attribute field is editable
    * 
    * @param string $type Grid block type
    * @param string $id Field ID
    * @return bool
    */
    public function isEditableAttributeField($type, $id)
    {
        return $this->isEditableValue($type, $id, self::EDITABLE_TYPE_ATTRIBUTE_FIELD);
    }
    
    /**
    * Return value edit additional parameters
    * 
    * @param string $type Grid block type
    * @param Mage_Adminhtml_Block_Widget_Grid $grid Grid block
    * @return array
    */
    public function getAdditionalEditParams($type, $grid)
    {
        return array();
    }
    
    /**
    * Extract edit values from given request
    * 
    * @param string $type Grid block type
    * @param array $config Edited value config
    * @param Mage_Core_Controller_Request_Http $request Request object
    * @return array
    */
    protected function _extractRequestEditValues($type, $config, $request)
    {
        $idsKey        = $config['config']['ids_key'];
        $additionalKey = $config['config']['additional_key'];
        $valuesKey     = $config['config']['values_key'];
        
        $params = array(
            'ids'        => $request->getParam($idsKey, array()),
            'additional' => $request->getParam($additionalKey, array()),
            'values'     => $request->getParam($valuesKey, array()),
            'global'     => array_diff_key($request->getParams(), array_flip(array($idsKey, $additionalKey, $valuesKey))),
        );
        
        return array_map(create_function('$a', 'return (is_array($a) ? $a : array());'), $params);
    }
    
    /**
    * Return entity identifiers keys
    * 
    * @param string $type Grid block type
    * @return array
    */
    protected function _getEntityRowIdentifiersKeys($type)
    {
        return array('id');
    }
    
    /**
    * Return entity identifier from given row
    * 
    * @param string $type Grid block type
    * @param Varien_Object $row Entity row
    * @param string $key Identifier key
    * @return mixed
    */
    protected function _getEntityRowIdentifier($type, Varien_Object $row, $key)
    {
        return $row->getData($key);
    }
    
    /**
    * Return entity identifiers from given row
    * 
    * @param string $type Grid block type
    * @param Varien_Object $row Entity row
    * @return array
    */
    public function getEntityRowIdentifiers($type, Varien_Object $row)
    {
        $identifiers = array();
        foreach ($this->_getEntityRowIdentifiersKeys($type) as $key) {
            $identifiers[$key] = $this->_getEntityRowIdentifier($type, $row, $key);
        }
        return $identifiers;
    }
    
    /**
    * Load edited entity
    * 
    * @param string $type Grid block type
    * @param array $config Edited value config
    * @param array $params Edit parameters
    * @return mixed
    */
    protected function _loadEditedEntity($type, $config, $params)
    {
        return null;
    }
    
    /**
    * Reload edited entity
    * 
    * @param string $type Grid block type
    * @param array $config Edited value config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @return mixed
    */
    protected function _reloadEditedEntity($type, $config, $params, $entity)
    {
        return $entity->load($entity->getId());
    }
    
    /**
    * Return whether edited entity has been successfully loaded
    * 
    * @param string $type Grid block type
    * @param array $config Edited value config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @return bool
    */
    protected function _isEditedEntityLoaded($type, $config, $params, $entity)
    {
        return (is_object($entity) ? ($entity->getId() ? true : false) : false);
    }
    
    /**
    * Return keys to use to register edited entity
    * 
    * @param string $type Grid block type
    * @param array $config Edited value config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @return array
    */
    protected function _getEditedEntityRegistryKeys($type, $config, $params, $entity)
    {
        return array();
    }
    
    /**
    * Register edited entity
    * 
    * @param string $type Grid block type
    * @param array $config Edited value config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @return this
    */
    protected function _registerEditedEntity($type, $config, $params, $entity)
    {
        foreach ($this->_getEditedEntityRegistryKeys($type, $config, $params, $entity) as $key) {
            Mage::register($key, $entity);
        }
        return $this;
    }
    
    /**
    * Return edited entity name
    * 
    * @param string $type Grid block type
    * @param array $config Edited value config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @return string
    */
    protected function _getLoadedEntityName($type, $config, $params, $entity)
    {
        return $entity->getName();
    }
    
    /**
    * Return whether given field is editable for given entity
    * 
    * @param string $type Grid block type
    * @param array $config Edited value config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @return bool
    */
    protected function _checkEntityEditableField($type, $config, $params, $entity)
    {
        return true;
    }
    
    /**
    * Return whether given attribute is editable for given entity
    * 
    * @param string $type Grid block type
    * @param array $config Edited value config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @return bool
    */
    protected function _checkEntityEditableAttribute($type, $config, $params, $entity)
    {
        return true;
    }
    
    /**
    * Return whether givne value is editable for given entity
    * 
    * @param string $type Grid block type
    * @param array $config Edited value config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @return bool
    */
    protected function _checkEntityEditableValue($type, $config, $params, $entity)
    {
        if ($config['origin'] == self::EDITABLE_TYPE_FIELD) {
            return $this->_checkEntityEditableField($type, $config, $params, $entity);
        } elseif ($config['origin'] == self::EDITABLE_TYPE_ATTRIBUTE) {
            return $this->_checkEntityEditableAttribute($type, $config, $params, $entity);
        } else {
            return false;
        }
    }
    
    /**
    * Return field edit block instance
    * 
    * @param string $type Grid block type
    * @param array $config Edited field config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @return Mage_Core_Block_Abstract
    */
    protected function _getFieldEditBlock($type, $config, $params, $entity)
    {
        $blockType = $config['config']['edit_block_type'];
        if (strpos($blockType, '/') === false) {
            $blockType = 'customgrid/widget_grid_form_static_'.$blockType;
        }
        return $this->_getLayout()->createBlock($blockType, '', array(
            'edited_entity'      => $entity,
            'edited_entity_name' => $this->_getLoadedEntityName($type, $config, $params, $entity),
            'edited_value'       => $config,
            'edited_config'      => $config['config'],
            'edited_in_grid'     => $config['config']['in_grid'],
            'edit_params'        => $params,
            'grid_block_type'    => $type,
        ));
    }
    
    /**
    * Prepare field edit block before display
    * 
    * @param string $type Grid block type
    * @param array $config Edited field config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @param Mage_Core_Block_Abstract $block Edit block
    * @return Mage_Core_Block_Abstract
    */
    protected function _prepareFieldEditBlock($type, $config, $params, $entity, $block)
    {
        return $block;
    }
    
    /**
    * Return layout handles to apply for given field edit
    * (only used for external edit)
    * 
    * @param string $type Grid block type
    * @param array $config Edited field config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @return array
    */
    protected function _getFieldEditLayoutHandles($type, $config, $params, $entity)
    {
        return $config['config']['layout_handles'];
    }
    
    /**
    * Return attribute edit block instance
    * 
    * @param string $type Grid block type
    * @param array $config Edited attribute config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @return Mage_Core_Block_Abstract
    */
    protected function _getAttributeEditBlock($type, $config, $params, $entity)
    {
        $blockType = $config['config']['edit_block_type'];
        if (strpos($blockType, '/') === false) {
            $blockType = 'customgrid/widget_grid_form_attribute_'.$blockType;
        }
        return $this->_getLayout()->createBlock($blockType, '', array(
            'edited_entity'      => $entity,
            'edited_entity_name' => $this->_getLoadedEntityName($type, $config, $params, $entity),
            'edited_attribute'   => $config['config']['attribute'],
            'edited_value'       => $config,
            'edited_config'      => $config['config'],
            'edited_in_grid'     => $config['config']['in_grid'],
            'edit_params'        => $params,
            'grid_block_type'    => $type,
        ));
    }
    
    /**
    * Prepare attribute edit block before display
    * 
    * @param string $type Grid block type
    * @param array $config Edited attribute config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @param Mage_Core_Block_Abstract $block Edit block
    * @return Mage_Core_Block_Abstract
    */
    protected function _prepareAttributeEditBlock($type, $config, $params, $entity, $block)
    {
        return $block;
    }
    
    /**
    * Return layout handles to apply for given attribute edit
    * (only used for external edit)
    * 
    * @param string $type Grid block type
    * @param array $config Edited attribute config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @return array
    */
    protected function _getAttributeEditLayoutHandles($type, $config, $params, $entity)
    {
        return $config['config']['layout_handles'];
    }
    
    /**
    * Return value edit block instance
    * 
    * @param string $type Grid block type
    * @param array $config Edited value config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @return Mage_Core_Block_Abstract
    */
    protected function _getValueEditBlock($type, $config, $params, $entity)
    {
        if ($config['origin'] == self::EDITABLE_TYPE_FIELD) {
            if ($block = $this->_getFieldEditBlock($type, $config, $params, $entity)) {
                return $this->_prepareFieldEditBlock($type, $config, $params, $entity, $block);
            } else {
                return null;
            }
        } elseif ($config['origin'] == self::EDITABLE_TYPE_ATTRIBUTE) {
            if ($block = $this->_getAttributeEditBlock($type, $config, $params, $entity)) {
                return $this->_prepareAttributeEditBlock($type, $config, $params, $entity, $block);
            } else {
                return null;
            }
        } else {
            return null;
        }
    }
    
    /**
    * Return layout handles to apply for given value edit
    * (only used for external edit)
    * 
    * @param string $type Grid block type
    * @param array $config Edited value config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @return array
    */
    protected function _getValueEditLayoutHandles($type, $config, $params, $entity)
    {
        if ($config['origin'] == self::EDITABLE_TYPE_FIELD) {
            return $this->_getFieldEditLayoutHandles($type, $config, $params, $entity);
        } elseif ($config['origin'] == self::EDITABLE_TYPE_ATTRIBUTE) {
            return $this->_getAttributeEditLayoutHandles($type, $config, $params, $entity);
        } else {
            return array();
        }
    }
    
    /**
    * Check if user has ACL permissions to edit values
    * 
    * @param string $type Grid block type
    * @param BL_CustomGrid_Model_Grid Grid model
    * @param Mage_Adminhtml_Block_Widget_Grid $grid Grid block instance (when known)
    * @param array $params Edit parameters (when grid block not known)
    * @return bool
    */
    public function checkUserEditPermissions($type, $model, $block=null, $params=array())
    {
        return $model->checkUserActionPermission(BL_CustomGrid_Model_Grid::GRID_ACTION_EDIT_COLUMNS_VALUES);
    }
    
    /**
    * Check if user can edit given value
    * 
    * @param string $type Grid block type
    * @param array $config Edited value config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @param BL_CustomGrid_Model_Grid $model Custom grid model
    * @return bool
    */
    protected function _canEditValue($type, $config, $params, $entity, $model)
    {
        if ($this->isEditableValue($type, $config['id'], $config['origin'])) {
            if (isset($params['additional']['column_id'])
                && ($column = $model->getColumnFromDbId($params['additional']['column_id']))
                && $column['allow_edit']) {
                return $this->checkUserEditPermissions($type, $model, null, $params);
            }
        }
        return false;
    }
    
    /**
    * Return value edit block corresponding to given value and request,
    * either as instance or HTML
    * 
    * @param string $type Grid block type
    * @param string $id Value ID
    * @param string $origin Value origin
    * @param Mage_Core_Controller_Request_Http $request Request object
    * @param BL_CustomGrid_Model_Grid $model Custom grid model
    * @param bool $asHtml Whether block HTML should be returned instead of its instance
    * @param bool $addHandles Whether suitable layout handles should be applied
    * @return mixed
    */
    public function getValueEditBlock($type, $id, $origin, $request, $model, $asHtml=true, $addHandles=false)
    {
        if ($config = $this->getEditableValue($type, $id, $origin)) {
            $valueConfig = compact('id', 'origin', 'config');
            $editParams  = $this->_extractRequestEditValues($type, $valueConfig, $request);
            $entity      = $this->_loadEditedEntity($type, $valueConfig, $editParams);
            
            if ($this->_isEditedEntityLoaded($type, $valueConfig, $editParams, $entity)) {
                $this->_registerEditedEntity($type, $valueConfig, $editParams, $entity);
                
                if ($this->_canEditValue($type, $valueConfig, $editParams, $entity, $model)
                    && $this->_checkEntityEditableValue($type, $valueConfig, $editParams, $entity)) {
                    if ($block = $this->_getValueEditBlock($type, $valueConfig, $editParams, $entity)) {
                        if ($addHandles) {
                            /*
                            Use our observer to add layout handles just before layout load :
                            it will ensure that "default" handle (and even most of others, if not all)
                            will be handled before our new ones, which is what we need
                            */
                            Mage::getSingleton('customgrid/observer')->addAdditionalLayoutHandle(
                                $this->_getValueEditLayoutHandles($type, $valueConfig, $editParams, $entity)
                            );
                        }
                        return ($asHtml ? $block->toHtml() : $block);
                    } else {
                        Mage::throwException(Mage::helper('customgrid')->__('The value edit block could not be retrieved'));
                    }
                } else {
                    Mage::throwException(Mage::helper('customgrid')->__('This value is not editable'));
                }
            } else {
                Mage::throwException(Mage::helper('customgrid')->__('The edited entity could not be loaded'));
            }
        } else {
            Mage::throwException(Mage::helper('customgrid')->__('This value is not editable'));
        }
    }
    
    /**
    * Dispatch given save event
    * 
    * @param string $type Grid block type
    * @param array $config Edited field config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @param mixed $value Edited field val
    * @param string $eventName Dispatched event name
    * @return this
    */
    protected function _dispatchSaveEvent($type, $config, $params, $entity, $value, $eventName)
    {
        Mage::dispatchEvent($eventName, array(
            'type_model'  => $this,
            'block_type'  => $type,
            'edit_config' => $config,
            'edit_params' => $params,
            'entity'      => $entity,
            'value'       => $value
        ));
        return $this;
    }
    
    /**
    * Filter given edited value
    * 
    * @param string $type Grid block type
    * @param array $config Edited value config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @param mixed $value Value to filter
    * @param string $filterType Kind of filter to apply
    * @param array $filterParams Filter parameters
    * @return mixed
    */
    protected function _filterEditedValue($type, $config, $params, $entity, $value, $filterType, $filterParams)
    {
        switch ($filterType) {
            case 'date';
                $value = Mage::helper('customgrid/editor')->filterDateValue($value);
                break;
        }
        return $value;
    }
    
    /**
    * Return edited field value
    * 
    * @param string $type Grid block type
    * @param array $config Edited field config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @param string $formName Shortcut for value's form name
    * @return mixed
    */
    protected function _getEditedFieldValue($type, $config, $params, $entity, $formName)
    {
        if (isset($params['values'][$formName])) {
            return $params['values'][$formName];
        } else {
            Mage::throwException(Mage::helper('customgrid')->__('No value given'));
        }
    }
    
    /**
    * Filter edited field value
    * 
    * @param string $type Grid block type
    * @param array $config Edited field config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @param mixed $value Edited field value
    * @return mixed
    */
    protected function _filterEditedFieldValue($type, $config, $params, $entity, $value)
    {
        $filterType   = $config['config'][(isset($config['config']['filter_type']) ? 'filter_type' : 'type')];
        $filterParams = $config['config']['filter_params'];
        return $this->_filterEditedValue($type, $config, $params, $entity, $value, $filterType, $filterParams);
    }
    
    /**
    * Do some actions before field value is applied to edited entity (such as filtering when needed)
    * 
    * @param string $type Grid block type
    * @param array $config Edited field config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @param mixed $value Edited field value
    * @return this
    */
    protected function _beforeApplyEditedFieldValue($type, $config, $params, $entity, &$value)
    {
        if ($config['config']['must_filter']) {
            $value = $this->_filterEditedFieldValue($type, $config, $params, $entity, $value);
        }
        return $this;
    }
    
    /**
    * Apply edited field value to edited entity
    * 
    * @param string $type Grid block type
    * @param array $config Edited field config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @param mixed $value Edited field value
    * @return this
    */
    protected function _applyEditedFieldValue($type, $config, $params, $entity, $value)
    {
        $entity->setData($config['config']['field_name'], $value);
        return $this;
    }
    
    /**
    * Do some actions before field value is saved
    * 
    * @param string $type Grid block type
    * @param array $config Edited field config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @param mixed $value Edited field value
    * @return this
    */
    protected function _beforeSaveEditedFieldValue($type, $config, $params, $entity, $value)
    {
        $this->_dispatchSaveEvent($type, $config, $params, $entity, $value, 'blcg_grid_type_before_save_field_value');
        return $this;
    }
    
    /**
    * Save edited entity
    * 
    * @param string $type Grid block type
    * @param array $config Edited field config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @param mixed $value Edited field value
    * @return bool
    */
    protected function _saveEditedFieldValue($type, $config, $params, $entity, $value)
    {
        $entity->save();
        return true;
    }
    
    /**
    * Do some actions after field value is saved (such as reloading when needed)
    * 
    * @param string $type Grid block type
    * @param array $config Edited field config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @param mixed $value Edited field value
    * @param bool $result Save result
    * @return this
    */
    protected function _afterSaveEditedFieldValue($type, $config, $params, $entity, $value, $result)
    {
        if ($config['config']['render_reload']) {
            $this->_reloadEditedEntity($type, $config, $params, $entity);
        }
        $this->_dispatchSaveEvent($type, $config, $params, $entity, $value, 'blcg_grid_type_after_save_field_value');
        return $this;
    }
    
    /**
    * Return edited attribute value
    * 
    * @param string $type Grid block type
    * @param array $config Edited attribute config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @param string $formName Shortcut for value's form name
    * @return mixed
    */
    protected function _getEditedAttributeValue($type, $config, $params, $entity, $formName)
    {
        if (isset($params['values'][$formName])) {
            return $params['values'][$formName];
        } else {
            Mage::throwException(Mage::helper('customgrid')->__('No value given'));
        }
    }
    
    /**
    * Filter edited attribute value
    * 
    * @param string $type Grid block type
    * @param array $config Edited attribute config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @param mixed $value Edited attribute value
    * @return mixed
    */
    protected function _filterEditedAttributeValue($type, $config, $params, $entity, $value)
    {
        $filterType   = $config['config']['filter_type'];
        $filterType   = ($filterType ? $filterType : $config['config']['attribute']->getFrontend()->getInputType());
        $filterParams = $config['config']['filter_params'];
        return $this->_filterEditedValue($type, $config, $params, $entity, $value, $filterType, $filterParams);
    }
    
    /**
    * Do some actions before attribute value is applied to edited entity (such as filtering when needed)
    * 
    * @param string $type Grid block type
    * @param array $config Edited attribute config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @param mixed $value Edited attribute value
    * @return this
    */
    protected function _beforeApplyEditedAttributeValue($type, $config, $params, $entity, &$value)
    {
        if ($config['config']['must_filter']) {
            $value = $this->_filterEditedAttributeValue($type, $config, $params, $entity, $value);
        }
        return $this;
    }
    
    /**
    * Apply edited attribute value to edited entity
    * 
    * @param string $type Grid block type
    * @param array $config Edited attribute config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @param mixed $value Edited attribute value
    * @return this
    */
    protected function _applyEditedAttributeValue($type, $config, $params, $entity, $value)
    {
        $entity->setData($config['config']['attribute']->getAttributeCode(), $value);
        return $this;
    }
    
    /**
    * Do some actions before attribute value is saved
    * 
    * @param string $type Grid block type
    * @param array $config Edited attribute config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @param mixed $value Edited attribute value
    * @return this
    */
    protected function _beforeSaveEditedAttributeValue($type, $config, $params, $entity, $value)
    {
        $this->_dispatchSaveEvent($type, $config, $params, $entity, $value, 'blcg_grid_type_before_save_attribute_value');
        return $this;
    }
    
    /**
    * Save edited entity
    * 
    * @param string $type Grid block type
    * @param array $config Edited attribute config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @param mixed $value Edited attribute value
    * @return bool
    */
    protected function _saveEditedAttributeValue($type, $config, $params, $entity, $value)
    {
        $entity->save();
        return true;
    }
    
    /**
    * Do some actions after field value is saved (such as reloading when needed)
    * 
    * @param string $type Grid block type
    * @param array $config Edited field config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @param mixed $value Edited field value
    * @param bool $result Save result
    * @return this
    */
    protected function _afterSaveEditedAttributeValue($type, $config, $params, $entity, $value, $result)
    {
        if ($config['config']['render_reload']) {
            $this->_reloadEditedEntity($type, $config, $params, $entity);
        }
        $this->_dispatchSaveEvent($type, $config, $params, $entity, $value, 'blcg_grid_type_after_save_attribute_value');
        return $this;
    }
    
    /**
    * Save edited value
    * 
    * @param string $type Grid block type
    * @param array $config Edited value config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @return bool
    */
    protected function _saveEditedValue($type, $config, $params, $entity)
    {
        if ($config['origin'] == self::EDITABLE_TYPE_FIELD) {
            $value  = $this->_getEditedFieldValue(
                $type,
                $config,
                $params,
                $entity,
                $config['config']['form']['name']
            );
            
            $result = $this->_beforeApplyEditedFieldValue($type, $config, $params, $entity, $value)
                ->_applyEditedFieldValue($type, $config, $params, $entity, $value)
                ->_beforeSaveEditedFieldValue($type, $config, $params, $entity, $value)
                ->_saveEditedFieldValue($type, $config, $params, $entity, $value);
            
            $this->_afterSaveEditedFieldValue($type, $config, $params, $entity, $value, $result);
            return $result;
        } elseif ($config['origin'] == self::EDITABLE_TYPE_ATTRIBUTE) {
            $value  = $this->_getEditedAttributeValue(
                $type,
                $config,
                $params,
                $entity,
                $config['config']['attribute']->getAttributeCode()
            );
            
            $result = $this->_beforeApplyEditedAttributeValue($type, $config, $params, $entity, $value)
                ->_applyEditedAttributeValue($type, $config, $params, $entity, $value)
                ->_beforeSaveEditedAttributeValue($type, $config, $params, $entity, $value)
                ->_saveEditedAttributeValue($type, $config, $params, $entity, $value);
            
            $this->_afterSaveEditedAttributeValue($type, $config, $params, $entity, $value, $result);
            return $result;
        } else {
            return false;
        }
    }
    
    /**
    * Return saved field value, ready for a future rendering
    * 
    * @param string $type Grid block type
    * @param array $config Edited field config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @return mixed
    */
    protected function _getSavedFieldValueForRender($type, $config, $params, $entity)
    {
        return $entity->getData($config['config']['field_name']);
    }
    
    /**
    * Return renderer block instance for saved field value
    * 
    * @param string $type Grid block type
    * @param array $config Edited field config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @param mixed $value Prepared field value
    * @return Mage_Core_Block_Abstract
    */
    protected function _getSavedFieldValueRendererBlock($type, $config, $params, $entity, $value)
    {
        $blockType = $config['config']['render_block_type'];
        if (strpos($blockType, '/') === false) {
            $blockType = 'customgrid/widget_grid_editor_renderer_static_'.$blockType;
        }
        return $this->_getLayout()->createBlock($blockType, '', array(
            'edited_entity'    => $entity,
            'edited_value'     => $config,
            'edited_config'    => $config['config'],
            'renderable_value' => $value,
            'edit_params'      => $params,
            'grid_block_type'  => $type,
        ));
    }
    
    /**
    * Prepare field value renderer block before display
    * 
    * @param string $type Grid block type
    * @param array $config Edited field config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @param mixed $value Prepared field value
    * @param Mage_Core_Block_Abstract $block Renderer block instance
    * @return Mage_Core_Block_Abstract
    */
    protected function _prepareSavedFieldValueRendererBlock($type, $config, $params, $entity, $value, $block)
    {
        return $block;
    }
    
    /**
    * Return saved field value, adapted for a render in grids
    * 
    * @param string $type Grid block type
    * @param array $config Edited field config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @return mixed
    */
    protected function _getRenderableSavedFieldValue($type, $config, $params, $entity)
    {
        $value = $this->_getSavedFieldValueForRender($type, $config, $params, $entity);
        if ($block = $this->_getSavedFieldValueRendererBlock($type, $config, $params, $entity, $value)) {
            $block = $this->_prepareSavedFieldValueRendererBlock($type, $config, $params, $entity, $value, $block);
            return $block->toHtml();
        } else {
            return Mage::helper('customgrid')->__('<em>Updated</em>');
        }
    }
    
    /**
    * Return saved attribute value, ready for a future rendering
    * 
    * @param string $type Grid block type
    * @param array $config Edited attribute config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @return mixed
    */
    protected function _getSavedAttributeValueForRender($type, $config, $params, $entity)
    {
        return $config['config']['attribute']->getFrontend()->getValue($entity);
    }
    
    /**
    * Return renderer block instance for saved attribute value
    * 
    * @param string $type Grid block type
    * @param array $config Edited attribute config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @param mixed $value Prepared attribute value
    * @return Mage_Core_Block_Abstract
    */
    protected function _getSavedAttributeValueRendererBlock($type, $config, $params, $entity, $value)
    {
        $blockType = $config['config']['render_block_type'];
        if (strpos($blockType, '/') === false) {
            $blockType = 'customgrid/widget_grid_editor_renderer_attribute_'.$blockType;
        }
        return $this->_getLayout()->createBlock($blockType, '', array(
            'edited_entity'    => $entity,
            'edited_attribute' => $config['config']['attribute'],
            'edited_value'     => $config,
            'edited_config'    => $config['config'],
            'renderable_value' => $value,
            'edit_params'      => $params,
            'grid_block_type'  => $type,
        ));
    }
    
    /**
    * Prepare attribute value renderer block before display
    * 
    * @param string $type Grid block type
    * @param array $config Edited attribute config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @param mixed $value Prepared attribute value
    * @param Mage_Core_Block_Abstract $block Renderer block instance
    * @return Mage_Core_Block_Abstract
    */
    protected function _prepareSavedAttributeValueRendererBlock($type, $config, $params, $entity, $value, $block)
    {
        return $block;
    }
    
    /**
    * Return saved attribute value, adapted for a render in grids
    * 
    * @param string $type Grid block type
    * @param array $config Edited attribute config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @return mixed
    */
    protected function _getRenderableSavedAttributeValue($type, $config, $params, $entity)
    {
        $value = $this->_getSavedAttributeValueForRender($type, $config, $params, $entity);
        if ($block = $this->_getSavedAttributeValueRendererBlock($type, $config, $params, $entity, $value)) {
            $block = $this->_prepareSavedAttributeValueRendererBlock($type, $config, $params, $entity, $value, $block);
            return $block->toHtml();
        } else {
            return Mage::helper('customgrid')->__('<em>Updated</em>');
        }
    }
    
    /**
    * Return saved value, adapted for a render in grids
    * 
    * @param string $type Grid block type
    * @param array $config Edited value config
    * @param array $params Edit parameters
    * @param mixed $entity Edited entity
    * @return mixed
    */
    protected function _getRenderableSavedValue($type, $config, $params, $entity)
    {
        if ($config['origin'] == self::EDITABLE_TYPE_FIELD) {
            return $this->_getRenderableSavedFieldValue($type, $config, $params, $entity);
        } elseif ($config['origin'] == self::EDITABLE_TYPE_ATTRIBUTE) {
            return $this->_getRenderableSavedAttributeValue($type, $config, $params, $entity);
        } else {
            return '';
        }
    }
    
    /**
    * Save edited value depending on given value and request, and return a renderable one
    * 
    * @param string $type Grid block type
    * @param string $id Value ID
    * @param string $origin Value origin
    * @param Mage_Core_Controller_Request_Http $request Request object
    * @param BL_CustomGrid_Model_Grid $model Custom grid model
    * @return mixed
    */
    public function saveEditedValue($type, $id, $origin, $request, $model)
    {
        if ($config = $this->getEditableValue($type, $id, $origin)) {
            $valueConfig = compact('id', 'origin', 'config');
            $editParams  = $this->_extractRequestEditValues($type, $valueConfig, $request);
            $entity      = $this->_loadEditedEntity($type, $valueConfig, $editParams);
            
            if ($this->_isEditedEntityLoaded($type, $valueConfig, $editParams, $entity)) {
                $this->_registerEditedEntity($type, $valueConfig, $editParams, $entity);
                
                if ($this->_canEditValue($type, $valueConfig, $editParams, $entity, $model)
                    && $this->_checkEntityEditableValue($type, $valueConfig, $editParams, $entity)) {
                    if ($this->_saveEditedValue($type, $valueConfig, $editParams, $entity)) {
                        return $this->_getRenderableSavedValue($type, $valueConfig, $editParams, $entity);
                    } else {
                        Mage::throwException(Mage::helper('customgrid')->__('The value could not be saved'));
                    }
                } else {
                    Mage::throwException(Mage::helper('customgrid')->__('This value is not editable'));
                }
            } else {
                Mage::throwException(Mage::helper('customgrid')->__('The edited entity could not be loaded'));
            }
        } else {
            Mage::throwException(Mage::helper('customgrid')->__('This value is not editable'));
        }
    }
    
    /**
    * Do some actions before grid is exported
    * 
    * @param string $format Export format
    * @param Mage_Adminhtml_Block_Widget_Grid $grid Exported grid block, is null at first call (before grid block creation)
    * @return this
    */
    public function beforeGridExport($format, $grid=null)
    {
        return $this;
    }
    
    /**
    * Do some actions after grid is exported
    * 
    * @param string $format Export format
    * @param Mage_Adminhtml_Block_Widget_Grid $grid Exported grid block
    * @return this
    */
    public function afterGridExport($format, $grid)
    {
        return $this;
    }
    
    /**
    * Do some actions before grid collection is prepared
    * 
    * @param Mage_Adminhtml_Block_Widget_Grid $grid Grid block
    * @param bool $firstTime Whether this is the first (= incomplete) grid collection preparation
    * @return this
    */
    public function beforeGridPrepareCollection($grid, $firstTime=true)
    {
        return $this;
    }
    
    /**
    * Do some actions after grid collection is prepared
    * 
    * @param Mage_Adminhtml_Block_Widget_Grid $grid Grid block
    * @param bool $firstTime Whether this is the first (= incomplete) grid collection preparation
    * @return this
    */
    public function afterGridPrepareCollection($grid, $firstTime=true)
    {
        return $this;
    }
    
    /**
    * Do some actions before given collection is set on given grid
    * 
    * @param Mage_Adminhtml_Block_Widget_Grid $grid Grid block
    * @param Varien_Data_Collection $collection Set collection
    * @return this
    */
    public function beforeGridSetCollection($grid, $collection)
    {
        return $this;
    }
    
    
    /**
    * Do some actions after given collection was set on given grid
    * 
    * @param Mage_Adminhtml_Block_Widget_Grid $grid Grid block
    * @param Varien_Data_Collection $collection Set collection
    * @return this
    */
    public function afterGridSetCollection($grid, $collection)
    {
        return $this;
    }
    
    /**
    * Do some actions before given grid loads given collection for export
    * 
    * @param Mage_Adminhtml_Block_Widget_Grid $grid Grid block
    * @param Varien_Data_Collection $collection Loaded collection
    * @return this
    */
    public function beforeGridExportLoadCollection($grid, $collection)
    {
        return $this;
    }
    
    /**
    * Do some actions after given grid has loaded given collection for export
    * 
    * @param Mage_Adminhtml_Block_Widget_Grid $grid Grid block
    * @param Varien_Data_Collection $collection Loaded collection
    * @return this
    */
    public function afterGridExportLoadCollection($grid, $collection)
    {
        return $this;
    }
}
