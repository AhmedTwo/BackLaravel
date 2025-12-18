<?php

// Déclare l'espace de noms (namespace) du contrôleur, essentiel pour le fonctionnement de Laravel.
// Cela permet d'organiser le code et d'éviter les conflits de noms.
namespace App\Http\Controllers;

// Importations des classes nécessaires (on apl sa les dépendances)
use Illuminate\Http\Request; // Pour gérer les requêtes HTTP entrantes (données envoyées par l'utilisateur).
use Illuminate\Support\Facades\Log; // Pour enregistrer des messages d'information ou d'erreur dans les logs de Laravel.
use PHPMailer\PHPMailer\PHPMailer; // Bibliothèque externe pour envoyer des e-mails via SMTP (comme Gmail).
use PHPMailer\PHPMailer\Exception; // Classe d'exception de PHPMailer pour gérer les erreurs d'envoi.

// Importations des Modèles Eloquent (classes qui interagissent avec les tables de la bdd)
use App\Models\Offer;  // Modèle pour interagir avec la table 'offers' (les offres d'emploi).
use App\Models\Apply;  // Modèle pour interagir avec la table 'applys' (les candidatures).
use App\Models\User; // Modèle pour interagir avec la table 'users' (les candidats).
use App\Models\Company; // Modèle pour interagir avec la table 'companies' (les entreprises).

// Définition de la classe du contrôleur. C'est ici qu'on définies les actions
// (des méthodes) qui répondent aux requêtes HTTP (par exemple, un clic sur un bouton "Postuler").
class ApplyOfferController extends Controller
{
    /**
     * je dois gérer la candidature à une offre (méthode principale du contrôleur):
     * 1. crée la candidature en base de données (table `applys`).
     * 2. incrémenter le compteur de participants sur l'offre (champ `participants_count` de la table `offers`).
     * 3. Envoie un mail de confirmation au candidat (méthode `sendMailToCandidate`).
     * 4. Envoie un mail de notification à l'entreprise (méthode `sendMailToCompany`).
     */

    public function sendSummaryOffer(Request $request)
    {
        // --- Étape 1: Validation et Préparation ---

        // Validation des données entrantes de la requête.
        // On s'assure que 'offer_id' est présent, est un entier, et existe bien dans la table 'offers'.
        $request->validate([
            'offer_id' => 'required|integer|exists:offers,id',
            'motivation_text' => 'required|string|max:5000',
        ]);

        try {
            // Bloc `try...catch` pour intercepter toute erreur durant le processus (DB, Email, etc.).

            // on recup l'utilisateur actuellement connecté (authentifié) via la requête.
            $user = $request->user();
            if (!$user) {
                // Si aucun utilisateur n'est connecté, renvoyer une erreur 401 (Non autorisé).
                return response()->json(['error' => 'Utilisateur non authentifié.'], 401);
            }

            // on recup l'objet Offer correspondant à l'ID envoyé dans la requête.
            // `findOrFail` renvoie une erreur 404 si l'offre n'existe pas.
            $offer = Offer::findOrFail($request->offer_id);
            // on recup l'objet Company associé à cette offre (via une relation Eloquent).
            $company = $offer->company; // Assumant qu'il existe une relation `company()` définie dans le modèle `Offer`.

            // Vérifier si l'utilisateur a déjà postulé (prévention du double-postulat).
            $existingApply = Apply::where('offer_id', $offer->id) // Cherche dans la table `applys` (via le Modèle `Apply`)
                ->where('user_id', $user->id) // une ligne pour cette offre ET cet utilisateur.
                ->first(); // Récupère la première ligne trouvée (s'il y en a une).

            if ($existingApply) {
                // Si une candidature existe déjà, renvoyer un message d'erreur 409 (Conflit).
                return response()->json(['message' => 'Vous avez déjà postulé à cette offre.'], 409);
            }

            // --- Étape 2: Création et Mise à jour de la Base de Données ---

            // Création de la nouvelle candidature dans la table `applys`.
            $apply = Apply::create([
                'offer_id' => $offer->id, // L'ID de l'offre.
                'user_id' => $user->id, // L'ID du candidat.
                'status' => 'pending', // Statut initial par défaut de la candidature.
                'motivation_text' => $request->motivation_text, // Récupération de la donnée validée
            ]);

            Log::info('Motivation stockée : ' . $apply->motivation_text);

            // Incrémentation du champ `participants_count` de l'offre de +1.
            $offer->increment('participants_count');

            // Enregistrement d'une trace dans le log pour indiquer le succès de la candidature.
            Log::info("Candidature réussie pour l'offre {$offer->id} par l'utilisateur {$user->id}.");

            // --- Étape 3: Envoi des E-mails ---

            // Appel de la méthode pour envoyer l'e-mail de confirmation au candidat.
            $this->sendMailToCandidate($user, $offer);

            // Appel de la méthode pour envoyer l'e-mail de notification à l'entreprise.
            $this->sendMailToCompany($user, $offer, $company, $apply);

            // --- Étape 4: Réponse HTTP de Succès ---
            // Renvoyer une réponse JSON de succès (code 200 OK) avec un message et l'ID de la nouvelle candidature.
            return response()->json([
                'success' => true,
                'message' => 'Candidature enregistrée et Emails de notification envoyés.',
                'apply_id' => $apply->id // L'ID pour le suivi côté client si nécessaire.
            ], 200);
        } catch (Exception $e) {
            // En cas d'erreur (DB, E-mail, etc.), enregistrement dans le log.
            Log::error("Échec de la candidature ou de l'envoi d'email : " . $e->getMessage());
            // Renvoyer une réponse JSON d'erreur (code 500 Erreur interne du serveur).
            return response()->json([
                'error' => "Une erreur est survenue lors du traitement de votre candidature. Veuillez réessayer."
            ], 500);
        }
    }

