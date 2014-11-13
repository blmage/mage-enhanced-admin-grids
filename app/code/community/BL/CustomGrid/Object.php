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

class BL_CustomGrid_Object extends Varien_Object
{
    protected $_clonableObjectsKeys = array();
    
    protected function _cloneObjects(array &$data, $key = '')
    {
        foreach ($data as $subKey => $value) {
            $valueKey = ($key !== '' ? $key . '/' . $subKey : $subKey);
            
            if (is_array($value)) {
                $this->_cloneObjects($data[$subKey], $valueKey);
            } elseif (in_array($valueKey, $this->_clonableObjectsKeys, true)) {
                $data[$subKey] = clone $value;
            }
        }
    }
    
    public function __clone()
    {
        $this->_cloneObjects($this->_data);
    }
    
    public function getClonableObjectsKeys()
    {
        return $this->_clonableObjectsKeys;
    }
    
    public function setClonableObjectsKeys(array $keys)
    {
        $this->_clonableObjectsKeys = array_unique($keys);
        return $this;
    }
    
    public function addClonableObjectsKeys(array $keys)
    {
        $this->_clonableObjectsKeys = array_unique(array_merge($this->_clonableObjectsKeys, $keys));
        return $this;
    }
    
    public function mergeData(array $data, $recursive = true)
    {
        if ($recursive) {
            $this->_data = array_merge_recursive($this->_data, $data);
        } else {
            $this->_data = array_merge($this->_data, $data);
        }
        return $this;
    }
    
    public function appendData($key, $value)
    {
        if (!is_array($data = $this->getDataSetDefault($key, array()))) {
            $data = array($data);
        }
        if (is_array($value)) {
            $data = array_merge($data, array_values($value));
        } else {
            $data[] = $value;
        }
        $this->setData($key, $data);
        return $this;
    }
    
    public function substractData($key, $value, $strict = false)
    {
        if ($this->hasData($key)) {
            if (is_array($data = $this->getData($key))) {
                $subKeys = array_keys($data, $value, $strict);
                
                if (!empty($subKeys)) {
                    foreach ($subKeys as $subKey) {
                        unset($data[$subKey]);
                    }
                    
                    $this->setData($key, $data);
                }
            } else {
                if ((!$strict && ($data == $value)) || ($strict && ($data === $value))) {
                    $this->unsetData($key);
                }
            }
        }
        return $this;
    }
    
    public function compareDataTo($key, BL_CustomGrid_Object $object)
    {
        $value = $this->getData($key);
        $otherValue = $object->getData($key);
        return ($value > $otherValue ? 1: ($value < $otherValue ? -1 : 0));
    }
    
    public function compareIntDataTo($key, BL_CustomGrid_Object $object)
    {
        $value = (int) $this->getData($key);
        $otherValue = (int) $object->getData($key);
        return ($value > $otherValue ? 1: ($value < $otherValue ? -1 : 0));
    }
    
    public function compareStringDataTo($key, BL_CustomGrid_Object $object, $withCase = true)
    {
        $value = (string) $this->getData($key);
        $otherValue = (string) $object->getData($key);
        return ($withCase ? strcmp($value, $otherValue) : strcasecmp($value, $otherValue));
    }
    
    public function sortData($key, $flags = SORT_REGULAR)
    {
        if (is_array($data = $this->getData($key))) {
            sort($data, $flags);
            $this->setData($key, $data);
        }
        return $this;
    }
    
    public function ksortData($key, $flags = SORT_REGULAR)
    {
        if (is_array($data = $this->getData($key))) {
            ksort($data, $flags);
            $this->setData($key, $data);
        }
        return $this;
    }
    
    protected function _explodeCompoundKey($key)
    {
        return array_filter(explode('/', $key), create_function('$v', 'return ($v !== "");'));
    }
    
    public function setData($key, $value = null)
    {
        if (is_string($key) && (strpos($key, '/') !== false)) {
            $this->_hasDataChanges = true;
            $data =& $this->_data;
            $keys =  $this->_explodeCompoundKey($key);
            
            foreach ($keys as $key) {
                if (!isset($data[$key])
                    || !(is_array($data[$key]) || ($data[$key] instanceof Varien_Object))) {
                    $data[$key] = array();
                }
                if ($data[$key] instanceof Varien_Object) {
                    $data =& $data[$key]->_data;
                } else {
                    $data =& $data[$key];
                }
            }
            
            $data = $value;
            return $this;
        }
        return parent::setData($key, $value);
    }
    
    public function unsetData($key = null)
    {
        if (is_string($key) && (strpos($key, '/') !== false)) {
            $this->_hasDataChanges = true;
            $data    =& $this->_data;
            $keys    =  $this->_explodeCompoundKey($key);
            $lastKey =  array_pop($keys);
            $found   =  true;
            
            foreach ($keys as $key) {
                if (isset($data[$key])
                    && (is_array($data[$key]) || ($data[$key] instanceof Varien_Object))) {
                    if ($data[$key] instanceof Varien_Object) {
                        $data =& $data[$key]->_data;
                    } else {
                        $data =& $data[$key];
                    }
                } else {
                    $found = false;
                    break;
                }
            }
            
            if ($found) {
                unset($data[$lastKey]);
            }
            
            return $this;
        }
        return parent::unsetData($key);
    }
    
    public function getDataSetDefault($key, $default)
    {
        if (is_string($key) && (strpos($key, '/') !== false)) {
            $data    =& $this->_data;
            $keys    =  $this->_explodeCompoundKey($key);
            $lastKey =  array_pop($keys);
            $found   = true;
            
            foreach ($keys as $key) {
                if (!isset($data[$key])
                    || !(is_array($data[$key]) || ($data[$key] instanceof Varien_Object))) {
                    $found = false;
                    $data[$key] = array();
                }
                if ($data[$key] instanceof Varien_Object) {
                    $data =& $data[$key]->_data;
                } else {
                    $data =& $data[$key];
                }
            }
            
            if (!$found || !isset($data[$lastKey])) {
                $data[$lastKey] = $default;
            }
            
            return $data[$lastKey];
        }
        return parent::getDataSetDefault($key, $default);
    }
    
    public function getNotEmptyData($key, $index = null, $default = null)
    {
        return (($data = $this->getData($key, $index)) ? $data : $default);
    }
    
    public function hasData($key = '')
    {
        if (is_string($key) && (strpos($key, '/') !== false)) {
            $data    =& $this->_data;
            $keys    =  $this->_explodeCompoundKey($key);
            $lastKey =  array_pop($keys);
            $found   =  true;
            
            foreach ($keys as $key) {
                if (isset($data[$key])
                    && (is_array($data[$key]) || ($data[$key] instanceof Varien_Object))) {
                    if ($data[$key] instanceof Varien_Object) {
                        $data =& $data[$key]->_data;
                    } else {
                        $data =& $data[$key];
                    }
                } else {
                    $found = false;
                    break;
                }
            }
            
            if ($found) {
                $found = array_key_exists($lastKey, $data);
            }
            
            return $found;
        }
        return parent::hasData($key);
    }
    
    public function __call($method, $args)
    {
        if (substr($method, 0, 2) == 'is') {
            $key = $this->_underscore(substr($method, 2));
            $result = false;
            
            if (array_key_exists('is_' . $key, $this->_data)) {
                $result = (bool) $this->_data['is_' . $key];
            } elseif (array_key_exists($key, $this->_data)) {
                $result = (bool) $this->_data[$key];
            }
            
            return $result;
        }
        return parent::__call($method, $args);
    }
}
