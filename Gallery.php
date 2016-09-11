<?php

class Gallery
{

    /**
     * Gallery folder path.
     *
     * @var string
     */
    public $galleryFolder;

    /**
     * Current image hash.
     *
     * @var string
     */
    private $currentImageHash;


    /**
     * Image not sorted
     *
     * @var array
     */
    public $notSortedImage = array();

    /**
     * Sorted folder path.
     *
     * @var array
     */
    public $ignoredFolders = array('.', '..', '.idea');

    /**
     * Images formats.
     *
     * @var array
     */
    public $formats = array(
        1 => 'gif',
        2 => 'jpeg',
        3 => 'png'
    );

    /**
     * Duplicates images.
     *
     * @var array
     */
    public $duplicates = array();


    /**
     * Image count.
     *
     * @var int
     */
    public $imageCount;

    /**
     * @param  string $galleryFolder
     * @construct
     */
    public function __construct($galleryFolder, $imageCount)
    {
        $this->galleryFolder = $galleryFolder;
        $this->imageCount = $imageCount;
        $this->run();
    }


    public function run(){
        echo "<h2 align='center'>Все картинки в папке ".$this->galleryFolder."</h2>";
        $this->sortImages();
        $this->echoImage();
        echo "<hr />";
        echo "<h2 align='center'>".$this->imageCount." Найменее похожих картинок</h2>";
        $this->createArrUniqueImage();
        $this->createMainArrImage();
    }

    /**
     * Sort images.
     *
     * @param string|bool $dir
     * @return void
     */
    public function sortImages($dir = false)
    {
        if (!$dir) {
            $dir = $this->galleryFolder;
        }
        if (is_dir($dir)) {
            $dir = (substr($dir, -1) === '/') ? $dir : $dir . '/';
            $list = scandir($dir);
            $list = array_values(array_diff($list, $this->ignoredFolders));
            if ($list) {
                $listCount = count($list);
                for ($i = 0; $i <= $listCount - 1; $i++) {
                    $path = $dir . $list[$i];
                    if (is_dir($path)) {
                        $this->sortImages($path);
                    } else {
                        $this->moveImage($path);
                    }
                }
            } else {
                echo "<div><p>" . $dir . " - is empty.</p></div>";
            }
        } else {
            echo "<div><p>Bad directory path: " . $dir . "</p></div>";
        }
    }


    /**
     * Move image.
     *
     * @param string $path
     * @return array|bool
     */
    private function moveImage($path) {
        $image = $this->isImage($path);
        if ($image){
            $minImagePath = $this->resizeImage($path);
            $blackWhiteMinImagePath = $this->convertImageToBlackWhite($minImagePath);
            $this->currentImageHash = $this->getPerceptHash($blackWhiteMinImagePath);
            $this->notSortedImage = array_merge($this->notSortedImage, array($path => $this->currentImageHash));
        }
    }


    public function createArrUniqueImage(){
        //( $this->notSortedImage ["путь картинки" =>"хеш на похожесть - 64 символа"])

        $test = array();
        $count = 0;
        foreach($this->notSortedImage as $main_path => $main_hash){
            foreach($this->notSortedImage as $path => $hash){
                if($main_path != $path){
                    similar_text($main_hash, $hash, $percent);
                    if ($percent >= 76) {
                        // формирую масив похожих фотографий
                        $test[$count][$main_path] = array($path => $percent);
                        $count++;
                    }
                }
            }
        }
        $this->duplicates = array_merge($this->duplicates, $test);
    }

    public function createMainArrImage()
    {
        // формирую масив с количеством одинаковых путей картинки
        $result = array();
        $count = count($this->duplicates);
        for ($i = 0; $i <= $count - 1; $i++) {
            foreach ($this->duplicates[$i] as $key => $value) {
                $result[] = $key;
            }
        }
        // полючаю количество сходств  картинки
        $test = array_count_values($result);
        // сливаю 2 масива (тот который $this->notSortedImage и масив с количеством сходств)
        // так как ключи не могут повторяться - значения(хеш) в соответствующих полях изменяться на количество сходств
        $mixed_array = array_merge($this->notSortedImage, $test);
        $createArrayMain = array();
        // формирую массив где ключ - путь картинки а значение количество сходств
        foreach ($mixed_array as $path => $val) {
            if (!is_string($val)) {
                $createArrayMain = array_merge($createArrayMain, array($path => $val));
            } else {
                $createArrayMain = array_merge($createArrayMain, array($path => 0));
            }
        }

        // сортирую  массив по возростанию и обрезаю для вывода найменее похожих картинок
        asort($createArrayMain);

        $full_array = array_slice($createArrayMain, 0, $this->imageCount, true);
        $this->echoMinSimilarImage($full_array);
//        var_dump($createArrayMain);
    }


