<?php
    require_once  __DIR__ . '/layouts/header.php';

    $pageData['title'] = 'Авторизация';
    $pagesGroup = ['login'];

    use OsT\Base\Message;
use OsT\Base\System;
    use OsT\User;

    if (isset($_POST['submit'])) {
        if (User::login($_POST['login'], $_POST['pass'])) {
            System::location('index.php');
            exit;
        } else {
            new Message('Проверьте корректность ввода логина и пароля', Message::TYPE_WARNING);
            System::location('login.php');
            exit;
        }
    }

    if (isset($_GET['logout'])) {
        User::logout();
        System::location('login.php');
        exit;
    }

    require_once __DIR__ . '/layouts/head.php';

?>

<style>
    .form{
        margin: 20px auto;
        display: inline-block;
    }
    .mainLogo {
        height: 270px;
        background-image: url(img/login-logo1.png);
        background-position: center;
        background-size: contain;
        background-repeat: no-repeat;
        margin: 80px auto 30px;
    }
    .loginTitle{
        font-size: 32px;
        font-family: RalewayB, sans-serif;
        color: #808080;
        cursor: default;
    }
    .form .input {
        width: 260px;
        font-family: RalewayB, sans-serif;
        padding: 5px 10px;
        margin: 0 0 18px;
        font-size: 16px;
    }
    .form .submit {
        font-size: 19px;
        font-family: RalewayB, sans-serif;
        color: #808080;
        cursor: pointer;
        width: 284px;
        padding: 5px 0;
    }
</style>
    <div class="mainLogo"></div>

    <div class="loginTitle">АВТОРИЗАЦИЯ</div>

    <form class="form" action="<?php echo $_SERVER [ 'PHP_SELF' ]; ?>" method="post" enctype="multipart/form-data">
        <input class="input" type="text" name="login" placeholder="Логин"><br>
        <input class="input" type="password" name="pass" placeholder="Пароль"><br>
        <input class="submit" type="submit" name="submit" value="ВОЙТИ">
    </form>

<?php
    require_once __DIR__ . '/layouts/footer.php';
?>

