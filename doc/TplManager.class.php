<?php 


/**
 * Мениджър за шаблони, които ще се използват от документи.
 * Добавя възможноста спрямо шаблона да се скриват/показват полета от мастъра
 * За целта е в класа и неговите детайли трябва да се дефинира '$toggleFields',
 * където са изброени незадължителните полета които могат да се скриват/показват.
 * Задават се във вида: "field1=caption1,field2=caption2"
 * 
 * Ако избрания мениджър има тези полета, то отдоло на формата се появява възможност за
 * избор на кои от тези незадължителни полета да се показват във въпросния шаблон. Ако никое
 * не е избрано. То се показват всичките
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_TplManager extends core_Master
{
    
    
    /**
     * Заглавие
     */
    public $title = "Мениджър на шаблони за документи";
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Шаблон";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_SaveAndNew, plg_Modified, doc_Wrapper, plg_RowTools, plg_State2';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,admin';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'ceo,admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin';


    /**
     * Кой може да го изтрива?
     */
    public $canDelete = 'ceo,admin';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,admin';

	
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,admin';


    /**
     * Кой има право да променя системните данни?
     */
    public $canEditsysdata = 'ceo,admin';

    
    /**
     * Файл с шаблон за единичен изглед
     */
    var $singleLayoutFile = 'doc/tpl/SingleTemplateLayout.shtml';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, name, docClassId, createdOn, createdBy, state';

    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Име, mandatory, width=100%');
        $this->FLD('docClassId', 'class(interface=doc_DocumentIntf,select=title,allowEmpty)', "caption=Документ, width=100%,mandatory,silent");
        $this->FLD('lang', 'varchar(2)', 'caption=Език,notNull,defValue=bg,value=bg,mandatory,width=2em');
        $this->FLD('content', 'text', "caption=Текст,column=none, width=100%,mandatory");
        $this->FLD('originId', 'key(mvc=doc_TplManager)', "input=hidden,silent");
        $this->FLD('hash', 'varchar', "input=none");
        
        // Полета които ще се показват в съответния мениджър и неговите детайли
        $this->FLD('toggleFields', 'blob(serialize,compress)', 'caption=Полета за скриване,input=none');
        
        // Уникален индекс
        $this->setDbUnique('name');
    }
    
    
    /**
     * След потготовка на формата за добавяне / редактиране
     */
    function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	
    	// Ако шаблона е клонинг
    	if($originId = $form->rec->originId){
    		
    		// Копират се нужните данни от ориджина
    		expect($origin = static::fetch($originId));
    		$form->setDefault('docClassId', $origin->docClassId);
    		$form->setDefault('lang', $origin->lang);
    		$form->setDefault('content', $origin->content);
    		$form->setDefault('toggleFields', $origin->toggleFields);
    	}
    	
    	// При смяна на документа се рефрешва формата
    	if(empty($form->rec->id)){
        	$form->addAttr('docClassId', array('onchange' => "addCmdRefresh(this.form);this.form.submit();"));
    	}
    	
    	// Ако има избран документ, се подготвят допълнителните полета
    	if($form->rec->docClassId){
    		$DocClass = cls::get($form->rec->docClassId); 
    		$this->prepareToggleFields($DocClass, $form);
    	}
    }
    
    
    /**
     * За мастър документа и всеки негов детайл се генерира поле за избор кои от
     * незадължителните му полета да се показват
     * 
     * @param core_Mvc $DocClass - класа на който е прикачен плъгина
     * @param core_Form $form - формата
     */
	private function prepareToggleFields(core_Mvc $DocClass, core_Form &$form)
    {
    	// Слагане на поле за избор на полета от мастъра
    	$this->setTempField($DocClass, $form);
    	
    	// За вски детайл (ако има) се създава поле
    	$details = arr::make($DocClass->details);
        if($details){
        	foreach ($details as $d){
        		$Dclass = cls::get($d);
        		$this->setTempField($Dclass, $form);
        	}
        }
    }
    
    
    /**
     * Ф-я създаваща FNC поле към формата за избор на кои от незадължителните му полета
     * да се показват. Използва 'toggleFields' от документа, за генериране на полетата
     * 
     * @param core_Mvc $DocClass - класа за който се създава полето
     * @param core_Form $form - формата
     */
    private function setTempField(core_Mvc $DocClass, core_Form &$form)
    {
    	// Ако са посочени незадължителни полета
    	if($DocClass->toggleFields){
    		
    		// Създаване на FNC поле със стойности идващи от 'toggleFields'
    		$fldName = ($DocClass instanceof core_Master) ? 'masterFld' : $DocClass->className;
    		$fields = array_keys(arr::make($DocClass->toggleFields));
    		$form->FNC($fldName, "set({$DocClass->toggleFields})", "caption=Полета за показване->{$DocClass->title},input,columns=3,tempFld,silent");
    		
    		// Стойност по подразбиране
    		if(isset($form->rec->$fldName)){
    			$default = $form->rec->$fldName;
    		} elseif(isset($form->rec->toggleFields) && array_key_exists($fldName, $form->rec->toggleFields)){
    			$default = $form->rec->toggleFields[$fldName];
    		} else {
    			$default = implode(',', $fields);
    		}
    		
    		$form->setDefault($fldName, $default);
    	}
    }
    
    
    /**
     * Проверка след изпращането на формата
     */
    function on_AfterInputEditForm($mvc, &$form)
    { 
    	if ($form->isSubmitted()){
    		
    		// Проверка дали избрания клас поддържа 'doc_plg_TplManager'
    		$plugins = cls::get($form->rec->docClassId)->getPlugins();
    		if(empty($plugins['doc_plg_TplManager'])){
    			$form->setError('docClassId', "Избрания клас трябва да поддържа 'doc_plg_TplManager'!");
    		}
    		
    		// Ако шаблона е клонинг
    		if($originId = $form->rec->originId){
    			
    			$origin = static::fetch($originId);
    			$new = preg_replace("/\s+/", "", $form->rec->content);
    			$old = preg_replace("/\s+/", "", $origin->content);
    			
    			// Ако клонинга е за същия документ като ориджина, и няма промяна
    			// в съдържанието се слага предупреждение
    			if(empty($form->rec->id) && $origin->docClassId == $form->rec->docClassId && $new == $old){
    				$form->setWarning('content' , 'Клонирания шаблон е със същото съдържание като оригинала!');
    			}
    		}
    		
    		// Ако има временни полета, то данните се обработват
    		$tempFlds = $form->selectFields("#tempFld");
    		if(count($tempFlds)){
    			$this->prepareDataFld($form, $tempFlds);
    		}
    	}
    }
    
    
    /**
     * Всяко едно допълнително поле се обработва и информацията
     * от него се записва в блоб полето
     * 
     * @param core_Form $form - формата
     * @param array $fields - FNC полетата
     */
    private function prepareDataFld(core_Form &$form, $fields)
    {
    	$rec = &$form->rec;
    	
    	// За всяко едно от опционалните полета
    	$toggleFields = array();
    	foreach ($fields as $name => $fld){
    		$toggleFields[$name] = $rec->$name;
    	}
    	
    	// Подготвяне на масива за сериализиране
    	$rec->toggleFields = $toggleFields;
    }
    
    
    /**
     * Връща подадения шаблон
     * @param int $id - ид на шаблон
     * @return core_ET $tpl - шаблона
     */
    public static function getTemplate($id)
    {
    	expect($content = static::fetchField($id, 'content'));
    	
    	return new ET(tr("|*" . $content));
    }
    
    
    /**
     * Връща всички активни шаблони за посочения мениджър
     * @param int $classId - ид на клас
     * @return array $options - опции за шаблоните на документа
     */
    public static function getTemplates($classId)
    {
    	$options = array();
    	expect(core_Classes::fetch($classId));
    	
    	// Извличане на всички активни шаблони за документа
    	$query = static::getQuery();
    	$query->where("#docClassId = {$classId}");
    	$query->where("#state = 'active'");
    	
    	while($rec = $query->fetch()){
    		$options[$rec->id] = $rec->name;
    	}
    	
    	ksort($options);
    	
    	return $options;
    }
    
    
    /**
     * Добавя шаблон
     * 
     * @param mixed $object - Обект или масив
     * @param int $added - брой добавени шаблони
     * @param int $updated - брой обновени шаблони
     * @param int $skipped - брой пропуснати шаблони
     */
    public static function addOnce($object, &$added = 0, &$updated = 0, &$skipped = 0)
    {
    	$object = (object)$object;
    	
    	// Ако има старо име на шаблона
    	if($object->oldName){
    		
    		// Извличане на записа на стария шаблон
    		$exRec = static::fetch("#name = '{$object->oldName}'");
    	}
    	
    	// Ако няма старо име проверка имали шаблон с текущото име
    	if(!$exRec){
    		$exRec = static::fetch("#name = '{$object->name}'");
    	}
    	
    	if($exRec){
    		$object->id = $exRec->id;
    		$object->hash = $exRec->hash;
    		
    		// Обновяване на името
    		$object->name = $object->name;
    	}
    	
    	// Ако файла на шаблона не е променян, то записа не се обновява
    	expect($fileHash = md5_file(getFullPath($object->content)));
    	if(empty($object->oldName) && isset($object->hash) && $object->hash == $fileHash){
    		$skipped++;
    		return;
    	}
    	
    	$object->hash = $fileHash;
    	$object->content = getFileContent($object->content);
    	$object->createdBy = -1;
    	$object->state = 'active';
    	
    	static::save($object);
    	($object->id) ? $updated++ : $added++;
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    function on_BeforeSave(&$invoker, &$id, &$rec, &$fields = NULL)
    {
    	// Ако записа е вкаран от сетъпа променяме за модифициран от да е @system
    	if($rec->_modifiedBy){
    		$rec->modifiedBy = $rec->_modifiedBy;
    	}
    }
    
    
    /**
     * След подготовка на единичния изглед
     */
	function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$data->toolbar->addBtn('Всички', array('doc_TplManager', 'list'), 'caption=Всички шаблони,ef_icon=img/16/view.png');
    	
    	// Добавяне на бутон за клониране
    	if($mvc->haveRightFor('add')){
    		$data->toolbar->addBtn('Клониране', array('doc_TplManager', 'add', 'originId' => $data->rec->id), 'ef_icon=img/16/copy16.png,title=Клониране на шаблона');
    	}
    }
    
    
	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'delete' && isset($rec)){
    		
    		// Ако шаблона е използван в някой документ, не може да се трие
    		if(cls::get($rec->docClassId)->fetch("#template = {$rec->id}")){
    			$res = 'no_one';
    		}
    	}
    	
    	if(($action == 'edit'  || $action == 'changestate') && isset($rec)){
    		if($rec->createdBy == -1){
    			$res = 'no_one';
    		}
    	}
    }
}  