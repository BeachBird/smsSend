<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Highloadblock\HighloadBlockTable as HLBT;

define("APP_PASS", "test");
define("APP_NAME", "test");
define("APP_ADDRES", "https://test");
define('PORTAL', $_REQUEST['member_id']);
define('PORTAL_URL', $_REQUEST['DOMAIN']);
define("USERS_HL_BLOCK_ID", 2);
define("LESSONS_HL_BLOCK_ID", 3);
define("PROGS_HL_BLOCK_ID", 7);
define("TOKEN_HL_BLOCK_ID", 4);
define("TOKEN_HL_FIELD_ID", 1);
define("SETTINGS_HL_BLOCK_ID",9);
define("CERT_HL_BLOCK_ID",10);
define("STAT_PROGS",8);
define("TPL_CERT_HL_BLOCK_ID",11);
class App_t {
	public static function GetNwToken(){
		if(CModule::IncludeModule('highloadblock')) {
			$entity_data_class = GetEntityDC(TOKEN_HL_BLOCK_ID);
			$rsData = $entity_data_class::getList(array(
				"select" => array("UF_TOKEN","UF_TOKEN_LIFETIME"),
				"filter" => array("ID" => TOKEN_HL_FIELD_ID)
			));
			$token = $rsData->fetch();

			$token_lifetime = strtotime($token["UF_TOKEN_LIFETIME"]);
			$now = time();
			if($now > $token_lifetime){
				$token["UF_TOKEN"] = self::SetNwTokenQ();
			}
			return $token["UF_TOKEN"];
		}
	}
	public static function GetSettings(){
		if(CModule::IncludeModule('highloadblock')) {
			$entity_data_class = GetEntityDC(SETTINGS_HL_BLOCK_ID);
			$rsData = $entity_data_class::getList(array(
				"select" => array("*"),
				"filter" => array("UF_DOMAIN_B24" => PORTAL)
			));
			$data = $rsData->fetch();
			return $data;
		}
	}
	public static function SetNwToken(){
		if(CModule::IncludeModule('highloadblock')) {
			$entity_data_class = GetEntityDC(TOKEN_HL_BLOCK_ID);
			$rsData = $entity_data_class::getList(array(
				"select" => array("UF_TOKEN","UF_TOKEN_LIFETIME"),
				"filter" => array("ID" => TOKEN_HL_FIELD_ID)
			));
			$token = $rsData->fetch();

			$token_lifetime = strtotime($token["UF_TOKEN_LIFETIME"]);
			$now = time();
			if($now > $token_lifetime){
				$token["UF_TOKEN"] = self::SetNwTokenQ();
			}
			return $token["UF_TOKEN"];
		}
	}
	public static function SetNwTokenQ(){
		$query = APP_ADDRES.'/Application/GetToken?ApplicationName='.APP_NAME .'&ApplicationPassword='.APP_PASS;
		try {$token = file_get_contents($query);} catch (Exception $e) {
			$error_det = " SetNwToken Query=ApplicationName=".APP_NAME .'&ApplicationPassword='.APP_PASS;
			include('error.php');
			die();
		}
		$token = json_decode($token, true);

		if(CModule::IncludeModule('highloadblock')) {
			$entity_data_class = GetEntityDC(TOKEN_HL_BLOCK_ID);
			$result = $entity_data_class::update(TOKEN_HL_FIELD_ID, array(
				'UF_TOKEN' => $token["Token"],
				'UF_TOKEN_LIFETIME' => $token["ValidTo"]
			));
		}
		return $token["Token"];
	}
	public static function ErrorFunc(){
		$error_ind=0;
		include('error.php');
		die();
	}
	public static function GetPasswordHash($login,$portal){
		$arUsers = getDBUsers($portal); 
		foreach ($arUsers as $user){
			if(mb_strtolower($user["LOGIN"]) == mb_strtolower($login)){ 
				if(!empty($user["PASSWORD_HASH"])) $password = $user["PASSWORD_HASH"];
				break;
			}
		}
		return $password;
	}

	public static function GetPasswordHashNew($login,$portal){    
		$password = "";      
		if(CModule::IncludeModule('highloadblock')) {
			$entity_data_class = GetEntityDC(USERS_HL_BLOCK_ID);
			$rsData = $entity_data_class::getList([
				'filter' => ['UF_LOGIN' => $login,'!=UF_PASSWORD_HASH' => '']
			]);
			$password = '';
			if ($el = $rsData->fetch()) {
				$password = $el["UF_PASSWORD_HASH"];
			}
		}
		return $password; 
	}


	public static function GetConfigurationFragment($token,$email,$password,$lesson){
		global $token;
		$attempts = 3;
		$success = false;

		for ($i = 1; $i <= $attempts; $i++) {
			$check_query = APP_ADDRES."/Application/GetConfigurationFragment?Token=".$token."&Login=".$email."&PasswordHash=".$password."&ConfigurationCode=".$lesson;
			$check_query_result = file_get_contents($check_query);
			$check_query_result = json_decode($check_query_result,true);

			if($check_query_result["Success"] !== false) {
				$success = true;
				break;
			}else{
				$token = self::SetNwToken();
			}
			usleep(500000);
		}

		if($success == true){
			echo "<iframe style='position:fixed; top:0; left:0; bottom:0; right:0; width:100%; height:100%; border:none; margin:0; padding:0; overflow:hidden; z-index:999999;' src='".APP_ADDRES."/Application/GetConfigurationFragment?Token=".$token."&Login=".$email."&PasswordHash=".$password."&ConfigurationCode=".$lesson."'></iframe>";

		} else {
			$error_det = "Func GetConfigurationFragment ".$check_query;
			include('error.php');
			die();
		}
	}
	public static function GetUsers($token){
		global $token;
		$attempts = 3;
		$success = false;
		$users = [];

		for ($i = 1; $i <= $attempts; $i++) {
			$query = APP_ADDRES."/Application/GetUsers?Token=".$token;
			$users = file_get_contents($query);
			$users = json_decode($users,true);

			if($users["Success"] !== false) {
				$success = true;
				break;
			}else{
				$token = self::SetNwToken();
			}
			sleep(0.5);
		}

		if($success){
			return $users;
		} else {
			$error_det = "Func GetUsers ". $query;
			include('error.php');
			die();
		}
	}
	public static function GetUsersPasswordHash($token){
		global $token;
		$attempts = 3;
		$success = false;

		for ($i = 1; $i <= $attempts; $i++) {
			$query = APP_ADDRES."/Application/GetUsers?Token=".$token;
			$users = file_get_contents($query);
			$users = json_decode($users,true);

			if($users["Success"] !== false) {
				$success = true;
				break;
			}else{
				$token = self::SetNwToken();
			}
			sleep(0.5);
		}

		if($success){
			return $users;
		} else {
			$error_det = "Func GetUsersPasswordHash ". $query;
			include('error.php');
			die();
		}
	}
	public static function checkUserAndRegister($arUser,$token){
		$users = GetUsers($token);
		$exist = false;
		foreach ($users["Users"] as $user){
			if($user["Login"] == $arUser["EMAIL"]){
				$exist = true;
				break;
			}
		}
		if($exist == false){
			$result = RegisterNbixUser($arUser["LOGIN"],$token);
			return $result;
		}
	}
	public static function RegisterNbixUser($login,$token){
		global $token;
		$attempts = 3;
		$success = false;
		if(mb_strpos($login,"/")==mb_strlen($login)-1)return false;
		for ($i = 1; $i <= $attempts; $i++) {
			$queryRegisterUser = APP_ADDRES."/Application/RegisterUser?Token=".$token."&Login=".$login;
			$arRes = file_get_contents($queryRegisterUser);
			$arRes = json_decode($arRes,true);
			if($arRes["Success"] !== false) {
				$success = true;
				break;
			}else{
				if($arRes["Message"]=="Token \"$token\" not found")
				{
					$token = self::SetNwTokenQ();
				}
				else
					$token = self::SetNwToken();
			}
			sleep(0.5);
		}

		if($success){

			$queryUrl = 'https://'.PORTAL_URL.'/rest/user.current.json';

			if(!empty($_REQUEST['AUTH_ID'])) {
				$queryData = http_build_query(array(
					"auth" => $_REQUEST['AUTH_ID']
				));

				$curl = curl_init();
				curl_setopt_array($curl, array(
					CURLOPT_SSL_VERIFYPEER => 0,
					CURLOPT_POST => 1,
					CURLOPT_HEADER => 0,
					CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_URL => $queryUrl,
					CURLOPT_POSTFIELDS => $queryData,
				));

				$result = json_decode(curl_exec($curl), true);
				curl_close($curl);
				$user = array(
					"NAME" => $result['result']['NAME'],
					"LAST_NAME" => $result['result']['LAST_NAME'],
					"SECOND_NAME" => $result['result']['SECOND_NAME'],
					"LOGIN" => PORTAL . "/" . $result['result']['ID']
				);

				$UserData = [
					'Login'      => $login,
					'LastName'   => $user['LAST_NAME'],
					'FirstName'  => $user['NAME'],
					'SecondName' => $user['SECOND_NAME']
				];

				$ChangeProperty = [
					'LastName'   => true,
					'FirstName'  => true,
					'SecondName' => true
				];

				$queryRegisterUser = APP_ADDRES."/Application/UpdateUserData?Token=".$token.'&UserData='.urlencode(json_encode($UserData)).'&UserDataUpdateMap='.urlencode(json_encode($ChangeProperty));

				file_get_contents($queryRegisterUser);
			}
			return $arRes["User"];
		} else {
			$error_det = "Func RegisterNbixUser ". $queryRegisterUser." ; ".implode(";",$arRes);
			include('error.php');
			die();
		}
	}
	public static function CheckExistsUser($login,$token){
		global $token;
		$attempts = 3;
		$success = false;
		if(mb_strpos($login,"/")==mb_strlen($login)-1)return false;
		for ($i = 1; $i <= $attempts; $i++) {
			$queryRegisterUser = APP_ADDRES."/Application/CheckExistsUser?Token=".$token."&Login=".$login;
			$arRes = file_get_contents($queryRegisterUser);
			$arRes = json_decode($arRes,true);
			if($arRes["Success"] !== false) {
				$success = true;
				break;
			}elseif($arRes["ErrorCode"]!==-1){
				$token = self::SetNwToken();
			}
			else
				break;
			sleep(0.5);
		}
		return $success;
	}

