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

class BL_CustomGrid_Model_Resource_Transaction extends Mage_Core_Model_Resource_Transaction
{
    /**
     * Parameterized callbacks array
     *
     * @var callable[]
     */
    protected $_parameterizedCommitCallbacks = array();
    
    /**
     * Add callback function and parameters, which will be called before commit transactions
     *
     * @param callable $callback Callback function
     * @param array $params Callback parameters
     * @return BL_CustomGrid_Model_Resource_Transaction
     */
    public function addParameterizedCommitCallback($callback, array $params)
    {
        $this->_parameterizedCommitCallbacks[] = array($callback, $params);
        return $this;
    }
    
    /**
     * Run all configured object callbacks
     *
     * @return BL_CustomGrid_Model_Resource_Transaction
     */
    protected function _runCallbacks()
    {
        parent::_runCallbacks();
        
        foreach ($this->_parameterizedCommitCallbacks as $callback) {
            call_user_func_array($callback[0], $callback[1]);
        }
        
        return $this;
    }
}
