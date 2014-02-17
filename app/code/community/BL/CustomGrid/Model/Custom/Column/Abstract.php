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

abstract class BL_CustomGrid_Model_Custom_Column_Abstract
    extends Varien_Object
{
    protected $_gridHelper = 'customgrid/grid';
    protected $_collectionHelper = 'customgrid/collection';
    
    static protected $_uniqueFlags   = array();
    static protected $_uniqueAliases = array();
    
    // Grid collection events on which to apply callbacks
    const GC_EVENT_BEFORE_PREPARE     = 'before_prepare';
    const GC_EVENT_AFTER_PREPARE      = 'after_prepare';
    const GC_EVENT_BEFORE_SET_FILTERS = 'before_set_filters';
    const GC_EVENT_AFTER_SET_FILTERS  = 'after_set_filters';
    const GC_EVENT_BEFORE_SET         = 'before_set';
    const GC_EVENT_AFTER_SET          = 'after_set';
    const GC_EVENT_BEFORE_EXPORT_LOAD = 'before_export_load';
    const GC_EVENT_AFTER_EXPORT_LOAD  = 'after_export_load';
    
    protected function _construct()
    {
        parent::_construct();
        $this->initConfig();
        
        if (!$this->getFromCustomGridXml()) {
            $this->finalizeConfig();
        }
    }
    
    protected function _getBaseHelper()
    {
        return Mage::helper('customgrid');
    }
    
    protected function _getGridHelper()
    {
        return Mage::helper($this->_gridHelper);
    }
    
    protected function _getCollectionHelper()
    {
        return Mage::helper($this->_collectionHelper);
    }
    
    protected function _getCollectionMainTableAlias($collection)
    {
        return $this->_getCollectionHelper()
            ->getCollectionMainTableAlias($collection);
    }
    
    protected function _getCollectionAdapter($collection, $withQiCallback=false)
    {
        $helper  = $this->_getCollectionHelper();
        $adapter = $helper->getCollectionAdapter($collection);
        
        if (!$withQiCallback) {
            return $adapter;
        }
        
        return array(
            $adapter,
            $helper->getQuoteIdentifierCallback($adapter),
        );
    }
    
    public function initConfig()
    {
        $boolKeys = array(
            'allow_store',
            'allow_renderers',
            'allow_customization',
            'allow_editor',
        );
        $arraysKeys = array(
            'allowed_versions',
            'excluded_versions',
            'allowed_blocks',
            'excluded_blocks',
            'allowed_rewrites',
            'excluded_rewrites',
            'grid_params',
            'model_params',
            'custom_params_config',
            'custom_params_window_config',
        );
        
        foreach ($boolKeys as $key) {
            $this->setDataUsingMethod($key, false);
        }
        foreach ($arraysKeys as $key) {
            $this->setDataUsingMethod($key, array());
        }
        
        return $this;
    }
    
    public function finalizeConfig()
    {
        return $this;
    }
    
    protected function _addAvailabilityConfig($type, $value, $merge=false)
    {
        if (!is_array($value)) {
            $value = array($value);
        }
        if ($merge) {
            if (!is_array($current = $this->getDataUsingMethod($type))) {
                $current = array();
            }
            $value = array_merge($current, $value);
        }
        return $this->setDataUsingMethod($type, $value);
    }
    
    public function addAllowedVersions($versions, $merge=false)
    {
        return $this->_addAvailabilityConfig('allowed_versions', $versions, $merge);
    }
    
    public function addExcludedVersions($versions, $merge=false)
    {
        return $this->_addAvailabilityConfig('excluded_versions', $versions, $merge);
    }
    
    public function addAllowedBlocks($blocks, $merge=false)
    {
        return $this->_addAvailabilityConfig('allowed_blocks', $blocks, $merge);
    }
    
    public function addExcludedBlocks($blocks, $merge=false)
    {
        return $this->_addAvailabilityConfig('excluded_blocks', $blocks, $merge);
    }
    
    public function addAllowedRewrites($rewrites, $merge=false)
    {
        return $this->_addAvailabilityConfig('allowed_rewrites', $rewrites, $merge);
    }
    
    public function addExcludedRewrites($rewrites, $merge=false)
    {
        return $this->_addAvailabilityConfig('excluded_rewrites', $rewrites, $merge);
    }
    
    protected function _getAvailabilityRegex($value)
    {
        return str_replace(array('\\*', '\\?'), array('.*', '.'), '#' . preg_quote($value, '#') . '#i');
    }
    
    protected function _checkAvailabilityValues($values, $reference, $excluded=false)
    {
        if (!is_array($values) || empty($values)) {
            return true;
        }
        
        $matched = false;
        
        foreach ($values as $value) {
            if (preg_match($this->_getAvailabilityRegex($value), $reference)) {
                $matched = true;
                break;
            }
        }
        
        return ($matched ? !$excluded : $excluded);
    }
    
    public function isAvailable($blockType, $rewritingClassName)
    {
        return ($this->_checkAvailabilityValues($this->getAllowedVersions(), Mage::getVersion())
            && $this->_checkAvailabilityValues($this->getExcludedVersions(), Mage::getVersion(), true)
            && $this->_checkAvailabilityValues($this->getAllowedBlocks(), $blockType)
            && $this->_checkAvailabilityValues($this->getExcludedBlocks(), $blockType, true)
            && $this->_checkAvailabilityValues($this->getAllowedRewrites(), $rewritingClassName)
            && $this->_checkAvailabilityValues($this->getExcludedRewrites(), $rewritingClassName, true));
    }
    
    public function shouldInvalidateFilters($grid, $column, $params, $rendererTypes)
    {
        return ($rendererTypes['old'] != $rendererTypes['new']);
    }
    
    public function setName($name)
    {
        $this->setData('name', $name);
        $windowConfig = $this->_getData('custom_params_window_config');
        
        if (is_array($windowConfig) && !isset($windowConfig['title'])) {
            $windowConfig['title'] = $this->_getBaseHelper()->__('Customization : %s', $name);
            $this->setCustomParamsWindowConfig($windowConfig);
        }
        
        return $this;
    }
    
    protected function _setConfigParams($key, array $params, $merge=false)
    {
        if ($merge) {
            $this->setData($key, array_merge($this->_getData($key), $params));
        } else {
            $this->setData($key, $params);
        }
        return $this;
    }
    
    public function setGridParams(array $params, $merge=false)
    {
        return $this->_setConfigParams('grid_params', $params, $merge);
    }
    
    public function getGridParam($key, $default=null)
    {
        $params = $this->getGridParams();
        return (is_array($params) && isset($params[$key]) ? $params[$key] : $default);
    }
    
    public function setModelParams(array $params, $merge=false)
    {
        return $this->_setConfigParams('model_params', $params, $merge);
    }
    
    public function getModelParam($key, $default=null)
    {
        $params = $this->getModelParams();
        return (is_array($params) && isset($params[$key]) ? $params[$key] : $default);
    }
    
    public function getCustomParamsConfigMinOrder()
    {
        $order  = null;
        
        foreach ($this->getCustomParamsConfig(false) as $param) {
            $order = (is_null($order) ? $param['sort_order'] : min($order, $param['sort_order']));
        }
        
        return $order;
    }
    
    public function getCustomParamsConfigMaxOrder()
    {
        $order  = null;
        
        foreach ($this->getCustomParamsConfig(false) as $param) {
            $order = (is_null($order) ? $param['sort_order'] : max($order, $param['sort_order']));
        }
        
        return $order;
    }
    
    public function addCustomParam($key, $data, $sortOrder='last', $override=true)
    {
        $customParams = $this->getCustomParamsConfig();
        
        if ($override || !isset($customParams[$key])) {
            if ($sortOrder === 'last') {
                $data['sort_order'] = $this->getCustomParamConfigMaxOrder() + 1;
            } elseif ($sortOrder === 'first') {
                $data['sort_order'] = $this->getCustomParamConfigMinOrder() - 1;
            } else {
                $data['sort_order'] = $sortOrder;
            }
            $data['key'] = $key;
            $data['visible'] = (isset($data['visible']) ? $data['visible'] : true);
            $customParams[$key] = new Varien_Object($data);
            $this->setCustomParamsConfig($customParams);
        }
        
        return $this;
    }
    
    protected function _sortCustomParamsConfig($a, $b)
    {
        $aOrder = (int)$a->getData('sort_order');
        $bOrder = (int)$b->getData('sort_order');
        return ($aOrder < $bOrder ? -1 : ($aOrder > $bOrder ? 1 : strcmp($a->getData('label'), $b->getData('label'))));
    }
    
    public function getCustomParamsConfig($sorted=true)
    {
        $params = $this->_getData('custom_params_config');
        
        if ($sorted) {
            uasort($params, array($this, '_sortCustomParamsConfig'));
        }
        
        return $params;
    }
    
    public function setCustomParamsWindowConfig(array $config, $merge=false)
    {
        return $this->_setConfigParams('custom_params_window_config', $config, $merge);
    }
    
    protected function _extractBoolParam(array $params, $key, $default=false)
    {
        return (isset($params[$key]) ? (bool)$params[$key] : $default);
    }
    
    protected function _extractIntParam(array $params, $key, $default=null, $notEmpty=false)
    {
        return (isset($params[$key]) ? ((!$notEmpty || (strval($params[$key]) !== '')) ? intval($params[$key]) : $default) : $default);
    }
    
    protected function _extractStringParam(array $params, $key, $default=null, $notEmpty=false)
    {
        return (isset($params[$key]) ? ((!$notEmpty || (strval($params[$key]) !== '')) ? strval($params[$key]) : $default) : $default);
    }
    
    protected function _getDefaultGridValues($block, $model, $id, $alias, $params, $store, $renderer=null)
    {
        return array('index' => $alias);
    }
    
    protected function _getGridValues($block, $model, $id, $alias, $params, $store, $renderer=null)
    {
        return array();
    }
    
    protected function _getForcedGridValues($block, $model, $id, $alias, $params, $store, $renderer=null)
    {
        return array();
    }
    
    public function getGridValues($block, $model, $id, $alias, $params, $store, $renderer=null)
    {
        return array_merge(
            $this->_getDefaultGridValues($block, $model, $id, $alias, $params, $store, $renderer),
            $this->_getGridValues($block, $model, $id, $alias, $params, $store, $renderer),
            (is_array($values = $this->getGridParams()) ? $values : array()),
            (is_object($renderer) ? $renderer->getColumnGridValues($alias, $store, $model) : array()),
            $this->_getForcedGridValues($block, $model, $id, $alias, $params, $store, $renderer)
        );
    }
    
    protected function _generateUniqueAlias($suffix, $existing)
    {
        $class = get_class($this);
        $alias = '_'.strtolower(preg_replace('#[^A-Z]#', '', $class).$suffix);
        $base  = $alias;
        $index = 1;
        
        while (in_array($alias, $existing)) {
            $alias = $base.'_'.$index++;
        }
        
        return $alias;
    }
    
    protected function _getUniqueCollectionFlag($suffix='')
    {
        $alias = $this->_generateUniqueAlias($suffix.'_applied', self::$_uniqueFlags);
        self::$_uniqueFlags[] = $alias;
        return $alias;
    }
    
    protected function _getUniqueTableAlias($suffix='')
    {
        $alias = $this->_generateUniqueAlias($suffix, self::$_uniqueAliases);
        self::$_uniqueAliases[] = $alias;
        return $alias;
    }
    
    protected function _verifyBlock($block, $model, $id, $alias, $params, $store, $renderer=null)
    {
        return $this->_getGridHelper()->verifyGridBlock($block, $model);
    }
    
    protected function _verifyCollection($block, $collection, $model, $id, $alias, $params, $store, $renderer=null)
    {
        return $this->_getGridHelper()->verifyGridCollection($block, $model);
    }
    
    protected function _handleApplyError($block, $model, $id, $alias, $params, $store, $renderer=null, $message='')
    {
        Mage::getSingleton('customgrid/session')
            ->addError(Mage::helper('customgrid')->__('The "%s" custom column could not be applied : "%s"', $this->getName(), $message));
        return $this;
    }
    
    public function prepareGridCollectionFiltersMap($model, $block, $collection, $filters)
    {
        $this->_getCollectionHelper()->prepareGridCollectionFiltersMap($collection, $block, $model, $filters);
        return $this;
    }
    
    public function restoreGridCollectionFiltersMap($model, $block, $collection, $filters)
    {
        $this->_getCollectionHelper()->restoreGridCollectionFiltersMap($collection, $block, $model, $filters);
        return $this;
    }
    
    protected function _prepareGridCollection($collection, $block, $model, $id, $alias, $params, $store, $renderer=null)
    {
        $block->blcg_addCollectionCallback(
            self::GC_EVENT_BEFORE_SET_FILTERS,
            array($this, 'prepareGridCollectionFiltersMap'),
            array($model),
            true
        );
        
        if ($this->_getBaseHelper()->isMageVersionGreaterThan(1, 6)
            && $this->_getGridHelper()->isEavEntityGrid($block, $model)) {
            /**
            * Fix for Mage_Eav_Model_Entity_Collection_Abstract::_renderOrders() on 1.7+,
            * which does not handle well fields with table aliases
            * (it forces the use of addAttributeToSort(), but then in our case possibly on mapped fields,
            *  which are not / can not be recognized as attributes or static fields)
            * Note that this does not affect filters applied on custom columns derived from BL_CustomGrid_Model_Custom_Column_Simple_Abstract,
            * as it forces field orders on EAV entity grids
            */
            $block->blcg_addCollectionCallback(
                self::GC_EVENT_AFTER_SET_FILTERS,
                array($this, 'restoreGridCollectionFiltersMap'),
                array($model),
                true
            );
            
            // @todo or it may not be needed at all to prepare the filters map for EAV entity collections ? (addAttributeToFilter() may do it by itself most of the time)
        }
        
        return $this;
    }
    
    abstract protected function _applyToGridCollection($collection, $block, $model, $id, $alias, $params, $store, $renderer=null);
    
    protected function _applyToGridBlock($block, $model, $id, $alias, $params, $store, $renderer=null)
    {
        $this->_prepareGridCollection($block->getCollection(), $block, $model, $id, $alias, $params, $store, $renderer);
        $this->_applyToGridCollection($block->getCollection(), $block, $model, $id, $alias, $params, $store, $renderer);
        return $this;
    }
    
    public function applyToGridBlock($block, $model, $id, $alias, $params, $store, $renderer=null)
    {
        try {
            if ($this->_verifyBlock($block, $model, $id, $alias, $params, $store, $renderer)) {
                if ($this->_verifyCollection($block, $block->getCollection(), $model, $id, $alias, $params, $store, $renderer)) {
                    $this->_applyToGridBlock($block, $model, $id, $alias, $params, $store, $renderer);
                } else {
                    Mage::throwException(Mage::helper('customgrid')->__('The block collection is not valid'));
                }
            } else {
                Mage::throwException(Mage::helper('customgrid')->__('The block is not valid'));
            }
            return $this->getGridValues($block, $model, $id, $alias, $params, $store, $renderer);
        } catch (Exception $e) {
            $this->_handleApplyError($block, $model, $id, $alias, $params, $store, $renderer, $e->getMessage());
            return false;
        }
    }
}