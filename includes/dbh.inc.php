<?php

$serverName = "localhost";
$dBuserName = "root";
$dBPassword = "";
$dBName = "the";

$conn = mysqli_connect($serverName, $dBuserName, $dBPassword, $dBName);

if(!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}