function calendar_commands(){this.init=function(){rcmail.register_command("plugin.calendar_newevent",calendar_commands.newevent,!0);rcmail.register_command("plugin.calendar_reload",calendar_commands.reload,!0);rcmail.register_command("plugin.calendar_runTests",calendar_commands.runTests,!0);rcmail.register_command("plugin.calendar_switchCalendar",calendar_commands.switchCalendar,!0);rcmail.register_command("plugin.exportEventsZip",calendar_commands.exportEventsZip,!0);rcmail.register_command("plugin.importEvents",
calendar_commands.importEvents,!0);rcmail.register_command("plugin.calendar_print",calendar_commands.previewPrintEvents,!0);rcmail.register_command("plugin.calendar_filterEvents",calendar_commands.filterEvents,!0);rcmail.register_command("plugin.calendar_do_print",calendar_commands.printEvents,!0);rcmail.register_command("plugin.calendar_toggle_view",calendar_commands.toggleView,!0);this.scheduleSync()};this.ksort=function(b){var e=[],h={},c;for(c in b)e.push(c);e.sort();for(c in e)h[e[c]]=b[e[c]];
return h};this.newevent=function(){d=36E5*Math.floor((new Date).getTime()/1E3/3600*rcmail.env.calsettings.settings.timeslots)/rcmail.env.calsettings.settings.timeslots+36E5/rcmail.env.calsettings.settings.timeslots;calendar_callbacks.dayClick(new Date(d),!1,!0,!1,rcmail.env.calsettings)};this.compose=function(b){b+="&_id="+rcmail.env.edit_event.id;rcmail.env.compose_newwindow?opencomposewindowcaller(b):document.location.href=b};this.edit_event=function(b,e){rcmail.env.edit_recurring=b;"resize"==e?
calendar_callbacks.eventResize(rcmail.env.edit_event,rcmail.env.edit_delta,rcmail.env.calsettings):"move"==e?calendar_callbacks.eventDrop(rcmail.env.edit_event,rcmail.env.edit_dayDelta,rcmail.env.minuteDelta,rcmail.env.allDay,rcmail.env.revertFunc,rcmail.env.calsettings):calendar_callbacks.eventClick(rcmail.env.edit_event,rcmail.env.calsettings);$("#calendaroverlay").hide();$("#calendaroverlay").html("")};this.edit_recurring_html=function(b){var e;e="<div id='recurringdialog'><br /><fieldset><legend>"+
rcmail.gettext("calendar.applyrecurring")+"</legend><p>";e=e+"<input class='button' type='button' onclick='calendar_commands.edit_event(\"initial\",\""+b+"\")' value='&bull;' />&nbsp;<span>"+rcmail.gettext("calendar.editall")+"</span><br />";e=e+"<input class='button' type='button' onclick='calendar_commands.edit_event(\"future\",\""+b+"\")' value='&bull;' />&nbsp;<span>"+rcmail.gettext("calendar.editfuture")+"</span><br />";e=e+"<input class='button' type='button' onclick='calendar_commands.edit_event(\"single\",\""+
b+"\")' value='&bull;' />&nbsp;<span>"+rcmail.gettext("calendar.editsingle")+"</span>";e=e+'</p></fieldset><div style=\'float: right\'><a href=\'#\' onclick=\'$("#calendar").fullCalendar("refetchEvents");$("#calendaroverlay").html("");$("#calendaroverlay").hide()\'>'+rcmail.gettext("calendar.cancel")+"</a></div></div>";$("#calendaroverlay").html(e);$("#calendaroverlay").show()};this.reload=function(){rcmail.http_request("plugin.calendar_renew")};this.scheduleSync=function(){window.setTimeout("calendar_commands.syncCalendar()",
1E4)};this.syncCalendar=function(){rcmail.http_request("plugin.calendar_syncEvents");window.setTimeout("calendar_commands.syncCalendar()",6E4)};this.runTests=function(){return!0};this.triggerSearch=function(){var b=$("#calsearchfilter").val();for($("#calsearchset").hide();-1<b.indexOf("\\");)b=b.replace("\\","");$("#calsearchfilter").val(b);if(2<b.length&&b!=rcmail.env.cal_search_string){var e=DstDetect($("#calendar").fullCalendar("getDate"));e[0]||(e[0]=new Date(0));e[1]||(e[1]=new Date(0));end=
new Date($("#calendar").fullCalendar("getDate").getTime()+31536E6);rcmail.env.cal_search_string=b;rcmail.env.replication_complete||rcmail.display_message(rcmail.gettext("calendar.replicationincomplete"),"notice");rcmail.http_post("plugin.calendar_searchEvents","_str="+b)}else $("#calsearchdialog").dialog("close")};this.searchFields=function(b){""==b&&$("#cal_search_field_summary").attr("checked","checked");rcmail.env.cal_search_string="";rcmail.http_post("plugin.calendar_searchSet",b)};this.gotoDate=
function(b,e){b=parseInt(b);try{var h=60*-((new Date).getTimezoneOffset()-(new Date(1E3*b)).getTimezoneOffset())}catch(c){h=0}h=new Date(1E3*(b+h));e?($("#rcmrow"+e).addClass("selected"),$("#rcmmatch"+e).removeClass("calsearchmatch"),$("#rcmmatch"+e).addClass("calsearchmatchselected"),rcmail.env.calsearch_id=e):rcmail.env.calsearch_id=null;$("#jqdatepicker").datepicker("setDate",h);$("#calendar").fullCalendar("gotoDate",$.fullCalendar.parseDate(h));$("#upcoming").fullCalendar("gotoDate",$.fullCalendar.parseDate(h))};
this.switchCalendar=function(){if(rcmail.env.replication_complete){var b=$("#calswitch"),e=b.find("select[name='_caluser']"),h=b.find("input[name='_token']"),c=rcmail.gettext("submit","calendar"),f=rcmail.gettext("cancel","calendar"),i={};i[c]=function(){rcmail.env.calendar_msgid=rcmail.set_busy(!0,"loading");rcmail.http_post("plugin.calendar_switch_user","_caluser="+e.val()+"&_token="+h.val());rcmail.env.cal_search_string="";$("#calendaroverlay").show();b.dialog("close")};i[f]=function(){b.dialog("close")};
b.dialog({modal:!1,title:rcmail.gettext("switch_calendar","calendar"),width:500,close:function(){b.dialog("destroy");b.hide()},buttons:i}).show()}else rcmail.display_message(rcmail.gettext("backgroundreplication","calendar"),"error")};this.exportEventsZip=function(){return!0};this.importEvents=function(){calendar_callbacks.dayClick(new Date,1,{start:new Date},"agendaWeek",rcmail.env.calsettings);for(var b=0;5>b;b++)calendar_gui.initTabs(2,b);$("#event").tabs("select",2);$("#event").tabs("disable",
0);$("#ui-dialog-title-event").html(rcmail.gettext("calendar.import"))};this.previewPrintEvents=function(){var b;b="./?_task=dummy&_action=plugin.calendar_print&_view="+escape($("#calendar").fullCalendar("getView").name.replace("agenda","basic"));b=b+"&_date="+$("#calendar").fullCalendar("getDate").getTime()/1E3;return(mycalpopup=window.open(b,"Print","width=740,height=740,location=0,resizable=1,scrollbars=1"))?(mycalpopup.focus(),rcmail.env.calpopup=!0):!1};this.filterEvents=function(){var b=$("#calfilter"),
e=rcmail.gettext("submit","calendar"),h=rcmail.gettext("cancel","calendar"),c=rcmail.gettext("removefilters","calendar"),f={};f[e]=function(){rcmail.http_post("plugin.calendar_setfilters",$("#filter").serialize());rcmail.env.cal_search_string="";b.dialog("close")};f[c]=function(){$("#calfilter input[type=checkbox]").each(function(){$(this).prop("checked",!1)});rcmail.http_post("plugin.calendar_setfilters","");rcmail.env.cal_search_string="";b.dialog("close")};f[h]=function(){b.dialog("close")};b.dialog({modal:!1,
title:rcmail.gettext("filter_events","calendar"),width:500,close:function(){b.dialog("destroy");b.hide()},buttons:f}).show()};this.printEvents=function(){$("#toolbar").hide();self.print();$("#toolbar").show();return!0};this.toggleView=function(){"agendalist"==rcmail.env.calendar_print_curview?(rcmail.env.calendar_print_curview="calendar",$("#agendalist").hide(),$("#calendar").show(),$("#calendar").fullCalendar("render")):(calendar_commands.createAgendalist(),$("#agendalist").show(),$("#calendar").hide(),
rcmail.env.calendar_print_curview="agendalist")};this.createAgendalist=function(){var b=[],e=[],h=[],c=[];h.Sun=rcmail.env.calsettings.settings.days_short[0];h.Mon=rcmail.env.calsettings.settings.days_short[1];h.Tue=rcmail.env.calsettings.settings.days_short[2];h.Wed=rcmail.env.calsettings.settings.days_short[3];h.Thu=rcmail.env.calsettings.settings.days_short[4];h.Fri=rcmail.env.calsettings.settings.days_short[5];h.Sat=rcmail.env.calsettings.settings.days_short[6];var f,i,k,j,l='<tr><th width="1%" class="day">'+
rcmail.gettext("day","calendar")+"</th>",n="<tr>";myevents=$("#calendar").fullCalendar("clientEvents");for(var a in myevents)if(c[c.length]=myevents[a],myevents[a].end)for(clone=jQuery.extend({},myevents[a]);clone.start.getTime()<clone.end.getTime()-864E5;)f=new Date(clone.start.getFullYear(),clone.start.getMonth(),clone.start.getDate(),0,0,0),clone=jQuery.extend({},myevents[a]),clone.start=new Date(f.getTime()+864E5),1==clone.start.getHours()&&(clone.start=new Date(clone.start.getTime()-36E5)),23==
clone.start.getHours()&&(clone.start=new Date(clone.start.getTime()+36E5)),c[c.length]=clone;myevents=[];for(a in c)f="",c[a].title&&(f=c[a].title),myevents[c[a].start.getTime()+"-"+f+"-"+a]=c[a];var c=calendar_commands.ksort(myevents),o=$("#calendar").fullCalendar("getView").visStart.getTime(),p=$("#calendar").fullCalendar("getView").visEnd.getTime(),g=0;for(a in c)if(c[a].end||(c[a].end=c[a].start),f=c[a].className,c[a].classNameDisp&&(f=c[a].classNameDisp),"string"==typeof f&&"undefined"==typeof b[f])b[f]=
!0,g++,e[g]=f==rcmail.gettext("default","calendar")?"":f;e.sort();for(a in e)f=e[a],""==f&&(f=rcmail.gettext("default","calendar")),l=l+'<th width="'+parseInt(100/e.length)+'%">'+f+"</th>";b=10*parseInt(700/e.length/10);rcmail.env.cal_print_cols=e.length;l+="</tr>";n="";cats=[];for(a in c)if(c[a]&&c[a].start&&c[a].start.getTime()>=o||c[a]&&c[a].end&&c[a].end.getTime()<=p)parseInt(c[a].start.getTime()/6E4),parseInt(c[a].end.getTime()/6E4),c[a].start.getDay()!=c[a].end.getDay()&&parseInt((new Date(c[a].start.getFullYear(),
c[a].start.getMonth(),c[a].start.getDate(),23,59,59)).getTime()/6E4),f=parseInt((new Date(c[a].start.getFullYear(),c[a].start.getMonth(),c[a].start.getDate(),0,0,0)).getTime()/6E4)+"-",f+=parseInt((new Date(c[a].start.getFullYear(),c[a].start.getMonth(),c[a].start.getDate(),23,59,59)).getTime()/6E4),cats[f]?cats[f][cats[f].length]=c[a]:(cats[f]=[],cats[f][0]=c[a]);c=-1;for(a in cats)if(!(cats[a][0]&&cats[a][0].start.getTime()<o||cats[a][0]&&cats[a][0].start.getTime()>=p)){f=$.fullCalendar.parseDate(cats[a][0].start);
i=$.fullCalendar.formatDate(f,"ddd");f=$.fullCalendar.formatDate(f,js_date_formats[rcmail.env.rc_date_format]);i='<tr><td nowrap width="1%" align="center" class="day">'+h[i]+"<br /><small>("+f+")</small></td>";var m={};for(g in cats[a])k=$.fullCalendar.formatDate(cats[a][g].start,js_time_formats[rcmail.env.rc_time_format]),cats[a][g].end?cats[a][g].start.getTime()==cats[a][g].end.getTime()?(j=k,k=""):0==cats[a][g].start.getHours()&&0==cats[a][g].start.getMinutes()&&23==cats[a][g].end.getHours()&&
59==cats[a][g].end.getMinutes()?(k="",j=rcmail.gettext("all-day","calendar")):j=$.fullCalendar.formatDate(cats[a][g].end,js_time_formats[rcmail.env.rc_time_format]):j="",f=cats[a][g].className,cats[a][g].classNameDisp&&(f=cats[a][g].classNameDisp),f==rcmail.gettext("default","calendar")&&(f=""),content=cats[a][g].title,cats[a][g].location&&(content=content+"<br />------<br /><small>@ "+cats[a][g].location+"</small>"),cats[a][g].description&&(content=content+"<br />------<br /><small>"+cats[a][g].description+
"</small>"),content+='<br />------<br /><table cellspacing="0" cellpadding="0">',""!=k&&(content=content+'<tr><td style="border-style:none"><small>'+rcmail.gettext("start","calendar")+':&nbsp;</small></td><td style="border-style:none"><small>'+k+"</small></td></tr>"),""!=j&&(content=j==rcmail.gettext("all-day","calendar")||""==k?content+'<tr><td style="border-style:none"><small>'+j+"</small></td></tr>":content+'<tr><td style="border-style:none"><small>'+rcmail.gettext("end","calendar")+':&nbsp;</small></td><td style="border-style:none"><small>'+
j+"</small></td></tr>"),content+="</table>",content='<fieldset style="max-width: '+b+50+'px;word-wrap: break-word;" class="print">'+content+"</fieldset>",m[f]=m[f]?m[f]+content:content;for(g in e)i=m[e[g]]?i+'<td valign="top">'+m[e[g]]+"</td>":i+"<td>&nbsp;</td>";c++;i+="</tr>";rcmail.env.myevents[c]=a+"_"+i}rcmail.env.myevents.sort();for(a in rcmail.env.myevents)f=rcmail.env.myevents[a].split("_"),i=rcmail.env.myevents[a].replace(f[0]+"_",""),n+=i;l='<tr><th colspan="'+e.length+3+'">'+$.fullCalendar.formatDate(new Date(o),
js_date_formats[rcmail.env.rc_date_format])+" - "+$.fullCalendar.formatDate(new Date(p-1),js_date_formats[rcmail.env.rc_date_format])+"</th></tr>"+l;$("#agendalist").html('<table id="calprinttable" cellspacing="0" width="99%"><thead>'+l+"</thead><tbody>"+n+"</tbody></table>")}}calendar_commands=new calendar_commands;