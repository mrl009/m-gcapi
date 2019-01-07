$.extend($.fn.datagrid.methods, {
	addToolbarItem : function (jq, param) {
		var addControlls=function(td,items){
			var constrain=items[0];
			for (var i = 1; i < items.length; i++) {
				var btn = items[i];
				if (btn == "-") {
					$("<div class=\"datagrid-btn-separator\"></div>").appendTo(td);
				}else if(btn=='|'){
					$("<br />").appendTo(td);
					
				}else{
					var label = '<span style="padding-left:8px">'+btn.text+'：</span>'; 
					if(btn.type==undefined){
						var b = $("<a href=\"javascript:void(0)\"></a>").appendTo(td);
						b[0].onclick = eval(btn.handler || function () {});
						b.linkbutton($.extend({}, btn, {
							plain : true
						}));
					}else if(btn.type=='combobox'){
						td.append(label);
						var b = $('<select name="'+btn.name+'" style="min-width:80px" '+(btn.change?'change='+btn.change:'')+'></select>').appendTo(td);
						if(btn.id!=undefined) b.attr('id',btn.id);
						var _config={
							//panelHeight:'50',
							onLoadSuccess:function(node,data){
								//if(btn.value!=undefined) b.combotree('setValue',btn.value==undefined?data[0].id:btn.value);
							},
							onSelect:function(record){
								if(constrain.instant) setTimeout(function(){this.search(form_id)},100);
							}
						};
						if(btn.url!=undefined){
							_config.url=btn.url;
							_config.valueField='id';
							_config.textField='text';
							if(btn.change) _config.onSelect=function(record){eval('('+$(this).attr('change')+')')(record);};
						}else{
							b.html(btn.items);
						}
						b.combobox($.extend({}, btn, _config));
					}else if(btn.type=='datebox'){
						td.append(label);
						var b = $('<input name="'+btn.name+'" id="'+btn.name+'">').appendTo(td);
						b.datebox($.extend({}, btn, {
							onSelect: function(date){
								if(constrain.instant) setTimeout(function(){search(form_id)},100);
							}
						}));
					}else if(btn.type=='datetimebox'){
						td.append(label);
						var b = $('<input name="'+btn.name+'">').appendTo(td);
						b.datetimebox($.extend({}, btn, {showSeconds:false,
							onSelect: function(date){
								if(constrain.instant) setTimeout(function(){search(form_id)},100);
							}
						}));
					}else if(btn.type=='textbox'){
						td.append(label);
						var b = $('<input name="'+btn.name+'" style="width:'+btn.width+'px;border:1px solid #cecece;padding:2px">').appendTo(td);
						if(btn.id!=undefined) b.attr('id',btn.id);
						b.blur(function(){
							if(b.val()!=''&& constrain.instant) setTimeout(function(){search(form_id)},100);
						});
					}else if(btn.type=='hidebox'){
						var b=$('<input type=hidden name="'+btn.name+'">').appendTo(td);
						if(btn.id!=undefined) b.attr('id',btn.id);
					}else if(btn.type=='menu'){
						var b = $("<a href=\"javascript:void(0)\"></a>").appendTo(td);
						b[0].onclick = eval(btn.handler || function () {});
						b.menubutton($.extend({}, btn, {
							menu:'#'+btn.menu
						}));
					}else if(btn.type=='label'){
						var b=$('<span id='+btn.id+'>'+(btn.html!=undefined?btn.html:'')+'</span>').appendTo(td);
						b[0].onclick = eval(btn.handler || function () {});
					}else if(btn.type=='menubutton'){
						var _id=guid(),arr=[];
						$.each(btn.items,function(i,d){arr.push('<div>'+d+'</div>')});
						var div=$('<div id="'+_id+'">'+arr.join(',')+'</div>').appendTo('body');
						var b=$('<a href="javascript:;" class="easyui-menubutton" data-options="menu:\'#'+_id+'\',iconCls:\''+btn.iconCls+'\'">'+btn.text+'</a>').appendTo(td);
						btn.handler(div);
					}
				}
			}
		};
		return jq.each(function () {
			var __=this;
			var form_id = 'form_'+$(this).attr('id'); //alert(form_id);
			var dpanel = $(this).datagrid('getPanel');
			var toolbar = dpanel.children("div.datagrid-toolbar");
			if (!toolbar.length) {
				toolbar = $("<div class=\"datagrid-toolbar\"  style='background:#eee'><form id='"+form_id+"' method='post'><table cellspacing=\"0\" cellpadding=\"0\"><tr><td></td></tr></table></form></div>").prependTo(dpanel);
				$(this).datagrid('resize');
			}
			var td = toolbar.find("td");
			addControlls(td,param.tools);
			
			var search = function(id){
				var obj=$(__);
				obj.datagrid("clearSelections");
				obj.datagrid('loadData', {total:0,rows:[]}); //清空所有行
				//添加搜索参数
				var arg = $('#'+form_id).serializeArray();
				var query = obj.datagrid('options').queryParams;
				$.each(arg,function(i,v){
					query[v.name]=v.value;
				});
				obj.datagrid('options').queryParams=query;       
				obj.datagrid('reload');	
			};
			if(param.pager!=undefined){
				var dpanel = $(this).datagrid('getPanel');
				var pagerbar = dpanel.children("div.datagrid-pager");
				var td=$('<td><form method="post"></form></td>').appendTo(pagerbar.find('table tr')).find('form');
				addControlls(td,param.pager);
			}
			
		});
	},
	removeToolbarItem : function (jq, param) {
		return jq.each(function () {
			var dpanel = $(this).datagrid('getPanel');
			var toolbar = dpanel.children("div.datagrid-toolbar");
			var cbtn = null;
			if (typeof param == "number") {
				cbtn = toolbar.find("td").eq(param).find('span.l-btn-text');
			} else if (typeof param == "string") {
				cbtn = toolbar.find("span.l-btn-text:contains('" + param + "')");
			}
			if (cbtn && cbtn.length > 0) {
				cbtn.closest('td').remove();
				cbtn = null;
			}
		});
	}
});