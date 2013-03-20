<?php
	// Condition check
	$pv = `pv --version`;
	if (!preg_match("/This program is free software/misU" , $pv)) {
		echo "PV Command not found\n";
		die();
	}
	
	$favorite_ip = array();
	$favorite_ip[] = array('Localhost' , '127.0.0.1');
	$favorite_ip[] = array('Pandemona' , '192.168.2.100');
	$favorite_ip[] = array('Spock' , 'spock.cs.co.id');
	$favorite_ip[] = array('Fred' , '192.168.2.27');
	$favorite_ip[] = array('Ted' , '192.168.2.125');
	$favorite_ip[] = array('Fred Mac' , 'megumi.local');
	
	$favorite_username = array();
	$favorite_username[] = "root";
	$favorite_username[] = "rickyok";
	$favorite_username[] = "wigo";
	
	function get_ip() {
		global $favorite_ip;
		
		echo "\n";
		foreach($favorite_ip as $k => $fi) {
			echo "[{$k}] {$fi[0]} - {$fi[1]}\n";
		}
		echo "Insert number or type IP address: ";
		$selection = trim(fgets(STDIN));
		if (array_key_exists($selection , $favorite_ip)) {
			return $favorite_ip[$selection][1];
		}
		else {
			return $selection;
		}
	}
	
	function get_username() {
		global $favorite_username;
		
		echo "\n";
		foreach($favorite_username as $k => $fi) {
			echo "[{$k}] {$fi}\n";
		}
		echo "Insert number or type username: ";
		$selection = trim(fgets(STDIN));
		if (array_key_exists($selection , $favorite_username)) {
			return $favorite_username[$selection];
		}
		else {
			return $selection;
		}
	}
	
	// SSH Proxy Source
	echo "Use ssh proxy for source? (y/N): ";
	$useproxy = trim(fgets(STDIN));
	if (strtoupper($useproxy) == 'Y') {
		echo "SSH proxy IP address: ";
		$sship = get_ip();
		echo "SSH username: ";
		$sshuser = get_username();
	}
	// SSH Proxy Destination
	echo "Use ssh proxy for target? (y/N): ";
	$useproxytarget = trim(fgets(STDIN));
	if (strtoupper($useproxy) == 'Y') {
		echo "SSH proxy IP address: ";
		$sshiptarget = get_ip();
		echo "SSH username: ";
		$sshusertarget = get_username();
	}
	// Source
	echo "Source IP Address: ";
	$sourceip = get_ip();
	echo "Source MySQL Username: ";
	$sourceuser = get_username();
	echo "Source MySQL Password: ";
	$sourcepass = trim(fgets(STDIN));
	// Target
	echo "Target IP Address: ";
	$targetip = get_ip();
	echo "Target MySQL Username: ";
	$targetuser = get_username();
	echo "Target MySQL Password: ";
	$targetpass = trim(fgets(STDIN));
	// Databases
	echo "Databases to be dump (space seperated): ";
	$databases = trim(fgets(STDIN));
	
	// Temp file setting
	$tempfile = "/tmp/tempsqldump.sql.gz";
	
	// The process
	if (strtoupper($useproxy) == 'Y') {
		echo "Dumping database from remote host\n";
		`ssh {$sshuser}@{$sship} "mysqldump -h{$sourceip} -u{$sourceuser} -p{$sourcepass} --databases {$databases} | gzip > {$tempfile}"`;
		//echo "ssh {$sshuser}@{$sship} \"mysqldump -h{$sourceip} -u{$sourceuser} -p{$sourcepass} --databases {$databases} | gzip > {$tempfile}\"";
		echo "Download dumpfile\n";
		`scp {$sshuser}@{$sship}:{$tempfile} {$tempfile}`;
		//echo "scp {$sshuser}@{$sship}:{$tempfile} {$tempfile}";
		echo "Deleting remote source file\n";
		`ssh {$sshuser}@{$sourceip} "rm {$tempfile}"`;
		//echo "ssh {$sshuser}@{$sship} \"rm {$tempfile}\"";
	}
	else {
		echo "Dumping local database\n";
		`mysqldump -h{$sourceip} -u{$sourceuser} -p{$sourcepass} --databases {$databases} | pv | gzip > {$tempfile}`;
		//echo "mysqldump -h{$sourceip} -u{$sourceuser} -p{$sourcepass} --databases {$databases} | pv | gzip > {$tempfile}";
	}
	if (strtoupper($useproxytarget) == 'Y') {
		echo "Upload file proxy\n";
		`scp {$tempfile} {$sshusertarget}@{$sshiptarget}:{$tempfile}`;
		//echo "scp {$tempfile} {$sshusertarget}@{$sshiptarget}:{$tempfile}";
		echo "Dumping on remote server\n";
		`ssh {$sshusertarget}@{$sshiptarget} "mysql -h{$sourceip} -u{$sourceuser} -p{$sourcepass} < {$tempfile}"`;
		//echo "ssh {$sshusertarget}@{$sshiptarget} \"mysql -h{$sourceip} -u{$sourceuser} -p{$sourcepass} < {$tempfile}\"";
		echo "Deleting remote target file\n";
		`ssh {$sshusertarget}@{$sshiptarget} "rm {$tempfile}"`;
		//echo "`ssh {$sshusertarget}@{$sshiptarget} \"rm {$tempfile}\"";
	}
	else {
		echo "Dumping file\n";
		`pv {$tempfile} | gunzip | mysql -u{$targetuser} -p{$targetpass} -h{$targetip}`;
		//echo "pv {$tempfile} | gunzip | mysql -u{$targetuser} -p{$targetpass} -h{$targetip}";
	}
	// Cleaning garbage
	echo "Deleting local file\n";
	`rm {$tempfile}`;
	//echo "rm {$tempfile}";
	echo "All done\n";