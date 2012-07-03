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

class BL_CustomGrid_Block_Widget_Grid_Form_Container
    extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('save');
        $this->_blockGroup = null;
        $this->setTemplate('bl/customgrid/widget/grid/form/container.phtml');
        
        $this->_addButton('save', array(
            'label'     => Mage::helper('adminhtml')->__('Save'),
            'onclick'   => 'blcgGridEditForm.submit();',
            'class'     => 'save',
        ), 1);
    }
    
    public function setChildForm(Mage_Core_Block_Abstract $block)
    {
        $this->setChild('form', $block);
        return $this;
    }
    
    public function getFormElementId()
    {
        if ($form = $this->getChild('form')) {
            return $form->getForm()->getId();
        }
        return 'edit-form';
    }
    
    public function getEditorJsObjectName()
    {
        if ($objectName = $this->getRequest()->getParam('editor_js_object_name', false)) {
            if ($this->getRequest()->getParam('is_external', false)) {
                $objectName = 'parent.'.$objectName;
            }
        }
        return $objectName;
    }
    
    public function getFormHtml()
    {
        if ($this->getChild('form')) {
            return parent::getFormHtml();
        }
        return '';
    }
}