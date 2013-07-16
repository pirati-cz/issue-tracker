var piratitask_typeahead = '';

jQuery('document').ready(function(){
     if(jQuery('#piratitask #tab2').length>0){
          piratitask_initTabs();
          piratitask_initNewIssue();
          piratitask_initFilter();
          piratitask_initSort();
          piratitask_loadIssues();
          piratitask_initSettings();
          piratitask_initGroups();
     }

     if(jQuery('#piratitask #piratitask-info').length>0){
          piratitask_initInfo();
          piratitask_initComments();

          // oprava stylů pirati.cz
          jQuery('#col-span').css('overflow','visible');
          jQuery('#page').css('overflow','visible');
     }

     if(jQuery('#piratitask #form-editissue').length>0){
          piratitask_initEdit();
     }
});

// funkcnost tabu
function piratitask_initTabs(){

     var hash = jQuery(location).attr('hash');
     if(hash=='#loc:tab1') jQuery('#piratitask .nav-tabs a[href="#tab1"]').tab('show');
     else if(hash=='#loc:tab2') jQuery('#piratitask .nav-tabs a[href="#tab2"]').tab('show');
     else if(hash=='#loc:tab3') jQuery('#piratitask .nav-tabs a[href="#tab3"]').tab('show');
     else if(hash=='#loc:tab4') jQuery('#piratitask .nav-tabs a[href="#tab4"]').tab('show');
     else jQuery('#piratitask #nav-tabs li:eq(1) a').tab('show');

     jQuery('#piratitask a[href="#tab1"], #piratitask a[href="#tab2"], #piratitask a[href="#tab3"], #piratitask a[href="#tab4"]').click(function(e){
          e.preventDefault();
          jQuery(this).tab('show');
          jQuery(this).parent().addClass('active');
          if(jQuery(this).attr('href')=='#tab2'){
               piratitask_loadIssues();
          }
          if(jQuery(this).attr('href')=='#tab4'){
               piratitask_loadGroups();
          }
          jQuery(location).attr('hash','loc:'+jQuery(this).attr('href').replace('#',''));
          e.stopPropagation();
     });
}

// validace noveho ukoly
function piratitask_error(txt,raw){
     jQuery('#alert').show();
     
     if(raw){
          jQuery('#errorlist').append(txt);
     } else {
          jQuery('#errorlist').append('<li>'+txt+'</li>');
     }

     var off = jQuery('#tab1').offset();
     jQuery('body').scrollTop(off.top-25);
}
function piratitask_validate(){
     jQuery('#alert').hide();
     jQuery('#errorlist').empty();
     var stat = true;
     // title
     if(!jQuery('#title').val()){
          piratitask_error(LANG.plugins.piratitask.title_blank,false);
          stat = false;
     }
     if(jQuery('#title').val().length>255){
          piratitask_error(LANG.plugins.piratitask.title_long,false);
          stat = false;
     }
     // priority
     if(jQuery('#priority').val()<0 || jQuery('#priority').val()>3){
          piratitask_error(LANG.plugins.piratitask.priority_bad,false);
          stat = false;
     }
     // term
     if(jQuery('#term').val() && !jQuery('#term').val().match(/^\d{1,2}\.\d{1,2}\.\d{4}$/)){
          piratitask_error(LANG.plugins.piratitask.term_format,false);
          stat = false;
     }
     // content
     if(!jQuery('#wiki__text').val()){
          piratitask_error(LANG.plugins.piratitask.content_empty,false);
          stat = false;
     }
     return stat;
}

