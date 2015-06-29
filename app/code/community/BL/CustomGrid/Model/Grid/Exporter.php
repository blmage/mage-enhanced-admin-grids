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

class BL_CustomGrid_Model_Grid_Exporter extends BL_CustomGrid_Model_Grid_Worker
{
    /**
     * Return whether the current grid model's results can be exported
     *
     * @return bool
     */
    public function canExport()
    {
        return ($typeModel = $this->getGridModel()->getTypeModel())
            && $typeModel->canExport($this->getGridModel()->getBlockType());
    }
    
    /**
     * Return the available export types for the current grid model
     *
     * @return BL_CustomGrid_Object[]
     */
    public function getExportTypes()
    {
        return ($typeModel = $this->getGridModel()->getTypeModel())
            ? $typeModel->getExportTypes($this->getGridModel()->getBlockType())
            : array();
    }
    
    /**
     * Return the additional parameters that should be included in the export forms
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return array
     */
    public function getAdditionalFormParams(Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        return ($typeModel = $this->getGridModel()->getTypeModel())
            ? $typeModel->getAdditionalExportParams($this->getGridModel()->getBlockType(), $gridBlock)
            : array();
    }
    
    /**
     * If allowed and possible, export current grid's results in given format
     *
     * @param string $format Export format
     * @param array|null $config Export configuration
     * @return string
     */
    protected function _exportTo($format, $config = null)
    {
        $gridModel = $this->getGridModel();
        
        if (!$gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid_Sentry::ACTION_EXPORT_RESULTS)) {
            $gridModel->getSentry()
                ->throwPermissionException(
                    $gridModel->getHelper()->__('You are not allowed to export this grid results')
                );
        }
        if (!$this->canExport($gridModel)) {
            Mage::throwException($gridModel->getHelper()->__('This grid results can not be exported'));
        }
        
        $typeModel = $gridModel->getTypeModel();
        $typeModel->beforeGridExport($format, null);
        /** @var $layout Mage_Core_Model_Layout */
        $layout = Mage::getSingleton('core/layout');
        $gridBlock = $layout->createBlock($gridModel->getBlockType());
        
        if (is_array($config)) {
            $gridBlock->blcg_setExportConfig($config);
        }
        
        $typeModel->beforeGridExport($format, $gridBlock);
        
        switch ($format) {
            case 'csv':
                $exportOutput = $gridBlock->getCsvFile();
                break;
            case 'xml':
                $exportOutput = $gridBlock->getExcelFile();
                break;
            default:
                $exportOutput = '';
                break;
        }
        
        $typeModel->afterGridExport($format, $gridBlock);
        return $exportOutput;
    }
    
    /**
     * Export the current grid model's results in CSV format
     *
     * @param array|null $config Export configuration
     * @return string
     */
    public function exportToCsv($config = null)
    {
        return $this->_exportTo('csv', $config);
    }
    
    /**
     * Export the current grid model's results in XML Excel format
     *
     * @param array|null $config Export configuration
     * @return string
     */
    public function exportToExcel($config = null)
    {
        return $this->_exportTo('xml', $config);
    }
    
    /**
     * Return whether the given request corresponds to an export request for the current grid model
     *
     * @param Mage_Core_Controller_Request_Http $request Request object
     * @return bool
     */
    public function isExportRequest(Mage_Core_Controller_Request_Http $request)
    {
        return ($typeModel = $this->getGridModel()->getTypeModel())
            && $typeModel->isExportRequest($this->getGridModel()->getBlockType(), $request);
    }
}
