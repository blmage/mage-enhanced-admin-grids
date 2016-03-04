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

class BL_CustomGrid_Model_Grid_Editor_Value_Renderer extends BL_CustomGrid_Model_Grid_Editor_Worker_Abstract
{
    const ACTION_TYPE_GET_RENDERABLE_CONTEXT_EDITED_VALUE = 'get_renderable_context_edited_value';
    const ACTION_TYPE_GET_CONTEXT_VALUE_RENDERER_BLOCK    = 'get_context_value_renderer_block';
    
    public function getType()
    {
        return BL_CustomGrid_Model_Grid_Editor_Abstract::WORKER_TYPE_VALUE_RENDERER;
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
     * Default main callback for
     * @see BL_CustomGrid_Model_Grid_Editor_Value_Renderer::getRenderableContextEditedValue()
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @param BL_CustomGrid_Object $transport Transport object used to hold the renderable value
     */
    public function _getRenderableContextEditedValue(
        BL_CustomGrid_Model_Grid_Editor_Context $context,
        BL_CustomGrid_Object $transport
    ) {
        if (!$transport->hasData('value')) {
            $valueConfig = $context->getValueConfig();
            
            if ($context->getValueOrigin() == BL_CustomGrid_Model_Grid_Editor_Abstract::EDITABLE_TYPE_ATTRIBUTE) {
                $attribute = $valueConfig->getAttribute();
                $transport->setData('value', $attribute->getFrontend()->getValue($context->getEditedEntity()));
            } else {
                $editedEntity   = $context->getEditedEntity();
                $formFieldName  = $context->getFormFieldName();
                $entityValueKey = $valueConfig->getEntityValueKey();
                
                $transport->setData(
                    'value',
                    $editedEntity->getData($editedEntity->hasData($formFieldName) ? $formFieldName : $entityValueKey)
                );
            }
        }
    }
    
    /**
     * Return the edited value from the given editor context, suitable for being rendered
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return mixed
     */
    public function getRenderableContextEditedValue(BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        $transport = new BL_CustomGrid_Object();
        
        $this->_runCallbackedAction(
            self::ACTION_TYPE_GET_RENDERABLE_CONTEXT_EDITED_VALUE,
            array('context' => $context, 'transport' => $transport),
            array($this, '_getRenderableContextEditedValue'),
            $context
        );
        
        if (!$transport->hasData('value')) {
            Mage::throwException('Could not retrieve the renderable edited value from the editor context');
        }
        
        return $transport->getData('value');
    }
    
    /**
     * Default main callback for
     * @see BL_CustomGrid_Model_Grid_Editor_Value_Renderer::getContextValueRendererBlock()
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @param mixed $previousReturnedValue Value returned by the previous callback
     * @return Mage_Core_Block_Abstract
     */
    public function _getContextValueRendererBlock(
        BL_CustomGrid_Model_Grid_Editor_Context $context,
        $previousReturnedValue
    ) {
        $valueConfig = $context->getValueConfig();
        $valueRendererBlock = $previousReturnedValue;
        $valueRendererData  = array(
            'edited_entity'     => $context->getEditedEntity(),
            'editor_context'    => $context,
            'value_config'      => $valueConfig,
        );
        
        if ($context->isAttributeValueContext()) {
            $valueRendererData['edited_attribute'] = $valueConfig->getAttribute();
        }
        
        if (!$valueRendererBlock instanceof Mage_Core_Block_Abstract) {
            $rendererBlockType = $valueConfig->getData('renderer/block_type');
            
            if (strpos($rendererBlockType, '/') === false) {
                $rendererBlockType = 'customgrid/widget_grid_editor_renderer'
                    . '_' . $context->getValueOrigin()
                    . '_' . $rendererBlockType;
            }
            
            if (!$valueRendererBlock = $this->_getLayout()->createBlock($rendererBlockType, '', $valueRendererData)) {
                Mage::throwException('Could not create the value renderer block');
            }
        } else {
            foreach ($valueRendererData as $key => $value) {
                if (!$valueRendererBlock->hasData($key)) {
                    $valueRendererBlock->setData($key, $value);
                }
            }
        }
        
        return $valueRendererBlock;
    }
    
    /**
     * Return the value renderer block from the given editor context
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return Mage_Core_Block_Abstract
     */
    public function getContextValueRendererBlock(BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        return $this->_runCallbackedAction(
            self::ACTION_TYPE_GET_CONTEXT_VALUE_RENDERER_BLOCK,
            array('context' => $context),
            array($this, '_getContextValueRendererBlock'),
            $context
        );
    }
    
    /**
     * Return the edited value from the given editor context, suitable for being displayed in the grid
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return mixed
     */
    public function getRenderedContextEditedValue(BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        if ($context->getValueConfig()->getData('renderer/must_reload')) {
            $isReloaded = $this->getEditor()
                ->getEntityLoader()
                ->reloadEditedEntity($context->getEditedEntity(), $context);
            
            if (!$isReloaded) {
                Mage::throwException('Could not reload the edited entity from the editor context');
            }
        }
        
        $renderableValue = $this->getRenderableContextEditedValue($context);
        
        if (!$rendererBlock = $this->getContextValueRendererBlock($context)) {
            $renderedValue = $this->getBaseHelper()->__('<em>Updated</em>');
        } else {
            $rendererBlock->setRenderableValue($renderableValue);
            $renderedValue = $rendererBlock->toHtml();
        }
        
        return $renderedValue;
    }
}
