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

class BL_CustomGrid_Model_Grid extends Mage_Core_Model_Abstract
{
    /**
    * Session params keys
    */
    const SESSION_BASE_KEY_CURRENT_PROFILE = '_blcg_session_key_current_profile_';
    const SESSION_BASE_KEY_APPLIED_FILTERS = '_blcg_session_key_applied_filters_';
    const SESSION_BASE_KEY_REMOVED_FILTERS = '_blcg_session_key_removed_filters_';
    const SESSION_BASE_KEY_TOKEN = '_blcg_session_key_token_';
    /**
    * Parameter name to use to hold grid token value
    */
    const GRID_TOKEN_PARAM_NAME  = '_blcg_token_';
    
    /**
    * Attribute columns base keys
    */
    const GRID_COLUMN_ATTRIBUTE_ID_PREFIX  = '_blcg_attribute_column_';
    const GRID_COLUMN_ATTRIBUTE_GRID_ALIAS = 'blcg_attribute_field_';
    
    /**
    * Custom columns base keys
    */
    const GRID_COLUMN_CUSTOM_ID_PREFIX  = '_blcg_custom_column_';
    const GRID_COLUMN_CUSTOM_GRID_ALIAS = 'blcg_custom_field_';
    
    /**
    * Grid profiles
    * 
    * @var array
    */
    protected $_profiles = null;
    /**
    * Current profile ID
    * 
    * @var int
    */
    protected $_currentProfileId = null;
    
    /**
    * Grid columns
    * 
    * @var array
    */
    protected $_columns = array();
    /**
    * Pitch to put between each column order at initialization
    * 
    * @var int
    */
    protected $_orderPitch = 10;
    /**
    * Current maximum order for all columns
    * 
    * @var int
    */
    protected $_maxOrder   = null;
    
    /**
    * Default pagination values (usually hard-coded in grid template)
    * 
    * @var array
    */
    protected $_defaultPaginationValues = array(20, 30, 50, 100, 200);
    /**
    * Actual pagination values in use
    * 
    * @var array
    */
    protected $_paginationValues = null;
    
    /**
    * Columns alignments
    */
    const GRID_COLUMN_ALIGNMENT_LEFT   = 'left';
    const GRID_COLUMN_ALIGNMENT_CENTER = 'center';
    const GRID_COLUMN_ALIGNMENT_RIGHT  = 'right';
    /**
    * Columns alignments options hash
    * 
    * @var array
    */
    static protected $_columnAlignments = null;
    
    /**
    * Columns origins
    */
    const GRID_COLUMN_ORIGIN_GRID       = 'grid';
    const GRID_COLUMN_ORIGIN_COLLECTION = 'collection';
    const GRID_COLUMN_ORIGIN_ATTRIBUTE  = 'attribute';
    const GRID_COLUMN_ORIGIN_CUSTOM     = 'custom';
    
    /**
    * Columns origins options hash
    * 
    * @var array
    */
    static protected $_columnOrigins = null;
    /**
    * Columns IDs by origin
    * 
    * @var array
    */
    protected $_originIds = array();
    
    /**
    * Grid corresponding type model
    * 
    * @var BL_CustomGrid_Model_Grid_Type_Abstract
    */
    protected $_typeModel = null;
    
    /**
    * Grid actions
    */
    const GRID_ACTION_CUSTOMIZE_COLUMNS       = 'customize';
    const GRID_ACTION_USE_CUSTOMIZED_COLUMNS  = 'use_customized';
    const GRID_ACTION_VIEW_GRID_INFOS         = 'view_infos';
    const GRID_ACTION_CHOOSE_EDITABLE_COLUMNS = 'choose_editable';
    const GRID_ACTION_EDIT_COLUMNS_VALUES     = 'edit_values';
    const GRID_ACTION_EDIT_DEFAULT_PARAMS     = 'edit_default';
    const GRID_ACTION_USE_DEFAULT_PARAMS      = 'use_default';
    const GRID_ACTION_EXPORT_RESULTS          = 'export';
    /*
    const GRID_ACTION_CHOOSE_PROFILE          = 'choose_profile';
    const GRID_ACTION_CHOOSE_DEFAULT_PROFILE  = 'choose_default_profile';
    const GRID_ACTION_CREATE_PROFILE          = 'create_profile';
    const GRID_ACTION_DELETE_PROFILE          = 'delete_profile';
    */ // @todo to reactivate if needed for profiles system
    
    /**
    * Grid actions options hash
    * 
    * @var array
    */
    static protected $_gridActions = null;
    
    /**
    * Grids actions corresponding permissions paths
    * 
    * @var array
    */
    static protected $_gridPermissionsPaths = array(
        self::GRID_ACTION_CUSTOMIZE_COLUMNS       => 'system/customgrid/customization/edit_columns',
        self::GRID_ACTION_USE_CUSTOMIZED_COLUMNS  => 'system/customgrid/customization/use_columns',
        self::GRID_ACTION_VIEW_GRID_INFOS         => 'system/customgrid/customization/view_grid_infos',
        self::GRID_ACTION_EDIT_DEFAULT_PARAMS     => 'system/customgrid/customization/edit_default_params',
        self::GRID_ACTION_USE_DEFAULT_PARAMS      => 'system/customgrid/customization/use_default_params',
        self::GRID_ACTION_EXPORT_RESULTS          => 'system/customgrid/customization/export_results',
        self::GRID_ACTION_CHOOSE_EDITABLE_COLUMNS => 'system/customgrid/editor/choose_columns',
        self::GRID_ACTION_EDIT_COLUMNS_VALUES     => 'system/customgrid/editor/edit_columns',
        /*
        self::GRID_ACTION_CHOOSE_PROFILE          => 'system/customgrid/profiles/choose',
        self::GRID_ACTION_CHOOSE_DEFAULT_PROFILE  => 'system/customgrid/profiles/choose_default',
        self::GRID_ACTION_CREATE_PROFILE          => 'system/customgrid/profiles/create',
        self::GRID_ACTION_DELETE_PROFILE          => 'system/customgrid/profiles/delete',
        */ // @todo to reactivate if needed for profiles system
    );
    
    /**
    * Grid actions permissions flags
    */
    const GRID_PERMISSION_USE_CONFIG = '0';
    const GRID_PERMISSION_ALLOWED    = '1';
    const GRID_PERMISSION_DISALLOWED = '2';
    
    /**
    * Grid roles config
    * 
    * @var array
    */
    protected $_rolesConfig = array();
    
    /**
    * Default parameters behaviours
    */
    const GRID_DEFAULT_PARAM_DEFAULT             = 'default';
    const GRID_DEFAULT_PARAM_FORCE_ORIGINAL      = 'force_original';
    const GRID_DEFAULT_PARAM_FORCE_CUSTOM        = 'force_custom';
    const GRID_DEFAULT_PARAM_MERGE_DEFAULT       = 'merge_default'; 
    const GRID_DEFAULT_PARAM_MERGE_BASE_ORIGINAL = 'merge_on_original';
    const GRID_DEFAULT_PARAM_MERGE_BASE_CUSTOM   = 'merge_on_custom';
    
    protected function _construct()
    {
        parent::_construct();
        $this->_init('customgrid/grid');
        $this->setIdFieldName('grid_id');
        //$this->resetProfiles();
        $this->resetColumns();
        $this->resetRolesConfig();
    }
    
    protected function _getHelper()
    {
        return Mage::helper('customgrid');
    }
    
    protected function _getConfigHelper()
    {
        return Mage::helper('customgrid/config');
    }
    
    protected function _beforeSave()
    {
        parent::_beforeSave();
        
        // Init max IDs when needed
        if (!($this->getMaxAttributeColumnId() > 0)) {
            $this->setMaxAttributeColumnId(0);
        }
        if (!($this->getMaxCustomColumnId() > 0)) {
            $this->setMaxCustomColumnId(0);
        }
        
        return $this;
    }
    
    protected function _refreshConfig()
    {
        $this->_initTypeModel();
        //$this->loadProfiles();
        //$this->initCurrentProfile();
        $this->loadColumns();
        $this->loadRolesConfig();
    }
    
    protected function _afterLoad()
    {
        parent::_afterLoad();
        $this->_refreshConfig();
        return $this;
    }
    
    protected function _afterSave()
    {
        parent::_afterSave();
        $this->_refreshConfig();
        return $this;
    }
    
    /**
    * Init type model instance depending on active grid
    * 
    * @return this
    */
    protected function _initTypeModel()
    {
        $this->_typeModel = null;
        $this->setType(null);
        
        if ($blockType = $this->getBlockType()) {
            $types = Mage::getSingleton('customgrid/grid_type')->getTypesInstances();
            
            foreach ($types as $code => $type) {
                if ($type->isAppliableToGrid($blockType, $this->getRewritingClassName())) {
                    $this->_typeModel = $type;
                    $this->setType($code);
                    break;
                }
            }
        }
        
        return $this;
    }
    
    /**
    *  Return type model instance
    * 
    * @return BL_CustomGrid_Model_Grid_Type_Abstract
    */
    public function getTypeModel()
    {
        return $this->_typeModel;
    }
    
    /**
    * Return type model name, or default value if there is no type model
    * 
    * @return mixed
    */
    public function getTypeModelName($default='')
    {
        if (!is_null($this->_typeModel)) {
            return $this->_typeModel->getName();
        }
        return $default;
    }
    
    // @todo finish profiles-related methods
    /*
    public function resetProfiles()
    {
        $this->_profiles = array();
        $this->setProfileId(null);
        return $this;
    }
    
    public function loadProfiles($force=true)
    {
        if (!$force && !is_null($this->_profiles)) {
            return $this;
        }
        
        $this->resetProfiles();
        
        if ($id = $this->getId()) {
            $profiles = $this->_getResource()->getGridProfiles($id);
            foreach ($profiles as $profile) {
                $this->_profiles[$profile['profile_id']] = $profile;
            }
        }
        
        return $this;
    }
    
    public function initCurrentProfile()
    {
        if (!$this->getId()) {
            return;
        }
        
        $this->_currentProfileId = null;
        $session   = Mage::getSingleton('admin/session');
        $available = $this->getProfiles(true);
        
        if ($user = $session->getUser()) {
            $sessionKey = self::SESSION_BASE_KEY_CURRENT_PROFILE . '_' . $this->getId();
        }
        
        return $this;
    }
    
    public function getCurrentProfileId()
    {
        return $this->_currentProfileId;
    }
    
    public function getProfiles($onlyAvailable=false, $asOptionHash=false, $asOptionArray=false)
    {
        $this->loadProfiles();
        $result = $this->_profiles;
        
        if ($onlyAvailable) {
            $session = Mage::getSingleton('admin/session');
            
            if (($user = $session->getUser())
                && ($role = $user->getRole())
                && ($config = $this->getRolesConfig($role))) {
                
            } else {
                $result = array();
            }
        }
        if (empty($result)) {
            return array();
        } elseif ($asOptionHash) {
            
        } elseif ($asOptionArray) {
            
        }
        
        return $result;
    }
    */
    