// funkcnost formulare pro novy ukol
function piratitask_initNewIssue(){
     // setup
     jQuery('#sponsor').typeahead({
          source: piratitask_typeahead,
          minLength: 2
     });
     jQuery('#warn').typeahead({
          source: piratitask_typeahead,
          minLength: 2,
          updater: function(item){
               // add
               jQuery('<li class="btn" onclick="jQuery(this).remove();">'+item+'</li>').appendTo('#warnlist');
          }
     });
     jQuery('#datepicker').datetimepicker({
          pickTime: false,
          language: 'cs',
          weekStart: 1,
          format: 'dd.MM.yyyy'
     });

     // submit
     jQuery('#form-newissue').submit(function(){
          if(!piratitask_validate()) return false;

          jQuery('#form-newissue #submit').button('loading');

          var watchmen = new Array();
          jQuery('#warnlist li').each(function(i,item){
               watchmen.push(jQuery(this).text());
          });

          jQuery.post(
               /*DOKU_BASE+*/'/lib/exe/ajax.php',
               {
                    call: 'piratitask_save',
                    id: JSINFO.id,
                    title: jQuery('#title').val(),
                    sponsor: jQuery('#sponsor').val(),
                    priority: jQuery('#priority').val(),
                    watches: watchmen,
                    term: jQuery('#term').val(),
                    content: jQuery('#wiki__text').val(),
                    sectok: jQuery('#form-newissue-sectok').val()
               }, function(data){
                    jQuery('#form-newissue #submit').button('reset');
                    // if good reset
                    if(data){
                         piratitask_error(data,true);
                    } else {
                         // all ok - reset
                         jQuery('#title').val('');
                         jQuery('#sponsor').val('');
                         jQuery('#priority').val(1);
                         jQuery('#warnlist').empty();
                         jQuery('#term').val('');
                         jQuery('#wiki__text').val('');
                         //
                         jQuery('#success').show();
                         var off = jQuery('#tab1').offset();
                         jQuery('body').scrollTop(off.top-50);
                         jQuery('#success').fadeOut(10000,'easeInExpo');
                    }
               }
          );
          return false;
     });
}

// funkcnost razeni
function piratitask_initSort(){
     var sort = jQuery.cookie('piratitask_sort');
}
function piratitask_sort(col,el){
     var sort = jQuery.cookie('piratitask_sort');
     var re_asc = new RegExp('^'+col+'$');
     var re_desc = new RegExp('^'+col+' DESC$');
     var s = '';

     if(re_asc.test(sort)){
          s = col+' DESC';
     } else if(re_desc.test(sort)){
          s = '';
     } else {
          s = col;
     }

     console.log('sort('+s+');');
     jQuery.cookie('piratitask_sort',s);
     piratitask_loadIssues();
     return false;
}

