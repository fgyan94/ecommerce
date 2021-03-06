<?php

use Hcode\Page;
use Hcode\Model\Address;
use Hcode\Model\Cart;
use Hcode\Model\Category;
use Hcode\Model\Product;
use Hcode\Model\User;
use Hcode\Model\Order;
use Hcode\Model\OrderStatus;
use function Composer\Autoload\includeFile;

$app->get ( '/', function () {
	
	$products = Product::listAll();
	
	$page = new Page ();
	
	$page->setTPL ( "index", array(
			"products" => Product::checkList($products)
	) );
} );
	
$app->get('/categories/:idcategory', function($idcategory) {
	
	$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
	
	$category = new Category();
	
	$category->get((int) $idcategory);
	
	$pagination = $category->getProductsPage($page);

	$pages = array();
	for ($i = 1; $i <= $pagination['pages']; $i++) {
		array_push($pages, array(
			'page' => $i,
			'link' => "/categories/".$category->getidcategory()."?page=$i"
		));
	}
	
	$page = new Page();
	
	$page->setTPL('category', array(
			'category' => $category->getValues(),
			'products' => $pagination['data'],
			'pages' => $pages
	));
});

$app->get('/products/:desurl', function($desurl){
	$product = new Product();
	
	$product->getFromURL($desurl);
	
	$page = new Page();
	
	$page->setTPL('product-detail', array(
			"product" => $product->getValues(),
			"categories" => $product->getCategories()
	));
});

$app->get('/cart', function(){
	
	if(!User::checkLogin()) {
		header('Location: /login');
		exit;
	}
	
	$cart = Cart::getFromSession();
	
	$page = new Page();
	
	$page->setTPL('cart', array(
				'cart' => $cart->getValues(), 
				'products' => $cart->getProducts(),
				'error' => Cart::getMsgEror()
		)	
	);
	
});

$app->get('/cart/:idproduct/add', function($idproduct) {
	
	if(!User::checkLogin()) {
		header('Location: /login');
		exit;
	}
	
	$product = new Product();
	
	$product->get((int) $idproduct);
	
	$cart = Cart::getFromSession();
	
	$qtd = isset($_GET['qtd']) ? (int) $_GET['qtd'] : 1;
	
	for($i = 0; $i < $qtd; $i++) {
		$cart->addProduct($product);
	}
	
	header('Location: /cart');
	exit;
});
	
$app->get('/cart/:idproduct/minus', function($idproduct) {
	
	if(!User::checkLogin()) {
		header('Location: /login');
		exit;
	}
	
	$product = new Product();
	
	$product->get((int) $idproduct);
	
	$cart = Cart::getFromSession();
	
	$cart->removeProduct($product);
	
	header('Location: /cart');
	exit;
});
	
$app->get('/cart/:idproduct/remove', function($idproduct) {
	
	if(!User::checkLogin()) {
		header('Location: /login');
		exit;
	}
	
	$product = new Product();
	
	$product->get((int) $idproduct);
	
	$cart = Cart::getFromSession();
	
	$cart->removeProduct($product, true);
	
	header('Location: /cart');
	exit;
});

$app->post('/cart/freight', function() {
	
	$cart = Cart::getFromSession();
	
	$cart->setFreight($_POST['zipcode']);
	
	
	header('Location: /cart');
	exit;
});

$app->get('/checkout', function() {
	
	if(!User::checkLogin()) {
		header('Location: /login');
		exit;
	}
	
	$address = new Address();
	
	$cart = Cart::getFromSession();
	
	if(isset($_GET['zipcode'])) {
		$address->loadFromCEP($_GET['zipcode']);
		$cart->setdeszipcode($_GET['zipcode']);
		$cart->save();
		$cart->calculateTotal();
	}
	
	if(!$address->getdesaddress()) $address->setdesaddress('');
	if(!$address->getdesnumber()) $address->setdesnumber('');
	if(!$address->getdescomplement()) $address->setdescomplement('');
	if(!$address->getdesdistrict()) $address->setdesdistrict('');
	if(!$address->getdescity()) $address->setdescity('');
	if(!$address->getdesstate()) $address->setdesstate('');
	if(!$address->getdescountry()) $address->setdescountry('');
	if(!$address->getdeszipcode()) $address->setdeszipcode('');
	
	$page = new Page();
	
	$page->setTPL('checkout', array(
			'cart' => $cart->getValues(),
			'address' => $address->getValues(),
			'products' => $cart->getProducts(),
			'checkoutError' => Address::getMsgEror()
	));
});

