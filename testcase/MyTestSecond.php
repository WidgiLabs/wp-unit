<?php 
/*Dummy Example 2*/
class MyTestSecond extends PHPUnit_Framework_TestCase {

function setUp() {
	global $wpdb;
   
  	
  }
	
	
function testadmin(){	
        
        $current_user = get_current_user();
        $this->assertEquals($current_user, 'admin');
}
  
}



?>