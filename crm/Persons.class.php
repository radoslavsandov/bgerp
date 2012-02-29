<?php



/**
 * Мениджър на физическите лица
 *
 *
 * @category  bgerp
 * @package   crm
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Физически лица
 */
class crm_Persons extends core_Master
{
    
    
    /**
     * Интерфайси, поддържани от този мениджър
     */
    var $interfaces = array(
        // Интерфайс на всички счетоводни пера, които представляват контрагенти
        'crm_ContragentAccRegIntf',
        
        // Интерфейс за счетоводни пера, отговарящи на физически лица   
        'crm_PersonAccRegIntf',
        
        // Интерфайс за всякакви счетоводни пера
        'acc_RegisterIntf',
        
        // Интерфейс на източник на събития за календара
        'crm_CalendarEventsSourceIntf',
        
        // Интерфейс за корица на папка
        'doc_FolderIntf',
    
        //Интерфей за данните на контрагента
        'doc_ContragentDataIntf'
    
    );
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Лица";
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Лице";
    
    
    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/vcard.png';
    
    
    /**
     * Кои полета ще извличаме, преди изтриване на заявката
     */
    var $fetchFieldsBeforeDelete = 'id,name';
    
    
    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_Printing, plg_LastUsedKeys, plg_Select,
                     crm_Wrapper, plg_SaveAndNew, plg_PrevAndNext, plg_Rejected, plg_State,
                     plg_Sorting, recently_Plugin, plg_Search, acc_plg_Registry, doc_FolderPlg';
    
    
    /**
     * Полета, които се показват в листови изглед
     */
    var $listFields = 'numb=№,nameList=Име,phonesBox=Комуникации,addressBox=Адрес,tools=Пулт,name=';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    var $lastUsedKeys = 'groupList';
    
    
    /**
     * Полета по които се прави пълнотестово търсене от плъгина plg_Search
     */
    var $searchFields = 'name,egn,birthday,country,place';
    
    
    /**
     * Права за писане
     */
    var $canWrite = 'crm,admin';
    
    
    /**
     * Права за четене
     */
    var $canRead = 'crm,admin';
    
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'crm/tpl/SinglePersonLayout.shtml';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Име на лицето
        $this->FLD('salutation', 'enum(,mr=Г-н,mrs=Г-жа,miss=Г-ца)', 'caption=Обръщение');
        $this->FLD('name', 'varchar(255)', 'caption=Имена,width=100%,mandatory,remember=info');
        $this->FNC('nameList', 'varchar', 'sortingLike=name');
        
        // Единен Граждански Номер
        $this->FLD('egn', 'drdata_EgnType', 'caption=ЕГН');
        
        // Дата на раждане
        $this->FLD('birthday', 'combodate', 'caption=Рожден ден');
        
        // Адресни данни
        $this->FLD('country', 'key(mvc=drdata_Countries,select=commonName,allowEmpty)', 'caption=Държава,remember');
        $this->FLD('pCode', 'varchar(255)', 'caption=Пощ. код,recently');
        $this->FLD('place', 'varchar(255)', 'caption=Нас. място,width=100%');
        $this->FLD('address', 'varchar(255)', 'caption=Адрес,width=100%');
        
        // Служебни комуникации
        $this->FLD('buzCompanyId', 'key(mvc=crm_Companies,select=name,allowEmpty)', 'caption=Служебни комуникации->Фирма,oldFieldName=buzCumpanyId');
        $this->FLD('buzEmail', 'email', 'caption=Служебни комуникации->Имейл,width=100%');
        $this->FLD('buzTel', 'drdata_PhoneType', 'caption=Служебни комуникации->Телефони,width=100%');
        $this->FLD('buzFax', 'drdata_PhoneType', 'caption=Служебни комуникации->Факс,width=100%');
        $this->FLD('buzAddress', 'varchar', 'caption=Служебни комуникации->Адрес,width=100%');
        
