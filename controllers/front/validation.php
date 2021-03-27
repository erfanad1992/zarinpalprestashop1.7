<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
@session_start();
class ZarinpalpaymentvalidationModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $cart = $this->context->cart;

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'zarinpalpayment') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->trans('This payment method is not available.', array(), 'Modules.Zarinpalpayment.Shop'));
        }

        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $currency = $this->context->currency;
        $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
        $authority = $_GET['Authority'];
        $orderId = Tools::getValue('paid');
        $merchant = Configuration::get('ZARINPAL_TERMINALID');

        /////////////////////////////////////////////////////////////////////////
        $params = array('merchant_id' => $merchant, 'authority' => $authority, 'amount' => $total);
        $jsonData = json_encode($params);
        $ch = curl_init('https://api.zarinpal.com/pg/v4/payment/verify.json');
        curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v1');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ));
        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        $response = json_decode($result, JSON_OBJECT_AS_ARRAY);
        /*      $url = 'https://sandbox.zarinpal.com/pg/services/WebGate/wsdl';
              $client = new SoapClient($url, ['encoding' => 'UTF-8']);
              $response = $client->PaymentVerification([
                  'MerchantID'     => $merchant,
                  'Authority'      => $authority,
                  'Amount'         => $total,
              ]);*/
        /////////////////////////////////////////////////////////////////////////////////////////////////

        if (!empty($response['data']['code']) and $response['data']['code'] == 100) {
            $ref_id = $response['data']['ref_id'];
            $order = new Order($orderId);
            if ((bool)Context::getContext()->customer->is_guest) {
                $url = Context::getContext()->link->getPageLink('guest-tracking', true);
            } else {
                $url = Context::getContext()->link->getPageLink('history', true);

            }

            $message = $this->bankShowStatus($ref_id);

            $this->context->smarty->assign([
                'message' => $message,
                'redirectUrl' => $url,
                'orderReference' => "{$ref_id}",

            ]);
            $OrderDesc = Configuration::get('PS_SHOP_NAME') . $this->l(' Order');
            $OrderDesc =$OrderDesc . $ref_id;
            $this->module->validateOrder((int)$cart->id, _PS_OS_PAYMENT_, $total, $this->module->displayName, "سفارش تایید شده / کد رهگیری ".$ref_id,array( 'transaction_id'=> $ref_id), (int)$currency->id, false, $customer->secure_key);
            Tools::redirect('index.php?controller=order-confirmation&id_cart=' . (int)$cart->id . '&id_module=' . (int)$this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key);


            return $this->setTemplate('module:zarinpalpayment/back.tpl');
        }
        else {

            if ((bool)Context::getContext()->customer->is_guest) {
                $url = Context::getContext()->link->getPageLink('guest-tracking', true);
            } else {
                $url = Context::getContext()->link->getPageLink('history', true);

            }
            $error = $response['errors']['message'];
            $message = $this->bankShowStatus($error);
            $this->context->smarty->assign([
                'message' => $message,
                'redirectUrl' => $url,
                'orderReference' => $authority,

            ]);

            return $this->setTemplate('module:zarinpalpayment/back.tpl');
        }



    }

    private function bankShowStatus($ErrorCode)
    {

        //return 'تراکنش ناموفق است' . $ErrorCode;
        return "تراکنش ناموفق است";
    }
    public function changeReference($id_order)
    {
        $order = new Order($id_order);
        $reference = $this->getFormattedReference($id_order);
        if (!$reference) {
            return;
        }
        $db = Db::getInstance();
        $db->update('orders', array('reference' => $reference),  'id_order=' . (int)$id_order, $limit = 1);
        return $reference;
    }
}