    /**
    * Reset all columns informations
    * 
    * @return this
    */
    public function resetColumns()
    {
        $this->_maxOrder  = null;
        $this->_columns   = array();
        $this->_originIds = array(
            self::GRID_COLUMN_ORIGIN_GRID       => array(),
            self::GRID_COLUMN_ORIGIN_COLLECTION => array(),
            self::GRID_COLUMN_ORIGIN_ATTRIBUTE  => array(),
            self::GRID_COLUMN_ORIGIN_CUSTOM     => array(),
        );
        $this->_attributesColumns = array();
        return $this;
    }
    
    /**
    * Load active grid columns from database
    * 
    * @return this
    */
    public function loadColumns()
    {
        $this->resetColumns();
        
        if ($id = $this->getId()) {
            $columns = $this->_getResource()->getGridColumns($id);
            foreach ($columns as $column) {
                $this->addColumn($column['id'], $column);
            }
        }
        
        return $this;
    }
    
    /**
    * Add a column to the grid columns list
    * 
    * @param string $columnId Column ID
    * @param array $column Column informations
    * @return this
    */
    public function addColumn($columnId, $column)
    {
        $this->_columns[$columnId] = $column;
        $this->_originIds[$column['origin']][] = $columnId;
        $this->_maxOrder = (!is_null($this->_maxOrder) ? max($column['order'], $this->_maxOrder) : $column['order']);
        return $this;
    }
    
    /**
    * Recompute columns maximum order
    * 
    * @return this
    */
    protected function _recomputeMaxOrder()
    {
        $this->_maxOrder = null;
        
        foreach ($this->_columns as $column) {
            $this->_maxOrder = max($this->_maxOrder, $column['order']);
        }
        
        return $this;
    }
    
    /**
    * Return columns maximum order
    * 
    * @return int
    */
    public function getMaxOrder()
    {
        return $this->_maxOrder;
    }
    
    /**
    * Increment maximum order and return it
    * 
    * @return int
    */
    protected function _getNextOrder()
    {
        $this->_maxOrder += $this->_orderPitch;
        return $this->_maxOrder;
    }
    
    /**
    * Return default interval between two columns orders values
    * 
    * @return int
    */
    public function getOrderPitch()
    {
        return $this->_orderPitch;
    }
    
    /**
    * Return whether grid accept attribute columns
    * 
    * @return bool
    */
    public function canHaveAttributeColumns()
    {
        if (!is_null($this->_typeModel)) {
            return $this->_typeModel->canHaveAttributeColumns($this->getBlockType());
        }
        return false;
    }
    
    /**
    * Return available attributes
    * 
    * @param bool $addRenderersCodes Whether renderers codes should be added to attributes objects
    * @return array
    */
    public function getAvailableAttributes($addRenderersCodes=false, $withEditableFlag=false)
    {
        if (!is_null($this->_typeModel)) {
            $attributes = $this->_typeModel->getAvailableAttributes($this->getBlockType(), $withEditableFlag);
            
            if ($addRenderersCodes) {
                $renderers = Mage::getSingleton('customgrid/column_renderer_attribute')
                    ->getRenderersInstances();
                
                foreach ($attributes as $attribute) {
                    $attribute->setRendererCode(null);
                    
                    foreach ($renderers as $code => $renderer) {
                        if ($renderer->isAppliableToColumn($attribute, $this)) {
                            $attribute->setRendererCode($code);
                            break;
                        }
                    }
                }
            }
            
            return $attributes;
        }
        return array();
    }
    
    /**
    * Return available attributes codes
    * 
    * @return array
    */
    public function getAvailableAttributesCodes()
    {
        return array_keys($this->getAvailableAttributes());
    }
    
    /**
    * Return renderer types codes from available attributes
    * 
    * @return array
    */
    public function getAvailableAttributesRendererTypes()
    {
        $result = array();
        $attributes = $this->getAvailableAttributes(true);
        
        foreach ($attributes as $code => $attribute) {
            $result[$code] = $attribute->getRendererCode();
        }
        
        return $result;
    }
    
    /**
    * Return next attribute column ID (auto-generated ones)
    * 
    * @return string
    */
    protected function _getNextAttributeColumnId()
    {
        if ($this->getMaxAttributeColumnId() > 0) {
            $columnId = $this->getMaxAttributeColumnId() + 1;
        } else {
            $columnId = 1;
        }
        $this->setMaxAttributeColumnId($columnId);
        return self::GRID_COLUMN_ATTRIBUTE_ID_PREFIX . $columnId;
    }
    
    /**
    * Return whether grid accept custom columns
    * 
    * @return bool
    */
    public function canHaveCustomColumns()
    {
        if (!is_null($this->_typeModel)) {
            return $this->_typeModel->canHaveCustomColumns($this->getBlockType(), $this->getRewritingClassName());
        }
        return false;
    }
    
    protected function _addTypeToCustomColumnCode(&$code, $key=null, $typeCode=null)
    {
        if (is_null($typeCode)) {
            if (!is_null($this->_typeModel)) {
                $typeCode = $this->_typeModel->getCode();
            } else {
                return $code;
            }
        }
        $code = $typeCode.'/'.$code;
        return $this;
    }
    
    public function getAvailableCustomColumns($grouped=false, $withTypeCode=false)
    {
        if (!is_null($this->_typeModel)) {
            $columns   = $this->_typeModel->getCustomColumns($this->getBlockType(), $this->getRewritingClassName());
            $usedCodes = $this->getUsedCustomColumnsCodes();
            $typeCode  = $this->_typeModel->getCode();
            
            if ($grouped) {
                $result = array();
                
                foreach ($columns as $code => $column) {
                    if (!isset($result[$column->getGroupId()])) {
                        $result[$column->getGroupId()] = array();
                    }
                    if (in_array($code, $usedCodes)) {
                        $column->setSelected(true);
                    }
                    if ($withTypeCode) {
                        $this->_addTypeToCustomColumnCode($code, null, $typeCode);
                    }
                    $result[$column->getGroupId()][$code] = $column;
                }
                
                $columns = $result;
            } elseif ($withTypeCode) {
                $result = array();
                
                foreach ($columns as $code => $column) {
                    $this->_addTypeToCustomColumnCode($code, null, $typeCode);
                    $result[$code] = $column;
                }
                
                $columns = $result;
            }
            
            return $columns;
        }
        return array();
    }
    
    public function getCustomColumnsGroups($onlyAvailable=true)
    {
        if (!is_null($this->_typeModel)) {
            $groups = $this->_typeModel->getCustomColumnsGroups();
            
            if ($onlyAvailable) {
                $groupsIds = array();
                
                foreach ($this->getAvailableCustomColumns() as $column) {
                    $groupsIds[] = $column->getGroupId();
                }
                
                $groups = array_intersect_key($groups, array_flip($groupsIds));
            }
            
            return $groups;
        }
        return array();
    }
    
    public function getAvailableCustomColumnsCodes($withTypeCode=false)
    {
        $codes = array_keys($this->getAvailableCustomColumns());
        
        if ($withTypeCode && !is_null($this->_typeModel)) {
            array_walk($codes, array($this, '_addTypeToCustomColumnCode'), $this->_typeModel->getCode());
        }
        
        return $codes;
    }
    
    public function getUsedCustomColumnsCodes($withTypeCode=false)
    {
        if (!is_null($this->_typeModel)) {
            $typeCode = $this->_typeModel->getCode();
        } else {
            return array();
        }
        $codes = array();
        
        foreach ($this->_originIds[self::GRID_COLUMN_ORIGIN_CUSTOM] as $columnId) {
            $parts = explode('/', $this->_columns[$columnId]['index']);
            
            if ($parts[0] == $typeCode) {
                $codes[] = $parts[1];
            }
        }
        if ($withTypeCode) {
            array_walk($codes, array($this, '_addTypeToCustomColumnCode'), $typeCode);
        }
        
        return $codes;
    }
    
    protected function _getNextCustomColumnId()
    {
        if ($this->getMaxCustomColumnId() > 0) {
            $columnId = $this->getMaxCustomColumnId() + 1;
        } else {
            $columnId = 1;
        }
        $this->setMaxCustomColumnId($columnId);
        return self::GRID_COLUMN_CUSTOM_ID_PREFIX . $columnId;
    }
    
    /**
    * Return whether grid results can be exported
    * 
    * @param Mage_Adminhtml_Block_Widget_Grid $grid Grid block
    * @return bool
    */
    public function canExport()
    {
        if (!is_null($this->_typeModel)) {
            return $this->_typeModel->canExport($this->getBlockType());
        }
        return false;
    }
    
    /**
    * Return available export types
    * 
    * @return array
    */
    public function getExportTypes()
    {
        if (!is_null($this->_typeModel)) {
            return $this->_typeModel->getExportTypes($this->getBlockType());
        }
        return array();
    }
    
    /**
    * @todo 
    * Turn _exportFile() into public exportFile()
    * Remove all explicit calls such as export[Format]File() from here and custom grid controller (make it give format)
    * Do export in grid type model, so new export types could "simply" be added
    * Add CSV / XML export to abstract grid type model
    */
    
    /**
    * Return whether current request corresponds to an export one for active grid
    * 
    * @param Mage_Core_Controller_Request_Http $request Request object
    * @return bool
    */
    public function isExportRequest($request)
    {
        if (!is_null($this->_typeModel)) {
            return $this->_typeModel->isExportRequest($request, $this->getBlockType());
        }
        return false;
    }
    
