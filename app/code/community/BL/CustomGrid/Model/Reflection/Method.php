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
 * @copyright  Copyright (c) 2013 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_Reflection_Method extends ReflectionMethod
{
    /**
     * Name of the class holding the reflected method
     * 
     * @var string
     */
    protected $_baseClassName;
    
    /**
     * Name of the reflected method
     * 
     * @var string
     */
    protected $_baseMethodName;
    
    /**
     * Object holding an accessible wrapper over the reflected method
     * 
     * @var mixed
     */
    protected $_accessibleObject = null;
    
    /**
     * Name of the accessible wrapper over the reflected method, holded by the accessible object
     * 
     * @var string|null
     */
    protected $_accessibleMethodWrapperName = null;
    
    /**
     * Whether setAccessible(true) has been called on this object
     * 
     * @var bool
     */
    protected $_hasForcedAccessibility = false;
    
    public function __construct($classCode, $classType, $methodName)
    {
        $config = $this->_getConfig();
        $this->_baseClassName  = $config->getClassNameForCodeAndType($classCode, $classType);
        $this->_baseMethodName = $methodName;
        $this->_accessibleMethodWrapperName = 'blcgInvoke' . ucfirst(ltrim($this->_baseMethodName, '_'));
        
        parent::__construct($this->_baseClassName, $this->_baseMethodName);
        
        if (!$config->canUseReflectionAccessibility()) {
            $this->_accessibleObject = $config->getAccessibleObjectForClassCodeAndType($classCode, $classType);
        }
    }
    
    /**
     * Return config object
     * 
     * @return BL_CustomGrid_Model_Reflection_Config
     */
    protected function _getConfig()
    {
        return Mage::getSingleton('customgrid/reflection_config');
    }
    
    /**
     * Set the method accessibility
     * 
     * @param bool $accessible Whether the method should be accessible
     */
    public function setAccessible($accessible)
    {
        $this->_hasForcedAccessibility = (bool) $accessible;
        
        if ($this->_getConfig()->canUseReflectionAccessibility()) {
            parent::setAccessible($accessible);
        }
    }
    
    /**
     * Set the accessible object
     * 
     * @param mixed $object Accessible object (object holding an accessible wrapper over the reflected method)
     */
    public function setAccessibleObject($object)
    {
        $this->_accessibleObject = $object;
    }
    
    /**
     * Set the accessible method wrapper name
     * 
     * @param string $wrapperName Method wrapper name
     */
    public function setAccessibleMethodWrapperName($wrapperName)
    {
        $this->_accessibleMethodWrapperName = $wrapperName;
    }
    
    /**
     * Invoke the reflected method on the given object, by using the accessible method wrapper
     * 
     * @param mixed $object Object on which to call the method (null if static method)
     * @param array $args Call arguments
     * @return mixed
     * @throws ReflectionException
     */
    protected function _invokeAccessibleWrapper($object, array $args)
    {
        $callback = array($this->_accessibleObject, $this->_accessibleMethodWrapperName);
        $fullMethodName = $this->_baseClassName . '::' . $this->_baseMethodName;
        
        if (empty($this->_accessibleObject) || empty($this->_accessibleMethodWrapperName)) {
            throw new ReflectionException(
                'Missing informations to invoke a reflected method : "' . $fullMethodName . '"'
            );
        } elseif (!is_callable($callback)) {
            throw new ReflectionException('Could not invoke a reflected method : "' . $fullMethodName . '"');
        }
        
        if (!is_null($object)) {
            array_unshift($args, $object);
        }
        
        return call_user_func_array($callback, $args);
    }
    
    public function invoke($object, $arg = null)
    {
        $args = func_get_args();
        array_shift($args);
        return $this->invokeArgs($object, $args);
    }
    
    public function invokeArgs($object, array $args)
    {
        return $this->_hasForcedAccessibility && !empty($this->_accessibleObject)
            ? $this->_invokeAccessibleWrapper($object, $args)
            : parent::invokeArgs($object, $args);
    }
}
