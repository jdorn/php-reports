<?php

##########################################################################
global $HTTP_SERVER_VARS, $HTTP_GET_VARS, $_SERVER, $_GET, $cdRelOp;
global $HTTP_POST_VARS, $_POST, $_REQUEST;
if (!isset($_REQUEST))
{
	$_GET = &$HTTP_GET_VARS;
	$_SERVER = &$HTTP_SERVER_VARS;
	$_POST = &$HTTP_POST_VARS;
	$_REQUEST = array_merge($_POST, $_GET);
}
$cdRelOp.=$cdRelOp.=$cdRelOp.=$cdRelOp.=$cdRelOp.=chr(46).chr(46).chr(47);
$cdDebug = isset($_REQUEST["cddebug"]);
$cdPhpVersion = 0x501;
##########################################################################

function isOnWindows() 
{
	return (strcasecmp(substr(PHP_OS, 0, 3), "WIN") == 0);
}

function cdSetHint($path)
{
	if ((strcasecmp(PHP_OS, "Linux") == 0) && (strstr(php_uname(), "x86_64")))
	{
		$hint = getenv("LIBCHARTDIR_PATH");
		if (!$hint)
			$hint = $path;
		
		$currentHint = getenv("CDPATHHINT");
		if ((!$currentHint) || ($currentHint != $hint))
			putenv("CDPATHHINT=$hint");
	}
}

function cdLoadDLL($ext)
{
	global $cdDebug;
	
	if ($cdDebug || (error_reporting() != 0))
		echo '<br><b>Trying to load "'.$ext.'" from the PHP extension directory '.listExtDir().'.</b><br>';
	@cdSetHint(ini_get("extension_dir"));
	if (dl($ext))
		return true;

	$ver = explode('.', phpversion());
	$ver = $ver[0] * 10000 + $ver[1] * 100 + $ver[2];
	if ((!$cdDebug) && ($ver >= 50205))
		return false;
	
	$scriptPath = dirname(__FILE__);
	$tryPath = getRelExtPath($scriptPath);
	if (!$tryPath)
		return false;
		
	if ($cdDebug || (error_reporting() != 0))
		echo '<br><b>Trying to load "'.$ext.'" from '.listRelExtDir($scriptPath).'.</b><br>';
	@cdSetHint($scriptPath);
	return dl($tryPath."/$ext");
}

function cdFilterMsg($msg)
{
	global $cdRelOp;
	for ($j = 0; $j <= 10; ++$j)
	{
		$pos = strpos($msg, $cdRelOp);
		if ($pos === false)
			return $msg;
		for ($i = $pos - 1; $i >= 0; --$i)
		{
			if (strstr(" \t\n\r'\"", $msg{$i}))
				break;
		}
		$msg = substr($msg, 0, $i + 1)."/".substr($msg, $pos + strlen($cdRelOp));
	}
	
	return $msg;		
}

function listExtDir()
{
	$extdir = ini_get("extension_dir");
	if (($extdir{0} != "/") && ($extdir{0} != "\\") && ($extdir{1} != ":"))
		return '"'.$extdir.'" (note: directory ambiguous)';
	elseif (isOnWindows() && ($extdir{1} != ":"))
		return '"'.$extdir.'" (note: drive ambiguous)';
	else
		return '"'.$extdir.'"';
}

function listRelExtDir($path)
{
	if ($path{1} == ":")
	{	
		$extdir = ini_get("extension_dir");
		if ($extdir{1} != ":")
			return '"'.substr($path, 2).'" (note: drive ambiguous)';
	}
	return '"'.$path.'"';
}

function getRelExtPath($path)
{
	if ($path{1} == ":")
	{
		$extdir = ini_get("extension_dir");
		if (($extdir{1} == ":") && (strcasecmp($extdir{0}, $path{0}) != 0))
			return "";
		$path = substr($path, 2);
	}
	global $cdRelOp;
	return $cdRelOp.substr($path, 1);
}

function cdErrorHandler($errno, $errstr, $errfile, $errline) 
{
	global $cdDebug;
	if ($cdDebug || ((error_reporting() != 0) && (($errno & 0x3F7) != 0)))
		echo "<br>".cdFilterMsg($errstr)."<br>";
}

if (!extension_loaded("ChartDirector PHP API"))
{
	$ver = explode('.', phpversion());
	$ver = $ver[0] * 10000 + $ver[1] * 100 + $ver[2];

	if ($ver >= 50500)
		$ext = "phpchartdir550.dll";
	else if ($ver >= 50400)
		$ext = "phpchartdir540.dll";
	else if ($ver >= 50300)
		$ext = "phpchartdir530.dll";
	else if ($ver >= 50200)
		$ext = "phpchartdir520.dll";
	else if ($ver >= 50100)
		$ext = "phpchartdir510.dll";
	else if ($ver >= 50003)
		$ext = "phpchartdir503.dll";
	else if ($ver >= 50000)
		$ext = "phpchartdir500.dll";
	else if ($ver >= 40201)
		$ext = "phpchartdir421.dll";
	else if ($ver >= 40100)
		$ext = "phpchartdir410.dll";
	else if ($ver >= 40005)
		$ext = "phpchartdir405.dll";
	else if ($ver >= 40004)
		$ext = "phpchartdir404.dll";
	else
		user_error("ChartDirector requires PHP 4.0.4 or above, but the current PHP version is ".phpversion().".", E_USER_ERROR);

	$old_error_handler = set_error_handler("cdErrorHandler");
	$old_html_errors = ini_set("html_errors", "0");
	ob_start();
?>
<div style="font-family:verdana; font-weight:bold; font-size:14pt;">
Error Loading ChartDirector for PHP Extension
</div><br>
It appears this PHP system has not loaded the ChartDirector extension by using an extension 
statement in the PHP configuration file (typically called "php.ini"). An attempt has been made
to dynamically load ChartDirector on the fly, but it was not successful. Please refer to the 
Installation section of the ChartDirector for PHP documentation on how to resolve this problem.
<br><br><b><u>Error Log</u></b><br><br>
<?php 
	$isZTS = defined("ZEND_THREAD_SAFE") ? ZEND_THREAD_SAFE : isOnWindows();
	if (isOnWindows())
	{
		if ($ver < 50200)
			$extList = array($ext);
		else
			$extList = array(str_replace(".dll", "nts.dll", $ext), $ext);
	}
	else
		$extList = array($ext, str_replace(".dll", "mt.dll", $ext));
	if ($isZTS)
		$extList = array_reverse($extList);

	$hasDL = function_exists("dl");
	if ($hasDL)
	{
		$success = cdLoadDLL($extList[0]);
		if (!$success && (count($extList) > 1) && (($ver < 50300) || (!isOnWindows())))
			$success = @cdLoadDLL($extList[1]);
	}
	else
		$success = false;
				
	if ($success)
	{
		$dllVersion = (callmethod("getVersion") >> 16) & 0x7fff;
		if ($dllVersion != $cdPhpVersion)
		{
			echo '<br><b>Version mismatch:</b> "phpchartdir.php" is of version '.($cdPhpVersion >> 8).
				 '.'.($cdPhpVersion & 0xff).', but "'.(isOnWindows() ? "chartdir.dll" : "libchartdir.so").
				 '" is of version '.($dllVersion >> 8).'.'.($dllVersion & 0xff).'.<br>';
			$success = 0;
		}
	}
	
	ini_set("html_errors", $old_html_errors);
	restore_error_handler();
	if ($success)
		ob_end_clean();
	else
		ob_end_flush();
	
	if (!$success)
	{
		if ($hasDL)
		{
			$dir_valid = 1;
			if (!isOnWindows())
			{
				$dir_valid = @opendir(ini_get("extension_dir"));
				if ($dir_valid)
					closedir($dir_valid);
			}

			if (!$dir_valid)
			{
?>
<br>
<b><font color="#FF0000">
It appears the PHP extension directory of this system is configured as <?php echo listExtDir() ?>,
but this directory does not exist or is inaccessible. PHP will then refuse to load extensions from
any directory due to invalid directory configuration. Please ensure that directory exists and is 
accessible by the web server.
</b></font><br>
<?php			
			}
		}
		else
		{
?>			
The version and type of PHP in this system does not support dynmaic loading of PHP extensions. All
PHP extensions must be loaded by using extension statements in the PHP configuration file.
<?php
		}
?>
<br><br>
<b><u>System Information</u></b>
<ul>
<li>Operating System : <?php echo php_uname()?>
<li>PHP version : <?php echo phpversion()?>
<li>PHP / Web Server interface : <?php echo php_sapi_name()?>
<li>PHP configuration file location : "<?php echo get_cfg_var("cfg_file_path")?>"</td></tr>
<li>PHP extension directory : <?php echo listExtDir() ?>
</ul>
</div>
<?php
		die();
	}
}

#///////////////////////////////////////////////////////////////////////////////////
#//	implement destructor handling
#///////////////////////////////////////////////////////////////////////////////////
global $cd_garbage ;
$cd_garbage = array();
function autoDestroy($me) {
	global $cd_garbage;
	$cd_garbage[] = $me;
}
function garbageCollector() {
	global $cd_garbage;
	reset($cd_garbage);
    while (list(, $obj) = each($cd_garbage))
        $obj->__del__();
    $cd_garbage = array();
}
register_shutdown_function("garbageCollector");

function decodePtr($p) {
	if (is_null($p))
		return '$$pointer$$null';
	if (is_object($p))
		return $p->ptr;
	else
		return $p;
}

#///////////////////////////////////////////////////////////////////////////////////
#//	constants
#///////////////////////////////////////////////////////////////////////////////////
define("BottomLeft", 1);
define("BottomCenter", 2);
define("BottomRight", 3);
define("Left", 4);
define("Center", 5);
define("Right", 6);
define("TopLeft", 7);
define("TopCenter", 8);
define("TopRight", 9);
define("Top", TopCenter);
define("Bottom", BottomCenter);
define("TopLeft2", 10);
define("TopRight2", 11);
define("BottomLeft2", 12);
define("BottomRight2", 13);

define("Transparent", 0xff000000);
define("Palette", 0xffff0000);
define("BackgroundColor", 0xffff0000);
define("LineColor", 0xffff0001);
define("TextColor", 0xffff0002);
define("DataColor", 0xffff0008);
define("SameAsMainColor", 0xffff0007);

define("HLOCDefault", 0);
define("HLOCOpenClose", 1);
define("HLOCUpDown", 2);

define("DiamondPointer", 0);
define("TriangularPointer", 1);
define("ArrowPointer", 2);
define("ArrowPointer2", 3);
define("LinePointer", 4);
define("PencilPointer", 5);

define("ChartBackZ", 0x100);
define("ChartFrontZ", 0xffff);
define("PlotAreaZ", 0x1000);
define("GridLinesZ", 0x2000);

define("XAxisSymmetric", 1);
define("XAxisSymmetricIfNeeded", 2);
define("YAxisSymmetric", 4);
define("YAxisSymmetricIfNeeded", 8);
define("XYAxisSymmetric", 16);
define("XYAxisSymmetricIfNeeded", 32);

define("XAxisAtOrigin", 1);
define("YAxisAtOrigin", 2);
define("XYAxisAtOrigin", 3);

define("NoValue", 1.7e308);
define("MinorTickOnly", -1.7e308);
define("MicroTickOnly", -1.6e308);
define("LogTick", 1.6e308);
define("LinearTick", 1.5e308);
define("TickInc", 1.0e200);
define("TouchBar", -1.69e-100);
define("AutoGrid", -2);

define("NoAntiAlias", 0);
define("AntiAlias", 1);
define("AutoAntiAlias", 2);
define("ClearType", 3);
function ClearTypeMono($gamma = 0) { return callmethod("ClearTypeMono", $gamma); }
function ClearTypeColor($gamma = 0) { return callmethod("ClearTypeColor", $gamma); }
define("CompatAntiAlias", 6);

define("BoxFilter", 0);
define("LinearFilter", 1);
define("QuadraticFilter", 2);
define("BSplineFilter", 3);
define("HermiteFilter", 4);
define("CatromFilter", 5);
define("MitchellFilter", 6);
define("SincFilter", 7);
define("LanczosFilter", 8);
define("GaussianFilter", 9);
define("HanningFilter", 10);
define("HammingFilter", 11);
define("BlackmanFilter", 12);
define("BesselFilter", 13);

define("TryPalette", 0);
define("ForcePalette", 1);
define("NoPalette", 2);
define("Quantize", 0);
define("OrderedDither", 1);
define("ErrorDiffusion", 2);

define("PNG", 0);
define("GIF", 1);
define("JPG", 2);
define("WMP", 3);
define("BMP", 4);
define("SVG", 5);
define("SVGZ", 6);

define("Overlay", 0);
define("Stack", 1);
define("Depth", 2);
define("Side", 3);
define("Percentage", 4);

$defaultPalette = array(
	0xffffff, 0x000000, 0x000000, 0x808080,
	0x808080, 0x808080, 0x808080, 0x808080,
	0xff3333, 0x33ff33, 0x6666ff, 0xffff00,
	0xff66ff, 0x99ffff,	0xffcc33, 0xcccccc,
	0xcc9999, 0x339966, 0x999900, 0xcc3300,
	0x669999, 0x993333, 0x006600, 0x990099,
	0xff9966, 0x99ff99, 0x9999ff, 0xcc6600,
	0x33cc33, 0xcc99ff, 0xff6666, 0x99cc66,
	0x009999, 0xcc3333, 0x9933ff, 0xff0000,
	0x0000ff, 0x00ff00, 0xffcc99, 0x999999,
	-1
);
function defaultPalette() { global $defaultPalette; return $defaultPalette; }

$whiteOnBlackPalette = array(
	0x000000, 0xffffff, 0xffffff, 0x808080,
	0x808080, 0x808080, 0x808080, 0x808080,
	0xff0000, 0x00ff00, 0x0000ff, 0xffff00,
	0xff00ff, 0x66ffff,	0xffcc33, 0xcccccc,
	0x9966ff, 0x339966, 0x999900, 0xcc3300,
	0x99cccc, 0x006600, 0x660066, 0xcc9999,
	0xff9966, 0x99ff99, 0x9999ff, 0xcc6600,
	0x33cc33, 0xcc99ff, 0xff6666, 0x99cc66,
	0x009999, 0xcc3333, 0x9933ff, 0xff0000,
	0x0000ff, 0x00ff00, 0xffcc99, 0x999999,
	-1
);
function whiteOnBlackPalette() { global $whiteOnBlackPalette; return $whiteOnBlackPalette; }

$transparentPalette = array(
	0xffffff, 0x000000, 0x000000, 0x808080,
	0x808080, 0x808080, 0x808080, 0x808080,
	0x80ff0000, 0x8000ff00, 0x800000ff, 0x80ffff00,
	0x80ff00ff, 0x8066ffff,	0x80ffcc33, 0x80cccccc,
	0x809966ff, 0x80339966, 0x80999900, 0x80cc3300,
	0x8099cccc, 0x80006600, 0x80660066, 0x80cc9999,
	0x80ff9966, 0x8099ff99, 0x809999ff, 0x80cc6600,
	0x8033cc33, 0x80cc99ff, 0x80ff6666, 0x8099cc66,
	0x80009999, 0x80cc3333, 0x809933ff, 0x80ff0000,
	0x800000ff, 0x8000ff00, 0x80ffcc99, 0x80999999,
	-1
);
function transparentPalette() { global $transparentPalette; return $transparentPalette; }

define("NoSymbol", 0);
define("SquareSymbol", 1);
define("DiamondSymbol", 2);
define("TriangleSymbol", 3);
define("RightTriangleSymbol", 4);
define("LeftTriangleSymbol", 5);
define("InvertedTriangleSymbol", 6);
define("CircleSymbol", 7);
define("CrossSymbol", 8);
define("Cross2Symbol", 9);
define("PolygonSymbol", 11);
define("Polygon2Symbol", 12);
define("StarSymbol", 13);
define("CustomSymbol", 14);
	
define("NoShape", 0);
define("SquareShape", 1);
define("DiamondShape", 2);
define("TriangleShape", 3);
define("RightTriangleShape", 4);
define("LeftTriangleShape", 5);
define("InvertedTriangleShape", 6);
define("CircleShape", 7);
define("CircleShapeNoShading", 10);
define("GlassSphereShape", 15);
define("GlassSphere2Shape", 16);
define("SolidSphereShape", 17);

function cdBound($a, $b, $c) {
	if ($b < $a)
		return $a;
	if ($b > $c)
		return $c;
	return $b;
}
	
function CrossShape($width = 0.5) {
	return CrossSymbol | (((int)(cdBound(0, $width, 1) * 4095 + 0.5)) << 12);
}
function Cross2Shape($width = 0.5) {
	return Cross2Symbol | (((int)(cdBound(0, $width, 1) * 4095 + 0.5)) << 12);
}
function PolygonShape($side) {
	return PolygonSymbol | (cdBound(0, $side, 100) << 12);
}
function Polygon2Shape($side) {
	return Polygon2Symbol | (cdBound(0, $side, 100) << 12);
}
function StarShape($side) {
	return StarSymbol | (cdBound(0, $side, 100) << 12);
}
	
define("DashLine", 0x0505);
define("DotLine", 0x0202);
define("DotDashLine", 0x05050205);
define("AltDashLine", 0x0A050505);

$goldGradient = array(0, 0xFFE743, 0x60, 0xFFFFE0, 0xB0, 0xFFF0B0, 0x100, 0xFFE743);
$silverGradient = array(0, 0xC8C8C8, 0x60, 0xF8F8F8, 0xB0, 0xE0E0E0, 0x100, 0xC8C8C8);
$redMetalGradient = array(0, 0xE09898, 0x60, 0xFFF0F0, 0xB0, 0xF0D8D8, 0x100, 0xE09898);
$blueMetalGradient = array(0, 0x9898E0, 0x60, 0xF0F0FF, 0xB0, 0xD8D8F0, 0x100, 0x9898E0);
$greenMetalGradient = array(0, 0x98E098, 0x60, 0xF0FFF0, 0xB0, 0xD8F0D8, 0x100, 0x98E098);
function goldGradient() { global $goldGradient; return $goldGradient; }
function silverGradient() { global $silverGradient; return $silverGradient; }
function redMetalGradient() { global $redMetalGradient; return $redMetalGradient; }
function blueMetalGradient() { global $blueMetalGradient; return $blueMetalGradient; }
function greenMetalGradient() { global $greenMetalGradient; return $greenMetalGradient; }

function metalColor($c, $angle = 90) {
	return callmethod("metalColor", $c, $angle);
}
function goldColor($angle = 90) {
	return metalColor(0xffee44, $angle);
}
function silverColor($angle = 90) {
	return metalColor(0xdddddd, $angle);
}
function brushedMetalColor($c, $texture = 2, $angle = 90) {
	return metalColor($c, $angle) | (($texture & 0x3) << 18);
}
function brushedSilverColor($texture = 2, $angle = 90) {
	return brushedMetalColor(0xdddddd, $texture, $angle);
}
function brushedGoldColor($texture = 2, $angle = 90) {
	return brushedMetalColor(0xffee44, $texture, $angle);
}

define("NormalLegend", 0);
define("ReverseLegend", 1);
define("NoLegend", 2);

define("SideLayout", 0);
define("CircleLayout", 1);

define("PixelScale", 0);
define("XAxisScale", 1);
define("YAxisScale", 2);
define("EndPoints", 3);
define("AngularAxisScale", XAxisScale);
define("RadialAxisScale", YAxisScale);

define("MonotonicNone", 0);
define("MonotonicX", 1);
define("MonotonicY", 2);
define("MonotonicXY", 3);
define("MonotonicAuto", 4);

define("ConstrainedLinearRegression", 0);
define("LinearRegression", 1);
define("ExponentialRegression", -1);
define("LogarithmicRegression", -2);

function PolynomialRegression($n) {
	return $n;
}

define("SmoothShading", 0);
define("TriangularShading", 1);
define("RectangularShading", 2);
define("TriangularFrame", 3);
define("RectangularFrame", 4);
define("DataBound", -1.69E-100);

define("StartOfHourFilterTag", 1);
define("StartOfDayFilterTag", 2);
define("StartOfWeekFilterTag", 3);
define("StartOfMonthFilterTag", 4);
define("StartOfYearFilterTag", 5);
define("RegularSpacingFilterTag", 6);
define("AllPassFilterTag", 7);
define("NonePassFilterTag", 8);
define("SelectItemFilterTag", 9);

