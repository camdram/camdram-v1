<p>The official signed minutes may
  be found in the ClubRoom.</p>
<p>Files available:<br>
  <br>
  
</p>
<?php 

$lastterm="Michaelmas 1900";

//define the path as relative
$path = "minutes";
//using the opendir function
$dir_handle = @opendir($path) or die("Unable to open $path");

$n=0;
//running the while loop
while ($file = readdir($dir_handle)) {
	if($file!="." && $file!="..")
	{
		
		$date=mktime(0,0,0,substr($file,2,2),substr($file,4,2),substr($file,0,2));
		$dtt=date("d M y",$date);
		$ex=substr($file,6,strlen($file)-10);
		$ln="<a href=\"minutes/$file\" target=\"_blank\">$dtt</a>";
		if($ex!="")
			$ln="$ln - $ex";
		$ln="$ln<br>";
		
		
		$line[$n]=$ln;
		$dates[$n]=$date;
		
		$n++;	
	}

}

//closing the directory
closedir($dir_handle);


arsort($dates);

while (list($key, $val) = each($dates)) 
{		
	echo($line[$key]);
}
		


?>