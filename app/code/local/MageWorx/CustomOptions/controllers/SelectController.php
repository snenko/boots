<?php

/**
 * MageWorx
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MageWorx EULA that is bundled with
 * this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.mageworx.com/LICENSE-1.0.html
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@mageworx.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extension
 * to newer versions in the future. If you wish to customize the extension
 * for your needs please refer to http://www.mageworx.com/ for more information
 * or send an email to sales@mageworx.com
 *
 * @category   MageWorx
 * @package    MageWorx_CustomOptions
 * @copyright  Copyright (c) 2009 MageWorx (http://www.mageworx.com/)
 * @license    http://www.mageworx.com/LICENSE-1.0.html
 */

/**
 * Multi Fees extension
 *
 * @category   MageWorx
 * @package    MageWorx_CustomOptions
 * @author     MageWorx Dev Team <dev@mageworx.com>
 */
class MageWorx_CustomOptions_SelectController extends Mage_Core_Controller_Front_Action {

    public function getImageAction() {
        $valueId = (int) $this->getRequest()->getParam('value');
        $data = $this->getRequest()->getParams();
        $http = new Zend_Controller_Request_Http();
        if ($http->isXmlHttpRequest()) {
            $html = '';
            if (Mage::helper('customoptions')->isCustomOptionsFile($valueId)) {
                $html = Mage::helper('customoptions')->getValueImgHtml($valueId);
            }
            print $html;
        } else {
            return Mage::helper('customoptions')->getImageView(isset($data['group_id']) ? $data['group_id'] : false, $data['option_id'], isset($data['value_id']) ? $data['value_id'] : false, isset($data['big']));
        }
    }

}