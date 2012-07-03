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

class BL_CustomGrid_Block_Options_Source_Edit
    extends Mage_Adminhtml_Block_Widget
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('bl/customgrid/options/source/edit.phtml');
        $this->setId('options_source_edit');
    }
    
    public function getOptionsSource()
    {
        return Mage::registry('current_options_source');
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
                    'onclick' => 'optionsSourceForm.submit()',
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
        
        if ($source->getId()) {
            $this->setChild('delete_button',
                $this->getLayout()->createBlock('adminhtml/widget_button')
                        ->setData(array(
                            'label'   => $this->__('Delete'),
                            'onclick' => 'confirmSetLocation(\''.$this->__('Are you sure?').'\', \''.$this->getDeleteUrl().'\')',
                            'class'   => 'delete',
                        ))
            );
        }
        
        return parent::_prepareLayout();
    }
    
    protected function _beforeToHtml()
    {
        if ($tabsBlock = $this->getChild('options_source_tabs')) {
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
    
    public function getOptionsSourceId()
    {
        return $this->getOptionsSource()->getId();
    }
    
    public function getDeleteUrl()
    {
        return $this->getUrl('*/*/delete', array('_current' => true));
    }
    
    public function getHeader()
    {
        $header = '';
        if ($this->getOptionsSource()->getId()) {
            $header = $this->htmlEscape($this->getOptionsSource()->getName());
        }
        else {
            $header = $this->__('New Options Source');
        }
        return $header;
    }
    
    public function getOptionsSourceType()
    {
        if (!($type = $this->getOptionsSource()->getType()) && $this->getRequest()) {
            $type = $this->getRequest()->getParam('type', null);
        }
        return $type;
    }
    
    public function getSelectedTabId()
    {
        return addslashes(htmlspecialchars($this->getRequest()->getParam('tab')));
    }
}