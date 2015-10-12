<?php


/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа cash_ExchangeDocument
 *
 * @category  bgerp
 * @package   cash
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class cash_transaction_ExchangeDocument extends acc_DocumentTransactionSource
{
    
    
    /**
     * 
     * @var cash_ExchangeDocument
     */
    public $class;
    
    
    /**
     *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
     *  Създава транзакция която се записва в Журнала, при контирането
     *
     *  Ако избраната валута е в основна валута
     *
     *  	Dt: 501. Каси 					(Каса, Валута)
     *  	Ct: 501. Каси					(Каса, Валута)
     *
     *  Ако е в друга валута различна от основната
     *
     *  	Dt: 501. Каси 					         (Каса, Валута)
     *  	Ct: 481. Разчети по курсови разлики		 (Валута)
     *
     *  	Dt: 481. Разчети по курсови разлики	     (Валута)
     *  	Ct: 501. Каси 					         (Каса, Валута)
     */
    public function getTransaction($id)
    {
    	// Извличаме записа
    	expect($rec = $this->class->fetchRec($id));
    
    	$toCase = array('501',
    			array('cash_Cases', $rec->peroTo),
    			array('currency_Currencies', $rec->debitCurrency),
    			'quantity' => $rec->debitQuantity);
    
    	$fromCase = array('501',
    			array('cash_Cases', $rec->peroFrom),
    			array('currency_Currencies', $rec->creditCurrency),
    			'quantity' => $rec->creditQuantity);
    
    	if($rec->creditCurrency == acc_Periods::getBaseCurrencyId($rec->valior)){
    		$entry = array('amount' => $rec->debitQuantity * $rec->debitPrice, 'debit' => $toCase, 'credit' => $fromCase);
    		$entry = array($entry);
    	} else {
    		$entry = array();
    		$entry[] = array('amount' => $rec->debitQuantity * $rec->debitPrice,
    				'debit' => $toCase,
    				'credit' => array('481', array('currency_Currencies', $rec->creditCurrency), 'quantity' => $rec->creditQuantity));
    		$entry[] = array('amount' => $rec->debitQuantity * $rec->debitPrice,
    				'debit' => array('481', array('currency_Currencies', $rec->creditCurrency), 'quantity' => $rec->creditQuantity),
    				'credit' => $fromCase);
    	}
    	 
    	// Подготвяме информацията която ще записваме в Журнала
    	$result = (object)array(
    			'reason' => $rec->reason,   // основанието за ордера
    			'valior' => $rec->valior,   // датата на ордера
    			'entries' => $entry,
    	);
    
    	return $result;
    }
}