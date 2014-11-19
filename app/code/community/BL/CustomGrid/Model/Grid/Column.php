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

class BL_CustomGrid_Model_Grid_Column extends BL_CustomGrid_Object
{
    /**
     * Possible column alignments values
     */
    const ALIGNMENT_LEFT   = 'left';
    const ALIGNMENT_CENTER = 'center';
    const ALIGNMENT_RIGHT  = 'right';
    
    /**
     * Alignments options hash
     * 
     * @var string[]
     */
    static protected $_alignmentsHash = null;
    
    /**
     * Column origins
     */
    const ORIGIN_GRID       = 'grid';
    const ORIGIN_COLLECTION = 'collection';
    const ORIGIN_ATTRIBUTE  = 'attribute';
    const ORIGIN_CUSTOM     = 'custom';
    
    /**
     * Origins options hash
     * 
     * @var string[]
     */
    static protected $_originsHash = null;
    
    /**
     * Return the (internal) ID of this column
     *
     * @return int
     */
    public function getId()
    {
        return $this->_getData('column_id');
    }
    
    /**
     * Return the grid model corresponding to this column
     *
     * @param bool $graceful Whether to throw an exception if the grid model is invalid, otherwise return null
     * @return BL_CustomGrid_Model_Grid|null
     */
    public function getGridModel($graceful = false)
    {
        if (($gridModel = $this->_getData('grid_model')) instanceof BL_CustomGrid_Model_Grid) {
            return $gridModel;
        } elseif (!$graceful) {
            Mage::throwException(Mage::helper('customgrid')->__('Invalid grid model'));
        }
        return null;
    }
    
    /**
     * Return whether this is a grid column
     *
     * @return bool
     */
    public function isGrid()
    {
        return ($this->getOrigin() == self::ORIGIN_GRID);
    }
    
    /**
     * Return whether this is a collection column
     *
     * @return bool
     */
    public function isCollection()
    {
        return ($this->getOrigin() == self::ORIGIN_COLLECTION);
    }
    
    /**
     * Return whether this is an attribute column
     *
     * @return bool
     */
    public function isAttribute()
    {
        return ($this->getOrigin() == self::ORIGIN_ATTRIBUTE);
    }
    
    /**
     * Return whether this is a custom column
     *
     * @return bool
     */
    public function isCustom()
    {
        return ($this->getOrigin() == self::ORIGIN_CUSTOM);
    }
    
    /**
     * Return whether this column is editable
     *
     * @return bool
     */
    public function isEditable()
    {
        return ($this->_getData('edit_config') instanceof BL_CustomGrid_Object);
    }
    
    /**
     * Return the corresponding custom column model
     *
     * @param bool $graceful Whether to throw an exception if the custom column model is invalid, otherwise return null
     * @return BL_CustomGrid_Model_Custom_Column_Abstract|null
     */
    public function getCustomColumnModel($graceful = true)
    {
        $customColumn = null;
        
        if ($this->isCustom()) {
            $customColumn = $this->getData('custom_column_model');
        }
        if (!$customColumn instanceof BL_CustomGrid_Model_Custom_Column_Abstract) {
            if (!$graceful) {
                Mage::throwException(Mage::helper('customgrid')->__('Invalid custom column model'));
            }
            $customColumn = null;
        }
        
        return $customColumn;
    }
    
    /**
     * Return whether this column can be assigned a store ID
     * 
     * @return bool
     */
    public function getAllowStore()
    {
        return ($this->isCollection() || ($this->isCustom() && $this->getCustomColumnModel(false)->getAllowStore()));
    }
    
    /**
     * Return whether this column can be assigned a renderer
     * 
     * @return bool
     */
    public function getAllowRenderer()
    {
        return $this->isCollection()
            || $this->isAttribute()
            || ($this->isCustom() && $this->getCustomColumnModel(false)->getAllowRenderers());
    }
    
