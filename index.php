<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>CCGX Remote Control</title>
    <!--    
        Modified version of noVNC example, source is here.
        https://github.com/kanaka/noVNC/blob/master/vnc_auto.html
    
        Copyright and license of original file:
        noVNC example: simple example using default UI
        Copyright (C) 2012 Joel Martin
        Copyright (C) 2013 Samuel Mannehed for Cendio AB
        noVNC is licensed under the MPL 2.0 (see LICENSE.txt)
        This file is licensed under the 2-Clause BSD license (see LICENSE.txt).
    -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link href="styling/styles.css" media="screen" rel="stylesheet" type="text/css" />
    <script src="include/util.js"></script>
	<script src="scripts/jquery-2.1.3.js"></script>
	<script src="scripts/bCrypt.js"></script>
</head>
<body>
    <div id="modal-popup-container-remote-console" class="modal-popup-container remote-console-popup-container mfp-with-anim">
		<div class="remote-console-status">
			<div class="remote-console-status-content">
				<div class="remote-console-login-icon alarm">
					<img src="/styling/img/svg-icons/icon_12_alarm-32px.svg" class="remote-console-control-icon svg" />
				</div>
				<div class="remote-console-login-icon notification">
					<img src="/styling/img/svg-icons/icon_11_notification-32px.svg" class="remote-console-control-icon svg" />
				</div>
				<h2 class="remote-console-login-title">Remote console</h2>
				<div class="remote-console-status-text"></div>
			</div>
		</div>
		<div class="remote-console-login">
			<div class="remote-console-display-container">
				<div class="remote-console-login-contents">
					<div class="remote-console-login-icon"><img src="/styling/img/svg-icons/password.svg" class="remote-console-control-icon svg" /></div>
					<h2 class="remote-console-login-title">Remote console</h2>
					<div class="remote-console-login-fields-container">
						<div class="remote-console-login-field-title">Password <span class="remote-console-login-field-title-asterisk">*</span></div>
						<div class="remote-console-login-fields">
							<input type="password" id="remote-console-password"><button id="remote-console-login" class="clickable btn green"><img src="/styling/img/svg-icons/enter.svg" class="remote-console-control-icon svg" /></button>
						</div>
					</div>
				</div>
			</div>
			<div class="remote-console-controls-container">
				<div class="remote-console-controls-text">
					<h3 class="remote-console-controls-title">Where do I find this password?</h3>
					<h4 class="remote-console-controls-explanation">This password can be found on the Color Control.</h4>
				</div>
			</div>
		</div>
		<div class="remote-console-logged-in">
			<h2 class="remote-console-title"></h2>
			<div class="remote-console-display-container">
				<div class="remote-console-display">
					<div class="remote-console-display-inner">
						<canvas id="remote-console-canvas">
							Your browser does not support canvas, which is required for using the remote console.
						</canvas>
					</div>
				</div>
			</div>
			<div class="remote-console-controls-container">
				<div class="remote-console-controls-text">
					<h2 class="remote-console-controls-title"></h2>
					<h2 class="remote-console-controls-subtitle">hotkeys</h2>
				</div>
				<div class="remote-console-controls">
					<div class="remote-console-controls-row">
						<div class="clickable btn light remote-console-control-button left-button wide" data-button="left-button"><span class="remote-console-control-icon text">esc</span><span class="remote-console-control-icon">&nbsp;</span></div>
						<div class="clickable btn light remote-console-control-button right-button wide" data-button="right-button"><img src="/styling/img/svg-icons/enter.svg" class="remote-console-control-icon svg" /></div>
					</div>
					<div class="remote-console-controls-row">
						<div class="clickable btn light remote-console-control-button up" data-button="up"><img src="/styling/img/svg-icons/arrow-up.svg" class="remote-console-control-icon svg" /></div>
					</div>
					<div class="remote-console-controls-row">
						<div class="clickable btn light remote-console-control-button left" data-button="left"><img src="/styling/img/svg-icons/arrow-left.svg" class="remote-console-control-icon svg" /></div>
						<div class="clickable btn light remote-console-control-button down" data-button="down"><img src="/styling/img/svg-icons/arrow-down.svg" class="remote-console-control-icon svg" /></div>
						<div class="clickable btn light remote-console-control-button right" data-button="right"><img src="/styling/img/svg-icons/arrow-right.svg" class="remote-console-control-icon svg" /></div>
					</div>
					<div class="remote-console-controls-row">
						<div class="clickable btn light remote-console-control-button center extra-wide" data-button="center"><img src="/styling/img/svg-icons/spacebar.svg" class="remote-console-control-icon svg" /></div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="remote-console-rotate-message-container">
		<div class="rotate-message-contents">
			<div class="rotate-message-icon"><img src="/styling/img/svg-icons/rotate-72px.svg" class="remote-console-control-icon svg" /></div>
			<h2 class="rotate-message-title">Rotate your screen</h2>
		</div>
	</div>

    <script type="text/javascript">
        "use strict";

        // Load supporting scripts
        Util.load_scripts(["webutil.js", "base64.js", "websock.js", "des.js", "keysymdef.js", "keyboard.js", "input.js", "display.js", "jsunzip.js", "rfb.js", "keysym.js"]);

        var rfb, remoteConsole, firstAttempt, identifier;
        window.onscriptsload = function() {
			remoteConsole = $("#modal-popup-container-remote-console");
			
			window.addEventListener('beforeunload', function() {
				disconnect();
			});

			// Resize display on window resize
			window.addEventListener('resize', function() {
				resize();
			});
			
			//Attach click listeners for the buttons
			remoteConsole.find('[data-button]').click(function() {
				sendButton($(this).data('button'));
			});
		
			firstAttempt = true;
        	// Connection settings
            var host = WebUtil.getQueryVar('host', window.location.hostname);
            var port = WebUtil.getQueryVar('port', 81);
            var password = WebUtil.getQueryVar('password', '');
            var path = WebUtil.getQueryVar('path', 'websockify');
			<?php
			$identifier = @file_get_contents('/sys/class/net/eth0/address');
			?>
			var identifier = '<?php echo $identifier !== false ? trim(str_replace(':', '', strtolower($identifier))) : ''?>';
			
            // Set up logging to console
            WebUtil.init_logging('warn');
			
			showStatus('Connecting.', 'notification');

            // Set up connection
            rfb = new RFB({
	            'target': $D('remote-console-canvas'),
	            'encrypt': WebUtil.getQueryVar('encrypt', false),
	            'repeaterID': '',
	            'true_color': true,
	            'local_cursor': false,
	            'shared': true,
	            'view_only': false,
				onUpdateState: function(rfb, state, oldstate, message) {
					switch(state) {
						case 'normal':
							showRemoteConsoleScreen();
							break;
						case 'failed':
							switch(oldstate) {
								case 'normal':
									showStatus('Disconnected.', 'alarm');
									break;
								case 'connect':
									showStatus('Failed to set up a connection to the CCGX. Check your connection and try again.', 'alarm');
									break;
								case 'ProtocolVersion':
									showStatus('Failed to connect to the CCGX.', 'alarm');
									break;
							}
							break;
					}
				},
				onPasswordRequired: function(rfb) {
					showPasswordScreen();
					if (!firstAttempt) {
						// Password was entered incorrectly
						alert('Incorrect password');
					}

					remoteConsole.find('#remote-console-login').off('click.login').on('click.login', function() {
						// Do a new connect, rfb.sendPassword does not seem to work
						rfb.connect(host, port, getHashFromPasswordField(identifier), path);
					});

					firstAttempt = false;
				}
            });
            
			// Initial resize
			resize();
			
            // Connect
            rfb.connect(host, port, password, path);
        };
		
		function getHashFromPasswordField(identifier) {
			var password = remoteConsole.find('#remote-console-password');
			if (password.val().length == 0) {
				return '';
			}

			var setting = genSaltSyncFromString(8, identifier + identifier)
			var hash = hashSync(password.val(), setting);
			if (hash.length > 8) {
				return hash.substr(hash.length - 8);
			} else {
				alert('Something went wrong while connecting to your CCGX');
			}
		}
		
		function showPasswordScreen() {
			remoteConsole.removeClass('logged-in').addClass("password-needed");;
			hideStatus();
		}

		function showRemoteConsoleScreen() {
			remoteConsole.removeClass("password-needed").addClass('logged-in');
			hideStatus();
		}

		function showStatus(status, type) {
			var statusContainer = remoteConsole.find('.remote-console-status');

			remoteConsole.removeClass("password-needed").removeClass('logged-in');
			remoteConsole.find('.remote-console-status-text').html(status).show();
			statusContainer.removeClass('notification');
			statusContainer.removeClass('alarm');
			statusContainer.addClass(type);
		}
		
		function hideStatus() {
			remoteConsole.find('.remote-console-status-text').empty().hide();
		}
		
		function disconnect() {
			// Check if we're connected
			if(rfb) {
				// Disconnect
				rfb.disconnect();
				rfb = null;
			}

			// Hide any status messages
			hideStatus();
		}
		
		function resize() {
			var $display = remoteConsole.find('.remote-console-display-inner');
			var windowHeight = $(window).height();
			var windowWidth = $(window).width();
			var isPortrait = windowHeight > windowWidth;

			if (windowWidth <= 767) {
				$display.height((windowWidth - 240) * 0.56);
			} else {
				$display.height('');
			}

			if (windowWidth < 686) {
				$display.width(480 - (686 - windowWidth));
			}
			else {
				$display.width('');
			}

			if (windowWidth < 768 && isPortrait && $('#modal-popup-container-remote-console').length > 0) {
				$('.remote-console-rotate-message-container').show();
			}
			else {
				$('.remote-console-rotate-message-container').hide();
			} 
		}
		
		function sendButton(button) {
			// Check if we're connected
			if(rfb) {
				// Mapping buttons to key codes
				// See https://www.cl.cam.ac.uk/~mgk25/ucs/keysymdef.h
				var keymap = {
					'primary':    	65307, // Esc
					'left-button':	65307, // Esc
					'secondary':  	65293, // Enter
					'right-button': 65293, // Enter
					'up':         	65362, // Arrow up
					'left':       	65361, // Arrow left
					'center':     	32,    // Space
					'right':      	65363, // Arrow right
					'down':       	65364  // Arrow down
				};

				// Send key
				rfb.sendKey(keymap[button]);
			}
		}
		
		$("img.svg").each(function() {
			var $img = jQuery(this);
			var imgID = $img.attr('id');
			var imgClass = $img.attr('class');
			var imgURL = $img.attr('src');

			jQuery.get(imgURL, function(data) {
				// Get the SVG tag, ignore the rest
				var $svg = jQuery(data).find('svg');

				// Add replaced image's ID to the new SVG
				if(typeof imgID !== 'undefined') {
					$svg = $svg.attr('id', imgID);
				}
				// Add replaced image's classes to the new SVG
				if(typeof imgClass !== 'undefined') {
					$svg = $svg.attr('class', imgClass+' replaced-svg');
				}

				// Remove any invalid XML tags as per http://validator.w3.org
				$svg = $svg.removeAttr('xmlns:a');

				// Check if the viewport is set, if the viewport is not set the SVG wont't scale.
				if(!$svg.attr('viewBox') && $svg.attr('height') && $svg.attr('width')) {
					$svg.attr('viewBox', '0 0 ' + $svg.attr('height') + ' ' + $svg.attr('width'))
				}

				// Replace image with new SVG
				$img.replaceWith($svg);

			}, 'xml');
		});
    </script>
</body>
</html>

