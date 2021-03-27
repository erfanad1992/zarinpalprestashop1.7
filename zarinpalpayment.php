<?php

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}
class Zarinpalpayment extends PaymentModule
{

    private $_html = '';
    private $_postErrors = array();

    public function __construct()
    {

        $this->name = 'zarinpalpayment';
        $this->tab = 'payments_gateways';
        $this->version = '3.0';
        $this->author = 'www.zarinpal.com';
        $this->controllers = array('payment', 'validation');
        $this->currencies = true;
        $this->currencies_mode = 'radio';

        parent::__construct();

        $this->page = basename(__FILE__, '.php');
        $this->displayName = $this->l('درگاه شرکت زرین پال');
        $this->description = $this->l('ساخته شده توسط زرین پال');
        $this->confirmUninstall = $this->l('شما از حذف این ماژول مطمئن هستید ؟');

        if (!sizeof(Currency::checkPaymentCurrencies($this->id)))
            $this->warning = $this->l('ارز تنظیم نشده است');

        $config = Configuration::getMultiple(array('ZARINPAL_PIN', ''));
        if (!isset($config['ZARINPAL_PIN']))
            $this->warning = $this->l('شما باید شناسه درگاه خود را تنظیم کرده باشید');

        if ($_SERVER['SERVER_NAME'] == 'localhost')
        $this->warning = $this->l('این ماژول روی لوکال کار نمیکند');
    }
    public function install()
    {
        return parent::install()
            && $this->registerHook('paymentOptions')
            && $this->registerHook('paymentReturn');
    }
    public function uninstall()
    {
        return Configuration::deleteByName('ZARINPAL_PIN')
            && Configuration::deleteByName('ZARINPAL_HASHKEY')
            && Configuration::deleteByName('sha1Key')
            && parent::uninstall();
    }

    public function displayFormSettings()
    {
        $bank_id = Configuration::get('ZARINPAL_HASHKEY');

        $this->_html .= '
		<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
			<fieldset>
				<legend><img src="../img/admin/cog.gif" alt="" class="middle" />' . $this->l('Settings') . '</legend>
				<label>' . $this->l('مرچنت آیدی') . '</label>
				<div class="margin-form"><input type="text" size="30" name="ZARINPAL_TERMINALID" value="' . Configuration::get('ZARINPAL_TERMINALID') . '" /></div>
			
				</div>
			
				<p class="hint clear" style="display: block; width: 501px;"><a href="http://www.zarinpal.com" target="_blank" >' . $this->l('ساخته شده توسط زرین پال') . '</a></p></div>
				<center><input type="submit" name="submitZARINPAL" value="' . $this->l('به روز رسانی تنظیمات') . '" class="button" /></center>			
			</fieldset>
		</form>';
    }

    public function displayConf()
    {
        $this->_html .= '
		<div class="conf confirm">
			<img src="../img/admin/ok.gif" alt="' . $this->l('Confirmation') . '" />
			' . $this->l('Settings updated') . '
		</div>';
    }

    public function displayErrors()
    {
        foreach ($this->_postErrors as $err)
            $this->_html .= '<div class="alert error">' . $err . '</div>';
    }

    public function getContent()
    {
        $this->_html = '<h2>' . $this->l('zarinpalpayment') . '</h2>';
        if (isset($_POST['submitZARINPAL'])) {
            if (empty($_POST['ZARINPAL_TERMINALID']))
                $this->_postErrors[] = $this->l('مرچنت کد را وارد کنید');

            if (!sizeof($this->_postErrors)) {
                Configuration::updateValue('ZARINPAL_TERMINALID', $_POST['ZARINPAL_TERMINALID']);


                $this->displayConf();
            } else
                $this->displayErrors();
        }

        $this->displayFormSettings();
        return $this->_html;
    }

    private function displayZarinpalPayment()
    {
        $this->_html .= '<img src="../modules/zarinpalpayment/zarinpal.png" style="float:left; margin-right:15px;"><b>' . $this->l('این ماژول امکان واریز آنلاین توسط درگاه زرین پال را مهیا می سازد') . '</b><br /><br />
		' . $this->l('تمامی کارت های عضو شتاب') . '<br /><br /><br />';
    }

