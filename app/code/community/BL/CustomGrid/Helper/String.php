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
     * Truncate a string to a certain length if necessary
     *
     * @param string $string String to be truncated
     * @param int $length Truncated string length (not including $etc)
     * @param string $etc Value to be appended at the end of the truncated string
     * @param string &$remainder Remainder of the original string that is not included in the truncated string
     * @param bool $breakWords Whether words can be broken (if not, truncation will stop on the first available space)
     * @return string
     */
    public function truncateText($string, $length = 80, $etc = '...', &$remainder = '', $breakWords = true)
    {
        if ($length == 0) {
            return '';
        }
        
        $helper = Mage::helper('core/string');
        $remainder = '';
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
                
                if (($spacePosition = strrpos($preparedString, ' ')) !== false) {
                    $preparedString = $helper->substr($preparedString, 0, $spacePosition);
                }
                
                $preparedLength = $helper->strlen($preparedString);
            }
            
            $remainder = $helper->substr($string, $preparedLength, $originalLength);
            return $helper->substr($preparedString, 0, $length) . $etc;
        }
        
        return $string;
    }
    
    /**
     * Truncates given string as HTML.
     * Original version found at :
     * http://dodona.wordpress.com/2009/04/05/how-do-i-truncate-an-html-string-without-breaking-the-html-code/
     *
     * @param string $string String to be truncated
     * @param integer $length Truncated string length (not including $etc)
     * @param string $etc Value to be appended at the end of the truncated string
     * @param bool $breakWords Whether words can be broken (if not, truncation will stop on the first available space)
     * @return string
     */
    public function truncateHtml($string, $length = 80, $etc = '...', $breakWords = true)
    {
        if ($length == 0) {
            return '';
        }
        
        $helper = Mage::helper('core/string');
        
        // If the plain text is shorter than the maximum length, return the whole text
        if ($helper->strlen(preg_replace('/<.*?>/', '', $string)) <= $length) {
            return $string;
        }
        
        // Splits all html-tags to scanable lines
        preg_match_all('/(<.+?>)?([^<>]*)/s', $string, $lines, PREG_SET_ORDER);
        $totalLength = $helper->strlen($etc);
        
        if ($length-$totalLength <= 0) {
            return '';
        }
        
        $openTags = array();
        $truncate = '';
        $emptyTagsRegex = '(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)';
        $htmlEntitiesRegex = '/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i';
        
        foreach ($lines as $lineMatchings) {
            // If there is any html-tag in this line, handle it and add it (uncounted) to the output
            if (!empty($lineMatchings[1])) {
                if (preg_match('/^<(\s*.+?\/\s*|\s*' . $emptyTagsRegex . '(\s.+?)?)>$/is', $lineMatchings[1])) {
                    // If it's an "empty element" with or without xhtml-conform closing slash (f.e. <br/>) : do nothing
                } elseif (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $lineMatchings[1], $tagMatchings)) {
                    // // If tag is a closing tag (f.e. </b>) : delete tag from $openTags list
                    if (($position = array_search($tagMatchings[1], $openTags)) !== false) {
                        unset($openTags[$position]);
                    }
                } elseif (preg_match('/^<\s*([^\s>!]+).*?>$/s', $lineMatchings[1], $tagMatchings)) {
                    // If tag is an opening tag (f.e. <b>) : add tag to the beginning of $openTags list
                    array_unshift($openTags, strtolower($tagMatchings[1]));
                }
                // Add html-tag to $truncate'd text
                $truncate .= $lineMatchings[1];
            }
            
            // Calculate the length of the plain text part of the line, handle entities as one character
            $content = preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $lineMatchings[2]);
            $contentLength = $helper->strlen($content);
            
            if ($totalLength+$contentLength > $length) {
                // The number of characters which are left
                $left = $length - $totalLength;
                $entitiesLength = 0;
                
                // Search for html entities
                if (preg_match_all($htmlEntitiesRegex, $lineMatchings[2], $entities, PREG_OFFSET_CAPTURE)) {
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
            // ...search the last occurence of a space...
            if (($spacePosition = strrpos($truncate, ' ')) !== false) {
                // ...and cut the text in this position
                $truncate = $helper->substr($truncate, 0, $spacePosition);
            }
        }
        
        // Close all unclosed html-tags
        foreach ($openTags as $tag) {
            $truncate .= '</' . $tag . '>';
        }
        
        // Add the defined ending to the text
        $truncate .= $etc;
        
        return $truncate;
    }
    
    /**
     * Make the first character of the given string lowercase
     * 
     * @param string $string
     * @return string
     */
    public function lcfirst($string)
    {
        if (function_exists('lcfirst')) {
            return lcfirst($string);
        }
        return strtolower(substr($string, 0, 1)) . substr($string, 1);
    }
    
    /**
     * Camelize the given string
     * 
     * @param string $string
     * @return string
     */
    public function camelize($string)
    {
        return $this->lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $string))));
    }
    
    /**
     * Camelize the keys of the given array
     * 
     * @param array $array Original array
     * @param bool $recursive Whether sub arrays keys should be camelized too
     * @param bool $overwrite Whether already existing keys can be overwritten
     * @return array
     */
    public function camelizeArrayKeys(array $array, $recursive = true, $overwrite = false)
    {
        $result = array();
        
        foreach ($array as $key => $value) {
            $camelizedKey = $this->camelize($key);
            
            if ($overwrite || !isset($result[$camelizedKey])) {
                if ($recursive && is_array($value)) {
                    $result[$camelizedKey] = $this->camelizeArrayKeys($value, true, $overwrite);
                } else {
                    $result[$camelizedKey] = $value;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Escape HTML entities, including already escaped entities (unlike Mage_Core_Helper_Abstract::escapeHtml())
     * 
     * @param array|string $data String or array of strings, in which to escape HTML entities
     * @param array|null $allowedTags HTML tags that should be preserved
     * @return array|string
     */
    public function htmlDoubleEscape($data, $allowedTags = null)
    {
        if (is_array($data)) {
            $result = array();
            
            foreach ($data as $item) {
                $result[] = $this->htmlDoubleEscape($item);
            }
        } else {
            $result = '';
            
            if (strlen($data)) {
                if (is_array($allowedTags) && !empty($allowedTags)) {
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
    
    /**
     * Sanitize given JS object name, by removing any unexpected character
     * 
     * @param string $name JS object name
     * @return string
     */
    public function sanitizeJsObjectName($name)
    {
        return preg_replace('#[^_0-9a-zA-Z]#', '', $name);
    }
}
