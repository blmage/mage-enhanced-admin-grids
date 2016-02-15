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

class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Order_Status_Color extends BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Options
{
    protected function _renderRow(Varien_Object $row, $renderedValue, $forExport = false)
    {
        if (!empty($renderedValue) && !$forExport) {
            $value  = $row->getData($this->getColumn()->getIndex());
            $colors = $this->getColumn()->getOptionsColors();
            
            if (is_array($colors) && isset($colors[$value])) {
                /** @var $helper Mage_Core_Helper_Data */
                $helper = $this->helper('core');
                $elementId = $helper->uniqHash('blcg-gcr-osc-');
                $onlyCell  = ($this->getColumn()->getOnlyCell() ? 'true' : 'false');
                
                $renderedValue .= '<span id="' . $elementId . '"></span>'
                    . '<script type="text/javascript">'
                    . "\n" . '//<![CDATA[' . "\n"
                    . 'blcg.Grid.CustomColumn.RowColorizer.colorizeRow('
                    . '"' . $elementId . '", '
                    . '"' . $this->jsQuoteEscape($colors[$value]['background'], '"') . '", '
                    . '"' . $this->jsQuoteEscape($colors[$value]['text'], '"') . '", '
                    . $onlyCell
                    . ');'
                    . "\n" . '//]]>' . "\n"
                    . '</script>';
            }
        }
        return $renderedValue;
    }
    
    public function render(Varien_Object $row)
    {
        return $this->_renderRow($row, parent::render($row));
    }
    
    public function renderExport(Varien_Object $row)
    {
        return $this->_renderRow($row, parent::render($row), true);
    }
}
