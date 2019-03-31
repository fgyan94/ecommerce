<?php

use Hcode\Page;
use Hcode\Model\Address;
use Hcode\Model\Cart;
use Hcode\Model\Category;
use Hcode\Model\Product;
use Hcode\Model\User;

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
	
	$product = new Product();
	
	$product->get((int) $idproduct);
	
	$cart = Cart::getFromSession();
	
	$cart->removeProduct($product);
	
	header('Location: /cart');
	exit;
});
	
$app->get('/cart/:idproduct/remove', function($idproduct) {
	
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
	
	$page = new Page();
	
	$page->setTPL('checkout', array(
			'cart' => $cart->getValues(),
			'address' => $address->getValues(),
			'products' => $cart->getProducts(),
			'error' => Address::getMsgEror()
	));
});

$app->post('/checkout', function() {
	User::checkLogin();
	
	foreach($_POST as $key => $value) {
		if($key !== 'descomplement') {
			if(!isset($_POST[$key]) || $_POST[$key] === '') {
				Address::setMsgErrorByKey($key);
				header('Location: /checkout');
				exit;
			}
		}
	}
	
	$user = User::getFromSession();
	
	$address = new Address();
	
	$_POST['deszipcode'] = $_POST['zipcode'];
	$_POST['idperson'] = $user->getiduser();
	
	$address->setData($_POST);
	
	$address->save();
	
	header('Location: /order');
	exit;
	
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
	
	User::checkLogin();
	
	$user = User::getFromSession();
	
	$page = new Page();
	$page->setTPL('profile', array(
		'user' => $user->getValues(),
		'profileMsg' => User::getSuccess(),
		'profileError' => User::getMsgEror()
	));
});

$app->post('/profile', function(){
	
	User::checkLogin();
	
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

?>