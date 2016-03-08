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

class BL_CustomGrid_Model_Grid_Default_Params_Handler extends BL_CustomGrid_Model_Grid_Worker_Abstract
{
    /**
     * Default pagination values (usually hard-coded in grid template)
     *
     * @var int[]
     */
    static protected $_defaultPaginationValues = array(20, 30, 50, 100, 200);
    
    public function getType()
    {
        return BL_CustomGrid_Model_Grid::WORKER_TYPE_DEFAULT_PARAMS_HANDLER;
    }
    
    /**
     * Return whether the custom pagination values should be merged with the base ones
     *
     * @return bool
     */
    public function getMergeBasePagination()
    {
        return is_null($value = $this->getGridModel()->getData('merge_base_pagination'))
            ? $this->getConfigHelper()->getMergeBasePagination()
            : (bool) $value;
    }
    
    /**
     * Return the custom pagination values
     *
     * @return int[]
     */
    public function getCustomPaginationValues()
    {
        return is_null($value = $this->getGridModel()->getData('pagination_values'))
            ? $this->getConfigHelper()->getPaginationValues()
            : $this->getBaseHelper()->parseCsvIntArray($value, true, true, 1);
    }
    
    /**
     * Return the default pagination value
     *
     * @return int
     */
    public function getDefaultPaginationValue()
    {
        return is_null($value = $this->getGridModel()->getData('default_pagination_value'))
            ? $this->getConfigHelper()->getDefaultPaginationValue()
            : (int) $value;
    }
    
    /**
     * Return the appliable pagination values
     *
     * @return int[]
     */
    public function getAppliablePaginationValues()
    {
        $gridModel = $this->getGridModel();
        
        if (!$gridModel->hasData('appliable_pagination_values')) {
            $values = $this->getCustomPaginationValues();
            
            if (!is_array($values) || empty($values)) {
                $values = self::$_defaultPaginationValues;
            } elseif ($this->getMergeBasePagination()) {
                $values = array_unique(array_merge($values, self::$_defaultPaginationValues));
                sort($values, SORT_NUMERIC);
            }
            
            $gridModel->setData('appliable_pagination_values', $values);
        }
        return $gridModel->_getData('appliable_pagination_values');
    }
    
    /**
     * Return whether the given behaviour is dedicated to the default filter values
     *
     * @param string $behaviour Default parameter behaviour
     * @return bool
     */
    protected function _isFilterDefaultParamBehaviour($behaviour)
    {
        return ($behaviour == BL_CustomGrid_Model_Grid::DEFAULT_PARAM_MERGE_DEFAULT)
        || ($behaviour == BL_CustomGrid_Model_Grid::DEFAULT_PARAM_MERGE_BASE_CUSTOM)
        || ($behaviour == BL_CustomGrid_Model_Grid::DEFAULT_PARAM_MERGE_BASE_ORIGINAL);
    }
    
    /**
     * Return appliable default filter value depending on the given block and custom values, and the given behaviour
     *
     * @param mixed $blockValue Base value
     * @param mixed $customValue User-defined value
     * @param bool $fromCustomSetter Whether this function is called from a setter applying user-defined values
     * @param string $behaviour Appliable behaviour
     * @return mixed
     */
    protected function _getGridBlockDefaultFilterValue($blockValue, $customValue, $fromCustomSetter, $behaviour)
    {
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
        
        return $value;
    }
    
    /**
     * Return appliable default parameter value depending on the given block and custom values, and the given behaviour
     *
     * @param string $type Parameter type (eg "limit" or "filter")
     * @param mixed $blockValue Base value
     * @param mixed $customValue User-defined value
     * @param bool $fromCustomSetter Whether this function is called from a setter applying user-defined values
     * @param string $behaviour Appliable behaviour
     * @return mixed
     */
    protected function _getGridBlockDefaultParamValue($type, $blockValue, $customValue, $fromCustomSetter, $behaviour)
    {
        $value = $blockValue;
        
        if (($behaviour == BL_CustomGrid_Model_Grid::DEFAULT_PARAM_FORCE_CUSTOM) && !is_null($customValue)) {
            $value = $customValue;
        } elseif ($this->_isFilterDefaultParamBehaviour($behaviour)
            && ($type == BL_CustomGrid_Model_Grid::GRID_PARAM_FILTER)) {
            $value = $this->_getGridBlockDefaultFilterValue($blockValue, $customValue, $fromCustomSetter, $behaviour);
        } elseif (($behaviour == BL_CustomGrid_Model_Grid::DEFAULT_PARAM_DEFAULT)
            && (!is_null($customValue) && $fromCustomSetter)) {
            $value = $customValue;
        }
        
        return $value;
    }
    
