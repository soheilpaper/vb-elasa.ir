
jQuery(function($)
{$('input:checkbox[rel]').change(function()
{var self=$(this),name=self.attr('rel').split('-');name[1].replace(new RegExp('[.\\\\+*?\\[\\^\\]$(){}=!<>|:\\-]','g'),'\\$&');console.log('input:checkbox[name'+name[0]+'="'+name[1]+'"]');var boxes=$('input:checkbox[name'+name[0]+'="'+name[1]+'"]');if(self.is(':checked'))
{boxes.attr('checked','checked');}
else
{boxes.removeAttr('checked');}});});