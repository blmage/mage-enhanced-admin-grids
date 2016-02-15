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
 * @copyright  Copyright (c) 2016 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Attribute_Default extends BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Abstract
{
    protected function _getRenderedValue()
    {
        $entity = $this->getEditedEntity();
        $attribute = $this->getEditedAttribute();
        $entityValue = $entity->getData($attribute->getAttributeCode());
        $renderableValue = $this->getRenderableValue();
        $renderedValue   = '';
        
        if ($attribute->getFrontendModel() == Mage_Eav_Model_Entity::DEFAULT_FRONTEND_MODEL) {
            if (((string) $renderableValue) != '') {
                if ($attribute->getFrontendInput() == 'textarea') {
                    // Values from textarea may be too long, so return default value as a default behaviour
                    $renderedValue = $this->getDefaultValue();
                    
                } elseif ($attribute->getFrontendInput() == 'price') {
                    // Convert prices for display (else they would be displayed as raw decimal)
                    if (is_string($renderableValue)) {
                        $renderedValue = Mage::app()
                            ->getStore($entity->getStoreId())
                            ->convertPrice($renderableValue, true, false);
                    } else {
                        $renderedValue = $this->getDefaultValue();
                    }
                    
                } elseif ($attribute->getFrontendInput() == 'date') {
                    // Convert dates for display (else they would be displayed in internal format)
                    $date = new Zend_Date($renderableValue, Varien_Date::DATETIME_INTERNAL_FORMAT);
                    $format = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
                    $renderedValue = $date->toString($format);
                    
                } elseif (in_array($attribute->getFrontendInput(), array('boolean', 'select'))
                    && !$attribute->getFrontend()->getOption($entityValue)) {
                    // Default frontend model uses boolean values when no option, so counter this
                    $renderedValue = '';
                    
                } else {
                    // Use escaped raw value for everything else
                    $renderedValue = $this->htmlEscape($renderableValue);
                }
            }
        } else {
            // If the frontend model is a specific one, trust it for the rendering
            $renderedValue = $renderableValue;
        }
        
        return $renderedValue;
    }
}
