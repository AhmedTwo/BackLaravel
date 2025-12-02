<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Facades\Log;

class SendIdentifiantCompanyController extends Controller
{
    /**
     * Gère l'envoi du double email après l'inscription d'une société/utilisateur.
     * Les données nécessaires (email company, login user, password) sont passées
     * directement par la requête front-end après le succès de addUser.
     */
    public function sendIdentifiants(Request $request)
    {
        // Validation des données critiques pour l'envoi
        $request->validate([
            'company_name' => 'required|string|max:255',
            'company_email' => 'required|email|max:255',
            'user_login_email' => 'required|email|max:255',
            'user_raw_password' => 'required|string|min:8|max:255', // Mot de passe non haché
            'user_full_name' => 'required|string|max:255',
        ]);

        $companyName = $request->input('company_name');
        $companyEmail = $request->input('company_email');
        $userLoginEmail = $request->input('user_login_email');
        $userRawPassword = $request->input('user_raw_password');
        $userFullName = $request->input('user_full_name');

        try {
            // --- ENVOI 1 : À LA SOCIÉTÉ (Identifiants de connexion) ---
            $this->sendLoginCredentialsMail($companyEmail, $userLoginEmail, $userRawPassword);

            // --- ENVOI 2 : À L'ADMIN (Notification d'inscription) ---
            $this->sendAdminNotificationMail($userLoginEmail, $userFullName, $companyName, $companyEmail);

            return response()->json([
                'success' => true,
                'message' => 'E-mails de notification envoyés avec succès.',
            ], 200);
        } catch (\Exception $e) {
            Log::error("Échec du processus d'envoi d'e-mail suite à l'inscription de {$companyName} : " . $e->getMessage());
            return response()->json([
                'error' => "Erreur lors de l'envoi des e-mails de notification.",
                'details' => $e->getMessage() // Retirer en production
            ], 500);
        }
    }

    // ==========================================================
    // LOGIQUE PHPMailer (Harmonisation avec .env)
    // ==========================================================

    protected function sendSmtpMail(string $toEmail, string $subject, string $body): bool
    {
        $mail = new PHPMailer(true);

        $auth_email = env('MAIL_USERNAME');
        $auth_password = env('MAIL_PASSWORD');

        if (empty($auth_email) || empty($auth_password)) {
            Log::error("Erreur de configuration SMTP: MAIL_USERNAME ou MAIL_PASSWORD non défini.");
            return false;
        }

        try {
            // Configuration SMTP (Votre configuration Gmail)
            $mail->isSMTP();
            $mail->Host = env('MAIL_HOST', 'smtp.gmail.com');
            $mail->SMTPAuth = true;
            $mail->Username = $auth_email;
            $mail->Password = $auth_password;
            // Utilise la configuration du .env (tls sur 587)
            $mail->SMTPSecure = env('MAIL_ENCRYPTION') === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int)env('MAIL_PORT', 587); // Convertit en entier
            $mail->CharSet = 'UTF-8';

            // Expéditeur technique (DOIT correspondre à MAIL_USERNAME pour Gmail)
            $mail->setFrom($auth_email, env('MAIL_FROM_NAME', 'Portal Job Administration'));

            // Destinataire
            $mail->addAddress($toEmail);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;

            $mail->send();
            return true;
        } catch (Exception $e) {
            Log::error("Erreur PHPMailer lors de l'envoi à {$toEmail}: " . $mail->ErrorInfo);
            // Nous ne renvoyons pas l'exception, mais False. L'appelant gère l'échec.
            return false;
        }
    }

    protected function sendLoginCredentialsMail(string $toEmail, string $login, string $password): void
    {
        $subject = "Vos identifiants de connexion - Portal Job";
        $body = "
            Bonjour,<br><br>
            Nous sommes heureux de vous compter parmi nous chez Portal Job.<br>
            Voici les identifiants de connexion de votre compte administrateur société :<br><br>
            <strong>Email :</strong> {$login}<br>
            <strong>Mot de passe :</strong> {$password}<br><br>
            Je vous invite à le modifier depuis votre profil.<br><br>
            Cordialement,<br><br>L'équipe Portal Job.
        ";
        $this->sendSmtpMail($toEmail, $subject, $body);
    }

    protected function sendAdminNotificationMail(string $userLoginEmail, string $userFullName, string $companyName, string $companyEmail): void
    {
        // Votre adresse de réception (MAIL_FROM_ADDRESS est souvent utilisé pour l'admin)
        $adminEmail = env('MAIL_FROM_ADDRESS', 'seghiriahmed9@gmail.com');
        $subject = "✅ Nouvelle inscription Société : {$companyName}";

        $body = "
            Un nouvel administrateur société a été ajouté :<br><br>
            Société : <strong>{$companyName}</strong> (Contact E-mail: {$companyEmail})<br>
            Nom/Prénom de l'utilisateur : {$userFullName}<br>
            Email de connexion généré : {$userLoginEmail}<br><br>
            Veuillez procéder à la vérification des informations de cette nouvelle société.
        ";
        $this->sendSmtpMail($adminEmail, $subject, $body);
    }
}
