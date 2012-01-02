<?php 
/*
Plugin Name: WP-Unit
Description: Enables you to create unit tests for your plugins, run and check the results in a centralized way.
Version: 2.0
Author: WidgiLabs team
Author URI: http://widgilabs.com
*/

define( 'WP_UNIT_DIR', WP_PLUGIN_DIR.'/'.plugin_basename(dirname(__FILE__)) );
define('DIR_TESTCASE', WP_UNIT_DIR.'/testcase'); 

$currdir = getcwd();

//fixes php unit installation problems
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . dirname( __FILE__ ) );


//require_once('wp-load.php');
//chdir(WP_UNIT_DIR);

//require_once ( ABSPATH . WPINC . '/load.php' );

require_once('PHPUnit/Autoload.php');
require_once('PHPUnit/Util/ErrorHandler.php');
require_once('PHPUnit/Framework/TestSuite.php');



add_action('admin_menu', 'wp_unit_add_pages');
function wp_unit_add_pages(){
	$title_menu = 'Run Unit Tests';
	
	$page = add_menu_page('Plugin Unit Testing', 'Unit Testing', 'administrator', 'unittests', 'manage_page');
	add_submenu_page('unittests', $title_menu, $title_menu, 'administrator', 'unittests', 'manage_page');	
}

function manage_page()
{
	// form for issuing the tests
	?>
	<h2><?php _e('Run Unit Tests','themejunkie') ?></h2>
	<p>When you press Run you will be running <strong>every</strong> unit tests defined within the <strong>testcase</strong> directory.</p>
		<form name="runtests" id="runtests" method="post" action="">
			<p class="submit">
			    <input id="submit" type="submit" name="Submit" class="submit" value="<?php _e('Run','themejunkie') ?>" />
			</p>
		</form>	
	<?php 

// ******************************************handle running the tests********************************
	if($_POST['Submit'] =='Run')
	{
		
		/*require all test cases within testcase directory*/
		$files = wptest_get_all_test_files(DIR_TESTCASE);
		foreach ($files as $file) {
			require_once($file);
		}
	
		$classes =  wptest_get_all_test_cases();
	
		// run the tests and print the results
		
		$result = new PHPUnit_Framework_TestResult; 
		$printer = new PHPUnit_TextUI_ResultPrinter;
		list ($result, $printer) = wptest_run_tests($classes, @$opts['t']);	
		
		wptest_print_result($printer,$result);
	
	}

}



	

	
/**********************************************Wordpress Stuff ****************************/
// simple functions for loading and running tests
/*This was retrieve from the unit test framework done by Wordpress*/
function wptest_get_all_test_files($dir) {
	$tests = array();
	$dh = opendir($dir);
	while (($file = readdir($dh)) !== false) {
		if ($file{0} == '.')
				continue;
				
		// these tests clash with other things
		if (in_array(strtolower($file), array('testplugin.php', 'testlocale.php')))
			continue;
		$path = realpath($dir . DIRECTORY_SEPARATOR . $file);
		$fileparts = pathinfo($file);
		if (is_file($path) and $fileparts['extension'] == 'php')
			$tests[] = $path;
		elseif (is_dir($path))
			$tests = array_merge($tests, wptest_get_all_test_files($path));
	}
	closedir($dh);

	return $tests;
}

function wptest_is_descendent($parent, $class) {

	$ancestor = strtolower(get_parent_class($class));

	while ($ancestor) {
		if ($ancestor == strtolower($parent)) return true;
		$ancestor = strtolower(get_parent_class($ancestor));
	}

	return false;
}

function wptest_get_all_test_cases() {
	$test_classes = array();
	$all_classes = get_declared_classes();
	// only classes that extend WPTestCase and have names that don't start with _ are included
	foreach ($all_classes as $class)
		if ($class{0} != '_' and wptest_is_descendent('PHPUnit_Framework_TestCase', $class))
			$test_classes[] = $class;
	return $test_classes;
}

function wptest_run_tests($classes, $classname='') 
{
	$suite = new PHPUnit_Framework_TestSuite(); 
	foreach ($classes as $testcase)
	{	
		if (!$classname or strtolower($testcase) == strtolower($classname)) {
			$suite->addTestSuite($testcase);		
		}
	}
	
	#return PHPUnit::run($suite);
	$result = new PHPUnit_Framework_TestResult; 
	
	require_once('PHPUnit/TextUI/ResultPrinter.php');
	
	$printer = new PHPUnit_TextUI_ResultPrinter(NULL,true,true);
	$result->addListener($printer);

	return array($suite->run($result), $printer);
}

