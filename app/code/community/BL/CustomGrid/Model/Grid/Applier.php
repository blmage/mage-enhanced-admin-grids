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

class BL_CustomGrid_Model_Grid_Applier extends BL_CustomGrid_Model_Grid_Worker
{
    /**
     * Parameter name to use to hold grid token value (used for filters verification)
     */
    const GRID_TOKEN_PARAM_NAME  = '_blcg_token_';
    
    /**
     * Return the store model usable for the given column
     * 
     * @param BL_CustomGrid_Model_Grid_Column $column Grid column
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return Mage_Core_Model_Store
     */
    protected function _getColumnStoreModel(
        BL_CustomGrid_Model_Grid_Column $column,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock
    ) {
        return is_null($column->getStoreId())
            ? $gridBlock->blcg_getStore()
            : Mage::app()->getStore($column->getStoreId());
    }
    
    /**
     * Return the renderer type and parameters usable to render the given collection column
     * 
     * @param BL_CustomGrid_Model_Grid_Column $column Collection column
     * @param array $lockedValues Column locked values
     * @return array
     */
    protected function _getCollectionColumnRendererValues(BL_CustomGrid_Model_Grid_Column $column, array $lockedValues)
    {
        if (isset($lockedValues['renderer'])) {
            $rendererType = $lockedValues['renderer'];
            $rendererParams = ($rendererType == $column->getRendererType())
                ? $column->getRendererParams()
                : array();
        } else {
            $rendererType = $column->getRendererType();
            $rendererParams = $column->getRendererParams();
        }
        return array($rendererType, $rendererParams);
    }
    
    /**
     * Return grid column block values for given collection column
     *
     * @param BL_CustomGrid_Model_Grid_Column $column Collection column
     * @param string $rendererType Renderer type code
     * @param string $rendererParams Encoded renderer parameters
     * @param Mage_Core_Model_Store $store Current store
     * @return array
     */
    protected function _getCollectionColumnBlockValues(
        BL_CustomGrid_Model_Grid_Column $column,
        $rendererType,
        $rendererParams,
        Mage_Core_Model_Store $store
    ) {
        $config = Mage::getSingleton('customgrid/column_renderer_config_collection');
        
        if ($renderer = $config->getRendererInstanceByCode($rendererType)) {
            if (is_array($decodedParams = $config->decodeParameters($rendererParams))) {
                $renderer->setValues($decodedParams);
            } else {
                $renderer->setValues(array());
            }
            
            return $renderer->getColumnBlockValues($column->getIndex(), $store, $this->getGridModel());
        }
        
        return array();
    }
    
    /**
     * Return grid column block values for given attribute column
     * 
     * @param Mage_Eav_Model_Entity_Attribute $attribute Corresponding attribute model
     * @param string $rendererParams Encoded renderer parameters
     * @param Mage_Core_Model_Store $store Current store
     * @return array
     */
    protected function _getAttributeColumnBlockValues(
        Mage_Eav_Model_Entity_Attribute $attribute,
        $rendererParams,
        Mage_Core_Model_Store $store
    ) {
        $gridModel = $this->getGridModel();
        $config = Mage::getSingleton('customgrid/column_renderer_config_attribute');
        $renderers = $config->getRenderersInstances();
        $values = array();
        
        foreach ($renderers as $renderer) {
            if ($renderer->isAppliableToAttribute($attribute, $gridModel)) {
                if (is_array($params = $config->decodeParameters($rendererParams))) {
                    $renderer->setValues($params);
                } else {
                    $renderer->setValues(array());
                }
                
                $values = $renderer->getColumnBlockValues($attribute, $store, $gridModel);
                $values = (is_array($values) ? $values : array());
                break;
            }
        }
        
        return $values;
    }
    
