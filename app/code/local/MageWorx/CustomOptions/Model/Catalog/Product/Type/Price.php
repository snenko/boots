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
class MageWorx_CustomOptions_Model_Catalog_Product_Type_Price extends Mage_Catalog_Model_Product_Type_Price {

    /**
     * Apply options price
     *
     * @param Mage_Catalog_Model_Product $product
     * @param int $qty
     * @param double $finalPrice
     * @return double
     */
    protected function _applyOptionsPrice($product, $qty, $finalPrice) {
        if ($optionIds = $product->getCustomOption('option_ids')) {
            $basePrice = $finalPrice;
            foreach (explode(',', $optionIds->getValue()) as $optionId) {
                if ($option = $product->getOptionById($optionId)) {

                    $quoteItemOption = $product->getCustomOption('option_' . $option->getId());
                    $group = $option->groupFactory($option->getType())
                                    ->setOption($option)
                                    ->setQuoteItemOption($quoteItemOption);

                    switch ($option->getType()) {
                        case 'field':
                        case 'area':
                        case 'file':
                        case 'date':
                        case 'date_time':
                        case 'time':
                            $finalPrice += $this->_getCustomOptionsChargableOptionPrice($option->getPrice(), $option->getPriceType() == 'percent', $basePrice, $qty, $option->getCustomoptionsIsOnetime());
                            break;
                        default:
                            $finalPrice += $group->getOptionPrice($quoteItemOption->getValue(), $basePrice, $qty);
                    }
                }
            }
        }

        return $finalPrice;
    }

    protected function _getCustomOptionsChargableOptionPrice($price, $isPercent, $basePrice, $qty = 1, $customoptionsIsOnetime = 0) {
        $sub = 1;
        if ($customoptionsIsOnetime) {
            $sub = $qty;
        }
        if ($isPercent) {
            return ($basePrice * $price / 100) / $sub;
        } else {
            return $price / $sub;
        }
    }

}