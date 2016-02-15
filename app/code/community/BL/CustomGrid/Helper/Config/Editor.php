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

class BL_CustomGrid_Helper_Config_Editor extends Mage_Core_Helper_Abstract
{
    /**
     * Configuration paths
     */
    
    // Google Sitemap
    const CONFIG_PATH_SITEMAP_DELETE_FILE = 'customgrid_editors/sitemap/delete_file';
    
    /**
     * Getter for the config value "Google Sitemap" > "Delete File Before Save"
     *
     * @return bool
     */
    public function getSitemapDeleteFile()
    {
        return Mage::getStoreConfigFlag(self::CONFIG_PATH_SITEMAP_DELETE_FILE);
    }
}
