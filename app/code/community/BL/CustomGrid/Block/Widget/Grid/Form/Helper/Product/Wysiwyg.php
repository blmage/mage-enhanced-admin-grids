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

class BL_CustomGrid_Block_Widget_Grid_Form_Helper_Product_Wysiwyg
    extends Mage_Adminhtml_Block_Catalog_Helper_Form_Wysiwyg
{
    public function getAfterElementHtml()
    {
        $html = Varien_Data_Form_Element_Textarea::getAfterElementHtml();
        if ($this->getIsWysiwygEnabled()) {
            $disabled = ($this->getDisabled() || $this->getReadonly());
            $html .= Mage::getSingleton('core/layout')
                ->createBlock('adminhtml/widget_button', '', array(
                    'label'    => Mage::helper('catalog')->__('WYSIWYG Editor'),
                    'type'     => 'button',
                    'disabled' => $disabled,
                    'class'    => ($disabled) ? 'disabled' : '',
                    'onclick'  => 'catalogWysiwygEditor.open(\''.Mage::helper('adminhtml')->getUrl('adminhtml/blcg_custom_grid_editor_product/wysiwyg').'\', \''.$this->getHtmlId().'\')'
                ))->toHtml();
        }
        return $html;
    }
}