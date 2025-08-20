<?php

namespace App\Classes;

use Illuminate\Notifications\Messages\MailMessage;

class CustomMailMessage extends MailMessage
{
    public $theme = 'custom-mailer';

    public function __construct(private CustomMailerManager $customMailerManager)
    {
        $this->viewData = ['cmm' => $this->customMailerManager];
        // $this->markdown('components.custom-mailer.notifications.email', ['cmm' => $this->customMailerManager]);
        $this->view([
            'html' => 'components.custom-mailer.notifications.email',
            'text' => 'components.custom-mailer.notifications.email-text',
        ], $this->viewData);
    }
}