// funkcnost filtru
function piratitask_initFilter(){
     // napoveda
     jQuery('#btn-filtr-my,#btn-filtr-watch,#btn-filtr-all,#btn-filtr-advanced').tooltip({
          container: 'body'
     });
     // filter window setup
     jQuery('#win-filter').modal({
          show: false
     });
     jQuery('#win-filter').on('hidden',function(){
          jQuery('#fcol').val('');
          jQuery('#fcond').hide();
          jQuery('#fafter').empty();
     });
     jQuery('a[href="#close"]','#win-filter').click(function(){
          jQuery('#win-filter').modal('hide'); return false;
     });
     jQuery('a[href="#addcond"]','#win-filter').click(function(){
          var col = jQuery('#fcol').val();
          var cond = jQuery('#fcond').val();
          var val = jQuery('#fval').val();
          var filter = jQuery.cookie('piratitask_filter');
          switch(col){
               case 'id':
                    if(val=='') return;
                    if(Math.floor(val) == val && jQuery.isNumeric(val)){ // integer
                         switch(cond){
                              case '1': c='<'; break;
                              case '2': c='>'; break;
                              default: c='=';
                         }
                         addFilter('id',c,val);
                    }
                    break;
               case 'status': if(val>=0 && val<=3) addFilter('status','=',val); break;
               case 'title': if(val=='') return; val = val.replace(',','.'); addFilter('title','=',val); break;
               case 'priority':
                    if(val>=0 && val<=3){
                         switch(cond){
                              case '1': c='<'; break;
                              case '2': c='>'; break;
                              default: c='=';
                         }
                         addFilter('priority',c,val);
                    }
                    break;
               case 'worker': if(val=='') val='-'; addFilter('worker','=',val); break;
               case 'term':
                    if(val=='') val='-';
                    switch(cond){
                         case '1': c='<'; break;
                         case '2': c='>'; break;
                         default: c='=';
                    }
                    addFilter('term',c,val);
                    break;
          }
          jQuery('#win-filter').modal('hide');
          //setupFilter();
          return false;
     });
     jQuery('#fcol').change(function(){
          jQuery('#fafter').empty();
          var col = jQuery(this).val();
          switch(col){
               case 'id':
                    jQuery('#fafter').append('<select id="fcond" class="input-medium"></select>&nbsp;');
                    jQuery('#fcond').append('<option value="0">'+LANG.plugins.piratitask.is+'</option>');
                    jQuery('#fcond').append('<option value="1">'+LANG.plugins.piratitask.lt+'</option>');
                    jQuery('#fcond').append('<option value="2">'+LANG.plugins.piratitask.gt+'</option>');
                    jQuery('#fafter').append('<input id="fval" type="text" class="input-mini">');
                    break;
               case 'status':
                    jQuery('#fafter').append('<select id="fcond" class="input-mini"></select>&nbsp;');
                    jQuery('#fcond').append('<option value="0">'+LANG.plugins.piratitask.is+'</option>');
                    jQuery('#fafter').append('<select id="fval"></select>');
                    jQuery('#fval').append('<option value="0">'+LANG.plugins.piratitask.open+'</option>');
                    jQuery('#fval').append('<option value="1">'+LANG.plugins.piratitask.close+'</option>');
                    jQuery('#fval').append('<option value="2">'+LANG.plugins.piratitask.duplicate+'</option>');
                    jQuery('#fval').append('<option value="3">'+LANG.plugins.piratitask.invalid+'</option>');
                    break;
               case 'priority':
                    jQuery('#fafter').append('<select id="fcond" class="input-medium"></select>&nbsp;');
                    jQuery('#fcond').append('<option value="0">'+LANG.plugins.piratitask.is+'</option>');
                    jQuery('#fcond').append('<option value="1">'+LANG.plugins.piratitask.lt+'</option>');
                    jQuery('#fcond').append('<option value="2">'+LANG.plugins.piratitask.gt+'</option>');
                    jQuery('#fafter').append('<select id="fval" class="input-small"></select>');
                    jQuery('#fval').append('<option value="0">'+LANG.plugins.piratitask.low+'</option>');
                    jQuery('#fval').append('<option value="1">'+LANG.plugins.piratitask.middle+'</option>');
                    jQuery('#fval').append('<option value="2">'+LANG.plugins.piratitask.high+'</option>');
                    jQuery('#fval').append('<option value="3">'+LANG.plugins.piratitask.critical+'</option>');
                    break;
               case 'title':
                    jQuery('#fafter').append('<select id="fcond" class="input-small"></select>&nbsp;');
                    jQuery('#fcond').append('<option value="0">'+LANG.plugins.piratitask.contains+'</option>');
                    jQuery('#fafter').append('<input id="fval" type="text" placeholder="hodnota" class="input-medium">');
                    break;
               case 'worker':
                    jQuery('#fafter').append('<select id="fcond" class="input-mini"></select>&nbsp;');
                    jQuery('#fcond').append('<option value="0">je</option>');
                    jQuery('#fafter').append('<input id="fval" type="text" placeholder="Uživatel">');
                    jQuery('#fval').typeahead({
                         source: piratitask_typeahead_u,
                         minLength: 2
                    });
                    break;
               case 'term':
                    jQuery('#fafter').append('<select id="fcond" class="input-medium"></select>&nbsp;');
                    jQuery('#fcond').append('<option value="0">je</option>');
                    jQuery('#fcond').append('<option value="1">je menší než</option>');
                    jQuery('#fcond').append('<option value="2">je větší než</option>');
                    jQuery('#fafter').append('<div id="datepickerf" class="input-append"><input type="text" id="fval" class="input-small"><span class="add-on"><i class="icon-calendar"></i></span></div>');
                    jQuery('#datepickerf').datetimepicker({
                         pickTime: false,
                         language: 'cs',
                         weekStart: 1,
                         format: 'dd.MM.yyyy'
                    });
                    break;
          }
     });

     // filtr & page defaults
     var filter = jQuery.cookie('piratitask_filter');
     var page = jQuery.cookie('piratitask_page');
     if(filter==null){
          // defaults
          addFilter('status','=','0');
     }
     if(page==null){
          page=0;
          jQuery.cookie('piratitask_page',page);
     }

     // setup filter interface
     setupFilter();

     // setup filter buttons
     jQuery('#btn-filtr-my').click(function(e){
          if(jQuery(this).hasClass('active')) removeFilter('worker','=',JSINFO.user.username);
          else addFilter('worker','=',JSINFO.user.username);
          return false;
     });
     jQuery('#btn-filtr-watch').click(function(){
          if(jQuery(this).hasClass('active')) removeFilter('watcher','=',JSINFO.user.username);
          else addFilter('watcher','=',JSINFO.user.username,'and');
          return false;
     });
     jQuery('#btn-filtr-open').click(function(){
          if(jQuery(this).hasClass('active')) removeFilter('status','=','0');
          else {
               removeFilter('status','=','1');
               addFilter('status','=','0');
          }
          return false;
     });
     jQuery('#btn-filtr-close').click(function(){
          if(jQuery(this).hasClass('active')) removeFilter('status','=','1');
          else {
               removeFilter('status','=','0');
               addFilter('status','=','1');
          }
          return false;
     });

     jQuery('#btn-filtr-advanced').click(function(){
          jQuery('#win-filter').modal('show');
          return false;
     });

     // setup tags filter
     //jQuery('#')
}

