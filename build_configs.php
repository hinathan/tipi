#!/usr/bin/php
<?php

define('kWebRootRoot','/Users/nathan/github/');
define('kOutputRoot',kWebRootRoot . 'tipi/public');
if(!is_dir(kOutputRoot)) {
	mkdir(kOutputRoot);
}


buildConfigs();

function buildConfigs() {
	
	$hosts_template = '127.0.0.1	localhost
	255.255.255.255	broadcasthost
	::1             localhost 
	fe80::1%lo0	localhost
	';
	
	$path = kWebRootRoot . '*';

	foreach(glob($path) as $entity) {

		if(preg_match('/^\./',basename($entity))) {
			continue;
		}
		if(!is_dir($entity)) {
			continue;
		}
		$webroot = $entity . '/public';
		if(!is_dir($webroot)) {
			print "Skipping: No public path in " . basename($entity) . "\n";
			if(is_dir($entity . '/.git')) {
				print "\t (BUT .git DOES EXIST)\n";
			}
			continue;
		}
		$hostname = preg_replace('/[^a-z\d]/','-',strtolower(basename($entity)));
		$hosts_template .= createHost($hostname,$webroot);
	}

	$outhostfile = kOutputRoot . "/hosts";
	file_put_contents($outhostfile,$hosts_template);
}

function createHost($hostname, $webroot) {
	$localhostname = $hostname . '.local';

	$hosts_entry = "\n127.0.0.1\t$localhostname\n::1\t\t$localhostname\n";

	$webrootescaped = escapeshellarg($webroot);

	$httpd_template = <<<EOT
<VirtualHost *:80>
	ServerName $localhostname
	ServerAlias *.$localhostname
	DocumentRoot $webrootescaped
	<Directory $webrootescaped>
		AllowOverride All
		Allow from all
		Options -MultiViews
	</Directory>
</VirtualHost>

EOT;


	$outfile = kOutputRoot . "/$hostname.conf";

	$original = "";
	if(file_exists($outfile)) {
		$original = file_get_contents($outfile);
		if($original != $httpd_template) {
			print "Would change $hostname - perhaps you edited it manually. Refer to $outfile.new\n";
			file_put_contents("$outfile.new",$httpd_template);
		} else {
			print "Unchanged $hostname\n";
		}
	} else {
		print "*** Yay *** created a new vhost $localhostname for $webroot\n";
		file_put_contents($outfile,$httpd_template);
	}

	return $hosts_entry;
}
