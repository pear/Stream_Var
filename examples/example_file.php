<?PHP
    require_once 'Stream/Var.php';
    stream_wrapper_register( "var", "Stream_Var" );

    $myVar = array(
                    "foo" => "bar",
                    "argh" => "I really like tomatoes."
                   );

    echo    "myVar:";
    echo	"<pre>";
    print_r( $myVar );
    echo	"</pre>";

    echo    "<strong>open 'var://GLOBALS/myVar/argh'</strong><br>";
    if ($fp = fopen('var://GLOBALS/myVar/argh','r+')) {
        echo    "success!<br>";

        $data = fread($fp, 9);
        echo    "reading 9 chars from stream: $data<br>";

        echo "write 'hate' to stream.<br>";
        fwrite($fp,"hate");

        fclose($fp);
    }
    echo    "<br>";
    echo    "myVar after fwrite:";
    echo	"<pre>";
    print_r( $myVar );
    echo	"</pre>";
?>