    /**
     * Return grid column block values for given custom column
     *
     * @param string $columnBlockId Column block ID
     * @param string $columnIndex Column index
     * @param BL_CustomGrid_Model_Custom_Column_Abstract $customColumn Custom column model
     * @param string $rendererType Renderer type code
     * @param string $rendererParams Encoded renderer parameters
     * @param string $customizationParams Encoded customization parameters
     * @param Mage_Core_Model_Store $store Current store
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return array
     */
    protected function _getCustomColumnBlockValues(
        $columnBlockId,
        $columnIndex,
        BL_CustomGrid_Model_Custom_Column_Abstract $customColumn,
        $rendererType,
        $rendererParams,
        $customizationParams,
        Mage_Core_Model_Store $store,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock
    ) {
        if ($customColumn->getAllowRenderers()) {
            if ($customColumn->getLockedRenderer()
                && ($customColumn->getLockedRenderer() != $rendererType)) {
                $rendererType = $customColumn->getLockedRenderer();
                $rendererParams = null;
            }
            
            $config = Mage::getSingleton('customgrid/column_renderer_config_collection');
            
            if ($renderer = $config->getRendererInstanceByCode($rendererType)) {
                if (is_array($params = $config->decodeParameters($rendererParams))) {
                    $renderer->setValues($params);
                } else {
                    $renderer->setValues(array());
                }
            }
        } else {
            $renderer = null;
        }
        
        if (!empty($customizationParams)) {
            $customizationParams = Mage::getSingleton('customgrid/grid_type_config')
                ->decodeParameters($customizationParams);
        } else {
            $customizationParams = array();
        }
        
        return $customColumn->getApplier()
            ->applyCustomColumnToGridBlock(
                $gridBlock,
                $this->getGridModel(),
                $columnBlockId,
                $columnIndex,
                (is_array($customizationParams) ? $customizationParams : array()),
                $store,
                $renderer
            );
    }
    
    /**
     * Prepare the given grid column, assuming it is part of the original grid block columns list
     * 
     * @param BL_CustomGrid_Model_Grid_Column $column Grid column
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return bool
     */
    protected function _prepareOriginalGridBlockColumn(
        BL_CustomGrid_Model_Grid_Column $column,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock
    ) {
        $gridModel = $this->getGridModel();
        
        if ($gridColumn = $gridBlock->getColumn($column->getBlockId())) {
            if (!$gridModel->getIgnoreCustomWidths()) {
                $gridColumn->setWidth($column->getWidth());
            }
            if (!$gridModel->getIgnoreCustomAlignments()) {
                $gridColumn->setAlign($column->getAlign());
            }
            if (!$gridModel->getIgnoreCustomHeaders()) {
                $gridColumn->setHeader($column->getHeader());
            }
        }
        
        return $this;
    }
    
