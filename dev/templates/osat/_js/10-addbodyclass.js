(function() {
	var docElement = document.getElementsByTagName('HTML')[0];

	/* Attach IE classes to html tag - attach .ie if it is IE, attach .lt-ieN if it is IE version N+1
	 * thomas.goellner@teamnawrot.de
	 */

	className = null;

	if(navigator.userAgent.match(/trident/i)) {

		className = docElement.className.replace(/lt-ie(\d+)/g,"");

		var version = parseInt(navigator.appVersion.substring(0,1));
		if(typeof document.documentMode!="undefined") {
			version = document.documentMode;
		}

		for (var i = version+1; i<13; i++) {
			className+= " lt-ie"+i
		}

		className+= " ie"+version;
		className+= " ie";
		className+= " is-not-edge";
	}
	else if(navigator.userAgent.match(/edge\/(\d+)/i)) {

		className = docElement.className.replace(/lt-ie(\d+)/g,"");
		var version = parseInt(navigator.userAgent.match(/edge\/(\d+)/i)[1]);
		if(typeof document.documentMode!="undefined") {
			version = document.documentMode;
		}

		for (var i = version+1; i<16; i++) {
			className+= " lt-ie"+i
		}

		className+= " ie"+version;
		className+= " ie";
		className+= " is-edge";
	}

	/* Attach touch device iOS classes to html tag - attach .ios if it is an iOS device, attach .lt-iosN if it is iOS version N+1
	 * attach .ipad if it is an iPad, attach .iphone if it is an iPod or iPhone.
	 * thomas.goellner@teamnawrot.de
	 */
	if("ontouchstart" in document.documentElement) {

		if(matches = navigator.userAgent.match(/OS ([0-9]+)/i)) {
			var version = parseInt(matches[1]);

			if(className == null) {
				className = docElement.className;
			}

			className+= " ios"+version;
			for (var i = version+1; i<15; i++) {
				className+= " lt-ios"+i
			}

			className+= " ios"+version;
			className+= " ios";
		}

		if(navigator.userAgent.match(/iPad/i)) {
			if(className == null) {
				className = docElement.className;
			}

			className+= " ipad";
		}

		if(navigator.userAgent.match(/(iPhone|iPod)/i)) {
			if(className == null) {
				className = docElement.className;
			}

			className+= " iphone";
		}
	}

	if(className != null)
	{
		docElement.className = className;
	}
})();