function wptest_print_result(PHPUnit_TextUI_ResultPrinter $printer, PHPUnit_Framework_TestResult $result) {

		
	$pass = $result->passed();
	$number_of_tests = $result->count();
	$detected_failures = $result->failureCount();
	$failures= $result->failures();
	$incompleted_tests= $result->notImplemented();
	$skipped = $result->skipped();
	$number_skipped=$result->skippedCount();
	
	$success = "unsuccessfully";
	$entire_test = $result->wasSuccessful();
	if($entire_test)
		$success = "successfully";	
	//print_r($pass);
	?>
		<h2>Test Results</h2>
		<p>The test suite finished <?php echo $success ?>.<br/>
		In <?php echo $number_of_tests?> tests there was  
		<?php echo $number_skipped?> skiped, <?php echo count($incompleted_tests);?> incomplete,
		<?php echo $detected_failures?> failing and <?php echo count($pass) ?> passed!</p>	
	
	
		<table class="widefat fixed" cellspacing="0">
		<thead>
			<tr>
				<th id="test" class="manage-column" scope="col">Test Case</th>
				<th id="function" class="manage-column" scope="col">Test</th>
				<th id="status" class="manage-column" scope="col">Status</th>
				<th id="Message" class="manage-column" scope="col">Message</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th id="test" class="manage-column" scope="col">Test Case</th>
				<th id="function" class="manage-column" scope="col">Test</th>
				<th id="status" class="manage-column" scope="col">Status</th>
				<th id="Message" class="manage-column" scope="col">Message</th>
			</tr>
			
		</tfoot>
		<tbody>				
			<?php				
				 $passedKeys = array_keys($pass);
				 $skippedKeys = array_keys($skipped);
				 $incompletedKeys = array_keys($incompleted_tests);
				 arrange_results($skippedKeys,'skipped');
				 arrange_results($incompletedKeys,'incompleted');
				 arrange_results($failuresKeys,'failed');
				 arrange_results($passedKeys,'passed');
				 
				 //handle failures
				 foreach ($failures as $failure)
				 {
				 	if($failure instanceof PHPUnit_Framework_TestFailure)
				 	{ 	 		 
				 		$failure_msg = $failure->getExceptionAsString();
		
				 		$failedTest = $failure->failedTest();	
				        if ($failedTest instanceof PHPUnit_Framework_SelfDescribing) 
				      		$testName = $failedTest->toString();
				        else 
				            $testName = get_class($failedTest);
				 
	
	  		          	$var = explode('::',$testName);
						$testname=$var[0];
						$function=$var[1];
			          	echo
						'<tr class="alternate author-self status-publish iedit" valign="top">
						<td class="test-title column-title">
						<strong>'.$testname.'</strong>
						</td>
						<td class="function column-status">
						<strong>'.$function.'</strong>
						</td>
						<td class="status column-status">
						<strong>failed</strong>
						</td>
			          	<td class="message column-status">
						<strong>'.$failure_msg.'</strong>
						</td>';								  			          	
				 	}
				 }				 
			?>
		</tbody>
		</table>
	
	<?php 	
}

function arrange_results($array,$status){
	
	if(!empty($array))
	{
		foreach ($array as $test)
		{	
			$var = explode('::',$test);
					
			$testname=$var[0];
			$function=$var[1];
							
			print_row($testname,$function,$status);
		}
	}				
}
function print_row($test, $function,$status)
{
	echo
	'<tr class="alternate author-self status-publish iedit" valign="top">
	<td class="test-title column-title">
	<strong>'.$test.'</strong>
	</td>
	<td class="function column-status">
	<strong>'.$function.'</strong>
	</td>
	<td class="status column-status">
	<strong>'.$status.'</strong>
	</td>
	<td class="message column-status">
	<strong></strong>
	</td>';
	
}

/**
 * Return and/or display the time from the page start to when function is called.
 *
 * You can get the results and print them by doing:
 * <code>
 * $nTimePageTookToExecute = timer_stop();
 * echo $nTimePageTookToExecute;
 * </code>
 *
 * Or instead, you can do:
 * <code>
 * timer_stop(1);
 * </code>
 * which will do what the above does. If you need the result, you can assign it to a variable, but
 * most cases, you only need to echo it.
 *
 * @since 0.71
 * @global int $timestart Seconds and Microseconds added together from when timer_start() is called
 * @global int $timeend  Seconds and Microseconds added together from when function is called
 *
 * @param int $display Use '0' or null to not echo anything and 1 to echo the total time
 * @param int $precision The amount of digits from the right of the decimal to display. Default is 3.
 * @return float The "second.microsecond" finished time calculation
 */
//function timer_stop( $display = 0, $precision = 3 ) { // if called like timer_stop(1), will echo $timetotal
//	global $timestart, $timeend;
//	$mtime = microtime();
//	$mtime = explode( ' ', $mtime );
//	$timeend = $mtime[1] + $mtime[0];
//	$timetotal = $timeend - $timestart;
//	$r = ( function_exists( 'number_format_i18n' ) ) ? number_format_i18n( $timetotal, $precision ) : number_format( $timetotal, $precision );
//	if ( $display )
//		echo $r;
//	return $r;
//}
/*end WordPress stuff*/




class MyTableAccessor {
	
	/*e.g. $what= status $where=users*/
  function get_data($what, $where) {
    global $wpdb;
	$result = null;
    
    if(isset($what) && isset($where))
		$result =  $wpdb->get_var('SELECT status from users');
    
    return $result;
    
  }
}


?>