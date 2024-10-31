<?php
namespace pscb_OOS
{
    require_once("class.SettingsField.php");
    /**
    * settings(title,description,type,options,etc) for all admin-specified fields for module
    */
    class Settings
    {
        
        private static $default_filename = "settings.xml";
        private $lang;
        private $settingsFields = array();
        
        /**
        * * reading XML file to array of SettingsFields
        * 
        * @param mixed $filename
        * @return SettingsField[]
        */
        private function loadXML($filename)
		{
            
            if(!file_exists($filename))
			{
                return false;
            }
            
            $settingsDoc = new \DOMDocument("1.0","UTF-8");
            //suppressing because of 'invalid' tags
            libxml_use_internal_errors(true);
            @$settingsDoc->loadHTML(mb_convert_encoding(file_get_contents($filename), 'HTML-ENTITIES', 'UTF-8'));
            $fieldsNodes = $settingsDoc->getElementsByTagName("field");
            
            foreach($fieldsNodes as $fieldNode)
			{
                
                $fieldDoc = new \DOMDocument();
                $fieldDoc->appendChild($fieldDoc->importNode($fieldNode,true));
                
                $alias = $this->getSimpleNodeValue($fieldDoc,"alias");
                $this->settingsFields[$alias] = SettingsField::getBuilder()
                    ->setAlias($alias)
                    ->setTitle($this->getLanguagedNodeValue($fieldDoc,"title"))
                    ->setRequired($this->getSimpleNodeValue($fieldDoc,"required"))
                    ->setDescription($this->getLanguagedNodeValue($fieldDoc,"description"))
                    ->setDefault($this->getSimpleNodeValue($fieldDoc,"default"))
                    ->setFieldType($this->getSimpleNodeValue($fieldDoc,"type"))
                    ->setOptions($this->getSelectNodeValue($fieldDoc,"options"))
                    ->setOrder($this->getSimpleNodeValue($fieldDoc,"order"))
                    ->setVisible($this->getSimpleNodeValue($fieldDoc,"visible"))
                    ->build();
            }
        }
        
        /**
        * reading admin fields settings from XML file, sorting by <order> tag
        * 
        * @param mixed $lang
        * @param mixed $additionals
        * @throws GeneralException if no such file
        */
        public function __construct($lang,$additionals = null)
		{
            $filename = dirname(__FILE__)."/".Settings::$default_filename;
            if(!file_exists($filename))
			{
                throw new GeneralException("no settings file");
            }
            
            $this->lang = $lang;
            
            $this->loadXML($filename);
            
            if(isset($additionals) && !empty($additionals) && is_array($additionals))
			{
                foreach($additionals as $filename)
				{
                    $this->loadXML($filename);
                }
            }
            
            //sorting by <order> tag
            uasort($this->settingsFields,function(SettingsField $o1,SettingsField $o2){
                $order_1 = $o1->getOrder();
                $order_2 = $o2->getOrder();
                if($order_1 < $order_2) return -1;
                if($order_1 > $order_2) return 1;
                elseif($order_1 == $order_2) return 0;
            });
            
            return;
        }
        
        /**
        * reading value from simple tag
        * 
        * @param \DOMDocument $elem
        * @param mixed $alias
        */
        private function getSimpleNodeValue(\DOMDocument $elem,$alias)
		{
            return $elem->getElementsByTagName($alias)->item(0)->nodeValue;
        }
        
        /**
        * reading value from multilanguaged tag
        * 
        * @param \DOMDocument $elem
        * @param mixed $alias
        */
        private function getLanguagedNodeValue(\DOMDocument $elem,$alias)
		{
            $translationTagsDoc = new \DOMDocument();
            $translationTagsDoc->appendChild($translationTagsDoc->importNode($elem->getElementsByTagName($alias)->item(0),true));
            return $this->getSimpleNodeValue($translationTagsDoc,$this->lang);
        }
        
        /**
        * reading options list for element, with accepted language
        * 
        * @param \DOMDocument $elem
        */
        private function getSelectNodeValue(\DOMDocument $elem,$alias)
		{
            $result = array();
            $options = $elem->getElementsByTagName("option");
            if(empty($options)) return null;
            
            foreach($options as $option){
                $optionTagDoc = new \DOMDocument();
                $optionTagDoc->appendChild($optionTagDoc->importNode($option,true));
                $result[$this->getSimpleNodeValue($optionTagDoc,"value")] = 
                    $this->getLanguagedNodeValue($optionTagDoc,"title");
            }
            return $result;
        }

        /**
        * reading field settings by its alias
        * 
        * @param mixed $field_alias
        * @return \pscb_OOS\SettingsField
        */
        public function getSetting($field_alias)
		{
            return $this->settingsFields[$field_alias];
        }
        
        //hide fields                
        public function getVisibleSettingAliases()
        {
            $result = array();

            foreach($this->settingsFields as $key => $element){
                if($element->getVisible()){
                    array_push($result, $key);
                }
            }
            return $result;
            

        }

        /**
        * return setting aliases list
        * @return array
        */
        public function getSettingsAliases(){
            return array_keys($this->settingsFields);
        }
    }
}