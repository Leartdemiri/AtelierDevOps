<?php

// Inclusion de l'autoload de Composer pour charger les dépendances
require '../vendor/autoload.php';
define("API_KEY", "fca_live_ns1BNzLhXFPsG7LPhY1pYVPHKvrnkNo8nO0un0y8");

// Importation des classes nécessaires de Monolog pour la journalisation
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Démarrage de la session pour gérer les requêtes utilisateur
session_start();

/**
 * Class CurrencyConverter
 * Gère le processus de conversion des devises
 */
class CurrencyConverter
{
    private $log;
    private $apiKey;
    private $currencies;
    private $maxRequests;
    private $timeFrame;
    private $ip;
    
    public function __construct()
    {
        // Initialisation des variables
        $this->apiKey = API_KEY;
        $this->currencies = ['USD', 'EUR', 'CAD', 'CHF', 'GBP'];
        $this->maxRequests = 5;
        $this->timeFrame = 60;
        $this->ip = $_SERVER['REMOTE_ADDR'];

        // Création du logger pour l'enregistrement des événements
        $this->log = new Logger('currency_exchange');
        $this->log->pushHandler(new StreamHandler(__DIR__ . '/logs/exchange.log', Logger::INFO));
    }

    /**
     * Vérifie et limite les requêtes
     * @return bool True si la limite est atteinte, false sinon
     */
    public function checkRequestLimit()
    {
        // Initialisation du journal des requêtes dans la session si non défini
        if (!isset($_SESSION['request_log'])) {
            $_SESSION['request_log'] = [];
        }

        // Suppression des anciennes requêtes en dehors de la fenêtre de temps définie
        $_SESSION['request_log'] = array_filter($_SESSION['request_log'], function ($timestamp) {
            return $timestamp > time() - $this->timeFrame;
        });

        // Vérification du respect de la limite de requêtes
        if (count($_SESSION['request_log']) >= $this->maxRequests) {
            $this->log->warning("Limite de requêtes dépassée par l'IP : $this->ip");
            return true;
        }
        return false;
    }

    /**
     * Effectue la conversion des devises
     * @param float $amount Montant à convertir
     * @param string $fromCurrency Devise de départ
     * @param string $toCurrency Devise de destination
     * @return string Résultat de la conversion
     */
    public function convertCurrency($amount, $fromCurrency, $toCurrency)
    {
        if (in_array($fromCurrency, $this->currencies) && in_array($toCurrency, $this->currencies) && is_numeric($amount) && $amount > 0) {
            // Construction de l'URL pour récupérer les taux de change
            $url = "https://api.freecurrencyapi.com/v1/latest?apikey=$this->apiKey&currencies=" . implode('%2C', $this->currencies);

            // Appel de l'API et décodage des données JSON
            $response = file_get_contents($url);
            $data = json_decode($response, true);

            // Vérification de la réponse de l'API et récupération du taux de change
            if ($data && isset($data['data'][$fromCurrency]) && isset($data['data'][$toCurrency])) {
                $rate = $data['data'][$toCurrency] / $data['data'][$fromCurrency]; // Calcul du taux de conversion
                $convertedAmount = number_format($amount * $rate, 2); // Conversion et formatage du montant
                $this->log->info("Conversion : $amount $fromCurrency en $convertedAmount $toCurrency avec un taux de $rate");
                return "<h4>$amount $fromCurrency = $convertedAmount $toCurrency</h4>";
            } else {
                $this->log->error("Échec de la récupération des taux de change.");
                return "<h4>Erreur lors de la récupération des taux de change.</h4>";
            }
        } else {
            $this->log->warning("Entrée invalide : amount=$amount, from=$fromCurrency, to=$toCurrency");
            return "<h4>Entrée invalide.</h4>";
        }
    }

    /**
     * Retourne la liste des devises supportées
     * @return array Liste des devises supportées
     */
    public function getSupportedCurrencies()
    {
        return $this->currencies;
    }
}

// Création d'une instance du convertisseur de devises
$converter = new CurrencyConverter();

// Variable pour stocker le résultat de la conversion
$result = '';

// Vérification de la limite de requêtes
if ($converter->checkRequestLimit()) {
    $result = "<h4>Trop de requêtes ! Veuillez patienter avant d'essayer à nouveau.</h4>";
} else {
    // Vérification si le formulaire a été soumis en méthode POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Enregistrement du moment de la requête dans la session
        $_SESSION['request_log'][] = time();

        // Récupération des données du formulaire
        $amount = $_POST['amount'];
        $fromCurrency = $_POST['fromCurrency'];
        $toCurrency = $_POST['toCurrency'];

        // Appel à la méthode de conversion
        $result = $converter->convertCurrency($amount, $fromCurrency, $toCurrency);
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <title>Convertisseur de devises</title>
</head>
<body>
    <div class="container-sm my-5 shadow-lg p-3 mb-5 bg-white rounded">
        <div class="container-fluid">
            <!-- Formulaire pour la conversion des devises -->
            <form method="POST">
                <div class="form-group py-3">
                    <label for="amount">Montant</label>
                    <input type="number" class="form-control" name="amount" placeholder="Entrez un montant" required>
                </div>
                <div class="form-group py-3">
                    <label for="fromCurrency">De</label>
                    <select class="form-control" name="fromCurrency" required>
                        <?php foreach ($converter->getSupportedCurrencies() as $currency) echo "<option value='$currency'>$currency</option>"; ?>
                    </select>
                </div>
                <div class="form-group py-3">
                    <label for="toCurrency">Vers</label>
                    <select class="form-control" name="toCurrency" required>
                        <?php foreach ($converter->getSupportedCurrencies() as $currency) echo "<option value='$currency'>$currency</option>"; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Convertir</button>
            </form>
            <!-- Affichage du résultat -->
            <div class="mt-3"> <?= $result; ?> </div>
        </div>
        <div class="container my-5">
            <footer class="text-center text-lg-start">  
                <div class="text-center p-3">
                    © 2025 Currency Exchange - Leart Demiri - Louis Robinson-Paris - Timoléon Hede 
                </div>
            </footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>