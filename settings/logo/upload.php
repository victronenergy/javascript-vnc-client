<?php

header('Content-Type: application/json');

$result = array (
	"ok"	=> 0
);

$target_file = '/data/themes/overlay/mobile-builder-logo.png';
$link_path = '/var/www/javascript-vnc-client/settings/logo/current-logo.png';

if ($_POST['img']) {
	
	$data = base64_decode ($_POST['img'], true);
	
	if ($data) {
		
		if (file_put_contents($target_file, $data)) {
			
			if (chmod ($target_file, 0644)) {
				
				$result["ok"] = 1;
			
				if (!is_link ($link_path)) {
					
					symlink ($target_file, $link_path);
				}
			}
			else {
				$result["fault"] = "Unable to change permissions.";
			}
		}
		else {
			$result['fault'] = "Cannot store image.";
		}		
	}
	else {
		$result['fault'] = "Cannot decode image data.";
	}
}
	
echo json_encode($result);

if ($result["ok"]) {
	system("/sbin/shutdown.sysvinit -r now");
}

?>