    /**
    * Export grid data in given format
    * 
    * @param string $format Export format
    * @param array $infos Export informations
    * @return mixed
    */
    protected function _exportFile($format, $infos=null)
    {
        if (!is_null($this->_typeModel)) {
            $this->_typeModel->beforeGridExport($format, null);
        }
        $block = Mage::getSingleton('core/layout')->createBlock($this->getBlockType());
        if (!is_null($infos)) {
            $block->blcg_setExportInfos($infos);
        }
        if (!is_null($this->_typeModel)) {
            $this->_typeModel->beforeGridExport($format, $block);
        }
        switch ($format) {
            case 'csv':
                $return = $block->getCsvFile();
                break;
            case 'xml':
                $return = $block->getExcelFile();
                break;
            default:
                $return = null;
                break;
        }
        if (!is_null($this->_typeModel)) {
            $this->_typeModel->afterGridExport($format, $block);
        }
        return $return;
    }
    
    /**
    * Export grid data in CSV format
    * 
    * @param array $infos Export informations
    * @return mixed
    */
    public function exportCsvFile($infos=null)
    {
        return $this->_exportFile('csv', $infos);
    }
    
    /**
    * Export grid data in XML Excel format
    * 
    * @param array $infos Export informations
    * @return mixed
    */
    public function exportExcelFile($infos=null)
    {
        return $this->_exportFile('xml', $infos);
    }
    
    /**
    * Return grid columns
    * 
    * @param bool $withEditable Whether columns editability informations should be added to result
    * @param bool $withCustom Whether custom columns models should be added to corresponding columns
    * @return array
    */
    public function getColumns($withEditable=false, $withCustom=false)
    {
        $columns = $this->_columns;
        
        if (!is_null($this->_typeModel)) {
            if ($withEditable) {
                $columns = $this->_typeModel->applyEditableConfigsToColumns($this->getBlockType(), $columns, $this);
            }
            if ($withCustom) {
                $customColumns = $this->getAvailableCustomColumns(false, true);
                
                foreach ($this->_originIds[self::GRID_COLUMN_ORIGIN_CUSTOM] as $columnId) {
                    if (isset($customColumns[$columns[$columnId]['index']])) {
                        $columns[$columnId]['custom_column'] = $customColumns[$columns[$columnId]['index']];
                    } else {
                        $columns[$columnId]['custom_column'] = null;
                    }
                }
            }
        }
        
        return $columns;
    }
    
    /**
    * Return grid sorted columns
    * 
    * @param bool $valid Whether valid columns should be returned (ie not missing ones)
    * @param bool $missing Whether missing columns should be returned
    * @param bool $fromAttribute Whether attribute columns should be returned
    * @param bool $fromCustom Whether custom columns should be returned
    * @param bool $onlyVisible Whether only visible columns should be returned
    * @param bool $withEditable Whether columns editability informations should be added to result
    * @param bool $withCustom Whether custom columns models should be added to corresponding columns
    * @return array
    */
    public function getSortedColumns($valid=true, $missing=true, $fromAttribute=true, $fromCustom=true,
        $onlyVisible=false, $withEditable=false, $withCustom=false)
    {
        $columns = array();
        
        foreach ($this->getColumns($withEditable, $withCustom) as $columnId => $column) {
            if (($onlyVisible && !$column['is_visible'])
                || (!$missing && $column['missing'])
                || (!$valid && !$column['missing'])
                || (!$fromAttribute && $this->isAttributeColumnOrigin($column['origin']))
                || (!$fromCustom && $this->isCustomColumnOrigin($column['origin']))) {
                // Unwanted column
                continue;
            }
            $columns[$columnId] = $column;
        }
        
        uasort($columns, array($this, '_sortColumns'));
        return $columns;
    }
    
    /**
    * Return column corresponding to given database ID
    * 
    * @param int $id Column database ID
    * @return mixed
    */
    public function getColumnFromDbId($id)
    {
        foreach ($this->getColumns() as $column) {
            if ($column['column_id'] == $id) {
                return $column;
            }
        }
        return null;
    }
    
    /**
    * Return a column index from its code
    * 
    * @param string $code Column code
    * @param string $origin Column origin
    * @param int $position Column position (for attribute origin)
    * @return null|string
    */
    public function getColumnIndexFromCode($code, $origin, $position=null)
    {
        if ($this->isAttributeColumnOrigin($origin)
            || $this->isCustomColumnOrigin($origin)) {
            // Assume given code corresponds to attribute code
            $column  = null;
            $columns = array();
            
            foreach ($this->_originIds[$origin] as $id) {
                if ($this->_columns[$id]['index'] == $code) {
                    $columns[] = $this->_columns[$id];
                }
            }
            
            usort($columns, '_sortColumns');
            $columnsNumber = count($columns);
            
            // If column if found, return the effective index that will be used
            if (($position >= 1) && ($position <= $columnsNumber)) {
                $column = $columns[$position-1];
            } elseif ($columnsNumber > 0) {
                $column = $columns[0];
            }
            
            if (!is_null($column)) {
                if ($this->isAttributeColumnOrigin($origin)) {
                    return self::GRID_COLUMN_ATTRIBUTE_GRID_ALIAS
                        . str_replace(self::GRID_COLUMN_ATTRIBUTE_ID_PREFIX, '', $column['id']);
                } else {
                    return self::GRID_COLUMN_CUSTOM_GRID_ALIAS
                        . str_replace(self::GRID_COLUMN_CUSTOM_ID_PREFIX, '', $column['id']);
                }
            }
        } elseif (array_key_exists($origin, self::getColumnOrigins())) {
            // Assume given code corresponds to column ID
            if (in_array($code, $this->_originIds[$origin], true)) {
                // Return column index only if column exists and comes from wanted origin
                return $this->_columns[$code]['index'];
            }
        }
        return null;
    }
    
    /**
    * Return whether the grid has editable columns
    * 
    * @return bool
    */
    public function hasEditableColumns()
    {
        if (!is_null($this->_typeModel)) {
            return ((count(array_intersect_key(
                        $this->getSortedColumns(true, true, false, false),
                        array_merge(
                            $this->_typeModel->getEditableFields($this->getBlockType()),
                            $this->_typeModel->getEditableAttributeFields($this->getBlockType())
                        )
                    )) > 0)
                    || (count($this->_typeModel->getEditableAttributes($this->getBlockType())) > 0));
        }
        return false;
    }
    
    /**
    * Return whether the current user has edit permissions over the grid
    * 
    * @param Mage_Adminhtml_Block_Widget_Grid $grid Grid block instance
    * @return bool
    */
    public function hasUserEditPermissions($grid)
    {
        if (!is_null($this->_typeModel)) {
            return $this->_typeModel->checkUserEditPermissions($this->getBlockType(), $this, $grid);
        } else {
            return $this->checkUserActionPermission(self::GRID_ACTION_EDIT_COLUMNS_VALUES);
        }
        return false;
    }
    
    /**
    * Return column header
    * 
    * @param string $id Column ID
    * @return null|string
    */
    public function getColumnHeader($id)
    {
        return (isset($this->_columns[$id]) ? $this->_columns[$id]['header'] : null);
    }
    
    /**
    * Return column locked values (ie that should not be given by user)
    * 
    * @param string $columnId Column ID
    * @param bool $defaultArray Whether an empty array should be returned if column has no locked values
    * @return mixed
    */
    public function getColumnLockedValues($columnId, $defaultArray=true)
    {
        $values = false;
        
        if (!is_null($this->_typeModel)
            && isset($this->_columns[$columnId])
            && $this->isCollectionColumnOrigin($this->_columns[$columnId]['origin'])) {
            $values = $this->_typeModel->getColumnLockedValues($this->getBlockType(), $columnId);
        }
        
        return (is_array($values) ? $values : ($defaultArray ? array() : $values));
    }
    
    /**
    * Add a grid column from block origin
    * 
    * @param Mage_Adminhtml_Block_Widget_Grid_Column $column Column object
    * @param int $order Column order
    * @return this
    */
    protected function _addColumnFromBlock(Mage_Adminhtml_Block_Widget_Grid_Column $column, $order)
    {
        $this->addColumn($column->getId(), array(
            'id'              => $column->getId(),
            'index'           => $column->getIndex(),
            'width'           => $column->getWidth(),
            'align'           => array_key_exists($column->getAlign(), $this->getColumnAlignments()) 
                ? $column->getAlign() 
                : self::GRID_COLUMN_ALIGNMENT_LEFT,
            'header'          => $column->getHeader(),
            'order'           => $order,
            'origin'          => self::GRID_COLUMN_ORIGIN_GRID,
            'is_visible'      => 1,
            'filter_only'     => 0,
            'is_system'       => ($column->getIsSystem() ? 1 : 0),
            'missing'         => 0,
            'store_id'        => null,
            'renderer_type'   => null,
            'renderer_params' => null,
            'allow_edit'      => 1,
            'custom_params'   => null,
        ));
        return $this;
    }
    
    /**
    * Add a grid column from collection origin
    * 
    * @param string $column Corresponding field key
    * @param int $order Column order
    * @return this
    */
    protected function _addColumnFromCollection($key, $order)
    {
        $this->addColumn($key, array(
            'id'              => $key,
            'index'           => $key,
            'width'           => '',
            'align'           => self::GRID_COLUMN_ALIGNMENT_LEFT,
            'header'          => $this->_getHelper()->getColumnHeaderName($key),
            'order'           => $order,
            'origin'          => self::GRID_COLUMN_ORIGIN_COLLECTION,
            'is_visible'      => 0,
            'filter_only'     => 0,
            'is_system'       => 0,
            'missing'         => 0,
            'store_id'        => null,
            'renderer_type'   => null,
            'renderer_params' => null,
            'allow_edit'      => 1,
            'custom_params'   => null,
        ));
        return $this;
    }
    
    /**
    * Return whether given block type and ID correspond to active grid
    * 
    * @param string $blockType Block type
    * @param string $blockId Block ID in layout
    * @return bool
    */
    public function matchGridBlock($blockType, $blockId)
    {
        if (!is_null($this->_typeModel)) {
            return $this->_typeModel->matchGridBlock($blockType, $blockId, $this);
        }
        return false;
    }
    
