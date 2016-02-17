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

class BL_CustomGrid_Model_Grid_Editor_Value_Form_Renderer extends BL_CustomGrid_Model_Grid_Editor_Worker_Abstract
{
    const ACTION_TYPE_GET_CONTEXT_VALUE_FORM_BLOCK = 'get_context_value_form_block';
    
    public function getType()
    {
        return BL_CustomGrid_Model_Grid_Editor_Abstract::WORKER_TYPE_VALUE_FORM_RENDERER;
    }
    
    /**
     * Return the layout model
     *
     * @return Mage_Core_Model_Layout
     */
    protected function _getLayout()
    {
        return Mage::getSingleton('core/layout');
    }
    
    /**
     * Return the name of the edited entity from the given editor context
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return string
     */
    protected function _getContextEditedEntityName(BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        return ($nameKey = $this->getEditor()->getBaseConfig()->getData('entity_name_data_key'))
            ? $context->getEditedEntity()->getDataUsingMethod($nameKey)
            : '';
    }
    
    /**
     * Default main callback for @see BL_CustomGrid_Model_Grid_Editor_Value_Form_Renderer::getContextValueFormBlock()
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @param mixed $previousReturnedValue Value returned by the previous callback
     * @return Mage_Core_Block_Abstract
     */
    public function _getContextValueFormBlock(BL_CustomGrid_Model_Grid_Editor_Context $context, $previousReturnedValue)
    {
        $valueConfig = $context->getValueConfig();
        $valueFormBlock = $previousReturnedValue;
        $valueFormData  = array(
            'edited_entity'      => $context->getEditedEntity(),
            'edited_entity_name' => $this->_getContextEditedEntityName($context),
            'edited_attribute'   => $valueConfig->getData('global/attribute'),
            'editor_context'     => $context,
            'value_config'       => $valueConfig,
            'is_edited_in_grid'  => (bool) $valueConfig->getDataSetDefault('form/is_in_grid', false),
        );
        
        if (!$valueFormBlock instanceof Mage_Core_Block_Abstract) {
            $formBlockType = $valueConfig->getData('form/block_type');
            
            if (strpos($formBlockType, '/') === false) {
                $formBlockType = 'customgrid/widget_grid_editor_form'
                    . '_' . $context->getValueOrigin()
                    . '_' . $formBlockType;
            }
            
            if (!$valueFormBlock = $this->_getLayout()->createBlock($formBlockType, '', $valueFormData)) {
                Mage::throwException('Could not create the value form block');
            }
        } else {
            foreach ($valueFormData as $key => $value) {
                if (!$valueFormBlock->hasData($key)) {
                    $valueFormBlock->setData($key, $value);
                }
            }
        }
        
        return $valueFormBlock;
    }
    
    /**
     * Return the value form block from the given editor context
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return Mage_Core_Block_Abstract
     */
    public function getContextValueFormBlock(BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        return $this->_runCallbackedAction(
            self::ACTION_TYPE_GET_CONTEXT_VALUE_FORM_BLOCK,
            array('context' => $context),
            array($this, '_getContextValueFormBlock'),
            $context
        );
    }
    
    /**
     * Return the layout handles appliable for the rendering of the value form from the given editor context
     * (by default, this is only used for non in-grid edit)
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return string[]
     */
    public function getContextValueFormLayoutHandles(BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        return $context->getValueConfig()->getDataSetDefault('form/layout_handles', array());
    }
}
