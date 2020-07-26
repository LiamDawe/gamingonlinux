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
		$body = '<img src="' . $this->core->config('static_image_url') . 'logos/' . 'icon.svg" width="35" height="35" alt="' . $this->core->config('site_title') . '"><br />' . $body;

		// add in footer
		$body = $body . '<div>
		<hr>
		<p><em>If you think you shouldn\'t be getting this email, please forward this email to '. $this->core->config('contact_email') .' so that we can take action.</em></p>
		<p>You can find us on: <a href="https://twitter.com/gamingonlinux">Twitter</a>, <a href="https://mastodon.social/@gamingonlinux">Mastodon</a>, <a href="https://t.me/linuxgaming">Telegram</a>, <a href="https://www.twitch.tv/gamingonlinux">Twitch</a>, <a href="https://discord.gg/0rxBtcSOonvGzXr4">Discord</a>, <a href="https://www.gamingonlinux.com/irc/">IRC</a> and <a href="https://www.youtube.com/gamingonlinux">YouTube</a>.</p>
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
