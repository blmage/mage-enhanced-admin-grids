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

class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Grid_Action extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Action
{
    protected function _isAllowedAction($action, BL_CustomGrid_Model_Grid $gridModel)
    {
        return (!isset($action['permissions']) || $gridModel->checkUserPermissions($action['permissions']));
    }
    
    protected function _toLinkHtml($action, Varien_Object $row)
    {
        if ($row instanceof BL_CustomGrid_Model_Grid) {
            if (!$this->_isAllowedAction($action, $row)) {
                return '';
            }
        }
        return parent::_toLinkHtml($action, $row);
    }
    
    protected function _toOptionHtml($action, Varien_Object $row)
    {
        if ($row instanceof BL_CustomGrid_Model_Grid) {
            if (!$this->_isAllowedAction($action, $row)) {
                return '';
            }
        }
        return parent::_toOptionHtml($action, $row);
    }
}
