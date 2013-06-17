<?php
// ZNC to JSON Config Conversion by Thomas Edwards
// Use commandline -o to overwrite regardless.
// Created around ZNC Config v1.0
$force = false;
if ($argc >= 2) {
	if ($argv[1] == "-o") {
		$force = true;
	}
}
$config = "znc.conf";
$dest = "zncconfig.json";
echo "ZNC to JSON Parser by Thomas Edwards\n";
if (!file_exists($config)) {
	die("The ZNC Config at '{$config}' could not be found.\n");
}
if (file_exists($dest) && !$force) {
	die("The JSON File {$dest} already exists.\n");
}

$src_data = explode("\n",file_get_contents($config));

// Setup the primary Structure.
$json = array();
$json['Listeners'] = array();
$json['Users'] = array();
$json['Modules'] = array();

// Setup some parsing variables.
$in_user = false;
$in_listener = false;
$in_network = false;
$in_pass = false;
$in_chan = false;

// Some Template arrays.
$user_temp = array();
$user_temp['Networks'] = array();
$user_temp['Modules'] = array();
$user_temp['Channels'] = array();

// Setup some temp arrays.
$listener = array();
$user = $user_temp;
$network = array();
$chan = array();
$password = array();

foreach ($src_data as $line) {
	$data = explode(chr(32),trim($line));
		if ($data[0] == "<Listener") {
			$in_listener = true;
			$listener['Name'] = substr($data[1],0,-1);
		}
		else if ($data[0] == "</Listener>") {
			$json['Listeners'][] = $listener;
			$listener = array();
			$in_listener = false;
		}
		else if ($data[0] == "<User") {
			$in_user = true; // Nothing kinkeh here :o!
			$user['Username'] = substr($data[1],0,-1);
		}
		else if ($data[0] == "</User>") {
			$in_user = false;
			$json['Users'][] = $user;
			$user = $user_temp;
		}
		else if ($data[0] == "<Network") {
			if ($in_user) {
				$in_network = true;
				$network['Name'] = substr($data[1],0,-1);
			}
		}
		else if ($data[0] == "</Network>") {
			if ($in_user) {
				$user['Networks'][] = $network;
				$in_network = false;
				$network = array();
			}
		}
		else if ($data[0] == "<Chan") {
			if ($in_user) {
				$in_chan = true;
				$chan['Name'] = substr($data[1],0,-1);
			}
		}
		else if ($data[0] == "</Chan>") {
			if ($in_user) {
				$in_chan = false;
				$user['Channels'][] = $chan;
				$chan = array();
			}
		}
		else if ($data[0] == "<Pass") {
			if ($in_user) {
				$in_pass = true;
			}
		}
		else if ($data[0] == "</Pass>") {
			if ($in_user) {
				$in_pass = false;
				$user['Password'] = $password;
				$password = array();
			}
		}
		else if ($data[0] != "//" && $data[0] != "") {
			// It's not a comment.
			//echo "Not comment. ".print_r($data,true);
			if ($in_user && !$in_chan && !$in_network && !$in_pass) {
				if	($data[0] != "LoadModule") {
					$user[$data[0]] = implode(chr(32),array_slice($data,2));
				}
				else {
					$user['Modules'][] = $user[$data[0]] = implode(chr(32),array_slice($data,2));
				}
			}
			else if ($in_network) {
				$network[$data[0]] = implode(chr(32),array_slice($data,2));
			}
			else if ($in_chan) {
				$channel[$data[0]] = implode(chr(32),array_slice($data,2));
			}
			else if ($in_pass) {
				$password[$data[0]] = implode(chr(32),array_slice($data,2));
			}
			else if ($in_listener) {
				$listener[$data[0]] = implode(chr(32),array_slice($data,2));
			}
			else if ($data[0] == "LoadModule" && !$in_user) {
				$json['Modules'][] = implode(chr(32),array_slice($data,2));
			}
		}
}
file_put_contents($dest,json_encode($json));
echo "Config parsed. ".count($src_data)." lines were parsed into JSON.\n";
?>