        // Лични комуникации
        $this->FLD('email', 'emails', 'caption=Лични комуникации->Имейл,width=100%');
        $this->FLD('tel', 'drdata_PhoneType', 'caption=Лични комуникации->Телефони,width=100%');
        $this->FLD('mobile', 'drdata_PhoneType', 'caption=Лични комуникации->Мобилен,width=100%');
        $this->FLD('fax', 'drdata_PhoneType', 'caption=Лични комуникации->Факс,width=100%');
        $this->FLD('website', 'url', 'caption=Лични комуникации->Сайт/Блог,width=100%');
        
        // Допълнителна информация
        $this->FLD('info', 'richtext', 'caption=Информация->Бележки,height=150px');
        $this->FLD('photo', 'fileman_FileType(bucket=pictures)', 'caption=Информация->Фото');
        
        // Лична карта
        $this->FLD('idCardNumber', 'varchar(16)', 'caption=Лична карта->Номер');
        $this->FLD('idCardIssuedOn', 'date', 'caption=Лична карта->Издадена на');
        $this->FLD('idCardExpiredOn', 'date', 'caption=Лична карта->Валидна до');
        $this->FLD('idCardIssuedBy', 'varchar(64)', 'caption=Лична карта->Издадена от');
        
        // В кои групи е?
        $this->FLD('groupList', 'keylist(mvc=crm_Groups,select=name)', 'caption=Групи->Групи,remember');
        
