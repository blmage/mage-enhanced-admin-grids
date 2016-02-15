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

class BL_CustomGrid_Block_Widget_Form_Container extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        if (!$originalTemplate = $this->getTemplate()) {
            $originalTemplate = 'bl/customgrid/widget/form/container.phtml';
        }
        parent::__construct();
        $this->setTemplate($originalTemplate);
    }
    
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        
        if ($this->_hasButton('saveandcontinue')) {
            if ($this->isTabbedFormContainer() && ($tabsBlock = $this->getFormTabsBlock())) {
                $this->_formScripts[] = '
function saveAndContinueEdit(urlTemplate) {
    var tabsIdValue = ' . $tabsBlock->getJsObjectName() . '.activeTab.id;
    var tabsBlockPrefix = \'' . $tabsBlock->getId() . '_\';
    
    if (tabsIdValue.startsWith(tabsBlockPrefix)) {
        tabsIdValue = tabsIdValue.substr(tabsBlockPrefix.length)
    }
    
    var template = new Template(urlTemplate, /(^|.|\\r|\\n)({{(\w+)}})/);
    var url = template.evaluate({tab_id:tabsIdValue});
    editForm.submit(url);
}
                ';
            } else {
                $this->_formScripts[] = '
function saveAndContinueEdit(url) {
    editForm.submit(url);
}
                ';
            }
        }
        
        return $this;
    }
    
    /**
     * Remove the buttons corresponding to the given IDs
     * 
     * @param array $buttonsIds IDs of the buttons to remove
     * @return BL_CustomGrid_Block_Widget_Form_Container
     */
    protected function _removeButtons(array $buttonsIds)
    {
        foreach ($buttonsIds as $buttonId) {
            $this->_removeButton($buttonId);
        }
        return $this;
    }
    
    /**
     * Return whether a button with the given ID exists
     * 
     * @param mixed $buttonId
     * @return bool
     */
    protected function _hasButton($buttonId)
    {
        $hasFoundButtonId = false;
        
        foreach ($this->_buttons as $level => $buttons) {
            if (isset($buttons[$buttonId])) {
                $hasFoundButtonId = true;
                break;
            }
        }
        
        return $hasFoundButtonId;
    }
    
    /**
     * Return whether this is a container for a tabbed form
     * 
     * @return bool
     */
    public function isTabbedFormContainer()
    {
        return false;
    }
    
    /**
     * Return the form tabs block
     * 
     * @return Mage_Adminhtml_Block_Widget_Tabs|null
     */
    public function getFormTabsBlock()
    {
        return null;
    }
    
    /**
     * Return the URL usable to save the form and be redirected back to it
     * 
     * @return string
     */
    protected function _getSaveAndContinueUrl()
    {
        $params = array(
            'back' => 'edit',
            '_current' => true,
        );
        
        if ($this->isTabbedFormContainer()) {
            $params['active_tab'] = '{{tab_id}}';
        }
        
        return $this->getUrl('*/*/save', $params);
    }
    
    /**
     * Add a "Save and Continue Edit" button to the buttons list
     * 
     * @param string|null $label Button label (null to use default)
     * @param int $position Button position
     * @return BL_CustomGrid_Block_Widget_Form_Container
     */
    protected function _addSaveAndContinueButton($label = null, $position = -100)
    {
        return $this->_addButton(
            'saveandcontinue',
            array(
                'label'   => (is_null($label) ? $this->__('Save and Continue Edit') : $label),
                'onclick' => 'saveAndContinueEdit(\'' . $this->_getSaveAndContinueUrl() . '\')',
                'class'   => 'save',
            ),
            $position
        );
    }
    
    /**
     * Return the HTML ID of the form
     * 
     * @return string
     */
    public function getFormHtmlId()
    {
        return ($dataForm = $this->getChild('form')->getForm())
            ? $dataForm->getId()
            : $this->getChild('form')->getFormId();
    }
    
    /**
     * Return whether the default JS form object should be used
     * 
     * @return bool
     */
    public function getUseDefaultJsFormObject()
    {
        return $this->getDataSetDefault('use_default_js_form_object', true);
    }
    
    /**
     * Return a sanitized JS object name from the given data key, initializing the corresponding value
     * with the current request if needed
     * 
     * @param string $key Data key
     * @param string $requestKey Corresponding parameter key in the current request
     * @return string
     */
    protected function _getJsObjectName($key, $requestKey = null)
    {
        if (!$this->hasData($key)) {
            if (is_null($requestKey)) {
                $requestKey = $key;
            }
            if ($jsObjectName = $this->getRequest()->getParam($requestKey, false)) {
                /** @var $helper BL_CustomGrid_Helper_String */
                $helper = $this->helper('customgrid/string');
                $jsObjectName = $helper->sanitizeJsObjectName($jsObjectName);
            } else {
                $jsObjectName = false;
            }
            $this->setData($key, $jsObjectName);
        }
        return $this->_getData($key);
    }
}
