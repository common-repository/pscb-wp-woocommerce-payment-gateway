<?php
namespace pscb_OOS
{
    /**
    * payment form creation
    */
    class FormCreator
    {

        private $merchant_id;
        private $merchant_key;
        private $work_mode;
        private $send_receipt;
        private $default_tax;
        private $payment_method;
        private $hold;
        private $widget;
        
        private $cms_info_keys = array("name","version","module_version","platform");
		
		private $required_form_fields = array("order_id","customer_id","customer_email","customer_comment","total","success_url","fail_url","cms","lang");
        
        private function map_bool($bool_param){
            $bool_param = "".$bool_param;
            if(empty($bool_param) || $bool_param == "no" || $bool_param == "0" || $bool_param == "нет"){
                return false;
            }else{
                return true;
            }
        }

        /**
        * initializing formCreator
        * 
        * @param mixed $merchant_id     - merchant ID
        * @param mixed $merchant_key    - merchant secret key
        * @param mixed $work_mode       - work mode (1/0 = work/test)
        * @param mixed $send_receipt    - send order receipt with payment (1/0 = send/not send)
		* @param mixed $default_tax		- default tax value to use for products
		* @param mixed $payment_method	- payment method to use
		* @param mixed $hold 			- if we should do a hold
		*
		* @throws GeneralException
        */
        function __construct($merchant_id,$merchant_key,$work_mode,$send_receipt,$default_tax,$shipping_tax,$payment_method,$hold,$widget = null, $payment_method_w = null)
        {
            $rules = new ParameterMapper();

            $this->merchant_id = $merchant_id;
			if(empty($this->merchant_id)){
                \pscb_OOS\Helper::doLog("cannot initialize FormCreator : merchant_id is empty", \pscb_OOS\ERROR);
				throw new GeneralException("cannot initialize FormCreator : merchant_id is empty");
			}
            $this->merchant_key = $merchant_key;
			if(empty($this->merchant_key)){
                \pscb_OOS\Helper::doLog("cannot initialize FormCreator : merchant_key is empty", \pscb_OOS\ERROR);
				throw new GeneralException("cannot initialize FormCreator : merchant_key is empty");
            }
            $this->work_mode = !($rules->mapParam("work_mode",$work_mode));
            $this->send_receipt = $rules->mapParam("send_receipt",$send_receipt);
            $this->default_tax = $default_tax;
            $this->shipping_tax = $shipping_tax;
            $this->payment_method = $payment_method;
            $this->payment_method_w = $payment_method_w;
            $this->hold = $rules->mapParam("hold",$hold);
            $this->widget = $rules->mapParam("widget",$widget);

            \pscb_OOS\Helper::doLog("default tax: " .$this->default_tax, \pscb_OOS\ERROR);
            \pscb_OOS\Helper::doLog("shipping tax: " .$this->shipping_tax, \pscb_OOS\ERROR);
            \pscb_OOS\Helper::doLog("widget: " .$this->widget ."," .$widget, \pscb_OOS\ERROR);
            \pscb_OOS\Helper::doLog("work_mode: " .$this->work_mode ."," .$work_mode, \pscb_OOS\ERROR);
        }
		
        /**
        * validating form parameters
        * 
        * @param mixed $params
        */
        private function validateFormParams($params)
        {
			foreach($this->required_form_fields as $form_field){
				if(!isset($params[$form_field])){
					\pscb_OOS\Helper::doLog("param validation : failed : empty ".$form_field, \pscb_OOS\ERROR);
					throw new GeneralException("cannot create payment form : '".$form_field."' param is empty");
				}
			}
            \pscb_OOS\Helper::doLog("param validation : success", \pscb_OOS\TRACE);
            return true;
        }
        
        public function getItemTax($tax)
        {
            $tax = (string)$tax;
            if(!array_key_exists($tax,\pscb_OOS\Helper::getAllowedTaxValues())){
                $tax = (string)round((double)(preg_replace("/[A-z]/","",$tax)));
                $result = (!array_key_exists($tax,\pscb_OOS\Helper::getAllowedTaxValues()) ? $this->default_tax : $tax );
            }else
                $result = $tax;
            return $result;
        }

        public function getShippingTax($tax)
        {
            $tax = (string)$tax;
            if(!array_key_exists($tax,\pscb_OOS\Helper::getAllowedTaxValues())){
                $tax = (string)round((double)(preg_replace("/[A-z]/","",$tax)));
                $result = (!array_key_exists($tax,\pscb_OOS\Helper::getAllowedTaxValues()) ? $this->shipping_tax : $tax );
            }else
                $result = $tax;
            return $result;
        }
        
        public function getCmsInfoKeys()
        {
            return $this->cms_info_keys;
        }
        