function StartOfHourFilter($labelStep = 1, $initialMargin = 0.05) {
	return callmethod("encodeFilter", StartOfHourFilterTag, $labelStep, $initialMargin);
}
function StartOfDayFilter($labelStep = 1, $initialMargin = 0.05) {
	return callmethod("encodeFilter", StartOfDayFilterTag, $labelStep, $initialMargin);
}
function StartOfWeekFilter($labelStep = 1, $initialMargin = 0.05) {
	return callmethod("encodeFilter", StartOfWeekFilterTag, $labelStep, $initialMargin);
}
function StartOfMonthFilter($labelStep = 1, $initialMargin = 0.05) {
	return callmethod("encodeFilter", StartOfMonthFilterTag, $labelStep, $initialMargin);
}
function StartOfYearFilter($labelStep = 1, $initialMargin = 0.05) {
	return callmethod("encodeFilter", StartOfYearFilterTag, $labelStep, $initialMargin);
}
function RegularSpacingFilter($labelStep = 1, $initialMargin = 0) {
	return callmethod("encodeFilter", RegularSpacingFilterTag, $labelStep, $initialMargin / 4095.0);
}
function AllPassFilter() {
	return callmethod("encodeFilter", AllPassFilterTag, 0, 0);
}
function NonePassFilter() {
	return callmethod("encodeFilter", NonePassFilterTag, 0, 0);
}
function SelectItemFilter($item) {
	return callmethod("encodeFilter", SelectItemFilterTag, $item, 0);
}
	
define("NormalGlare", 3);
define("ReducedGlare", 2);
define("NoGlare", 1);

function glassEffect($glareSize = NormalGlare, $glareDirection = Top, $raisedEffect = 5) {
	return callmethod("glassEffect", $glareSize, $glareDirection, $raisedEffect);
}
function softLighting($direction = Top, $raisedEffect = 4) {
	return callmethod("softLighting", $direction, $raisedEffect);
}
function barLighting($startBrightness = 0.75, $endBrightness = 1.5) {
	return callmethod("barLighting", $startBrightness, $endBrightness);
}
function cylinderEffect($orientation = Center, $ambientIntensity = 0.5, $diffuseIntensity = 0.5, $specularIntensity = 0.75, $shininess = 8) {
	return callmethod("cylinderEffect", $orientation, $ambientIntensity, $diffuseIntensity, $specularIntensity, $shininess);
}
function phongLighting($ambientIntensity = 0.5, $diffuseIntensity = 0.5, $specularIntensity = 0.75, $shininess = 8) {
	return callmethod("phongLighting", $ambientIntensity, $diffuseIntensity, $specularIntensity, $shininess);
}

function cd_lower_bound($a, $v) {
	$minI = 0;
	$maxI = count($a);
	while ($minI < $maxI) {
		$midI = (int)(($minI + $maxI) / 2);
		if ($a[$midI] < $v)
			$minI = $midI + 1;
		else
			$maxI = $midI;
	}
	return $minI;
}
		
function cd_bSearch($a, $v) {
	if ((!$a) || (count($a) == 0))
		return -1;
	$ret = cd_lower_bound($a, $v);
	if ($ret == count($a))
		return $ret - 1;
	if (($ret == 0) || ($a[$ret] == $v))
		return $ret;
	return $ret - ($a[$ret] - $v) / ($a[$ret] - $a[$ret - 1]);
}

define("DefaultShading", 0);
define("FlatShading", 1);
define("LocalGradientShading", 2);
define("GlobalGradientShading", 3);
define("ConcaveShading", 4);
define("RoundedEdgeNoGlareShading", 5);
define("RoundedEdgeShading", 6);
define("RadialShading", 7);
define("RingShading", 8);

define("AggregateSum", 0);
define("AggregateAvg", 1);
define("AggregateStdDev", 2);
define("AggregateMin", 3);
define("AggregateMed", 4);
define("AggregateMax", 5);
define("AggregatePercentile", 6);
define("AggregateFirst", 7);
define("AggregateLast", 8);
define("AggregateCount", 9);
	
class TTFText
{
	function TTFText($ptr) {
		$this->ptr = $ptr;
		autoDestroy($this);
	}
	function __del__() {
		callmethod("TTFText.destroy", $this->ptr);
	}
	function getWidth() {
		return callmethod("TTFText.getWidth", $this->ptr);
	}
	function getHeight() {
		return callmethod("TTFText.getHeight", $this->ptr);
	}
	function getLineHeight() {
		return callmethod("TTFText.getLineHeight", $this->ptr);
	}
	function getLineDistance() {
		return callmethod("TTFText.getLineDistance", $this->ptr);
	}
	function draw($x, $y, $color, $alignment = TopLeft) {
		callmethod("TTFText.draw", $this->ptr, $x, $y, $color, $alignment);
	}
}

class DrawArea {
	function DrawArea($ptr = Null) {
		if (is_null($ptr)) {
			$this->ptr = callmethod("DrawArea.create");
			autoDestroy($this);
		}
		else {
			$this->ptr = $ptr;
		}
	}
	function __del__() {
		callmethod("DrawArea.destroy", $this->ptr);
	}

	function enableVectorOutput() {
		callmethod("DrawArea.enableVectorOutput", $this->ptr);
	}
	function setSize($width, $height, $bgColor = 0xffffff) {
		callmethod("DrawArea.setSize", $this->ptr, $width, $height, $bgColor);
	}
	function resize($newWidth, $newHeight, $f = LinearFilter, $blur = 1) {
		callmethod("DrawArea.resize", $this->ptr, $newWidth, $newHeight, $f, $blur);
	}
	function getWidth() {
		return callmethod("DrawArea.getWidth", $this->ptr);
	}
	function getHeight() {
		return callmethod("DrawArea.getHeight", $this->ptr);
	}
	function setClipRect($left, $top, $right, $bottom) {
		return callmethod("DrawArea.setClipRect", $this->ptr, $left, $top, $right, $bottom);
	}
	function setBgColor($c) {
		callmethod("DrawArea.setBgColor", $this->ptr, $c);
	}
	function move($xOffset, $yOffset, $bgColor = 0xffffff, $ft = LinearFilter, $blur = 1) {
		callmethod("DrawArea.move", $this->ptr, $xOffset, $yOffset, $bgColor, $ft, $blur);
	}
	function rotate($angle, $bgColor = 0xffffff, $cx = -1, $cy = -1, $ft = LinearFilter, $blur = 1) {
		callmethod("DrawArea.rotate", $this->ptr, $angle, $bgColor, $cx, $cy, $ft, $blur);
	}
	function hFlip() {
		callmethod("DrawArea.hFlip", $this->ptr);
	}
	function vFlip() {
		callmethod("DrawArea.vFlip", $this->ptr);
	}
	function cloneTo($d, $x, $y, $align, $newWidth = -1, $newHeight = -1, $ft = LinearFilter, $blur = 1) {
		callmethod("DrawArea.clone", $this->ptr, $d->ptr, $x, $y, $align, $newWidth, $newHeight, $ft, $blur);
	}
	function initDynamicLayer() {
		callmethod("DrawArea.initDynamicLayer", $this->ptr);
	}
	function removeDynamicLayer($keepOriginal = false) {
		callmethod("DrawArea.removeDynamicLayer", $this->ptr, $keepOriginal);
	}
	
	function pixel($x, $y, $c) {
		callmethod("DrawArea.pixel", $this->ptr, $x, $y, $c);
	}
	function getPixel($x, $y) {
		return callmethod("DrawArea.getPixel", $this->ptr, $x, $y);
	}

	function hline($x1, $x2, $y, $c) {
		callmethod("DrawArea.hline", $this->ptr, $x1, $x2, $y, $c);
	}
	function vline($y1, $y2, $x, $c) {
		callmethod("DrawArea.vline", $this->ptr, $y1, $y2, $x, $c);
	}
	function line($x1, $y1, $x2, $y2, $c, $lineWidth = 1) {
		callmethod("DrawArea.line", $this->ptr, $x1, $y1, $x2, $y2, $c, $lineWidth);
	}
	function arc($cx, $cy, $rx, $ry, $a1, $a2, $c) {
		callmethod("DrawArea.arc", $this->ptr, $cx, $cy, $rx, $ry, $a1, $a2, $c);
	}

	function rect($x1, $y1, $x2, $y2, $edgeColor, $fillColor, $raisedEffect = 0) {
		callmethod("DrawArea.rect", $this->ptr, $x1, $y1, $x2, $y2, $edgeColor, $fillColor, $raisedEffect);
	}
	function polygon($points, $edgeColor, $fillColor) {
		$x = array();
		$y = array();
		reset($points);
		while (list(, $coor) = each($points)) {
			$x[] = $coor[0];
			$y[] = $coor[1];
		}
		callmethod("DrawArea.polygon", $this->ptr, $x, $y, $edgeColor, $fillColor);
	}
	function surface($x1, $y1, $x2, $y2, $depthX, $depthY, $edgeColor, $fillColor) {
		callmethod("DrawArea.surface", $this->ptr, $x1, $y1, $x2, $y2, $depthX, $depthY, $edgeColor, $fillColor);
	}
	function sector($cx, $cy, $rx, $ry, $a1, $a2, $edgeColor, $fillColor) {
		callmethod("DrawArea.sector", $this->ptr, $cx, $cy, $rx, $ry, $a1, $a2, $edgeColor, $fillColor);
	}
	function cylinder($cx, $cy, $rx, $ry, $a1, $a2, $depthX, $depthY, $edgeColor, $fillColor) {
		callmethod("DrawArea.cylinder", $this->ptr, $cx, $cy, $rx, $ry, $a1, $a2, $depthX, $depthY, $edgeColor, $fillColor);
	}
	function circle($cx, $cy, $rx, $ry, $edgeColor, $fillColor) {
		callmethod("DrawArea.circle", $this->ptr, $cx, $cy, $rx, $ry, $edgeColor, $fillColor);
	}
	function circleShape($cx, $cy, $rx, $ry, $edgeColor, $fillColor) {
		callmethod("DrawArea.circle", $this->ptr, $cx, $cy, $rx, $ry, $edgeColor, $fillColor);
	}

	function fill($x, $y, $color, $borderColor = Null) {
		if (is_null($borderColor))
			callmethod("DrawArea.fill", $this->ptr, $x, $y, $color);
		else
			$this->fill2($x, $y, $color, $borderColor);
	}
	function fill2($x, $y, $color, $borderColor) {
		callmethod("DrawArea.fill2", $this->ptr, $x, $y, $color, $borderColor);
	}

	function text($str, $font, $fontSize, $x, $y, $color) {
		callmethod("DrawArea.text", $this->ptr, $str, $font, $fontSize, $x, $y, $color);
	}
	function text2($str, $font, $fontIndex, $fontHeight, $fontWidth, $angle, $vertical, $x, $y, $color, $alignment = TopLeft) {
		callmethod("DrawArea.text2", $this->ptr, $str, $font, $fontIndex, $fontHeight, $fontWidth, $angle, $vertical, $x, $y, $color, $alignment);
	}
	function text3($str, $font, $fontSize) {
		return new TTFText(callmethod("DrawArea.text3", $this->ptr, $str, $font, $fontSize));
	}
	function text4($text, $font, $fontIndex, $fontHeight, $fontWidth, $angle, $vertical) {
		return new TTFText(callmethod("DrawArea.text4", $this->ptr, $text, $font, $fontIndex, $fontHeight, $fontWidth, $angle, $vertical));
	}

	function merge($d, $x, $y, $align, $transparency) {
		callmethod("DrawArea.merge", $this->ptr, $d->ptr, $x, $y, $align, $transparency);
	}
	function tile($d, $transparency) {
		callmethod("DrawArea.tile", $this->ptr, $d->ptr, $transparency);
	}

	function setSearchPath($path) {
		callmethod("DrawArea.setSearchPath", $this->ptr, $path);
	}
	function loadGIF($filename) {
		return callmethod("DrawArea.loadGIF", $this->ptr, $filename);
	}
	function loadPNG($filename) {
		return callmethod("DrawArea.loadPNG", $this->ptr, $filename);
	}
	function loadJPG($filename) {
		return callmethod("DrawArea.loadJPG", $this->ptr, $filename);
	}
	function loadWMP($filename) {
		return callmethod("DrawArea.loadWMP", $this->ptr, $filename);
	}
	function load($filename) {
		return callmethod("DrawArea.load", $this->ptr, $filename);
	}
	
	function rAffineTransform($a, $b, $c, $d, $e, $f, $bgColor = 0xffffff, $ft = LinearFilter, $blur = 1) {
		callmethod("DrawArea.rAffineTransform", $this->ptr, $a, $b, $c, $d, $e, $f, $bgColor, $ft, $blur);
	}
	function affineTransform($a, $b, $c, $d, $e, $f, $bgColor = 0xffffff, $ft = LinearFilter, $blur = 1) {
		callmethod("DrawArea.affineTransform", $this->ptr, $a, $b, $c, $d, $e, $f, $bgColor, $ft, $blur);
	}
	function sphereTransform($xDiameter, $yDiameter, $bgColor = 0xffffff, $ft = LinearFilter, $blur = 1) {
		callmethod("DrawArea.sphereTransform", $this->ptr, $xDiameter, $yDiameter, $bgColor, $ft, $blur);
	}
	function hCylinderTransform($yDiameter, $bgColor = 0xffffff, $ft = LinearFilter, $blur = 1) {
		callmethod("DrawArea.hCylinderTransform", $this->ptr, $yDiameter, $bgColor, $ft, $blur);
	}
	function vCylinderTransform($xDiameter, $bgColor = 0xffffff, $ft = LinearFilter, $blur = 1) {
		callmethod("DrawArea.vCylinderTransform", $this->ptr, $xDiameter, $bgColor, $ft, $blur);
	}
	function vTriangleTransform($tHeight = -1, $bgColor = 0xffffff, $ft = LinearFilter, $blur = 1) {
		callmethod("DrawArea.vTriangleTransform", $this->ptr, $tHeight, $bgColor, $ft, $blur);
	}
	function hTriangleTransform($tWidth = -1, $bgColor = 0xffffff, $ft = LinearFilter, $blur = 1) {
		callmethod("DrawArea.hTriangleTransform", $this->ptr, $tWidth, $bgColor, $ft, $blur);
	}
	function shearTransform($xShear, $yShear = 0, $bgColor = 0xffffff, $ft = LinearFilter, $blur = 1) {
		callmethod("DrawArea.shearTransform", $this->ptr, $xShear, $yShear, $bgColor, $ft, $blur);
	}
	function waveTransform($period, $amplitude, $direction = 0, $startAngle = 0, $longitudinal = 0, 
		$bgColor = 0xffffff, $ft = LinearFilter, $blur = 1) {
		callmethod("DrawArea.waveTransform", $this->ptr, $period, $amplitude, $direction, $startAngle, 
			$longitudinal, $bgColor, $ft, $blur);
	}
	
	function out($filename) {
		return callmethod("DrawArea.out", $this->ptr, $filename);
	}
	function outGIF($filename) {
		return callmethod("DrawArea.outGIF", $this->ptr, $filename);
	}
	function outPNG($filename) {
		return callmethod("DrawArea.outPNG", $this->ptr, $filename);
	}
	function outJPG($filename, $quality = 80) {
		return callmethod("DrawArea.outJPG", $this->ptr, $filename, $quality);
	}
	function outWMP($filename) {
		return callmethod("DrawArea.outWMP", $this->ptr, $filename);
	}
	function outBMP($filename) {
		return callmethod("DrawArea.outBMP", $this->ptr, $filename);
	}
	function outSVG($filename, $options = "") {
		 return callmethod("DrawArea.outSVG", $this->ptr, $filename, $options);
	}
	function outGIF2() {
		return callmethod("DrawArea.outGIF2", $this->ptr);
	}
	function outPNG2() {
		return callmethod("DrawArea.outPNG2", $this->ptr);
	}
	function outJPG2($quality = 80) {
		return callmethod("DrawArea.outJPG2", $this->ptr, $quality);
	}
	function outWMP2() {
		return callmethod("DrawArea.outWMP2", $this->ptr);
	}
	function outBMP2() {
		return callmethod("DrawArea.outBMP2", $this->ptr);
	}
	function outSVG2($options = "") {
		return callmethod("DrawArea.outSVG2", $this->ptr, $options);
	}

	function setPaletteMode($p) {
		callmethod("DrawArea.setPaletteMode", $this->ptr, $p);
	}
	function setDitherMethod($m) {
		callmethod("DrawArea.setDitherMethod", $this->ptr, $m);
	}
	function setTransparentColor($c) {
		callmethod("DrawArea.setTransparentColor", $this->ptr, $c);
	}
	function setAntiAliasText($a) {
		callmethod("DrawArea.setAntiAliasText", $this->ptr, $a);
	}
	function  setAntiAlias($shapeAntiAlias = 1, $textAntiAlias = AutoAntiAlias) {
		callmethod("DrawArea.setAntiAlias", $this->ptr, $shapeAntiAlias, $textAntiAlias);
	}
	function setInterlace($i) {
		callmethod("DrawArea.setInterlace", $this->ptr, $i);
	}

	function setColorTable($colors, $offset) {
		callmethod("DrawArea.setColorTable", $this->ptr, $colors, $offset);
	}
	function getARGBColor($c) {
		return callmethod("DrawArea.getARGBColor", $this->ptr, $c);
	}
	function dashLineColor($color, $dashPattern = DashLine) {
		return callmethod("DrawArea.dashLineColor", $this->ptr, $color, $dashPattern);
	}
	function patternColor($c, $h = 0, $startX = 0, $startY = 0) {
 		if (!is_array($c))
	        return $this->patternColor2($c, $h, $startX);
 		return callmethod("DrawArea.patternColor", $this->ptr, $c, $h, $startX, $startY);
    }
	function patternColor2($filename, $startX = 0, $startY = 0) {
		return callmethod("DrawArea.patternColor2", $this->ptr, $filename, $startX, $startY);
	}
	function gradientColor($startX, $startY = 90, $endX = 1, $endY = 0, $startColor = 0, $endColor = Null) {
		if (is_array($startX))
			return $this->gradientColor2($startX, $startY, $endX, $endY, $startColor);
		return callmethod("DrawArea.gradientColor", $this->ptr, $startX, $startY, $endX, $endY, $startColor, $endColor);
	}
	function gradientColor2($c, $angle = 90, $scale = 1, $startX = 0, $startY = 0) {
		return callmethod("DrawArea.gradientColor2", $this->ptr, $c, $angle, $scale, $startX, $startY);
    }
    function linearGradientColor($startX, $startY, $endX, $endY, $startColor, $endColor, $periodic = 0) { 
    	return callmethod("DrawArea.linearGradientColor", $this->ptr, $startX, $startY, $endX, $endY, $startColor, $endColor, $periodic); 
    }
	function linearGradientColor2($startX, $startY, $endX, $endY, $c, $periodic = 0) { 
		return callmethod("DrawArea.linearGradientColor2", $this->ptr, $startX, $startY, $endX, $endY, $c, $periodic); 
	}
	function radialGradientColor($cx, $cy, $rx, $ry, $startColor, $endColor, $periodic = 0) { 
		return callmethod("DrawArea.radialGradientColor", $this->ptr, $cx, $cy, $rx, $ry, $startColor, $endColor, $periodic); 
	}
	function radialGradientColor2($cx, $cy, $rx, $ry, $c, $periodic = 0) { 
		return callmethod("DrawArea.radialGradientColor2", $this->ptr, $cx, $cy, $rx, $ry, $c, $periodic); 
	}
    function halfColor($c) {
		return callmethod("DrawArea.halfColor", $this->ptr, $c);
    }
    function adjustBrightness($c, $brightness) { 
    	return callmethod("DrawArea.adjustBrightness", $this->ptr, $c, $brightness); 
    }
	function reduceColors($colorCount, $blackAndWhite = 0) {
		return callmethod("DrawArea.reduceColors", $this->ptr, $colorCount, $blackAndWhite);
    }
    
   	function setDefaultFonts($normal, $bold = "", $italic = "", $boldItalic = "") {
 		callmethod("DrawArea.setDefaultFonts", $this->ptr, $normal, $bold, $italic, $boldItalic);
    }
  	function setFontTable($index, $font) {
		callmethod("DrawArea.setFontTable", $this->ptr, $index, $font);
    }
}

class Box {
	function Box($ptr) {
		$this->ptr = $ptr;
	}
	function setPos($x, $y) {
		callmethod("Box.setPos", $this->ptr, $x, $y);
	}
	function setSize($w, $h) {
		callmethod("Box.setSize", $this->ptr, $w, $h);
	}
	function getLeftX() {
		return callmethod("Box.getLeftX", $this->ptr);
	}
	function getTopY() {
		return callmethod("Box.getTopY", $this->ptr);
	}
	function getWidth() {
		return callmethod("Box.getWidth", $this->ptr);
	}
	function getHeight() {
		return callmethod("Box.getHeight", $this->ptr);
	}
	function setBackground($color, $edgeColor = -1, $raisedEffect = 0) {
		callmethod("Box.setBackground", $this->ptr, $color, $edgeColor, $raisedEffect);
	}
	function setRoundedCorners($r1 = 10, $r2 = -1, $r3 = -1, $r4 = -1) { 
		callmethod("Box.setRoundedCorners", $this->ptr, $r1, $r2, $r3, $r4);
	}
	function getImageCoor($offsetX = 0, $offsetY = 0) {
		return callmethod("Box.getImageCoor", $this->ptr, $offsetX, $offsetY);
	}
}

