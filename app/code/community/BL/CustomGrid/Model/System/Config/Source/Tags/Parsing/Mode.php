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

class BL_CustomGrid_Model_System_Config_Source_Tags_Parsing_Mode
{
    public function toOptionArray()
    {
        $helper = Mage::helper('customgrid');
        return array(
            array('value' => 0,       'label' => $helper->__('No')),
            array('value' => 'block', 'label' => $helper->__('With CMS blocks templates processor')),
            array('value' => 'page',  'label' => $helper->__('With CMS pages templates processor')),
        );
    }
}