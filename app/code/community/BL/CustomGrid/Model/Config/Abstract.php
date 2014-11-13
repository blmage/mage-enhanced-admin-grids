<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Widget
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

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

abstract class BL_CustomGrid_Model_Config_Abstract extends BL_CustomGrid_Object
{
    /**
     * Return the config manager
     * 
     * @return BL_CustomGrid_Model_Config_Manager
     */
    protected function _getConfigManager()
    {
        return Mage::getSingleton('customgrid/config_manager');
    }
    
    /**
     * Return the handled configuration type
     * 
     * @return string
     */
    abstract public function getConfigType();
    
    /**
     * Return whether the corresponding configuration elements do accept parameters
     * 
     * @return bool
     */
    public function getAcceptParameters()
    {
        return false;
    }
    
    /**
     * Return the corresponding XML configuration object
     * 
     * @return Varien_Simplexml_Config
     */
    public function getXmlConfig()
    {
        return $this->_getConfigManager()->getXmlConfig($this->getConfigType());
    }
    
    /**
     * Return the root element from the XML configuration object
     * 
     * @return Varien_Simplexml_Element
     */
    public function getRootXmlElement()
    {
        return $this->getXmlConfig()->getNode();
    }
    
    /**
     * Return the codes of all the elements
     * 
     * @return string[]
     */
    public function getElementsCodes()
    {
        if (!$this->hasData('elements_codes')) {
            $codes = array();
            
            foreach ($this->getRootXmlElement()->children() as $xmlElement) {
                $codes[] = $xmlElement->getName();
            }
            
            $this->setData('elements_codes', $codes);
        }
        return $this->_getData('elements_codes');
    }
    
    /**
     * Return the XML element corresponding to the given element code
     * 
     * @param string $code Element code
     * @return Varien_Simplexml_Element|null
     */
    public function getXmlElementByCode($code)
    {
        $dataKey = 'xml_element_' . $code;
        
        if (!$this->hasData($dataKey)) {
            $element  = null;
            $elements = $this->getXmlConfig()->getXpath($code);
            
            if (is_array($elements)
                && isset($elements[0]) 
                && ($elements[0] instanceof Varien_Simplexml_Element)) {
                $element = $elements[0];
            }
            
            $this->setData($dataKey, $element);
        }
        
        return $this->_getData($dataKey);
    }
    
    /**
     * Callback for elements objects sorting
     * 
     * @param BL_CustomGrid_Object $elementA One element object
     * @param BL_CustomGrid_Object $elementB Another element object
     * @return int
     */
    protected function _sortElements(BL_CustomGrid_Object $elementA, BL_CustomGrid_Object $elementB)
    {
        $result = $elementA->compareIntDataTo('sort_order', $elementB);
        return ($result === 0 ? $elementA->compareStringDataTo('name', $elementB) : $result);
    }
    
    /**
     * Return the additional sub values that should be added to the element object corresponding to the
     * given XML element
     * 
     * @param Varien_Simplexml_Element $xmlElement XML element
     * @param array $baseValues Element base values
     * @param Mage_Core_Helper_Abstract $helper Translation helper
     * @return array
     */
    public function getElementsArrayAdditionalSubValues(
        Varien_Simplexml_Element $xmlElement,
        array $baseValues,
        Mage_Core_Helper_Abstract $helper
    ) {
        return array();
    }
    
    /**
     * Return the elements objects in an array
     * 
     * @return BL_CustomGrid_Object[]
     */
    public function getElementsArray()
    {
        if (!$this->_getData('elements_array')) {
            $elements = array();
            
            foreach ($this->getRootXmlElement()->children() as $xmlElement) {
                $module = ($xmlElement->getAttribute('module') ? $xmlElement->getAttribute('module') : 'customgrid');
                $helper = Mage::helper('customgrid')->getSafeHelper((string) $module);
                
                $values =  array(
                    'code' => $xmlElement->getName(),
                    'type' => $xmlElement->getAttribute('type'),
                    'name' => $helper->__((string) $xmlElement->name),
                    'help' => $helper->__((string) $xmlElement->help),
                    'sort_order'  => (int) $xmlElement->{'sort_order'}, // CheckStyle validators do not like snake case
                    'description' => $helper->__((string) $xmlElement->description),
                    'is_customizable' => $this->getAcceptParameters(),
                );
                
                $element = new BL_CustomGrid_Object(
                    array_merge(
                        $values,
                        $this->getElementsArrayAdditionalSubValues($xmlElement, $values, $helper)
                    )
                );
                
                $elements[$element['code']] = $element;
            }
            
            uasort($elements, array($this, '_sortElements'));
            $this->setData('elements_array', $elements);
        }
        return $this->_getData('elements_array');
    }
    
