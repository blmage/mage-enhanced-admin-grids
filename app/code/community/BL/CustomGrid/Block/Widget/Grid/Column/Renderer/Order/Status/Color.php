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

class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Order_Status_Color
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Text
{
    protected function _renderRow(Varien_Object $row, $forExport=false)
    {
        $return  = '';
        $options = $this->getColumn()->getOptions();
        $colors  = $this->getColumn()->getOptionsColors();
        
        if (!is_array($colors)) {
            $colors = array();
        }
        if (!empty($options) && is_array($options)) {
            $value = $row->getData($this->getColumn()->getIndex());
            
            if (isset($options[$value])) {
                $return = $options[$value];
            } else {
                $return = $value;
            }
            if (!$forExport && isset($colors[$value])) {
                $elementId = Mage::helper('core')->uniqHash('blcg-gcr-osc-');
                $onlyCell  = ((bool)$this->getColumn()->getOnlyCell() ? 'true' : 'false');
                
                $return   .= '<span id="'.$elementId.'"></span>'
                    . '<script type="text/javascript">'
                    . 'blcg.CustomColumn.OptionsColor.registerRowChange("'.$elementId.'", "'
                    .$this->jsQuoteEscape($colors[$value]['background'], '"').'", "'
                    .$this->jsQuoteEscape($colors[$value]['text'], '"').'", '.$onlyCell.');</script>';
            }
        }
        
        return $return;
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