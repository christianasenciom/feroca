<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificarDenuncia extends Mailable
{
    use Queueable, SerializesModels;

    public $message;
    public $subject;
    public $data;

    /**
     * Create a new message instance.
     */
    public function __construct($message, $subject, $data)
    {
        $this->message = $message;
        $this->subject = $subject;
        $this->data = $data;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'notificacionDenuncia',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        //dd(public_path());
        //dd(storage_path('app\public\1733788798.jpg'));
        //return Attachment::fromPath('D:/GORE/SistemaRondil/Back/api-base-grc-rondas/public/files_rondas/documentos_asambleas/21/1733788798.jpg');
        return [
            //Attachment::fromStorage('app\public\hola.pdf'),
        ];
    }
    public function build()
    {
        // Puedes usar el método 'view()' para especificar la vista del correo
        return $this->view('notificacionDenuncia')
                    ->subject($this->subject)
                    //->with(['contenido' => $this->contenido])
                    ->attach(public_path('files_rondas/documentos_asambleas/21/1733788798.jpg'), [
                        //'as' => 'archivo.pdf', // Nombre con el que se guardará el archivo adjunto
                        //'mime' => 'application/pdf', // Tipo MIME del archivo
                    ]);
    }

}
