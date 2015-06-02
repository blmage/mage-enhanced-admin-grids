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

/**
 * @method string getName() Return the name of this options source
 * @method string getDescription() Return the description of this options source
 * @method string getType() Return the type of this options source
 */

/**
 * @method string getName() Return the name of the options source
 * @method string getDescription() Return the description of the options source
 * @method string getType() Return the type of the options source
 * @method array getOptions() Return the available options (for sources based on a custom list)
 * @method string getModelName() Return the name of the Magento model (for sources based on a model)
 * @method string getModelType() Return the type of the Magento model (for sources based on a model)
 * @method string getMethod() Return which is the method to call on the Magento model (for sources based on a model)
 * @method string getReturnType() Return the return type of the Magento model method (for sources based on a model)
 * @method string getValueKey() Return the data key where to find the value in the result (for sources based on a model)
 * @method string getLabelKey() Return the data key where to find the label in the result (for sources based on a model)
 */

class BL_CustomGrid_Model_Options_Source extends Mage_Core_Model_Abstract
{
    /**
     * Predefined types
     * 
     * @var array|null
     */
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
    
    /**
     * Return the Magento model on which the options source is based
     * 
     * @return mixed
     */
    protected function _getMageModel()
    {
        if ($this->getModelType() == self::MODEL_TYPE_SINGLETON) {
            $model = Mage::getSingleton($this->getModelName());
        } elseif ($this->getModelType() == self::MODEL_TYPE_RESOURCE_MODEL) {
            $model = Mage::getResourceModel($this->getModelName());
        } else {
            $model = Mage::getModel($this->getModelName());
        }
        return $model;
    }
    
    /**
     * Return the option array for an options source based on a custom list
     * 
     * @return array
     */
    protected function _getCustomListOptionArray()
    {
        return (is_array($options = $this->getOptions()) ? $options : array());
    }
    
    /**
     * Parse the given value coming from a Magento model method into an option usable in an option array
     * 
     * @param string $methodReturnType Return type of the method from which the value is coming from
     * @param mixed $key Key
     * @param mixed $value Value
     * @return array|null
     */
    protected function _parseMageModelValue($methodReturnType, $key, $value)
    {
        $option = null;
        
        if ($methodReturnType == self::RETURN_TYPE_OPTION_ARRAY) {
            if (is_array($value) && isset($value['value']) && isset($value['label'])) {
                $option = array(
                    'value' => $value['value'],
                    'label' => $value['label'],
                );
            }
        } elseif ($methodReturnType == self::RETURN_TYPE_OPTION_HASH) {
            $option = array(
                'value' => $key,
                'label' => $value,
            );
        } elseif ($methodReturnType == self::RETURN_TYPE_VARIEN_OBJECT_COLLECTION) {
            if (is_object($value) && ($value instanceof Varien_Object)) {
                $option = array(
                    'value' => $value->getData($this->_getData('value_key')),
                    'label' => $value->getData($this->_getData('label_key')),
                );
            }
        }
        
        return $option;
    }
    
    /**
     * Return the option array for an options source based on a Magento model
     * 
     * @return array
     */
    protected function _getMageModelOptionArray()
    {
        $options = array(); 
        
        try {
            $model  = $this->_getMageModel();
            $result = call_user_func(array($model, $this->getMethod()));
            
            if (is_array($result) || ($result instanceof Traversable)) {
                $returnType = $this->_getData('return_type');
                
                foreach ($result as $key => $value) {
                    if (is_array($option = $this->_parseMageModelValue($returnType, $key, $value))) {
                        $options[] = $option;
                    }
                }
            }
        } catch (Exception $e) {
            $options = array();
        }
        
        return $options;
    }
    
    /**
     * Return available options as an option array
     * 
     * @return array
     */
    public function getOptionArray()
    {
        if (!$this->hasData('option_array')) {
            $options = array();
            
            if ($this->getType() == self::TYPE_CUSTOM_LIST) {
                $options = $this->_getCustomListOptionArray();
            } elseif ($this->getType() == self::TYPE_MAGE_MODEL) {
               $options = $this->_getMageModelOptionArray();
            }
            
            $this->setData('option_array', $options);
        }
        return $this->_getData('option_array');
    }
    
    /**
     * Return available options as an option hash
     * 
     * @return string[]
     */
    public function getOptionHash()
    {
        /** @var $helper BL_CustomGrid_Helper_Data */
        $helper = Mage::helper('customgrid');
        return $helper->getOptionHashFromOptionArray($this->getOptionArray());
    }
    
    /**
     * Return base predefined types for options sources
     * 
     * @return array
     */
    protected function _getPredefinedTypes()
    {
        $types  = array();
        /** @var $helper BL_CustomGrid_Helper_Data */
        $helper = Mage::helper('customgrid');
        
        $optionArrays = array(
            'blcg_oa_yesno' => array(
                'model' => 'customgrid/system_config_source_yesno',
                'label' => $helper->__('Yes/No'),
            ),
            'blcg_oa_enableddisabled' => array(
                'model' => 'customgrid/system_config_source_enableddisabled',
                'label' => $helper->__('Enabled/Disabled'),
            ),
            'blcg_oa_payment_methods' => array(
                'model' => 'customgrid/system_config_source_payment_allmethods',
                'label' => $helper->__('Payment Methods'),
            ),
            'blcg_oa_shipping_methods' => array(
                'model' => 'customgrid/system_config_source_shipping_methods',
                'label' => $helper->__('Shipping Methods (Sales > Orders)'),
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
    
    /**
     * Return predefined types for options sources
     * 
     * @return array
     */
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
    
    /**
     * Return the config values for the given predefined type
     * 
     * @param string $typeId Predefined type ID
     * @return array|null
     */
    public function getPredefinedType($typeId)
    {
        $types = self::getPredefinedTypes();
        return (isset($types[$typeId]) ? $types[$typeId] : null);
    }
    
    /**
     * Return the available types of options sources as an option hash
     * 
     * @param bool $withPredefined Whether predefined types should be included
     * @return string[]
     */
    public function getTypesAsOptionHash($withPredefined = false)
    {
        /** @var $helper BL_CustomGrid_Helper_Data */
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
    
    /**
     * Return handled model types as an option hash
     * 
     * @return string[]
     */
    public function getModelTypesAsOptionHash()
    {
        /** @var $helper BL_CustomGrid_Helper_Data */
        $helper = Mage::helper('customgrid');
        
        return array(
            self::MODEL_TYPE_MODEL          => $helper->__('Model'),
            self::MODEL_TYPE_RESOURCE_MODEL => $helper->__('Resource Model'),
            self::MODEL_TYPE_SINGLETON      => $helper->__('Singleton'),
        );
    }
    
    /**
     * Return handled return types as an option hash
     * 
     * @return string[]
     */
    public function getModelReturnTypesAsOptionHash()
    {
        /** @var $helper BL_CustomGrid_Helper_Data */
        $helper = Mage::helper('customgrid');
        
        return array(
            self::RETURN_TYPE_OPTION_ARRAY => $helper->__('Options Array'),
            self::RETURN_TYPE_OPTION_HASH  => $helper->__('Options Hash'),
            self::RETURN_TYPE_VARIEN_OBJECT_COLLECTION => $helper->__('Varien_Object Collection'),
        );
    }
}
