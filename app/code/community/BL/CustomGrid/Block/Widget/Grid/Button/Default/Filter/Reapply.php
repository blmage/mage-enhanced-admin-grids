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

class BL_CustomGrid_Block_Widget_Grid_Button_Default_Filter_Reapply extends Mage_Adminhtml_Block_Widget_Button
{
    public function getGridBlockJsObjectName()
    {
        return (($gridBlock = $this->getGridBlock()) ? $gridBlock->getJsObjectName() : null);
    }
    
    public function getReapplyDefaultFilterUrl()
    {
        return $this->getUrl(
            'customgrid/grid/reapplyDefaultFilter',
            array(
                'grid_id' => $this->getGridModel()->getId(),
                'profile_id' => $this->getGridModel()->getProfileId(),
            )
        );
    }
    
    public function getFilterResetRequestValue()
    {
        return BL_CustomGrid_Model_Observer::GRID_FILTER_RESET_REQUEST_VALUE;
    }
    
    protected function _beforeToHtml()
    {
        if ($this->getGridBlock() && $this->getGridModel()) {
            $this->setData(
                array(
                    'label'   => $this->__('Reset Filter'),
                    'onclick' => 'blcg.Grid.Tools.reapplyDefaultFilter('
                        . '\'' . $this->jsQuoteEscape($this->getGridBlockJsObjectName()) . '\','
                        . '\'' . $this->getReapplyDefaultFilterUrl() . '\','
                        . '\'' . $this->jsQuoteEscape($this->getFilterResetRequestValue()) . '\''
                        . ')',
                )
            );
        }
        return parent::_beforeToHtml();
    }
}
