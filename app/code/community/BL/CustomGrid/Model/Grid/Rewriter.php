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

class BL_CustomGrid_Model_Grid_Rewriter
    extends Varien_Object
{
    protected $_rewriters = null;
    protected $_enabledRewriters = null;
    
    protected function _getRewriter($code, $config)
    {
        if (!isset($this->_rewriters[$code])) {
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
                ->setPriority(isset($config['priority']) ? intval($config['priority']) : 0)
                ->setDisplayErrors(isset($config['display_errors']) ? (bool) $config['display_errors'] : false)
                ->setLogErrors(isset($config['log_errors']) ? (bool) $config['log_errors'] : false);
            
            if ($rewriter->getDisplayErrors()) {
                $rewriter->setDisplayErrorsIfSuccess(isset($config['display_errors_if_success']) ? (bool) $config['display_errors_if_success'] : false);
            } else {
                $rewriter->setDisplayErrorsIfSuccess(false);
            }
            if ($rewriter->getLogErrors()) {
                $rewriter->setLogErrorsIfSuccess(isset($config['log_errors_if_success']) ? (bool) $config['log_errors_if_success'] : false);
            } else {
                $rewriter->setLogErrorsIfSuccess(false);
            }
            
            $this->_rewriters[$code] = $rewriter;
        }
        return $this->_rewriters[$code];
    }
    
    protected function _sortRewriters($a, $b)
    {
        return ($a->getPriority() > $b->getPriority() ? 1 : ($a->getPriority() < $b->getPriority() ? -1 : 0));
    }
    
    public function getAllRewriters($sorted=false)
    {
        if (is_null($this->_rewriters)) {
            $this->_rewriters = array();
            $config = Mage::getStoreConfig('customgrid_rewriters');
            
            foreach ($config as $code => $rewriterConfig) {
                $this->_getRewriter($code, $rewriterConfig);
            }
        }
        
        $rewriters = $this->_rewriters;
        
        if ($sorted) {
            uasort($rewriters, array($this, '_sortRewriters'));
        }
        
        return $rewriters;
    }
    
    public function getEnabledRewriters($sorted=false)
    {
        $rewriters = $this->getAllRewriters();
        
        if (is_null($this->_enabledRewriters)) {
            $this->_enabledRewriters = array();
            
            foreach ($rewriters as $code => $rewriter) {
                if (Mage::getStoreConfigFlag('customgrid_rewriters/'.$code.'/enabled')) {
                    $this->_enabledRewriters[$code] = true;
                }
            }
        }
        
        $rewriters = array_intersect_key($rewriters, $this->_enabledRewriters);
        
        if ($sorted) {
            uasort($rewriters, array($this, '_sortRewriters'));
        }
        
        return $rewriters;
    }
    
    public function getRewriterInstance($code)
    {
        $config = Mage::getStoreConfig('customgrid_rewriters/'.$code);
        return (!empty($config) ? $this->_getRewriter($code, $config) : false);
    }
}