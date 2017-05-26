<?php
class mailer 
{
    private $mail;
	private $core;

    public function __construct($core) 
	{
		$this->mail = new PHPMailer();
		$this->core = $core;

		$this->mail->isHTML(true);
		$this->mail->setFrom($this->core->config('mailer_email'), $this->core->config('site_title') . ' Mailer');
    }

    public function sendMail($to, $subject, $body, $plainText = NULL, $reply_to = NULL)
	{
		// add in logo
		$body = '<img src="' . $this->core->config('website_url') . 'templates/' . $this->core->config('template') . '/images/icon.png" alt="' . $this->core->config('site_title') . '"><br />' . $body;

		// add in footer
		$body = $body . '<div>
		<hr>
		<p>If you haven\'t registered at <a href="' . $this->core->config('website_url') . '" target="_blank">' . $this->core->config('website_url') . '</a>, please forward this email to '. $this->core->config('contact_email') .' so that we can take action.</p>
		</div>';

        $this->mail->addAddress($to);
		if ($reply_to != NULL)
		{
			$this->mail->addReplyTo($reply_to['email'], $reply_to['name']);
		}
		else
		{
			$this->mail->addReplyTo($this->core->config('contact_email'), $this->core->config('site_title') . ' Admin');
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
