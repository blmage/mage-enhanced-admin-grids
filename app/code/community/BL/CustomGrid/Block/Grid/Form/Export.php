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

class BL_CustomGrid_Block_Grid_Form_Export extends BL_CustomGrid_Block_Grid_Form_Abstract
{
    protected function _getFormFieldNameSuffix()
    {
        return 'export';
    }
    
    public function getUseFieldValueForUrl()
    {
        return $this->getFormatFieldHtmlId();
    }
    
    public function getUseAjaxSubmit()
    {
        return false;
    }
    
    /**
     * Return available export types
     * 
     * @return BL_CustomGrid_Object[]
     */
    protected function _getExportTypes()
    {
        return $this->getGridModel()
            ->getExporter()
            ->getExportTypes();
    }
    
    /**
     * Return available export types as an option hash (url => label)
     * 
     * @return array
     */
    protected function _getExportTypesHash()
    {
        $exportTypes = array();
        
        foreach ($this->_getExportTypes() as $exportType) {
            $exportTypes[$exportType->getUrl()] = $exportType->getLabel();
        }
        
        return $exportTypes;
    }
    
    /**
     * Return available export sizes as an option hash
     * 
     * @return array
     */
    protected function _getExportSizesHash()
    {
        $exportSizes = array();
        
        if ($totalSize = $this->_getData('total_size')) {
            $exportSizes[$totalSize] = $this->__('Total (%s)', $totalSize);
        }
        
        foreach ($this->getGridModel()->getAppliablePaginationValues() as $paginationValue) {
            $exportSizes[$paginationValue] = $paginationValue;
        }
        
        $exportSizes['_other_'] = $this->__('Other');
        return $exportSizes;
    }
    
    protected function _addFieldsToForm(Varien_Data_Form $form)
    {
        parent::_addFieldsToForm($form);
        
        $fieldset = $form->addFieldset(
            'configuration',
            array(
                'legend' => $this->__('Configuration'),
                'class'  => 'fielset-wide',
            )
        );
        
        $formatField = $fieldset->addField(
            'format',
            'select',
            array(
                'name'     => 'format',
                'label'    => $this->__('Format'),
                'required' => true,
                'values'   => $this->_getExportTypesHash(),
            )
        );
        
        $this->setFormatFieldHtmlId($formatField->getHtmlId());
        
        $exportSizes = $this->_getExportSizesHash();
        reset($exportSizes);
        $defaultExportSize = key($exportSizes);
        
        $sizeField = $fieldset->addField(
            'size',
            'select',
            array(
                'name'     => 'size',
                'label'    => $this->__('Size'),
                'required' => true,
                'values'   => $exportSizes,
                'value'    => $defaultExportSize,
            )
        );
        
        $customSizeField = $fieldset->addField(
            'custom_size',
            'text',
            array(
                'name'     => 'custom_size',
                'label'    => $this->__('Custom Size'),
                'required' => true,
                'class'    => 'validate-greater-than-zero',
            )
        );
        
        $fieldset->addField(
            'from_result',
            'text',
            array(
                'name'     => 'from_result',
                'label'    => $this->__('From Result'),
                'required' => true,
                'class'    => 'validate-greater-than-zero',
                'value'    => (int) $this->getDataSetDefault('first_index', 1),
            )
        );
        
        if (is_array($additionalParams = $this->_getData('additional_params'))) {
            foreach ($additionalParams as $key => $value) {
                $fieldset->addField(
                    $key,
                    'hidden',
                    array(
                        'name'  => $form->addSuffixToName($key, 'additional_params'),
                        'value' => $value,
                    )
                );
            }
        }
        
        $this->getDependenceBlock()
            ->addFieldMap($sizeField->getHtmlId(), 'size')
            ->addFieldMap($customSizeField->getHtmlId(), 'custom_size')
            ->addFieldDependence('custom_size', 'size', '_other_');
        
        return $this;
    }
}