$app->post('/checkout', function() {
	
	if(!User::checkLogin()) {
		header('Location: /login');
		exit;
	}
	
// 	foreach($_POST as $key => $value) {
// 		if($key !== 'descomplement') {
// 			if(!isset($_POST[$key]) || $_POST[$key] === '') {
// 				Address::setMsgErrorByKey($key);
// 				header('Location: /checkout');
// 				exit;
// 			}
// 		}
// 	}
	
	$user = User::getFromSession();
	
	$address = new Address();
	
	$_POST['deszipcode'] = $_POST['zipcode'];
	$_POST['idperson'] = $user->getiduser();
	
	$address->setData($_POST);	
	$address->save();
	
	$cart = Cart::getFromSession();
	
	$cart->calculateTotal();
	
	$order = new Order();
	
	$order->setData(array(
		'idorder' => $order->getidorder(),
		'idcart' => $cart->getidcart(),
		'idaddress' => $address->getidaddress(),
		'iduser' => $user->getiduser(),
		'idstatus' => OrderStatus::EM_ABERTO,
		'vltotal' => $cart->getvltotal()
	));
	
	$order->save();
	
	switch((int) $_POST['payment-method']) {
		case 1:
			header('Location: /order/'.$order->getidorder().'/pagseguro');
			exit;
		break;
		
		case 2:
			header('Location: /order/'.$order->getidorder().'/paypal');
			exit;
		break;
	}
	
	
});

$app->get('/order/:idorder/pagseguro', function($idorder) {
	
	if(!User::checkLogin()) {
		header('Location: /login');
		exit;
	}
	
	$order = new Order();
	
	$order->get((int) $idorder);
	
	$cart = Cart::getFromSession();
	
	$products = $cart->getProducts();
	
	$page = new Page([
		'header' => false,
		'footer' => false
	]);
	
	$page->setTPL("payment-pagseguro", [
			"order" => $order->getValues(),
			"cart" => $cart->getValues(),
			"products" => $products,
			"phone" => [
				'area' => substr($order->getnrphone(), 0, 2), 
				'number' => substr($order->getnrphone(), 2)
			]
	]);
	
});
	
$app->get('/order/:idorder/paypal', function($idorder) {
	
	if(!User::checkLogin()) {
		header('Location: /login');
		exit;
	}
	
	$order = new Order();
	
	$order->get((int) $idorder);
	
	$cart = Cart::getFromSession();
	
	$products = $cart->getProducts();
	
	$page = new Page([
			'header' => false,
			'footer' => false
	]);
	
	$page->setTPL("payment-paypal", [
			"order" => $order->getValues(),
			"cart" => $cart->getValues(),
			"products" => $products
	]);
	
});

$app->get('/login', function() {
	
	$page = new Page();
	
	$page->setTPL('login', array(
			'error' => User::getMsgEror(),
			'errorRegister' => User::getErrorRegister(),
			'registerValues' => isset($_SESSION['registerValues']) ?
								$_SESSION['registerValues'] : array(
									'name' => '', 'email' => '', 'phone' => ''
								)
	));
});

$app->post('/login', function() {
	
	try {
		User::login($_POST['login'], $_POST['password']);
	} catch(\Exception $e) {
		User::setMsgError($e->getMessage());
	}
	
	header('Location: /checkout');
	exit;
	
});

$app->get('/logout', function() {
	User::logout();
	Cart::logout();
	session_regenerate_id();
	header('Location: /');
	exit;
});

$app->post('/register', function() {
	
	$_SESSION['registerValues'] = $_POST;
	
	if(!isset($_POST['name']) || $_POST['name'] == '') {
		User::setErrorRegister('Preencha o seu nome');
		header('Location: /login');
		exit;
	}
	
	if(!isset($_POST['email']) || $_POST['email'] == '') {
		User::setErrorRegister('Preencha o seu email');
		header('Location: /login');
		exit;
	}
	
	if(!isset($_POST['password']) || $_POST['password'] == '') {
		User::setErrorRegister('Preencha o campo senha');
		header('Location: /login');
		exit;
	}
	
	if(User::checkLoginExists($_POST['email'])) {
		User::setErrorRegister('O email informado já está sendo usado por outro usuário');
		header('Location: /login');
		exit;
	}
	
	$user = new User();
	$user->setData(array(
		'inadmin' => false,
		'deslogin' => $_POST['email'],
		'desperson' => $_POST['name'],
		'desemail' => $_POST['email'],
		'despassword' => $_POST['password'],
		'nrphone' => $_POST['phone']
	));
	
	$user->save();
	
	User::login($_POST['email'], $_POST['password']);
	
	header('Location: /checkout');
	exit;
});

