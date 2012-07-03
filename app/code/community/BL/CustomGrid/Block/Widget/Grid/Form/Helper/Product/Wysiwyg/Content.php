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

class BL_CustomGrid_Block_Widget_Grid_Form_Helper_Product_Wysiwyg_Content
    extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
            'id'     => 'wysiwyg_edit_form',
            'action' => $this->_getData('action'),
            'method' => 'post',
        ));
        
        $config['document_base_url'] = $this->_getData('store_media_url');
        $config['store_id']          = $this->_getData('store_id');
        $config['add_variables']     = false;
        $config['add_widgets']       = false;
        $config['add_directives']    = true;
        $config['use_container']     = true;
        $config['container_class']   = 'hor-scroll';
        
        $form->addField($this->_getData('editor_element_id'), 'editor', array(
            'name'       => 'content',
            'style'      => 'width:725px; height:460px',
            'required'   => true,
            'force_load' => true,
            'config'     => Mage::helper('customgrid/editor')->getWysiwygConfig($config)
        ));
        
        $this->setForm($form);
        return parent::_prepareForm();
    }
}