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

class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Order_Status_Color
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Text
{
    protected function _renderRow(Varien_Object $row, $forExport=false)
    {
        $result  = '';
        $options = $this->getColumn()->getOptions();
        $colors  = $this->getColumn()->getOptionsColors();
        
        if (!is_array($colors)) {
            $colors = array();
        }
        if (!empty($options) && is_array($options)) {
            $value = $row->getData($this->getColumn()->getIndex());
            
            if (isset($options[$value])) {
                $result = $options[$value];
            } else {
                $result = $value;
            }
            if (!$forExport && isset($colors[$value])) {
                $elementId = $this->helper('core')->uniqHash('blcg-gcr-osc-');
                $onlyCell  = ($this->getColumn()->getOnlyCell() ? 'true' : 'false');
                
                $result .= '<span id="' . $elementId . '"></span>'
                    . '<script type="text/javascript">'
                    . "\n" . '//<![CDATA[' . "\n"
                    . 'blcg.Grid.CustomColumn.OptionsColor.registerRowChange("' . $elementId . '", '
                    . '"' . $this->jsQuoteEscape($colors[$value]['background'], '"') . '", '
                    . '"' . $this->jsQuoteEscape($colors[$value]['text'], '"') . '", ' . $onlyCell . ');'
                    . "\n" . '//<![CDATA[' . "\n"
                    . '</script>';
            }
        }
        
        return $result;
    }
    
    public function render(Varien_Object $row)
    {
        return $this->_renderRow($row);
    }
    
    public function renderExport(Varien_Object $row)
    {
        return $this->_renderRow($row, true);
    }
}