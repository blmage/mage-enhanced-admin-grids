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

/**
 * @method string|null getWorkerType() Return the type of worker holding the action to which this callback is attached
 * @method string|null getActionType() Return the type of action to which this callback is attached
 * @method string|null getPosition() Return the position of this callback
 * @method BL_CustomGrid_Model_Grid_Editor_Callback setShouldStopAfter(bool $flag) 
 */
class BL_CustomGrid_Model_Grid_Editor_Callback extends BL_CustomGrid_Object
{
    const POSITION_BEFORE = 'before';
    const POSITION_MAIN   = 'main';
    const POSITION_AFTER  = 'after';
    
    /**
     * Priority used by the internal callbacks defined by the worker models
     */
    const PRIORITY_WORKER_INTERNAL = 300000;
    
    /**
     * Convenient priorities for callbacks defined by the editor models
     */
    const PRIORITY_EDITOR_INTERNAL_HIGH = 200000;
    const PRIORITY_EDITOR_INTERNAL_LOW  = 400000;
    
    /**
     * Convenient priorities for callbacks defined by external entities
     */
    const PRIORITY_EXTERNAL_HIGHER = 100000;
    const PRIORITY_EXTERNAL_LOWER  = 500000;
    
    /**
     * Cache holding the specifications of the callable parameters retrieved by Reflection
     * 
     * @var array
     */
    static protected $_callableParamsSpecs = array();
    
    /**
     * Default callback usable for editor callbacks sorting
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Callback $callbackA One editor callback
     * @param BL_CustomGrid_Model_Grid_Editor_Callback $callbackB Another editor callback
     * @return int
     */
    public static function sortCallbacks(
        BL_CustomGrid_Model_Grid_Editor_Callback $callbackA,
        BL_CustomGrid_Model_Grid_Editor_Callback $callbackB
    ) {
        return $callbackA->getPriority() - $callbackB->getPriority();
    }
    
    /**
     * Return the priority of this callback (lower come first)
     * 
     * @return int
     */
    public function getPriority()
    {
        return (int) $this->_getData('priority');
    }
    
    /**
     * Return whether the execution of the next callbacks of the same kind
     * (ie, same worker and action types, same position) should be stopped after that this callback has been called
     * 
     * @return bool
     */
    public function getShouldStopAfter()
    {
        return (bool) $this->_getData('should_stop_after');
    }
    
    /**
     * Resolve the given callable and return consistent values depending on the callable type, ie :
     * - for functions : a string, the function name
     * - for methods : an array, with the corresponding object or class name at index 0, and the method name at index 1
     * 
     * @param callable $callable Resolvable callable
     * @return string|array
     */
    protected function _getResolvedCallable($callable)
    {
        $resolvedCallable = $callable;
        
        if (is_array($callable)) {
            if (substr($callable[1], 0, 8) === 'parent::') {
                $resolvedCallable = array(get_parent_class($callable[0]), substr($callable[1], 8));
            }
        } elseif (is_string($callable)) {
            if (($scopeOperatorPosition = strpos($callable, '::')) !== false) {
                $resolvedCallable = array(
                    substr($callable, 0, $scopeOperatorPosition),
                    substr($callable, $scopeOperatorPosition + 2)
                );
            }
        } else {
            Mage::throwException('Unhandled callback type');
        }
        
        return $resolvedCallable;
    }
    
    /**
     * Return an identifying name for the given resolved callable
     * 
     * @param callable $callable Resolved callable
     * @return string
     */
    protected function _getCallableName($callable)
    {
        if (is_array($callable)) {
            $callableName = (is_object($callable[0]) ? get_class($callable[0]) : $callable[0]) . '::' . $callable[1];
        } else {
            $callableName = $callable;
        }
        return $callableName;
    }
    
