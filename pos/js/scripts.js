function posActions() {

	// Забраняване на скалирането, за да избегнем забавяне
	if(is_touch_device()){
		 $('meta[name=viewport]').remove();
		 $('meta').attr('name', 'viewport').attr('content', 'width=device-width, user-scalable=no').appendTo('head');
	}
	
	// Извикване на функцията за преизчисления на размерите на елементите
	if($('body').hasClass('wide')){
		calculateWidth();
		$(window).resize( function() {
			calculateWidth();
		});
	} 
	
	// Ширина на контейнера на бързите бутони в мобилен
	var width = (parseInt($('.pos-product').length) + 1) * 45 ;
	$('.narrow #pos-products > div').css('width',width);
	
	
	// Засветяване на избрания ред и запис в хидън поле
	$(".pos-sale").live("click", function() {
		var id = $(this).attr("data-id");
		$(".pos-sale td").removeClass('pos-hightligted');
		$('[data-id="'+ id +'"] td').addClass('pos-hightligted');
		$("input[name=rowId]").val(id);
	});
	
	// Използване на числата за въвеждане в пулта
	$("#tools-form .numPad").live("click", function() {
		var val = $(this).val();
		
		var inpVal = $("input[name=ean]").val();
		if(val == '.'){
			if(inpVal.length == 0){
				inpVal = 0;
			}
			
			if(inpVal.indexOf(".")  != -1){
				return;
			}
		}
		
		inpVal += val;
		$("input[name=ean]").val(inpVal);
	});
	
	// Използване на числата за въвеждане на суми за плащания
	$("#tools-payment .numPad").live("click", function() {
		var val = $(this).val();
		
		var inpVal = $("input[name=paysum]").val();
		if(val == '.'){
			if(inpVal.length == 0){
				inpVal = 0;
			}
			
			if(inpVal.indexOf(".")  != -1){
				return;
			}
		}
		
		inpVal += val;
		$("input[name=paysum]").val(inpVal);
	});
	
	// Триене на числа в пулта
	$("#tools-form .numBack").live("click", function() {
		var inpValLength = $("input[name=ean]").val().length;
		var newVal = $("input[name=ean]").val().substr(0, inpValLength-1);
		
		$("input[name=ean]").val(newVal);
	});
	
	// Триене на числа при плащанията
	$("#tools-payment .numBack").live("click", function() {
		var inpValLength = $("input[name=paysum]").val().length;
		var newVal = $("input[name=paysum]").val().substr(0, inpValLength-1);
		
		$("input[name=paysum]").val(newVal);
	});
	
	// Модифициране на количество
	$(".tools-modify").live("click", function() {
		var inpVal = $("input[name=ean]").val();
		var rowVal = $("input[name=rowId]").val();
		
		var url = $(this).attr("data-url");
		var data = {recId:rowVal, amount:inpVal};
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, data);
		$("input[name=ean]").val("");
	});
	
	// Добавяне на клиентска карта
	$("#tools-addclient").live("click", function() {
		var inpVal = $("input[name=ean]").val();
		var rowVal = $("input[name=receiptId]").val();

		var url = $(this).attr("data-url");
		var data = {receiptId:rowVal, ean:inpVal};
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, data);
		$("input[name=ean]").val("");
	});
	
	// Добавя продукт при събмит на формата
	$("#toolsForm").on("submit",function(event){
	    var url = $("#toolsForm").attr("action");
		var code = $("input[name=ean]").val();
		var receiptId = $("input[name=receiptId]").val();
		var data = {receiptId:receiptId, ean:code};
		
		resObj = new Object();
		resObj['url'] = url;
		getEfae().process(resObj, data);
	
		$("input[name=ean]").val("");
		event.preventDefault();
		scrollRecieptBottom();
	    return false; 
	});
	
	// Добавя продукт от комбо бокса
	$("#searchForm").on("submit",function(event){
		var url = $("#searchForm").attr("action");
		var productId = $("#searchForm select[name=productId]").val();
		var receiptId = $("#searchForm input[name=receiptId]").val();
		var data = {receiptId:receiptId, productId:productId};
		
		resObj = new Object();
		resObj['url'] = url;
		getEfae().process(resObj, data);
		
		event.preventDefault();
		scrollRecieptBottom();
	    return false;
	});
	
	// Направата на плащане след натискане на бутон
	$(".paymentBtn").live("click", function() {
		var url = $(this).attr("data-url");
		var type = $(this).attr("data-type");
		var amount = $("input[name=paysum]").val();
		var receiptId = $("input[name=receiptId]").val();
		
		var data = {receiptId:receiptId, amount:amount, type:type};
		
		resObj = new Object();
		resObj['url'] = url;
		getEfae().process(resObj, data);
	
		$("input[name=paysum]").val("");
		scrollRecieptBottom();
	});
	
	// Бутоните за приключване приключват бележката
	$(".closeBtns").live("click", function(event) {
		var url = $(this).attr("data-url");
		var receiptId = $("input[name=receiptId]").val();
		
		if(!url){
			return;
		}
		
		var data = {receipt:receiptId};
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, data);
		scrollRecieptBottom();
	});
	
	// Добавяне на продукти от бързите бутони
	$('.pos-product').live("click", function(event) {
		var url = $(this).attr("data-url");
		var productId = $(this).attr("data-id");
		var receiptId = $("input[name=receiptId]").val();
		
		var data = {receiptId:receiptId,productId:productId};
		
		resObj = new Object();
		resObj['url'] = url;
		
		getEfae().process(resObj, data);
		scrollRecieptBottom();
	});

	// Скриване на бързите бутони спрямо избраната категория
	$(".pos-product-category[data-id='']").addClass('active');
	$('.pos-product-category').live("click", function(event) {
		var value = $(this).attr("data-id");
		
		$(this).addClass('active').siblings().removeClass('active');
		
		var counter = 0;
		if(value) {
			var nValue = "|" + value + "|";
			
			$("div.pos-product[data-cat !*= '"+nValue+"']").each(function() {
				$(this).hide();
			});
			
			$("div.pos-product[data-cat *= '"+nValue+"']").each(function() {
				$(this).show();
				counter++;
			});
		} else {
			$("div.pos-product").each(function() {
				$(this).show();
				counter++;
			});
		}
		var width = parseInt((counter+1) * 45 );
		$('.narrow #pos-products > div').css('width',width);
	});
	
	// При клик на бутон изтрива запис от бележката
	$('.pos-del-btn').live("click", function(event) {
		var warning = $(this).attr("data-warning");
		var url = $(this).attr("data-url");
		var recId = $(this).attr("data-recId");
		if (!confirm(warning)){
			return false; 
		} else {
			
			resObj = new Object();
			resObj['url'] = url;
			
			getEfae().process(resObj, {recId:recId});
		}
	});
	
	// Скриване на табовете
	$('.pos-tabs a ').live("click", function(e) {
		var currentAttrValue= $(this).attr('href');

		$('.tab-content' + currentAttrValue).show().siblings().hide();
		$(this).parent('li').addClass('active').siblings().removeClass('active');
		if($('body').hasClass('wide')){
			calculateWidth();
		}
		e.preventDefault();
	}); 
	
	// Смяна на текущата клавиатура
	$('.keyboard-change-btn').live("click", function() {
		var currentAttrValue = $(this).attr('data-klang');
		$('.keyboard#' + currentAttrValue).show().siblings().hide();
	}); 
	
	// Попълване на символи от клавиатурата
	$('.keyboard-btn').live("click", function() {
		var currentAttrValue = $(this).val();
		var isChangeBtn = $(this).attr('data-klang');
		
		if(currentAttrValue == 'SPACE'){
			currentAttrValue = ' ';
		}
		
		// Ако е натиснат бутон за смяна на език, не правим нищо
		if(isChangeBtn != undefined) {
			return;
		}
		
		var inpVal = $("#select-input-pos").val();
		inpVal += currentAttrValue;
		$("#select-input-pos").val(inpVal);
		
		// Задействаме евент 'keyup' в инпут полето
		var e = jQuery.Event("keyup");
		$("#select-input-pos").trigger(e);
	}); 
	
	// Триене на символи от формата за търсене
	$(".keyboard-back-btn").live("click", function() {
		var inpValLength = $("#select-input-pos").val().length;
		var newVal = $("#select-input-pos").val().substr(0, inpValLength-1);
		
		$("#select-input-pos").val(newVal);
		var e = jQuery.Event("keyup");
		$("#select-input-pos").trigger(e);
	});
	
	var timeout;
	
	// След въвеждане на стойност, прави заявка по Ajax
	$("#select-input-pos").keyup(function() {
		//console.log('up');
		var inpVal = $("#select-input-pos").val();
		var receiptId = $("input[name=receiptId]").val();
		
		var url = $(this).attr("data-url");
		
		resObj = new Object();
		resObj['url'] = url;
		
		//clearTimeout(timeout);
		//timeout = setTimeout(function(){
			//console.log('vikam',url);
			getEfae().process(resObj, {searchString:inpVal,receiptId:receiptId});
		//}, 500);
	});
	
	// Добавяне на продукт от резултатите за търсене
	$('.pos-add-res-btn').live("click", function() {
		var elemRow = $(this).closest('tr');
		$(elemRow).addClass('pos-hightligted');
		setTimeout(function(){$(elemRow).removeClass('pos-hightligted');},1000);
		var receiptId = $(this).attr("data-recId");
		var url = $(this).attr("data-url");
		var productId = $(this).attr("data-productId");
		
		resObj = new Object();
		resObj['url'] = url;
		getEfae().process(resObj, {receiptId:receiptId,productId:productId});
	});
}


