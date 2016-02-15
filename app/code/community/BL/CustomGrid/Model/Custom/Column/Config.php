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

class BL_CustomGrid_Model_Custom_Column_Config
{
    /**
     * Keys of the raw fields definable in the XML configuration (pre-flipped for convenience)
     * 
     * @var array
     */
    static protected $_xmlRawFieldsKeys = array(
        'locked_renderer' => true,
    );
    
    /**
     * Keys of the boolean fields definable in the XML configuration (pre-flipped for convenience)
     * 
     * @var array
     */
    static protected $_xmlBooleanFieldsKeys = array(
        'allow_store'         => true,
        'allow_renderers'     => true,
        'allow_customization' => true,
        'allow_editor'        => true,
    );
    
    /**
     * Keys of the translatable fields definable in the XML configuration (pre-flipped for convenience)
     * 
     * @var array
     */
    static protected $_xmlTranslatableFieldsKeys = array(
        'name'           => true,
        'description'    => true,
        'warning'        => true,
        'group'          => true,
        'renderer_label' => true,
    );
    
    /**
     * Cast the given value to boolean
     * 
     * @param mixed $value Castable value
     * @return bool
     */
    protected function _getBooleanValue($value)
    {
        return (bool) $value;
    }
    
    /**
     * Extract and return the raw fields values from the given XML values
     * 
     * @param array $xmlValues XML values
     * @return array
     */
    protected function _getRawFieldsFromXmlValues(array $xmlValues)
    {
        return array_intersect_key($xmlValues, self::$_xmlRawFieldsKeys);
    }
    
    /**
     * Extract and return the boolean fields values from the given XML values
     * 
     * @param array $xmlValues XML values
     * @return array
     */
    protected function _getBooleanFieldsFromXmlValues(array $xmlValues)
    {
        return array_map(
            array($this, '_getBooleanValue'),
            array_intersect_key($xmlValues, self::$_xmlBooleanFieldsKeys)
        );
    }
    
    /**
     * Extract and return the translatable fields values from the given XML values
     * 
     * @param array $xmlValues XML values
     * @param Mage_Core_Helper_Abstract $helper Helper usable for translation
     * @return array
     */
    protected function _getTranslatableFieldsFromXmlValues(array $xmlValues, Mage_Core_Helper_Abstract $helper)
    {
        return array_map(
            array($helper, '__'),
            array_intersect_key($xmlValues, self::$_xmlTranslatableFieldsKeys)
        );
    }
    
    /**
     * Extract and return the availability fields values from the given XMLv alues
     * 
     * @param array $xmlValues XML values
     * @return array
     */
    protected function _getAvailabilityFieldsFromXmlValues(array $xmlValues)
    {
        $availabilityFields = array();
        
        if (isset($xmlValues['availability'])) {
            $availabilityTypes  = array('allowed', 'excluded');
            $availabilityValues = array('versions', 'blocks', 'rewrites');
            
            foreach ($availabilityTypes as $type) {
                foreach ($availabilityValues as $value) {
                    $key = $type . '_' . $value;
                    
                    if (isset($xmlValues['availability'][$key])) {
                        $value = $xmlValues['availability'][$key];
                        
                        if (!is_array($value)) {
                            $value = array($value);
                        } else {
                            $value = array_filter($value, 'is_scalar');
                        }
                        
                        $availabilityFields[$key] = array_values($value);
                    }
                }
            }
        }
        
        return $availabilityFields;
    }
    
    protected function _getParsedBaseParams(array $params)
    {
        if (isset($params['@'])) {
            /** @var $baseHelper BL_CustomGrid_Helper_Data */
            $baseHelper = Mage::helper('customgrid');
            $module = (isset($params['@']['module']) ? $params['@']['module'] : 'customgrid'); 
            $translatable = (isset($params['@']['translate']) ? explode(' ', $params['@']['translate']) : array());
            $helper = $baseHelper->getSafeHelper((string) $module);
            unset($params['@']);
        } else {
            $translatable = false;
        }
        
        foreach ($params as $key => $param) {
            if (is_array($param)) {
                if (isset($param[0])) {
                    if (is_array($translatable) && in_array($key, $translatable)) {
                        $params[$key] = $helper->__((string) $param[0]);
                    } else {
                        $params[$key] = $param[0];
                    }
                } else {
                    $params[$key] = $this->_getParsedBaseParams($param);
                }
            } elseif (is_array($translatable) && in_array($key, $translatable)) {
                $params[$key] = $helper->__((string) $param);
            }
        }
        
        return $params;
    }
    
