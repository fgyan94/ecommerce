<?php 

use Hcode\Model\User;

function formatPrice(float $vlprice) {
	
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

?>