    /**
     * Prepare the given grid column, assuming it is not part of the original grid block columns list
     * 
     * @param BL_CustomGrid_Model_Grid_Column $column Grid column
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param array $attributes Available attributes
     * @param array $addedAttributes Attributes that were already added (values format: "[code]_[store_id]")
     * @return bool
     */
    protected function _prepareExternalGridBlockColumn(
        BL_CustomGrid_Model_Grid_Column $column,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        array $attributes,
        array &$addedAttributes
    ) {
        $gridModel = $this->getGridModel();
        $lockedValues = $gridModel->getColumnLockedValues($column->getBlockId());
        
        $data = array(
            'header' => $column->getHeader(),
            'align'  => $column->getAlign(),
            'width'  => $column->getWidth(),
            'index'  => $column->getIndex(),
        );
        
        $data = array_merge($data, array_intersect_key($lockedValues, $data));
        
        if ($column->isCollection() && (isset($lockedValues['renderer']) || $column->getRendererType())) {
            list($rendererType, $rendererParams) = $this->_getCollectionColumnRendererValues($column, $lockedValues);
            
            $data = array_merge(
                $data, 
                $this->_getCollectionColumnBlockValues(
                    $column,
                    $rendererType,
                    $rendererParams,
                    $gridBlock->blcg_getStore()
                )
            );
        } elseif ($column->isAttribute() && isset($attributes[$column->getIndex()])) {
            $store = $this->_getColumnStoreModel($column, $gridBlock);
            $attributeKey = $column->getIndex() . '_' . $store->getId();
            
            if (!isset($addedAttributes[$attributeKey])) {
                $data['index'] = BL_CustomGrid_Model_Grid::ATTRIBUTE_COLUMN_GRID_ALIAS
                    . str_replace(BL_CustomGrid_Model_Grid::ATTRIBUTE_COLUMN_ID_PREFIX, '', $column->getBlockId());
                
                $gridBlock->blcg_addAdditionalAttribute(
                    array(
                        'alias'     => $data['index'],
                        'attribute' => $attributes[$column->getIndex()],
                        'bind'      => 'entity_id',
                        'filter'    => null,
                        'join_type' => 'left',
                        'store_id'  => $store->getId(),
                    )
                );
                
                $addedAttributes[$attributeKey] = $data['index'];
            } else {
                $data['index'] = $addedAttributes[$attributeKey];
            }
            
            $data = array_merge(
                $data,
                $this->_getAttributeColumnBlockValues(
                    $attributes[$column->getIndex()],
                    $column->getRendererParams(),
                    $store
                )
            );
        } elseif ($column->isCustom() && ($customColumn = $column->getCustomColumnModel())) {
            $store = $this->_getColumnStoreModel($column, $gridBlock);
            $data['index'] = BL_CustomGrid_Model_Grid::CUSTOM_COLUMN_GRID_ALIAS
                . str_replace(BL_CustomGrid_Model_Grid::CUSTOM_COLUMN_ID_PREFIX, '', $column->getBlockId());
            
            $customValues = $this->_getCustomColumnBlockValues(
                $column->getBlockId(),
                $data['index'],
                $customColumn,
                $column->getRendererType(),
                $column->getRendererParams(),
                $column->getCustomizationParams(),
                $store,
                $gridBlock
            );
            
            $data= (is_array($customValues) ? array_merge($data, $customValues) : null);
        } else {
            $data = null;
        }
        
        if (!empty($data)) {
            if (isset($lockedValues['config_values']) && is_array($lockedValues['config_values'])) {
                $data = array_merge($data, $lockedValues['config_values']);
            }
            $gridBlock->addColumn($column->getBlockId(), $data);
        }
        
        return $this;
    }
    
    /**
     * Arrange the given grid block's columns according to the given sorted column block IDs
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param array $sortedBlockIds Sorted column block IDs
     * @return this
     */
    protected function _arrangeGridBlockColumns(Mage_Adminhtml_Block_Widget_Grid $gridBlock, array $sortedBlockIds)
    {
        $gridBlock->blcg_resetColumnsOrder();
        $previousBlockId = null;
        
        foreach ($sortedBlockIds as $columnBlockId) {
            if (!is_null($previousBlockId)) {
                $gridBlock->addColumnsOrder($columnBlockId, $previousBlockId);
            }
            $previousBlockId = $columnBlockId;
        }
        
        $gridBlock->sortColumnsByOrder();
        return $this;
    }
    
    /**
     * Apply the columns customization from the current grid model to the given grid block
     *
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param bool $applyFromCollection Whether collection columns should be added to the grid block
     * @return this
     */
    public function applyGridModelColumnsToGridBlock(Mage_Adminhtml_Block_Widget_Grid $gridBlock, $applyFromCollection)
    {
        $gridModel = $this->getGridModel();
        $columns = $gridModel->getColumns(false, true);
        uasort($columns, array($gridModel, 'sortColumns'));
        $sortedBlockIds = array();
        $gridColumnIds  = array_keys($gridBlock->getColumns());
        
        $attributes = $gridModel->getAvailableAttributes();
        $addedAttributes = array();
        
        foreach ($columns as $column) {
            if (!in_array($column->getBlockId(), $gridColumnIds, true)) {
                if ($column->isVisible() && !$column->isMissing()
                    && (!$column->isCollection() || $applyFromCollection)) {
                    $this->_prepareExternalGridBlockColumn(
                        $column,
                        $gridBlock,
                        $attributes,
                        $addedAttributes
                    );
                }
            } elseif ($column->isVisible()) {
                $this->_prepareOriginalGridBlockColumn($column, $gridBlock);
            } else {
                $gridBlock->blcg_removeColumn($column->getBlockId());
            }
            if ($columnBlock = $gridBlock->getColumn($column->getBlockId())) {
                if ($column->isOnlyFilterable()) {
                    $columnBlock->setBlcgFilterOnly(true);
                    
                    if ($gridBlock->blcg_isExport()) {
                        // Columns with is_system flag set won't be exported, so forcing it will save us two overloads
                        $columnBlock->setIsSystem(true);
                    }
                } else {
                    $sortedBlockIds[] = $column->getBlockId();
                }
            }
        }
        
        $this->_arrangeGridBlockColumns($gridBlock, $sortedBlockIds);
        return $this;
    }
    
