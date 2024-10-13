<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CareerFormMail extends Mailable
{
    use Queueable, SerializesModels;

    public $careerData;
    public $cvFileName;

    /**
     * Create a new message instance.
     *
     * @param array $careerData
     * @param string $cvFileName
     */
    public function __construct($careerData, $cvFileName)
    {
        $this->careerData = $careerData;
        $this->cvFileName = $cvFileName;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('New Career Form Submission')
                    ->view('emails.career')
                    ->attach(public_path('images/Cv/' . $this->cvFileName), [
                        'as' => 'CV.' . pathinfo($this->cvFileName, PATHINFO_EXTENSION),
                        'mime' => mime_content_type(public_path('images/Cv/' . $this->cvFileName)),
                    ])
                    ->with('careerData', $this->careerData);
    }
}