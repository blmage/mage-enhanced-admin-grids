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

class BL_CustomGrid_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function implodeArray($array, $glue=',')
    {
        return (is_array($array) ? implode($glue, $array) : '');
    }
    
    public function unserializeArray($array)
    {
        return (is_array($array = @unserialize($array)) ? $array : array());
    }
    
    protected function _parseIntValue($value)
    {
        return ($value !== '' ? intval($value) : null);
    }
    
    public function parseCsvIntArray($string, $unique=true, $sorted=false, $min=null, $max=null)
    {
        $values = array_map(array($this, '_parseIntValue'), explode(',', $string));
        $filterCodes = array('!is_null($v)');
        
        if ($unique) {
            $values = array_unique($values);
        }
        if (!is_null($min)) {
            $filterCodes[] = '($v >= '.intval($min).')';
        }
        if (!is_null($max)) {
            $filterCodes[] = '($v <= '.intval($max).')';
        }
        
        $filterCode = 'return ('.implode(' && ', $filterCodes).');';
        $values = array_filter($values, create_function('$v', $filterCode));
        
        if ($sorted) {
            sort($values, SORT_NUMERIC);
        }
        
        return $values;
    }
    
    public function getOptionsHashFromOptionsArray(array $optionsArray, $withEmpty=false)
    {
        $optionsHash = array();
        
        foreach ($optionsArray as $key => $value) {
            if (is_array($value)) {
                if (isset($value['value']) && isset($value['label'])) {
                    if ($withEmpty || ($value['value'] !== '')) {
                        $optionsHash[$value['value']] = $value['label'];
                    }
                }
            } else {
                // Seems to already be an options hash
                $optionsHash[$key] = $value;
            }
        }
        
        return $optionsHash;
    }
    
    public function getOptionsArrayFromOptionsHash(array $optionsHash, $withEmpty=false)
    {
        $optionsArray = array();
        
        foreach ($optionsHash as $key => $value) {
            if (!is_array($value)) {
                if ($withEmpty || ($key !== '')) {
                    $optionsArray[] = array(
                        'value' => $key,
                        'label' => $value,
                    );
                }
            } elseif (isset($value['value']) && isset($value['label'])) {
                // Seems to already be an options array, remove anyway empty values if needed
                if ($withEmpty || ($value['value'] !== '')) {
                    $optionsArray[] = $value;
                }
            }
        }
        
        return $optionsArray;
    }
    
    public function getColumnHeaderName($key)
    {
        // Beautify column key
        $key = trim(str_replace('_', ' ', strtolower($key)));
        
        // Play on words case for translation
        // Try three of the whole possibilities, which should represent most of the successfull ones
        $helper = Mage::helper('adminhtml');
        
        if (($key === ($result = $helper->__($key)))
            && (ucfirst($key) === ($result = $helper->__(ucfirst($key))))
            && (uc_words($key, ' ', ' ') === ($result = $helper->__(uc_words($key, ' ', ' '))))) {
            // Use basic key if no translation succeeded
            $result = uc_words($key, ' ', ' ');
        }
        
        return $result;
    }
    
    public function isMageVersion($major, $minor, $revision=null)
    {
        $infos = Mage::getVersionInfo();
        return (($infos['major'] == $major)
                 && ($infos['minor'] == $minor)
                 && (is_null($revision) || ($infos['revision'] == $revision)));
    }
    
    public function isMageVersionGreaterThan($major, $minor, $revision=null)
    {
        $infos  = Mage::getVersionInfo();
        
        if (($iMajor = intval($infos['major'])) > $major) {
            return true;
        } elseif ($iMajor == $major) {
            if (($iMinor = intval($infos['minor'])) > $minor) {
                return true;
            } elseif (($iMinor == $minor) && !is_null($revision)) {
                return (intval($infos['revision']) > $revision);
            }
        }
        
        return false;
    }
    
    public function isMageVersionLesserThan($major, $minor, $revision=null)
    {
        $infos  = Mage::getVersionInfo();
        
        if (($iMajor = intval($infos['major'])) < $major) {
            return true;
        } elseif ($iMajor == $major) {
            if (($iMinor = intval($infos['minor'])) < $minor) {
                return true;
            } elseif (($iMinor == $minor) && !is_null($revision)) {
                return (intval($infos['revision']) < $revision);
            }
        }
        
        return false;
    }
    
    public function isMageVersion14()
    {
        return $this->isMageVersion(1, 4);
    }
    
    public function isMageVersion15()
    {
        return $this->isMageVersion(1, 5);
    }
    
    public function isMageVersion16()
    {
        return $this->isMageVersion(1, 6);
    }
    
    public function isMageVersion17()
    {
        return $this->isMageVersion(1, 7);
    }
    
    public function getMageVersionRevision()
    {
        $infos = Mage::getVersionInfo();
        return $infos['revision'];
    }
    
    public function isRewritedGrid($block)
    {
        if ($class = get_class($block)) {
            return (bool) preg_match('#^BL_CustomGrid_Block_Rewrite_.+$#', $class);
        }
        return false;
    }
    
    public function isAjaxRequest()
    {
        return $this->_getRequest()->isAjax();
    }
}