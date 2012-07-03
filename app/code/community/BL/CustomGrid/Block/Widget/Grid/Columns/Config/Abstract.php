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

abstract class BL_CustomGrid_Block_Widget_Grid_Columns_Config_Abstract
    extends Mage_Adminhtml_Block_Widget
{
    public function getFormKey()
    {
        return Mage::getSingleton('core/session')->getFormKey();
    }
    
    protected function _toHtml()
    {
        if (($model = $this->getGridModel())
            && ((!$this->getNeedExistingModel() || !$this->getIsNewModel())
            && ($this->getDisplayableWithoutBlock()
                || (($block = $this->getGridBlock())
                    && Mage::helper('customgrid')->isRewritedGrid($block))))) {
            return parent::_toHtml();
        }
        return '';
    }
}
