<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function submitContact(Request $request)
    {
        // on valide les des données du form contact.vue
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        // on recup ensuite les des données validées
        $name = $request->input('name');
        $fromEmail = $request->input('email');
        $subject = $request->input('subject');
        $messageContent = $request->input('message');

        // Configuration de PHPMailer pour l'envoi
        $mail = new PHPMailer(true);

        $auth_email = env('MAIL_USERNAME');
        $auth_password = env('MAIL_PASSWORD');

        if (empty($auth_email) || empty($auth_password)) {
            Log::error("Erreur de configuration SMTP: MAIL_USERNAME ou MAIL_PASSWORD non défini pour Contact.");
            return response()->json(['error' => "Erreur de configuration SMTP interne."], 500);
        }

        try {
            // Configuration SMTP
            $mail->isSMTP();
            $mail->Host = env('MAIL_HOST', 'smtp.gmail.com');
            $mail->SMTPAuth = true;
            $mail->Username = $auth_email;
            $mail->Password = $auth_password;
            // Utilise la configuration du .env (tls sur 587)
            $mail->SMTPSecure = env('MAIL_ENCRYPTION') === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int)env('MAIL_PORT', 587); // Convertit en entier
            $mail->CharSet = 'UTF-8';

            // Expéditeur technique : Doit correspondre à l'utilisateur SMTP (pour l'authentification)
            // DOIT être MAIL_USERNAME pour Gmail
            $mail->setFrom($auth_email, 'Formulaire Contact Portal_Job');

            // Destinataire : Votre adresse e-mail personnelle/d'administration
            $mail->addAddress('seghiriahmed9@gmail.com');

            // Adresse de Réponse : L'e-mail de l'utilisateur. C'est ici que vous répondrez.
            $mail->addReplyTo($fromEmail, $name);

            // Contenu du message
            $mail->isHTML(false);
            $mail->Subject = "Nouveau message de contact : " . $subject;
            $mail->Body = "De: {$name} ({$fromEmail})\n\nSujet: {$subject}\n\nMessage:\n{$messageContent}";

            $mail->send();

            // Succès
            return response()->json(['message' => 'Votre message a été envoyé avec succès !'], 200);
        } catch (Exception $e) {
            // Échec
            // Loggez l'erreur pour la déboguer
            Log::error("Erreur d'envoi PHPMailer: " . $mail->ErrorInfo);

            return response()->json(['error' => "Erreur lors de l'envoi du mail. Veuillez réessayer."], 500);
        }
    }
}
