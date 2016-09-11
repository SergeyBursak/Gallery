<?php
require("Gallery.php");
?>
<!DOCTYPE html>
<html>
<head lang="eng">
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="css/styles.css">
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.min.js"></script>
    <title>Gallery</title>
</head>
<body>

<?php

//создаю обьект Gallery где первый параметр - ето путь к папке где храняться все картинки.
// и второй параметр (число) - ето количество найменее похожих картинок которые выведутся на екран
if(isset($_POST['go'])){
    $a = new Gallery("not_sorted/", trim(1*$_POST['num']));
}else{
    $a = new Gallery("not_sorted/", 5);
}
?>


<form action="index.php" method="post">
    <h4>Выберите число найменее похожих фотографий</h4>
    <label for="int">Введите число </label><input type="number" name="num" min="1" step="1" max="1000" id="int"><br>
    <input class="buttonGreen longWidth" type="submit" name="go" value="Изменить">
</form>



</body>
</html>





