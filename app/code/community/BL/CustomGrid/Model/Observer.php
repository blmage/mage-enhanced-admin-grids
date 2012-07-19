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

class BL_CustomGrid_Model_Observer
{
    /**
    * Current module name
    * 
    * @var string
    */
    protected $_moduleName       = null;
    /**
    * Current controller name
    * 
    * @var string
    */
    protected $_controllerName   = null;
    /**
    * Informations concerning block types, such as class name
    * 
    * @var array
    */
    protected $_blockTypeInfos   = array();
    /**
    * Blocks' original rewriting class names
    * 
    * @var array
    */
    protected $_originalRewrites = array();
    /**
    * Rewrited block types by code
    * 
    * @var array
    */
    protected $_rewritedTypes    = array();
    /**
    * New (created on current request) grid models by block type
    * 
    * @var array
    */
    protected $_newGridModels    = array();
    /**
    * Excluded models (rewrited grids that now/currently are excluded)
    * 
    * @var array
    */
    protected $_excludedModels   = array();
    /**
    * Collection of all grids models corresponding to current request
    * 
    * @var BL_CustomGrid_Model_Mysql4_Grid_Collection
    */
    protected $_gridsCollection  = null;
    /**
    * Additional layout handles to use at layout load
    */
    protected $_additionalLayoutHandles = array();
    
