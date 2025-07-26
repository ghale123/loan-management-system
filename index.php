<?php
require ("user_register.php");
require ("login.php");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login page</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap" />
    <script src="https://kit.fontawesome.com/e9ffc8e955.js" crossorigin="anonymous"></script>
    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>

</head>

<body>
    <div class="wrapper hide">
        <div class="form_container_register ">
            <div class="form_wrapper">
                <h2>Login Form</h2>
                <form action="" method="post">
                    <div class="fields">
                        <div class="input_fields">
                            <label>Fullname</label>
                            <input type="text" name="name" placeholder="Enter your name" required>

                        </div>
                        <div class="input_fields">
                            <label>Email</label>
                            <input type="text" name="email" placeholder="Enter your email" required>

                        </div>
                        <div class="input_fields">
                            <label>Password</label>
                            <input type="password" name="password" placeholder="Enter your password" required>
                        </div>
                        <div class="input_fields">
                            <label>Address</label>
                            <input type="text" name="address" placeholder="Enter your address" required>
                        </div>
                        <div class="input_fields">
                            <label>Pan No.</label>
                            <input type="number" name="pan" placeholder="Enter your pan number" required>
                        </div>
                        <div class="input_fields">
                            <label>Gender</label>
                            <select name="gender" required>
                                <option selected disabled>select your gender</option>
                                <option>Male</option>
                                <option>Female</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="input_fields">
                            <label>Profession</label>
                            <input type="text" name="profession" placeholder="Enter your profession" required>
                        </div>
                    </div>
                    <div class="submit_btn">
                        <button type="submit" name="register_btn">Register</button>
                    </div>
                    <div class="link">
                        <p>Already Have account?<a href="#" onclick="loginPopup()">Login</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- register form -->
    <div class="form_container ">
        <div class="form_wrapper">
            <h2>Login Form</h2>
            <form action="" method="post">
                <div class="fields">
                    <div class="input_fields">
                        <label>Email</label>
                        <input type="text" name="email" placeholder="Enter your email" required>

                    </div>
                    <div class="input_fields">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Enter your password" required>

                    </div>
                </div>
                <div class="submit_btn">
                    <button type="submit" name="submit">Login</button>
                </div>
                <div class="link">
                    <p>Don't Have account?<a href="#" onclick="registerPopup()">Create New Account</a></p>
                </div>
            </form>
        </div>
    </div>

    <script>
        function loginPopup() {
            document.querySelector(".form_container").classList.remove("hide");
            document.querySelector(".wrapper").classList.add("hide");

        }
        function registerPopup() {
            document.querySelector(".form_container").classList.add("hide");
            document.querySelector(".wrapper").classList.remove("hide");

        }
    </script>
</body>

</html>