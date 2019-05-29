<?php 

namespace Hcode\Model;


use Hcode\DB\Sql;
use Hcode\Model;

require_once 'functions.php';

class Cart extends Model {
	const SESSION = "Cart";
	const SESSION_ERROR = "CartError";
	
	public static function getFromSession() {
		$cart = new Cart();
		
		
		if(isset($_SESSION[Cart::SESSION]) && (int)$_SESSION[Cart::SESSION]['idcart'] > 0) {
			$cart->get((int) $_SESSION[Cart::SESSION]['idcart']);
		} else {
			$cart->getFromSessionID();
			
			if(!((int)$cart->getidcart()) > 0) {
				$data = array(
					"dessessionid" => session_id()
				);
				
				if(User::checkLogin()) {
				
					$user = User::getFromSession();
				
					$data['iduser'] = $user->getiduser();
				}
				
				$cart->setData($data);
				
				$cart->save();
				
				$cart->setSession();
			}
		}
			
		return $cart;
	}
	
	public function save() {
		$sql = new Sql();
		
		$results = $sql->select("CALL sp_carts_save(:idcart, :dessessionid, :iduser, :deszipcode, :vlfreight, :nrdays)",
			array(":idcart" => $this->getidcart(),
					":dessessionid" => $this->getdessessionid(),
					":iduser" => $this->getiduser(),
					":deszipcode" => $this->getdeszipcode(),
					":vlfreight" => $this->getvlfreight(),
					":nrdays" => $this->getnrdays()
			)
		);
		
		$this->setData($results[0]);
	}
	
	public function getFromSessionID() {
		$sql = new Sql();
		
		$results = $sql->select("SELECT * FROM tb_carts WHERE dessessionid = :dessessionid", 
								array(":dessessionid" => session_id()));
		
		if(count($results) > 0)
			$this->setData($results[0]);
	}
	
	public function get($idcart) {
		$sql = new Sql();
		
		$results = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart", array(":idcart" => $idcart));
		
		if(count($results) > 0)
			$this->setData($results[0]);
	}
	
	public function setSession() {
		
		$_SESSION[Cart::SESSION] = $this->getValues();
	}
	
	public function addProduct(Product $product) {		
		$sql = new Sql();
		
		$sql->query("INSERT INTO tb_cartsproducts (idcart, idproduct) VALUES(:idcart, :idproduct)",
					array(":idcart" => $this->getidcart(), ":idproduct" => $product->getidproduct())
		);
		
		$this->calculateTotal();
	}
	
	public function removeProduct(Product $product, $all = false) {
		$sql = new Sql();
		
		if($all) {
			$sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW()
						WHERE idcart = :idcart
						AND idproduct = :idproduct
						AND dtremoved IS NULL",
						array(":idcart" => $this->getidcart(), ":idproduct" => $product->getidproduct())		
			);
		} else {
			$sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() 
						WHERE idcart = :idcart 
						AND idproduct = :idproduct
						AND dtremoved IS NULL LIMIT 1",
					array(":idcart" => $this->getidcart(), ":idproduct" => $product->getidproduct())
			);
		}
		
		$this->calculateTotal();
	}
	
	public function getProducts() {
		$sql = new Sql();
		
		$results = $sql->select("SELECT b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl,
								COUNT(*) AS nrqtd,
								SUM(b.vlprice) AS vltotal
								FROM tb_cartsproducts a INNER JOIN
								tb_products b ON a.idproduct = b.idproduct
								WHERE a.idcart = :idcart
								AND a.dtremoved IS NULL
								GROUP BY b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl
								ORDER BY b.desproduct",
								[":idcart" => $this->getidcart()]
		);
		
		return Product::checkList($results);
	}
	
	public function getProductTotals() {
		$sql = new Sql();
		
		$results = $sql->select("SELECT
								SUM(vlprice) AS vlprice,
								SUM(vlwidth) AS vlwidth,
								SUM(vlheight) AS vlheight,
								SUM(vllength) AS vllength,
								SUM(vlweight) AS vlweight,
								COUNT(*) AS nrqtd
								FROM tb_products a
								INNER JOIN tb_cartsproducts b
								ON a.idproduct = b.idproduct
								WHERE b.idcart = :idcart AND dtremoved IS NULL LIMIT 1;",
								array(":idcart" => $this->getidcart())
		);
		
		if(count($results) > 0)
			return $results[0];
		else
			return array();
		
	}
	
	public function setFreight($zipcode) {
		$zipcode = str_replace(["-", "."], "", $zipcode);
		
		$total = $this->getProductTotals();
		
		if($total['nrqtd'] > 0) {
			
			if($total['vlheight'] < 2) $total['vlheight'] = 2;
			if($total['vllength'] < 16) $total['vllength'] = 16;
			
			
			$qs = http_build_query(array(
					'nCdEmpresa' => '',
					'sDsSenha' => '',
					'nCdServico' => '40010',
					'sCepOrigem' => '93290440',
					'sCepDestino' => $zipcode,
					'nVlPeso' => $total['vlweight'],
					'nCdFormato' => '1',
					'nVlComprimento' => $total['vllength'],
					'nVlAltura' => $total['vlheight'],
					'nVlLargura' => $total['vlwidth'],
					'nVlDiametro' => '0',
					'sCdMaoPropria' => 'N',
					'nVlValorDeclarado' => $total['vlprice'],
					'sCdAvisoRecebimento' => 'N'
			));
			
			$xml = simplexml_load_file("http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo?".$qs);
			
			$result = $xml->Servicos->cServico;
			
			$result->MsgErro != '' ? Cart::setMsgError($result->MsgErro) : Cart::clearMsgError();
			
			$this->setnrdays($result->PrazoEntrega);
			$this->setvlfreight($result->Valor);
			$this->setdeszipcode($zipcode);
			
			$this->save();
			
			return $result;
		}
	}
	
	public function updateFreight() {
		
		if($this->getdeszipcode() != '')
			$this->setFreight($this->getdeszipcode());
		
		
	}
	
	public static function setMsgError($msg) {
		
		$_SESSION[Cart::SESSION_ERROR] = $msg;
		
 	}
 	
 	
 	public static function getMsgEror() {
 		
 		$msg = isset($_SESSION[Cart::SESSION_ERROR]) ? $_SESSION[Cart::SESSION_ERROR] : "";
 		
 		Cart::clearMsgError();
 		
 		return $msg;
 		
 	}
 	
 	public static function clearMsgError() {
 		
 		$_SESSION[Cart::SESSION_ERROR] = null;
 		
 	}
	
 	public function getValues() {
 		
 		$this->calculateTotal();
 		
 		return parent::getValues();
 		
 	}
 	
 	public function calculateTotal() {
 		
 		$this->updateFreight();
 		
 		$total = $this->getProductTotals();
 		
 		$this->setvlsubtotal($total['vlprice']);
 		$this->setvltotal($this->getvlsubtotal() + $this->getvlfreight());
 		
 	}
 	
 	public static function logout() {
 		unset($_SESSION[Cart::SESSION]);
 	}
 	
}

?>