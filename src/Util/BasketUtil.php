<?php

namespace Plugin\jtl_payrexx\Util;

use JTL\Cart\CartItem;
use JTL\Checkout\Bestellung;
use JTL\Helpers\Tax;
use JTL\Session\Frontend;

class BasketUtil
{

    /**
     * @param Bestellung $order
     * @return array
     */
    public static function getBasketDetails(Bestellung $order): array
    {
        $products = $order->Positionen;
        $basketItems = [];
        $currencyFactor = Frontend::getCurrency()->getConversionFactor();
        foreach ($products as $productData) {
            switch ($productData->nPosTyp) {
                case \C_WARENKORBPOS_TYP_VERSANDPOS:
                    $shippingPrice = Tax::getGross(
                        $productData->fPreis * $productData->nAnzahl,
                        CartItem::getTaxRate($productData)
                    );
                    $shippingPrice *= $currencyFactor;
                    $shippingPrice = number_format($shippingPrice, 2, '.', '');

                    $basketItems[] = [
                        'name' => 'shipping',
                        'quantity' => 1,
                        'amount' => $shippingPrice * 100,
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
                    if (\in_array($productData->nPosTyp, [\C_WARENKORBPOS_TYP_KUPON], true)) {
                        $isDiscount = true;
                    }
                    $name = \is_array($productData->cName)
                        ? $productData->cName[$_SESSION['cISOSprache']]
                        : $productData->cName;

                    $includingTax = true; // To Do: Improve
                    if ($includingTax) {
                        // fPreis is price, nAnzahl is quantity
                        $priceTotal = Tax::getGross(
                            $productData->fPreis,
                            CartItem::getTaxRate($productData)
                        );
                        $priceTotal *= $currencyFactor;
                        $priceTotal = (float)number_format($priceTotal, 2, '.', '');
                    } else {
                        // Do not apply taxes
                        $priceTotal = (float)number_format($productData->fPreis, 2, '.', '');
                    }

                    $type = 'product';
                    if ($isDiscount === true) {
                        $type = 'discount';
                        if ($priceTotal > 0) {
                            $priceTotal = -100 * $priceTotal;
                        }
                    }

                    if ($type === 'product') {
                        $basketItems[] = [
                            'name' => $name,
                            'description' => $productData->Artikel->cKurzBeschreibung,
                            'quantity' => $productData->nAnzahl,
                            'amount' => $priceTotal * 100,
                            'sku' => $productData->cArtNr,
                        ];
                    }

                    if ($type === 'discount') {
                        $basketItems[] = [
                            'name' => 'Discount',
                            'quantity' => 1,
                            'amount' => $priceTotal * 100,
                        ];
                    }
            }
        }
        return $basketItems;
    }

    /**
     * Get Basket Amount
     *
     * @param array $basket
     * @return float
     */
    public static function getBasketAmount(array $basket)
    {
        $basketAmount = 0;

        foreach ($basket as $product) {
            $amount = $product['amount'] / 100;
            $basketAmount += $product['quantity'] * $amount;
        }
        return floatval(number_format($basketAmount, 2, '.', ''));
    }

    /**
     * Create purpose from basket
     *
     * @param array $basket
     * @return string
     */
    public static function createPurposeByBasket(array $basket): string
    {
        $desc = [];
        foreach ($basket as $product) {
            $desc[] = implode(' ', [
                $product['name'],
                $product['quantity'],
                'x',
                number_format($product['amount'] / 100, 2, '.', ''),
            ]);
        }
        return implode('; ', $desc);
    }
}