    /**
     * Encode filters array
     *
     * @param array $filters Filters values
     * @return string
     */
    public function encodeGridFiltersArray(array $filters)
    {
        return base64_encode(http_build_query($filters));
    }
    
    /**
     * Decode filters string
     *
     * @param string $filters Encoded filters string
     * @return array
     */
    public function decodeGridFiltersString($filters)
    {
        return (is_string($filters) ? Mage::helper('adminhtml')->prepareFilterString($filters) : $filters);
    }
    
    /**
     * Compare grid filter values
     *
     * @param mixed $valueA One filter value
     * @param mixed $valueB Another filter value
     * @return bool Whether given values are equal
     */
    public function compareGridFilterValues($valueA, $valueB)
    {
        if (is_array($valueA) && is_array($valueB)) {
            ksort($valueA);
            ksort($valueB);
            $valueA = $this->encodeGridFiltersArray($valueA);
            $valueB = $this->encodeGridFiltersArray($valueB);
            return ($valueA == $valueB);
        }
        return ($valueA === $valueB);
    }
    
    /**
     * Return if the column on which the given filter is applied has significantly changed since the last request
     * during which the filter had been used, meaning the filter should be unvalidated
     * 
     * @param array $sessionFilter Session filter values
     * @param BL_CustomGrid_Model_Grid_Column $column Corresponding column
     * @param array $attributesRenderers Available attributes renderers
     * @return bool
     */
    protected function _checkGridBlockFilterColumnChanges(
        array $sessionFilter,
        BL_CustomGrid_Model_Grid_Column $column,
        array $attributesRenderers
    ) {
        $columnIndex = $column->getIndex();
        $hasColumnChanged = false;
        
        if ($sessionFilter['origin'] != $column->getOrigin()) {
            $hasColumnChanged = true;
        } elseif ($column->isCollection()) {
            $hasColumnChanged = ($sessionFilter['renderer_type'] != $column->getRendererType());
        } elseif ($column->isAttribute()) {
            $previousIndex = $sessionFilter['index'];
            
            if (isset($attributesRenderers[$previousIndex])) {
                $previousRenderer = $attributesRenderers[$previousIndex];
                $columnRenderer   = $attributesRenderers[$columnIndex];
                $hasColumnChanged = ($previousRenderer != $columnRenderer);
            } else {
                $hasColumnChanged = true;
            }
        } elseif ($column->isCustom()) {
            $typeConfig = Mage::getSingleton('customgrid/grid_type_config');
            $previousIndex = $sessionFilter['index'];
            
            $rendererTypes = array(
                'previous' => $sessionFilter['renderer_type'],
                'current'  => $column->getRendererType(),
            );
            $customizationParams = array(
                'previous' => $typeConfig->decodeParameters($sessionFilter['customization_params'], true),
                'current'  => $typeConfig->decodeParameters($column->getCustomizationParams(), true),
            );
            
            if (($previousIndex != $columnIndex)
                || (!$customColumn = $column->getCustomColumnModel())) {
                $hasColumnChanged = true;
            } else {
                $hasColumnChanged = $customColumn->shouldInvalidateFilters(
                    $this->getGridModel(),
                    $column,
                    $customizationParams,
                    $rendererTypes
                );
            }
        }
        
        return $hasColumnChanged;
    }
    
