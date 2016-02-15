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

class BL_CustomGrid_Block_Widget_Grid_Column_Filter_Yesno extends BL_CustomGrid_Block_Widget_Grid_Column_Filter_Select
{
    protected function _getOptions()
    {
        return array(
            array(
                'value' => 1,
                'label' => $this->__('Yes'),
            ),
            array(
                'value' => 0,
                'label' => $this->__('No'),
            ),
        );
    }
}
