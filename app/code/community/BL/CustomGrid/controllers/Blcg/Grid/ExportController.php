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

class BL_CustomGrid_Blcg_Grid_ExportController extends BL_CustomGrid_Controller_Grid_Action
{
    public function formAction()
    {
        $this->_prepareWindowGridFormLayout(
            'export',
            array(
                'total_size'  => $this->getRequest()->getParam('total_size'),
                'first_index' => $this->getRequest()->getParam('first_index'),
                'additional_params' => $this->getRequest()->getParam('additional_params', array()),
            ),
            BL_CustomGrid_Model_Grid_Sentry::ACTION_EXPORT_RESULTS
        );
        $this->renderLayout();
    }
    
    /**
     * Restore in the request the additional parameters from the given export config
     *
     * @param array $exportConfig Export config values
     */
    protected function _restoreExportAdditionalParams(array $exportConfig)
    {
        if (isset($exportConfig['additional_params']) && is_array($exportConfig['additional_params'])) {
            foreach ($exportConfig['additional_params'] as $key => $value) {
                if (!$this->getRequest()->has($key)) {
                    $this->getRequest()->setParam($key, $value);
                }
            }
        }
    }
    
    /**
     * Apply an export action for the given format and file name
     *
     * @param string $format Export format
     * @param string $fileName Exported file name
     */
    protected function _applyExportAction($format, $fileName)
    {
        try {
            $gridModel = $this->_initGridModel();
            $this->_initGridProfile();
            
            if (!is_array($config = $this->getRequest()->getParam('export'))) {
                $config = null;
            }
            
            $this->_restoreExportAdditionalParams($config);
            
            if ($format == 'csv') {
                $exportOutput = $gridModel->getExporter()->exportToCsv($config);
            } elseif ($format == 'xml') {
                $exportOutput = $gridModel->getExporter()->exportToExcel($config);
            } else {
                $exportOutput = '';
            }
            
            $this->_prepareDownloadResponse($fileName, $exportOutput);
            
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            $this->_redirectReferer();
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($this->__('An error occurred while exporting grid results'));
            $this->_redirectReferer();
        }
    }
    
    public function exportCsvAction()
    {
        $this->_applyExportAction('csv', 'export.csv');
    }
    
    public function exportExcelAction()
    {
        $this->_applyExportAction('xml', 'export.xml');
    }
    
    protected function _isAllowed()
    {
        // Specific permissions are enforced by the models
        return true;
    }
}
