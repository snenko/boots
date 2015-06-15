<?php

class MageWorx_CustomOptions_Model_Checkout_Cart extends Mage_Checkout_Model_Cart {

    public function updateItems($data) {
        Mage::dispatchEvent('checkout_cart_update_items_before', array('cart' => $this, 'info' => $data));

        /* @var $messageFactory Mage_Core_Model_Message */
        $messageFactory = Mage::getSingleton('core/message');
        $session = $this->getCheckoutSession();
        $qtyRecalculatedFlag = false;
        foreach ($data as $itemId => $itemInfo) {
            $item = $this->getQuote()->getItemById($itemId);
            if (!$item) {
                continue;
            }

            if (!empty($itemInfo['remove']) || (isset($itemInfo['qty']) && $itemInfo['qty'] == '0')) {
                $this->removeItem($itemId);
                continue;
            }

            $qty = isset($itemInfo['qty']) ? (float) $itemInfo['qty'] : false;
            if ($qty > 0) {
                $optionSummaryQty = 100500;

                if (Mage::helper('customoptions')->isInventoryEnabled()) {
                    foreach ($item->getOptions() as $option) {
                        $value = @unserialize($option->getValue());
                        if ($value !== false) {
                            foreach ($value['options'] as $optionId => $valueId) {
                                if (is_array($valueId)) {
                                    foreach ($valueId as $valId) {
                                        $productOption = Mage::getModel('catalog/product_option')->load($optionId);
                                        $row = $productOption->getOptionValue($valId);
                                        if (isset($row['customoptions_qty']) && $row['customoptions_qty'] != '' && $optionSummaryQty > $row['customoptions_qty']) {
                                            $optionSummaryQty = $row['customoptions_qty'];
                                        }
                                    }
                                } else {
                                    $productOption = Mage::getModel('catalog/product_option')->load($optionId);
                                    $row = $productOption->getOptionValue($valueId);
                                    if (isset($row['customoptions_qty']) && $row['customoptions_qty'] != '' && $optionSummaryQty > $row['customoptions_qty']) {
                                        $optionSummaryQty = $row['customoptions_qty'];
                                    }
                                }
                            }
                        }
                    }
                }

                if ($optionSummaryQty < $qty) {
					$session->addError(Mage::helper('cataloginventory')->__('The requested quantity for "%s" is not available.', $item->getProduct()->getName()));
                } else {
                    $item->setQty($qty);

                    if (isset($itemInfo['before_suggest_qty']) && ($itemInfo['before_suggest_qty'] != $qty)) {
                        $qtyRecalculatedFlag = true;
                        $message = $messageFactory->notice(Mage::helper('checkout')->__('Quantity was recalculated from %d to %d', $itemInfo['before_suggest_qty'], $qty));
                        $session->addQuoteItemMessage($item->getId(), $message);
                    }
                }
            }
        }

        if ($qtyRecalculatedFlag) {
            $session->addNotice(
                    Mage::helper('checkout')->__('Some products quantities were recalculated because of quantity increment mismatch')
            );
        }

        Mage::dispatchEvent('checkout_cart_update_items_after', array('cart' => $this, 'info' => $data));
        return $this;
    }

}
