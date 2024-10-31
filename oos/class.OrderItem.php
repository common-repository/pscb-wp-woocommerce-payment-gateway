<?php       
namespace pscb_OOS
{
	
	abstract class OrderItemProto
	{
        protected $title;
        protected $quantity;
        //price with taxes and no discounts
        protected $price_for_one;
        //price with taxes and discounts
        protected $price_for_all;
        protected $tax;
		//payment type -- optional
		protected $itemType;
		//payment product type -- optional
		protected $itemObject;
		//product measure -- optional
		protected $itemUnit;
		
		public function getTitle()
        {
            return $this->title;
        }
        
        public function getQuantity()
        {
            return $this->quantity;
        }
        
        public function getPriceForOne()
        {
            return $this->price_for_one;
        }
        
        public function getPriceForAll()
        {
            return $this->price_for_all;
        }
		
        public function getTax()
        {
            return $this->tax;
        }
        
		public function getItemType()
        {
            return $this->itemType;
        }
		
		public function getItemObject()
        {
            return $this->itemObject;
        }
		
		public function getItemUnit()
        {
            return $this->itemUnit;
        }
		
		/**
		* forming assoc array for receipt sending in payment form
		*/
		public function getResultLine()
        {
            $allowed_tax_values = Helper::getAllowedTaxValues();
            $result = array("text" => mb_substr($this->title, 0, 62, 'utf-8'),
							"quantity" => $this->quantity,
							"price" => $this->price_for_one,
							"amount" => $this->price_for_all,
							"tax" => $allowed_tax_values[$this->tax]);
			if(isset($this->itemType) && !empty($this->itemType)){
				$result["type"] = $this->itemType;
			}
			if(isset($this->itemObject) && !empty($this->itemObject)){
				$result["object"] = $this->itemObject;
			}
			if(isset($this->itemUnit) && !empty($this->itemUnit)){
				$result["unit"] = $this->itemUnit;
			}
			return $result;
        }
		
		/**
        * if order_amount > items_amount (for example, discount for order, not items)
        * fixing items prices to state order_amount = items_amount
        * total discount transforming to items discounts
        * 
        * @param mixed $price_coeff
        */
        public function fixPrice($price_coeff)
        {
            $this->price_for_all = Helper::preparePrice($this->price_for_all * $price_coeff);
        }
	}
	class OrderItemBuilder extends OrderItemProto{
		public function setTitle($val)
        {
            $this->title = strip_tags($val);
			return $this;
        }
        
        public function setQuantity($val)
        {
            $this->quantity = $val;
			return $this;
        }
        
        public function setPriceForOne($val)
        {
            $this->price_for_one = $val;
			return $this;
        }
        
        public function setPriceForAll($val)
        {
            $this->price_for_all = $val;
			return $this;
        }
        
        public function setTax($val)
        {
            $this->tax = $val;
            return $this;
        }
		
		public function setItemType($val)
        {
            $this->itemType = $val;
			return $this;
        }
		
		public function setItemObject($val)
        {
            $this->itemObject = $val;
			return $this;
        }
		
		public function setItemUnit($val)
        {
            $this->itemUnit = $val;
			return $this;
        }
		
		public function build(){
			return new OrderItem($this);
		}
	}
	
    class OrderItem extends OrderItemProto
    {
        public function __construct(OrderItemBuilder $builder)
        {
            $this->title = $builder->getTitle();
            $this->quantity = $builder->getQuantity();
			$price_for_one = $builder->getPriceForOne();
            $this->price_for_one = Helper::preparePrice($price_for_one);
			$price_for_all = $builder->getPriceForAll();
            $this->price_for_all = Helper::preparePrice($price_for_all);
            $this->tax = $builder->getTax();
			$this->itemType = $builder->getItemType();
			$this->itemObject = $builder->getItemObject();
			$this->itemUnit = $builder->getItemUnit();
        }
        
    }
}
