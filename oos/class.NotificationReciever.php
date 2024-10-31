<?php
namespace pscb_OOS
{
    class NotificationReciever
    {
        
        private $merchant_id;
        private $merchant_key;
		private $status_pending;
		private $status_success;
		private $status_canceled;
		private $pm_id;
		private $accept_all_payments = "";
		
		//status_pending allowed to be NULL for compatiblity with some `special` CMS
		private $required_params = array("merchant_id","merchant_key","status_success","status_canceled","pm_id","accept_all_payments");
		
        private $result_response = array();
        //
        
        /**
        * creating reciever for payment notifications
        * 
        * @param mixed merchant_id           -   merchant id in OOS
        * @param mixed merchant_key          -   merchant secret key
        * @param mixed status_pending        -   status while pending
        * @param mixed status_success        -   status after payment
        * @param mixed status_canceled       -   status after failed payment
        * @param mixed pm_id                 -   payment method id in CMS
        * @param mixed accept_all_payments   -   flag to accept all payments
		*
		* @throws GeneralException
        */
        function __construct($merchant_id,$merchant_key, $status_pending, $status_success, $status_canceled, $pm_id, $accept_all_payments)
        {
            $rules = new ParameterMapper();
			
            $this->merchant_id = $merchant_id;
            $this->merchant_key = $merchant_key;
            $this->status_pending = $status_pending;
			$this->status_success = $status_success;
			$this->status_canceled = $status_canceled;
			$this->pm_id = $pm_id;
			$this->accept_all_payments = $rules->mapParam("accept_all_payments",$accept_all_payments);
            
			foreach($this->required_params as $required_param)
			{
				if(!isset($this->$required_param))
				{
					throw new GeneralException("cannot create NotificationReciever : ".$required_param." is missing");
				}
			}
        }
        
        /**
        * checking if request IP is from strict range
        * IT IS DEPRECATED, LOGICS REMOVED
        */
        public static function is_ip_acceptable()
        {
            return true;
        }
        
        /**
        * decrypting accepted request
        * 
        * @param mixed $encrypted
        * @param mixed $arbitrary_key
        */
        private function decrypt_aes128_ecb_pkcs5($encrypted, $arbitrary_key)
        {
            $hashed_key = hash("md5", $arbitrary_key, true);
            Helper::doLog("accepted base64-encoded input : ".base64_encode($encrypted),\pscb_OOS\TRACE);
            if(is_callable("openssl_decrypt"))
            {
                Helper::doLog("using openssl_decrypt",\pscb_OOS\TRACE);
                $decrypted = openssl_decrypt($encrypted, "AES-128-ECB", hash("md5", $arbitrary_key, true),OPENSSL_RAW_DATA);
                Helper::doLog("decrypted: ".$decrypted,\pscb_OOS\TRACE);
            }
            elseif(is_callable("mcrypt_decrypt"))
            {
                Helper::doLog("using mcrypt_decrypt",\pscb_OOS\TRACE);
                $decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $hashed_key, $encrypted, MCRYPT_MODE_ECB);
                $padSize = ord(substr($decrypted, -1));
                $decrypted = substr($decrypted, 0, $padSize*-1);
            }
            else
            {
                throw new GeneralException("cannot decrypt request: no decryption method available");
            }
            
            return $decrypted;
        }
        
