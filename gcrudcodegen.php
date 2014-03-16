<?php

$class = isset($_POST['class'])?$_POST['class']:"";

if(!isset($_POST['host']) || !isset($_POST['user']) || !isset($_POST['pass']) || !isset($_POST['base']))
{
    print "Must enter all required data";
    exit;
}

$link = mysql_connect($_POST['host'], $_POST['user'], $_POST['pass']);

if (!$link) 
{
    echo 'Could not connect to database';
    exit;    
}
else
{
    mysql_select_db($_POST['base']);
}

$sql = "SHOW TABLES FROM ".$_POST['base'];
$resultado = mysql_query($sql);

if (!$resultado) {
    echo "Database error\n";
    echo 'MySQL error: ' . mysql_error();
    exit;
}

$menu = "";
$req_fields = "";
$columns = "";
$display_as = "";
$wclass = "";
$txtclass = "";

while ($table = mysql_fetch_row($resultado)) 
{
    // echo "Table: {$table[0]}\n";
    
    $res = mysql_query("SHOW COLUMNS FROM ".$table[0]);
    
    $menu.= "\t\t<a href='<?php echo site_url('".$class."/".strtolower($table[0])."')?>'>".strtoupper($table[0])."</a> |\n";
    
    if (!$res) 
    {
        echo "Failed to execute the query for the table: " .$table[0]. "<br>Error: ".mysql_error();
        exit;
    }
    
    if (mysql_num_rows($res) > 0) 
    {
        $wclass.= "\n\n\tpublic function ".$table[0]."()";
        $wclass.= "\n\t{";
        $wclass.= "\n\t\ttry";
        $wclass.= "\n\t\t{";
        $wclass.= "\n\t\t\t$"."crud = new grocery_CRUD();";
        $wclass.= "\n\t\t\t$"."crud->set_theme(null);";
        $wclass.= "\n\t\t\t$"."crud->set_table('".$table[0]."');";
        $wclass.= "\n\t\t\t$"."crud->set_subject('".ucfirst($table[0])."');";
        $req_fields.="\n\t\t\t$"."crud->required_fields(";
        $columns.="\t\t\t$"."crud->columns(";
        $i = 0;
        while ($field = mysql_fetch_assoc($res)) 
        {
            $req_fields.="'".$field['Field']."'";
            $columns.="'".$field['Field']."'";   
            if($i == mysql_num_rows($res)-1)
            {
                $req_fields.=");\n";
                $columns.=");\n";
            }
            else
            {
                $req_fields.=",";
                $columns.=",";
            }
            
            $display_as.="\t\t\t // $"."crud->display_as('".$field['Field']."','');\n";
            
            $i++;
        }
            
        $wclass.=$req_fields;
        $wclass.=$columns;
        $wclass.=$display_as;

        $req_fields = "";
        $columns = "";
        $display_as = "";
        
        $wclass.="\t\t\t$"."output=$"."crud->render();\n";
        $wclass.="\t\t\t$"."this->_example_output($"."output);";
        $wclass.="\n\t\t}";
        $wclass.="\n\t\tcatch(Exception $"."e)";
        $wclass.="\n\t\t{";
        $wclass.="\n\t\t\tshow_error($"."e->getMessage().' --- '.$"."e->getTraceAsString());";
        $wclass.="\n\t\t}\n";
    
        $txtclass.=$wclass;

        $wclass = "";
    }
    
    $txtclass.="\t}";

}

// print "<pre>".$txtclass."</pre>";


// Write a menu

$fview = "references/default_view.php";

if($f = fopen($fview,"rb"))
{
    $txtview = fread($f,filesize($fview));
    fclose($f);
}

$txtview = str_replace("<!--~menu~-->",$menu,$txtview);

$newfview = "results/application/views/".$class.".php";

$fw = fopen($newfview, "w");
fwrite($fw, $txtview);
fclose($fw);

// Write a classes

$fcontroller = "references/default_controller.php";

if($f = fopen($fcontroller,"rb"))
{
    $txtcontroller = fread($f,filesize($fcontroller));
    fclose($f);
}


$txtcontroller = str_replace("~classname~",$class,$txtcontroller);
$txtcontroller = str_replace("//~classes~",$txtclass,$txtcontroller);

$newfcontroller = "results/application/controllers/".$class.".php";

$fw = fopen($newfcontroller, "w");
fwrite($fw, $txtcontroller);
fclose($fw);

if(file_exists($newfview) && file_exists($newfcontroller))
{
    print "Successfully generated files in:<br>".$newfview."<br>".$newfcontroller;
}
else
{
    print "Failed to execute the process";
}

mysql_free_result($res);

mysql_free_result($resultado);

?>