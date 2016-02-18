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
 * @copyright  Copyright (c) 2016 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Helper_Worker extends Mage_Core_Helper_Abstract
{
    /**
     * Worker configs by holding model class
     * 
     * @var array
     */
    protected $_workerConfigs = array(
        'BL_CustomGrid_Model_Grid' => array(
            'base_class'   => 'BL_CustomGrid_Model_Grid_Worker_Abstract',
            'base_default_class_code' => 'customgrid/grid_',
            'holder_class' => 'BL_CustomGrid_Model_Grid',
            'holder_key'   => 'grid_model',
        ),
        'BL_CustomGrid_Model_Grid_Editor_Abstract' => array(
            'base_class'   => 'BL_CustomGrid_Model_Grid_Editor_Worker_Abstract',
            'base_default_class_code' => 'customgrid/grid_editor_',
            'holder_class' => 'BL_CustomGrid_Model_Grid_Editor_Abstract',
            'holder_key'   => 'editor',
        ),
        'BL_CustomGrid_Model_Custom_Column_Abstract' => array(
            'base_class'   => 'BL_CustomGrid_Model_Custom_Column_Worker_Abstract',
            'base_default_class_code' => 'customgrid/custom_column_',
            'holder_class' => 'BL_CustomGrid_Model_Custom_Column_Abstract',
            'holder_key'   => 'custom_column',
        ),
    );
    
    /**
     * Return the worker config used by the workers of the given model
     * 
     * @param Varien_Object $model
     * @return array
     * @throws Mage_Core_Exception
     */
    protected function _getModelWorkerConfig(Varien_Object $model)
    {
        $modelWorkerConfig = null;
        
        foreach ($this->_workerConfigs as $modelClass => $workerConfig) {
            if ($model instanceof $modelClass) {
                $modelWorkerConfig = $workerConfig;
                break;
            }
        }
        
        if (is_null($modelWorkerConfig)) {
            Mage::throwException('No worker config defined for "' . get_class($model) . '"');
        }
        
        return $modelWorkerConfig;
    }
    
    /**
     * Return the worker of the given type holded by the given model
     * 
     * @param Varien_Object $model Model holding the worker
     * @param string $type Worker type
     * @return BL_CustomGrid_Object
     * @throws Mage_Core_Exception
     */
    public function getModelWorker(Varien_Object $model, $type)
    {
        if (!$model->hasData($type)) {
            $workerConfig = $this->_getModelWorkerConfig($model);
            $classCodeDataKey = 'worker_model_class_code_' . $type;
            
            if (!$model->hasData($classCodeDataKey)) {
                $model->setData($classCodeDataKey, $workerConfig['base_default_class_code'] . $type);
            }
            
            $worker = Mage::getModel($model->getData($classCodeDataKey));
            
            if (!$worker instanceof $workerConfig['base_class']) {
                Mage::throwException(
                    'Invalid worker model: must be an instance of ' . $workerConfig['base_class']
                    . ' ("' . $type . '" for "' . $workerConfig['holder_class'] . '")'
                );
            }
            
            $worker->setDataUsingMethod($workerConfig['holder_key'], $model);
            $model->setData($type, $worker);
        }
        return $model->getData($type);
    }
}