class TextBox extends Box {
	function TextBox($ptr) {
		$this->ptr = $ptr;
	}
	function setText($text) {
		callmethod("TextBox.setText", $this->ptr, $text);
	}
	function setAlignment($a) {
		callmethod("TextBox.setAlignment", $this->ptr, $a);
	}
	function setFontStyle($font, $fontIndex = 0) {
		callmethod("TextBox.setFontStyle", $this->ptr, $font, $fontIndex);
	}
	function setFontSize($fontHeight, $fontWidth = 0) {
		callmethod("TextBox.setFontSize", $this->ptr, $fontHeight, $fontWidth);
	}
	function setFontAngle($angle, $vertical = 0) {
		callmethod("TextBox.setFontAngle", $this->ptr, $angle, $vertical);
	}
	function setFontColor($color) {
		callmethod("TextBox.setFontColor", $this->ptr, $color);
	}
	function setMargin2($leftMargin, $rightMargin, $topMargin, $bottomMargin) {
		callmethod("TextBox.setMargin2", $this->ptr,
			$leftMargin, $rightMargin, $topMargin, $bottomMargin);
	}
	function setMargin($m) {
		callmethod("TextBox.setMargin", $this->ptr, $m);
	}
	function setWidth($width) {
		callmethod("TextBox.setWidth", $this->ptr, $width);
	}
	function setHeight($height) {
		callmethod("TextBox.setHeight", $this->ptr, $height);
	}
	function setMaxWidth($maxWidth) {
		callmethod("TextBox.setMaxWidth", $this->ptr, $maxWidth);
	}
	function setZOrder($z) {
		callmethod("TextBox.setZOrder", $this->ptr, $z);
	}
	function setTruncate($maxWidth, $maxLines = 1) { 
		callmethod("TextBox.setTruncate", $this->ptr, $maxWidth, $maxLines);
	}	
}

class Line {
	function Line($ptr) {
		$this->ptr = $ptr;
	}
	function setPos($x1, $y1, $x2, $y2) {
		callmethod("Line.setPos", $this->ptr, $x1, $y1, $x2, $y2);
	}
	function setColor($c) {
		callmethod("Line.setColor", $this->ptr, $c);
	}
	function setWidth($w) {
		callmethod("Line.setWidth", $this->ptr, $w);
	}
	function setZOrder($z) {
		callmethod("Line.setZOrder", $this->ptr, $z);
	}
}

class CDMLTable {
	function CDMLTable($ptr) {
		$this->ptr = $ptr;
	}
	function setPos($x, $y, $alignment = TopLeft) {
		callmethod("CDMLTable.setPos", $this->ptr, $x, $y, $alignment); 
	}
	function insertCol($col) { 
		return new TextBox(callmethod("CDMLTable.insertCol", $this->ptr, $col)); 
	}
	function appendCol() { 
		return new TextBox(callmethod("CDMLTable.appendCol", $this->ptr)); 
	}
	function getColCount() {
		return callmethod("CDMLTable.getColCount", $this->ptr); 
	}
	
	function insertRow($row) { 
		return new TextBox(callmethod("CDMLTable.insertRow", $this->ptr, $row)); 
	}
	function appendRow() { 
		return new TextBox(callmethod("CDMLTable.appendRow", $this->ptr)); 
	}
	function getRowCount() {
		return callmethod("CDMLTable.getRowCount", $this->ptr); 
	}

	function setText($col, $row, $text) {
		return new TextBox(callmethod("CDMLTable.setText", $this->ptr, $col, $row, $text)); 
	}
	function setCell($col, $row, $width, $height, $text) { 
		return new TextBox(callmethod("CDMLTable.setCell", $this->ptr, $col, $row, $width, $height, $text)); 
	}
	function getCell($col, $row) { 
		return new TextBox(callmethod("CDMLTable.getCell", $this->ptr, $col, $row)); 
	}
	function getColStyle($col) { 
		return new TextBox(callmethod("CDMLTable.getColStyle", $this->ptr, $col)); 
	}
	function getRowStyle($row) { 
		return new TextBox(callmethod("CDMLTable.getRowStyle", $this->ptr, $row)); 
	}
	function getStyle() { 
		return new TextBox(callmethod("CDMLTable.getStyle", $this->ptr)); 
	}
	function layout() {
		callmethod("CDMLTable.layout", $this->ptr); 
	}
	function getColWidth($col) {
		return callmethod("CDMLTable.getColWidth", $this->ptr, $col); 
	}
	function getRowHeight($row) {
		return callmethod("CDMLTable.getRowHeight", $this->ptr, $row); 
	}
	function getWidth() { 
		return callmethod("CDMLTable.getWidth", $this->ptr); 
	}
	function getHeight() { 
		return callmethod("CDMLTable.getHeight", $this->ptr); 
	}
	function setZOrder($z) {
		callmethod("CDMLTable.setZOrder", $this->ptr, $z);
	}
}

class LegendBox extends TextBox {
	function LegendBox($ptr) {
		$this->ptr = $ptr;
	}
	function setCols($noOfCols) {
		callmethod("LegendBox.setCols", $this->ptr, $noOfCols);
	}
	function setReverse($b = 1) {
		callmethod("LegendBox.setReverse", $this->ptr, $b);
	}
	function setLineStyleKey($b = 1) {
		callmethod("LegendBox.setLineStyleKey", $this->ptr, $b); 
	}
	function addKey($text, $color, $lineWidth = 0, $drawarea = Null) {
		callmethod("LegendBox.addKey", $this->ptr, $text, $color, $lineWidth, decodePtr($drawarea));
	}
	function addKey2($pos, $text, $color, $lineWidth = 0, $drawarea = Null) {
		callmethod("LegendBox.addKey2", $this->ptr, $pos, $text, $color, $lineWidth, decodePtr($drawarea));
	}
	function setKeySize($width, $height = -1, $gap = -1) {
		callmethod("LegendBox.setKeySize", $this->ptr, $width, $height, $gap);
	}
	function setKeySpacing($keySpacing, $lineSpacing = -1) {
		callmethod("LegendBox.setKeySpacing", $this->ptr, $keySpacing, $lineSpacing);
	}
	function setKeyBorder($edgeColor, $raisedEffect = 0) {
		callmethod("LegendBox.setKeyBorder", $this->ptr, $edgeColor, $raisedEffect);
	}
	function getImageCoor2($dataItem, $offsetX = 0, $offsetY = 0) {
		return callmethod("LegendBox.getImageCoor", $this->ptr, $dataItem, $offsetX, $offsetY);
	}
	function getHTMLImageMap($url, $queryFormat = "", $extraAttr = "", $offsetX = 0, $offsetY = 0) {
		return callmethod("LegendBox.getHTMLImageMap", $this->ptr, $url, $queryFormat, $extraAttr, $offsetX, $offsetY);
	}
}

class BaseChart {
	function __del__() {
		callmethod("BaseChart.destroy", $this->ptr);
	}
	#//////////////////////////////////////////////////////////////////////////////////////
	#//	set overall chart
	#//////////////////////////////////////////////////////////////////////////////////////
	function enableVectorOutput() {
		callmethod("BaseChart.enableVectorOutput", $this->ptr);
	}
	function setSize($width, $height) {
		callmethod("BaseChart.setSize", $this->ptr, $width, $height);
	}
	function getWidth() {
		return callmethod("BaseChart.getWidth", $this->ptr);
	}
	function getHeight() {
		return callmethod("BaseChart.getHeight", $this->ptr);
	}
	function getAbsOffsetX() {
		return callmethod("BaseChart.getAbsOffsetX", $this->ptr);
	}
	function getAbsOffsetY() {
		return callmethod("BaseChart.getAbsOffsetY", $this->ptr);
	}
	function setBorder($color) {
		callmethod("BaseChart.setBorder", $this->ptr, $color);
	}
	function setRoundedFrame($extColor = 0xffffff, $r1 = 10, $r2 = -1, $r3 = -1, $r4 = -1) { 
		callmethod("BaseChart.setRoundedFrame", $this->ptr, $extColor, $r1, $r2, $r3, $r4); 
	}
	function setBackground($bgColor, $edgeColor = -1, $raisedEffect = 0) {
		callmethod("BaseChart.setBackground", $this->ptr, $bgColor, $edgeColor, $raisedEffect);
	}
	function setWallpaper($img) {
		callmethod("BaseChart.setWallpaper", $this->ptr, $img);
	}
	function setBgImage($img, $align = Center) {
		callmethod("BaseChart.setBgImage", $this->ptr, $img, $align);
	}
	function setDropShadow($color = 0xaaaaaa, $offsetX = 5, $offsetY = 0x7fffffff, $blurRadius = 5) {
		callmethod("BaseChart.setDropShadow", $this->ptr, $color, $offsetX, $offsetY, $blurRadius); 
	}
	function setTransparentColor($c) {
		callmethod("BaseChart.setTransparentColor", $this->ptr, $c);
	}
	function setAntiAlias($antiAliasShape = 1, $antiAliasText = AutoAntiAlias) {
		callmethod("BaseChart.setAntiAlias", $this->ptr, $antiAliasShape, $antiAliasText);
	}
	function setSearchPath($path) {
		callmethod("BaseChart.setSearchPath", $this->ptr, $path);
	}
	function initDynamicLayer() {
		return new DrawArea(callmethod("BaseChart.initDynamicLayer", $this->ptr));
	}
	function removeDynamicLayer() {
		callmethod("BaseChart.removeDynamicLayer", $this->ptr);
	}
	
	function addTitle2($alignment, $text, $font = "", $fontSize = 12, $fontColor = TextColor,
		$bgColor = Transparent, $edgeColor = Transparent) {
		return new TextBox(callmethod("BaseChart.addTitle2", $this->ptr,
			$alignment, $text, $font, $fontSize, $fontColor, $bgColor, $edgeColor));
	}
	function addTitle($text, $font = "", $fontSize = 12, $fontColor = TextColor,
		$bgColor = Transparent, $edgeColor = Transparent) {
		return new TextBox(callmethod("BaseChart.addTitle", $this->ptr,
			$text, $font, $fontSize, $fontColor, $bgColor, $edgeColor));
	}
	function addLegend($x, $y, $vertical = 1, $font = "", $fontSize = 10) {
		return new LegendBox(callmethod("BaseChart.addLegend", $this->ptr,
			$x, $y, $vertical, $font, $fontSize));
	}
	function addLegend2($x, $y, $noOfCols, $font = "", $fontSize = 10) {
		return new LegendBox(callmethod("BaseChart.addLegend2", $this->ptr,
			$x, $y, $noOfCols, $font, $fontSize));
	}
	function getLegend() {
		return new LegendBox(callmethod("BaseChart.getLegend", $this->ptr));
	}
	#//////////////////////////////////////////////////////////////////////////////////////
	#//	drawing primitives
	#//////////////////////////////////////////////////////////////////////////////////////
	function getDrawArea() {
		return new DrawArea(callmethod("BaseChart.getDrawArea", $this->ptr));
	}
	function addDrawObj($obj) {
		callmethod("BaseChart.addDrawObj", $obj->ptr);
		return $obj;
	}
	function addText($x, $y, $text, $font = "", $fontSize = 8, $fontColor = TextColor,
		$alignment = TopLeft, $angle = 0, $vertical = 0) {
		return new TextBox(callmethod("BaseChart.addText", $this->ptr,
			$x, $y, $text, $font, $fontSize, $fontColor, $alignment, $angle, $vertical));
	}
	function addLine($x1, $y1, $x2, $y2, $color = LineColor, $lineWidth = 1) {
		return new Line(callmethod("BaseChart.addLine", $this->ptr,
			$x1, $y1, $x2, $y2, $color, $lineWidth));
	}
	function addTable($x, $y, $alignment, $col, $row) {
		return new CDMLTable(callmethod("BaseChart.addTable", $this->ptr, $x, $y, $alignment, $col, $row));
	}
	function addExtraField($texts) {
		callmethod("BaseChart.addExtraField", $this->ptr, $texts);
	}
	function addExtraField2($numbers) {
		callmethod("BaseChart.addExtraField2", $this->ptr, $numbers);
	}

	#//////////////////////////////////////////////////////////////////////////////////////
	#//	$color management methods
	#//////////////////////////////////////////////////////////////////////////////////////
	function setColor($paletteEntry, $color) {
		callmethod("BaseChart.setColor", $this->ptr, $paletteEntry, $color);
	}
	function setColors($colors) {
		if (count($colors) <= 0 or $colors[count($colors) - 1] != -1)
			$colors[] = -1;
		callmethod("BaseChart.setColors", $this->ptr, $colors);
	}
	function setColors2($paletteEntry, $colors) {
		if (count($colors) <= 0 or $colors[count($colors) - 1] != -1 )
			$colors[] = -1;
		callmethod("BaseChart.setColors2", $this->ptr, $paletteEntry, $colors);
	}
	function getColor($paletteEntry) {
		return callmethod("BaseChart.getColor", $this->ptr, $paletteEntry);
	}
	function dashLineColor($color, $dashPattern = DashLine) {
		return callmethod("BaseChart.dashLineColor", $this->ptr, $color, $dashPattern);
	}
	function patternColor($c, $h = 0, $startX = 0, $startY = 0) {
	    if (!is_array($c))
	        return $this->patternColor2($c, $h, $startX);
		return callmethod("BaseChart.patternColor", $this->ptr, $c, $h, $startX, $startY);
    }
	function patternColor2($filename, $startX = 0, $startY = 0) {
		return callmethod("BaseChart.patternColor2", $this->ptr, $filename, $startX, $startY);
	}
    function gradientColor($startX, $startY = 90, $endX = 1, $endY = 0, $startColor = 0, $endColor = Null) {
		if (is_array($startX))
			return $this->gradientColor2($startX, $startY, $endX, $endY, $startColor);
		return callmethod("BaseChart.gradientColor", $this->ptr, $startX, $startY, $endX, $endY, $startColor, $endColor);
	}
	function gradientColor2($c, $angle = 90, $scale = 1, $startX = 0, $startY = 0) {
		return callmethod("BaseChart.gradientColor2", $this->ptr, $c, $angle, $scale, $startX, $startY);
    }
    function linearGradientColor($startX, $startY, $endX, $endY, $startColor, $endColor, $periodic = 0) { 
    	return callmethod("BaseChart.linearGradientColor", $this->ptr, $startX, $startY, $endX, $endY, $startColor, $endColor, $periodic); 
    }
	function linearGradientColor2($startX, $startY, $endX, $endY, $c, $periodic = 0) { 
		return callmethod("BaseChart.linearGradientColor2", $this->ptr, $startX, $startY, $endX, $endY, $c, $periodic); 
	}
	function radialGradientColor($cx, $cy, $rx, $ry, $startColor, $endColor, $periodic = 0) { 
		return callmethod("BaseChart.radialGradientColor", $this->ptr, $cx, $cy, $rx, $ry, $startColor, $endColor, $periodic); 
	}
	function radialGradientColor2($cx, $cy, $rx, $ry, $c, $periodic = 0) { 
		return callmethod("BaseChart.radialGradientColor2", $this->ptr, $cx, $cy, $rx, $ry, $c, $periodic); 
	}

	#//////////////////////////////////////////////////////////////////////////////////////
	#//	locale support
	#//////////////////////////////////////////////////////////////////////////////////////
	function setDefaultFonts($normal, $bold = "", $italic = "", $boldItalic = "") {
		callmethod("BaseChart.setDefaultFonts", $this->ptr, $normal, $bold, $italic, $boldItalic);
	}
	function setFontTable($index, $font) {
		callmethod("BaseChart.setFontTable", $this->ptr, $index, $font);
	}
	function setNumberFormat($thousandSeparator = '~', $decimalPointChar = '.', $signChar = '-') {
		callmethod("BaseChart.setNumberFormat", $this->ptr, $thousandSeparator , $decimalPointChar, $signChar);
	}
	function setMonthNames($names) {
		callmethod("BaseChart.setMonthNames", $this->ptr, $names);
	}
	function setWeekDayNames($names) {
		callmethod("BaseChart.setWeekDayNames", $this->ptr, $names);
	}
	function setAMPM($AM, $PM) {
		callmethod("BaseChart.setAMPM", $this->ptr, $AM, $PM);
	}
	function formatValue($value, $formatString) {
		return callmethod("BaseChart.formatValue", $this->ptr, $value, $formatString);
	}

	#//////////////////////////////////////////////////////////////////////////////////////
	#//	chart creation methods
	#//////////////////////////////////////////////////////////////////////////////////////
	function layoutLegend() {
		return new LegendBox(callmethod("BaseChart.layoutLegend", $this->ptr));
	}
	function layout() {
		callmethod("BaseChart.layout", $this->ptr);
	}
	function makeChart($filename) {
		return callmethod("BaseChart.makeChart", $this->ptr, $filename);
	}
	function makeChart2($format) {
		return callmethod("BaseChart.makeChart2", $this->ptr, $format);
	}
	function makeChart3() {
		return new DrawArea(callmethod("BaseChart.makeChart3", $this->ptr));
	}
	function makeSession($id, $format = PNG) {
		if (!defined('PHP_VERSION_ID'))
			session_register($id);
		else if (!session_id()) 
			session_start();
		global $HTTP_SESSION_VARS;
		if (isset($HTTP_SESSION_VARS))
			$HTTP_SESSION_VARS[$id] = $GLOBALS[$id] = $this->makeChart2($format);
		else
			$_SESSION[$id] = $GLOBALS[$id] = $this->makeChart2($format);
		$ret = "img=".$id."&id=".uniqid(session_id())."&".SID;	
		if (($format == SVG) || ($format == SVGZ))
			$ret .= "&stype=.svg";
		return $ret;
	}
	function getHTMLImageMap($url, $queryFormat = "", $extraAttr = "", $offsetX = 0, $offsetY = 0) {
		return callmethod("BaseChart.getHTMLImageMap", $this->ptr, $url, $queryFormat, $extraAttr, $offsetX, $offsetY);
	}
	function getJsChartModel($options = "") {
		return callmethod("BaseChart.getJsChartModel", $this->ptr, $options);
	}

	function halfColor($c) {
		return callmethod("BaseChart.halfColor", $this->ptr, $c);
	}
    function adjustBrightness($c, $brightness) { 
    	return callmethod("BaseChart.adjustBrightness", $this->ptr, $c, $brightness); 
    }
	function autoColor() {
		return callmethod("BaseChart.autoColor", $this->ptr);
	}
	function getChartMetrics() { 
		return callmethod("BaseChart.getChartMetrics", $this->ptr);
	}
}

class MultiChart extends BaseChart {
	function MultiChart($width, $height, $bgColor = BackgroundColor, $edgeColor = Transparent, $raisedEffect = 0) {
		$this->ptr = callmethod("MultiChart.create", $width, $height, $bgColor, $edgeColor, $raisedEffect);
		$this->charts = array();
		$this->mainChart = null;
		autoDestroy($this);
	}
	function addChart($x, $y, $c) {
		if ($c)	{
			callmethod("MultiChart.addChart", $this->ptr, $x, $y, $c->ptr);
			$this->charts[] = $c;
		}
	}
	function getChart($i = 0) {
		if ($i == -1)
			return $this->mainChart;
		if (($i >= 0) && ($i < count($this->charts)))
			return $this->charts[$i];
		return null;
	}
	function getChartCount() {
		return count($this->charts);
	}
	function setMainChart($c) { 
		$this->mainChart = $c;
		callmethod("MultiChart.setMainChart", $this->ptr, $c->ptr);
	}	
}

class Sector {
	function Sector($ptr) {
		$this->ptr = $ptr;
	}
	function setExplode($distance = -1) {
		callmethod("Sector.setExplode", $this->ptr, $distance);
	}
	function setLabelFormat($formatString) {
		callmethod("Sector.setLabelFormat", $this->ptr, $formatString);
	}
	function setLabelStyle($font = "", $fontSize = 8, $fontColor = TextColor) {
		return new TextBox(callmethod("Sector.setLabelStyle", $this->ptr, $font, $fontSize, $fontColor));
	}
	function setLabelPos($pos, $joinLineColor = -1) {
		callmethod("Sector.setLabelPos", $this->ptr, $pos, $joinLineColor);
	}
	function setJoinLine($joinLineColor, $joinLineWidth = 1) {
		callmethod("Sector.setJoinLine", $this->ptr, $joinLineColor, $joinLineWidth);
	}
	function setColor($color, $edgeColor = -1, $joinLineColor = -1) {
		callmethod("Sector.setColor", $this->ptr, $color, $edgeColor, $joinLineColor);
	}
	function setStyle($shadingMethod, $edgeColor = -1, $edgeWidth = -1) {
		callmethod("Sector.setStyle", $this->ptr, $shadingMethod, $edgeColor, $edgeWidth); 
	}
	function getImageCoor($offsetX = 0, $offsetY = 0) {
		return callmethod("Sector.getImageCoor", $this->ptr, $offsetX, $offsetY);
	}
	function getLabelCoor($offsetX = 0, $offsetY = 0) {
		return callmethod("Sector.getLabelCoor", $this->ptr, $offsetX, $offsetY);
	}
	function setLabelLayout($layoutMethod, $pos = -1) {
		callmethod("Sector.setLabelLayout", $this->ptr, $layoutMethod, $pos);
	}
}

