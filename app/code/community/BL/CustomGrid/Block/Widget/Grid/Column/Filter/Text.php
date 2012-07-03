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

class BL_CustomGrid_Block_Widget_Grid_Column_Filter_Text
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Filter_Text
{
    public function getCondition()
    {
        $helper = Mage::helper('core/string');
        $value  = $this->getValue();
        $length = $helper->strlen($value);
        $expr   = '';
        
        $singleWc   = $this->getColumn()->getSingleWildcard();
        $multipleWc = $this->getColumn()->getMultipleWildcard();
        
        for ($i=0; $i<$length; $i++) {
            $char = $helper->substr($value, $i, 1);
            
            if ($char === $singleWc) {
                $expr .= '_';
            } elseif ($char === $multipleWc) {
                $expr .= '%';
            } elseif (($char === '%') || ($char === '_')) {
                $expr .= '\\'.$char;
            } elseif ($char == '\\') {
                $expr .= '\\\\';
            } else {
                $expr .= $char;
            }
        }
        
        return array('like' => (!$this->getColumn()->getExactFilter() ? '%'.$expr.'%' : $expr));
    }
}