    public function execPayment($cart)
    {
        global $cookie, $smarty;

        $purchase_currency = new Currency(Currency::getIdByIsoCode('IRR'));
        $OrderDesc = Configuration::get('PS_SHOP_NAME') . $this->l(' Order');
        $current_currency = new Currency($this->context->cookie->id_currency);
        if ($current_currency->id == $purchase_currency->id)
            $PurchaseAmount = number_format($cart->getOrderTotal(true, 3), 0, '', '');
        else
            $PurchaseAmount = number_format(Tools::convertPrice($cart->getOrderTotal(true, 3), $purchase_currency), 0, '', '');

        $terminal_id = Configuration::get('ZARINPAL_TERMINALID');
        $OrderId = $cart->id;

        $amount = $PurchaseAmount;
        $redirect_url = $this->context->link->getModuleLink($this->name, 'validation', array(), true);
        @session_start();
        $_SESSION['paymentId'] = $OrderId;
        $params['amount'] = $amount;
        $params['merchantId'] = $terminal_id;
        $params['invoiceNo'] = time();
        $params['paymentId'] = time();
        $params['specialPaymentId'] = time();
        $params['revertURL'] = $redirect_url . '?paid=' . $OrderId;
        $params['description'] = "";




        $terminalID = Configuration::get('ZARINPAL_TERMINALID');


        /*	$data = array();
            $data["request"] = array(
                "acceptorId" => $acceptorid,
                "amount" => (int) $amount,
                "billInfo" => null,
                "requestId" => uniqid(),
                "paymentId" => (string) $OrderId,
                "requestTimestamp" => time(),
                "revertUri" => $redirect_url . '?paid=' . $OrderId,
                "terminalId" => $terminalID,
                "transactionType" => "Purchase"
            );*/
        //////////////////////////////////////////////////////////////////////////////////////////
        $orderId = $cart ->id;
        $txt = 'پرداخت سفارش شماره: ' . $cart ->id;
        $mobile='0';
        $email='0';
        $params = array("merchant_id" => $terminalID,
            "amount" => $amount,
            "callback_url" => $redirect_url,
            'description' => $OrderDesc,
            'metadata' => ['mobile' => $mobile,'email' => $email,],
        );

/////////////////////////////////////////////////////////////////////////////////////////////////////
        /* $url = 'https://sandbox.zarinpal.com/pg/services/WebGate/wsdl';
         $client = new SoapClient($url, ['encoding' => 'UTF-8']);

         $response = $client->PaymentRequest(
             [
                 'MerchantID'  => $terminalID,
                 'Amount'      => $amount,
                 'Description' => $OrderDesc,
                 'Email'       => '',
                 'Mobile'      => '',
                 'CallbackURL' => $redirect_url,
             ]
         );
         $response = json_decode($response, true);*/


        //$res = $client->call('PaymentRequest', $params);

        $jsonData = json_encode($params);
        $ch = curl_init('https://api.zarinpal.com/pg/v4/payment/request.json');
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
        $response = json_decode($result, true, JSON_PRETTY_PRINT);
        curl_close($ch);

        return $response;
    }
    public function confirmPayment($order_amount, $Status, $Refnumber)
    {
    }
    public function hookPaymentOptions()
    {
        if (!$this->active) {
            return;
        }

        $this->smarty->assign(
            $this->getTemplateVars()
        );
        $newOption = new PaymentOption();
        $newOption->setCallToActionText($this->trans($this->displayName, array(), 'Modules.Zarinpalpayment.Shop'))
            ->setAction($this->context->link->getModuleLink($this->name, 'payment', array(), true))
            ->setAdditionalInformation($this->fetch('module:zarinpalpayment/payment_info.tpl'));
        $payment_options = array($newOption);

        return $payment_options;
    }

    public function getTemplateVars()
    {
        $cart = $this->context->cart;
        $total = $this->trans(
            '%amount% (tax incl.)',
            array(
                '%amount%' => Tools::displayPrice($cart->getOrderTotal(true, Cart::BOTH)),
            ),
            'Modules.Zarinpalpayment.Admin'
        );

        $checkOrder = Configuration::get('CHEQUE_NAME');
        if (!$checkOrder) {
            $checkOrder = '___________';
        }

        $checkAddress = Tools::nl2br(Configuration::get('CHEQUE_ADDRESS'));
        if (!$checkAddress) {
            $checkAddress = '___________';
        }

        return array(
            'checkTotal' => $total,
            'checkOrder' => $checkOrder,
            'checkAddress' => $checkAddress,
        );
    }
}
