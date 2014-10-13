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
 * @copyright  Copyright (c) 2014 Benoît Leulliette <benoit.leulliette@gmail.com>
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
                'label'   => $this->helper('adminhtml')->__('Save'),
                'onclick' => 'blcgGridEditorForm.submit();',
                'class'   => 'save',
            ),
            1
        );
    }
    
    public function getUseDefaultForm()
    {
        return false;
    }
    
    public function setChildForm(Mage_Core_Block_Abstract $formBlock)
    {
        return $this->setChild('form', $formBlock);
    }
    
    public function getFormHtml()
    {
        return ($this->getChild('form') ? parent::getFormHtml() : '');
    }
    
    public function getEditorJsObjectName()
    {
        if (!$this->hasData('editor_js_object_name')) {
            if ($jsObjectName = $this->getRequest()->getParam('editor_js_object_name', false)) {
                $jsObjectName = $this->helper('customgrid/string')->sanitizeJsObjectName($jsObjectName);
                
                if ($this->getRequest()->getParam('is_external', false)) {
                    $jsObjectName = 'parent.' . $jsObjectName;
                }
            } else {
                $jsObjectName = false;
            }
            $this->setData('editor_js_object_name', $jsObjectName);
        }
        return $this->_getData('editor_js_object_name');
    }
}
