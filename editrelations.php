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
    <!-- TODO keine freifelder bei diversen flags | TODO was ist kontakt ? -->
<br><br>
<?php 
require 'auth.php';
require 'nav.php';
$time_start = microtime(true); 

require 'vendor/autoload.php';
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


// ------------------------- get cats -------------------

$data['symbols']['genre']=[
    1=>'♯', // acoustic
    2=>'★', //indie
    3=>'🔌', // electro
    4=>'☉', // pop
    5=>'☥', // metal  ⛤
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
//echo "<pre>"; echo "GET:\n"; print_r ($_GET); //echo "</pre>";
//echo "<pre>"; 

$noprocess=false;
// ------------------------- gather get vars   -------------------
foreach ($_GET as $k => $val) 
{
	if ($k=='edit_id') // from link 'i want to edit this'
		if (preg_match('/^\d+$/', $val))
			$getedit['edit_id']=$val;


  if (preg_match('/^prop_(\S+)$/', $k, $matches))
      if ($k!='prop_uid')
				$getedit['props'][$matches[1]]=$val;

		if (preg_match('/^(\D+)(\d+)$/', $k, $matches))
		{
			if (in_array($matches[1], $categories))
				$getedit[$matches[1]][$matches[2]]=$val;
		}
}
if (@$_GET['action_edit_id']!=@$_GET['prop_uid']) die ('edit_id != props_id sanity check failed');
//echo "<pre>"; 
//echo 'gathered getedit:'; print_r ($getedit); //echo "</pre>";


// create/new == wie edit
// read/update/edit == 3 subsectionen fuer die cats-> 1 form
// delte wie edit


// ------------------------- process get actions   -------------------

$uid=false;
if (isset($_GET['action_edit_id']))
	$uid=$_GET['action_edit_id'];
else
	if (!isset ($getedit['edit_id'])) die ("no action_edit_id set or no edit_id set.aborting");

if (preg_match('/^\d+$/', $uid)) // from the form 'submit'
{
	echo "processing $uid\n";
	$getedit['edit_id']=$uid;

  // table main
  $database->update ("main", $getedit['props'], ['uid' => $uid]);

  // tables nm_{categories,...}_main
	foreach ($categories as $cat)
	{
		// alle loeschen, dann alle gesetzten neu setzen
		$database->delete ("nm_${cat}_main", ['main_uid' => $uid]);

		foreach ($getedit[$cat] as $itemid => $ison) 
		{
			if (!$ison) continue;

			$columnname=$cat.'_id';
			$database->insert ("nm_${cat}_main", ['main_uid' => $uid, $columnname => $itemid]);
			//echo ("insert into nm_${cat}_main :". 'main_uid' ."=> $uid, $columnname => $itemid\n");
		}
	}
}


//echo "<pre>"; echo 'processed getedit:'; print_r ($getedit); echo "</pre>";

$rows=$database->select('main','*', ['uid'=>$getedit['edit_id']]);

foreach ($categories as $cat)
{
	$temp=[];
	$temp=$database->select("nm_${cat}_main",'*', ['main_uid'=>$getedit['edit_id']]);

//	echo "$cat temp:".print_r ($temp, true);
	$data[$getedit['edit_id']][$cat]=[];
	foreach ($temp as $item)	
		$data[$getedit['edit_id']][$cat][$item[$cat.'_id']]=true;
}

// ------------------------ just read all addresses in --------------------

//print_r( $database->log() );
//print_r($database->error());
//print_r($database->error());

//echo "<pre> <br> rows:"; print_r ($rows); print_r ($data); echo " </pre >";
//echo "got ".count($rows)." records<br>\n";
// ---------------------------- EVA

// -------------------------- print the data -----------------------

echo "<form method=\"get\" action=\"editrelations.php\">";

$style="style=\"vertical-align: top; padding: 4px;\"";
$floatstyle="style='float: left; padding:4px; border: 1px solid lightgrey;'";
echo "<div $floatstyle> <table border=\"1\"><tr>";
//	foreach ($props as $p)
//		echo "<th $style>$p</th>";
//echo "</tr>";
$colcount=0;
//$columnname=$getedit['edit_cat'];
foreach ($rows as $row) 
{
   

  foreach ($props as $p)
  {
    echo "<tr>";
	  echo "<td $style>$p";
    echo "</td>";

	  echo "<td $style>";
		if ($p=='gender')
		{
		  echo "<select name='prop_$p'>
			  <option value=''  ".($row[$p]==NULL?"selected":"").">not set</option>
			  <option value='0' ".($row[$p]==0?"selected":"").">Female</option>
			  <option value='1' ".($row[$p]==1?"selected":"").">Male</option>
			  <option value='2' ".($row[$p]==2?"selected":"").">Neither</option>
			</select>";
		}
		else
		{
		  echo "<input type=\"input\" name=\"prop_$p\" value=\"${row[$p]}\">";
		}
	  echo "</td>";
    echo "</tr>";
  }

  $colcount++;
}
echo "</table></div>";
// ------------------------- output cat display&form   -------------------
//echo "<h2>editing main:</h2>";
$id=$getedit['edit_id'];
//echo "id:$id ";
echo "<table border=1 style=\"\"><tr>";
foreach ($categories as $cat)
{
	echo "<td $style>";
	echo "<h3>$cat</h3>";
	echo "<table border=\"0\">";
		foreach ($data[$cat] as $itemid => $item)
		{
			$bit=@$data[$id][$cat][$itemid]==true ? 'checked' : '';
      $name="$cat$itemid";
      @$symbol=$data['symbols'][$cat][$itemid];
			echo "<tr><td $style><input type=\"hidden\"  name=\"$name\">".
		             "<input type=\"checkbox\"  name=\"$name\" $bit></td>".
			     "<td $style>$symbol $item</td></tr>";
		}
	echo "</table>";
	echo "</td>";
}
echo "</tr><table>";
echo " <input type=\"hidden\"  name=\"action_edit_id\" value=\"${getedit['edit_id']}\">
<input type=\"submit\" value=\"submit\">";
echo "</form>";

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
<?php
/*$rows=$database->select('main',
    [
    "[>]nm_band_main" => ["uid" => "main_uid"],
    "[>]band" => ["nm_band_main.band_id" => "id"],

    "[>]nm_genre_main" => ["uid" => "main_uid"],
    "[>]genre" => ["nm_genre_main.genre_id" => "id"],

    "[>]nm_branche_main" => ["uid" => "main_uid"],
    "[>]branche" => ["nm_branche_main.branche_id" => "id"],
    ],
    [
//    "uid", "email1", "name", "plz", "ort", "region","land","notiz"
//"nm_band_main.band_id",
    "uid",
    "nm_band_main.band_id", "band.band",
    "nm_genre_main.genre_id", "genre.genre",
    "nm_branche_main.branche_id", "branche.branche",
    ],
    [
	"uid" =>	$getedit['edit_id']
    ]
    );
*/
?>
