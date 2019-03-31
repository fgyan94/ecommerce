<?php

namespace Hcode\Model;

use Hcode\DB;
use Hcode\Model;
use Hcode\DB\Sql;

class Address extends Model {
	const SESSION_ERROR = "AddressError";
	
	public static function getCep($nrcep) {
		$nrcep = str_replace("-", "", $nrcep);
		
		$ch = curl_init();
		
		curl_setopt($ch, CURLOPT_URL, "https://viacep.com.br/ws/$nrcep/json/");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		$data = json_decode(curl_exec($ch), true);
		
		curl_close($ch);
		
		return $data;
	}
	
	public function loadFromCEP($nrcep) {
		
		$data = Address::getCep($nrcep);
		
		$this->setdesaddress($data['logradouro']);
		$this->setdescomplement($data['complemento']);
		$this->setdesdistrict($data['bairro']);
		$this->setdescity($data['localidade']);
		$this->setdesstate($data['uf']);
		$this->setdescountry('Brasil');
		$this->setdeszipcode($nrcep);
		
	}
	
	public function save() {
		$sql = new Sql();
		
		$results = $sql->select("CALL sp_address_save(:idaddress, :idperson, :desaddress, 
													  :descomplement, :descity, :desstate, 
													  :descountry, :deszipcode, :desdistrict",
				array(	':idaddress' => $this->getidaddress(), 
						':idperson' => $this->getidperson(), 
						':desaddress' => utf8_decode($this->getdesaddress()), 
						':descomplement' => utf8_decode($this->getdescomplement()), 
						':descity' => utf8_decode($this->getdescity()), 
						':desstate' => utf8_decode($this->getdesstate()), 
						':descountry' => utf8_decode($this->getdescountry()), 
						':deszipcode' => $this->getdeszipcode(),
						':desdistrict' => utf8_decode($this->getdesdistrict())
					)
		);
		
		if(count($results) > 0)
			$this->setData($results[0]);
		
	}
	
	public static function setMsgError($msg) {
		
		$_SESSION[Address::SESSION_ERROR] = $msg;
		
	}
	
	
	public static function getMsgEror() {
		
		$msg = isset($_SESSION[Address::SESSION_ERROR]) ? $_SESSION[Address::SESSION_ERROR] : "";
		
		Address::clearMsgError();
		
		return $msg;
		
	}
	
	public static function clearMsgError() {
		
		$_SESSION[Address::SESSION_ERROR] = null;
		
	}	
	
	public static function setMsgErrorByKey($key) {
		
		switch ($key) {
			case 'deszipcode' || 'zipcode':
				Address::setMsgError("Informe o CEP");
				break;
				
			case 'desaddress':
				Address::setMsgError("Informe o endereço");
				break;
				
			case 'desdistrict':
				Address::setMsgError("Informe o bairro");
				break;
				
			case 'descity':
				Address::setMsgError("Informe a cidade");
				break;
				
			case 'desstate':
				Address::setMsgError("Informe o estado");
				break;
				
			case 'descountry':
				Address::setMsgError("Informe o país");
				break;
				
			default:
				break;
		}
	}
}


?>