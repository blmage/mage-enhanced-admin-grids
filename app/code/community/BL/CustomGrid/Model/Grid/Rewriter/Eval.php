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

class BL_CustomGrid_Model_Grid_Rewriter_Eval
    extends BL_CustomGrid_Model_Grid_Rewriter_Abstract
{
    protected function _isEvalDisabled()
    {
        if (extension_loaded('suhosin')) {
            // This does not check suhosin.executor.eval.whitelist or blacklist
            return (@ini_get('suhosin.executor.disable_eval') == '1');
        }
        return false;
    }
    
    protected function _rewriteGrid($blcgClass, $originalClass, $gridType)
    {
        if (!$this->_isEvalDisabled()) {
            try {
                eval($this->_getRewriteCode($blcgClass, $originalClass, $gridType));
            } catch (Exception $e) {
                Mage::throwException(Mage::helper('customgrid')->__('An error occured while eval()ing the rewrite code : "%s"', $e->getMessage()));
            }
        } else {
            Mage::throwException(Mage::helper('customgrid')->__('eval() is not available on your server'));
        }
        return $this;
    }
}