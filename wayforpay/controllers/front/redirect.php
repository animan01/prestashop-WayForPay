<?php
require_once(dirname(__FILE__) . '../../../wayforpay.php');
require_once(dirname(__FILE__) . '../../../wayforpay.cls.php');

class WayforpayRedirectModuleFrontController extends ModuleFrontController {
    /**
     * @see FrontController::initContent()
     */
    public function initContent() {
        parent::initContent();

        global $cookie, $link;

        $language = Language::getIsoById(intval($cookie->id_lang));
        $language = (!in_array($language, [
            'ua',
            'en',
            'ru',
        ])) ? 'ua' : $language;
        $language = strtoupper($language);

        $cart = $this->context->cart;


        $currency = new CurrencyCore($cart->id_currency);
        $payCurrency = $currency->iso_code;
        $w4p = new Wayforpay();
        $w4pCls = new WayForPayCls();
        $total = $cart->getOrderTotal();


        $option = [];
        $option['wfp_pay_plg'] = 'PrestaShop ' . (defined('_PS_VERSION_') ? _PS_VERSION_ : '');
        $option['merchantAccount'] = $w4p->getOption('merchant');
        $option['orderDate'] = strtotime($cart->date_add);
        $option['merchantAuthType'] = 'simpleSignature';
        $option['merchantDomainName'] = $_SERVER['HTTP_HOST'];
        $option['merchantTransactionSecureType'] = 'AUTO';
        $option['currency'] = $payCurrency;
        $option['amount'] = $total;
        $option['language'] = $language;
        $option['serviceUrl'] = $link->getModuleLink('wayforpay', 'callback');
        $option['returnUrl'] = $link->getModuleLink('wayforpay', 'result');

        $productNames = [];
        $productPrices = [];
        $productQty = [];

        foreach ($cart->getProducts() as $product) {
            $productNames[] = str_replace(["'", '"', '&#39;'], [
                '',
                '',
                '',
            ], htmlspecialchars_decode($product['name']));
            $productPrices[] = $product['total_wt'];
            $productQty[] = $product['quantity'];
        }

        $option['productName'] = $productNames;
        $option['productPrice'] = $productPrices;
        $option['productCount'] = $productQty;

        $address = new AddressCore($cart->id_address_invoice);
        if ($address) {
            $customer = new CustomerCore($address->id_customer);
            /**
             * Check phone
             */
            $phone = str_replace(['+', ' ', '(', ')'], [
                '',
                '',
                '',
                '',
            ], $address->phone_mobile);
            if (strlen($phone) == 10) {
                $phone = '38' . $phone;
            }
            elseif (strlen($phone) == 11) {
                $phone = '3' . $phone;
            }

            $option['clientFirstName'] = $address->firstname;
            $option['clientLastName'] = $address->lastname;
            $option['clientEmail'] = $customer->email;
            $option['clientPhone'] = $phone;
            $option['clientCity'] = $address->city;
            $option['clientAddress'] = $address->address1 . ' ' . $address->address2;
            $option['clientCountry'] = 'UKR';
        }
        $w4p->validateOrder(intval($cart->id), _PS_OS_PREPARATION_, $total, $w4p->displayName);
        $option['orderReference'] = $w4p->currentOrder . WayForPayCls::ORDER_SEPARATOR . time();
        $option['merchantSignature'] = $w4pCls->getRequestSignature($option);

        $url = WayForPayCls::URL;

        $this->context->smarty->assign(['fields' => $option, 'url' => $url]);
        $this->setTemplate('module:wayforpay/views/templates/front/redirect.tpl');
    }
}
