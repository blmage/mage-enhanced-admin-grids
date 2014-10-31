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

class BL_CustomGrid_Helper_Reflection extends Mage_Core_Helper_Abstract
{
    const CLASS_TYPE_BLOCK = 'block';
    const CLASS_TYPE_MODEL = 'model';
    
    const VALUE_TYPE_METHOD   = 'method';
    const VALUE_TYPE_PROPERTY = 'property';
    
    protected function _getPHPReflectionValue($className, $valueName, $valueType)
    {
        $reflectionValue = null;
        
        try {
            if ($valueType == self::VALUE_TYPE_METHOD) {
                $reflectionValue = new ReflectionMethod($className, $valueName);
            } elseif ($valueType == self::VALUE_TYPE_PROPERTY) {
                $reflectionValue = new ReflectionProperty($className, $valueName);
            } else {
                return null;
            }
            if (is_callable(array($reflectionValue, 'setAccessible'))) {
                $reflectionValue->setAccessible(true);
            } else {
                $reflectionValue = null;
            }
        } catch (ReflectionException $e) {
            $reflectionValue = null;
        }
        
        return $reflectionValue;
    }
    
    protected function _getOwnReflectionValue($classCode, $valueType, $classType)
    {
        $reflectionValue = null;
        $classCodeParts  = explode('/', $classCode);
        $reflectionClassCode = 'customgrid/reflection_' . $valueType . '_' . strtolower($classCodeParts[1]);
        
        try {
            if ($classType == self::CLASS_TYPE_BLOCK) {
                $reflectionValue = Mage::getBlockSingleton($reflectionClassCode);
            } elseif ($classType == self::CLASS_TYPE_MODEL) {
                $reflectionValue = Mage::getSingleton($reflectionClassCode);
            }
        } catch (Exception $e) {
            $reflectionValue = null;
        }
        
        return $reflectionValue;
    }
    
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
        
        if ($classType == self::CLASS_TYPE_BLOCK) {
            $className = Mage::app()->getConfig()->getBlockClassName($classCode);
        } elseif ($classType == self::CLASS_TYPE_MODEL) {
            $className = Mage::app()->getConfig()->getModelClassName($classCode);
        } else {
            return null;
        }
        
        if (!is_object($reflectionValue = $this->_getPHPReflectionValue($className, $valueName, $valueType))) {
            $reflectionValue = $this->_getOwnReflectionValue($classCode, $valueType, $classType);
        }
        if (!is_object($reflectionValue)) {
            if (!$graceful) {
                Mage::throwException($this->__('Could not get reflected value for "%s"', $value));
            }
            $reflectionValue = null;
        }
        
        return $reflectionValue;
    }
    
    public function getBlockReflectionMethod($method, $graceful = false)
    {
        return $this->getReflectionValue($method, self::VALUE_TYPE_METHOD, self::CLASS_TYPE_BLOCK, $graceful);
    }
    
    public function getModelReflectionMethod($method, $graceful = false)
    {
        return $this->getReflectionValue($method, self::VALUE_TYPE_METHOD, self::CLASS_TYPE_MODEL, $graceful);
    }
    
    public function getBlockReflectionProperty($property, $graceful = false)
    {
        return $this->getReflectionValue($property, self::VALUE_TYPE_PROPERTY, self::CLASS_TYPE_BLOCK, $graceful);
    }
    
    public function getModelReflectionProperty($property, $graceful = false)
    {
        return $this->getReflectionValue($property, self::VALUE_TYPE_PROPERTY, self::CLASS_TYPE_MODEL, $graceful);
    }
}
