<?php
define("APP_ROOT", dirname( dirname(__FILE__) ) . '/public_html');

require APP_ROOT . "/includes/bootstrap.php";

// TODO
/*
NEED TO ADJUST SUPPORTER PLUS, TO TAKE INTO ACCOUNT MULTIPLE PAYMENTS
NEED TO ADD TO SUPPORTER PLUS TIME, IF THEIR ADDITIONAL PAYMENT COVERS IT LIKE WE DO WITH NORMAL PAYMENTS
*/

$start_date = date("Y-m-d", strtotime("first day of this month")) . 'T00:00:00Z';
$end_date = date("Y-m-d") . 'T'. date('H:i:s') . 'Z';

echo 'Start: ' . $start_date . '<br />' . 'End: ' . $end_date;

// live
$info = 'USER=' . $core->config('paypal_api_user')
.'&PWD=' . $core->config('paypal_api_password')
.'&SIGNATURE=' . $core->config('paypal_api_signature')
.'&METHOD=TransactionSearch'
.'&TRANSACTIONCLASS=RECEIVED'
.'&STARTDATE=' . $start_date
.'&ENDDATE=' . $end_date
.'&VERSION=94';


$curl = curl_init('https://api-3t.paypal.com/nvp');
curl_setopt($curl, CURLOPT_FAILONERROR, true);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

curl_setopt($curl, CURLOPT_POSTFIELDS,  $info);
curl_setopt($curl, CURLOPT_HEADER, 0);
curl_setopt($curl, CURLOPT_POST, 1);

$result = curl_exec($curl);

# Bust the string up into an array by the ampersand (&)
# You could also use parse_str(), but it would most likely limit out
$result = explode("&", $result);

# Loop through the new array and further bust up each element by the equal sign (=)
# and then create a new array with the left side of the equal sign as the key and the right side of the equal sign as the value
foreach($result as $value){
    $value = explode("=", $value);
    $temp[$value[0]] = $value[1];
}

# At the time of writing this code, there were 11 different types of responses that were returned for each record
# There may only be 10 records returned, but there will be 110 keys in our array which contain all the different pieces of information for each record
# Now create a 2 dimensional array with all the information for each record together
for($i=0; $i<count($temp)/11; $i++)
{
    $returned_array[$i] = array (
        "timestamp"         =>    urldecode($temp["L_TIMESTAMP".$i]),
        "timezone"          =>    urldecode($temp["L_TIMEZONE".$i]),
        "type"              =>    urldecode($temp["L_TYPE".$i]),
        "email"             =>    urldecode($temp["L_EMAIL".$i]),
        "name"              =>    urldecode($temp["L_NAME".$i]),
        "transaction_id"    =>    urldecode($temp["L_TRANSACTIONID".$i]),
        "status"            =>    urldecode($temp["L_STATUS".$i]),
        "amt"               =>    urldecode($temp["L_AMT".$i]),
        "currency_code"     =>    urldecode($temp["L_CURRENCYCODE".$i]),
        "fee_amount"        =>    urldecode($temp["L_FEEAMT".$i]),
		"net_amount"        =>    urldecode($temp["L_NETAMT".$i])
	);
}

$user_id_list = array();

