<?php
include ("connection.php");

session_start();
if (isset($_POST['submit'])) {
    require ("user_register.php");
    $email = $_POST['email'];
    $password = $_POST['password'];
    //login to user dash
    $login_query = "SELECT * FROM user WHERE email='$email' AND password='$password'";
    $login_data = mysqli_query($conn, $login_query);
    //login to admin dahs
    $login_query = "SELECT * FROM admin WHERE admin_name='$email' AND password='$password'";
    $admin_data = mysqli_query($conn, $login_query);

    if (mysqli_num_rows($login_data) == 1) {
        $user = mysqli_fetch_assoc($login_data);
        $_SESSION['user_id'] = $user['user_id'];
        header("location: user_dashboard.php");
        exit();
    } else if (mysqli_num_rows($admin_data) == 1) {
        $admin = mysqli_fetch_assoc($admin_data);
        $_SESSION['admin_id'] = $admin['admin_id'];
        header("location: admin_dashboard.php");
        exit();
    } else {
        echo "<script>alert('incorrect email or password.')</script>";
    }
}
?>