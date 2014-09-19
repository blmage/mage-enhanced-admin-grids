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

class BL_CustomGrid_Block_Widget_Grid_Config_Infos
    extends BL_CustomGrid_Block_Widget_Grid_Config_Abstract
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('bl/customgrid/widget/grid/config/infos.phtml');
    }
    
    public function getGridInformations()
    {
        $gridBlock = $this->getGridBlock();
        $gridModel = $this->getGridModel();
        $helper = $this->helper('customgrid');
        
        // @todo allow to change forced grid type from this block (+ in the grid edit page)
        return array(
            array(
                'label' => $helper->__('Block Type'),
                'value' => $gridModel->getBlockType(),
            ),
            array(
                'label' => $helper->__('Grid Type'),
                'value' => $gridModel->getTypeModelName($helper->__('none')),
            ),
            array(
                'label' => $helper->__('Rewriting Class'),
                'value' => $gridModel->getRewritingClassName(),
            ),
            array(
                'label' => $helper->__('Module Name'),
                'value' => $gridModel->getModuleName(),
            ),
            array(
                'label' => $helper->__('Controller Name'),
                'value' => $gridModel->getControllerName(),
            ),
            array(
                'label' => $helper->__('Block ID'),
                'value' => ($gridBlock ? $gridBlock->getId() : $gridModel->getBlockId()),
            ),
        );
    }
}