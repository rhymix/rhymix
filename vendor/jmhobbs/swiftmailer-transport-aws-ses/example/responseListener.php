<?php
	/*
	 * Example sending email with AWS.
	 *
	 * 1. Run composer.phar install in the root (next to compser.json)
	 * 2. Copy config.php.example to config.php and add your AWS credentials
	 * 3. Run this script!
	 */
	require_once(__DIR__ . '/../vendor/autoload.php');
	require_once('./config.php');
	
	$transport = Swift_AWSTransport::newInstance( AWSAccessKeyId, AWSSecretKey );
	$transport->setEndpoint( AWSSESEndpoint );
	$transport->setDebug( true ); // Print the response from AWS to the error log for debugging.
	$transport->registerPlugin(
		new Swift_Events_ResponseReceivedListener( function ( $message, $body ) {
			echo sprintf( "Message \"%s\" sent by SES with Message-ID %s. Now you can store it in your database to handle bounces, complaints and deliveries.\n", $message->getSubject(), $body->SendRawEmailResult->MessageId );
		})
	);
	
	//Create the Mailer using your created Transport
	$mailer = Swift_Mailer::newInstance( $transport );

	//Create the message
	$message = Swift_Message::newInstance()
	->setSubject( 'Testing Swiftmailer SES' )
	->setFrom( array( FROM_ADDRESS ) )
	->setTo( array( TO_ADDRESS ) )
	->setBody( "<p>Dude, I'm <b>totally</b> sending you email via AWS.</p>", 'text/html' )
	->addPart( "Dude, I'm _totally_ sending you email via AWS.", 'text/plain' );

	echo "Sending\n";
	try {
		echo "Sent: " . $mailer->send( $message ) . "\n";
	}
	catch( AWSEmptyResponseException $e ) {
		echo $e . "\n";
	}