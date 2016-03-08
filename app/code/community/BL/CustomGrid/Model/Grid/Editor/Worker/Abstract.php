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

abstract class BL_CustomGrid_Model_Grid_Editor_Worker_Abstract extends BL_CustomGrid_Model_Worker_Abstract
{
    /**
     * Return the editor helper
     *
     * @return BL_CustomGrid_Helper_Editor
     */
    public function getEditorHelper()
    {
        return Mage::helper('customgrid/editor');
    }
    
    /**
     * Set the current grid editor model
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Abstract $editor Grid editor model to set as current
     * @return BL_CustomGrid_Model_Grid_Editor_Worker_Abstract
     */
    public function setEditor(BL_CustomGrid_Model_Grid_Editor_Abstract $editor)
    {
        return $this->setData('editor', $editor);
    }
    
    /**
     * Return the current grid editor model
     * 
     * @return BL_CustomGrid_Model_Grid_Editor_Abstract
     */
    public function getEditor()
    {
        if ((!$editor = $this->_getData('editor'))
            || (!$editor instanceof BL_CustomGrid_Model_Grid_Editor_Abstract)) {
            Mage::throwException('Invalid editor model');
        }
        return $editor;
    }
    
    /**
     * Wrap the the given callable into an internal callback model configured for the given action type and position
     * 
     * @param callable $callable Base callable
     * @param string $actionType Action type
     * @param string $position Callback position
     * @return BL_CustomGrid_Model_Grid_Editor_Callback
     */
    protected function _getInternalCallbackFromCallable(
        $callable,
        $actionType,
        $position = BL_CustomGrid_Model_Grid_Editor_Callback::POSITION_MAIN
    ) {
        return $this->getEditor()
            ->getCallbackManager()
            ->getCallbackFromCallable(
                $callable,
                $this->getType(),
                $actionType,
                $position,
                BL_CustomGrid_Model_Grid_Editor_Callback::PRIORITY_WORKER_INTERNAL
            );
    }
    
    /**
     * Return the cache data key for the callbacks corresponding to the given action and editor context
     * 
     * @param string $actionType Action type
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context (if any)
     * @return string
     */
    protected function _getActionCallbacksCacheKey(
        $actionType,
        BL_CustomGrid_Model_Grid_Editor_Context $context = null
    ) {
        return 'action_callbacks/' . $actionType . '/' . (!is_null($context) ? $context->getKey() : '_base_');
    }
    
    /**
     * Return all the usable callbacks for the given action type
     * 
     * @param string $actionType Action type
     * @param callable $mainCallable Callable to use for the main internal callback (if any)
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context (if any)
     * @param BL_CustomGrid_Model_Grid_Editor_Callback[] $defaultCallbacks Default callbacks, arranged by position
     * @param bool $cacheable Whether the whole callbacks list can be cached as-prepared for the next uses
     * @return BL_CustomGrid_Model_Grid_Editor_Callback[]
     */
    protected function _getActionCallbacks(
        $actionType,
        $mainCallable = null,
        BL_CustomGrid_Model_Grid_Editor_Context $context = null,
        array $defaultCallbacks = array(),
        $cacheable = false
    ) {
        $dataKey = $this->_getActionCallbacksCacheKey($actionType, $context);
        
        if ($cacheable && $this->hasData($dataKey)) {
            $callbacks = $this->getData($dataKey);
        } else {
            if (!is_null($mainCallable)) {
                $mainCallback = $this->_getInternalCallbackFromCallable($mainCallable, $actionType);
                $defaultCallbacks[BL_CustomGrid_Model_Grid_Editor_Callback::POSITION_MAIN][] = $mainCallback;
            }
            
            $callbacks = $this->getEditor()
                ->getCallbackManager()
                ->getWorkerActionSortedCallbacks($this->getType(), $actionType, $context, $defaultCallbacks);
            
            if ($cacheable) {
                $this->setData($dataKey, $callbacks);
            }
        }
        
        return $callbacks;
    }
    
    /**
     * Run the given action callbacks with the given available parameters
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Callback[] $callbacks Runnable callbacks (already sorted by position)
     * @param array $availableParams Available parameters
     * @return mixed
     */
    protected function _runActionCallbacks(array $callbacks, array $availableParams)
    {
        $returnedValue = null;
        
        foreach ($callbacks as $callback) {
            $returnedValue = $callback->call($availableParams, $returnedValue);
            
            if ($callback->getShouldStopAfter()) {
                break;
            }
        }
        
        return $returnedValue;
    }
    
    /**
     * Run the given callback-powered action with the given available parameters,
     * and return the result of the last ran main callback.
     * Some additional parameters will be automatically provided to the callables, on top of the given parameters :
     * - BL_CustomGrid_Model_Grid_Editor_Callback $editorCallback The parent callback model
     * - mixed $previousReturnedValue The value returned by the previous callback
     * 
     * @param string $actionType Action type
     * @param array $availableParams Available parameters
     * @param callable $mainCallable Callable to use for the main internal callback (if any)
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context (if any)
     * @param BL_CustomGrid_Model_Grid_Editor_Callback[] $defaultCallbacks Default callbacks, arranged by position
     * @param bool $cacheableCallbacks Whether the whole callbacks list can be cached as-prepared for next uses
     * @return mixed
     */
    protected function _runCallbackedAction(
        $actionType,
        array $availableParams,
        $mainCallable = null,
        BL_CustomGrid_Model_Grid_Editor_Context $context = null,
        array $defaultCallbacks = array(),
        $cacheableCallbacks = false
    ) {
        $mainReturnedValue = null;
        
        $callbacks = $this->_getActionCallbacks(
            $actionType,
            $mainCallable,
            $context,
            $defaultCallbacks,
            $cacheableCallbacks
        );
        
        if (isset($callbacks[BL_CustomGrid_Model_Grid_Editor_Callback::POSITION_BEFORE])) {
            $this->_runActionCallbacks(
                $callbacks[BL_CustomGrid_Model_Grid_Editor_Callback::POSITION_BEFORE],
                $availableParams
            );
        }
        if (isset($callbacks[BL_CustomGrid_Model_Grid_Editor_Callback::POSITION_MAIN])) {
            $mainReturnedValue = $this->_runActionCallbacks(
                $callbacks[BL_CustomGrid_Model_Grid_Editor_Callback::POSITION_MAIN],
                $availableParams
            );
        }
        if (isset($callbacks[BL_CustomGrid_Model_Grid_Editor_Callback::POSITION_AFTER])) {
            $availableParams['mainReturnedValue'] = $mainReturnedValue;
            
            $this->_runActionCallbacks(
                $callbacks[BL_CustomGrid_Model_Grid_Editor_Callback::POSITION_AFTER],
                $availableParams
            );
        }
        
        return $mainReturnedValue;
    }
}
