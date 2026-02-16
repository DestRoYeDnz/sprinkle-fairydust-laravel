<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StyledHtmlMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $mailSubject,
        public string $htmlContent,
        public ?string $textContent = null,
    ) {}

    public function build(): static
    {
        $mail = $this->subject($this->mailSubject)
            ->html($this->htmlContent);

        if ($this->textContent !== null && trim($this->textContent) !== '') {
            $mail->text('emails.plain-text', [
                'content' => $this->textContent,
            ]);
        }

        return $mail;
    }
}
