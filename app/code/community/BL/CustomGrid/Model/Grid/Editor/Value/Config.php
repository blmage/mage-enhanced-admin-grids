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

class BL_CustomGrid_Model_Grid_Editor_Value_Config extends BL_CustomGrid_Object
{
    /**
     * Return the data required by the editor JS object
     * 
     * @return array
     */
    public function getEditorJsData()
    {
        return array(
            'formUrl' => $this->getData('global/form_url'),
            'saveUrl' => $this->getData('global/save_url'),
            'idsKey'  => $this->getData('request/ids_key'),
            'additionalKey' => $this->getData('request/additional_key'),
            'columnParams'  => $this->getData('request/column_params'),
            'inGrid'  => (bool) $this->getData('form/is_in_grid'),
            'window'  => $this->getData('window'),
        );
    }
    
    /**
     * Return the result of a call to the callback stored in the given data key.
     * Base callback parameters (if any) will be searched for in the key "{$callbackKey}_params",
     * and the given additional parameters will be appended after them.
     * Callbacks can be useful (or even required) when dealing with values that can not be determined at the time
     * the config is built.
     * 
     * @param string $callbackKey Data key where the callback is stored
     * @param array $additionalParams Parameters to append to the base parameters
     * @return mixed
     */
    public function runConfigCallback($callbackKey, array $additionalParams = array())
    {
        $value = null;
        
        if (is_callable($callable = $this->getData($callbackKey))) {
            $callbackParams = $this->getData($callbackKey . '_params');
            
            $callableParams = array_merge(
                (is_array($callbackParams) ? array_values($callbackParams) : array()),
                $additionalParams
            );
            
            $value = call_user_func_array($callable, $callableParams);
        }
        
        return $value;
    }
    
    /**
     * Return the value for the given key, but throw an exception if none can be found
     * 
     * @param string $key Value key
     * @return mixed
     * @throws Mage_Core_Exception
     */
    protected function _getRequiredValue($key)
    {
        if (!$this->hasData($key)) {
            $exception = new Exception();Mage::log($exception->getTraceAsString());
            Mage::throwException('Missing required value in editor value config : "' . $key . '"');
        }
        return $this->getData($key); 
    }
    
    /**
     * Return the edited attribute model
     * 
     * @return Mage_Eav_Model_Entity_Attribute
     */
    public function getAttribute()
    {
        return $this->_getRequiredValue('global/attribute');
    }
    
    /**
     * Return the key where to search for the edited value in the edited entities
     * 
     * @return string
     */
    public function getEntityValueKey()
    {
        return $this->_getRequiredValue('global/entity_value_key');
    }
    
    /**
     * Return the form field values
     * 
     * @return array
     */
    public function getFormFieldValues()
    {
        return (array) $this->_getRequiredValue('form_field');
    }
    
    /**
     * Return the form field ID
     * 
     * @return string
     */
    public function getFormFieldId()
    {
        return $this->_getRequiredValue('form_field/id');
    }
    
    /**
     * Return the form field name
     * 
     * @return string
     */
    public function getFormFieldName()
    {
        return $this->_getRequiredValue('form_field/name');
    }
    
    /**
     * Return the form field type
     * 
     * @return string
     */
    public function getFormFieldType()
    {
        return $this->_getRequiredValue('form_field/type');
    }
    
    /**
     * Return the key where to store / search for the user-defined values in editor requests
     * 
     * @return string
     */
    public function getRequestValuesKey()
    {
        return $this->_getRequiredValue('request/values_key');
    }
}
