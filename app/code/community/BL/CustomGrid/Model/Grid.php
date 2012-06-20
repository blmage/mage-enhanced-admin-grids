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
 * @copyright  Copyright (c) 2011 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_Grid extends Mage_Core_Model_Abstract
{
    /**
    * Session params keys
    */
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
    
    protected function _construct()
    {
        parent::_construct();
        $this->_init('customgrid/grid');
        $this->setIdFieldName('grid_id');
        $this->resetColumns();
    }
    
    protected function _beforeSave()
    {
        parent::_beforeSave();
        if (!($this->getMaxAttributeColumnId() > 0)) {
            $this->setMaxAttributeColumnId(0);
        }
        return $this;
    }
    
    protected function _afterLoad()
    {
        parent::_afterLoad();
        $this->_initTypeModel();
        $this->loadColumns();
        return $this;
    }
    
    protected function _afterSave()
    {
        parent::_afterSave();
        // Reload columns to get new database IDs and such informations
        $this->loadColumns();
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
    * Reset all columns informations
    * 
    * @return this
    */
    public function resetColumns()
    {
        $this->_columns = array();
        $this->_attributesColumns = array();
        $this->_originIds = array(
            self::GRID_COLUMN_ORIGIN_GRID => array(),
            self::GRID_COLUMN_ORIGIN_COLLECTION => array(),
            self::GRID_COLUMN_ORIGIN_ATTRIBUTE => array(),
        );
        $this->_maxOrder = null;
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
        
        if ($this->getId()) {
            $columns = $this->_getResource()->getGridColumns($this->getId());
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
                
                foreach ($attributes as $code => $attribute) {
                    $attribute->setRendererCode(null);
                    foreach ($renderers as $name => $renderer) {
                        if ($renderer->isAppliableToColumn($attribute, $this)) {
                            $attribute->setRendererCode($name);
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
    * TODO 
    * Turn _exportFile() into public exportFile()
    * Remove all explicit calls such as export[Format]File() from here and custom grid controller (make it give format)
    * Do export in grid type model, so new export types could "simply" be added
    * Add CSV / XML export to abstract grid type model
    */
    
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
    * @return array
    */
    public function getColumns($withEditable=false)
    {
        $columns = $this->_columns;
        
        if ($withEditable && !is_null($this->_typeModel)) {
            $columns = $this->_typeModel->applyEditableConfigsToColumns($this->getBlockType(), $columns, $this);
        }
        
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
        if ($this->isAttributeColumnOrigin($origin)) {
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
                return self::GRID_COLUMN_ATTRIBUTE_GRID_ALIAS
                       . str_replace(self::GRID_COLUMN_ATTRIBUTE_ID_PREFIX, '', $column['id']);
            }
        } elseif (array_key_exists($origin, self::getColumnOrigins())) {
            // Assume given code corresponds to column ID
            if (in_array($code, $this->_originIds[$origin])) {
                // Return column index only if column exists and comes from wanted origin
                return $this->_columns[$code]['index'];
            }
        }
        return null;
    }
    
    /**
    * Return grid sorted columns
    * 
    * @param bool $valid Whether valid columns should be returned (ie not missing ones)
    * @param bool $missing Whether missing columns should be returned
    * @param bool $fromAttribute Whether attribute columns should be returned
    * @param bool $onlyVisible Whether only visible columns should be returned
    * @param bool $withEditable Whether columns editability informations should be added to result
    * @return array
    */
    public function getSortedColumns($valid=true, $missing=true, $fromAttribute=true, $onlyVisible=false, $withEditable=false)
    {
        $columns = array();
        
        foreach ($this->getColumns($withEditable) as $columnId => $column) {
            if ((!$column['is_visible'] && $onlyVisible)
                || ($column['missing'] && !$missing)
                || (!$column['missing'] && !$valid)
                || ($this->isAttributeColumnOrigin($column['origin']) && !$fromAttribute)) {
                // Unwanted column
                continue;
            }
            $columns[$columnId] = $column;
        }
        
        uasort($columns, array($this, '_sortColumns'));
        return $columns;
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
                        $this->getSortedColumns(true, true, false),
                        array_merge(
                            $this->_typeModel->getEditableFields($this->getGridType()),
                            $this->_typeModel->getEditableAttributeFields($this->getGridType())
                        )
                    )) > 0)
                    || (count($this->_typeModel->getEditableAttributes($this->getGridType())) > 0));
        }
        return false;
    }
    
    /**
    * Return whether the current user has edit permissions over the grid
    * 
    * @return bool
    */
    public function hasUserEditPermissions()
    {
        if (!is_null($this->_typeModel)) {
            return $this->_typeModel->checkUserEditPermissions($this->getGridType());
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
            'is_system'       => ($column->getIsSystem() ? 1 : 0),
            'missing'         => 0,
            'store_id'        => null,
            'renderer_type'   => null,
            'renderer_params' => null,
            'allow_edit'      => 1,
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
            'header'          => Mage::helper('customgrid')->getColumnHeaderName($key),
            'order'           => $order,
            'origin'          => self::GRID_COLUMN_ORIGIN_COLLECTION,
            'is_visible'      => 0,
            'is_system'       => 0,
            'missing'         => 0,
            'store_id'        => null,
            'renderer_type'   => null,
            'renderer_params' => null,
            'allow_edit'      => 1,
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
                if (!in_array($key, $gridIndexes) 
                    && !in_array($key, $this->_originIds[self::GRID_COLUMN_ORIGIN_GRID])
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
                        // TODO reset renderer_type and renderer_params ?
                    )
                );
                
                if (!$this->isGridColumnOrigin($previousOrigin)) {
                    // If column did not previously come from grid, refresh origin IDs
                    unset($this->_originIds[$previousOrigin][array_search($columnId, $this->_originIds[$previousOrigin])]);
                    $this->_originIds[self::GRID_COLUMN_ORIGIN_GRID][] = $columnId;
                }
            } else {
                // New column
                $this->_maxOrder += $this->getOrderPitch();
                $this->_addColumnFromBlock($column, $this->_maxOrder);
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
                        
                        if (!in_array($key, $foundGridIds)) {
                            // Existing column that already came from collection, or not found anymore in the grid
                            if (!in_array($key, $gridIndexes)) {
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
                                    $this->_originIds[self::GRID_COLUMN_ORIGIN_COLLECTION] = $key;
                                    // TODO reset renderer_type and renderer_params ?
                                }
                                
                                $foundCollectionIds[] = $key;
                            } else {
                                // If it does now collide with a grid index, remove it
                                unset($this->_columns[$key]);
                            }
                        } // Existing column from the grid, already handled
                    } else {
                        if (!in_array($key, $foundGridIds)
                            && !in_array($key, $gridIndexes)) {
                            // New column if no collision
                            $this->_maxOrder += $this->getOrderPitch();
                            $this->_addColumnFromCollection($key, $this->_maxOrder);
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
            $attributes = array_keys($this->getAvailableAttributes());
            
            foreach ($columnsIds as $columnId) {
                // Verify attributes existences
                if (in_array($this->_columns[$columnId]['index'], $attributes)) {
                    $this->_columns[$columnId]['missing'] = 0;
                    $foundAttributesIds[] = $columnId;
                }
            }
        }
        
        // Mark found to be missing columns as such
        $foundIds   = array_merge($foundGridIds, $foundCollectionIds, $foundAttributesIds);
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
    * @param string $rendererType Renderer type code
    * @param array $rendererParams Renderer parameters
    * @param string $index Column index
    * @param Mage_Core_Model_Store $store Current store
    */
    protected function _getCollectionColumnGridValues($rendererType, $rendererParams, $index, $store)
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
    * @param array $rendererParams Renderer parameters
    * @param Mage_Core_Model_Store $store Current store
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
        
        foreach ($filters as $columnId => $data) {
            if (isset($this->_columns[$columnId])) {
                $column = $this->_columns[$columnId];
                
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
        The only remaining potential source of "maybe wrong" filters could come  from 
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
        $grid->blcg_setFilterParam($filterParam);
        if ($grid->getRequest()->has($grid->getVarNameFilter())) {
            $grid->getRequest()->setParam($grid->getVarNameFilter(), $filterParam);
        }
        
        return $filters;
    }
    
    /**
    * Apply default parameters to grid block
    * 
    * @param Mage_Adminhtml_Block_Widget_Grid $grid Grid block instance
    * @return this
    */
    public function applyDefaultToGridBlock(Mage_Adminhtml_Block_Widget_Grid $grid)
    {
        // Apply default values
        if ($default = $this->_getData('default_page')) {
            $grid->setDefaultPage($default);
        }
        if ($default = $this->_getData('default_limit')) {
            $grid->setDefaultLimit($default);
        }
        if ($default = $this->_getData('default_sort')) {
            $grid->setDefaultSort($default);
        }
        if ($default = $this->_getData('default_dir')) {
            $grid->setDefaultDir($default);
        }
        if ($filters = $this->_getData('default_filters')) {
            if (is_array($filters = @unserialize($filters))) {
                // Only apply still valid filters
                $appliable = array();
                $attributesRenderers = $this->getAvailableAttributesRendererTypes();
                
                foreach ($filters as $columnId => $filter) {
                    if (isset($this->_columns[$columnId])) {
                        $column = $this->_columns[$columnId];
                        if ($filter['column']['origin'] == $column['origin']) {
                            if ($this->isCollectionColumnOrigin($column['origin'])) {
                                $valid = ($filter['column']['renderer_type'] == $column['renderer_type']);
                            } elseif ($this->isAttributeColumnOrigin($column['origin'])) {
                                $valid = ($filter['column']['renderer_type'] == $attributesRenderers[$column['index']]);
                            } else {
                                $valid = true;
                            }
                            if ($valid) {
                                $appliable[$columnId] = $filter['value'];
                            }
                        }
                    }
                }
                
                $grid->setDefaultFilter($appliable);
            }
        }
        return $this;
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
        $columns = $this->getColumns();
        uasort($columns, array($this, '_sortColumns'));
        $attributes = $this->getAvailableAttributes();
        
        foreach ($columns as $column) {
            if (!in_array($column['id'], $gridIds)) {
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
                                    $rendererType,
                                    $rendererParams,
                                    $column['index'],
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
                    $grid->getColumn($column['id'])
                        ->setWidth($column['width'])
                        ->setAlign($column['align'])
                        ->setHeader($column['header']);
                    $columnsOrders[] = $column['id'];
                } else {
                    // Remove not visible columns
                    $grid->blcg_removeColumn($column['id']);
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
    * @return array Extracted values
    */
    protected function _extractColumnValues(array $column, $allowStore=false, $allowRenderer=false, $needRendererType=true, $allowEditable=false)
    {
        $values = array();
        
        if (isset($column['align'])
            && array_key_exists($column['align'], self::getColumnAlignments())) {
            $values['align'] = $column['align'];
        }
        if (isset($column['header'])) {
            $values['header'] = $column['header'];
        }
        $values['is_visible'] = (isset($column['is_visible']) && $column['is_visible'] ? 1 : 0);
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
        
        return $values;
    }
    
    /**
    * Update grid columns according to given values and save
    * 
    * @param array $columns New columns informations
    * @return this
    */
    public function updateColumns(array $columns)
    {
        $this->loadColumns();
        $allowEditable = Mage::getModel('admin/session')
            ->isAllowed('system/customgrid/editor/choose_columns');
        
        // Update existing columns
        foreach ($this->getColumns(true) as $columnId => $column) {
            if (isset($columns[$column['column_id']])) {
                $newColumn    = $columns[$column['column_id']];
                $isCollection = $this->isCollectionColumnOrigin($column['origin']);
                $isAttribute  = !$isCollection && $this->isAttributeColumnOrigin($column['origin']);
                
                $this->_columns[$columnId] = array_merge(
                    $this->_columns[$columnId],
                    $this->_extractColumnValues(
                        $newColumn,
                        $isAttribute,
                        ($isCollection || $isAttribute),
                        $isCollection,
                        ($allowEditable && isset($column['editable']) && $column['editable'])
                    )
                );
                    
                if ($isAttribute && isset($newColumn['index'])
                    && in_array($newColumn['index'], $this->getAvailableAttributesCodes()))  {
                    // Update index if possible for attribute columns
                    $this->_columns[$columnId]['index'] = $newColumn['index'];
                }
                
                // At the end, there should only remain in $columns new attribute columns
                unset($columns[$column['column_id']]);
            } else {
                // Assume deleted column
                unset($this->_originIds[$this->_columns[$columnId]['origin']][array_search(
                    $columnId, 
                    $this->_columns[$columnId]['origin']
                )]);
                unset($this->_columns[$columnId]);
            }
        }
        
        // Add new attribute columns
        if ($this->canHaveAttributeColumns($this->getBlockType())) {
            foreach ($columns as $columnId => $column) {
                if ($columnId < 0 // Concerned columns IDs should be negative, so assume others IDs are inexisting ones
                    && isset($column['index'])
                    && in_array($column['index'], $this->getAvailableAttributesCodes())) {
                    $newColumnId = $this->_getNextAttributeColumnId();
                    $this->_columns[$newColumnId] = array_merge(
                        array(
                            'grid_id'    => $this->getId(),
                            'id'         => $newColumnId,
                            'index'      => $column['index'],
                            'width'      => '',
                            'align'      => self::GRID_COLUMN_ALIGNMENT_LEFT,
                            'header'     => '',
                            'order'      => 0,
                            'origin'     => self::GRID_COLUMN_ORIGIN_ATTRIBUTE,
                            'is_visible' => 1,
                            'is_system'  => 0,
                            'missing'    => 0,
                        ),
                        $this->_extractColumnValues($column, true, true, false, $allowEditable)
                    );
                    $this->_originIds[self::GRID_COLUMN_ORIGIN_ATTRIBUTE][] = $newColumnId;
                }
            }
        }
        
        // Recompute max order, as it may have now changed
        $this->_recomputeMaxOrder();
        
        return $this->save();
    }
    
    /**
    * Update grid default parameters and save
    * 
    * @param array $add New default parameters values
    * @param array $remove Keys of default parameters to remove
    * @return this
    */
    public function updateDefaultParameters($add, $remove=null)
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
            if (isset($add['filters'])) {
                $filters = $add['filters'];
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
                            } else {
                                $rendererType = null;
                            }
                            
                            $filters[$columnId] = array(
                                'value'  => $value,
                                'column' => array(
                                    'origin'        => $column['origin'],
                                    'renderer_type' => $rendererType,
                                    'index'         => $column['index'],
                                ),
                            );
                        } else {
                            unset($filters[$columnId]);
                        }
                    }
                    $this->setData('default_filters', serialize($filters));
                } else {
                    $this->setData('default_filters', null);
                }
            }
        }
        if (is_array($remove)) {
            // Remove wanted default parameters
            $params = array('page', 'limit', 'sort', 'dir', 'filters');
            foreach ($params as $param) {
                if (isset($remove[$param]) && (bool)$remove[$param]) {
                    $this->setData('default_'.$param, null);
                }
            }
        }
        return $this->save();
    }
    
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
    * Return whether given origin code is grid one
    * 
    * @param string $origin Origin code
    * @return bool
    */
    static public function isGridColumnOrigin($origin)
    {
        return ($origin == self::GRID_COLUMN_ORIGIN_GRID);
    }
}