    /**
    * Init values with grid block instance
    * 
    * @param Mage_Adminhtml_Block_Widget_Grid $grid Grid block instance
    * @return this
    */
    public function initWithGridBlock(Mage_Adminhtml_Block_Widget_Grid $grid)
    {
        // Init global grid values
        $this->addData(array(
            'block_id'   => $grid->getId(),
            'block_type' => $grid->getType(),
        ));
        $this->_initTypeModel();
        
        // Init columns
        $this->resetColumns();
        
        $order = 0;
        $gridIndexes = array();
        
        foreach ($grid->getColumns() as $column) {
            // Take all columns from grid
            $this->_addColumnFromBlock($column, (++$order * $this->getOrderPitch()), self::GRID_COLUMN_ORIGIN_GRID);
            $gridIndexes[] = $column->getIndex();
        }
        
        if ($grid->getCollection() 
            && ($grid->getCollection()->count() > 0)) {
            // Initialize collection columns if possible
            $item = $grid->getCollection()->getFirstItem();
            
            foreach ($item->getData() as $key => $value) {
                if (!in_array($key, $gridIndexes, true) 
                    && !in_array($key, $this->_originIds[self::GRID_COLUMN_ORIGIN_GRID], true)
                    && (is_scalar($value) || is_null($value))) {
                    /*
                    From collection, only take columns that are not already used by grid,
                    and do not correspond to array / object / resource values
                    */
                    $this->_addColumnFromCollection($key, (++$order * $this->getOrderPitch()));
                }
            }
        }
        
        return $this;
    }
    
    /**
    * Check values against grid block instance, and save up-to-date values
    * 
    * @param Mage_Adminhtml_Block_Widget_Grid $grid Grid block instance
    * @return bool Whether we could check collection columns (if false, using them could be "dangerous")
    */
    public function checkColumnsAgainstGridBlock(Mage_Adminhtml_Block_Widget_Grid $grid)
    {
        $foundGridIds = array();
        $gridIndexes  = array();
        
        // Grid columns
        foreach ($grid->getColumns() as $column) {
            $columnId = $column->getId();
            
            if (isset($this->_columns[$columnId])) {
                // Existing column : update its base values (all values that cannot be changed by user)
                $previousOrigin = $this->_columns[$columnId]['origin'];
                
                $this->_columns[$columnId] = array_merge(
                    $this->_columns[$columnId],
                    array(
                        'id'         => $columnId,
                        'index'      => $column->getIndex(),
                        'origin'     => self::GRID_COLUMN_ORIGIN_GRID,
                        'is_system'  => ($column->getIsSystem() ? 1 : 0),
                        'missing'    => 0,
                    )
                );
                
                if (!$this->isGridColumnOrigin($previousOrigin)) {
                    // If column did not previously come from grid, refresh origin IDs
                    unset($this->_originIds[$previousOrigin][array_search($columnId, $this->_originIds[$previousOrigin])]);
                    $this->_originIds[self::GRID_COLUMN_ORIGIN_GRID][] = $columnId;
                }
            } else {
                // New column
                $this->_addColumnFromBlock($column, $this->_getNextOrder());
            }
            
            $gridIndexes[]  = $column->getIndex();
            $foundGridIds[] = $columnId;
        }
        
        $foundCollectionIds = array();
        $checkedCollection  = false;
        
        // Collection columns
        if ($grid->getCollection()
            && ($grid->getCollection()->count() > 0)) {
            // Update collection  columns if possible
            $item = $grid->getCollection()->getFirstItem();
            $checkedCollection = true;
            
            foreach ($item->getData() as $key => $value) {
                if (is_scalar($value) || is_null($value)) {
                    if (isset($this->_columns[$key])) {
                        // Existing column
                        $previousOrigin = $this->_columns[$key]['origin'];
                        
                        if (!in_array($key, $foundGridIds, true)) {
                            // Existing column that already came from collection, or not found anymore in the grid
                            if (!in_array($key, $gridIndexes, true)) {
                                // If it doesnt now collide with a grid index, update its base values
                                $this->_columns[$key] = array_merge(
                                    $this->_columns[$key],
                                    array(
                                        'id'         => $key,
                                        'index'      => $key,
                                        'origin'     => self::GRID_COLUMN_ORIGIN_COLLECTION,
                                        'is_system'  => 0,
                                        'missing'    => 0,
                                    )
                                );
                                
                                if (!$this->isCollectionColumnOrigin($previousOrigin)) {
                                    // If column did not previously come from collection, remove it from its previous origin
                                    unset($this->_originIds[$previousOrigin][array_search($key, $this->_originIds[$previousOrigin])]);
                                    $this->_originIds[self::GRID_COLUMN_ORIGIN_COLLECTION][] = $key;
                                }
                                
                                $foundCollectionIds[] = $key;
                            } else {
                                // If it does now collide with a grid index, remove it
                                unset($this->_columns[$key]);
                            }
                        } // Existing column from the grid, already handled
                    } else {
                        if (!in_array($key, $foundGridIds, true)
                            && !in_array($key, $gridIndexes, true)) {
                            // New column if no collision
                            $this->_addColumnFromCollection($key, $this->_getNextOrder());
                            $foundCollectionIds[] = $key;
                        }
                    }
                }
            }
        }
        
        // Attributes columns
        $foundAttributesIds = array();
        
        if ($this->canHaveAttributeColumns($this->getBlockType())) {
            $columnsIds = $this->_originIds[self::GRID_COLUMN_ORIGIN_ATTRIBUTE];
            $attributes = $this->getAvailableAttributesCodes();
            
            foreach ($columnsIds as $columnId) {
                // Verify attributes existences
                if (in_array($this->_columns[$columnId]['index'], $attributes, true)) {
                    $this->_columns[$columnId]['missing'] = 0;
                    $foundAttributesIds[] = $columnId;
                }
            }
        }
        
        // Custom columns
        $foundCustomIds = array();
        
        if ($this->canHaveCustomColumns()) {
            $columnsIds     = $this->_originIds[self::GRID_COLUMN_ORIGIN_CUSTOM];
            $availableCodes = $this->getAvailableCustomColumnsCodes(true);
            
            foreach ($columnsIds as $columnId) {
                // Verify custom columns existence / match
                if (in_array($this->_columns[$columnId]['index'], $availableCodes, true)) {
                    $this->_columns[$columnId]['missing'] = 0;
                    $foundCustomIds[] = $columnId;
                }
            }
        }
        
        // Mark found to be missing columns as such
        $foundIds   = array_merge(
            $foundGridIds,
            $foundCollectionIds,
            $foundAttributesIds,
            $foundCustomIds
        );
        $missingIds = array_diff(array_keys($this->_columns), $foundIds);
        
        foreach ($missingIds as $missingId) {
            if ($checkedCollection
                || !$this->isCollectionColumnOrigin($this->_columns[$missingId]['origin'])) {
                $this->_columns[$missingId]['missing'] = 1;
            }
        }
        
        $this->save();
        return $checkedCollection;
    }
    
    /**
    * Return additional parameters needed for edit,
    * corresponding to given edit block
    * 
    * @param Mage_Adminhtml_Block_Widget_Grid $grid Grid block instance
    * @return array
    */
    public function getAdditionalEditParams(Mage_Adminhtml_Block_Widget_Grid $grid)
    {
        if (!is_null($this->_typeModel)) {
            return $this->_typeModel->getAdditionalEditParams($this->getBlockType(), $grid);
        }
        return array();
    }
    
    /**
    * Return grid row identifiers
    * 
    * @param Varien_Object $row Grid row
    * @return array
    */
    public function getCollectionRowIdentifiers(Varien_Object $row)
    {
        if (!is_null($this->_typeModel)) {
            return $this->_typeModel->getEntityRowIdentifiers($this->getBlockType(), $row);
        }
        return array();
    }
    
    /**
    * Grid columns sort callback
    * 
    * @param array $a
    * @param array $b
    * @return int
    */
    protected function _sortColumns($a, $b)
    {
        return ($a['order'] < $b['order'] 
            ? -1 : ($a['order'] > $b['order'] ? 1 : strcmp($a['header'], $b['header'])));
    }
    
    /**
    * Return column block values for given collection column
    * 
    * @param string $index Column index
    * @param string $rendererType Renderer type code
    * @param string $rendererParams Encoded renderer parameters
    * @param Mage_Core_Model_Store $store Current store
    * @return array
    */
    protected function _getCollectionColumnGridValues($index, $rendererType, $rendererParams, $store)
    {
        $renderer = Mage::getSingleton('customgrid/column_renderer_collection')
            ->getRendererInstanceByCode($rendererType, $rendererParams);
        
        if ($renderer) {
            return $renderer->getColumnGridValues($index, $store, $this);
        }
        
        return array();
    }
    
    /**
    * Return column block values for given attribute column
    * 
    * @param Mage_Eav_Model_Entity_Attribute $attribute Corresponding attribute model
    * @param string $rendererParams Encoded renderer parameters
    * @param Mage_Core_Model_Store $store Current store
    * @return array
    */
    protected function _getAttributeColumnGridValues($attribute, $rendererParams, $store)
    {
        $singleton = Mage::getSingleton('customgrid/column_renderer_attribute');
        $renderers = $singleton->getRenderersInstances();
        
        foreach ($renderers as $renderer) {
            if ($renderer->isAppliableToColumn($attribute, $this)) {
                if (is_array($params = $singleton->decodeParameters($rendererParams))) {
                    $renderer->addData($params);
                }
                $values = $renderer->getColumnGridValues($attribute, $store, $this);
                return (is_array($values) ? $values : array());
            }
        }
        
        return array();
    }
    
    /**
    * Return column block values for given custom column
    * 
    * @param string $index Column index
    * @param BL_CustomGrid_Model_Custom_Column_Abstract $customColumn Custom column model
    * @param string $rendererType Renderer type code
    * @param array $rendererParams Renderer parameters
    * @param string $customParams Encoded customization parameters
    * @param Mage_Core_Model_Store $store Current store
    * @param Mage_Adminhtml_Block_Widget_Grid Grid block
    * @retrn 
    */
    protected function _getCustomColumnGridValues($id, $index, $customColumn, $rendererType, $rendererParams,
        $customParams, $store, $block)
    {
        if ($customColumn->getAllowRenderers()) {
            if ($customColumn->getLockedRenderer()
                && ($customColumn->getLockedRenderer() != $renderer)) {
                $rendererType   = $customColumn->getLockedRenderer();
                $rendererParams = array();
            }
            $renderer = Mage::getSingleton('customgrid/column_renderer_collection')
                ->getRendererInstanceByCode($rendererType, $rendererParams);
        } else {
            $renderer = null;
        }
        if (!empty($customParams)) {
            $customParams = Mage::getSingleton('customgrid/grid_type')->decodeParameters($customParams);
            $customParams = (is_array($customParams) ? $customParams : array());
        } else {
            $customParams = array();
        }
        return $customColumn->applyToGridBlock($block, $this, $id, $index, $customParams, $store, $renderer);
    }
    
