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

class BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Field_Date extends BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Abstract
{
    /**
     * Return the input locale from the given value config
     *
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig Value config
     * @return string|Zend_Locale|null
     */
    protected function _getDateInputLocale(BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig)
    {
        return $valueConfig->getData('renderer/input_locale');
    }
    
    /**
     * Return the input date format from the given value config
     *
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig Value config
     * @return string
     */
    protected function _getDateInputFormat(BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig)
    {
        return $valueConfig->hasData('renderer/input_format')
            ? $valueConfig->etData('renderer/input_format')
            : Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
    }
    
    /**
     * Return a Zend_Date object from the given renderable date value
     *
     * @param Zend_Date $renderableValue Renderable value
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig Value config
     * @return Zend_Date|null
     */
    protected function _getRenderableZendDate(
        $renderableValue,
        BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig
    ) {
        if (ctype_digit($renderableValue)) {
            $renderableValue = (int) $renderableValue;
            
            if ($renderableValue > 3155760000) {
                $renderableValue = 0;
            }
            
            $renderableValue = new Zend_Date($renderableValue);
        } else {
            try {
                $renderableValue = new Zend_Date(
                    $renderableValue,
                    $this->_getDateInputFormat($valueConfig),
                    $this->_getDateInputLocale($valueConfig)
                );
            } catch (Exception $e) {
                $renderableValue = null;
            }
        }
        return $renderableValue;
    }
    
    /**
     * Return the output locale from the given value config
     *
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig Value config
     * @return string|Zend_Locale|null
     */
    protected function _getDateOutputLocale(BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig)
    {
        return $valueConfig->getData('renderer/output_locale');
    }
    
    /**
     * Return the output date format from the given value config
     *
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig Value config
     * @return string
     */
    protected function _getDateOutputFormat(BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig)
    {
        return $valueConfig->hasData('renderer/output_format')
            ? $valueConfig->etData('renderer/output_format')
            : Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
    }
    
    protected function _getRenderedValue($renderableValue)
    {
        $valueConfig = $this->getValueConfig();
        
        if (empty($renderableValue)) {
            return '';
        } elseif ((!$renderableValue instanceof Zend_Date)
            && (!$renderableValue = $this->_getRenderableZendDate($renderableValue, $valueConfig))) {
            return $this->getDefaultValue();
        }
        
        return $renderableValue->toString(
            $this->_getDateOutputFormat($valueConfig),
            null,
            $this->_getDateOutputLocale($valueConfig)
        );
    }
}
