<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendNextLandReqeuest extends Mailable
{
    use Queueable, SerializesModels;

    public $dataArray;

    /**
     * Create a new message instance.
     *
     * @param array $dataArray
     * @return void
     */
    public function __construct($dataArray)
    {
        $this->dataArray = $dataArray;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->subject($this->dataArray['subject'])
                    ->view('email.' . $this->dataArray['link'] . 'mail.terusan')
                    ->with([
                        'dataArray' => $this->dataArray,
                    ]);
    }
}