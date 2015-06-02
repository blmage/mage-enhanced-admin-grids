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

class BL_CustomGrid_Model_System_Config_Source_Custom_Column_Unverified_Behaviour extends BL_CustomGrid_Model_Source_Fixed
{
    protected $_optionHash = array(
        BL_CustomGrid_Model_Custom_Column_Applier::UNVERIFIED_BEHAVIOUR_NONE
             => 'None',
        BL_CustomGrid_Model_Custom_Column_Applier::UNVERIFIED_BEHAVIOUR_WARNING
            => 'Display a warning message',
        BL_CustomGrid_Model_Custom_Column_Applier::UNVERIFIED_BEHAVIOUR_STOP
            => 'Do not apply the column and display an error message',
    );
}