        /**
        * forming html form for sending to payment page
        * params : array (order_id,customer_id,customer_email,customer_comment,total,succ_url,fail_url)
        * orders_data : Order
        * 
        * @param mixed $params
        * @param Order $order_data
        * @param mixed $return_only_data
		* 
		* @throws GeneralException in case of invalid params
        */
        public function createPaymentForm($params,Order $order_data, $return_only_data = false)
        {
            
			\pscb_OOS\Helper::doLog("createPaymentForm enter", \pscb_OOS\TRACE);
            $this->validateFormParams($params);
                
            
            if($order_data->isReady() && empty($params['details']))
            {
                $params['details'] = $order_data->getDetails();
            }
            elseif(empty($params['details']))
            {
                $params['details'] = "";
            }
            if (empty($params["cms"]["version"]))
            {
                $params["cms"]["version"] = "";
            }



            $fdReceipt = $order_data -> getFdReceipt();
            
            $message = array(
                'marketPlace' => $this->merchant_id,
                'nonce' => sha1(time().'_'.$params['order_id']),
                'customerAccount' => $params['customer_id'],
                'customerEmail' => $params['customer_email'],
                'customerPhone' => $params['customer_phone'],
                'customerComment' => $params['customer_comment'],
                'orderId' => $params['order_id'],
                'details' => $params['details'],
                'amount' => $params['total'],
                'paymentMethod' => ($this->widget ? $this->payment_method_w : $this->payment_method),
                'displayLanguage' => $params['lang'],
                'successUrl' => $params['success_url'],
                'failUrl' => $params['fail_url'],
                'data' => array( 'debug' => '1',
                                 'fdReceipt' => $fdReceipt,
                                 'cms' => array("name" =>$params["cms"]["name"],
                                                "releaseCms" => $params["cms"]["version"],
                                                "releaseModule" => $params["cms"]["module_version"],
                                                "platform" => $params["cms"]["platform"]),
                                 'hold' => ($this->hold ? true : false)
                                 )
            );
            if ($this->widget){

                $test_mode = false;
    
                switch (true){
                    case $this->work_mode == "true" && $this->widget == "true":
                        $test_mode = $this->work_mode;
                        break;
                    case $this->work_mode == "false" && $this->widget == "true":
                        $test_mode = !$this->work_mode;
                        break;
                }

                $message['marketPlace'] = $this->merchant_id;
                $message['data']['testMode'] = !$test_mode;
            }
            \pscb_OOS\Helper::doLog("message formed :\n".json_encode($message), \pscb_OOS\ERROR);
            
            //if necessary, generating receipt and adding to message
            if(!empty($this->send_receipt) && $order_data->isReady())
            {
                $receipt_total = $order_data->getTotal();
                if($params['total'] != $receipt_total)
                {
                    $coeff_to_fix_prices = $order_data->getFixPriceCoeffiecient($params['total'],$receipt_total);
                    \pscb_OOS\Helper::doLog("fixing price coefficient formed :".$coeff_to_fix_prices, \pscb_OOS\TRACE);
                    \pscb_OOS\Helper::doLog("Сумма заказа больше, чем сумма по строкам", \pscb_OOS\ERROR);
                    $order_data->fixPriceDifference($coeff_to_fix_prices);
                }
                $message['data'] += $order_data->getFdReceipt();
            }
            
            $messageText = json_encode($message);

            $url = ($this->work_mode ? \pscb_OOS\WORK_PAYURL : \pscb_OOS\TEST_PAYURL);

            $http_params = array(
                'url' => $url,
                'marketPlace' => $this->merchant_id,
                'message' => base64_encode($messageText),
                'signature' => hash('sha256', $messageText . $this->merchant_key),
                'widget' => $this->widget
            );
            
            \pscb_OOS\Helper::doLog("html_params formed :\n".json_encode($http_params), \pscb_OOS\TRACE);
            
            if(!empty($return_only_data))
            {
                $result = $http_params;
            }
            else
            {
                //rendering template
                $html = file_get_contents(__DIR__."/template.payment_form.html");
                preg_match_all("/(?<=\{\\$)http\_params[A-z0-9\'\'\"\"\[\]\_]+(?=\})/si",$html,$html_vars);
                if(!empty($html_vars))
                {
                    $html_vars = array_pop($html_vars);
                    foreach($html_vars as $html_varname)
                    {
                        if(strpos($html_varname,'['))
                        {
                            $element_name = substr($html_varname,strpos($html_varname,'['));
                            $element_name = str_replace(array('[\'','\']','["','"]'),"",$element_name);
                            $array_varname = substr($html_varname,0,strpos($html_varname,'['));
                            $replacer = ${$array_varname}[$element_name];
                        }
                        else
                        {
                            $replacer = ${$html_varname};
                        }
                        $html = str_replace('{$'.$html_varname.'}',$replacer,$html);
                    }
                }
                $result = $html;
            }
            
            return $result;
        }
        
    }
}
