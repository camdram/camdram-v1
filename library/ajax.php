<?php
function enableedit($id,$type,$getvars,$classname='edit-inplace',$backcol="#ffffff",$callback="") {
	 
	 
?><script type="text/javascript">
//<!--
	      new Ajax.InPlaceEditor('<?=$id?>','<?=$base?>/postback.php?type=<?=$type?><?php

foreach ($getvars as $k=>$v) {
	echo "&".urlencode($k)."=".urlencode($v);
}

					?>',
					{ formClassName: '<?=$classname?>',
					  okText: 'Save',
				 	   cancelText: 'cancel',
						  highlightEndColor: '<?=$backcol?>'<?php
if($callback!="") echo ", onComplete: $callback "; ?>}
						  );
//--></script><?php
}


function ajaxbutton($text, $type, $getvars, $callback="") {
?><span class="vbutton" onclick="new Ajax.Request('<?=$base?>/postback.php?type=<?php
echo $type;
foreach($getvars as $k=>$v) {
		 echo "&".urlencode($k)."=".urlencode($v);
}

if($callback!="") { ?>', {onSuccess: <?=$callback?> });"><?php } else { ?>');"> <?php }
echo $text."</span>";
}
?>