function addFilter(col,cond,val,jointype){
     console.log('addFilter('+col+','+cond+','+val+');');
     removeFilter(col,cond,val,false); // duplicity
     var filter = jQuery.cookie('piratitask_filter');
     if(filter==null) filter='';
     var f = col+','+cond+','+val;
     if(filter!=''){
          if(jointype=='and') filter += ',and,';
          else filter += ',or,';
     }
     filter += f;
     jQuery.cookie('piratitask_filter',filter);
     setupFilter();
     piratitask_loadIssues();
}
function removeFilter(col,cond,val,load){
     var filter = jQuery.cookie('piratitask_filter');
     if(filter!=null){
          var f = col+','+cond+','+val;
          var re = new RegExp('(,or,)*'+f,'g');
          filter = filter.replace(re,'');
          filter = ftrim(filter,',');
          filter = ftrim(filter,'or');
          filter = ftrim(filter,'and');
          filter = ftrim(filter,',');
          jQuery.cookie('piratitask_filter',filter);
          if(load!=false){
               setupFilter();
               piratitask_loadIssues();
          }
     }
}
function ftrim(str,d){
     if(str=='' || str==null) return '';

     c = str.substr(0,d.length);
     while(str!='' && c==d){
          str = str.substr(d.length);
          c = str.substr(0,d.length);
     }
     c = str.substr(str.length-d.length,d.length);
     while(str!='' && c==d){
          str = str.substr(0,str.length-d.length);
          c = str.substr(str.length-d.length,d.length);
     }

     return str;
}

function piratitask_page(p){
     jQuery.cookie('piratitask_page',p);
     piratitask_loadIssues();
     return false;
}