    /**
    * Encode filters array
    * 
    * @param array $filters
    * @return string
    */
    public function encodeGridFiltersArray($filters)
    {
        if (is_array($filters)) {
            return base64_encode(http_build_query($filters));
        } else {
            return $filters;
        }
    }
    
    /**
    * Decode filters string
    * 
    * @param string $filters
    * @return array
    */
    public function decodeGridFiltersString($filters)
    {
        if (is_string($filters)) {
            return Mage::helper('adminhtml')->prepareFilterString($filters);
        } else {
            return $filters;
        }
    }
    
    /**
    * Compare grid filter values
    * 
    * @param mixed $a
    * @param mixed $b
    * @return bool
    */
    public function compareGridFilterValues($a, $b)
    {
        if (is_array($a) && is_array($b)) {
            ksort($a);
            ksort($b);
            $a = $this->encodeGridFiltersArray($a);
            $b = $this->encodeGridFiltersArray($b);
            return ($a == $b);
        } else {
            return ($a === $b);
        }
    }
    
    /**
    * Verify validities of filters applied to given grid block,
    * and return safely appliable filters
    * Mostly used for custom columns, which may change of renderers
    * (and those renderers may crash with unexpected kind of filter values)
    * 
    * @param Mage_Adminhtml_Block_Widget_Grid $grid Grid block
    * @param array $filters Applied filters
    * @return array
    */
    public function verifyGridBlockFilters(Mage_Adminhtml_Block_Widget_Grid $grid, $filters)
    {
        // Get previous filters informations from session
        $session = Mage::getSingleton('adminhtml/session');
        // Applied ones
        $sessionFilters = $session->getData(self::SESSION_BASE_KEY_APPLIED_FILTERS . $this->getId());
        $sessionFilters = (is_array($sessionFilters) ? $sessionFilters : array());
        // Removed ones
        $removedFilters = $session->getData(self::SESSION_BASE_KEY_REMOVED_FILTERS . $this->getId());
        $removedFilters = (is_array($removedFilters) ? $removedFilters : array());
        
        $foundIds = array();
        $newRemovedIds = array();
        $attributesRenderers = $this->getAvailableAttributesRendererTypes();
        
        /*
        Verify grid tokens, if request one does not correspond to session one,
        then it is almost sure that we currently come from anywhere but from an effective grid action
        (such as search, sort, export, pagination, ...)
        May be too restrictive, but at the moment, rather be too restrictive than not enough
        */
        if ($grid->getRequest()->has(self::GRID_TOKEN_PARAM_NAME)
            && $session->hasData(self::SESSION_BASE_KEY_TOKEN . $this->getId())) {
            $requestValue = $grid->getRequest()->getParam(self::GRID_TOKEN_PARAM_NAME, null);
            $sessionValue = $session->getData(self::SESSION_BASE_KEY_TOKEN . $this->getId());
            $isGridAction = ($requestValue == $sessionValue);
        } else {
            $isGridAction = false;
        }  
        
        $columns = $this->getColumns(false, true);
        
        foreach ($filters as $columnId => $data) {
            if (isset($columns[$columnId])) {
                $column = $columns[$columnId];
                
                if (isset($sessionFilters[$columnId])) {
                    // Previously existing/applied filter
                    $changed = false;
                    
                    if ($sessionFilters[$columnId]['origin'] != $column['origin']) {
                        $changed = true;
                    } elseif ($this->isCollectionColumnOrigin($column['origin'])) {
                        // Check renderer types for collection columns
                        $changed = ($sessionFilters[$columnId]['renderer_type'] != $column['renderer_type']);
                    } elseif ($this->isAttributeColumnOrigin($column['origin'])) {
                        // Check corrresponding attributes renderers for attribute columns
                        $oldIndex = $sessionFilters[$columnId]['index'];
                        
                        if (isset($attributesRenderers[$oldIndex])) {
                            $changed = ($attributesRenderers[$oldIndex] != $attributesRenderers[$column['index']]);
                        } else {
                            $changed = true;
                        }
                    } elseif ($this->isCustomColumnOrigin($column['origin'])) {
                        /*
                        Check index (= corresponding model - although not meant to change),
                        renderer type and custom params for custom columns
                        */
                        $oldIndex  = $sessionFilters[$columnId]['index'];
                        $typeModel = Mage::getSingleton('customgrid/grid_type');
                        
                        $rendererTypes = array(
                            'old' => $sessionFilters[$columnId]['renderer_type'],
                            'new' => $column['renderer_type'],
                        );
                        $customParams  = array(
                            'old' => $typeModel->decodeParameters($sessionFilters[$columnId]['custom_params'], true),
                            'new' => $typeModel->decodeParameters($column['custom_params'], true),
                        );
                        
                        if (($oldIndex != $column['index'])
                            || !is_object($column['custom_column'])
                            || ($column['custom_column']->shouldInvalidateFilters($this, $column, $customParams, $rendererTypes))) {
                            $changed = true;
                        }
                    }
                    
                    if ($changed) {
                        // Column has significantly changed, unvalidate filter
                        // Remove filter from filters array, to prevent it from being applied
                        unset($filters[$columnId]);
                        // Remove filter from session, to allow new filters to later be set on this column
                        unset($sessionFilters[$columnId]);
                        // Remember which value has been unvalidated, to prevent it to be re-applied from, eg, a page refresh
                        $removedFilters[$columnId] = $data;
                        $newRemovedIds[] = $columnId;
                    }
                } elseif (isset($removedFilters[$columnId]) && !$isGridAction) {
                    // Filter on a column for which another applied filter was previously removed
                    if ($this->compareGridFilterValues($removedFilters[$columnId], $data)) {
                        // Previously removed filter had same value, unvalidate it again
                        unset($filters[$columnId]);
                    }
                } else {
                    // New filter, remember some needed informations in session
                    $sessionFilters[$columnId] = array(
                        'index'  => $column['index'],
                        'origin' => $column['origin'],
                        'renderer_type' => $column['renderer_type'],
                        'custom_params' => $column['custom_params'],
                    );
                }
                
                $foundIds[] = $columnId;
            } else {
                // Unexisting column : unneeded filter
                unset($filters[$columnId]);
            }
        }
        
        /**
        * Note : adding new parameters to grid request 
        * will make them be added to, eg, URLs got from next retrievals of current URL
        */
        
        /*
        Add our token to current request and session
        Use ":" in hash to force Varien_Db_Adapter_Pdo_Mysql::query() using a bind param instead of full request path,
        (as it uses this condition : strpos($sql, ':') !== false),
        when querying core_url_rewrite table, else the query could be too long, 
        making Zend_Db_Statement::_stripQuoted() sometimes crash on one of its call to preg_replace()
        */
        $tokenValue = Mage::helper('core')->uniqHash('blcg:');
        $grid->getRequest()->setParam(self::GRID_TOKEN_PARAM_NAME, $tokenValue);
        $session->setData(self::SESSION_BASE_KEY_TOKEN . $this->getId(), $tokenValue);
        
        // Remove obsolete filters and save up-to-date filters array to session
        $obsoleteIds = array_diff(array_keys($sessionFilters), $foundIds);
        foreach ($obsoleteIds as $columnId) {
            unset($sessionFilters[$columnId]);
        }
        $session->setData(self::SESSION_BASE_KEY_APPLIED_FILTERS . $this->getId(), $sessionFilters);
        
        /*
        Remove removed filters once a grid action is done
        The only remaining potential source of "maybe wrong" filters could come from 
        the use of an old URL with obsolete filter(s) in it (eg from browser history),
        but there is no way at the moment to detect them
        (at least I didnt find a simple one with few impacts)
        */
        if ($isGridAction) {
            $session->setData(
                self::SESSION_BASE_KEY_REMOVED_FILTERS.$this->getId(), 
                array_intersect_key($removedFilters, $newRemovedIds)
            );
        } else {
            $session->setData(
                self::SESSION_BASE_KEY_REMOVED_FILTERS.$this->getId(), 
                $removedFilters
            );
        }
        
        $filterParam = $this->encodeGridFiltersArray($filters);
        
        if ($grid->blcg_getSaveParametersInSession()) {
            $session->setData($grid->blcg_getSessionParamKey($grid->getVarNameFilter()), $filterParam);
        }
        if ($grid->getRequest()->has($grid->getVarNameFilter())) {
            $grid->getRequest()->setParam($grid->getVarNameFilter(), $filterParam);
        }
        $grid->blcg_setFilterParam($filterParam);
        
        return $filters;
    }
    
    public function getGridBlockDefaultParamValue($type, $blockValue, $customValue=null, $fromCustom=false, $originalValue=null)
    {
        // @todo review this code (seems to be working correctly, but the fact is what should actually be the correct way ?)
        // @todo in the meantime, greatly improve corresponding hints / descriptions, make it as intuitive as possible :)
        $value = $blockValue;
        $customValue = (!$fromCustom ? $this->getData('default_'.$type) : $customValue);
        
        if (!$behaviour = $this->_getData('default_'.$type.'_behaviour')) {
            $behaviour = $this->_getConfigHelper()->getCustomDefaultParamBehaviour($type);
        }
        if ($behaviour == self::GRID_DEFAULT_PARAM_FORCE_CUSTOM) {
            // Take the custom value if it is available, else keep the block value
            if (!is_null($customValue)) {
                $value = $customValue;
            }
        } elseif ($behaviour == self::GRID_DEFAULT_PARAM_FORCE_ORIGINAL) {
            // Take custom value if there is no one for block and if we're currently setting custom default params
            if (is_null($blockValue) && $fromCustom) {
                $value = $blockValue;
            }
        } elseif (($type == 'filter')
                  && (($behaviour == self::GRID_DEFAULT_PARAM_MERGE_DEFAULT)
                      || ($behaviour == self::GRID_DEFAULT_PARAM_MERGE_BASE_CUSTOM)
                      || ($behaviour == self::GRID_DEFAULT_PARAM_MERGE_BASE_ORIGINAL))) {
            $blockFilters  = (is_array($blockValue)  ? $blockValue  : array());
            $customFilters = (is_array($customValue) ? $customValue : array());
            
            if ($behaviour == self::GRID_DEFAULT_PARAM_MERGE_BASE_CUSTOM) {
                $value = array_merge($customFilters, $blockFilters);
            } elseif ($behaviour == self::GRID_DEFAULT_PARAM_MERGE_BASE_ORIGINAL) {
                $value = array_merge($blockFilters, $customFilters);
            } else {
                if ($fromCustom) {
                    $value = array_merge($blockFilters, $customFilters);
                } else {
                    $value = array_merge($customFilters, $blockFilters);
                }
            }
        } else {
            // Take "natural order" value
            if (!is_null($customValue) && $fromCustom) {
                $value = $customValue;
            }
        }
        
        if ($type == 'limit') {
            // Check limit against available values, return original value if invalid
            // @todo here is assumed that the only value that is checked is the prioritized one, should we fallback instead ?
            
            if (!in_array($value, $this->getPaginationValues())) {
                $value = (is_null($originalValue) ? $blockValue : $originalValue);
            }
        }
        
        return $value;
    }
    
