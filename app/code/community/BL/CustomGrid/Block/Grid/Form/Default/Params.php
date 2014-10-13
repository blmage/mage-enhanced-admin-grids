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

class BL_CustomGrid_Block_Grid_Form_Default_Params extends BL_CustomGrid_Block_Grid_Form_Abstract
{
    public function getFormAction()
    {
        return $this->getUrl('customgrid/grid/saveDefaultParams');
    }
    
    public function getReloadGridAfterSuccess()
    {
        return false;
    }
    
    public function getDefaultParamTypes()
    {
        return $this->getDataSetDefault(
            'default_param_types',
            array(
                'page'   => $this->__('Page Number'),
                'limit'  => $this->__('Page Size'),
                'sort'   => $this->__('Sort'),
                'dir'    => $this->__('Sort Direction'),
                'filter' => $this->__('Filter'),
            )
        );
    }
    
    protected function _isPossiblyEmptyFilterValue($value)
    {
        $isEmpty = false;
        
        if (is_array($value)) {
            if ((isset($value['currency']) || isset($value['locale']))
                && (count($value) == 1)) {
                $isEmpty = true;
            }
        } elseif ($value === '') {
            $isEmpty = true;
        }
        
        return $isEmpty;
    }
    
    protected function _rendererDefaultFilterValue($value, $isAppliable, BL_CustomGrid_Model_Grid $gridModel)
    {
        if ($isAppliable) {
            if (!is_array($value)) {
                $value = $gridModel->decodeGridFiltersString($value);
            }
        }
        if (is_array($value) || is_array($value = @unserialize($value))) {
            $values = array();
            
            foreach ($value as $columnBlockId => $filterValue) {
                if (!$isAppliable && is_array($filterValue) && isset($filterValue['value'])) {
                    $filterValue = $filterValue['value'];
                }
                $header = (($header = $gridModel->getColumnHeader($columnBlockId)) ? $header : $columnBlockId);
                $values[] = $this->__('column "%s"', $header)
                    . ($this->_isPossiblyEmptyFilterValue($filterValue) ? ' ' . $this->__('(possibly empty)') : '');
            }
            
            if (empty($values)) {
                $value = $this->__('None');
            } else {
                $value = '<br />' . implode('<br />', $values);
            }
        }
        return $value;
    }
    
    protected function _renderDefaultParamValue($type, $value, $isAppliable, BL_CustomGrid_Model_Grid $gridModel)
    {
        if (($type == 'page') || ($type == 'limit')) {
            $value = (int) $value;
        } elseif ($type == 'sort') {
            $value = $this->__('column "%s"', (($header = $gridModel->getColumnHeader($value)) ? $header : $value));
        } elseif ($type == 'dir') {
            $value = $this->__(strtolower($type) == 'asc' ? 'ascending' : 'descending');
        } elseif ($type == 'filter') {
            $value = $this->_rendererDefaultFilterValue($value, $isAppliable, $gridModel);
        }
        return $value;
    }
    
    protected function _addRemovableParamsFieldsToForm(
        Varien_Data_Form $form,
        BL_CustomGrid_Block_Widget_Form_Element_Dependence $dependenceBlock,
        BL_CustomGrid_Model_Grid $gridModel
    ) {
        $fieldset = $form->addFieldset(
            'remove',
            array(
                'legend' => $this->__('Remove'),
                'class'  => 'fielset-wide',
            )
        );
        
        $yesNoValues = Mage::getSingleton('customgrid/system_config_source_yesno')->toOptionArray();
        $noDefaultParam = true;
        
        foreach ($this->getDefaultParamTypes() as $typeKey => $typeLabel) {
            if (!is_null($currentValue = $gridModel->getData('default_' . $typeKey))) {
                $noDefaultParam = false;
                $renderedValue = $this->_renderDefaultParamValue($typeKey, $currentValue, false, $gridModel);
                
                $field = $fieldset->addField(
                    'remove_' . $typeKey,
                    'select',
                    array(
                        'name'   => 'removable_default_params[' . $typeKey . ']',
                        'label'  => $typeLabel,
                        'note'   => $this->__('Current Value : <strong>%s</strong>', $renderedValue),
                        'values' => $yesNoValues,
                    )
                );
                
                $dependenceBlock->addFieldMap($field->getHtmlId(), 'remove_' . $typeKey);
                $dependenceBlock->addFieldDependence('remove_' . $typeKey, 'apply_' . $typeKey, '0');
            }
        }
        
        if ($noDefaultParam) {
            $field = $fieldset->addField(
                'remove_no_default_param',
                'note',
                array(
                    'label' => '',
                    'text'  => $this->__('There is no removable default parameter'),
                )
            );
        }
        
        return $this;
    }
    
    protected function _addAppliableParamsFieldsToForm(
        Varien_Data_Form $form,
        BL_CustomGrid_Block_Widget_Form_Element_Dependence $dependenceBlock,
        BL_CustomGrid_Model_Grid $gridModel
    ) {
        $fieldset = $form->addFieldset(
            'apply',
            array(
                'legend' => $this->__('Apply'),
                'class'  => 'fielset-wide',
            )
        );
        
        $yesNoValues = Mage::getSingleton('customgrid/system_config_source_yesno')->toOptionArray();
        $defaultParams = (array) $this->getDataSetDefault('default_params', array());
        
        foreach ($this->getDefaultParamTypes() as $typeKey => $typeLabel) {
            if (isset($defaultParams[$typeKey])) {
                $currentValue  = $gridModel->getData('default_' . $typeKey);
                $renderedValue = $this->_renderDefaultParamValue($typeKey, $defaultParams[$typeKey], true, $gridModel);
                
                $field = $fieldset->addField(
                    'apply_' . $typeKey,
                    'select',
                    array(
                        'name'   => 'appliable_default_params[' . $typeKey . ']',
                        'label'  => $typeLabel,
                        'note'   => $this->__('New Value : <strong>%s</strong>', $renderedValue),
                        'values' => $yesNoValues,
                    )
                );
                
                $fieldset->addField(
                    'apply_' . $typeKey . '_value',
                    'hidden',
                    array(
                        'name'  => 'appliable_values[' . $typeKey . ']',
                        'value' => $defaultParams[$typeKey],
                    )
                );
                
                $dependenceBlock->addFieldMap($field->getHtmlId(), 'apply_' . $typeKey);
                $dependenceBlock->addFieldDependence('apply_' . $typeKey, 'remove_' . $typeKey, '0');
            }
        }
    }
    
    protected function _addFieldsToForm(Varien_Data_Form $form)
    {
        parent::_addFieldsToForm($form);
        $gridModel = $this->getGridModel();
        
        $dependenceBlock = $this->getLayout()
            ->createBlock('customgrid/widget_form_element_dependence')
            ->addConfigOptions(array('chainHidden' => false));
        
        $this->setChild('form_after', $dependenceBlock);
        $this->_addRemovableParamsFieldsToForm($form, $dependenceBlock, $gridModel);
        $this->_addAppliableParamsFieldsToForm($form, $dependenceBlock, $gridModel);
        
        return $this;
    }
}
