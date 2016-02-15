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
 * @copyright  Copyright (c) 2015 Matthew Gamble (https://github.com/mwgamble)
 * @copyright  Copyright (c) 2016 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_System_Config_Source_Payment_Allmethods
{
    /**
     * Options cache
     * 
     * @var array|null
     */
    protected $_optionArray = null;
    
    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (is_null($this->_optionArray)) {
            $helper = Mage::helper('payment');
            $this->_optionArray = $helper->getPaymentMethodList(true, true);
        }
        return $this->_optionArray;
    }
}