    /**
     * Sanitize the given filters, by removing any filter that may be unsafe if applied
     * Return the list of column block IDs with valid filters, and the list of column block IDs whose filter
     * has been removed
     * 
     * @param array $filters Applied filters
     * @param array $sessionAppliedFilters Previously applied filters
     * @param array $sessionRemovedFilters Previously removed filters
     * @param bool $isGridAction Whether the current request corresponds to a grid action (search, export, ...)
     * @return array
     */
    protected function _sanitizeGridBlockFilters(
        array &$filters,
        array &$sessionAppliedFilters,
        array &$sessionRemovedFilters,
        $isGridAction
    ) {
        $gridModel = $this->getGridModel();
        $columns = $gridModel->getColumns(false, true);
        $attributesRenderers = $gridModel->getAvailableAttributesRendererTypes();
        
        $foundFilterBlockIds = array();
        $removedFilterBlockIds = array();
        
        foreach ($filters as $columnBlockId => $filterData) {
            if (isset($columns[$columnBlockId])) {
                $column = $columns[$columnBlockId];
                
                if (isset($sessionAppliedFilters[$columnBlockId])) {
                    $hasColumnChanged = $this->_checkGridBlockFilterColumnChanges(
                        $sessionAppliedFilters[$columnBlockId],
                        $column,
                        $attributesRenderers
                    );
                    
                    if ($hasColumnChanged) {
                        unset($filters[$columnBlockId]);
                        unset($sessionAppliedFilters[$columnBlockId]);
                        $sessionRemovedFilters[$columnBlockId] = $filterData;
                        $removedFilterBlockIds[] = $columnBlockId;
                    }
                } elseif (isset($sessionRemovedFilters[$columnBlockId]) && !$isGridAction) {
                    if ($this->compareGridFilterValues($sessionRemovedFilters[$columnBlockId], $filterData)) {
                        // The same filter was invalidated before, remove it again
                        unset($filters[$columnBlockId]);
                    }
                } else {
                    $sessionAppliedFilters[$columnBlockId] = array(
                        'index'  => $column->getIndex(),
                        'origin' => $column->getOrigin(),
                        'renderer_type' => $column->getRendererType(),
                        'customization_params' => $column->getCustomizationParams(),
                    );
                }
                
                $foundFilterBlockIds[] = $columnBlockId;
            } else {
                // Unexisting column : unneeded filter
                unset($filters[$columnBlockId]);
            }
        }
        
        return array($foundFilterBlockIds, $removedFilterBlockIds);
    }
    
