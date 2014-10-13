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
    abstract public function getConfigType();
    
    public function getAcceptParameters()
    {
        return false;
    }
    
    public function getXmlConfig()
    {
        return Mage::getSingleton('customgrid/config')->getXmlConfig($this->getConfigType());
    }
    
    public function getRootXmlElement()
    {
        return $this->getXmlConfig()->getNode();
    }
    
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
    
    protected function _sortElements(BL_CustomGrid_Object $elementA, BL_CustomGrid_Object $elementB)
    {
        $result = $elementA->compareIntDataTo('sort_order', $elementB);
        return ($result === 0 ? $elementA->compareStringDataTo('name', $elementB) : $result);
    }
    
    public function getElementsArrayAdditionalSubValues(
        Varien_Simplexml_Element $xmlElement,
        array $baseValues,
        Mage_Core_Helper_Abstract $helper
    ) {
        return array();
    }
    
    public function getElementsArray()
    {
        if (!$this->_getData('elements_array')) {
            $elements = array();
            
            foreach ($this->getRootXmlElement()->children() as $xmlElement) {
                $module = ($xmlElement->getAttribute('module') ? $xmlElement->getAttribute('module') : 'customgrid');
                
                if (!$helper = Mage::helper($module)) {
                    $helper = Mage::helper('customgrid');
                }
                
                $values =  array(
                    'code' => $xmlElement->getName(),
                    'type' => $xmlElement->getAttribute('type'),
                    'name' => $helper->__((string) $xmlElement->name),
                    'help' => $helper->__((string) $xmlElement->help),
                    'sort_order'  => (int) $xmlElement->{'sort_order'}, // CheckStyle validatorsdo not like snake case
                    'description' => $helper->__((string) $xmlElement->description),
                    'is_customizable' => $this->getAcceptParameters(),
                );
                
                $element = new BL_CustomGrid_Object(array_merge(
                    $values,
                    $this->getElementsArrayAdditionalSubValues($xmlElement, $values, $helper)
                ));
                
                $elements[$element['code']] = $element;
            }
            
            uasort($elements, array($this, '_sortElements'));
            $this->setData('elements_array', $elements);
        }
        return $this->_getData('elements_array');
    }
    
    protected function _sortParams(BL_CustomGrid_Object $paramA, BL_CustomGrid_Object $paramB)
    {
        return $paramA->compareIntDataTo('sort_order', $paramB);
    }
    
    protected function _parseXmlElementParamsArray(array $rawParams)
    {
        $objectParams = array();
        $sortOrder = 0;
        
        foreach ($rawParams as $key => $data) {
            if (is_array($data)) {
                $data['key'] = $key;
                $data['sort_order'] = (isset($data['sort_order']) ? (int) $data['sort_order'] : $sortOrder);
                $values = array();
                
                if (isset($data['values']) && is_array($data['values'])) {
                    foreach ($data['values'] as $value) {
                        if (is_array($value) && isset($value['label']) && isset($value['value'])) {
                            $values[] = $value;
                        }
                    }
                }
                
                $data['values'] = $values;
                
                // Prepare helper block object
                if (isset($data['helper_block']) && is_array($data['helper_block'])) {
                    $helperBlock = new BL_CustomGrid_Object();
                    
                    if (isset($data['helper_block']['data']) && is_array($data['helper_block']['data'])) {
                        $helperBlock->addData($data['helper_block']['data']);
                    }
                    if (isset($data['helper_block']['type'])) {
                        $helperBlock->setType($data['helper_block']['type']);
                    }
                    
                    $data['helper_block'] = $helperBlock;
                }
                
                $objectParams[$key] = new BL_CustomGrid_Object($data);
                ++$sortOrder;
            }
        }
        
        return $objectParams;
    }
    
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
        $translatableKeys = array('name', 'description', 'help');
        
        if (!$helper = Mage::helper($object->getModule())) {
            $helper = Mage::helper('customgrid');
        }
        
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
                if (!$helper = Mage::helper($module)) {
                    $helper = Mage::helper('customgrid');
                }
                
                $model->setCode($code);
                $model->setName($helper->__((string) $element->name));
            }
        }
        
        return $model;
    }
    
    public function encodeParameters($params)
    {
        return (is_array($params) ? serialize($params) : $params);
    }
    
    public function decodeParameters($params)
    {
        if (is_string($params)) {
            $params = unserialize($params);
        }
        return (is_array($params) ? $params : array());
    }
}
