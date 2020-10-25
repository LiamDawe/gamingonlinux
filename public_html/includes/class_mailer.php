<?php
// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class mailer 
{
    private $mail;
	private $core;

    public function __construct($core) 
	{
		$this->mail = new PHPMailer;

		$this->mail->CharSet = 'UTF-8';
		$this->core = $core;

		$this->mail->isHTML(true);
		$this->mail->setFrom($this->core->config('mailer_email'), $this->core->config('site_title') . ' Mailer');
    }

    public function sendMail($to, $subject, $body, $plainText = NULL, $reply_to = NULL)
	{
		// add in logo
		$body = '
		<head><style>.center {
			display: block;
			margin-left: auto;
			margin-right: auto;
		  }</style></head>
		<body bgcolor="#313131" style="margin:0px auto; padding:0px; background-color: #313131;">

		<table style="width:100%; margin-top: 5px">
			  <tbody>
				  <tr><td style="height:22px" height="22">&nbsp;</td></tr>
				  <tr>
					  <td align="center" style="text-align: center;">
					  <img src="' . $this->core->config('static_image_url') . 'logos/' . 'email_logo.png" width="450" height="150" alt="' . $this->core->config('site_title') . '" class="center">
					  </td>
				  </tr>
	  
				  <tr><td style="height:22px" height="22">&nbsp;</td></tr>            <tr>
				  <td>
					  <table style="border-radius: 10px; width: 100%; max-width:1000px; table-layout:fixed; padding-left:25px; padding-right:35px; background-color:#ffffff;" width="100%" cellspacing="0" cellpadding="0" border="0" align="center">
						  <tbody>
							  
							  <tr><td style="height:8px; font-size:0px; line-height:0px" height="8">&nbsp;</td></tr>
							  <tr>
								  <td align="left">' . $body;

		// add in footer
		$body = $body . '</td>
		</tr>

		<tr>
			<td style="height:16px; font-size:1px; line-height:1px;" height="16">&nbsp;</td>
		</tr>
		<tr>
			<td>
		<hr>
		<p><em>If you think you shouldn\'t be getting this email, please forward this email to '. $this->core->config('contact_email') .' so that we can take action.</em></p>
		<p>You can find us on: <a href="https://twitter.com/gamingonlinux">Twitter</a>, <a href="https://mastodon.social/@gamingonlinux">Mastodon</a>, <a href="https://t.me/linuxgaming">Telegram</a>, <a href="https://www.twitch.tv/gamingonlinux">Twitch</a>, <a href="https://discord.gg/0rxBtcSOonvGzXr4">Discord</a>, <a href="https://www.gamingonlinux.com/irc/">IRC</a> and <a href="https://www.youtube.com/gamingonlinux">YouTube</a>.</p>
		</div></tr></td></table></body>';

        $this->mail->addAddress($to);
		if ($reply_to != NULL)
		{
			$this->mail->addReplyTo($reply_to['email'], $reply_to['name']);
		}
        $this->mail->Subject = $subject;
        $this->mail->Body = $body;
		if ($plainText != NULL)
		{
        	$this->mail->AltBody = $plainText;
		}

        return $this->mail->send();
    }
}
