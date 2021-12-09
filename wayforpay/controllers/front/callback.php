<?php

require_once(dirname(__FILE__) . '../../../wayforpay.php');
require_once(dirname(__FILE__) . '../../../wayforpay.cls.php');

class WayforpayCallbackModuleFrontController extends ModuleFrontController {

    public $display_column_left = FALSE;

    public $display_column_right = FALSE;

    public $display_header = FALSE;

    public $display_footer = FALSE;

    public $ssl = TRUE;

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess() {
        try {

            $data = json_decode(file_get_contents("php://input"), TRUE);

            $order_id = !empty($data['orderReference']) ? $data['orderReference'] : NULL;
            $order = new OrderCore(intval($order_id));
            if (empty($order)) {
                die('Заказ не найден');
            }

            $wayForPayCls = new WayForPayCls();

            $isPaymentValid = $wayForPayCls->isPaymentValid($data);
            if ($isPaymentValid !== TRUE) {
                exit($isPaymentValid);
            }

            [$orderId,] = explode(WayForPayCls::ORDER_SEPARATOR, $data['orderReference']);
            $history = new OrderHistory();
            $history->id_order = $orderId;
            $history->changeIdOrderState((int) Configuration::get('PS_OS_PAYMENT'), $orderId);
            $history->addWithemail(TRUE, [
                'order_name' => $orderId,
            ]);

            echo $wayForPayCls->getAnswerToGateWay($data);
            exit();
        } catch (Exception $e) {
            exit(get_class($e) . ': ' . $e->getMessage());
        }
    }
}
