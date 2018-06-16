<?php

$f3=require('lib/base.php');
$var=require('lib/variable.php');

$f3->set('DEBUG',1);
if ((float)PCRE_VERSION<7.9)
	trigger_error('PCRE version is out of date');

$f3->config('config.ini');

$f3->route('GET /', function($f3){
	echo 'this is index';
});

$f3->route('GET /profile' , function($f3){
	if ($f3->exists("SESSION.email")){
		$f3->set('content', 'profile.htm');
		echo View::instance()->render('layout.htm');	
	}else{
		echo "you're logged out.";
	}
});

$f3->route('GET|POST /login' , function($f3){
	$db = new \DB\SQL('mysql:host=localhost;dbname=comfirm_users', 'root', '');
	$user = new \DB\SQL\Mapper($db, 'users');
	$auth = new \Auth($user, array('id'=>'email', 'pw'=>'password'));
	if ($f3->exists("SESSION.email")){
		$f3->set('content', 'profile.htm');
		echo View::instance()->render('layout.htm');	
	}else{	
	$f3->set('content', 'login.htm');
	echo View::instance()->render('layout.htm');	
	$email_login = $f3->get('POST.email');
	$password_login = $f3->get('POST.password');
	$isemailverify = $db->exec("SELECT isEmailConfirmed FROM users WHERE email='".$email_login."'");
	$password_result = $db->exec("SELECT password FROM users WHERE email='".$email_login."'");
	$password_db = $password_result[0]['password'];
	$password_verify = password_verify($password_login, $password_db);
	if ($password_verify==1){
		if ($isemailverify[0]['isEmailConfirmed'] == 1){
			$f3->set("SESSION.email",$email_login);			
			$f3->reroute('/profile');
		}else{
			echo "<script>alert('please verify your email');</script>";
			

		}
	}
	}
});

$f3->route('GET|POST /logout',function($f3){
	
	if ($f3->exists('SESSION.email')){
		$f3->set('msg','thank for using me..');
		$f3->clear('SESSION.email');
	}else{
		$f3->set('msg','back to home');
	}
	$f3->set('content', 'logout.htm');
	echo View::instance()->render('layout.htm');
});

$f3->route('GET /register',function($f3){
	$f3->set('content', 'register.htm');
	echo View::instance()->render('layout.htm');
});

function sendingmail($tkn){
	$mail = new PHPMailer();
	$mail->isSMTP();
	$mail->SMTPAuth = true;
	$mail->SMTPSecure = "tls";
	$mail->Host = "smtp.gmail.com"; 
	$mail->Port = 587;
	$mail->isHTML();
	$mail->CharSet = "utf-8"; 
	$mail->Username = "opg6.eiei.153@gmail.com"; 
	$mail->Password = "1534260w"; 
	$mail->SetFrom = ('xxxxx@gmail.com'); 
	$mail->FromName = "Sender Person"; 
	$mail->Subject = "ทดสอบการส่งอีเมล์";  
	$mail->Body = "<a href='http://localhost:8000/verify/".$tkn."'>Verify your email</a>";
	$mail->AddAddress('chaturapak.bo.58@ubu.ac.th','Recive Name'); 
	
	if ($mail->Send()){
	echo "ข้อความของคุณได้ถูกส่งไปแล้ว!!";
	}else{
	echo "การส่งไม่สำเร็จ";
	}
}

$f3->route('GET|POST /register/insert' ,function($f3){
	require_once('PHPMailer/PHPMailerAutoload.php');
	$db = new \DB\SQL('mysql:host=localhost;dbname=comfirm_users', 'root', '');
	$user = new \DB\SQL\Mapper($db, 'users');
	// $arr = array('id'=>'user_id', 'pw'=>'password');
	$fullname = $f3->get('POST.fullname');
	$email = $f3->get('POST.email');
	$password = $f3->get('POST.password');
	$confirmPassword = $f3->get('POST.confirm-password');
	$str = str_shuffle("QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm1234567890!$()*");
	$token = substr($str, 0,10);
	$flag = false;
	$password_encrypt = password_hash($password, PASSWORD_BCRYPT);
	
	// $password_encrypt = $f3->hash($password ,10);
if ($fullname != '' || $email != ''){
	$row = $db->exec('SELECT * FROM users');
	foreach ($row as $rows){
		if ($password != $confirmPassword){
			echo 'password mismatch';
			$flag = true;
		}else {
			if ($rows['email'] == $email){
				
				$flag = true;
			}
		}	
	}
	if ($flag == false){	
		$result = $db->exec("INSERT INTO users(name, email, password, token) VALUES('".$fullname."', '".$email."','".$password_encrypt."','".$token."')");
		sendingmail($token);
	}else {
		echo 'This email already exists, please try again.';
	}
}else{
	echo 'empty fullname or email';
}	
	$f3->set('content', 'register.htm');
	echo View::instance()->render('layout.htm');
	
});

$f3->route('GET /verify/@token', function($f3){
	$tokenURL = $f3->get('PARAMS.token');	
	$db = new \DB\SQL('mysql:host=localhost;dbname=comfirm_users', 'root', '');
	$user = new \DB\SQL\Mapper($db, 'users');
	$update = $db->exec("UPDATE users SET isEmailConfirmed=1 WHERE token='".$tokenURL."'");
	$f3->set('content', 'verify.htm');
	$f3->set('msg','thank for verify.');
	echo View::instance()->render('layout.htm');
});


$f3->redirect('GET|HEAD /*', '/');

$f3->run();
