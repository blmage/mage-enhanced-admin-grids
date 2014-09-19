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

class BL_CustomGrid_Block_Widget_Grid_Column_Filter_Text
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Filter_Text
{
    public function getCondition()
    {
        $helper = $this->helper('core/string');
        $value  = $this->getValue();
        $length = $helper->strlen($value);
        $expression = '';
        
        $singleWildcard = $this->getColumn()->getSingleWildcard();
        $multipleWildcard = $this->getColumn()->getMultipleWildcard();
        
        for ($i=0; $i<$length; $i++) {
            $char = $helper->substr($value, $i, 1);
            
            if ($char === $singleWildcard) {
                $expression .= '_';
            } elseif ($char === $multipleWildcard) {
                $expression .= '%';
            } elseif (($char === '%') || ($char === '_')) {
                $expression .= '\\' . $char;
            } elseif ($char == '\\') {
                $expression .= '\\\\';
            } else {
                $expression .= $char;
            }
        }
        
        return array('like' => (!$this->getColumn()->getExactFilter() ? '%' . $expression . '%' : $expression));
    }
}