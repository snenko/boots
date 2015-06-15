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
 * Custom Options extension
 *
 * @category   MageWorx
 * @package    MageWorx_CustomOptions
 * @author     MageWorx Dev Team <dev@mageworx.com>
 */
class MageWorx_CustomOptions_Helper_Data extends Mage_Core_Helper_Abstract {
    const STATUS_VISIBLE = 1;
    const STATUS_HIDDEN = 2;

    const XML_PATH_CUSTOMOPTIONSINVENTORY_INVENTORY_ENABLED = 'mageworx_sales/customoptionsinventory/inventory_enabled';
    const XML_PATH_CUSTOMOPTIONSINVENTORY_DISPLAY_QTY_FOR_OPTIONS = 'mageworx_sales/customoptionsinventory/display_qty_for_options';
    const XML_PATH_CUSTOMOPTIONSINVENTORY_HIDE_OUT_OF_STOCK_OPTIONS = 'mageworx_sales/customoptionsinventory/hide_out_of_stock_options';
    const XML_PATH_CUSTOMOPTIONS_IMAGES_ABOVE_OPTIONS = 'mageworx_sales/customoptionsinventory/images_above_options';
    const XML_PATH_CUSTOMOPTIONS_ENABLE_CUSTOMER_GROUPS = 'mageworx_sales/customoptionsinventory/enable_customer_groups';

    const DEFAULT_IMG_SIZE = 70;
    const XML_IMG_MAX_WIDTH = 70;
    const XML_IMG_MAX_HEIGHT = 70;

    public function isInventoryEnabled() {
        return Mage::getStoreConfig(self::XML_PATH_CUSTOMOPTIONSINVENTORY_INVENTORY_ENABLED);
    }

    public function canDisplayQtyForOptions() {
        return Mage::getStoreConfig(self::XML_PATH_CUSTOMOPTIONSINVENTORY_DISPLAY_QTY_FOR_OPTIONS);
    }

    public function canHideOutOfStockOptions() {
        return Mage::getStoreConfig(self::XML_PATH_CUSTOMOPTIONSINVENTORY_HIDE_OUT_OF_STOCK_OPTIONS);
    }

    public function isImagesAboveOptions() {        
        return Mage::getStoreConfig(self::XML_PATH_CUSTOMOPTIONS_IMAGES_ABOVE_OPTIONS);
    }
    
    public function isCustomerGroupsEnabled() {
        return Mage::getStoreConfigFlag(self::XML_PATH_CUSTOMOPTIONS_ENABLE_CUSTOMER_GROUPS);  
    }

    public function getOptionStatusArray() {
        return array(
            self::STATUS_VISIBLE => $this->__('Active'),
            self::STATUS_HIDDEN => $this->__('Disabled'),
        );
    }

    public function isEnterprise() {
        $res = false;
        if (version_compare(Mage::getVersion(), '1.4.0', '>=')) {
            $res = true;
        }
        return $res;
    }

