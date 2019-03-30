<?php 

function formatPrice(float $vlprice) {
	
	return number_format($vlprice, 2, ",", ".");
	
}

function formatValueToDecimal($value):float {
	
	$value = str_replace(".", "", $value);
	return str_replace(",", ".", $value);
	
}

?>