    /**
     * Verify validities of filters applied to given grid block,
     * and return safely appliable filters.
     * Mostly used for collection and custom columns, which may have their renderer changed at any time
     * (and the new renderers may crash when given unexpected kind of filter values)
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param array $filters Applied filters
     * @return array
     */
    public function verifyGridBlockFilters(Mage_Adminhtml_Block_Widget_Grid $gridBlock, array $filters)
    {
        $gridModel = $this->getGridModel();
        
        // Get previous filtering informations from session
        $session = Mage::getSingleton('adminhtml/session');
        $tokenSessionKey   = $gridModel->getFiltersTokenSessionKey();
        $appliedSessionKey = $gridModel->getAppliedFiltersSessionKey();
        $removedSessionKey = $gridModel->getRemovedFiltersSessionKey();
        
        if (!is_array($sessionAppliedFilters = $session->getData($appliedSessionKey))) {
            $sessionAppliedFilters = array();
        }
        if (!is_array($sessionRemovedFilters = $session->getData($removedSessionKey))) {
            $sessionRemovedFilters = array();
        }
        
        /*
        Verify grid tokens, if request one does not correspond to session one,
        then it is almost sure that we currently come from anywhere but from an acual grid action
        (such as search, sort, export, pagination, ...)
        May be too restrictive, but at the moment, rather be too restrictive than not enough
        */
        if ($gridBlock->getRequest()->has(self::GRID_TOKEN_PARAM_NAME)
            && $session->hasData($tokenSessionKey)) {
            $requestValue = $gridBlock->getRequest()->getParam(self::GRID_TOKEN_PARAM_NAME, null);
            $sessionValue = $session->getData($tokenSessionKey);
            $isGridAction = ($requestValue == $sessionValue);
        } else {
            $isGridAction = false;
        }  
        
        list($foundFilterBlockIds, $removedFilterBlockIds) = $this->_sanitizeGridBlockFilters(
            $filters,
            $sessionAppliedFilters,
            $sessionRemovedFilters,
            $isGridAction
        );
        
        /**
         * Note : adding new parameters to the request object will make them be added to, eg,
         * URLs retrieved later with the use of the current values
         * (eg. with Mage::getUrl('module/controller/action', array('_current' => true)))
         */
        
        /*
        Add our token to current request and session
        Use ":" in hash to force Varien_Db_Adapter_Pdo_Mysql::query() using a bind param instead of full request path,
        (as it uses this condition : strpos($sql, ':') !== false),
        when querying core_url_rewrite table, else the query could be too long, 
        making Zend_Db_Statement::_stripQuoted() sometimes crash on one of its call to preg_replace()
        */
        $tokenValue = Mage::helper('core')->uniqHash('blcg:');
        $gridBlock->getRequest()->setParam(self::GRID_TOKEN_PARAM_NAME, $tokenValue);
        $session->setData($tokenSessionKey, $tokenValue);
        
        // Remove obsolete filters and save up-to-date filters array to session
        $obsoleteFilterBlockIds = array_diff(array_keys($sessionAppliedFilters), $foundFilterBlockIds);
        
        foreach ($obsoleteFilterBlockIds as $columnBlockId) {
            unset($sessionAppliedFilters[$columnBlockId]);
        }
        
        $session->setData($appliedSessionKey, $sessionAppliedFilters);
        
        if ($isGridAction) {
            /*
            Apply newly removed filters only when a grid action is done
            The only remaining potential source of "maybe wrong" filters could come from  the use of an old URL with
            obsolete filter(s) in it (eg from browser history), but there is no way at the moment to detect them
            (at least not any simple one with only few impacts)
            */
            $session->setData($removedSessionKey, array_intersect_key($sessionRemovedFilters, $removedFilterBlockIds));
        } else {
            $session->setData($removedSessionKey, $sessionRemovedFilters);
        }
        
        $filterParam = $this->encodeGridFiltersArray($filters);
        
        if ($gridBlock->blcg_getSaveParametersInSession()) {
            $session->setData($gridBlock->blcg_getSessionParamKey($gridBlock->getVarNameFilter()), $filterParam);
        }
        if ($gridBlock->getRequest()->has($gridBlock->getVarNameFilter())) {
            $gridBlock->getRequest()->setParam($gridBlock->getVarNameFilter(), $filterParam);
        }
        
        $gridBlock->blcg_setFilterParam($filterParam);
        return $filters;
    }
    
