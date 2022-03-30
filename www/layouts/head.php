<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta http-equiv="Cache-Control" content="private">

 		<meta charset="utf-8" />
        <meta name="description" content="<?php echo $pageData['description'];?>">
        <meta name="keywords" content="<?php echo $pageData['keywords'];?>">
        <title><?php echo $pageData['title'];?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
        <link rel="shortcut icon" href="<?php echo $pageData['shortcuticon'];?>"/>
        <link rel="stylesheet" href="css/main.css">
        <script src="js/jquery-3.3.1.min.js"></script>
        <script src="js/main.js"></script>

        <script src="js/toast/toast.js"></script>
        <link rel="stylesheet" href="js/toast/toast.css">

    </head>
    
    <body>
        <div class="pace-background"></div>
        <?php 
            require_once('headNav.php');
        ?>

        <div class="content">