    /**
    * Apply base default limit to grid block (based on possibly custom pagination values)
    * 
    * @param Mage_Adminhtml_Block_Widget_Grid $grid Grid block instance
    * @return this
    */
    public function applyBaseDefaultLimitToGridBlock(Mage_Adminhtml_Block_Widget_Grid $grid)
    {
        $configLimit  = $this->_getConfigHelper()->getDefaultPaginationValue();
        $blockLimit   = $grid->getDefaultLimit();
        $defaultLimit = null;
        $values = $this->getPaginationValues();
        
        if (!empty($configLimit) && in_array($configLimit, $values)) {
            $defaultLimit = $configLimit;
        } elseif (!empty($blockLimit) && in_array($blockLimit, $values)) {
            $defaultLimit = $blockLimit;
        } else {
            $defaultLimit = array_shift($values);
        }
        
        $grid->blcg_setDefaultLimit($defaultLimit, true);
        return $this;
    }
    
    /**
    * Apply default parameters to grid block
    * 
    * @param Mage_Adminhtml_Block_Widget_Grid $grid Grid block instance
    * @return this
    */
    public function applyDefaultToGridBlock(Mage_Adminhtml_Block_Widget_Grid $grid)
    {
        if ($default = $this->_getData('default_page')) {
            $grid->blcg_setDefaultPage($default);
        }
        if (($default = $this->_getData('default_limit'))
            && in_array($default, $this->getPaginationValues())) {
            $grid->blcg_setDefaultLimit($default);
        }
        if ($default = $this->_getData('default_sort')) {
            $grid->blcg_setDefaultSort($default);
        }
        if ($default = $this->_getData('default_dir')) {
            $grid->blcg_setDefaultDir($default);
        }
        if ($filters = $this->_getData('default_filter')) {
            if (is_array($filters = @unserialize($filters))) {
                // Only apply still valid filters
                $columns   = $this->getColumns(false, true);
                $appliable = array();
                $attributesRenderers = $this->getAvailableAttributesRendererTypes();
                
                foreach ($filters as $columnId => $filter) {
                    if (isset($columns[$columnId])) {
                        $column = $columns[$columnId];
                        
                        // Basically, those are the same verifications than the ones in verifyGridBlockFilters()
                        if ($filter['column']['origin'] == $column['origin']) {
                            if ($this->isCollectionColumnOrigin($column['origin'])) {
                                $valid = ($filter['column']['renderer_type'] == $column['renderer_type']);
                            } elseif ($this->isAttributeColumnOrigin($column['origin'])) {
                                $valid = ($filter['column']['renderer_type'] == $attributesRenderers[$column['index']]);
                            } elseif ($this->isCustomColumnOrigin($column['origin'])) {
                                $oldIndex  = $filter['column']['index'];
                                $typeModel = Mage::getSingleton('customgrid/grid_type');
                                
                                $rendererTypes = array(
                                    'old' => $filter['column']['renderer_type'],
                                    'new' => $column['renderer_type'],
                                );
                                $customParams  = array(
                                    'old' => $typeModel->decodeParameters($filter['column']['custom_params'], true),
                                    'new' => $typeModel->decodeParameters($column['custom_params'], true),
                                );
                                
                                if (($oldIndex != $column['index'])
                                    || !is_object($column['custom_column'])
                                    || ($column['custom_column']->shouldInvalidateFilters($this, $column, $customParams, $rendererTypes))) {
                                    $valid = false;
                                } else {
                                    $valid = true;
                                }
                            } else {
                                $valid = true;
                            }
                            if ($valid) {
                                $appliable[$columnId] = $filter['value'];
                            }
                        }
                    }
                }
                
                $grid->blcg_setDefaultFilter($appliable);
            }
        }
        return $this;
    }
    
    public function getIgnoreCustomHeaders()
    {
        return $this->_getConfigHelper()->getIgnoreCustomHeaders();
    }
    
    public function getIgnoreCustomWidths()
    {
        return $this->_getConfigHelper()->getIgnoreCustomWidths();
    }
    
    public function getIgnoreCustomAlignments()
    {
        return $this->_getConfigHelper()->getIgnoreCustomAlignments();
    }
    
    public function getPaginationValues()
    {
        // @todo do not forget to reset this whenever different pagination values will be available at grid-level
        if (is_null($this->_paginationValues)) {
            $values = $this->_getConfigHelper()->getPaginationValues();
            
            if (!is_array($values) || empty($values)) {
                $values = $this->_defaultPaginationValues;
            } elseif ($this->_getConfigHelper()->getMergeBasePagination()) {
                $values = array_unique(array_merge($values, $this->_defaultPaginationValues));
                sort($values, SORT_NUMERIC);
            }
            
            $this->_paginationValues = $values;
        }
        return $this->_paginationValues;
    }
    
    public function getPinnableHeader()
    {
        return $this->_getConfigHelper()->getPinHeader();
    }
    
    /**
    * Apply columns values to grid block
    * 
    * @param Mage_Adminhtml_Block_Widget_Grid $grid Grid block instance
    * @param bool $applyFromCollection Whether values from collection columns should be applied
    * @return this
    */
    public function applyColumnsToGridBlock(Mage_Adminhtml_Block_Widget_Grid $grid, $applyFromCollection)
    {
        $gridIds = array_keys($grid->getColumns());
        $columnsOrders = array();
        $columns = $this->getColumns(false, true);
        uasort($columns, array($this, '_sortColumns'));
        $attributes = $this->getAvailableAttributes();
        
        foreach ($columns as $column) {
            if (!in_array($column['id'], $gridIds, true)) {
                if ($column['is_visible'] && !$column['missing']
                    && (!$this->isCollectionColumnOrigin($column['origin']) || $applyFromCollection)) {
                    // Add from collection/attribute visible and not missing columns
                    $lockedValues = $this->getColumnLockedValues($column['id']);
                    
                    $data = array(
                        'header' => $column['header'],
                        'align'  => $column['align'],
                        'width'  => $column['width'],
                        'index'  => $column['index'],
                    );
                    $data = array_merge($data, array_intersect_key($lockedValues, $data));
                    
                    if ($this->isCollectionColumnOrigin($column['origin'])) {
                        if (isset($lockedValues['renderer'])
                            || !is_null($column['renderer_type'])) {
                            // Add collection specific column values
                            if (isset($lockedValues['renderer'])) {
                                $rendererType   = $lockedValues['renderer'];
                                $rendererParams = ($rendererType == $column['renderer_type'] ? $column['renderer_params'] : array());
                            } else {
                                $rendererType   = $column['renderer_type'];
                                $rendererParams = $column['renderer_params'];
                            }
                            
                            $data = array_merge(
                                $data, 
                                $this->_getCollectionColumnGridValues(
                                    $column['index'],
                                    $rendererType,
                                    $rendererParams,
                                    $grid->blcg_getStore()
                                )
                            );
                        }
                    } elseif ($this->isAttributeColumnOrigin($column['origin'])) {
                        if (!isset($attributes[$column['index']])) {
                            // Unknown attribute
                            continue;
                        }
                        
                        if (is_null($column['store_id'])) {
                            // Grid's store
                            $store = $grid->blcg_getStore();
                        } else {
                            // Specific store
                            $store = Mage::app()->getStore($column['store_id']);
                        }
                        
                        // Use auto-generated unique ID as index
                        $alias = self::GRID_COLUMN_ATTRIBUTE_GRID_ALIAS
                            . str_replace(self::GRID_COLUMN_ATTRIBUTE_ID_PREFIX, '', $column['id']);
                        $data['index'] = $alias;
                        
                        // Tell grid to select current attribute
                        $grid->blcg_addAdditionalAttribute(array(
                            'alias'     => $alias,
                            'attribute' => $attributes[$column['index']],
                            'bind'      => 'entity_id',
                            'filter'    => null,
                            'join_type' => 'left',
                            'store_id'  => $store->getId(),
                        ));
                        
                        // Add attribute specific column values
                        $data = array_merge(
                            $data,
                            $this->_getAttributeColumnGridValues(
                                $attributes[$column['index']],
                                $column['renderer_params'],
                                $store
                            )
                        );
                    } elseif ($this->isCustomColumnOrigin($column['origin'])) {
                        if (!is_object($column['custom_column'])) {
                            // Unknown column
                            continue;
                        } 
                        
                        if (is_null($column['store_id'])) {
                            // Grid's store
                            $store = $grid->blcg_getStore();
                        } else {
                            // Specific store
                            $store = Mage::app()->getStore($column['store_id']);
                        }
                        
                        // Use auto-generated unique ID as index
                        $alias = self::GRID_COLUMN_CUSTOM_GRID_ALIAS
                            . str_replace(self::GRID_COLUMN_CUSTOM_ID_PREFIX, '', $column['id']);
                        $data['index'] = $alias;
                        
                        // Add attribute specific column values
                        $customValues = $this->_getCustomColumnGridvalues(
                            $column['id'],
                            $data['index'],
                            $column['custom_column'],
                            $column['renderer_type'],
                            $column['renderer_params'],
                            $column['custom_params'],
                            $store,
                            $grid
                        );
                        
                        if (!is_array($customValues)) {
                            // An error occured while applying custom column
                            continue;
                        }
                        
                        $data = array_merge($data, $customValues);
                    }
                    
                    if (isset($lockedValues['config_values'])
                        && is_array($lockedValues['config_values'])) {
                        $data = array_merge($data, $lockedValues['config_values']);
                    }
                    
                    $grid->addColumn($column['id'], $data);
                    $columnsOrders[] = $column['id'];
                }
            } else {
                if ($column['is_visible']) {
                    // Update visible columns
                    if ($gridColumn = $grid->getColumn($column['id'])) {
                        if (!$this->getIgnoreCustomWidths()) {
                            $gridColumn->setWidth($column['width']);
                        }
                        if (!$this->getIgnoreCustomAlignments()) {
                            $gridColumn->setAlign($column['align']);
                        }
                        if (!$this->getIgnoreCustomHeaders()) {
                            $gridColumn->setHeader($column['header']);
                        }
                    }
                    $columnsOrders[] = $column['id'];
                    
                } else {
                    // Remove not visible columns
                    $grid->blcg_removeColumn($column['id']);
                }
            }
            
            if ($column['filter_only']
                && ($columnBlock = $grid->getColumn($column['id']))) {
                $columnBlock->setBlcgFilterOnly(true);
                
                if ($grid->blcg_isExport()) {
                    // Columns with is_system flag on won't be exported, so forcing it will save us two overloads
                    $columnBlock->setIsSystem(true);
                }
            }
        }
        
        // Apply columns orders
        $grid->blcg_resetColumnsOrder();
        $previousId = null;
        
        foreach ($columnsOrders as $columnId) {
            if (!is_null($previousId)) {
                $grid->addColumnsOrder($columnId, $previousId);
            }
            $previousId = $columnId;
        }
        
        $grid->sortColumnsByOrder();
        
        return $this;
    }
    
