<?php



/**
 * Клас 'batch_plg_DocumentMovementDetail' - За генериране на партидни движения от документите
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo да се разработи
 */
class batch_plg_DocumentMovementDetail extends core_Plugin
{
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		setIfNot($mvc->productFieldName, 'productId');
		setIfNot($mvc->storeFieldName, 'storeId');
		$mvc->declareInterface('batch_MovementSourceIntf');
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$form = &$data->form;
		$rec = &$form->rec;
		$storeId = $mvc->Master->fetchField($rec->{$mvc->masterKey}, $mvc->Master->storeFieldName);
		if(!$storeId) return;
		
		if($mvc->Master->batchMovementDocument == 'out') return;
		$form->FNC('batch', 'text', 'caption=Партида,after=productId,input=none');
		
		// Задаване на типа на партидата на полето
		if(isset($rec->{$mvc->productFieldName})){
			$BatchClass = batch_Defs::getBatchDef($rec->{$mvc->productFieldName});
			if($BatchClass){
				$form->setField('batch', 'input');
				$form->setFieldType('batch', $BatchClass->getBatchClassType());
				
				if(isset($BatchClass->fieldPlaceholder)){
					$form->setField('batch', "placeholder={$BatchClass->fieldPlaceholder}");
				}
				
				if(isset($BatchClass->fieldCaption)){
					$form->setField('batch', "caption={$BatchClass->fieldCaption}");
				}
				
				if(isset($rec->id)){
					$batch = batch_BatchesInDocuments::fetchField("#detailClassId = {$mvc->getClassId()} AND #detailRecId = {$rec->id}", 'batch');
					$form->setDefault('batch', $batch);
				}
			}
		}
	}
	
	
	/**
	 * Извиква се след въвеждането на данните от Request във формата ($form->rec)
	 *
	 * @param core_Mvc $mvc
	 * @param core_Form $form
	 */
	public static function on_AfterInputEditForm($mvc, &$form)
	{
		$rec = &$form->rec;
		$storeId = $mvc->Master->fetchField($rec->{$mvc->masterKey}, $mvc->Master->storeFieldName);
		if(haveRole('partner')) return;
		
		if($mvc->Master->batchMovementDocument == 'out') return;
		if(!$storeId) return;
		
		if(isset($rec->{$mvc->productFieldName})){
			$BatchClass = batch_Defs::getBatchDef($rec->{$mvc->productFieldName});
			if($BatchClass){
				$form->setField('batch', 'input,class=w50');
				if(!empty($rec->batch)){
					$rec->batch = $BatchClass->denormalize($rec->batch);
				}
			} else {
				$form->setField('batch', 'input=none');
				unset($rec->batch);
			}
			
			if($form->isSubmitted()){
				if(is_object($BatchClass)){
					if(!empty($rec->batch)){
						$productInfo = cat_Products::getProductInfo($rec->{$mvc->productFieldName});
						$quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
						$quantity = ($rec->packQuantity) ? $rec->packQuantity * $quantityInPack : $quantityInPack;
							
						if(!$BatchClass->isValid($rec->batch, $quantity, $msg)){
							$form->setError('batch', $msg);
						}
					}
				}
			}
		}
	}
	
	
	/**
	 * Изпълнява се след създаване на нов запис
	 */
	public static function on_AfterCreate($mvc, $rec)
	{
		if($mvc->Master->batchMovementDocument == 'out'){
			
			// След създаване се прави опит за разпределяне на количествата според наличните партиди
			$BatchClass = batch_Defs::getBatchDef($rec->{$mvc->productFieldName});
			if(is_object($BatchClass)){
				$info = $mvc->getRowInfo($rec->id);
				$batches = $BatchClass->allocateQuantityToBatches($info->quantity, $info->storeId, $info->date);
				batch_BatchesInDocuments::saveBatches($mvc, $rec->id, $batches);
			}
		} else {
			
			// Ако се създава нова партида, прави се опит за автоматичното и създаване
			if(empty($rec->batch)){
				$BatchClass = batch_Defs::getBatchDef($rec->{$mvc->productFieldName});
				if(is_object($BatchClass)){
					$masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey}, "{$mvc->Master->storeFieldName},{$mvc->Master->valiorFld}");
					$rec->batch = $BatchClass->getAutoValue($mvc->Master, $rec->{$mvc->masterKey}, $masterRec->{$mvc->Master->storeFieldName}, $masterRec->{$mvc->Master->valiorFld});
				}
			}
		}
	}
	
	
	/**
	 * Преди запис на документ
	 */
	public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
	{
		// Нормализираме полето за партидата
		if(!empty($rec->batch)){
			$BatchClass = batch_Defs::getBatchDef($rec->productId);
			if(is_object($BatchClass)){
				$rec->batch = $BatchClass->normalize($rec->batch);
			}
		} else {
			$rec->batch = NULL;
		}
	}
	
	
	/**
	 * Извиква се след успешен запис в модела
	 *
	 * @param core_Mvc $mvc
	 * @param int $id първичния ключ на направения запис
	 * @param stdClass $rec всички полета, които току-що са били записани
	 */
	public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
	{
		if($mvc->Master->batchMovementDocument == 'out') return;
		batch_BatchesInDocuments::sync($mvc->getClassId(), $rec->id, $rec->batch, $rec->quantity);
	}
	
	
	/**
	 * Преди подготовка на полетата за показване в списъчния изглед
	 */
	public static function on_AfterPrepareListRows($mvc, $data)
	{
		if(!count($data->rows) || haveRole('partner')) return;
		$storeId = $data->masterData->rec->{$mvc->Master->storeFieldName};
		if(!$storeId) return;
		
		foreach ($data->rows as $id => &$row){
			$rec = &$data->recs[$id];
			
			if(batch_BatchesInDocuments::haveRightFor('modify', (object)array('detailClassId' => $mvc->getClassId(), 'detailRecId' => $rec->id, 'storeId' => $storeId))){
				core_RowToolbar::createIfNotExists($row->_rowTools);
				core_Request::setProtected('detailClassId,detailRecId,storeId');
				$url = array('batch_BatchesInDocuments', 'modify', 'detailClassId' => $mvc->getClassId(), 'detailRecId' => $rec->id, 'storeId' => $storeId, 'ret_url' => TRUE);
				$row->_rowTools->addLink('Партиди', $url, array('ef_icon' => "img/16/wooden-box.png", 'title' => "Избор на партиди"));
				core_Request::removeProtected('detailClassId,detailRecId,storeId');
			}
		}
	}
	
	
	/**
	 * Преди рендиране на таблицата
	 */
	public static function on_BeforeRenderListTable($mvc, &$res, &$data)
	{
		if(!count($data->rows) || haveRole('partner')) return;
		
		$rows = &$data->rows;
		$storeId = $data->masterData->rec->{$mvc->Master->storeFieldName};
		if(!$storeId) return;
		
		foreach ($rows as $id => &$row){
			$rec = &$data->recs[$id];
			if(!batch_Defs::getBatchDef($rec->{$mvc->productFieldName})) continue;
			
			$row->{$mvc->productFieldName} = new core_ET($row->{$mvc->productFieldName});
			$row->{$mvc->productFieldName}->append(batch_BatchesInDocuments::renderBatches($mvc, $rec->id, $storeId));
		}
	}
	
	
	/**
	 * Метод по пдоразбиране на getRowInfo за извличане на информацията от реда
	 */
	public static function on_AfterGetRowInfo($mvc, &$res, $rec)
	{
		if(isset($res)) return;
		
		$rec = $mvc->fetchRec($rec);
		if(isset($mvc->rowInfo[$rec->id])){
			$res = $mvc->rowInfo[$rec->id];
			return;
		}
		
		$operation = ($mvc->Master->batchMovementDocument == 'out') ? 'out' : 'in';
		$masterRec = $mvc->Master->fetch($rec->{$mvc->masterKey}, "{$mvc->Master->storeFieldName},containerId,{$mvc->Master->valiorFld}");
		
		$res = (object)array('productId'      => $rec->{$mvc->productFieldName},
		                     'packagingId'    => $rec->packagingId,
		                     'quantity'       => $rec->quantity,
		                     'quantityInPack' => $rec->quantityInPack,
		                     'containerId'    => $masterRec->containerId,
		                     'storeId'        => $masterRec->{$mvc->Master->storeFieldName},
		                     'date'           => $masterRec->{$mvc->Master->valiorFld},
		                     'state'          => $masterRec->state,
		                     'operation'      => $operation);
		
		$mvc->rowInfo[$rec->id] = $res;
		$res = $mvc->rowInfo[$rec->id];
	}
	
	
	/**
	 * Кои роли могат да променят групово партидите на изходящите документи
	 */
	public static function on_AfterGetRolesToModfifyBatches($mvc, &$res, $rec)
	{
		$rec = $mvc->fetchRec($rec);
		if(!batch_Defs::getBatchDef($rec->{$mvc->productFieldName})){
			$res = 'no_one';
		} else {
			// Ако има склад и документа е входящ, не може
			$storeId = $mvc->Master->fetchField($rec->{$mvc->masterKey}, $mvc->Master->storeFieldName);
			if(!$storeId){
				$res = 'no_one';
			} elseif($mvc->Master->batchMovementDocument != 'out'){
				$res = 'no_one';
			} else {
				$info = $mvc->getRowInfo($rec);
				$quantities = batch_Items::getBatchQuantitiesInStore($info->productId, $info->storeId, $info->date);
				
				if(!count($quantities)){
					if(!batch_BatchesInDocuments::fetchField("#detailClassId = {$mvc->getClassId()} AND #detailRecId = {$rec->id}")){
						$res = 'no_one';
					} else {
						$res = $mvc->getRequiredRoles('edit', $rec);
					}
				} else {
					$res = $mvc->getRequiredRoles('edit', $rec);
				}
			}
		}
	}
	
	
	/**
	 * Филтриране по подразбиране на наличните партиди
	 */
	public static function on_AfterFilterBatches($mvc, &$res, $rec, &$batches)
	{
		
	}
	
	
	/**
	 * След изтриване на запис
	 */
	protected static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
	{
		foreach ($query->getDeletedRecs() as $id => $rec) {
			batch_BatchesInDocuments::delete("#detailClassId = {$mvc->getClassId()} AND #detailRecId = {$id}");
		}
	}
}