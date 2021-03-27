<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
class ZarinpalpaymentpaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {

        parent::initContent();
        $cart = $this->context->cart;
        $response = $this->module->execPayment($cart);
        if ($response['errors']) {
            echo $response['errors']['message'];
        } else {
            if ($response['data']['code'] == 100) {

                header('Location: https://www.zarinpal.com/pg/StartPay/' . $response['data']["authority"]);

            } else {
                echo "error occured";
            }



            return $this->setTemplate('module:zarinpalpayment/redirect.tpl');
        }
    }

    private function bankShowStatus($ErrorCode)
    {
        $msgArray = array(
            '-51' => 'تراکنش ناموفق‬',
            '-3' => '‫مبلغ پایینتر از ۱۰۰',
            '-50' => '‫مبلغ تراکنش با مبلغ وریفای متفاوت‬',
            '101' => '‫تراکنش‬ ‫قبلا وریفای شده است',
            '-10' => 'مرچنت صحیح نیست',
            '-11' => '‫مرچنت صحیح نیست',
            '-9' => 'مقادیر ارسالی صحیح نیست',
        );


        if (isset($msgArray[$ErrorCode])) {
            return $msgArray[$ErrorCode];
        }
        return 'در حین پرداخت خطا رخ داده است .' . $ErrorCode;
    }
}