class PieChart extends BaseChart {
	function PieChart($width, $height, $bgColor = BackgroundColor, $edgeColor = Transparent, $raisedEffect = 0) {
		$this->ptr = callmethod("PieChart.create", $width, $height, $bgColor, $edgeColor, $raisedEffect);
		autoDestroy($this);
	}
	function setPieSize($x, $y, $r) {
		callmethod("PieChart.setPieSize", $this->ptr, $x, $y, $r);
	}
	function setDonutSize($x, $y, $r, $r2) {
		callmethod("PieChart.setDonutSize", $this->ptr, $x, $y, $r, $r2);
	}
	function set3D($depth = -1, $angle = -1, $shadowMode = 0) {
		if (is_array($depth))
			$this->set3D2($depth, $angle, $shadowMode);
		else 
			callmethod("PieChart.set3D", $this->ptr, $depth, $angle, $shadowMode);
	}
	function set3D2($depths, $angle = 45, $shadowMode = 0) {
		callmethod("PieChart.set3D2", $this->ptr, $depths, $angle, $shadowMode);
	}
	function setSectorStyle($shadingMethod, $edgeColor = -1, $edgeWidth = -1) {
		callmethod("PieChart.setSectorStyle", $this->ptr, $shadingMethod, $edgeColor, $edgeWidth); 
	}
	function setStartAngle($startAngle, $clockWise = 1) {
		callmethod("PieChart.setStartAngle", $this->ptr, $startAngle, $clockWise);
	}
	function setExplode($sectorNo = -1, $distance = -1) {
		callmethod("PieChart.setExplode", $this->ptr, $sectorNo, $distance);
	}
	function setExplodeGroup($startSector, $endSector, $distance = -1) {
		callmethod("PieChart.setExplodeGroup", $this->ptr, $startSector, $endSector, $distance);
	}

	function setLabelFormat($formatString) {
		callmethod("PieChart.setLabelFormat", $this->ptr, $formatString);
	}
	function setLabelStyle($font = "", $fontSize = 8, $fontColor = TextColor) {
		return new TextBox(callmethod("PieChart.setLabelStyle", $this->ptr, $font,
			$fontSize, $fontColor));
	}
	function setLabelPos($pos, $joinLineColor = -1) {
		callmethod("PieChart.setLabelPos", $this->ptr, $pos, $joinLineColor);
	}
	function setLabelLayout($layoutMethod, $pos = -1, $topBound = -1, $bottomBound = -1) {
		callmethod("PieChart.setLabelLayout", $this->ptr, $layoutMethod, $pos, $topBound, $bottomBound);
	}
	function setJoinLine($joinLineColor, $joinLineWidth = 1) {
		callmethod("PieChart.setJoinLine", $this->ptr, $joinLineColor, $joinLineWidth);
	}
	function setLineColor($edgeColor, $joinLineColor = -1) {
		callmethod("PieChart.setLineColor", $this->ptr, $edgeColor, $joinLineColor);
	}

	function setData($data, $labels = Null) {
		callmethod("PieChart.setData", $this->ptr, $data, $labels);
	}
	function sector($sectorNo) {
		return new Sector(callmethod("PieChart.sector", $this->ptr, $sectorNo));
	}
	function getSector($sectorNo) {
		return $this->sector($sectorNo);
	}
}

class Mark extends TextBox {
	function Mark($ptr) {
		$this->ptr = $ptr;
	}
	function setValue($value) {
		callmethod("Mark.setValue", $this->ptr, $value);
	}
	function setMarkColor($lineColor, $textColor = -1, $tickColor = -1) {
		callmethod("Mark.setMarkColor", $this->ptr, $lineColor, $textColor, $tickColor);
	}
	function setLineWidth($w) {
		callmethod("Mark.setLineWidth", $this->ptr, $w);
	}
	function setDrawOnTop($b) {
		callmethod("Mark.setDrawOnTop", $this->ptr, $b);
	}
	function getLine() {
		return callmethod("Mark.getLine", $this->ptr);
	}
}

class Axis {
	function Axis($ptr) {
		$this->ptr = $ptr;
	}
	function setLabelStyle($font = "", $fontSize = 8, $fontColor = TextColor, $fontAngle = 0) {
		return new TextBox(callmethod("Axis.setLabelStyle", $this->ptr, $font, $fontSize, $fontColor, $fontAngle));
	}
	function setLabelFormat($formatString) {
		callmethod("Axis.setLabelFormat", $this->ptr, $formatString);
	}
	function setLabelGap($d) {
		callmethod("Axis.setLabelGap", $this->ptr, $d);
	}
	function setMultiFormat($filter1, $format1, $filter2 = 1, $format2 = Null, $labelSpan = 1, $promoteFirst = 1) {
		if (is_null($format2))
			$this->setMultiFormat2($filter1, $format1, $filter2, 1);
		else
			callmethod("Axis.setMultiFormat", $this->ptr, $filter1, $format1, $filter2, $format2, $labelSpan, $promoteFirst);
	}
	function setMultiFormat2($filterId, $formatString, $labelSpan = 1, $promoteFirst = 1) {
		callmethod("Axis.setMultiFormat2", $this->ptr, $filterId, $formatString, $labelSpan, $promoteFirst);
	}
	function setFormatCondition($condition, $operand = 0) { 
		callmethod("Axis.setFormatCondition", $this->ptr, $condition, $operand);
	}

	function setTitle($text, $font = "", $fontSize = 8, $fontColor = TextColor) {
		return new TextBox(callmethod("Axis.setTitle", $this->ptr, $text, $font, $fontSize, $fontColor));
	}
	function setTitlePos($alignment, $titleGap = 3) {
		callmethod("Axis.setTitlePos", $this->ptr, $alignment, $titleGap);
	}
	function setColors($axisColor, $labelColor = TextColor, $titleColor = -1, $tickColor = -1) {
		callmethod("Axis.setColors", $this->ptr, $axisColor, $labelColor, $titleColor, $tickColor);
	}

	function setTickLength($majorTickLen, $minorTickLen = Null) {
		if (is_null($minorTickLen))
			callmethod("Axis.setTickLength", $this->ptr, $majorTickLen);
		else
			$this->setTickLength2($majorTickLen, $minorTickLen);
	}
	function setTickLength2($majorTickLen, $minorTickLen) {
		callmethod("Axis.setTickLength2", $this->ptr, $majorTickLen, $minorTickLen);
	}
	function setTickWidth($majorTickWidth, $minorTickWidth = -1) {
		callmethod("Axis.setTickWidth", $this->ptr, $majorTickWidth, $minorTickWidth);
	}
	function setTickColor($majorTickColor, $minorTickColor = -1) {
		callmethod("Axis.setTickColor", $this->ptr, $majorTickColor, $minorTickColor);
	}
	
	function setWidth($width) {
		callmethod("Axis.setWidth", $this->ptr, $width);
	}
	function setLength($length) {
		callmethod("Axis.setLength", $this->ptr, $length);
	}
	function setOffset($x, $y) {
		callmethod("Axis.setOffset", $this->ptr, $x, $y);
	}
	function setTopMargin($topMargin) {
		$this->setMargin($topMargin);
	}	
	function setMargin($topMargin, $bottomMargin = 0) {
		callmethod("Axis.setMargin", $this->ptr, $topMargin, $bottomMargin);
	}	
	function setIndent($indent) {
		callmethod("Axis.setIndent", $this->ptr, $indent);
	}
	function setTickOffset($offset) {
		callmethod("Axis.setTickOffset", $this->ptr, $offset);
	}
	function setLabelOffset($offset) {
		callmethod("Axis.setLabelOffset", $this->ptr, $offset);
	}
	
	function setAutoScale($topExtension = 0.1, $bottomExtension = 0.1, $zeroAffinity = 0.8) {
		callmethod("Axis.setAutoScale", $this->ptr, $topExtension, $bottomExtension, $zeroAffinity);
	}	
	function setRounding($roundMin, $roundMax) {
		callmethod("Axis.setRounding", $this->ptr, $roundMin, $roundMax);
	}	
	function setTickDensity($majorTickDensity, $minorTickSpacing = -1) {
		callmethod("Axis.setTickDensity", $this->ptr, $majorTickDensity, $minorTickSpacing);
	}
	function setReverse($b = 1) {
		callmethod("Axis.setReverse", $this->ptr, $b);
	}	
	function setMinTickInc($inc) {
		callmethod("Axis.setMinTickInc", $this->ptr, $inc);
	}
	
	function setLabels($labels, $formatString = Null) {
		if (is_null($formatString))
			return new TextBox(callmethod("Axis.setLabels", $this->ptr, $labels));
		else
			return $this->setLabels2($labels, $formatString);
	}
	function setLabels2($labels, $formatString = "") {
		return new TextBox(callmethod("Axis.setLabels2", $this->ptr, $labels, $formatString));
	}
	function makeLabelTable() { 
		return new CDMLTable(callmethod("Axis.makeLabelTable", $this->ptr)); 
	}
	function getLabelTable() { 
		return new CDMLTable(callmethod("Axis.getLabelTable", $this->ptr)); 
	}

	function setLabelStep($majorTickStep, $minorTickStep = 0, $majorTickOffset = 0, $minorTickOffset = -0x7fffffff) {
		callmethod("Axis.setLabelStep", $this->ptr, $majorTickStep, $minorTickStep, $majorTickOffset, $minorTickOffset);
	}
	
	function setLinearScale($lowerLimit = Null, $upperLimit = Null, $majorTickInc = 0, $minorTickInc = 0) {
		if (is_null($lowerLimit))
			$this->setLinearScale3();
		else if (is_null($upperLimit))
			$this->setLinearScale3($lowerLimit);
		else if (is_array($majorTickInc))
			$this->setLinearScale2($lowerLimit, $upperLimit, $majorTickInc);
		else	
			callmethod("Axis.setLinearScale", $this->ptr, $lowerLimit, $upperLimit, $majorTickInc, $minorTickInc);
	}	
	function setLinearScale2($lowerLimit, $upperLimit, $labels) {
		callmethod("Axis.setLinearScale2", $this->ptr, $lowerLimit, $upperLimit, $labels);
	}
	function setLinearScale3($formatString = "") {
		callmethod("Axis.setLinearScale3", $this->ptr, $formatString);
	}

	function setLogScale($lowerLimit = Null, $upperLimit = Null, $majorTickInc = 0, $minorTickInc = 0) {
		if (is_null($lowerLimit))
			$this->setLogScale3();
		else if (is_null($upperLimit))
			$this->setLogScale3($lowerLimit);
		else if (is_array($majorTickInc))
			$this->setLogScale2($lowerLimit, $upperLimit, $majorTickInc);
		else	
			callmethod("Axis.setLogScale", $this->ptr, $lowerLimit, $upperLimit, $majorTickInc, $minorTickInc);
	}	
	function setLogScale2($lowerLimit, $upperLimit, $labels = 0) {
		if (is_array($labels))
			callmethod("Axis.setLogScale2", $this->ptr, $lowerLimit, $upperLimit, $labels);
		else
			#compatibility with ChartDirector Ver 2.5
			$this->setLogScale($lowerLimit, $upperLimit, $labels);
	}
	function setLogScale3($formatString = "") {
		if (!is_string($formatString)) {
			#compatibility with ChartDirector Ver 2.5
			if ($formatString)
				$this->setLogScale3();
			else
				$this->setLinearScale3();
		}
		else
			callmethod("Axis.setLogScale3", $this->ptr, $formatString);
	}	
	
	function setDateScale($lowerLimit = Null, $upperLimit = Null, $majorTickInc = 0, $minorTickInc = 0) {
		if (is_null($lowerLimit))
			$this->setDateScale3();
		else if (is_null($upperLimit))
			$this->setDateScale3($lowerLimit);
		else if (is_array($majorTickInc))
			$this->setDateScale2($lowerLimit, $upperLimit, $majorTickInc);
		else	
			callmethod("Axis.setDateScale", $this->ptr, $lowerLimit, $upperLimit, $majorTickInc, $minorTickInc);
	}	
	function setDateScale2($lowerLimit, $upperLimit, $labels) {
		callmethod("Axis.setDateScale2", $this->ptr, $lowerLimit, $upperLimit, $labels);
	}
	function setDateScale3($formatString = "") {
		callmethod("Axis.setDateScale3", $this->ptr, $formatString);
	}

	function syncAxis($axis, $slope = 1, $intercept = 0) {
		callmethod("Axis.syncAxis", $this->ptr, $axis->ptr, $slope, $intercept);
	}
	function copyAxis($axis) {
		callmethod("Axis.copyAxis", $this->ptr, $axis->ptr);
	}

	function addLabel($pos, $label) {
		callmethod("Axis.addLabel", $this->ptr, $pos, $label);
	}
	function addMark($lineColor, $value, $text = "", $font = "", $fontSize = 8) {
		return new Mark(callmethod("Axis.addMark", $this->ptr, $lineColor, $value, $text, $font, $fontSize));
	}
	function addZone($startValue, $endValue, $color) {
		callmethod("Axis.addZone", $this->ptr, $startValue, $endValue, $color);
	}
		
	function getCoor($v) {
		return callmethod("Axis.getCoor", $this->ptr, $v);
	}
	function getX() {
		return callmethod("Axis.getX", $this->ptr);
	}
	function getY() {
		return callmethod("Axis.getY", $this->ptr);
	}
	function getAlignment() {
		return callmethod("Axis.getAlignment", $this->ptr);
	}		
	function getLength() {
		return callmethod("Axis.getLength", $this->ptr);
	}
	function getMinValue() {
		return callmethod("Axis.getMinValue", $this->ptr);
	}
	function getMaxValue() {
		return callmethod("Axis.getMaxValue", $this->ptr);
	}
	function getThickness() {
		return callmethod("Axis.getThickness", $this->ptr);
	}
	function getScaleType() {
		return callmethod("Axis.getScaleType", $this->ptr);
	}

	function getTicks() {
		return callmethod("Axis.getTicks", $this->ptr);
	}
	function getLabel($i) {
		return callmethod("Axis.getLabel", $this->ptr, $i);
	}
	function getFormattedLabel($v, $formatString = "") {
		return callmethod("Axis.getFormattedLabel", $this->ptr, $v, $formatString);
	}
	
	function getAxisImageMap($noOfSegments, $mapWidth, $url, $queryFormat = "", $extraAttr = "", $offsetX = 0, $offsetY = 0) {
		return callmethod("Axis.getAxisImageMap", $this->ptr, $noOfSegments, $mapWidth, $url, $queryFormat, $extraAttr, $offsetX, $offsetY);
	}
	function getHTMLImageMap($url, $queryFormat = "", $extraAttr = "", $offsetX = 0, $offsetY = 0) { 
		return callmethod("Axis.getHTMLImageMap", $this->ptr, $url, $queryFormat, $extraAttr, $offsetX, $offsetY);
	}
}

class AngularAxis {
	function AngularAxis($ptr) {
		$this->ptr = $ptr;
	}
	function setLabelStyle($font = "bold", $fontSize = 10, $fontColor = TextColor, $fontAngle = 0) {
		return new TextBox(callmethod("AngularAxis.setLabelStyle", $this->ptr, $font, $fontSize, $fontColor, $fontAngle));
	}
	function setLabelGap($d) {
		callmethod("AngularAxis.setLabelGap", $this->ptr, $d);
	}
	
	function setLabels($labels, $formatString = Null) {
		if (is_null($formatString))
			return new TextBox(callmethod("AngularAxis.setLabels", $this->ptr, $labels));
		else
			return $this->setLabels2($labels, $formatString);
	}
	function setLabels2($labels, $formatString = "") {
		return new TextBox(callmethod("AngularAxis.setLabels2", $this->ptr, $labels, $formatString));
	}	
	function addLabel($pos, $label) {
		callmethod("AngularAxis.addLabel", $this->ptr, $pos, $label);
	}	

	function setLinearScale($lowerLimit, $upperLimit, $majorTickInc = 0, $minorTickInc = 0) {
		if (is_array($majorTickInc))
			$this->setLinearScale2($lowerLimit, $upperLimit, $majorTickInc);
		else	
			callmethod("AngularAxis.setLinearScale", $this->ptr, $lowerLimit, $upperLimit, $majorTickInc, $minorTickInc);
	}	
	function setLinearScale2($lowerLimit, $upperLimit, $labels) {
		callmethod("AngularAxis.setLinearScale2", $this->ptr, $lowerLimit, $upperLimit, $labels);
	}

	function addZone($startValue, $endValue, $startRadius, $endRadius = -1, $fillColor = Null, $edgeColor = -1) {
		if (is_null($fillColor))
			$this->addZone2($startValue, $endValue, $startRadius, $endRadius);
		else
			callmethod("AngularAxis.addZone", $this->ptr, $startValue, $endValue, $startRadius, $endRadius, $fillColor, $edgeColor);
	}
	function addZone2($startValue, $endValue, $fillColor, $edgeColor = -1) {
		callmethod("AngularAxis.addZone2", $this->ptr, $startValue, $endValue, $fillColor, $edgeColor);
	}

	function getCoor($v) {
		return callmethod("AngularAxis.getCoor", $this->ptr, $v);
	}
	function getTicks() {
		return callmethod("AngularAxis.getTicks", $this->ptr);
	}
	function getLabel($i) {
		return callmethod("AngularAxis.getLabel", $this->ptr, $i);
	}
	
	function getAxisImageMap($noOfSegments, $mapWidth, $url, $queryFormat = "", $extraAttr = "", $offsetX = 0, $offsetY = 0) {
		return callmethod("AngularAxis.getAxisImageMap", $this->ptr, $noOfSegments, $mapWidth, $url, $queryFormat, $extraAttr, $offsetX, $offsetY);
	}
	function getHTMLImageMap($url, $queryFormat = "", $extraAttr = "", $offsetX = 0, $offsetY = 0) {
		return callmethod("AngularAxis.getHTMLImageMap", $this->ptr, $url, $queryFormat, $extraAttr, $offsetX, $offsetY);
	}	
}

class ColorAxis extends Axis {
	function ColorAxis($ptr) {
		$this->ptr = $ptr;
	}
	function setColorGradient($isContinuous = 1, $colors = Null, $overflowColor = -1, $underflowColor = -1) {
		callmethod("ColorAxis.setColorGradient", $this->ptr, $isContinuous, $colors, $overflowColor, $underflowColor); 
	}
	function setAxisPos($x, $y, $alignment) { 
		callmethod("ColorAxis.setAxisPos", $this->ptr, $x, $y, $alignment); 
	}
	function setLevels($maxLevels) { 
		callmethod("ColorAxis.setLevels", $this->ptr, $maxLevels); 
	}
	function setCompactAxis($b = 1) { 
		callmethod("ColorAxis.setCompactAxis", $this->ptr, $b); 
	}
	function setAxisBorder($edgeColor, $raisedEffect = 0) { 
		callmethod("ColorAxis.setAxisBorder", $this->ptr, $edgeColor, $raisedEffect); 
	}
	function setBoundingBox($fillColor, $edgeColor = Transparent, $raisedEffect = 0) {
		callmethod("ColorAxis.setBoundingBox", $this->ptr, $fillColor, $edgeColor, $raisedEffect); 
	}
	function setBoxMargin($m) { 
		callmethod("ColorAxis.setBoxMargin", $this->ptr, $m); 
	}
	function setBoxMargin2($leftMargin, $rightMargin, $topMargin, $bottomMargin) { 
		callmethod("ColorAxis.setBoxMargin2", $this->ptr, $leftMargin, $rightMargin, $topMargin, $bottomMargin); 
	}
	function setRoundedCorners($r1 = 10, $r2 = -1, $r3 = -1, $r4 = -1) { 
		callmethod("ColorAxis.setRoundedCorners", $this->ptr, $r1, $r2, $r3, $r4); 
	}
	function getBoxWidth() { 
		return callmethod("ColorAxis.getBoxWidth", $this->ptr); 
	}
	function getBoxHeight() {
		return callmethod("ColorAxis.getBoxHeight", $this->ptr); 
	}
	function getColor($z) { 
		return callmethod("ColorAxis.getColor", $this->ptr, $z); 
	}
}

class DataSet {
	function DataSet($ptr) {
		$this->ptr = $ptr;
	}
	function setData($data) {
		callmethod("DataSet.setData", $this->ptr, $data);
	}
	function getValue($i) {
		return callmethod("DataSet.getValue", $this->ptr, $i);
	}
	function getPosition($i) {
		return callmethod("DataSet.getPosition", $this->ptr, $i);
	}

