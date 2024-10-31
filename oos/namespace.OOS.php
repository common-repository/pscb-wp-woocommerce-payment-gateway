<?php
/**
 *
 * PSCB payment plugin core
 *
 * @author Belousov Nikolai
 * @version $1.5$
 * Copyright (C) 2018 JSC Bank "PSCB". All rights reserved.
 */
namespace pscb_OOS
{
    const VERSION = "1.6.1";

    const RESPONSE_ACTION_CONFIRM = "CONFIRM";
    const RESPONSE_ACTION_REJECT = "REJECT";

    const TEST_PAYURL = "https://oosdemo.pscb.ru/pay/";
    const WORK_PAYURL = "https://oos.pscb.ru/pay/";

    const SETTINGS_LANG_RU = "ru";
    const SETTINGS_LANG_EN = "en";

    class Loader
    {
        public static function loadClasses($for = false){
            require_once("class.Helper.php");
            require_once("class.Settings.php");
            require_once("class.GeneralException.php");
            require_once("class.ParameterMapper.php");
            switch($for){
                case "form":
                    require_once("class.FormCreator.php");
                    require_once("class.Order.php");
                    break;
                case "notification":
                    require_once("class.NotificationReciever.php");
                    break;
            }
            return true;
        }
    }
}
