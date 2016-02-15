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

class BL_CustomGrid_Block_Widget_Grid_Column_Filter_Product_Categories extends Mage_Adminhtml_Block_Widget_Grid_Column_Filter_Abstract
{
    public function getHtml()
    {
        if ($this->getColumn()->getBooleanFilter()) {
            $mustExist = (!is_null($value = $this->getValue()) ? (bool) $value : null);
            $existentSelected = ($mustExist === true  ? ' selected="selected"' : '');
            $nonExistentSelected = ($mustExist === false  ? ' selected="selected"' : '');
            
            $html = '<select name="' . $this->_getHtmlName() . '" id="' . $this->_getHtmlId() . '" class="no-changes">'
                . '<option value=""></option>'
                . '<option value="1"' . $existentSelected . '>' . $this->__('With') . '</option>'
                . '<option value="0"' . $nonExistentSelected . '>' . $this->__('Without') . '</option>'
                . '</select>';
        } else {
            /** @var $helper Mage_Core_Helper_Data */
            $helper = $this->helper('core');
            $jsId   = $helper->uniqHash('blcgCategoriesFilter');
            $htmlId = $helper->uniqHash($this->_getHtmlId());
            $windowUrl = $this->getUrl('adminhtml/blcg_grid_column_filter/categories', array('js_object_name' => $jsId));
            $windowJsonConfig = $helper->jsonEncode(
                array(
                    'width'  => '700px',
                    'height' => '480px',
                    'title'  => $this->__('Choose Categories To Filter'),
                    'draggable' => true,
                    'resizable' => true,
                    'recenterAuto' => false,
                )
            );
            
            $ids = array_filter(array_unique(explode(',', $this->getValue())));
            sort($ids, SORT_NUMERIC);
            $idsString = implode(', ', $ids);
            
            $html = '<div class="blcg-categories-filter">'
                    . '<span class="label">' . $this->__('IDs:') . ' </span>'
                    . '<span class="blcg-filter-value" id="' . $htmlId . '_container">' . $idsString . '</span>'
                    . '<input type="hidden" name="' . $this->_getHtmlName() . '" id="' . $htmlId . '"'
                    . ' value="' . $this->htmlEscape($this->getValue()) . '" />'
                    . '<span class="blcg-filter-button" id="' . $htmlId . '_button"></span>'
                . '</div>'
                . '<script type="text/javascript">'
                . "\n" . '//<![CDATA[' . "\n"
                . $jsId.' = new blcg.Grid.Filter.Categories("' . $htmlId . '", "' . $htmlId . '_button", '
                    . '"' . $htmlId . '_container", "' . $windowUrl . '", "ids", '.$windowJsonConfig.');'
                 . "\n" . '//]]>' . "\n"
                . '</script>';
        }
        
        return $html;
    }
}
