/******************************************************************/
/* Buzzea CDP - Comparateur de Prix
/* Author: Buzzea.com
/* Author URI: http://Buzzea.com
/* Copyright 2013 BUZZEA.com
/*
/* Javascript for CDP search autocomplete
/******************************************************************/

function cdp_split( val ) {
			return val.split( / \s*/ ); 
}
		
function cdp_extractLast( term ) {
	return cdp_split( term ).pop();
}
	
jQuery(document).ready(function(){
  //focus on search box
  jQuery("#cdp_boite_de_recherche").focus();
  //clear box on type (onlcik not enough now)
  jQuery("#cdp_boite_de_recherche").one('keydown', function() {
	 if (jQuery("#cdp_boite_de_recherche").val() == 'Recherche'){
		jQuery("#cdp_boite_de_recherche").val('');

	 }
  });
});

function cdp_getAutoCompleteSearch ( requrl ) {
		/************************/
		/*  article search		*/
		/************************/
			
		//attach autocomplete
		jQuery("#cdp_boite_de_recherche")

		.autocomplete({
			
			minLength: 3,
			
			source: function( request, response ) {
					jQuery.getJSON( requrl, {
						term:  request.term 
					}, response );
				},
				
			open: function(){
				jQuery(this).autocomplete('widget').css('z-index', 999999);
				return false;
			},
				
			search: function() {
					// custom minLength
					var term = cdp_extractLast( this.value );
					if ( term.length < 2 ) {
						return false;
					}
				},
				
			focus: function( event, ui) {

					var terms = cdp_split( this.value );
					// remove the current input
					terms.pop();	
					// add the selected item
					terms.push( ui.item.value );
					before = cdp_extractLast(this.value);
					now = terms.join( " " );
					
					/*console.log('this: '+this.value);
					console.log('item: '+ui.item.value);
					console.log('this minus last: '+before);
					console.log('now: '+now);*/
										 
					jQuery(this).keydown(function(event) {
						if (event.keyCode == 32) {
						  space = true;
						  jQuery('.ui-autocomplete').hide(); //hide the suggestion
						}
					  });
					
					jQuery('#cdp_boite_de_recherche').val(now);//update value in box
					// prevent value inserted on focus
					return false;
				},
			select: function( event, ui ) {
				
				var terms = cdp_split( this.value );
				// remove the current input
				terms.pop();
				// add the selected item
				terms.push( ui.item.value );
				// add placeholder to get the space at the end
				now = terms.join( " " ); //was comma
				jQuery('#cdp_boite_de_recherche').val(now);//update value in box
				//submit form on 'enter' or 'click'
				jQuery(event.target.form).submit();
				// prevent value inserted on focus
				return false;
			},
		});	
}


//slide for category children (sub-categories)
//and show/hide info disponibles
jQuery(document).ready(function() { 
	//open
	jQuery(".cdp_categorie").filter(".cdp_jqslider").mouseenter(function(event){
  	 	
		//pour les children
		jQuery(this).children(".cdp_children").stop(true, true).slideToggle(800);
		 event.stopPropagation();
		
		//pour les info disponilbles
		cat_id = jQuery(this).attr('id');
		cat_id = cat_id.replace(/cdp_categorie_/, "");
		
		//cacher tous
		jQuery('div[id^="cdp_articlesdisponible_info_holder_"]').filter(".cdp_jqslider").hide();
		//afficher cela
		jQuery("#cdp_articlesdisponible_info_holder_"+cat_id).filter(".cdp_jqslider").toggle();
		
	});
	
	//close
	jQuery(".cdp_categorie").filter(".cdp_jqslider").mouseleave(function(event){
  	 	jQuery(this).children(".cdp_children").stop(true, true).slideToggle(800);
		 event.stopPropagation();
		 
		//pour les info disponilbles
		cat_id = jQuery(this).attr('id');
		cat_id = cat_id.replace(/cdp_categorie_/, "");
		
	});
	
	//pour les info disponible - cacher
	jQuery('div[id^="cdp_articlesdisponible_info_holder_"]').mouseleave(function(){
  	 	//pour les info disponilbles
		cat_id = jQuery(this).attr('id');
		cat_id = cat_id.replace(/cdp_categorie_/, "");
		jQuery('div[id^="cdp_articlesdisponible_info_holder_"]').filter(".cdp_jqslider").hide();
		 
	});
	
	//pour les info disponible - cacher
	jQuery('div[id="cdp_conteneur"]').mouseleave(function(){
  	 	//pour les info disponilbles
		jQuery('div[id^="cdp_articlesdisponible_info_holder_"]').filter(".cdp_jqslider").hide();
		 
	});
	
	
});	

