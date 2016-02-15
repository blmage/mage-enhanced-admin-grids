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

class BL_CustomGrid_Model_Grid_Type_Newsletter extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    /**
     * Class codes of the singletonized grid collections, arranged by corresponding grid block type
     * 
     * @var string[]
     */
    static protected $_collectionClassCodes = array(
        'adminhtml/newsletter_subscriber_grid' => 'newsletter/subscriber_collection',
        'adminhtml/newsletter_template_grid'   => 'newsletter/template_collection',
    );
    
    protected function _getSupportedBlockTypes()
    {
        return array(
            'adminhtml/newsletter_problem_grid',
            'adminhtml/newsletter_queue_grid',
            'adminhtml/newsletter_subscriber_grid',
            'adminhtml/newsletter_template_grid',
        );
    }
    
    public function beforeGridPrepareCollection(Mage_Adminhtml_Block_Widget_Grid $gridBlock, $firstTime = true)
    {
        $blockType = $gridBlock->getType();
        
        if (!$firstTime && isset(self::$_collectionClassCodes[$blockType])) {
            $this->_getBaseHelper()->unregisterResourceSingleton(self::$_collectionClassCodes[$blockType]);
        }
        
        return $this;
    }
}
