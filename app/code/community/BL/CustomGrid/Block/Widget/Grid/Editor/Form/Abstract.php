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

abstract class BL_CustomGrid_Block_Widget_Grid_Editor_Form_Abstract extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('bl/customgrid/widget/grid/editor/form.phtml');
    }
    
    protected function _prepareLayout()
    {
        $return   = parent::_prepareLayout();
        $inGrid   = $this->getEditedInGrid();
        $required = $this->getIsRequiredValueEdit();
        
        Varien_Data_Form::setElementRenderer(
            $this->getLayout()
                ->createBlock('customgrid/widget_grid_editor_form_renderer_element')
                ->setEditedInGrid($inGrid)
                ->setIsRequiredValueEdit($required)
        );
        
        Varien_Data_Form::setFieldsetRenderer(
            $this->getLayout()
                ->createBlock('customgrid/widget_grid_editor_form_renderer_fieldset')
                ->setEditedInGrid($inGrid)
                ->setIsRequiredValueEdit($required)
        );
        
        Varien_Data_Form::setFieldsetElementRenderer(
            $this->getLayout()
                ->createBlock('customgrid/widget_grid_editor_form_renderer_fieldset_element')
                ->setEditedInGrid($inGrid)
                ->setIsRequiredValueEdit($required)
        );
        
        return $return;
    }
    
    protected function _beforeToHtml()
    {
        if (is_object($this->getEditedEntity()) && is_object($this->getEditedValue())) {
            $this->setCanDisplay(true);
            return parent::_beforeToHtml();
        }
        $this->setCanDisplay(false);
        return $this;
    }
    
    protected function _toHtml()
    {
        if ($this->getCanDisplay()) {
            $html = parent::_toHtml();
            
            if ($this->getEditedInGrid()) {
                $html .= '<input name="form_key" type="hidden" value="' . $this->getFormKey() . '" />';
            }
            
            return $html;
        }
        return '';
    }
    
    public function getFormId()
    {
        return $this->getDataSetDefault('form_id', $this->helper('core')->uniqHash('blcg_grid_editor_form'));
    }
    
    protected function _initializeForm()
    {
        return new Varien_Data_Form(
            array(
                'id'     => $this->getFormId(),
                'method' => 'post',
                'html_id_prefix' => $this->getFormId(),
                'use_container'  => true,
            )
        );
    }
    
    abstract public function getIsRequiredValueEdit();
}