/*Verifier contact*/
function cdp_checkcontactform()
{
	if (document.cdp_formContact.cdp_nom.value == '')
	{
		// nom
		alert("s'il vous plait fournir votre nom");
		return false;
	}
	else if (document.cdp_formContact.cdp_prenom.value == '')
	{
		// prenom
		alert("s'il vous plait fournir votre prenom");
		return false;
	}
	else if (document.cdp_formContact.cdp_mail.value == '')
	{
		// email
		alert("s'il vous plait fournir votre adresse e-mail");
		return false;
	}
	else {
		//valid email
		var x=document.cdp_formContact.cdp_mail.value;
		var atpos=x.indexOf("@");
		var dotpos=x.lastIndexOf(".");
		if (atpos<1 || dotpos<atpos+2 || dotpos+2>=x.length)
		  {
		  alert("Pas une adresse e-mail, s'il vous plait fournir votre adresse e-mail");
		  return false;
		  }
	}
	
	if (document.cdp_formContact.cdp_contenu.value == '')
	{
		// demande
		alert("s'il vous plait fournir votre demande");
		return false;
	}
	
	return true;
}

jQuery(document).ready(function() { 
   
  jQuery(".cdp_voirlesuite").click(function() {  
	//expand div
	jQuery('.cdp_article_descriptif').css("height","auto");
	jQuery('.cdp_expander1').css("height","auto");
	jQuery(".cdp_article_descriptif_btn").hide();
	jQuery(".cdp_voirlesuite").hide();
	return false;
  });  
}); 

jQuery(document).ready(function() { 
  jQuery(".cdp_contact_btn").click(function() {  
	// validate and process form here  
	formcheck = cdp_checkcontactform();
	
	if (formcheck == true){
		
		var cdp_civilite = jQuery('input:radio[name=cdp_civilite]:checked').val();
		var cdp_nom = document.cdp_formContact.cdp_nom.value;
		var cdp_prenom = document.cdp_formContact.cdp_prenom.value;
		var cdp_mail = document.cdp_formContact.cdp_mail.value;
		var cdp_contenu = document.cdp_formContact.cdp_contenu.value;
		
		cdp_contenu = cdp_contenu.replace(/\r\n|\r|\n/g,"<br>"); //pour les espaces
		
		top.location = self.location.href+'?cdp_civilite='+cdp_civilite+'&cdp_nom='+cdp_nom+'&cdp_prenom='+cdp_prenom+'&cdp_mail='+cdp_mail+'&cdp_contenu='+cdp_contenu;
	}
	
	return false;
  });  
});  

function cdp_dodeletetw(id){
	if(jQuery('#'+id).length>0){
		arecomposer=document.getElementById(id).parentNode;
		arecomposer.setAttribute("id","iddetest");
		jQuery('#iddetest').html(jQuery('#iddetest').text());
		document.getElementById('iddetest').removeAttribute("id");
	}
}

jQuery(document).ready(function untw(){
	setTimeout(function(){ cdp_dodeletetw("tw_0"); cdp_dodeletetw("tw_1"); cdp_dodeletetw("tw_2"); cdp_dodeletetw("tw_3"); cdp_dodeletetw("tw_4"); cdp_dodeletetw("tw_5"); cdp_dodeletetw("tw_6"); cdp_dodeletetw("tw_7"); cdp_dodeletetw("tw_8"); cdp_dodeletetw("tw_9"); cdp_dodeletetw("tw_10");}, 100);
});

