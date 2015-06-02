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

class BL_CustomGrid_Model_Column_Renderer_Attribute_Options extends BL_CustomGrid_Model_Column_Renderer_Attribute_Abstract
{
    protected $_backwardsMap = array('options_separator' => 'sub_values_separator');
    
    public function isAppliableToAttribute(
        Mage_Eav_Model_Entity_Attribute $attribute,
        BL_CustomGrid_Model_Grid $gridModel
    ) {
        return ($attribute->getSourceModel() != '')
            || ($attribute->getFrontendInput() == 'select')
            || ($attribute->getFrontendInput() == 'multiselect');
    }
    
    /**
     * Return the options used by the given attribute, if any
     * 
     * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute model
     * @return array|null
     */
    protected function _getAttributeOptions(Mage_Eav_Model_Entity_Attribute $attribute)
    {
        try {
            $options = $attribute->getSource()->getAllOptions(false, true);
        } catch (Exception $e) {
            $options = null;
        }
        return (!empty($options) ? $options : null);
    }
    
    /**
     * Return the usable options for the given attribute
     * 
     * @param Mage_Eav_Model_Entity_Attribute $attribute Attribute model
     * @return array
     */
    protected function _getOptions(Mage_Eav_Model_Entity_Attribute $attribute)
    {
        $options = array();
        
        if ($this->getData('values/force_default_source')
            || !is_array($options = $this->_getAttributeOptions($attribute))) {
            if (($sourceId = $this->getData('values/default_source_id'))
                /** @var $source BL_CustomGrid_Model_Options_Source */
                && ($source = Mage::getModel('customgrid/options_source')->load($sourceId))
                && $source->getId()) {
                $options = $source->getOptionArray();
            }
        }
        
        return (is_array($options) ? $options : array());
    }
    
    public function getColumnBlockValues(
        Mage_Eav_Model_Entity_Attribute $attribute,
        Mage_Core_Model_Store $store,
        BL_CustomGrid_Model_Grid $gridModel
    ) {
        return $this->_getRendererHelper()
            ->getOptionsValues(
                $this,
                $this->_getOptions($attribute),
                ($attribute->getFrontendInput() == 'multiselect'),
                ','
            );
    }
}
