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

class BL_CustomGrid_Helper_Xml_Config extends Mage_Core_Helper_Abstract
{
    /**
     * Prepare and return the options values from the given element parameter data
     * 
     * @param array $paramData Parameter data
     * @return array
     */
    public function getElementParamOptionsValues(array $paramData)
    {
        $values = array();
        
        if (isset($paramData['values']) && is_array($paramData['values'])) {
            foreach ($paramData['values'] as $value) {
                if (is_array($value) && isset($value['value']) && isset($value['label'])) {
                    $values[] = $value;
                }
            }
        }
        
        return $values;
    }
    
    /**
     * Prepare and return the helper block from the given element parameter data
     * 
     * @param array $paramData Parameter data
     * @return BL_CustomGrid_Object|null
     */
    public function getElementParamHelperBlock(array $paramData)
    {
        $helperBlock = null;
        
        if (isset($paramData['helper_block'])) {
            $helperBlock = new BL_CustomGrid_Object();
            
            if (isset($paramData['helper_block']['data']) && is_array($paramData['helper_block']['data'])) {
                $helperBlock->addData($paramData['helper_block']['data']);
            }
            if (isset($paramData['helper_block']['type'])) {
                $helperBlock->setType($paramData['helper_block']['type']);
            }
            
            $paramData['helper_block'] = $helperBlock;
        }
        
        return $helperBlock;
    }
}
