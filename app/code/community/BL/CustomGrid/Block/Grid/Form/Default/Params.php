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
    
    /**
     * Return the header of the grid column corresponding to the given block ID
     * 
     * @param string $columnBlockId Grid column block ID
     * @return string
     */
    protected function _getGridColumnHeader($columnBlockId)
    {
        return (($header = $this->getGridModel()->getColumnHeader($columnBlockId)) ? $header : $columnBlockId);
    }
    
    /**
     * Return whether the given filter value possibly corresponds to an empty filter
     * 
     * @param mixed $value Filter value
     * @return bool
     */
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
    
    /**
     * Render the given sub value from a grid default filter, applied on the grid column corresponding to
     * the given block ID
     * 
     * @param string $columnBlockId Grid column block ID
     * @param mixed $filterValue Default filter value
     * @param bool $isAppliable Whether the sub value is rendered in the appliable params fieldset (otherwise removable)
     * @return string
     */
    protected function _renderDefaultFilterSubValue($columnBlockId, $filterValue, $isAppliable)
    {
        if (!$isAppliable && is_array($filterValue) && isset($filterValue['value'])) {
            $filterValue = $filterValue['value'];
        }
        
        $value  = $this->__('column "%s"', $this->_getGridColumnHeader($columnBlockId));
        
        if ($this->_isPossiblyEmptyFilterValue($filterValue)) {
            $value .= ' ' . $this->__('(possibly empty)');
        }
        
        return $value;
    }
    
    /**
     * Render the given default filter value
     * 
     * @param array|string $value Default filter value (array or encoded string)
     * @param mixed $isAppliable Whether the value is rendered in the appliable params fieldset (otherwise removable)
     * @return string
     */
    protected function _renderDefaultFilterValue($value, $isAppliable)
    {
        if ($isAppliable && !is_array($value)) {
            $value = $this->getGridModel()->getApplier()->decodeGridFiltersString($value);
        }
        
        if (is_array($value) || is_array($value = @unserialize($value))) {
            $values = array();
            
            foreach ($value as $columnBlockId => $filterValue) {
                $values[] = $this->_renderDefaultFilterSubValue($columnBlockId, $filterValue, $isAppliable);
            }
            
            if (empty($values)) {
                $value = $this->__('None');
            } else {
                $value = '<br />' . implode('<br />', $values);
            }
        }
        
        return $value;
    }
    
    /**
     * Render the given default param value
     * 
     * @param mixed $value Default param value
     * @param mixed $isAppliable Whether the value is rendered in the appliable params fieldset (otherwise removable)
     * @return string
     */
    protected function _renderDefaultParamValue($type, $value, $isAppliable)
    {
        if (($type == BL_CustomGrid_Model_Grid::GRID_PARAM_PAGE)
            || ($type == BL_CustomGrid_Model_Grid::GRID_PARAM_LIMIT)) {
            $value = (int) $value;
        } elseif ($type == BL_CustomGrid_Model_Grid::GRID_PARAM_SORT) {
            $value = $this->__('column "%s"', $this->_getGridColumnHeader($value));
        } elseif ($type == BL_CustomGrid_Model_Grid::GRID_PARAM_DIR) {
            $value = $this->__(strtolower($type) == 'asc' ? 'ascending' : 'descending');
        } elseif ($type == BL_CustomGrid_Model_Grid::GRID_PARAM_FILTER) {
            $value = $this->_renderDefaultFilterValue($value, $isAppliable);
        }
        return $value;
    }
    
    /**
     * Add removable params-related fields to the given form
     * 
     * @param Varien_Data_Form $form Form
     * @return BL_CustomGrid_Block_Grid_Form_Default_Params
     */
    protected function _addRemovableParamsFieldsToForm(Varien_Data_Form $form)
    {
        $gridModel   = $this->getGridModel();
        $gridProfile = $gridModel->getProfile();
        $yesNoValues = $this->_getYesNoOptionArray();
        $dependenceBlock   = $this->getDependenceBlock();
        $hasNoDefaultParam = true;
        
        $fieldset = $form->addFieldset(
            'remove',
            array(
                'legend' => $this->__('Remove'),
                'class'  => 'fielset-wide',
            )
        );
        
        foreach ($this->_getGridParamsOptionArray(false) as $gridParam) {
            if (!is_null($currentValue = $gridProfile->getData('default_' . $gridParam['value']))) {
                $hasNoDefaultParam = false;
                $renderedValue = $this->_renderDefaultParamValue($gridParam['value'], $currentValue, false);
                
                $field = $fieldset->addField(
                    'remove_' . $gridParam['value'],
                    'select',
                    array(
                        'name'   => $gridParam['value'],
                        'label'  => $gridParam['label'],
                        'note'   => $this->__('Current Value : <strong>%s</strong>', $renderedValue),
                        'values' => $yesNoValues,
                    )
                );
                
                $dependenceBlock->addFieldMap($field->getHtmlId(), 'remove_' . $gridParam['value'])
                    ->addFieldDependence('remove_' . $gridParam['value'], 'apply_' . $gridParam['value'], '0');
            }
        }
        
        if ($hasNoDefaultParam) {
            $fieldset->addField(
                'remove_no_default_param',
                'note',
                array(
                    'label' => '',
                    'text'  => $this->__('There is no removable default parameter'),
                )
            );
        }
        
        $this->_addSuffixToFieldsetFieldNames($fieldset, 'removable_default_params');
        return $this;
    }
    
    /**
     * Add appliable params-related fields to the given form
     * 
     * @param Varien_Data_Form $form Form
     * @return BL_CustomGrid_Block_Grid_Form_Default_Params
     */
    protected function _addAppliableParamsFieldsToForm(Varien_Data_Form $form)
    {
        $yesNoValues = $this->_getYesNoOptionArray();
        $dependenceBlock = $this->getDependenceBlock();
        $defaultParams   = (array) $this->getDataSetDefault('default_params', array());
        
        $fieldset = $form->addFieldset(
            'apply',
            array(
                'legend' => $this->__('Apply'),
                'class'  => 'fielset-wide',
            )
        );
        
        foreach ($this->_getGridParamsOptionArray(false) as $gridParam) {
            if (isset($defaultParams[$gridParam['value']])) {
                $renderedValue = $this->_renderDefaultParamValue(
                    $gridParam['value'],
                    $defaultParams[$gridParam['value']],
                    true
                );
                
                $field = $fieldset->addField(
                    'apply_' . $gridParam['value'],
                    'select',
                    array(
                        'name'   => $form->addSuffixToName($gridParam['value'], 'appliable_default_params'),
                        'label'  => $gridParam['label'],
                        'note'   => $this->__('New Value : <strong>%s</strong>', $renderedValue),
                        'values' => $yesNoValues,
                    )
                );
                
                $fieldset->addField(
                    'apply_' . $gridParam['value'] . '_value',
                    'hidden',
                    array(
                        'name'  => $form->addSuffixToName($gridParam['value'], 'appliable_values'),
                        'value' => $defaultParams[$gridParam['value']],
                    )
                );
                
                $dependenceBlock->addFieldMap($field->getHtmlId(), 'apply_' . $gridParam['value'])
                    ->addFieldDependence('apply_' . $gridParam['value'], 'remove_' . $gridParam['value'], '0');
            }
        }
        
        return $this;
    }
    
    protected function _addFieldsToForm(Varien_Data_Form $form)
    {
        parent::_addFieldsToForm($form);
        $this->_addRemovableParamsFieldsToForm($form);
        $this->_addAppliableParamsFieldsToForm($form);
        $this->getDependenceBlock()->addConfigOptions(array('chainHidden' => false));
        return $this;
    }
}
