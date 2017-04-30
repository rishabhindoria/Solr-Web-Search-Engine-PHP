<?php
include 'SpellCorrector.php';
include 'simple_html_dom.php';
header("Access-Control-Allow-Origin: *");
header('Content-Type: text/html; charset=utf-8');

$limit = 10;
$query = isset($_REQUEST['search']) ? $_REQUEST['search'] : false;
$results = false;

if ($query)
{
  require_once('Apache/Solr/Service.php');
  $solr = new Apache_Solr_Service('localhost', 8983, '/solr/rishabh_example');
  if (get_magic_quotes_gpc() == 1)
  {
    $query = stripslashes($query);
  }
  try
  {
    if ($_GET['choice']=="lucene"){
        $additionalParameters = array(
            'fl' => array('id','description','title')
    );
  }
  else{
    $additionalParameters = array(
        'fl' => array('id','description','title'),
        'sort'=>"pageRankFile desc"
    );
  }
    $words = preg_split("/\s+/", $query);
    $result = "";
    $correction = 0;
    foreach ($words as $value) {
        $correct = SpellCorrector::correct($value);
		if( $correct != $value)
			$correction = 1;
        $result = $result . $correct . " ";
    }
	$result=trim($result," ");
	$results = $solr->search($result, 0, 10, $additionalParameters);
  }
  catch (Exception $e)
  {
    die("<html><head><title>SEARCH EXCEPTION</title><body><pre>{$e->__toString()}</pre></body></html>");
  }

function getFileURL($input)
{
    $res="";
    if (($handle = fopen("/home/rishabh/Downloads/solr-6.5.0/mapNBCNewsDataFile.csv", "r")) !== FALSE) {
        while (($data = fgetcsv($handle)) !== FALSE) 
		{
			if($data[0] == $input)
            {
                $res=$data[1];
                break;
            }
        }
        fclose($handle);
        return $res;
	}
}
}

?>
<html>
<title>PHP Solr</title>
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script>
$(document).ready(function() {
	$( "#search" ).autocomplete({
		source: function( request, response ) {
			var search_term=$.trim($("#search").val().toLowerCase());
			var start_term="";
			var index=search_term.lastIndexOf(" ");
			if(index!=-1)
			{
				start_term=search_term.substring(0,index);
				search_term=search_term.substring(index+1);
			}
			var myurl = 'http://localhost:8983/solr/rishabh_example/suggest?indent=on&q='+search_term+'&wt=json';
			$.ajax({
				url: 'proxy.php',
				type: 'GET',
				dataType: 'json',
				data: {
					address: myurl
				},
				success: function(data){
					var output=[];					
					data.suggest.suggest[search_term].suggestions.forEach(function(x){
						output.push(start_term + " " + x.term);
					});
					response(output);
				}
			});
		},
      minLength: 2,
    });
});
</script>
<body>
<form  accept-charset="utf-8" method="get">
<div align="center">
<input type="text" id="search" name="search" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/>
<input type="radio" name="choice" value="lucene" <?php if(isset($_GET['choice'])&&$_GET['choice']=="lucene") echo "checked";?>>Lucene
<input type="radio" name="choice" value="pagerank" <?php if(isset($_GET['choice'])&&$_GET['choice']=="pagerank") echo "checked";?> >PageRank
<input type="submit" value="Search" id="go"/>
</div>
</form>
</body>
<?php
if ($results)
{
  $total = (int) $results->response->numFound;
  $start = min(1, $total);
  $end = min($limit, $total);
  if($correction == 1)
  {
	echo "Showing results for '";
	echo $result;
	echo "' instead of '";
	echo $query;
	echo "' <br/> <br/>";
  }
  echo "Results ";
  echo $start." - ".$end." of ".$total;
  echo "<br>";
}
?>
<?php
  foreach ($results->response->docs as $doc)
  {
    foreach ($doc as $field => $value)
    {
		if($field=="title"){
			$title_val = $value;
		}
		else if($field=="id"){
			$result = strtolower($result);
			$b=explode("/",$value);
			$url = getFileURL(end($b));
			$id_value = $value;
			$des = str_get_html(file_get_contents($id_value))->plaintext;
			$query_terms = preg_split("/\s+/", $result);
			$sentences = explode(".", $des);
			$desc = "";
			$flag = 0;
			while (($statement = current($sentences)) ) {
				if (strpos(strtolower($statement), $result) !== false) {
					$desc = $desc . $statement;
					$flag =1;
					break;
				}
				next($sentences);
			}
			if($flag == 0)
			{
				$sentences = explode(".", $des);
				foreach($query_terms as $wd)    
				{
					foreach($sentences as $element) 
					{
						$elementlower=strtolower($element);
						if (strpos($elementlower, $wd) !== false) 
						{
							$desc=$element;
							break 2;
						}
					}  
				}
			}
			$desc=str_replace("share on facebook","",strtolower($desc));
			$desc=str_replace("share on twitter","",strtolower($desc));
			$desc=str_replace("share on google plus","",strtolower($desc));
			$desc=str_replace("email page link","",strtolower($desc));
			$desc=str_replace("secondary navigation","",strtolower($desc));	
			$desc=str_replace("search","",strtolower($desc));       
		}
    }
?>
<br>
<a style="text-decoration:none" target="_blank" href="<?php echo htmlspecialchars($url, ENT_NOQUOTES, 'utf-8')?>"><?php echo htmlspecialchars($title_val, ENT_NOQUOTES, 'utf-8')?></a>
<br>
<a target="_blank" style="color:green" href="<?php echo htmlspecialchars($url, ENT_NOQUOTES, 'utf-8')?>"><?php echo htmlspecialchars($url, ENT_NOQUOTES, 'utf-8')?></a>
<br>
<?php
if(mb_strlen($desc,'UTF-8')>160)
{
	$st=mb_substr($desc, 0, 160,'UTF-8');
	if (trim($st)){
		echo $st."...";
		echo "<br>";
	}
}
else if(mb_strlen($desc,'UTF-8')>5)
{
	echo mb_substr($desc, 0,mb_strlen($desc,'UTF-8'),'UTF-8');
	echo "<br>";
}
echo "<font size='2'>".htmlspecialchars($id_value, ENT_NOQUOTES, 'utf-8')."</font><br>"; 
}
?>
</body>
</html>
