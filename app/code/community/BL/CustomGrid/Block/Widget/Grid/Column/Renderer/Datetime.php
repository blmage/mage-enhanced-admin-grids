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

class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Datetime
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Datetime
{
    public function render(Varien_Object $row)
    {
        try {
            return parent::render($row);
        } catch (Exception $e) {
            // Exception can be thrown if, eg, values do not correspond to dates
            return $e->getMessage();
        }
    }
}