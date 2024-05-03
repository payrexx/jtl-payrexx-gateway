<?php

namespace Plugin\jtl_payrexx\Util;

use JTL\Cart\CartItem;
use JTL\Helpers\Tax;
use JTL\Session\Frontend;

class BasketUtil {

    public static function getBasketDetails($order)
    {   
        $products = $order->Positionen;
        $lineItems = [];
        foreach ($products as $productData) {
            switch ($productData->nPosTyp) {
                case \C_WARENKORBPOS_TYP_VERSANDPOS:
  
                    $currencyFactor = Frontend::getCurrency()->getConversionFactor();
                    $priceDecimal = Tax::getGross(
                        $productData->fPreis * $productData->nAnzahl,
                        CartItem::getTaxRate($productData)
                    );
                    $priceDecimal *= $currencyFactor;
                    $priceDecimal = (float)number_format($priceDecimal, 2, '.', '');

                    $lineItems[] = [
                        'name' => 'shipping',
                        'quantity' => 1,
                        'amount' => $priceDecimal
                    ];
                    break;

                case \C_WARENKORBPOS_TYP_ARTIKEL:
                case \C_WARENKORBPOS_TYP_KUPON:
                case \C_WARENKORBPOS_TYP_GUTSCHEIN:
                case \C_WARENKORBPOS_TYP_ZAHLUNGSART:
                case \C_WARENKORBPOS_TYP_VERSANDZUSCHLAG:
                case \C_WARENKORBPOS_TYP_NEUKUNDENKUPON:
                case \C_WARENKORBPOS_TYP_NACHNAHMEGEBUEHR:
                case \C_WARENKORBPOS_TYP_VERSAND_ARTIKELABHAENGIG:
                case \C_WARENKORBPOS_TYP_VERPACKUNG:
                case \C_WARENKORBPOS_TYP_GRATISGESCHENK:
                default:
                    $isDiscount = false;
                    if (\in_array($productData->nPosTyp, [
                        \C_WARENKORBPOS_TYP_KUPON
                    ], true)) {
                        $isDiscount = true;
                    }
                    $name = \is_array($productData->cName) ? $productData->cName[$_SESSION['cISOSprache']] : $productData->cName;

                    $currencyFactor = Frontend::getCurrency()->getConversionFactor();

                    $includingTax = true;
                    if ($includingTax) {
                        // fPreis is price, nAnzahl is quantity
                        $priceDecimal = Tax::getGross(
                            $productData->fPreis * $productData->nAnzahl,
                            CartItem::getTaxRate($productData)
                        );
                        $priceDecimal *= $currencyFactor;
                        $priceDecimal = (float)number_format($priceDecimal, 2, '.', '');
                    } else {
                        // For customer credit - do not apply taxes
                        $priceDecimal = $productData->fPreis * $productData->nAnzahl;
                        $priceDecimal = (float)number_format($priceDecimal, 2, '.', '');
                    }

                    $type = 'product';
                    if ($isDiscount === true) {
                        $type = 'discount';
                        if ($priceDecimal > 0) {
                            $priceDecimal = -1 * $priceDecimal;
                        }
                    }

                    if ($type === 'product') {
                        $lineItems[] = [
                            'name' => $name,
                            'description' => $productData->cKurzBeschreibung,
                            'quantity' => $productData->nAnzahl,
                            'amount' => $priceDecimal,
                            'sku' => $productData->cArtNr,
                        ];
                    }

                    if ($type === 'discount') {
                        $lineItems[] = [
                            'name' => 'Discount',
                            'quantity' => 1,
                            'amount' => $priceDecimal,
                        ];
                    }
		    }
        }
        return $lineItems;
    }

    public static function getBasketAmount($basket)
    {

    }
}