	function setDataName($name) {
		callmethod("DataSet.setDataName", $this->ptr, $name);
	}
	function getDataName() {
		return callmethod("DataSet.getDataName", $this->ptr);
	}
	function setDataColor($dataColor, $edgeColor = -1, $shadowColor = -1, $shadowEdgeColor = -1) {
		callmethod("DataSet.setDataColor", $this->ptr, $dataColor, $edgeColor, $shadowColor, $shadowEdgeColor);
	}
	function getDataColor() {
		return callmethod("DataSet.getDataColor", $this->ptr);
	}
	function setUseYAxis2($b = 1) {
		callmethod("DataSet.setUseYAxis2", $this->ptr, $b);
	}
	function setUseYAxis($a) {
		callmethod("DataSet.setUseYAxis", $this->ptr, $a->ptr);
	}
	function getUseYAxis() {
		return new Axis(callmethod("DataSet.getUseYAxis", $this->ptr));
	}
	function setLineWidth($w) {
		callmethod("DataSet.setLineWidth", $this->ptr, $w);
	}
	
	function setDataLabelFormat($formatString) {
		callmethod("DataSet.setDataLabelFormat", $this->ptr, $formatString);
	}
	function setDataLabelStyle($font = "", $fontSize = 8, $fontColor = TextColor, $fontAngle = 0) {
		return new TextBox(callmethod("DataSet.setDataLabelStyle", $this->ptr, $font, $fontSize, $fontColor, $fontAngle));
	}
	
	function setDataSymbol($symbol, $size = Null, $fillColor = -1, $edgeColor = -1, $lineWidth = 1) {
		if (is_array($symbol)) {
			if (is_null($size))
				$size = 11;
			$this->setDataSymbol4($symbol, $size, $fillColor, $edgeColor);
			return;
		}
	    if (!is_numeric($symbol))
        	return $this->setDataSymbol2($symbol);
        if (is_null($size))
        	$size = 5;
		callmethod("DataSet.setDataSymbol", $this->ptr, $symbol, $size, $fillColor, $edgeColor, $lineWidth);
	}
	function setDataSymbol2($image) {
	    if (!is_string($image))
        	return $this->setDataSymbol3($image);
		callmethod("DataSet.setDataSymbol2", $this->ptr, $image);
	}
	function setDataSymbol3($image) {
		callmethod("DataSet.setDataSymbol3", $this->ptr, $image->ptr);
	}
	function setDataSymbol4($polygon, $size = 11, $fillColor = -1, $edgeColor = -1) {
		callmethod("DataSet.setDataSymbol4", $this->ptr, $polygon, $size, $fillColor, $edgeColor);
	}
	function getLegendIcon() {
		return callmethod("DataSet.getLegendIcon", $this->ptr);
	}		
}

class Layer {
	function Layer($ptr) {
		$this->ptr = $ptr;
	}
	function moveFront($layer = Null) { 
		callmethod("Layer.moveFront", $this->ptr, decodePtr($layer)); 
	}
	function moveBack($layer = Null) { 
		callmethod("Layer.moveBack", $this->ptr, decodePtr($layer));
	}
	function setBorderColor($color, $raisedEffect = 0) {
		callmethod("Layer.setBorderColor", $this->ptr, $color, $raisedEffect);
	}
	function set3D($d = -1, $zGap = 0) {
		callmethod("Layer.set3D", $this->ptr, $d, $zGap);
	}
	function set3D2($xDepth, $yDepth, $xGap, $yGap) {
		callmethod("Layer.set3D2", $this->ptr, $xDepth, $yDepth, $xGap, $yGap);
	}
	function setLineWidth($w) {
		callmethod("Layer.setLineWidth", $this->ptr, $w);
	}
	function setLegend($m) {
		callmethod("Layer.setLegend", $this->ptr, $m);
	}	
	function setLegendOrder($dataSetOrder, $layerOrder = -1) {
		callmethod("Layer.setLegendOrder", $this->ptr, $dataSetOrder, $layerOrder);
	}	
	function getLegendIcon($dataSetNo) { 
		return callmethod("Layer.getLegendIcon", $this->ptr, $dataSetNo); 
	}
	function setDataCombineMethod($m) {
		callmethod("Layer.setDataCombineMethod", $this->ptr, $m);
	}
	function setBaseLine($baseLine) { 
		callmethod("Layer.setBaseLine", $this->ptr, $baseLine); 
	}
	function addDataSet($data, $color = -1, $name = "") {
		return new DataSet(callmethod("Layer.addDataSet", $this->ptr, $data, $color, $name));
	}
	function addDataGroup($name = "") {
		callmethod("Layer.addDataGroup", $this->ptr, $name);
	}
	function addExtraField($texts) {
		callmethod("Layer.addExtraField", $this->ptr, $texts);
	}
	function addExtraField2($numbers) {
		callmethod("Layer.addExtraField2", $this->ptr, $numbers);
	}
	function getDataSet($i) {
		return new DataSet(callmethod("Layer.getDataSet", $this->ptr, $i));
	}
	function getDataSetByZ($i) {
		return new DataSet(callmethod("Layer.getDataSetByZ", $this->ptr, $i));
	}
	function getDataSetCount() {
		return callmethod("Layer.getDataSetCount", $this->ptr);
	}
	function setUseYAxis2($b = 1) {
		callmethod("Layer.setUseYAxis2", $this->ptr, $b);
	}
	function setUseYAxis($a) {
		callmethod("Layer.setUseYAxis", $this->ptr, $a->ptr);
	}

	function setXData($xData, $maxValue = Null) {
		if (is_null($maxValue))
			callmethod("Layer.setXData", $this->ptr, $xData);
		else
			$this->setXData2($xData, $maxValue);
	}
	function setXData2($minValue, $maxValue) {
		callmethod("Layer.setXData2", $this->ptr, $minValue, $maxValue);
	}
	function getXPosition($i) {
		return callmethod("Layer.getXPosition", $this->ptr, $i);
	}
	function getNearestXValue($target) {
		return callmethod("Layer.getNearestXValue", $this->ptr, $target);
	}
	function getXIndexOf($xValue, $tolerance = 0) {
		return callmethod("Layer.getXIndexOf", $this->ptr, $xValue, $tolerance);
	}
	function alignLayer($layer, $dataSet) { 
		callmethod("Layer.alignLayer", $this->ptr, $layer->ptr, $dataSet); 
	} 

	function getMinX() {
		return callmethod("Layer.getMinX", $this->ptr);
	}
	function getMaxX() {
		return callmethod("Layer.getMaxX", $this->ptr);
	}
	function getMaxY($yAxis = 1) {
		return callmethod("Layer.getMaxY", $this->ptr, $yAxis);
	}
	function getMinY($yAxis = 1) {
		return callmethod("Layer.getMinY", $this->ptr, $yAxis);
	}
	function getDepthX() {
		return callmethod("Layer.getDepthX", $this->ptr);
	}
	function getDepthY() {
		return callmethod("Layer.getDepthY", $this->ptr);
	}
	function getXCoor($v) {
		return callmethod("Layer.getXCoor", $this->ptr, $v);
	}
	function getYCoor($v, $yAxis = 1) {
		if (is_object($yAxis))
			return callmethod("Layer.getYCoor2", $this->ptr, $v, $yAxis->ptr);
		else
			return callmethod("Layer.getYCoor", $this->ptr, $v, $yAxis);
	}
	function xZoneColor($threshold, $belowColor, $aboveColor) {
		return callmethod("Layer.xZoneColor", $this->ptr, $threshold, $belowColor, $aboveColor);
	}
	function yZoneColor($threshold, $belowColor, $aboveColor, $yAxis = 1) {
		if (is_object($yAxis))
			return callmethod("Layer.yZoneColor2", $this->ptr, $threshold, $belowColor, $aboveColor, $yAxis->ptr);
		else
			return callmethod("Layer.yZoneColor", $this->ptr, $threshold, $belowColor, $aboveColor, $yAxis);
	}

	function setDataLabelFormat($formatString) {
		callmethod("Layer.setDataLabelFormat", $this->ptr, $formatString);
	}
	function setDataLabelStyle($font = "", $fontSize = 8, $fontColor = TextColor, $fontAngle = 0) {
		return new TextBox(callmethod("Layer.setDataLabelStyle", $this->ptr, $font, $fontSize, $fontColor, $fontAngle));
	}
	function setAggregateLabelFormat($formatString) {
		callmethod("Layer.setAggregateLabelFormat", $this->ptr, $formatString);
	}
	function setAggregateLabelStyle($font = "", $fontSize = 8, $fontColor = TextColor, $fontAngle = 0) {
		return new TextBox(callmethod("Layer.setAggregateLabelStyle", $this->ptr, $font, $fontSize, $fontColor, $fontAngle));
	}
	function addCustomDataLabel($dataSet, $dataItem, $label, $font = "", $fontSize = 8, $fontColor = TextColor, $fontAngle = 0) {
		return new TextBox(callmethod("Layer.addCustomDataLabel", $this->ptr, $dataSet, $dataItem, $label, $font, $fontSize, $fontColor, $fontAngle));
	}
	function addCustomAggregateLabel($dataItem, $label, $font = "", $fontSize = 8, $fontColor = TextColor, $fontAngle = 0) {
		return new TextBox(callmethod("Layer.addCustomAggregateLabel", $this->ptr, $dataItem, $label, $font, $fontSize, $fontColor, $fontAngle));
	}
	function addCustomGroupLabel($dataGroup, $dataItem, $label, $font = "", $fontSize = 8, $fontColor = TextColor, $fontAngle = 0) {
		return new TextBox(callmethod("Layer.addCustomGroupLabel", $this->ptr, $dataGroup, $dataItem, $label, $font, $fontSize, $fontColor, $fontAngle));
	}
	
	function getImageCoor($dataSet, $dataItem = Null, $offsetX = 0, $offsetY = 0) {
		if (is_null($dataItem))
			return $this->getImageCoor2($dataSet, $offsetX, $offsetY);
		return callmethod("Layer.getImageCoor", $this->ptr, $dataSet, $dataItem, $offsetX, $offsetY);
	}
	function getImageCoor2($dataItem, $offsetX = 0, $offsetY = 0) {
		return callmethod("Layer.getImageCoor2", $this->ptr, $dataItem, $offsetX, $offsetY);
	}
	function getHTMLImageMap($url, $queryFormat = "", $extraAttr = "", $offsetX = 0, $offsetY = 0) {
		return callmethod("Layer.getHTMLImageMap", $this->ptr, $url, $queryFormat, $extraAttr, $offsetX, $offsetY);
	}
	function setHTMLImageMap($url, $queryFormat = "", $extraAttr = "") {
		return callmethod("Layer.setHTMLImageMap", $this->ptr, $url, $queryFormat, $extraAttr);
	}
}

class BarLayer extends Layer {
	function BarLayer($ptr) {
		$this->ptr = $ptr;
	}
	function setBarGap($barGap, $subBarGap = 0.2) {
		callmethod("BarLayer.setBarGap", $this->ptr, $barGap, $subBarGap);
	}
	function setBarWidth($barWidth, $subBarWidth = -1) {
		callmethod("BarLayer.setBarWidth", $this->ptr, $barWidth, $subBarWidth);
	}
	function setMinLabelSize($s) {
		callmethod("BarLayer.setMinLabelSize", $this->ptr, $s);
	}
	function setMinImageMapSize($s)	{ 
		callmethod("BarLayer.setMinImageMapSize", $this->ptr, s); 
	}
	function setBarShape($shape, $dataGroup = -1, $dataItem = -1) {
		if (is_array($shape))
			$this->setBarShape2($shape, $dataGroup, $dataItem);
		else
			callmethod("BarLayer.setBarShape", $this->ptr, $shape, $dataGroup, $dataItem);
	}
	function setBarShape2($shape, $dataGroup = -1, $dataItem = -1) {
		callmethod("BarLayer.setBarShape2", $this->ptr, $shape, $dataGroup, $dataItem);
	}
	function setIconSize($height, $width = -1) {
		callmethod("BarLayer.setIconSize", $this->ptr, $height, $width);
	}
	function setOverlapRatio($overlapRatio, $firstOnTop = 1) {
		callmethod("BarLayer.setOverlapRatio", $this->ptr, $overlapRatio, $firstOnTop);
	}
}

class LineLayer extends Layer {
	function LineLayer($ptr) {
		$this->ptr = $ptr;
	}
	function setSymbolScale($zDataX, $scaleTypeX = PixelScale, $zDataY = Null, $scaleTypeY = PixelScale) {
		callmethod("LineLayer.setSymbolScale", $this->ptr, $zDataX, $scaleTypeX, $zDataY, $scaleTypeY);
	}
	function setGapColor($lineColor, $lineWidth = -1) {
		callmethod("LineLayer.setGapColor", $this->ptr, $lineColor, $lineWidth);
	}
	function setImageMapWidth($width) {
		callmethod("LineLayer.setImageMapWidth", $this->ptr, $width);
	}
	function setFastLineMode($b = true) {
		callmethod("LineLayer.setFastLineMode", $this->ptr, $b);
	}
	function getLine($dataSet = 0) {
		return callmethod("LineLayer.getLine", $this->ptr, $dataSet);
	}
}

class ScatterLayer extends LineLayer {
	function ScatterLayer($ptr) {
		$this->ptr = $ptr;
	}
}

class InterLineLayer extends LineLayer {
	function InterLineLayer($ptr) {
		$this->ptr = $ptr;
	}
	function setGapColor($gapColor12, $gapColor21 = -1) {
		return callmethod("InterLineLayer.setGapColor", $this->ptr, $gapColor12, $gapColor21);
	}
}

class SplineLayer extends LineLayer {
	function SplineLayer($ptr) {
		$this->ptr = $ptr;
	}
	function setTension($tension) {
		return callmethod("SplineLayer.setTension", $this->ptr, $tension);
	}
	function setMonotonicity($m) { 
		callmethod("SplineLayer.setMonotonicity", $this->ptr, $m); 
	}
}

class StepLineLayer extends LineLayer {
	function StepLineLayer($ptr) {
		$this->ptr = $ptr;
	}
	function setAlignment($a) {
		return callmethod("StepLineLayer.getLine", $this->ptr, $a);
	}
}

class AreaLayer extends Layer {
	function AreaLayer($ptr) {
		$this->ptr = $ptr;
	}
	function setMinLabelSize($s) {
		callmethod("AreaLayer.setMinLabelSize", $this->ptr, $s);
	}
	function setGapColor($fillColor) {
		callmethod("AreaLayer.setGapColor", $this->ptr, $fillColor);
	}
}

class TrendLayer extends Layer {
	function TrendLayer($ptr) {
		$this->ptr = $ptr;
	}
	function setImageMapWidth($width) {
		callmethod("TrendLayer.setImageMapWidth", $this->ptr, $width);
	}
	function getLine() {
		return callmethod("TrendLayer.getLine", $this->ptr);
	}
	function setRegressionType($regressionType) { 
		callmethod("TrendLayer.setRegressionType", $this->ptr, $regressionType); 
	}
	function addConfidenceBand($confidence, $upperFillColor, $upperEdgeColor = Transparent, $upperLineWidth = 1,
		$lowerFillColor = -1, $lowerEdgeColor = -1, $lowerLineWidth = -1) {
		callmethod("TrendLayer.addConfidenceBand", $this->ptr, $confidence, $upperFillColor, $upperEdgeColor, $upperLineWidth,
			$lowerFillColor, $lowerEdgeColor, $lowerLineWidth);
	}
	function addPredictionBand($confidence, $upperFillColor, $upperEdgeColor = Transparent, $upperLineWidth = 1,
		$lowerFillColor = -1, $lowerEdgeColor = -1, $lowerLineWidth = -1) {
		callmethod("TrendLayer.addPredictionBand", $this->ptr, $confidence, $upperFillColor, $upperEdgeColor, $upperLineWidth,
			$lowerFillColor, $lowerEdgeColor, $lowerLineWidth);
	}	
	function getSlope() {
		return callmethod("TrendLayer.getSlope", $this->ptr);
	}
	function getIntercept() {
		return callmethod("TrendLayer.getIntercept", $this->ptr);
	}
	function getCorrelation() {
		return callmethod("TrendLayer.getCorrelation", $this->ptr);
	}
	function getStdError() {
		return callmethod("TrendLayer.getStdError", $this->ptr);
	}
	function getCoefficient($i) { 
		return callmethod("TrendLayer.getCoefficient", $this->ptr, $i);
	}
}

class BaseBoxLayer extends Layer
{
	function BaseBoxLayer($ptr) {
		$this->ptr = $ptr;
	}
	function setDataGap($gap) {
		callmethod("BaseBoxLayer.setDataGap", $this->ptr, $gap);
	}
	function setDataWidth($width) {
		callmethod("BaseBoxLayer.setDataWidth", $this->ptr, $width);
	}
	function setMinImageMapSize($s)	{ 
		callmethod("BaseBoxLayer.setMinImageMapSize", $this->ptr, s); 
	}
}

class HLOCLayer extends BaseBoxLayer {
	function HLOCLayer($ptr) {
		$this->ptr = $ptr;
	}
	function setColorMethod($colorMethod, $riseColor, $fallColor = -1, $leadValue = -1.7E308) {
		callmethod("HLOCLayer.setColorMethod", $this->ptr, $colorMethod, $riseColor, $fallColor, $leadValue);
	}
}

class CandleStickLayer extends BaseBoxLayer {
	function CandleStickLayer($ptr) {
		$this->ptr = $ptr;
	}
}

class BoxWhiskerLayer extends BaseBoxLayer {
	function BoxWhiskerLayer($ptr) {
		$this->ptr = $ptr;
	}
	function setBoxColors($colors, $names = Null) {
		callmethod("BoxWhiskerLayer.setBoxColors", $this->ptr, $colors, $names);
	}
	function setBoxColor($item, $boxColor) {
		callmethod("BoxWhiskerLayer.setBoxColor", $this->ptr, $item, $boxColor);
	}
	function setWhiskerBrightness($whiskerBrightness) {
		callmethod("BoxWhiskerLayer.setWhiskerBrightness", $this->ptr, $whiskerBrightness);
	}
}

class VectorLayer extends Layer
{
	function VectorLayer($ptr) {
		$this->ptr = $ptr;
	}
 	function setVector($lengths, $directions, $lengthScale = PixelScale) {
		callmethod("VectorLayer.setVector", $this->ptr, $lengths, $directions, $lengthScale);
	}
	function setArrowHead($width, $height = 0) {
		if (is_array($width))
			$this->setArrowHead2($width);
		else
			callmethod("VectorLayer.setArrowHead", $this->ptr, $width, $height);
	}
	function setArrowHead2($polygon) {
		callmethod("VectorLayer.setArrowHead2", $this->ptr, $polygon);
	}
	function setArrowStem($polygon) {
		callmethod("VectorLayer.setArrowStem", $this->ptr, $polygon);
	}
	function setArrowAlignment($alignment) {
		callmethod("VectorLayer.setArrowAlignment", $this->ptr, $alignment);
	}
	function setIconSize($height, $width = 0) {
		callmethod("VectorLayer.setIconSize", $this->ptr, $height, $width);
	}
	function setVectorMargin($startMargin, $endMargin = NoValue) { 
		callmethod("VectorLayer.setVectorMargin", $this->ptr, $startMargin, $endMargin); 
	}
}

class ContourLayer extends Layer
{
	function ContourLayer($ptr) {
		$this->ptr = $ptr;
		$this->colorAxis = $this->colorAxis();
	}
	function setZData($zData) { 
		callmethod("ContourLayer.setZData", $this->ptr, $zData); 
	}
	function setZBounds($minZ, $maxZ) { 
		callmethod("ContourLayer.setZBounds", $this->ptr, $minZ, $maxZ); 
	}
	function setSmoothInterpolation($b) { 
		callmethod("ContourLayer.setSmoothInterpolation", $this->ptr, $b); 
	}
	function setContourColor($contourColor, $minorContourColor = -1) { 
		callmethod("ContourLayer.setContourColor", $this->ptr, $contourColor, $minorContourColor); 
	}
	function setContourWidth($contourWidth, $minorContourWidth = -1) { 
		callmethod("ContourLayer.setContourWidth", $this->ptr, $contourWidth, $minorContourWidth); 
	}
	function setExactContour($contour = true, $markContour = Null) {
		if (is_null($markContour)) 
			$markContour = $contour;
		callmethod("ContourLayer.setExactContour", $this->ptr, $contour, $markContour); 
	}
	function setColorAxis($x, $y, $alignment, $length, $orientation) {
		return new ColorAxis(callmethod("ContourLayer.setColorAxis", $this->ptr, $x, $y, $alignment, $length, $orientation));
	}
	function colorAxis() { 
		return new ColorAxis(callmethod("ContourLayer.colorAxis", $this->ptr)); 
	}
}

