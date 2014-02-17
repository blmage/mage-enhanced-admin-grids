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

class BL_CustomGrid_Model_Grid_Rewriter_File
    extends BL_CustomGrid_Model_Grid_Rewriter_Abstract
{
    protected function _rewriteGrid($blcgClass, $originalClass, $gridType)
    {
        $classParts = explode('_', str_replace($this->_getBlcgClassPrefix(), '', $blcgClass));
        $fileName   = array_pop($classParts) . '.php';
        $rewriteDir = dirname(__FILE__).'/../../../Block/Rewrite/'.implode('/', $classParts);
        
        $ioFile = new Varien_Io_File();
        $ioFile->setAllowCreateFolders(true);
        $ioFile->checkAndCreateFolder($rewriteDir);
        $ioFile->cd($rewriteDir);
        
        if ($ioFile->fileExists($fileName, true)
            && ($content = $ioFile->read($fileName))) {
            $lines = preg_split('#\R#', $content, 3);
            
            if (isset($lines[0])
                && isset($lines[1])
                && ($lines[0] == '<?php')
                && preg_match('#^// BLCG_REWRITE_CODE_VERSION\\=([0-9]+)$#', $lines[1], $matches)) {
                if ($matches[1] === strval(self::REWRITE_CODE_VERSION)) {
                    // File is up-to-date
                    return $this;
                }
            }
        }
        
        $content = '<?php
// BLCG_REWRITE_CODE_VERSION=' . self::REWRITE_CODE_VERSION . '
// This file was generated automatically. Do not alter its content.

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
 * @copyright  Copyright (c) ' . date('Y') . ' Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

';
        
        $content .= $this->_getRewriteCode($blcgClass, $originalClass, $gridType);
        
        if (!$ioFile->write($fileName, $content)) {
            Mage::throwException();
        }
        
        return $this;
    }
}