function setupFilter(){
     var filter = jQuery.cookie('piratitask_filter');
     var sf = filter.split(',');

     // reset
     jQuery('#btn-filtr-open').removeClass('active');
     jQuery('#btn-filtr-close').removeClass('active');
     jQuery('#btn-filtr-my').removeClass('active');
     jQuery('#btn-filtr-watch').removeClass('active');
     jQuery('#filtr').empty();

     var col = '';
     var cond = '';
     var val = '';
     var clu = '';
     var t = 'col';
     for(var i=0;i<sf.length;i++){
          if(sf[i]=='') continue;
          if(t=='col'){ col = sf[i]; t='cond'; }
          else if(t=='cond'){ cond = sf[i]; t='val'; }
          else if(t=='val'){ val = sf[i]; t='clu'; }
          
          if(t=='clu' /*&& sf[i+1]==undefined*/){
               if(sf[i+1]=='or' || sf[i+1]=='and'){
                    clu=sf[i+1];
               }
               t='col'; i++;
               //alert('COL: '+col+', COND: '+cond+', VAL: '+val+', CLU: '+clu);

               // open on
               if(col=='status' && cond=='=' && val=='0') jQuery('#btn-filtr-open').addClass('active');
               if(col=='status' && cond=='=' && val=='1') jQuery('#btn-filtr-close').addClass('active');
               if(col=='worker' && cond=='=' && val==JSINFO.user.username) jQuery('#btn-filtr-my').addClass('active');
               if(col=='watcher' && cond=='=' && val==JSINFO.user.username) jQuery('#btn-filtr-watch').addClass('active');

               // view conditions
               var c = '';
               //if(sf[i]=='or') c = 'nebo';
               //if(sf[i]=='and') c = 'a';
               switch(col){
                    case 'id':
                         switch(cond){
                              case '<': c = LANG.plugins.piratitask.lt; break;
                              case '>': c = LANG.plugins.piratitask.gt; break;
                              default: c = LANG.plugins.piratitask.is;
                         }
                         jQuery('#filtr').append('<p><button class="btn btn-mini" onclick="removeFilter(\'id\',\''+cond+'\',\''+val+'\')">ID - '+c+' - '+val+'</button> <span class="filtr-cond">'+c+'</span></p>');
                         break;
                    case 'status':
                         switch(val){
                              case '1': v = LANG.plugins.piratitask.close;  break;
                              case '2': v = LANG.plugins.piratitask.duplicate; break;
                              case '3': v = LANG.plugins.piratitask.invalid; break;
                              default: v = LANG.plugins.piratitask.open;
                         }
                         jQuery('#filtr').append('<p><button class="btn btn-mini" onclick="removeFilter(\'status\',\'=\',\''+val+'\')">'+LANG.plugins.piratitask.status+' - '+LANG.plugins.piratitask.is+' - '+v+'</button> <span class="filtr-cond">'+c+'</span></p>');
                         break;
                    case 'title':
                         jQuery('#filtr').append('<p><button class="btn btn-mini" onclick="removeFilter(\'title\',\'=\',\''+val+'\')">'+LANG.plugins.piratitask.title+' - '+LANG.plugins.piratitask.contains+' - '+val+'</button> <span class="filtr-cond">'+c+'</span></p>');
                         break;
                    case 'priority':
                         switch(val){
                              case '1': v = LANG.plugins.piratitask.middle;  break;
                              case '2': v = LANG.plugins.piratitask.high; break;
                              case '3': v = LANG.plugins.piratitask.critical; break;
                              default: v = LANG.plugins.piratitask.low;
                         }
                         switch(cond){
                              case '<': c = LANG.plugins.piratitask.lt; break;
                              case '>': c = LANG.plugins.piratitask.gt; break;
                              default: c = LANG.plugins.piratitask.is;
                         }
                         jQuery('#filtr').append('<p><button class="btn btn-mini" onclick="removeFilter(\'priority\',\''+cond+'\',\''+val+'\')">'+LANG.plugins.piratitask.priority+' - '+c+' - '+v+'</button> <span class="filtr-cond">'+c+'</span></p>');
                         break;
                    case 'worker':
                         jQuery('#filtr').append('<p><button class="btn btn-mini" onclick="removeFilter(\'worker\',\'=\',\''+val+'\')">'+LANG.plugins.piratitask.assign+' - '+LANG.plugins.piratitask.is+' - '+(val=='-'?LANG.plugins.piratitask.empty:val)+'</button> <span class="filtr-cond">'+c+'</span></p>');
                         break;
                    case 'watcher':
                         jQuery('#filtr').append('<p><button class="btn btn-mini" onclick="removeFilter(\'watcher\',\'=\',\''+val+'\')">'+LANG.plugins.piratitask.watching+' - '+LANG.plugins.piratitask.is+' - '+(val=='-'?LANG.plugins.piratitask.empty:val)+'</button> <span class="filtr-cond">'+c+'</span></p>');
                         break;
                    case 'term':
                         switch(cond){
                              case '<': c = LANG.plugins.piratitask.lt; break;
                              case '>': c = LANG.plugins.piratitask.gt; break;
                              default: c = LANG.plugins.piratitask.is;
                         }
                         jQuery('#filtr').append('<p><button class="btn btn-mini" onclick="removeFilter(\'term\',\''+cond+'\',\''+val+'\')">'+LANG.plugins.piratitask.term+' - '+c+' - '+(val=='-'?LANG.plugins.piratitask.empty:val)+'</button> <span class="filtr-cond">'+c+'</span></p>');
               }

               // reset filter
               col=''; cond=''; val=''; clu='';
          }
     }
}

// 
function piratitask_loadIssues(){
     jQuery('#issues').empty();
     jQuery('#issues').removeClass('issueempty');
     jQuery('#issues').text(LANG.plugins.piratitask.loading);
     jQuery('#issues').addClass('issueload');

     var page = jQuery.cookie('piratitask_page');
     var filter = jQuery.cookie('piratitask_filter');
     var sort = jQuery.cookie('piratitask_sort');

     console.log('loadIssues('+filter+')');

     jQuery('#issues').load(/*DOKU_BASE+*/'/lib/exe/ajax.php',
          {
               call: 'piratitask_list',
               id: JSINFO.id,
               page: page,
               filter: filter,
               sort: sort
          }, function(data){
               // init helps
               jQuery('.task-tooltip').tooltip({
                    container: 'body'
               });
               //
               jQuery('#issues').removeClass('issueload');
               if(data==''){
                    jQuery('#issues').addClass('issueempty');
                    jQuery('#issues').text(LANG.plugins.piratitask.notask);
               }
          }
     );

}

