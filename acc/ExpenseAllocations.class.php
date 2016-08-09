<?php



/**
 * Документ за Разпределяне на разходи
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_ExpenseAllocations extends core_Master
{
    
	
    /**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Заглавие на мениджъра
     */
    public $title = "Разпределяне на разходи";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools2, acc_Wrapper, doc_DocumentPlg, plg_Printing, acc_plg_DocumentSummary, plg_Search, doc_ActivatePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = "title=Документ,originId=Към документ,folderId,createdBy,createdOn";
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'acc, ceo, purchase';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'acc, ceo, purchase';
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Разпределяне на разходи';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Eal";
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'acc, ceo, purchase';
    
    
    /**
     * Детайли на документа
     */
    public $details = 'acc_ExpenseAllocationDetails';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    public $singleLayoutFile = 'acc/tpl/SingleLayoutExpenseAllocation.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'originId,folderId';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn';
    
    
    /**
     * Работен кеш
     */
    protected static $cache = array();
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = &$form->rec;
    	expect($origin = doc_Containers::getDocument($rec->originId));
    	$form->info = tr("Разпределяне на разходи по редовете на") . " " . $origin->getLink(0);
    	
    	// Редовете за разпределяне
    	$products = $origin->getRecsForAllocation();
    	expect(count($products));
    	
    	$count = 1;
    	foreach ($products as $key => $product){
    		
    		// Вербалното име на реда е и секцията във формата
    		$name = acc_ExpenseAllocationDetails::getOriginRecTitle($product, $count);
    			
    		// Поставяме полета за всеки артикул за разпределяне
    		$form->FLD("originRecId|{$key}", 'int', "input=hidden,silent");
    		$form->FLD("productId|{$key}", 'key(mvc=cat_Products)', "input=hidden,silent");
    		$form->FLD("packagingId|{$key}", 'key(mvc=cat_UoM)',"input=hidden,silent");
    		$form->FLD("quantityInPack|{$key}", 'double', "input=hidden,silent");
    		$form->FLD("quantity|{$key}", 'double', "caption=|*{$name}->К-во,silent");
    		$form->FLD("expenseItemId|{$key}", 'acc_type_Item(select=titleNum,allowEmpty,lists=600,allowEmpty)', "input,caption=|*{$name}-> ,inlineTo=quantity|{$key},placeholder=Избор на разход,silent");

    		// Попълваме полетата с дефолтите
    		foreach (array('productId', 'packagingId', 'quantityInPack', 'quantity', 'originRecId') as $fld){
    			$form->setDefault("{$fld}|{$key}", $product->{$fld});
    		}
    		
    		// Увеличаваме брояча
    		$count++;
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->originId = doc_Containers::getDocument($rec->originId)->getHyperLink(TRUE);
    	
    	if(isset($fields['-list'])){
    		$row->title = $mvc->getLink($rec->id, 0);
    	}
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
    	$rec = $data->form->rec;
    	if(isset($rec->originId)){
    		$origin = doc_Containers::getDocument($rec->originId);
    		$data->form->title = core_Detail::getEditTitle($origin->className, $origin->that, $mvc->singleTitle, $rec->id);
    	}
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
    	$arr = (array)$rec;
    	
    	// Какво ще записваме в детайла
    	$recsToSave = array();
    	
    	// За всеки запис
    	foreach ($arr as $k => $v){
    		
    		 // Ако има динамични полета във формата
    		 if(strpos($k, "|") != FALSE){
    		 	$split = explode('|', $k);
    		 	$recsToSave[$split[1]][$split[0]] = $v;
    		 }
    	}
    	
    	// За всяко поле
    	foreach ($recsToSave as $i => $a){
    		
    		// Ако за съответния ред е избран разход, записваме го
    		if(empty($a['expenseItemId'])) continue;
    		$dRec = (object)$a;
    		$dRec->allocationId = $rec->id;
    		
    		// Запис на ред от детайла
    		acc_ExpenseAllocationDetails::save($dRec);
    	}
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	$data->toolbar->removeBtn('btnAdd');
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
    	return FALSE;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    protected static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'add' && isset($rec)){
    		
    		// Трябва документа да има ориджин
    		if(!isset($rec->originId)){
    			$requiredRoles = 'no_one';
    			return;
    		}
    		
    		// Ако към ориджина има вече документ за разпределяне на разходи, не може да се добавя
    		if(acc_ExpenseAllocations::fetchField("#originId = {$rec->originId} AND #id != '{$rec->id}' AND #state != 'rejected'")){
    			//$requiredRoles = 'no_one';
    			//return;
    		}
    		
    		$origin = doc_Containers::getDocument($rec->originId);
    		
    		// Ако няма за разпределяне, не може да се добавя
    		$recsToAllocate = $origin->getRecsForAllocation(1);
    		
    		if(!count($recsToAllocate)){
    			$requiredRoles = 'no_one';
    			return;
    		}
    		
    		//... и потребителя да има достъп до него
    		if(!$origin->haveRightFor('single')){
    			$requiredRoles = 'no_one';
    			return;
    		}
    		
    		//... и да има доспусимия интерфейс
    		if(!$origin->haveInterface('acc_ExpenseAllocatableIntf')){
    			$requiredRoles = 'no_one';
    			return;
    		}
    		
    		//... и да е активен
    		$state = $origin->fetchField('state');
    		if($state != 'active'){
    			//$requiredRoles = 'no_one';
    			//return;
    		}
    		
    		//... и да е в отворен период
    		$valior = $origin->fetchField($origin->valiorFld);
    		$period = acc_Periods::fetchByDate($valior);
    		if(!$period || $period->state == 'closed'){
    			$requiredRoles = 'no_one';
    			return;
    		}
    	}
    	
    	// Не може да се редактира документа след като е създаден, защото няма полета за редакция
    	if($action == 'edit' && isset($rec->id)){
    		$requiredRoles = 'no_one';
    	}
    	
    	// Не може да се възстановява, ако към същия ориджин има друг неоттеглен документ
    	if($action == 'restore' && isset($rec->id)){
    		if(acc_ExpenseAllocations::fetchField("#originId = {$rec->originId} AND #id != '{$rec->id}' AND #state != 'rejected'")){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow($id)
    {
    	$rec = $this->fetchRec($id);
    	 
    	$row = new stdClass();
    	$row->title = $this->getRecTitle($rec);
    	$row->authorId = $rec->createdBy;
    	$row->author = $this->getVerbal($rec, 'createdBy');
    	$row->state = $rec->state;
    	$row->recTitle = $rec->title;
    	 
    	return $row;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$self = cls::get(__CLASS__);
    
    	return "{$self->singleTitle} №{$rec->id}";
    }
    
    
    /**
     * Връща кешираната информацията за реда от оридижина или за целия ориджин
     * 
     * @param int $originId        - ориджин ид
     * @param string $recId        - ид на записа от ориджина, NULL ако искаме всичките
     * @return array|stdClass $res - кешираните записи за реда или за документа;
     */
    public static function getRecsForAllocationFromOrigin($originId, $recId = NULL)
    {
    	// Ако няма запис в кеша, кешираме
    	if(!array_key_exists($originId, static::$cache)){
    		$origin = doc_Containers::getDocument($originId);
    		static::$cache[$originId] = $origin->getRecsForAllocation();
    	}
    	
    	// Връщаме кешираната информация
    	$res = isset($recId) ? static::$cache[$originId][$recId] : static::$cache[$originId];
    	
    	return $res;
    }
}