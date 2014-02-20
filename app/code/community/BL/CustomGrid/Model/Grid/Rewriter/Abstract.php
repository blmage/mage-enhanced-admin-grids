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

abstract class BL_CustomGrid_Model_Grid_Rewriter_Abstract
    extends Varien_Object
{
    const REWRITE_CODE_VERSION = 1; // bump this value when significant changes are made to the rewrite code
    
    protected function _getBlcgClassPrefix()
    {
        return 'BL_CustomGrid_Block_Rewrite_';
    }
    
    protected function _getBlcgClass($originalClass, $gridType)
    {
        $classParts = array_map('ucfirst', array_map('strtolower', explode('_', $originalClass)));
        return $this->_getBlcgClassPrefix() . implode('_', $classParts);
    }
    
    abstract protected function _rewriteGrid($blcgClass, $originalClass, $gridType);
    
    final public function rewriteGrid($originalClass, $gridType)
    {
        $blcgClass = $this->_getBlcgClass($originalClass, $gridType);
        $rewriteSuccess = false;
        
        try {
            if (!class_exists($originalClass, true)) {
                Mage::throwException(Mage::helper('customgrid')->__('The original class "%s" does not exist', $originalClass));
            }
            if (class_exists($blcgClass, false)) {
                Mage::throwException(Mage::helper('customgrid')->__('The rewriting class "%s" already exists', $blcgClass));
            }
            
            $this->_rewriteGrid($blcgClass, $originalClass, $gridType);
            
            if (!class_exists($blcgClass, true)) {
                Mage::throwException(Mage::helper('customgrid')->__('The generated rewriting class "%s" can not be found', $blcgClass));
            }
            
            $rewriteSuccess = true;
            
        } catch (Exception $e) {
            Mage::throwException(Mage::helper('customgrid')->__('An error occured while rewriting "%s" : "%s" (rewriter: "%s")', $gridType, $e->getMessage(), $this->getId()));
        }
        
        return ($rewriteSuccess ? $blcgClass : false);
    }
    
    protected function _getRewriteCode($blcgClass, $originalClass, $gridType)
    {
        return 'class '.$blcgClass.' extends '.$originalClass.'
{
    private $_blcg_gridModel   = null;
    private $_blcg_typeModel   = null;
    private $_blcg_filterParam = null;
    private $_blcg_exportInfos = null;
    private $_blcg_exportedCollection    = null;
    private $_blcg_holdPrepareCollection = false;
    private $_blcg_prepareEventsEnabled  = true;
    private $_blcg_defaultParameters     = array();
    private $_blcg_collectionCallbacks   = array(
        \'before_prepare\'     => array(),
        \'after_prepare\'      => array(),
        \'before_set_filters\' => array(),
        \'after_set_filters\'  => array(),
        \'before_set\'         => array(),
        \'after_set\'          => array(),
        \'before_export_load\' => array(),
        \'after_export_load\'  => array(),
    );
    private $_blcg_additionalAttributes = array();
    private $_blcg_mustSelectAdditionalAttributes   = false;
    
    public function getModuleName()
    {
        $module = $this->getData(\'module_name\');
        
        if (is_null($module)) {
            if (!$class = get_parent_class($this)) {
                $class = get_class($this);
            }
            $module = substr($class, 0, strpos($class, \'_Block\'));
            $this->setData(\'module_name\', $module);
        }
        
        return $module;
    }
    
    public function setCollection($collection)
    {
        if (!is_null($this->_blcg_typeModel)) {
            $this->_blcg_typeModel->beforeGridSetCollection($this, $collection);
        }
        $this->_blcg_launchCollectionCallbacks(\'before_set\', array($this, $collection));
        $return = parent::setCollection($collection);
        $this->_blcg_launchCollectionCallbacks(\'after_set\', array($this, $collection));
        if (!is_null($this->_blcg_typeModel)) {
            $this->_blcg_typeModel->afterGridSetCollection($this, $collection);
        }
        return $return;
    }
    
    public function getCollection()
    {
        $collection = parent::getCollection();
        if ($this->_blcg_mustSelectAdditionalAttributes
            && ($collection instanceof Mage_Eav_Model_Entity_Collection_Abstract)
            && count($this->_blcg_additionalAttributes)) {
            $this->_blcg_mustSelectAdditionalAttributes = false;
            foreach ($this->_blcg_additionalAttributes as $attr) {
                $collection->joinAttribute($attr[\'alias\'], $attr[\'attribute\'], $attr[\'bind\'], $attr[\'filter\'], $attr[\'join_type\'], $attr[\'store_id\']);
            }
        }
        return $collection;
    }
    
    protected function _setFilterValues($data)
    {
        if ($this->_blcg_holdPrepareCollection) {
            return $this;
        } else {
            if (!is_null($this->_blcg_gridModel)) {
                $data = $this->_blcg_gridModel->verifyGridBlockFilters($this, $data);
            }
            $this->_blcg_launchCollectionCallbacks(\'before_set_filters\', array($this, $this->_collection, $data));
            $return = parent::_setFilterValues($data);
            $this->_blcg_launchCollectionCallbacks(\'after_set_filters\', array($this, $this->_collection, $data));
            return $return;
        }
    }
    
    protected function _prepareCollection()
    {
        // @todo should we use getCollection() for callbacks, but temporary passing the "_blcg_mustSelectAdditionalAttributes" flag to false ?
        if (!is_null($this->_blcg_typeModel)) {
            $this->_blcg_typeModel->beforeGridPrepareCollection($this, $this->_blcg_prepareEventsEnabled);
        }
        if ($this->_blcg_prepareEventsEnabled) {
            Mage::getSingleton(\'customgrid/observer\')->beforeGridPrepareCollection($this);
            $this->_blcg_launchCollectionCallbacks(\'before_prepare\', array($this, $this->_collection, true));
            $return = parent::_prepareCollection();
            $this->_blcg_launchCollectionCallbacks(\'after_prepare\', array($this, $this->_collection, true));
            Mage::getSingleton(\'customgrid/observer\')->afterGridPrepareCollection($this);
        } else {
            $this->_blcg_launchCollectionCallbacks(\'before_prepare\', array($this, $this->_collection, false));
            $return = parent::_prepareCollection();
            $this->_blcg_launchCollectionCallbacks(\'after_prepare\', array($this, $this->_collection, false));
        }
        if (!is_null($this->_blcg_typeModel)) {
            $this->_blcg_typeModel->afterGridPrepareCollection($this, $this->_blcg_prepareEventsEnabled);
        }
        return $return;
    }
    
    public function _exportIterateCollection($callback, array $args)
    {
        if (!is_array($this->_blcg_exportInfos)) {
            return parent::_exportIterateCollection($callback, $args);
        } else {
            if (!is_null($this->_blcg_exportedCollection)) {
                $originalCollection = $this->_blcg_exportedCollection;
            } else {
                $originalCollection = $this->getCollection();
            }
            if ($originalCollection->isLoaded()) {
                Mage::throwException(Mage::helper(\'customgrid\')->__(\'This grid does not seem to be compatible with the custom export. If you wish to report this problem, please indicate this class name : "%s"\', get_class($this)));
            }
            
            $exportPageSize = (isset($this->_exportPageSize) ? $this->_exportPageSize : 1000);
            $infos = $this->_blcg_exportInfos;
            $total = (isset($infos[\'custom_size\']) ?
                intval($infos[\'custom_size\']) :
                (isset($infos[\'size\']) ? intval($infos[\'size\']) : $exportPageSize));
                
            if ($total <= 0) {
                return;
            }
            
            $fromResult = (isset($infos[\'from_result\']) ? intval($infos[\'from_result\']) : 1);
            $pageSize   = min($total, $exportPageSize);
            $page       = ceil($fromResult/$pageSize);
            $pitchSize  = ($fromResult > 1 ? $fromResult-1 - ($page-1)*$pageSize : 0);
            $break      = false;
            $count      = null;
            
            while ($break !== true) {
                $collection = clone $originalCollection;
                $collection->setPageSize($pageSize);
                $collection->setCurPage($page);
                
                if (!is_null($this->_blcg_typeModel)) {
                    $this->_blcg_typeModel->beforeGridExportLoadCollection($this, $collection);
                }
                $this->_blcg_launchCollectionCallbacks(\'before_export_load\', array($this, $collection, $page, $pageSize));
                $collection->load();
                $this->_blcg_launchCollectionCallbacks(\'after_export_load\', array($this, $collection, $page, $pageSize));
                if (!is_null($this->_blcg_typeModel)) {
                    $this->_blcg_typeModel->afterGridExportLoadCollection($this, $collection);
                }
                
                if (is_null($count)) {
                    $count = $collection->getSize();
                    $total = min(max(0, $count-$fromResult+1), $total);
                    if ($total == 0) {
                        $break = true;
                        continue;
                    }
                    $first = true;
                    $exported = 0;
                }
                
                $page++;
                $i = 0;
                
                foreach ($collection as $item) {
                    if ($first) {
                        if ($i++ < $pitchSize) {
                            continue;
                        } else {
                            $first = false;
                        }
                    }
                    if (++$exported > $total) {
                        $break = true;
                        break;
                    }
                    call_user_func_array(array($this, $callback), array_merge(array($item), $args));
                }
            }
        }
    }
    
    public function blcg_isExport()
    {
        return $this->_isExport;
    }
    
    public function setDefaultPage($page)
    {
        if (!is_null($this->_blcg_gridModel)) {
            $page = $this->_blcg_gridModel->getGridBlockDefaultParamValue(\'page\', $page, null, false, $this->_defaultPage);
        }
        return parent::setDefaultPage($page);
    }
    
    public function setDefaultLimit($limit)
    {
        if (!is_null($this->_blcg_gridModel)) {
            $limit = $this->_blcg_gridModel->getGridBlockDefaultParamValue(\'limit\', $limit, null, false, $this->_defaultLimit);
        }
        return parent::setDefaultLimit($limit);
    }
    
    public function setDefaultSort($sort)
    {
        if (!is_null($this->_blcg_gridModel)) {
            $sort = $this->_blcg_gridModel->getGridBlockDefaultParamValue(\'sort\', $sort, null, false, $this->_defaultSort);
        }
        return parent::setDefaultSort($sort);
    }
    
    public function setDefaultDir($dir)
    {
        if (!is_null($this->_blcg_gridModel)) {
            $dir = $this->_blcg_gridModel->getGridBlockDefaultParamValue(\'dir\', $dir, null, false, $this->_defaultDir);
        }
        return parent::setDefaultDir($dir);
    }
    
    public function setDefaultFilter($filter)
    {
        if (!is_null($this->_blcg_gridModel)) {
            $filter = $this->_blcg_gridModel->getGridBlockDefaultParamValue(\'filter\', $filter, null, false, $this->_defaultFilter);
        }
        return parent::setDefaultFilter($filter);
    }
    
    public function blcg_setDefaultPage($page)
    {
        if (!is_null($this->_blcg_gridModel)) {
            $page = $this->_blcg_gridModel->getGridBlockDefaultParamValue(\'page\', $this->_defaultPage, $page, true);
        }
        return parent::setDefaultPage($page);
    }
    
    public function blcg_setDefaultLimit($limit, $forced=false)
    {
        if (!$forced && !is_null($this->_blcg_gridModel)) {
            $limit = $this->_blcg_gridModel->getGridBlockDefaultParamValue(\'limit\', $this->_defaultLimit, $limit, true);
        }
        return parent::setDefaultLimit($limit);
    }
    
    public function blcg_setDefaultSort($sort)
    {
        if (!is_null($this->_blcg_gridModel)) {
            $sort = $this->_blcg_gridModel->getGridBlockDefaultParamValue(\'sort\', $this->_defaultSort, $sort, true);
        }
        return parent::setDefaultSort($sort);
    }
    
    public function blcg_setDefaultDir($dir)
    {
        if (!is_null($this->_blcg_gridModel)) {
            $dir = $this->_blcg_gridModel->getGridBlockDefaultParamValue(\'dir\', $this->_defaultDir, $dir, true);
        }
        return parent::setDefaultDir($dir);
    }
    
    public function blcg_setDefaultFilter($filter)
    {
        if (!is_null($this->_blcg_gridModel)) {
            $filter = $this->_blcg_gridModel->getGridBlockDefaultParamValue(\'filter\', $this->_defaultFilter, $filter, true);
        }
        return parent::setDefaultFilter($filter);
    }
    
    public function blcg_setGridModel($model)
    {
        $this->_blcg_gridModel = $model;
        return $this;
    }
    
    public function blcg_getGridModel()
    {
        return $this->_blcg_gridModel;
    }
    
    public function blcg_setTypeModel($model)
    {
        $this->_blcg_typeModel = $model;
        return $this;
    }
    
    public function blcg_setFilterParam($param)
    {
        $this->_blcg_filterParam = $param;
        return $this;
    }
    
    public function blcg_getFilterParam()
    {
        return $this->_blcg_filterParam;
    }
    
    public function blcg_setExportInfos($infos)
    {
        $this->_blcg_exportInfos = $infos;
    }
    
    public function blcg_getStore()
    {
        if (method_exists($this, \'_getStore\')) {
            return $this->_getStore();
        }
        $storeId = (int)$this->getRequest()->getParam(Mage::helper(\'customgrid/config\')->getStoreParameter(\'store\'), 0);
        return Mage::app()->getStore($storeId);
    }
    
    public function blcg_getSaveParametersInSession()
    {
        return $this->_saveParametersInSession;
    }
    
    public function blcg_getSessionParamKey($name)
    {
        return $this->getId().$name;
    }
    
    public function blcg_getPage()
    {
        if ($this->getCollection() && $this->getCollection()->isLoaded()) {
            return $this->getCollection()->getCurPage();
        }
        return $this->getParam($this->getVarNamePage(), $this->_defaultPage);
    }
    
    public function blcg_getLimit()
    {
        return $this->getParam($this->getVarNameLimit(), $this->_defaultLimit);
    }
    
    public function blcg_getSort($checkExists=true)
    {
        $columnId = $this->getParam($this->getVarNameSort(), $this->_defaultSort);
        if (!$checkExists || (isset($this->_columns[$columnId]) && $this->_columns[$columnId]->getIndex())) {
            return $columnId;
        }
        return null;
    }
    
    public function blcg_getDir()
    {
        if ($this->blcg_getSort()) {
            return (strtolower($this->getParam($this->getVarNameDir(), $this->_defaultDir)) == \'desc\') ? \'desc\' : \'asc\';
        }
        return null;
    }
    
    public function blcg_getCollectionSize()
    {
        if ($this->getCollection()) {
            return $this->getCollection()->getSize();
        }
        return null;
    }
    
    public function blcg_addAdditionalAttribute(array $attribute)
    {
        $this->_blcg_additionalAttributes[] = $attribute;
        return $this;
    }
    
    public function blcg_setExportedCollection($collection)
    {
        $this->_blcg_exportedCollection = $collection;
        return $this;
    }
    
    public function blcg_holdPrepareCollection()
    {
        $this->_blcg_holdPrepareCollection = true;
        return $this;
    }
    
    public function blcg_finishPrepareCollection()
    {
        if ($this->getCollection()) {
            $this->_blcg_holdPrepareCollection = false;
            $this->_blcg_prepareEventsEnabled  = false;
            $this->_blcg_mustSelectAdditionalAttributes = true;
            $this->_prepareCollection();
        }
        return $this;
    }
    
    public function blcg_removeColumn($id)
    {
        if (array_key_exists($id, $this->_columns)) {
            unset($this->_columns[$id]);
            if ($this->_lastColumnId == $id) {
                $keys = array_keys($this->_columns);
                $this->_lastColumnId = array_pop($keys);
            }
        }
        return $this;
    }
    
    public function blcg_resetColumnsOrder()
    {
        $this->_columnsOrder = array();
        return $this;
    }
    
    public function blcg_addCollectionCallback($type, $callback, $params=array(), $addNative=true)
    {
        $this->_blcg_collectionCallbacks[$type][] = array(
            \'callback\'   => $callback,
            \'params\'     => $params,
            \'add_native\' => $addNative,
        );
        end($this->_blcg_collectionCallbacks[$type]);
        $key = key($this->_blcg_collectionCallbacks);
        reset($this->_blcg_collectionCallbacks);
        return $key;
    }
    
    public function blcg_removeCollectionCallback($type, $id)
    {
        if (isset($this->_blcg_collectionCallbacks[$type][$id])) {
            unset($this->_blcg_collectionCallbacks[$type][$id]);
        }
        return $this;
    }
    
    protected function _blcg_launchCollectionCallbacks($type, $params=array())
    {
        foreach ($this->_blcg_collectionCallbacks[$type] as $callback) {
            call_user_func_array(
                $callback[\'callback\'],
                array_merge(
                    array_values($callback[\'params\']),
                    ($callback[\'add_native\']? array_values($params) : array())
                )
            );
        }
        return $this;
    }
}';
    }
}