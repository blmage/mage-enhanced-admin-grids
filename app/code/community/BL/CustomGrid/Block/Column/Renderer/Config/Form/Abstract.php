<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Widget
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

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
 * @copyright  Copyright (c) 2011 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

abstract class BL_CustomGrid_Block_Column_Renderer_Config_Form_Abstract
    extends Mage_Adminhtml_Block_Widget_Form
{
    protected $_defaultElementType = 'text';
    protected $_translationHelper  = null;
    
    abstract protected function _getFormId();
    abstract public function getRenderer();
    
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        
        $renderer = $this->getRenderer();
        $fieldsetHtmlId = 'options_fieldset' . md5($renderer->getCode());
        $this->setFieldsetHtmlId($fieldsetHtmlId);
        $fieldset = $form->addFieldset($fieldsetHtmlId, array(
            'legend' => $this->__('Configuration')
        ));
        
        $form->setUseContainer(true);
        $form->setId($this->_getFormId());
        $form->setMethod('post');
        $form->setAction($this->getUrl('*/*/buildRenderer'));
        $this->setForm($form);
        
        // Add dependence javascript block
        $block = $this->getLayout()->createBlock('customgrid/widget_form_element_dependence');
        $this->setChild('form_after', $block);
        
        $this->addRendererFields($fieldset);
    }
    
    /**
     * Add fields to given fieldset based on specified renderer type
     *
     * @param Varien_Data_Form_Element_Fieldset $fieldset
     * @return Mage_Adminhtml_Block_Widget_Form
     */
    public function addRendererFields($fieldset)
    {
        $renderer = $this->getRenderer();
        if (!$renderer->getParameters()) {
            return $this;
        }
        $module = $renderer->getModule();
        $this->_translationHelper = Mage::helper($module ? $module : 'customgrid');
        foreach ($renderer->getParameters() as $parameter) {
            $this->_addRendererField($fieldset, $parameter);
        }
        
        return $this;
    }
    
    /**
     * Add field to form based on parameter configuration
     *
     * @param Varien_Data_Form_Element_Fieldset $fieldset
     * @param Varien_Object $parameter
     * @return Varien_Data_Form_Element_Abstract
     */
    protected function _addRendererField($fieldset, $parameter)
    {
        $form = $this->getForm();
        
        // Prepare element data with values (either from request of from default values)
        $fieldName = $parameter->getKey();
        $data = array(
            'name'      => $form->addSuffixToName($fieldName, 'parameters'),
            'label'     => $this->_translationHelper->__($parameter->getLabel()),
            'required'  => $parameter->getRequired(),
            'class'     => 'renderer-option',
            'note'      => $this->_translationHelper->__($parameter->getDescription()),
        );
        
        if ($values = $this->getRendererParams()) {
            $data['value'] = (isset($values[$fieldName]) ? $values[$fieldName] : '');
        } else {
            $data['value'] = $parameter->getValue();
            // Prepare unique ID value
            if (($fieldName == 'unique_id') && ($data['value'] == '')) {
                $data['value'] = md5(microtime(1));
            }
        }
        
        // Prepare element dropdown values
        if ($values  = $parameter->getValues()) {
            // Dropdown options are specified in configuration
            $data['values'] = array();
            foreach ($values as $option) {
                $data['values'][] = array(
                    'label' => $this->_translationHelper->__($option['label']),
                    'value' => $option['value']
                );
            }
        } elseif ($sourceModel = $parameter->getSourceModel()) {
            // Otherwise, a source model is specified
            if (is_array($sourceModel)) {
                // TODO check if invalid model / method ?
                $data['values'] = call_user_func(array(Mage::getModel($sourceModel['model']), $sourceModel['method']));
            } else {
                $data['values'] = Mage::getModel($sourceModel)->toOptionArray();
            }
        }
        
        // Prepare field type or renderer
        $fieldRenderer = null;
        $fieldType = $parameter->getType();
        
        if (!$parameter->getVisible()) {
            // Hidden element
            $fieldType = 'hidden';
        } elseif (false !== strpos($fieldType, '/')) {
            // Just an element renderer
            $fieldRenderer = $this->getLayout()->createBlock($fieldType);
            $fieldType = $this->_defaultElementType;
        }
        
        // Instantiate field and render html
        $field = $fieldset->addField($this->getFieldsetHtmlId().'_'.$fieldName, $fieldType, $data);
        if ($fieldRenderer) {
            $field->setRenderer($fieldRenderer);
        }
        
        // Extra html preparations
        if ($helper = $parameter->getHelperBlock()) {
            $helperBlock = $this->getLayout()->createBlock($helper->getType(), '', $helper->getData());
            if ($helperBlock instanceof Varien_Object) {
                $helperBlock->setConfig($helper->getData())
                    ->setFieldsetId($fieldset->getId())
                    ->setTranslationHelper($this->_translationHelper)
                    ->prepareElementHtml($field);
            }
        }
        
        // Dependencies from other fields
        $dependenceBlock = $this->getChild('form_after');
        $dependenceBlock->addFieldMap($field->getId(), $fieldName);
        if ($parameter->getDepends()) {
            foreach ($parameter->getDepends() as $from => $row) {
                $values = isset($row['values']) ? array_values($row['values']) : (string)$row['value'];
                $dependenceBlock->addFieldDependence($fieldName, $from, $values);
            }
        }
        
        return $field;
    }
}