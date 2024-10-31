<?php
namespace pscb_OOS
{
    class OrderShipping
    {
        private $title;
        private $price;
        private $tax;
        private $order_type;
        private $order_object;
        private $order_unit;
        
        public function __construct($title,$price,$tax,$order_type = null,$order_object = null,$order_unit = null)
        {
            $this->title = $title;
            $this->price = Helper::preparePrice($price);
            $this->tax = $tax;
            $this->order_type = (isset($order_type) ? $order_type : "");
            $this->order_object = (isset($order_object) ? $order_object : "service");
            $this->order_unit = (isset($order_unit) ? $order_unit : "");
        }
        
        public function getPrice()
        {
            return $this->price;
        }

        public function getTitle()
        {
            return $this->title;
        }

        public function getType()
        {
            return $this->order_type;
        }

        public function getObject()
        {
            return $this->order_object;
        }
        
        public function getUnit()
        {
            return $this->order_unit;
        }

        public function getResultLine()
        {
            $allowed_tax_values = Helper::getAllowedTaxValues();
            $allowed_item_types = Helper::getAllowedOrderItemTypeValues();
            $allowed_item_objects = Helper::getAllowedOrderItemObjectValues();
            return array("text" => mb_substr($this->title, 0, 62, 'utf-8'),
                         "quantity" => 1,
                         "price" => $this->price,
                         "amount" => $this->price,
                         "tax" => $allowed_tax_values[$this->tax],
                         "type" => $allowed_item_types[$this->order_type],
                         "object" => $allowed_item_objects[$this->order_object],
                         "unit" => $this->order_unit
                         );
        }
    }
}