<?php
$url = "https://maps.googleapis.com/maps/api/place/autocomplete/json?";
 
$fields_string = "";
//url-ify the data for the POST
foreach($_GET as $key=>$value) {
    $fields_string .= $key.'='. urlencode( $value ) .'&';
}

$fields_string .= "sensor=false&radius=1000&key=AIzaSyAjwCd4DzOM_sQsR7JyXMhA60vEfRXRT-Y";
//open connection
$ch = curl_init();
 
//set the url, number of POST vars, POST data
curl_setopt($ch,CURLOPT_URL,$url.$fields_string);
 
//execute post
$result = curl_exec($ch);
 
//close connection
?>