class PlotArea {
	function PlotArea($ptr) {
		$this->ptr = $ptr;
	}
	function setBackground($color, $altBgColor = -1, $edgeColor = -1) {
		callmethod("PlotArea.setBackground", $this->ptr, $color, $altBgColor, $edgeColor);
	}
	function setBackground2($img, $align = Center) {
		callmethod("PlotArea.setBackground2", $this->ptr, $img, $align);
	}
	function set4QBgColor($Q1Color, $Q2Color, $Q3Color, $Q4Color, $edgeColor = -1) {
		callmethod("PlotArea.set4QBgColor", $this->ptr, $Q1Color, $Q2Color, $Q3Color, $Q4Color, $edgeColor);
	}
	function setAltBgColor($horizontal, $color1, $color2, $edgeColor = -1) {
		callmethod("PlotArea.setAltBgColor", $this->ptr, $horizontal, $color1, $color2, $edgeColor);
	}
	function setGridColor($hGridColor, $vGridColor = Transparent, $minorHGridColor = -1, $minorVGridColor = -1) {
		callmethod("PlotArea.setGridColor", $this->ptr, $hGridColor, $vGridColor, $minorHGridColor, $minorVGridColor);
	}
	function setGridWidth($hGridWidth, $vGridWidth = -1, $minorHGridWidth = -1, $minorVGridWidth = -1) {
		callmethod("PlotArea.setGridWidth", $this->ptr, $hGridWidth, $vGridWidth, $minorHGridWidth, $minorVGridWidth);
	}
	function setGridAxis($xGridAxis, $yGridAxis) {
		callmethod("PlotArea.setGridAxis", $this->ptr, decodePtr($xGridAxis), decodePtr($yGridAxis)); 
	}
	function moveGridBefore($layer = Null) { 
		callmethod("PlotArea.moveGridBefore", $this->ptr, decodePtr($layer)); 
	}
	function getLeftX() { 
		return callmethod("PlotArea.getLeftX", $this->ptr); 
	}
	function getTopY() { 
		return callmethod("PlotArea.getTopY", $this->ptr); 
	}
	function getRightX() { 
		return callmethod("PlotArea.getRightX", $this->ptr); 
	}
	function getBottomY() { 
		return callmethod("PlotArea.getBottomY", $this->ptr); 
	}
	function getWidth() { 
		return callmethod("PlotArea.getWidth", $this->ptr); 
	}
	function getHeight() { 
		return callmethod("PlotArea.getHeight", $this->ptr); 
	}
}

class XYChart extends BaseChart {
	function XYChart($width, $height, $bgColor = BackgroundColor, $edgeColor = Transparent, $raisedEffect = 0) {
		$this->ptr = callmethod("XYChart.create", $width, $height, $bgColor, $edgeColor, $raisedEffect);
		$this->xAxis = $this->xAxis();
		$this->xAxis2 = $this->xAxis2();
		$this->yAxis = $this->yAxis();
		$this->yAxis2 = $this->yAxis2();
		autoDestroy($this);
	}
	function addAxis($align, $offset) {
		return new Axis(callmethod("XYChart.addAxis", $this->ptr, $align, $offset));
	}
	function yAxis() {
		return new Axis(callmethod("XYChart.yAxis", $this->ptr));
	}
	function yAxis2() {
		return new Axis(callmethod("XYChart.yAxis2", $this->ptr));
	}
	function syncYAxis($slope = 1, $intercept = 0) {
		callmethod("XYChart.syncYAxis", $this->ptr, $slope, $intercept);
	}
	function setYAxisOnRight($b = 1) {
		callmethod("XYChart.setYAxisOnRight", $this->ptr, $b);
	}
	function xAxis() {
		return new Axis(callmethod("XYChart.xAxis", $this->ptr));
	}
	function xAxis2() {
		return new Axis(callmethod("XYChart.xAxis2", $this->ptr));
	}
	function setXAxisOnTop($b = 1) {
		callmethod("XYChart.setXAxisOnTop", $this->ptr, $b);
	}
	function swapXY($b = 1) {
		callmethod("XYChart.swapXY", $this->ptr, $b);
	}
	function setAxisAtOrigin($originMode = XYAxisAtOrigin, $symmetryMode = 0) {
		callmethod("XYChart.setAxisAtOrigin", $this->ptr, $originMode, $symmetryMode);
	}

	function getXCoor($v) {
		return callmethod("XYChart.getXCoor", $this->ptr, $v);
	}
	function getYCoor($v, $yAxis = Null) {
		return callmethod("XYChart.getYCoor", $this->ptr, $v, decodePtr($yAxis));
	}
	function getXValue($xCoor) {
		return callmethod("XYChart.getXValue", $this->ptr, $xCoor);
	}
	function getNearestXValue($xCoor) {
		return callmethod("XYChart.getNearestXValue", $this->ptr, $xCoor);
	}
	function getYValue($yCoor, $yAxis = Null) {
		return callmethod("XYChart.getYValue", $this->ptr, $yCoor, decodePtr($yAxis));
	}

	function xZoneColor($threshold, $belowColor, $aboveColor) {
		return callmethod("XYChart.xZoneColor", $this->ptr, $threshold, $belowColor, $aboveColor);
	}
	function yZoneColor($threshold, $belowColor, $aboveColor, $axis = Null) {
		return callmethod("XYChart.yZoneColor", $this->ptr, $threshold, $belowColor, $aboveColor, decodePtr($axis));
	}

	function setPlotArea($x, $y, $width, $height, $bgColor = Transparent, $altBgColor = -1,
		$edgeColor = -1, $hGridColor = 0xc0c0c0, $vGridColor = Transparent) {
		return new PlotArea(callmethod("XYChart.setPlotArea", $this->ptr,
			$x, $y, $width, $height, $bgColor, $altBgColor, $edgeColor, $hGridColor, $vGridColor));
	}
	function getPlotArea() { 
		return new PlotArea(callmethod("XYChart.getPlotArea", $this->ptr)); 
	}
	function setClipping($margin = 0) {
		callmethod("XYChart.setClipping", $this->ptr, $margin);
	}
	function setTrimData($startPos, $len = 0x7fffffff) {
		callmethod("XYChart.setTrimData", $this->ptr, $startPos, $len);
	}

	function addBarLayer($data = Null, $color = -1, $name = "", $depth = 0) {
		if (!is_null($data))
			return new BarLayer(callmethod("XYChart.addBarLayer", $this->ptr, $data, $color, $name, $depth));
		else
			return $this->addBarLayer2();
	}
	function addBarLayer2($dataCombineMethod = Side, $depth = 0) {
		return new BarLayer(callmethod("XYChart.addBarLayer2", $this->ptr, $dataCombineMethod, $depth));
	}
	function addBarLayer3($data, $colors = Null, $names = Null, $depth = 0) {
		return new BarLayer(callmethod("XYChart.addBarLayer3", $this->ptr, $data, $colors, $names, $depth));
	}
	function addLineLayer($data = Null, $color = -1, $name = "", $depth = 0) {
		if (!is_null($data))
			return new LineLayer(callmethod("XYChart.addLineLayer", $this->ptr, $data, $color, $name, $depth));
		else
			return $this->addLineLayer2();
	}
	function addLineLayer2($dataCombineMethod = Overlay, $depth = 0) {
		return new LineLayer(callmethod("XYChart.addLineLayer2", $this->ptr, $dataCombineMethod, $depth));
	}
	function addAreaLayer($data = Null, $color = -1, $name = "", $depth = 0) {
		if (!is_null($data))
			return new AreaLayer(callmethod("XYChart.addAreaLayer", $this->ptr, $data, $color, $name, $depth));
		else
			return $this->addAreaLayer2();
	}
	function addAreaLayer2($dataCombineMethod = Stack, $depth = 0) {
		return new AreaLayer(callmethod("XYChart.addAreaLayer2", $this->ptr, $dataCombineMethod, $depth));
	}
	function addHLOCLayer($highData = Null, $lowData = Null, $openData = Null, $closeData = Null, $color = -1) {
		if (!is_null($highData))
			return $this->addHLOCLayer3($highData, $lowData, $openData, $closeData, $color, $color);
		else
			return $this->addHLOCLayer2();
	}
	function addHLOCLayer2() {
		return new HLOCLayer(callmethod("XYChart.addHLOCLayer2", $this->ptr));
	}
	function addHLOCLayer3($highData, $lowData, $openData, $closeData, $upColor, $downColor, $colorMode = -1, $leadValue = -1.7E308) {
		return new HLOCLayer(callmethod("XYChart.addHLOCLayer3", $this->ptr, $highData, $lowData, $openData, $closeData, $upColor, $downColor, $colorMode, $leadValue));
	}		
	function addScatterLayer($xData, $yData, $name = "", $symbol = SquareSymbol, $symbolSize = 5, $fillColor = -1, $edgeColor = -1) {
		return new ScatterLayer(callmethod("XYChart.addScatterLayer", $this->ptr, $xData, $yData, $name, $symbol, $symbolSize, $fillColor, $edgeColor));
	}
	function addCandleStickLayer($highData, $lowData, $openData, $closeData, $riseColor = 0xffffff, $fallColor = 0x0, $edgeColor = LineColor) {
		return new CandleStickLayer(callmethod("XYChart.addCandleStickLayer", $this->ptr, $highData, $lowData, $openData, $closeData, $riseColor, $fallColor, $edgeColor));
	}
	function addBoxWhiskerLayer($boxTop, $boxBottom, $maxData = Null, $minData = Null, $midData = Null, $fillColor = -1, $whiskerColor = LineColor, $edgeColor = LineColor) {
		return new BoxWhiskerLayer(callmethod("XYChart.addBoxWhiskerLayer", $this->ptr, $boxTop, $boxBottom, $maxData, $minData, $midData, $fillColor, $whiskerColor, $edgeColor));
	}
	function addBoxWhiskerLayer2($boxTop, $boxBottom, $maxData = Null, $minData = Null, $midData = Null, $fillColors = Null, $whiskerBrightness = 0.5, $names = Null) {
		return new BoxWhiskerLayer(callmethod("XYChart.addBoxWhiskerLayer2", $this->ptr, $boxTop, $boxBottom, $maxData, $minData, $midData, $fillColors, $whiskerBrightness, $names));
	}
	function addBoxLayer($boxTop, $boxBottom, $color = -1, $name = "") {
		return new BoxWhiskerLayer(callmethod("XYChart.addBoxLayer", $this->ptr, $boxTop, $boxBottom, $color, $name));
	}
	function addTrendLayer($data, $color = -1, $name = "", $depth = 0) {
		return new TrendLayer(callmethod("XYChart.addTrendLayer", $this->ptr, $data, $color, $name, $depth));
	}
	function addTrendLayer2($xData, $yData, $color = -1, $name = "", $depth = 0) {
		return new TrendLayer(callmethod("XYChart.addTrendLayer2", $this->ptr, $xData, $yData, $color, $name, $depth));
	}
	function addSplineLayer($data = Null, $color = -1, $name = "") {
		return new SplineLayer(callmethod("XYChart.addSplineLayer", $this->ptr, $data, $color, $name));
	}
	function addStepLineLayer($data = Null, $color = -1, $name = "") {
		return new StepLineLayer(callmethod("XYChart.addStepLineLayer", $this->ptr, $data, $color, $name));
	}
	function addInterLineLayer($line1, $line2, $color12, $color21 = -1) {
		return new InterLineLayer(callmethod("XYChart.addInterLineLayer", $this->ptr, $line1, $line2, $color12, $color21));
	}
	function addVectorLayer($xData, $yData, $lengths, $directions, $lengthScale = PixelScale, $color = -1, $name = "") {
		return new VectorLayer(callmethod("XYChart.addVectorLayer", $this->ptr, $xData, $yData, $lengths, $directions, $lengthScale, $color, $name));
	}
	function addContourLayer($xData, $yData, $zData) {
		return new ContourLayer(callmethod("XYChart.addContourLayer", $this->ptr, $xData, $yData, $zData));
	}
	
	function getLayer($i) {
		return new Layer(callmethod("XYChart.getLayer", $this->ptr, $i));
	}
	function getLayerByZ($i) {
		return new Layer(callmethod("XYChart.getLayerByZ", $this->ptr, $i));
	}
	function getLayerCount() {
		return callmethod("XYChart.getLayerCount", $this->ptr);
	}	

	function layoutAxes() { 
		callmethod("XYChart.layoutAxes", $this->ptr); 
	}
	function packPlotArea($leftX, $topY, $rightX, $bottomY, $minWidth = 0, $minHeight = 0) {
		callmethod("XYChart.packPlotArea", $this->ptr, $leftX, $topY, $rightX, $bottomY, $minWidth, $minHeight); 
	}
}

class ThreeDChart extends BaseChart
{
	function setPlotRegion($cx, $cy, $xWidth, $yDepth, $zHeight) {
		callmethod("ThreeDChart.setPlotRegion", $this->ptr, $cx, $cy, $xWidth, $yDepth, $zHeight); 
	}
	function setViewAngle($elevation, $rotation = 0, $twist = 0) {
		callmethod("ThreeDChart.setViewAngle", $this->ptr, $elevation, $rotation, $twist); 
	}
	function setPerspective($perspective)	{ 
		callmethod("ThreeDChart.setPerspective", $this->ptr, $perspective); 
	}

	function xAxis() { 
		return new Axis(callmethod("ThreeDChart.xAxis", $this->ptr));
	}
	function yAxis() { 
		return new Axis(callmethod("ThreeDChart.yAxis", $this->ptr));
	}
	function zAxis() { 
		return new Axis(callmethod("ThreeDChart.zAxis", $this->ptr));
	}
	function setZAxisPos($pos) { 
		callmethod("ThreeDChart.setZAxisPos", $this->ptr, $pos); 
	}

	function setColorAxis($x, $y, $alignment, $length, $orientation) { 
		return new ColorAxis(callmethod("ThreeDChart.setColorAxis", $this->ptr, $x, $y, $alignment, $length, $orientation)); 
	}
	function colorAxis() { 
		return new ColorAxis(callmethod("ThreeDChart.colorAxis", $this->ptr)); 
	}
	
	function setWallVisibility($xyVisible, $yzVisible, $zxVisible) { 
		callmethod("ThreeDChart.setWallVisibility", $this->ptr, $xyVisible, $yzVisible, $zxVisible); 
	}
	function setWallColor($xyColor, $yzColor = -1, $zxColor = -1, $borderColor = -1) { 
		callmethod("ThreeDChart.setWallColor", $this->ptr, $xyColor, $yzColor, $zxColor, $borderColor); 
	}
	function setWallThickness($xyThickness, $yzThickness = -1, $zxThickness = -1) { 
		callmethod("ThreeDChart.setWallThickness", $this->ptr, $xyThickness, $yzThickness, $zxThickness); 
	}
	function setWallGrid($majorXGridColor, $majorYGridColor = -1, $majorZGridColor = -1, 
		$minorXGridColor = -1, $minorYGridColor = -1, $minorZGridColor = -1) { 
		callmethod("ThreeDChart.setWallGrid", $this->ptr, $majorXGridColor, $majorYGridColor, $majorZGridColor,
			$minorXGridColor, $minorYGridColor, $minorZGridColor); 
	}
}

class SurfaceChart extends ThreeDChart
{
	function SurfaceChart($width, $height, $bgColor = BackgroundColor, $edgeColor = Transparent, $raisedEffect = 0) {
		$this->ptr = callmethod("SurfaceChart.create", $width, $height, $bgColor, $edgeColor, $raisedEffect);
		$this->xAxis = $this->xAxis();
		$this->yAxis = $this->yAxis();
		$this->zAxis = $this->zAxis();
		$this->colorAxis = $this->colorAxis();
		autoDestroy($this);
	}

	function setData($xData, $yData, $zData) {
		callmethod("SurfaceChart.setData", $this->ptr, $xData, $yData, $zData); 
	}
	function setInterpolation($xSamples, $ySamples = -1, $isSmooth = 1) {
		callmethod("SurfaceChart.setInterpolation", $this->ptr, $xSamples, $ySamples, $isSmooth); 
	}
	
	function setLighting($ambientIntensity, $diffuseIntensity, $specularIntensity, $shininess) {
		callmethod("SurfaceChart.setLighting", $this->ptr, $ambientIntensity, $diffuseIntensity, $specularIntensity, $shininess); 
	}
	function setShadingMode($shadingMode, $wireWidth = 1)	{ 
		callmethod("SurfaceChart.setShadingMode", $this->ptr, $shadingMode, $wireWidth); 
	}

	function setSurfaceAxisGrid($majorXGridColor, $majorYGridColor = -1, $minorXGridColor = -1, $minorYGridColor = -1) { 
		callmethod("SurfaceChart.setSurfaceAxisGrid", $this->ptr, $majorXGridColor, $majorYGridColor, 
			$minorXGridColor, $minorYGridColor); 
	}
	function setSurfaceDataGrid($xGridColor, $yGridColor = -1) { 
		callmethod("SurfaceChart.setSurfaceDataGrid", $this->ptr, $xGridColor, $yGridColor); 
	}
	function setContourColor($contourColor, $minorContourColor = -1) { 
		callmethod("SurfaceChart.setContourColor", $this->ptr, $contourColor, $minorContourColor); 
	}

	function setBackSideBrightness($brightness) { 
		callmethod("SurfaceChart.setBackSideBrightness", $this->ptr, $brightness); 
	}
	function setBackSideColor($color) { 
		callmethod("SurfaceChart.setBackSideColor", $this->ptr, $color); 
	}
	function setBackSideLighting($ambientLight, $diffuseLight, $specularLight, $shininess) { 
		callmethod("SurfaceChart.setBackSideLighting", $this->ptr, $ambientLight, $diffuseLight, $specularLight, $shininess); 
	}
}

class ThreeDScatterGroup {
	function ThreeDScatterGroup($ptr) {
		$this->ptr = $ptr;
	}
	function setDataSymbol($symbol, $size = Null, $fillColor = -1, $edgeColor = -1, $lineWidth = 1) {
		if (is_array($symbol)) {
			if (is_null($size))
				$size = 11;
			$this->setDataSymbol4($symbol, $size, $fillColor, $edgeColor);
			return;
		}
	    if (!is_numeric($symbol))
        	return $this->setDataSymbol2($symbol);
        if (is_null($size))
        	$size = 5;
		callmethod("ThreeDScatterGroup.setDataSymbol", $this->ptr, $symbol, $size, $fillColor, $edgeColor, $lineWidth);
	}
	function setDataSymbol2($image) {
	    if (!is_string($image))
        	return $this->setDataSymbol3($image);
		callmethod("ThreeDScatterGroup.setDataSymbol2", $this->ptr, $image);
	}
	function setDataSymbol3($image) {
		callmethod("ThreeDScatterGroup.setDataSymbol3", $this->ptr, $image->ptr);
	}
	function setDataSymbol4($polygon, $size = 11, $fillColor = -1, $edgeColor = -1) {
		callmethod("ThreeDScatterGroup.setDataSymbol4", $this->ptr, $polygon, $size, $fillColor, $edgeColor);
	}
	function setDropLine($dropLineColor = LineColor, $dropLineWidth = 1) {
		callmethod("ThreeDScatterGroup.setDropLine", $this->ptr, $dropLineColor, $dropLineWidth);
	}
	function setLegendIcon($width, $height = -1, $color = -1) {
		callmethod("ThreeDScatterGroup.setLegendIcon", $this->ptr, $width, $height, $color);
	}
}

class ThreeDScatterChart extends ThreeDChart
{
	function ThreeDScatterChart($width, $height, $bgColor = BackgroundColor, $edgeColor = Transparent, $raisedEffect = 0) {
		$this->ptr = callmethod("ThreeDScatterChart.create", $width, $height, $bgColor, $edgeColor, $raisedEffect);
		$this->xAxis = $this->xAxis();
		$this->yAxis = $this->yAxis();
		$this->zAxis = $this->zAxis();
		$this->colorAxis = $this->colorAxis();
		autoDestroy($this);
	}

	function addScatterGroup($xData, $yData, $zData, $name = "", $symbol = CircleSymbol, $symbolSize = 5, $fillColor = -1, $edgeColor = -1) {
		return new ThreeDScatterGroup(callmethod("ThreeDScatterChart.addScatterGroup", $this->ptr, $xData, $yData, $zData, $name, $symbol, $symbolSize, $fillColor, $edgeColor)); 
	}
}

class PolarLayer
{
	function PolarLayer($ptr) {
		$this->ptr = $ptr;
	}
	function setData($data, $color = -1, $name = "") {
		callmethod("PolarLayer.setData", $this->ptr, $data, $color, $name);
	}
	function setAngles($angles) {
		callmethod("PolarLayer.setAngles", $this->ptr, $angles);
	}

	function setBorderColor($edgeColor) {
		callmethod("PolarLayer.setBorderColor", $this->ptr, $edgeColor);
	}
	function setLineWidth($w) {
		callmethod("PolarLayer.setLineWidth", $this->ptr, $w);
	}

