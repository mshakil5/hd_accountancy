<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ScheduleMail extends Mailable
{
    use Queueable, SerializesModels;

    public $scheduleData;

    /**
     * Create a new message instance.
     *
     * @param $scheduleData
     */
    public function __construct($scheduleData)
    {
        $this->scheduleData = $scheduleData;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Meeting Schedule Details')
                    ->view('emails.schedule')
                    ->with('scheduleData', $this->scheduleData);
    }
}
