<?php
 
// list of supers
$supers = array(
// CS
23919	=> 'Aeon',
23913	=> 'Nyx',
22852	=> 'Hel',
23917	=> 'Wyvern',
3514	=> 'Revenant',
// TITAN
11567	=> 'Avatar',
671	=> 'Erebus',
23773	=> 'Ragnarok',
3764	=> 'Leviathan',
);
 
// kb to check
$kb_url = 'http://rooksandkings.com/killboard';
 
// mail title (with placeholders)
$mail_title = "RnK killed %{SHIP} (%{DATE})!";
 
// where to send notification emails for super kills
$send_mails = array('**********@*****.***');
 
// file to keep last processed kill number
$lastid_file = 'findsuperkills_lastintID.txt';
 
 
// END OF CONFIG //
 
if ('cli' != php_sapi_name()) {
header("Content-Type: text/plain;charset=utf-8");
}
 
$query = array('a' => 'idfeed');
$lastintID = null;
if(file_exists($lastid_file) && $lastintID = (int)file_get_contents($lastid_file)) {
$query['lastintID']	= ($lastintID+1);
} else {	// first run, take last 90 days
$query['startdate']	= (time() - (90*24*60*60));
}
 
$feedurl = $kb_url.'?'.http_build_query($query);
$xml = @simplexml_load_string(file_get_contents($feedurl));
 
if(!$xml) {
exit("error processing xml from {$feedurl}\n");
}
if(empty($xml->result->rowset)) {
exit("no new mails!\n");
}
 
$rowset = (array)@$xml->result->rowset;
foreach($rowset['row'] as $km) {
if(!isset($km['killInternalID']) || !isset($km->victim['shipTypeID'])) {
continue;
}
$lastintID	= (int)$km['killInternalID'];
$shipTypeID	= (int)$km->victim['shipTypeID'];
// check if super
if(!isset($supers[$shipTypeID])) {
continue;
}
$message = $supers[$shipTypeID].' piloted by '.@$km->victim['characterName'].
' (corp: '.@$km->victim['corporationName'].
'; alliance:'.@$km->victim['allianceName'].')'.
" is killed!\n".
"You can see kill details here: ".$kb_url.'?a=kill_detail&kll_id='.$lastintID;
$subject = str_replace(array('%{SHIP}','%{DATE}'),array($supers[$shipTypeID],@$km['killTime']),$mail_title);
$headers = 'Content-Type: text/plain; charset=UTF-8';
foreach ($send_mails as $mail) {
// send mail!
$res = mail($mail,$subject,$message,$headers);
if(!$res) {
die("mail send error to {$mail}!");
} else {
echo "mail send for kill #{$lastintID} (".$supers[$shipTypeID].") \n";
}
}
}
 
echo "done! last-id #{$lastintID}; ".count($rowset['row'])." mails processed\n";
 
file_put_contents($lastid_file, (string)$lastintID);
