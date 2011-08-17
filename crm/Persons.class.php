<?php
/**
 * Физически лица
 * 
 * Мениджър на физическите лица
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    crm
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
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
                         
                        // Интерфейс за разширяване на информацията за дадена фирма
                        'crm_CompanyExpanderIntf',
                        
                        // Интерфайс за всякакви счетоводни пера
                        'acc_RegisterIntf',
    );

    /**
     *  Заглавие на мениджъра
     */
    var $title = "Лица";
    

    /**
     *  Плъгини и MVC класове, които се зареждат при инициализация
     */
    var $loadList = 'plg_Created, plg_RowTools,  plg_Printing,Companies=crm_Companies,
                     Groups=crm_Groups, crm_Wrapper, plg_SaveAndNew, plg_PrevAndNext,
                     plg_Sorting, fileman_Files, recently_Plugin,crm_Companies,plg_Search,
                     plg_AccRegistry';
    

    /**
     *  Полета, които се показват в листови изглед
     */
    var $listFields = 'id,nameList=Име,addressBox=Адрес,phonesBox=Комуникации';
    

    /**
     *  Полета по които се прави пълнотестово търсене от плъгина plg_Search
     */
    var $searchFields = 'name,egn,birthday,country,place';
    
    
    /**
     * Права за писане
     */
    var $canWrite = 'crm,admin';
    
    
    /**
     *  Права за четене
     */
    var $canRead = 'crm,admin';
    
    
    /**
     * @var crm_Groups
     */
    var $Groups;
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        // Име на лицето
        $this->FLD('salutation', 'enum(,mr=Г-н,mrs=Г-жа,miss=Г-ца)', 'caption=Обръщение');
        $this->FLD('name', 'varchar(255)', 'caption=Име,width=100%,mandatory,remember=info');
        
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
        $this->FLD('buzEmail', 'email', 'caption=Служебни комуникации->Е-мейл,width=100%');
        $this->FLD('buzTel', 'drdata_PhoneType', 'caption=Служебни комуникации->Телефони,width=100%');
        $this->FLD('buzFax', 'drdata_PhoneType', 'caption=Служебни комуникации->Факс,width=100%');
        $this->FLD('buzAddress', 'varchar', 'caption=Служебни комуникации->Адрес,width=100%');
        
        // Лични комуникации
        $this->FLD('email', 'email', 'caption=Лични комуникации->Е-мейл,width=100%');
        $this->FLD('tel', 'drdata_PhoneType', 'caption=Лични комуникации->Телефони,width=100%');
        $this->FLD('mobile', 'drdata_PhoneType', 'caption=Лични комуникации->Мобилен,width=100%');
        $this->FLD('fax', 'drdata_PhoneType', 'caption=Лични комуникации->Факс,width=100%');
        $this->FLD('website', 'varchar(255)', 'caption=Лични комуникации->Сайт/Блог,width=100%');

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
            $data->query->orderBy('name=DESC');
        } elseif($data->listFilter->rec->order == 'last') {
            $data->query->orderBy('createdOn=DESC');
        }
        
        if($data->listFilter->rec->alpha) {
            if($data->listFilter->rec->alpha{0} == '0') {
                $cond = "#name NOT REGEXP '^[a-zA-ZА-Яа-я]'";
            } else {
                $alphaArr = explode('-', $data->listFilter->rec->alpha);
                $cond = array();
                $i = 1;
                
                foreach($alphaArr as $a) {
                    $cond[0] .= ($cond[0]?' OR ':'') .
                    "(LOWER(#name) LIKE LOWER('[#{$i}#]%'))";
                    $cond[$i] = $a;
                    $i++;
                }
            }
            
            $data->query->where($cond);
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
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай');
        
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
        $data->toolbar->removeBtn('*');
        $data->toolbar->addBtn('Ново лице', array('Ctr' => $this, 'Act' => 'Add', 'ret_url' => TRUE));
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
            $form->setDefault('country', $Countries->fetchField("#commonName = '" . BGERP_OWN_COMPANY_COUNTRY . "'", 'id' ));
        }
    }
    
    
    /**
     * Манипулации със заглавието
     *
     * @param core_Mvc $mvc
     * @param core_Et $tpl
     * @param stdClass $data
     */
    function on_BeforeRenderListTitle($mvc, $tpl, $data)
    {
        if($data->listFilter->rec->groupId) {
            $data->title = "Лица в групата|* \"<b style='color:green'>" .
            $this->Groups->getTitleById($data->groupId) . "</b>\"";
        } elseif($data->listFilter->rec->search) {
            $data->title = "Лица отговарящи на филтъра|* \"<b style='color:green'>" .
            type_Varchar::toVerbal($data->listFilter->rec->search) .
            "</b>\"";
        } elseif($data->listFilter->rec->alpha) {
            if($data->listFilter->rec->alpha{0} == '0') {
                $data->title = "Лица, които не започват с букви";
            } else {
                $data->title = "Лица започващи с буквите|* \"<b style='color:green'>{$data->listFilter->rec->alpha}</b>\"";
            }
        } elseif(!$data->title) {
            $data->title = "Всички лица";
        }
    }
    
    
    /**
     * Изпълнява се след въвеждането на данните от заявката във формата
     */
    function on_AfterInputEditForm($mvc, $form)
    {
        $rec = $form->rec;
        
        if( isset($rec->egn) && ($rec->birthday == '??-??-????') ) {
            try {
                $Egn = new drdata_BulgarianEGN($rec->egn);
            } catch( Exception $e ) {
                $err = $e->getMessage();
            }
            
            if(!$err) {
                $rec->birthday = $Egn->birth_day . "-" . $Egn->birth_month . "-" . $Egn->birth_year;
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
        $mvc = $this;
        
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphavit'));
        
        $alpha = Request::get('alpha');
        
        $selected = 'none';
        
        $letters = arr::make('0-9,А-A,Б-B,В-V=В-V-W,Г-G,Д-D,Е-D,Ж-J,З-Z,И-I,Й-J,К-Q=К-K-Q,' .
        'Л-L,М-M,Н-N,О-O,П-P,Р-R,С-S,Т-T,У-U,Ф-F,Х-H=Х-X-H,Ц-Ч,Ш-Щ,Ю-Я', TRUE);
        
        foreach($letters as $a => $set) {
            $tabs->TAB($a, '|*' . str_replace('-', '<br>', $a), array($mvc, 'list', 'alpha' => $set));
            
            if($alpha == $set) {
                $selected = $a;
            }
        }
        
        $tpl = $tabs->renderHtml($tpl, $selected);
        
        //$tpl->prepend('<br>');
        
        return $tpl;
    }
    
    
    /**
     * Шаблон за визитката
     *
     * @return core_Et $tpl
     */
    function renderSingleLayout_($data)
    {
        // BEGIN Prepare groups for a contact
        $groupList = type_Keylist::toArray($data->rec->groupList);
        
        $groupListVerbal = array();
        
        foreach ($groupList as $group) {
            $groupListVerbal[] = $this->Groups->fetchField($group, 'name');
        }
        
        $data->rec->groupListVerbal = $groupListVerbal;
        // END Prepare groups for a contact
        
        $viewContact = cls::get('crm_tpl_SinglePersonLayout', array('data' => $data));
        
        return $viewContact;
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
        $row->nameList = Ht::createLink(type_Varchar::toVerbal($rec->name), array($this, 'single', $rec->id));
         
        // Fancy ефект за картинката
        $Fancybox = cls::get('fancybox_Fancybox');
        
        $tArr = array(200, 150);
        $mArr = array(600, 450);
        
        if($rec->photo) {
            $row->image = $Fancybox->getImage($rec->photo, $tArr, $mArr);
        } else {
            $row->image = "<img class=\"hgsImage\" src=" . sbf('img/noimage120.gif'). " alt='no image'>";
        }
        
        $country = tr($mvc->getVerbal($rec, 'country'));
        $pCode   = $mvc->getVerbal($rec, 'pCode');
        $place   = $mvc->getVerbal($rec, 'place');
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
        
        $egn = $mvc->getVerbal($rec, 'egn');
        
        $row->title .= ($egn ? "&nbsp;&nbsp;<div style='float:right'>{$egn}</div>" : "");
        
        $row->nameList .= ($egn ? "<div style='font-size:0.8em;margin-top:5px;'>{$egn}</div>" : "");
        
        if($rec->buzCompanyId && $this->Companies->haveRightFor('single', $rec->buzCompanyId) ) {  
            $row->buzCompanyId = ht::createLink($mvc->getVerbal($rec, 'buzCompanyId'), array('crm_Companies', 'single', $rec->buzCompanyId));
            $row->nameList .= "<div>{$row->buzCompanyId}</div>";
        }
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function on_AfterSave($mvc, $id, $rec)
    {
        $mvc->updateGroupsCnt();
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
                $this->Groups->save($groupsRec, 'personsCnt');
            }
        }
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
    	$result = null;
    	
    	if ($rec = $self->fetch($objectId)) {
    		$result = (object)array(
    			'num' => $rec->id,
    			'title' => $rec->name,
    			'features' => 'foobar' // @todo!
    		);
    	}
    	
    	return $result;
    }
    
    static function getLinkToObj($objectId)
    {
    	$self = cls::get(__CLASS__);
    	
    	if ($rec  = $self->fetch($objectId)) {
    		$result = ht::createLink($rec->name, array($self, 'Single', $objectId)); 
    	} else {
    		$result = '<i>неизвестно</i>';
    	}
    	
    	return $result;
    }
    
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
    function prepareCompanyExpandData(&$data, $companyRec)
    {
        $query = $this->getQuery();
        $query->where("#buzCompanyId = {$companyRec->id}");
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
        $table = cls::get('core_TableView');
                
        $tpl = $table->get($data->rows, array('name' => 'Представители->Име', 
                                              'mobile' => 'Представители->Мобилен', 
                                              'buzTel' => 'Представители->Телефон',
                                              'buzEmail' => 'Представители->Е-мейл'));
        $tpl->prepend("<br>");

        return $tpl;

    }


 }