    // ==========================================================
    // LOGIQUE PHPMailer (Méthodes pour l'envoi d'e-mails)
    // ==========================================================

    /**
     * Envoie un e-mail via SMTP en utilisant la bibliothèque PHPMailer.
     * C'est la méthode technique de bas niveau pour l'envoi.
     * @return bool Vrai si l'envoi a réussi, Faux sinon.
     */
    protected function sendSmtpMail(string $toEmail, string $subject, string $body, ?string $attachmentPath = null): bool
    {
        // Instanciation de la classe PHPMailer. `true` active les exceptions.
        $mail = new PHPMailer(true);
        $mail->SMTPDebug = 0; // ca va écrire exactement pourquoi l’email ne part pas dans storage/logs/laravel.log.

        // Récupération des identifiants SMTP à partir des variables d'environnement (.env).
        $auth_email = env('MAIL_USERNAME');
        $auth_password = env('MAIL_PASSWORD');

        // verifier dans les logs les infos du fichier .env
        Log::info('MAIL_USERNAME récupéré : ' . env('MAIL_USERNAME'));
        Log::info('MAIL_PASSWORD récupéré : ' . (empty(env('MAIL_PASSWORD')) ? 'VIDE' : 'PRÉSENT')); // Masquer le mot de passe réel du log

        if (empty($auth_email) || empty($auth_password)) {
            Log::error("Erreur de configuration SMTP: MAIL_USERNAME ou MAIL_PASSWORD non défini.");
            return false;
        }

        try {
            // Configuration générale du serveur SMTP
            $mail->isSMTP(); // Utiliser le protocole SMTP.
            $mail->Host = env('MAIL_HOST', 'smtp.gmail.com'); // Adresse du serveur SMTP (par défaut Gmail).
            $mail->SMTPAuth = true; // Activer l'authentification SMTP.
            $mail->Username = $auth_email; // Nom d'utilisateur (votre e-mail).
            $mail->Password = $auth_password; // Mot de passe (ou mot de passe d'application).
            // Utilise la configuration du .env (tls sur 587)
            $mail->SMTPSecure = env('MAIL_ENCRYPTION') === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int)env('MAIL_PORT', 587); // Convertit en entier
            $mail->CharSet = 'UTF-8'; // Encodage des caractères.

            // Définition de l'expéditeur.
            $mail->setFrom($auth_email, env('MAIL_FROM_NAME', 'Portal_Job')); // L'e-mail et le nom affiché de l'expéditeur.

            // Définition du destinataire.
            $mail->addAddress($toEmail);

            // Définition du contenu de l'e-mail.
            $mail->isHTML(true); // Le corps du message est en HTML.
            $mail->Subject = $subject; // Sujet de l'e-mail.
            $mail->Body = $body; // Corps du message (HTML).
            $mail->AltBody = strip_tags($body); // Version texte simple du corps (pour les clients qui n'affichent pas le HTML).

            Log::info("PHPMailer prêt à envoyer à: " . $toEmail);

            if ($attachmentPath && file_exists($attachmentPath)) {
                $mail->addAttachment($attachmentPath, 'CV_' . time() . '.pdf');
            }

            // Envoi de l'e-mail.
            $mail->send();
            return true;
        } catch (Exception $e) {
            // Gestion de l'erreur PHPMailer.
            Log::error("Erreur PHPMailer lors de l'envoi à {$toEmail}: " . $mail->ErrorInfo);
            return false;
        }
    }

    /**
     * Construit et envoie l'e-mail de confirmation au candidat après la candidature.
     */
    protected function sendMailToCandidate($user, $offer)
    {

        // --- LOG ---
        Log::info("Tentative d'envoi au candidat : " . $user->email);
        // ----------------------

        $subject = "✅ Confirmation : Votre candidature à l'offre {$offer->title}";
        $body = "
        Bonjour {$user->prenom},<br><br>
        Nous vous confirmons la réception de votre candidature pour l'offre suivante :<br><br>
        <strong>Titre de l'offre :</strong> {$offer->title}<br>
        <strong>Société :</strong> {$offer->company->name}<br>
        <strong>Date de candidature :</strong> " . now()->format('d/m/Y H:i') . "<br><br>
        Votre dossier est en cours d'examen. Vous serez contacté(e) directement par l'entreprise si votre profil est retenu.<br>
        Cordialement,<br><br>L'équipe Portal Job.
        ";

        $result = $this->sendSmtpMail($user->email, $subject, $body);

        Log::info("Mail candidat envoyé ? " . ($result ? 'OUI' : 'NON'));

        return $result;
    }

    /**
     * Construit et envoie l'e-mail de notification à l'entreprise pour l'informer d'une nouvelle candidature.
     */
    protected function sendMailToCompany($user, $offer, $company, $apply)
    {
        $companyEmail = $company->email_company;

        // --- LOG ---
        Log::info("Tentative d'envoi à l'entreprise : " . $companyEmail);
        // ----------------------

        $subject = "🔔 Nouvelle candidature pour l'offre : {$offer->title}";
        $body = "
        Cher recruteur,<br><br>
        Un nouveau candidat a postulé :<br><br>
        <strong>Offre :</strong> {$offer->title}<br>
        <strong>Date de candidature :</strong> " . now()->format('d/m/Y H:i') . "<br><br>
        <strong>Nom :</strong> {$user->prenom} {$user->nom}<br>
        <strong>Qualification :</strong> {$user->qualification}<br>
        <strong>Email :</strong> {$user->email}<br>
        <strong>Téléphone :</strong> {$user->telephone}<br><br>

        <strong>Motivation du candidat :</strong><br> {$apply->motivation_text}<br><br>

        Le <strong>CV</strong> est joint à cet email.<br><br>

        Cordialement,<br>L'équipe Portal Job.
        ";

        $cvPath = storage_path('app/public/' . $user->cv_pdf);

        $result = $this->sendSmtpMail(
            $companyEmail,
            $subject,
            $body,
            $cvPath
        );

        Log::info("Mail entreprise envoyé ? " . ($result ? 'OUI' : 'NON'));

        return $result;
    }
}
