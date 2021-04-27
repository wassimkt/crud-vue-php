<?php
require __DIR__ . '/vendor/autoload.php';

use Nowakowskir\JWT\JWT;
use Nowakowskir\JWT\TokenDecoded;
use Nowakowskir\JWT\TokenEncoded;


// recuperer l'access token envoyé 
function getBearerToken()
{

    $headers = getallheaders();

    

    if (!empty($headers["Authorization"])) {

        if (preg_match('/Bearer\s(\S+)/', $headers["Authorization"], $matches)) {
            return $matches[1];
            
        }

    } else {
        echo ("failed");
    }
    return null;
}


$token = getBearerToken();

if (!isset($token)) {
    http_response_code(400);
    die("no token ");
}
//conversion du header of token en json
$tokenEncoded = new TokenEncoded(getBearerToken());
$header = $tokenEncoded->decode()->getHeader();

//conversion du payload du token 
$user = $tokenEncoded->decode()->getPayload();




//la connexion a la base de données 


$conn = new mysqli("localhost", "root", "", "crud-vue");
if ($conn->connect_error) {

    die("connection Failed !" . $conn->connect_error);
}

$token = base64_decode(getBearerToken());

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL,  'http://localhost:8080/auth/realms/keycloak-vue-php/protocol/openid-connect/userinfo');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');


$headers = array();
$headers[] = 'Authorization: Bearer ' . getBearerToken();
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$res = json_decode(curl_exec($ch));

if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
}
if (!empty($res->error)) {
    die($res->error);
}

curl_close($ch);

$action = '';
if (isset($_GET['action'])) {
    $action = $_GET['action'];
}

            //fonction "read" pour afficher les données des clients 

                            //debut
if ($action == 'read') {


    $role = ($user["realm_access"])["roles"];
    if (!in_array("admin", $role)) {
        die("not authorized");
    }






    $sql = $conn->query("select *from users");
    $users = array();
    while ($row = $sql->fetch_assoc()) {
        array_push($users, $row);
    }
    $result['users'] = $users;
}
                                //fin

            //fonction "create" pour la creation et l'ajout d'un client dans la BD 

                                //Debut

if ($action == 'create') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    $sql = $conn->query("INSERT INTO users (name,email,phone) VALUES 
    ('$name','$email','$phone')");


    if ($sql) {
        $result['message'] = "User added succesfully!";
    } else {
        $result['error'] = true;

        $result['message'] = "Failed to add user !";
    }
}
                                //fin   

            //la fonction "update" pour editer un client 

                                //debut
if ($action == 'update') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    $sql = $conn->query("UPDATE users SET name ='$name',email='$email',phone='$phone' WHERE id='$id'");

    if ($sql) {
        $result['message'] = "User updated succesfully!";
    } else {
        $result['error'] = true;
        $result['message'] = "Failed to update user !";
    }
}
                                //fin 

            //la fonction "delete" pour la suppression d'un client 

                                //debut
if ($action == 'delete') {
    $id = $_POST['id'];

    $sql = $conn->query("DELETE FROM users WHERE id='$id'");

    if ($sql) {
        $result['message'] = "User deleted succesfully!";
    } else {
        $result['error'] = true;
        $result['message'] = "Failed to delete user !";
    }
}
                                //fin 

$conn->close();
echo json_encode($result);
