<?php 
namespace Hcode\Model;

use Hcode\DB\Sql;
use Hcode\Model;
use Hcode\Mailer;

class User extends Model {
	const SESSION = "User";
	const SECRET = "HcodePHP7_Secret";
	const SESSION_ERROR = "UserError";
	const SESSION_ERROR_REGISTER = "UserErrorRegister";
	const SESSION_SUCCESS = "UserSuccess";
	
	public static function getFromSession() {
		$user = new User();
		
		if(isset($_SESSION[User::SESSION]) && (int) $_SESSION[User::SESSION]['iduser'] > 0) {
			
			$user->setData($_SESSION[User::SESSION]);
			
		}
		
		return $user;
	}
	
	public static function checkLogin() {
		if(!isset($_SESSION[User::SESSION]) || !$_SESSION[User::SESSION] || !(int)$_SESSION[User::SESSION]['iduser'] > 0) {
			return false;
		}
		
		return true;
	}
	
	public static function login($login, $password) {
		$sql = new Sql();
		
		$result = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE deslogin = :LOGIN", 
		array(
			":LOGIN"=>$login
		));
		
		if(count($result) === 0) {
			User::setMsgError("Usuário inexistente ou senha inválida.");
		} else {
		
			$data = $result[0];
			
			if(password_verify($password, $data['despassword'])) {
				$user = new User();
				$data['desperson'] = utf8_encode($data['desperson']);
				$user->setData($data);
				$_SESSION[User::SESSION] = $user->getValues();
				
				return $user;
				
			} else{
				User::setMsgError("Usuário inexistente ou senha inválida.");
			}
		}
	}
	
	public static function verifyLogin($inadmin = true) {
		if(!User::checkLogin() || (bool)$_SESSION[User::SESSION]['inadmin'] !== $inadmin) {
			header("Location: /admin/login");
			exit();
		}
	}
	
	public static function listAll() {
		$sql = new Sql();
		return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");
	}
	
	public static function getForgot($email, $inadmin = true) {
		$sql = new Sql();
		$results = $sql->select("SELECT * FROM tb_persons a INNER JOIN tb_users b USING(idperson) WHERE a.desemail = :email",
								array(":email" => $email));
		
		if(count($results) == 0) {
			throw new \Exception("Não foi possível recuperar a senha.");
		} else {
			$data = $results[0];
			$results_recovery = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", 
							array(	":iduser" => $data['iduser'],
									":desip" => $_SERVER['REMOTE_ADDR']
							));
			
			if(count($results_recovery) == 0) {
				throw new \Exception("Não foi possível recuperar a senha.");
			} else {
				$data_recovery = $results_recovery[0];
				
				$code = base64_encode(openssl_encrypt($data_recovery['idrecovery'], 'AES-128-CBC', User::SECRET));
				
				if($inadmin)
					$link = "http://www.hcodecommerce.com.br/admin/forgot/reset?code=$code";
				else
					$link = "http://www.hcodecommerce.com.br/forgot/reset?code=$code";
				
				$mailer = new Mailer(
						$data['desemail'], 
						$data['desperson'], 
						"Redefinir senha da HCode Store", 
						'forgot',
						array(
							"name" => $data['desperson'],
							"link" => $link
						)
				);
				
				$mailer->send();
				
				return $data;
				
			}
		}
	}
	
	public static function validForgotDecrypt($code) {
		$idrecovery = openssl_decrypt(base64_decode($code), 'AES-128-CBC', User::SECRET);
		
		$sql = new Sql();
		$results = $sql->select("
						SELECT * FROM tb_userspasswordsrecoveries a
						INNER JOIN tb_users b USING(iduser)
						INNER JOIN tb_persons c USING(idperson)
						WHERE a.idrecovery = :idrecovery
						AND a.dtrecovery IS NULL
						AND DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW()
					",
					array(
						":idrecovery" => $idrecovery
					)
				);
		
		if(count($results) === 0) {
			throw new \Exception("Não foi possível recuperar a senha.");
		} else {
			return $results[0];
		}
			
	}
	
	public static function setForgotUsed($idrecovery) {
		$sql = new Sql();
		$sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecover = NOW() WHERE idrecovery = :idrecovery",
					array(":idrecovery" => $idrecovery)
		);
	}
	
	public function save() {
		$sql = new Sql();
		$results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
				":desperson" => utf8_decode($this->getdesperson()),
				":deslogin" => $this->getdeslogin(),
				":despassword" => User::getPasswordHash($this->getdespassword()),
				":desemail" => $this->getdesemail(),
				":nrphone" => $this->getnrphone(),
				":inadmin" => $this->getinadmin()
		));
		
		$this->setData($results[0]);
	}
	
	public function update() {
		$sql = new Sql();

		$results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)",
				array(	
						":iduser" => $this->getiduser(),
						":desperson" => utf8_decode($this->getdesperson()),
						":deslogin" => $this->getdeslogin(),
						":despassword" => User::getPasswordHash($this->getdespassword()),
						":desemail" => $this->getdesemail(),
						":nrphone" => $this->getnrphone(),
						":inadmin" => $this->getinadmin()
				));

		
		$this->setData($results[0]);
	}
	
	public function delete() {
		$sql = new Sql();

		$sql->query("CALL sp_users_delete(:iduser)", array(
				":iduser"=>$this->getiduser()
		));
	}
	
	public function setPassword($password) {
		$sql = new Sql();
		$sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser",
					array(":password" => User::getPasswordHash($password), ":iduser" => $this->getiduser())
		);

		$sql->query("CALL sp_users_delete(:iduser)", array(":iduser" => $this->getiduser()));
	}
	
	public function get($iduser){
		$sql = new Sql();
		$results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser",
								array(":iduser"=>$iduser));
		
		$data = $results[0];
		
		$data['desperson'] = utf8_encode($data['desperson']);
		
		$this->setData($data);
	}
	
	public static function logout() {
		unset($_SESSION[User::SESSION]);
	}
	
	public static function setMsgError($msg) {
		
		$_SESSION[User::SESSION_ERROR] = $msg;
		
	}
	
	
	public static function getMsgEror() {
		
		$msg = isset($_SESSION[User::SESSION_ERROR]) ? $_SESSION[User::SESSION_ERROR] : "";
		
		User::clearMsgError();
		
		return $msg;
		
	}
	
	public static function clearMsgError() {
		
		$_SESSION[User::SESSION_ERROR] = "";
		
	}
	
	public static function getPasswordHash($password) {
		return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
	}
	
	public static function setErrorRegister($msg) {
		$_SESSION[User::SESSION_ERROR_REGISTER] = $msg;
	}
	
	public static function getErrorRegister() {
		
		$msg = isset($_SESSION[User::SESSION_ERROR_REGISTER]) ? $_SESSION[User::SESSION_ERROR_REGISTER] : "";
		
		User::clearMsgErrorRegister();
		
		return $msg;
		
	}
	
	public static function clearMsgErrorRegister() {
		
		$_SESSION[User::SESSION_ERROR_REGISTER] = null;
		
	}
	
	public static function checkLoginExists($login) {
		$sql = new Sql();
		
		$results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :deslogin", [':deslogin' => $login]);
		
		return count($results) > 0;
	}
	
	public static function setSuccess($msg) {
		
		$_SESSION[User::SESSION_SUCCESS] = $msg;
		
	}
	
	
	public static function getSuccess() {
		
		$msg = isset($_SESSION[User::SESSION_SUCCESS]) ? $_SESSION[User::SESSION_SUCCESS] : "";
		
		User::clearSuccess();
		
		return $msg;
		
	}
	
	public static function clearSuccess() {
		
		$_SESSION[User::SESSION_SUCCESS] = "";
		
	}
	
	public function getOrders() {
		$sql = new Sql();
		
		$results = $sql->select("SELECT *
								 FROM tb_orders a
								 INNER JOIN tb_ordersstatus b USING(idstatus)
								 INNER JOIN tb_carts c USING(idcart)
								 INNER JOIN tb_users d ON d.iduser = a.iduser
								 INNER JOIN tb_addresses e USING(idaddress)
								 INNER JOIN tb_persons f ON f.idperson = d.idperson
								 WHERE a.iduser = :iduser
								",
				array(
						':iduser' => $this->getiduser() 
					)
				);
		
		return $results;
			
	}
	
	public static function setMsgErrorByKey($key) {
		
		switch ($key) {
			case "current_pass":
				User::setMsgError("Digite a senha atual");
				break;
			case "new_pass":
				User::setMsgError("Digite a nova senha");
				break;
			case "new_pass_confirm":
				User::setMsgError("Confirme a nova senha");
				break;
				
			default:
				break;
		}
	}
	
	public function checkChangePass($post) {
		if($post['current_pass'] === $post['new_pass']) {
			User::setMsgError("A nova senha não pode ser idêntica à atual");
			return false;
		} else if($post['new_pass'] !== $_POST['new_pass_confirm']) {
			User::setMsgError("As novas senhas não correspondem");
			return false;
		}
		
		return true;
	}
	
	public static function getPage($page = 1, $itemsPerPage = 10) {
		$start = ($page - 1) * $itemsPerPage;
		
		$sql = new Sql();
		
		$results = $sql->select("SELECT SQL_CALC_FOUND_ROWS * 
								  FROM tb_users a 
								  INNER JOIN tb_persons b USING(idperson) 
								  ORDER BY b.desperson
								  LIMIT $start, $itemsPerPage");
		
		$nrTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal");
		
		return array("data" => $results,
				"total" => (int) $nrTotal[0]['nrtotal'],
				"pages" => ceil($nrTotal[0]['nrtotal'] / $itemsPerPage)
		);
	}
	
	public static function getPageSearch($search, $page = 1, $itemsPerPage = 10) {
		$start = ($page - 1) * $itemsPerPage;
		
		$sql = new Sql();
		
		$results = $sql->select("SELECT SQL_CALC_FOUND_ROWS *
								  FROM tb_users a
								  INNER JOIN tb_persons b USING(idperson)
								  WHERE b.desperson LIKE :search OR b.desemail = :search OR a.deslogin LIKE :search
								  ORDER BY b.desperson
								  LIMIT $start, $itemsPerPage", 
								[
									":search" => $search
								]);
		
		$nrTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal");
		
		return array("data" => $results,
				"total" => (int) $nrTotal[0]['nrtotal'],
				"pages" => ceil($nrTotal[0]['nrtotal'] / $itemsPerPage)
		);
	}
}

?>