$app->get('/forgot', function() {
	
	$page = new Page();
	
	$page->setTPL ( "forgot" );
	exit;
});
	
$app->post('/forgot', function() {
	User::getForgot($_POST['email'], false);
		
	header("Location: /forgot/sent");
	exit;
});
		
$app->get("/forgot/sent", function(){
			
	$page = new Page ();
			
	$page->setTPL ( "forgot-sent" );
});
			
$app->get('/forgot/reset', function(){
	
	$user = User::validForgotDecrypt($_GET['code']);
	
	$page = new Page ();
	$page->setTPL ( "forgot-reset",
			array(
				 "name" => $user['desperson'],
				"code" => $_GET['code']
			)
	);
});
				
$app->post('/forgot/reset', function(){
	
	$forgot = User::validForgotDecrypt($_POST['code']);
	
	User::setForgotUsed($forgot['idrecovery']);
	
	$user = new User();
	
	$user->get((int) $forgot['iduser']);
	
	$user->setPassword($_POST['password']);
	
	$page = new Page ();
	
	$page->setTPL ( "forgot-reset-success");
	
});

$app->get('/profile', function(){
	
	if(!User::checkLogin()) {
		header('Location: /login');
		exit;
	}
	
	$user = User::getFromSession();
	
	$page = new Page();
	$page->setTPL('profile', array(
		'user' => $user->getValues(),
		'profileMsg' => User::getSuccess(),
		'profileError' => User::getMsgEror()
	));
});

$app->post('/profile', function(){
	
	if(!User::checkLogin()) {
		header('Location: /login');
		exit;
	}
		
	if(!isset($_POST['desperson']) || $_POST['desperson'] === '') {
		User::setMsgError("Preencha o seu nome");
		header("Location: /profile#user");
		exit;
	}
	
	if(!isset($_POST['desemail']) || $_POST['desemail'] === '') {
		User::setMsgError("Preencha o seu email");
		header("Location: /profile#user");
		exit;
	}
	
	$user = User::getFromSession();
	
	if($_POST['desemail'] !== $user->getdesemail()) {
		if(User::checkLoginExists($_POST['desemail'])){
			User::setMsgError("Este endereço de email já está sendo usado por outro usuário");
			header('Location: /profile#user');
			exit;
		}
	}
	
	$user->setData($_POST);
	
	$user->update();
	
	User::setSuccess("Os dados foram atualizados com sucesso!");
	
	header('Location: /profile#user');
	exit;
	
});

$app->get('/order/:idorder', function($idorder) {
	
	if(!User::checkLogin()) {
		header('Location: /login');
		exit;
	}
	
	$order = new Order();
	
	$order->get((int) $idorder);
	
	$page = new Page();
	
	$page->setTPL('payment', array(
			'order' => $order->getValues()
	));
	
});

