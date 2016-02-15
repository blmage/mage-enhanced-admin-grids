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

class BL_CustomGrid_Model_Grid_Rewriter_Config extends BL_CustomGrid_Object
{
    /**
     * Prepare a rewriter model with the given code and config values and return it
     * 
     * @param string $code Rewriter code
     * @param array $config Config values
     * @return BL_CustomGrid_Model_Grid_Rewriter_Abstract|false
     */
    protected function _getRewriter($code, array $config)
    {
        $dataKey = 'rewriters/' . $code;
        
        if (!$this->hasData($dataKey)) {
            if (!isset($config['model'])) {
                return false;
            }
            
            try {
                $rewriter = Mage::getModel($config['model']);
            } catch (Exception $e) {
                Mage::logException($e);
                return false;
            }
            
            $rewriter->setId($code)
                ->addData($config)
                ->setPriority((int) $rewriter->getDataSetDefault('priority', 0))
                ->setLogErrors((bool) $rewriter->getData('log_errors'))
                ->setDisplayErrors((bool) $rewriter->getData('display_errors'))
                ->setLogErrorsIfSuccess($rewriter->getLogErrors() && $rewriter->getData('log_errors_if_success'))
                ->setDisplayErrorsIfSuccess(
                    $rewriter->getDisplayErrors() && $rewriter->getData('display_errors_if_success')
                );
            
            $this->setData($dataKey, $rewriter);
        }
        
        return $this->getData($dataKey);
    }
    
    /**
     * Return the rewriter model corresponding to the given code
     * 
     * @param string $code Rewriter code
     * @return BL_CustomGrid_Model_Grid_Rewriter_Abstract|false
    */
    public function getRewriter($code)
    {
        $rewriterConfig = Mage::getStoreConfig('customgrid_rewriters/' . $code);
        return (is_array($rewriterConfig) ? $this->_getRewriter($code, $rewriterConfig) : false);
    }
    
    /**
     * Rewriters sort callback
     * 
     * @param BL_CustomGrid_Model_Grid_Rewriter_Abstract $rewriterA One rewriter
     * @param BL_CustomGrid_Model_Grid_Rewriter_Abstract $rewriterB Another rewriter
     * @return int
     */
    protected function _sortRewriters(
        BL_CustomGrid_Model_Grid_Rewriter_Abstract $rewriterA,
        BL_CustomGrid_Model_Grid_Rewriter_Abstract $rewriterB
    ) {
        return $rewriterA->compareIntDataTo('position', $rewriterB);
    }
    
    /**
     * Return all the available rewriters, no matter what their state is
     * 
     * @param bool $sorted Whether the rewriters should be sorted
     * @return BL_CustomGrid_Model_Grid_Rewriter_Abstract[]
     */
    public function getAllRewriters($sorted = false)
    {
        if (!$this->hasData('rewriters')) {
            foreach (Mage::getStoreConfig('customgrid_rewriters') as $code => $rewriterConfig) {
                $this->_getRewriter($code, $rewriterConfig);
            }
        }
        
        $rewriters = $this->getDataSetDefault('rewriters', array());
        
        if ($sorted) {
            uasort($rewriters, array($this, '_sortRewriters'));
        }
        
        return $rewriters;
    }
    
    /**
     * Return all the available and enabled rewriters
     * 
     * @param bool $sorted Whether the rewriters should be sorted
     * @return BL_CustomGrid_Model_Grid_Rewriter_Abstract[]
     */
    public function getEnabledRewriters($sorted = false)
    {
        $rewriters = $this->getAllRewriters();
        
        if (!$this->hasData('enabled_rewriters')) {
            $enabledRewriters = array();
            
            foreach ($rewriters as $code => $rewriter) {
                if (Mage::getStoreConfigFlag('customgrid_rewriters/' . $code . '/enabled')) {
                    $enabledRewriters[$code] = true;
                }
            }
            
            $this->setData('enabled_rewriters', $enabledRewriters);
        }
        
        $rewriters = array_intersect_key($rewriters, $this->_getData('enabled_rewriters'));
        
        if ($sorted) {
            uasort($rewriters, array($this, '_sortRewriters'));
        }
        
        return $rewriters;
    }
}
