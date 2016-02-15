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

class BL_CustomGrid_Helper_Js extends Mage_Core_Helper_Abstract
{
    /**
     * Turn the given HTML string into a string that can directly be output in a <script> tag as-is
     * 
     * @param string $html Base HTML string
     * @param bool $canTrim Whether string lines can be trimmed
     * @return string
     */
    public function prepareHtmlForJsOutput($html, $canTrim = false)
    {
        $parts  = preg_split('#\r\n|\r[^\n]|\n#', ($canTrim ? trim($html) : $html));
        $result = '';
        $first  = true;
        
        foreach ($parts as $part) {
            $result .= ($first ? '' : "\r\n+ ") . '\'' 
                . str_replace(array('\\', '\'', '/'), array('\\\\', '\\\'', '\\/'), ($canTrim ? trim($part) : $part))
                . '\'';
            $first = false;
        }
        
        return ($result !== '' ? $result : '\'\'');
    }
}
