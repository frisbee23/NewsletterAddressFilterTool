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
require 'auth.php';

require 'vendor/autoload.php';
require 'nav.php';
error_reporting (E_ALL);
$time_start = microtime(true); 

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
$rows=$database->select('main', 
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
      "uid" => 2, "LIMIT"=> 20
    ]
    );
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
//echo "<pre>"; echo "GET:\n"; print_r ($_GET); echo "</pre>";

// ------------------------- process get vars   -------------------
if (@$_GET['export']=='export')
  $doexport=true;
else
  $doexport=false;
if (@$_GET['not']=='on')
  $propnot=true;
else
  $propnot=false;

$getprops=[];
foreach (array_merge (['not'], $props) as $p)
{
	if (@$_GET[$p]!='') 
		$getprops[$p]=$_GET[$p]; 
}
//echo 'getprops'; print_r ($getprops);
foreach (['','not'] as $pol)
{
	foreach ($categories as $cat)
	{
//TODO	  $getcats[$pol.$cat]=[];
	  foreach ($_GET as $g => $ison) 
	  {
	    if (preg_match_all ("/^$pol$cat([0-9]+)/", $g, $matches))
		if ($ison=='on')
		      $getcats[$pol.$cat][$matches[1][0]]=$ison;
	  }
	}
}

//echo "<pre>"; echo 'getcats:'; @print_r ($getcats); echo "</pre>";
// ------------------------- output cat form   -------------------
// ------------------------- output cat form   -------------------
// ------------------------- output cat form   -------------------
echo "<form method=\"get\" action=\"index.php\">";

$notdummy=@$getprops['not']=='on'?'checked':'';

$floatstyle="style='float: left; padding:4px; border: 1px solid lightgrey;'";
echo "<div $floatstyle>";
echo "<h2>attribute</h2>";
  
echo "<input type=\"checkbox\" name=\"not\" $notdummy> <b>not</b><br/ >";
echo "<table border=0>";
foreach ($props as $p)
{
    echo "<tr><td style='padding: 4px'>";
//    echo "<input type=\"hidden\" name=\"$$p\">";
    $propdummy=@$getprops[$p];
    //echo "<div $floatstyle>$p<input type=\"input\" name=\"$p\" value=\"$propdummy\"></div>";
	if ($p=='gender')
	{
	  if (!isset ($getprops[$p])) $propdummy=NULL;
	  else $propdummy=intval ($propdummy);

	  echo "$p</td><td><select name='$p'>
		  <option value=''  ".($propdummy==NULL?"selected":"").">not set/all</option>
		  <option value='0' ".($propdummy===0?"selected":"").">Female</option>
		  <option value='1' ".($propdummy==1?"selected":"").">Male</option>
		  <option value='2' ".($propdummy==2?"selected":"").">Neither</option>
		</select>";
	}
	else
	{
	    echo "$p</td><td style='padding: 4px'><input type=\"input\" name=\"$p\" value=\"$propdummy\">";
	}
    echo "</td></tr>";

} 
echo "</table></div>";

echo "<div $floatstyle> <h2>kategorien</h2>";
foreach (['','not'] as $pol)
{
	foreach ($categories as $cat)
	{
    echo "<div $floatstyle>";
    //echo "<div $floatstyle><b>$pol $cat:</b></div>";
    if ($pol!='not')
    echo "<a href=\"edit.php?edit_cat=$cat\" style='text-decoration:none'>🔧</a>";
	  echo "<b>$pol $cat:</b><br>";
	  foreach ($data[$cat] as $catid=>$thecat)
	  {
	    $catstr=$pol.$cat;
	    $displaytext=$thecat;

	    $bit=@$getcats[$catstr][$catid]=='on' ? 'checked' : '';
	    // hidden checkboxes so unchecked stuff gets submitted too
	    //echo "<div $floatstyle><input type=\"hidden\" name=\"$catstr$catid\">";
	    echo "<input type=\"hidden\" name=\"$catstr$catid\">";
	    echo "<input type=\"checkbox\" name=\"$catstr$catid\" $bit> 
        <a href=\"?$catstr$catid=on\">$displaytext</a><br>";
	  }
    echo "</div>";
  }
}
echo "</div>";
	  echo "<br style='clear:both'>";
echo "<br style='clear:both'>";

echo "<input type=\"submit\" value=\"update filter\" name=\"update filter\" >";
echo "<br><br><input type=\"submit\" value=\"export\" name=\"export\" >&nbsp;";
echo "<button id=\"copyButton\">copy export to clipboard</button><br><br>";

echo "</form>";



//echo "<pre>";print_r ($getprops); echo "</pre>";

$where=['AND' => ['newsletter' => 1] ];
if (count ($getprops))
  foreach ($getprops as $k => $p)
    if ($k!='not')
      if ($propnot)
        $where['AND'][$k."[!~]"]=$p;
      else
        $where['AND'][$k."[~]"]=$p;

//$where["LIMIT"]=5000;
//echo "<pre>mergewhere: "; print_r($where); echo "</pre>";

