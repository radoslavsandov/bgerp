<?php



/**
 * Модел  Рецептурник
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_Recipes extends core_Master {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Заглавие
     */
    var $title = 'Рецептурник';
    
    
    /**
     * Заглавие
     */
    var $singleTitle = 'Рецепта за себестойност';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт, productId, uom, groups, state, createdOn, createdBy, modifiedOn, modifiedBy';
    
    
    /**
	 * Коментари на статията
	 */
	var $details = 'cat_RecipeDetails';
	
	
	/**
	 * Брой рецепти на страница
	 */
	var $listItemsPerPage = '40';
	
	
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, cat_Wrapper, cat_RecipeWrapper, doc_DocumentPlg,
    	 plg_Printing, bgerp_plg_Blank, plg_Sorting, plg_Search, doc_ActivatePlg';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от 
     * таблицата.
     */
    var $rowToolsField = 'tools';

    
    /**
     * Икона на единичния обект
     */
    var $singleIcon = 'img/16/legend.png';
    
    
    /**
     * Кой може да чете
     */
    var $canRead = 'cat, admin';
    
    
    /**
     * Кой може да пише
     */
    var $canWrite = 'cat, admin';
    
    
	/**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'productId';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    var $singleLayoutFile = 'cat/tpl/SingleLayoutRecipes.shtml';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "10.1|Каталог";
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('productId', 'key(mvc=cat_Products, select=name)', 'caption=Продукт,width=18em');
    	$this->FLD('uom', 'key(mvc=cat_UoM, select=name, allowEmpty)', 'caption=Мярка,notSorting,width=18em');
    	$this->FLD('info', 'text(rows=4)', 'caption=Информация,width=18em');
    	$this->FLD('groups', 'keylist(mvc=cat_RecipeGroups, select=title)', 'caption=Групи, mandatory');
    	$this->FLD('state','enum(draft=Чернова, active=Активиран, rejected=Оттеглен)', 'caption=Статус, input=none');
    
    	$this->setDbUnique('productId,uom');
    }
    
    
    /**
     * Обработка след изпращане на формата
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()) {
    		$productUom = cat_Products::fetchField($form->rec->productId, 'measureId');
    		if($form->rec->uom) {
    			$productUomRec = cat_UoM::fetch($productUom);
    			$uomRec = cat_UoM::fetch($form->rec->uom);
    			($productUomRec->baseUnitId) ? $baseUnit = $productUomRec->baseUnitId : $baseUnit = $productUom;
    			if($uomRec->baseUnitId != $baseUnit && $uomRec->id != $baseUnit) {
    				$form->setError('uom', 'Избраната мярка не е от същата група като основната мярка на продукта');
    			}
    		} else {
    			$form->rec->uom = $productUom;
    		}
    	}
    }
    
    
    /**
   	 * Обработка на SingleToolbar-a
   	 */
   	static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$data->toolbar->addBtn('Изчисли', array($mvc, 'calcPrice', $data->rec->id), NULL, 'ef_icon=img/16/calculator.png');
    }
   
    
    /**
     * Извлича продуктите които съставят даден продукт
     * @TODO
     * @param int $productId - ид на продукт
     * @param int $quantity - количество от продукта
     * @return array $results - масив с обекти на съставящите го
     * продукти
     */
    public static function getIngredients($productId, $quantity = 1)
    {
    	$results = array();
    	expect($productRec = cat_Products::fetch($productId));
    	expect($rec = static::fetchByProduct($productId, NULL));
    	
    	$query = cat_RecipeDetails::getQuery();
    	$query->where("#recipeId = {$rec->id}");
    	while($detail = $query->fetch()){
    		$obj = new stdClass();
    		$obj->productId = $detail->dProductId;
    		$obj->uom = $detail->dUom;
    		$obj->quantity = $detail->quantity;
    		$results[$detail->id] = $obj;
    	}
    	
    	return $results;
    }
    
    
 	/**
   	 * Обработка на SingleToolbar-a
   	 */
   	static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	// Да предавам филтър формата на екшъна !!!
    	$data->toolbar->addBtn('Калкулиране на себестойности', array($mvc, 'calcAll'), NULL, 'ef_icon=img/16/calculator.png,warning=Наистинали искате да изчислите себестойностите на показваните продукти?');
    }
    
	
    /**
     * 
     * @param unknown_type $id
     */
    function getAllowedProducts($id)
    {
    	$productId = $this->fetchField($id,'productId');
    	
    	$test = '';
    	$notAllowed = array();
    	$qq = static::getQuery();
    	
    	while($rec = $qq->fetch()){
    		$this->searchProduct($rec, $productId, $notAllowed, $test);
    	}
    	bp($test,$notAllowed);
    }
    
    
	function act_test(){
    	$productId = '7';
    	$test = '';
    	$notAllowed = array();
    	$query = $this->getQuery();
    	
    	while($rec = $query->fetch()){
    		$this->searchProduct($rec, $productId, $notAllowed, $test);
    	}
    	bp($test,$notAllowed);
    }
    
    
    function searchProduct($rec, $productId, &$notAllowed, &$test, $path = NULL)
    {
    	/*
    	 * Да го рефакторна
    	$ingredients = cat_Recipes::getIngredients($rec->productId);
    	$test .= "РЕЦЕПТА Nomer: {$rec->id} с продукт {$rec->productId}\n|";
    	if(!$ingredients){
    		$test .= " Няма продукти\n|";
    		return;
    	}
    	$test .= " Има продукти\n|";
    	foreach($ingredients as $ing){
    		$test .= " Съставка: {$ing->productId}\n|";
    		$recipeRec = static::fetchByProduct($ing->productId);
    		if($recipeRec){
    			$path[$recipeRec->id] = $rec->productId;
    			$test .= "  ->рецепта Nomer: {$recipeRec->id} за продукт: {$ing->productId}\n|";
    			$this->searchProduct($recipeRec, $productId, $notAllowed, $test, $path);
    		} else {
    			$test .= "  ->продукта е листо: {$ing->productId}\n|";
    		}
    	}*/
    }
    
    
    /**
     * 
     * Enter description here ...
     * @param unknown_type $productId
     */
    public static function fetchByProduct($productId)
    {
    	$query = static::getQuery();
    	$query->where("#productId = {$productId}");
    	return $query->fetch();
    }
    
    
    /** 
     * Изчислява себестойноста на продукта
     * @TODO да вземам предвид мярката на продукта
     * @param int $productId
     * @param int $quantity
     * @param datetime $datetime
     * @param int $uom - мярка на продукта
     * @return double - цената на продукта
     */
    public static function calcCost($productId, $quantity = 1, $datetime = NULL, $uom = NULL)
    {
    	$price = 0;
    	$conf = core_Packs::getConfig('price');
    	$uomRate = cat_UoM::fetchField($uom, 'baseUnitRatio');
    	
    	$ingredients = static::getIngredients($productId, $quantity);
    
    	if(!$ingredients) {
    		$price = price_ListRules::getPrice($conf->PRICE_LIST_COST, $productId, NULL, $datetime);
    		expect($price, "Проблем при изчислението на себестойноста на продукт: {$productId}");
    		return $quantity * $price * $uomRate;
    	}
    	
    	foreach($ingredients as $ing){
    		$recipeRec = static::fetchByProduct($ing->productId);
    		if($recipeRec){
	    			$pPrice = static::calcCost($ing->productId, $ing->quantity, $datetime, $ing->uom);
	    			$price += $pPrice;
	    		} else {
	    			$priceRule = price_ListRules::getPrice($conf->PRICE_LIST_COST, $ing->productId, NULL, $datetime);
	    			expect($priceRule, "Проблем при изчислението на себестойноста на продукт: {$ing->productId}");
	    			$rate = cat_UoM::fetchField($ing->uom, 'baseUnitRatio');
	    			$price += $rate * $ing->quantity * $priceRule;
	    		}
    	}
    	
    	return $uomRate * $quantity * $price;
    }
    
    
    /**
     * Изпълнява се след създаване на нова рецепта
     */
    function on_AfterCreate($mvc, $id)
    {
    	// Обновяване на броя рецепти във всяка група
    	cat_RecipeGroups::updateCount();
    }
    
    
    /**
     * Изчислява себестойноста на всички листвани рецепти и ги
     * записва в модел себестойности
     */
    function act_calcAll()
    {
    	$count = 0;
    	$conf = core_Packs::getConfig('price');
    	$query = $this->getQuery();
    	while($rec = $query->fetch()) {
    		$listRec = new stdClass();
    		$listRec->listId = $conf->PRICE_LIST_COST;
    		$listRec->productId = $rec->productId;
    		$listRec->price = cat_Recipes::calcCost($rec->productId, 1, NULL, $rec->uom);
    		$listRec->type = 'value';
    		$listRec->validFrom = dt::now();
    		if($listRec->price){
    			$count++;
    			price_ListRules::save($listRec);
    		}
    	}
    	
    	return Redirect(array($this, 'list'), FALSE, "Изчислени са себестойностите на {$count} продукта");
    }
    
    
    /**
     * 
     */
    function act_calcPrice()
    {
    	$this->requireRightFor('read');
    	expect($id = Request::get('id', 'int'));
    	expect($rec = $this->fetch($id));
    	$data = new stdClass();
    	$data->rec = $rec;
    	$data->row = $this->recToverbal($rec);
    	$this->prepareCalcPrice($data);
    	if($data->form) {
        	$rec = $data->form->input();
        	if($rec->quantity <= 0){
        		$data->form->setError('quantity', 'Неправилно количество');
        	}
            $this->requireRightFor('add', $data->rec);
            if ($data->form->isSubmitted()){
            	$price = cat_Recipes::calcCost($data->rec->productId, $rec->quantity, NULL, $rec->uom);
            	
            	return Redirect(array($this, 'single', $data->rec->id), FALSE, "Себестойноста  на {$rec->quantity}  {$data->row->productId} е {$price}");
            }
    	}
    	
    	$tpl = $this->renderWrapping($data->form->renderHtml());
    	return $tpl;
    }
    
    
    /**
     * @TODO
     */
    private function prepareCalcPrice(&$data)
    {
    	$form = cls::get("core_Form");
    	$form->FNC('uom', 'key(mvc=cat_UoM, select=name)', 'input,caption=Мярка,width=11em');
    	$form->FNC('quantity', 'int', 'input,caption=Количество,width=11em');
    	if(!$data->rec->uom){
    		$data->rec->uom = cat_Products::fetchField($data->rec->productId, 'measureId');
    	}
    	$form->setOptions('uom', cat_UoM::getSameTypeMeasures($data->rec->uom));
    	$form->setDefault('quantity', '1');
    	$form->setDefault('uom', $data->rec->uom);
    	$form->toolbar->addSbBtn("Изчисли");
    	$form->title = tr("Изчисляване на себестойност на продукт") . "|*:{$data->row->productId}";
    	$data->form = $form;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>{$row->ident}</b>" . " ({$row->state})" ;
		
		if($fields['-single']){
			$icon = sbf("img/16/package-icon.png");
			$row->productId = ht::createLink($row->productId, array('cat_Products', 'single', $rec->productId), NULL, "style=background-image:url({$icon}),class=linkWithIcon");
		}
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = "Отчет за бърза продажба №{$rec->id}";
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;

        return $row;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    static function getHandle($id)
    {
    	$rec = static::fetch($id);
    	$self = cls::get(get_called_class());
    	
    	return $self->abbr . $rec->id;
    }
    
    
    /**
	 *  Филтриране на статиите по ключови думи и категория
	 */
	static function on_AfterPrepareListFilter($mvc, $data)
	{	
		$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        $data->listFilter->view = 'horizontal';
		$data->listFilter->FNC('gr', 'key(mvc=cat_RecipeGroups, select=title, allowEmpty)', 'width=9em,silent');
		$data->listFilter->FNC('measure', 'key(mvc=cat_UoM, select=name, allowEmpty)', 'width=9em,caption=Мярка,silent');
		$data->listFilter->setDefault('date', date('Y-m-01'));
		$data->listFilter->showFields = 'gr,measure';
		$data->listFilter->input();
		if($filter = $data->listFilter->rec) {
			if($group = Request::get('gr', 'int')){
				$data->query->where("#groups LIKE '%|{$group}|%'");
			} elseif($group = $filter->group){
				$data->query->where("#groups LIKE '%|{$filter->group}|%'");
			}
		}
	}
    
	
	/**
     * След обработка на ролите
     */
	static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'activate') {
			$query = $mvc->cat_RecipeDetails->getQuery();
			$query->where("#recipeId = {$rec->id}");
			if(!$rec || $query->count() == 0){
				
				// Ако несме създали още рецептата или няма съставки
				// никой неможе да активира
				$res = 'no_one';
			}
		}
	}
}