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

class BL_CustomGrid_Model_Reflection_Config
{
    const CLASS_TYPE_BLOCK = 'block';
    const CLASS_TYPE_MODEL = 'model';
    
    /**
     * Class codes for additional, prioritary, accessible classes, arranged by class type and original class code
     * 
     * @var array
     */
    protected $_accessibleClassCodes = array(
        self::CLASS_TYPE_BLOCK => array(),
        self::CLASS_TYPE_MODEL => array(),
    );
    
    /**
     * Whether the setAccessible() methods from ReflectionMethod and ReflectionProperty are available
     * 
     * @var bool
     */
    protected $_canUseReflectionAccessibility = null;
    
    /**
     * Return whether the setAccessible() methods from ReflectionMethod and ReflectionProperty are available
     * 
     * @return bool
     */
    public function canUseReflectionAccessibility()
    {
        if (is_null($this->_canUseReflectionAccessibility)) {
            $this->_canUseReflectionAccessibility = (version_compare(phpversion(), '5.3.2', '>=') === true);
        }
        return $this->_canUseReflectionAccessibility;
    }
    
    /**
     * Return the class name corresponding to the given class code and type
     * 
     * @param string $classCode Class code
     * @param string $classType Class type (block or model)
     * @return string
     */
    public function getClassNameForCodeAndType($classCode, $classType)
    {
        $className = null;
        
        if ($classType == self::CLASS_TYPE_BLOCK) {
            $className = Mage::app()->getConfig()->getBlockClassName($classCode);
        } elseif ($classType == self::CLASS_TYPE_MODEL) {
            $className = Mage::app()->getConfig()->getModelClassName($classCode);
        }
        
        return $className;
    }
    
    /**
     * Return the "accessible" object corresponding to the given class code and type
     * 
     * @param string $classCode Class code
     * @param string $classType Class type (block or model)
     * @return mixed
     */
    public function getAccessibleObjectForClassCodeAndType($classCode, $classType)
    {
        $accessibleObject = null;
        
        if (isset($this->_accessibleClassCodes[$classType][$classCode])) {
            $accessibleClassCode = $this->_accessibleClassCodes[$classType][$classCode];
        } else {
            $classCodeParts = array_map('strtolower', explode('/', $classCode));
            $accessibleClassCode = 'customgrid/reflection_accessible_' . implode('_', $classCodeParts);
        }
        
        if ($classType == self::CLASS_TYPE_BLOCK) {
            $accessibleObject = Mage::getBlockSingleton($accessibleClassCode);
        } elseif ($classType == self::CLASS_TYPE_MODEL) {
            $accessibleObject = Mage::getSingleton($accessibleClassCode);
        }
        
        return $accessibleObject;
    }
    
    /**
     * Register an additional accessible class of the given type
     * 
     * @param string $originalClassCode Original class code (eg: "sales/order")
     * @param string $accessibleClassCode Accessible class code (eg: "customgrid/reflection_accessible_sales_order")
     * @param string $classType Class type (block or model)
     * @return BL_CustomGrid_Model_Reflection_Config
     */
    public function registerAccessibleClass($originalClassCode, $accessibleClassCode, $classType)
    {
        $this->_accessibleClassCodes[$classType][$originalClassCode] = $accessibleClassCode;
        return $this;
    }
    
    /**
     * Register an additional accessible block class
     * 
     * @param string $originalClassCode Original class code (eg: "cms/block")
     * @param string $accessibleClassCode Accessible class code (eg: "customgrid/reflection_accessible_cms_block")
     * @return BL_CustomGrid_Model_Reflection_Config
     */
    public function registerAccessibleBlockClass($originalClassCode, $accessibleClassCode)
    {
        return $this->registerAccessibleClass($originalClassCode, $accessibleClassCode, self::CLASS_TYPE_BLOCK);
    }
    
    /**
     * Register an additional accessible model class
     * 
     * @param string $originalClassCode Original class code (eg: "sales/order")
     * @param string $accessibleClassCode Accessible class code (eg: "customgrid/reflection_accessible_sales_order")
     * @return BL_CustomGrid_Model_Reflection_Config
     */
    public function registerAccessibleModelClass($originalClassCode, $accessibleClassCode)
    {
        return $this->registerAccessibleClass($originalClassCode, $accessibleClassCode, self::CLASS_TYPE_MODEL);
    }
}
