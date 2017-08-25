<?php

header('Content-Type: application/json');

$result = array (
	"ok"	=> 0
);

if ($_GET['alive']) {

	exec ('ps | grep /opt/victronenergy/gui | grep "gui/gui" | wc -l', $gui_running);
	
	if ($gui_running[0] == "1") {
		exec ('ps | grep /opt/victronenergy/websockify-c | grep "websockify-c/websockify" | wc -l', $gui_running);
	
		if ($gui_running[1] == "1") {
			exec ('netstat -lnt | grep 0.0.0.0:81 | grep LISTEN | wc -l', $gui_running);
			
			if ($gui_running[2] == "1") {
				
				$result["ok"] = 1;
			}
			else {
				$result["fault"] = "Port 81 not open.";
			}
		}
		else {
			$result["fault"] = "Websocket not running.";
		}
	}
	else {
		$result["fault"] = "GUI not running.";
	}	
}

echo json_encode($result);


?>