// 
function piratitask_initSettings(){
     jQuery('#mailme').change(function(){

          var val = jQuery(this).val();

          jQuery.post(
               /*DOKU_BASE+*/'/lib/exe/ajax.php',
               {
                    call: 'piratitask_settings',
                    id: JSINFO.id,
                    mail: val
               }, function(data){
                    // if good reset
                    if(data){

                    } else {
                         // all ok - reset
                    }
               }
          );
     });
}

// 
function btnoff(btn){
     btn.removeClass('active');
     btn.removeClass('btn-info');
     btn.removeClass('btn-danger');
     jQuery('i',btn).removeClass('icon-white');
}
function btnon(btn,cls){
     btn.addClass('active');
     btn.addClass(cls);
     jQuery('i',btn).addClass('icon-white');
}
function btnwatch(taskid,btn){
     btn = jQuery(btn);
     jQuery.post(
          /*DOKU_BASE+*/'/lib/exe/ajax.php',
          {
               call: 'piratitask_watch',
               id: JSINFO.id,
               taskid: taskid
          },function(data){ 
               var w = jQuery('#piratitask-watchers');
               if(w.length>0){ // task detail
                    var wtext = parseInt(w.text());
               }
               if(data.status=='off'){
                    // task detail
                    if(w.length>0){
                         wtext = wtext-1;
                         jQuery('#piratitask-watchers-win ol li:contains("'+data.fullname+'")').remove();
                    }
                    btnoff(btn); // styles
                    // change help
                    btn.attr('data-original-title',LANG.plugins.piratitask.startwatch);
                    btn.attr('title',LANG.plugins.piratitask.startwatch);
               }
               if(data.status=='on'){
                    // task detail
                    if(w.length>0){
                         wtext = wtext+1;
                         jQuery('#piratitask-watchers-win ol').append('<li>'+data.fullname+'</li>');
                    }
                    btnon(btn,'btn-info'); // styles
                    // change help
                    btn.attr('data-original-title',LANG.plugins.piratitask.stopwatch);
                    btn.attr('title',LANG.plugins.piratitask.stopwatch);
               }
               // task detail
               if(w.length>0){
                    if(wtext>0){
                         w.html('<button onclick="return piratitask_winWatchers();" class="btn btn-mini">'+wtext+'</button>');
                    } else w.text(wtext);
               }
          },'json'
     );
}
function btnwork(taskid,btn){
     btn = jQuery(btn);
     jQuery.post(
          /*DOKU_BASE+*/'/lib/exe/ajax.php',
          {
               call: 'piratitask_work',
               id: JSINFO.id,
               taskid: taskid
          },function(data){
               var w = jQuery('.piratitask-worker',btn.parent().parent());
               if(data.status=='off'){
                    btnoff(btn); // styles
                    //w.text(LANG.plugins.piratitask.nobody);
                    w.text('-');
                    // change help
                    btn.attr('data-original-title',LANG.plugins.piratitask.startwork);
                    btn.attr('title',LANG.plugins.piratitask.startwork);
               }
               if(data.status=='on'){
                    btnon(btn,'btn-danger'); // styles
                    w.text(data.fullname);
                    // change help
                    btn.attr('data-original-title',LANG.plugins.piratitask.stopwork);
                    btn.attr('title',LANG.plugins.piratitask.stopwork);
               }
          },'json'
     );
}

