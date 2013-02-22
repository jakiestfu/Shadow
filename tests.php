<?PHP

require 'src/Shadow.php';

$time_start = microtime(true);

$autoTrack = false;
$shadow = new Shadow('MyAppName', function($sh){
	global $autoTrack;
	$sh->type('shadow_test')->item('object-id')->meta('autoTrack', "tracked")->track();
	$autoTrack = $sh->type('shadow_test')->item('object-id')->meta('autoTrack')->get() == "tracked" ? true : false;
});

// Simple String Operation
$shadow->type('shadow_test')->item('object-id')->meta('stringKey', "shadowROX")->track();
$simpleStringRes = $shadow->type('shadow_test')->item('object-id')->meta('stringKey')->get() == "shadowROX" ? true : false;

// Complex String Operation
$shadow->type('shadow_test')->item('object-id')->meta('complexStringKey/wow', "shadowComplexROX")->track();
$complexStringRes = $shadow->type('shadow_test')->item('object-id')->meta('complexStringKey/wow')->get() == "shadowComplexROX" ? true : false;


// Simple Array Operation
$simpArray = array(
	'alpha' => 'beta',
	'foo' => 'bar',
	'hello' => 200
);
$shadow->type('shadow_test')->item('object-id')->meta('arrayKey', $simpArray)->track();
$simpleArrayRes = $shadow->type('shadow_test')->item('object-id')->meta('arrayKey')->get() == $simpArray ? true : false;

// Complex Array Operation
$compArray = array(
	'alpha' => 'beta',
	'foo' => 'bar',
	'hello' => 200
);
$shadow->type('shadow_test')->item('object-id')->meta('arrayComplexKey', $compArray)->track();
$complexArrayRes = $shadow->type('shadow_test')->item('object-id')->meta('arrayComplexKey')->get() == $compArray ? true : false;

// Simple Count Operation
$shadow->type('shadow_test')->item('object-id')->meta('impressions')->track();
$shadow->type('shadow_test')->item('object-id')->meta('impressions')->track();
$simpleCountRes = $shadow->type('shadow_test')->item('object-id')->meta('impressions')->get() == 2 ? true : false;

// Complex Operation
$shadow->type('shadow_test')->item('object-id')->meta('gender/male')->track();
$shadow->type('shadow_test')->item('object-id')->meta('gender/male')->track();
$shadow->type('shadow_test')->item('object-id')->meta('gender/male')->track();
$shadow->type('shadow_test')->item('object-id')->meta('gender/female')->track();
$shadow->type('shadow_test')->item('object-id')->meta('gender/female')->track();
$complexRes = $shadow->type('shadow_test')->item('object-id')->meta('gender')->get();
$complexCountRes = $complexRes['male'] == 3 && $complexRes['female']==2 ? true : false;

// Unary Operation
$shadow->type('shadow_test')->item('object-id')->relation('unary', 1, true)->track();
$unaryRes = $shadow->type('shadow_test')->item('object-id')->relation('unary', 1)->get() ? true : false;


// Binary Operation
$shadow->type('shadow_test')->item('object-id')->relation('binary', 1, false)->track();
$binaryRes = $shadow->type('shadow_test')->item('object-id')->relation('binary', 1)->get();
$binaryRes = $binaryRes===false ? true : false;


// Multary Operation
$shadow->type('shadow_test')->item('object-id')->relation('multary', 1, 4)->track();
$multaryRes = $shadow->type('shadow_test')->item('object-id')->relation('multary', 1)->get() == 4 ? true : false;


$testResults = array(

	'auto_track' => $autoTrack,
	
	'simple_string_operation' => $simpleStringRes,
	'simple_array_operation' => $simpleArrayRes,
	'simple_count_operation' => $simpleCountRes,
	
	'complex_string_operation' => $complexStringRes,
	'complex_array_operation' => $complexArrayRes,
	'complex_count_operation' => $complexCountRes,
	
	'unary_relation' => $unaryRes,
	'binary_relation' => $binaryRes,
	'multary_relation' => $multaryRes

);

$shadow->clearDataByType('shadow_test');

$time_end = microtime(true);

?><!doctype html>
<html lang="en">
	<head>
		<title>Shadow Tests</title>
		<style type="text/css">
			ul{
				margin: 0;
				padding: 0;
				}
			ul, ul li{
				display:block;
			}
			.success, .error{
				padding:20px;
				color:#fff;
				font-family:monospace;
				font-size:18px;
				margin-bottom:10px;
				-webkit-border-radius:5px;
				-moz-border-radius:5px;
				border-radius:5px;
				}
				.success{
					background:#468847;
				}
				.error{
					background:#b94a48;
				}
				span{
					float:right;
					}
		</style>
	</head>
	<body><?PHP 
		
		echo '<p>Completed in '.number_format($time_end-$time_start, 3).' seconds</p>';
		
		?>
		<ul>
			<?PHP
			
			foreach($testResults as $k=>$r){
				$klass = $r ? 'success' : 'error';
				$text = $r ? 'Passed' : 'Failed';
				echo '<li class="'.$klass.'">'.ucwords(str_ireplace('_',' ',$k)).'<span>'.$text.'</span></li>';
			}
			
			?>
		</ul>
	</body>
</html>