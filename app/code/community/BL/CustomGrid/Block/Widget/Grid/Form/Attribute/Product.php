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

class BL_CustomGrid_Block_Widget_Grid_Form_Attribute_Product
    extends BL_CustomGrid_Block_Widget_Grid_Form_Attribute_Default
{
    protected function _prepareLayout()
    {
        $return   = parent::_prepareLayout();
        $inGrid   = $this->getEditedInGrid();
        $required = $this->getIsRequiredValueEdit();
        
        Varien_Data_Form::setFieldsetElementRenderer(
            $this->getLayout()
                ->createBlock('customgrid/widget_grid_form_renderer_product_fieldset_element')
                ->setEditedInGrid($inGrid)
                ->setIsRequiredValueEdit($required)
        );
        
        return $return;
    }
    
    protected function _prepareForm()
    {
        $return   = parent::_prepareForm();
        $inGrid   = $this->getEditedInGrid();
        $required = $this->getIsRequiredValueEdit();
        
        if ($form = $this->getForm()) {
            if ($urlKey = $form->getElement('url_key')) {
                $urlKey->setRenderer(
                    $this->getLayout()
                        ->createBlock('customgrid/widget_grid_form_renderer_product_attribute_urlkey')
                        ->setEditedInGrid($inGrid)
                        ->setIsRequiredValueEdit($required)
                );
            }
            if ($tierPrice = $form->getElement('tier_price')) {
                $tierPrice->setRenderer(
                    $this->getLayout()->createBlock('adminhtml/catalog_product_edit_tab_price_tier')
                );
            }
            if ($recurringProfile = $form->getElement('recurring_profile')) {
                $recurringProfile->setRenderer(
                    $this->getLayout()->createBlock('adminhtml/catalog_product_edit_tab_price_recurring')
                );
            }
            if ($form->getElement('meta_description')) {
                $form->getElement('meta_description')->setOnkeyup('checkMaxLength(this, 255);');
            }
            
            Mage::dispatchEvent('adminhtml_catalog_product_edit_prepare_form', array('form' => $form));
        }
        
        return $return;
    }
    
    protected function _getAdditionalElementTypes()
    {
        $result = array(
            'price'    => Mage::getConfig()->getBlockClassName('customgrid/widget_grid_form_helper_product_price'),
            'gallery'  => Mage::getConfig()->getBlockClassName('adminhtml/catalog_product_helper_form_gallery'),
            'image'    => Mage::getConfig()->getBlockClassName('adminhtml/catalog_product_helper_form_image'),
            'boolean'  => Mage::getConfig()->getBlockClassName('adminhtml/catalog_product_helper_form_boolean'),
            'textarea' => Mage::getConfig()->getBlockClassName('customgrid/widget_grid_form_helper_product_wysiwyg'),
        );
        
        if ($this->helper('customgrid')->isMageVersionGreaterThan(1, 6)) {
            $result['weight'] = Mage::getConfig()->getBlockClassName('adminhtml/catalog_product_helper_form_weight');
        }
        
        $response = new Varien_Object();
        $response->setTypes(array());
        Mage::dispatchEvent('adminhtml_catalog_product_edit_element_types', array('response' => $response));
        
        foreach ($response->getTypes() as $typeName => $typeClass) {
            $result[$typeName] = $typeClass;
        }
        
        return $result;
    }
}