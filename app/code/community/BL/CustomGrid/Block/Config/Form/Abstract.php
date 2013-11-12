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
 * @copyright  Copyright (c) 2012 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

abstract class BL_CustomGrid_Block_Config_Form_Abstract
    extends Mage_Adminhtml_Block_Widget_Form
{
    protected $_defaultElementType = 'text';
    protected $_translationHelper  = null;
    
    abstract protected function _getFormId();
    abstract protected function _getFormCode();
    abstract public function addConfigFields($fieldset);
    
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $fieldsetHtmlId = 'options_fieldset' . md5($this->_getFormCode());
        $this->setFieldsetHtmlId($fieldsetHtmlId);
        
        $fieldset = $form->addFieldset($fieldsetHtmlId, array('legend' => $this->__('Configuration')));
        /*
        Use an own renderer for multiselect fields to prevent a bug between
        Prototype JS / Form.serializeElements() (imploding the values) and
        Varien_Data_Form_Element_Multiselect (generating an array parameter),
        leading to obtain an array with a single value containing the expected imploded values
        */
        $fieldset->addType('multiselect', 'BL_CustomGrid_Block_Config_Form_Element_Multiselect');
        
        $form->setUseContainer(true);
        $form->setId($this->_getFormId());
        $form->setMethod('post');
        $form->setAction($this->getUrl('*/*/buildConfig'));
        $this->setForm($form);
        
        // Add dependence javascript block
        $block = $this->getLayout()->createBlock('customgrid/widget_form_element_dependence');
        $this->setChild('form_after', $block);
        
        $this->addConfigFields($fieldset);
    }
    
    protected function _addConfigField($fieldset, $parameter)
    {
        $form = $this->getForm();
        
        // Prepare element data with values (either from request of from default values)
        $fieldName = $parameter->getKey();
        
        $data = array(
            'name'     => $form->addSuffixToName($fieldName, 'parameters'),
            'label'    => $parameter->getLabel(),
            'required' => $parameter->getRequired(),
            'class'    => 'renderer-option',
            'note'     => $parameter->getDescription(),
        );
        
        // Only translate if needed
        if (!is_null($this->_translationHelper)) {
            $data['label'] = $this->_translationHelper->__($data['label']);
            $data['note']  = $this->_translationHelper->__($data['note']);
        }
        
        if ($values = $this->getConfigParams()) {
            $data['value'] = (isset($values[$fieldName]) ? $values[$fieldName] : '');
        } else {
            $data['value'] = $parameter->getValue();
            
            // Prepare unique ID value
            if (($fieldName == 'unique_id') && ($data['value'] == '')) {
                $data['value'] = md5(microtime(true));
            }
        }
        
        // Prepare element dropdown values
        if ($values  = $parameter->getValues()) {
            // Dropdown options are specified in configuration
            $data['values'] = array();
            
            foreach ($values as $option) {
                if (!is_null($this->_translationHelper)) {
                    $option['label'] = $this->_translationHelper->__($option['label']);
                }
                $data['values'][] = array(
                    'label' => $option['label'],
                    'value' => $option['value']
                );
            }
        } elseif ($sourceModel = $parameter->getSourceModel()) {
            // Otherwise, a source model is specified
            if (is_array($sourceModel)) {
                // @todo check if invalid model / method ?
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
        
        // @todo type-specific values whenever needed
        
        // Instantiate field
        $field = $fieldset->addField($this->getFieldsetHtmlId().'_'.$fieldName, $fieldType, $data);
        
        if ($fieldRenderer) {
            $field->setRenderer($fieldRenderer);
        }
        
        // Type-specific values that may be overriden if set before
        if ($fieldType == 'multiselect') {
            if ($size = $parameter->getSize()) {
                $field->setSize($size);
            }
        }
        
        // Extra html preparations
        if ($helper = $parameter->getHelperBlock()) {
            $helperBlock = $this->getLayout()->createBlock($helper->getType(), '', $helper->getData());
            
            if ($helperBlock instanceof Varien_Object) {
                $helperBlock->setConfig($helper->getData())
                    ->setFieldsetId($fieldset->getId())
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