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

class BL_CustomGrid_Model_System_Config_Backend_Serialized_Grid_Exceptions extends Mage_Adminhtml_Model_System_Config_Backend_Serialized
{
    protected function _beforeSave()
    {
        $value = $this->getValue();
        
        if (is_array(($value))) {
            if (isset($value['__empty'])) {
                unset($value['__empty']);
            }
            
            foreach ($value as $key => $exception) {
                if (!is_array($exception)
                    || !isset($exception['block_type'])
                    || (trim($exception['block_type']) == '')) {
                    unset($value[$key]);
                }
            }
        } else {
            $value = array();
        }
        
        $this->setValue($value);
        parent::_beforeSave();
    }
}
