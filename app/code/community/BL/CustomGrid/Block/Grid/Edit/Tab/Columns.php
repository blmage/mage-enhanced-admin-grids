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
 * @copyright  Copyright (c) 2014 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Block_Grid_Edit_Tab_Columns
    extends BL_CustomGrid_Block_Widget_Grid_Config
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    public function isStandAlone()
    {
        return true;
    }
    
    public function getTabLabel()
    {
        return $this->__('Columns');
    }
    
    public function getTabTitle()
    {
        return $this->__('Columns');
    }
    
    public function canShowTab()
    {
        return true;
    }
    
    public function isHidden()
    {
        return false;
    }
}