    public function echoMinSimilarImage($full_array){

        // вывод найменее похожих картинок
        $string = "<style>
        .other_image img{
            width: 5% !important;
            height: 5% !important;
        }</style><div class='other_image'>";
        foreach ($full_array as $path => $val){
            $string .= "<img src='$path'>";
        }
        $string .= "</div>";

        echo $string;

    }



    /**
     * Check is image.
     *
     * @param string $path
     * @return array|bool
     */
    private function isImage($path) {
        $image = @getimagesize($path);
        if (!$image) {
            return false;
        } else if (!in_array($image[2], array(1, 2, 3))) {
            return false;
        } else {
            return $image;
        }
    }


    /**
     * Get image name from path.
     *
     * @param string $path
     * @return string
     */
    private function getImageNameFromPath($path) {
        return substr($path, strrpos($path, '/') + 1);
    }

    /**
     * Resize image to min(16x16).
     *
     * @param string $path
     * @return string
     */
    private function resizeImage($path) {
        $image_to = 'temp_folder/min' . $this->getImageNameFromPath($path);
        $width = $height = 16;
        $image_vars = getimagesize($path);
        $src_width = $image_vars[0];
        $src_height = $image_vars[1];
        $src_type = $image_vars[2];
        $width = ($width > $src_width) ? $src_width : $width;
        $height = ($height > $src_height) ? $src_height : $height;

        $src_image = call_user_func("imagecreatefrom{$this->formats[$src_type]}", $path);
        $dest_image = imagecreatetruecolor($width, $height);
        imagecopyresized($dest_image, $src_image, 0, 0, 0, 0, $width, $height, $src_width, $src_height);
        call_user_func("image{$this->formats[$src_type]}", $dest_image, $image_to);

        return $image_to;
    }

    /**
     * Convert min image to black-white.
     *
     * @param string $path
     * @return string
     */
    private function convertImageToBlackWhite($path) {
        $resultPath = 'temp_folder/black-white-' . $this->getImageNameFromPath($path);
        // получаем размеры исходного изображения
        $imgData = getimagesize($path);
        $width = $imgData[0];
        $height = $imgData[1];
        $formatKey = $imgData[2];
        // создаем новое изображение
        $newImg = imageCreate($width, $height);
        // задаем серую палитру для нового изображения
        for ($color = 0; $color <= 255; $color++) {
            imageColorAllocate($newImg, $color, $color, $color);
        }
        // создаем изображение из исходного
        $image = call_user_func("imagecreatefrom{$this->formats[$formatKey]}", $path);
        imageCopyMerge($newImg, $image, 0, 0, 0, 0, $width, $height, 100);
        call_user_func("image{$this->formats[$formatKey]}", $newImg, $resultPath);
        imagedestroy($newImg);
        unlink($path);

        return $resultPath;
    }

    /**
     * Get percept hash.
     *
     * @param string $path
     * @return string
     */
    private function getPerceptHash($path) {

        $e = getimagesize($path);
        $image = call_user_func("imagecreatefrom{$this->formats[$e[2]]}", $path);

        //В первом проходе считаем сумму, которую потом делим на 256 (16*16), чтобы получить среднее значение
        $summ = 0;
        for ($i = 0; $i < 8; $i++) {
            for ($j = 0; $j < 8; $j++) {
                $summ += imagecolorat($image, $i, $j);
            }
        }
        $sred = $summ / 64;
        //При втором проходе сравниваем значение цвета каждой точки со средним значением и записываем в результат 0 или 1
        $str = '';
        for ($i = 0; $i < 8; $i++) {
            for ($j = 0; $j < 8; $j++) {
                if (imagecolorat($image, $i, $j) >= $sred) {
                    $str .= '1';
                } else {
                    $str .= '0';
                }
            }
        }
        //Переводим в 16-ю систему, для удобства
//        $hash = base_convert($str, 2, 16);
        unlink($path);

        return $str;
    }

    public function echoImage(){
        $string = "<style>
.other_image img{
    width: 5% !important;
    height: 5% !important;
}
</style><div class='other_image'>";
        foreach ($this->notSortedImage as $path => $hash) {
            $string .= "<img width='5%' height='5%' src='$path'/>";
        }
        $string .= "</div>";
        echo $string;
    }

}