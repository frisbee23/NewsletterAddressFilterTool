<!doctype html>
<html class="no-js" lang="">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title></title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="apple-touch-icon" href="apple-touch-icon.png">
        <!-- Place favicon.ico in the root directory -->

        <link rel="stylesheet" href="css/normalize.css">
        <link rel="stylesheet" href="css/main.css">
        <script src="js/vendor/modernizr-2.8.3.min.js"></script>
    </head>
    <body>
        <!--[if lt IE 8]>
            <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
        <![endif]-->
<?php 
$time_start = microtime(true); 

require 'auth.php';
require 'vendor/autoload.php';
require 'nav.php';
error_reporting (E_ALL);

//echo " <pre style='white-space:normal;'>";
// ------------------------- init -------------------
require 'config.php';
$database = new medoo([
    'database_type' => 'mysql',
    'database_name' => $config_database_name,
    'server' => $config_server,
    'username' => $config_username,
    'password' => $config_password,
    'charset' => 'utf8'
    ]);


// ------------------------- mainjoin -------------------
/*
*/
$props=[
	'uid',
	'email1',
	'email2',
	'anredename',
	'vorname',
	'name',
	'firma',
	'zusatz',
	'strasse',
	'plz',
	'ort',
	'region',
	'land',
	'tel',
	'notiz',
	'gender',
	'kontakt',
	'physisch',
	'newsletter',
	'sprache',
];
$categories=array ('band','branche','genre');

//echo "</pre> <br> <pre style='white-space:normal;'>";
//$x=$database->log(); print_r ($x[0]);
//echo "</pre> <br> rows: <pre >";
//echo preg_replace (['/SELECT/','/FROM/','/LEFT JOIN/','/ON/','/WHERE/'], ["SELECT\n","\n\nFROM\n","\n\nLEFT JOIN\n","\n\nON\n","\n\nWHERE\n"], $x[0]);

// ------------------------- get cats -------------------
//$data['band']=$database->select('band','*'); //print_r($database->error());
//$data['genre']=$database->select('genre','*'); //print_r($database->error());
//$data['branche']=$database->select('branche','*'); //print_r($database->error());

$data['symbols']['genre']=[
    1=>'♯', // acoustic
    2=>'★', //indie
    3=>'🔌', // electro
    4=>'☉', // pop
    5=>'⛤', // metal  ☥
    6=>'♕', // hiphop
    7=>'⚧', // LGBT
  ];
foreach ($categories as $cat)
{	
	$temp=$database->select($cat,'*');
	foreach ($temp as $t)
		$data[$cat][$t['id']]=$t[$cat];
}
unset ($temp);

//echo "<pre>"; echo "genres:\n"; print_r ($data['genre']); echo "</pre>";
//echo "<pre>"; echo "GET:\n"; print_r ($_GET); echo "</pre>";

$noprocess=false;
// ------------------------- gather get vars   -------------------
foreach ($_GET as $k => $val) 
{
	if ($k=='edit_cat')
		if (in_array ($val, $categories))
			    $getedit[$k]=$val;

	if ($k=='edit_id') // from link 'i want to edit this'
		if (preg_match('/^\d+$/', $val))
			$getedit['edit_id']=$val;

	if ($k=='edit_item') 
		if (preg_match('/^\S+$/', $val))
			$getedit['edit_item']=$val;
		else
		{
			$noprocess=true;
			echo 'item may not contain spaces, aborting!';
		}
}
//echo "<pre>"; echo 'gathered getedit:'; print_r ($getedit); echo "</pre>";
// ------------------------- process get actions   -------------------
if (!$noprocess)
{
	$columnname=@$getedit['edit_cat'];
	foreach ($_GET as $k => $val) 
	{
		if ($k=='action_del_id')
		{
			if (preg_match('/^\d+$/', $val))	
			{	
				$actionresult=$database->delete ($getedit['edit_cat'],
					['id' => $val]);
				$getedit['action_del_id']=$val;
			}
			else
				echo 'action_del_id was not numeric, aborted.';
			break;
		}

		if ($k=='action_new')
		{
			if ($val!='')
			{
				$columnname=$getedit['edit_cat'];
				$actionresult=$database->insert ($getedit['edit_cat'],
					[$columnname => $val]);
			}
			break;
		}
			
		if ($k=='action_edit_id') // from the form with new itemtext
		{
			if (preg_match('/^\d+$/', $val))	
			{	
				$actionresult=$database->update ($getedit['edit_cat'],
					[ $columnname=>$getedit['edit_item'] ],
					[ 'id'=>$val, ]);	
				$getedit['action_edit_id']=$val;
			}
			else
				echo 'edit_id was not numeric, aborted.';
			break;
		}

	}
}

