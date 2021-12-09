<?php

require_once(dirname(__FILE__) . '../../../wayforpay.php');
require_once(dirname(__FILE__) . '../../../wayforpay.cls.php');

class WayforpayResultModuleFrontController extends ModuleFrontController {

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess() {

        $data = $_POST;

        $order_id = !empty($data['orderReference']) ? $data['orderReference'] : NULL;
        $order = new OrderCore(intval($order_id));
        if (!Validate::isLoadedObject($order)) {
            die('Заказ не найден');
        }

        $wayForPayCls = new WayForPayCls();

        $isPaymentValid = $wayForPayCls->isPaymentValid($data);
        if ($isPaymentValid !== TRUE) {
            $this->errors[] = Tools::displayError($isPaymentValid);
        }

        $customer = new CustomerCore($order->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        if (empty($this->errors)) {
            //            list($orderId,) = explode(WayForPayCls::ORDER_SEPARATOR, $data['orderReference']);
            //            $history = new OrderHistory();
            //            $history->id_order = $orderId;
            //            $history->changeIdOrderState((int)Configuration::get('PS_OS_PAYMENT'), $orderId);
            //            $history->addWithemail(true, array(
            //                'order_name' => $orderId
            //            ));
            //
            Tools::redirectLink(__PS_BASE_URI__ . 'order-confirmation?key=' . $customer->secure_key . '&id_cart=' . $order->id_cart . '&id_module=' . $this->module->id . '&id_order=' . $order->id);
        }
        else {
            $this->displayError('Something wrong with payment on WayForPay. Please contact to administrator.');
        }
    }

    /**
     * An error occured and is shown on a new page.
     *
     * @param $message
     *   Message text.
     * @param false $description
     *
     * @throws \PrestaShopException
     */
    protected function displayError($message, $description = FALSE) {
        $this->container = $this->buildContainer();
        /**
         * Create the breadcrumb for your ModuleFrontController.
         */
        $this->context->smarty->assign('path', '
			<a href="' . $this->context->link->getPageLink('order', NULL, NULL, 'step=3') . '">' . $this->module->l('Payment') . '</a>
			<span class="navigation-pipe">&gt;</span>' . $this->module->l('Error'));
        /**
         * Set error message and description for the template.
         */
        array_push($this->errors, $this->module->l($message), $description);

        if (_PS_VERSION_ < '1.7.0.1') {
            return $this->setTemplate('error_old.tpl');
        }
        else {
            return $this->setTemplate('module:wayforpay/views/templates/front/error.tpl');
        }
    }
}
