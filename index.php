<?php
$quality = [85,60,45];
$categories = [];
$images = [];

$dir = './';
if (is_dir($dir)) {
    if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
            if (is_dir($file) && !stristr($file, '.')) {

                $categories[] = $file;
            }
        }
        closedir($dh);
    }
}

if( isset($_GET['generate']) ) {

    foreach( $categories as $index => $file ) {

        // Max exec time fix
        //if($index < 6)
        //    continue;

        // Retina
        $thumb = new Imagick();
        $thumb->readImage($file . "/header.jpg");
        processImage($thumb, $file, '@2x');

        // Normal
        $thumb = new Imagick();
        $thumb->readImage($file . "/header.jpg");
        $thumb->resizeImage(1920, 644, Imagick::FILTER_LANCZOS, 1);
        processImage($thumb, $file, 'no-sample', false);

        // Normal
        $thumb = new Imagick();
        $thumb->readImage($file . "/header.jpg");
        $thumb->resizeImage(1920, 644, Imagick::FILTER_LANCZOS, 1);
        processImage($thumb, $file);
    }

    die( 'generated' );
}

function processImage(&$thumb, $file, $retina='', $compressColor = true) {
    global $quality;

    foreach( $quality as $q ) {
        $filename = $file . "/img-". $q . $retina .".jpg";

        // Strip exif data etc.
        $thumb->stripImage();

        // Enable progressive images
        $thumb->setInterlaceScheme(Imagick::INTERLACE_PLANE);

        // Remove unneeded color information
        // See: http://diywpblog.com/optimizing-large-images-for-maxium-quality-and-performance/
        // And: http://users.wfu.edu/matthews/misc/jpg_vs_gif/JpgCompTest/JpgChromaSub.html
        if($compressColor)
            $thumb->setImageProperty('jpeg:sampling-factor', '4:2:0');

        if($q != 100)
            $thumb->setImageCompressionQuality($q);

        $thumb->writeImage($filename);
    }

    $thumb->clear();
    $thumb->destroy();
}

function human_filesize($bytes, $decimals = 2) {
    $sz = 'BKMGTP';
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
}

$c = (isset($_GET['c'])) ? $_GET['c'] : 'Belgie';
$q = (isset($_GET['q'])) ? $_GET['q'] : 100;
?>
<html>
<head>
    <title>Retina compression test</title>
    <style>
        html,body {
            width: 100%;
            height: 100%;
            padding: 0;
            margin: 0;
        }
        #image {
            position: absolute;
            top: 0;
            width: 100%;
            height: 100%;
            background-position: top center;
            background-repeat: no-repeat;
            background-image: url("<?=$c;?>/img-<?=$q;?>.jpg");
            background-size: contain;
            max-height: 644px;
        }

        .links {
            font-size: 14px;
            position: absolute;
        }

        a {
            float: left;
            width: 180px;

        }

        @media only screen and (-webkit-min-device-pixel-ratio: 1.5), only screen and (min--moz-device-pixel-ratio: 1.5), only screen and (min-device-pixel-ratio: 1.5) {
            #image {
                background-image: url("<?=$c;?>/img-<?=$q;?>@2x.jpg");
                background-size: 1920px 644px;
            }
        }
    </style>
</head>
<body>
<div id="image"></div>
<?php
foreach( $categories as $index => $c ) {
    ?>
<div class="links" style="bottom: <?=$index;?>em">
    <?php
    echo '<a href="" onclick="document.getElementById(\'image\').style.backgroundImage = \'url('.$c . '/header.jpg)\';return false;">original ('.human_filesize(filesize( $c . '/header.jpg' )).')</a>';

    foreach ($quality as $q) {
        echo '<a href="?q=' . $q . '&c=' . $c . '">' . $c . ' ' . $q . '% ('.human_filesize(filesize( $c . '/img-'.$q.'.jpg' )).')</a>';
    }
    echo '</div>';
}
?>
</body>
</html>