//$rows=$database->select('main', '*', $where);
$rows=$database->select('main', [
'uid','email1','email2','anredename','firma','notiz','sprache'], $where);
//echo '<br>current mem usage,after select main: '.memory_get_usage ()."\n";

// read the tables----------------------
// read the tables----------------------
// read the tables----------------------
//echo "<small><b>query: </b><pre>".print_r($database->log(),true)."</pre></small>";
if ($database->error()[0]!=0)
  print_r($database->error());


echo "<h2>results:</h2>";
echo "<pre>legende: ♯ acoustic ★ indie 🔌electro ☉ pop ☥ metal  ♕ hiphop ⚧ LGBT </pre>";
////print_r( $rows );
$rowcount=count($rows);
/*
   [0] => Array
        (
          [uid] => 1
            [band_id] => 
            [band] => 
            [genre_id] => 1
            [genre] => Acoustic
            [branche_id] => 
            [branche] => 
          )*/
// ---------------------------- EVA
//print_r ($rows); die();
/*  [0] => Array
        (
            [uid] => 1
            [email1] => Email
            [email2] => 
            [anredename] => Anredename
            [vorname] => Vorname
            [name] => Name
            [firma] => Firma
            [zusatz] => Zusatz
            [strasse] => Strasse
            [plz] => PLZ
            [ort] => Ort
            [region] => Region
            [land] => Land
            [tel] => Tel
            [notiz] => Notitz
            [gender] => 1
            [kontakt] => 1
            [physisch] => 1
            [newsletter] => 1
            [sprache] => sprache
        )
*/
// ---------------------------- fill data array. process mysql output ------------
// ---------------------------- fill data array. process mysql output ------------
// ---------------------------- fill data array. process mysql output ------------
// ---------------------------- fill data array. process mysql output ------------
foreach ($rows as $row) // uid
{
	$data['main'][$row['uid']]=$row;
}
unset ($rows);

foreach ($categories as $cat)
{
//	echo "cat: $cat\n";
	$nm[$cat]=$database->select("nm_${cat}_main", '*', ['ORDER'=>'main_uid']);
//	echo "<br>current mem usage,after select nm_${cat}_main:".memory_get_usage ()."\n";

	foreach ($nm[$cat] as $c)
	{
//		echo "c: ${c['main_uid']} - ${c[$cat.'_id']}\n";
		// es gibt doppelte uid - cat like eintraege !! zb uid 227 - hier tun wir die nur hoechstens einmal (als index) speichern
		if (isset ($data['main'][$c['main_uid']]))
			$data['main'][$c['main_uid']][$cat][$c[$cat.'_id']]=true;
		// else ... if we filter the query to main, we get many entries here which we can't put to corresponding entries in data['main']
	}
	unset ($nm[$cat]);
//	echo "<br>current mem usage,after UNSET nm_${cat}_main:".memory_get_usage ()."\n";
}
//echo '<br>current mem usage: '.memory_get_usage ()."\n";
// -------------------------- print the data -----------------------
//echo "<pre>"; echo 'getcats:'; print_r ($getcats); echo "</pre>";

$skipdisplay=['vorname','name','zusatz','plz','ort','land','tel','strasse','region','gender','kontakt','physisch','newsletter'];
$tdstyle="style=\"padding:4px;\"";


