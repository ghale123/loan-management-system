<?php
require('connection.php');

if(isset($_POST['register_btn'])){
    $name=$_POST['name'];
    $email=$_POST['email'];
    $address=$_POST['address'];
    $pan=$_POST['pan'];
    $gender=$_POST['gender'];
    $profession=$_POST['profession'];
    $password=$_POST['password'];


    $insert_query="INSERT INTO user(user_name, address, gender, email, password, pan_no, profession) VALUES('$name', '$address','$gender', '$email', '$password', '$pan', '$profession')";
    $insert_data=mysqli_query($conn, $insert_query);
    if($insert_data){
        echo "successfull";
    }else{
        echo "failed".mysqli_error($conn);
    }
}
?>