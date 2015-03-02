<?php 
    /*
    Plugin Name: Daisycon Transactions (dashboard widget)
    Plugin URI: http://zonneveldcloud.nl
    Description: Display new Daisycon transactions as a dashboard widget in de admin
    Author: Zonneveld Cloud
    Version: 1.0.1
    Author URI: http://zonneveldcloud.nl
    */

//////////// SETTINGS //////////////////////

	// publisher
	$publisher_id = get_option('daisycon_transactions_publisher_id');
	
	// authentication
	$userAndPassword = base64_encode( get_option('daisycon_transactions_username') . ':' . get_option('daisycon_transactions_password') );

///////////////////////////////////////////


function getProgramName($id)
{

	global $publisher_id;
	global $userAndPassword;

	$url = 'https://services.daisycon.com:443/publishers/'.$publisher_id.'/programs/'.$id;
	
	// initialize curl resource
	$ch = curl_init();
	
	// set the http request authentication headers
	$headers = array( 'Authorization: Basic ' . $userAndPassword );
	
	// set curl options
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
	
	// execute curl
	$response = curl_exec($ch);
	
	// check http code
	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
	// close curl resource
	curl_close($ch);
		
	if ($code == 200)
	{
		// json decode reponse
		$program = json_decode($response);
		
		return $program->name;
	}
	else 
	{
		return false;
	}
}



//create dashboard widget
function daisycon_add_dashboard_earnings_widget() {

	wp_add_dashboard_widget(
                 'daisycon_dashboard_earnings_widget',         // Widget slug.
                 'New Daisycon transactions	',  		       // Title.
                 'daisycon_dashboard_earnings_widget_function' // Display function.
        );	
}
add_action( 'wp_dashboard_setup', 'daisycon_add_dashboard_earnings_widget' );

function daisycon_dashboard_earnings_widget_function() {
	
	global $publisher_id, $userAndPassword;

	// service
	$startDate = date("Y-m-d", strtotime( date( "Y-m-d", strtotime( date("Y-m-d") ) ) . "-1 month" ) ); //get all transactions between now and 1 month ago
	//$startDate = '2014-01-01';

	$url = 'https://services.daisycon.com:443/publishers/'.$publisher_id.'/transactions?page=1&per_page=1000&date_click_start='.$startDate;
	
	// initialize curl resource
	$ch = curl_init();
	
	// set the http request authentication headers
	$headers = array( 'Authorization: Basic ' . $userAndPassword );
	
	// set curl options
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
	
	// execute curl
	$response = curl_exec($ch);
	
	// check http code
	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
	// close curl resource
	curl_close($ch);
	
	if ($code == 200)
	{
	    $transactions = json_decode($response);
	    
	    $daisyconTransactions = Array();
	    foreach ($transactions as $transaction)
	    {
	    	$programName = getProgramName($transaction->program_id);
	    	
	    	if(empty($programName))
	    	{
		    	$programName = $transaction->parts[0]->publisher_description;
	    	}
	    	
	    	$daisyconTransactions[] = Array(
	    		'name' => $programName,
	    		'date' => $transaction->date,
	    		'commission' => $transaction->parts[0]->commission,
	    		'status' => $transaction->parts[0]->status
	    	);	
	    	
	    	usort($daisyconTransactions, "sortFunction");
		}
	    
	    
	    $response = '<table width="100%">';
		$response.= '<thead style="font-weight:bold;"><tr>';
	        $response .= '<td width="20px"></td>';
	        $response .= '<td>Publisher</td>';
	        $response .= '<td>Commission</td>';
	        $response .= '<td>Date</td>';
	        $response .= '</tr></thead>';

	    foreach($daisyconTransactions as $daisyconTransaction)
	    	
		{
	    
	    	$response.= '<tr>';
	        $response .= '<td><img width="15" src="'. plugins_url( 'images/'.$daisyconTransaction['status'].'.png', __FILE__ ) . '" /></td>';
	        $response .= '<td>'. $daisyconTransaction['name'] . "</td>";
	        $response .= '<td> &euro; '. number_format($daisyconTransaction['commission'], 2, ',', ' ') . "</td>";
	        $response .= '<td>'. date("d-m-Y", strtotime($daisyconTransaction['date'])) .'</td>';
	        $response .= '</tr>';
	    }
	    
	    $response .= '</table>';
	    
	    echo $response;
	}
} 

// create custom plugin settings menu
add_action('admin_menu', 'create_settings_page');

function create_settings_page() {

	//create new top-level menu
	add_menu_page('Daisycon Transactions', 'Daisycon Transactions', 'administrator', __FILE__, 'daisycon_transaction_settings',plugins_url('/images/daisycon-icon.png', __FILE__));

	//call register settings function
	add_action( 'admin_init', 'save_daisycon_transaction_settings' );
}


function save_daisycon_transaction_settings() {
	//register our settings

	register_setting( 'daisycon-transactions', 'daisycon_transactions_publisher_id' );
	register_setting( 'daisycon-transactions', 'daisycon_transactions_username' );
	register_setting( 'daisycon-transactions', 'daisycon_transactions_password' );
}

function daisycon_transaction_settings() {
?>
<div class="wrap">
<h2>Daisycon Transactions</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'daisycon-transactions' ); ?>
    <?php do_settings_sections( 'daisycon-transactions' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Publisher ID</th>
        <td><input type="text" name="daisycon_transactions_publisher_id" value="<?php echo esc_attr( get_option('daisycon_transactions_publisher_id') ); ?>" /></td>
        </tr>
         
        <tr valign="top">
        <th scope="row">Username</th>
        <td><input type="text" name="daisycon_transactions_username" value="<?php echo esc_attr( get_option('daisycon_transactions_username') ); ?>" /></td>
        </tr>
        
        <tr valign="top">
        <th scope="row">Password</th>
        <td><input type="password" name="daisycon_transactions_password" value="<?php echo esc_attr( get_option('daisycon_transactions_password') ); ?>" /></td>
        </tr>
    </table>
    
    <?php submit_button(); ?>

</form>
</div>
<?php } 

function sortFunction( $a, $b ) {
	return strtotime($b["date"]) - strtotime($a["date"]);
}
?>