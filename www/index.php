<?php
    require_once  'layouts/header.php';

    $pageData['title'] = 'АИС "МУРАВЕЙ"';
    $pagesGroup = ['index'];

    require_once  'layouts/head.php';

?>

    <style>
        .mainLogo {
            height: 270px;
            background-image: url(img/login-logo1.png);
            background-position: center;
            background-size: contain;
            background-repeat: no-repeat;
            margin: 80px auto 30px;
        }
        .indexTitle {
            font-size: 34px;
            font-family: RalewayB;
            margin: 0 auto;
            color: #a0a0a0;
            width: 60%;
            line-height: 60px;
            text-shadow: 2px 2px 3px #000000ab;
            cursor: default;
        }
    </style>

    <div class="mainLogo"></div>
    <div class="indexTitle no_select">
        АВТОМАТИЗИРОВАННАЯ ИНФОРМАЦИОННАЯ СИСТЕМА<br> УПРАВЛЕНИЯ СЛУЖЕБНОЙ НАГРУЗКОЙ "МУРАВЕЙ"
    </div>

<?php
    require_once 'layouts/footer.php';
?>