        /**
        * converting number(price) to acceptable format
        * 
        * @param mixed $sum
        */
        private function to_float($sum)
        {
            if (strpos($sum, "."))
            {
                $sum=round($sum,2);
            }
            else 
            {
                $sum=$sum.".0";
            }
            return $sum;
        }
        /**
        * orderRecord : order_amount,order_currency,pay_for,state
        * 
        * @param mixed $orderRecord
        */
        public function getOrderIdFromRecord(array $orderRecord)
        {
            return $orderRecord["pay_for"];
        }
        
        
        /**
        * processing orderRecord while iterating accepted notifications
        * orderInfo : total,status
        * orderRecord : order_amount,order_currency,pay_for,state
        * 
        * @param mixed $orderRecord
        * @param mixed $orderInfo
        */
        public function processOrderRecord(array $orderRecord,array $orderInfo)
        {
            $order_amount = $orderRecord['order_amount'];
            $order_currency = $orderRecord['order_currency'];
            $orderId = $orderRecord['pay_for'];
            
            //if no order, checking accept_all
            if (!$orderInfo)
            {
                //if accepting payments from OOS lk (accepting_all option), confirming
                $order_action = ($this->accept_all_payments ? \pscb_OOS\RESPONSE_ACTION_CONFIRM : \pscb_OOS\RESPONSE_ACTION_CONFIRM);
                Helper::doLog($order_action." on orderId = ".$orderId." : according to Simplified integration support param = '".$this->accept_all_payments."'",\pscb_OOS\TRACE);
                array_push($this->result_response, array(
                    "orderId" => $orderId,
                    "action" => $order_action
                ));
                return false;
            }
            
            //проверяем pay запрос
            $localOrder = array(
                'customer_notified' => 1,
                'order_status' => $this->status_pending
            );                
            switch(true){
                case (empty($order_amount)):
                    $action = \pscb_OOS\RESPONSE_ACTION_REJECT;
                    Helper::doLog("REJECT on orderId = ".$orderId." : order amount is empty",\pscb_OOS\TRACE);
                    break;
                case (!is_numeric($order_amount)):
                    $action = \pscb_OOS\RESPONSE_ACTION_REJECT;
                    Helper::doLog("REJECT on orderId = ".$orderId." : invalid order amount",\pscb_OOS\TRACE);
                    break;
                /*
                case ($order_amount != $this->to_float($orderInfo['total']) && $order_amount < $this->to_float($orderInfo['total'])):
                    $action = \pscb_OOS\RESPONSE_ACTION_CONFIRM;
                    Helper::doLog("REJECT on orderId = ".$orderId." : order amount != amount from notification and order amount < amount from notification, accepting payment",\pscb_OOS\TRACE);
                    $localOrder['order_status'] = $this->status_pending;
                    Helper::doLog("CONFIRM on orderId = ".$orderId.", status = pending",\pscb_OOS\TRACE);
                    break;
                */
                case ($order_amount != $this->to_float($orderInfo['total'])):
                    $action = \pscb_OOS\RESPONSE_ACTION_REJECT;
                    Helper::doLog("REJECT on orderId = ".$orderId." : order amount != amount from notification",\pscb_OOS\TRACE);
                    break;
                case (empty($order_currency)):
                    $action = "REJECT";
                    Helper::doLog("REJECT on orderId = ".$orderId." : empty order currency",\pscb_OOS\TRACE);
                    break;
                case(strlen($order_currency) > 4):
                    $action = "REJECT";
                    Helper::doLog("REJECT on orderId = ".$orderId." : invalid order currency",\pscb_OOS\TRACE);
                    break;
                case ($orderId):
                    //сверяем строчки хеша (присланную и созданную нами)
                    $state = $orderRecord['state'];
                    $responseAction = null;
                    switch($state){
                        //finished
                        case "end":
                            $action = \pscb_OOS\RESPONSE_ACTION_CONFIRM;
                            $localOrder['order_status'] = $this->status_success;
							Helper::doLog("CONFIRM on orderId = ".$orderId.", status = success",\pscb_OOS\TRACE);
                            break;
                        //rejected
                        case "rej":
                            $action = \pscb_OOS\RESPONSE_ACTION_CONFIRM;
                            $localOrder['order_status'] = $this->status_canceled;
							Helper::doLog("CONFIRM on orderId = ".$orderId.", status = canceled",\pscb_OOS\TRACE);
                            break;
                        //returned
                        case "ref":
                            $action = \pscb_OOS\RESPONSE_ACTION_CONFIRM;
                            $localOrder['order_status'] = $this->status_success;
							Helper::doLog("CONFIRM on orderId = ".$orderId.", status = success",\pscb_OOS\TRACE);
                            break;
                        //expired
                        case "exp":
                            $action = \pscb_OOS\RESPONSE_ACTION_CONFIRM;
                            $localOrder['order_status'] = $this->status_pending;
							Helper::doLog("CONFIRM on orderId = ".$orderId.", status = pending",\pscb_OOS\TRACE);
                            break;
                        //canceled
                        case "canceled":
                            $action = \pscb_OOS\RESPONSE_ACTION_CONFIRM;
                            $localOrder['order_status'] = $this->status_canceled;
							Helper::doLog("CONFIRM on orderId = ".$orderId.", status = canceled",\pscb_OOS\TRACE);
                            break;
                        //error occured
                        case "err":
                            $action = \pscb_OOS\RESPONSE_ACTION_CONFIRM;
                            $localOrder['order_status'] = $this->status_canceled;
							Helper::doLog("CONFIRM on orderId = ".$orderId.", status = canceled",\pscb_OOS\TRACE);
                            break;
                        //new/processing/hold/undef - doing nothing
                        case "new":
                        case "sent":
                        case "hold":
                        case "undef":
                            $action = \pscb_OOS\RESPONSE_ACTION_CONFIRM;
							Helper::doLog("CONFIRM on orderId = ".$orderId.", no changes",\pscb_OOS\TRACE);
                            break;
                        default:
                            $action = null;
                    }
                    if(!empty($action))
                    {
                        
                        $localOrder['comments'] = sprintf($orderId);
                        
                        //if some actions already occured with this order (status is not initial):
                        if($orderInfo['status'] !== $this->status_pending)
                        {
                            
                            //if result is different than current, no actions
                            if($orderInfo['status'] !== $localOrder['order_status'])
                            {
                                Helper::doLog("CONFIRM on orderId = ".$orderId." : status=".$orderInfo['status']." found, changed to ".$localOrder["order_status"] ,\pscb_OOS\TRACE);
                            }
                            else
                            {
								Helper::doLog("CONFIRM on orderId = ".$orderId,\pscb_OOS\TRACE);
                            }
                            $action = \pscb_OOS\RESPONSE_ACTION_CONFIRM;
                        }
                    }
                    else
                    {
                        $action = \pscb_OOS\RESPONSE_ACTION_REJECT;
                        $localOrder['order_status'] = $orderInfo['status'];
                    }
                    break;
                default:
                    $action = \pscb_OOS\RESPONSE_ACTION_REJECT;
            }
            array_push($this->result_response, array("orderId" => $orderId,"action" => $action));
            return $localOrder;
        }
        
        
        /**
        * decoding accepted to array, collecting order ids
        * 
        * @param mixed $encrypted_request
        */
        public function decodeRequestToArray($encrypted_request)
        {
            $decrypted_request = $this->decrypt_aes128_ecb_pkcs5($encrypted_request, $this->merchant_key);
            $json_request = json_decode($decrypted_request, true);
            
            if(empty($json_request))
            {
                $msg = 'Failed decrypting secret text or decoding JSON';
                echo "Bad request";
                throw new \Exception($msg);
            }
            
            
            $ordersArray = $json_request['payments'];
            if (!$ordersArray)
            {
                $msg = 'Incorrect data accepted';
                echo "Bad request structure";
                throw new \Exception($msg);
            }
            
            $ordersArray = array_map(function($v)
            {
                return array(
                    "pay_for" => $v["orderId"],
                    "order_amount" => $this->to_float($v["amount"]),
                    "payment_id" => $v["paymentId"],
                    "order_currency" => "RUB",
                    "paymentDateTime" => date_format(date_create_from_format('Y-m-d\TH:i:s.ue',$v['stateDate']),
                                                                             'l jS \of F Y h:i:s A'),
                    "state" => $v['state']
                );
            },$ordersArray);
            
            return $ordersArray;
        }
        
        /**
        * printing final response
        * 
        */
        public function doFinishOutput()
        {
            if ($this->result_response)
            {
                $jsonResponse = array("payments" => $this->result_response);
                $jsonResponseStr = json_encode($jsonResponse);
                echo $jsonResponseStr;
            }else{
                echo "Unknown error, cannot form response";
            }
            
            die;
        }
    }
}
