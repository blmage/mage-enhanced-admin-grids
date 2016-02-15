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

class BL_CustomGrid_Block_Widget_Grid_Editor_Form_Container extends BL_CustomGrid_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        
        $this->_blockGroup = null;
        $this->_removeButtons(array('back', 'delete', 'reset'));
        $this->setTemplate('bl/customgrid/widget/grid/editor/form/container.phtml');
        
        $this->_addButton(
            'save',
            array(
                'label'   => $this->__('Save'),
                'onclick' => 'blcgGridEditorForm.submit();',
                'class'   => 'save',
            ),
            1
        );
    }
    
    public function getUseDefaultJsFormObject()
    {
        return false;
    }
    
    /**
     * Set the given form block as the "form" child of this block
     * 
     * @param Mage_Core_Block_Abstract $formBlock Form block
     * @return BL_CustomGrid_Block_Widget_Grid_Editor_Form_Container
     */
    public function setChildForm(Mage_Core_Block_Abstract $formBlock)
    {
        return $this->setChild('form', $formBlock);
    }
    
    public function getFormHtml()
    {
        return ($this->getChild('form') ? parent::getFormHtml() : '');
    }
    
    /**
     * Return the editor JS object name
     * 
     * @return string
     */
    public function getEditorJsObjectName()
    {
        if (!$this->hasData('editor_js_object_name')) {
            if (($jsObjectName = $this->_getJsObjectName('editor_js_object_name'))
                && !$this->getIsEditedInGrid()) {
                $this->setData('editor_js_object_name', 'parent.' . $jsObjectName);
            }
        }
        return $this->_getData('editor_js_object_name');
    }
}
