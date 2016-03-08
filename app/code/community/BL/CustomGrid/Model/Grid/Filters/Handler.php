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
 * @copyright  Copyright (c) 2016 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_Grid_Filters_Handler extends BL_CustomGrid_Model_Grid_Worker_Abstract
{
    const SESSION_BASE_KEY_GRID_FILTERS_TOKEN = '_blcg_session_key_token_';
    
    /**
     * Name of the parameter usable to hold the value of the grid token in requests (used for filters verification)
     */
    const GRID_TOKEN_PARAM_NAME  = '_blcg_token_';
    
    public function getType()
    {
        return BL_CustomGrid_Model_Grid::WORKER_TYPE_FILTERS_HANDLER;
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
        /** @var Mage_Adminhtml_Helper_Data $helper */
        $helper = Mage::helper('adminhtml');
        return (is_string($filters) ? $helper->prepareFilterString($filters) : $filters);
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
     * @param string[] $attributesRenderers Available attributes renderers
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
            $gridModel  = $this->getGridModel();
            $typeConfig = $gridModel->getGridTypeConfig();
            $previousIndex = $sessionFilter['index'];
            
            $rendererTypes = array(
                'previous' => $sessionFilter['renderer_type'],
                'current'  => $column->getRendererType(),
            );
            $customizationParams = array(
                'previous' => $typeConfig->decodeParameters($sessionFilter['customization_params']),
                'current'  => $typeConfig->decodeParameters($column->getCustomizationParams()),
            );
            
            if (($previousIndex != $columnIndex)
                || (!$customColumn = $column->getCustomColumnModel())) {
                $hasColumnChanged = true;
            } else {
                $hasColumnChanged = $customColumn->shouldInvalidateFilters(
                    $gridModel,
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
     * Return the session key for the filters token of the current grid model
     *
     * @return string
     */
    protected function _getFiltersTokenSessionKey()
    {
        return self::SESSION_BASE_KEY_GRID_FILTERS_TOKEN . $this->getGridModel()->getId();
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
        $gridModel   = $this->getGridModel();
        $gridProfile = $gridModel->getProfile();
        
        // Get session previous filtering informations
        $session = $gridModel->getAdminhtmlSession();
        $tokenSessionKey = $this->_getFiltersTokenSessionKey();
        $sessionAppliedFilters = $gridProfile->getSessionAppliedFilters();
        $sessionRemovedFilters = $gridProfile->getSessionRemovedFilters();
        
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
        
        /**
         * Add our token to current request and session
         * Use ":" in hash to force Varien_Db_Adapter_Pdo_Mysql::query() using a bind parameter instead of full request path,
         * (as it uses this condition : strpos($sql, ':') !== false),
         * when querying core_url_rewrite table, else the query could be too long,
         * making Zend_Db_Statement::_stripQuoted() sometimes crash on one of its call to preg_replace()
         */
        
        /** @var Mage_Core_Helper_Data $helper */
        $helper = Mage::helper('core');
        $tokenValue = $helper->uniqHash('blcg:');
        $gridBlock->getRequest()->setParam(self::GRID_TOKEN_PARAM_NAME, $tokenValue);
        $session->setData($tokenSessionKey, $tokenValue);
        
        // Remove obsolete filters and save up-to-date filters array to session
        $obsoleteFilterBlockIds = array_diff(array_keys($sessionAppliedFilters), $foundFilterBlockIds);
        
        foreach ($obsoleteFilterBlockIds as $columnBlockId) {
            unset($sessionAppliedFilters[$columnBlockId]);
        }
        
        $gridProfile->setSessionAppliedFilters($sessionAppliedFilters);
        
        if ($isGridAction) {
            /*
            Apply newly removed filters only when a grid action is done
            The only remaining potential source of "maybe wrong" filters could come from  the use of an old URL with
            obsolete filter(s) in it (eg from browser history), but there is no way at the moment to detect them
            (at least not any simple one with only few impacts)
            */
            $gridProfile->setSessionRemovedFilters(array_intersect_key($sessionRemovedFilters, $removedFilterBlockIds));
        } else {
            $gridProfile->setSessionRemovedFilters($sessionRemovedFilters);
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
        $gridModel   = $this->getGridModel();
        $gridProfile = $gridModel->getProfile();
        
        if (!$gridModel->hasData('appliable_default_filter')) {
            $appliableDefaultFilter = null;
            
            if (($filters = $gridProfile->getData('default_filter')) && is_array($filters = @unserialize($filters))) {
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
                            $typeConfig = $gridModel->getGridTypeConfig();
                            
                            $rendererTypes = array(
                                'previous' => $previousRendererType,
                                'current'  => $column->getRendererType(),
                            );
                            $customizationParams = array(
                                'previous' => $typeConfig->decodeParameters($previousCustomizationParams),
                                'current'  => $typeConfig->decodeParameters($column->getCustomizationParams()),
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
     * Return the renderer type of the given grid column
     *
     * @param BL_CustomGrid_Model_Grid_Column $gridColumn Grid column
     * @param string[] $attributesRenderers Attributes renderer types
     * @return string|null
     */
    protected function _getGridColumnRendererType(
        BL_CustomGrid_Model_Grid_Column $gridColumn,
        array $attributesRenderers
    ) {
        $rendererType = null;
        
        if ($gridColumn->isCollection()) {
            $rendererType = $gridColumn->getRendererType();
        } elseif ($gridColumn->isAttribute()) {
            $rendererType = $attributesRenderers[$gridColumn->getIndex()];
        } elseif ($gridColumn->isCustom()) {
            $rendererType = $gridColumn->getRendererType();
        }
        
        return $rendererType;
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
            $columns = $gridModel->getColumns();
            $attributesRenderers = $gridModel->getAvailableAttributesRendererTypes();
            
            foreach ($filters as $columnBlockId => $filterData) {
                if (isset($columns[$columnBlockId])) {
                    $column = $columns[$columnBlockId];
                    $rendererType = $this->_getGridColumnRendererType($column, $attributesRenderers);
                    
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
