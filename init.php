<?
define("ID_GROUP_MANAGERS", 6);
define("PERSON_TYPE_ID_NO_VAT", 2);
use Bitrix\Main;
use Bitrix\Sale;

//меняем ответсвенного для заказа на ФИО менеджера
Main\EventManager::getInstance()->addEventHandler("sale", "OnSaleOrderBeforeSaved", "changeBeforeOrderSave");

function changeBeforeOrderSave(Main\Event $event)
{
    global $USER;
    $order = $event->getParameter("ENTITY");
    // автоизован и не админ
    if ( $USER->IsAuthorized() && $USER->GetID() != 1){
        //пользователь из группы Менеджеров
        if ( in_array( ID_GROUP_MANAGERS, $USER->GetUserGroupArray()) ){
            $order->setField("RESPONSIBLE_ID", $USER->GetID());
            
            // фамилия имя ответственного
            if (!empty($USER->GetLastName()) || !empty($USER->GetFirstName())){
                $fio = trim(trim($USER->GetLastName())." ".trim($USER->GetFirstName())); 

                if (!empty($fio)){
                    // получаем свойства заказа
                    $propertyCollection = $order->getPropertyCollection();
                    foreach ($propertyCollection as $propertyItem) {

                        switch ($propertyItem->getField("CODE")) {
                            // Прописываем ФИО в одно поле
                            case "FIO_MAIN_MANAGER":
                                $propertyItem->setField("VALUE", $fio);
                                break;
                        }
                    }
                }
            }
        }
    } 
}

AddEventHandler("sale", "OnOrderSave", "changeVat");
// при сохранение заказа меняет налог по типу плательщика
function changeVat($orderId, &$fields, &$orderFields, $isNew) {
    
    if(CModule::IncludeModule("sale")){  
        if($orderId){
            // получаем заказ
            $order = Order::load($orderId);
            // получаем корзину
            $basket = $order->getBasket();
            $basketItems = $basket->getBasketItems();
            if(!empty($basketItems)){
                if($fields["PERSON_TYPE_ID"] == PERSON_TYPE_ID_NO_VAT){
                    foreach($basketItems as $item){
                        $item->setField("VAT_RATE", "0.2");
                        $item->save();
                    }
                }else{
                    foreach($basketItems as $item){
                        $item->setField("VAT_RATE", "0");
                        $item->save();
                    }
                }
            }
        }
    }   
}