/* task info */
function piratitask_initInfo(){
     // watchers
     jQuery('#piratitask-watchers-win').modal({
          show: false
     });
     jQuery('a[href="#close"]','#piratitask-watchers-win').click(function(){
          jQuery('#piratitask-watchers-win').modal('hide'); return false;
     });

     /* changeTerm */
     if(jQuery('#datepicker').length>0){
          jQuery('#datepicker').datetimepicker({
               pickTime: false,
               language: 'cs',
               weekStart: 1,
               format: 'dd.MM.yyyy',
               startDate: new Date()
          });
          jQuery('#datepicker').on('changeDate',function(e){
               var date = e.localDate;
               var newterm = (date.getDate()+'.'+(date.getMonth()+1)+'.'+date.getFullYear());
               // term
               if(newterm.match(/^\d{1,2}\.\d{1,2}\.\d{4}$/)){
                    jQuery.post(
                         /*DOKU_BASE+*/'/lib/exe/ajax.php',
                         {
                              call: 'piratitask_updateparam',
                              id: JSINFO.id,
                              type: 'term',
                              term: newterm
                         },function(data){
                              if(data.status=='ok'){
                                   jQuery('#datepicker button').text(newterm);
                                   piratitask_loadComments();                        
                              } else alert(data.msg);
                         },'json'
                    );
               } else alert(LANG.plugins.piratitask.term_format);
          });
          jQuery('#datepicker').removeClass('input-append');
          jQuery('#datepicker i').remove();

          // set actuall
          var date = jQuery('#datepicker').text().split('.');
          var picker = jQuery('#datepicker').data('datetimepicker');
          picker.setDate(new Date(Date.UTC(date[2], date[1]-1, date[0], 0, 0)));
     }
}
function piratitask_winWatchers(){
     jQuery('#piratitask-watchers-win').modal('show');
     return false;
}
function piratitask_changeStatus(a,ids){
     var btngroup = jQuery(a).parent().parent().parent();
     jQuery.post(
          /*DOKU_BASE+*/'/lib/exe/ajax.php',
          {
               call: 'piratitask_updateparam',
               id: JSINFO.id,
               type: 'status',
               ids: ids
          }, function(data){
               if(data.status=='ok'){
                    jQuery('button',btngroup).html(data.msg[0]+' <span class="caret"></span>');
                    jQuery('ul',btngroup).empty();
                    for(var i=0;i<data.msg[1].length;i++)
                         jQuery('ul',btngroup).append('<li><a href="#" onclick="return piratitask_changeStatus(this,'+data.msg[1][i][0]+');">'+data.msg[1][i][1]+'</a></li>');
                    piratitask_loadComments();
               } else {
                    alert(data.msg);
               }
          },'json'
     );
     return false;
}
function piratitask_changePriority(a,ids){
     var btngroup = jQuery(a).parent().parent().parent();
     jQuery.post(
          /*DOKU_BASE+*/'/lib/exe/ajax.php',
          {
               call: 'piratitask_updateparam',
               id: JSINFO.id,
               type: 'priority',
               ids: ids
          }, function(data){
               if(data.status=='ok'){
                    jQuery('button',btngroup).html(data.msg[0]+' <span class="caret"></span>');
                    jQuery('ul',btngroup).empty();
                    for(var i=0;i<data.msg[1].length;i++)
                         jQuery('ul',btngroup).append('<li><a href="#" onclick="return piratitask_changePriority(this,'+data.msg[1][i][0]+');">'+data.msg[1][i][1]+'</a></li>');
                    piratitask_loadComments();
               } else {
                    alert(data.msg);
               }
          },'json'
     );
     return false;

}

/* comments */
function piratitask_initComments(){ 

     // add new comment
     jQuery('#form-addcomment').submit(function(){
          jQuery('#form-addcomment #submit').button('loading');
          jQuery.post(
               /*DOKU_BASE+*/'/lib/exe/ajax.php',
               {
                    call: 'piratitask_addcomm',
                    id: JSINFO.id,
                    content: jQuery('#piratitask-commtext').val()
               }, function(data){
                    if(data.status=='ok'){
                         piratitask_loadComments();
                         jQuery('#piratitask-commtext').val('');
                    } else alert(data.msg);
                    jQuery('#form-addcomment #submit').button('reset');
               },'json'
          );
          return false;
     });

     piratitask_loadComments();
}
function piratitask_loadComments(){
     jQuery('#piratitask-commlist').load(/*DOKU_BASE+*/'/lib/exe/ajax.php',
          {
               call: 'piratitask_comments',
               id: JSINFO.id
          }, function(data){
               
          }
     );
}

