<?php

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class Wayforpay extends PaymentModule {

    private $settingsList = [
        'WAYFORPAY_MERCHANT',
        'WAYFORPAY_SECRET_KEY',
    ];

    public function __construct() {
        $this->name = 'wayforpay';
        $this->tab = 'payments_gateways';
        $this->version = '1.1.0';
        $this->author = 'WayForPay';

        parent::__construct();
        $this->displayName = $this->l('Платежи WayForPay');
        $this->description = $this->l('Оплата через WayForPay');
        $this->confirmUninstall = $this->l('Действительно хотите удалить модуль?');
    }

    public function install() {
        if (!parent::install() or
            !$this->registerHook('payment') or
            !$this->registerHook('displayPayment') or
            !$this->registerHook('displayPaymentTop') or
            !$this->registerHook('paymentOptions')) {
            return FALSE;
        }
        return TRUE;
    }

    public function uninstall() {
        foreach ($this->settingsList as $val) {
            if (!Configuration::deleteByName($val)) {
                return FALSE;
            }
        }
        if (!parent::uninstall()) {
            return FALSE;
        }
        return TRUE;
    }

    public function getOption($name) {
        return trim(Configuration::get("WAYFORPAY_" . strtoupper($name)));
    }

    private function _displayForm() {
        $this->_html .=
            '<form action="' . Tools::htmlentitiesUTF8($_SERVER['REQUEST_URI']) . '" method="post">
			<fieldset>
			<legend><img src="../img/admin/AdminTools.gif" />' . $this->l('Merchant Settings') . '</legend>
				<table border="0" width="500" cellpadding="0" cellspacing="0" id="form">
					<tr><td colspan="2">' . $this->l('Please specify the WayForPay account details for customers') . '.<br /><br /></td></tr>

					<tr>
						<td width="130" style="height: 35px;">' . $this->l('Merchant Account') . '</td>
						<td><input type="text" name="merchant" value="' . $this->getOption("merchant") . '" style="width: 300px;" /></td>
					</tr>
					<tr>
						<td width="130" style="height: 35px;">' . $this->l('Secret key') . '</td>
						<td><input type="text" name="secret_key" value="' . $this->getOption("secret_key") . '" style="width: 300px;" /></td>
					</tr>
					<tr><td colspan="2" align="center"><input class="button" name="btnSubmit" value="' . $this->l('Update settings') . '" type="submit" /></td></tr>
				</table>
			</fieldset>
		</form>';
    }

    private function _displayWayForPay() {
        $this->_html .= '<img src="../modules/wayforpay/img/w4p.png" style="float:left; margin-right:15px;"><b>' .
            $this->l('This module allows you to accept payments by WayForPay.') . '</b><br /><br />' .
            $this->l('If the client chooses this payment mode, the order will change its status into a \'Waiting for payment\' status.') .
            '<br /><br /><br />';
    }

    public function getContent() {
        $this->_html = '<h2>' . $this->displayName . '</h2>';

        if (Tools::isSubmit('btnSubmit')) {
            $this->_postValidation();
            if (!sizeof($this->_postErrors)) {
                $this->_postProcess();
            }
            else {
                foreach ($this->_postErrors as $err) {
                    $this->_html .= '<div class="alert error">' . $err . '</div>';
                }
            }
        }
        else {
            $this->_html .= '<br />';
        }
        $this->_displayWayForPay();
        $this->_displayForm();
        return $this->_html;
    }

    private function _postValidation() {
        if (Tools::isSubmit('btnSubmit')) {
            /*$this->_postErrors[] = $this->l('Account details are required.');*/
        }
    }

    private function _postProcess() {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('WAYFORPAY_MERCHANT', Tools::getValue('merchant'));
            Configuration::updateValue('WAYFORPAY_SECRET_KEY', Tools::getValue('secret_key'));
        }
        $this->_html .= '<div class="conf confirm"><img src="../img/admin/ok.gif" alt="' . $this->l('ok') . '" /> ' . $this->l('Settings updated') . '</div>';
    }

    # Display

    /* Prestashop 1.7.x.x */
    public function hookPaymentOptions($params) {
        if (!$this->active) {
            return;
        }
        if (!$this->_checkCurrency($params['cart'])) {
            return;
        }

        global $smarty;
        $smarty->assign([
            'this_path' => $this->_path,
            'id' => (int) $params['cart']->id,
            'this_path_ssl' => Tools::getShopDomainSsl(TRUE, TRUE) . __PS_BASE_URI__ . 'modules/' . $this->name . '/',
            'this_description' => 'Оплата через систему WayForPay',
        ]);
        $cart_id = Context::getContext()->cart->id;

        $newOption = new PaymentOption();
        $newOption->setModuleName($this->name)
            ->setCallToActionText($this->trans('Wayforpay', [], 'Modules.Wayforpay.Shop'))
            ->setAction($this->context->link->getModuleLink('wayforpay', 'redirect', ['id_cart' => $cart_id]))
            ->setAdditionalInformation($this->fetch('module:wayforpay/wayforpay.tpl'));
        $payment_options = [
            $newOption,
        ];

        return $payment_options;
    }

    private function _checkCurrency($cart) {
        $currency_order = new Currency((int) ($cart->id_currency));
        $currencies_module = $this->getCurrency((int) $cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return TRUE;
                }
            }
        }
        return FALSE;
    }
}