if (!$doexport)
{
  echo "<table border=1><tr>";
  foreach ($props as $p)
  {
    if (in_array ($p, $skipdisplay)) 
      continue;
    else
      echo "<th $tdstyle>$p</th>";
  }
  foreach ($categories as $cat)
    echo "<th $tdstyle>$cat</th>";
  echo "</tr>";
}
else echo "<textarea id=\"copyTarget\" cols=\"100\" rows=\"25\">";
/////////////////// main loop
//
if (count(@$data['main'])<1) die('got no data from database, change the filter! aborting');
$colcount=0; 
foreach ($data['main'] as $uid=>$row) // uid
{
   
   //  echo "<td>"; print_r ($row); //continue;
   // echo "</td>"; echo "</tr>"; continue;
  // uid

	// wenn keine getcats positiver pol angehangt sind, dann des net checken
	// wenn getcats positiver pol angehangt sind,
		// mit false beginnen und true setzen sobald alle drin sind

	if (false and $uid>7031) 
	{	$debug=true;	
		print_r ($row);
	}
	else $debug=false;

	$printit=true;
	foreach ($categories as $cat)
	{
		unset ($cator);
		if ($debug) echo "checking $cat \n";
		if (isset ($getcats[$cat]))
		{
			if ($debug) echo "checking $cat\n";
			$cator=false;
			foreach ($getcats[$cat] as $cat2filter => $dummy)
			{
				if ($debug) echo "cat2filter: $cat2filter\n";
				if ($debug) echo "row[cat]: ".print_r ($row[$cat],true)."\n";
				if (isset ($row[$cat]) and 
					array_key_exists ($cat2filter, $row[$cat]))
				{
					if ($debug) echo "cator true\n";
					$cator=true;
					break;
				}
			}
		}
		if (isset ($cator) and $cator==false) 
		{
			if ($debug) echo "cator set and false\n";
			$printit=false;
			break;
		}
	}
	if ($debug) echo "printing uid $uid\n";
	
	// wenn keine getcats not pol  angehangt sind, nix tun
	// wenn getcats not pol angehakt sind, 
		// mit true beginnen und false setzen sobald einer drin ist
  //$row[cat]	

	/*
getcats:Array
(
    [notgenre] => Array
        (
            [5] => on
            [7] => on
        )

)*/
	foreach ($categories as $cat)
	{
		if (isset ($getcats['not'.$cat]))
		{
			foreach ($getcats['not'.$cat] as $cat2filter => $dummy)
				if (isset ($row[$cat]) and 
				array_key_exists ($cat2filter, $row[$cat]))
				{
					$printit=false;
					break 2;
				}
		}
	}

//////////////// decide to print, or goto next row
  if (!$printit) 
      continue;

  /////////////// print
      
  if (!$doexport)
    echo "<tr>";

  //debug
  if (!isset ($row['uid']))
  {
    if (!$doexport)
      echo "<td $tdstyle> ".print_r ($row, true)."</td>";
    continue;
  }

  foreach ($props as $p)
  {
    if (in_array ($p, $skipdisplay)) 
      continue;

    if (!$doexport)
      echo "<td $tdstyle>";

    if ($p=='uid')
    {
      $id=$row[$p];
      if (!$doexport)
        echo "<a href=\"editrelations.php?edit_id=$id\">$id</a>";
      else 
          echo $id;
    }
    else
    if ($p=='notiz' && $row[$p]!='')
    {
      if (strpos ($row[$p], 'http')===false)
        $row[$p]='http://'.$row[$p];
      
      if (!$doexport)
        echo "<a href=\"${row[$p]}\" alt=\"${row[$p]}\">☞</a>";	
    }
    else
        echo $row[$p];
    
    if (!$doexport)
      echo "</td>";
    else
      echo ";";
  }
  foreach ($categories as $cat)
  {
    if (!$doexport)
      echo "<td $tdstyle>";
    
    if (isset($row[$cat]))
      foreach ($row[$cat] as $c => $dummy)
      {
        if (!$doexport and $cat=='genre')
          echo $data['symbols']['genre'][$c]; 
        else
          echo $data[$cat][$c].", ";
      }
    
    if (!$doexport)
      echo "</td>";
    else
      echo ";";
  }


  if (!$doexport)
    echo "</tr>";
  else
    echo "\n";
  $colcount++;
} // end main loop

if (!$doexport)
{
  echo "</table></pre>";
  echo "got $rowcount from database, after filtering printed $colcount of these.<br>";
}
else 
  echo "</textarea><br>";
$time_end = microtime(true);

//dividing with 60 will give the execution time in minutes other wise seconds
$execution_time = ($time_end - $time_start);

//execution time of the script
echo '<b>Total Execution Time:</b> '.$execution_time.' seconds';
?>

        <script src="https://code.jquery.com/jquery-1.12.0.min.js"></script>
        <script>window.jQuery || document.write('<script src="js/vendor/jquery-1.12.0.min.js"><\/script>')</script>
        <script src="js/plugins.js"></script>
        <script src="js/main.js"></script>

<script>
//<![CDATA[

document.getElementById("copyButton").addEventListener("click", function() {
    copyToClipboard(document.getElementById("copyTarget"));
});

function copyToClipboard(elem) {
    // create hidden text element, if it doesn't already exist
    var targetId = "_hiddenCopyText_";
    var isInput = elem.tagName === "INPUT" || elem.tagName === "TEXTAREA";
    var origSelectionStart, origSelectionEnd;
    if (isInput) {
        // can just use the original source element for the selection and copy
        target = elem;
        origSelectionStart = elem.selectionStart;
        origSelectionEnd = elem.selectionEnd;
    } else {
        // must use a temporary form element for the selection and copy
        target = document.getElementById(targetId);
        if (!target) {
            var target = document.createElement("textarea");
            target.style.position = "absolute";
            target.style.left = "-9999px";
            target.style.top = "0";
            target.id = targetId;
            document.body.appendChild(target);
        }
        target.textContent = elem.textContent;
    }
    // select the content
    var currentFocus = document.activeElement;
    target.focus();
    target.setSelectionRange(0, target.value.length);
    
    // copy the selection
    var succeed;
    try {
        succeed = document.execCommand("copy");
    } catch(e) {
        succeed = false;
    }
    // restore original focus
    if (currentFocus && typeof currentFocus.focus === "function") {
        currentFocus.focus();
    }
    
    if (isInput) {
        // restore prior selection
        elem.setSelectionRange(origSelectionStart, origSelectionEnd);
    } else {
        // clear temporary content
        target.textContent = "";
    }
    return succeed;
}

//]]>
  </script>
    </body>
</html>
