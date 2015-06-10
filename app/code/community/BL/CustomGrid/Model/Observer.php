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
 * @copyright  Copyright (c) 2015 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_Observer extends BL_CustomGrid_Object
{
    /**
     * If the filter value in the current request equals to this constant, it must be nullified.
     * Used to reapply default filter.
     * 
     * @var string
     */
    const GRID_FILTER_RESET_REQUEST_VALUE = '_blcg_reset';
    
    /**
     * Return base configuration object
     *
     * @return Mage_Core_Model_Config
     */
    protected function _getMageConfig()
    {
        return Mage::app()->getConfig();
    }
    
    /**
     * Return current request object
     *
     * @return Mage_Core_Controller_Request_Http
     */
    protected function _getRequest()
    {
        return Mage::app()->getRequest();
    }
    
    /**
     * Return base helper
     * 
     * @return BL_CustomGrid_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('customgrid');
    }
    
    /**
     * Return block class name for given config group and class
     *
     * @param string $configGroup Config block group
     * @param string $configClass Config block class
     * @return string
     */
    public function getBlockClassName($configGroup, $configClass)
    {
        // Same behaviour as Mage_Core_Model_Config, but avoids class names cache
        $configNode = $this->_getMageConfig()->getNode('global/blocks/' . $configGroup);
        
        if (!empty($configNode)) {
            $className = $configNode->getClassName();
        }
        if (empty($className)) {
            $className = 'mage_' . $configGroup . '_block';
        }
        if (!empty($configClass)) {
            $className .= '_' . $configClass;
        }
        
        return uc_words($className);
    }
    
    /**
     * Return block group, block class and rewriting class name for given block type
     *
     * @param string $blockType Block type
     * @return array
     */
    public function getBlockTypeInfos($blockType)
    {
        $dataKey = 'block_type_infos/' . $blockType;
        
        if (!$this->hasData($dataKey)) {
            $typeParts   = explode('/', $blockType);
            $configGroup = $typeParts[0];
            $configClass = (!empty($typeParts[1]) ? $typeParts[1] : null);
            $configPath  = 'global/blocks/' . $configGroup . '/rewrite/' . $configClass;
            $rewriteNode = $this->_getMageConfig()->getNode($configPath);
            $rewritingClassName = '';
            
            if (!empty($rewriteNode)) {
                $rewriteValues = $rewriteNode->asCanonicalArray();
                
                if (is_array($rewriteValues) && !empty($rewriteValues)) {
                    // Different rewrites in different modules lead to only one rewrite in config
                    $rewritingClassName = array_shift($rewriteValues);
                } elseif (is_string($rewriteValues)) {
                    $rewritingClassName = $rewriteValues;
                }
            }
            
            $this->setData($dataKey, array($configGroup, $configClass, $rewritingClassName));
        }
        
        return $this->getData($dataKey);
    }
    
    /**
     * Return a collection of grid models matching the current request
     * 
     * @return BL_CustomGrid_Model_Mysql4_Grid_Collection
     */
    public function getGridModelsCollection()
    {
        if (!$this->hasData('grid_models_collection')) {
            if (($moduleName = $this->_getData('module_name'))
                && ($controllerName = $this->_getData('controller_name'))) {
                /**
                 * @var $collection BL_CustomGrid_Model_Mysql4_Grid_Collection
                 */
                $collection = Mage::getResourceModel('customgrid/grid_collection');
                
                $collection->addFieldToFilter('module_name', $moduleName)
                    ->addFieldToFilter('controller_name', $controllerName)
                    ->load();
                
                $this->setData('grid_models_collection', $collection);
            }
        }
        return $this->_getData('grid_models_collection');
    }
    
    /**
     * Return grid model for given block type and block ID (assuming it corresponds to the current request)
     *
     * @param string $blockType Grid block type
     * @param string $blockId Grid block ID
     * @param bool $exceptExcluded Whether null should be returned if a grid model is found but is excluded
     * @return BL_CustomGrid_Model_Grid|null
     */
    public function getGridModel($blockType, $blockId, $exceptExcluded = true)
    {
        $matchingGridModel = null;
        
        foreach ($this->getGridModelsCollection() as $gridModel) {
            /** @var $gridModel BL_CustomGrid_Model_Grid */
            if ($gridModel->matchGridBlock($blockType, $blockId)) {
                if (!$exceptExcluded || !$this->isExcludedGridModel($gridModel)) {
                    $matchingGridModel = $gridModel;
                }
                break;
            }
        }
        
        return $matchingGridModel;
    }
    
    /**
     * Return whether given grid model is excluded
     * 
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @return bool
     */
    public function isExcludedGridModel(BL_CustomGrid_Model_Grid $gridModel)
    {
        $dataKey = 'excluded_grid_models/' . $gridModel->getId();
        
        if (!$this->hasData($dataKey)) {
            /** @var $configHelper BL_CustomGrid_Helper_Config */
            $configHelper = Mage::helper('customgrid/config');
            
            $this->setData(
                $dataKey,
                $configHelper->isExcludedGridBlock($gridModel->getBlockType(), $gridModel->getRewritingClassName())
            );
        }
        
        return $this->getData($dataKey);
    }
    
    /**
     * Add the given grid model to the list of excluded grid models
     * 
     * @param BL_CustomGrid_Model_Grid $gridModel Excluded grid model
     * @return BL_CustomGrid_Model_Observer
     */
    public function addExcludedGridModel(BL_CustomGrid_Model_Grid $gridModel)
    {
        return $this->setData('excluded_grid_models/' . $gridModel->getId(), true);
    }
    
    /**
     * Return whether given grid model is obsolete
     * (ie the corresponding grid block is implemented by another class than the one the grid model was created with)
     * 
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @return bool
     */
    public function isObsoleteGridModel(BL_CustomGrid_Model_Grid $gridModel)
    {
        $dataKey = 'obsolete_grid_models/' . $gridModel->getId();
        
        if (!$this->hasData($dataKey)) {
            $blockType = $gridModel->getBlockType();
            $modelRewritingClass = $gridModel->getRewritingClassName();
            list(,, $blockRewritingClass) = $this->getBlockTypeInfos($blockType);
            
            $this->setData(
                $dataKey,
                !((!$blockRewritingClass && !$modelRewritingClass) || ($blockRewritingClass == $modelRewritingClass))
            );
        }
        
        return $this->getData($dataKey);  
    }
    
    /**
     * Return whether given block type is rewrited by the extension
     * 
     * @param string $blockType Block type
     * @return bool
     */
    public function isRewritedBlockType($blockType)
    {
        return (bool) $this->getData('rewrited_block_types/' . $blockType); 
    }
    
    /**
     * Add the given block type to the list of rewrited block types
     * 
     * @param string $blockType Rewrited block type
     * @return BL_CustomGrid_Model_Observer
     */
    public function addRewritedBlockType($blockType)
    {
        return $this->setData('rewrited_block_types/' . $blockType, true);
    }
    
    /**
     * Handle grid block rewrite errors, by displaying and/or logging them depending on the current config
     *
     * @param array $rewriteErrors Rewrite errors
     * @param bool $isSuccess Whether the rewrite was successfull
     */
    protected function _handleGridBlockRewriteErrors(array $rewriteErrors, $isSuccess)
    {
        foreach ($rewriteErrors as $error) {
            if (isset($error['rewriter']) && isset($error['exception'])) {
                /** @var $rewriter BL_CustomGrid_Model_Grid_Rewriter_Abstract */
                $rewriter  = $error['rewriter'];
                /** @var $exception Exception */
                $exception = $error['exception'];
                
                if (($isSuccess && $rewriter->getDisplayErrorsIfSuccess())
                    || (!$isSuccess && $rewriter->getDisplayErrors())) {
                    /** @var $session BL_CustomGrid_Model_Session */
                    $session = Mage::getSingleton('customgrid/session');
                    $session->addError($exception->getMessage());
                }
                if (($isSuccess && $rewriter->getLogErrorsIfSuccess())
                    || (!$isSuccess && $rewriter->getLogErrors())) {
                    Mage::logException($exception);
                }
            }
        }
    }
    
    /**
     * Rewrite given grid block type with an own auto-generated extending class, improving existing features
     *
     * @param string $blockType Grid block type
     * @return bool Whether rewrite succeeded
     */
    protected function _rewriteGridBlock($blockType)
    {
        $isSuccess = true;
        
        if (!$this->isRewritedBlockType($blockType)) {
            list($configGroup, $configClass, $rewritingClassName) = $this->getBlockTypeInfos($blockType);
            $blcgClassName = false;
            
            /** @var $rewritersConfig BL_CustomGrid_Model_Grid_Rewriter_Config */
            $rewritersConfig = Mage::getSingleton('customgrid/grid_rewriter_config');
            $rewriters = $rewritersConfig->getEnabledRewriters(true);
            $rewriteErrors = array();
            
            if (!$originalClassName = $rewritingClassName) {
                $originalClassName = $this->getBlockClassName($configGroup, $configClass);
            }
            
            foreach ($rewriters as $rewriter) {
                /** @var $rewriter BL_CustomGrid_Model_Grid_Rewriter_Abstract */
                try {
                    $blcgClassName = $rewriter->rewriteGrid($originalClassName, $blockType);
                } catch (Exception $e) {
                    $blcgClassName = false;
                    $rewriteErrors[] = array('exception' => $e, 'rewriter' => $rewriter);
                }
                if ($blcgClassName) {
                    break;
                }
            }
            
            if ($blcgClassName) {
                $this->_handleGridBlockRewriteErrors($rewriteErrors, true);
                
                if ($rewritingClassName) {
                    $this->setData('original_rewrites/' . $blockType, $rewritingClassName);
                }
                
                // Register rewrite in config (this will also replace previous rewrite if existing)
                $rewriteXml = new Varien_Simplexml_Config();
                
                $rewriteXml->loadString(
                    '<config>'
                    . '<global>'
                    . '<blocks>'
                    . '<' . $configGroup . '>'
                    . '<rewrite>'
                    . '<' . $configClass . '>' . $blcgClassName . '</' . $configClass . '>'
                    . '</rewrite>'
                    . '</' . $configGroup . '>'
                    . '</blocks>'
                    . '</global>'
                    . '</config>'
                );
                
                $this->_getMageConfig()->extend($rewriteXml, true);
                $this->addRewritedBlockType($blockType);
                
            } else {
                $this->_handleGridBlockRewriteErrors($rewriteErrors, false);
                $isSuccess = false;
            }
        }
        
        return $isSuccess;
    }
    
    /**
     * If one does exist, rewrite the grid block type that may not correspond to the current request,
     * but whom the results are currently exported, and return the corresponding grid model
     *
     * @return BL_CustomGrid_Model_Grid|null
     */
    protected function _rewriteExportedGridBlock()
    {
        $request = $this->_getRequest();
        
        if ((!$gridId = $request->getParam('grid_id', null))
            /** @var $gridModel BL_CustomGrid_Model_Grid */
            || (!$gridModel = Mage::getModel('customgrid/grid')->load($gridId))
            || !$gridModel->getId()
            || $gridModel->getDisabled()
            || $this->isExcludedGridModel($gridModel)
            || !$gridModel->getExporter()->isExportRequest($request)
            || !$this->_rewriteGridBlock($gridModel->getBlockType())) {
            $gridModel = null;
        }
        
        return $gridModel;
    }
    
    /**
     * Register given layout handles, they will be added to the layout update upon layout load
     * 
     * @param string|array $layoutHandles Additional layout handles
     * @return BL_CustomGrid_Model_Observer
     */
    public function registerAdditionalLayoutHandles(array $layoutHandles)
    {
        return $this->appendData('additional_layout_handles', $layoutHandles);
    }
    
    /**
     * Callback for the "controller_action_predispatch" event observer
     * Initialize the grid models corresponding to the current request, rewrite the corresponding grid blocks
     *
     * @param Varien_Event_Observer $observer
     */
    public function onControllerActionPreDispatch(Varien_Event_Observer $observer)
    {
        $request = $this->_getRequest();
        $this->setData('module_name', $request->getModuleName());
        $this->setData('controller_name', $request->getControllerName());
        $gridModelsCollection = $this->getGridModelsCollection();
        
        foreach ($gridModelsCollection as $gridModel) {
            /** @var $gridModel BL_CustomGrid_Model_Grid */
            if ($this->isObsoleteGridModel($gridModel)) {
                // Remove obsolete grid models from the collection to avoid them later being used by confound
                $gridModelsCollection->removeItemByKey($gridModel->getId());
            } elseif (!$this->isExcludedGridModel($gridModel)) {
                // Exclude grid models that should not be used for any reason
                if ($gridModel->getDisabled()
                    || !$this->_rewriteGridBlock($gridModel->getBlockType())) {
                    $this->addExcludedGridModel($gridModel);
                }
            }
        }
        
        if ($gridModel = $this->_rewriteExportedGridBlock()) {
            if (!$gridModelsCollection->getItemById($gridModel->getId())) {
                $gridModelsCollection->addItem($gridModel);
            }
        }
    }
    
    /**
     * Callback for the "controller_action_layout_load_before" event observer
     * Add additional layout handles
     * 
     * @param Varien_Event_Observer $observer
     */
    public function beforeControllerActionLayoutLoad(Varien_Event_Observer $observer)
    {
        if ($layout = $observer->getLayout()) {
            /**
             * @var $layout Mage_Core_Model_Layout
             */
            $layoutHandles = $this->getDataSetDefault('additional_layout_handles', array());
            
            if ($this->_getHelper()->isMageVersionLesserThan(1, 7)) {
                $layoutHandles[] = 'blcg_magento_version_to_16';
            } else {
                $layoutHandles[] = 'blcg_magento_version_from_17';
            }
            
            $layout->getUpdate()->addHandle(array_unique($layoutHandles));
        }
    }
    
    /**
     * Callback for the "core_block_abstract_prepare_layout_before" event observer
     * Set the corresponding grid and type models to the corresponding rewrited grid blocks
     *
     * @param Varien_Event_Observer $observer
     */
    public function beforeBlockPrepareLayout(Varien_Event_Observer $observer)
    {
        if (($gridBlock = $observer->getEvent()->getBlock())
            && ($gridBlock instanceof Mage_Adminhtml_Block_Widget_Grid)) {
            $blockType = $gridBlock->getType();
            $blockId   = $gridBlock->getId();
            
            if ($gridModel = $this->getGridModel($blockType, $blockId)) {
                if ($this->_getHelper()->isRewritedGridBlock($gridBlock)) {
                    $gridBlock->blcg_setGridModel($gridModel);
                    $gridBlock->blcg_setTypeModel($gridModel->getTypeModel());
                } else {
                    // For some reason the grid was not rewrited, exclude it to prevent possible problems
                    $this->addExcludedGridModel($gridModel);
                }
            }
        }
    }
    
    /**
     * Prepare the given grid block, by setting our own children blocks, rearranging the filter buttons,
     * and applying our corresponding custom template
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param bool $isNewGridModel Whether the given grid model has just been created
     */
    protected function _prepareGridBlock(
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        $isNewGridModel
    ) {
        $helper = $this->_getHelper();
        $layout = $gridBlock->getLayout();
        
        // Apply main blocks
        
        /** @var $configBlock BL_CustomGrid_Block_Widget_Grid_Config */
        $configBlock  = $layout->createBlock('customgrid/widget_grid_config');
        /** @var $editorBlock BL_CustomGrid_Block_Widget_Grid_Columns_Editor */
        $editorBlock  = $layout->createBlock('customgrid/widget_grid_columns_editor');
        /** @var $filtersBlock BL_CustomGrid_Block_Widget_Grid_Columns_Filters */
        $filtersBlock = $layout->createBlock('customgrid/widget_grid_columns_filters');
        
        $gridBlock->setChild(
            'blcg_grid_config',
            $configBlock->setGridBlock($gridBlock)
                ->setGridModel($gridModel)
                ->setIsNewGridModel($isNewGridModel)
        );
        
        $gridBlock->setChild(
            'blcg_grid_columns_editor',
            $editorBlock->setGridBlock($gridBlock)
                ->setGridModel($gridModel)
                ->setIsNewGridModel($isNewGridModel)
        );
        
        $gridBlock->setChild(
            'blcg_grid_columns_filters',
            $filtersBlock->setGridBlock($gridBlock)
                ->setGridModel($gridModel)
                ->setIsNewGridModel($isNewGridModel)
        );
        
        if (!$isNewGridModel) {
            // Rearrange filter buttons
            
            /** @var $filterButtonsList Mage_Core_Block_Text_List */
            $filterButtonsList   = $layout->createBlock('core/text_list');
            $resetFilterButton   = $gridBlock->getChild('reset_filter_button');
            $defaultFilterButton = $layout->createBlock('customgrid/widget_grid_button_default_filter_reapply');
            /** @var $defaultFilterButton BL_CustomGrid_Block_Widget_Grid_Button_Default_Filter_Reapply */
            $defaultFilterButton->setGridBlock($gridBlock)->setGridModel($gridModel);
            
            if (!$gridModel->getHideFilterResetButton() && $resetFilterButton) {
                $filterButtonsList->append($resetFilterButton);
            }
            
            $filterButtonsList->append($defaultFilterButton);
            $gridBlock->setChild('reset_filter_button', $filterButtonsList);
        }
        
        // Apply custom template
        if ($helper->isMageVersionGreaterThan(1, 5)) {
            $gridBlock->setTemplate('bl/customgrid/widget/grid/16.phtml');
        } elseif ($helper->isMageVersion15()) {
            $gridBlock->setTemplate('bl/customgrid/widget/grid/15.phtml');
        } else {
            $revision = $helper->getMageVersionRevision();
            $gridBlock->setTemplate('bl/customgrid/widget/grid/14' . ((int) $revision) . '.phtml');
        }
    }
    
    /**
     * Handle the given output grid block
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Output grid block
     */
    protected function _handleOutputGridBlock(Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        $blockId   = $gridBlock->getId();
        $blockType = $gridBlock->getType();
        $isNewGridModel = false;
        
        if (is_null($gridModel = $this->getGridModel($blockType, $blockId, false))) {
            /** @var $configHelper BL_CustomGrid_Helper_Config */
            $configHelper = Mage::helper('customgrid/config');
            
            if (!$rewritingClassName = $this->getData('original_rewrites/' . $blockType)) {
                list(,, $rewritingClassName) = $this->getBlockTypeInfos($blockType);
            }
            if ($configHelper->isExcludedGridBlock($blockType, $rewritingClassName)) {
                return;
            }
            
            /** @var $gridModel BL_CustomGrid_Model_Grid */
            $gridModel = Mage::getModel('customgrid/grid');
            $gridModel->setId(null)
                ->setModuleName($this->getModuleName())
                ->setControllerName($this->getControllerName())
                ->setRewritingClassName($rewritingClassName);
            
            $isNewGridModel = true;
        }
        
        if (!$gridModel->getDisabled() && !$this->isExcludedGridModel($gridModel)) {
            $this->_prepareGridBlock($gridBlock, $gridModel, $isNewGridModel);
        }
    }
    
    /**
     * Callback for the "core_block_abstract_to_html_before" event observer
     * Create new grid models when necessary, set own sub blocks and custom template to the rewrited grid blocks
     *
     * @param Varien_Event_Observer $observer
     */
    public function beforeBlockToHtml(Varien_Event_Observer $observer)
    {
        if (($gridBlock = $observer->getEvent()->getBlock())
            && ($gridBlock instanceof Mage_Adminhtml_Block_Widget_Grid)
            /** @var $gridBlock Mage_Adminhtml_Block_Widget_Grid */
            && ($gridBlock->getTemplate() == 'widget/grid.phtml')
            && $gridBlock->getType()) {
            $this->_handleOutputGridBlock($gridBlock);
        }
    }
    
    /**
     * Callback for the "core_block_abstract_to_html_after" event observer
     * For Ajax requests, display our messages block at the end of the first output grid block (rewrited or not)
     * This ensures that all the error messages are always available to the user as soon as possible
     * (especially, when messages were added because a grid block could not be rewrited)
     * 
     * @param Varien_Event_Observer $observer
     */
    public function afterBlockToHtml(Varien_Event_Observer $observer)
    {
        if ($this->_getHelper()->isAjaxRequest()
            && !$this->hasData('has_output_ajax_messages_block')
            && ($transport = $observer->getEvent()->getTransport())
            && ($gridBlock = $observer->getEvent()->getBlock())
            && ($gridBlock instanceof Mage_Adminhtml_Block_Widget_Grid)) {
            /** @var $gridBlock Mage_Adminhtml_Block_Widget_Grid */
            $layout = $gridBlock->getLayout();
            
            if (!$messagesBlock = $layout->getBlock('blcg.messages')) {
                /** @var $messagesBlock BL_CustomGrid_Block_Messages */
                $messagesBlock = $layout->createBlock('customgrid/messages');
            }
            
            $messagesBlock->setIsAjaxMode(true);
            $transport->setHtml($transport->getHtml() . $messagesBlock->toHtml());
            $this->setData('has_output_ajax_messages_block', true);
        }
    }
     
    /**
     * "Callback" to use just before the call to Mage_Adminhtml_Block_Widget_Grid::_prepareCollection()
     * Apply default values to the given grid block, and put the collection preparation on hold
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     */
    public function beforeGridPrepareCollection(Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        $blockType = $gridBlock->getType();
        $blockId   = $gridBlock->getId();
        
        if ($gridModel = $this->getGridModel($blockType, $blockId)) {
            $request = $this->_getRequest();
            
            if ($request->getParam($gridBlock->getVarNameFilter()) == self::GRID_FILTER_RESET_REQUEST_VALUE) {
                $request->setParam($gridBlock->getVarNameFilter(), null);
            }
            
            $gridModel->getApplier()->applyBaseDefaultLimitToGridBlock($gridBlock);
            
            if ($gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid_Sentry::ACTION_USE_DEFAULT_PARAMS)) {
                $gridModel->getApplier()->applyDefaultsToGridBlock($gridBlock);
            }
            
            /**
             * Put the collection preparation on hold, this will prevent any filter / page / limit to be applied,
             * making it less more likely that not any result will be found (unless no results exist at all)
             */
            $gridBlock->blcg_holdPrepareCollection();
        }
    }
    
    /**
     * "Callback" to use just after the call to Mage_Adminhtml_Block_Widget_Grid::_prepareCollection()
     * Check potential columns changes, and apply columns customizations for the given grid block
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     */
    public function afterGridPrepareCollection(Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        $blockType = $gridBlock->getType();
        $blockId   = $gridBlock->getId();
        
        if ($gridModel = $this->getGridModel($blockType, $blockId)) {
            $canUseCustomizedColumns = $gridModel->checkUserActionPermission(
                BL_CustomGrid_Model_Grid_Sentry::ACTION_USE_CUSTOMIZED_COLUMNS
            );
            
            if ($collection = $gridBlock->getCollection()) {
                $collection->setPageSize(1)->setCurPage(1)->load();
                $applyFromCollection = $gridModel->getAbsorber()->checkGridModelAgainstGridBlock($gridBlock);
                
                if ($canUseCustomizedColumns) {
                    $gridModel->getApplier()->applyGridModelColumnsToGridBlock($gridBlock, $applyFromCollection);
                }
                
                $gridBlock->blcg_finishPrepareCollection();
            } else {
                $gridModel->getAbsorber()->checkGridModelAgainstGridBlock($gridBlock);
                
                if ($canUseCustomizedColumns) {
                    $gridModel->getApplier()->applyGridModelColumnsToGridBlock($gridBlock, false);
                }
            }
        }
    }
}