$app->get('/boleto/:idorder', function($idorder) {
	
	if(!User::checkLogin()) {
		header('Location: /login');
		exit;
	}
	
	$order = new Order();
	
	$order->get((int) $idorder);
	
	// DADOS DO BOLETO PARA O SEU CLIENTE
	$dias_de_prazo_para_pagamento = 10;
	$taxa_boleto = 5.00;
	$data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006";
	$valor_cobrado = $order->getvltotal();
// 	$valor_cobrado = formatPrice((float)$order->getvltotal()); // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
// 	$valor_cobrado = str_replace(",", ".",$valor_cobrado);
	$valor_boleto=number_format($valor_cobrado+$taxa_boleto, 2, ',', '');
	
	$dadosboleto["nosso_numero"] = $order->getidorder();  // Nosso numero - REGRA: Máximo de 8 caracteres!
	$dadosboleto["numero_documento"] = $order->getidorder();	// Num do pedido ou nosso numero
	$dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
	$dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
	$dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
	$dadosboleto["valor_boleto"] = $valor_boleto; 	// Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula
	
	// DADOS DO SEU CLIENTE
	$dadosboleto["sacado"] = $order->getdesperson();
	$dadosboleto["endereco1"] = $order->getdesaddress() . " - " . $order->getdesdistrict() . " " .
								
	$dadosboleto["endereco2"] = $order->getdescity() . "/" . $order->getdesstate() . " - " .$order->getdescountry() 
								. " - CEP: " . $order->getdeszipcode();
	
	// INFORMACOES PARA O CLIENTE
	$dadosboleto["demonstrativo1"] = "Pagamento de Compra na Loja Hcode E-commerce";
	$dadosboleto["demonstrativo2"] = "Taxa bancária - R$ 0,00";
	$dadosboleto["demonstrativo3"] = "";
	$dadosboleto["instrucoes1"] = "- Sr. Caixa, cobrar multa de 2% após o vencimento";
	$dadosboleto["instrucoes2"] = "- Receber até 10 dias após o vencimento";
	$dadosboleto["instrucoes3"] = "- Em caso de dúvidas entre em contato conosco: suporte@hcode.com.br";
	$dadosboleto["instrucoes4"] = "&nbsp; Emitido pelo sistema Projeto Loja Hcode E-commerce - www.hcode.com.br";
	
	// DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
	$dadosboleto["quantidade"] = "";
	$dadosboleto["valor_unitario"] = "";
	$dadosboleto["aceite"] = "";
	$dadosboleto["especie"] = "R$";
	$dadosboleto["especie_doc"] = "";
	
	
	// ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //
	
	
	// DADOS DA SUA CONTA - ITAÚ
	$dadosboleto["agencia"] = "1690"; // Num da agencia, sem digito
	$dadosboleto["conta"] = "48781";	// Num da conta, sem digito
	$dadosboleto["conta_dv"] = "2"; 	// Digito do Num da conta
	
	// DADOS PERSONALIZADOS - ITAÚ
	$dadosboleto["carteira"] = "175";  // Código da Carteira: pode ser 175, 174, 104, 109, 178, ou 157
	
	// SEUS DADOS
	$dadosboleto["identificacao"] = "Hcode Treinamentos";
	$dadosboleto["cpf_cnpj"] = "24.700.731/0001-08";
	$dadosboleto["endereco"] = "Rua Ademar Saraiva Leão, 234 - Alvarenga, 09853-120";
	$dadosboleto["cidade_uf"] = "São Bernardo do Campo - SP";
	$dadosboleto["cedente"] = "HCODE TREINAMENTOS LTDA - ME";
	
	// NÃO ALTERAR!
	$path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'res' . DIRECTORY_SEPARATOR . "boletophp"
			. DIRECTORY_SEPARATOR . "include" . DIRECTORY_SEPARATOR;
	
	require_once($path . "funcoes_itau.php");
	require_once($path . "layout_itau.php");
	
});

$app->get('/profile/orders', function () {
	
	if(!User::checkLogin()) {
		header('Location: /login');
		exit;
	}
	
	$user = User::getFromSession();
	
	$page = new Page();
	
	$page->setTPL('profile-orders', array(
			'orders' => $user->getOrders()
	));
});

$app->get('/profile/orders/:idorder', function($idorder) {
	
	if(!User::checkLogin()) {
		header('Location: /login');
		exit;
	}
	
	$order = new Order();
	
	$order->get((int) $idorder);
	
	$cart = new Cart();
	
	$cart->get((int) $order->getidcart());
	
	$cart->calculateTotal();
	
	$page = new Page();
	
	$page->setTPL('profile-orders-detail', array(
			'order' => $order->getValues(),
			'cart' => $cart->getValues(),
			'products' => $cart->getProducts()
	));
});
	
$app->get('/profile/changepassword', function() {
	
	if(!User::checkLogin()) {
		header('Location: /login');
		exit;
	}
	
	$page = new Page();
	
	$page->setTPL('profile-change-password', array(
			'changePassError' => User::getMsgEror(),
			'changePassSuccess' => User::getSuccess()
	));
});

$app->post('/profile/changepassword', function() {
	
	if(!User::checkLogin()) {
		header('Location: /login');
		exit;
	}
	
	$user = User::getFromSession();
	
	foreach($_POST as $key => $value) {
		if(!isset($_POST[$key]) || $_POST[$key] === '') {
			User::setMsgErrorByKey($key);
			header('Location: /profile/changepassword');
			exit;
		}
	}
	
	if(!$user->checkChangePass($_POST)) {
		header('Location: /profile/changepassword');
		exit;
	}
	
	if(!password_verify($_POST['current_pass'], $user->getdespassword())) {
		User::setMsgError("Senha atual inválida");
		header('Location: /profile/changepassword');
		exit;		
	}
	
	$user->setdespassword($_POST['new_pass']);
	
	$user->update();
	
	User::setSuccess("Senha alterada com sucesso!");
	
	header('Location: /profile/changepassword');
	exit;
});

?>