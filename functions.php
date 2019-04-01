<?php 

use Hcode\Model\User;
use Hcode\Model\Cart;

function formatPrice($vlprice) {
	
	if($vlprice < 0) $vlprice = 0;
	
	return number_format($vlprice, 2, ",", ".");
	
}

function formatValueToDecimal($value):float {
	
	$value = str_replace(".", "", $value);
	return str_replace(",", ".", $value);
	
}

function checkLogin() {
	return User::checkLogin();
}

function getUserName() {
	$user = User::getFromSession();
	$user->get((int) $user->getiduser());
	
	return $user->getdesperson();
}

function getProductTotal() {
	$cart = Cart::getFromSession();
	
	$val = $cart->getProductTotals();
	
	return $val['nrqtd'];
}

function getTotal() {
	$cart = Cart::getFromSession();
	
	$val = $cart->getProductTotals();
	
	return $val['vlprice'];
}

?>