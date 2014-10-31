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

class BL_CustomGrid_Model_Options_Source extends Mage_Core_Model_Abstract
{
    static protected $_predefinedTypes = null;
    
    const TYPE_CUSTOM_LIST = 'custom_list';
    const TYPE_MAGE_MODEL  = 'mage_model';
    
    const MODEL_TYPE_MODEL          = 'model';
    const MODEL_TYPE_RESOURCE_MODEL = 'resource_model';
    const MODEL_TYPE_SINGLETON      = 'singleton';
    
    const RETURN_TYPE_OPTION_ARRAY = 'options_array';
    const RETURN_TYPE_OPTION_HASH  = 'options_hash';
    const RETURN_TYPE_VARIEN_OBJECT_COLLECTION = 'vo_collection';
    
    protected function _construct()
    {
        parent::_construct();
        $this->_init('customgrid/options_source');
        $this->setIdFieldName('source_id');
    }
    
    protected function _getOptionArray()
    {
        if (!$this->hasData('option_array')) {
            $options = array();
            
            if ($this->getType() == self::TYPE_CUSTOM_LIST) {
                if (!is_array($options = $this->getOptions())) {
                    $options = array();
                }
            } elseif ($this->getType() == self::TYPE_MAGE_MODEL) {
                try {
                    if ($this->getModelType() == self::MODEL_TYPE_SINGLETON) {
                        $model = Mage::getSingleton($this->getModelName());
                    } elseif ($this->getModelType() == self::MODEL_TYPE_RESOURCE_MODEL) {
                        $model = Mage::getResourceModel($this->getModelName());
                    } else {
                        $model = Mage::getModel($this->getModelName());
                    }
                    
                    $result = call_user_func(array($model, $this->getMethod()));
                    
                    if (is_array($result) || ($result instanceof Traversable)) {
                        $returnType = $this->_getData('return_type');
                        $valueKey = $this->_getData('value_key');
                        $labelKey = $this->_getData('label_key');
                        
                        foreach ($result as $key => $value) {
                            if ($returnType == self::RETURN_TYPE_OPTION_ARRAY) {
                                if (is_array($value) && isset($value['value']) && isset($value['label'])) {
                                    $options[] = array(
                                        'value' => $value['value'],
                                        'label' => $value['label'],
                                    );
                                }
                            } elseif ($returnType == self::RETURN_TYPE_OPTION_HASH) {
                                $options[] = array(
                                    'value' => $key,
                                    'label' => $value,
                                );
                            } elseif ($returnType == self::RETURN_TYPE_VARIEN_OBJECT_COLLECTION) {
                                if (is_object($value) && ($value instanceof Varien_Object)) {
                                    $options[] = array(
                                        'value' => $value->getData($valueKey),
                                        'label' => $value->getData($labelKey),
                                    );
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    $options = array();
                }
            }
            
            $this->setData('option_array', $options);
        }
        return $this->_getData('option_array');
    }
    
    public function getOptionArray()
    {
        return $this->_getOptionArray();
    }
    
    public function getOptionHash()
    {
        return Mage::helper('customgrid')->getOptionHashFromOptionArray($this->_getOptionArray());
    }
    
    protected function _getPredefinedTypes()
    {
        $types  = array();
        $helper = Mage::helper('customgrid');
        
        $optionArrays = array(
            'blcg_oa_yesno' => array(
                'model' => 'customgrid/system_config_source_yesno',
                'label' => $helper->__('Yes/No'),
            ),
            'blcg_oa_enableddisabled' => array(
                'model' => 'customgrid/system_config_source_enableddisabled',
                'label' => $helper->__('Enabled/Disabled'),
            )
        );
        
        foreach ($optionArrays as $typeId => $config) {
            $types[$typeId] = array(
                'name'        => $config['label'],
                'type'        => 'mage_model',
                'model_name'  => $config['model'],
                'model_type'  => 'model',
                'method'      => 'toOptionArray',
                'return_type' => self::RETURN_TYPE_OPTION_ARRAY,
                'value_key'   => 'value',
                'label_key'   => 'label',
            );
        }
        
        return $types;
    }
    
    public function getPredefinedTypes()
    {
        if (!is_array(self::$_predefinedTypes)) {
            $types = $this->_getPredefinedTypes();
            $response = new BL_CustomGrid_Object(array('types' => $types));
            Mage::dispatchEvent('blcg_options_source_predefined_types', array('response' => $response));
            
            if (is_array($types = $response->getTypes())) {
                self::$_predefinedTypes = $types;
            } else {
                self::$_predefinedTypes = array();
            }
        }
        return self::$_predefinedTypes;
    }
    
    public function getPredefinedType($typeId)
    {
        $types = self::getPredefinedTypes();
        return (isset($types[$typeId]) ? $types[$typeId] : null);
    }
    
    public function getTypesAsOptionHash($withPredefined = false)
    {
        $helper = Mage::helper('customgrid');
        $types  = array(
            self::TYPE_CUSTOM_LIST => $helper->__('Custom List'),
            self::TYPE_MAGE_MODEL  => $helper->__('Magento Model'),
        );
        
        if ($withPredefined) {
            $predefinedTypes = self::getPredefinedTypes();
            
            foreach ($predefinedTypes as $typeId => $type) {
                if (!isset($types[$typeId])) {
                    $types[$typeId] = $helper->__('%s (predefined)', $type['name']);
                }
            }
        }
        
        return $types;
    }
    
    public function getModelTypesAsOptionHash()
    {
        return array(
            self::MODEL_TYPE_MODEL          => Mage::helper('customgrid')->__('Model'),
            self::MODEL_TYPE_RESOURCE_MODEL => Mage::helper('customgrid')->__('Resource Model'),
            self::MODEL_TYPE_SINGLETON      => Mage::helper('customgrid')->__('Singleton'),
        );
    }
    
    public function getModelReturnTypesAsOptionHash()
    {
        return array(
            self::RETURN_TYPE_OPTION_ARRAY => Mage::helper('customgrid')->__('Options Array'),
            self::RETURN_TYPE_OPTION_HASH  => Mage::helper('customgrid')->__('Options Hash'),
            self::RETURN_TYPE_VARIEN_OBJECT_COLLECTION => Mage::helper('customgrid')->__('Varien_Object Collection'),
        );
    }
}
