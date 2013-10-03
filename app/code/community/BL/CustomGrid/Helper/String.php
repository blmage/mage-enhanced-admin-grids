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

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Core
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Helper_String extends Mage_Core_Helper_Abstract
{
    const ICONV_CHARSET = 'UTF-8'; 
    
    /**
     * Truncate a string to a certain length if necessary, appending the $etc string.
     * $remainder will contain the string that has been replaced with $etc.
     *
     * @param string $string
     * @param int $length
     * @param string $etc
     * @param string &$remainder
     * @param bool $breakWords
     * @return string
     */
    public function truncateText($string, $length=80, $etc='...', &$remainder='', $breakWords=true)
    {
        $remainder = '';
        if (0 == $length) {
            return '';
        }
        
        $helper = Mage::helper('core/string');
        
        $originalLength = $helper->strlen($string);
        if ($originalLength > $length) {
            $length -= $helper->strlen($etc);
            if ($length <= 0) {
                return '';
            }
            $preparedString = $string;
            $preparedLength = $length;
            
            if (!$breakWords) {
                $preparedString = $helper->substr($string, 0, $length+1);
                $spacePos = strrpos($preparedString, ' ');
                if (isset($spacePos)) {
                    $preparedString = $helper->substr($preparedString, 0, $spacePos);
                }
                $preparedLength = $helper->strlen($preparedString);
            }
            
            $remainder = $helper->substr($string, $preparedLength, $originalLength);
            return $helper->substr($preparedString, 0, $length) . $etc;
        }
        
        return $string;
    }
    
    /**
    * Truncates HTML text.
    * Original version found at :
    * http://dodona.wordpress.com/2009/04/05/how-do-i-truncate-an-html-string-without-breaking-the-html-code/
    *
    * Cuts a string to the length of $length and replaces the last characters
    * with the ending if the text is longer than length.
    *
    * @param string  $text String to truncate
    * @param integer $length Length of returned string, including ellipsis
    * @param string  $ending Ending to be appended to the trimmed string
    * @param string  $dummy Unused variable to have the same signature as core/string helper truncate function
    * @param boolean $breakWords If false, $text will not be cut mid-word
    * @return string Trimmed string
    */
    function truncateHtml($text, $length=80, $ending='...', $dummy='', $breakWords=true)
    {
        if ($length == 0) {
            return '';
        }
        
        $helper = Mage::helper('core/string');
        
        // If the plain text is shorter than the maximum length, return the whole text
        if (($textLength = $helper->strlen(preg_replace('/<.*?>/', '', $text))) <= $length) {
            return $text;
        }
        
        // Splits all html-tags to scanable lines
        preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
        $totalLength = $helper->strlen($ending);
        
        if ($length-$totalLength <= 0) {
            return '';
        }
        
        $openTags = array();
        $truncate = '';
        
        foreach ($lines as $lineMatchings) {
            // If there is any html-tag in this line, handle it and add it (uncounted) to the output
            if (!empty($lineMatchings[1])) {
                if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $lineMatchings[1])) {
                    // // If it's an "empty element" with or without xhtml-conform closing slash (f.e. <br/>) : do nothing
                } elseif (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $lineMatchings[1], $tagMatchings)) {
                    // // If tag is a closing tag (f.e. </b>) : delete tag from $openTags list
                    $pos = array_search($tagMatchings[1], $openTags);
                    if ($pos !== false) {
                        unset($openTags[$pos]);
                    }
                } elseif (preg_match('/^<\s*([^\s>!]+).*?>$/s', $lineMatchings[1], $tagMatchings)) {
                    // If tag is an opening tag (f.e. <b>) : add tag to the beginning of $openTags list
                    array_unshift($openTags, strtolower($tagMatchings[1]));
                }
                // Add html-tag to $truncate'd text
                $truncate .= $lineMatchings[1];
            }
            
            // Calculate the length of the plain text part of the line, handle entities as one character
            $contentLength = $helper->strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $lineMatchings[2]));
            if ($totalLength+$contentLength > $length) {
                // The number of characters which are left
                $left = $length - $totalLength;
                $entitiesLength = 0;
                
                // Search for html entities
                if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $lineMatchings[2], $entities, PREG_OFFSET_CAPTURE)) {
                    // Calculate the real length of all entities in the legal range
                    foreach ($entities[0] as $entity) {
                        if ($entity[1]+1-$entitiesLength <= $left) {
                            $left--;
                            $entitiesLength += $helper->strlen($entity[0]);
                        } else {
                            // No more characters left
                            break;
                        }
                    }
                }
                $truncate .= $helper->substr($lineMatchings[2], 0, $left+$entitiesLength);
                // Maximum length is reached, so get off the loop
                break;
            } else {
                $truncate .= $lineMatchings[2];
                $totalLength += $contentLength;
            }
            
            // If the maximum length is reached, get off the loop
            if ($totalLength >= $length) {
                break;
            }
        }
        
        // If the words shouldn't be cut in the middle...
        if (!$breakWords) {
            // ...search the last occurance of a space...
            $spacepos = strrpos($truncate, ' ');
            if (isset($spacepos)) {
                // ...and cut the text in this position
                $truncate = $helper->substr($truncate, 0, $spacepos);
            }
        }
        
        // Close all unclosed html-tags
        foreach ($openTags as $tag) {
            $truncate .= '</' . $tag . '>';
        }
        
        // Add the defined ending to the text
        $truncate .= $ending;
        
        return $truncate;
    }
    
    /**
     * Find position of last occurrence of a string
     *
     * @param string $haystack
     * @param string $needle
     * @param int $offset
     * @return int|false
     */
    public function strrpos($haystack, $needle, $offset=null)
    {
        return iconv_strrpos($haystack, $needle, $offset, self::ICONV_CHARSET);
    }
    
    public function lcfirst($string)
    {
        if (function_exists('lcfirst')) {
            return lcfirst($string);
        } else {
            return strtolower(substr($string, 0, 1)).substr($string, 1);
        }
    }
    
    public function camelize($string)
    {
        return $this->lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $string))));
    }
    
    public function camelizeArrayKeys($array, $recursive=true, $overwrite=false)
    {
        foreach ($array as $key => $value) {
            if (($camelized = $this->camelize($key)) != $key) {
                if ($overwrite || !isset($array[$camelized])) {
                    $array[$camelized] = $value;
                }
                unset($array[$key]);
            }
            if ($recursive && is_array($value)) {
                $array[$camelized] = $this->camelizeArrayKeys($array, true, $overwrite);
            }
        }
        return $array;
    }
    
    public function htmlDoubleEscape($data, $allowedTags=null)
    {
        if (is_array($data)) {
            $result = array();
            foreach ($data as $item) {
                $result[] = $this->htmlDoubleEscape($item);
            }
        } else {
            if (strlen($data)) {
                if (is_array($allowedTags) and !empty($allowedTags)) {
                    $allowed = implode('|', $allowedTags);
                    $result = preg_replace('/<([\/\s\r\n]*)(' . $allowed . ')([\/\s\r\n]*)>/si', '##$1$2$3##', $data);
                    $result = htmlspecialchars($result, ENT_COMPAT, 'UTF-8', true);
                    $result = preg_replace('/##([\/\s\r\n]*)(' . $allowed . ')([\/\s\r\n]*)##/si', '<$1$2$3>', $result);
                } else {
                    $result = htmlspecialchars($data, ENT_COMPAT, 'UTF-8', true);
                }
            } else {
                $result = $data;
            }
        }
        return $result;
    }
}