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

abstract class BL_CustomGrid_Block_Widget_Grid_Form
    extends Mage_Adminhtml_Block_Widget_Form
{
    protected $_canDisplay = false;
    
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('bl/customgrid/widget/grid/form.phtml');
    }
    
    protected function _prepareLayout()
    {
        $return   = parent::_prepareLayout();
        $inGrid   = $this->getEditedInGrid();
        $required = $this->getIsRequiredValueEdit();
        
        Varien_Data_Form::setElementRenderer(
            $this->getLayout()
                ->createBlock('customgrid/widget_grid_form_renderer_element')
                ->setEditedInGrid($inGrid)
                ->setIsRequiredValueEdit($required)
        );
        Varien_Data_Form::setFieldsetRenderer(
            $this->getLayout()
                ->createBlock('customgrid/widget_grid_form_renderer_fieldset')
                ->setEditedInGrid($inGrid)
                ->setIsRequiredValueEdit($required)
        );
        Varien_Data_Form::setFieldsetElementRenderer(
            $this->getLayout()
                ->createBlock('customgrid/widget_grid_form_renderer_fieldset_element')
                ->setEditedInGrid($inGrid)
                ->setIsRequiredValueEdit($required)
        );
        
        return $return;
    }
    
    protected function _beforeToHtml()
    {
        if (is_object($this->getEditedEntity())
            && is_array($this->getEditedValue())) {
            $this->_canDisplay = true;
            return parent::_beforeToHtml();
        } else {
            $this->_canDisplay = false;
            return $this;
        }
    }
    
    protected function _toHtml()
    {
        if ($this->_canDisplay) {
            $html = parent::_toHtml();
            if ($this->getEditedInGrid()) {
                $html .= '<input name="form_key" type="hidden" value="'.Mage::getSingleton('core/session')->getFormKey().'" />';
            }
            return $html;
        }
        return '';
    }
    
    protected function _createForm()
    {
        $form = new Varien_Data_Form(array(
            'id'     => Mage::helper('core')->uniqHash('blcg_grid_form'),
            'method' => 'post', // Needed to get form key if using container
        ));
        $form->setHtmlIdPrefix(Mage::helper('core')->uniqHash('blcg_grid_form_'));
        $form->setUseContainer(!$this->getEditedInGrid());
        return $form;
    }
    
    abstract public function getIsRequiredValueEdit();
}