	public static function deleteNbixUser($login,$token){
		global $token;
		$attempts = 3;
		$success = false;

		for ($i = 1; $i <= $attempts; $i++) {
			$query = APP_ADDRES."/Application/DeleteUser?Token=".$token."&Login=".$login;
			$arRes = file_get_contents($query);
			$arRes = json_decode($arRes,true);

			if($arRes["Success"] !== false) {
				$success = true;
				break;
			}else{
				$token = self::SetNwToken();
			}
			sleep(0.5);
		}

		if($success){
			return $arRes;
		} else {
			$error_det = "Func deleteNbixUser ". $query;
			include('error.php');
			die();
		}
	}
	public static function GetEntityDC($HlBlockId) {
		if(CModule::IncludeModule('highloadblock')) {
			if (empty($HlBlockId) || $HlBlockId < 1) {
				return false;
			}
			$hlblock = HLBT::getById($HlBlockId)->fetch();
			$entity = HLBT::compileEntity($hlblock);
			$entity_data_class = $entity->getDataClass();
			return $entity_data_class;
		}
	}
	public static function DeleteNbixLesson($token,$code,$login,$pas){
		global $token;
		$attempts = 3;
		$success = false;
		if($code!=""){
			for ($i = 1; $i <= $attempts; $i++) {
				$queryDeleteLesson=APP_ADDRES."/EducationHome/DeleteLessonConfiguration?Token=".$token."&ConfigurationCode=".$code."&Login=".$login."&PasswordHash=".$pas;
				$arRes = file_get_contents($queryDeleteLesson);
				$arRes = json_decode($arRes, true);

				if($arRes["Success"] !== false) {
					$success = true;
					break;
				}else{
					$token = self::SetNwToken();
				}
				sleep(0.5);
			}
			if($success === false){
				$error_det = "Func DeleteNbixLesson ". $queryDeleteLesson." ; ".implode(";",$arRes);
				include('error.php');
				die();
			}
		}
	}
	public static function AddNbixLessonTeachers($token,$lesson_code,$teacher_logins){
		global $token;
		$attempts = 3;
		$success = false;
		for ($i = 1; $i <= $attempts; $i++) {
			if(!empty($teacher_logins)){
				$teachers_string = "[";
				if(is_array($teacher_logins)){
					foreach ($teacher_logins as $user){
						$teachers_string = $teachers_string."{Login:'".$user."'},";
					}
				}else{
					$teachers_string = $teachers_string."{Login:'".$teacher_logins."'},";
				}
				$teachers_string = $teachers_string."]";
			}
			$query=APP_ADDRES."/EducationHome/AddLessonEditors?Token=".$token."&LessonCode=".$lesson_code."&EditorsToAdd=".$teachers_string;
			//echo $query;
			$arRes = file_get_contents($query);
			$arRes = json_decode($arRes, true);

			if($arRes["Success"] !== false) {
				$success = true;
				break;
			}else{
				$token = self::SetNwToken();
			}
			sleep(0.5);
		}
		if($success === false){
			$error_det = "Func AddNbixLessonTeachers ". $query;
			include('error.php');
			die();
		}
	}
	public static function DeleteNbixLessonTeachers($token,$lesson_code,$teacher_logins){
		global $token;
		$attempts = 3;
		$success = false;
		for ($i = 1; $i <= $attempts; $i++) {
			if(!empty($teacher_logins)){
				$teachers_string = "[";
				if(is_array($teacher_logins)){
					foreach ($teacher_logins as $user){
						$teachers_string = $teachers_string."{Login:'".$user."'},";
					}
				}else{
					$teachers_string = $teachers_string."{Login:'".$teacher_logins."'},";
				}
				$teachers_string = $teachers_string."]";
			}
			$query=APP_ADDRES."/EducationHome/RemoveLessonEditors?Token=".$token."&LessonCode=".$lesson_code."&EditorsToRemove=".$teachers_string;

			$arRes = file_get_contents($query);
			$arRes = json_decode($arRes, true);

			if($arRes["Success"] !== false) {
				$success = true;
				break;
			}else{
				$token = self::SetNwToken();
			}
			sleep(0.5);
		}
		if($success === false){
			$error_det = "Func DeleteNbixLessonTeachers ". $query;
			include('error.php');
			die();
		}
	}
	public static function deleteStudentsStat($token,$lessons, $users){
		global $token;
		$queryUrl = APP_ADDRES.'/EducationHome/DeleteUsersStatistics';

		if(!empty($users)){
			$users_string = "[";
			if(is_array($users)){
				foreach ($users as $user){
					$users_string = $users_string."{Email:'".$user."'},";
				}
			}else{
				$users_string = $users_string."{Email:'".$users."'},";
			}
			$users_string = $users_string."]";
		}

		if(!empty($lessons)){
			$lesson_string = "[";
			if(is_array($lessons)){
				foreach ($lessons as $lesson){
					$lesson_string = $lesson_string."{Code:'".$lesson."'},";
				}
			}else{
				$lesson_string = $lesson_string."{Code:'".$lessons."'},";
			}
			$lesson_string = $lesson_string."]";
		}

		if(!empty($users_string) && empty($lesson_string)){
			$queryData = http_build_query(array(
				"Token" => $token,
				"Users" => $users_string
			));
		}elseif(empty($users_string) && !empty($lesson_string)){
			$queryData = http_build_query(array(
				"Token" => $token,
				"Lessons" => $lesson_string,
			));
		}elseif (!empty($users_string) && !empty($lesson_string)){
			$queryData = http_build_query(array(
				"Token" => $token,
				"Lessons" => $lesson_string,
				"Users" => $users_string
			));
		}

		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_POST => 1,
			CURLOPT_HEADER => 0,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $queryUrl,
			CURLOPT_POSTFIELDS => $queryData,
		));

		$result = json_decode(curl_exec($curl), true);
		curl_close($curl);

		if($result["Success"] !== true){
			$token = self::SetNwToken();
			$new_result =  self::deleteStudentsStat($token,$lessons, $users);
		}
	}
	public static function getStudentsStat($token,$lessons, $users){
		$queryUrl = APP_ADDRES.'/EducationHome/GetStatistics';

		if(!empty($users)){
			$users_string = "[";
			if(is_array($users)){
				foreach ($users as $user){
					$users_string = $users_string."{Email:'".$user."'},";
				}
			}else{
				$users_string = $users_string."{Email:'".$users."'},";
			}
			$users_string = $users_string."]";
		}

		if(!empty($lessons)){
			$lesson_string = "[";
			if(is_array($lessons)){
				foreach ($lessons as $lesson){
					$lesson_string = $lesson_string."{Code:'".$lesson."'},";
				}
			}else{
				$lesson_string = $lesson_string."{Code:'".$lessons."'},";
			}
			$lesson_string = $lesson_string."]";
		}

		if(!empty($users_string) && empty($lesson_string)){
			$queryData = http_build_query(array(
				"Token" => $token,
				"Users" => $users_string
			));
		}elseif(empty($users_string) && !empty($lesson_string)){
			$queryData = http_build_query(array(
				"Token" => $token,
				"Lessons" => $lesson_string,
			));
		}elseif (!empty($users_string) && !empty($lesson_string)){
			$queryData = http_build_query(array(
				"Token" => $token,
				"Lessons" => $lesson_string,
				"Users" => $users_string
			));
		}
		
		$today = date("y_m_d")."_"; 
		$file = new Bitrix\Main\IO\File($_SERVER["DOCUMENT_ROOT"]."/bitrix24_applications/nbicsapp/logs/".$today."new_stats.txt");
		if(!$file->isExists()){$file->putContents("begin\n", Bitrix\Main\IO\File::APPEND);}
	
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_POST => 1,
			CURLOPT_HEADER => 0,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $queryUrl,
			CURLOPT_POSTFIELDS => $queryData,
		));

		$result = json_decode(curl_exec($curl), true);
		curl_close($curl);
		if($file->isExists()){
			$file->putContents("!".serialize($result)."!\n", Bitrix\Main\IO\File::APPEND); 
		}
		return $result;
	}
	public static function getSimpleStat($token,$lessons, $users,$get_tries='true'){
		$queryUrl = APP_ADDRES.'/EducationHome/GetStatisticsAggregatedByLesson';

		if(!empty($users)){
			$users_string = "[";
			if(is_array($users)){
				foreach ($users as $user){
					$users_string = $users_string."{Email:'".$user."'},";
				}
			}else{
				$users_string = $users_string."{Email:'".$users."'},";
			}
			$users_string = $users_string."]";
		}

		if(!empty($lessons)){
			$lesson_string = "[";
			if(is_array($lessons)){
				foreach ($lessons as $lesson){
					$lesson_string = $lesson_string."{Code:'".$lesson."'},";
				}
			}else{
				$lesson_string = $lesson_string."{Code:'".$lessons."'},";
			}
			$lesson_string = $lesson_string."]";
		}

		if(!empty($users_string) && empty($lesson_string)){
			$queryData = http_build_query(array(
				"Token" => $token,
				"Users" => $users_string,
				"GetOnlyFinalResult" => $get_tries
			));
		}elseif(empty($users_string) && !empty($lesson_string)){
			$queryData = http_build_query(array(
				"Token" => $token,
				"Lessons" => $lesson_string,
			));
		}elseif (!empty($users_string) && !empty($lesson_string)){
			$queryData = http_build_query(array(
				"Token" => $token,
				"Lessons" => $lesson_string,
				"Users" => $users_string,
				"GetOnlyFinalResult" => $get_tries
			));
		}
		
		$today = date("y_m_d")."_";
		$file = new Bitrix\Main\IO\File($_SERVER["DOCUMENT_ROOT"]."/bitrix24_applications/nbicsapp/logs/".$today."simple_stats.txt");
		if(!$file->isExists()){$file->putContents("begin\n", Bitrix\Main\IO\File::APPEND);}
		
		if($file->isExists()){
			$file->putContents("!".$queryData."!\n", Bitrix\Main\IO\File::APPEND); 
		}
		
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_POST => 1,
			CURLOPT_HEADER => 0,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $queryUrl,
			CURLOPT_POSTFIELDS => $queryData,
		));

		$result = json_decode(curl_exec($curl), true);
		
		curl_close($curl);
		
		return $result;
	}
	public static function UpdateDBUser($portal_id,$user_db_id){
		$entity_data_class = GetEntityDC(USERS_HL_BLOCK_ID);
		$result = $entity_data_class::update($user_db_id, [
			"UF_B24_ID" => $portal_id
		]);
	}
	public static function UpdateDBUserInfo($arr_to_upd,$user_db_id){
		if(!empty($arr_to_upd)&&intval($user_db_id)>0)
		{
			$entity_data_class = GetEntityDC(USERS_HL_BLOCK_ID);
			$result = $entity_data_class::update($user_db_id, $arr_to_upd);
		}
	}

	public static function getAnalysis($token,$lessons, $users){
		$res = [];
		$rf =  getStudentsStat($token,$lessons, $users);
	
		$aw = array();
		foreach ($rf["Data"] as $h){
			$aw[$h["Configuration"]["Code"]][$h["LessonTryGuid"]][] = array(
				"Success" => $h["Phase"]["Success"],
				"WaitForManualCheck" => $h["Phase"]["WaitForManualCheck"],
				"StudentSuccessTestCount" =>  $h["Phase"]["StudentSuccessTestCount"],
				"LessonPhasesCount" => $h["Phase"]["LessonPhasesCount"],
				"TotalTestCount" =>  $h["Phase"]["TotalTestCount"],
				"Email" => $h["Student"]["Email"],
			);
		}

		foreach ($aw as $lesson_code => $LessonAttemptsIds){
			$good_attempts = 0;
			$arrPercent = array();
			$lesson_passed = false;
			$wait=0;

			foreach ($LessonAttemptsIds as $LessonTryid){
				$passed_phases = 0;
				$total_lesson_points = 0;
				$score = 0;

				foreach ($LessonTryid as $phase){
					$score = $score + floatval($phase["StudentSuccessTestCount"]);
					$total_lesson_points = $total_lesson_points + (int)$phase["TotalTestCount"];
					if($phase["Success"]) $passed_phases++;
					if($phase["WaitForManualCheck"]) $wait=1;
				}
				if($passed_phases == $LessonTryid[0]["LessonPhasesCount"]){
					$lesson_passed = true;
					$good_attempts++;
					$percent = ($score !=0 && $total_lesson_points != 0)? ($score/$total_lesson_points)*100 : 0;
					$arrPercent[] = $percent;
				}
			}

			$res[$lesson_code] = array(
				"RESULT" => $lesson_passed,
				"ATTEMPTS" => count($aw[$lesson_code]),
				"WAIT" => $wait
			);

			if($lesson_passed == true){
				$res[$lesson_code]["PERCENT"] = max($arrPercent);
				$res[$lesson_code]["GOOD_ATTEMPTS"] = $good_attempts;
			}
		}
		return $res;

	}
	public static function getAnalysisDB($token,$lessons, $users,$userid,$less_ids,$all=0){
			if(CModule::IncludeModule('highloadblock')) {
				$res = [];
				$entity_data_class_13 = GetEntityDC(13);
				$rsData = $entity_data_class_13::getList(array(
								'select' => array('*'),
								'filter' => array('UF_USER'=>$userid),
								'order' => array('ID'=>'DESC') 
							));
				while ($el = $rsData->fetch()) {
					$res[$el["UF_LESSON"]] = array(
						"RESULT" => $el["UF_RESULT"],
						"ATTEMPTS" => $el["UF_ATTEMPTS"],
						"WAIT" => $el["UF_WAIT"]
					);			
				}
				if(!empty($res))return $res;
				if($all==0)	
				{
					$arLessons = getAllDBLessons(PORTAL);
					if(!empty($arLessons)){
						foreach ($arLessons as $k=>$itm_lesson){
							if(!isset($less_ids[$itm_lesson['CODE']]))
								$less_ids[$itm_lesson['CODE']]=$k;
						}
					}
				}	
				$rf = getSimpleStat($token,$lessons,$users,'false');
				foreach ($rf["Data"] as $h){
					$student = $userid;
					foreach($h["Lessons"] as $k1=>$v1)
					{
						$lesson =$less_ids[$v1["Code"]];
						$success = $v1["Success"];
						$wait_for_check = $v1["SomeTryWaitForManualCheck"];
						$tries_count = count($v1["Tries"]);
						$rsData = $entity_data_class_13::getList(array(
								'select' => array('*'),
								'filter' => array('UF_USER'=>$student, 'UF_LESSON'=>$lesson),
								'order' => array('ID'=>'DESC') 
							));
						if ($el = $rsData->fetch()) {
							if($el["UF_ATTEMPTS"]!=$tries_count||$el["UF_WAIT"]!=$wait_for_check||$el["UF_RESULT"]!=$success)
							{
								 $result = $entity_data_class_13::update($el["ID"], [
									"UF_ATTEMPTS" => $tries_count,
									"UF_WAIT" => $wait_for_check,
									"UF_RESULT" => $success,
									"UF_UPDATE_TIME" => new \Bitrix\Main\Type\DateTime()
								]);
							}
						}
						else {
								$result = $entity_data_class_13::add(array(
									'UF_USER'=>$student, 
									'UF_LESSON'=>$lesson,
									"UF_ATTEMPTS" => $tries_count,
									"UF_WAIT" => $wait_for_check,
									"UF_RESULT" => $success
								));							
							}
						$res[$lesson] = array(
							"RESULT" =>  $success,
							"ATTEMPTS" => $tries_count,
							"WAIT" => $wait_for_check
						
						);			
					}
				}
				return $res;
			}
	}
	public static function getAnalysis2($token,$lessons, $users){
		$rf =  self::getStudentsStat($token,$lessons, $users);
		$res = [];
	
		
		$aw = array();
		foreach ($rf["Data"] as $h){
		
			$aw[$h["Student"]["Email"]][$h["LessonTryGuid"]][] = array(
				"Success" => $h["Phase"]["Success"],
				"StudentSuccessTestCount" =>  $h["Phase"]["StudentSuccessTestCount"],
				"LessonPhasesCount" => $h["Phase"]["LessonPhasesCount"],
				"TotalTestCount" =>  $h["Phase"]["TotalTestCount"],
				"Lesson" => $h["Configuration"]["Code"]
			);
		}
	
		foreach ($aw as $user => $LessonAttemptsIds){
			$good_attempts = 0;
			$arrPercent = array();
			$lesson_passed = false;

			foreach ($LessonAttemptsIds as $LessonTryid){
				$passed_phases = 0;
				$total_lesson_points = 0;
				$score = 0;

				foreach ($LessonTryid as $phase){
					$score = $score + floatval($phase["StudentSuccessTestCount"]);
					$total_lesson_points = $total_lesson_points + (int)$phase["TotalTestCount"];
					if($phase["Success"]) $passed_phases++;
				}
				if($passed_phases == $LessonTryid[0]["LessonPhasesCount"]){
					$lesson_passed = true;
					$good_attempts++;
					$percent = ($score !=0 && $total_lesson_points != 0)? ($score/$total_lesson_points)*100 : 0;
					$arrPercent[] = $percent;
				}
			}
			$res[$user] = array(
				"RESULT" => $lesson_passed,
				"ATTEMPTS" => count($aw[$user])
			);

			if($lesson_passed == true){
				$res[$user]["PERCENT"] = max($arrPercent);
				$res[$user]["GOOD_ATTEMPTS"] = $good_attempts;
			}
		}

		return $res;
	}
	public static function find_in_arr_by_ind($x,$arr,$field){
		$res=false;
		foreach ($arr as $k=>$v)
		{
			if($v[$field]==$x)
			{
				$res=$k;
				break;
			}
		}
		return $res;
	}
	public static function getAnalysis2_simple($token,$lessons, $users,$t_les,$arr_students=Array()){
		$rf =  self::getSimpleStat($token,$lessons, $users);
		$res = Array();
		
		  
	   foreach ($rf["Data"] as $h){
			$passed_count = 0;
			$wait_count = 0;
			$unpassed_count = 0;
			foreach ($h["Lessons"] as $v){
				$ind=find_in_arr_by_ind($v["Code"],$t_les,"CODE");
			
				if($ind!==FALSE)
				{
					if(in_array($ind,$arr_students[$h["Student"]["Email"]]["LESSONS"] ))
					{
						if(intval($v["Success"])==1)$passed_count++;
						elseif(intval($v["SomeTryWaitForManualCheck"])==1)$wait_count++;  
						else $unpassed_count++;
					}
				}
			}
			$res[$h["Student"]["Email"]]["Passed"]=$passed_count;
			$res[$h["Student"]["Email"]]["Unpassed"]=$unpassed_count;
			$res[$h["Student"]["Email"]]["Wait"]=$wait_count;
			$res[$h["Student"]["Email"]]["Lessons"]=$h["Lessons"];
		}
		return $res;
	}
	public static function getAnalysis2_simple_les($token,$lessons, $users,$t_les=Array(),$arr_students=Array()){
		$rf =  self::getSimpleStat($token,$lessons, $users);
		$res = Array();
		foreach ($rf["Data"] as $h){
			
			foreach ($h["Lessons"] as $v){
				$pass=0;
				$wait=0;
				$unpassed=0;
				$code=0;
				if(intval($v["Success"])==1){$pass++;$code=1;}
					elseif(intval($v["SomeTryWaitForManualCheck"])==1){$wait++;$code=3;}
					else {$unpassed++;$code=2;}
				if(!isset($res[$v["Code"]])){$res[$v["Code"]]=Array('Name'=>$v['Name'],'Passed'=>0,'Unpassed'=>0,'Wait'=>0,"Students"=>[]);}
				$res[$v["Code"]]["Students"][]=Array("Login"=>$h['Student']['Email'],"Res"=>$code,"Time"=>$v["SuccessTime"]);
				$res[$v["Code"]]["Passed"]+=$pass;
				$res[$v["Code"]]["Wait"]+=$wait;
				$res[$v["Code"]]["Unpassed"]+=$unpassed;	
			}
		}
		return $res;
	}
	public static function getDBUsers($portal_domain){
		$users = [];
		if(CModule::IncludeModule('highloadblock')) {
			$entity_data_class = GetEntityDC(USERS_HL_BLOCK_ID);
			$rsData = $entity_data_class::getList(array(
				'filter' => array('UF_DOMAIN_B24' => $portal_domain)
			));

			while ($el = $rsData->fetch()) {
				$users[] = array(
					"ID" => $el["ID"],
					"IS_ADMIN" => $el["UF_ADMIN"],
					"IS_TEACHER" => $el["UF_TEACHER"],
					"LOGIN" => $el["UF_LOGIN"],
					"NAME" => $el["UF_NAME"],
					"LAST_NAME" => $el["UF_LAST_NAME"],
					"PASSWORD_HASH" => $el["UF_PASSWORD_HASH"]
				);
			}
			return $users;
		}
	}
	public static function GetStudentsNamebyId($user_id){
		if(CModule::IncludeModule('highloadblock')) {
			$entity_data_class = GetEntityDC(USERS_HL_BLOCK_ID);
			$rsData = $entity_data_class::getList(array(
				'select' => array("UF_NAME", "UF_LAST_NAME"),
				'filter' => array('ID' => $user_id)
			));
			while ($el = $rsData->fetch()) {
				$name = $el["UF_NAME"] . " " . $el["UF_LAST_NAME"];
			}
			return $name;
		}
	}
	public static function GetStudentsOfLesson($lesson_id){
		$user_info = [];
		if(CModule::IncludeModule('highloadblock')) {
			$entity_data_class = GetEntityDC(USERS_HL_BLOCK_ID);
			$rsData = $entity_data_class::getList(array(
				'filter' => array('UF_LESSONS' => $lesson_id)
			));
			while ($el = $rsData->fetch()) {
				$user_info[] = array(
					"ID" => $el["ID"],
					"LOGIN" => $el["UF_LOGIN"],
					"NAME" => $el["UF_NAME"],
					"LAST_NAME" => $el["UF_LAST_NAME"],
				);
			}
			return $user_info;
		}
	}
	public static function GetStudentsOfProg($lesson_id){
		if(CModule::IncludeModule('highloadblock')) {
			$entity_data_class = GetEntityDC(USERS_HL_BLOCK_ID);
			$rsData = $entity_data_class::getList(array(
				'filter' => array('UF_PROGS' => $lesson_id)
			));
			$user_info = [];
			while ($el = $rsData->fetch()) {
				$user_info[$el["ID"]] = array(
					"ID" => $el["ID"],
					"LOGIN" => $el["UF_LOGIN"],
					"NAME" => $el["UF_NAME"],
					"LAST_NAME" => $el["UF_LAST_NAME"],
					"PROGS" =>  $el["UF_PROGS"]
				);
			}
			return $user_info;
		}
	}
	public static function getStatProg($progs,$students){
			$stat=[];
			$entity_data_class = GetEntityDC(STAT_PROGS);
			$rsData = $entity_data_class::getList(array(
				'select' => array('*'),
				'filter' => array('UF_PROG' => $progs,'UF_USER'=>$students)
			));
			while ($el = $rsData->fetch()) {
				if(!isset($stat[$el["UF_PROG"]]))
					$stat[$el["UF_PROG"]]=array();
				$stat[$el["UF_PROG"]][$el["UF_USER"]] = $el["UF_STAT_DET"];
			}
			return $stat;
	}
	public static function _remove_empty_internal($value) {
	  return !($value==""||is_array($value));
	}
	public static function remove_empty($array) {
	  return array_filter($array, '_remove_empty_internal');
	}
	public static function GetStudentsOfLessons($lessons,$flag_prog=false,$prog=[]){
		$user_info = [];
		if(CModule::IncludeModule('highloadblock')) {
			$entity_data_class = GetEntityDC(SETTINGS_HL_BLOCK_ID);
			$rsData = $entity_data_class::getList(array(
					"select" => array("UF_LESSONS_FOR_ALL"),
					"filter" => array("UF_DOMAIN_B24" => PORTAL)
				));
			$for_all=0;
			if($arData = $rsData->fetch())
			{
				$for_all=$arData["UF_LESSONS_FOR_ALL"];
			}
			
			if($for_all==0){ 
				$filter=array('UF_LESSONS' => $lessons);
				if($flag_prog&&!empty($prog))
				{
					$filter=array('LOGIC' => 'OR',
							array('UF_PROGS' => $prog),
							array('UF_LESSONS' => $lessons)
						);
				}
			} 
			else
			{
				$filter=array('LOGIC' => 'OR',
							array('UF_DOMAIN_B24' => PORTAL),
							array('UF_LESSONS' => $lessons)
						);
				if($flag_prog&&!empty($prog))
				{
					$filter=array('LOGIC' => 'OR',
							//array('UF_DOMAIN_B24' => PORTAL),
							array('UF_PROGS' => $prog),
							array('UF_LESSONS' => $lessons)
						);
				}
				//$filter=array('UF_DOMAIN_B24' => /*(PORTAL=="779a62798b30d0db967806b0c26d3a8f")?"efe65e86d23086336f37b6c73a47745e":*/PORTAL);
			}
			$entity_data_class = GetEntityDC(USERS_HL_BLOCK_ID);
			$rsData = $entity_data_class::getList(array(
				'filter' => $filter
			));
			$prog_arr=[];
			while ($el = $rsData->fetch()) {
				if($flag_prog)
				{
					$arr_prog=$el["UF_PROGS"];
					foreach ($el["UF_PROGS"] as $v)
					{
						if(!isset($prog_arr[$v]))
						{
							$prog_arr[$v] = getDBLessonsByProgsSimple($v);
						}
					}
				}
				$user_info[$el["UF_LOGIN"]] = array(
					"ID" => $el["ID"],
					"LOGIN" => $el["UF_LOGIN"],
					"NAME" => $el["UF_NAME"],
					"LAST_NAME" => $el["UF_LAST_NAME"],
					"LESSONS" => remove_empty($el["UF_LESSONS"])
				);
				if($flag_prog)
				{
					foreach ($el["UF_PROGS"] as $v)
					{
						foreach($prog_arr[$v] as $les_id)
							$user_info[$el["UF_LOGIN"]]["LESSONS"][]=$les_id;
					}
				}
			}
			
			return $user_info;
		}
	}
	public static function GetLessonsInfo($ids, $teachers_id){
		$lessons=[];
		if(CModule::IncludeModule('highloadblock')) {
			$entity_data_class = GetEntityDC(LESSONS_HL_BLOCK_ID);
			$rsData = $entity_data_class::getList(array(
				'select' => array('*'),
				'order' => array('UF_NAME' => 'ASC'),
				'filter' => array('ID' => $ids,'!UF_CREATOR' => $teachers_id)
			));
			while ($el = $rsData->fetch()) {
				$lessons[$el["ID"]] = array(
					"ID" => $el["ID"],
					"DESCRIPTION" => $el["UF_DESCRIPTION"],
					"NAME" => $el["UF_NAME"],
					"CODE" => $el["UF_CODE"],
				);
			}
			return $lessons;
		}
	}
	public static function GetLessonsCodes($ids){
		$lessons=[];
		if(CModule::IncludeModule('highloadblock')) {
			$entity_data_class = GetEntityDC(LESSONS_HL_BLOCK_ID);
			$rsData = $entity_data_class::getList(array(
				'select' => array('UF_CODE','ID'),
				'filter' => array('ID' => $ids)
			));
			while ($el = $rsData->fetch()) {
				$lessons[$el["ID"]] = $el["UF_CODE"];
			}
			return $lessons;
		}
	}
	public static function GetTeachersLessons($teachers_id){
		$lessons=[];
		if(CModule::IncludeModule('highloadblock')) {
			$entity_data_class = GetEntityDC(LESSONS_HL_BLOCK_ID);
			$filter=array('LOGIC' => 'OR',
							array('UF_CREATOR' => $teachers_id),
							array('UF_TEACHERS' => $teachers_id));			
			$rsData = $entity_data_class::getList(array(
				'select' => array('*'),
				'order' => array('UF_NAME' => 'ASC'),
				'filter' => $filter
			));
			while ($el = $rsData->fetch()) {
				$lessons[$el["ID"]] = array(
					"ID" => $el["ID"],
					"DESCRIPTION" => $el["UF_DESCRIPTION"],
					"NAME" => $el["UF_NAME"],
					"CODE" => $el["UF_CODE"],
				);
			}
			
			return $lessons;
		}
	}
	public static function GetTeachersLessonsByIds($ids){
		$lessons=[];
		if(CModule::IncludeModule('highloadblock')) {
			$entity_data_class = GetEntityDC(LESSONS_HL_BLOCK_ID);
			$filter=array('ID' => $ids);			
			$rsData = $entity_data_class::getList(array(
				'select' => array('*'),
				'order' => array('UF_NAME' => 'ASC'),
				'filter' => $filter
			));
			while ($el = $rsData->fetch()) {
				$lessons[$el["ID"]] = array(
					"ID" => $el["ID"],
					"DESCRIPTION" => $el["UF_DESCRIPTION"],
					"NAME" => $el["UF_NAME"],
					"CODE" => $el["UF_CODE"],
				);
			}
			
			return $lessons;
		}
	}
	public static function GetTeachersProgs($teachers_id){
		$lessons=[];
		if(CModule::IncludeModule('highloadblock')) {
			$entity_data_class = GetEntityDC(PROGS_HL_BLOCK_ID);
			$arFilter = array(
				"LOGIC" => "OR",
				array('UF_CREATOR' => $teachers_id),
				array('UF_ADMIN' => $teachers_id),
				array('UF_TEACHERS'=>$teachers_id)
			);
			$rsData = $entity_data_class::getList(array(
				'select' => array('*'),
				'order' => array('UF_NAME' => 'ASC'),
				'filter' => $arFilter
			));
			while ($el = $rsData->fetch()) {
				$lessons[] = array(
					"ID" => $el["ID"],
					"DESCRIPTION" => $el["UF_DESCRIPTION"],
					"NAME" => $el["UF_NAME"]
				);
			}
			return $lessons;
		}
	}
	public static function getDBLessons($arUser){
		$lessons=[];
		if(CModule::IncludeModule('highloadblock')) {
			$entity_data_class = GetEntityDC(LESSONS_HL_BLOCK_ID);
			$rsData = $entity_data_class::getList(array(
				'select' => array('*'),
				'order' => array("UF_NAME" => "ASC"),
				'filter' => array('ID' => $arUser["LESSONS"])
			));
			while ($el = $rsData->fetch()) {
				$lessons[$el["ID"]] = array(
					"NAME" => $el["UF_NAME"],
					"CODE" => $el["UF_CODE"],
					"CREATOR" => $el["UF_CREATOR"],
					"DESCRIPTION" => $el["UF_DESCRIPTION"],
					"ATTEMP" => $el["UF_ATTEMP"]
				);
			}
			return $lessons;
		}
	}
	public static function getAllDBLessons($domain){
		$lessons=[];
		if(CModule::IncludeModule('highloadblock')) {
			$entity_data_class = GetEntityDC(LESSONS_HL_BLOCK_ID);
			$rsData = $entity_data_class::getList(array(
				'select' => array('*'),
				'order' => array("UF_NAME" => "ASC"),
				'filter' => array('UF_DOMAIN_B24' => $domain)
			));
			while ($el = $rsData->fetch()) {
				$lessons[$el["ID"]] = array(
					"NAME" => $el["UF_NAME"],
					"CODE" => $el["UF_CODE"],
					"CREATOR" => $el["UF_CREATOR"],
					"DESCRIPTION" => $el["UF_DESCRIPTION"],
					"ATTEMP" => $el["UF_ATTEMP"]
				);
			}
			return $lessons;
		}
	}
	public static function getDBProgs($arUser){
		$lessons=[];
		if(CModule::IncludeModule('highloadblock')) {
			$entity_data_class = GetEntityDC(PROGS_HL_BLOCK_ID);
			$rsData = $entity_data_class::getList(array(
				'select' => array('*'),
				'filter' => array('ID' => $arUser["PROGS"])
			));
			while ($el = $rsData->fetch()) {
				$lessons[] = array(
					"ID" => $el["ID"],
					"NAME" => $el["UF_NAME"],
					"CODE" => $el["UF_CODE"],
					"CREATOR" => $el["UF_CREATOR"],
					"DESCRIPTION" => $el["UF_DESCRIPTION"],
					"LESSONS" => $el["UF_CODES"],
					"ATTEMPTS" => $el["UF_COUNT"],
					"ORDER_NCHECK" => $el["UF_ORDER_NCHECK"]
				);
			}
			return $lessons;
		}
	}
		
	public static function getDBLessonsByProgs($arProgs){
		$prog_ids = array_column($arProgs,"ID");
		$lessons=[];
		if(CModule::IncludeModule('highloadblock')) {
			$entity_data_class = GetEntityDC(PROGS_HL_BLOCK_ID);
			$rsData = $entity_data_class::getList(array(
				'select' => array('UF_CODES'),
				'filter' => array('ID' => $prog_ids)
			));
			while ($el = $rsData->fetch()) {
				foreach($el["UF_CODES"] as $v)
				{
					if(!in_array($v,$lessons))
						$lessons[] = $v;
				}
			}
			return $lessons;
		}
	}
	public static function getDBLessonsByProgsSimple($prog_id){
		$lessons=[];
		if(CModule::IncludeModule('highloadblock')) {
			$entity_data_class = GetEntityDC(PROGS_HL_BLOCK_ID);
			$rsData = $entity_data_class::getList(array(
				'select' => array('UF_CODES'),
				'filter' => array('ID' =>$prog_id)
			));
			while ($el = $rsData->fetch()) {
				foreach($el["UF_CODES"] as $v)
				{
					if(!in_array($v,$lessons))
						$lessons[] = $v;
				}
			}
			return $lessons;
		}
	}
	public static function getQuantityPortalLessons($domain){
		$lessons = [];
		if(CModule::IncludeModule('highloadblock')) {
			$entity_data_class = GetEntityDC(LESSONS_HL_BLOCK_ID);

			$rsData = $entity_data_class::getList(array(
				'select' => array('ID'),
				'filter' => array('UF_DOMAIN_B24' => $domain)
			));
			while ($el = $rsData->fetch()) {
				$lessons[] = $el["ID"];
			}
			return count($lessons);
		}
	}
	public static function checkExistDBUser($arUser,$domain){
		$user_info=[];
		if(CModule::IncludeModule('highloadblock')) {
			$entity_data_class = GetEntityDC(USERS_HL_BLOCK_ID);
			$rsData = $entity_data_class::getList(array(
				'filter' => array('UF_LOGIN' => $arUser["LOGIN"], 'UF_DOMAIN_B24' => $domain)
			));

			while ($el = $rsData->fetch()) {
				$user_info = array(
					"ID" => $el["ID"],
					"B24_ID" => $el["UF_B24_ID"],
					"TEACHER" => $el["UF_TEACHER"],
					"ADMIN" => $el["UF_ADMIN"],
					"LESSONS" => $el["UF_LESSONS"],
					"PROGS" => $el["UF_PROGS"],
					"NAME" => $el["UF_NAME"],
					"LAST_NAME" => $el["UF_LAST_NAME"],
					"UF_PODPIS" =>  $el["UF_PODPIS"],
					"UF_USER_TPL_CERT" => $el["UF_USER_TPL_CERT"]
				);
			}
			return $user_info;
		}
	}
	public static function checkExistDBUserHuman($arUser,$domain_human){
		$user_info=[];
		if(CModule::IncludeModule('highloadblock')) {
			$entity_data_class = GetEntityDC(USERS_HL_BLOCK_ID);
			$rsData = $entity_data_class::getList(array(
				'filter' => array('UF_LOGIN' => $arUser["LOGIN"], 'UF_DOMAIN_B24_HUMAN' => $domain_human)
			));
			if ($el = $rsData->fetch()) {
				$user_info = array(
					"ID" => $el["ID"],
					"B24_ID" => $el["UF_B24_ID"],
					"TEACHER" => $el["UF_TEACHER"],
					"ADMIN" => $el["UF_ADMIN"],
					"LESSONS" => $el["UF_LESSONS"],
				);
			}
			return $user_info;
		} 
	}
	public static function addDBUser($arUser,$domain, $domain_human){
		if(CModule::IncludeModule('highloadblock')) {
			$entity_data_class = GetEntityDC(USERS_HL_BLOCK_ID);
			
			 $rsData = $entity_data_class::getList(array(
				'select' => array("ID"),
				'filter' => array('UF_LOGIN' => $arUser["LOGIN"],'UF_DOMAIN_B24' => $domain)
			));

			$id = $rsData->fetch()["ID"];
			
			if(empty($id)){
				$result = $entity_data_class::add(array(
					'UF_NAME'         => $arUser["NAME"],
					'UF_LAST_NAME'         => $arUser["LAST_NAME"],
					'UF_LOGIN'         => $arUser["LOGIN"],
					'UF_DOMAIN_B24'   => $domain,
					'UF_B24_ID'   => $arUser["PORTAL_ID"],
					'UF_DOMAIN_B24_HUMAN' => $domain_human,
					'UF_PASSWORD_HASH' => $arUser["PASSWORD"],
					'UF_ADMIN' => false,
					'UF_TEACHER' => false,
					'UF_DATETIME' => new \Bitrix\Main\Type\DateTime()
				));
			}
			else {
				$result = $entity_data_class::update($id, array(
					'UF_PASSWORD_HASH' => $arUser["PASSWORD"]
				));
			}
		}
	}
	public static function checkExistDBUserOnInstall($arUser,$domain,$domain_human){
		CModule::IncludeModule("highloadblock");
		$entity_data_class = GetEntityDC(USERS_HL_BLOCK_ID);
		$rsData = $entity_data_class::getList(array(
			'select' => array("ID"),
			'filter' => array('UF_LOGIN' => $arUser["LOGIN"],'UF_DOMAIN_B24' => $domain)
		));

		$id = $rsData->fetch()["ID"];

		if(empty($id)){
			$result = $entity_data_class::add(array(
				'UF_NAME'         => $arUser["NAME"],
				'UF_LAST_NAME'         => $arUser["LAST_NAME"],
				'UF_LOGIN'         => $arUser["LOGIN"],
				'UF_B24_ID'   => $arUser["PORTAL_ID"],
				'UF_DOMAIN_B24'   => $domain,
				'UF_ADMIN' => true,
				'UF_TEACHER' => true,
				'UF_DATETIME' => new \Bitrix\Main\Type\DateTime(),
				'UF_DOMAIN_B24_HUMAN' => $domain_human
			));
		}
		$appInfo = getAppInfo(PORTAL_URL,$_REQUEST['AUTH_ID']);
		$box = 0;
		if($appInfo["CODE"]!=="westpower.uroki_i_testy")
			$box = 1;
		$entity_data_class = GetEntityDC(SETTINGS_HL_BLOCK_ID);
		$rsData = $entity_data_class::getList(array(
				"select" => array("*"),
				"filter" => array("UF_DOMAIN_B24" => PORTAL,"UF_BOX"=>$box)
			));
		$data = $rsData->fetch();
		
		$id_set = $rsData->fetch()["ID"];

		if(empty($id_set)){
			$result = $entity_data_class::add(array(
			   "UF_DOMAIN_B24"=>$_REQUEST["member_id"],
			   "UF_BOX"=>$box
			));
		}
	}
	public static function prepareLessonName($name,$count){
		if(PORTAL_URL != "portal.coxo.ru") {
			$length = strlen($name);
			if ($length > (int)$count) {
				$abbreviated_name = mb_substr($name, 0, (int)$count);
				$name = $abbreviated_name . "...";
			}
		}
		return $name;
	}
	public static function check_rights_edit($user_id,$lesson_id){
		$res = false;
		CModule::IncludeModule("highloadblock");
		$entity_data_class = GetEntityDC(LESSONS_HL_BLOCK_ID);
		$rsData = $entity_data_class::getList(array(
			'select' => array("ID"/*,"UF_TEACHERS","UF_CREATOR"*/),
			'filter' => array('ID' => $lesson_id, 
								array('LOGIC' => 'OR',
									array('UF_CREATOR' => $user_id),
									array('UF_TEACHERS' => $user_id),
								))
					));
		if($el = $rsData->fetch()){
		   $res = true;
		}
		return $res;
	}
	public static function check_rights_edit_prog($user_id,$prog_id){
		$res = false;
		CModule::IncludeModule("highloadblock");
		$entity_data_class = GetEntityDC(PROGS_HL_BLOCK_ID);
		$rsData = $entity_data_class::getList(array(
			'select' => array("ID"/*,"UF_TEACHERS","UF_CREATOR"*/),
			'filter' => array('ID' => $prog_id, 
								array('LOGIC' => 'OR',
									array('UF_CREATOR' => $user_id),
									array('UF_TEACHERS' => $user_id),
								))
					));
		if($el = $rsData->fetch()){
		   $res = true;
		}
		return $res;
	}
	public static function check_rights_edit_lescode($user_id,$lesson_code){
		$res = false;
		CModule::IncludeModule("highloadblock");
		$entity_data_class = GetEntityDC(LESSONS_HL_BLOCK_ID);
		$rsData = $entity_data_class::getList(array(
			'select' => array("ID"/*,"UF_TEACHERS","UF_CREATOR"*/),
			'filter' => array('UF_CODE' => $lesson_code, 
								array('LOGIC' => 'OR',
									array('UF_CREATOR' => $user_id),
									array('UF_TEACHERS' => $user_id),
								))
					));
		if($el = $rsData->fetch()){
		   $res = true;
		}
		return $res;
	}
	public static function checkForDeleteOldLessons($domain){
		$old_lessons_ids=[];
		CModule::IncludeModule("highloadblock");
		$entity_data_class = GetEntityDC(LESSONS_HL_BLOCK_ID);
		$rsData = $entity_data_class::getList(array(
			'select' => array("ID"),
			'filter' => array('UF_DOMAIN_B24' => $domain)
		));

		while($el = $rsData->fetch()){
			$old_lessons_ids[] = $el["ID"];
		}

		foreach ($old_lessons_ids as $id){
			$result = $entity_data_class::delete($id);
		}
	}
	public static function checkForDeleteOldUsers($domain){
		$old_users_ids=[];
		CModule::IncludeModule("highloadblock");
		$entity_data_class = GetEntityDC(USERS_HL_BLOCK_ID);
		$rsData = $entity_data_class::getList(array(
			'select' => array("ID"),
			'filter' => array('UF_DOMAIN_B24' => $domain)
		));

		while($el = $rsData->fetch()){
			$old_users_ids[] = $el["ID"];
		}

		foreach ($old_users_ids as $id){
			$result = $entity_data_class::delete($id);
		}
	}
	public static function CheckCurrentAdminUser($domain,$auth_id){
		$queryUrl = 'https://'.$domain.'/rest/user.admin.json';
		$queryData = http_build_query(array(
			"auth" => $auth_id
		));

		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_POST => 1,
			CURLOPT_HEADER => 0,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $queryUrl,
			CURLOPT_POSTFIELDS => $queryData,
		));

		$result = json_decode(curl_exec($curl), true);
		curl_close($curl);
		return $result['result'];
	}
	public static function GetLessonsCerts($lesson_id){
		$certs=[];
		if(CModule::IncludeModule('highloadblock')) {
			$entity_data_class = GetEntityDC(CERT_HL_BLOCK_ID);
			$filter=array('LOGIC' => 'OR',
							array('UF_LESSON' => $lesson_id));			
			$rsData = $entity_data_class::getList(array(
				'select' => array('*'),
				'filter' => $filter
			));
			while ($el = $rsData->fetch()) {
				if(empty( $certs[$el["UF_LESSON"]]))
					$certs[$el["UF_LESSON"]]=[];
				$certs[$el["UF_LESSON"]][$el["UF_UCHENIK"]] = $el["UF_CERT"];
			}
			return $certs;
		}
	}
	public static function GetProgsCerts($prog_id){
		$certs=[];
		if(CModule::IncludeModule('highloadblock')) {
			$entity_data_class = GetEntityDC(CERT_HL_BLOCK_ID);
			$filter=array('LOGIC' => 'OR',
							array('UF_PROG' => $prog_id));			
			$rsData = $entity_data_class::getList(array(
				'select' => array('*'),
				'filter' => $filter
			));
			while ($el = $rsData->fetch()) {
				if(empty( $certs[$el["UF_PROG"]]))
					$certs[$el["UF_PROG"]]=[];
				$certs[$el["UF_PROG"]][$el["UF_UCHENIK"]] = $el["UF_CERT"];
			}
			return $certs;
		}
	}
	public static function GetUserCerts($user_id, $type_flag="PROG"){
		$certs=[];
		if(CModule::IncludeModule('highloadblock')) {
			$entity_data_class = GetEntityDC(CERT_HL_BLOCK_ID);
			if($type_flag=="PROG")
				$filter=array('UF_UCHENIK' => $user_id,'!UF_PROG' => 0);	
			else {
				$filter=array('UF_UCHENIK' => $user_id,'!UF_LESSON' => 0);	
			}
			$rsData = $entity_data_class::getList(array(
				'select' => array('*'),
				'filter' => $filter
			));
			while ($el = $rsData->fetch()) {
				if($el["UF_CERT"]>0)
					$certs[$el["UF_".$type_flag]] = $el["UF_CERT"];
			}
			return $certs;
		}
	}
	public static function GetCurrentUser($request){
		$queryUrl = 'https://'.$request['DOMAIN'].'/rest/user.current.json';

		if(!empty($request['AUTH_ID'])) {
			$queryData = http_build_query(array(
				"auth" => $request['AUTH_ID']
			));

			$curl = curl_init();
			curl_setopt_array($curl, array(
				CURLOPT_SSL_VERIFYPEER => 0,
				CURLOPT_POST => 1,
				CURLOPT_HEADER => 0,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_URL => $queryUrl,
				CURLOPT_POSTFIELDS => $queryData,
			));

			$result = json_decode(curl_exec($curl), true);
			curl_close($curl);

			$user = [
				"PORTAL_ID" => $result['result']['ID'],
				"NAME" => $result['result']['NAME'],
				"LAST_NAME" => $result['result']['LAST_NAME'],
				"SECOND_NAME" => $result['result']['SECOND_NAME'],
				"LOGIN" => $request['member_id'] . "/" . $result['result']['ID'],
				"EMAIL" => $result['result']['EMAIL']
			];
			return $user;
		}else {
			if(empty($result))
				$result=[];
			$error_det = "Func GetCurrentUser; ".implode(";",$result)."<br>".serialize($request)."<br>";
			include('error.php');
			die();
		}

	}
	public static function getAppInfo($domain,$auth_id){
		$queryUrl = 'https://'.$domain.'/rest/app.info.json';
		$queryData = http_build_query(array(
			"auth" => $auth_id
		));

		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_POST => 1,
			CURLOPT_HEADER => 0,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $queryUrl,
			CURLOPT_POSTFIELDS => $queryData,
		));

		$result = json_decode(curl_exec($curl), true);
		curl_close($curl);
	
		return $result['result'];
	}
	public static function GetUserNameLastName($domain,$auth_id,$logins){
		$queryUrl = 'https://'.$domain.'/rest/user.get.json';
		$queryData = http_build_query(array(
			"auth" => $auth_id,
			"FILTER" => array("EMAIL" => $logins)
		));
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_POST => 1,
			CURLOPT_HEADER => 0,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $queryUrl,
			CURLOPT_POSTFIELDS => $queryData,
		));
		$result = json_decode(curl_exec($curl), true);
		curl_close($curl);

		foreach ($result["result"] as $rez){
			$arResult[$rez["EMAIL"]] = $rez["NAME"]." ".$rez["LAST_NAME"];
		}
		return $arResult;
	}
	public static function getUserNamePortal($usercode){
		CModule::IncludeModule("highloadblock");
		$entity_data_class = GetEntityDC(USERS_HL_BLOCK_ID);
		$rsData = $entity_data_class::getList(array(
			'select' => array("UF_NAME","UF_LAST_NAME"),
			'filter' => array('UF_LOGIN' => $usercode)
		));
		$data=false;
		$data = $rsData->fetch();
		return $data;
	}
	public static function count_less_portal($portal){
		$count = 0;
		if(CModule::IncludeModule('highloadblock')) {
			$entityDataClass = GetEntityDC(LESSONS_HL_BLOCK_ID);
			   
			$result = $entityDataClass::getList(array(

				'select' => array('CNT'),

				'filter' => array('UF_DOMAIN_B24'=>$portal),

				'runtime' => array(

					new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)'),

				),

			));

			if($arRow = $result->Fetch())
				$count = $arRow['CNT'];
		}
		return $count;
	}
	public static function getUserInfo($userid,$fields=array("UF_PODPIS")){
		CModule::IncludeModule("highloadblock");
		$entity_data_class = GetEntityDC(USERS_HL_BLOCK_ID);
		$rsData = $entity_data_class::getList(array(
			'select' => $fields,
			'filter' => array('ID' => $userid)
		));
		$data=false;
		$data = $rsData->fetch();
		return $data;
	}
	public static function p($output,$currentUser){
		if($currentUser["PORTAL_ID"] == "1019"){
			?><pre><?print_r($output)?></pre><?
		}
	}
	public static function check_podpiska_date_ready($user_id)
	{
		if(CModule::IncludeModule('iblock'))
		{
			$rs = CIBlockElement::GetList(
				array(), 
				array(
					"IBLOCK_ID" => 48, 
					array("NAME" => "Подписка ".$user_id )
				),
				false, 
				false,
				array("ID","PROPERTY_*","ACTIVE")
			);
			if($ar = $rs->GetNext()) {
				$d=strtotime($ar["PROPERTY_77"]);
				if($ar["ACTIVE"]=='N'&&(time()-$d)>60*60*24*365)
				{
					return true;
				}
				else
					return false;
			}
			else return true;
		}
		return false;
	}
}