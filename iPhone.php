<?PHP
 //iPhone.php by @TenGbps

 $user = 'steve@apple.com';
 $pass = 'SuPerICloudPassW000rD';
 $auth = $user.':'.$pass;

 $agnt = 'FindMyiPhone/500 CFNetwork/758.4.3 Darwin/15.5.0';
 $init = array();
 $head = array('X-Apple-Realm-Support: 1.0', 'X-Apple-Find-API-Ver: 3.0', 'X-Apple-AuthScheme: UserIDGuest');

 function iCloud_Logon() {
  global $auth, $user, $init, $agnt, $head;
  $call = curl_init();
  curl_setopt($call, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($call, CURLOPT_POST,           true);
  curl_setopt($call, CURLOPT_HTTPHEADER,     $head);
  curl_setopt($call, CURLOPT_CUSTOMREQUEST,  'POST');
  curl_setopt($call, CURLOPT_USERAGENT,      $agnt);
  curl_setopt($call, CURLOPT_USERPWD,        $auth);
  curl_setopt($call, CURLOPT_URL,            'https://fmipmobile.icloud.com/fmipservice/device/'.$user.'/initClient');
  $result = curl_exec($call);
  $httpcode = curl_getinfo($call, CURLINFO_HTTP_CODE);
  curl_close($call);
  $init = json_decode($result, true);
  if($httpcode == 200) {
   return true;
  } else {
   return false;
  }
 }
 
 function iCloud_Refresh_Phone($phoneid) {
  global $auth, $user, $init, $agnt, $head;
  $call = curl_init();
  curl_setopt($call, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($call, CURLOPT_POST,           true);
  curl_setopt($call, CURLOPT_HTTPHEADER,     $head);
  curl_setopt($call, CURLOPT_CUSTOMREQUEST,  'POST');
  curl_setopt($call, CURLOPT_USERAGENT,      $agnt);
  curl_setopt($call, CURLOPT_USERPWD,        $auth);
  $post = json_encode(array('clientContext' => array('appVersion' => '4.0', 'shouldLocate' => true, 'selectedDevice' => $phoneid, 'fmly' => true)));
  curl_setopt($call, CURLOPT_POSTFIELDS,      $post);
  curl_setopt($call, CURLOPT_URL,            'https://fmipmobile.icloud.com/fmipservice/device/'.$user.'/refreshClient');
  $result = curl_exec($call);
  curl_close($call);
  $tmp = json_decode($result, true);
  $init['content'] = $tmp['content'];
 }
 
 function iCloud_Ring_Phone($phoneid, $text) {
  global $auth, $user, $init, $agnt, $head;
  echo " \e[97m[>] Sending ring request to ".$phoneid."...\e[0m\n";
  $call = curl_init();
  curl_setopt($call, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($call, CURLOPT_POST,           true);
  curl_setopt($call, CURLOPT_HTTPHEADER,     $head);
  curl_setopt($call, CURLOPT_CUSTOMREQUEST,  'POST');
  curl_setopt($call, CURLOPT_USERAGENT,      $agnt);
  curl_setopt($call, CURLOPT_USERPWD,        $auth);
  $post = json_encode(array('device' => $phoneid, 'subject' => $text));
  curl_setopt($call, CURLOPT_POSTFIELDS,      $post);
  curl_setopt($call, CURLOPT_URL,            'https://fmipmobile.icloud.com/fmipservice/device/'.$user.'/playSound');
  $result = curl_exec($call);
  curl_close($call);
  echo " \e[32m[+] Ring request sended!\e[0m\n\n";
 }
 
 function GpsToAddress($lat, $lon) {
  $call = curl_init();
  curl_setopt($call, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($call, CURLOPT_USERAGENT,      'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:52.0) Gecko/20100101 Firefox/52.0');
  curl_setopt($call, CURLOPT_URL,            'https://maps.googleapis.com/maps/api/geocode/json?latlng='.$lat.','.$lon);
  $resul = curl_exec($call);
  $httpcode = curl_getinfo($call, CURLINFO_HTTP_CODE);
  curl_close($call);
  if($httpcode == 200) {
   $tmp = json_decode($resul, true);
   return $tmp['results'][0]['formatted_address'];
  } else {
   return "No address...";
  }
 }
 
 function AreaDetection($lat, $lon) {
  $area = 'En Mouvement';
  if($lat <= 50.012345 && $lat >= 50.012345 && $lon >= 3.123456 && $lon <= 3.123456) { $area = 'MyHome'; }
  return $area;
 }

 echo " \e[97m[*] Connecting to iCloud Fmip Web Service...\e[0m\n";
 if(iCloud_Logon()) {
  $firstname = $init['userInfo']['firstName'];
  $lastname  = $init['userInfo']['lastName'];
  $clientid  = base64_decode($init['serverContext']['clientId']);
  echo " \e[32m[+] Connected to account $clientid ($lastname $firstname)!\e[0m\n\n";

  foreach($init['content'] as $phoneId => $phoneData) {
   $phid = $init['content'][$phoneId]['id'];
   iCloud_Refresh_Phone($phid);

   $lost = intval($init['content'][$phoneId]['lostModeEnabled']);
   $code = intval($init['content'][$phoneId]['passcodeLength']);
   $name = $init['content'][$phoneId]['name'];
   $batt = $init['content'][$phoneId]['batteryStatus'];
   $blvl = $init['content'][$phoneId]['batteryLevel'] * 100;
   $mode = $init['content'][$phoneId]['deviceDisplayName'];
   $prot = intval($init['content'][$phoneId]['activationLocked']);

   $lati = round($init['content'][$phoneId]['location']['latitude'], 6);
   $long = round($init['content'][$phoneId]['location']['longitude'], 6);
   $addr = GpsToAddress($lati, $long);
   $area = AreaDetection($lati, $long);
   
   $code_txt = "\e[35m".$code." Digits Code\e[0m";
   $gpss_txt = "\e[35m(".$lati.",".$long.")\e[0m";
   $addr_txt = "\e[95m".$addr."\e[0m";
   $area_txt = "\e[94m[".$area."]\e[0m";
   $phid_txt = substr($phid, 0, 10).'...';

   if($lost == '0')           { $lost_txt = "\e[32mNot Lost\e[0m";             } else { $lost_txt = "\e[32mLOST!\e[0m";                   }
   if($prot == '1')           { $prot_txt = "\e[32miCloud Protected\e[0m";     } else { $prot_txt = "\e[32mNot Protected by iCloud\e[0m"; }
   if($batt == 'NotCharging') { $batt_txt = "\e[90mCharger Disconnected\e[0m"; } else { $batt_txt = "\e[93mCharging...\e[0m";             }

   if($blvl <= 20) { $blvl_txt = "\e[31mBattery ".$blvl."%\e[0m"; }
   if($blvl >  20) { $blvl_txt = "\e[33mBattery ".$blvl."%\e[0m"; }
   if($blvl >  50) { $blvl_txt = "\e[32mBattery ".$blvl."%\e[0m"; }

   echo " \e[97m[>] $name, $mode, $phid_txt\e[0m\n";
   echo "     $blvl_txt, $lost_txt, $code_txt, $batt_txt, $prot_txt\n";
   echo "     $addr_txt $gpss_txt $area_txt\n";
   echo "\n";

  }
  //iCloud_Ring_Phone('demoblahblahid', "Test from php !");

 } else {
  exit(" \e[32m[X] Access denied to iCloud !\e[0m\n");
 }
?>
