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

class BL_CustomGrid_Block_Widget_Grid_Column_Filter_Product_Image extends Mage_Adminhtml_Block_Widget_Grid_Column_Filter_Abstract
{
    public function getHtml()
    {
        if ($this->getColumn()->getFilterOnName()) {
            $html = '<div class="field-100">'
                . '<input type="text" name="' . $this->_getHtmlName() . '" id="' . $this->_getHtmlId() . '" value="'
                . $this->getEscapedValue() . '" class="input-text no-changes" />'
                . '</div>';
        } else {
            $mustExist = (!is_null($value = $this->getValue()) ? (bool) $value : null);
            $existentSelected = ($mustExist === true  ? ' selected="selected"' : '');
            $nonExistentSelected = ($mustExist === false  ? ' selected="selected"' : '');
            
            $html =  '<select name="' . $this->_getHtmlName() . '" id="' . $this->_getHtmlId() . '" class="no-changes">'
                . '<option value=""></option>'
                . '<option value="1"' . $existentSelected . '>' . $this->__('Existent') . '</option>'
                . '<option value="0"' . $nonExistentSelected . '>' . $this->__('No image') . '</option>'
                . '</select>';
        }
        return $html;
    }
    
    public function getCondition()
    {
        if (is_null($this->getValue())) {
            return null;
        } elseif ($this->getColumn()->getFilterOnName()) {
            return parent::getCondition();
        } elseif ((bool) $this->getValue()) {
            return array('nin' => array('no_selection', ''));
        }
        return array(array('null' => 1), array('in' => array('no_selection', '')));
    }
}