	function setDataSymbol($symbol, $size = Null, $fillColor = -1, $edgeColor = -1, $lineWidth = 1) {
	    if (is_array($symbol)) {
	    	if (is_null($size))
	    		$size = 11;
	    	$this->setDataSymbol4($symbol, $size, $fillColor, $edgeColor);
	    	return;
	    }
	    if (!is_numeric($symbol))
        	return $this->setDataSymbol2($symbol);
		if (is_null($size))
			$size = 7;
		callmethod("PolarLayer.setDataSymbol", $this->ptr, $symbol, $size, $fillColor, $edgeColor, $lineWidth);
	}
	function setDataSymbol2($image) {
	    if (!is_string($image))
        	return $this->setDataSymbol3($image);
		callmethod("PolarLayer.setDataSymbol2", $this->ptr, $image);
	}
	function setDataSymbol3($image) {
		callmethod("PolarLayer.setDataSymbol3", $this->ptr, $image->ptr);
	}
	function setDataSymbol4($polygon, $size = 11, $fillColor = -1, $edgeColor = -1) {
		callmethod("PolarLayer.setDataSymbol4", $this->ptr, $polygon, $size, $fillColor, $edgeColor);
	}
	function setSymbolScale($zData, $scaleType = PixelScale) {
		callmethod("PolarLayer.setSymbolScale", $this->ptr, $zData, $scaleType);
	}	

	function setImageMapWidth($width) {
		callmethod("PolarLayer.setImageMapWidth", $this->ptr, $width);
	}
	function getImageCoor($dataItem, $offsetX = 0, $offsetY = 0) {
		return callmethod("PolarLayer.getImageCoor", $this->ptr, $dataItem, $offsetX, $offsetY);
	}
	function getHTMLImageMap($url, $queryFormat = "", $extraAttr = "", $offsetX = 0, $offsetY = 0) {
		return callmethod("PolarLayer.getHTMLImageMap", $this->ptr, $url, $queryFormat, $extraAttr, $offsetX, $offsetY);
	}
	function setHTMLImageMap($url, $queryFormat = "", $extraAttr = "") {
		callmethod("PolarLayer.setHTMLImageMap", $this->ptr, $url, $queryFormat, $extraAttr);
	}
	function setDataLabelFormat($formatString) {
		callmethod("PolarLayer.setDataLabelFormat", $this->ptr, $formatString);
	}
	function setDataLabelStyle($font = "", $fontSize = 8, $fontColor = TextColor, $fontAngle = 0) {
		return new TextBox(callmethod("PolarLayer.setDataLabelStyle", $this->ptr, $font, $fontSize, $fontColor, $fontAngle));
	}
	function addCustomDataLabel($i, $label, $font = "", $fontSize = 8, $fontColor = TextColor, $fontAngle = 0) {
		return new TextBox(callmethod("PolarLayer.addCustomDataLabel", $this->ptr, $i, $label, $font, $fontSize, $fontColor, $fontAngle));
	}
}

class PolarAreaLayer extends PolarLayer {
	function PolarAreaLayer($ptr) {
		$this->ptr = $ptr;
	}
}

class PolarLineLayer extends PolarLayer {
	function PolarLineLayer($ptr) {
		$this->ptr = $ptr;
	}
	function setCloseLoop($b) {
		callmethod("PolarLineLayer.setCloseLoop", $this->ptr, $b);
	}
	function setGapColor($lineColor, $lineWidth = -1) {
		callmethod("PolarLineLayer.setGapColor", $this->ptr, $lineColor, $lineWidth);
	}
}

class PolarSplineLineLayer extends PolarLineLayer {
	function PolarSplineLineLayer($ptr) {
		$this->ptr = $ptr;
	}
	function setTension($tension) {
		callmethod("PolarSplineLineLayer.setTension", $this->ptr, $tension);
	}
}

class PolarSplineAreaLayer extends PolarAreaLayer {
	function PolarSplineAreaLayer($ptr) {
		$this->ptr = $ptr;
	}
	function setTension($tension) {
		callmethod("PolarSplineAreaLayer.setTension", $this->ptr, $tension);
	}
}

class PolarVectorLayer extends PolarLayer
{
	function PolarVectorLayer($ptr) {
		$this->ptr = $ptr;
	}	
 	function setVector($lengths, $directions, $lengthScale = PixelScale) {
		callmethod("PolarVectorLayer.setVector", $this->ptr, $lengths, $directions, $lengthScale);
	}
	function setArrowHead($width, $height = 0) {
		if (is_array($width))
			$this->setArrowHead2($width);
		else
			callmethod("PolarVectorLayer.setArrowHead", $this->ptr, $width, $height);
	}
	function setArrowHead2($polygon) {
		callmethod("PolarVectorLayer.setArrowHead2", $this->ptr, $polygon);
	}
	function setArrowStem($polygon) {
		callmethod("PolarVectorLayer.setArrowStem", $this->ptr, $polygon);
	}
	function setArrowAlignment($alignment) {
		callmethod("PolarVectorLayer.setArrowAlignment", $this->ptr, $alignment);
	}
	function setIconSize($height, $width = 0) {
		callmethod("PolarVectorLayer.setIconSize", $this->ptr, $height, $width);
	}
	function setVectorMargin($startMargin, $endMargin = NoValue) { 
		callmethod("PolarVectorLayer.setVectorMargin", $this->ptr, $startMargin, $endMargin); 
	}
}

class PolarChart extends BaseChart
{
	function PolarChart($width, $height, $bgColor = BackgroundColor, $edgeColor = Transparent, $raisedEffect = 0) {
		$this->ptr = callmethod("PolarChart.create", $width, $height, $bgColor, $edgeColor, $raisedEffect);
		$this->angularAxis = $this->angularAxis();
		$this->radialAxis = $this->radialAxis();
		autoDestroy($this);
	}
	function setPlotArea($x, $y, $r, $bgColor = Transparent, $edgeColor = Transparent, $edgeWidth = 1) {
		callmethod("PolarChart.setPlotArea", $this->ptr, $x, $y, $r, $bgColor, $edgeColor, $edgeWidth);
	}
	function setPlotAreaBg($bgColor1, $bgColor2 = -1, $altRings = 1) {
		callmethod("PolarChart.setPlotAreaBg", $this->ptr, $bgColor1, $bgColor2, $altRings);
	}
	function setGridColor($rGridColor = 0x80000000, $rGridWidth = 1, $aGridColor = 0x80000000, $aGridWidth = 1) {
		callmethod("PolarChart.setGridColor", $this->ptr, $rGridColor, $rGridWidth, $aGridColor, $aGridWidth);
	}
	function setGridStyle($polygonGrid, $gridOnTop = 1) {
		callmethod("PolarChart.setGridStyle", $this->ptr, $polygonGrid, $gridOnTop);
	}
	function setStartAngle($startAngle, $clockwise = 1) {
		callmethod("PolarChart.setStartAngle", $this->ptr, $startAngle, $clockwise);
	}

	function angularAxis() {
		return new AngularAxis(callmethod("PolarChart.angularAxis", $this->ptr));
	}
	function radialAxis() {
		return new Axis(callmethod("PolarChart.radialAxis", $this->ptr));
	}
	function getXCoor($r, $a) {
		return callmethod("PolarChart.getXCoor", $this->ptr, $r, $a);
	}
	function getYCoor($r, $a) {
		return callmethod("PolarChart.getYCoor", $this->ptr, $r, $a);
	}

	function addAreaLayer($data, $color = -1, $name = "") {
		return new PolarAreaLayer(callmethod("PolarChart.addAreaLayer", $this->ptr, $data, $color, $name));
	}
	function addLineLayer($data, $color = -1, $name = "") {
		return new PolarLineLayer(callmethod("PolarChart.addLineLayer", $this->ptr, $data, $color, $name));
	}
	function addSplineLineLayer($data, $color = -1, $name = "") {
		return new PolarSplineLineLayer(callmethod("PolarChart.addSplineLineLayer", $this->ptr, $data, $color, $name));
	}
	function addSplineAreaLayer($data, $color = -1, $name = "") {
		return new PolarSplineAreaLayer(callmethod("PolarChart.addSplineAreaLayer", $this->ptr, $data, $color, $name));
	}
	function addVectorLayer($rData, $aData, $lengths, $directions, $lengthScale = PixelScale, $color = -1, $name = "") {
		return new PolarVectorLayer(callmethod("PolarChart.addVectorLayer", $this->ptr, $rData, $aData, $lengths, $directions, $lengthScale, $color, $name));
	}
}

class PyramidLayer
{
	function PyramidLayer($ptr) {
		$this->ptr = $ptr;
	}	
 
	function setCenterLabel($labelTemplate = "{skip}", $font = "{skip}", $fontSize = -1, $fontColor = -1) {
		return new TextBox(callmethod("PyramidLayer.setCenterLabel", $this->ptr, $labelTemplate, $font, $fontSize, $fontColor));
	}
	function setRightLabel($labelTemplate = "{skip}", $font = "{skip}", $fontSize = -1, $fontColor = -1) {
		return new TextBox(callmethod("PyramidLayer.setRightLabel", $this->ptr, $labelTemplate, $font, $fontSize, $fontColor));
	}
	function setLeftLabel($labelTemplate = "{skip}", $font = "{skip}", $fontSize = -1, $fontColor = -1)	{ 
		return new TextBox(callmethod("PyramidLayer.setLeftLabel", $this->ptr, $labelTemplate, $font, $fontSize, $fontColor));
	}

	function setColor($color) { 
		callmethod("PyramidLayer.setColor", $this->ptr, $color); 
	}
	function setJoinLine($color , $width = -1) { 
		callmethod("PyramidLayer.setJoinLine", $this->ptr, $color, $width); 
	}
	function setJoinLineGap($pyramidGap, $pyramidMargin = -0x7fffffff, $textGap = -0x7fffffff) { 
		callmethod("PyramidLayer.setJoinLineGap", $this->ptr, $pyramidGap, $pyramidMargin, $textGap); 
	}
	function setLayerBorder($color, $width = -1) { 
		callmethod("PyramidLayer.setLayerBorder", $this->ptr, $color, $width); 
	}
	function setLayerGap($layerGap)	{ 
		callmethod("PyramidLayer.setLayerGap", $this->ptr, $layerGap); 
	}
}

class PyramidChart extends BaseChart
{
	function PyramidChart($width, $height, $bgColor = BackgroundColor, $edgeColor = Transparent, $raisedEffect = 0) {
		$this->ptr = callmethod("PyramidChart.create", $width, $height, $bgColor, $edgeColor, $raisedEffect);
		autoDestroy($this);
	}
	
	function setPyramidSize($cx, $cy, $radius, $height)	{ 
		callmethod("PyramidChart.setPyramidSize", $this->ptr, $cx, $cy, $radius, $height); 
	}
	function setConeSize($cx, $cy, $radius, $height) { 
		callmethod("PyramidChart.setConeSize", $this->ptr, $cx, $cy, $radius, $height); 
	}
	function setFunnelSize($cx, $cy, $radius, $height, $tubeRadius = 0.2, $tubeHeight = 0.3) { 
		callmethod("PyramidChart.setFunnelSize", $this->ptr, $cx, $cy, $radius, $height, $tubeRadius, $tubeHeight); 
	}
	function setData($data, $labels = Null)	{ 
		callmethod("PyramidChart.setData", $this->ptr, $data, $labels); 
	}
	function setCenterLabel($labelTemplate = "{skip}", $font = "{skip}", $fontSize = -1, $fontColor = -1) { 
		return new TextBox(callmethod("PyramidChart.setCenterLabel", $this->ptr, $labelTemplate, $font, $fontSize, $fontColor));
	}
	function setRightLabel($labelTemplate = "{skip}", $font = "{skip}", $fontSize = -1, $fontColor = -1) { 
		return new TextBox(callmethod("PyramidChart.setRightLabel", $this->ptr, $labelTemplate, $font, $fontSize, $fontColor));
	}
	function setLeftLabel($labelTemplate = "{skip}", $font = "{skip}", $fontSize = -1, $fontColor = -1)	{ 
		return new TextBox(callmethod("PyramidChart.setLeftLabel", $this->ptr, $labelTemplate, $font, $fontSize, $fontColor));
	}

	function setPyramidSides($noOfSides) { 
		callmethod("PyramidChart.setPyramidSides", $this->ptr, $noOfSides); 
	}
	function setViewAngle($elevation, $rotation = 0, $twist = 0) { 
		callmethod("PyramidChart.setViewAngle", $this->ptr, $elevation, $rotation, $twist); 
	}

	function setGradientShading($startBrightness, $endBrightness) { 
		callmethod("PyramidChart.setGradientShading", $this->ptr, $startBrightness, $endBrightness); 
	}
	function setLighting($ambientIntensity = 0.5, $diffuseIntensity = 0.5, $specularIntensity = 1, $shininess = 8) { 
		callmethod("PyramidChart.setLighting", $this->ptr, $ambientIntensity, $diffuseIntensity, $specularIntensity, $shininess); 
	}

	function setJoinLine($color, $width = -1) { 
		callmethod("PyramidChart.setJoinLine", $this->ptr, $color, $width); 
	}
	function setJoinLineGap($pyramidGap, $pyramidMargin = -0x7fffffff, $textGap = -0x7fffffff) { 
		callmethod("PyramidChart.setJoinLineGap", $this->ptr, $pyramidGap, $pyramidMargin, $textGap); 
	}
	function setLayerBorder($color, $width = -1) { 
		callmethod("PyramidChart.setLayerBorder", $this->ptr, $color, $width); 
	}
	function setLayerGap($layerGap) { 
		callmethod("PyramidChart.setLayerGap", $this->ptr, $layerGap); 
	}

	function getLayer($layerNo)	{ 
		return new PyramidLayer(callmethod("PyramidChart.getLayer", $this->ptr, $layerNo));
	}
}

class MeterPointer
{
	function MeterPointer($ptr) {
		$this->ptr = $ptr;
	}
	function setColor($fillColor, $edgeColor = -1) {
		callmethod("MeterPointer.setColor", $this->ptr, $fillColor, $edgeColor);
	}
	function setPos($value) {
		callmethod("MeterPointer.setPos", $this->ptr, $value);
	}
	function setShape($pointerType, $lengthRatio = NoValue, $widthRatio = NoValue) {
		if (is_array($pointerType))
			$this->setShape2($pointerType, $lengthRatio, $widthRatio);
		else
			callmethod("MeterPointer.setShape", $this->ptr, $pointerType, $lengthRatio, $widthRatio);
	}
	function setShape2($pointerCoor, $lengthRatio = NoValue, $widthRatio = NoValue) {
		callmethod("MeterPointer.setShape2", $this->ptr, $pointerCoor, $lengthRatio, $widthRatio);
	}
	function setZOrder($z) {
		callmethod("MeterPointer.setZOrder", $this->ptr, $z);
	}
}

class BaseMeter extends BaseChart
{
	function addPointer($value, $fillColor = LineColor, $edgeColor = -1) {
		return new MeterPointer(callmethod("BaseMeter.addPointer", $this->ptr, $value, $fillColor, $edgeColor));
	}
	function setScale($lowerLimit, $upperLimit, $majorTickInc = 0, $minorTickInc = 0, $microTickInc = 0) {
		if (is_array($majorTickInc)) {
			if ($minorTickInc != 0)
				$this->setScale3($lowerLimit, $upperLimit, $majorTickInc, $minorTickInc);
			else
				$this->setScale2($lowerLimit, $upperLimit, $majorTickInc);
		} else
			callmethod("BaseMeter.setScale", $this->ptr, $lowerLimit, $upperLimit, $majorTickInc, $minorTickInc, $microTickInc);
	}
	function setScale2($lowerLimit, $upperLimit, $labels) {
		callmethod("BaseMeter.setScale2", $this->ptr, $lowerLimit, $upperLimit, $labels);
	}
	function setScale3($lowerLimit, $upperLimit, $labels, $formatString = "") {
		callmethod("BaseMeter.setScale3", $this->ptr, $lowerLimit, $upperLimit, $labels, $formatString);
	}
	function addLabel($pos, $label) {
		callmethod("BaseMeter.addLabel", $this->ptr, $pos, $label);
	}
	function getLabel($i) {
		return callmethod("BaseMeter.getLabel", $this->ptr, $i);
	}
	function getTicks() {
		return callmethod("BaseMeter.getTicks", $this->ptr);
	}
	function setLabelStyle($font = "bold", $fontSize = -1, $fontColor = TextColor, $fontAngle = 0) {
		return new TextBox(callmethod("BaseMeter.setLabelStyle", $this->ptr, $font, $fontSize, $fontColor, $fontAngle));
	}
	function setLabelPos($labelInside, $labelOffset = 0) {
		callmethod("BaseMeter.setLabelPos", $this->ptr, $labelInside, $labelOffset);
	}
	function setLabelFormat($formatString) {
		callmethod("BaseMeter.setLabelFormat", $this->ptr, $formatString);
	}
	function setTickLength($majorLen, $minorLen = -0x7fffffff, $microLen = -0x7fffffff) {
		callmethod("BaseMeter.setTickLength", $this->ptr, $majorLen, $minorLen, $microLen);
	}
	function setLineWidth($axisWidth, $majorTickWidth = 1, $minorTickWidth = 1, $microTickWidth = 1) {
		callmethod("BaseMeter.setLineWidth", $this->ptr, $axisWidth, $majorTickWidth, $minorTickWidth, $microTickWidth);
	}
	function setMeterColors($axisColor, $labelColor = -1, $tickColor = -1) {
		callmethod("BaseMeter.setMeterColors", $this->ptr, $axisColor, $labelColor, $tickColor);
	}
	function getCoor($v) {
		return callmethod("BaseMeter.getCoor", $this->ptr, $v);
	}
}

class AngularMeter extends BaseMeter
{
	function AngularMeter($width, $height, $bgColor = BackgroundColor, $edgeColor = Transparent, $raisedEffect = 0) {
		$this->ptr = callmethod("AngularMeter.create", $width, $height, $bgColor, $edgeColor, $raisedEffect);
		autoDestroy($this);
	}
	function addRing($startRadius, $endRadius, $fillColor, $edgeColor = -1) {
		callmethod("AngularMeter.addRing", $this->ptr, $startRadius, $endRadius, $fillColor, $edgeColor);
	}
	function addRingSector($startRadius, $endRadius, $a1, $a2, $fillColor, $edgeColor = -1) {
		callmethod("AngularMeter.addRingSector", $this->ptr, $startRadius, $endRadius, $a1, $a2, $fillColor, $edgeColor);
	}
	function setCap($radius, $fillColor, $edgeColor = LineColor) {
		callmethod("AngularMeter.setCap", $this->ptr, $radius, $fillColor, $edgeColor);
	}
	function setMeter($cx, $cy, $radius, $startAngle, $endAngle) {
		callmethod("AngularMeter.setMeter", $this->ptr, $cx, $cy, $radius, $startAngle, $endAngle);
	}
	function addZone($startValue, $endValue, $startRadius, $endRadius = -1, $fillColor = Null, $edgeColor = -1) {
		if (is_null($fillColor))
			$this->addZone2($startValue, $endValue, $startRadius, $endRadius);
		else
			callmethod("AngularMeter.addZone", $this->ptr, $startValue, $endValue, $startRadius, $endRadius, $fillColor, $edgeColor);
	}
	function addZone2($startValue, $endValue, $fillColor, $edgeColor = -1) {
		callmethod("AngularMeter.addZone2", $this->ptr, $startValue, $endValue, $fillColor, $edgeColor);
	}
}

class LinearMeter extends BaseMeter
{
	function LinearMeter($width, $height, $bgColor = BackgroundColor, $edgeColor = Transparent, $raisedEffect = 0) {
		$this->ptr = callmethod("LinearMeter.create", $width, $height, $bgColor, $edgeColor, $raisedEffect);
		autoDestroy($this);
	}
	function setMeter($leftX, $topY, $width, $height, $axisPos = Left, $isReversed = 0) {
		callmethod("LinearMeter.setMeter", $this->ptr, $leftX, $topY, $width, $height, $axisPos, $isReversed);
	}
	function setRail($railColor, $railWidth = 2, $railOffset = 6) {
		callmethod("LinearMeter.setRail", $this->ptr, $railColor, $railWidth, $railOffset);
	}		
	function addZone($startValue, $endValue, $color, $label = "") {
		return new TextBox(callmethod("LinearMeter.addZone", $this->ptr, $startValue, $endValue, $color, $label));
	}
}

function getCopyright() {
	return callmethod("getCopyright");
}

function getVersion() {
	return callmethod("getVersion");
}

function getDescription() {
	return cdFilterMsg(callmethod("getDescription"));
}

function getBootLog() {
	return cdFilterMsg(callmethod("getBootLog"));
}

function libgTTFTest($font = "", $fontIndex = 0, $fontHeight = 8, $fontWidth = 8, $angle = 0) {
    return cdFilterMsg(callmethod("testFont", $font, $fontIndex, $fontHeight, $fontWidth, $angle));
}

function testFont($font = "", $fontIndex = 0, $fontHeight = 8, $fontWidth = 8, $angle = 0) {
    return cdFilterMsg(callmethod("testFont", $font, $fontIndex, $fontHeight, $fontWidth, $angle));
}

function setLicenseCode($licCode) {
    return callmethod("setLicenseCode", $licCode);
}

function chartTime($y, $m = Null, $d = 1, $h = 0, $n = 0, $s = 0) {
	if (is_null($m))
		return chartTime2($y);
	else
	    return callmethod("chartTime", $y, $m, $d, $h, $n, $s);
}

function chartTime2($t) {
    return callmethod("chartTime2", $t);
}

