<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Facades\Log;
use App\Models\User; // Assurez-vous d'importer votre modèle User correct

class SendIdentifiantForgetController extends Controller
{
    // Fonction utilitaire (à placer en dehors des contrôleurs, mais je garde ici pour l'exemple)
    private function generateRandomPassword(int $length = 10): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $password;
    }

    public function sendIdentifiants(Request $request)
    {
        // Validation de l'email fourni par le front-end
        $request->validate([
            'user_login_email' => 'required|email|max:255',
        ]);

        $userLoginEmail = $request->input('user_login_email');

        try {
            // RECHERCHE DE L'UTILISATEUR dans la table users du model User focement
            $user = User::where('email', $userLoginEmail)->first();

            if (!$user) {
                // IMPORTANT : Pour des raisons de sécurité, on indique pas si l'utilisateur existe.
                // On log l'échec, mais on renvoie un message générique de succès pour ne pas donner d'indices aux attaquants.
                Log::warning("Tentative de récupération de mot de passe pour un email non trouvé: " . $userLoginEmail);
                return response()->json([
                    'success' => true,
                    'message' => 'Si l\'adresse mail existe, un nouveau mot de passe vous a été envoyé.'
                ], 200);
            }

            // GÉNÉRATION ET MISE À JOUR DU MOT DE PASSE
            $newRawPassword = $this->generateRandomPassword(10); // Génère le nouveau mot de passe brut
            $user->password = $newRawPassword; // le models soccupe de le hasher
            $user->save(); // Sauvegarde le nouveau mot de passe haché en bdd

            // ENVOI DE L'EMAIL (avec le mot de passe BRUT pour la connexion)
            $this->sendLogin($userLoginEmail, $userLoginEmail, $newRawPassword);

            return response()->json([
                'success' => true,
                'message' => 'Un nouveau mot de passe a été envoyé à votre adresse e-mail.',
            ], 200);
        } catch (\Exception $e) {
            Log::error("Échec du processus d'envoi d'e-mail ou de BDD: " . $e->getMessage());
            return response()->json([
                'error' => "Erreur interne lors de la réinitialisation du mot de passe.",
                'details' => $e->getMessage() // À masquer en production
            ], 500);
        }
    }

    // ==========================================================
    // LOGIQUE PHPMailer 
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

    protected function sendLogin(string $toEmail, string $login, string $password): void
    {
        $subject = "Identifiant Mot de passe oublié - Portal Job";
        $body = "
            Bonjour,<br><br>
            Vous nous avez fait une demande de mot de passe oublié.<br>
            Voici des nouveaux identifiants de connexion pour votre compte :<br><br>
            <strong>Email (le même) :</strong> {$login}<br>
            <strong>Mot de passe :</strong> {$password}<br><br>
            Je vous invite à vous rendre sur votre page profil afin de modifier celui-ci !<br><br>
            Cordialement,<br><br>L'équipe Portal Job.
        ";
        $this->sendSmtpMail($toEmail, $subject, $body);
    }
}
