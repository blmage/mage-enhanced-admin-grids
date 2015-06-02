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

class BL_CustomGrid_Helper_Reflection extends Mage_Core_Helper_Abstract
{
    const VALUE_TYPE_METHOD   = 'method';
    const VALUE_TYPE_PROPERTY = 'property';
    
    /**
     * Return the reflected counterpart for the given value, already set as accessible for convenience
     * 
     * @param mixed $value Full value name, including class code
     * @param mixed $valueType Value type (method or property)
     * @param mixed $classType Class type (block or model)
     * @param bool $graceful Whether to throw an exception if the reflected value could not be retrieved
     * @return BL_CustomGrid_Model_Reflection_Method|BL_CustomGrid_Model_Reflection_Property|null
     */
    public function getReflectionValue($value, $valueType, $classType, $graceful = false)
    {
        $valueParts = explode('::', $value);
        
        if ((count($valueParts) != 2) || (strpos($valueParts[0], '/') === false)) {
            if (!$graceful) {
                Mage::throwException($this->__('Invalid reflected value requested : "%s"', $value));
            }
            return null;
        }
        
        list($classCode, $valueName) = $valueParts;
        
        try {
            if ($valueType == self::VALUE_TYPE_METHOD) {
                $reflectionValue = new BL_CustomGrid_Model_Reflection_Method($classCode, $classType, $valueName);
            } elseif ($valueType == self::VALUE_TYPE_PROPERTY) {
                $reflectionValue = new BL_CustomGrid_Model_Reflection_Property($classCode, $classType, $valueName);
            } else {
                return null;
            }
            $reflectionValue->setAccessible(true);
        } catch (ReflectionException $e) {
            $reflectionValue = null;
        }
        
        if (!is_object($reflectionValue)) {
            if (!$graceful) {
                Mage::throwException($this->__('Could not get reflected value for "%s"', $value));
            }
            $reflectionValue = null;
        }
        
        return $reflectionValue;
    }
    
    /**
     * Return the reflected counterpart for the given method coming from a block class,
     * already set as accessible for convenience
     * 
     * @param string $method Full method name, including class code (eg: "adminhtml/widget_grid::_prepareCollection")
     * @param bool $graceful Whether to throw an exception if the reflected method could not be retrieved
     * @return BL_CustomGrid_Model_Reflection_Method|null
     */
    public function getBlockReflectionMethod($method, $graceful = false)
    {
        return $this->getReflectionValue(
            $method,
            self::VALUE_TYPE_METHOD,
            BL_CustomGrid_Model_Reflection_Config::CLASS_TYPE_BLOCK,
            $graceful
        );
    }
    
    /**
     * Return the reflected counterpart for the given method coming from a model class,
     * already set as accessible for convenience
     * 
     * @param string $method Full method name, including class code (eg: "core/layout::_generateAction")
     * @param bool $graceful Whether to throw an exception if the reflected method could not be retrieved
     * @return BL_CustomGrid_Model_Reflection_Method|null
     */
    public function getModelReflectionMethod($method, $graceful = false)
    {
        return $this->getReflectionValue(
            $method,
            self::VALUE_TYPE_METHOD,
            BL_CustomGrid_Model_Reflection_Config::CLASS_TYPE_MODEL,
            $graceful
        );
    }
    
    /**
     * Return the reflected counterpart for the given property coming from a block class,
     * already set as accessible for convenience
     * 
     * @param string $property Full property name, including class code (eg: "core/layout::_blocks")
     * @param bool $graceful Whether to throw an exception if the reflected property could not be retrieved
     * @return BL_CustomGrid_Model_Reflection_Property|null
     */
    public function getBlockReflectionProperty($property, $graceful = false)
    {
        return $this->getReflectionValue(
            $property,
            self::VALUE_TYPE_PROPERTY,
            BL_CustomGrid_Model_Reflection_Config::CLASS_TYPE_BLOCK,
            $graceful
        );
    }
    
    /**
     * Return the reflected counterpart for the given property coming from a model class,
     * already set as accessible for convenience
     * 
     * @param string $property Full property name, including class code (eg: "adminhtml/widget_grid::_collection")
     * @param bool $graceful Whether to throw an exception if the reflected property could not be retrieved
     * @return BL_CustomGrid_Model_Reflection_Property|null
     */
    public function getModelReflectionProperty($property, $graceful = false)
    {
        return $this->getReflectionValue(
            $property,
            self::VALUE_TYPE_PROPERTY,
            BL_CustomGrid_Model_Reflection_Config::CLASS_TYPE_MODEL,
            $graceful
        );
    }
}
