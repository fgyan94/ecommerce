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
	
	$cart = Cart::getFromSession();
	
	$address = new Address();
	
	$page = new Page();
	
	$page->setTPL('checkout', array(
			'cart' => $cart->getValues(),
			'address' => $address->getValues()
	));
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
	
// 	try {
// 		User::login($_POST['login'], $_POST['password']);
// 	} catch(\Exception $e) {
// 		User::setMsgError($e->getMessage());
// 	}
	User::login($_POST['login'], $_POST['password']);
	
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

?>