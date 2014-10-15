<?ini_set("display_errors","1");
ini_set("display_startup_errors","1");
ini_set('error_reporting', E_ALL);

$root = "/var/www/megatorg-spb.ru/www/";
$_SERVER["DOCUMENT_ROOT"] = $root;
require($root."/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule('main');
CModule::IncludeModule('sale');
$arFilter = array();
$dbUser = CUser::GetList($by = 'ID', $order = 'ASC', $arFilter);
/*
 * Для каждого пользователя выполняем следующий код
 */
$endDate = new DateTime;
$beginDate = $endDate->modify('-30 day');

while ($arUser = $dbUser->Fetch()){
    $arFilter=Array("USER_ID" => $arUser["ID"]);
    $res = CSaleUser::GetList($arFilter);
    $fuser_id=$res['ID'];

    /*
     * получаем товары в заказе за указанную дату
     */
    $dbBasketOrderItems = CSaleBasket::GetList(
        array("NAME" => "ASC", "ID" => "ASC"),
        array("FUSER_ID" => $fuser_id, "DELAY" => 'N', ">ORDER_ID" => "0", ">DATE_INSERT" => $beginDate->format("d-m-Y"), "<DATA_INSERT"=>$endDate->format("d-m-Y")),
        false,
        false,
        array("ID", "PRODUCT_ID", "NAME"));
    $arProductOrderID = array();
    while ($arBasketOrderItems = $dbBasketOrderItems->Fetch()){
        $arProductOrderID[] = $arBasketOrderItems["PRODUCT_ID"];
    }

    /*
     * Получили отложенные товары пользователя
     */
    $dbBasketDelayItems = CSaleBasket::GetList(
        array("NAME" => "ASC", "ID" => "ASC"),
        array("FUSER_ID" => $fuser_id, "DELAY" => 'Y', "ORDER_ID" => "NULL", ">DATE_INSERT" => $beginDate->format("d-m-Y"), "<DATA_INSERT"=>$endDate->format("d-m-Y")),
        false,
        false,
        array("ID", "PRODUCT_ID", "NAME"));
    $delayProductList = "";
    while ($arBasketDelayItem = $dbBasketDelayItems->Fetch()){
       if (!in_array($arBasketDelayItem["PRODUCT_ID"],$arProductOrderID)){
           if ($delayProductList) $delayProductList .= ', '.$arBasketDelayItem["NAME"];
                else $delayProductList .= $arBasketDelayItem["NAME"];
       }

    }
    /*
     * отправляем сообщение пользователю. Можно использовать функции битрикса,
     * чтобы привязать к почтовым шаблонам в адмиинке, для большей автономности скрипта буду использовать mail
     */
    if ($delayProductList)
    //mail($arUser["EMAIL"], "Отложенные товары","Добрый день, ".$arUser['LAST_NAME']." ".$arUser['NAME']." В вашем вишлисте хранятся товары ".$delayProductList.".");
    //file_put_contents($root.'/adduser_log.txt', var_export("4", true), FILE_APPEND);
    file_put_contents($root.'/adduser_log.txt', var_export($arUser["EMAIL"]." Добрый день, ".$arUser['LAST_NAME']." ".$arUser['NAME']." В вашем вишлисте хранятся товары ".$delayProductList.".\n", true), FILE_APPEND);
}