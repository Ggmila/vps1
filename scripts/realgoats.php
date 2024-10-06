<?php
date_default_timezone_set("UTC");

function Curl($url, $h = 0, $post = 0,$data_post = 0) {
	while(true){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_COOKIE,TRUE);
		if($post) {
			curl_setopt($ch, CURLOPT_POST, true);
		}
		if($data_post) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_post);
		}
		if($h) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $h);
		}
		curl_setopt($ch, CURLOPT_HEADER, true);
		$r = curl_exec($ch);
		$c = curl_getinfo($ch);
		if(!$c) return "Curl Error : ".curl_error($ch); else{
			$head = substr($r, 0, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
			$body = substr($r, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
			curl_close($ch);
			if(!$body){
				print "Check your Connection!";
				sleep(2);
				print "\r                         \r";
				continue;
			}
			return array($head,$body);
		}
	}
}
function Save($nama_data){
	if(file_exists($nama_data)){
		$data = file_get_contents($nama_data);
	}else{
		$data = readline("input ".$nama_data." :");
		echo "\n";
		file_put_contents($nama_data,$data);
	}
	return $data;
}
function spasi($string, $spasi){
	return $string.str_repeat(" ",$spasi-strlen($string))."| ";
}
function kolom($urutan, $wl, $wr, $bal){
	$spasi = strlen($bal)+2;
	$urutan = spasi($urutan, $spasi);
	$wl = spasi($wl, $spasi);
	$wr = spasi($wr, $spasi);
	$bal = spasi($bal, $spasi);
	return $urutan.$wl.$wr.$bal;
}
ulang:
$token = Save('Autorization');
if(!preg_match('/Bearer/', $token)){
	unlink('Autorization');
	print "isi Authorization dengan awal Bearer\n";
	goto ulang;
}

$h = [
	'Accept: application/json, text/plain, */*',
	'Content-Type: application/json',
	'origin: https://dev.goatsbot.xyz',
	'Authorization: '.$token,
	'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36 Edg/128.0.0.0'
];

$r = json_decode(curl('https://api-me.goatsbot.xyz/users/me', $h)[1],1);
$bal = $r['balance'];
$user = $r['user_name'];
if(!$user){unlink('Autorization'); goto ulang;}

print "Username: $user\n";
print "Balance : $bal\n";
print str_repeat('~', 50)."\n";

print "1. special mission\n";
print "2. dice\n";
print str_repeat('~', 50)."\n";

$switch = readline("input nomor: ");
print str_repeat('~', 50)."\n";

if($switch == 1){
	while(true){
		$r = json_decode(curl('https://api-mission.goatsbot.xyz/missions/user', $h)[1],1);
		$special_mision = $r["SPECIAL MISSION"][0];
		$id = $special_mision["_id"];
		if(!$id)goto ulang;
		$time = $special_mision["next_time_execute"];
		$tmr=$time-time();
		if($tmr>0){
			for($i=$tmr+rand(2,5); $i>0; $i--){
				print $i."\r";
				sleep(1);
			}
		}
		$r = json_decode(curl('https://dev-api.goatsbot.xyz/missions/action/'.$id, $h, 1)[1],1);
		if($r['status'] == "success"){
			print "success\n";
		}
		$bal = json_decode(curl('https://api-me.goatsbot.xyz/users/me', $h)[1],1)['balance'];
		print "New Balance : $bal\n";
		print str_repeat('~', 50)."\n";
	}
	exit;
}elseif($switch == 2){
	
	$maxwin = 1000;//1000x bet
	$progress = 0;
	
	$putaran = 0;
	$putaran_win = 0;
	$putaran_lose = 0;
	
	isi_bet:
	print "isi Bet dengan angka min 1 = 1 Goats\n";
	print "win chance 49% | iflose: 2x bet | ifwin: normal bet\n";
	print "stop after win X$maxwin bet total\n";
	if(file_exists('Bet')){
		$confirm = readline("apakah mau ganti bet? (y/n):");
		if(strtolower($confirm[0]) == "y"){
			unlink('Bet');
		}
	}
	print str_repeat('~', 50)."\n";
	
	$betawal = Save('Bet');
	if(is_numeric($betawal)){
	}else{
		unlink('Bet');
		"isi angka woy!!";
		goto isi_bet;
	}

	if($betawal > $bal){
		unlink("Bet");
		"Bet lebih besar dari saldo njer\n";
		goto isi_bet;
	}
	//setting
	$bet = $betawal;
	$maxwin = $bet*$maxwin;
	
	//exsekusi
	while(true){
		$data = '{"point_milestone":49,"is_upper":false,"bet_amount":'.$bet.'}';
		$r = json_decode(curl('https://api-dice.goatsbot.xyz/dice/action', $h, 1, $data)[1],1);
		$reward = $r['dice']['reward'];
		$balance = $r['user']['balance'];
		if($reward){
			$putaran_win++;
			$progress = $progress+$bet;
			print kolom($putaran, "Win", "+".$bet, $balance)."\n";
			$bet = $betawal;
		}else{
			$putaran_lose++;
			$progress = $progress-$bet;
			print kolom($putaran, "Lose", "-".$bet, $balance)."\n";
			//print $putaran."| Lose | - ".$bet." | ".$balance."\n";
			$bet = $bet*2;
		}
		sleep(1);
		if($bet > $balance)exit("bankrut\n");
		if($progress >= $maxwin){
			print str_repeat('~', 50)."\n";
			print "Balance awal: $bal\n";
			print "Total putaran: ".($putaran+1)."\n";
			print "Putaran Win: $putaran_win\n";
			print "Putaran Lose: $putaran_lose\n";
			print "Balance akhir: $balance\n";
			exit;
		}
		$putaran++;
	}
}else{
	exit("Tolol!\n");
}