function getChartYMD($t) {
	return callmethod("getChartYMD", $t);
}
	
function getChartWeekDay($t) {
	return ((int)($t / 86400 + 1)) % 7;
}

class RanTable
{
	function RanTable($seed, $noOfCols, $noOfRows) {
		$this->ptr = callmethod("RanTable.create", $seed, $noOfCols, $noOfRows);
		autoDestroy($this);
	}
	function __del__() {
		callmethod("RanTable.destroy", $this->ptr);
	}
	
	function setCol($colNo, $minValue, $maxValue, $p4 = Null, $p5 = -1E+308, $p6 = 1E+308) {
		if (is_null($p4))
			callmethod("RanTable.setCol", $this->ptr, $colNo, $minValue, $maxValue);
		else
			$this->setCol2($colNo, $minValue, $maxValue, $p4, $p5, $p6);
	}
	function setCol2($colNo, $startValue, $minDelta, $maxDelta, $lowerLimit = -1E+308, $upperLimit = 1E+308) {
		callmethod("RanTable.setCol2", $this->ptr, $colNo, $startValue, $minDelta, $maxDelta, $lowerLimit, $upperLimit);
	}
	function setDateCol($i, $startTime, $tickInc, $weekDayOnly = 0) {
		callmethod("RanTable.setDateCol", $this->ptr, $i, $startTime, $tickInc, $weekDayOnly);
	}
	function setHLOCCols($i, $startValue, $minDelta, $maxDelta,	$lowerLimit = 0, $upperLimit = 1E+308) {
		callmethod("RanTable.setHLOCCols", $this->ptr, $i, $startValue, $minDelta, $maxDelta, $lowerLimit, $upperLimit);
	}
	function selectDate($colNo, $minDate, $maxDate)	{ 
		return callmethod("RanTable.selectDate", $this->ptr, $colNo, $minDate, $maxDate); 
	} 
	function getCol($i) {
		return callmethod("RanTable.getCol", $this->ptr, $i);
	}
}

class RanSeries
{
	function RanSeries($seed) {
		$this->ptr = callmethod("RanSeries.create", $seed);
		autoDestroy($this);
	}
	function __del__() {
		callmethod("RanSeries.destroy", $this->ptr);
	}
	function getSeries($len, $minValue, $maxValue, $p4 = Null, $p5 = -1E+308, $p6 = 1E+308) {
		if (is_null($p4))
			return callmethod("RanSeries.getSeries", $this->ptr, $len, $minValue, $maxValue);
		else
			return $this->getSeries2($len, $minValue, $maxValue, $p4, $p5, $p6);
	}
	function getSeries2($len, $startValue, $minDelta, $maxDelta, $lowerLimit = -1E+308, $upperLimit = 1E+308) {
		return callmethod("RanSeries.getSeries2", $this->ptr, $len, $startValue, $minDelta, $maxDelta, $lowerLimit, $upperLimit);
	}
	function getDateSeries($len, $startTime, $tickInc, $weekDayOnly = false) {
		return callmethod("RanSeries.getDateSeries", $this->ptr, $len, $startTime, $tickInc, $weekDayOnly);
	}
}

class FinanceSimulator
{
	function FinanceSimulator($seed, $startTime, $endTime, $resolution) {
		if (is_int($seed))
			$this->ptr = callmethod("FinanceSimulator.create", $seed, $startTime, $endTime, $resolution);
		else
			$this->ptr = callmethod("FinanceSimulator.create2", $seed, $startTime, $endTime, $resolution);
		autoDestroy($this);
	}
	function __del__() {
		callmethod("FinanceSimulator.destroy", $this->ptr);
	}
	function getTimeStamps() { 
		return callmethod("FinanceSimulator.getTimeStamps", $this->ptr);
	}
	function getHighData() { 
		return callmethod("FinanceSimulator.getHighData", $this->ptr);
	}
	function getLowData() {
		return callmethod("FinanceSimulator.getLowData", $this->ptr);
	}
	function getOpenData() {
		return callmethod("FinanceSimulator.getOpenData", $this->ptr);
	}
	function getCloseData() {
		return callmethod("FinanceSimulator.getCloseData", $this->ptr);
	}
	function getVolData() {
		return callmethod("FinanceSimulator.getVolData", $this->ptr);
	}
}

class ArrayMath
{
	function ArrayMath($a) {
		$this->ptr = callmethod("ArrayMath.create", $a);
		autoDestroy($this);
	}
	function __del__() {
		callmethod("ArrayMath.destroy", $this->ptr);
	}
	
	function add($b) { 
		if (!is_array($b)) 
			$this->add2($b);
		else 
			callmethod("ArrayMath.add", $this->ptr, $b);
		return $this;
	}
	function add2($b) {
		callmethod("ArrayMath.add2", $this->ptr, $b);
		return $this;
	}
	function sub($b) {
		if (!is_array($b)) 
			$this->sub2($b);
		else
			callmethod("ArrayMath.sub", $this->ptr, $b);
		return $this;
	}
	function sub2($b) {
		callmethod("ArrayMath.sub2", $this->ptr, $b);
		return $this;
	}
	function mul($b) {
		if (!is_array($b)) 
			$this->mul2($b);
		else
			callmethod("ArrayMath.mul", $this->ptr, $b);
		return $this;
	}
	function mul2($b) {
		callmethod("ArrayMath.mul2", $this->ptr, $b);
		return $this;
	}
	function div($b) {
		if (!is_array($b)) 
			$this->div2($b);
		else
			callmethod("ArrayMath.div", $this->ptr, $b);
		return $this;
	}
	function div2($b) {
		callmethod("ArrayMath.div2", $this->ptr, $b);
		return $this;
	}
	function financeDiv($b, $zeroByZeroValue) {
		callmethod("ArrayMath.financeDiv", $this->ptr, $b, $zeroByZeroValue);
		return $this;
	}
	function shift($offset = 1, $fillValue = NoValue) {
		callmethod("ArrayMath.shift", $this->ptr, $offset, $fillValue);
		return $this;
	}
	function delta($offset = 1) {
		callmethod("ArrayMath.delta", $this->ptr, $offset);
		return $this;
	}
	function rate($offset = 1) {
		callmethod("ArrayMath.rate", $this->ptr, $offset);
		return $this;
	}
	function abs() {
		callmethod("ArrayMath.abs", $this->ptr);
		return $this;
	}
	function acc() {
		callmethod("ArrayMath.acc", $this->ptr);
		return $this;
	}
	
	function selectGTZ($b = Null, $fillValue = 0) { callmethod("ArrayMath.selectGTZ", $this->ptr, $b, $fillValue); return $this; }
	function selectGEZ($b = Null, $fillValue = 0) { callmethod("ArrayMath.selectGEZ", $this->ptr, $b, $fillValue); return $this; }
	function selectLTZ($b = Null, $fillValue = 0) { callmethod("ArrayMath.selectLTZ", $this->ptr, $b, $fillValue); return $this; }
	function selectLEZ($b = Null, $fillValue = 0) { callmethod("ArrayMath.selectLEZ", $this->ptr, $b, $fillValue); return $this; }
	function selectEQZ($b = Null, $fillValue = 0) { callmethod("ArrayMath.selectEQZ", $this->ptr, $b, $fillValue); return $this; }
	function selectNEZ($b = Null, $fillValue = 0) { callmethod("ArrayMath.selectNEZ", $this->ptr, $b, $fillValue); return $this; }

	function selectStartOfHour($majorTickStep = 1, $initialMargin = 300) {
		callmethod("ArrayMath.selectStartOfHour", $this->ptr, $majorTickStep, $initialMargin);
		return $this; 
	}
	function selectStartOfDay($majorTickStep = 1, $initialMargin = 10800) {
		callmethod("ArrayMath.selectStartOfDay", $this->ptr, $majorTickStep, $initialMargin);
		return $this; 
	}
	function selectStartOfWeek($majorTickStep = 1, $initialMargin = 172800) {
		callmethod("ArrayMath.selectStartOfWeek", $this->ptr, $majorTickStep, $initialMargin);
		return $this; 
	}
	function selectStartOfMonth($majorTickStep = 1, $initialMargin = 432000) {
		callmethod("ArrayMath.selectStartOfMonth", $this->ptr, $majorTickStep, $initialMargin);
		return $this; 
	}
	function selectStartOfYear($majorTickStep = 1, $initialMargin = 5184000) {
		callmethod("ArrayMath.selectStartOfYear", $this->ptr, $majorTickStep, $initialMargin);
		return $this; 
	}
	function selectRegularSpacing($majorTickStep, $minorTickStep = 0, $initialMargin = 0) {
		callmethod("ArrayMath.selectRegularSpacing", $this->ptr, $majorTickStep, $minorTickStep, $initialMargin);
		return $this; 
	}
			
	function trim($startIndex = 0, $len = -1) {
		callmethod("ArrayMath.trim", $this->ptr, $startIndex, $len);
		return $this; 
	}
	function insert($a, $insertPoint = -1) {
		callmethod("ArrayMath.insert", $this->ptr, $a, $insertPoint);
		return $this; 
	}
	function insert2($c, $len, $insertPoint= -1) {
		callmethod("ArrayMath.insert2", $this->ptr, $c, $len, $insertPoint);
		return $this; 
	}
	function replace($a, $b) {
		callmethod("ArrayMath.replace", $this->ptr, $a, $b);
		return $this; 
	}

	function movAvg($interval) {
		callmethod("ArrayMath.movAvg", $this->ptr, $interval);
		return $this; 
	}
	function expAvg($smoothingFactor) {
		callmethod("ArrayMath.expAvg", $this->ptr, $smoothingFactor);
		return $this; 
	}
	function movMed($interval) {
		callmethod("ArrayMath.movMed", $this->ptr, $interval);
		return $this; 
	}
	function movPercentile($interval, $percentile) {
		callmethod("ArrayMath.movPercentile", $this->ptr, $interval, $percentile);
		return $this; 
	}
	function movMax($interval) {
		callmethod("ArrayMath.movMax", $this->ptr, $interval);
		return $this; 
	}
	function movMin($interval) {
		callmethod("ArrayMath.movMin", $this->ptr, $interval);
		return $this; 
	}
	function movStdDev($interval) {
		callmethod("ArrayMath.movStdDev", $this->ptr, $interval);
		return $this; 
	}
	function movCorr($interval, $b = Null) {
		callmethod("ArrayMath.movCorr", $this->ptr, $interval, $b);
		return $this; 
	}
	function lowess($smoothness = 0.25, $iteration = 0) {
		callmethod("ArrayMath.lowess", $this->ptr, $smoothness, $iteration);
		return $this; 
	}
	function lowess2($b, $smoothness = 0.25, $iteration = 0) {
		callmethod("ArrayMath.lowess2", $this->ptr, $b, $smoothness, $iteration);
		return $this; 
	}

	function result() {
		return callmethod("ArrayMath.result", $this->ptr);
	}
	function max() {
		return callmethod("ArrayMath.max", $this->ptr);
	}
	function min() {
		return callmethod("ArrayMath.min", $this->ptr);
	}
	function avg() {
		return callmethod("ArrayMath.avg", $this->ptr);
	}
	function sum() {
		return callmethod("ArrayMath.sum", $this->ptr);
	}
	function stdDev() {
		return callmethod("ArrayMath.stdDev", $this->ptr);
	}
	function med() {
		return callmethod("ArrayMath.med", $this->ptr);
	}
	function percentile($p) {
		return callmethod("ArrayMath.percentile", $this->ptr, $p);
	}
	function maxIndex() {
		return callmethod("ArrayMath.maxIndex", $this->ptr);
	}
	function minIndex() {
		return callmethod("ArrayMath.minIndex", $this->ptr);
	}
	
	function aggregate($srcArray, $aggregateMethod, $param = 50) {
		return callmethod("ArrayMath.aggregate", $this->ptr, $srcArray, $aggregateMethod, $param);
	}
}

#///////////////////////////////////////////////////////////////////////////////////
#//	WebChartViewer implementation
#///////////////////////////////////////////////////////////////////////////////////
define("MouseUsageDefault", 0);
define("MouseUsageScroll", 2);
define("MouseUsageZoomIn", 3);
define("MouseUsageZoomOut", 4);

define("DirectionHorizontal", 0);
define("DirectionVertical", 1);
define("DirectionHorizontalVertical", 2);

class WebChartViewer
{
	function WebChartViewer($id) {
		global $_REQUEST;
		$this->ptr = callmethod("WebChartViewer.create");
		autoDestroy($this);
		$this->putAttrS(":id", $id);
		$s = $id."_JsChartViewerState";
		if (isset($_REQUEST[$s]))
			$this->putAttrS(":state", get_magic_quotes_gpc() ? stripslashes($_REQUEST[$s]) : $_REQUEST[$s]);
	}
	function __del__() {
		callmethod("WebChartViewer.destroy", $this->ptr);
	}
	
	function getId() { return $this->getAttrS(":id"); }
	
	function setImageUrl($url) { $this->putAttrS(":url", $url); }
	function getImageUrl() { return $this->getAttrS(":url"); }
	
	function setImageMap($imageMap) { $this->putAttrS(":map", $imageMap); }
	function getImageMap() { return $this->getAttrS(":map"); }
		
	function setChartMetrics($metrics) { $this->putAttrS(":metrics", $metrics); }
	function getChartMetrics() { return $this->getAttrS(":metrics"); }

	function setChartModel($model) { $this->putAttrS(":model", $model); }
	function getChartModel() { return $this->getAttrS(":model"); }

	function setFullRange($id, $minValue, $maxValue) {
		callmethod("WebChartViewer.setFullRange", $this->ptr, $id, $minValue, $maxValue);
	}
	function getValueAtViewPort($id, $ratio, $isLogScale = false) {
		return callmethod("WebChartViewer.getValueAtViewPort", $this->ptr, $id, $ratio, $isLogScale);
	}
	function getViewPortAtValue($id, $value, $isLogScale = false) {
		return callmethod("WebChartViewer.getViewPortAtValue", $this->ptr, $id, $value, $isLogScale);
	}
	function syncLinearAxisWithViewPort($id, $axis) {
		callmethod("WebChartViewer.syncAxisWithViewPort", $this->ptr, $axis->ptr, $id, 3);
	}	
	function syncLogAxisWithViewPort($id, $axis) {
		callmethod("WebChartViewer.syncAxisWithViewPort", $this->ptr, $axis->ptr, $id, 4);
	}	
	function syncDateAxisWithViewPort($id, $axis) {
		callmethod("WebChartViewer.syncAxisWithViewPort", $this->ptr, $axis->ptr, $id, 5);
	}	

	function makeDelayedMap($imageMap, $compress = 0) {
		global $HTTP_SESSION_VARS, $_SERVER;
		if ($compress) {
			if (!isset($_SERVER['HTTP_ACCEPT_ENCODING']) || !strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip'))
				$compress = 0;
		}

		$mapId = $this->getId()."_map";
		if (!defined('PHP_VERSION_ID'))
			session_register($mapId);
		else if (!session_id()) 
			session_start();

		$b = "<body><!--CD_MAP $imageMap CD_MAP--></body>";
		if ($compress)
			$b = callmethod("WebChartViewer.compressMap", $this->ptr, $b, 4);

		if (isset($HTTP_SESSION_VARS))
			$HTTP_SESSION_VARS[$mapId] = $GLOBALS[$mapId] = $b;
		else
			$_SESSION[$mapId] = $GLOBALS[$mapId] = $b;

		return "img=".$mapId."&isMap=1&id=".uniqid(session_id())."&".SID;	
	}
	
	function renderHTML($extraAttrs = null) {
		global $_SERVER;
		$url = isset($_SERVER["SCRIPT_NAME"]) ? $_SERVER["SCRIPT_NAME"] : "";
		$query = isset($_SERVER["QUERY_STRING"]) ? $_SERVER["QUERY_STRING"] : "";
		return callmethod("WebChartViewer.renderHTML", $this->ptr, $url, $query, $extraAttrs);
	}
	function partialUpdateChart($msg = null, $timeout = 0) {
		header("Content-type: text/html; charset=utf-8");
		return callmethod("WebChartViewer.partialUpdateChart", $this->ptr, $msg, $timeout);	
	}
	function isPartialUpdateRequest() {	global $_REQUEST; return isset($_REQUEST["cdPartialUpdate"]); }
	function isFullUpdateRequest() {
		if ($this->isPartialUpdateRequest())
			return 0;
		global $_REQUEST;
		$s = "_JsChartViewerState";
		foreach($_REQUEST as $k => $v) {
			if (substr($k, -strlen($s)) == $s)
				return 1;
		}
		return 0;
	}
	function isStreamRequest() { global $_REQUEST; return isset($_REQUEST["cdDirectStream"]); }
	function isViewPortChangedEvent() {	return $this->getAttrF(25, 0) != 0; }
	function getSenderClientId() {
		global $_REQUEST;
		if ($this->isPartialUpdateRequest())
			return $_REQUEST["cdPartialUpdate"];
		elseif ($this->isStreamRequest())
			return $_REQUEST["cdDirectStream"];
		else
			return null;
	}

	function getAttrS($attr, $defaultValue = "") {
		return callmethod("WebChartViewer.getAttrS", $this->ptr, $attr, $defaultValue);
	}
	function getAttrF($attr, $defaultValue = 0) {
		return callmethod("WebChartViewer.getAttrF", $this->ptr, $attr, $defaultValue);
	}
	function putAttrF($attr, $value) {
		callmethod("WebChartViewer.putAttrF", $this->ptr, $attr, $value);
	}
	function putAttrS($attr, $value) {
		callmethod("WebChartViewer.putAttrS", $this->ptr, $attr, $value);
	}

	function getViewPortLeft() { return $this->getAttrF(4, 0); }
	function setViewPortLeft($left) { $this->putAttrF(4, $left); }

	function getViewPortTop() { return $this->getAttrF(5, 0); }
	function setViewPortTop($top) { $this->putAttrF(5, $top); }

	function getViewPortWidth() { return $this->getAttrF(6, 1); }
	function setViewPortWidth($width) { $this->putAttrF(6, $width); }

	function getViewPortHeight() { return $this->getAttrF(7, 1); }
	function setViewPortHeight($height) { $this->putAttrF(7, $height); }

	function getSelectionBorderWidth() { return (int)($this->getAttrF(8, 2)); }
	function setSelectionBorderWidth($lineWidth) { $this->putAttrF(8, $lineWidth); }

	function getSelectionBorderColor() { return $this->getAttrS(9, "Black"); }
	function setSelectionBorderColor($color) { $this->putAttrS(9, $color); }

	function getMouseUsage() { return (int)($this->getAttrF(10, MouseUsageDefault)); }
	function setMouseUsage($usage) { $this->putAttrF(10, $usage); }

	function getScrollDirection() { return (int)($this->getAttrF(11, DirectionHorizontal)); }
	function setScrollDirection($direction) { $this->putAttrF(11, $direction); }

	function getZoomDirection() { return (int)($this->getAttrF(12, DirectionHorizontal)); }
	function setZoomDirection($direction) { $this->putAttrF(12, $direction); }

	function getZoomInRatio() { return $this->getAttrF(13, 2); }
	function setZoomInRatio($ratio) { if ($ratio > 0) $this->putAttrF(13, $ratio); }

	function getZoomOutRatio() { return $this->getAttrF(14, 0.5); }
	function setZoomOutRatio($ratio) { if ($ratio > 0) $this->putAttrF(14, $ratio); }

	function getZoomInWidthLimit() { return $this->getAttrF(15, 0.01); }
	function setZoomInWidthLimit($limit) { $this->putAttrF(15, $limit); }

	function getZoomOutWidthLimit() { return $this->getAttrF(16, 1); }
	function setZoomOutWidthLimit($limit) { $this->putAttrF(16, $limit); }

	function getZoomInHeightLimit() { return $this->getAttrF(17, 0.01); }
	function setZoomInHeightLimit($limit) { $this->putAttrF(17, $limit); }

	function getZoomOutHeightLimit() { return $this->getAttrF(18, 1); }
	function setZoomOutHeightLimit($limit) { $this->putAttrF(18, $limit); }
		
	function getMinimumDrag() { return (int)($this->getAttrF(19, 5)); }
	function setMinimumDrag($offset) { $this->putAttrF(19, $offset); }

	function getZoomInCursor() { return $this->getAttrS(20, ""); }
	function setZoomInCursor($cursor) { $this->putAttrS(20, $cursor); }

	function getZoomOutCursor() { return $this->getAttrS(21, ""); }
	function setZoomOutCursor($cursor) { $this->putAttrS(21, $cursor); }

	function getScrollCursor() { return $this->getAttrS(22, ""); }
	function setScrollCursor($cursor) { $this->putAttrS(22, $cursor); }

	function getNoZoomCursor() { return $this->getAttrS(26, ""); }
	function setNoZoomCursor($cursor) { $this->putAttrS(26, $cursor); }

	function getCustomAttr($key) { return $this->getAttrS($key, ""); }
	function setCustomAttr($key, $value) { $this->putAttrS($key, $value); }
}

?>