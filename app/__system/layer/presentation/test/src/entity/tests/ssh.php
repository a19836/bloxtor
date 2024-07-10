<?php
//http://jplpinto.localhost/__system/test/tests/ssh

//MyCurl test
include_once get_lib("org.phpframework.util.web.SSHHandler");

$SSHHandler = new SSHHandler();
$SSHHandler->setSSHAuthKeyTmpFolderPath(TMP_PATH);
$username = ""; //delete this username after finished my test
$password = ""; //delete this password after finished my test

if (!$username || !$password) {
	echo "Username or Password missing";
	die();
}

$connected = $SSHHandler->connect(array(
	"host" => "localhost",
	"port" => "22",
	"username" => $username,
	"password" => $password,
	"ssh_auth_pub_string" => null,
	"ssh_auth_priv_string" => null,
	"ssh_auth_passphrase" => null,
	"fingerprint" => null,
));

if (!$connected) {
	echo "No connected";
	die();
}

if (!$SSHHandler->createRemoteFolder("/tmp/test/")) {
	echo "Could not create /tmp/test/";
	die();
}

$output = $SSHHandler->exec("echo 123 > /tmp/test/numbers.txt");
if (trim($output)) {
	echo "Something went wrong trying to create /tmp/test/numbers.txt";
	die();
}

if (!$SSHHandler->createRemoteFolder("/tmp/test/sub_folder")) {
	echo "Could not create /tmp/test/sub_folder";
	die();
}

$output = $SSHHandler->exec("echo 'jp' > /tmp/test/sub_folder/text.txt");
if (trim($output)) {
	echo "Something went wrong trying to create /tmp/test/text.txt";
	die();
}

$sftp = $SSHHandler->sshToSftp();
$exists = $SSHHandler->getFileInfo("/tmp/test/sub_folder_link");
if (!$exists && !ssh2_sftp_symlink($sftp, "/tmp/test/sub_folder", "/tmp/test/sub_folder_link")) {
	echo "Could not create symbolic link to /tmp/test/sub_folder";
	die();
}

$files = $SSHHandler->scanRemoteDir("/tmp/test/");

echo "Files inside of /tmp/test/ folder:\n<br>";
echo "<pre>";
print_r($files);
echo "</pre>";

echo "Files details inside of /tmp/test/ folder:\n<br>";
if ($files)
	foreach ($files as $file) {
		$info = $SSHHandler->getFileInfo("/tmp/test/$file");
		
		echo "file: $file\n<br>";
		echo "<pre>";
		print_r($info);
		echo "</pre>";
		echo "\n<br>";
	}

if (!$SSHHandler->removeRemoteFile("/tmp/test/sub_folder_link/")) {
	echo "Could not delete symbolic link /tmp/test/sub_folder_link";
	die();
}
else
	echo "symbolic link deleted (/tmp/test/sub_folder_link)<br><br>";

$files = $SSHHandler->scanRemoteDir("/tmp/test/");
$sub_files = $SSHHandler->scanRemoteDir("/tmp/test/sub_folder");
echo "Files inside of /tmp/test/ folder after removing symbolic link:\n<br>";
echo "<pre>";
print_r($files);
echo "Files inside of /tmp/test/sub_folder:\n";
print_r($sub_files);
echo "</pre>";

if ($sub_files && count($files) == 2)
	echo "Files correctly and sub files inside of /tmp/test/sub_folder were NOT deleted! Only symbolic link was deleted!<br>SO THIS IS OK<br><br>";
else
	echo "Files NOT correctly and sub files inside of /tmp/test/sub_folder were deleted! Folder where symbolic link is pointing to was deleted too!<br>SO THIS IS WRONG<br><br>";
	
if (!$SSHHandler->removeRemoteFile("/tmp/test/")) {
	echo "Could not delete /tmp/test/ folder";
	die();
}
echo "/tmp/test/ folder was deleted correctly!";
?>
