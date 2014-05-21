(function(e){if(typeof define==="function"&&define.amd){define(["jquery"],e)}else if(typeof exports==="object"){e(require("jquery"))}else{e(jQuery)}})(function(e){function n(e){return u.raw?e:encodeURIComponent(e)}function r(e){return u.raw?e:decodeURIComponent(e)}function i(e){return n(u.json?JSON.stringify(e):String(e))}function s(e){if(e.indexOf('"')===0){e=e.slice(1,-1).replace(/\\"/g,'"').replace(/\\\\/g,"\\")}try{e=decodeURIComponent(e.replace(t," "));return u.json?JSON.parse(e):e}catch(n){}}function o(t,n){var r=u.raw?t:s(t);return e.isFunction(n)?n(r):r}var t=/\+/g;var u=e.cookie=function(t,s,a){if(s!==undefined&&!e.isFunction(s)){a=e.extend({},u.defaults,a);if(typeof a.expires==="number"){var f=a.expires,l=a.expires=new Date;l.setTime(+l+f*864e5)}return document.cookie=[n(t),"=",i(s),a.expires?"; expires="+a.expires.toUTCString():"",a.path?"; path="+a.path:"",a.domain?"; domain="+a.domain:"",a.secure?"; secure":""].join("")}var c=t?undefined:{};var h=document.cookie?document.cookie.split("; "):[];for(var p=0,d=h.length;p<d;p++){var v=h[p].split("=");var m=r(v.shift());var g=v.join("=");if(t&&t===m){c=o(g,s);break}if(!t&&(g=o(g))!==undefined){c[m]=g}}return c};u.defaults={};e.removeCookie=function(t,n){if(e.cookie(t)===undefined){return false}e.cookie(t,"",e.extend({},n,{expires:-1}));return!e.cookie(t)}})

jQuery(function () {

  if jQuery.cookie('skidki_company_key') === null {
    alert('asd');
  } else {
    alert('123');
  }

  jQuery('#company_login_button').click(function() {
    login = jQuery('#login_phone').val();
    pass = jQuery('#login_password').val();

    getCL = "http://salestatic.ru/yola/company_login/" + login + "/" + pass + "/" ;
    jQuery.ajax({
      type: 'GET',
      url: getCL,
      processData: true,
          data: {},
      dataType: 'jsonp',
      success:function(data){	
        //jQuery('#company_login').css("display", "none");
        //jQuery('#company_data').css("display", "block");
        jQuery.cookie('skidki_company_key',data['key'], { expires: 7 });
      },
      error:function(){
        alert('Такие телефон с таким паролем не найден.');
      },
    });
    
  });
    
    
  jQuery('#company_register_button').click(function() {

      login = jQuery('#register_phone').val();
      pass = jQuery('#register_password').val();

      if(jQuery('#comp_type_free').is(':checked')) { type= 'free'; }
      if(jQuery('#comp_type_limit').is(':checked')) { type= 'limit'; }
      if(jQuery('#comp_type_full').is(':checked')) { type= 'full'; }

      getCLO = "http://salestatic.ru/yola/company_register/" + login + "/" + pass + "/" + type + "/";
      jQuery.ajax({
        type: 'GET',
        url: getCLO,
        processData: true,
            data: {},
        dataType: 'jsonp',
        success:function(data){	
         alert('asd');
        },
        error:function(){
        },
      });
  
  });
  
  
    jQuery('#company_add_button').click(function() {

      key = jQuery('#company_key').val();
      title = jQuery('#company_title').val();
      area = jQuery('#company_area').val();


      getCLO = "http://salestatic.ru/yola/company_data/" + key + "/" + title + "/" + area + "/";
      jQuery.ajax({
        type: 'GET',
        url: getCLO,
        processData: true,
            data: {},
        dataType: 'jsonp',
        success:function(data){	
         alert('asd');
        },
        error:function(){
        },
      });
  
  });
});