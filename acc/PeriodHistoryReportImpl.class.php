<?php



/**
 * Имплементация на 'frame_ReportSourceIntf' за направата на справка на баланса
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_PeriodHistoryReportImpl extends acc_HistoryReportImpl
{
	
	
	/**
	 * Кой може да избира драйвъра
	 */
	public $canSelectSource = 'ceo, acc';
	
	
	/**
	 * Кои интерфейси имплементира
	 */
	public $interfaces = 'frame_ReportSourceIntf';
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Счетоводство»Обороти по период';
	
	
	/**
	 * Брой записи на страница
	 */
	public $listItemsPerPage = 31;
	
	
	/**
	 * След подготовката на ембеднатата форма
	 */
	public static function on_AfterAddEmbeddedFields($mvc, core_Form &$form)
	{
		$form->FLD('step', "enum(day=Дни,week=Седмици,month=Месеци)", "caption=Групиране по");
		
		if(isset($mvc->defaultAccount)){
			$accId = acc_Accounts::getRecBySystemId($mvc->defaultAccount)->id;
			$form->setDefault('accountId', $accId);
			$form->setReadOnly('accountId');
		}
		
		$form->setDefault('isGrouped', 'yes');
		$form->setHidden('isGrouped');
		
		$form->setField('orderField', 'input=none');
		$form->setField('orderBy', 'input=none');
	}
	
	
	/**
	 * Подготвя вътрешното състояние, на база въведените данни
	 *
	 * @param core_Form $innerForm
	 */
	public function prepareInnerState()
	{
		set_time_limit(600);
		
		// Подготвяне на данните
		$data = new stdClass();
    	$data->rec = $this->innerForm;
    	$data->recs = array();
    	$data->isHistory = FALSE;
    	if(empty($data->rec->toDate)){
    		$data->rec->toDate = $data->rec->fromDate;
    	}
    	
    	$accSysId = acc_Accounts::fetchField($data->rec->accountId, 'systemId');
    	
    	// Започваме да извличаме баланса от началната дата
    	// За всеки ден от периода намираме какви са салдата и движенията по аналитичната сметка
    	$curDate = $data->rec->fromDate;
    	$step = 'week';
    	
    	switch($data->rec->step){
    		case 'day':
    			$toDate = $curDate;
    			break;
    		case 'week':
    			$year = dt::mysql2verbal($curDate, 'Y');
    			$week = dt::mysql2verbal($curDate, 'W');
    			$toDate = dt::verbal2mysql(dt::timestamp2Mysql(strtotime("{$year}-W{$week}-7")), FALSE);
    			break;
    		case 'month':
    			$toDate = dt::getLastDayOfMonth($curDate);
    			break;
    	}
    	
    	if($data->rec->fromDate == $data->rec->toDate){
    		$data->isHistory = TRUE;
    		
    		$balHistory = acc_ActiveShortBalance::getBalanceHystory($accSysId, $data->rec->fromDate, $data->rec->toDate, $data->rec->ent1Id, $data->rec->ent2Id, $data->rec->ent3Id);
    		
    		$baseBalanceRec = array('docId' => "Начален баланс", 
    							    'debitQuantity' => 0, 'creditQuantity' => 0, 'debitAmount' => 0, 'creditAmount' => 0,
    								'blQuantity'    => $balHistory['summary']['baseQuantity'], 
    								'blAmount'      => $balHistory['summary']['blAmount'],
    								'ROW_ATTR'      => array('style' => 'background-color:#eee;font-weight:bold'));
    		$blBalanceRec = array('docId' => "Краен баланс", 
    							  'debitQuantity'  => $balHistory['summary']['debitQuantity'],
    							  'debitAmount'    => $balHistory['summary']['debitAmount'],
    							  'creditQuantity' => $balHistory['summary']['creditQuantity'], 
    							  'credittAmount'  => $balHistory['summary']['creditAmount'],
    							  'blQuantity'     => $balHistory['summary']['blQuantity'], 
    							  'blAmount'       => $balHistory['summary']['blAmount'],
    							  'ROW_ATTR'       => array('style' => 'background-color:#eee;font-weight:bold'));
    		
    		array_unshift($balHistory['history'], $baseBalanceRec);
    		$balHistory['history'][] = $blBalanceRec;
    		$data->recs = array_merge($data->recs, $balHistory['history']);
    		
    	} else {
    		do{
    			$newRec = (object)array('date' => $curDate);
    			$newRec->from = $curDate;
    			$newRec->to = $toDate;
    			
    			$verb = dt::mysql2verbal($curDate, 'W');
    			echo "<li>{$curDate} - {$toDate} | {$verb}";
    			
    			// Намираме движенията по сметката за тези пера за тази дата
    			$Balance = new acc_ActiveShortBalance(array('from' => $curDate, 'to' => $toDate, 'accs' => $accSysId, 'cacheBalance' => FALSE, "item1" => $data->rec->ent1Id, "item2" => $data->rec->ent2Id, "item3" => $data->rec->ent3Id));
    			$balance = $Balance->getBalance($accSysId);
    			
    			// Ако има баланс
    			if(count($balance)){
    				foreach ($balance as $b){
    		
    					// И в нея да участват перата
    					if(!($b->ent1Id == $data->rec->ent1Id && $b->ent2Id == $data->rec->ent2Id && $b->ent3Id == $data->rec->ent3Id)) continue;
    			
    					// Сабираме салдата и оборотите
    					foreach (array('baseQuantity', 'debitQuantity', 'creditQuantity', 'blQuantity', 'baseAmount', 'debitAmount', 'creditAmount', 'blAmount') as $fld){
    						if(isset($b->$fld)){
    							$newRec->$fld += $b->$fld;
    						}
    					}
    				}
    			}
    		
    			// Добавяме към записите
    			$data->recs[] = $newRec;
    			
    			// Интересувани чустата дата без часът
    			$curDate = dt::verbal2mysql($curDate, FALSE);
    			
    			// Ако групираме по седмици
    			if($data->rec->step == 'week'){
    				$curDate = dt::addSecs(60 * 60 * 26 , $toDate);
    				$curDate = dt::verbal2mysql($curDate, FALSE);
    				
    				$toDate = dt::addSecs(60 * 60 * 26 * 7, $toDate);
    				$toDate = dt::verbal2mysql($toDate, FALSE);
    				
    			// Ако групираме по месеци
    			} elseif($data->rec->step == 'month'){
    				$curDate = dt::addSecs(60 * 60 * 26 , $toDate);
    				$curDate = dt::verbal2mysql($curDate, FALSE);
    				$toDate = dt::getLastDayOfMonth($curDate);
    			
    			// Ако групираме по дни
    			} else {
    				$curDate = dt::addSecs(60 * 60 * 26 , $curDate);
    				$curDate = dt::verbal2mysql($curDate, FALSE);
    				$toDate = $curDate;

    			}
    			
    			if($toDate > $data->rec->toDate){
    				$toDate = $data->rec->toDate;
    			}
    			
    			// Продължаваме докато текущата дата се изравни с крайната
    		} while($curDate <= $data->rec->toDate);
    	}
    	
    	// Връщаме данните
    	return $data;
	}
	
	
	/**
	 * Рендира вградения обект
	 *
	 * @param stdClass $data
	 */
	public function renderEmbeddedData($data)
	{
		if(empty($data)) return;
		
		$tpl = $this->getReportLayout();
		$tpl->replace($this->title, 'TITLE');
		
		// Рендираме статичната форма
		$this->prependStaticForm($tpl, 'FORM');
		 
		// Рендираме таблицата с намерените записи
		$tableMvc = new core_Mvc;
		$tableMvc->FLD('baseQuantity', 'int', 'tdClass=accCell');
		$tableMvc->FLD('debitQuantity', 'int', 'tdClass=accCell');
		$tableMvc->FLD('creditQuantity', 'int', 'tdClass=accCell');
		$tableMvc->FLD('blQuantity', 'int', 'tdClass=accCell');
		$tableMvc->FLD('baseAmount', 'int', 'tdClass=accCell');
		$tableMvc->FLD('debitAmount', 'int', 'tdClass=accCell');
		$tableMvc->FLD('creditAmount', 'int', 'tdClass=accCell');
		$tableMvc->FLD('blAmount', 'int', 'tdClass=accCell');
		
		$table = cls::get('core_TableView', array('mvc' => $tableMvc));
		 
		$tpl->append($table->get($data->rows, $data->listFields), 'DETAILS');
		 
		if($data->Pager){
			$tpl->append($data->Pager->getHtml(), 'PAGER_TOP');
			$tpl->append($data->Pager->getHtml(), 'PAGER_BOTTOM');
		}
		 
		return $tpl;
	}
	
	
	/**
	 * Връща шаблона на репорта
	 *
	 * @return core_ET $tpl - шаблона
	 */
	public function getReportLayout_()
	{
		$tpl = getTplFromFile('cash/tpl/CaseReportDays.shtml');
	
		return $tpl;
	}
	
	
	/**
	 * Какви са полетата на таблицата
	 */
	public function prepareListFields_(&$data)
	{
		$data->listFields = array(
							'baseQuantity'   => 'Начално->К-во',
							'baseAmount'     => 'Начално->Сума',
							'debitQuantity'  => 'Дебит->К-во',
							'debitAmount'    => 'Дебит->Сума',
							'creditQuantity' => 'Кредит->К-во',
							'creditAmount'   => 'Кредит->Сума',
							'blQuantity'     => 'Остатък->К-во',
							'blAmount'       => 'Остатък->Сума',);
		
		switch ($data->rec->step){
			case 'day':
				$dateCaption = 'Ден';
				break;
			case 'week':
				$dateCaption = 'Седмица';
				break;
			case 'month':
				$dateCaption = 'Месец';
				break;
		}
		
		$firstColumn = ($data->isHistory == FALSE) ? array('date' => $dateCaption) : array('docId' => 'Документ');
		
		// Ако к-та са равни на сумите, оставяме само едните
		if($data->hasSameValues === TRUE){
			unset($data->listFields['baseAmount'], 
				  $data->listFields['debitAmount'], 
				  $data->listFields['debitAmount'], 
				  $data->listFields['creditAmount'], 
				  $data->listFields['blAmount']);
			
			$data->listFields['baseQuantity'] = 'Начално';
			$data->listFields['debitQuantity'] = 'Дебит';
			$data->listFields['creditQuantity'] = 'Кредит';
			$data->listFields['blQuantity'] = 'Остатък';
		}
		
		$data->listFields = $firstColumn + $data->listFields;
	}
	
	
	/**
	 * След подготовката на показването на информацията
	 */
	public static function on_AfterPrepareEmbeddedData($mvc, &$data)
	{
		$data->recs = array_reverse($data->recs, TRUE);
		$data->hasSameValues = TRUE;
		
		// Можели потребителя да вижда хронологията на сметката
		$attr = array();
		$attr['class'] = 'linkWithIcon';
		$attr['style'] = 'background-image:url(' . sbf('img/16/clock_history.png', '') . ');';
		$attr['title'] = tr("Хронологична справка");
		
		$canSeeHistory = acc_BalanceDetails::haveRightFor('history', (object)array('ent1Id' => $data->rec->ent1Id, 'ent2Id' => $data->rec->ent2Id, 'ent3Id' => $data->rec->ent3Id));
		if($canSeeHistory){
			$histUrl = array('acc_BalanceHistory', 'History', 'accNum' => acc_Accounts::fetchField($data->rec->accountId, 'num'));
			$histUrl['ent1Id'] = $data->rec->ent1Id;
			$histUrl['ent2Id'] = $data->rec->ent2Id;
			$histUrl['ent3Id'] = $data->rec->ent3Id;
		} 
		
		
		//, 'fromDate' => $balanceRec->fromDate, 'toDate' => $balanceRec->toDate
		// Ако има намерени записи
		if(count($data->recs)){
			
			if(!Mode::is('printing')){
				
				// Подготвяме страницирането
				$data->Pager = cls::get('core_Pager',  array('itemsPerPage' => $mvc->listItemsPerPage));
				$data->Pager->itemsCount = count($data->recs);
			}
	
			// За всеки запис
			foreach ($data->recs as &$rec){
				foreach (array('base', 'debit', 'credit', 'bl') as $type){
					if($rec->{"{$type}Quantity"} != $rec->{"{$type}Amount"}){
						$data->hasSameValues = FALSE;
						break;
					}
				}
				
				// Ако не е за текущата страница не го показваме
				if(isset($data->Pager) && !$data->Pager->isOnPage()) continue;
	
				// Вербално представяне на записа
				$row = $mvc->getVerbalRec($rec);
				if($canSeeHistory){
					$histUrl['fromDate'] = $rec->from;
					$histUrl['toDate'] = $rec->to;
					$row->date = ht::createLink('', $histUrl, NULL, $attr) . " {$row->date}";
				}
				
				$data->rows[] = $row;
			}
		}
		
		$mvc->prepareListFields($data);
	}
	
	
	/**
	 * Вербално представяне на групираните записи
	 *
	 * @param stdClass $rec - групиран запис
	 * @return stdClass $row - вербален запис
	 */
	private function getVerbalRec($rec)
	{
		$rec = (object)$rec;
		$row = new stdClass();
		$Double = cls::get('type_Double', array('params' => array('decimals' => 2)));
		 
		if(isset($rec->docId)){
			try{
				$Class = cls::get($rec->docType);
				$row->docId = $Class->getShortHyperLink($rec->docId);
			} catch(core_exception_Expect $e){
				if(is_numeric($rec->docId)){
					$row->docId = "<span style='color:red'>" . tr("Проблем при показването") . "</span>";
				} else {
					$row->docId = $rec->docId;
				}
			}
		}
		 
		switch($this->innerForm->step){
			case 'week':
				$verb = dt::mysql2verbal($rec->date, 'W');
				$row->from = dt::mysql2verbal($rec->from, "d.m.Y");
				$row->to = dt::mysql2verbal($rec->to, "d.m.Y");
				$row->date = "{$verb} <span class='small'>($row->from - $row->to)</span>";
				break;
			case 'day':
				$row->date = dt::mysql2verbal($rec->date, "d.m.Y");
				break;
			case 'month':
				$row->date = dt::mysql2verbal($rec->to, "M Y");
				break;
		}
		
		// Вербално представяне на сумите и к-та
		foreach (array('baseQuantity', 'debitQuantity', 'creditQuantity', 'blQuantity', 'baseAmount', 'creditAmount', 'debitAmount', 'blAmount') as $fld){
			if(isset($rec->{$fld})){
				$row->{$fld} = $Double->toVerbal($rec->{$fld});
				if($rec->{$fld} < 0){
					$row->{$fld} = "<span class='red'>{$row->{$fld}}</span>";
					}
				}
			}
			 
			if($rec->ROW_ATTR){
				$row->ROW_ATTR = $rec->ROW_ATTR;
		}
		
		// Връщаме подготвеното вербално рпедставяне
		return $row;
	}
}