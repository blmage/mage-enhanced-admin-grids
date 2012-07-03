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
 * @copyright  Copyright (c) 2012 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

abstract class BL_CustomGrid_Model_Config_Abstract extends Varien_Object
{
    protected $_acceptParameters = false;
    
    abstract public function getConfigType();
    
    public function getXmlConfig()
    {
        return Mage::getSingleton('customgrid/config')->getXmlConfig($this->getConfigType());
    }
    
    public function getXmlElementByCode($code)
    {
        $elements = $this->getXmlConfig()->getXpath($code);
        if (is_array($elements) && isset($elements[0]) 
            && ($elements[0] instanceof Varien_Simplexml_Element)) {
            return $elements[0];
        }
        return null;
    }
    
    public function getConfigAsXml($code)
    {
        return $this->getXmlElementByCode($code);
    }
    
    public function getElementsXml()
    {
        return $this->getXmlConfig()->getNode();
    }
    
    public function getConfigAsObject($code)
    {
        $xml = $this->getConfigAsXml($code);
        $object = new Varien_Object();
        
        if ($xml === null) {
            return $object;
        }
        
        // Save all nodes to object data
        $object->setCode($code);
        $object->setData($xml->asCanonicalArray());
        
        // Set module for translations etc..
        $module = $object->getData('@/module');
        $object->setModule($module ? $module : 'customgrid');
        
        // Set type
        $type = $object->getData('@/type');
        $object->setType($type);
        
        // Translate name, description and help
        $helper = Mage::helper($object->getModule());
        
        if ($object->hasName()) {
            $object->setName($helper->__((string)$object->getName()));
        }
        if ($object->hasDescription()) {
            $object->setDescription($helper->__((string)$object->getDescription()));
        }
        if ($object->hasHelp()) {
            $object->setHelp($helper->__((string)$object->getHelp()));
        }
        
        if ($this->_acceptParameters) {
            // Correct element parameters and convert its data to objects if needed
            $params = $object->getData('parameters');
            $newParams = array();
            
            if (is_array($params)) {
                $sortOrder = 0;
                foreach ($params as $key => $data) {
                    if (is_array($data)) {
                        $data['key'] = $key;
                        $data['sort_order'] = (isset($data['sort_order']) ? (int)$data['sort_order'] : $sortOrder);
                        
                        // Prepare values (for dropdowns) specified directly in configuration
                        $values = array();
                        if (isset($data['values']) && is_array($data['values'])) {
                            foreach ($data['values'] as $value) {
                                if (isset($value['label']) && isset($value['value'])) {
                                    $values[] = $value;
                                }
                            }
                        }
                        $data['values'] = $values;
                        
                        // Prepare helper block object
                        if (isset($data['helper_block'])) {
                            $helper = new Varien_Object();
                            if (isset($data['helper_block']['data']) && is_array($data['helper_block']['data'])) {
                                $helper->addData($data['helper_block']['data']);
                            }
                            if (isset($data['helper_block']['type'])) {
                                $helper->setType($data['helper_block']['type']);
                            }
                            $data['helper_block'] = $helper;
                        }
                        
                        $newParams[$key] = new Varien_Object($data);
                        $sortOrder++;
                    }
                }
            }
            
            uasort($newParams, array($this, '_sortParameters'));
            $object->setData('parameters', $newParams);
        }
        
        return $object;
    }
    
    public function getElementArrayValues($element, $values, $helper)
    {
        return array();
    }
    
    public function getElementsArray()
    {
        if (!$this->_getData('elements_array')) {
            $result = array();
            
            if ($this->getElementsXml()) {
                foreach ($this->getElementsXml()->children() as $element) {
                    $helper = ($element->getAttribute('module') ? $element->getAttribute('module') : 'customgrid');
                    $helper = Mage::helper($helper);
                    
                    $values = array(
                        'code' => $element->getName(),
                        'type' => $element->getAttribute('type'),
                        'name' => $helper->__((string)$element->name),
                        'help' => $helper->__((string)$element->help),
                        'sort_order'  => (int)$element->sort_order,
                        'description' => $helper->__((string)$element->description),
                        'is_customizable' => $this->_acceptParameters,
                    );
                    
                    $result[$element->getName()] = array_merge(
                        $values,
                        $this->getElementArrayValues($element, $values, $helper)
                    );
                }
            }
            
            uasort($result, array($this, '_sortElements'));
            $this->setData('elements_array', $result);
        }
        return $this->_getData('elements_array');
    }
    
    public function getElementInstanceByCode($code, $params=null)
    {
        if ($element = $this->getXmlElementByCode($code)) {
            if (!$this->_acceptParameters) {
                $instance = Mage::getSingleton($element->getAttribute('type'));
            } else {
                $instance = Mage::getModel($element->getAttribute('type'));
                if ($instance && !is_null($params)) {
                    if (is_array($params = $this->decodeParameters($params))) {
                        $instance->addData($params);
                    }
                }
            }
            
            $helper = ($element->getAttribute('module') ? $element->getAttribute('module') : 'customgrid');
            $helper = Mage::helper($helper);
            $instance->setCode($code);
            $instance->setName($helper->__((string)$element->name));
            
            return $instance;
        }
        return null;
    }
    
    public function encodeParameters($parameters)
    {
        if (is_array($parameters)) {
            return serialize($parameters);
        }
        return $parameters;
    }
    
    public function decodeParameters($parameters, $forceArray=false)
    {
        if (is_string($parameters)) {
            $parameters = unserialize($parameters);
        }
        return ($forceArray && !is_array($parameters) ? array() : $parameters);
    }
    
    protected function _sortElements($a, $b)
    {
        $aOrder = $a['sort_order'];
        $bOrder = $b['sort_order'];
        return ($aOrder < $bOrder 
            ? -1 : ($aOrder > $bOrder ? 1 : strcmp($a['name'], $b['name'])));
    }
    
    protected function _sortParameters($a, $b)
    {
        $aOrder = (int)$a->getData('sort_order');
        $bOrder = (int)$b->getData('sort_order');
        return ($aOrder < $bOrder ? -1 : ($aOrder > $bOrder ? 1 : 0));
    }
}