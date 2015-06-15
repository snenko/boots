<?php

class MageWorx_CustomOptions_Block_Catalog_Product_View_Options_Type_Text extends Mage_Catalog_Block_Product_View_Options_Type_Text {

    public function getOptionImgHtml($option, $groupId = null) {
        return Mage::helper('customoptions')->getOptionImgHtml($option, $groupId);
    }
}