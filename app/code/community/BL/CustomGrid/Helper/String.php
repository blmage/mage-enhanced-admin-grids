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
 * @copyright   Copyright (c) 2014 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Helper_String extends Mage_Core_Helper_Abstract
{
    const ICONV_CHARSET = 'UTF-8'; 
    
    /**
     * Truncate a string to a certain length if necessary
     *
     * @param string $string String to be truncated
     * @param int $truncateLength Truncated string length (not including $etc)
     * @param string $etc Value to be appended at the end of the truncated string
     * @param string &$remainder Remainder of the original string that is not included in the truncated string
     * @param bool $breakWords Whether words can be broken (if not, truncation will stop on the first available space)
     * @return string
     */
    public function truncateText($string, $truncateLength = 80, $etc = '...', &$remainder = '', $breakWords = true)
    {
        $remainder = '';
        
        if ($truncateLength == 0) {
            return '';
        }
        
        /** @var $helper Mage_Core_Helper_String */
        $helper = Mage::helper('core/string');
        $originalLength = $helper->strlen($string);
        
        if ($originalLength > $truncateLength) {
            $truncateLength -= $helper->strlen($etc);
            
            if ($truncateLength <= 0) {
                return '';
            }
            
            $preparedString = $string;
            $preparedLength = $truncateLength;
            
            if (!$breakWords) {
                $preparedString = preg_replace('/\s+?(\S+)?$/u', '', $this->substr($string, 0, $truncateLength + 1));
                $preparedLength = $this->strlen($preparedString);
            }
            
            $remainder = $helper->substr($string, $preparedLength, $originalLength);
            return $helper->substr($preparedString, 0, $truncateLength) . $etc;
        }
        
        return $string;
    }
    
    /**
     * Handle the given HTML opening tag from a truncated HTML string
     * 
     * @param string $htmlTag HTML opening tag
     * @param string[] $openedTags Currently opened tags
     * @return BL_CustomGrid_Helper_String
     */
    protected function _handleHtmlOpeningTag($htmlTag, array &$openedTags)
    {
        $emptyTagsRegex = '(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)';
        
        if (preg_match('/^<(\s*.+?\/\s*|\s*' . $emptyTagsRegex . '(\s.+?)?)>$/is', $htmlTag)) {
            // The tag is an "empty element" (with or without XHTML-conform closing slash) (eg. <br/>)
        } elseif (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $htmlTag, $tagMatchings)) {
            // // The tag is a closing tag (eg. </b>)
            if (($position = array_search($tagMatchings[1], $openedTags)) !== false) {
                unset($openedTags[$position]);
            }
        } elseif (preg_match('/^<\s*([^\s>!]+).*?>$/s', $htmlTag, $tagMatchings)) {
            // The tag is an opening tag (eg. <b>)
            array_unshift($openedTags, strtolower($tagMatchings[1]));
        }
        
        return $this;
    }
    
    /**
     * Supplement the given truncated HTML string with the given HTML content, stripped of its tags
     * 
     * @param string $content Additional content
     * @param string $truncated Current truncated string
     * @param int $totalLength Length of the current truncated string
     * @param int $truncateLength Maximum truncated string length
     * @param bool $breakWords Whether words can be broken
     * @return BL_CustomGrid_Helper_String
     */
    protected function _supplementTruncatedHtml($content, &$truncated, &$totalLength, $truncateLength, $breakWords)
    {
        /** @var $helper Mage_Core_Helper_String */
        $helper = Mage::helper('core/string');
        $htmlEntitiesRegex = '/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i';
        
        // Calculate the length of the content, handle HTML entities as one character
        $parsedContent = preg_replace($htmlEntitiesRegex, ' ', $content);
        $parsedLength  = $helper->strlen($parsedContent);
        
        if ($totalLength+$parsedLength > $truncateLength) {
            $remainingLength = $truncateLength-$totalLength;
            $entitiesLength = 0;
            
            // Adapt the remaining length by taking HTML entities into account
            if (preg_match_all($htmlEntitiesRegex, $content, $entities, PREG_OFFSET_CAPTURE)) {
                foreach ($entities[0] as $entity) {
                    if ($entity[1]+1-$entitiesLength <= $remainingLength) {
                        $remainingLength--;
                        $entitiesLength += $helper->strlen($entity[0]);
                    } else {
                        break;
                    }
                }
            }
            
            $content = $helper->substr($content, 0, $remainingLength+$entitiesLength);
            
            if (!$breakWords) {
                // To ensure that we do not get false positives, we can only check for spaces in the additional content
                $spacePosition = max((int) strrpos($content, '&nbsp;'), (int) strrpos($content, ' '));
                
                if ($spacePosition > 0) {
                    $content = $helper->substr($content, 0, $spacePosition);
                } else {
                    $content = '';
                } 
            }
            
            $truncated .= $content;
            $totalLength = $truncateLength;
        } else {
            $truncated .= $content;
            $totalLength += $parsedLength;
        }
    }
    
    /**
     * Truncate given string as HTML
     * Original version found at :
     * http://dodona.wordpress.com/2009/04/05/how-do-i-truncate-an-html-string-without-breaking-the-html-code/
     *
     * @param string $string String to be truncated
     * @param integer $truncateLength Truncated string length (not including $etc)
     * @param string $etc Value to be appended at the end of the truncated string
     * @param bool $breakWords Whether words can be broken (if not, truncation will stop on the first available space)
     * @return string
     */
    public function truncateHtml($string, $truncateLength = 80, $etc = '...', $breakWords = true)
    {
        if ($truncateLength == 0) {
            return '';
        }
        
        /** @var $helper Mage_Core_Helper_String */
        $helper = Mage::helper('core/string');
        
        if ($helper->strlen(preg_replace('/<.*?>/', '', $string)) <= $truncateLength) {
            return $string;
        }
        
        // Splits all HTML tags to scannable lines
        preg_match_all('/(<.+?>)?([^<>]*)/s', $string, $lines, PREG_SET_ORDER);
        $totalLength = $helper->strlen($etc);
        
        if (!is_array($lines) || ($truncateLength-$totalLength <= 0)) {
            return '';
        }
        
        $truncated  = '';
        $openedTags = array();
        
        foreach ($lines as $lineMatchings) {
            if (!empty($lineMatchings[1])) {
                // If there is any HTML tag in this line, handle it and add it (uncounted) to the output
                $this->_handleHtmlOpeningTag($lineMatchings[1], $openedTags);
                $truncated .= $lineMatchings[1];
            }
            
            $this->_supplementTruncatedHtml($lineMatchings[2], $truncated, $totalLength, $truncateLength, $breakWords);
            
            if ($totalLength >= $truncateLength) {
                break;
            }
        }
        
        // Close all unclosed html-tags
        foreach ($openedTags as $tag) {
            $truncated .= '</' . $tag . '>';
        }
        
        // Add the defined ending to the text
        $truncated .= $etc;
        
        return $truncated;
    }
    
    /**
     * Make the first character of the given string lowercase
     * 
     * @param string $string
     * @return string
     */
    public function lcfirst($string)
    {
        return function_exists('lcfirst')
            ? lcfirst($string)
            : strtolower(substr($string, 0, 1)) . substr($string, 1);
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
     * @param string[]|string $data String or array of strings, in which to escape HTML entities
     * @param array|null $allowedTags HTML tags that should be preserved
     * @return string[]|string
     */
    public function htmlDoubleEscape($data, $allowedTags = null)
    {
        if (is_array($data)) {
            $result = array();
            
            foreach ($data as $item) {
                $result[] = $this->htmlDoubleEscape($item);
            }
        } else {
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
