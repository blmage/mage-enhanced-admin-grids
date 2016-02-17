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

class BL_CustomGrid_Model_Grid_Editor_Callback_Manager extends BL_CustomGrid_Model_Grid_Editor_Worker_Abstract
{
    public function getType()
    {
        return BL_CustomGrid_Model_Grid_Editor_Abstract::WORKER_TYPE_CALLBACK_MANAGER;
    }
    
    /**
     * Return whether the given value is an instance of BL_CustomGrid_Model_Grid_Editor_Callback
     *
     * @param mixed $value Checked value
     * @return bool
     */
    protected function _isCallbackObject($value)
    {
        return ($value instanceof BL_CustomGrid_Model_Grid_Editor_Callback);
    }
    
    /**
     * Arrange the given callbacks array by worker and action types, then by position,
     * and return the result
     *
     * @param BL_CustomGrid_Model_Grid_Editor_Callback[] $callbacks Callbacks array to arrange
     * @return array
     */
    protected function _arrangeCallbacksArray(array $callbacks)
    {
        $arrangedCallbacks = array();
        $callbacks = array_filter($callbacks, array($this, '_isCallbackObject'));
        
        /** @var BL_CustomGrid_Model_Grid_Editor_Callback $callback */
        foreach ($callbacks as $callback) {
            $workerType = $callback->getWorkerType();
            $actionType = $callback->getActionType();
            $position   = $callback->getPosition();
            $arrangedCallbacks[$workerType][$actionType][$position][] = $callback;
        }
        
        return $arrangedCallbacks;
    }
    
    /**
     * Wrap the the given callable into a callback model configured with the given values
     *
     * @param callable $callable Base callable
     * @param string $workerType Worker type
     * @param string $actionType Action type
     * @param string $position Callback position
     * @param int $priority Callback priority (lower are executed first)
     * @param bool $shouldStopAfter Whether to stop the execution of the next callbacks of the same kind after this one
     * @return BL_CustomGrid_Model_Grid_Editor_Callback
     */
    public function getCallbackFromCallable(
        $callable,
        $workerType,
        $actionType,
        $position,
        $priority,
        $shouldStopAfter = false
    ) {
        return new BL_CustomGrid_Model_Grid_Editor_Callback(
            array(
                'callable'    => $callable,
                'worker_type' => $workerType,
                'action_type' => $actionType,
                'position'    => $position,
                'priority'    => (int) $priority,
                'should_stop_after' => (bool) $shouldStopAfter,
            )
        );
    }
    
    /**
     * Wrap the the given callable into a callback model configured with the given values,
     * that will be given priority over the default callback during the main phase of the given action type
     *
     * @param callable $callable Base callable
     * @param string $workerType Worker type
     * @param string $actionType Action type
     * @param bool $shouldStopAfter Whether to stop the execution of the next callbacks of the same kind after this one
     * @return BL_CustomGrid_Model_Grid_Editor_Callback
     */
    public function getInternalMainCallbackFromCallable(
        $callable,
        $workerType,
        $actionType,
        $shouldStopAfter = false
    ) {
        return $this->getCallbackFromCallable(
            $callable,
            $workerType,
            $actionType,
            BL_CustomGrid_Model_Grid_Editor_Callback::POSITION_MAIN,
            BL_CustomGrid_Model_Grid_Editor_Callback::PRIORITY_EDITOR_INTERNAL_HIGH,
            $shouldStopAfter
        );
    }
    
    /**
     * Return the usable base callbacks, arranged and optionally filtered by worker and action types
     *
     * @param string $workerType Worker type
     * @param string $actionType Action type
     * @return array
     */
    public function getBaseCallbacks($workerType = null, $actionType = null)
    {
        if (!$this->hasData('base_callbacks')) {
            $editor = $this->getEditor();
            $callbacks = $editor->getDefaultBaseCallbacks($this);
            $response  = new BL_CustomGrid_Object(array('callbacks' => array()));
            
            Mage::dispatchEvent(
                'blcg_grid_editor_base_callbacks',
                array(
                    'response'     => $response,
                    'type_model'   => $editor->getGridTypeModel(),
                    'editor_model' => $editor,
                )
            );
            
            $this->setData(
                'base_callbacks',
                $this->_arrangeCallbacksArray(array_merge($callbacks, (array) $response->getData('callbacks')))
            );
        }
        
        $dataKey = 'base_callbacks';
        
        if (!is_null($workerType)) {
            $dataKey .= '/' . $workerType . (!is_null($actionType) ? '/' . $actionType : '');
        }
        
        return $this->getDataSetDefault($dataKey, array());
    }
    
    /**
     * Return all the usable additional callbacks for the given editor context,
     * arranged and optionally filtered by worker and action types
     *
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @param string $workerType Worker type
     * @param string $actionType Action type
     * @return array
     */
    public function getContextAdditionalCallbacks(
        BL_CustomGrid_Model_Grid_Editor_Context $context,
        $workerType = null,
        $actionType = null
    ) {
        $dataKey = 'context_additional_callbacks/' . $context->getKey();
        
        if (!$this->hasData($dataKey)) {
            $editor = $this->getEditor();
            $editorCallbacks = $editor->getContextDefaultAdditionalCallbacks($context, $this);
            $columnCallbacks = array();
            
            if ($customColumn = $context->getGridColumn()->getCustomColumnModel()) {
                $columnCallbacks = $customColumn->getEditorContextAdditionalCallbacks($context, $this);
            }
            
            $response = new BL_CustomGrid_Object(array('callbacks' => array()));
            
            Mage::dispatchEvent(
                'blcg_grid_editor_additional_context_callbacks',
                array(
                    'response'       => $response,
                    'type_model'     => $editor->getGridTypeModel(),
                    'editor_model'   => $editor,
                    'editor_context' => $context,
                )
            );
            
            $this->setData(
                $dataKey,
                $this->_arrangeCallbacksArray(
                    array_merge(
                        $editorCallbacks,
                        $columnCallbacks,
                        (array) $response->getData('callbacks')
                    )
                )
            );
        }
        
        if (!is_null($workerType)) {
            $dataKey .= '/' . $workerType . (!is_null($actionType) ? '/' . $actionType : '');
        }
        
        return $this->getDataSetDefault($dataKey, array());
    }
    
    /**
     * Return the sorted callbacks for the given worker and action types, arranged by position (before / main / after).
     * The given additional callbacks will be registered and sorted along with the external callbacks defined for the
     * same positions.
     *
     * @param string $workerType Worker type
     * @param string $actionType Action type
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context (if any)
     * @param BL_CustomGrid_Model_Grid_Editor_Callback[] $additionalCallbacks Additional (internal) callbacks
     * @return array
     */
    public function getWorkerActionSortedCallbacks(
        $workerType,
        $actionType,
        BL_CustomGrid_Model_Grid_Editor_Context $context = null,
        array $additionalCallbacks = array()
    ) {
        $callbacks = array_merge_recursive(
            $this->getBaseCallbacks($workerType, $actionType),
            (!is_null($context) ? $this->getContextAdditionalCallbacks($context, $workerType, $actionType) : array()),
            $additionalCallbacks
        );
        
        foreach ($callbacks as $position => $positionCallbacks) {
            if (empty($positionCallbacks)) {
                unset($callbacks[$position]);
            } else {
                uasort($callbacks[$position], 'BL_CustomGrid_Model_Grid_Editor_Callback::sortCallbacks');
            }
        }
        
        return $callbacks;
    }
}