//echo "<pre>"; echo 'processed getedit:'; print_r ($getedit); echo "</pre>";

// --------------------------get cat
if (!isset($getedit)) $getedit=['edit_cat'=>'band']; //default
$rows=$database->select($getedit['edit_cat'], '*');

// ------------------------- output cat form   -------------------
echo "<form method=\"get\" action=\"edit.php\">";
echo "<h2>choose category to edit</h2>";
echo "<select name=\"edit_cat\" size=\"3\">";
	foreach ($categories as $cat)
	{
	    $catstr='edit_'.$cat;
	    $displaytext=$cat;

	    $bit=@$getedit['edit_cat']==$cat? 'selected': '';
	    echo "<option $bit>$displaytext</option>";
	}
echo "</select><br>";
echo "<input type=\"submit\" value=\"choose\">";

// --------------------------- form modes -----------------------
if (	
	isset($getedit['action_edit_id'])or
	isset($getedit['action_del_id'])
) //
	echo "<div style=\"background: #88e;\">processed item, actionresult=$actionresult (when adding-new-item: its the new id; when updating/deleting: count of affected rows;)</div>";
else if (isset($getedit['edit_id'])) // user clicked to edit one item
{
	echo "<div style=\"background: #ee0;\">";
	echo "<h2>edit item</h2>";
	$columnname=$getedit['edit_cat'];
	$id=$getedit['edit_id'];
	// get current value via given id
	foreach ($rows as $r)
		if ($r['id']==$getedit['edit_id'])
			$currentval=$r[$columnname];
	echo "id:$id ";
	echo "<input type=\"hidden\" name=\"action_edit_id\" value=\"$id\"/>";
	echo "item:";
	echo "<input type=\"text\" name=\"edit_item\" value=\"$currentval\"/>";
	echo "</div>";
}
else // default, display 'new-item form'
{
	echo "<h2>add item to selected category</h2>";
	echo "<input type=\"text\" name=\"action_new\" />";
}

echo "<input type=\"submit\" value=\"submit\">";
echo "</form><br><br>";

// ------------------------ just read all addresses in --------------------

//print_r( $database->log() );
//print_r($database->error());

//echo "</pre> <br> rows: </pre >";
//echo "got ".count($rows)." records<br>\n";
// ---------------------------- EVA

// -------------------------- print the data -----------------------
$tdstyle="  style='padding: 4px'";
echo "<table border=1><tr>";
	echo "<th $tdstyle>id</th>";
	echo "<th $tdstyle>text</th>";
	echo "<th $tdstyle>action</th>";
echo "</tr>";
$colcount=0;
$columnname=$getedit['edit_cat'];
foreach ($rows as $row) 
{
   
  echo "<tr>";
  echo "<td $tdstyle>";
  echo $row['id'];
  echo "</td>";

  
  echo "<td $tdstyle>";
  echo $row[$columnname];
  echo "</td>";

  echo "<td $tdstyle>";
  echo "<a href=\"?edit_id=${row['id']}&edit_cat=${getedit['edit_cat']}\" style=\"text-decoration:none;\">🔧</a>&nbsp;&nbsp;";
  echo "<a href=\"?action_del_id=${row['id']}&edit_cat=${getedit['edit_cat']}\" style=\"text-decoration:none;\">☒</a>";

  echo "</td>";

  echo "</tr>";
  $colcount++;
}
echo "</table><br>";
//echo "printed $colcount columns";
$time_end = microtime(true);

//dividing with 60 will give the execution time in minutes other wise seconds
$execution_time = ($time_end - $time_start);

//execution time of the script
//echo '<b>Total Execution Time:</b> '.$execution_time.' seconds';
?>

        <script src="https://code.jquery.com/jquery-1.12.0.min.js"></script>
        <script>window.jQuery || document.write('<script src="js/vendor/jquery-1.12.0.min.js"><\/script>')</script>
        <script src="js/plugins.js"></script>
        <script src="js/main.js"></script>

    </body>
</html>
