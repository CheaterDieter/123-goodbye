<?php
$message_to_encrypt = "555";
$secret_key = "my-secret-key";
$method = "aes128";
$iv_length = openssl_cipher_iv_length($method);
$iv = "DQB6P7HTpPyNInfe";

$encrypted_message = openssl_encrypt($message_to_encrypt, $method, $secret_key, 0, $iv);

echo $encrypted_message;
echo ("<br>");

$encrypted_message = "1+ow8YocIlWHcdfdQzfvtg==";

$decrypted_message = openssl_decrypt($encrypted_message, $method, $secret_key, 0, $iv);

echo $decrypted_message;
?>
