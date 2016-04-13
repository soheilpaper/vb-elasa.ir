
$.noConflict(true)(function($)
{var ftd=$(document);$('input[name=contestloader]').on('click',function(e)
{var contestid=$(this).attr('data-contestid');$.each(contestsJson[contestid],function(index,value)
{if(value!=vbphrase['n_a'])
{$('#vba_'+index).html(value);$('tr[data-parentfor="'+index+'"]').show();}
else
{$('tr[data-parentfor="'+index+'"]').hide();}});});function animateBox(title,description,onoff)
{var box=$('#ajaxprogress');if(onoff)
{box.css('display','inline-block');box.css('opacity',0);}
if(title)
{$('#progresstitle').html(title);}
if(description)
{$('#progresscontent').html(description);}
box.animate({opacity:(onoff?0.8:0)},{duration:700,complete:function()
{if(!onoff)
{$(this).fadeOut('fast');}}});}});function getParentElement(starterElement,classPattern,testTagName){var currElement=starterElement;var foundElement=null;while(!foundElement&&(currElement=currElement.parentNode)){if((classPattern&&(currElement.className.indexOf(classPattern)!=-1))||(testTagName&&(testTagName.toLowerCase()==currElement.tagName.toLowerCase())))
{foundElement=currElement;}}
return foundElement;}
function tabViewPicker(anchorObject){var clickedTabId=null;var tabtree=getParentElement(anchorObject,"tabslight");var anchorInventory=tabtree.getElementsByTagName("a");var tabIds=[];for(var i=0;(currAnchor=anchorInventory[i]);i++){var anchorId=currAnchor.href.substring(currAnchor.href.indexOf("#")+1,currAnchor.href.length);var parentDd=getParentElement(currAnchor,null,"dd");if(currAnchor==anchorObject){clickedTabId=anchorId;parentDd.className="selected";}else{parentDd.className="";}
tabIds.push(anchorId);}
for(var j=0;(currTabId=tabIds[j]);j++){var elem=document.getElementById("view-"+currTabId);if(!elem){continue;}
if(currTabId==clickedTabId){elem.className="selected_view_section";}else{elem.className="view_section";}}
return false;}