
vBActivity_Admin_Obj=function()
{this.previews={};this.image_preview=function(name,url)
{var selectMatches=new RegExp('^sel_'+name+'_(\\d+)$','i');var selects=fetch_tags(document,'select');for(var s=0;s<selects.length;s++)
{if(selects[s].id.match(selectMatches))
{this.previews[selects[s].id]={'prev':name,'url':url};previmg=document.createElement('img');previmg.style.paddingLeft='10px';previmg.style.verticalAlign='middle';previmg.id=name;selects[s].onchange=function(){vBActivity_Admin.image_change(this);};selects[s].parentNode.appendChild(previmg);this.image_change(selects[s]);break;};};};this.image_change=function(sel)
{if(data=vBActivity_Admin.previews[sel.id])
{if(sel.options[sel.options.selectedIndex].value)
{YAHOO.util.Dom.get(data['prev']).src=data['url']+sel.options[sel.options.selectedIndex].value;}
else
{YAHOO.util.Dom.get(data['prev']).src='';}};};};var vBActivity_Admin=new vBActivity_Admin_Obj();