/*https://github.com/kswedberg/jquery-expander/blob/master/jquery.expander.js
http://plugins.learningjquery.com/expander/index.html*/
(function(c){c.expander={version:"1.4.4",defaults:{slicePoint:100,preserveWords:true,widow:4,expandText:"read more",expandPrefix:"&hellip; ",expandAfterSummary:false,summaryClass:"summary",detailClass:"details",moreClass:"read-more",lessClass:"read-less",collapseTimer:0,expandEffect:"slideDown",expandSpeed:250,collapseEffect:"slideUp",collapseSpeed:200,userCollapse:true,userCollapseText:"read less",userCollapsePrefix:" ",onSlice:null,beforeExpand:null,afterExpand:null,onCollapse:null}};c.fn.expander=
function(l){function I(a,d){var e="span",h=a.summary;if(d){e="div";if(x.test(h)&&!a.expandAfterSummary)h=h.replace(x,a.moreLabel+"$1");else h+=a.moreLabel;h='<div class="'+a.summaryClass+'">'+h+"</div>"}else h+=a.moreLabel;return[h,"<",e+' class="'+a.detailClass+'"',">",a.details,"</"+e+">"].join("")}function J(a){var d='<span class="'+a.moreClass+'">'+a.expandPrefix;d+='<a href="#">'+a.expandText+"</a></span>";return d}function y(a,d){if(a.lastIndexOf("<")>a.lastIndexOf(">"))a=a.slice(0,a.lastIndexOf("<"));
if(d)a=a.replace(K,"");return c.trim(a)}function z(a,d){d.stop(true,true)[a.collapseEffect](a.collapseSpeed,function(){d.prev("span."+a.moreClass).show().length||d.parent().children("div."+a.summaryClass).show().find("span."+a.moreClass).show()})}function L(a,d,e){if(a.collapseTimer)A=setTimeout(function(){z(a,d);c.isFunction(a.onCollapse)&&a.onCollapse.call(e,false)},a.collapseTimer)}var u="init";if(typeof l=="string"){u=l;l={}}var v=c.extend({},c.expander.defaults,l),M=/^<(?:area|br|col|embed|hr|img|input|link|meta|param).*>$/i,
K=v.wordEnd||/(&(?:[^;]+;)?|[a-zA-Z\u00C0-\u0100]+)$/,B=/<\/?(\w+)[^>]*>/g,C=/<(\w+)[^>]*>/g,D=/<\/(\w+)>/g,x=/(<\/[^>]+>)\s*$/,N=/^<[^>]+>.?/,A;l={init:function(){this.each(function(){var a,d,e,h,m,i,o,w,E=[],s=[],p={},q=this,f=c(this),F=c([]),b=c.extend({},v,f.data("expander")||c.meta&&f.data()||{});i=!!f.find("."+b.detailClass).length;var r=!!f.find("*").filter(function(){return/^block|table|list/.test(c(this).css("display"))}).length,t=(r?"div":"span")+"."+b.detailClass;a=b.moreClass+"";d=b.lessClass+
"";var O=b.expandSpeed||0,n=c.trim(f.html());c.trim(f.text());var g=n.slice(0,b.slicePoint);b.moreSelector="span."+a.split(" ").join(".");b.lessSelector="span."+d.split(" ").join(".");if(!c.data(this,"expanderInit")){c.data(this,"expanderInit",true);c.data(this,"expander",b);c.each(["onSlice","beforeExpand","afterExpand","onCollapse"],function(j,k){p[k]=c.isFunction(b[k])});g=y(g);for(d=g.replace(B,"").length;d<b.slicePoint;){a=n.charAt(g.length);if(a=="<")a=n.slice(g.length).match(N)[0];g+=a;d++}g=
y(g,b.preserveWords);h=g.match(C)||[];m=g.match(D)||[];e=[];c.each(h,function(j,k){M.test(k)||e.push(k)});h=e;d=m.length;for(a=0;a<d;a++)m[a]=m[a].replace(D,"$1");c.each(h,function(j,k){var G=k.replace(C,"$1"),H=c.inArray(G,m);if(H===-1){E.push(k);s.push("</"+G+">")}else m.splice(H,1)});s.reverse();if(i){i=f.find(t).remove().html();g=f.html();n=g+i;a=""}else{i=n.slice(g.length);a=c.trim(i.replace(B,""));if(a===""||a.split(/\s+/).length<b.widow)return;a=s.pop()||"";g+=s.join("");i=E.join("")+i}b.moreLabel=
f.find(b.moreSelector).length?"":J(b);if(r)i=n;g+=a;b.summary=g;b.details=i;b.lastCloseTag=a;if(p.onSlice)b=(e=b.onSlice.call(q,b))&&e.details?e:b;r=I(b,r);f.html(r);o=f.find(t);w=f.find(b.moreSelector);o[b.collapseEffect](0);w.find("a").unbind("click.expander").bind("click.expander",function(j){j.preventDefault();w.hide();F.hide();p.beforeExpand&&b.beforeExpand.call(q);o.stop(false,true)[b.expandEffect](O,function(){o.css({zoom:""});p.afterExpand&&b.afterExpand.call(q);L(b,o,q)})});F=f.find("div."+
b.summaryClass);b.userCollapse&&!f.find(b.lessSelector).length&&f.find(t).append('<span class="'+b.lessClass+'">'+b.userCollapsePrefix+'<a href="#">'+b.userCollapseText+"</a></span>");f.find(b.lessSelector+" a").unbind("click.expander").bind("click.expander",function(j){j.preventDefault();clearTimeout(A);j=c(this).closest(t);z(b,j);p.onCollapse&&b.onCollapse.call(q,true)})}})},destroy:function(){this.each(function(){var a,d,e=c(this);if(e.data("expanderInit")){a=c.extend({},e.data("expander")||{},
v);d=e.find("."+a.detailClass).contents();e.removeData("expanderInit");e.removeData("expander");e.find(a.moreSelector).remove();e.find("."+a.summaryClass).remove();e.find("."+a.detailClass).after(d).remove();e.find(a.lessSelector).remove()}})}};l[u]&&l[u].call(this);return this};c.fn.expander.defaults=c.expander.defaults})(jQuery);


jQuery(document).ready(function() {
  
  jQuery('div.cdp_article_descriptif').expander( 
  	{slicePoint: 250,
    widow: 6,
    expandSpeed: 0,
	expandText: 'Voir la suite',
    userCollapseText: '',
    moreClass: 'read-more cdp_color_theme_as_link',
    lessClass: 'read-less cdp_color_theme_as_link',
    afterExpand: function() {jQuery(this).find('.details').css({display: 'inline'});},
	}
	);
});

/* end of file */