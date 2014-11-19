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
    const GRID_REWRITE_CODE_VERSION = 1;
    
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
    
    protected function _getSession()
    {
        return Mage::getSingleton('customgrid/session');
    }
    
    protected function _getHelper()
    {
        return Mage::helper('customgrid');
    }
    
    protected function _getConfigHelper()
    {
        return Mage::helper('customgrid/config');
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
            $type  = explode('/', $blockType);
            $group = $type[0];
            $class = (!empty($type[1]) ? $type[1] : null);
            $node  = $this->_getConfig()->getNode('global/blocks/' . $group . '/rewrite/' . $class);
            
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
            
            if ($this->_getConfigHelper()->isExcludedGrid($grid->getBlockType(), $rewritingClassName)) {
                // Do not rewrite if now excluded
                $this->_excludedModels[] = $grid->getId();
                
            } elseif (!isset($this->_rewritedTypes[$grid->getBlockType()])) {
                // Generate and register our rewriting class (extending previous rewrite if existing)
                $rewriters = Mage::getSingleton('customgrid/grid_rewriter')->getEnabledRewriters(true);
                $blcgClass = false;
                $originalClass = ($rewritingClassName ? $rewritingClassName : $this->_getBlockClassName($group, $class));
                $rewriteErrors = array();
                
                foreach ($rewriters as $rewriter) {
                    try {
                        $blcgClass = $rewriter->rewriteGrid($originalClass, $grid->getBlockType());
                    } catch (Exception $e) {
                        $blcgClass = false;
                        $rewriteErrors[] = array('exception' => $e, 'rewriter' => $rewriter);
                    }
                    if ($blcgClass) {
                        break;
                    }
                }
                
                if ($blcgClass) {
                    foreach ($rewriteErrors as $error) {
                        if ($error['rewriter']->getDisplayErrorsIfSuccess()) {
                            $this->_getSession()->addError($error['exception']->getMessage());
                        }
                        if ($error['rewriter']->getLogErrorsIfSuccess()) {
                            Mage::logException($error['exception']);
                        }
                    }
                    
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
                                        <' . $class . '>' . $blcgClass . '</' . $class . '>
                                    </rewrite>
                                </' . $group . '>
                            </blocks>
                        </global>
                    </config>
                    ');
                    
                    $this->_getConfig()->extend($rewriteXml, true);
                    
                    if ($this->_getConfigHelper()->getForceGridRewrites()) {
                        // Put the rewriting class name in the config cache (should prevent some problems when the config gets overriden afterwards)
                        $this->_getConfig()->getBlockClassName($grid->getBlockType());
                    }
                    
                    // Remember current type is now rewrited
                    $this->_rewritedTypes[$grid->getBlockType()] = true;
                    
                } else {
                    foreach ($rewriteErrors as $error) {
                        if ($error['rewriter']->getDisplayErrors()) {
                            $this->_getSession()->addError($error['exception']->getMessage());
                        }
                        if ($error['rewriter']->getLogErrors()) {
                            Mage::logException($error['exception']);
                        }
                    }
                    
                    // Exclude failed rewrites
                    $this->_excludedModels[] = $grid->getId();
                    
                }
                
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
            $this->_additionalLayoutHandles = array_merge($this->_additionalLayoutHandles, $handle);
        } else {
            $this->_additionalLayoutHandles[] = $handle;
        }
    }
    
    public function beforeControllerActionLayoutLoad($observer)
    {
        if ($layout = $observer->getLayout()) {
            $layout->getUpdate()->addHandle(array_unique($this->_additionalLayoutHandles));
            
            if ($this->_getHelper()->isMageVersionLesserThan(1, 7)) {
                $layout->getUpdate()->addHandle('blcg_magento_version_to_16');
            } else {
                $layout->getUpdate()->addHandle('blcg_magento_version_from_17');
            }
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
            && ($grid instanceof Mage_Adminhtml_Block_Widget_Grid)
            && ($blockType = $grid->getType())) {
            if ($grid->getTemplate() == 'widget/grid.phtml') {
                // Get corresponding custom grid model, create a new one if needed (first time)
                $blockId  = $grid->getId();
                $newModel = false;
                
                if (is_null($model = $this->_getGridModel($blockType, $blockId, false, false))) {
                    // Initialize new model with request and grid values
                    if (isset($this->_originalRewrites[$blockType])) {
                        $rewritingClassName = $this->_originalRewrites[$blockType];
                    } else {
                        list(,, $rewritingClassName) = $this->_getBlockTypeInfos($blockType);
                    }
                    
                    if ($this->_getConfigHelper()->isExcludedGrid($blockType, $rewritingClassName)) {
                        return;
                    }
                    
                    $model = Mage::getModel('customgrid/grid')
                        ->setId(null)
                        ->setModuleName($this->getModuleName())
                        ->setControllerName($this->getControllerName())
                        ->setRewritingClassName($rewritingClassName);
                    
                    $this->_gridsCollection->addItem($model);
                    
                    // Remember this is a new model, we won't have to do further actions with it now
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
                    )->setChild(
                        'bl_custom_grid_grid_columns_filters',
                        $grid->getLayout()->createBlock('customgrid/widget_grid_columns_filters')
                            ->setGridBlock($grid)
                            ->setGridModel($model)
                            ->setIsNewGridModel($newModel)
                    );
                    
                    if ($messagesBlock = $grid->getLayout()->getBlock('customgrid.messages')) {
                        $grid->setMessagesBlock($messagesBlock);
                    } else {
                        $grid->setMessagesBlock($grid->getLayout()->createBlock('customgrid/messages'));
                    }
                    
                    // Replace grid template with our own one
                    $helper = $this->_getHelper();
                    
                    if ($helper->isMageVersionGreaterThan(1, 5)) {
                        $grid->setTemplate('bl/customgrid/widget/grid/16.phtml');
                    } elseif ($helper->isMageVersion15()) {
                        $grid->setTemplate('bl/customgrid/widget/grid/15.phtml');
                    } else {
                        $revision = $helper->getMageVersionRevision();
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
                    && !$model->getDisabled()) {
                    if ($this->_getHelper()->isRewritedGrid($grid)) {
                        // Add models to the grids here and not in the "before_to_html" event, because the latter is not called for export
                        $grid->blcg_setGridModel($model)->blcg_setTypeModel($model->getTypeModel());
                    } else {
                        // For some reason the grid was not rewrited, exclude it to prevent problems
                        $this->_getSession()->addError($this->_getHelper()->__('The "%s" grid was not rewrited', $blockType));
                        $this->_excludedModels[] = $model->getId();
                    }
                }
        }
    }
    
    public function beforeGridPrepareCollection($grid)
    {
        $blockType = $grid->getType();
        $blockId   = $grid->getId();
        
        if (!is_null($model = $this->_getGridModel($blockType, $blockId, true))
            && !$model->getDisabled()) {
            // Apply base default limit (as original one may not be found in the custom pagination values)
            $model->applyBaseDefaultLimitToGridBlock($grid);
            
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