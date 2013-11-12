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
 * @copyright  Copyright (c) 2013 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Block_Widget_Grid_Column_Filter_Country_Eu
    extends BL_CustomGrid_Block_Widget_Grid_Column_Filter_Yesno
{
    public function getCondition()
    {
        $value = $this->getValue();
        
        if (is_null($value) || ($value === '')) {
            return null;
        }
        
        $value = (bool) $value;
        $euCountries = $this->getColumn()->getEuCountries();
        
        if (!is_array($euCountries) || empty($euCountries)) {
            return ($value ? array('eq' => -1) : null);
        }
        
        $condition = ($value ? 'in' : 'nin');
        return array($condition => $euCountries);
    }
}