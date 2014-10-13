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

class BL_CustomGrid_Model_Grid_Type_Config extends BL_CustomGrid_Model_Config_Abstract
{
    public function getConfigType()
    {
        return BL_CustomGrid_Model_Config::TYPE_GRID_TYPES;
    }
    
    protected function _prepareCustomColumnBaseParams(array $params)
    {
        if (isset($params['@'])) {
            $module = (isset($params['@']['module']) ? $params['@']['module'] : 'customgrid'); 
            $translate = (isset($params['@']['translate']) ? explode(' ', $params['@']['translate']) : array());
            
            if (!$helper = Mage::helper((string) $module)) {
                $helper = Mage::helper('customgrid');
            }
            
            unset($params['@']);
        } else {
            $translate = array();
        }
        
        foreach ($params as $key => $param) {
            if (is_array($param)) {
                if (isset($param[0])) {
                    if (in_array($key, $translate)) {
                        $params[$key] = $helper->__((string) $param[0]);
                    } else {
                        $params[$key] = $param[0];
                    }
                } else {
                    $params[$key] = $this->_prepareCustomColumnBaseParams($param);
                }
            } elseif (in_array($key, $translate)) {
                $params[$key] = $helper->__((string) $param);
            }
        }
        
        return $params;
    }
    
    protected function _loadXmlElementCustomizationParams(Varien_Simplexml_Element $xmlElement)
    {
        $params = array();
        $sortOrder = 0;
        
        foreach ($xmlElement->asCanonicalArray() as $key => $data) {
            if (is_array($data)) {
                $data['sort_order'] = (isset($data['sort_order']) ? (int) $data['sort_order'] : 'top');
                $values = array();
                
                if (isset($data['values']) && is_array($data['values'])) {
                    foreach ($data['values'] as $value) {
                        if (is_array($value) && isset($value['value']) && isset($value['label'])) {
                            $values[] = $value;
                        }
                    }
                }
                
                $data['values'] = $values;
                
                if (isset($data['helper_block'])) {
                    $helperBlock = new BL_CustomGrid_Object();
                    
                    if (isset($data['helper_block']['data']) && is_array($data['helper_block']['data'])) {
                        $helperBlock->addData($data['helper_block']['data']);
                    }
                    if (isset($data['helper_block']['type'])) {
                        $helperBlock->setType($data['helper_block']['type']);
                    }
                    
                    $data['helper_block'] = $helperBlock;
                }
                
                $params[$key] = $data;
                ++$sortOrder;
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
    
    protected function _loadXmlElementCustomColumns(Varien_Simplexml_Element $xmlElement)
    {
        $columnModels = array();
        
        if ($columnsXmlElement = $xmlElement->descend('custom_columns')) {
            foreach ($columnsXmlElement->children() as $columnId => $columnXmlElement) {
                $customColumn = $columnXmlElement->asArray();
                
                if (!isset($customColumn['@']) || !isset($customColumn['@']['model'])) {
                    continue;
                }
                
                $columnModel = Mage::getModel($customColumn['@']['model']);
                $columnModel->setId($columnId);
                $module = (isset($customColumn['@']['module']) ? (string) $customColumn['@']['module'] : 'customgrid'); 
                $columnModel->setModule($module);
                
                if (!$helper = Mage::helper($module)) {
                    $helper = Mage::helper('customgrid');
                }
                
                $rawFields = array('locked_renderer');
                $booleanFields = array('allow_store', 'allow_renderers', 'allow_customization', 'allow_editor');
                $translatableFields = array('name', 'description', 'warning', 'group', 'renderer_label');
                
                foreach ($rawFields as $field) {
                    if (isset($customColumn[$field])) {
                        $columnModel->setDataUsingMethod($field, $customColumn[$field]);
                    }
                }
                foreach ($booleanFields as $field) {
                    if (isset($customColumn[$field])) {
                        $columnModel->setDataUsingMethod($field, (bool) $customColumn[$field]);
                    }
                }
                foreach ($translatableFields as $field) {
                    if (isset($customColumn[$field])) {
                        $columnModel->setDataUsingMethod($field, $helper->__((string) $customColumn[$field]));
                    }
                }
                
                if (isset($customColumn['availability'])) {
                    $availabilityTypes  = array('allowed', 'excluded');
                    $availabilityValues = array('versions', 'blocks', 'rewrites');
                    
                    foreach ($availabilityTypes as $type) {
                        foreach ($availabilityValues as $value) {
                            $key = $type . '_' . $value;
                            
                            if (isset($customColumn['availability'][$key])) {
                                $value = $customColumn['availability'][$key];
                                
                                if (!is_array($value)) {
                                    $value = array($value);
                                } else {
                                    $value = array_filter($value, 'is_scalar');
                                }
                                
                                call_user_func(
                                    array($columnModel, 'set' . $this->_camelize($key)),
                                    array_values($value),
                                    true
                                );
                            }
                        }
                    }
                }
                
                if (isset($customColumn['block_params']) && is_array($customColumn['block_params'])) {
                    $params = $this->_prepareCustomColumnBaseParams($customColumn['block_params']);
                    $columnModel->setBlockParams($params, true);
                }
                if (isset($customColumn['config_params']) && is_array($customColumn['config_params'])) {
                    $params = $this->_prepareCustomColumnBaseParams($customColumn['config_params']);
                    $columnModel->setConfigParams($params, true);
                }
                
                if ($columnModel->getAllowCustomization()) {
                    $configWindow = array();
                    
                    if ($paramsXmlElement = $columnXmlElement->descend('customization_params')) {
                        if ($configXmlElement = $paramsXmlElement->descend('config')) {
                            foreach ($this->_loadXmlElementCustomizationParams($configXmlElement) as $key => $param) {
                                $sortOrder = $param['sort_order'];
                                unset($param['sort_order']);
                                $columnModel->addCustomizationParam($key, $param, $sortOrder);
                            }
                        }
                        if ($windowXmlElement = $paramsXmlElement->descend('config_window')) {
                            $configWindow = $this->_loadXmlElementConfigWindow($windowXmlElement, $helper);
                        }
                    }
                    if (!isset($configWindow['title'])) {
                        $configWindow['title'] = $helper->__('Customization : %s', $columnModel->getName());
                    }
                    
                    $columnModel->setCustomizationWindowConfig($configWindow, true);
                }
                
                $columnModels[$columnId] = $columnModel;
            }
        }
        
        return $columnModels;
    }
    
    public function getTypesInstances()
    {
        $types = array();
        
        foreach ($this->getElementsCodes() as $code) {
            if ($instance = $this->getElementInstanceByCode($code)) {
                $types[$code] = $instance;
            }
        }
        
        return $types;
    }
    
    public function getTypeInstanceByCode($code)
    {
        return parent::getElementInstanceByCode($code);
    }
    
    public function getCustomColumnsByTypeCode($code)
    {
        if ($xmlElement = $this->getXmlElementByCode($code)) {
            return $this->_loadXmlElementCustomColumns($xmlElement);
        }
        return array();
    }
    
    public function getTypesAsOptionHash($sorted = false, $withEmpty = false)
    {
        $types = array();
        
        foreach ($this->getElementsArray() as $type) {
            $types[$type->getCode()] = $type->getName();
        }
        if ($sorted) {
            uasort($types, 'strcmp');
        }
        if ($withEmpty) {
            $types = array('' => Mage::helper('customgrid')->__('None')) + $types;
        }
        
        return $types;
    }
}