    /**
     * Return the value of "default_filter" from the current grid model, stripped of all the obsolete or invalid values
     * 
     * @return null|array
     */
    public function getAppliableDefaultFilter()
    {
        $gridModel = $this->getGridModel();
        
        if (!$gridModel->hasData('appliable_default_filter')) {
            $appliableDefaultFilter = null;
            
            if (($filters = $gridModel->getData('default_filter')) && is_array($filters = @unserialize($filters))) {
                $columns = $gridModel->getColumns(false, true);
                $appliableDefaultFilter = array();
                $attributesRenderers = $gridModel->getAvailableAttributesRendererTypes();
                
                foreach ($filters as $columnBlockId => $filter) {
                    if (isset($columns[$columnBlockId])
                        && ($filter['column']['origin'] == $columns[$columnBlockId]->getOrigin())) {
                        $column = $columns[$columnBlockId];
                        $columnIndex = $column->getIndex();
                        
                        // Basically, those are the same verifications than the ones used in verifyGridBlockFilters()
                        $isValidFilter = true;
                        $previousRendererType = $filter['column']['renderer_type'];
                        $previousCustomizationParams = $filter['column']['customization_params'];
                        
                        if ($column->isCollection()) {
                            $isValidFilter = ($previousRendererType == $column->getRendererType());
                        } elseif ($column->isAttribute()) {
                            $previousIndex = $filter['column']['index'];
                            
                            if (isset($attributesRenderers[$previousIndex])) {
                                $previousRenderer = $attributesRenderers[$previousIndex];
                                $columnRenderer = $attributesRenderers[$columnIndex];
                                $isValidFilter  = ($previousRenderer == $columnRenderer);
                            } else {
                                $isValidFilter = false;
                            }
                        } elseif ($column->isCustom()) {
                            $previousIndex = $filter['column']['index'];
                            $typeConfig = Mage::getSingleton('customgrid/grid_type_config');
                            
                            $rendererTypes = array(
                                'previous' => $previousRendererType,
                                'current'  => $column->getRendererType(),
                            );
                            $customizationParams = array(
                                'previous' => $typeConfig->decodeParameters($previousCustomizationParams, true),
                                'current'  => $typeConfig->decodeParameters($column->getCustomizationParams(), true),
                            );
                            
                            if (($previousIndex != $columnIndex)
                                || (!$customColumn = $column->getCustomColumnModel())) {
                                $isValidFilter = false;
                            } else {
                                $isValidFilter = !$customColumn->shouldInvalidateFilters(
                                    $gridModel,
                                    $column,
                                    $customizationParams,
                                    $rendererTypes
                                );
                            }
                        }
                        
                        if ($isValidFilter) {
                            $appliableDefaultFilter[$columnBlockId] = $filter['value'];
                        }
                    }
                }
            }
            
            $gridModel->setData('appliable_default_filter', $appliableDefaultFilter);
        }
        
        return $gridModel->getData('appliable_default_filter');
    }
    
    /**
     * Return appliable default parameter value depending on the available values and the defined behaviour
     *
     * @param string $type Parameter type (eg "limit" or "filter")
     * @param mixed $blockValue Base value
     * @param mixed $customValue User-defined value
     * @param bool $fromCustomSetter Whether this function is called from a setter applying user-defined values
     * @param mixed $originalValue Current value (to be replaced)
     * @return mixed
     */
    public function getGridBlockDefaultParamValue(
        $type,
        $blockValue,
        $customValue = null,
        $fromCustomSetter = false,
        $originalValue = null
    ) {
        $value = $blockValue;
        $gridModel = $this->getGridModel();
        
        if (!$fromCustomSetter) {
            if ($type == BL_CustomGrid_Model_Grid::GRID_PARAM_FILTER) {
                $customValue = $gridModel->getAppliableDefaultFilter();
            } else {
                $customValue = $gridModel->getData('default_' . $type);
            }
        }
        
        if (!$behaviour = $gridModel->getData('default_' . $type . '_behaviour')) {
            $behaviour = Mage::helper('customgrid/config')->geDefaultParameterBehaviour($type);
        }
        if ($behaviour == BL_CustomGrid_Model_Grid::DEFAULT_PARAM_FORCE_CUSTOM) {
            if (!is_null($customValue)) {
                $value = $customValue;
            }
        } elseif ($behaviour == BL_CustomGrid_Model_Grid::DEFAULT_PARAM_FORCE_ORIGINAL) {
            if (is_null($blockValue) && $fromCustomSetter) {
                $value = $blockValue;
            }
        } elseif (($type == BL_CustomGrid_Model_Grid::GRID_PARAM_FILTER)
                  && (($behaviour == BL_CustomGrid_Model_Grid::DEFAULT_PARAM_MERGE_DEFAULT)
                      || ($behaviour == BL_CustomGrid_Model_Grid::DEFAULT_PARAM_MERGE_BASE_CUSTOM)
                      || ($behaviour == BL_CustomGrid_Model_Grid::DEFAULT_PARAM_MERGE_BASE_ORIGINAL))) {
            $blockFilters  = (is_array($blockValue)  ? $blockValue  : array());
            $customFilters = (is_array($customValue) ? $customValue : array());
            
            if ($behaviour == BL_CustomGrid_Model_Grid::DEFAULT_PARAM_MERGE_BASE_CUSTOM) {
                $value = array_merge($customFilters, $blockFilters);
            } elseif ($behaviour == BL_CustomGrid_Model_Grid::DEFAULT_PARAM_MERGE_BASE_ORIGINAL) {
                $value = array_merge($blockFilters, $customFilters);
            } elseif ($fromCustomSetter) {
                $value = array_merge($blockFilters, $customFilters);
            } else {
                $value = array_merge($customFilters, $blockFilters);
            }
        } else {
            if (!is_null($customValue) && $fromCustomSetter) {
                $value = $customValue;
            }
        }
        
        if ($type == BL_CustomGrid_Model_Grid::GRID_PARAM_LIMIT) {
            if (!in_array($value, $gridModel->getAppliablePaginationValues())) {
                $value = (is_null($originalValue) ? $blockValue : $originalValue);
            }
        }
        
        return $value;
    }
    
