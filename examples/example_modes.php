<?PHP
    require_once 'Stream/Var.php';
    stream_wrapper_register( "var", "Stream_Var" );

    $varname = 'var://_GET/foo';

    echo	"_GET variables:<pre>";
    print_r( $_GET );
    echo	"</pre>";

    echo    "<strong>open '$varname' with mode 'r':</strong><br>";
    echo    "This should fail, as the variable does not exist.<br>";
    $fp = fopen($varname, "r");
    fclose($fp);

    echo	"<br>_GET variables:<pre>";
    print_r( $_GET );
    echo	"</pre>";

    echo    "<br><strong>open '$varname' with mode 'w':</strong><br>";
    echo    "This should work, as the 'w' creates the variable.<br>";
    $fp = fopen($varname, "w");
    echo    "writing data to the variable.<br>";
    fwrite($fp, "This is a");
    fclose($fp);

    echo	"<br>_GET variables:<pre>";
    print_r( $_GET );
    echo	"</pre>";

    echo    "<br><strong>open '$varname' with mode 'a+':</strong><br>";
    echo    "This will append data to a variable or create.<br>";
    $fp = fopen($varname, "a+");
    echo    "writing data to the variable.<br>";
    fwrite($fp, " Test");
    fseek($fp, 0, SEEK_SET);
    $data = fgets($fp,200);
    echo    "read data: ".$data."<br>";
    fclose($fp);

    echo	"<br>_GET variables:<pre>";
    print_r( $_GET );
    echo	"</pre>";

    echo    "<br><strong>open '$varname' with mode 'x':</strong><br>";
    echo    "This should fail, as the variable does exist and 'x' wants to create a new file.<br>";
    $fp = fopen($varname, "x");
    fclose($fp);

    echo	"<br>_GET variables:<pre>";
    print_r( $_GET );
    echo	"</pre>";
?>
