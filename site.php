<?php

use \Hcode\Page;
use Hcode\Model\Product;
use Hcode\Model\User;
use Hcode\Model\Category;
use Hcode\Model\Cart;

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
	
	$page->setTPL('cart');
	
});

?>