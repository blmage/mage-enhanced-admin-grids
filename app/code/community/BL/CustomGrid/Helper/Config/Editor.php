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
 * @copyright  Copyright (c) 2012 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Helper_Config_Editor
    extends Mage_Core_Helper_Abstract
{
    const XML_CONFIG_SITEMAP_DELETE_FILE = 'customgrid/editor_sitemap/delete_file';
    
    public function getSitemapDeleteFile()
    {
        return Mage::getStoreConfigFlag(self::XML_CONFIG_SITEMAP_DELETE_FILE);
    }
}