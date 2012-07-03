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

class BL_CustomGrid_Block_Options_Source_Edit_Tabs
    extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('options_source_info_tabs');
        $this->setDestElementId('options_source_edit_form');
        $this->setTitle($this->__('Options Source'));
    }
    
    public function getOptionsSourceType()
    {
        if (!($type = $this->getOptionsSource()->getType()) && $this->getRequest()) {
            $type = $this->getRequest()->getParam('type', null);
        }
        return $type;
    }
    
    protected function _prepareLayout()
    {
        $source = $this->getOptionsSource();
        $type   = $this->getOptionsSourceType();
        
        if ($type) {
            $this->addTab('general', array(
                'label'   => $this->__('General'),
                'content' => $this->getLayout()->createBlock('customgrid/options_source_edit_tab_general')->toHtml(),
                'active'  => true,
            ));
        } else {
            $this->addTab('type', array(
                'label'   => $this->__('Settings'),
                'content' => $this->getLayout()->createBlock('customgrid/options_source_edit_tab_settings')->toHtml(),
                'active'  => true,
            ));
        }
        
        return parent::_prepareLayout();
    }
    
    public function getOptionsSource()
    {
        if (!($this->_getData('options_source') instanceof BL_CustomGrid_Model_Options_Source)) {
            $this->setData('options_source', Mage::registry('options_source'));
        }
        return $this->_getData('options_source');
    }
}