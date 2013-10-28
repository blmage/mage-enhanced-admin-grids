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
 * @copyright  Copyright (c) 2012 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_Grid_Type
    extends BL_CustomGrid_Model_Config_Abstract
{
    public function getConfigType()
    {
        return BL_CustomGrid_Model_Config::TYPE_GRID_TYPES;
    }
    
    protected function _parseColumnCommonParams(array $params)
    {
        if (isset($params['@'])) {
            $translate = (isset($params['@']['translate']) ? explode(' ', $params['@']['translate']) : array());
            $module    = (isset($params['@']['module']) ? $params['@']['module'] : 'customgrid'); 
            $helper    = Mage::helper((string)$module);
            unset($params['@']);
        } else {
            $translate = array();
        }
        foreach ($params as $key => $param) {
            if (is_array($param)) {
                if (isset($param[0])) {
                    if (in_array($key, $translate)) {
                        $params[$key] = $helper->__((string)$param[0]);
                    } else {
                        $params[$key] = $param[0];
                    }
                } else {
                    $params[$key] = $this->_parseColumnCommonParams($param);
                }
            } elseif (in_array($key, $translate)) {
                $params[$key] = $helper->__((string)$param);
            }
        }
        return $params;
    }
    
    protected function _loadXmlElementCustomParams($element)
    {
        $params    = array();
        $sortOrder = 0;
        
        foreach ($element->asCanonicalArray() as $key => $data) {
            if (is_array($data)) {
                $data['sort_order'] = (isset($data['sort_order']) ? (int)$data['sort_order'] : 'top');
                
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
                
                $params[$key] = $data;
                $sortOrder++;
            }
        }
        
        return $params;
    }
    
    protected function _loadXmlElementConfigWindow($element, $helper)
    {
        $configWindow = $element->asArray();
        
        if (isset($configWindow['width'])) {
            if (($v = intval($configWindow['width'])) > 0) {
                $configWindow['width'] = $v;
            } else {
                unset($configWindow['width']);
            }
        }
        if (isset($configWindow['height'])) {
            if (($v = intval($configWindow['height'])) > 0) {
                $configWindow['height'] = $v;
            } else {
                unset($configWindow['height']);
            }
        }
        if (isset($configWindow['title'])) {
            $configWindow['title'] = $helper->__((string)$configWindow['title']);
        }
        
        return $configWindow;
    }
    
    protected function _loadXmlElementColumns($element)
    {
        if ($columns = $element->descend('custom_columns')) {
            foreach ($columns->children() as $id => $columnXml) {
                $column = $columnXml->asArray();
                
                // Prepare column singleton
                if (!isset($column['@']) || !isset($column['@']['model'])) {
                    continue;
                }
                $model = Mage::getModel($column['@']['model'], array('from_custom_grid_xml' => true));
                $model->setId($id);
                
                // Apply main raw fields
                $rawFields = array('locked_renderer');
                
                foreach ($rawFields as $field) {
                    if (isset($column[$field])) {
                        $model->setDataUsingMethod($field, $column[$field]);
                    }
                }
                
                // Apply translated base fields
                $module = (isset($column['@']['module']) ? $column['@']['module'] : 'customgrid'); 
                $model->setModule($module);
                $helper = Mage::helper((string)$module);
                $translate = array('name', 'description', 'warning', 'group', 'renderer_label');
                
                foreach ($translate as $field) {
                    if (isset($column[$field])) {
                        $model->setDataUsingMethod($field, $helper->__((string)$column[$field]));
                    }
                }
                
                // Apply main boolean flags
                $boolFlags = array(
                    'allow_store',
                    'allow_renderers',
                    'allow_customization',
                    'allow_editor'
                );
                
                foreach ($boolFlags as $flag) {
                    if (isset($column[$flag])) {
                        $model->setDataUsingMethod($flag, (bool)$column[$flag]);
                    }
                }
                
                // Apply availability config
                if (isset($column['availability'])) {
                    $listsVariants = array('allowed', 'excluded');
                    $listsKeys     = array('versions', 'blocks', 'rewrites');
                    
                    foreach ($listsVariants as $variant) {
                        foreach ($listsKeys as $key) {
                            $key = $variant.'_'.$key;
                            
                            if (isset($column['availability'][$key])) {
                                $value = $column['availability'][$key];
                                
                                if (!is_array($value)) {
                                    $value = array($value);
                                } else {
                                    $value = array_filter($value, 'is_scalar');
                                }
                                
                                call_user_func(array($model, 'add'.$this->_camelize($key)), array_values($value), true);
                            }
                        }
                    }
                }
                
                // Parse and apply common params
                if (isset($column['grid_params']) && is_array($column['grid_params'])) {
                    $model->setGridParams($this->_parseColumnCommonParams($column['grid_params']), true);
                }
                if (isset($column['model_params']) && is_array($column['model_params'])) {
                    $model->setModelParams($this->_parseColumnCommonParams($column['model_params']), true);
                }
                // @todo editor params
                
                // Parse and apply custom params config
                if ($model->getAllowCustomization()) {
                    $configWindow = array();
                    
                    if ($paramsXml = $columnXml->descend('custom_params')) {
                        if ($configXml = $paramsXml->descend('config')) {
                            foreach ($this->_loadXmlElementCustomParams($configXml) as $key => $param) {
                                $sortOrder = $param['sort_order'];
                                unset($param['sort_order']);
                                $model->addCustomParam($key, $param, $sortOrder);
                            }
                        }
                        if ($configXml = $paramsXml->descend('config_window')) {
                            $configWindow = $this->_loadXmlElementConfigWindow($configXml, $helper);
                        }
                    }
                    if (!isset($configWindow['title'])) {
                        $configWindow['title'] = $helper->__('Customization : %s', $model->getName());
                    }
                    
                    $model->setCustomParamsWindowConfig($configWindow, true);
                }
                
                $model->finalizeConfig();
                $models[$id] = $model;
            }
            
            return $models;
        }
        return array();
    }
    
    public function getTypeInstanceByCode($code, $params=null)
    {
        return parent::getElementInstanceByCode($code, $params);
    }
    
    public function getTypeCustomColumnsByCode($code)
    {
        if ($element = $this->getXmlElementByCode($code)) {
            return $this->_loadXmlElementColumns($element);
        }
        return array();
    }
    
    public function getTypesInstances()
    {
        $types = array();
        
        foreach ($this->getElementsArray() as $type) {
            if ($instance = $this->getElementInstanceByCode($type['code'])) {
                $types[$type['code']] = $instance;
            }
        }
        
        return $types;
    }
    
    public function getTypesAsOptionHash($sortOnName=false)
    {
        $types = array();
        
        foreach ($this->getElementsArray() as $type) {
            $types[$type['code']] = $type['name'];
        }
        if ($sortOnName) {
            uasort($types, 'strcmp');
        }
        
        return $types;
    }
}