    /**
    * Return whether grids customization is allowed to current user
    * 
    * @return bool
    */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')
            ->isAllowed('system/customgrid/customization/use_columns');
    }
    
    protected function _getRequest()
    {
        return Mage::app()->getRequest();
    }   
    
    protected function _getConfig()
    {
        return Mage::app()->getConfig();
    }
    
    /**
    * Register a new grid block class extending another one, 
    * and add a bunch of new useful methods to it.
    * See BL_CustomGrid_Block_Grid_Rewrite for a commented version (currently no more updated)
    * 
    * @param string $className New grid class name
    * @param string $extends Grid class to extend
    * @return this
    */
    protected function _registerGridClass($className, $extends)
    {
        if (class_exists($className, false)
            || !class_exists($extends, true)) {
            return $this;
        }
        
        // @todo minify this code if this is useful
        eval('
        class ' . $className . ' extends ' . $extends . '
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
                \'before_set\'         => array(),
                \'after_set\'          => array(),
                \'before_export_load\' => array(),
                \'after_export_load\'  => array(),
            );
            private $_blcg_additionalAttributes = array();
            private $_blcg_mustSelectAdditionalAttributes   = false;
            
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
                    foreach ($this->_blcg_additionalAttributes as $attribute) {
                        $collection->joinAttribute(
                            $attribute[\'alias\'],
                            $attribute[\'attribute\'],
                            $attribute[\'bind\'],
                            $attribute[\'filter\'],
                            $attribute[\'join_type\'],
                            $attribute[\'store_id\']
                        );
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
                    return parent::_setFilterValues($data);
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
                    $this->_blcg_launchCollectionCallbacks(\'before_prepare\', array($this, $this->_collection, $this->_blcg_prepareEventsEnabled));
                    $return = parent::_prepareCollection();
                    $this->_blcg_launchCollectionCallbacks(\'after_prepare\', array($this, $this->_collection, $this->_blcg_prepareEventsEnabled));
                    Mage::getSingleton(\'customgrid/observer\')->afterGridPrepareCollection($this);
                } else {
                    $this->_blcg_launchCollectionCallbacks(\'before_prepare\', array($this, $this->_collection, $this->_blcg_prepareEventsEnabled));
                    $return = parent::_prepareCollection();
                    $this->_blcg_launchCollectionCallbacks(\'after_prepare\', array($this, $this->_collection, $this->_blcg_prepareEventsEnabled));
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
                    $page = $this->_blcg_gridModel->getGridBlockDefaultParamValue(\'page\', $page);
                }
                return parent::setDefaultPage($page);
            }
            
            public function setDefaultLimit($limit)
            {
                if (!is_null($this->_blcg_gridModel)) {
                    $limit = $this->_blcg_gridModel->getGridBlockDefaultParamValue(\'limit\', $limit);
                }
                return parent::setDefaultLimit($limit);
            }
            
            public function setDefaultSort($sort)
            {
                if (!is_null($this->_blcg_gridModel)) {
                    $sort = $this->_blcg_gridModel->getGridBlockDefaultParamValue(\'sort\', $sort);
                }
                return parent::setDefaultSort($sort);
            }
            
            public function setDefaultDir($dir)
            {
                if (!is_null($this->_blcg_gridModel)) {
                    $dir = $this->_blcg_gridModel->getGridBlockDefaultParamValue(\'dir\', $dir);
                }
                return parent::setDefaultDir($dir);
            }
            
            public function setDefaultFilter($filter)
            {
                if (!is_null($this->_blcg_gridModel)) {
                    $filter = $this->_blcg_gridModel->getGridBlockDefaultParamValue(\'filter\', $filter);
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
            
            public function blcg_setDefaultLimit($limit)
            {
                if (!is_null($this->_blcg_gridModel)) {
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
                        $this->_lastColumnId = array_pop(array_keys($this->_columns));
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
        }
        ');
        return $this;
    }
   
   /**
     * Retrieve block class name
     *
     * @param string $group Block group
     * @param string $class Block class
     * @return string
     */
    protected function _getBlockClassName($group, $class)
    {
        // Same behaviour as Mage_Core_Model_Config, but allow to avoid class names cache
        $config = $this->_getConfig()->getNode('global/blocks/' . $group);
        
        if (!empty($config)) {
            $className = $config->getClassName();
        }
        if (empty($className)) {
            $className = 'mage_' . $group . '_block';
        }
        if (!empty($class)) {
            $className .= '_' . $class;
        }
        
        return uc_words($className);
    }
    
    /**
    * Initialize some useful values from request
    * 
    * @param Mage_Core_Controller_Request_Http $request
    * @return this
    */
    protected function _initializeFromRequest($request)
    {
        $this->_moduleName = $request->getModuleName();
        $this->_controllerName = $request->getControllerName();
        return $this;
    }
    
    /**
    * Get current module name
    * 
    * @return string
    */
    public function getModuleName()
    {
        return $this->_moduleName;
    }
    
    /**
    * Return current controller name
    * 
    * @return string
    */
    public function getControllerName()
    {
        return $this->_controllerName;
    }
    
    /**
    * Retrieve some useful values from a block type
    * 
    * @param string $blockType
    * @return array
    */
    protected function _getBlockTypeInfos($blockType)
    {
        if (!isset($this->_blockTypeInfos[$blockType])) {
            $type = explode('/', $blockType);
            $group = $type[0];
            $class = (!empty($type[1]) ? $type[1] : null);
            
            $node = $this->_getConfig()->getNode('global/blocks/' . $group . '/rewrite/' . $class);
            if (is_object($node)) {
                $node = $node->asCanonicalArray();
                if (is_array($node) && count($node)) {
                    // Different rewrites in different modules lead to only one rewrite in config
                    $rewritingClassName = $node[0];
                } else {
                    $rewritingClassName = $node;
                }
            } else {
                $rewritingClassName = '';
            }
            $this->_blockTypeInfos[$blockType] = array($group, $class, $rewritingClassName);
        }
        return $this->_blockTypeInfos[$blockType];
    }
    
    /**
    * Rewrite a grid block, to add it some useful/needed methods
    * 
    * @param BL_CustomGrid_Model_Grid $grid
    * @return bool
    */
    protected function _rewriteGridBlock($grid)
    {
        // Get block infos
        list($group, $class, $rewritingClassName) = $this->_getBlockTypeInfos($grid->getBlockType());
            
        if ((!$rewritingClassName && ($grid->getRewritingClassName() == ''))
            || ($rewritingClassName == $grid->getRewritingClassName())) {
            // Grid model corresponds to current configuration
            
            if (Mage::helper('customgrid/config')->isExcludedGrid($grid->getBlockType(), $rewritingClassName)) {
                // Do not rewrite if now excluded
                $this->_excludedModels[] = $grid->getId();
            } elseif (!isset($this->_rewritedTypes[$grid->getBlockType()])) {
                // Register our rewriting class (extending previous rewrite if existing)
                $className = 'BL_CustomGrid_Block_Rewrite_' . uc_words($class);
                $extends   = ($rewritingClassName ? $rewritingClassName : $this->_getBlockClassName($group, $class));
                $this->_registerGridClass($className, $extends);
                
                if ($rewritingClassName) {
                    $this->_originalRewrites[$grid->getBlockType()] = $rewritingClassName;
                }
                
                // Register rewrite in config (this will also replace previous rewrite if existing)
                // This doesnt seem to affect Magento config cache in any way
                $rewriteXml = new Varien_Simplexml_Config();
                $rewriteXml->loadString('
                <config>
                    <global>
                        <blocks>
                            <' . $group . '>
                                <rewrite>
                                    <' . $class . '>' . $className . '</' . $class . '>
                                </rewrite>
                            </' . $group . '>
                        </blocks>
                    </global>
                </config>
                ');
                $this->_getConfig()->extend($rewriteXml, true);
                
                // Remember current type is now rewrited
                $this->_rewritedTypes[$grid->getBlockType()] = true;
            }
            
            return true;
        } else {
            return false;
        }
    }
    
    /**
    * Rewrite grids that may not be found with current request (as it does not correspond to),
    * but may be found to be currently exported with another module's common export action
    * 
    * @return BL_CustomGrid_Model_Grid
    */
    protected function _handleExportedGrid()
    {
        $request = $this->_getRequest();
        
        if (($gridId = $request->getParam('grid_id', null))
            && ($grid = Mage::getModel('customgrid/grid')->load($gridId))
            && $grid->getId()) {
            // A valid grid seems to have been passed as parameter
            if ($grid->isExportRequest($request)) {
                /*
                If we are on an export request, grid may not be rewrited by the common treatments,
                (because of not corresponding actions), so let's force its rewrite
                */
                $this->_rewriteGridBlock($grid);
                return $grid;
            }
        }
        
        return null;
    }
    
    /**
    * Rewrite all needed grids for current request
    * 
    * @param Varien_Event_Observer $observer
    */
    public function onControllerActionPreDispatch($observer)
    {
        $request = $this->_getRequest();
        $this->_initializeFromRequest($request);
        $this->_newGridModels = array();
        
        // Get grids corresponding to current request
        $this->_gridsCollection = Mage::getResourceModel('customgrid/grid_collection')
            ->addFieldToFilter('module_name', $this->getModuleName())
            ->addFieldToFilter('controller_name', $this->getControllerName())
            ->load();
        
        // Rewrite all known grids
        foreach ($this->_gridsCollection as $key => $grid) {
            if (!$this->_rewriteGridBlock($grid)) {
                // Remove not corresponding grids, to avoid using them later by confound
                $this->_gridsCollection->removeItemByKey($key);
            }
        }
        
        // Handle potentially exported grid
        if ($exportedGrid = $this->_handleExportedGrid()) {
            if (!$this->_gridsCollection->getItemById($exportedGrid->getId())) {
                $this->_gridsCollection->addItem($exportedGrid);
            }
        }
    }
    
    public function addAdditionalLayoutHandle($handle)
    {
        if (is_array($handle)) {
            $this->_additionalLayoutHandles = array_merge(
                $this->_additionalLayoutHandles,
                $handle
            );
        } else {
            $this->_additionalLayoutHandles[] = $handle;
        }
    }
    
    public function beforeControllerActionLayoutLoad($observer)
    {
        if ($layout = $observer->getLayout()) {
            $layout->getUpdate()->addHandle(array_unique($this->_additionalLayoutHandles));
        }
    }
    
    /**
    * Retrieve grid model by block type and layout ID
    * 
    * @param string $blockType Block type
    * @param string $blockId Block ID in layout
    * @param bool $noNew Whether no new model should be returned
    * @param bool $noExcluded Whether no excluded model should be returned
    * @return BL_CustomGrid_Model_Grid
    */
    protected function _getGridModel($blockType, $blockId, $noNew=false, $noExcluded=true)
    {
        $model = null;
                
        foreach ($this->_gridsCollection as $gridModel) {
            if ($gridModel->matchGridBlock($blockType, $blockId)) {
                if ((!$noNew || !isset($this->_newGridModels[$blockType])
                     || !isset($this->_newGridModels[$blockType][$blockType.'_'.$blockId]))
                    && (!$noExcluded || !in_array($gridModel->getId(), $this->_excludedModels))) {
                    $model = $gridModel;
                }
                break;
            }
        }
        
        return $model;
    }
    
    /**
    * Apply some needed changes to grid blocks before their HTML output
    * 
    * @param Varien_Event_Observer $observer
    */
    public function beforeBlockToHtml($observer)
    {
        if (($grid = $observer->getEvent()->getBlock())
            && ($grid instanceof Mage_Adminhtml_Block_Widget_Grid)) {
            if ($grid->getTemplate() == 'widget/grid.phtml') {
                // Get corresponding custom grid model, create a new one if needed (first time)
                $blockType = $grid->getType();
                $blockId   = $grid->getId();
                $newModel  = false;
                
                if (is_null($model = $this->_getGridModel($blockType, $blockId, false, false))) {
                    // Initialize new model with request and grid values
                    if (isset($this->_originalRewrites[$blockType])) {
                        $rewritingClassName = $this->_originalRewrites[$blockType];
                    } else {
                        list(,, $rewritingClassName) = $this->_getBlockTypeInfos($blockType);
                    }
                    
                    if (Mage::helper('customgrid/config')->isExcludedGrid($blockType, $rewritingClassName)) {
                        return;
                    }
                    
                    $model = Mage::getModel('customgrid/grid')
                        ->setId(null)
                        ->setModuleName($this->getModuleName())
                        ->setControllerName($this->getControllerName())
                        ->setRewritingClassName($rewritingClassName);
                    
                    $this->_gridsCollection->addItem($model);
                    
                    // Remember this is a new model, we wont have to do further actions with it now 
                    // (as it is not yet initialized, etc...)
                    if (!isset($this->_newGridModels[$blockType])) {
                        $this->_newGridModels[$blockType] = array();
                    }
                    $this->_newGridModels[$blockType][$blockType.'_'.$blockId] = true;
                    $newModel = true;
                }
                
                // Add columns config block directly into the grid
                if (!$model->getDisabled()
                    && !in_array($model->getId(), $this->_excludedModels)) {
                    $grid->setChild(
                        'bl_custom_grid_grid_columns_config',
                        $grid->getLayout()->createBlock('customgrid/widget_grid_columns_config')
                            ->setGridBlock($grid)
                            ->setGridModel($model)
                    )->setChild(
                        'bl_custom_grid_grid_columns_editor',
                        $grid->getLayout()->createBlock('customgrid/widget_grid_columns_editor')
                            ->setGridBlock($grid)
                            ->setGridModel($model)
                            ->setIsNewGridModel($newModel)
                    );
                    
                    // Replace grid template with our own one
                    if (Mage::helper('customgrid')->isMageVersion16()) {
                        $grid->setTemplate('bl/customgrid/widget/grid/16.phtml');
                    } elseif (Mage::helper('customgrid')->isMageVersion15()) {
                        $grid->setTemplate('bl/customgrid/widget/grid/15.phtml');
                    } else {
                        $revision = Mage::helper('customgrid')->getMageVersionRevision();
                        $grid->setTemplate('bl/customgrid/widget/grid/14'.intval($revision).'.phtml');
                    }
                }
            } // Don't do anything if it's not the base template, as it would be unreliable
        }
    }
    
    /**
    * Apply some needed changes to grid blocks before their layout preparation
    * 
    * @param Varien_Event_Observer $observer
    */
    public function beforeBlockPrepareLayout($observer)
    {
        if (($grid = $observer->getEvent()->getBlock())
            && ($grid instanceof Mage_Adminhtml_Block_Widget_Grid)) {
                $blockType = $grid->getType();
                $blockId   = $grid->getId();
                
                if (($model = $this->_getGridModel($blockType, $blockId, true))
                    && (!$model->getDisabled())) {
                    // Add type model here and not in before_to_html event, because this one is not called for export
                    $grid->blcg_setGridModel($model)->blcg_setTypeModel($model->getTypeModel());
                }
        }
    }
    
    public function beforeGridPrepareCollection($grid)
    {
        $blockType = $grid->getType();
        $blockId   = $grid->getId();
        
        if (!is_null($model = $this->_getGridModel($blockType, $blockId, true))
            && !$model->getDisabled()) {
            if ($model->checkUserActionPermission(BL_CustomGrid_Model_Grid::GRID_ACTION_USE_DEFAULT_PARAMS)) {
                // Apply custom default values to grid block
                $model->applyDefaultToGridBlock($grid);
            }
            // Ask grid to currently not do further actions that could lead into getting no items
            $grid->blcg_holdPrepareCollection();
        }
    }
    
    public function afterGridPrepareCollection(Mage_Adminhtml_Block_Widget_Grid $grid)
    {
        $blockType = $grid->getType();
        $blockId   = $grid->getId();
        
        if (!is_null($model = $this->_getGridModel($blockType, $blockId, true))
            && !$model->getDisabled()) {
            if ($grid->getCollection()) {
                // Check grid model against grid columns
                $grid->getCollection()->load();
                $applyFromCollection = $model->checkColumnsAgainstGridBlock($grid);
                
                if ($model->checkUserActionPermission(BL_CustomGrid_Model_Grid::GRID_ACTION_USE_CUSTOMIZED_COLUMNS)) {
                    // Apply it to grid block (only apply from collection if it could be checked)
                    $model->applyColumnsToGridBlock($grid, $applyFromCollection);
                }
                
                // Finish to prepare grid collection
                $grid->blcg_finishPrepareCollection();
            } else {
                // If grid has no collection, check and apply directly
                $model->checkColumnsAgainstGridBlock($grid);
                
                if ($model->checkUserActionPermission(BL_CustomGrid_Model_Grid::GRID_ACTION_USE_CUSTOMIZED_COLUMNS)) {
                    $model->applyColumnsToGridBlock($grid, false);
                }
            }
        }
    }
}
