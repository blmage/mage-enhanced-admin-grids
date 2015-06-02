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

class BL_CustomGrid_Block_Widget_Grid_Editor_Form_Attribute_Product extends BL_CustomGrid_Block_Widget_Grid_Editor_Form_Attribute_Default
{
    protected function _prepareLayout()
    {
        $returnValue = parent::_prepareLayout();
        $inGrid   = $this->getEditedInGrid();
        $required = $this->getIsRequiredValueEdit();
        
        /** @var $elementRenderer BL_CustomGrid_Block_Widget_Grid_Editor_Form_Renderer_Product_Fieldset_Element */
        $elementRenderer = $this->getLayout()
            ->createBlock('customgrid/widget_grid_editor_form_renderer_product_fieldset_element');
        
        $elementRenderer->setEditedInGrid($inGrid)->setIsRequiredValueEdit($required);
        Varien_Data_Form::setFieldsetElementRenderer($elementRenderer);
        
        return $returnValue;
    }
    
    protected function _prepareForm()
    {
        $returnValue = parent::_prepareForm();
        $inGrid   = $this->getEditedInGrid();
        $required = $this->getIsRequiredValueEdit();
        
        if ($form = $this->getForm()) {
            if ($urlKey = $form->getElement('url_key')) {
                /** @var $renderer BL_CustomGrid_Block_Widget_Grid_Editor_Form_Renderer_Product_Attribute_Urlkey */
                $renderer = $this->getLayout()
                    ->createBlock('customgrid/widget_grid_editor_form_renderer_product_attribute_urlkey');
                
                $renderer->setEditedInGrid($inGrid)->setIsRequiredValueEdit($required);
                $urlKey->setRenderer($renderer);
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
        
        return $returnValue;
    }
    
    protected function _getAdditionalElementTypes()
    {
        $config = Mage::getConfig();
        /** @var $helper BL_CustomGrid_Helper_Data */
        $helper= $this->helper('customgrid');
        
        $result = array(
            'price'    => $config->getBlockClassName('customgrid/widget_grid_editor_form_helper_product_price'),
            'gallery'  => $config->getBlockClassName('adminhtml/catalog_product_helper_form_gallery'),
            'image'    => $config->getBlockClassName('adminhtml/catalog_product_helper_form_image'),
            'boolean'  => $config->getBlockClassName('adminhtml/catalog_product_helper_form_boolean'),
            'textarea' => $config->getBlockClassName('customgrid/widget_grid_editor_form_helper_product_wysiwyg'),
        );
        
        if ($helper->isMageVersionGreaterThan(1, 6)) {
            $result['weight'] = $config->getBlockClassName('adminhtml/catalog_product_helper_form_weight');
        }
        
        $response = new BL_CustomGrid_Object();
        $response->setTypes(array());
        Mage::dispatchEvent('adminhtml_catalog_product_edit_element_types', array('response' => $response));
        
        foreach ($response->getTypes() as $typeName => $typeClass) {
            $result[$typeName] = $typeClass;
        }
        
        return $result;
    }
}