        // Състояние
        $this->FLD('state', 'enum(active=Вътрешно,closed=Нормално,rejected=Оттеглено)', 'caption=Състояние,value=closed,notNull,input=none');
    }
    
    
    /**
     * Подредба и филтър на on_BeforePrepareListRecs()
     * Манипулации след подготвянето на основния пакет данни
     * предназначен за рендиране на списъчния изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        // Подредба
        if($data->listFilter->rec->order == 'alphabetic' || !$data->listFilter->rec->order) {
            $data->query->orderBy('#name');
        } elseif($data->listFilter->rec->order == 'last') {
            $data->query->orderBy('#createdOn=DESC');
        }
        
        if($data->listFilter->rec->alpha) {
            if($data->listFilter->rec->alpha{0} == '0') {
                $cond = "#name NOT REGEXP '^[a-zA-ZА-Яа-я]'";
            } else {
                $alphaArr = explode('-', $data->listFilter->rec->alpha);
                $cond = array();
                $i = 1;
                
                foreach($alphaArr as $a) {
                    $cond[0] .= ($cond[0] ? ' OR ' : '') .
                    "(LOWER(#name) LIKE LOWER('[#{$i}#]%'))";
                    $cond[$i] = $a;
                    $i++;
                }
            }
            
            $data->query->where($cond);
        }
        
        if($names = Request::get('names')) {
            $namesArr = explode(',', $names);
            $first = TRUE;
            
            foreach($namesArr as $name) {
                $name = trim($name);
                
                if($first) {
                    $data->query->where(array("#searchKeywords LIKE ' [#1#] %'", $name));
                } else {
                    $data->query->orWhere(array("#searchKeywords LIKE ' [#1#] %'", $name));
                }
                $first = FALSE;
            }
            
            $date = Request::get('date', 'date');
            
            if($date) {
                $data->title = "Именници на <font color='green'>" . dt::mysql2verbal($date, 'd-m-Y, l') . "</font>";
            } else {
                $data->title = "Именници";
            }
        }
        
        if($data->groupId = Request::get('groupId', 'key(mvc=crm_Groups,select=name)')) {
            $data->query->where("#groupList LIKE '%|{$data->groupId}|%'");
        }
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('order', 'enum(alphabetic=Азбучно,last=Последно добавени)', 'caption=Подредба,input,silent');
        $data->listFilter->FNC('groupId', 'key(mvc=crm_Groups,select=name,allowEmpty)', 'placeholder=Всички групи,caption=Група,input,silent');
        $data->listFilter->FNC('alpha', 'varchar', 'caption=Буква,input=hidden,silent');
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'search,order,groupId';
        
        $data->listFilter->input('alpha,search,order,groupId', 'silent');
    }
    
    
    /**
     * Премахване на бутон и добавяне на нови два в таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareListToolbar($mvc, $res, $data)
    {
        if($data->toolbar->removeBtn('btnAdd')) {
            $data->toolbar->addBtn('Ново лице', array('Ctr' => $mvc, 'Act' => 'Add'), 'id=btnAdd,class=btn-add');
        }
    }
    
    
    /**
     * Модифициране на edit формата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $form = $data->form;
        
        if(empty($form->rec->id)) {
            // Слагаме Default за поле 'country'
            $Countries = cls::get('drdata_Countries');
            $form->setDefault('country', $Countries->fetchField("#commonName = '" . BGERP_OWN_COMPANY_COUNTRY . "'", 'id'));
        }
        
        $mvrQuery = drdata_Mvr::getQuery();
        
        while($mvrRec = $mvrQuery->fetch()) {
            $mvrName = 'МВР - ';
            $mvrName .= drdata_Mvr::getVerbal($mvrRec, 'city');
            $mvrSug[$mvrName] = $mvrName;
        }
        
        $form->setSuggestions('idCardIssuedBy', $mvrSug);
    }
    
    
    /**
     * Манипулации със заглавието
     *
     * @param core_Mvc $mvc
     * @param core_Et $tpl
     * @param stdClass $data
     */
    function on_AfterPrepareListTitle($mvc, $tpl, $data)
    {
        if($data->listFilter->rec->groupId) {
            $data->title = "Лица в групата|* \"<b style='color:green'>" .
            crm_Groups::getTitleById($data->groupId) . "</b>\"";
        } elseif($data->listFilter->rec->search) {
            $data->title = "Лица отговарящи на филтъра|* \"<b style='color:green'>" .
            type_Varchar::escape($data->listFilter->rec->search) .
            "</b>\"";
        } elseif($data->listFilter->rec->alpha) {
            if($data->listFilter->rec->alpha{0} == '0') {
                $data->title = "Лица, които не започват с букви";
            } else {
                $data->title = "Лица започващи с буквите|* \"<b style='color:green'>{$data->listFilter->rec->alpha}</b>\"";
            }
        } else {
            $data->title = '';
        }
    }
    
    
    /**
     * Изпълнява се след въвеждането на данните от заявката във формата
     */
    function on_AfterInputEditForm($mvc, $form)
    {
        $rec = $form->rec;
        
        if(isset($rec->egn) && ($rec->birthday == '??-??-????')) {
            try {
                $Egn = new drdata_BulgarianEGN($rec->egn);
            } catch(Exception $e) {
                $err = $e->getMessage();
            }
            
            if(!$err) {
                $rec->birthday = $Egn->birth_day . "-" . $Egn->birth_month . "-" . $Egn->birth_year;
            }
        }
        
        if($form->isSubmitted()) {
            
            // Правим проверка за дублиране с друг запис
            if(!$rec->id) {
                $nameL = strtolower(trim(STR::utf2ascii($rec->name)));
                
                $query = $mvc->getQuery();
                
                while($similarRec = $query->fetch(array("#searchKeywords LIKE '% [#1#] %'", $nameL))) {
                    $similars[$similarRec->id] = $similarRec;
                    $similarName = TRUE;
                }
                
                $egnNumb = preg_replace("/[^0-9]/", "", $rec->egn);
                
                if($egnNumb) {
                    $query = $mvc->getQuery();
                    
                    while($similarRec = $query->fetch(array("#egn LIKE '[#1#]'", $egnNumb))) {
                        $similars[$similarRec->id] = $similarRec;
                    }
                    $similarEgn = TRUE;
                }
                
                if(count($similars)) {
                    foreach($similars as $similarRec) {
                        $similarPersons .= "<li>";
                        $similarPersons .= ht::createLink($similarRec->name, array($mvc, 'single', $similarRec->id), NULL, array('target' => '_blank'));
                        
                        if($similarRec->egn) {
                            $similarPersons .= ", " . $mvc->getVerbal($similarRec, 'egn');
                        } elseif($birthday = $mvc->getverbal($similarRec, 'birthday')) {
                            $similarPersons .= ", " . $birthday;
                        }
                        
                        if(trim($similarRec->place)) {
                            $similarPersons .= ", " . $mvc->getVerbal($similarRec, 'place');
                        }
                        $similarPersons .= "</li>";
                    }
                    
                    $fields = ($similarEgn && $similarName) ? "name,egn" : ($similarName ? "name" : "egn");
                    
                    $sledniteLica = (count($similars) == 1) ? "следното лице" : "следните лица";
                    
                    $form->setWarning($fields, "Възможно е дублиране със {$sledniteLica}|*: <ul>{$similarPersons}</ul>");
                }
            }
            
            if($rec->place) {
                $rec->place = drdata_Address::canonizePlace($rec->place);
            }
        }
    }
    
    
    /**
     * Добавяне на табове
     *
     * @param core_Et $tpl
     * @return core_et $tpl
     */
    function renderWrapping_($tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphavit'));
        
        $alpha = Request::get('alpha');
        
        $selected = 'none';
        
        $letters = arr::make('0-9,А-A,Б-B,В-V=В-V-W,Г-G,Д-D,Е-E,Ж-J,З-Z,И-I,Й-J,К-Q=К-K-Q-C,' .
            'Л-L,М-M,Н-N,О-O,П-P,Р-R,С-S,Т-T,У-U,Ф-F,Х-H=Х-X-H,Ц-Ч,Ш-Щ,Ю-Я', TRUE);
        
        foreach($letters as $a => $set) {
            $tabs->TAB($a, '|*' . str_replace('-', '<br>', $a), array($this, 'list', 'alpha' => $set));
            
            if($alpha == $set) {
                $selected = $a;
            }
        }
        
        $tpl = $tabs->renderHtml($tpl, $selected);
        
        //$tpl->prepend('<br>');
        
        return $tpl;
    }
    
    
    /**
     * Промяна на данните от таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->nameList = $row->name;
        
        $row->numb = $rec->id;
        
        // Fancy ефект за картинката
        $Fancybox = cls::get('fancybox_Fancybox');
        
        $tArr = array(200, 150);
        $mArr = array(600, 450);
        
        if($rec->photo) {
            $row->image = $Fancybox->getImage($rec->photo, $tArr, $mArr);
        } else {
            $row->image = "<img class=\"hgsImage\" src=" . sbf('img/noimage120.gif') . " alt='no image'>";
        }
        
        $country = tr($mvc->getVerbal($rec, 'country'));
        $pCode = $mvc->getVerbal($rec, 'pCode');
        $place = $mvc->getVerbal($rec, 'place');
        $address = $mvc->getVerbal($rec, 'address');
        
        $row->addressBox = $country;
        $row->addressBox .= ($pCode || $place) ? "<br>" : "";
        
        $row->addressBox .= $pCode ? "{$pCode} " : "";
        $row->addressBox .= $place;
        
        $row->addressBox .= $address ? "<br/>{$address}" : "";
        
        $mob = $mvc->getVerbal($rec, 'mobile');
        $tel = $mvc->getVerbal($rec, 'tel');
        $fax = $mvc->getVerbal($rec, 'fax');
        $eml = $mvc->getVerbal($rec, 'email');
        
        // phonesBox
        $row->phonesBox .= $mob ? "<div class='mobile'>{$mob}</div>" : "";
        $row->phonesBox .= $tel ? "<div class='telephone'>{$tel}</div>" : "";
        $row->phonesBox .= $fax ? "<div class='fax'>{$fax}</div>" : "";
        $row->phonesBox .= $eml ? "<div class='email'>{$eml}</div>" : "";
        
        $row->title = $row->name;
        
        $row->title .= ($row->country ? ", " : "") . $country;
        
        $birthday = trim($mvc->getVerbal($rec, 'birthday'));
        
        if($birthday) {
            $row->title .= "&nbsp;&nbsp;<div style='float:right'>{$birthday}</div>";
            
            if(strlen($birthday) == 5) {
                $dateType = 'Рожден&nbsp;ден';
            } else {
                if($rec->salutation == 'mr') {
                    $dateType = 'Роден';
                } elseif($rec->salutation == 'mrs' || $rec->salutation == 'miss') {
                    $dateType = 'Родена';
                } else {
                    $dateType = 'Роден(а)';
                }
            }
            $row->nameList .= "<div style='font-size:0.8em;margin-top:5px;'>$dateType:&nbsp;{$birthday}</div>";
        } elseif($rec->egn) {
            $egn = $mvc->getVerbal($rec, 'egn');
            $row->title .= "&nbsp;&nbsp;<div style='float:right'>{$egn}</div>";
            $row->nameList .= "<div style='font-size:0.8em;margin-top:5px;'>{$egn}</div>";
        }
        
        if($rec->buzCompanyId && crm_Companies::haveRightFor('single', $rec->buzCompanyId)) {
            $row->buzCompanyId = ht::createLink($mvc->getVerbal($rec, 'buzCompanyId'), array('crm_Companies', 'single', $rec->buzCompanyId));
            $row->nameList .= "<div>{$row->buzCompanyId}</div>";
        }
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    function on_AfterSave($mvc, $id, $rec)
    {
        if($rec->groupList) {
            $mvc->updateGroupsCnt = TRUE;
        }
        
        $mvc->updatedRecs[$id] = $rec;
        
        $mvc->updateRoutingRules($rec);
    }
    
    
    /**
     * Рутинни действия, които трябва да се изпълнят в момента преди терминиране на скрипта
     */
    function on_Shutdown($mvc)
    {
        if($mvc->updateGroupsCnt) {
            $mvc->updateGroupsCnt();
        }
        
        if(count($mvc->updatedRecs)) {
            foreach($mvc->updatedRecs as $id => $rec) {
                
                // Обновяваме рожденните дни
                crm_Calendar::updateEventsPerObject($mvc, $id);
                
                //                $mvc->updateRoutingRules($rec);
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function on_AfterDelete($mvc, $numDelRows, $query, $cond)
    {
        foreach($query->getDeletedRecs() as $id => $rec) {
            crm_Calendar::deleteEventsPerObject($mvc, $id);
            
            // изтриваме всички правила за рутиране, свързани с визитката
            email_Router::removeRules('person', $rec->id);
        }
    }
    
    
    /**
     * Обновява информацията за количеството на визитките в групите
     */
    function updateGroupsCnt()
    {
        $query = $this->getQuery();
        
        while($rec = $query->fetch()) {
            $keyArr = type_Keylist::toArray($rec->groupList);
            
            foreach($keyArr as $groupId) {
                $groupsCnt[$groupId]++;
            }
        }
        
        if(count($groupsCnt)) {
            foreach($groupsCnt as $groupId => $cnt) {
                $groupsRec = new stdClass();
                $groupsRec->personsCnt = $cnt;
                $groupsRec->id = $groupId;
                crm_Groups::save($groupsRec, 'personsCnt');
            }
        }
    }
    
    
    /**
     * Връща масив със събития за посочения човек
     */
    function getCalendarEvents_($objectId, $years = array())
    {
        // Ако липсва, подготвяме масива с годините, за които ще се запише събитието
        if(!count($years)) {
            $cYear = date("Y");
            $years = array($cYear, $cYear + 1, $cYear + 2);
        }
        
        $rec = $this->fetch($objectId);
        
        // Добавяме рождените дни, ако са посочени
        list($d, $m, $y) = explode('-', $rec->birthday);
        
        if($d>0 && $m>0) {
            foreach($years as $y) {
                $calRec = new stdClass();
                $calRec->date = "{$y}-{$m}-{$d}";
                $calRec->type = 'birthday';
                $res[] = $calRec;
            }
        }
        
        // Добавяме изтичанията на личните документи....
        
        return $res;
    }
    
    
    /**
     * Връща вербалното име на посоченото събитие за посочения обект
     */
    function getVerbalCalendarEvent($type, $objectId, $date)
    {
        $rec = $this->fetch($objectId);
        
        if($rec) {
            switch($type) {
                case 'birthday' :
                    list($d, $m, $y) = explode('-', $rec->birthday);
                    
                    if($y>0) {
                        $old = dt::mysql2verbal($date, 'Y') - $y;
                    }
                    $person = ht::createLink($rec->name, array($this, 'single', $objectId));
                    
                    if($old>70) {
                        $event = new ET("$old г. от рождението на [#1#]", $person);
                    } else {
                        $event = new ET("ЧРД [#1#] на $old г.", $person);
                    }
                    break;
            }
        }
        
        return $event;
    }
    
    
    /**
     * Ако е празна таблицата с контактите я инициализираме с един нов запис
     * Записа е с id=1 и е с данните от файла bgerp.cfg.php
     *
     * @param unknown_type $mvc
     * @param unknown_type $res
     */
    function on_AfterSetupMvc($mvc, &$res)
    {
        if(Request::get('Full')) {
            
            $query = $mvc->getQuery();
            
            while($rec = $query->fetch()) {
                if($rec->state == 'active') {
                    $rec->state = 'closed';
                }
                
                $mvc->save($rec, 'state');
            }
        }
        
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('pictures', 'Снимки', 'jpg,jpeg', '3MB', 'user', 'every_one');
    }
    
    /**
     * ИМПЛЕМЕНТАЦИЯ на интерфейса @see crm_PersonAccRegIntf
     */
    
    
    /**
     * Връща запис-перо съответстващо на лицето
     *
     * @see crm_PersonAccRegIntf::getItemRec()
     */
    static function getItemRec($objectId)
    {
        $self = cls::get(__CLASS__);
        $result = NULL;
        
        if ($rec = $self->fetch($objectId)) {
            $result = (object)array(
                'num' => $rec->id,
                'title' => $rec->name,
                'features' => 'foobar' // @todo!
            );
        }
        
        return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::getLinkToObj
     * @param int $objectId
     */
    static function getLinkToObj($objectId)
    {
        $self = cls::get(__CLASS__);
        
        if ($rec = $self->fetch($objectId)) {
            $result = ht::createLink($rec->name, array($self, 'Single', $objectId));
        } else {
            $result = '<i>неизвестно</i>';
        }
        
        return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     * @param int $objectId
     */
    static function itemInUse($objectId)
    {
        // @todo!
    }
    
    /****************************************************************************************
     *                                                                                      *
     *  Реализиране на интерфейса crm_CompanyExpandIntf                                     *
     *                                                                                      *
     ****************************************************************************************/
    
    
    /**
     * Подготва (извлича) данните за представителите на фирмата
     */
    function prepareCompanyExpandData(&$data)
    {
        $query = $this->getQuery();
        $query->where("#buzCompanyId = {$data->masterId}");
        
        while($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $row = $data->rows[$rec->id] = $this->recToVerbal($rec, 'name,mobile,tel,email,buzEmail,buzTel');
            $row->name = ht::createLink($row->name, array($this, 'Single', $rec->id));
            
            if(!$row->buzTel) $row->buzTel = $row->tel;
            
            if(!$row->buzEmail) $row->buzEmail = $row->email;
        }
    }
    
    
    /**
     * Рендира данните
     */
    function renderCompanyExpandData($data)
    {
        if(!count($data->rows)) return '';
        
        $tpl = new ET("<fieldset class='detail-info'>
                            <legend class='groupTitle'>" . tr('Представители') . "</legend>
                                <div class='groupList,clearfix21'>
                                 [#persons#]
                            </div>
                            <!--ET_BEGIN regCourt--><div><b>[#regCourt#]</b></div><!--ET_END regCourt--> 
                         </fieldset>");
        
        foreach($data->rows as $row) {
            $tpl->append("<div style='padding:5px; float:left;min-width:300px;'>", 'persons');
            
            $tpl->append("<div style='font-weight:bold;'>{$row->name}</div>", 'persons');
            
            if($row->mobile) {
                $tpl->append("<div class='mobile'>{$row->mobile}</div>", 'persons');
            }
            
            if($row->buzTel) {
                $tpl->append("<div class='telephone'>{$row->buzTel}</div>", 'persons');
            }
            
            if($row->email) {
                $tpl->append("<div class='email'>{$row->email}</div>", 'persons');
            }
            
            $tpl->append("</div>", 'persons');
            
            if ($i ++ % 2 == 1) {
                $tpl->append("<div class='clearfix21'></div>", 'persons');
            }
        }
        
        return $tpl;
    }
    
    
    /**
     * Обновява правилата за рутиране според наличните данни във визитката
     *
     * @param stdClass $rec
     */
    static function updateRoutingRules($rec)
    {
        if ($rec->state == 'rejected') {
            // Визитката е оттеглена - изтриваме всички правила за рутиране, свързани с нея
            email_Router::removeRules('person', $rec->id);
        } else {
            if ($rec->buzEmail) {
                // Лицето има служебен имейл. Ако има и фирма, регистрираме служебния имейл на  
                // името на фирмата
                if ($rec->buzCompanyId) {
                    static::createRoutingRules($rec->buzEmail, $rec->buzCompanyId, 'company');
                }
                
                static::createRoutingRules($rec->buzEmail, $rec->id, 'person');
            }
            
            if ($rec->email) {
                // Регистрираме личния имейл на името на лицето
                static::createRoutingRules($rec->email, $rec->id, 'person');
            }
        }
    }
    
    /**
     * Създава `From` и `Doman` правила за рутиране след запис на визитка
     *
     * Използва се от @link crm_Persons::updateRoutingRules() като инструмент за добавяне на
     * правила според различни сценарии на базата на данните на визитката
     *
     * @access protected
     * @param string $email
     * @param int $objectId
     * @param string $objectType person | company
     */
    protected static function createRoutingRules($email, $objectId, $objectType)
    {
        // Приоритетът на всички правила, генериране след запис на визитка е нисък и намаляващ с времето
        $priority = email_Router::dateToPriority(dt::now(), 'low', 'desc');
        
        // Създаване на `From` правило
        email_Router::saveRule(
            (object)array(
                'type' => email_Router::RuleFrom,
                'key' => email_Router::getRoutingKey($email, NULL, email_Router::RuleFrom),
                'priority' => $priority,
                'objectType' => $objectType,
                'objectId' => $objectId
            )
        );
        
        // Създаване на `Domain` правило
        if ($key = email_Router::getRoutingKey($email, NULL, email_Router::RuleDomain)) {
            // $key се генерира само за непублични домейни (за публичните е FALSE), така че това 
            // е едновременно индиректна проверка дали домейнът е публичен.
            email_Router::saveRule(
                (object)array(
                    'type' => email_Router::RuleDomain,
                    'key' => $key,
                    'priority' => $priority,
                    'objectType' => $objectType,
                    'objectId' => $objectId
                )
            );
        }
    }
    
    
    /**
     * Връща данните на лицето
     * @param integer $id    - id' то на записа
     * @param email   $email - Имейл
     *
     * return object
     */
    static function getContragentData($id)
    {      
        //Вземаме данните
        $person = crm_Persons::fetch($id);
        
        //Заместваме и връщаме данните
        if ($person) {
            $contrData->recipient = crm_Persons::getVerbal($person, 'buzCompanyId');
            $contrData->attn = $person->name;
            $contrData->tel  = ($person->mobile) ? $person->mobile : $person->tel;
            $contrData->fax = $person->fax;
            $contrData->country = crm_Persons::getVerbal($person, 'country');
            $contrData->pCode = $person->pCode;
            $contrData->place = $person->place;
            $contrData->address = $person->address;
            $contrData->email = $person->email;
            
            $contrData->salutation = mb_strtolower(crm_Persons::getVerbal($person, 'salutation'));      
        }
        
        return $contrData;
    }
}