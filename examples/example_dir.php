<?PHP
    require_once __DIR__ . '/../vendor/autoload.php';
    stream_wrapper_register( "var", "Stream_Var" );

    $dirname = 'var://_SERVER';
    $dir = opendir($dirname);
    echo    "<strong>opening directory '$dirname'</strong><br><br>";

    while ($entry = readdir($dir)) {
        echo "opening file $dirname/$entry<br>";
        if (!$fp = @fopen($dirname."/".$entry,"r")) {
            echo "seems to be a directory<br><br>";
            continue;
        }

        echo "reading from $entry<br>";
        while (!feof($fp)) {
            echo fread($fp, 16);
        }
        fclose($fp);
        echo    "<br><br>";
    }
    closedir($dir);
?>
