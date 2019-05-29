<?php if(!class_exists('Rain\Tpl')){exit;}?><form action="https://ws.pagseguro.uol.com.br/v2/checkout" method="post">
    
    <input name="email" type="hidden" value="fgyan94@gmail.com" />
    <input name="token" type="hidden" value="fb2b2c11-b598-4941-9e38-9a2f5ab29afcc8018f04453cbff60b7080c58539159af74f-4c8d-48e5-8951-f0fb3629aac6" />
     <input name="currency" type="hidden" value="BRL" />
     
     <?php $counter1=-1;  if( isset($products) && ( is_array($products) || $products instanceof Traversable ) && sizeof($products) ) foreach( $products as $key1 => $value1 ){ $counter1++; ?>
     	<input name="itemId<?php echo htmlspecialchars( $counter1+1, ENT_COMPAT, 'UTF-8', FALSE ); ?>" type="hidden" value="<?php echo htmlspecialchars( $value1["idproduct"], ENT_COMPAT, 'UTF-8', FALSE ); ?>" />
     	<input name="itemDescription<?php echo htmlspecialchars( $counter1+1, ENT_COMPAT, 'UTF-8', FALSE ); ?>" type="hidden" value="<?php echo htmlspecialchars( $value1["desproduct"], ENT_COMPAT, 'UTF-8', FALSE ); ?>" />
     	<input name="itemAmount<?php echo htmlspecialchars( $counter1+1, ENT_COMPAT, 'UTF-8', FALSE ); ?>" type="hidden" value="<?php echo htmlspecialchars( $value1["vltotal"], ENT_COMPAT, 'UTF-8', FALSE ); ?>" />
     	<input name="itemQuantity<?php echo htmlspecialchars( $counter1+1, ENT_COMPAT, 'UTF-8', FALSE ); ?>" type="hidden" value="<?php echo htmlspecialchars( $value1["nrqtd"], ENT_COMPAT, 'UTF-8', FALSE ); ?>" />
     	<input name="itemWeight<?php echo htmlspecialchars( $counter1+1, ENT_COMPAT, 'UTF-8', FALSE ); ?>" type="hidden" value="<?php echo htmlspecialchars( $value1["vlweight"]*1000, ENT_COMPAT, 'UTF-8', FALSE ); ?>" />
     <?php } ?>
     
     <input name="reference" type="hidden" value="<?php echo htmlspecialchars( $order["idorder"], ENT_COMPAT, 'UTF-8', FALSE ); ?>" />
     
     <input name="shippingType" type="hidden" value="1" />
     <input name="shippingAddressRequired" type="hidden" value="true" />
     <input name="shippingAddressPostalCode" type="hidden" value="<?php echo htmlspecialchars( $order["deszipcode"], ENT_COMPAT, 'UTF-8', FALSE ); ?>"/>
     <input name="shippingAddressStreet" type="hidden" value='<?php echo utf8_encode($order["desaddress"]); ?>' />
     <input name="shippingAddressNumber" type="hidden" value='<?php echo utf8_encode($order["desnumber"]); ?>' />
     <input name="shippingAddressComplement" type="hidden" value='<?php echo utf8_encode($order["descomplement"]); ?>' />
     <input name="shippingAddressDistrict" type="hidden" value='<?php echo utf8_encode($order["desdistrict"]); ?>' />
     <input name="shippingAddressCity" type="hidden" value='<?php echo utf8_encode($order["descity"]); ?>' />
     <input name="shippingAddressState" type="hidden" value='<?php echo utf8_encode($order["desstate"]); ?>' />
     <input name="shippingAddressCountry" type="hidden" value='<?php echo utf8_encode($order["descountry"]); ?>' />
     
     <input name="senderName" type="hidden" value='Teste da Silva'/>
     <input name="senderEmail" type="hidden" value="<?php echo htmlspecialchars( $order["desemail"], ENT_COMPAT, 'UTF-8', FALSE ); ?>" />
     <input name="senderAreaCode" type="hidden" value="<?php echo htmlspecialchars( $phone["area"], ENT_COMPAT, 'UTF-8', FALSE ); ?>" />
     <input name="senderPhone" type="hidden" value="<?php echo htmlspecialchars( $phone["number"], ENT_COMPAT, 'UTF-8', FALSE ); ?>" />

</form>
<script>

document.forms[0].submit();

</script>