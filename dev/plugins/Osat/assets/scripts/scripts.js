$(document).on("ready",function(a){$(document).on({"toggle.osat":function(a){$(this).nextAll(".controls").first().toggleClass("is--open")},"close.osat":function(a){$(this).nextAll(".controls").first().removeClass("is--open")},"click.osat":function(a){$(this).trigger("toggle.osat")}},".setting.control-group.setting-text label.control-label, .setting.control-group.setting-html label.control-label"),$(document).on({"toggle.osat":function(a){for(var b=$(this).nextAll(),c=0;c<b.length;c++){var d=b[c];if($(d).is(".setting.control-group.setting-info"))break;$(d).toggleClass("is--open"),$(d).hasClass("is--open")&&$(d).find("label.control-label").length&&($(d).find("label.control-label").trigger("close.osat"),0==c&&$(d).find("label.control-label").trigger("toggle.osat"))}},"click.osat":function(a){$(this).trigger("toggle.osat")}},".setting.control-group.setting-info"),$(".setting.control-group.setting-info").first().trigger("toggle.osat")});