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
 * @copyright  Copyright (c) 2011 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_Options_Source
    extends Mage_Core_Model_Abstract
{
    protected $_optionsArray = null;
    
    const SOURCE_TYPE_CUSTOM_LIST = 'custom_list';
    const SOURCE_TYPE_MAGE_MODEL  = 'mage_model';
    
    const SOURCE_MODEL_TYPE_MODEL          = 'model';
    const SOURCE_MODEL_TYPE_RESOURCE_MODEL = 'resource_model';
    const SOURCE_MODEL_TYPE_SINGLETON      = 'singleton';
    
    const SOURCE_MODEL_RETURN_TYPE_OPTIONS_ARRAY = 'options_array';
    const SOURCE_MODEL_RETURN_TYPE_OPTIONS_HASH  = 'options_hash';
    const SOURCE_MODEL_RETURN_TYPE_VO_COLLECTION = 'vo_collection';
    
    protected function _construct()
    {
        parent::_construct();
        $this->_init('customgrid/options_source');
        $this->setIdFieldName('source_id');
    }
    
    public function _collectOptions()
    {
        if (is_null($this->_optionsArray)) {
            $this->_optionsArray = array();
            
            if ($this->getType() == self::SOURCE_TYPE_CUSTOM_LIST) {
                // Custom list
                if (is_array($this->getOptions())) {
                    // Build an option array from custom list's options
                    foreach ($this->getOptions() as $option) {
                        $this->_optionsArray[] = array(
                            'value' => $option['value'],
                            'label' => $option['label'],
                        );
                    }
                }
            } elseif ($this->getType() == self::SOURCE_TYPE_MAGE_MODEL) {
                // Magento model
                try {
                    if ($this->getModelType() == self::SOURCE_MODEL_TYPE_SINGLETON) {
                        $model = Mage::getSingleton($this->getModelName());
                    } elseif ($this->getModelType() == self::SOURCE_MODEL_TYPE_RESOURCE_MODEL) {
                        $model = Mage::getResourceModel($this->getModelName());
                    } else {
                        $model = Mage::getModel($this->getModelName());
                    }
                    
                    // Get options from given model's method
                    $result = call_user_func(array($model, $this->getMethod()));
                    
                    if (is_array($result) || ($result instanceof Traversable)) {
                        foreach ($result as $key => $value) {
                            if ($this->_getData('return_type') == self::SOURCE_MODEL_RETURN_TYPE_OPTIONS_ARRAY) {
                                // Take "options array"-looking values
                                if (is_array($value) && isset($value['value']) && isset($value['label'])) {
                                    $this->_optionsArray[] = array(
                                        'value' => $value['value'],
                                        'label' => $value['label'],
                                    );
                                }
                            } elseif ($this->_getData('return_type') == self::SOURCE_MODEL_RETURN_TYPE_OPTIONS_HASH) {
                                // Simply build options array from hash values
                                $this->_optionsArray[] = array(
                                    'value' => $key,
                                    'label' => $value,
                                );
                            } elseif ($this->_getData('return_type') == self::SOURCE_MODEL_RETURN_TYPE_VO_COLLECTION) {
                                // Take values from Varien_Object instances
                                if (is_object($value) && ($value instanceof Varien_Object)) {
                                    $this->_optionsArray[] = array(
                                        'value' => $value->getData($this->_getData('value_key')),
                                        'label' => $value->getData($this->_getData('label_key')),
                                    );
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    // If an error occured (wrong model/method, ..), use empty array
                }
            }
        }
        return $this->_optionsArray;
    }
    
    public function getOptionsArray()
    {
        return $this->_collectOptions();
    }
    
    public function getOptionsHash()
    {
        $result = $this->_collectOptions();
        return Mage::helper('customgrid')->getOptionsHashFromOptionsArray($result);
    }
    
    static public function getTypesAsOptionHash()
    {
        return array(
            self::SOURCE_TYPE_CUSTOM_LIST => Mage::helper('customgrid')->__('Custom List'),
            self::SOURCE_TYPE_MAGE_MODEL  => Mage::helper('customgrid')->__('Magento Model'),
        );
    }
    
    static public function getModelTypesAsOptionHash()
    {
        return array(
            self::SOURCE_MODEL_TYPE_MODEL          => Mage::helper('customgrid')->__('Model'),
            self::SOURCE_MODEL_TYPE_RESOURCE_MODEL => Mage::helper('customgrid')->__('Resource Model'),
            self::SOURCE_MODEL_TYPE_SINGLETON      => Mage::helper('customgrid')->__('Singleton'),
        );
    }
    
    static public function getModelReturnTypesAsOptionHash()
    {
        return array(
            self::SOURCE_MODEL_RETURN_TYPE_OPTIONS_ARRAY => Mage::helper('customgrid')->__('Options Array'),
            self::SOURCE_MODEL_RETURN_TYPE_OPTIONS_HASH  => Mage::helper('customgrid')->__('Options Hash'),
            self::SOURCE_MODEL_RETURN_TYPE_VO_COLLECTION => Mage::helper('customgrid')->__('Varien_Object Collection'),
        );
    }
}