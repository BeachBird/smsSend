<?php

IncludeModuleLangFile(__FILE__);
$module_id = "company.send_sms"; 

class company_send_sms {

	protected $host = 'http://x.x.x.x/send_sms.php';
    protected $login = null;
	protected $password = null;
	protected $sender = null;
	protected $proxyURL = null;
	protected $proxyPORT = null;
	
	function __construct() {
		$this->login = COption::GetOptionString('company.send_sms', "LOGIN");
		$this->password = COption::GetOptionString('company.send_sms', "PASSWORD");
		$this->sender = COption::GetOptionString('company.send_sms', "SENDER");
			
	}
	
	/**
    * отправка смс
    *
    * @access protected
    * @param $arDataJson json
    * @return array
    */
	protected function sms_send($arDataJson = null) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $this->host);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($ch, CURLOPT_POST, 1);
	        curl_setopt($ch, CURLOPT_POSTFIELDS, $arDataJson);
			
			$answer = curl_exec($ch);
			curl_close($ch);
			$arDataDecod = json_decode($answer, true);
			
			return $arDataDecod;
	}
	
	/**
    * генерируем код для рассылки 
    *
    * @access public
    * @param $len - длина кода
    * @return $code
    */
	public function GetCode($len = NULL) {
		$arNumer = array('1','2','3','4','5','6','7','8','9','0');  
		$code = "";
		
		if (isset($len))
			$count = $len;
		else {
			if (COption::GetOptionString('company.send_sms', "SMS_CODE") != "")
				$count = COption::GetOptionString('company.send_sms', "SMS_CODE");
			else
			{
				$count = 4;
			}
		}
		for($i = 0; $i < $count; $i++) {  
		  $index = mt_rand(0, count($arNumer) - 1);
		  $code .= $arNumer[$index];  
		}
		  
		return $code;  
	} 

	/**
    * проверка номера
    *
    * @access public
    * @param $phone - номер телефона
    * @return verify phone or false
    */
	public function CheckPhone($phone) {
		$phone = preg_replace("/\D/", "", $phone);
		
		if (strlen($phone) == 10 AND $phone[0] == "9") {
			$phone = "7".$phone;
		}
		if (strlen($phone) == 11) {
			$region = substr($phone, 0, 2);
			if ($region != "79")  {$result = 0;}
			else $result = trim($phone);
		}
		else {
		   $result = 0;
		}
		
		return $result;
	}
	
	/**
    * генерируем код и отправляем смс
    *
    * @access public
    * @param $phone - номер телефона
	* @param $arPattern - array of pattern for send
    * @return $code
    */
	public function SendCode($phone, $arPattern) {
		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

		$codeGen = $this->GetCode();
		$dtinsert = time();

		$arMessageOK = array();
		if (count($arPattern) > 0) {
			foreach ($arPattern as $key => $mess) {
				$arMessageOK[] = str_replace("#MESSAGE#", $codeGen, $mess);
			}
		}
		else {
			$arMessageOK[] = $codeGen;
		}
		
		$sended = false;
		foreach ($arMessageOK as $val) {
			$qry = "INSERT INTO b_check (date_send, code, phone, message, status) VALUES ('$dtinsert', '$codeGen', '".$DB->ForSql($phone)."', '$val', '0')";
			if ($DB->Query($qry)) {
				$rez = $this->smsSend($phone, $val);
				
				$sended = true;
			}
		}
		
		return $sended;
	} 
	
	/**
    * проверка кода из смс
    *
    * @access public
    * @param $code - код из смс
    * @return phone or false
    */
	public function CheckCode($code) {
		$teckTime =time();
		$qry = "SELECT * FROM b_check WHERE status = '0' AND code = '".$DB->ForSql($code)."' AND (($teckTime - date_send) <= 900) LIMIT 1";
		if ($res = $DB->Query($qry)) {
			$Row = $res->Fetch();
			$phoneUser = trim($Row['phone']);
			$qry = "UPDATE b_check SET status = '1' WHERE status = '0' AND code = '".$DB->ForSql($code)."' AND phone = '$phoneUser' LIMIT 1";
			$res = $DB->Query($qry);
			
			return $phoneUser;
		}
		else {
			return false;
		}
	} 
	
		
	/**
    * массив инициализации
    *
    * @access protected
    * @param $arData - formated array for send
    * @return array formated data
    */
	protected function sendPack($arData = null) {
		$arData['login'] = $this->login;
		$arData['password'] = $this->password;
		$arData['sender'] = $this->sender;
        
		return $arData;
    }
	
	/**
    * публичная функция для отсылки смс
    *
    * @access public
    * @param $phone - телефон для смс 
	* @param $message - сoобщение для отправки
    * @return array
    */
	public function smsSend($phone, $message, $id = NULL) {
		if (isset($id) && $id != "") {
			$id = IntVal($id);
			$v = CEventMessage::GetByID($id);
			$arPat = $v->Fetch();
			$pattern = strip_tags($arPat['MESSAGE']);
			if (strlen($pattern) > 0) {
				$message = str_replace("#MESSAGE#", $message, $pattern);
			}
		}
		$arData = array("phone" => $phone, "message" => $message, "operation" => "send");
		$arDataPuck = $this->sendPack($arData);
		
		return $this->sms_send($arDataPuck);
	}
	
}

?>