foreach ($returned_array as $payment)
{
	// ignore patreon
	if (isset($payment['email']) && $payment['email'] != 'support@patreon.com' && $payment['email'] != 'payments@humblebundle.com')
	{
		echo '<pre>';
		print_r($payment);
		echo '</pre>';

		$supporter_status = 0;
		$supporter_plus = 0;
		if ($payment['currency_code'] == 'GBP')
		{
			if ($payment['amt'] >= 3)
			{
				$supporter_status = 1;
				$total_months = round($payment['amt'] / 3);
			}
			if ($payment['amt'] >= 5.50)
			{
				$supporter_plus = 1;
			}
		}
		if ($payment['currency_code'] == 'USD')
		{
			if ($payment['amt'] >= 4)
			{
				$supporter_status = 1;
				$total_months = round($payment['amt'] / 4);
			}
			if ($payment['amt'] >= 7)
			{
				$supporter_plus = 1;
			}
		}
		if ($payment['currency_code'] == 'EUR')
		{
			if ($payment['amt'] >= 3.50)
			{
				$supporter_status = 1;
				$total_months = round($payment['amt'] / 3.50);
			}
			if ($payment['amt'] >= 6.20)
			{
				$supporter_plus = 1;
			}
		}

		if ($supporter_status == 1)
		{
			//echo 'SUPPORTER!';
			//echo '<br />Total months worth: ' . $total_months;

			// basic end date, for those who haven't pledged before
			$end_date = date('Y-m-d H:i:s', strtotime("+".$total_months." months", strtotime($payment['timestamp'])));
			$plus_end_date = date('Y-m-d H:i:s', strtotime("+1 months", strtotime($payment['timestamp'])));
			$last_paid_date = date('Y-m-d H:i:s', strtotime($payment['timestamp']));
			//echo '<br />Last paid date: '.$last_paid_date.'<br />End date: ' . $end_date;

			$their_email = trim($payment['email']);
			$user_info = $dbl->run("SELECT `username`, `user_id`,`supporter_type`,`supporter_end_date`,`supporter_plus_end`,`supporter_last_paid_date` FROM `users` WHERE `email` = ? OR `supporter_email` = ?", array($their_email, $their_email))->fetch();

			if ($user_info)
			{
				$paypal_sql_text = array();
				$paypal_sql_data = array();

				// gather a list of all emails that are eligble for supporter status
				$user_id_list[] = $user_info['user_id'];

				$their_groups = $user->post_group_list([$user_info['user_id']]);

				// they're not currently set as a supporter, give them the status
				if (!in_array(6, $their_groups[$user_info['user_id']]))
				{
					$dbl->run("INSERT INTO `user_group_membership` SET `user_id` = ?, `group_id` = 6", [$user_info['user_id']]);

					//echo "\nGiven Supporter status\n\n";
				}

				// they're not currently set as a supporter plus, give them the status
				if ($supporter_plus == 1)
				{
					if (!in_array(9, $their_groups[$user_info['user_id']]))
					{
						$dbl->run("INSERT INTO `user_group_membership` SET `user_id` = ?, `group_id` = 9", [$user_info['user_id']]);
					}

					if ($user_info['supporter_plus_end '] == NULL || $user_info['supporter_plus_end '] < $plus_end_date)
					{
						$paypal_sql_text[] = "`supporter_plus_end` = ?";
						$paypal_sql_data[] = $plus_end_date;
					}

					//echo "\nGiven Supporter Plus status\n\n";
				}

				// payment wasn't enough for Supporter Plus, remove it if time is up
				if ($supporter_plus == 0)
				{
					$remove_plus = 0;
					if ($user_info['supporter_plus_end '] != NULL)
					{
						$plus_expires = new DateTime($user_info['supporter_plus_end']);
						$now = new DateTime();
	
						if ($plus_expires < $now)
						{
							$remove_plus = 1;
						}
					}
					else
					{
						$remove_plus = 1;
					}

					if (in_array(9, $their_groups[$user_info['user_id']]) && $remove_plus == 1)
					{
						$dbl->run("DELETE FROM `user_group_membership` WHERE `user_id` = ? AND `group_id` = 9", [$user_info['user_id']]);

						//echo "\Removed Supporter Plus status\n\n";
					}
				}

                // if they weren't previously set as a PayPal supporter, set it
				if ($user_info['supporter_type'] != 'paypal')
				{
					$paypal_sql_text[] = "`supporter_type` = 'paypal'";
				}

                // if they hadn't paid before, or the date they last paid is older than the latest
				if ($user_info['supporter_last_paid_date'] == NULL || $user_info['supporter_last_paid_date'] < $last_paid_date)
				{				
					$paypal_sql_text[] = "`supporter_last_paid_date` = ?";
					$paypal_sql_data[] = $last_paid_date;
				}

				// they have no end date or their current end date would be before the new one so update it
				if ($user_info['supporter_end_date'] == NULL || $user_info['supporter_end_date'] < $end_date)
				{
					$paypal_sql_text[] = "`supporter_end_date` = ?";
					$paypal_sql_data[] = $end_date;
				}

				// run what's needed
				if (!empty($paypal_sql_text))
				{
					$dbl->run("UPDATE `users` SET ".implode(', ', $paypal_sql_text)." WHERE `user_id` = ?", array_merge($paypal_sql_data, [$user_info['user_id']]));
				}

				if ($user_info['supporter_last_paid_date'] < $last_paid_date)
				{
					$dbl->run("UPDATE `users` SET `supporter_last_paid_date` = ? WHERE `user_id` = ?", array($last_paid_date, $user_info['user_id']));

					// now deal with people topping it up, we need to add more months to their existing sub to extend it (not replace it if they paid a lot before)
					// say they paid 10 months, now 2 months in they paid another 1 month, need to add it on top
					if ($user_info['supporter_end_date'] > $end_date)
					{
						$new_end_date = date('Y-m-d H:i:s', strtotime("+".$total_months." months", strtotime($user_info['supporter_end_date'])));
						$dbl->run("UPDATE `users` SET `supporter_end_date` = ? WHERE `user_id` = ?", array($new_end_date, $user_info['user_id']));
					}
				}
			}
		}
	}
	unset($supporter_status);
	unset($their_email);
	unset($total_months);
}
?>
