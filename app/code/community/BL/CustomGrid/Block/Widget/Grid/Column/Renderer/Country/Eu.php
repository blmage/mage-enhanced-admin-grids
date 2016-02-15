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

class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Country_Eu extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Render the corresponding value from the given grid row, according to the given display format
     * 
     * @param Varien_Object $row Grid row
     * @param string $displayFormat Display format
     * @return string
     */
    protected function _render(Varien_Object $row, $displayFormat)
    {
        if ($displayFormat === '') {
            $displayFormat = '{is_eu_country}';
        }
        
        $code = $this->_getValue($row);
        $searches  = array('{is_eu_country}', '{country_code}', '{country_name}');
        $replacements = array($this->__('No'), $code, $code);
        $countries = $this->getColumn()->getAllCountries();
        
        if (is_array($countries) && isset($countries[$code])) {
            $replacements[0] = $this->__($countries[$code]->getIsEu() ? 'Yes' : 'No');
            $replacements[2] = $countries[$code]->getName();
        }
        
        return str_replace($searches, $replacements, $displayFormat);
    }
    
    public function render(Varien_Object $row)
    {
        return $this->_render($row, strval($this->getColumn()->getBaseDisplayFormat()));
    }
    
    public function renderExport(Varien_Object $row)
    {
        return (($displayFormat = strval($this->getColumn()->getExportDisplayFormat())) !== '')
            ? $this->_render($row, $displayFormat)
            : $this->render($row);
    }
}
