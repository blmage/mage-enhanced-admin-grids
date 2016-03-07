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

class BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Field_Default extends BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Abstract
{
    /**
     * Renderer types by field type, knowing that :
     * - a false value indicates that the default value must be displayed
     * - the "text" renderer will be used for unlisted field types
     * 
     * @var array
     */
    static protected $_fieldTypeRendererTypes = array(
        'checkbox'    => false,
        'checkboxes'  => 'checkbox',
        'date'        => 'date',
        'editor'      => 'longtext',
        'multiselect' => 'multiselect',
        'radio'       => false,
        'radios'      => 'radio',
        'select'      => 'select',
        'text'        => 'text',
        'textarea'    => 'longtext',
    );
    
    /**
     * Return the default rendered value
     * 
     * @return string
     */
    protected function _renderDefaultValue()
    {
        return $this->getDefaultValue();
    }
    
    protected function _getRenderedValue($renderableValue)
    {
        $fieldType = $this->getValueConfig()->getFormFieldType();
        
        if (isset(self::$_fieldTypeRendererTypes[$fieldType])) {
            $rendererType = self::$_fieldTypeRendererTypes[$fieldType];
        } else {
            $rendererType = 'text';
        }
        
        if (is_string($rendererType)) {
            /** @var BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Abstract $rendererBlock */
            $rendererBlock = $this->getLayout()
                ->createBlock('customgrid/widget_grid_editor_renderer_field_' . $rendererType);
            
            $rendererBlock->addData(
                array_intersect_key(
                    $this->getData(),
                    array_flip(
                        array(
                            'editor_context',
                            'value_config',
                            'edited_attribute',
                            'edited_entity',
                            'default_value',
                            'renderable_value',
                        )
                    )
                )
            );
            
            $renderedValue = $rendererBlock->toHtml();
        } else {
            $renderedValue = $this->_renderDefaultValue();
        }
        
        return $renderedValue;
    }
}