    /**
    * Extract column values from given array
    * 
    * @param array $column Array of values
    * @param bool $allowStore Whether store ID value is allowed
    * @param bool $allowRenderer Whether renderer values are allowed
    * @param bool $needRendererType Whether renderer type is needed
    * @param bool $allowEditable Whether editability value is allowed
    * @param bool $allowCustomParams Whether custom params are allowed
    * @return array Extracted values
    */
    protected function _extractColumnValues(array $column, $allowStore=false, $allowRenderer=false,
        $needRendererType=true, $allowEditable=false, $allowCustomParams=false)
    {
        $values = array();
        
        if (isset($column['align'])
            && array_key_exists($column['align'], self::getColumnAlignments())) {
            $values['align'] = $column['align'];
        }
        if (isset($column['header'])) {
            $values['header'] = $column['header'];
        }
        
        $values['is_visible']  = (isset($column['is_visible']) && $column['is_visible'] ? 1 : 0);
        $values['filter_only'] = ($values['is_visible'] && isset($column['filter_only']) && $column['filter_only'] ? 1 : 0);
        
        if (isset($column['order'])) {
            $values['order'] = intval($column['order']);
        }
        if (isset($column['width'])) {
            $values['width'] = $column['width'];
        }
        if ($allowStore && isset($column['store_id']) && ($column['store_id'] !== '')) {
            $values['store_id'] = $column['store_id'];
        } else {
            $values['store_id'] = null;
        }
        if ($allowRenderer 
            && (!$needRendererType || (isset($column['renderer_type']) && ($column['renderer_type'] !== '')))) {
             $values['renderer_type'] = ($needRendererType ? $column['renderer_type'] : null);
             if (isset($column['renderer_params']) && ($column['renderer_params'] !== '')) {
                 $values['renderer_params'] = $column['renderer_params'];
             } else {
                 $values['renderer_params'] = null;
             }
        } else {
            $values['renderer_type'] = null;
            $values['renderer_params'] = null;
        }
        if ($allowEditable) {
            $values['allow_edit'] = (isset($column['editable']) && $column['editable'] ? 1 : 0);
        }
        if ($allowCustomParams && isset($column['custom_params']) && ($column['custom_params'] !== '')) {
            $values['custom_params'] = $column['custom_params'];
        } else {
            $values['custom_params'] = null;
        }
        
        return $values;
    }
    
    /**
    * Update grid columns according to given values and save
    * 
    * @param array $columns New columns informations
    * @return this
    */
    public function updateColumns(array $columns, $mustSave=true)
    {
        $this->loadColumns();
        $allowEditable = $this->checkUserActionPermission(self::GRID_ACTION_CHOOSE_EDITABLE_COLUMNS);
        
        // Update existing columns
        foreach ($this->getColumns(true, true) as $columnId => $column) {
            if (isset($columns[$column['column_id']])) {
                $newColumn     = $columns[$column['column_id']];
                $isCollection  = $this->isCollectionColumnOrigin($column['origin']);
                $isAttribute   = $this->isAttributeColumnOrigin($column['origin']);
                $isCustom      = $this->isCustomColumnOrigin($column['origin']);
                $customColumn  = ($isCustom && is_object($column['custom_column']) ? $column['custom_column'] : false);
                
                $this->_columns[$columnId] = array_merge(
                    $this->_columns[$columnId],
                    $this->_extractColumnValues(
                        $newColumn,
                        ($isAttribute || ($customColumn && $customColumn->getAllowStore())),
                        ($isCollection || $isAttribute || ($customColumn && $customColumn->getAllowRenderers())),
                        ($isCollection || ($customColumn && $customColumn->getAllowRenderers())),
                        ($allowEditable && isset($column['editable']) && $column['editable']),
                        $isCustom
                    )
                );
                    
                if ($isAttribute && isset($newColumn['index'])
                    && in_array($newColumn['index'], $this->getAvailableAttributesCodes(), true)) {
                    // Update index if possible for attribute columns
                    $this->_columns[$columnId]['index'] = $newColumn['index'];
                }
                
                // At the end, there should only remain in $columns new attribute columns
                unset($columns[$column['column_id']]);
            } else {
                // Assume deleted column
                if (($key = array_search($columnId, $this->_originIds[$this->_columns[$columnId]['origin']])) !== false) {
                    unset($this->_originIds[$this->_columns[$columnId]['origin']][$key]);
                }
                unset($this->_columns[$columnId]);
            }
        }
        
        // Add new attribute columns
        if ($this->canHaveAttributeColumns($this->getBlockType())) {
            foreach ($columns as $columnId => $column) {
                if ($columnId < 0 // Concerned columns IDs should be negative, so assume others IDs are inexisting ones
                    && isset($column['index'])
                    && in_array($column['index'], $this->getAvailableAttributesCodes(), true)) {
                    $newColumnId = $this->_getNextAttributeColumnId();
                    
                    $this->_columns[$newColumnId] = array_merge(
                        array(
                            'grid_id'     => $this->getId(),
                            'id'          => $newColumnId,
                            'index'       => $column['index'],
                            'width'       => '',
                            'align'       => self::GRID_COLUMN_ALIGNMENT_LEFT,
                            'header'      => '',
                            'order'       => 0,
                            'origin'      => self::GRID_COLUMN_ORIGIN_ATTRIBUTE,
                            'is_visible'  => 1,
                            'filter_only' => 0,
                            'is_system'   => 0,
                            'missing'     => 0,
                        ),
                        $this->_extractColumnValues($column, true, true, false, $allowEditable)
                    );
                    
                    $this->_originIds[self::GRID_COLUMN_ORIGIN_ATTRIBUTE][] = $newColumnId;
                }
            }
        }
        
        // Recompute max order, as it may have now changed
        $this->_recomputeMaxOrder();
        
        return ($mustSave ? $this->save() : $this->setDataChanges(true));
    }
    
    /**
    * Update grid available custom columns and save
    * 
    * @param array $columns New columns informations
    * @return this
    */
    public function updateCustomColumns(array $columns, $mustSave=true)
    {
        $this->loadColumns();
        $helper = $this->_getHelper();
        
        if (!is_null($this->_typeModel)) {
            $typeCode = $this->_typeModel->getCode();
        } else {
            return $this;
        }
        $availableColumns = $this->getAvailableCustomColumns();
        $availableCodes   = array_keys($availableColumns);
        
        // Requested codes
        $customCodes = (!is_null($typeCode) ? $columns : array());
        // Codes of the same grid type that are already used
        $usedCodes   = array();
        // IDs that should be removed
        $removedIds  = array();
        
        foreach ($this->_originIds[self::GRID_COLUMN_ORIGIN_CUSTOM] as $columnId) {
            if (!is_null($typeCode)) {
                $parts = explode('/', $this->_columns[$columnId]['index']);
                
                if (($typeCode == $parts[0])
                    && in_array($parts[1], $customCodes)
                    && in_array($parts[1], $availableCodes)) {
                    $usedCodes[] = $parts[1];
                } else {
                    $removedIds[] = $columnId;
                }
            } else {
                $removedIds[] = $columnId;
            }
        }
        
        // Add new columns whenever needed
        $newCodes = array_intersect(
            $availableCodes,
            array_diff($customCodes, $usedCodes)
        );
        $columnsGroups = $this->getCustomColumnsGroups();
        
        foreach ($newCodes as $code) {
            $newColumnId = $this->_getNextCustomColumnId();
            $columnModel = $availableColumns[$code];
            
            if (isset($columnsGroups[$columnModel->getGroupId()])
                && $this->_getConfigHelper()->getAddGroupToCustomColumnsDefaultHeader()) {
                $header = $helper->__('%s (%s)', $columnModel->getName(), $columnsGroups[$columnModel->getGroupId()]);
            } else {
                $header = $columnModel->getName();
            }
            
            $this->_columns[$newColumnId] = array(
                'grid_id'         => $this->getId(),
                'id'              => $newColumnId,
                'index'           => $typeCode.'/'.$code,
                'width'           => '',
                'align'           => self::GRID_COLUMN_ALIGNMENT_LEFT,
                'header'          => $header,
                'order'           => $this->_getNextOrder(),
                'origin'          => self::GRID_COLUMN_ORIGIN_CUSTOM,
                'is_visible'      => 1,
                'filter_only'     => 0,
                'is_system'       => 0,
                'missing'         => 0,
                'store_id'        => null,
                'renderer_type'   => null,
                'renderer_params' => null,
                'allow_edit'      => 0,
                'custom_params'   => null,
            );
            
            $this->_originIds[self::GRID_COLUMN_ORIGIN_CUSTOM][] = $newColumnId;
        }
        
        // Remove necessary IDs
        foreach ($removedIds as $columnId) {
            unset($this->_columns[$columnId]);
        }
        
        // Recompute max order, as it may have now changed
        $this->_recomputeMaxOrder();
        
        return ($mustSave ? $this->save() : $this->setDataChanges(true));
    }
    
