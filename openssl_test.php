<?php
error_reporting(E_ALL);
function gen($bits){
  echo "Attempt bits=$bits\n";
  $cfg=[
    'private_key_bits'=>$bits,
    'private_key_type'=>OPENSSL_KEYTYPE_RSA,
    'digest_alg'=>'sha256'
  ];
  $res=openssl_pkey_new($cfg);
  if(!$res){
    echo "FAIL generating key ($bits)"."\n";
    while($e=openssl_error_string()){ echo $e."\n"; }
    return;
  }
  if(!openssl_pkey_export($res,$priv)){
    echo "EXPORT FAIL ($bits)"."\n";
    while($e=openssl_error_string()){ echo $e."\n"; }
    return;
  }
  $det=openssl_pkey_get_details($res);
  echo "OK bits=$bits pubLen=".strlen($det['key'])." privLen=".strlen($priv)."\n";
}

gen(4096);
gen(3072);
gen(2048);
