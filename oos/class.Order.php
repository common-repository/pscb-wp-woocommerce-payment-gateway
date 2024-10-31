<?php
namespace pscb_OOS
{
    require_once("class.OrderItem.php");
    require_once("class.OrderShipping.php");
    
    class Order
    {
        //array of OrderItems
        private $taxSystem;
        private $companyEmail;
        private $items_list;
        private $shipping;
        
        public function __construct()
        {
            $this->taxSystem;
            $this->companyEmail;
            $this->items_list = array();
            $this->shipping = null;
        }
        
        public function isReady()
        {
            return (!empty($this->items_list));
        }
        
        public function addItem(OrderItem $orderItem)
        {
            $this->items_list[] = $orderItem;
        }
        
        public function addTS($taxSystem)
        {
            $this->taxSystem = $taxSystem;
        }

        public function addCE($companyEmail)
        {
            $this->companyEmail = $companyEmail;
        }

        public function addShipping(OrderShipping $orderShipping)
        {
            $this->shipping = $orderShipping;
        }
        /**
        * returning receipt total
        * 
        */
        public function getTotal()
        {
            $result = array_sum(array_map(function($v){ return $v->getPriceForAll();},$this->items_list));
            if(!empty($this->shipping))
                $result += $this->shipping->getPrice();
            return $result;
        }
        
        public function getFixPriceCoeffiecient($real_total, $receipt_total)
        {
            $result = 1;
            if(!empty($this->shipping))
                $result = ($real_total - $this->shipping->getPrice())/(($receipt_total - $this->shipping->getPrice())*1.0);
            else
                $result = $real_total/($receipt_total*1.0);
            return $result;
        }
        
        /**
        * fixing price differenct between receipt sum and order total
        * by lowering orderItems pack cost
        * 
        * @param mixed $coeff_to_fix_prices
        */
        public function fixPriceDifference($coeff_to_fix_prices)
        {
            $this->items_list = array_map(function($v)use ($coeff_to_fix_prices){
                $v->fixPrice($coeff_to_fix_prices);
                return $v;
            },$this->items_list);
            
            return true;
        }
        
        /**
        * returning formed FdReceipt for sending
        * 
        */
        public function getFdReceipt()
        {
            $result = array_map(function($v){return $v->getResultLine();},$this->items_list);
            if(!empty($this->shipping))
                $result[] = $this->shipping->getResultLine();
            $result = array('taxSystem' => $this->taxSystem, 'companyEmail' => $this->companyEmail,'items' => $result);
            
            return $result;
        }
        
        /**
        * generating order details
        * 
        */
        public function getDetails()
        {
            $order_details = implode(', ',array_map(function($v){return $v->getTitle().' x'.$v->getQuantity();},$this->items_list));
            if(isset($this->shipping)){
                $shipping_details = $this->shipping->getTitle();
                $order_details .= ', '.$shipping_details;
            }
            return $order_details;
        }
    }
}
