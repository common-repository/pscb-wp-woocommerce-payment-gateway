<?php
namespace pscb_OOS
{
    /**
	* Single rule from ParameterMapper
	*/
	class ParameterMapperRule
	{
		//target param alias
		//for example: 'widget'
		private $param_alias;

		//value type
		//for example: 'boolean'
		private $type;
		
		//rules array
		//for example: ("no" => false, "0" => false, "yes" => true, "1" => true)
		private $mapping_rules;
		
		
		function __construct($alias,$type,$rules)
		{
			$this->param_alias = $alias;
			$this->type = $type;
			$this->mapping_rules = $rules;
		}
		
		/**
		* mapping foreign parameter value to allowd param value
		*
		* @param $foreign_value
		*/
		public function mapValue($foreign_value)
		{
			if(!isset($foreign_value)){
				$foreign_value = false;
			}
			$foreign_value = (string)$foreign_value;
			
			//no such rule, throwing 
			if(!array_key_exists($foreign_value,$this->mapping_rules)){
				\pscb_OOS\Helper::doLog("mapping '".$foreign_value."' for parameter ".$this->param_alias, \pscb_OOS\TRACE);
				throw new GeneralException("Cannot map parameter value '".$foreign_value."' for parameter ".$this->param_alias);
			}
			$x = $this->mapping_rules[$foreign_value];
			switch (true){
				case ($this->type == "integer"):
					return (int)$x;
					break;
				case ($this->type == "boolean"):
					return $x == "true";
					break;
				case ($this->type == "string"):
				default:
					return $x;
			}
			//returning mapped parameter value
			//return $this->mapping_rules[$foreign_value];

		}
	}
		
	
    /**
    * Mapping parameters from foreign values to inner, according to 
    */
    class ParameterMapper
    {
		//rules list, read from XML
		//for example: ("merchantId" => $someParameterMapperRule, ...)
		private $rules = array();

		private static $C_PATH;

		public static function setCustomPath($custom_path)
        {
            ParameterMapper::$C_PATH = $custom_path;
        }

		private function getSimpleNodeValue(\DOMDocument $elem,$alias)
		{
            return $elem->getElementsByTagName($alias)->item(0)->nodeValue;
		}
		
		private function getValue(\DOMDocument $elem,$alias)
		{
			$result = array();
            $values = $elem->getElementsByTagName("value");
			if(empty($values)) return null;
            
            foreach($values as $value){
                $valueTagDoc = new \DOMDocument();
				$valueTagDoc->appendChild($valueTagDoc->importNode($value,true));

				$sources = $valueTagDoc->getElementsByTagName("source");
				if(empty($sources)) return null;
				foreach($sources as $source){
					$sourceTagDoc = new \DOMDocument();
					$sourceTagDoc->appendChild($sourceTagDoc->importNode($source,true));
					$result[$source->nodeValue] = $this->getSimpleNodeValue($valueTagDoc,"target");
				}
            }
            return $result;
		}
		
		private function getSourceNodeValue(\DOMDocument $elem,$alias)
		{
            $result = array();
            $sources = $elem->getElementsByTagName("source");
            if(empty($sources)) return null;
            
            foreach($sources as $source){
                $sourceTagDoc = new \DOMDocument();
                $sourceTagDoc->appendChild($sourceTagDoc->importNode($source,true));
                $result[] = $this->getSimpleNodeValue($sourceTagDoc,"source");
            }
            return $result;
		}



		private function loadXML($filename){

			$rulesDoc = new \DOMDocument("1.0","UTF-8");
            //suppressing because of 'invalid' tags
            libxml_use_internal_errors(true);
            @$rulesDoc->loadHTML(mb_convert_encoding(file_get_contents($filename), 'HTML-ENTITIES', 'UTF-8'));
			$rulesNodes = $rulesDoc->getElementsByTagName("rule");
			
			foreach($rulesNodes as $ruleNode){
				$ruleDoc = new \DOMDocument();
				$ruleDoc->appendChild($ruleDoc->importNode($ruleNode,true));

				
				$alias = $this->getSimpleNodeValue($ruleDoc,"alias");
				$type = $this->getSimpleNodeValue($ruleDoc,"type");
				$values = $this->getValue($ruleDoc,"values");

				$a = new ParameterMapperRule($alias, $type, $values);
				$this->rules[$alias] = $a;
				
			}
		}
			
		
        function __construct()
        {
			//read rules from XML
			$filename = dirname(__FILE__)."/mapping_rules.xml";
			if(!file_exists($filename))
			{
                throw new GeneralException("no rules file");
            }
			$this->loadXML($filename);
			
			if(isset(ParameterMapper::$C_PATH) && !empty(ParameterMapper::$C_PATH) && is_array(ParameterMapper::$C_PATH))
			{
                foreach(ParameterMapper::$C_PATH as $filename)
				{
					if(file_exists($filename)){
						$this->loadXML($filename);
					}
                }
            }
		}

		/**
		* mapping foreign parameter value to inner, oos-specific value
		*
		* @param $param_alias
		* @param $foreign_param_value
		*/
        public function mapParam($param_alias,$foreign_param_value)
		{
			if(!array_key_exists($param_alias,$this->rules)){
				throw new GeneralException("Unknown param alias : ".$param_alias);
			}
			
			return $this->rules[$param_alias]->mapValue($foreign_param_value);
		}
        
    }
}