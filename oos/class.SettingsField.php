<?php
namespace pscb_OOS
{
    
    class SettingsFieldBuilder extends SettingsField
    {
        
        public function __construct(){}

        public function setAlias($val){ $this->alias = $val; return $this;}
        public function setTitle($val){ $this->title = $val; return $this;}
        public function setDescription($val){ $this->description = $val; return $this;}
        public function setDefault($val){ $this->default = $val; return $this;}
        public function setFieldType($val){ $this->fieldType = $val; return $this;}
        public function setRequired($val){ $this->required = $val; return $this;}
        public function setOptions($val){ $this->options = $val; return $this;}
        public function setOrder($val){ $this->order = $val; return $this;}
        public function setVisible($val){ $this->visible = $val; return $this;}
        
        public function build(){
            return new SettingsField($this);
        }
    }
    
    /**
    * settings(title,description,type,options,etc) for one field
    */
    class SettingsField
    {
        
        protected $alias;
        protected $title;
        protected $description;
        protected $default;
        protected $fieldType;
        protected $required;
        protected $options;
        protected $order;
        protected $visible;
        
        public static function getBuilder(){return new SettingsFieldBuilder();}
        
        public function __construct(SettingsFieldBuilder $settingsFieldBuilder){
            $this->alias = $settingsFieldBuilder->getAlias();
            $this->title = $settingsFieldBuilder->getTitle();
            $this->description = $settingsFieldBuilder->getDescription();
            $this->default = $settingsFieldBuilder->getDefault();
            $this->fieldType = $settingsFieldBuilder->getFieldType();
            $this->required = $settingsFieldBuilder->getRequired();
            $this->options = $settingsFieldBuilder->getOptions();
            $this->order = $settingsFieldBuilder->getOrder();
            $this->visible = $settingsFieldBuilder->getVisible();
        }
        
        public function getAlias(){ return $this->alias;}
        public function getTitle(){ return $this->title;}
        public function getDescription(){ return $this->description;}
        public function getDefault(){ return $this->default;}
        public function getFieldType(){ return $this->fieldType;}
        public function getRequired(){ return $this->required;}
        public function getOptions(){ return $this->options;}
        public function getOrder(){ return $this->order;}
        public function getVisible(){ return $this->visible;}
    }
}
