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
 * @copyright  Copyright (c) 2016 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Return the base helper of the given module, or this helper if the other is not accessible
     * 
     * @param string $module Helper module
     * @return Mage_Core_Helper_Abstract
     */
    public function getSafeHelper($module)
    {
        $helperClassName = Mage::getConfig()->getHelperClassName($module);
        return (class_exists($helperClassName, true) ? Mage::helper($module) : $this);
    }
    
    /**
     * Implode the given value, return an empty string if it is not an array
     * 
     * @param array $array Value to implode
     * @param string $glue Imploding glue
     * @return string
     */
    public function implodeArray($array, $glue = ',')
    {
        return (is_array($array) ? implode($glue, $array) : '');
    }
    
    /**
     * Unserialize the given value, always return an array (empty by default)
     * 
     * @param string $array Value to unserialize
     * @return array
     */
    public function unserializeArray($array)
    {
        return (($array !== '') && is_array($array = @unserialize($array)) ? $array : array());
    }
    
    /**
     * Parse the given value as an integer, but return null if it is an empty string
     * 
     * @param string $value Value to parse
     * @return int|null
     */
    protected function _parseIntValue($value)
    {
        return ($value !== '' ? (int) $value : null);
    }
    
    /**
     * Parse the integer values contained in the given csv string and return them as an array
     * 
     * @param string $string Csv string
     * @param bool $unique Whether only unique values should be returned
     * @param bool $sorted Whether returned values should be sorted
     * @param int|null $min Minimum allowed value (lesser will be excluded)
     * @param int|null $max Maximum allowed value (greater will be excluded)
     * @return array
     */
    public function parseCsvIntArray($string, $unique = true, $sorted = false, $min = null, $max = null)
    {
        $values = array_map(array($this, '_parseIntValue'), explode(',', $string));
        $filterCodes = array('!is_null($v)');
        
        if (!is_null($min)) {
            $filterCodes[] = '($v >= ' . (int) $min . ')';
        }
        if (!is_null($max)) {
            $filterCodes[] = '($v <= ' . (int) $max . ')';
        }
        
        $filterCode = 'return (' . implode(' && ', $filterCodes) . ');';
        $values = array_filter($values, create_function('$v', $filterCode));
        
        if ($unique) {
            $values = array_unique($values);
        }
        if ($sorted) {
            sort($values, SORT_NUMERIC);
        }
        
        return $values;
    }
    
    /**
     * Return an options hash corresponding to the given options array
     * 
     * @param array $optionArray Options array
     * @param bool $withEmpty Whether empty values from the array should be kept in the hash
     * @return string[]
     */
    public function getOptionHashFromOptionArray(array $optionArray, $withEmpty = false)
    {
        $optionHash = array();
        
        foreach ($optionArray as $key => $value) {
            if (is_array($value)) {
                if (isset($value['value']) && isset($value['label'])) {
                    if ($withEmpty || ($value['value'] !== '')) {
                        $optionHash[$value['value']] = $value['label'];
                    }
                }
            } else {
                // Seems to already be an options hash
                $optionHash[$key] = $value;
            }
        }
        
        return $optionHash;
    }
    
    /**
     * Return an options array corresponding to the given options hash
     * 
     * @param string[] $optionHash Options hash
     * @param bool $withEmpty Whether empty values from the hash should be kept in the array
     * @return array
     */
    public function getOptionArrayFromOptionHash(array $optionHash, $withEmpty = false)
    {
        $optionArray = array();
        
        foreach ($optionHash as $key => $value) {
            if (!is_array($value)) {
                if ($withEmpty || ($key !== '')) {
                    $optionArray[] = array(
                        'value' => $key,
                        'label' => $value,
                    );
                }
            } elseif (isset($value['value']) && isset($value['label'])) {
                // Seems to already be an options array, remove anyway empty values if needed
                if ($withEmpty || ($value['value'] !== '')) {
                    $optionArray[] = $value;
                }
            }
        }
        
        return $optionArray;
    }
    
    /**
     * Return the current Magento version's revision number
     * 
     * @return int
     */
    public function getMageVersionRevision()
    {
        $version = Mage::getVersionInfo();
        return (int) $version['revision'];
    }
    
    /**
     * Return whether the given Magento version corresponds to the current version
     * 
     * @param int $major Major version number
     * @param int $minor Minor version number
     * @param int|null $revision Revision version number
     * @return bool
     */
    public function isMageVersion($major, $minor, $revision = null)
    {
        $version = Mage::getVersionInfo();
        return ($version['major'] == $major)
            && ($version['minor'] == $minor)
            && (is_null($revision) || ($version['revision'] == $revision));
    }
    
    /**
     * Return whether the current Magento version is strictly greater than the given one
     * 
     * @param int $major Major version number
     * @param int $minor Minor version number
     * @param int|null $revision Revision version number
     * @return bool
     */
    public function isMageVersionGreaterThan($major, $minor, $revision = null)
    {
        $version = Mage::getVersionInfo();
        
        if (($currentMajor = (int) $version['major']) > $major) {
            return true;
        } elseif ($currentMajor == $major) {
            if (($currentMinor = (int) $version['minor']) > $minor) {
                return true;
            } elseif (($currentMinor == $minor) && !is_null($revision)) {
                return ((int) $version['revision'] > $revision);
            }
        }
        
        return false;
    }
    
    /**
     * Return whether the current Magento version is strictly lesser than the given one
     * 
     * @param int $major Major version number
     * @param int $minor Minor version number
     * @param int|null $revision Revision version number
     * @return bool
     */
    public function isMageVersionLesserThan($major, $minor, $revision = null)
    {
        $version = Mage::getVersionInfo();
        
        if (($currentMajor = (int) $version['major']) < $major) {
            return true;
        } elseif ($currentMajor == $major) {
            if (($currentMinor = (int) $version['minor']) < $minor) {
                return true;
            } elseif (($currentMinor == $minor) && !is_null($revision)) {
                return ((int) $version['revision'] < $revision);
            }
        }
        
        return false;
    }
    
    /**
     * Return whether the current Magento version is 1.4.x
     * 
     * @return bool
     */
    public function isMageVersion14()
    {
        return $this->isMageVersion(1, 4);
    }
    
    /**
     * Return whether the current Magento version is 1.5.x
     * 
     * @return bool
     */
    public function isMageVersion15()
    {
        return $this->isMageVersion(1, 5);
    }
    
    /**
     * Return whether the current Magento version is 1.6.x
     * 
     * @return bool
     */
    public function isMageVersion16()
    {
        return $this->isMageVersion(1, 6);
    }
    
    /**
     * Return whether the current Magento version is 1.7.x
     * 
     * @return bool
     */
    public function isMageVersion17()
    {
        return $this->isMageVersion(1, 7);
    }
    
    /**
     * Return whether the current Magento version is 1.8.x
     * 
     * @return bool
     */
    public function isMageVersion18()
    {
        return $this->isMageVersion(1, 8);
    }
    
    /**
     * Return whether the current Magento version is 1.9.x
     * 
     * @return bool
     */
    public function isMageVersion19()
    {
        return $this->isMageVersion(1, 8);
    }
    
    /**
     * Return whether the current request uses Ajax
     * 
     * @return bool
     */
    public function isAjaxRequest()
    {
        return $this->_getRequest()->isAjax();
    }
    
    /**
     * Return whether the given grid block has been rewrited by this extension
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return bool
     */
    public function isRewritedGridBlock(Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        return ($className = get_class($gridBlock))
            ? (bool) preg_match('#^BL_CustomGrid_Block_Rewrite_.+$#', $className)
            : false;
    }
    
    /**
     * Return a suitable column header name from the given key
     * 
     * @param string $key Column key
     * @return string
     */
    public function getColumnHeaderName($key)
    {
        $key = trim(str_replace('_', ' ', strtolower($key)));
        
        // Play on words case for translation
        // Try three of the whole possibilities, which should represent most of the possible ones
        $helper = Mage::helper('adminhtml');
        
        if (($key === ($result = $helper->__($key)))
            && (ucfirst($key) === ($result = $helper->__(ucfirst($key))))
            && (uc_words($key, ' ', ' ') === ($result = $helper->__(uc_words($key, ' ', ' '))))) {
            // Use basic key if not any translation succeeded
            $result = uc_words($key, ' ', ' ');
        }
        
        return $result;
    }
    
    /**
     * Return the default non-admin store ID
     * 
     * @return int
     */
    public function getDefaultNonAdminStoreId()
    {
        $stores = Mage::app()->getStores(false);
        $store  = array_shift($stores);
        return $store->getId();
    }
    
    /**
     * Unregister the resource singleton corresponding to the given class code
     * 
     * @param string $classCode Class code of the singletonized resource model
     * @return BL_CustomGrid_Helper_Data
     */
    public function unregisterResourceSingleton($classCode)
    {
        Mage::unregister('_resource_singleton/' . $classCode);
        return $this;
    }
}