function calculateWidth(){
	var winWidth = parseInt($(window).width());
	var winHeight = parseInt($(window).height());
	var padd = 2 * parseInt($('.single-receipt-wrapper').css('padding-top'));
	var marg = 2 * parseInt($('.single-receipt-wrapper').css('margin-top'));
	var totalOffset = marg + padd + 2;
	$('.single-receipt-wrapper').css('height', winHeight -  totalOffset);
	
	var usefulWidth = winWidth - totalOffset;
	
	var maxColWidth = parseInt(usefulWidth/2) - 10;
	if(maxColWidth < 285) {
		maxColWidth = 245;
	}
	
	//задаване на ширина на двете колони
	$('#single-receipt').css('width', maxColWidth);
	$('.tabs-holder-content').css('width', maxColWidth);
	$('.tools-wide-select-content').css('width', maxColWidth);

	//максимална височина на дясната колона и на елементите й
	$('.tools-wide-select-content').css('maxHeight', winHeight-85);
	$('.wide #pos-products').css('maxHeight', winHeight-155);
	
	//височина за таблицата с резултатите
	var searchTopHeight = parseInt($('.search-top-holder').height());
	$('#pos-search-result-table').css('maxHeight', winHeight - searchTopHeight - 120);
	
	//максимална височина на бележката
	var downPanelHeight = parseInt($('#tools-holder').outerHeight());
	$('.scrolling-vertical').css('maxHeight', winHeight -  totalOffset - downPanelHeight -30);
	$('.scrolling-vertical').scrollTo = $('.scrolling-vertical').scrollHeight;
	scrollRecieptBottom();
	
}

// скролиране на бележката до долу
function scrollRecieptBottom(){
	var el = $('.scrolling-vertical');
	setTimeout(function(){el.scrollTop( el.get(0).scrollHeight );},500);
}