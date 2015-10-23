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

class BL_CustomGrid_Block_Widget_Grid_Column_Filter_Product_Categories
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Filter_Abstract
{
    public function getHtml()
    {
        $html = '';
        
        if ($this->getColumn()->getBooleanFilter()) {
            $hasValue  = !is_null($this->getValue());
            $mustExist = ($hasValue && (bool)$this->getValue());
            $html .=  '<select name="'.$this->_getHtmlName().'" id="'.$this->_getHtmlId().'" class="no-changes">'
                . '<option value=""></option>'
                . '<option value="1"'.($hasValue && $mustExist  ? ' selected="selected"' : '').'>'.$this->__('With').'</option>'
                . '<option value="0"'.($hasValue && !$mustExist ? ' selected="selected"' : '').'>'.$this->__('Without').'</option>'
                . '</select>';
        } else {
            $htmlId = Mage::helper('core')->uniqHash($this->_getHtmlId());
            $jsId   = Mage::helper('core')->uniqHash('blcgCategoriesFilter');
            $url    = $this->getUrl('adminhtml/blcg_custom_grid_column_filter/categories', array('js_object' => $jsId));
            $window = Mage::helper('core')->jsonEncode(array(
                'width'        => '700px',
                'height'       => '480px',
                'title'        => $this->__('Choose Categories To Filter'),
                'draggable'    => true,
                'resizable'    => true,
                'recenterAuto' => false,
            ));
            
            $ids = array_filter(array_unique(explode(',', $this->getValue())));
            sort($ids, SORT_NUMERIC);
            $output = implode(', ', $ids);
            
            $html = '<div class="blcg-categories-filter">'
                . '<span class="label">'.$this->__('IDs: ').'</span>'
                . '<span class="blcg-filter-value" id="'.$htmlId.'_container">'.$output.'</span>'
                . '<input type="hidden" name="'.$this->_getHtmlName().'" id="'.$htmlId.'" value="'.$this->htmlEscape($this->getValue()).'" />'
                . '<span class="blcg-filter-button" id="'.$htmlId.'_button"></span>'
                . '</div>'
                . '<script type="text/javascript">'
                . $jsId.' = new blcg.Filter.Categories("'.$htmlId.'", "'.$htmlId.'_button", "'.$htmlId.'_container", "'.$url.'", "ids", '.$window.');'
                . '</script>';
        }
        
        return $html;
    }
}