!function(a,b,c){function d(a,b){return typeof a===b}function e(){var a,b,c,e,f,g,h;for(var i in t)if(t.hasOwnProperty(i)){if(a=[],b=t[i],b.name&&(a.push(b.name.toLowerCase()),b.options&&b.options.aliases&&b.options.aliases.length))for(c=0;c<b.options.aliases.length;c++)a.push(b.options.aliases[c].toLowerCase());for(e=d(b.fn,"function")?b.fn():b.fn,f=0;f<a.length;f++)g=a[f],h=g.split("."),1===h.length?v[h[0]]=e:(!v[h[0]]||v[h[0]]instanceof Boolean||(v[h[0]]=new Boolean(v[h[0]])),v[h[0]][h[1]]=e),s.push((e?"":"no-")+h.join("-"))}}function f(a){var b=x.className,c=v._config.classPrefix||"";if(y&&(b=b.baseVal),v._config.enableJSClass){var d=new RegExp("(^|\\s)"+c+"no-js(\\s|$)");b=b.replace(d,"$1"+c+"js$2")}v._config.enableClasses&&(b+=" "+c+a.join(" "+c),y?x.className.baseVal=b:x.className=b)}function g(){return"function"!=typeof b.createElement?b.createElement(arguments[0]):y?b.createElementNS.call(b,"http://www.w3.org/2000/svg",arguments[0]):b.createElement.apply(b,arguments)}function h(a,b){return!!~(""+a).indexOf(b)}function i(a){return a.replace(/([a-z])-([a-z])/g,function(a,b,c){return b+c.toUpperCase()}).replace(/^-/,"")}function j(a,b){return function(){return a.apply(b,arguments)}}function k(a,b,c){var e;for(var f in a)if(a[f]in b)return c===!1?a[f]:(e=b[a[f]],d(e,"function")?j(e,c||b):e);return!1}function l(){var a=b.body;return a||(a=g(y?"svg":"body"),a.fake=!0),a}function m(a,c,d,e){var f,h,i,j,k="modernizr",m=g("div"),n=l();if(parseInt(d,10))for(;d--;)i=g("div"),i.id=e?e[d]:k+(d+1),m.appendChild(i);return f=g("style"),f.type="text/css",f.id="s"+k,(n.fake?n:m).appendChild(f),n.appendChild(m),f.styleSheet?f.styleSheet.cssText=a:f.appendChild(b.createTextNode(a)),m.id=k,n.fake&&(n.style.background="",n.style.overflow="hidden",j=x.style.overflow,x.style.overflow="hidden",x.appendChild(n)),h=c(m,a),n.fake?(n.parentNode.removeChild(n),x.style.overflow=j,x.offsetHeight):m.parentNode.removeChild(m),!!h}function n(a){return a.replace(/([A-Z])/g,function(a,b){return"-"+b.toLowerCase()}).replace(/^ms-/,"-ms-")}function o(b,d){var e=b.length;if("CSS"in a&&"supports"in a.CSS){for(;e--;)if(a.CSS.supports(n(b[e]),d))return!0;return!1}if("CSSSupportsRule"in a){for(var f=[];e--;)f.push("("+n(b[e])+":"+d+")");return f=f.join(" or "),m("@supports ("+f+") { #modernizr { position: absolute; } }",function(a){return"absolute"==getComputedStyle(a,null).position})}return c}function p(a,b,e,f){function j(){l&&(delete F.style,delete F.modElem)}if(f=!d(f,"undefined")&&f,!d(e,"undefined")){var k=o(a,e);if(!d(k,"undefined"))return k}for(var l,m,n,p,q,r=["modernizr","tspan"];!F.style;)l=!0,F.modElem=g(r.shift()),F.style=F.modElem.style;for(n=a.length,m=0;m<n;m++)if(p=a[m],q=F.style[p],h(p,"-")&&(p=i(p)),F.style[p]!==c){if(f||d(e,"undefined"))return j(),"pfx"!=b||p;try{F.style[p]=e}catch(s){}if(F.style[p]!=q)return j(),"pfx"!=b||p}return j(),!1}function q(a,b,c,e,f){var g=a.charAt(0).toUpperCase()+a.slice(1),h=(a+" "+C.join(g+" ")+g).split(" ");return d(b,"string")||d(b,"undefined")?p(h,b,e,f):(h=(a+" "+A.join(g+" ")+g).split(" "),k(h,b,c))}function r(a,b,d){return q(a,c,c,b,d)}var s=[],t=[],u={_version:"3.3.1",_config:{classPrefix:"",enableClasses:!0,enableJSClass:!0,usePrefixes:!0},_q:[],on:function(a,b){var c=this;setTimeout(function(){b(c[a])},0)},addTest:function(a,b,c){t.push({name:a,fn:b,options:c})},addAsyncTest:function(a){t.push({name:null,fn:a})}},v=function(){};v.prototype=u,v=new v,v.addTest("svg",!!b.createElementNS&&!!b.createElementNS("http://www.w3.org/2000/svg","svg").createSVGRect);var w=u._config.usePrefixes?" -webkit- -moz- -o- -ms- ".split(" "):["",""];u._prefixes=w;var x=b.documentElement,y="svg"===x.nodeName.toLowerCase(),z="Moz O ms Webkit",A=u._config.usePrefixes?z.toLowerCase().split(" "):[];u._domPrefixes=A;var B=function(){function a(a,b){var e;return!!a&&(b&&"string"!=typeof b||(b=g(b||"div")),a="on"+a,e=a in b,!e&&d&&(b.setAttribute||(b=g("div")),b.setAttribute(a,""),e="function"==typeof b[a],b[a]!==c&&(b[a]=c),b.removeAttribute(a)),e)}var d=!("onblur"in b.documentElement);return a}();u.hasEvent=B,v.addTest("cssgradients",function(){for(var a,b="background-image:",c="gradient(linear,left top,right bottom,from(#9f9),to(white));",d="",e=0,f=w.length-1;e<f;e++)a=0===e?"to ":"",d+=b+w[e]+"linear-gradient("+a+"left top, #9f9, white);";v._config.usePrefixes&&(d+=b+"-webkit-"+c);var h=g("a"),i=h.style;return i.cssText=d,(""+i.backgroundImage).indexOf("gradient")>-1});var C=u._config.usePrefixes?z.split(" "):[];u._cssomPrefixes=C;var D=u.testStyles=m;v.addTest("touchevents",function(){var c;if("ontouchstart"in a||a.DocumentTouch&&b instanceof DocumentTouch)c=!0;else{var d=["@media (",w.join("touch-enabled),("),"heartz",")","{#modernizr{top:9px;position:absolute}}"].join("");D(d,function(a){c=9===a.offsetTop})}return c});var E={elem:g("modernizr")};v._q.push(function(){delete E.elem});var F={style:E.elem.style};v._q.unshift(function(){delete F.style}),u.testAllProps=q,u.testAllProps=r,v.addTest("backgroundsize",r("backgroundSize","100%",!0)),v.addTest("csstransitions",r("transition","all",!0)),e(),f(s),delete u.addTest,delete u.addAsyncTest;for(var G=0;G<v._q.length;G++)v._q[G]();a.Modernizr=v}(window,document),function(){var a=document.getElementsByTagName("HTML")[0];if(className=null,navigator.userAgent.match(/trident/i)){className=a.className.replace(/lt-ie(\d+)/g,"");var b=parseInt(navigator.appVersion.substring(0,1));"undefined"!=typeof document.documentMode&&(b=document.documentMode);for(var c=b+1;c<13;c++)className+=" lt-ie"+c;className+=" ie"+b,className+=" ie",className+=" is-not-edge"}else if(navigator.userAgent.match(/edge\/(\d+)/i)){className=a.className.replace(/lt-ie(\d+)/g,"");var b=parseInt(navigator.userAgent.match(/edge\/(\d+)/i)[1]);"undefined"!=typeof document.documentMode&&(b=document.documentMode);for(var c=b+1;c<16;c++)className+=" lt-ie"+c;className+=" ie"+b,className+=" ie",className+=" is-edge"}if("ontouchstart"in document.documentElement){if(matches=navigator.userAgent.match(/OS ([0-9]+)/i)){var b=parseInt(matches[1]);null==className&&(className=a.className),className+=" ios"+b;for(var c=b+1;c<15;c++)className+=" lt-ios"+c;className+=" ios"+b,className+=" ios"}navigator.userAgent.match(/iPad/i)&&(null==className&&(className=a.className),className+=" ipad"),navigator.userAgent.match(/(iPhone|iPod)/i)&&(null==className&&(className=a.className),className+=" iphone")}null!=className&&(a.className=className)}();var reposBalloons=function(){console.log($("*[data-balloon]").length);var a=100,b=$(document).width()-100;$("*[data-balloon]").each(function(c,d){var e=$(d).offset().left,f=$(d).offset().left+$(d).outerWidth();e<a?$(d).attr("data-balloon-pos","topleft"):f>b?$(d).attr("data-balloon-pos","topright"):$(d).attr("data-balloon-pos",null)})};$(window).on("resize",reposBalloons),$(document).on("ready",function(){reposBalloons()}),$(document).on("ready",function(){console.log("DROPDOWN"),$(document).on({"change.osat":function(a){$(this).trigger("updatelabel.osat")},"updatelabel.osat":function(a){var b=$('label[for="'+$(this).attr("id")+'"]'),c=this.options[this.selectedIndex].value;b.length&&(c||(c=$(b).attr("data-title")),$(b).text(c))}},".form-group.is-select select"),$(".form-group.is-select select").trigger("updatelabel.osat")});var osatfeedback={init:function(){osatfeedback.addSubmitEvent()},addSubmitEvent:function(a){a="undefined"==typeof a?$("body"):a,$(a).find("#osat-feedback-form").on("submit.osatfeedback",osatfeedback.submitForm),console.log("EVENT ADDED")},submitForm:function(){var a=this,b=$(a).parents(".osat-feedback").length?$(a).parents(".osat-feedback").first():$(a);$(b).addClass("is--loading");$.ajax({type:"POST",url:$(a).attr("action"),headers:{OSATFEEDBACK_AJAX:"TRUE"},data:$(a).serialize()}).done($.proxy(function(a){var b=$(this).hasClass("osat-feedback")?$(this):$(this).find(".osat-feedback"),a=$(a).hasClass("osat-feedback")?$(a):$(a).find(".osat-feedback");b&&a?($(b).replaceWith($(a)),osatfeedback.addSubmitEvent(a)):$(this).addClass("has--error")},b)).fail(function(){$(this).addClass("has--error"),x}).always($.proxy(function(a){$(this).removeClass("is--loading")},b));return!1}};$(document).on("ready",osatfeedback.init),$(document).on("ready",function(){$(document).on({"change.osat":function(a){var b=window.location.href.replace(/(\?|&)lang=[a-z]{2}/i,""),c=b.match(/#.*$/)?b.match(/#.*$/)[0]:"";b=b.replace(c,""),b+=(b.indexOf("?")>-1?"&":"?")+"lang="+this.options[this.selectedIndex].value+c,$(this).trigger("updatelabel.osat")},"updatelabel.osat":function(a){var b=$('label[for="'+$(this).attr("id")+'"]'),c=this.options[this.selectedIndex].value;c=c[0].toUpperCase()+c.slice(1),console.log(c),$(b).text(c)}},".languagechanger")});var osatstats={init:function(){$(document).on({"click.osatstats":osatstats.toggleQuestions},".osatstats-table:not(.is--chart) tr.osatstats-table--group"),$(document).on({"change.osatstats":osatstats.switchFilter},"#assessmentfilter select"),$(document).on({"change.osatstats":function(a){$(this).parents("form").first().trigger("submit.osatstats")}},"#assessmentfilter select"),$(document).on({"click.osatstats":osatstats.resetFilter},"#assessmentfilter button"),osatstats.addSubmitEvent(),$(document).on({"click.osatstats":osatstats.activateAssessment},"*[data-gid]")},toggleQuestions:function(a){var b=$(this).is("tbody")?this:$(this).parents("tbody");b.length&&b.toggleClass("has--details")},switchFilter:function(a){var b=$(this).parents("form").first(),c=[],d=$('label[for="'+this.id+'"]').first();$(b).find("select").not(this).each(function(a,b){b.selectedIndex=0,$.proxy(osatstats.switchFilter,b)});for(var e=0;e<this.options.length;e++)1==this.options[e].selected&&c.push($.trim($(this.options[e]).text()));c.length?$(d).text(c.join(", ",c)):$(d).text($(d).attr("title"))},resetFilter:function(a){var b=$(this).parents("form").first();return $(b).find("select").each(function(a,b){b.selectedIndex=0,$.proxy(osatstats.switchFilter,b)}),$(b).trigger("submit.osatstats"),!1},addSubmitEvent:function(a){a="undefined"==typeof a?$("body"):a,$(a).find("#assessmentfilter").on("submit.osatstats",osatstats.submitForm)},submitForm:function(){var a=this,b=$(a).parents(".osatstats-table").length?$(a).parents(".osatstats-table").first():$(a);$(b).addClass("is--loading");$.ajax({type:"POST",url:$(a).attr("action"),headers:{OSATSTATS_AJAX:"TRUE"},data:$(a).serialize()}).done($.proxy(function(a){var b=$(this).hasClass("osatstats-table")?$(this):$(this).find(".osatstats-table"),a=$(a).hasClass("osatstats-table")?$(a):$(a).find(".osatstats-table");b&&a?($(b).replaceWith($(a)),osatstats.addSubmitEvent(a),$(window).trigger("resize")):$(this).addClass("has--error")},b)).fail(function(){$(this).addClass("has--error")}).always($.proxy(function(a){$(this).removeClass("is--loading")},b));return!1},activateAssessment:function(){var a=$(this).attr("data-gid");console.log("activate "+a),$(".is--active[data-gid]").removeClass("is--active"),$('[data-gid="'+a+'"]').addClass("is--active")}};$(document).on("ready",osatstats.init),$(document).on("ready",function(){console.log("change form action"),$("form#limesurvey").each(function(a,b){var c=$(b).attr("action");$(b).attr("action",c+"#limesurvey"),c.indexOf("#")<=-1&&$(b).attr("action",c+"#limesurvey")})});
//# sourceMappingURL=scripts.js.map