    /**
     * Return appliable default parameter value depending on the available values and the defined behaviour
     *
     * @param string $type Parameter type (eg "limit" or "filter")
     * @param mixed $blockValue Base value
     * @param mixed $customValue User-defined value
     * @param bool $fromCustomSetter Whether this function is called from a setter applying user-defined values
     * @param mixed $originalValue Current value (that is being replaced)
     * @return mixed
     */
    public function getGridBlockDefaultParamValue(
        $type,
        $blockValue,
        $customValue = null,
        $fromCustomSetter = false,
        $originalValue = null
    ) {
        $gridModel = $this->getGridModel();
        
        if (!$fromCustomSetter) {
            if ($type == BL_CustomGrid_Model_Grid::GRID_PARAM_FILTER) {
                $customValue = $gridModel->getFiltersHandler()->getAppliableDefaultFilter();
            } else {
                $customValue = $gridModel->getData('default_' . $type);
            }
        }
        
        if (!$behaviour = $gridModel->getData('default_' . $type . '_behaviour')) {
            $behaviour = $this->getConfigHelper()->geDefaultParamBehaviour($type);
        }
        
        $value = $this->_getGridBlockDefaultParamValue($type, $blockValue, $customValue, $fromCustomSetter, $behaviour);
        
        if (($type == BL_CustomGrid_Model_Grid::GRID_PARAM_LIMIT)
            && !in_array($value, $this->getAppliablePaginationValues())) {
            $value = (is_null($originalValue) ? $blockValue : $originalValue);
        }
        
        return $value;
    }
    
    /**
     * Apply base default limit to the given grid block (possibly based on custom pagination values)
     *
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return BL_CustomGrid_Model_Grid_Applier
     */
    public function applyBaseDefaultLimitToGridBlock(Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        $customLimit = $this->getDefaultPaginationValue();
        $blockLimit  = $gridBlock->getDefaultLimit();
        $values = $this->getAppliablePaginationValues();
        
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
     * @return BL_CustomGrid_Model_Grid_Applier
     */
    public function applyDefaultsToGridBlock(Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        $gridModel   = $this->getGridModel();
        $gridProfile = $this->getGridProfile();
        
        if ($defaultValue = $gridProfile->getData('default_page')) {
            $gridBlock->blcg_setDefaultPage($defaultValue);
        }
        if ($defaultValue = $gridProfile->getData('default_limit')) {
            $gridBlock->blcg_setDefaultLimit($defaultValue);
        }
        if ($defaultValue = $gridProfile->getData('default_sort')) {
            $gridBlock->blcg_setDefaultSort($defaultValue);
        }
        if ($defaultValue = $gridProfile->getData('default_dir')) {
            $gridBlock->blcg_setDefaultDir($defaultValue);
        }
        if (is_array($defaultValue = $gridModel->getFiltersHandler()->getAppliableDefaultFilter())) {
            $gridBlock->blcg_setDefaultFilter($defaultValue);
        }
        
        return $this;
    }
    
    /**
     * Update the default parameters behaviours for the current grid model
     *
     * @param array $behaviours New parameters behaviours
     * @return BL_CustomGrid_Model_Grid_Default_Params_Handler
     */
    public function updateDefaultParamsBehaviours(array $behaviours)
    {
        $gridModel = $this->getGridModel();
        
        $gridModel->checkUserActionPermission(
            BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_DEFAULT_PARAMS_BEHAVIOURS,
            false
        );
        
        $keys = array_fill_keys($gridModel->getGridParamsKeys(), false);
        $keys[BL_CustomGrid_Model_Grid::GRID_PARAM_FILTER] = true;
        
        $scalarValues = array(
            BL_CustomGrid_Model_Grid::DEFAULT_PARAM_DEFAULT,
            BL_CustomGrid_Model_Grid::DEFAULT_PARAM_FORCE_CUSTOM,
            BL_CustomGrid_Model_Grid::DEFAULT_PARAM_FORCE_ORIGINAL,
        );
        
        $arrayValues = array(
            BL_CustomGrid_Model_Grid::DEFAULT_PARAM_MERGE_DEFAULT,
            BL_CustomGrid_Model_Grid::DEFAULT_PARAM_MERGE_BASE_CUSTOM,
            BL_CustomGrid_Model_Grid::DEFAULT_PARAM_MERGE_BASE_ORIGINAL,
        );
        
        foreach ($keys as $key => $isArray) {
            if (isset($behaviours[$key])) {
                $value = null;
                
                if (in_array($behaviours[$key], $scalarValues)
                    || ($isArray && in_array($behaviours[$key], $arrayValues))) {
                    $value = $behaviours[$key];
                }
                
                $gridModel->setData('default_' . $key . '_behaviour', $value);
            }
        }
        
        $gridModel->setDataChanges(true);
        return $this;
    }
}