    /**
     * Compare the order from this column to the order from the given column
     *
     * @param BL_CustomGrid_Model_Grid_Column $column Column against which to compare the order
     * @return int
     */
    public function compareOrderTo(BL_CustomGrid_Model_Grid_Column $column)
    {
        return $this->compareIntDataTo('order', $column);
    }
    
    /**
     * Parse the given user values and return the corresponding proper column values,
     * basing on the given behaviour flags
     *
     * @param array $userValues User values
     * @param bool $allowStore Whether store ID value is allowed
     * @param bool $allowRenderer Whether renderer values are allowed
     * @param bool $requireRendererType Whether renderer type is required
     * @param bool $allowEditable Whether editability value is allowed
     * @param bool $allowCustomizationParams Whether customization parameters are allowed
     * @return array
     */
    protected function _parseGridModelColumnUserValues(
        array $userValues,
        $allowStore = false,
        $allowRenderer = false,
        $requireRendererType = true,
        $allowEditable = false,
        $allowCustomizationParams = false
    ) {
        $userValues = new BL_CustomGrid_Object($userValues);
        
        $values = array();
        $values['is_visible'] = (bool) $userValues->getData('is_visible');
        $values['is_only_filterable'] = ($values['is_visible'] && $userValues->getData('filter_only'));
        $values['align']  = Mage::getSingleton('customgrid/grid')->getValidAlignment($userValues->getData('align'));
        $values['header'] = $userValues->getData('header');
        $values['order']  = (int) $userValues->getData('order');
        $values['width']  = $userValues->getData('width');
        $values['is_edit_allowed'] = ($allowEditable && $userValues->getData('editable'));
        
        if ($allowStore && (($storeId = $userValues->getData('store_id')) !== '')) {
            $values['store_id'] = $storeId;
        } else {
            $values['store_id'] = null;
        }
        
        $rendererType = null;
        
        if ($allowRenderer
            && (!$requireRendererType || ($rendererType = $userValues->getData('renderer_type')))) {
             $values['renderer_type'] = $rendererType;
             $values['renderer_params'] = $userValues->getNotEmptyData('renderer_params');
        } else {
            $values['renderer_type'] = null;
            $values['renderer_params'] = null;
        }
        
        if ($allowCustomizationParams) {
            $values['customization_params'] = $userValues->getNotEmptyData('customization_params');
        } else {
            $values['customization_params'] = null;
        }
        
        return $values;
    }
    
    /**
     * Return the proper values with wich to update the given grid column, from the given user values
     * 
     * @param array $userValues User values
     * @param BL_CustomGrid_Model_Grid_Column $column Updated grid column
     * @param bool $allowEditable Whether the user has the permission to choose which columns should be editable
     * @param string[] $availableAttributeCodes Available attributes codes
     * @return array
     */
    protected function _getGridModelColumnNewValues(
        array $userValues,
        BL_CustomGrid_Model_Grid_Column $column,
        $allowEditable,
        array $availableAttributeCodes
    ) {
        $columnValues = $this->_parseGridModelColumnUserValues(
            $userValues,
            $column->getAllowStore(),
            $column->getAllowRenderer(),
            ($column->isCollection() || $column->isCustom()),
            ($allowEditable && $column->isEditable()),
            $column->isCustom()
        );
        
        if ($column->isAttribute()
            && isset($userValues['index'])
            && in_array($userValues['index'], $availableAttributeCodes, true)) {
            $columnValues['index'] = $userValues['index'];
        }
        
        return $columnValues;
    }
    
