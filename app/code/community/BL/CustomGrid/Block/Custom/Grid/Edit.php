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

class BL_CustomGrid_Block_Custom_Grid_Edit
    extends Mage_Adminhtml_Block_Widget
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('bl/customgrid/custom/grid/edit.phtml');
        $this->setId('custom_grid_edit');
    }
    
    public function getCustomGrid()
    {
        return Mage::registry('current_custom_grid');
    }
    
    protected function _prepareLayout()
    {
        $source = $this->getOptionsSource();
        
        $this->setChild('back_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'   => $this->__('Back'),
                    'onclick' => 'setLocation(\''.$this->getUrl('*/*/').'\')',
                    'class'   => 'back',
                ))
        );
        
        $this->setChild('reset_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'   => $this->__('Reset'),
                    'onclick' => 'setLocation(\''.$this->getUrl('*/*/*', array('_current' => true)).'\')',
                ))
        );
        
        $this->setChild('save_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'   => $this->__('Save'),
                    'onclick' => 'customGridForm.submit()',
                    'class'   => 'save',
                ))
        );
        
        $this->setChild('save_and_edit_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array(
                    'label'   => $this->__('Save and Continue Edit'),
                    'onclick' => 'saveAndContinueEdit(\''.$this->getSaveAndContinueUrl().'\')',
                    'class'   => 'save',
                ))
        );
        
        $this->setChild('delete_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setData(array(
                        'label'   => $this->__('Delete'),
                        'onclick' => 'confirmSetLocation(\''.$this->__('Are you sure?').'\', \''.$this->getDeleteUrl().'\')',
                        'class'   => 'delete',
                    ))
        );
        
        return parent::_prepareLayout();
    }
    
    protected function _beforeToHtml()
    {
        if ($tabsBlock = $this->getChild('custom_grid_tabs')) {
            $tabsBlock->setActiveTab($this->getSelectedTabId());
        }
        return parent::_beforeToHtml();
    }
    
    public function getBackButtonHtml()
    {
        return $this->getChildHtml('back_button');
    }
    
    public function getCancelButtonHtml()
    {
        return $this->getChildHtml('reset_button');
    }
    
    public function getSaveButtonHtml()
    {
        return $this->getChildHtml('save_button');
    }
    
    public function getSaveAndEditButtonHtml()
    {
        return $this->getChildHtml('save_and_edit_button');
    }
    
    public function getDeleteButtonHtml()
    {
        return $this->getChildHtml('delete_button');
    }
    
    public function getSaveUrl()
    {
        return $this->getUrl('*/*/save', array('_current' => true, 'back' => null));
    }
    
    public function getSaveAndContinueUrl()
    {
        return $this->getUrl('*/*/save', array(
            '_current'   => true,
            'back'       => 'edit',
            'tab'        => '{{tab_id}}',
            'active_tab' => null,
        ));
    }
    
    public function getDeleteUrl()
    {
        return $this->getUrl('*/*/delete', array('_current' => true));
    }
    
    public function getHeader()
    {
        $grid   = $this->getCustomGrid();
        $header = $this->__('Custom Grid: %s', $grid->getBlockType()).' - ';
        
        if ($grid->getRewritingClassName()) {
            $header .= $grid->getRewritingClassName();
        } else {
            $header .= $this->__('Base Class');
        }
        
        return $header;
    }
    
    public function getSelectedTabId()
    {
        return addslashes(htmlspecialchars($this->getRequest()->getParam('tab')));
    }
    
    public function getCustomGridId()
    {
        return $this->getCustomGrid()->getId();
    }
    
    public function getRefererInputName()
    {
        return Mage_Adminhtml_Controller_Action::PARAM_NAME_URL_ENCODED;
    }
    
    public function getRefererInputValue()
    {
        return Mage::helper('core')->urlEncode($this->getUrl('*/*'));
    }
}