    public function getFilter($data) {
        $result = array();
        $filter = new Zend_Filter();
        $filter->addFilter(new Zend_Filter_StringTrim());

        if ($data) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $result[$key] = $this->getFilter($value);
                } else {
                    $result[$key] = $filter->filter($value);
                }
            }
        }
        return $result;
    }

    public function getFiles($path) {
        return @glob($path . "*.*");
    }

    public function isCustomOptionsFile($groupId, $optionId, $valueId = false) {
        return $this->getFiles($this->getCustomOptionsPath($groupId, $optionId, $valueId));
    }

    public function getCustomOptionsPath($groupId, $optionId = false, $valueId = false) {
        return Mage::getBaseDir('media') . DS . 'customoptions' . DS . ($groupId ? $groupId : 'options') . DS . ($optionId ? $optionId . DS : '') . ($valueId ? $valueId . DS : '');
    }

    public function getImageView($groupId, $optionId, $valueId = false, $isRealSize = true) {
        $files = $this->getFiles($this->getCustomOptionsPath($groupId, $optionId, $valueId));
        if ($files) {
            $image = new Varien_Image($files[0]);

            $origHeight = $image->getOriginalHeight();
            $origWidth = $image->getOriginalWidth();
            $width = null;
            $height = null;
            if (Mage::app()->getStore()->isAdmin()) {
                if ($origHeight > $origWidth) {
                    $height = self::DEFAULT_IMG_SIZE;
                } else {
                    $width = self::DEFAULT_IMG_SIZE;
                }
            } else {
                $configWidth = (int) Mage::getStoreConfig(self::XML_IMG_MAX_WIDTH);
                $configHeight = (int) Mage::getStoreConfig(self::XML_IMG_MAX_HEIGHT);
                if (empty($configWidth)) {
                    $configWidth = self::DEFAULT_IMG_SIZE;
                }
                if (empty($configHeight)) {
                    $configHeight = self::DEFAULT_IMG_SIZE;
                }
                if ($origHeight > $origWidth) {
                    $height = $configHeight;
                } else {
                    $width = $configWidth;
                }
            }

            if ($isRealSize === false) {
                $image->resize($width, $height);
            }
            $image->constrainOnly(true);
            $image->keepAspectRatio(true);
            $image->keepFrame(false);
            $image->display();
        }
    }

    public function getValueImgView($option) {
        $block = Mage::app()->getLayout()
                ->createBlock('core/template')
                ->setTemplate('customoptions/option_image.phtml')
                ->addData(array('items' => $option))
                ->toHtml();

        return $block;
    }

    public function deleteValueFile($groupId, $optionId, $valueId = false) {
        $dir = $this->getCustomOptionsPath($groupId, $optionId, $valueId);
        $files = $this->getFiles($dir);
        if ($files) {
            foreach ($files as $value) {
                @unlink($value);
            }
        }
    }

    public function getFileName($filePath) {
        $name = '';
        $name = substr(strrchr($filePath, '/'), 1);
        if (!$name) {
            $name = substr(strrchr($filePath, '\\'), 1);
        }
        return $name;
    }

    public function getValueImgHtml($groupId, $optionId = false, $valueId = false, $hide = false, $optionTypeId = 0, $customOptionId = 0) {
        $file = $this->isCustomOptionsFile($groupId, $optionId, $valueId);
        if ($file) {
            $fileName = $this->getFileName($file[0]);
            $impOption = array(
                'big_img_url' => Mage::getModel('core/url')->getUrl('customoptions/select/getImage', array('big' => 1, 'group_id' => $groupId, 'option_id' => $optionId, 'value_id' => $valueId, 'file' => $fileName)),
                'url' => Mage::getModel('core/url')->getUrl('customoptions/select/getImage', array('group_id' => $groupId, 'option_id' => $optionId, 'value_id' => $valueId, 'file' => $fileName)),
                'value' => $valueId,
                'hide' => $hide,
                'option_type_id' => $optionTypeId,
                'option_id' => $customOptionId
            );
            return $this->getValueImgView(new Varien_Object($impOption));
        }
    }

    public function getOptionImgHtml($option) {
        $path = explode('/', $option->getImagePath());
		if ($option->getImagePath() == '') {
			return false;
		}
        $file = $this->isCustomOptionsFile($path[0], $path[1]);
        if ($file) {
            $fileName = $this->getFileName($file[0]);
            $impOption = array(
                'big_img_url' => Mage::getModel('core/url')->getUrl('customoptions/option/getImage', array('option' => $option->getId(), 'big-image' => true, 'file' => $fileName)),
                'url' => Mage::getModel('core/url')->getUrl('customoptions/option/getImage', array('group_id' => $path[0], 'option' => $option->getId(), 'file' => $fileName)),
                'option' => $option,
                'group_id' => $path[0],
            );
            return $this->getOptionImgView(new Varien_Object($impOption));
        }
    }

    public function getOptionImgView($option) {
        $block = Mage::app()->getLayout()
                ->createBlock('core/template')
                ->setTemplate('customoptions/option_image.phtml')
                ->addData(array('items' => $option))
                ->toHtml();

        return $block;
    }

    public function copyFolder($path, $dest) {
        if (is_dir($path)) {
            @mkdir($dest);
            $objects = scandir($path);
            if (sizeof($objects) > 0) {
                foreach ($objects as $file) {
                    if ($file == "." || $file == "..")
                        continue;
                    // go on
                    if (is_dir($path . DS . $file)) {
                        $this->copyFolder($path . DS . $file, $dest . DS . $file);
                    } else {
                        copy($path . DS . $file, $dest . DS . $file);
                    }
                }
            }
            return true;
        } elseif (is_file($path)) {
            return copy($path, $dest);
        } else {
            return false;
        }
    }

    public function deleteFolder($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . DS . $object) == "dir") {
                        $this->deleteFolder($dir . DS . $object);
                    } else {
                        unlink($dir . DS . $object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }
    
    public function getMaxOptionId() {
        $tablePrefix = (string) Mage::getConfig()->getTablePrefix();
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $select = $connection->select()->from($tablePrefix . 'catalog_product_option', 'MAX(`option_id`)');        
        return intval($connection->fetchOne($select));
    }
    
}
