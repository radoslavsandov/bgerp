<?php

/**
 * Клас 'cat_products_Files' 
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class cat_products_Files extends cat_products_Detail
{
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'productId';
    
    
    /**
     * Заглавие
     */
    var $title = 'Файлове';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'file,description,tools,createdOn,createdBy';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'cat_Wrapper, plg_RowTools, plg_Created';
    
    
    /**
     * Активния таб в случай, че wrapper-а е таб контрол.
     */
    var $tabName = 'cat_Products';
    
    
    /**
     * Поле за редактиране на ред
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'input=hidden,silent');
        $this->FLD('file', 'fileman_FileType(bucket=productsFiles)', 'caption=Файл, notSorting');
        $this->FLD('description', 'varchar', 'caption=Описание,input');
    }
    
    
    /**
     * Създаваме кофа
     *
     * @param core_MVC $mvc
     * @param stdClass $res
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('productsFiles', 'Файлове към продукта', '', '100MB', 'user', 'every_one');
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar(core_Manager $mvc, $data)
    {
        if ($mvc->haveRightFor('add')) {
            $data->addUrl = array(
                $mvc,
                'add',
                'productId'=>$data->masterId,
                'ret_url'=>getCurrentUrl() + array('#'=>get_class($mvc))
            );
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    static function on_AfterInputEditForm($mvc, $form)
    {
        $productRec = cat_Products::fetch($form->rec->productId);
        $productName = cat_Products::getVerbal($productRec, 'name');
        
        $form->title = "Файл към|* {$productName}";
    }
    
    
    /**
     * Рендиране на детайла
     */
    public function renderDetail_($data)
    {
        $tpl = new ET(tr("|*" . getFileContent('cat/tpl/products/Files.shtml')));
        
        if ($data->addUrl) {
            $addBtn = ht::createLink("<img src=" . sbf('img/16/add.png') . " valign=bottom style='margin-left:5px;'>", $data->addUrl);
            $tpl->append($addBtn, 'TITLE');
        }
        
        if(count($data->rows)){
	        foreach((array)$data->rows as $row) {
	            $block = $tpl->getBlock('row');
	            $block->placeObject($row);
	            $block->append2Master();
	        }
        } else {
        	$tpl->replace(tr("Няма записи"), 'row');
        }
            
        return $tpl;
    }
    
    
    /**
     * Преди подготовка на списъчния изглед
     */
    static function on_BeforePrepareListFilter($mvc, &$res, $data)
    {
    	$data->query->orderBy('createdOn', 'DESC');
    }

    
    /**
     * Подготовка на файловете
     */
    public static function prepareFiles($data)
    {   
        $data->TabCaption = 'Файлове';
        $data->Order = 10;

        static::prepareDetail($data);
    }
    
    
    /**
     * Рендиране на файловете
     */
    public static function renderFiles($data)
    {
        return static::renderDetail($data);
    }
}