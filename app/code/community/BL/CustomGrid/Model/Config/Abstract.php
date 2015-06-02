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
 * @copyright  Copyright (c) 2015 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

abstract class BL_CustomGrid_Model_Config_Abstract extends BL_CustomGrid_Object
{
    /**
     * Return the base helper of the given module, or a default helper if the other is not accessible
     * 
     * @param string $module Helper module
     * @return Mage_Core_Helper_Abstract
     */
    protected function _getSafeHelper($module)
    {
        /** @var $helper BL_CustomGrid_Helper_Data */
        $helper = Mage::helper('customgrid');
        return $helper->getSafeHelper($module);
    }
    
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
                $codes[(int) $xmlElement->descend('sort_order')][] = $xmlElement->getName();
            }
            
            ksort($codes, SORT_NUMERIC);
            $sortedCodes = array();
            
            foreach ($codes as $codeGroup) {
                $sortedCodes = array_merge($sortedCodes, array_values($codeGroup));
            }
            
            $this->setData('elements_codes', $sortedCodes);
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
            $xmlElement  = null;
            $xmlElements = $this->getXmlConfig()->getXpath($code);
            
            if (is_array($xmlElements)
                && isset($xmlElements[0]) 
                && ($xmlElements[0] instanceof Varien_Simplexml_Element)) {
                $xmlElement = $xmlElements[0];
            }
            
            $this->setData($dataKey, $xmlElement);
        }
        
        return $this->_getData($dataKey);
    }
    
    /**
     * Return whether the given element model corresponds to what is expected
     * 
     * @param mixed $model Element model
     * @return bool
     */
    protected function _checkElementModelCompliance($model)
    {
        return ($model instanceof BL_CustomGrid_Object);
    }
    
    /**
     * Callback for element parameters sorting
     * 
     * @param BL_CustomGrid_Object $paramA One parameter
     * @param BL_CustomGrid_Object $paramB Another parameter
     * @return int
     */
    protected function _sortElementParams(BL_CustomGrid_Object $paramA, BL_CustomGrid_Object $paramB)
    {
        return $paramA->compareIntDataTo('sort_order', $paramB);
    }
    
    /**
     * Parse the given raw parameters coming from a XML element into an array of prepared parameters objects
     * 
     * @param array $rawParams Raw parameters
     * @return BL_CustomGrid_Object[]
     */
    protected function _parseXmlElementParamsArray(array $rawParams)
    {
        /** @var $configHelper BL_CustomGrid_Helper_Xml_Config */
        $configHelper = Mage::helper('customgrid/xml_config');
        $parsedParams = array();
        $sortOrder = 0;
        
        foreach ($rawParams as $key => $data) {
            if (is_array($data)) {
                $data['key'] = $key;
                $data['sort_order'] = (isset($data['sort_order']) ? (int) $data['sort_order'] : $sortOrder);
                $data['values'] = $configHelper->getElementParamOptionsValues($data);
                $data['helper_block'] = $configHelper->getElementParamHelperBlock($data);
                $parsedParams[$key] = new BL_CustomGrid_Object($data);
                ++$sortOrder;
            }
        }
        
        return $parsedParams;
    }
    
    /**
     * Load and parse the parameters list from the given XML element into the given element model
     * 
     * @param BL_CustomGrid_Object $model Element model
     * @param Varien_Simplexml_Element $xmlElement Corresponding XML element
     * @return BL_CustomGrid_Model_Config_Abstract
     */
    protected function _loadElementModelParams(BL_CustomGrid_Object $model, Varien_Simplexml_Element $xmlElement)
    {
        if ($this->getAcceptParameters() && ($paramsXmlElement = $xmlElement->descend('parameters'))) {
            $params = $this->_parseXmlElementParamsArray($paramsXmlElement->asCanonicalArray());
            uasort($params, array($this, '_sortElementParams'));
        } else {
            $params = array();
        }
        
        $model->addData(
            array(
                'parameters' => $params,
                'is_customizable' => !empty($params),
            )
        );
        
        return $this;
    }
    
    /**
     * Handle additional preparations on the given element model
     * 
     * @param BL_CustomGrid_Object $model Element model
     * @param Varien_Simplexml_Element $xmlElement Corresponding XML element
     * @return BL_CustomGrid_Model_Config_Abstract
     */
    protected function _prepareElementModel(BL_CustomGrid_Object $model, Varien_Simplexml_Element $xmlElement)
    {
        return $this;
    }
    
    /**
     * Return the model corresponding to the given element code
     * 
     * @param string $code Element code
     * @return BL_CustomGrid_Object|null
     */
    public function getElementModelByCode($code)
    {
        $dataKey = 'element_model_' . $code;
        
        if (!$this->hasData($dataKey)) {
            if (($xmlElement = $this->getXmlElementByCode($code))
                && ($modelName = $xmlElement->getAttribute('model'))
                && $this->_checkElementModelCompliance($model = Mage::getSingleton($modelName))) {
                $module = ($xmlElement->getAttribute('module') ? $xmlElement->getAttribute('module') : 'customgrid');
                $helper = $this->_getSafeHelper((string) $module);
                
                $model->setData(
                    array(
                        'code'   => $xmlElement->getName(),
                        'type'   => $xmlElement->getAttribute('type'),
                        'module' => $module,
                        'name'   => $helper->__((string) $xmlElement->name),
                        'help'   => $helper->__((string) $xmlElement->help),
                        'helper' => $helper,
                        'sort_order'  => (int) $xmlElement->{'sort_order'}, // CheckStyle validator / snake-case
                        'description' => $helper->__((string) $xmlElement->description),
                        'is_customizable' => false,
                    )
                );
                
                $this->_loadElementModelParams($model, $xmlElement);
                $this->_prepareElementModel($model, $xmlElement);
            } else {
                $model = null;
            }
            
            $this->setData($dataKey, $model);
        }
        
        return $this->_getData($dataKey);
    }
    
    /**
     * Callback for elements models sorting
     * 
     * @param BL_CustomGrid_Object $modelA One element model
     * @param BL_CustomGrid_Object $modelB Another element model
     * @return int
     */
    protected function _sortElementsModels(BL_CustomGrid_Object $modelA, BL_CustomGrid_Object $modelB)
    {
        $result = $modelA->compareIntDataTo('sort_order', $modelB);
        return ($result === 0 ? $modelA->compareStringDataTo('name', $modelB) : $result);
    }
    
    /**
     * Return the models corresponding to the available elements
     * 
     * @param bool $sorted Whether the models should be sorted
     * @return BL_CustomGrid_Object[]
     */
    public function getElementsModels($sorted = false)
    {
        $models = array();
        
        foreach ($this->getElementsCodes() as $code) {
            if ($model = $this->getElementModelByCode($code)) {
                $models[$code] = $model;
            }
        }
        
        if ($sorted) {
            uasort($models, array($this, '_sortElementsModels'));
        }
        
        return $models;
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
