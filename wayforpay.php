<?php

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Wayforpay extends PaymentModule
{
    private $settingsList = array(
        'WAYFORPAY_MERCHANT',
        'WAYFORPAY_SECRET_KEY'
    );

    public function __construct()
    {
        $this->name = 'wayforpay';
        $this->tab = 'payments_gateways';
        $this->version = '1.0';
        $this->author = 'WayForPay';
        $this->controllers = array('callback', 'redirect', 'result');
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);

        parent::__construct();
        $this->displayName = $this->l('Платежи WayForPay');
        $this->description = $this->l('Оплата через WayForPay');
        $this->confirmUninstall = $this->l('Действительно хотите удалить модуль?');
    }

    public function install()
    {
        if (!parent::install() || !$this->registerHook('paymentOptions') || !$this->registerHook('paymentReturn')) {
            return false;
        }
        return true;
    }

    public function uninstall()
    {
        foreach ($this->settingsList as $val) {
            if (!Configuration::deleteByName($val)) {
                return false;
            }
        }
        if (!parent::uninstall()) {
            return false;
        }
        return true;
    }

    public function getOption($name)
    {
        return trim(Configuration::get("WAYFORPAY_" . strtoupper($name)));
    }

    private function _displayForm()
    {
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

    private function _displayWayForPay()
    {
        $this->_html .= '<img src="../modules/wayforpay/img/w4p.png" style="float:left; margin-right:15px;"><b>' .
            $this->l('This module allows you to accept payments by WayForPay.') . '</b><br /><br />' .
            $this->l('If the client chooses this payment mode, the order will change its status into a \'Waiting for payment\' status.') .
            '<br /><br /><br />';
    }

    public function getContent()
    {
        $this->_html = '<h2>' . $this->displayName . '</h2>';

        if (Tools::isSubmit('btnSubmit')) {
            $this->_postValidation();
            if (!sizeof($this->_postErrors)) {
                $this->_postProcess();
            } else {
                foreach ($this->_postErrors AS $err) {
                    $this->_html .= '<div class="alert error">' . $err . '</div>';
                }
            }
        } else {
            $this->_html .= '<br />';
        }
        $this->_displayWayForPay();
        $this->_displayForm();
        return $this->_html;
    }


    private function _postValidation()
    {
        if (Tools::isSubmit('btnSubmit')) {
            /*$this->_postErrors[] = $this->l('Account details are required.');*/
        }
    }

    private function _postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('WAYFORPAY_MERCHANT', Tools::getValue('merchant'));
            Configuration::updateValue('WAYFORPAY_SECRET_KEY', Tools::getValue('secret_key'));
        }
       // TODO right path to ok.gif
        $this->_html .= '<div class="conf confirm"><img src="../img/admin/ok.gif" alt="' . $this->l('ok') . '" /> ' . $this->l('Settings updated') . '</div>';
    }

    # Display

//    public function hookPayment($params)
//    {
//        if (!$this->active) return;
//        if (!$this->_checkCurrency($params['cart'])) return;
//
//        global $smarty;
//        $smarty->assign(array(
//            'this_path' => $this->_path,
//            'id' => (int)$params['cart']->id,
//            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/',
//            'this_description' => 'Оплата через систему WayForPay'
//        ));
//
//        return $this->display(__FILE__, 'wayforpay.tpl');
//    }

    public function hookPaymentOptions($params)
    {
//        error_reporting(E_ALL);
//        ini_set('display_errors', 1);

        if (!$this->active)	return;
        if (!$this->_checkCurrency($params['cart'])) return;

        $this->smarty->assign(array(
            'this_path' => $this->_path,
            'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/',
            'this_description' => 'Оплата через систему WayForPay',
            'id' => (int)$params['cart']->id,
        ));

        $newOption = new PaymentOption();
        $newOption->setCallToActionText($this->trans('Оплата через систему WayForPay', array(), 'Modules.WayForPay.Shop'))
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
            ->setAdditionalInformation($this->fetch('module:wayforpay/wayforpay.tpl'));

        $payment_options = [
            $newOption,
        ];

        return $payment_options;
    }

    private function _checkCurrency($cart)
    {
        $currency_order = new Currency((int)($cart->id_currency));
        $currencies_module = $this->getCurrency((int)$cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module AS $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }
}
