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
 * @copyright  Copyright (c) 2015 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Block_Options_Source_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('blcg_options_source_edit_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle($this->__('Options Source'));
    }
    
    protected function _prepareLayout()
    {
        if ($type = $this->getOptionsSourceType()) {
            $this->addTab('general', 'customgrid/options_source_edit_tab_general');
            
            if ($type == BL_CustomGrid_Model_Options_Source::TYPE_MAGE_MODEL) {
                $this->addTab('mage_model', 'customgrid/options_source_edit_tab_model');
            } elseif ($type == BL_CustomGrid_Model_Options_Source::TYPE_CUSTOM_LIST) {
                $this->addTab('custom_list', 'customgrid/options_source_edit_tab_custom');
            }
        } else {
            $this->addTab('type', 'customgrid/options_source_edit_tab_settings');
        }
        return parent::_prepareLayout();
    }
    
    /**
     * Return the edited options source
     * 
     * @return BL_CustomGrid_Model_Options_Source
     */
    public function getOptionsSource()
    {
        return Mage::registry('blcg_options_source');
    }
    
    /**
     * Return the type of the edited options source
     * 
     * @return string
     */
    public function getOptionsSourceType()
    {
        return (!$type = $this->getOptionsSource()->getType())
            ? $this->getRequest()->getParam('type', null)
            : $type;
    }
}
