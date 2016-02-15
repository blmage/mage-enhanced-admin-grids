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

class BL_CustomGrid_Model_System_Config_Source_Default_Param_Behaviour_Scalar extends BL_CustomGrid_Model_Source_Fixed
{
    protected $_optionHash = array(
        BL_CustomGrid_Model_Grid::DEFAULT_PARAM_DEFAULT        => 'Default (Last Set Value is Used)',
        BL_CustomGrid_Model_Grid::DEFAULT_PARAM_FORCE_ORIGINAL => 'Force Original Value',
        BL_CustomGrid_Model_Grid::DEFAULT_PARAM_FORCE_CUSTOM   => 'Force Custom Value',
    );
}