    /**
     * Update the existing columns for the given grid model, with the given user values
     *
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param array $columns New columns values
     * @param bool $allowEditable Whether the user has the permission to choose which columns should be editable
     * @param string[] $availableAttributeCodes Available attributes codes
     * @return this
     */
    protected function _updateGridModelExistingColumns(
        BL_CustomGrid_Model_Grid $gridModel,
        array &$columns,
        $allowEditable,
        array $availableAttributeCodes
    ) {
        foreach ($gridModel->getColumns(true, true) as $columnBlockId => $column) {
            $columnId = $column->getId();
            
            if (isset($columns[$columnId])) {
                $gridModel->updateColumn(
                    $columnBlockId,
                    $this->_getGridModelColumnNewValues(
                        $columns[$columnId],
                        $column,
                        $allowEditable,
                        $availableAttributeCodes
                    )
                );
                
                // In the end, there should only remain in $columns the new attribute columns (without a valid ID yet)
                unset($columns[$columnId]);
            } else {
                $gridModel->removeColumn($columnBlockId);
            }
        }
        return $this;
    }
    
    /**
     * Update the attribute columns for the given grid model, with the given user values
     *
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param array $columns New columns values
     * @param bool $allowEditable Whether the user has the permission to choose which columns should be editable
     * @param string[] $availableAttributeCodes Available attributes codes
     * @return this
     */
    protected function _updateGridModelAttributeColumns(
        BL_CustomGrid_Model_Grid $gridModel,
        array $columns,
        $allowEditable,
        array $availableAttributeCodes
    ) {
        foreach ($columns as $columnId => $columnValues) {
            if (($columnId < 0) // Concerned columns IDs should be < 0, so assume other IDs are obsolete ones
                && isset($columnValues['index'])
                && in_array($columnValues['index'], $availableAttributeCodes, true)) {
                $gridModel->addColumn(
                    array_merge(
                        array(
                            'grid_id'              => $gridModel->getId(),
                            'block_id'             => $gridModel->getNextAttributeColumnBlockId(),
                            'index'                => $columnValues['index'],
                            'width'                => '',
                            'align'                => self::ALIGNMENT_LEFT,
                            'header'               => '',
                            'order'                => $gridModel->getNextColumnOrder(),
                            'origin'               => self::ORIGIN_ATTRIBUTE,
                            'is_visible'           => true,
                            'is_only_filterable'   => false,
                            'is_system'            => false,
                            'is_missing'           => false,
                            'store_id'             => null,
                            'renderer_type'        => null,
                            'renderer_params'      => null,
                            'is_edit_allowed'      => true,
                            'customization_params' => null,
                        ),
                        $this->_parseGridModelColumnUserValues($columnValues, true, true, false, $allowEditable)
                    )
                );
            }
        }
        return $this;
    }
    
    /**
     * Update the columns for the given grid model, with the given user values
     *
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param array $columns New columns values
     * @return BL_CustomGrid_Model_Grid
     */
    public function updateGridModelColumns(BL_CustomGrid_Model_Grid $gridModel, array $columns)
    {
        if (!$gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_CUSTOMIZE_COLUMNS)) {
            $gridModel->throwPermissionException();
        }
        
