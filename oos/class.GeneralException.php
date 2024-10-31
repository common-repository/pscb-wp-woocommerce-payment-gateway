<?php
namespace pscb_OOS
{
    class GeneralException extends \Exception
    {
        public function __construct($message,$code = 0, Exception $prev = null)
        {
            parent::__construct($message,$code,$prev);
        }
    }
}