    /**
     * Apply base default limit to the given grid block (possibly based on custom pagination values)
     *
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return this
     */
    public function applyBaseDefaultLimitToGridBlock(Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        $gridModel = $this->getGridModel();
        
        $customLimit = $gridModel->getDefaultPaginationValue();
        $blockLimit  = $gridBlock->getDefaultLimit();
        $values = $gridModel->getAppliablePaginationValues();
        
        if (!empty($customLimit) && in_array($customLimit, $values)) {
            $defaultLimit = $customLimit;
        } elseif (!empty($blockLimit) && in_array($blockLimit, $values)) {
            $defaultLimit = $blockLimit;
        } else {
            $defaultLimit = array_shift($values);
        }
        
        $gridBlock->blcg_setDefaultLimit($defaultLimit, true);
        return $this;
    }
    
    /**
     * Apply default parameters to the given grid block
     *
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return this
     */
    public function applyDefaultsToGridBlock(Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        $gridModel = $this->getGridModel();
        
        if ($defaultValue = $gridModel->getData('default_page')) {
            $gridBlock->blcg_setDefaultPage($defaultValue);
        }
        if ($defaultValue = $gridModel->getData('default_limit')) {
            $gridBlock->blcg_setDefaultLimit($defaultValue);
        }
        if ($defaultValue = $gridModel->getData('default_sort')) {
            $gridBlock->blcg_setDefaultSort($defaultValue);
        }
        if ($defaultValue = $gridModel->getData('default_dir')) {
            $gridBlock->blcg_setDefaultDir($defaultValue);
        }
        if (is_array($defaultValue = $this->getAppliableDefaultFilter())) {
            $gridBlock->blcg_setDefaultFilter($defaultValue);
        }
        
        return $this;
    }
    
    /**
     * Prepare the given default filter value by first checking it, then (if necessary) adding some useful informations
     * to it that will later be helpful in determining the validity of each corresponding filter
     * 
     * @param string|array $filters Default filter value (encoded or not)
     * @return string|null
     */
    public function prepareDefaultFilterValue($filters)
    {
        if (!is_array($filters)) {
            $filters = $this->decodeGridFiltersString($filters);
        }
        if (is_array($filters) && !empty($filters)) {
            $gridModel = $this->getGridModel();
            $columns = $gridModel->getGgetColumns();
            $attributesRenderers = $gridModel->getAvailableAttributesRendererTypes();
            
            foreach ($filters as $columnBlockId => $filterData) {
                if (isset($columns[$columnBlockId])) {
                    $column = $columns[$columnBlockId];
                    
                    if ($column->isCollection()) {
                        $rendererType = $column->getRendererType();
                    } elseif ($column->isAttribute()) {
                        $rendererType = $attributesRenderers[$column->getIndex()];
                    } elseif ($column->isCustom()) {
                        $rendererType = $column->getRendererType();
                    } else {
                        $rendererType = null;
                    }
                    
                    $filters[$columnBlockId] = array(
                        'value'  => $filterData,
                        'column' => array(
                            'origin' => $column->getOrigin(),
                            'index'  => $column->getIndex(),
                            'renderer_type' => $rendererType,
                            'customization_params' => $column->getCustomizationParams(),
                        ),
                    );
                } else {
                    unset($filters[$columnBlockId]);
                }
            }
            
            $filters = serialize($filters);
        } else {
            $filters = null;
        }
        return $filters;
    }
}
