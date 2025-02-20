<?php
// Inclusion de l'autoload de Composer pour charger les dépendances
require '../vendor/autoload.php';

// Importation des classes nécessaires de Monolog pour la journalisation
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Démarrage de la session pour gérer les requêtes utilisateur
session_start();

// Création d'un logger pour enregistrer les événements liés à la conversion
$log = new Logger('currency_exchange');
$log->pushHandler(new StreamHandler(__DIR__.'/logs/exchange.log', Logger::INFO));

// Clé API pour accéder au service de conversion de devises
$apiKey = 'fca_live_ns1BNzLhXFPsG7LPhY1pYVPHKvrnkNo8nO0un0y8';

// Liste des devises supportées
$currencies = ['USD', 'EUR', 'CAD', 'CHF', 'GBP'];

// Variable pour stocker le résultat de la conversion
$result = '';

// Paramètres de limitation du taux de requêtes
$maxRequests = 5; // Nombre maximal de requêtes autorisées
$timeFrame = 60; // Durée de la fenêtre en secondes (60 secondes)
$ip = $_SERVER['REMOTE_ADDR']; // Récupération de l'adresse IP de l'utilisateur

// Initialisation du journal des requêtes dans la session si non défini
if (!isset($_SESSION['request_log'])) {
    $_SESSION['request_log'] = [];
}

// Suppression des anciennes requêtes en dehors de la fenêtre de temps définie
$_SESSION['request_log'] = array_filter($_SESSION['request_log'], function($timestamp) use ($timeFrame) {
    return $timestamp > time() - $timeFrame;
});

// Vérification du respect de la limite de requêtes
if (count($_SESSION['request_log']) >= $maxRequests) {
    $result = "<h4>Trop de requêtes ! Veuillez patienter avant d'essayer à nouveau.</h4>";
    $log->warning("Limite de requêtes dépassée par l'IP : $ip");
} else {
    // Vérification si le formulaire a été soumis en méthode POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Enregistrement du moment de la requête dans la session
        $_SESSION['request_log'][] = time();
        
        // Récupération des données du formulaire
        $amount = $_POST['amount'];
        $fromCurrency = $_POST['fromCurrency'];
        $toCurrency = $_POST['toCurrency'];

        // Vérification des entrées : validité des devises et de la somme
        if (in_array($fromCurrency, $currencies) && in_array($toCurrency, $currencies) && is_numeric($amount) && $amount > 0) {
            // Construction de l'URL pour récupérer les taux de change
            $url = "https://api.freecurrencyapi.com/v1/latest?apikey=$apiKey&currencies=" . implode('%2C', $currencies);
            
            // Appel de l'API et décodage des données JSON
            $response = file_get_contents($url);
            $data = json_decode($response, true);
            
            // Vérification de la réponse de l'API et récupération du taux de change
            if ($data && isset($data['data'][$fromCurrency]) && isset($data['data'][$toCurrency])) {
                $rate = $data['data'][$toCurrency] / $data['data'][$fromCurrency]; // Calcul du taux de conversion
                $convertedAmount = number_format($amount * $rate, 2); // Conversion et formatage du montant
                $result = "<h4>$amount $fromCurrency = $convertedAmount $toCurrency</h4>";
                $log->info("Conversion : $amount $fromCurrency en $convertedAmount $toCurrency avec un taux de $rate");
            } else {
                $result = "<h4>Erreur lors de la récupération des taux de change.</h4>";
                $log->error("Échec de la récupération des taux de change.");
            }
        } else {
            $result = "<h4>Entrée invalide.</h4>";
            $log->warning("Entrée invalide : amount=$amount, from=$fromCurrency, to=$toCurrency");
        }
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
                        <?php foreach ($currencies as $currency) echo "<option value='$currency'>$currency</option>"; ?>
                    </select>
                </div>
                <div class="form-group py-3">
                    <label for="toCurrency">Vers</label>
                    <select class="form-control" name="toCurrency" required>
                        <?php foreach ($currencies as $currency) echo "<option value='$currency'>$currency</option>"; ?>
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

