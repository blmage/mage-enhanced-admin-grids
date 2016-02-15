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

class BL_CustomGrid_Model_Reflection_Property extends ReflectionProperty
{
    /**
     * Name of the class holding the reflected property
     * 
     * @var string
     */
    protected $_baseClassName;
    
    /**
     * Name of the reflected property
     * 
     * @var string
     */
    protected $_basePropertyName;
    
    /**
     * Object holding accessible getter and setter for the reflected property
     * 
     * @var mixed
     */
    protected $_accessibleObject = null;
    
    /**
     * Name of the accessible getter for the reflected property, holded by the accessible object
     * 
     * @var string|null
     */
    protected $_accessibleGetterName = null;
    
    /**
     * Name of the accessible setter for the reflected property, holded by the accessible object
     * 
     * @var string|null
     */
    protected $_accessibleSetterName = null;
    
    /**
     * Whether setAccessible(true) has been called on this object
     * 
     * @var bool
     */
    protected $_hasForcedAccessibility = false;
    
    public function __construct($classCode, $classType, $propertyName)
    {
        $config = $this->_getConfig();
        $this->_baseClassName = $config->getClassNameForCodeAndType($classCode, $classType);
        $this->_basePropertyName = $propertyName;
        $this->_accessibleGetterName = 'blcgGet' . ucfirst(ltrim($this->_basePropertyName, '_')) . 'Value';
        $this->_accessibleSetterName = 'blcgSet' . ucfirst(ltrim($this->_basePropertyName, '_')) . 'Value';
        
        parent::__construct($this->_baseClassName, $this->_basePropertyName);
        
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
     * Set the property accessibility
     * 
     * @param bool $accessible Whether the property should be accessible
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
     * @param mixed $object Accessible object (object holding accessible getter and setter for the reflected property)
     */
    public function setAccessibleObject($object)
    {
        $this->_accessibleObject = $object;
    }
    
    /**
     * Set the accessible getter name
     * 
     * @param string $getterName Getter name
     */
    public function setAccessibleGetterName($getterName)
    {
        $this->_accessibleGetterName = $getterName;
    }
    
    /**
     * Set the accessible setter name
     * 
     * @param string $setterName Setter name
     */
    public function setAccessibleSetterName($setterName)
    {
        $this->_accessibleSetterName = $setterName;
    }
    
    /**
     * Return the property value from the given object, using the accessible getter
     * 
     * @param mixed $object Object from which to get the value (null if static property)
     * @return mixed
     * @throws ReflectionException
     */
    protected function _getValueUsingAccessibleGetter($object)
    {
        /** @var $helper BL_CustomGrid_Helper_Data */
        $helper = Mage::helper('customgrid');
        $callback = array($this->_accessibleObject, $this->_accessibleGetterName);
        $fullPropertyName = $this->_baseClassName . '::' . $this->_basePropertyName;
        
        if (empty($this->_accessibleObject) || empty($this->_accessibleGetterName)) {
            throw new ReflectionException(
                $helper->__('Missing informations to get the value from a reflected property : "%s"', $fullPropertyName)
            );
        } elseif (!is_callable($callback)) {
            throw new ReflectionException(
                $helper->__('Could not get the value from a reflected property : "%s"', $fullPropertyName)
            );
        }
        
        return (!is_null($object) ? call_user_func($callback, $object) : call_user_func($callback));
    }
    
    public function getValue($object = null)
    {
        return $this->_hasForcedAccessibility && !empty($this->_accessibleObject)
            ? $this->_getValueUsingAccessibleGetter($object)
            : (is_null($object) ? parent::getValue() : parent::getValue($object));
    }
    
    /**
     * Set the property value for the given object, using the accessible setter
     * 
     * @param mixed $object Object on which to set the value (null if static property)
     * @param mixed $value Property value
     * @return void
     * @throws ReflectionException
     */
    protected function _setValueUsingAccessibleSetter($object, $value)
    {
        $callback = array($this->_accessibleObject, $this->_accessibleSetterName);
        $fullPropertyName = $this->_baseClassName . '::' . $this->_basePropertyName;
        
        if (empty($this->_accessibleObject) || empty($this->_accessibleSetterName)) {
            throw new ReflectionException(
                'Missing informations to set the value for a reflected property : "' . $fullPropertyName . '"'
            );
        } elseif (!is_callable($callback)) {
            throw new ReflectionException(
                'Could not set the value for a reflected property : "' . $fullPropertyName . '"'
            );
        }
        
        if (!is_null($object)) {
            call_user_func($callback, $object, $value);
        } else {
            call_user_func($callback, $value);
        }
    }
    
    public function setValue($arg1 = null, $arg2 = null)
    {
        $argsCount = func_num_args();
        $object = ($argsCount == 2 ? $arg1 : null);
        $value  = ($argsCount == 2 ? $arg2 : $arg1);
        
        if ($this->_hasForcedAccessibility && !empty($this->_accessibleObject)) {
            $this->_setValueUsingAccessibleSetter($object, $value);
        } elseif (!is_null($object)) {
            parent::setValue($object, $value);
        } else {
            parent::setValue($value);
        }
    }
}
