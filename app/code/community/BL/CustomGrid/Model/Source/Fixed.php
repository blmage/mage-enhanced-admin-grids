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

abstract class BL_CustomGrid_Model_Source_Fixed
{
    /**
     * Options hash
     * 
     * @var array
     */
    protected $_optionHash  = array();
    
    /**
     * Options array
     * 
     * @var array|null
     */
    private $_optionArray = null;
    
    /**
     * Whether options should be translated
     * 
     * @var bool
     */
    protected $_translateOptions = true;
    
    /**
     * Return the helper usable to translate the options labels
     * 
     * @return Mage_Core_Helper_Abstract
     */
    protected function _getTranslationHelper()
    {
        return Mage::helper('customgrid');
    }
    
    /**
     * Return the corresponding options array
     * 
     * @return array
     */
    public function toOptionArray()
    {
        if (!is_array($this->_optionArray)) {
            $helper = $this->_getTranslationHelper();
            $this->_optionArray = array();
            
            foreach ($this->_optionHash as $value => $label) {
                $this->_optionArray[] = array(
                    'value' => $value,
                    'label' => ($this->_translateOptions ? $helper->__($label) : $label),
                );
            }
        }
        return $this->_optionArray;
    }
}