    /**
     * Callback for element parameters sorting
     * 
     * @param BL_CustomGrid_Object $paramA One parameter
     * @param BL_CustomGrid_Object $paramB Another parameter
     * @return int
     */
    protected function _sortParams(BL_CustomGrid_Object $paramA, BL_CustomGrid_Object $paramB)
    {
        return $paramA->compareIntDataTo('sort_order', $paramB);
    }
    
    /**
     * Parse the given raw parameters coming from a XML element
     * 
     * @param array $rawParams Raw parameters
     * @return BL_CustomGrid_Object[]
     */
    protected function _parseXmlElementParamsArray(array $rawParams)
    {
        /** @var $configHelper BL_CustomGrid_Helper_Xml_Config */
        $configHelper = Mage::helper('customgrid/xml_config');
        $objectParams = array();
        $sortOrder = 0;
        
        foreach ($rawParams as $key => $data) {
            if (is_array($data)) {
                $data['key'] = $key;
                $data['sort_order'] = (isset($data['sort_order']) ? (int) $data['sort_order'] : $sortOrder);
                $data['values'] = $configHelper->getElementParamOptionsValues($data);
                $data['helper_block'] = $configHelper->getElementParamHelperBlock($data);
                $objectParams[$key] = new BL_CustomGrid_Object($data);
                ++$sortOrder;
            }
        }
        
        return $objectParams;
    }
    
    /**
     * Return an element object from the corresponding XML element
     * 
     * @param string $code Element code
     * @param Varien_Simplexml_Element $xmlElement XML element
     * @return BL_CustomGrid_Object
     */
    protected function _getObjectElementFromXmlElement($code, Varien_Simplexml_Element $xmlElement)
    {
        // Initialize object
        $object = new BL_CustomGrid_Object();
        $object->setData($xmlElement->asArray());
        $object->setCode($code);
        $object->setType($object->getData('@/type'));
        $object->setModule($object->getDataSetDefault('@/module', 'customgrid'));
        $object->unsetData('@');
        
        // Apply translations
        $helper = Mage::helper('customgrid')->getSafeHelper($object->getModule());
        $translatableKeys = array('name', 'description', 'help');
        
        foreach ($translatableKeys as $key) {
            if ($object->hasData($key)) {
                $object->setData($key, $helper->__((string) $object->getData($key)));
            }
        }
        
        // Parse parameters
        if ($this->getAcceptParameters()) {
            $objectParams = is_array($rawParams = $object->getData('parameters'))
                ? $this->_parseXmlElementParamsArray($rawParams)
                : array();
            
            uasort($objectParams, array($this, '_sortParams'));
            $object->setData('parameters', $objectParams);
        }
        
        return $object;
    }
    
    /**
     * Return the element object corresponding to the given element code
     * 
     * @param string $code Element code
     * @return BL_CustomGrid_Object
     */
    public function getObjectElementByCode($code)
    {
        $dataKey = 'object_element_' . $code;
        
        if (!$this->hasData($dataKey)) {
            $xmlElement = $this->getXmlElementByCode($code);
            
            if (!is_null($xmlElement)) {
                $object = $this->_getObjectElementFromXmlElement($code, $xmlElement);
            } else {
                $object = new BL_CustomGrid_Object();
            }
            
            $this->setData($dataKey, $object);
        }
        
        return $this->_getData($dataKey);
    }
    
    /**
     * Return an instantiated model corresponding to the given element code,
     * on which can be applied some encoded parameters if given
     * 
     * @param string $code Element code
     * @param string|null $parameters Encoded parameters
     * @return mixed
     */
    public function getElementInstanceByCode($code, $parameters = null)
    {
        $model = null;
        
        if (($element = $this->getXmlElementByCode($code)) && $element->getAttribute('model')) {
            if (!$this->getAcceptParameters()) {
                $model = Mage::getSingleton($element->getAttribute('model'));
            } else {
                if ($model = Mage::getModel($element->getAttribute('model'))) {
                    if (!is_null($parameters)) {
                        $model->setValues($this->decodeParameters($parameters));
                    }
                }
            }
            
            if ($model) {
                if (!$module = $element->getAttribute('module')) {
                    $module = 'customgrid';
                }
                
                $helper = Mage::helper('customgrid')->getSafeHelper($module);
                $model->setCode($code);
                $model->setName($helper->__((string) $element->name));
            }
        }
        
        return $model;
    }
    
    /**
     * Encode the given parameters into a string
     * 
     * @param mixed $params Parameters
     * @return string
     */
    public function encodeParameters($params)
    {
        return (is_array($params) ? serialize($params) : $params);
    }
    
    /**
     * Decode the given encoded parameters string
     * 
     * @param string $params Encoded parameters string
     * @return array
     */
    public function decodeParameters($params)
    {
        if (is_string($params)) {
            $params = unserialize($params);
        }
        return (is_array($params) ? $params : array());
    }
}
