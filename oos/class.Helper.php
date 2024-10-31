<?php
namespace pscb_OOS
{
    
    const TRACE = 1;
    const ERROR = 4;
    
    class Helper
    {
        
        private static $logfile_limit = 4096000;
        
        private static $logfile_name = "oos.log";
        
        private static $log_level = TRACE;
        
        private static $log_level_names = array(TRACE => "TRACE",
                                                ERROR => "ERROR");
        
        private static $allowed_tax_values = array('' =>'none',
                                                    '0' => 'vat0',
                                                    '10' => 'vat10',
                                                    '18' => 'vat20',
                                                    '20' => 'vat20',
                                                    '10/110' => 'vat110',
                                                    '18/118' => 'vat120',
                                                    '20/120' => 'vat120');
        
        private static $allowed_pm_values = array('' => '',
                                                    'ac' => 'bank_card',
                                                    'ym' => 'yandex_money',
                                                    'qiwi' => 'qiwi_wallet',
                                                    'wm' => 'web_money',
                                                    'alfa' => 'alfa_click',
                                                    'pscb_terminal' => 'pscb_terminal',
                                                    'mobi-money' => 'mobile_pay',
                                                    'sbp' => "sbp");

        private static $allowed_pm_values_w = array('' => 'any',
                                                    'ac' => 'bank_card',
                                                    'sbp' => "sbp");
													
		private static $allowed_item_types = array("" => "",
													"full_prepayment" => "full_prepayment",
													"prepayment" => "prepayment",
													"advance" => "advance",
													"full_payment" => "full_payment");
		
		private static $allowed_item_objects = array("" => "",
														"commodity" => "commodity",
														"job" => "job",
														"service" => "service",
														"lottery" => "lottery",
														"composite" => "composite",
                                                        "another" => "another");
                                                        
        private static $allowed_tax_systems = array("" => "",
														"osn" => "osn",
														"usn_income" => "usn_income",
														"usn_income_outcome" => "usn_income_outcome",
														"esn" => "esn",
														"patent" => "patent");
        
        public static function getAllowedTaxValues()
        {
            return Helper::$allowed_tax_values;
        }
        
        public static function getAllowedPmValues()
        {
            return Helper::$allowed_pm_values;
        }

        public static function getAllowedPmValuesW()
        {
            return Helper::$allowed_pm_values_w;
        }
		
		public static function getAllowedOrderItemTypeValues()
        {
            return Helper::$allowed_item_types;
        }
		
		public static function getAllowedOrderItemObjectValues()
        {
            return Helper::$allowed_item_objects;
        }

        public static function getTaxSystemsValues()
        {
            return Helper::$allowed_tax_systems;
        }

        public static function getAllowedLogLevelValues()
        {
            return Helper::$log_level_names;
        }
        
        private static function getLogFilename()
        {
            $result = __DIR__."/".self::$logfile_name;
            
            if(!file_exists($result))
            {
                return $result;
            }
            if(filesize($result) >= self::$logfile_limit)
            {
                $i = 1;
                while(file_exists($result.$i))
                {
                    ++$i;
                }
                copy($result,$result.$i);
                file_put_contents($result,"");
            }
            
            return $result;
        }
        
        public static function doLog($something_to_log, $log_level)
        {
            if($log_level >= self::$log_level)
            {
                file_put_contents(self::getLogFilename(),
                    @date_format(date_create(), 'Y-m-d H:i:s')." : ".self::$log_level_names[$log_level]." : ".$something_to_log."\r\n",
                    FILE_APPEND);
            }
        }
        
        public static function setLogLevel($log_level)
        {
            Helper::$log_level = $log_level;
        }
        
        public static function preparePrice($val)
        {
            return number_format(round($val,2), 2, '.', '');
        }
    }
}
