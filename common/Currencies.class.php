<?php

/**
 * Валутите
 */
class common_Currencies extends core_Manager {
    
    /**
     * Интерфайси, поддържани от този мениджър
     */
    var $interfaces = 'acc_RegisterIntf';

    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, common_Wrapper, acc_RegisterPlg,
                     CurrencyGroups=common_CurrencyGroups,  plg_Sorting, plg_State2';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Списък с всички валути';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = "id, name, code, lastUpdate, lastRate, state, createdOn, createdBy";
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Валута->Име,mandatory');
        $this->FLD('code', 'varchar(3)', 'caption=Валута->Код,mandatory');
        $this->FLD('lastUpdate', 'date', 'caption=Последно->обновяване, input=none');
        $this->FLD('lastRate', 'double', 'caption=Последно->курс, input=none');
        $this->FLD('groups', 'keylist(mvc=common_CurrencyGroups, select=name)', 'caption=Групи');
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * Приготвяне на данните, ако имаме groupId от $_GET
     * В този случай няма да листваме всички записи, а само тези, които
     * имат в полето 'groups' groupId-то от $_GET
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        if ($groupId = Request::get('groupId', 'int')) {
            
            $groupRec = $mvc->CurrencyGroups->fetch($groupId);
            
            // Полето 'groups' е keylist и затова имаме LIKE
            $data->query->where("#groups LIKE '%|{$groupId}|%'");
            
            // Сменяме заглавието
            $data->title = 'Валути в група "|*' . $groupRec->name . "\"";
        }
    }
    
    
    /**
     * Смяна на бутона
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareListToolbar($mvc, $res, $data)
    {
        $data->toolbar->removeBtn('btnAdd');
        
        $data->toolbar->addBtn('Нова валута', array($mvc, 'Add', 'groupId' => Request::get('groupId')));
    }
    
    
    /**
     * Слагаме default за checkbox-овете на полето 'groups', когато редактираме групи на дадена валута
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        if (empty($data->form->rec->id) && ($groupId = Request::get('groupId', 'int'))) {
            $data->form->setDefault('groups', '|' . $groupId . '|');
        }
    }
    
    
    /**
     * Връща заглавието и мярката на перото за продукта
     *
     * Част от интерфейса: intf_Register
     */
    function getAccItemRec($rec)
    {
        return (object) array( 'title' => $rec->code );
    }
    
    
    /**
     *  Извиква се след SetUp-а на таблицата за модела
     */
    function on_AfterSetupMVC($mvc, &$res)
    {
        $currDefs = array( "АВСТРАЛИЙСКИ ДОЛАР|AUD",
            "БРАЗИЛСКИ РЕАЛ|BRL",
            "КАНАДСКИ ДОЛАР|CAD",
            "ШВЕЙЦАРСКИ ФРАНК|CHF",
            "КИТАЙСКИ РЕНМИНБИ ЮАН|CNY",
            "ЧЕШКА КРОНА|CZK",
            "ДАТСКА КРОНА|DKK",
            "БРИТАНСКА ЛИРА|GBP",
            "ХОНГКОНГСКИ ДОЛАР|HKD",
            "ХЪРВАТСКА КУНА|HRK",
            "УНГАРСКИ ФОРИНТ|HUF",
            "ИНДОНЕЗИЙСКА РУПИЯ|IDR",
            "ИЗРАЕЛСКИ ШЕКЕЛ|ILS",
            "ИНДИЙСКА РУПИЯ|INR",
            "ЯПОНСКА ЙЕНА|JPY",
            "ЮЖНОКОРЕЙСКИ ВОН|KRW",
            "ЛИТОВСКИ ЛИТАС|LTL",
            "ЛАТВИЙСКИ ЛАТ|LVL",
            "МЕКСИКАНСКО ПЕСО|MXN",
            "МАЛАЙЗИЙСКИ РИНГИТ|MYR",
            "НОРВЕЖКА КРОНА|NOK",
            "НОВОЗЕЛАНДСКИ ДОЛАР|NZD",
            "ФИЛИПИНСКО ПЕСО|PHP",
            "ПОЛСКА ЗЛОТА|PLN",
            "НОВА РУМЪНСКА ЛЕЯ|RON",
            "РУСКА РУБЛА|RUB",
            "ШВЕДСКА КРОНА|SEK",
            "СИНГАПУРСКИ ДОЛАР|SGD",
            "ТАЙЛАНДСКИ БАТ|THB",
            "ТУРСКА ЛИРА|TRY",
            "ЩАТСКИ ДОЛАР|USD",
            "ЮЖНОАФРИКАНСКИ РАНД|ZAR",
            "ЕВРО|EUR" );
        $insertCnt = 0;
        
        foreach( $currDefs as $c) {
            
            $rec = new stdClass();
            
            list($rec->name, $rec->code) = explode('|', $c);
            
            if (!$this->fetch("#code = '{$rec->code}'")){
                $rec->lastUpdate = dt::verbal2mysql();
                
                if($rec->code == 'EUR') {
                    $rec->lastRate = 1;
                }
                $rec->state = "active";
                
                $this->save($rec);
                
                $insertCnt++;
            }
        }
        
        if($insertCnt) {
            $res .= "<li>Добавени са запис/и за {$insertCnt} валути.</li>";
        }
    }
}