    protected function _loadXmlElementCustomizationParams(Varien_Simplexml_Element $xmlElement)
    {
        /** @var $helper BL_CustomGrid_Helper_Xml_Config */
        $helper = Mage::helper('customgrid/xml_config');
        $params = array();
        
        foreach ($xmlElement->asCanonicalArray() as $key => $data) {
            if (is_array($data)) {
                $data['sort_order'] = (isset($data['sort_order']) ? (int) $data['sort_order'] : 'top');
                $data['values'] = $helper->getElementParamOptionsValues($data);
                $data['helper_block'] = $helper->getElementParamHelperBlock($data);
                $params[$key] = $data;
            }
        }
        
        return $params;
    }
    
    protected function _loadXmlElementConfigWindow(
        Varien_Simplexml_Element $xmlElement,
        Mage_Core_Helper_Abstract $helper
    ) {
        $configWindow = $xmlElement->asCanonicalArray();
        
        if (isset($configWindow['width'])) {
            if (($value = (int) $configWindow['width']) > 0) {
                $configWindow['width'] = $value;
            } else {
                unset($configWindow['width']);
            }
        }
        if (isset($configWindow['height'])) {
            if (($value = (int) $configWindow['height']) > 0) {
                $configWindow['height'] = $value;
            } else {
                unset($configWindow['height']);
            }
        }
        if (isset($configWindow['title'])) {
            $configWindow['title'] = $helper->__((string) $configWindow['title']);
        }
        
        return $configWindow;
    }
    
    protected function _prepareCustomColumnAvailabilityFields(
        BL_CustomGrid_Model_Custom_Column_Abstract $customColumn,
        array $xmlValues
    ) {
        /** @var $stringHelper BL_CustomGrid_Helper_String */
        $stringHelper = Mage::helper('customgrid/string');
        
        foreach ($this->_getAvailabilityFieldsFromXmlValues($xmlValues) as $key => $value) {
            call_user_func(
                array($customColumn, 'set' . $stringHelper->camelize($key)),
                $value,
                true
            );
        }
        
        return $this;
    }
    
    protected function _prepareCustomColumnBaseParams(
        BL_CustomGrid_Model_Custom_Column_Abstract $customColumn,
        array $xmlValues
    ) {
        if (isset($xmlValues['block_params']) && is_array($xmlValues['block_params'])) {
            $customColumn->setBlockParams($this->_getParsedBaseParams($xmlValues['block_params']), true);
        }
        if (isset($xmlValues['config_params']) && is_array($xmlValues['config_params'])) {
            $customColumn->setConfigParams($this->_getParsedBaseParams($xmlValues['config_params']), true);
        }
        return $this;
    }
    
    protected function _prepareCustomColumnCustomizationParams(
        BL_CustomGrid_Model_Custom_Column_Abstract $customColumn,
        Varien_Simplexml_Element $xmlElement,
        Mage_Core_Helper_Abstract $helper
    ) {
        if ($customColumn->getAllowCustomization()) {
            $configWindow = array();
            
            if ($customizationXmlElement = $xmlElement->descend('customization_params')) {
                if ($configXmlElement = $customizationXmlElement->descend('config')) {
                    foreach ($this->_loadXmlElementCustomizationParams($configXmlElement) as $key => $param) {
                        $sortOrder = $param['sort_order'];
                        unset($param['sort_order']);
                        $customColumn->addCustomizationParam($key, $param, $sortOrder);
                    }
                }
                if ($configWindowXmlElement = $customizationXmlElement->descend('config_window')) {
                    $configWindow = $this->_loadXmlElementConfigWindow($configWindowXmlElement, $helper);
                }
            }
            if (!isset($configWindow['title'])) {
                $configWindow['title'] = $helper->__('Customization : %s', $customColumn->getName());
            }
            
            $customColumn->setCustomizationWindowConfig($configWindow, true);
        }
        return $this;
    }
    
    public function initializeCustomColumnFromXmlConfig(
        BL_CustomGrid_Model_Custom_Column_Abstract $customColumn,
        Varien_Simplexml_Element $xmlElement,
        array $xmlValues
    ) {
        if (!isset($xmlValues['@'])) {
            $xmlValues = $xmlElement->asArray();
        }
        
        /** @var $baseHelper BL_CustomGrid_Helper_Data */
        $baseHelper = Mage::helper('customgrid');
        $module = (isset($xmlValues['@']['module']) ? (string) $xmlValues['@']['module'] : 'customgrid'); 
        $customColumn->setModule($module);
        $helper = $baseHelper->getSafeHelper($module);
        
        $customColumn->addData($this->_getRawFieldsFromXmlValues($xmlValues));
        $customColumn->addData($this->_getBooleanFieldsFromXmlValues($xmlValues));
        $customColumn->addData($this->_getTranslatableFieldsFromXmlValues($xmlValues, $helper));
        
        $this->_prepareCustomColumnAvailabilityFields($customColumn, $xmlValues);
        $this->_prepareCustomColumnBaseParams($customColumn, $xmlValues);
        $this->_prepareCustomColumnCustomizationParams($customColumn, $xmlElement, $helper);
        
        return $this;
    }
}