        $this->setGridModel($gridModel);
        $gridModel->getColumnBlockIdsByOrigin();
        $allowEditable = $gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_CHOOSE_EDITABLE_COLUMNS);
        $availableAttributeCodes = $gridModel->getAvailableAttributesCodes();
        
        $this->_updateGridModelExistingColumns($gridModel, $columns, $allowEditable, $availableAttributeCodes);
        
        if ($gridModel->canHaveAttributeColumns()) {
            $this->_updateGridModelAttributeColumns($gridModel, $columns, $allowEditable, $availableAttributeCodes);
        }
        
        return $gridModel->setDataChanges(true);
    }
    
    /**
     * Update the available custom columns for the given grid model, with the given custom columns codes
     *
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param string[] $columnsCodes New custom columns codes
     * @return BL_CustomGrid_Model_Grid
     */
    public function updateGridModelCustomColumns(BL_CustomGrid_Model_Grid $gridModel, array $columnsCodes)
    {
        if (!$gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_CUSTOMIZE_COLUMNS)) {
            $gridModel->throwPermissionException();
        }
        if ($typeModel = $gridModel->getTypeModel()) {
            $typeCode = $typeModel->getCode();
        } else {
            return $gridModel;
        }
        
        $helper  = $gridModel->getHelper();
        $columns = $gridModel->getColumns();
        $originalBlockIds = $gridModel->getColumnBlockIdsByOrigin(self::ORIGIN_CUSTOM);
        
        $availableCustomColumns = $gridModel->getAvailableCustomColumns();
        $availableCodes = array_keys($availableCustomColumns);
        
        $appliedCodes = $columnsCodes;
        $currentCodes = array();
        
        foreach ($originalBlockIds as $columnBlockId) {
            if (isset($columns[$columnBlockId])) {
                $parts = explode('/', $columns[$columnBlockId]->getIndex());
                
                if (($typeCode == $parts[0])
                    && in_array($parts[1], $appliedCodes)
                    && in_array($parts[1], $availableCodes)) {
                    $currentCodes[] = $parts[1];
                } else {
                    $gridModel->removeColumn($columnBlockId);
                    unset($columns[$columnBlockId]);
                }
            }
        }
        
        $newCodes = array_intersect($availableCodes, array_diff($appliedCodes, $currentCodes));
        $customColumnsGroups = $gridModel->getCustomColumnsGroups();
        $addGroupsToHeaders  = $gridModel->getConfigHelper()->getAddGroupToCustomColumnsDefaultHeader();
        
        foreach ($newCodes as $customColumnCode) {
            $columnBlockId = $gridModel->getNextCustomColumnBlockId();
            $columnModel   = $availableCustomColumns[$customColumnCode];
            $groupId = $columnModel->getGroupId();
            
            if (isset($columnsGroups[$groupId]) && $addGroupsToHeaders) {
                $header = $helper->__('%s (%s)', $columnModel->getName(), $customColumnsGroups[$groupId]);
            } else {
                $header = $columnModel->getName();
            }
            
            $columnValues = array(
                'grid_id'              => $gridModel->getId(),
                'block_id'             => $columnBlockId,
                'index'                => $typeCode . '/' . $customColumnCode,
                'width'                => '',
                'align'                => self::ALIGNMENT_LEFT,
                'header'               => $header,
                'order'                => $gridModel->getNextColumnOrder(),
                'origin'               => self::ORIGIN_CUSTOM,
                'is_visible'           => true,
                'is_only_filterable'   => false,
                'is_system'            => false,
                'is_missing'           => false,
                'store_id'             => null,
                'renderer_type'        => null,
                'renderer_params'      => null,
                'is_edit_allowed'      => false,
                'customization_params' => null,
            );
            
            $gridModel->addColumn($columnValues);
        }
        
        return $gridModel->setDataChanges(true);
    }
    
    /**
     * Return alignments options hash
     *
     * @return string[]
     */
    public function getAlignments()
    {
        if (is_null(self::$_alignmentsHash)) {
            $helper = Mage::helper('customgrid');
            
            self::$_alignmentsHash = array(
                self::ALIGNMENT_LEFT   => $helper->__('Left'),
                self::ALIGNMENT_CENTER => $helper->__('Middle'),
                self::ALIGNMENT_RIGHT  => $helper->__('Right'),
            );
        }
        return self::$_alignmentsHash;
    }
    
    /**
     * Return origins options hash
     *
     * @return string[]
     */
    public function getOrigins()
    {
        if (is_null(self::$_originsHash)) {
            $helper = Mage::helper('customgrid');
            
            self::$_originsHash = array(
                self::ORIGIN_GRID       => $helper->__('Grid'),
                self::ORIGIN_COLLECTION => $helper->__('Collection'),
                self::ORIGIN_ATTRIBUTE  => $helper->__('Attribute'),
                self::ORIGIN_CUSTOM     => $helper->__('Custom'),
            );
        }
        return self::$_originsHash;
    }
}