    /**
    * Update grid default parameters and save
    * 
    * @param array $add New default parameters values
    * @param array $remove Keys of default parameters to remove
    * @param bool $mustSave Whether grid must directly be saved after having updated columns
    * @return this
    */
    public function updateDefaultParameters($add, $remove=null, $mustSave=true)
    {
        if (is_array($add)) {
            // Save new default parameters
            if (isset($add['page'])) {
                $this->setData('default_page', intval($add['page']));
            }
            if (isset($add['limit'])) {
                $this->setData('default_limit', intval($add['limit']));
            }
            if (isset($add['sort'])) {
                if (isset($this->_columns[$add['sort']])) {
                    $this->setData('default_sort', $add['sort']);
                } else {
                    $this->setData('default_sort', null);
                }
            }
            if (isset($add['dir'])) {
                if (($add['dir'] == 'asc') || ($add['dir'] == 'desc')) {
                    $this->setData('default_dir', $add['dir']);
                } else {
                    $this->setData('default_dir', null);
                }
            }
            if (isset($add['filter'])) {
                $filters = $add['filter'];
                
                if (!is_array($filters)) {
                    $filters = $this->decodeGridFiltersString($filters);
                }
                if (is_array($filters) && !empty($filters)) {
                    /*
                    Add some informations from current columns values to filters,
                    to later be able to check if they remain valid
                    */
                    $attributesRenderers = $this->getAvailableAttributesRendererTypes();
                    
                    foreach ($filters as $columnId => $value) {
                        if (isset($this->_columns[$columnId])) {
                            $column = $this->_columns[$columnId];
                            
                            if ($this->isCollectionColumnOrigin($column['origin'])) {
                                $rendererType = $column['renderer_type'];
                            } elseif ($this->isAttributeColumnOrigin($column['origin'])) {
                                $rendererType = $attributesRenderers[$column['index']];
                            } elseif ($this->isCustomColumnOrigin($column['origin'])) {
                                $rendererType = $column['renderer_type'];
                            } else {
                                $rendererType = null;
                            }
                            
                            $filters[$columnId] = array(
                                'value'  => $value,
                                'column' => array(
                                    'origin' => $column['origin'],
                                    'index'  => $column['index'],
                                    'renderer_type' => $rendererType,
                                    'custom_params' => $column['custom_params'],
                                ),
                            );
                        } else {
                            unset($filters[$columnId]);
                        }
                    }
                    
                    $this->setData('default_filter', serialize($filters));
                } else {
                    $this->setData('default_filter', null);
                }
            }
        }
        if (is_array($remove)) {
            // Remove wanted default parameters
            $params = array('page', 'limit', 'sort', 'dir', 'filter');
            foreach ($params as $param) {
                if (isset($remove[$param]) && (bool)$remove[$param]) {
                    $this->setData('default_'.$param, null);
                }
            }
        }
        return ($mustSave ? $this->save() : $this->setDataChanges(true));
    }
    
    public function resetRolesConfig()
    {
        $this->_rolesConfig = array();
        return $this;
    }
    
    public function loadRolesConfig()
    {
        $this->resetRolesConfig();
        
        if ($id = $this->getId()) {
            $this->_rolesConfig = $this->_getResource()->getGridRoles($id);
        }
        
        return $this;
    }
    
    public function getRolesConfig($roleId=null, $default=array())
    {
        return (!is_null($roleId) 
            ? (isset($this->_rolesConfig[$roleId]) ? $this->_rolesConfig[$roleId] : $default)
            : $this->_rolesConfig);
    }
    
    public function getRolePermissions($roleId, $default=array())
    {
        if (is_array($config = $this->getRolesConfig($roleId, false))) {
            return $config['permissions'];
        } else {
            return $default;
        }
    }
    
    public function updateRolesConfig($roles, $mustSave=false)
    {
        if (is_array($roles)) {
            $actions  = self::getGridActions();
            $rolesIds = Mage::getModel('admin/roles')
                ->getCollection()
                ->getAllIds();
            
            $flags = array(
                self::GRID_PERMISSION_USE_CONFIG,
                self::GRID_PERMISSION_ALLOWED,
                self::GRID_PERMISSION_DISALLOWED,
            );
            
            foreach ($roles as $roleId => $config) {
                if (isset($config['permissions']) && is_array($config['permissions'])) {
                    foreach ($config['permissions'] as $action => $flag) {
                        $flag = strval($flag);
                        
                        if (!isset($actions[$action])
                            || !(empty($flag) || in_array($flag, $flags, true))) {
                            unset($roles[$roleId]['permissions'][$action]);
                        }
                    }
                } else {
                    $roles[$roleId]['permissions'] = array();
                }
            }
            
            $this->_rolesConfig = $roles;
        } else {
            $this->_rolesConfig = array();
        }
        return ($mustSave ? $this->save() : $this->setDataChanges(true));
    }
    
    public function checkUserActionPermission($action, $aclPermission=null)
    {
        $session = Mage::getSingleton('admin/session');
        
        // Get user role ID
        if (($user = $session->getUser()) && ($role = $user->getRole())) {
            $roleId = $role->getId();
        } else {
            $roleId  = null;
        }
        
        // Get role permission
        if ($roleId && ($permissions = $this->getRolePermissions($roleId, false))) {
            $permission = (isset($permissions[$action]) ? $permissions[$action] : self::GRID_PERMISSION_USE_CONFIG);
        } else {
            $permission = self::GRID_PERMISSION_USE_CONFIG;
        }
        
        // Compute actual permission
        if ($permission === self::GRID_PERMISSION_DISALLOWED) {
            return false;
        } elseif ($permission === self::GRID_PERMISSION_ALLOWED) {
            return true;
        } else {
            return (is_null($aclPermission)
                ? $session->isAllowed(self::$_gridPermissionsPaths[$action])
                : (bool) $aclPermission);
        }
    }
    
    /**
    * Return column alignments options hash
    * 
    * @return array
    */
    static public function getColumnAlignments()
    {
        if (is_null(self::$_columnAlignments)) {
            $helper = Mage::helper('customgrid');
            
            self::$_columnAlignments = array(
                self::GRID_COLUMN_ALIGNMENT_LEFT   => $helper->__('Left'),
                self::GRID_COLUMN_ALIGNMENT_CENTER => $helper->__('Middle'),
                self::GRID_COLUMN_ALIGNMENT_RIGHT  => $helper->__('Right'),
            );
        }
        return self::$_columnAlignments;
    }
    
    /**
    * Return column origins options hash
    * 
    * @return array
    */
    static public function getColumnOrigins()
    {
        if (is_null(self::$_columnOrigins)) {
            $helper = Mage::helper('customgrid');
            
            self::$_columnOrigins = array(
                self::GRID_COLUMN_ORIGIN_GRID       => $helper->__('Grid'),
                self::GRID_COLUMN_ORIGIN_COLLECTION => $helper->__('Collection'),
                self::GRID_COLUMN_ORIGIN_ATTRIBUTE  => $helper->__('Attribute'),
                self::GRID_COLUMN_ORIGIN_CUSTOM     => $helper->__('Custom'),
            );
        }
        return self::$_columnOrigins;
    }
    
    /**
    * Return whether given origin code is attribute one
    * 
    * @param string $origin Origin code
    * @return bool
    */
    static public function isAttributeColumnOrigin($origin)
    {
        return ($origin == self::GRID_COLUMN_ORIGIN_ATTRIBUTE);
    }
    
    /**
    * Return whether given origin code is collection one
    * 
    * @param string $origin Origin code
    * @return bool
    */
    static public function isCollectionColumnOrigin($origin)
    {
        return ($origin == self::GRID_COLUMN_ORIGIN_COLLECTION);
    }
    
    /**
    * Return whether given origin code is custom one
    * 
    * @param string $origin Origin code
    * @return bool
    */
    static public function isCustomColumnOrigin($origin)
    {
        return ($origin == self::GRID_COLUMN_ORIGIN_CUSTOM);
    }
    
    /**
    * Return whether given origin code is grid one
    * 
    * @param string $origin Origin code
    * @return bool
    */
    static public function isGridColumnOrigin($origin)
    {
        return ($origin == self::GRID_COLUMN_ORIGIN_GRID);
    }
    
    static public function getGridActions()
    {
        if (is_null(self::$_gridActions)) {
            $helper = Mage::helper('customgrid');
            
            self::$_gridActions = array(
                self::GRID_ACTION_CUSTOMIZE_COLUMNS       => $helper->__('Customize Columns'),
                self::GRID_ACTION_USE_CUSTOMIZED_COLUMNS  => $helper->__('Use Customized Columns'),
                self::GRID_ACTION_VIEW_GRID_INFOS         => $helper->__('View Grids Informations'),
                self::GRID_ACTION_EDIT_DEFAULT_PARAMS     => $helper->__('Edit Default Parameters'),
                self::GRID_ACTION_USE_DEFAULT_PARAMS      => $helper->__('Use Default Parameters'),
                self::GRID_ACTION_EXPORT_RESULTS          => $helper->__('Export Results'),
                self::GRID_ACTION_CHOOSE_EDITABLE_COLUMNS => $helper->__('Choose Editable Columns'),
                self::GRID_ACTION_EDIT_COLUMNS_VALUES     => $helper->__('Edit Columns Values'),
                /*
                self::GRID_ACTION_CHOOSE_PROFILE          => $helper->__('Choose Profile'),
                self::GRID_ACTION_CHOOSE_DEFAULT_PROFILE  => $helper->__('Choose Default Profile'),
                self::GRID_ACTION_CREATE_PROFILE          => $helper->__('Create Profile'),
                self::GRID_ACTION_DELETE_PROFILE          => $helper->__('Delete Profile'),
                */ // @todo to reactivate if needed for profiles system
            );
        }
        return self::$_gridActions;
    }
}