    /**
     * Return the specifications of the defined parameters from the given resolved callable
     * 
     * @param callable $callable Resolved callable
     * @return array
     */
    protected function _getCallableParamsSpecs($callable)
    {
        $callableName = $this->_getCallableName($callable);
        
        if (!isset(self::$_callableParamsSpecs[$callableName])) {
            try {
                if (is_array($callable)) {
                    $reflectedCallableBase = new ReflectionMethod($callable[0], $callable[1]);
                } else {
                    $reflectedCallableBase = new ReflectionFunction($callable);
                }
                
                $reflectedParams = $reflectedCallableBase->getParameters();
                
                foreach ($reflectedParams as $reflectedParam) {
                    /** @var ReflectionParameter $reflectedParam */
                    $paramName = $reflectedParam->getName();
                    
                    self::$_callableParamsSpecs[$callableName][$paramName] = array(
                        'allows_null' => $reflectedParam->allowsNull(),
                        'class_name'  => (($class = $reflectedParam->getClass()) ? $class->getName() : null),
                        'is_array'    => $reflectedParam->isArray(),
                        'is_optional' => $reflectedParam->isOptional(),
                    );
                    
                    if ($reflectedParam->isOptional()) {
                        $defaultValue = $reflectedParam->getDefaultValue();
                        self::$_callableParamsSpecs[$callableName][$paramName]['default_value'] = $defaultValue;
                    }
                }
            } catch (ReflectionException $e) {
                $message = $e->getMessage();
                Mage::throwException('Could not handle the callback callable or its parameters : "' . $message . '"');
            }
        }
        
        return self::$_callableParamsSpecs[$callableName];
    }
    
    /**
     * Return whether the given value is valid in regard to the given parameter specifications
     * 
     * @param array $paramSpecs Parameter specifications
     * @param mixed $availableValue Corresponding available value
     * @return bool
     */
    protected function _checkCallableCallParamValue(array $paramSpecs, $availableValue)
    {
        $isValidValue = true;
        
        if (is_null($availableValue)) {
            $isValidValue = $paramSpecs['allows_null'];
        } elseif ($paramSpecs['is_array']) {
            $isValidValue = is_array($availableValue);
        } elseif ($paramSpecs['class_name']) {
            $isValidValue = ($availableValue instanceof $paramSpecs['class_name']);
        }
        
        return $isValidValue;
    }
    
    /**
     * Map the available parameters to those of the given resolved callable,
     * and return the corresponding values list in the right order
     * 
     * @param callable $callable Resolved callable
     * @param array $availableParams Available parameters
     * @return array|null
     */
    protected function _getCallableCallParams($callable, array $availableParams)
    {
        $callParams  = array();
        $paramsSpecs = $this->_getCallableParamsSpecs($callable);
        
        foreach ($paramsSpecs as $paramName => $paramSpecs) {
            $isValidValue = true;
            
            if (!array_key_exists($paramName, $availableParams)) {
                if (!$paramSpecs['is_optional']) {
                    $isValidValue = false;
                } else {
                    $availableParams[$paramName] = $paramSpecs['default_value'];
                }
            } else {
                $isValidValue = $this->_checkCallableCallParamValue($paramSpecs, $availableParams[$paramName]);
            }
            if ($isValidValue) {
                $callParams[] = $availableParams[$paramName];
            } else {
                Mage::throwException('The callback callable parameter is not valid ("' . $paramName . '")');
            }
        }
        
        return $callParams;
    }
    
    /**
     * Make a call to the wrapped callable using the given available parameters
     * 
     * @param array $availableParams Available parameters
     * @param mixed $previousReturnedValue The value returned by the previous callback (if any)
     * @return mixed
     */
    public function call(array $availableParams, $previousReturnedValue = null)
    {
        $availableParams['editorCallback'] = $this;
        $availableParams['previousReturnedValue'] = $previousReturnedValue;
        $callParams = array();
        
        if (is_callable($callable = $this->_getData('callable'))) {
            $callable   = $this->_getResolvedCallable($callable);
            $callParams = $this->_getCallableCallParams($callable, $availableParams);
        } else {
            Mage::throwException('The callback callable is not callable');
        }
        
        return call_user_func_array($callable, $callParams);
    }
}