/* edit task */
function piratitask_validate_edit(){
     jQuery('#alert').hide();
     jQuery('#errorlist').empty();
     var stat = true;
     // title
     if(!jQuery('#title').val()){
          piratitask_error(LANG.plugins.piratitask.title_blank,false);
          stat = false;
     }
     if(jQuery('#title').val().length>255){
          piratitask_error(LANG.plugins.piratitask.title_long,false);
          stat = false;
     }
     // content
     if(!jQuery('#wiki__text').val()){
          piratitask_error(LANG.plugins.piratitask.content_empty,false);
          stat = false;
     }
     return stat;
}
function piratitask_initEdit(){
     jQuery('#form-editissue').submit(function(){
          if(!piratitask_validate_edit()) return false;

          jQuery('#form-editissue #submit').button('loading');


          jQuery.post(
               /*DOKU_BASE+*/'/lib/exe/ajax.php',
               {
                    call: 'piratitask_update',
                    id: JSINFO.id,
                    title: jQuery('#title').val(),
                    content: jQuery('#wiki__text').val()
               }, function(data){
                    
                    // if bad reset, good redirect
                    if(data.status=='ok'){
                         var url = window.location.href.split('?');
                         window.location.replace(url[0]);
                    } else {
                         if(data.errors.length>0){
                              for(var i=0;i<data.errors.length;i++){
                                   piratitask_error(data.errors[i],false);
                              }
                         } else alert(data.msg);
                         jQuery('#form-editissue #submit').button('reset');
                    }
               },'json'
          );


          return false;
     });
}

function piratitask_initGroupEvents(){
     jQuery('#group_list input').change(function(){

          var grpcnt = jQuery('span',jQuery(this).parent());
          var ch = jQuery(this).prop('checked');
          var txt = jQuery.trim(jQuery(this).parent().text());
          var cnt = txt.match(/(\d)/);
          cnt = parseInt(cnt[1]);
          //
          var pos = jQuery(this).position();
          jQuery('#group_list').parent().addClass('groupload');
          jQuery('#group_list').parent().css('background-position-y',pos.top-333);
          
          jQuery.post(
               /*DOKU_BASE+*/'/lib/exe/ajax.php',
               {
                    call: 'piratitask_changegroup',
                    id: JSINFO.id,
                    groups: jQuery('#group_list :checked').map(function(){ return jQuery(this).val(); }).toArray()
               }, function(data){
                    jQuery('#group_list').parent().removeClass('groupload');
                    jQuery('#group_list label').removeClass('in');
                    jQuery('#group_list :checked').each(function(){
                         jQuery(this).parent().addClass('in');
                    });
               
                    if(ch){
                         grpcnt.text(++cnt);
                    } else {
                         grpcnt.text(--cnt);
                    }
                    //var grp = jQuery(this).parent();
                    //var content = grp.html();
                    //content = content.replace('/'+txt+'/','AAA');
                    //grp.html(content);
                    //text('AAA');
               }
          );
     });
}

function piratitask_loadGroups(){
     jQuery('#group_list').empty();
     jQuery('#group_list').removeClass('issueempty');
     jQuery('#group_list').text(LANG.plugins.piratitask.loading);
     jQuery('#group_list').addClass('issueload');

     jQuery('#group_list').load(/*DOKU_BASE+*/'/lib/exe/ajax.php',
          {
               call: 'piratitask_groups',
               id: JSINFO.id
          }, function(data){
               jQuery('#group_list').removeClass('issueload');
               if(data==''){
                    jQuery('#group_list').addClass('issueempty');
                    jQuery('#group_list').text(LANG.plugins.piratitask.nogroup);
               } else {
                    jQuery('#group_list').removeClass('issueempty');
               }

               piratitask_initGroupEvents();
          }
     );    
}
function piratitask_initGroups(){
     
     // add new - submit
     jQuery('#form-newgroup').submit(function(){
          jQuery('#form-newgroup button').button('loading');

          jQuery.post(
               /*DOKU_BASE+*/'/lib/exe/ajax.php',
               {
                    call: 'piratitask_newgroup',
                    id: JSINFO.id,
                    //parent: jQuery('#form-newgroup select[name="parent"]').val(),
                    title: jQuery('#form-newgroup input[name="title"]').val(),
               }, function(data){
                    jQuery('#form-newgroup button').button('reset');
                    // if good reset
                    if(data==''){
                         jQuery('#form-newgroup input[name="title"]').val('');
                         piratitask_loadGroups();
                    } else {
                         // all ok - reset
                         //jQuery('#form-newgroup select[name="parent"]').val('');
                         piratitask_error(data,true);
                    }
               }
          );
          return false;
     });

     // load
     piratitask_loadGroups();
}

