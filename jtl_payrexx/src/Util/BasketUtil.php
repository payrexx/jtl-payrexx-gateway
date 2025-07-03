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
            $vatRate = CartItem::getTaxRate($productData);
            switch ($productData->nPosTyp) {
                case \C_WARENKORBPOS_TYP_VERSANDPOS:
                    $shippingPrice = Tax::getGross(
                        $productData->fPreis * $productData->nAnzahl,
                        CartItem::getTaxRate($productData)
                    );
                    $shippingPrice *= $currencyFactor;
                    $shippingPrice = number_format($shippingPrice, 2, '.', '');

                    $basketItems[] = [
                        'name' => self::getPurposeLangText('Shipping'),
                        'quantity' => 1,
                        'amount' => $shippingPrice * 100,
                        'vatRate' => $vatRate,
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
                            'vatRate' => $vatRate,
                        ];
                    }

                    if ($type === 'discount') {
                        $basketItems[] = [
                            'name' => self::getPurposeLangText('Discount'),
                            'quantity' => 1,
                            'amount' => $priceTotal * 100,
                            'vatRate' => $vatRate,
                        ];
                    }
            }
        }
        if ($order->GuthabenNutzen && $order->fGuthaben && $order->fGuthaben < 0) {
            $gutscheinPrice = number_format(
                $order->Waehrung->getConversionFactor() * $order->fGuthaben, 2, '.', ''
            );
            $basketItems[] = [
                'name' => self::getPurposeLangText('Customer Credit'),
                'quantity' => 1,
                'amount' => $gutscheinPrice * 100,
            ];
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
        $locale = $_SESSION['currentLanguage']->getIso639() ?? 'en';
        $desc = [];
        foreach ($basket as $product) {
            $productName = $product['name'];
            $desc[] = implode(' ', [
                is_array($productName)
                    ? self::getPurposeLangText($productName[2], $locale)
                    : $productName,
                $product['quantity'],
                'x',
                number_format($product['amount'] / 100, 2, '.', ''),
            ]);
        }
        return implode('; ', $desc);
    }

    private static function getPurposeLangText(string $textKey, ?string $locale = null): array|string
    {
        $translation =  [
            'Shipping' => [
                1 => 'Versand',
                2 => 'Shipping',
                3 => 'Livraison',
                4 => 'Spedizione',
            ],
            'Discount' => [
                1 => 'Rabatt',
                2 => 'Discount',
                3 => 'Remise',
                4 => 'Sconto',
            ],
            'Customer Credit' => [
                1 => 'Kundenguthaben',
                2 => 'Customer Credit',
                3 => 'Avoirs des clients',
                4 => 'Saldo del credito del cliente',
                15 => 'Kundenguthaben',
            ],
        ];
        if ($locale === null) {
            return $translation[$textKey] ?? $textKey;
        }

        $map = [
            'de' => 1,
            'en' => 2,
            'fr' => 3,
            'it' => 4,
        ];
        $langKey = $map[$locale] ?? 2;
        return $translation[$textKey][$langKey] ?? $textKey;
    }
}
