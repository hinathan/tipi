#!/usr/bin/php
<?php

define('kWebRootRoot',$_ENV['HOME'] . '/github/');
define('kOutputRoot',kWebRootRoot . 'tipi/public');
define('kBasePort',80);
define('kAlternatePort',80);
if(!is_dir(kOutputRoot)) {
	print "About to create path: " . kOutputRoot . "\n";
	fgets(STDIN);
	mkdir(kOutputRoot);
}


buildConfigs();

function buildConfigs() {
	
	$hosts_template = '127.0.0.1	localhost local.letsral.ly
255.255.255.255	broadcasthost
::1             localhost 
fe80::1%lo0	localhost
	';
	
	$path = kWebRootRoot . '*';

	foreach(glob($path) as $entity) {
		$port = kBasePort;
		if(preg_match('/^\./',basename($entity))) {
			continue;
		}
		if(!is_dir($entity)) {
			continue;
		}
		$webroot = $entity . '/public';
		$hostname = preg_replace('/[^a-z\d]/','-',strtolower(basename($entity)));
		if(!is_dir($webroot)) {
			$port = kAlternatePort;
			print "No public path in " . basename($entity) . "\n";
			if(is_dir($entity . '/.git')) {
				print "\t (BUT .git DOES EXIST)\n";
				print "\t  ... so 'hiding' at port $hostname:$port\n";
				$webroot = $entity;
			} else {
				continue;
			}
		}
		$hosts_template .= createHost($hostname,$webroot,$port);
	}

	$outhostfile = kOutputRoot . "/hosts";
	file_put_contents($outhostfile,$hosts_template);
}

function createHost($hostname, $webroot, $port) {
	$localhostname = $hostname . '.local';

	$hosts_entry = "\n127.0.0.1\t$localhostname\n::1\t\t$localhostname\n";

	$webrootescaped = escapeshellarg($webroot);

	$httpd_template = <<<EOT
<VirtualHost *:$port>
	ServerName $localhostname
	ServerAlias *.$localhostname
	DocumentRoot $webrootescaped
	<Directory $webrootescaped>
		AllowOverride All
		Allow from all
		Options -MultiViews

		<FilesMatch ".(ico|epub)$">
			ExpiresActive On
			ExpiresDefault "access plus 10 years"
		</FilesMatch>
	</Directory>
	<LocationMatch "^/assets/.*$">
		# Some browsers still send conditional-GET requests if there's a
		# Last-Modified header or an ETag header even if they haven't
		# reached the expiry date sent in the Expires header.
		Header unset Last-Modified
		Header unset ETag
		FileETag None
		# RFC says only cache for 1 year
		ExpiresActive On
		ExpiresDefault "access plus 1 year"
	</LocationMatch>
	
</VirtualHost>

EOT;


	$outfile = kOutputRoot . "/$hostname.conf";

	$original = "";
	if(file_exists($outfile)) {
		$original = file_get_contents($outfile);
		if($original != $httpd_template) {
			print "Would change $hostname - perhaps you edited it manually. Instead writing to $outfile.new\n";
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
