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

class BL_CustomGrid_Block_Widget_Grid_Config_Rss_Links extends Mage_Adminhtml_Block_Widget
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('bl/customgrid/widget/grid/config/rss/links.phtml');
    }
    
    protected function _toHtml()
    {
        return $this->getGridBlock()
            ? parent::_toHtml()
            : '';
    }
    
    public function getId()
    {
        if (!$this->hasData('id')) {
            /** @var $helper Mage_Core_Helper_Data */
            $helper = $this->helper('core');
            $this->setData('id', $helper->uniqHash('blcgRSS'));
        }
        return $this->_getData('id');
    }
    
    /**
     * Return the RSS links from the current grid block
     * 
     * @return array
     */
    public function getRssLinks()
    {
        /** @var $gridBlock Mage_Adminhtml_Block_Widget_Grid */
        $gridBlock = $this->getGridBlock();
        return $gridBlock->getRssLists();
    }
}
